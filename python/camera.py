# Requirements:
#   pip install flask flask-cors opencv-python requests
#   pip install deepface  # optional, for emotion detection

from flask import Flask, Response, jsonify, request, stream_with_context
from flask_cors import CORS
import cv2
import threading
import time
import requests
import json
import os
from collections import deque

try:
    from deepface import DeepFace  # optional
    deepface_available = True
except ImportError:
    deepface_available = False

app = Flask(__name__)
CORS(app)

NO_FACE_THRESHOLD_SECONDS = int(os.environ.get('NO_FACE_THRESHOLD', 10))
CALLBACK_URL = None  # set to e.g. "http://yourserver/notify"
SAMPLE_EMOTION_EVERY = 30  # analyze emotion every N frames

camera = cv2.VideoCapture(0)
if not camera.isOpened():
    raise RuntimeError("Could not open camera device (0).")

haar_path = cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
face_cascade = cv2.CascadeClassifier(haar_path)

_state_lock = threading.Lock()
last_face_time = 0.0
face_present = False
no_face_sent = False
last_emotion = "unknown"
frame_counter = 0

clients = set()

def notify_callback(payload):
    global CALLBACK_URL
    if not CALLBACK_URL:
        return
    try:
        requests.post(CALLBACK_URL, json=payload, timeout=3)
    except Exception as e:
        app.logger.exception("Callback POST failed: %s", e)

def send_sse_message(msg):
    dead = []
    for q in clients:
        try:
            q.append(msg)
        except Exception:
            dead.append(q)
    for d in dead:
        try:
            clients.remove(d)
        except Exception:
            pass

def analyze_emotion(frame):
    global last_emotion
    if not deepface_available:
        last_emotion = "unavailable"
        return
    try:
        rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        res = DeepFace.analyze(rgb, actions=['emotion'], enforce_detection=False)
        if isinstance(res, list) and res:
            res = res[0]
        dominant = res.get("dominant_emotion") if isinstance(res, dict) else None
        if not dominant and isinstance(res.get("emotion"), dict):
            dominant = max(res['emotion'].items(), key=lambda x: x[1])[0]
        last_emotion = str(dominant).lower() if dominant else "undetected"
    except Exception as e:
        last_emotion = "error"
        app.logger.debug("Emotion analysis failed: %s", e)

def gen_frames():
    global last_face_time, face_present, no_face_sent, frame_counter
    while True:
        success, frame = camera.read()
        if not success:
            time.sleep(0.1)
            continue

        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(60,60))
        now = time.time()

        with _state_lock:
            if len(faces) > 0:
                last_face_time = now
                if not face_present:
                    face_present = True
                    no_face_sent = False
                    payload = {"event": "face_detected", "timestamp": now}
                    send_sse_message(json.dumps(payload))
                    threading.Thread(target=notify_callback, args=(payload,), daemon=True).start()
            else:
                if face_present and (now - last_face_time) >= NO_FACE_THRESHOLD_SECONDS:
                    face_present = False
                    if not no_face_sent:
                        no_face_sent = True
                        payload = {"event": "no_face", "timestamp": now, "no_face_seconds": int(now - last_face_time)}
                        send_sse_message(json.dumps(payload))
                        threading.Thread(target=notify_callback, args=(payload,), daemon=True).start()

        frame_counter += 1
        if frame_counter % SAMPLE_EMOTION_EVERY == 0:
            copy_frame = frame.copy()
            threading.Thread(target=analyze_emotion, args=(copy_frame,), daemon=True).start()

        try:
            for (x,y,w,h) in faces:
                cv2.rectangle(frame, (x,y), (x+w, y+h), (0,255,0), 2)
            status_text = "Face: YES" if face_present else "Face: NO"
            cv2.putText(frame, status_text, (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0,255,0), 2)
            cv2.putText(frame, f"Emotion: {last_emotion}", (10,60), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255,255,255), 2)
            _, buffer = cv2.imencode('.jpg', frame)
            frame_bytes = buffer.tobytes()
            yield (b'--frame\r\nContent-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
        except Exception as e:
            app.logger.debug("Frame encoding failed: %s", e)
            continue

@app.route('/video_feed')
def video_feed():
    return Response(gen_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/status', methods=['GET'])
def status():
    with _state_lock:
        now = time.time()
        last = last_face_time
        return jsonify({
            "face_present": face_present,
            "last_face_seen": last,
            "no_face_duration": round(now - last, 2),
            "last_emotion": last_emotion
        })

@app.route('/current_emotion', methods=['GET'])
def current_emotion():
    with _state_lock:
        return jsonify({"emotion": last_emotion})

@app.route('/reset', methods=['POST'])
def reset_state():
    global last_face_time, face_present, no_face_sent, last_emotion
    with _state_lock:
        last_face_time = 0.0
        face_present = False
        no_face_sent = False
        last_emotion = "unknown"
    return jsonify({"status":"reset"})

@app.route('/events')
def sse_events():
    q = deque()
    clients.add(q)
    def stream():
        try:
            with _state_lock:
                initial = {"event":"initial", "face_present": face_present, "last_emotion": last_emotion, "timestamp": time.time()}
            yield f"data: {json.dumps(initial)}\n\n"
            while True:
                if q:
                    msg = q.popleft()
                    yield f"data: {msg}\n\n"
                else:
                    time.sleep(0.2)
        except GeneratorExit:
            try:
                clients.remove(q)
            except Exception:
                pass
    return Response(stream_with_context(stream()), mimetype='text/event-stream')

@app.route('/set_callback', methods=['POST'])
def set_callback():
    global CALLBACK_URL
    body = request.get_json(force=True)
    url = body.get('callback_url')
    if not url:
        return jsonify({"error":"callback_url required"}), 400
    CALLBACK_URL = url
    return jsonify({"status":"callback_set", "callback_url": CALLBACK_URL})

if __name__ == '__main__':
    import atexit
    def cleanup():
        if camera.isOpened():
            camera.release()
    atexit.register(cleanup)
    app.run(host='0.0.0.0', port=5000, threaded=True)