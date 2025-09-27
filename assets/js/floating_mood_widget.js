(function(){
  'use strict';

  if (window.__MC_FLOATING_CAMERA__) return; // prevent double-init
  window.__MC_FLOATING_CAMERA__ = true;

  const PY_HOST = 'http://localhost:5000';

  // Compute app base path like /MindCare-AI so we can call PHP endpoints from any page
  function getAppBasePath() {
    const path = window.location.pathname;
    const m = path.match(/\/MindCare-AI(\/|$)/);
    if (m && m.index !== undefined) {
      return path.slice(0, m.index) + '/MindCare-AI';
    }
    // Fallback: try to infer from <base> or assume current directory
    const baseEl = document.querySelector('base[href]');
    if (baseEl) {
      try { return new URL(baseEl.getAttribute('href'), window.location.origin).pathname.replace(/\/$/, ''); } catch(e) {}
    }
    return '';
  }
  const APP_BASE = getAppBasePath();
  const SAVE_MOOD_URL = APP_BASE + '/moodtracker/save_mood.php';

  // Inject styles
  const style = document.createElement('style');
  style.textContent = `
    .mcw-wrap { position: fixed; right: 16px; bottom: 16px; width: 280px; height: 220px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 30px rgba(17,24,39,0.18); z-index: 2147483000; display: flex; flex-direction: column; overflow: hidden; }
    .mcw-wrap.mcw-max { width: min(720px, 94vw); height: min(540px, 78vh); right: 3vw; bottom: 3vh; }
    .mcw-head { display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; background: linear-gradient(180deg,#f9fafb,#eef2ff); border-bottom: 1px solid #e5e7eb; cursor: move; user-select: none; }
    .mcw-title { display:flex; align-items:center; gap:8px; font: 600 13px/1.2 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:#111827; }
    .mcw-title .dot { width:8px; height:8px; background:#6366f1; border-radius:999px; box-shadow: 0 0 0 4px rgba(99,102,241,0.18); }
    .mcw-ctrls { display:flex; align-items:center; gap:6px; }
    .mcw-btn { background:#fff; border:1px solid #d1d5db; color:#374151; padding: 4px 8px; border-radius: 8px; font-size: 12px; cursor: pointer; }
    .mcw-btn:hover { background:#f3f4f6; }
    .mcw-body { position: relative; flex: 1; background: #000; }
    .mcw-body img { position:absolute; inset:0; width:100%; height:100%; object-fit: cover; }
    .mcw-badge { position: absolute; left:8px; top:8px; background: rgba(17,24,39,0.75); color:#fff; font: 600 12px/1 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 6px 8px; border-radius: 999px; }

    /* Modal */
    .mcw-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 2147483600; display:none; align-items:center; justify-content:center; }
    .mcw-modal { width: min(92vw, 520px); background:#fff; border-radius: 14px; border:1px solid #e5e7eb; box-shadow: 0 30px 60px rgba(17,24,39,0.25); overflow: hidden; }
    .mcw-modal-hd { padding: 12px 14px; display:flex; align-items:center; justify-content:space-between; background: linear-gradient(180deg,#f9fafb,#eef2ff); border-bottom: 1px solid #e5e7eb; }
    .mcw-modal-ttl { font: 700 16px/1.2 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:#111827; }
    .mcw-modal-close { background: #fff; border:1px solid #d1d5db; color:#374151; padding:6px 10px; border-radius: 8px; font-size: 12px; cursor: pointer; }
    .mcw-modal-bd { padding: 14px; color:#111827; font: 500 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .mcw-pills { display:flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 12px; }
    .mcw-pill { border-radius: 999px; border: 1px solid #e5e7eb; padding: 6px 10px; font-size:12px; background:#f9fafb; }
    .mcw-pill.dom { background: #dcfce7; border-color: #86efac; color: #064e3b; font-weight:700; }
    .mcw-muted { color:#6b7280; font-weight:500; }
  `;
  document.head.appendChild(style);

  // Build modal once
  const modalBackdrop = document.createElement('div');
  modalBackdrop.className = 'mcw-modal-backdrop';
  modalBackdrop.innerHTML = `
    <div class="mcw-modal" role="dialog" aria-modal="true" aria-labelledby="mcwModalTitle">
      <div class="mcw-modal-hd">
        <div id="mcwModalTitle" class="mcw-modal-ttl">Mood Update</div>
        <button type="button" class="mcw-modal-close" aria-label="Close">Close</button>
      </div>
      <div class="mcw-modal-bd">
        <div id="mcwModalContent">Analyzing your emotions...</div>
      </div>
    </div>`;
  document.body.appendChild(modalBackdrop);
  modalBackdrop.querySelector('.mcw-modal-close').addEventListener('click', ()=>{ modalBackdrop.style.display='none'; });
  modalBackdrop.addEventListener('click', (e)=>{ if (e.target === modalBackdrop) modalBackdrop.style.display='none'; });

  function showModal(html) {
    const el = document.getElementById('mcwModalContent');
    if (el) el.innerHTML = html;
    modalBackdrop.style.display = 'flex';
  }

  // Build floating widget
  const wrap = document.createElement('div');
  wrap.className = 'mcw-wrap';
  wrap.setAttribute('role','region');
  wrap.setAttribute('aria-label','Mood tracker camera');
  wrap.innerHTML = `
    <div class="mcw-head">
      <div class="mcw-title"><span class="dot"></span><span>Mood Tracker</span></div>
      <div class="mcw-ctrls">
        <button type="button" class="mcw-btn" data-act="size">Maximize</button>
      </div>
    </div>
    <div class="mcw-body">
      <div class="mcw-badge">Emotion: <span id="mcwCur">Analyzing...</span></div>
      <img id="mcwFeed" alt="Camera Feed" src="${PY_HOST}/video_feed" crossorigin="anonymous" />
    </div>`;
  document.body.appendChild(wrap);

  // Restore persisted state
  try {
    const persisted = JSON.parse(localStorage.getItem('mcw_state') || '{}');
    if (persisted && persisted.max) {
      wrap.classList.add('mcw-max');
      wrap.querySelector('[data-act="size"]').textContent = 'Minimize';
    }
    if (persisted && persisted.pos && !persisted.max) {
      const {x,y} = persisted.pos;
      if (typeof x === 'number' && typeof y === 'number') {
        wrap.style.right = 'auto';
        wrap.style.bottom = 'auto';
        wrap.style.left = Math.max(8, Math.min(window.innerWidth - 80, x)) + 'px';
        wrap.style.top = Math.max(8, Math.min(window.innerHeight - 80, y)) + 'px';
      }
    }
  } catch(_){}

  // Drag move
  (function enableDrag(){
    const head = wrap.querySelector('.mcw-head');
    let startX=0, startY=0, startLeft=0, startTop=0, dragging=false;
    head.addEventListener('mousedown', (e)=>{
      // Don't drag if clicking on a button
      if (e.target.closest('button')) return;
      dragging = true;
      wrap.style.right='auto';
      wrap.style.bottom='auto';
      startX = e.clientX; startY = e.clientY;
      const rect = wrap.getBoundingClientRect();
      startLeft = rect.left; startTop = rect.top;
      document.body.style.userSelect='none';
    });
    window.addEventListener('mousemove', (e)=>{
      if (!dragging || wrap.classList.contains('mcw-max')) return;
      const dx = e.clientX - startX; const dy = e.clientY - startY;
      let nx = Math.max(8, Math.min(window.innerWidth - wrap.offsetWidth - 8, startLeft + dx));
      let ny = Math.max(8, Math.min(window.innerHeight - wrap.offsetHeight - 8, startTop + dy));
      wrap.style.left = nx + 'px';
      wrap.style.top = ny + 'px';
    });
    window.addEventListener('mouseup', ()=>{
      if (!dragging) return; dragging=false; document.body.style.userSelect='';
      try {
        const rect = wrap.getBoundingClientRect();
        const state = JSON.parse(localStorage.getItem('mcw_state') || '{}');
        state.pos = { x: rect.left, y: rect.top };
        localStorage.setItem('mcw_state', JSON.stringify(state));
      } catch(_){}
    });
  })();

  // Maximize/minimize control
  wrap.querySelector('[data-act="size"]').addEventListener('click', ()=>{
    const isMax = wrap.classList.toggle('mcw-max');
    wrap.querySelector('[data-act="size"]').textContent = isMax ? 'Minimize' : 'Maximize';
    try {
      const state = JSON.parse(localStorage.getItem('mcw_state') || '{}');
      state.max = isMax;
      localStorage.setItem('mcw_state', JSON.stringify(state));
    } catch(_){}
  });

  // Update current emotion badge every 2s
  let lastEmotion = 'Analyzing...';
  async function pollCurrentEmotion(){
    try {
      const res = await fetch(PY_HOST + '/current_emotion', { mode: 'cors' });
      if (!res.ok) return;
      const data = await res.json();
      const emo = (data && data.emotion) ? String(data.emotion) : 'neutral';
      if (emo !== lastEmotion) {
        lastEmotion = emo;
        const disp = emo.charAt(0).toUpperCase() + emo.slice(1);
        const el = document.getElementById('mcwCur');
        if (el) el.textContent = disp;
      }
    } catch(_) {}
  }
  setInterval(pollCurrentEmotion, 2000);
  pollCurrentEmotion();

  // Helper to compute dominant emotion from percentages object
  function computeDominant(map) {
    let dom = 'neutral'; let pct = 0;
    if (map && typeof map === 'object') {
      for (const [k,v] of Object.entries(map)) {
        const val = Number(v) || 0;
        if (val > pct) { pct = val; dom = k; }
      }
    }
    return { emotion: dom, percentage: Math.round(pct*100)/100 };
  }

  function formatPills(map, dominantKey) {
    if (!map || typeof map !== 'object') return '<div class="mcw-muted">No data</div>';
    const order = Object.keys(map).sort((a,b)=> (map[b]||0) - (map[a]||0));
    return '<div class="mcw-pills">' + order.map(k=>{
      const cls = (k===dominantKey) ? 'mcw-pill dom' : 'mcw-pill';
      const pct = (Number(map[k])||0).toFixed(2);
      return `<span class="${cls}">${k}: ${pct}%</span>`;
    }).join('') + '</div>';
  }

  async function fiveMinuteTick() {
    // Pull percentages from Python, compute dominant, show modal, save to PHP, then reset Python counters
    let percentages = null;
    try {
      const res = await fetch(PY_HOST + '/emotion_percentages', { mode: 'cors' });
      if (res.ok) percentages = await res.json();
    } catch(_) {}

    const { emotion, percentage } = computeDominant(percentages || {});

    const html = [
      `<div><strong>Dominant emotion:</strong> ${emotion.charAt(0).toUpperCase()+emotion.slice(1)} (${percentage.toFixed(2)}%)</div>`,
      '<div class="mcw-muted">Distribution for the last period:</div>',
      formatPills(percentages || {}, emotion)
    ].join('');

    showModal(html);

    // Persist to backend (session-authenticated)
    try {
      const form = new URLSearchParams();
      form.set('emotion', emotion);
      form.set('percentage', String(percentage));
      form.set('duration', String(300));
      await fetch(SAVE_MOOD_URL, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString(), credentials: 'include' });
    } catch(_) {}

    // Reset python counters to measure next 5-minute window
    try { await fetch(PY_HOST + '/reset_emotions', { method:'POST', mode:'cors' }); } catch(_) {}
  }

  // Start the 5-minute schedule (first popup after 5 minutes)
  setTimeout(()=>{
    fiveMinuteTick();
    setInterval(fiveMinuteTick, 5*60*1000);
  }, 5*60*1000);

})();
