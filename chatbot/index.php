<?php
session_start();
$guest = isset($_GET['guest']) || !isset($_SESSION['user_id']);
$name = $_SESSION['name'] ?? 'Guest';
$user_id = $_SESSION['user_id'] ?? null;
$chat_limit = 5;
$mode = $_GET['mode'] ?? 'basic';
$endpoint = $mode === 'pro' ? 'professional.php' : 'basic.php';

// Use only the first letter of the first name for the avatar
$initial = strtoupper(substr($name, 0, 1) ?: 'G');

// Recent chats for logged-in users
$recentChats = [];
$has_history = false;
$allLogs = [];
if (!$guest && $user_id) {
  require_once '../config/dbcon.php';
  if (isset($conn)) {
    // Sidebar recent list (latest 12)
    $stmt = $conn->prepare("SELECT id, message, reply, mode, timestamp FROM chat_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 12");
    if ($stmt) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        $recentChats[] = $row;
      }
      $stmt->close();
    }
    // Full transcript cache (last 100, ascending for playback)
    $stmt2 = $conn->prepare("SELECT id, message, reply, mode, timestamp FROM chat_logs WHERE user_id = ? ORDER BY timestamp ASC LIMIT 100");
    if ($stmt2) {
      $stmt2->bind_param("i", $user_id);
      $stmt2->execute();
      $res2 = $stmt2->get_result();
      while ($row = $res2->fetch_assoc()) {
        $allLogs[] = $row;
      }
      $stmt2->close();
    }
  }
  $has_history = count($recentChats) > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>MindCare | Chatbot</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --main-bg: #f7f8fc;
      --sidebar-bg: #ffffff;
      --card-bg: #ffffff;
      --user-bubble: #e8f0fe;
      --ai-bubble: #f3f0ff;
      --text-color: #222;
      --muted: #6b7280;
      --primary: #6C63FF;
      --accent: #22c55e;
      --danger: #ef4444;
      --border: #e5e7eb;
    }

    body {
      background: var(--main-bg);
      font-family: 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
      color: var(--text-color);
    }

    .dark-mode {
      --main-bg: #111827;
      --sidebar-bg: #0f172a;
      --card-bg: #111827;
      --user-bubble: #1f2937;
      --ai-bubble: #1e293b;
      --text-color: #e5e7eb;
      --muted: #94a3b8;
      --border: #1f2937;
    }

    .wrapper { display: flex; min-height: 100vh; }

    /* Sidebar */
    .sidebar {
      position: fixed; height: 100vh; width: 300px; background: var(--sidebar-bg);
      border-right: 1px solid var(--border); transition: width 0.25s ease-in-out; z-index: 100;
      display: flex; flex-direction: column;
    }
    .sidebar.collapsed { width: 84px; }

    .sidebar .logo-details {
      height: 64px; display: flex; align-items: center; justify-content: space-between;
      padding: 0 16px; border-bottom: 1px solid var(--border); gap: 10px;
    }
    .brand { display: flex; align-items: center; gap: 10px; }
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: grid; place-items: center; font-weight: 700; }
    .logo_name { font-size: 18px; font-weight: 700; }
    .toggle-btn { border: 0; background: transparent; color: var(--text-color); font-size: 22px; }

    .sidebar .section { padding: 16px; border-bottom: 1px solid var(--border); }
    .sidebar .btn-action { width: 100%; border-radius: 10px; padding: 10px 12px; }

    .search-input { position: relative; }
    .search-input input { padding-left: 36px; }
    .search-input .bi-search { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); }

    .recent-list { max-height: calc(100vh - 310px); overflow-y: auto; margin: 0; padding: 0; list-style: none; }
    .recent-item { padding: 10px 12px; border-radius: 10px; transition: background 0.15s ease; cursor: pointer; }
    .recent-item:hover { background: rgba(108, 99, 255, 0.08); }
    .recent-item .title { font-size: 14px; line-height: 1.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .recent-item .meta { font-size: 12px; color: var(--muted); }
    .badge-mode { font-size: 10px; }
    .recent-actions { display: flex; align-items: center; gap: 6px; }
    .btn-recent-delete { border: 0; background: transparent; color: var(--muted); padding: 4px; border-radius: 6px; }
    .btn-recent-delete:hover { color: var(--text-color); background: rgba(0,0,0,0.05); }

    .sidebar.collapsed .hide-when-collapsed { display: none; }

    .user-section { padding: 16px; border-top: 1px solid var(--border); margin-top: auto; }
    .user-details { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 12px; border-radius: 10px; transition: background 0.15s ease; }
    .user-details:hover { background: rgba(108, 99, 255, 0.08); }
    .user-name { font-size: 16px; font-weight: 500; }
    .logout-menu { display: none; position: absolute; bottom: 70px; left: 16px; width: 268px; background: var(--sidebar-bg); border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 101; }
    .logout-menu.show { display: block; }
    .logout-menu a { display: block; padding: 10px 12px; color: var(--text-color); text-decoration: none; }
    .logout-menu a:hover { background: rgba(108, 99, 255, 0.08); }
    .sidebar.collapsed .user-section .hide-when-collapsed { display: none; }

    /* Main */
    .main-content { margin-left: 300px; flex: 1; padding: 24px; transition: margin-left 0.25s ease-in-out; }
    .sidebar.collapsed + .main-content { margin-left: 84px; }

    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .page-title { font-weight: 800; }

    .referral-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 14px; margin-bottom: 16px; }
    .referral-card .form-select { border-radius: 10px; }

    #chatbox { height: 56vh; min-height: 420px; overflow-y: auto; background: var(--card-bg); border: 1px solid var(--border); padding: 18px; border-radius: 16px; box-shadow: 0 2px 16px rgba(17,24,39,0.05); display: flex; flex-direction: column; gap: 10px; }

    .chat-row { display: flex; align-items: flex-end; gap: 10px; }
    .chat-bubble { padding: 12px 14px; border-radius: 16px; max-width: 78%; }
    .chat-bubble.user { margin-left: auto; background: var(--user-bubble); }
    .chat-bubble.ai { background: var(--ai-bubble); display: flex; align-items: flex-start; gap: 10px; }
    .chat-bubble.ai.important {
      border: 2px solid var(--primary);
      background: var(--ai-bubble);
      box-shadow: 0 4px 12px rgba(108, 99, 255, 0.2);
    }

    .ai-avatar { width: 32px; height: 32px; background: var(--primary); color: #fff; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }

    .composer { display: flex; gap: 10px; margin-top: 12px; }
    .composer .form-control { border-radius: 12px; }
    .composer .btn { border-radius: 12px; }

    .user .msg-actions { text-align: right; margin-top: 4px; }
    .btn-icon { border: 0; background: transparent; color: var(--muted); padding: 2px 6px; border-radius: 6px; }
    .btn-icon:hover { color: var(--text-color); background: rgba(0,0,0,0.05); }
    .edit-controls { display: flex; gap: 6px; justify-content: flex-end; margin-top: 6px; }
    .edit-textarea { width: 100%; min-height: 60px; border-radius: 8px; padding: 8px; }

    .typing-dots::after { content: '...'; animation: blink 1s infinite; }
    @keyframes blink { 0%{opacity:.2} 50%{opacity:1} 100%{opacity:.2} }

    .link-muted { color: var(--muted); text-decoration: none; }
    .link-muted:hover { color: var(--text-color); }

    /* Markdown-like formatting for AI messages */
    .markdown-body { line-height: 1.6; white-space: normal; }
    .markdown-body p { margin: 0 0 10px 0; }
    .markdown-body ul, .markdown-body ol { padding-left: 1.2rem; margin: 0 0 10px 0; }
    .markdown-body li { margin: 4px 0; }
    .markdown-body h1, .markdown-body h2, .markdown-body h3,
    .markdown-body h4, .markdown-body h5, .markdown-body h6 {
      margin: 14px 0 8px 0; font-weight: 700;
    }
    .markdown-body h1 { font-size: 1.25rem; }
    .markdown-body h2 { font-size: 1.15rem; }
    .markdown-body h3 { font-size: 1.05rem; }
    .markdown-body code { background: rgba(0,0,0,0.06); padding: 2px 4px; border-radius: 6px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 0.95em; }
    .markdown-body pre { background: rgba(0,0,0,0.06); padding: 10px; border-radius: 10px; overflow: auto; }
    .markdown-body pre code { background: transparent; padding: 0; }
    .markdown-body blockquote { border-left: 3px solid rgba(108,99,255,0.6); padding-left: 10px; color: var(--muted); margin: 8px 0; }
    .markdown-body hr { border: 0; border-top: 1px solid var(--border); margin: 12px 0; }
  </style>
</head>
<body>
<div class="wrapper">
  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
    <div class="logo-details">
      <div class="brand">
        <div class="user-avatar"><?php echo htmlspecialchars($initial); ?></div>
        <div class="hide-when-collapsed">
          <div class="logo_name">MindCare</div>
          <div class="text-muted small" style="margin-top:-2px;">Aura Chatbot</div>
        </div>
      </div>
<button class="toggle-btn" onclick="toggleSidebar()">
  <i class="bi bi-chevron-left"></i>
</button>
    </div>

    <div class="section">
      <button id="newChatBtn" class="btn btn-primary btn-action" title="Start a new chat session (clears current chat)"><i class="bi bi-plus-lg me-1"></i> <span class="hide-when-collapsed">New Chat</span></button>
    </div>

    <div class="section hide-when-collapsed">
      <div class="search-input mb-2">
        <i class="bi bi-search"></i>
        <input id="searchRecent" type="text" class="form-control" placeholder="Search chats..." />
      </div>
      <div class="d-flex gap-2">
        <a href="?mode=basic<?php echo $guest ? '&guest=1' : '' ?>" class="btn btn-outline-secondary flex-fill <?php echo $mode === 'basic' ? 'active' : '' ?>">Basic</a>
        <a href="?mode=pro<?php echo $guest ? '&guest=1' : '' ?>" class="btn btn-outline-secondary flex-fill <?php echo $mode === 'pro' ? 'active' : '' ?>">Pro</a>
      </div>
    </div>

    <div class="section hide-when-collapsed" id="recentSection" style="<?php echo ($has_history ? '' : 'display:none;') ?>">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-bold">Recent Chats</span>
        <a class="link-muted small" href="chat_history.php"><i class="bi bi-clock-history"></i> History</a>
      </div>
      <ul id="recentList" class="recent-list">
        <?php if ($has_history): ?>
          <?php foreach ($recentChats as $c): ?>
            <li class="recent-item" data-id="<?php echo (int)$c['id']; ?>" data-mode="<?php echo htmlspecialchars($c['mode']); ?>" data-text="<?php echo htmlspecialchars(strtolower($c['message'])); ?>" data-ts="<?php echo htmlspecialchars($c['timestamp']); ?>">
              <div class="d-flex justify-content-between align-items-center">
                <div class="title"><?php echo htmlspecialchars(mb_strimwidth($c['message'], 0, 60, '…')); ?></div>
                <div class="recent-actions">
                  <span class="badge bg-secondary badge-mode text-uppercase"><?php echo htmlspecialchars($c['mode']); ?></span>
                  <button class="btn-recent-delete" title="Delete"><i class="bi bi-three-dots"></i></button>
                </div>
              </div>
              <div class="meta"><?php echo date("M d, h:i A", strtotime($c['timestamp'])); ?></div>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>

    <div class="user-section hide-when-collapsed">
      <div class="user-details" id="userDetails">
        <div class="user-avatar"><?php echo htmlspecialchars($initial); ?></div>
        <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
      </div>
      <div class="logout-menu" id="logoutMenu">
        <?php if (!$guest): ?>
          <a href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        <?php else: ?>
          <a href="../auth/login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="page-header">
      <div>
        <h4 class="page-title mb-0">MindCare | Chat with <strong>Aura</strong> <span class="text-muted">(<?php echo ucfirst($mode); ?>)</span></h4>
        <div class="small text-muted">A supportive mental wellness assistant</div>
      </div>
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-gear me-1"></i> Menu
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
          <li><a class="dropdown-item" href="../dashboard/user_dashboard.php"><i class="bi bi-house me-2"></i>Back to Dashboard</a></li>
          <?php if (!$guest): ?>
            <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          <?php else: ?>
            <li><a class="dropdown-item" href="../auth/login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a></li>
          <?php endif; ?>
          <li><button class="dropdown-item" onclick="toggleDarkMode()"><i class="bi bi-moon-stars me-2"></i>Dark Mode</button></li>
        </ul>
      </div>
    </div>

    <!-- Reason/Referral Selection -->
    <div class="referral-card">
      <label for="referral" class="form-label mb-1 fw-semibold">Reason / Referral</label>
      <div class="row g-2 align-items-center">
        <div class="col-md-8">
          <select id="referral" class="form-select" aria-label="Select Reason">
            <option value="" selected>Choose a reason to tailor the responses…</option>
            <option>Poor Academic Performance</option>
            <option>Issue on Self-Control (misbehavior)</option>
            <option>Problem Dealing with Others</option>
            <option>Family Issues/Conflict</option>
            <option>Victim of Bullying</option>
            <option>Personal Problems</option>
            <option>Suspected Victim Abuse (physical, verbal, and/or sexual)</option>
          </select>
        </div>
        <div class="col-md-4 text-md-end">
          <span class="text-muted small">Responses will adapt to the selected reason.</span>
        </div>
      </div>
    </div>

    <div id="chatbox"></div>

    <form id="chatForm" class="composer" autocomplete="off">
      <input type="text" id="prompt" name="prompt" class="form-control" placeholder="Type your concern here…" required />
      <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> <span class="d-none d-md-inline">Send</span></button>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Recent Chat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this item from Recent Chats? This only removes it from the sidebar.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Preload all logs for logged-in users (for transcript playback)
  window.allLogs = <?php echo json_encode($allLogs, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
<script>
  // State
  let messageCount = <?php echo $guest ? 'parseInt(localStorage.getItem("chat_limit") || 0)' : '0' ?>;
  const chatForm = document.getElementById('chatForm');
  const chatbox = document.getElementById('chatbox');
  const referral = document.getElementById('referral');
  const recentSection = document.getElementById('recentSection');
  const recentList = document.getElementById('recentList');
  const mode = '<?php echo $mode; ?>';
  const isGuest = <?php echo $guest ? 'true' : 'false' ?>;
  let requestQueue = [];
  let isProcessing = false;

  // Thread management (client-side) to avoid duplicate recent items and keep a single chat thread like ChatGPT
  const THREADS_KEY = 'mindcare_threads';
  const CURRENT_THREAD_KEY = 'mindcare_current_thread_id';

  function loadThreads() {
    try { return JSON.parse(localStorage.getItem(THREADS_KEY) || '[]'); } catch (e) { return []; }
  }
  function saveThreads(arr) {
    localStorage.setItem(THREADS_KEY, JSON.stringify(arr || []));
  }
  function getCurrentThreadId() { return localStorage.getItem(CURRENT_THREAD_KEY) || null; }
  function setCurrentThreadId(id) { if (id) localStorage.setItem(CURRENT_THREAD_KEY, id); else localStorage.removeItem(CURRENT_THREAD_KEY); }

  function upsertThreadFromMessage(firstMessage, mode, ts) {
    if (!recentList) return;
    let threads = loadThreads();
    let id = getCurrentThreadId();
    if (!id) {
      id = 't_' + Date.now();
      setCurrentThreadId(id);
      threads.unshift({ id, title: truncate(firstMessage || 'New chat', 60), mode, start: ts, end: ts });
    } else {
      const idx = threads.findIndex(t => t.id === id);
      if (idx >= 0) {
        threads[idx].end = ts;
        const t = threads.splice(idx, 1)[0];
        threads.unshift(t);
      } else {
        threads.unshift({ id, title: truncate(firstMessage || 'New chat', 60), mode, start: ts, end: ts });
      }
    }
    saveThreads(threads);
    rebuildRecentList();
  }

  function rebuildRecentList() {
    if (!recentList) return;
    const threads = loadThreads();
    recentList.innerHTML = '';
    if (threads.length === 0) { recentSection.style.display = 'none'; return; }
    recentSection.style.display = '';
    threads.slice(0, 12).forEach(t => renderThreadItem(t));
  }

  function renderThreadItem(t) {
    const li = document.createElement('li');
    li.className = 'recent-item';
    li.setAttribute('data-thread-id', t.id);
    li.dataset.text = (t.title || '').toLowerCase();
    li.dataset.mode = t.mode || '';
    li.setAttribute('data-start', t.start || '');
    li.setAttribute('data-end', t.end || '');
    li.innerHTML = `
      <div class="d-flex justify-content-between align-items-center">
        <div class="title">${escapeHtml(truncate(t.title || 'New chat', 60))}</div>
        <div class="recent-actions">
          <span class="badge bg-secondary badge-mode text-uppercase">${(t.mode || '').toString().slice(0,3)}</span>
          <button class="btn-recent-delete" title="Delete"><i class="bi bi-three-dots"></i></button>
        </div>
      </div>
      <div class="meta">${formatTime(t.end)}</div>
    `;
    recentList.appendChild(li);
  }

  function removeThread(id) {
    const threads = loadThreads();
    const idx = threads.findIndex(t => t.id === id);
    if (idx >= 0) {
      threads.splice(idx, 1);
      saveThreads(threads);
    }
  }

  function loadThread(startTs, endTs) {
    chatbox.innerHTML = '';
    if (isGuest) {
      const store = JSON.parse(localStorage.getItem('guest_recent_chats') || '[]');
      const s = startTs ? new Date(startTs).getTime() : -Infinity;
      const e = endTs ? new Date(endTs).getTime() : Infinity;
      const within = store.filter(x => {
        const t = new Date(x.timestamp).getTime();
        return t >= s && t <= e;
      });
      within.forEach(item => {
        const urow = addChatBubble(item.message, 'user');
        if (urow) {
          if (item.id) urow.dataset.logId = item.id;
          if (item.mode) urow.dataset.mode = item.mode;
        }
        addChatBubble(item.reply || '', 'ai');
      });
    } else {
      const all = Array.isArray(window.allLogs) ? window.allLogs : [];
      const s = startTs ? new Date(startTs).getTime() : -Infinity;
      const e = endTs ? new Date(endTs).getTime() : Infinity;
      const within = all.filter(x => {
        const t = new Date(x.timestamp).getTime();
        return t >= s && t <= e;
      });
      within.forEach(item => {
        const urow = addChatBubble(item.message, 'user');
        if (urow) {
          if (item.id) urow.dataset.logId = item.id;
          if (item.mode) urow.dataset.mode = item.mode;
        }
        addChatBubble(item.reply || '', 'ai');
      });
    }
    chatbox.scrollTop = chatbox.scrollHeight;
  }

  // Delete modal state
  let pendingDeleteLi = null;
  const deleteModalEl = document.getElementById('deleteModal');
  const deleteModal = new bootstrap.Modal(deleteModalEl);
  document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (!pendingDeleteLi) return;

    // Remove client-side thread entry only (does not touch server logs)
    const threadId = pendingDeleteLi.getAttribute('data-thread-id');
    if (threadId && !pendingDeleteLi.getAttribute('data-id')) {
      removeThread(threadId);
      if (getCurrentThreadId() === threadId) setCurrentThreadId(null);
      pendingDeleteLi.remove();
      pendingDeleteLi = null;
      if (recentList && recentList.children.length === 0) recentSection.style.display = 'none';
      deleteModal.hide();
      return;
    }

    if (isGuest) {
      const store = JSON.parse(localStorage.getItem('guest_recent_chats') || '[]');
      const ts = pendingDeleteLi.getAttribute('data-ts');
      const title = pendingDeleteLi.querySelector('.title')?.textContent || '';
      const idx = store.findIndex(x => (new Date(x.timestamp).toISOString() === new Date(ts).toISOString()) || (x.message && title && x.message.startsWith(title.replace('…',''))));
      if (idx >= 0) {
        store.splice(idx, 1);
        localStorage.setItem('guest_recent_chats', JSON.stringify(store));
      }
      pendingDeleteLi.remove();
      pendingDeleteLi = null;
      if (recentList && recentList.children.length === 0) recentSection.style.display = 'none';
      deleteModal.hide();
      return;
    }

    const id = pendingDeleteLi.getAttribute('data-id');
    if (!id) {
      pendingDeleteLi.remove();
      pendingDeleteLi = null;
      if (recentList && recentList.children.length === 0) recentSection.style.display = 'none';
      deleteModal.hide();
      return;
    }

    try {
      const res = await fetch('delete_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id }).toString()
      });
      const payload = await res.json().catch(() => ({ success: false, error: 'Invalid server response' }));
      if (payload && payload.success) {
        pendingDeleteLi.remove();
        pendingDeleteLi = null;
        if (recentList && recentList.children.length === 0) recentSection.style.display = 'none';
        deleteModal.hide();
      } else {
        alert('Could not delete item: ' + (payload.error || 'Unknown error'));
      }
    } catch (err) {
      console.error(err);
      alert('Network error while deleting. Please try again.');
    }
  });

  // User menu toggle
  const userDetails = document.getElementById('userDetails');
  const logoutMenu = document.getElementById('logoutMenu');
  userDetails.addEventListener('click', () => {
    logoutMenu.classList.toggle('show');
  });

  // Close logout menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!userDetails.contains(e.target) && !logoutMenu.contains(e.target)) {
      logoutMenu.classList.remove('show');
    }
  });

  referral.value = localStorage.getItem('mindcare_referral') || '';
  referral.addEventListener('change', () => {
    localStorage.setItem('mindcare_referral', referral.value);
  });

  if (isGuest) {
    // Migrate legacy guest messages into a single thread if no threads yet, then rebuild recent list
    let threads = loadThreads();
    if (threads.length === 0) {
      const guestRecent = JSON.parse(localStorage.getItem('guest_recent_chats') || '[]');
      if (guestRecent.length > 0) {
        const first = guestRecent[guestRecent.length - 1]; // oldest
        const last = guestRecent[0]; // newest
        const id = 't_' + Date.now();
        threads = [{ id, title: truncate(first.message || 'Chat', 60), mode, start: first.timestamp, end: last.timestamp }];
        saveThreads(threads);
        setCurrentThreadId(id);
      }
    }
    rebuildRecentList();
  } else {
    // For logged-in users, use client-side threads to avoid duplicates
    rebuildRecentList();
  }

  const searchRecent = document.getElementById('searchRecent');
  if (searchRecent) {
    searchRecent.addEventListener('input', () => {
      const q = searchRecent.value.trim().toLowerCase();
      Array.from(recentList.querySelectorAll('.recent-item')).forEach(li => {
        li.style.display = li.dataset.text.includes(q) ? '' : 'none';
      });
    });
  }

  if (recentList) {
    recentList.addEventListener('click', (e) => {
      const target = e.target;
      const li = e.target.closest('.recent-item');
      if (!li) return;
      if (target.closest('.btn-recent-delete')) {
        e.stopPropagation();
        openDeleteModal(li);
        return;
      }
      const start = li.getAttribute('data-start');
      const end = li.getAttribute('data-end');
      if (start || end) {
        loadThread(start, end);
        return;
      }
      const ts = li.getAttribute('data-ts');
      const session = findSessionForTimestamp(ts);
      if (session) {
        const title = li.querySelector('.title')?.textContent || 'Chat';
        const sess = { id: 't_' + new Date(session.start).getTime(), title, mode: li.dataset.mode || mode, start: session.start, end: session.end };
        let threads = loadThreads();
        const idx = threads.findIndex(t => t.id === sess.id);
        if (idx >= 0) { threads[idx] = sess; } else { threads.unshift(sess); }
        saveThreads(threads);
        setCurrentThreadId(sess.id);
        rebuildRecentList();
        loadThread(session.start, session.end);
      } else {
        loadThread(null, null);
      }
    });
  }

  document.getElementById('newChatBtn').addEventListener('click', () => {
    chatbox.innerHTML = '';
    setCurrentThreadId(null);
    document.getElementById('prompt').focus();
  });

  async function processQueue() {
    if (isProcessing || requestQueue.length === 0) return;
    isProcessing = true;
    const { prompt, reason, resolve, reject } = requestQueue.shift();

    try {
      const res = await fetch('<?php echo $endpoint; ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ prompt, reason, mode }).toString()
      });
      const data = await res.text();
      resolve(data);
    } catch (err) {
      reject(err);
    } finally {
      isProcessing = false;
      processQueue();
    }
  }

  chatForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const promptEl = document.getElementById('prompt');
    const userPrompt = promptEl.value.trim();
    if (!userPrompt) return;

    <?php if ($guest): ?>
    if (messageCount >= <?php echo $chat_limit; ?>) {
      alert('Guest limit reached. Please log in.');
      return;
    }
    messageCount++;
    localStorage.setItem('chat_limit', messageCount);
    <?php endif; ?>

    addChatBubble(userPrompt, 'user');
    promptEl.value = '';
    showTypingBubble();

    const reason = referral.value || '';
    const composed = reason
      ? `Context: The user's stated reason/referral is: ${reason}. Please ensure your response is sensitive and tailored to this context.\nMessage: ${userPrompt}`
      : userPrompt;

    const promise = new Promise((resolve, reject) => {
      requestQueue.push({ prompt: composed, reason, resolve, reject });
      processQueue();
    });

    promise
      .then(data => {
        removeTyping();
        addChatBubble(data, 'ai');
        chatbox.scrollTop = chatbox.scrollHeight;

        const replyText = stripToText(data);

        upsertThreadFromMessage(userPrompt, mode, new Date().toISOString());

        if (isGuest) {
          const store = JSON.parse(localStorage.getItem('guest_recent_chats') || '[]');
          store.unshift({ message: userPrompt, reply: replyText, mode, timestamp: new Date().toISOString() });
          if (store.length > 12) store.length = 12;
          localStorage.setItem('guest_recent_chats', JSON.stringify(store));
        } else {
          syncLastLogIdForLastPair();
          const lastUser = (function(){ const rows = chatbox.querySelectorAll('.chat-row.user'); return rows[rows.length-1] || null; })();
          if (lastUser) lastUser.dataset.mode = mode;
        }
      })
      .catch(() => {
        removeTyping();
        addChatBubble('⚠️ Network error. Please try again.', 'ai');
      });
  });

  function addChatBubble(message, sender) {
    const row = document.createElement('div');
    row.className = 'chat-row ' + sender;

    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${sender}`;
    if (sender === 'ai') {
      const html = renderAIMessage(String(message || ''));
      bubble.innerHTML = `<div class="ai-avatar">A</div><div class="ai-msg markdown-body${html.startsWith('<div class=\"important\">') ? ' important' : ''}">${html}</div>`;
    } else {
      const safe = escapeHtml(String(message || ''));
      bubble.innerHTML = `
        <div class="user-msg">
          <div class="msg-text">${safe}</div>
          <div class="msg-actions">
            <button class="btn-icon btn-edit-message" title="Edit"><i class="bi bi-pencil"></i></button>
          </div>
        </div>`;
      row.dataset.mode = mode;
    }
    row.appendChild(bubble);
    chatbox.appendChild(row);
    chatbox.scrollTop = chatbox.scrollHeight;
    return row;
  }

  function showTypingBubble() {
    const row = document.createElement('div');
    row.className = 'chat-row ai typing';
    row.innerHTML = `<div class="chat-bubble ai"><div class="ai-avatar">A</div><div class="typing-dots">Aura is typing</div></div>`;
    chatbox.appendChild(row);
    chatbox.scrollTop = chatbox.scrollHeight;
  }

  function removeTyping() {
    document.querySelectorAll('.chat-row.typing').forEach(el => el.remove());
  }

  function renderAIMessage(text) {
    const raw = String(text || '');
    let isImportant = false;

    if (raw.match(/^(#{1,3}\s)|[-*]\s|\d+\.\s|\*\*.*?\*\*/m)) {
      isImportant = true;
    } else if (raw.length > 200) {
      isImportant = true;
    }

    try {
      if (window.marked && window.DOMPurify) {
        const html = marked.parse(raw, { breaks: true });
        const sanitized = DOMPurify.sanitize(html, { USE_PROFILES: { html: true } });
        return isImportant ? `<div class="important">${sanitized}</div>` : sanitized;
      }
    } catch (e) { /* noop */ }
    const esc = raw.replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    return isImportant ? `<div class="important">${esc.replace(/\n/g, '<br>')}</div>` : esc.replace(/\n/g, '<br>');
  }

  function stripToText(s) {
    const raw = String(s || '');
    let html = raw;
    try {
      if (window.marked) { html = marked.parse(raw, { breaks: true }); }
    } catch (e) { /* noop */ }
    try {
      if (window.DOMPurify) { html = DOMPurify.sanitize(html, { USE_PROFILES: { html: true } }); }
    } catch (e) { /* noop */ }
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent || tmp.innerText || '').trim();
  }

  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    logoutMenu.classList.remove('show'); // Close logout menu when collapsing
  }

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('dark-mode', document.body.classList.contains('dark-mode') ? 'on' : 'off');
  }

  if (localStorage.getItem('dark-mode') === 'on') {
    document.body.classList.add('dark-mode');
  }

  // Deprecated: replaced by thread-based recents. Use upsertThreadFromMessage and rebuildRecentList instead.
  function appendRecentItem(message, reply, mode, timestamp) {
    upsertThreadFromMessage(message, mode, timestamp);
  }

  function truncate(str, n) { return (str && str.length > n) ? str.slice(0, n - 1) + '…' : (str || ''); }
  function escapeHtml(str) { return (str || '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
  function formatTime(ts) {
    try { const d = ts ? new Date(ts) : new Date(); return d.toLocaleString([], { month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' }); } catch(e) { return ''; }
  }

  // Utility: open delete modal
  function openDeleteModal(li) { pendingDeleteLi = li; deleteModal.show(); }

  // Build sessions from all logs using a 30-minute inactivity gap
  function buildSessionsFromAllLogs() {
    const all = Array.isArray(window.allLogs) ? window.allLogs : [];
    const asc = all.slice().sort((a,b) => new Date(a.timestamp) - new Date(b.timestamp));
    const sessions = [];
    const gap = 30 * 60 * 1000;
    let cur = null;
    for (const it of asc) {
      const t = new Date(it.timestamp).getTime();
      if (!cur) { cur = { start: it.timestamp, end: it.timestamp }; continue; }
      const prev = new Date(cur.end).getTime();
      if (t - prev <= gap) { cur.end = it.timestamp; }
      else { sessions.push(cur); cur = { start: it.timestamp, end: it.timestamp }; }
    }
    if (cur) sessions.push(cur);
    return sessions;
  }

  function findSessionForTimestamp(ts) {
    if (!ts) return null;
    const sessions = buildSessionsFromAllLogs();
    const t = new Date(ts).getTime();
    for (const s of sessions) {
      const s1 = new Date(s.start).getTime();
      const s2 = new Date(s.end).getTime();
      if (t >= s1 && t <= s2) return s;
    }
    return null;
  }

  function findNextAiRow(userRow) {
    let n = userRow.nextElementSibling;
    while (n) {
      if (n.classList.contains('ai')) return n;
      n = n.nextElementSibling;
    }
    return null;
  }

  async function syncLastLogIdForLastPair() {
    try {
      const res = await fetch('chat_last.php', { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (json && json.success && json.data) {
        const rows = chatbox.querySelectorAll('.chat-row.user');
        const row = rows[rows.length - 1];
        if (row) {
          row.dataset.logId = json.data.id;
          row.dataset.mode = json.data.mode || row.dataset.mode || mode;
        }
      }
    } catch (e) { /* ignore */ }
  }

  async function regenerateAiForUserRow(userRow, newMessage) {
    let aiRow = findNextAiRow(userRow);
    if (!aiRow) {
      aiRow = document.createElement('div');
      aiRow.className = 'chat-row ai typing';
      aiRow.innerHTML = `<div class="chat-bubble ai"><div class="ai-avatar">A</div><div class="typing-dots">Aura is typing</div></div>`;
      chatbox.insertBefore(aiRow, userRow.nextElementSibling);
    }

    const reason = referral.value || '';
    const composed = reason
      ? `Context: The user's stated reason/referral is: ${reason}. Please ensure your response is sensitive and tailored to this context.\nMessage: ${newMessage}`
      : newMessage;

    if (isGuest) {
      try {
        const res = await fetch('<?php echo $endpoint; ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ prompt: composed, reason, mode }).toString()
        });
        const data = await res.text();
        aiRow.classList.remove('typing');
        const bubble = aiRow.querySelector('.chat-bubble.ai');
        const html = renderAIMessage(String(data || ''));
        bubble.innerHTML = `<div class="ai-avatar">A</div><div class="ai-msg markdown-body${html.startsWith('<div class=\"important\">') ? ' important' : ''}">${html}</div>`;
      } catch (e) {
        alert('Network error while regenerating response.');
      }
      return;
    }

    const id = userRow.dataset.logId;
    const itemMode = userRow.dataset.mode || mode;
    if (!id) {
      alert('Cannot edit: missing message id.');
      return;
    }

    try {
      const res = await fetch('update_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: new URLSearchParams({ id, message: newMessage, mode: itemMode, reason }).toString()
      });
      const json = await res.json();
      if (json && json.success) {
        aiRow.classList.remove('typing');
        const bubble = aiRow.querySelector('.chat-bubble.ai');
        const html = renderAIMessage(String(json.reply || json.reply_html || ''));
        bubble.innerHTML = `<div class="ai-avatar">A</div><div class="ai-msg markdown-body${html.startsWith('<div class=\"important\">') ? ' important' : ''}">${html}</div>`;
      } else {
        alert('Edit failed: ' + (json && json.error ? json.error : 'Unknown error'));
      }
    } catch (e) {
      alert('Network error while saving edit.');
    }
  }

  // In-place editing actions
  chatbox.addEventListener('click', (e) => {
    const btnEdit = e.target.closest('.btn-edit-message');
    const btnSave = e.target.closest('.btn-save-edit');
    const btnCancel = e.target.closest('.btn-cancel-edit');

    if (btnEdit) {
      const row = btnEdit.closest('.chat-row.user');
      if (!row) return;
      const textEl = row.querySelector('.msg-text');
      const original = textEl ? textEl.textContent : '';
      row.dataset.originalText = original;
      const bubble = row.querySelector('.chat-bubble.user');
      bubble.innerHTML = `
        <textarea class="edit-textarea form-control">${original}</textarea>
        <div class="edit-controls">
          <button class="btn btn-sm btn-primary btn-save-edit"><i class="bi bi-check"></i> Save</button>
          <button class="btn btn-sm btn-outline-secondary btn-cancel-edit">Cancel</button>
        </div>
      `;
      return;
    }

    if (btnCancel) {
      const row = btnCancel.closest('.chat-row.user');
      if (!row) return;
      const original = row.dataset.originalText || '';
      const bubble = row.querySelector('.chat-bubble.user');
      bubble.innerHTML = `
        <div class="user-msg">
          <div class="msg-text">${escapeHtml(original)}</div>
          <div class="msg-actions">
            <button class="btn-icon btn-edit-message" title="Edit"><i class="bi bi-pencil"></i></button>
          </div>
        </div>
      `;
      return;
    }

    if (btnSave) {
      const row = btnSave.closest('.chat-row.user');
      if (!row) return;
      const textarea = row.querySelector('.edit-textarea');
      const newMessage = textarea ? textarea.value.trim() : '';
      if (!newMessage) return;
      const bubble = row.querySelector('.chat-bubble.user');
      bubble.innerHTML = `
        <div class="user-msg">
          <div class="msg-text">${escapeHtml(newMessage)}</div>
          <div class="msg-actions">
            <button class="btn-icon btn-edit-message" title="Edit"><i class="bi bi-pencil"></i></button>
          </div>
        </div>
      `;
      regenerateAiForUserRow(row, newMessage);
      return;
    }
  });
</script>
</body>
</html>