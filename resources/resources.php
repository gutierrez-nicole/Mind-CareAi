<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit();
}

$name = $_SESSION['name'];
$parts = explode(" ", $name);
$initials = strtoupper(substr($parts[0], 0, 1) . ($parts[1] ?? substr($parts[0], 1, 1)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resources | MindCare AI</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    :root {
      --main-bg: #f7f8fc;
      --sidebar-bg: #fff;
      --card-bg: #ffffff;
      --card-bg-2: #f2f5ff;
      --text-color: #1f2937;
      --muted: #6b7280;
      --primary: #6C63FF;
      --primary-600: #5b55f2;
      --accent: #22c55e;
      --border: #e5e7eb;
      --shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    }

    body, html { height: 100%; overflow: hidden; }
    body {
      background: var(--main-bg);
      font-family: 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
      color: var(--text-color);
    }

    .dark-mode {
      --main-bg: #0b1220;
      --sidebar-bg: #0f172a;
      --card-bg: #0b1220;
      --card-bg-2: #0b1220;
      --text-color: #e5e7eb;
      --muted: #94a3b8;
      --border: #1f2937;
      --shadow: 0 12px 30px rgba(0,0,0,0.35);
    }

    .wrapper { display: flex; min-height: 100vh; }

    /* Sidebar (UNCHANGED) */
    .sidebar {
      position: fixed; height: 100vh; width: 250px; background: var(--sidebar-bg);
      transition: all 0.3s ease; box-shadow: 0 0 20px rgba(0,0,0,0.05); z-index: 99;
    }
    .sidebar.collapsed { width: 78px; }
    .sidebar .logo-details { height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 15px; border-bottom: 1px solid #eee; }
    .logo_name { font-size: 20px; font-weight: 600; color: var(--text-color); }
    .toggle-btn { font-size: 24px; background: none; border: none; color: var(--text-color); cursor: pointer; }
    .sidebar .nav-links { margin-top: 20px; list-style: none; padding-left: 0; }
    .sidebar .nav-links li { width: 100%; margin: 10px 0; }
    .sidebar .nav-links li a { display: flex; align-items: center; text-decoration: none; padding: 12px 20px; color: var(--text-color); font-size: 16px; border-radius: 8px; transition: all 0.3s ease; }
    .sidebar .nav-links li a i { min-width: 28px; font-size: 18px; }
    .sidebar .nav-links li a:hover, .sidebar .nav-links li a.active { background: var(--primary); color: #fff; }
    .sidebar.collapsed .sidebar-text, .sidebar.collapsed .logo_name { display: none; }

    /* Main Content Shell (no vertical scroll) */
    .main-content {
      margin-left: 250px; flex-grow: 1; height: 100vh; padding: 24px; transition: all 0.3s ease; overflow: hidden;
      display: flex; flex-direction: column; gap: 16px;
    }
    .sidebar.collapsed + .main-content { margin-left: 78px; }

    /* Header */
    .header {
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      padding: 16px 18px; border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, var(--card-bg-2) 100%);
      box-shadow: var(--shadow); border: 1px solid var(--border);
    }
    .header-title { display: flex; align-items: center; gap: 12px; }
    .header-title .logo-dot { width: 10px; height: 10px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 0 4px rgba(108,99,255,0.15); }

    .header-controls { display: flex; align-items: center; gap: 8px; }
    .search-wrap { position: relative; }
    .search-wrap .bi-search { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); }
    .search-input { padding-left: 34px; width: 320px; border-radius: 12px; }

    .filter-pills .btn { border-radius: 999px; padding: 8px 14px; }
    .filter-pills .btn.active { background: var(--primary); color: #fff; border-color: var(--primary-600); }

    /* Content Area (no vertical scroll, horizontal lanes) */
    .content-area { flex: 1; background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 16px; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 12px; overflow: hidden; }

    .lane-title { display: flex; align-items: center; justify-content: space-between; color: var(--muted); font-weight: 600; margin: 0 6px; }

    .lane {
      flex: 1; overflow-x: auto; overflow-y: hidden; display: flex; align-items: stretch; gap: 16px; scroll-snap-type: x mandatory; padding-bottom: 6px;
    }
    .lane::-webkit-scrollbar { height: 10px; }
    .lane::-webkit-scrollbar-thumb { background: #c7c9d1; border-radius: 10px; }

    .resource-card {
      min-width: 320px; max-width: 320px; flex: 0 0 auto;
      border: 1px solid var(--border); border-radius: 16px; background: linear-gradient(180deg, #ffffff 0%, rgba(246,247,255,1) 100%);
      box-shadow: var(--shadow); scroll-snap-align: start; position: relative; overflow: hidden; display: flex; flex-direction: column;
    }
    .dark-mode .resource-card { background: linear-gradient(180deg, #0b1220 0%, rgba(11,18,32,1) 100%); }

    .resource-media { height: 120px; display: grid; place-items: center; background: radial-gradient(120px 60px at 20% 20%, rgba(108,99,255,0.25), transparent), radial-gradient(100px 60px at 80% 60%, rgba(34,197,94,0.18), transparent); }
    .resource-media i { font-size: 40px; color: var(--primary); }

    .resource-body { padding: 14px; display: flex; flex-direction: column; gap: 8px; }
    .resource-title { font-weight: 700; font-size: 16px; }
    .resource-desc { color: var(--muted); font-size: 14px; }
    .resource-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .tag { font-size: 12px; padding: 4px 8px; border-radius: 999px; background: rgba(108,99,255,0.12); color: var(--primary); }
    .tag.alt { background: rgba(34,197,94,0.12); color: var(--accent); }

    .resource-actions { display: flex; gap: 8px; margin-top: auto; }

    @media (max-width: 1200px) { .search-input { width: 240px; } }
    @media (max-width: 992px)  { .search-input { width: 200px; } .header { flex-wrap: wrap; } }
    @media (max-width: 768px)  { .search-input { width: 160px; } }
  </style>
</head>
<body>

<div class="wrapper">
  <!-- Sidebar (UNCHANGED) -->
  <div id="sidebar" class="sidebar">
    <div class="logo-details">
      <span class="logo_name sidebar-text">MindCare</span>
      <button class="toggle-btn" onclick="toggleSidebar()">
        <i id="sidebarIcon" class="bi bi-list"></i>
      </button>
    </div>
    <ul class="nav-links">
      <li>
        <a href="../chatbot/index.php">
          <i class="bi bi-chat-dots"></i>
          <span class="sidebar-text">Chatbot</span>
        </a>
      </li>
      <li>
        <a href="../journal/journal.php">
          <i class="bi bi-journal-bookmark"></i>
          <span class="sidebar-text">Journal</span>
        </a>
      </li>
      <li>
        <a href="resource.php" class="active">
          <i class="bi bi-book"></i>
          <span class="sidebar-text">Resources</span>
        </a>
      </li>
            <li>
        <a href="../dashboard/user_dashboard.php">
          <i class="bi bi-house"></i>
          <span class="sidebar-text">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="../auth/logout.php">
          <i class="bi bi-box-arrow-right"></i>
          <span class="sidebar-text">Logout</span>
        </a>
      </li>
      <li>
        <a href="#" onclick="toggleDarkMode()">
          <i class="bi bi-moon-stars"></i>
          <span class="sidebar-text">Dark Mode</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <div class="header-title">
        <span class="logo-dot"></span>
        <div>
          <h5 class="mb-0 fw-bold">Mental Health Resources</h5>
          <div class="small text-muted">Curated articles, videos, and toolkits — tailored for your wellness journey</div>
        </div>
      </div>
      <div class="header-controls">
        <div class="filter-pills btn-group" role="group" aria-label="Filter">
          <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
          <button type="button" class="btn btn-outline-secondary" data-filter="article">Articles</button>
          <button type="button" class="btn btn-outline-secondary" data-filter="video">Videos</button>
          <button type="button" class="btn btn-outline-secondary" data-filter="toolkit">Toolkits</button>
          <button type="button" class="btn btn-outline-secondary" data-filter="emergency">Emergency</button>
        </div>
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input id="searchInput" type="text" class="form-control search-input" placeholder="Search resources..." />
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content-area">
      <div class="lane-title">
        <span>Explore</span>
        <span class="small">Scroll horizontally for more</span>
      </div>
      <div id="lane" class="lane">
        <!-- Article: Anxiety -->
        <div class="resource-card" data-type="article" data-title="Understanding Anxiety HelpGuide">
          <div class="resource-media">
            <i class="bi bi-journal-text"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Understanding Anxiety</div>
            <div class="resource-desc">What anxiety is and practical ways to manage it.</div>
            <div class="resource-tags">
              <span class="tag">Article</span>
              <span class="tag alt">Coping</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.helpguide.org/articles/anxiety/anxiety-disorders-and-anxiety-attacks.htm" target="_blank" class="btn btn-primary w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Read</a>
            </div>
          </div>
        </div>

        <!-- Video: Mindfulness -->
        <div class="resource-card" data-type="video" data-title="Mindfulness Meditation 10-minute YouTube Calm">
          <div class="resource-media">
            <i class="bi bi-play-circle"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Mindfulness Meditation</div>
            <div class="resource-desc">A 10-minute guided practice to calm your mind.</div>
            <div class="resource-tags">
              <span class="tag">Video</span>
              <span class="tag alt">Breathing</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.youtube.com/watch?v=inpok4MKVLM" target="_blank" class="btn btn-success w-100"><i class="bi bi-play-fill me-1"></i>Watch</a>
            </div>
          </div>
        </div>

        <!-- Toolkit PDF -->
        <div class="resource-card" data-type="toolkit" data-title="Mental Health Toolkit PDF Download">
          <div class="resource-media">
            <i class="bi bi-folder2-open"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Mental Health Toolkit</div>
            <div class="resource-desc">Download practical tips, exercises, and tools.</div>
            <div class="resource-tags">
              <span class="tag">Toolkit</span>
              <span class="tag alt">PDF</span>
            </div>
            <div class="resource-actions">
              <a href="../assets/resources/mental_health_toolkit.pdf" class="btn btn-secondary w-100" download><i class="bi bi-download me-1"></i>Download</a>
            </div>
          </div>
        </div>

        
        <!-- Additional curated placeholders (unique layout maintains single row) -->
        <div class="resource-card" data-type="article" data-title="Sleep Hygiene Better Rest Guide">
          <div class="resource-media">
            <i class="bi bi-moon-stars"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Sleep Hygiene</div>
            <div class="resource-desc">Habits and routines to improve quality sleep.</div>
            <div class="resource-tags">
              <span class="tag">Article</span>
              <span class="tag alt">Routine</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.sleepfoundation.org/sleep-hygiene" target="_blank" class="btn btn-primary w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Read</a>
            </div>
          </div>
        </div>

        <div class="resource-card" data-type="video" data-title="Box Breathing Stress Relief">
          <div class="resource-media">
            <i class="bi bi-wind"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Box Breathing</div>
            <div class="resource-desc">A simple technique for quick stress relief.</div>
            <div class="resource-tags">
              <span class="tag">Video</span>
              <span class="tag alt">Calm</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.youtube.com/results?search_query=box+breathing+exercise" target="_blank" class="btn btn-success w-100"><i class="bi bi-play-fill me-1"></i>Watch</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('sidebarIcon');
    sidebar.classList.toggle('collapsed');
  }

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('dark-mode', document.body.classList.contains('dark-mode') ? 'on' : 'off');
  }

  if (localStorage.getItem('dark-mode') === 'on') {
    document.body.classList.add('dark-mode');
  }

  // Filtering + search
  const filterButtons = document.querySelectorAll('.filter-pills .btn');
  const lane = document.getElementById('lane');
  const cards = Array.from(document.querySelectorAll('.resource-card'));
  const searchInput = document.getElementById('searchInput');

  function applyFilters() {
    const activeBtn = document.querySelector('.filter-pills .btn.active');
    const type = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
    const q = (searchInput.value || '').trim().toLowerCase();

    let visibleCount = 0;
    cards.forEach(card => {
      const t = card.getAttribute('data-type');
      const title = (card.getAttribute('data-title') || '').toLowerCase();
      const matchType = (type === 'all') || (t === type);
      const matchText = !q || title.includes(q);
      const show = matchType && matchText;
      card.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    // If nothing visible, show a simple empty state inline
    let empty = document.getElementById('emptyLane');
    if (!visibleCount) {
      if (!empty) {
        empty = document.createElement('div');
        empty.id = 'emptyLane';
        empty.className = 'd-flex align-items-center justify-content-center text-muted';
        empty.style.minWidth = '100%';
        empty.innerHTML = '<div class="text-center"><i class="bi bi-inbox fs-3"></i><div>No resources found</div></div>';
        lane.appendChild(empty);
      }
    } else if (empty) {
      empty.remove();
    }
  }

  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      filterButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilters();
    });
  });

  searchInput.addEventListener('input', applyFilters);
  applyFilters();
</script>
<script src="/MindCare-AI/assets/js/floating_mood_widget.js?v=1" defer></script>
</body>
</html>