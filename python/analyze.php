<script>
let cameraOpen = false;
let stream = null;
let startTime = Date.now();

function autoCheck() {
  const elapsed = (Date.now() - startTime) / 1000;
  if (!cameraOpen && elapsed >= 300) {
    openCamera();
  }
}

function openCamera() {
  navigator.mediaDevices.getUserMedia({ video: true }).then(s => {
    stream = s;
    document.getElementById('video').srcObject = stream;
    document.getElementById('moodTrackerBox').style.display = 'block';
    cameraOpen = true;
    detectEmotionLoop();
  });
}

function closeCamera() {
  if (stream) stream.getTracks().forEach(t => t.stop());
  stream = null;
  cameraOpen = false;
  document.getElementById('moodTrackerBox').style.display = 'none';
}

document.getElementById('doneBtn').onclick = () => {
  closeCamera();
};
document.getElementById('toggleBtn').onclick = () => {
  cameraOpen ? closeCamera() : openCamera();
};

function detectEmotionLoop() {
  const canvas = document.getElementById('canvas');
  const video = document.getElementById('video');
  const ctx = canvas.getContext('2d');

  setInterval(() => {
    if (!cameraOpen) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imgData = canvas.toDataURL('image/jpeg');

    fetch('result.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ image: imgData })
    })
    .then(res => res.json())
    .then(data => {
      document.getElementById('emotionResult').innerText = 'Detected: ' + data.emotion;
    });
  }, 5000);
}

setInterval(autoCheck, 10000); // check every 10s
</script>
