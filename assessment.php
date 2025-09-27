<?php
session_start();
require_once 'config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all assessments and whether current user has completed them
$sql = "SELECT a.*, ar.score AS user_score, ar.category AS user_category
        FROM assessments a
        LEFT JOIN assessment_results ar
          ON ar.assessment_id = a.id AND ar.user_id = ?
        ORDER BY a.id DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) { die("SQL Error: " . $conn->error); }
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assessments = $stmt->get_result();
$assessment_count = $assessments ? $assessments->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Assessments • MindCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    :root {
      --brand: #6C63FF;
      --brand-2: #8A85FF;
      --surface: #ffffff;
      --text: #172036;
      --muted: #6c778b;
      --soft-border: #e7eaf3;
      --card-shadow: 0 10px 30px rgba(20, 15, 70, .06);
    }
    [data-bs-theme="dark"] {
      --surface: #0f131a;
      --text: #e6e8ee;
      --muted: #a6b0c1;
      --soft-border: #212837;
      --card-shadow: 0 10px 30px rgba(0, 0, 0, .35);
    }

    html, body { height: 100%; }
    body {
      background:
        radial-gradient(1200px 600px at 10% -10%, rgba(108, 99, 255, 0.12), transparent 60%),
        radial-gradient(900px 500px at 100% 0%, rgba(138, 133, 255, 0.10), transparent 50%),
        linear-gradient(180deg, rgba(247, 249, 255, 0.8), rgba(247, 249, 255, 0.2));
      color: var(--text);
      font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji';
    }

    .app-shell { max-width: 1100px; margin: 0 auto; padding: 28px 16px 80px; }

    /* Gradient Glass Header */
    .hero {
      position: relative;
      border: 1px solid var(--soft-border);
      border-radius: 18px;
      background: linear-gradient(180deg, rgba(255,255,255,.65), rgba(255,255,255,.35));
      backdrop-filter: blur(12px);
      box-shadow: var(--card-shadow);
      overflow: hidden;
    }
    [data-bs-theme="dark"] .hero {
      background: linear-gradient(180deg, rgba(28,34,48,.6), rgba(28,34,48,.3));
    }
    .hero::after {
      content: "";
      position: absolute; inset: 0;
      background:
        radial-gradient(400px 200px at 0% 0%, rgba(108,99,255,.18), transparent 60%),
        radial-gradient(400px 200px at 100% 0%, rgba(138,133,255,.18), transparent 60%);
      pointer-events: none;
    }

    .hero-content { padding: 22px 22px; }
    .hero-title { font-weight: 700; letter-spacing: 0.2px; }
    .hero-sub { color: var(--muted); }

    .chip {
      display: inline-flex; align-items: center; gap: 8px;
      border: 1px solid var(--soft-border);
      border-radius: 999px; padding: 6px 12px; font-size: .875rem; color: var(--muted);
      background: rgba(255,255,255,.65);
    }
    [data-bs-theme="dark"] .chip { background: rgba(15, 19, 26, .6); }

    .toolbar { display: flex; gap: 8px; align-items: center; }

    .theme-toggle { border: 1px solid var(--soft-border); }

    /* Search and Filters */
    .filters {
      display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
      margin-top: 14px; margin-bottom: 6px;
    }
    .filter-btn {
      border: 1px solid var(--soft-border);
      background: transparent;
      color: var(--muted);
    }
    .filter-btn.active { background: linear-gradient(180deg, #eef0ff, #ffffff); color: var(--text); border-color: #dfe3ff; }
    [data-bs-theme="dark"] .filter-btn.active { background: linear-gradient(180deg, #20263a, #141a29); border-color: #2b3450; }

    /* Assessment Cards */
    .grid { margin-top: 14px; }
    .assessment-card .card {
      border: 1px solid var(--soft-border);
      border-radius: 16px; overflow: hidden; box-shadow: var(--card-shadow);
      background: var(--surface);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      position: relative;
    }
    .assessment-card .card::before {
      content: "";
      position: absolute; inset: 0; pointer-events: none;
      background: linear-gradient(120deg, rgba(108,99,255,.18), rgba(138,133,255,.12));
      opacity: 0; transition: opacity .2s ease;
    }
    .assessment-card .card:hover {
      transform: translateY(-3px);
      border-color: #cfd7ff;
    }
    .assessment-card .card:hover::before { opacity: 1; }

    .badge-completed { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .badge-not-taken { background: #eef1ff; color: #3d44d1; border: 1px solid #dfe3ff; }

    /* Radio list options */
    .list-group .list-group-item { cursor: pointer; border: 1px solid var(--soft-border) !important; transition: background .15s ease, border-color .15s ease; }
    .list-group .list-group-item:hover { background: rgba(108, 99, 255, 0.06); }
    .list-group .list-group-item:has(input:checked) {
      border-color: #b8befc !important; background: linear-gradient(180deg, #f6f6ff, #ffffff);
    }
    [data-bs-theme="dark"] .list-group .list-group-item:has(input:checked) {
      background: linear-gradient(180deg, #1b2132, #111729);
    }

    /* Progress Bar */
    .progress { height: 10px; border-radius: 20px; background: #ecf0ff; }
    .progress .progress-bar {
      border-radius: 20px; background-image: linear-gradient(90deg, var(--brand), var(--brand-2));
      transition: width .25s ease;
    }

    /* Progress circle */
    .progress-circle {
      --size: 150px; --track: #e9ecf6; --bar: conic-gradient(#6C63FF, #8a85ff); --bg: var(--surface); --text: var(--text); --val: 0;
      width: var(--size); height: var(--size); border-radius: 50%; display: grid; place-items: center;
      background:
        conic-gradient(#6C63FF calc(var(--val) * 1%), var(--track) 0),
        radial-gradient(closest-side, var(--bg) 78%, transparent 79% 100%, var(--bg) 0);
      box-shadow: var(--card-shadow);
      transition: background .5s ease;
    }
    .progress-circle span { font-weight: 700; color: var(--text); font-size: 1.35rem; }

    /* Modals */
    .modal-header.sticky-top { position: sticky; top: 0; z-index: 2; background: var(--surface); border-bottom: 1px solid var(--soft-border); }
    .modal-content { border-radius: 16px; border: 1px solid var(--soft-border); box-shadow: var(--card-shadow); }
    .question-meta { color: var(--muted); font-size: .95rem; }
    .question-actions { position: sticky; bottom: -12px; z-index: 1; background: linear-gradient(180deg, rgba(255,255,255,0) 0%, var(--surface) 30%); padding-top: 12px; margin-top: 12px; }

    /* Important note dock */
    .important-note {
      position: fixed; bottom: 14px; left: 0; right: 0; z-index: 1085;
      border-radius: 14px; border: 1px solid #ffe69c; background: #fffbea; max-width: 980px; margin: 0 auto; padding: 12px 14px;
      box-shadow: 0 12px 32px rgba(163, 121, 0, 0.15); font-size: 0.9375rem; opacity: 0; transform: translateY(12px); transition: opacity .22s ease, transform .22s ease;
      pointer-events: none;
    }
    .important-note.show { opacity: 1; transform: translateY(0); }
    .important-note .note-title { font-weight: 700; }
    body.has-important-note { padding-bottom: 110px; }
    body.has-important-note .modal.show .modal-dialog { margin-bottom: 120px; }

    /* Utilities */
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    .muted { color: var(--muted); }
  /* Additions */
    .text-orange { color: #fd7e14 !important; }
    .input-group .form-control { border-top-right-radius: 999px; border-bottom-right-radius: 999px; }
    .input-group .input-group-text { border-top-left-radius: 999px; border-bottom-left-radius: 999px; }
    .input-group .form-control, .input-group .input-group-text { border: 1px solid var(--soft-border); background: transparent; }

    .mini-ring {
      --size: 44px; width: var(--size); height: var(--size); border-radius: 50%;
      display: grid; place-items: center;
      background:
        conic-gradient(var(--brand) calc(var(--val)*1%), #e9ecf6 0),
        radial-gradient(closest-side, var(--surface) 74%, transparent 0 99.9%, var(--surface) 0);
      font-weight: 600; font-size: .8rem; color: var(--text);
      box-shadow: var(--card-shadow);
    }
    .mini-ring span { transform: translateY(0.5px); }

    [data-bs-theme="dark"] .mini-ring {
      background:
        conic-gradient(var(--brand) calc(var(--val)*1%), #232a3b 0),
        radial-gradient(closest-side, var(--surface) 74%, transparent 0 99.9%, var(--surface) 0);
    }

    .pill-soft { background: #eef1ff; color: #3d44d1; border: 1px solid #dfe3ff; }
    [data-bs-theme="dark"] .pill-soft { background: #1c2439; color: #c9ceff; border-color: #2b3450; }

    .empty-state { border: 1px dashed var(--soft-border); }

    /* Collapsible cards: show only title by default */
    .card-details { display: none; }
    .assessment-card.expanded .card-details { display: block; }
    .card-toggle { background: transparent; border: 0; padding: 4px 0; color: var(--text); width: 100%; text-align: left; }
    .card-toggle:hover { color: var(--brand); }
    .card-toggle:focus-visible { outline: 2px solid #b8befc; outline-offset: 2px; border-radius: 8px; }
    .toggle-caret { transition: transform .2s ease; }
    .assessment-card.expanded .toggle-caret { transform: rotate(180deg); }

    @media (prefers-reduced-motion: reduce) {
      * { transition: none !important; }
    }
  </style>
</head>
<body>

<div class="app-shell">
  <!-- Header / Hero -->
  <div class="hero mb-4">
    <div class="hero-content">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="chip"><i class="bi bi-activity"></i> MindCare</span>
            <span class="chip"><i class="bi bi-collection"></i> <?= (int)$assessment_count ?> available</span>
          </div>
          <h2 class="hero-title mb-1">Assessments</h2>
          <p class="hero-sub mb-0">Gain insights and tailored recommendations by completing short assessments.</p>
        </div>
        <div class="toolbar">
          <div class="input-group">
            <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
            <input id="searchAssessments" type="text" class="form-control" placeholder="Search assessments" aria-label="Search assessments">
          </div>
          <button id="themeToggle" class="btn btn-light theme-toggle" type="button" aria-label="Toggle theme">
            <i class="bi bi-moon-stars"></i>
          </button>
          <a href="dashboard/user_dashboard.php" class="btn btn-outline-primary"><i class="bi bi-grid"></i> Dashboard</a>
        </div>
      </div>

      <div class="filters">
        <span class="muted">Show:</span>
        <div class="btn-group" role="group" aria-label="Filter assessments">
          <button type="button" class="btn filter-btn active" data-filter="all">All</button>
          <button type="button" class="btn filter-btn" data-filter="pending">Not taken</button>
          <button type="button" class="btn filter-btn" data-filter="completed">Completed</button>
        </div>
      </div>
    </div>
  </div>

  <div id="emptyState" class="text-center text-muted d-none py-5">
    <div class="border rounded-3 p-4 mx-auto" style="max-width: 680px; border-color: var(--soft-border)">
      <i class="bi bi-search fs-3 d-block mb-2"></i>
      <h5 class="mb-1">No assessments found</h5>
      <p class="mb-0">Try adjusting filters or clearing the search.</p>
    </div>
  </div>

  <!-- Grid -->
  <div class="row grid" id="assessmentsGrid">
    <?php if ($assessments && $assessments->num_rows > 0): ?>
      <?php while ($row = $assessments->fetch_assoc()): ?>
        <?php $completed = $row['user_score'] !== null; ?>
        <div class="col-md-6 col-lg-4 assessment-card" data-completed="<?= $completed ? '1' : '0' ?>" data-title="<?= htmlspecialchars($row['title']) ?>" data-description="<?= htmlspecialchars($row['description']) ?>">
          <div class="card mb-4 h-100">
            <div class="card-body d-flex flex-column">
              <button class="card-toggle d-flex align-items-center justify-content-between w-100 mb-2" type="button" aria-expanded="false">
                <h5 class="card-title mb-0 flex-grow-1 text-start"><?= htmlspecialchars($row['title']) ?></h5>
                <i class="bi bi-chevron-down ms-2 toggle-caret"></i>
              </button>

              <div class="card-details">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-2 status-row">
                  <div class="d-flex align-items-center gap-2">
                    <?php if ($completed): ?>
                      <div class="mini-ring" style="--val: <?= (int)$row['user_score'] ?>">
                        <span><?= (int)$row['user_score'] ?>%</span>
                      </div>
                      <span class="badge badge-completed rounded-pill px-2 py-1"><i class="bi bi-check2-circle"></i> Completed</span>
                    <?php else: ?>
                      <span class="badge badge-not-taken rounded-pill px-2 py-1"><i class="bi bi-compass"></i> Not taken</span>
                    <?php endif; ?>
                    <?php if ($completed && !empty($row['user_category'])): ?>
                      <span class="badge rounded-pill pill-soft"><i class="bi bi-award"></i> <?= htmlspecialchars($row['user_category']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <p class="card-text text-muted"><?= htmlspecialchars($row['description']) ?></p>

                <div class="mt-2 d-flex align-items-center justify-content-between">
                  <div class="small text-muted">
                    <i class="bi bi-question-circle"></i>
                    <span>Short self-assessment</span>
                  </div>
                  <div class="btn-group">
                    <?php if ($completed): ?>
                      <button class="btn btn-outline-primary btn-sm view-result-btn"
                              aria-label="View result for <?= htmlspecialchars($row['title']) ?>"
                              data-id="<?= $row['id'] ?>"
                              data-title="<?= htmlspecialchars($row['title']) ?>"
                              data-score="<?= (int)$row['user_score'] ?>"
                              data-category="<?= htmlspecialchars($row['user_category']) ?>"
                              data-bs-toggle="modal"
                              data-bs-target="#resultModal">
                        <i class="bi bi-graph-up-arrow"></i> View Result
                      </button>
                    <?php else: ?>
                      <button class="btn btn-primary btn-sm take-assessment-btn"
                              aria-label="Take <?= htmlspecialchars($row['title']) ?> assessment"
                              data-bs-toggle="modal"
                              data-bs-target="#assessmentModal"
                              data-id="<?= $row['id'] ?>"
                              data-title="<?= htmlspecialchars($row['title']) ?>">
                        <i class="bi bi-play-circle"></i> Take Assessment
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="alert alert-info border shadow-sm">
          No assessments available at the moment. Please check back later.
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Take Assessment Modal -->
<div class="modal fade" id="assessmentModal" tabindex="-1" aria-labelledby="assessmentTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header sticky-top">
        <h5 class="modal-title" id="assessmentTitle">Assessment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close assessment"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-column gap-2">
          <div class="d-flex justify-content-between align-items-center">
            <span class="question-meta" id="questionCounter" aria-live="polite"></span>
            <span class="question-meta" id="selectionHint">Select an option to continue</span>
          </div>
          <div class="progress" role="progressbar" aria-label="Assessment progress" aria-valuemin="0" aria-valuemax="100">
            <div id="progressBar" class="progress-bar" style="width: 0%;">0%</div>
          </div>
        </div>

        <div id="questionsContainer" class="p-3 text-start">
          <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <p class="mt-2 mb-0">Loading questions...</p>
          </div>
        </div>

        <div class="question-actions">
          <div class="d-flex justify-content-between">
            <button id="btnPrev" type="button" class="btn btn-outline-secondary" onclick="prevQuestion()" disabled>Previous</button>
            <div>
              <button id="btnNext" type="button" class="btn btn-primary d-none" onclick="nextQuestion()" disabled>Next</button>
              <button id="btnSubmit" type="button" class="btn btn-success d-none" onclick="submitAssessment()" disabled>Submit</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-bs-dismiss="modal" aria-label="Back to assessment">Back</button>
        <h5 class="modal-title">Assessment Result</h5>
      </div>
      <div class="modal-body">
        <div class="row g-4 align-items-center">
          <div class="col-md-7">
            <h4 class="mb-3" id="resultAssessmentTitle">Assessment Complete</h4>

            <div class="d-flex align-items-center gap-4 flex-wrap">
              <div class="progress-circle" id="resultCircle" style="--val: 0">
                <span id="resultPercent">0%</span>
              </div>
              <div>
                <h5 class="mb-1">Status: <span id="resultStatus" class="fw-bold">-</span></h5>
                <p class="text-muted mb-0" id="resultMessage">You may be experiencing some challenges that are worth addressing.</p>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="p-3 border rounded-3" style="border-color: var(--soft-border)">
              <h6 class="mb-2"><i class="bi bi-lightbulb"></i> Recommendations</h6>
              <ul class="mb-0">
                <li>Practice stress-reduction (deep breathing, mindfulness)</li>
                <li>Maintain regular sleep schedule</li>
                <li>Exercise regularly</li>
                <li>Talk to family/friends or a trusted person</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="btnRetake" disabled>Take Assessment Again</button>
        <a href="dashboard/user_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
      </div>
    </div>
  </div>
</div>

<!-- Important Note (shown when user starts answering) -->
<div id="importantNote" class="important-note alert alert-warning d-none shadow-sm" role="region" aria-live="polite" aria-label="Important note">
  <div class="d-flex align-items-start gap-2">
    <span class="mt-1" aria-hidden="true">⚠️</span>
    <div>
      <div class="note-title">Important Note</div>
      <div class="mb-0">
        <em>This assessment is for self-reflection and is not a substitute for professional diagnosis. If you're experiencing thoughts of self-harm or suicide, please reach out to a crisis helpline or emergency services immediately.</em>
      </div>
    </div>
  </div>
</div>

<script>
let currentIndex = 0;
let questions = [];
let answers = {};
let assessmentId = 0;
let assessmentTitle = '';

function showImportantNote(show) {
  const note = document.getElementById('importantNote');
  if (!note) return;
  if (show) {
    note.classList.remove('d-none');
    requestAnimationFrame(() => note.classList.add('show'));
    document.body.classList.add('has-important-note');
  } else {
    note.classList.remove('show');
    document.body.classList.remove('has-important-note');
    setTimeout(() => note.classList.add('d-none'), 150);
  }
}

// Fetch questions when clicking Take Assessment
document.querySelectorAll('.take-assessment-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    assessmentId = this.getAttribute('data-id');
    assessmentTitle = this.getAttribute('data-title');
    document.getElementById('assessmentTitle').innerText = assessmentTitle;

    const container = document.getElementById('questionsContainer');
    const counter = document.getElementById('questionCounter');
    if (counter) counter.textContent = '';

    container.style.opacity = '0.6';
    container.innerHTML = `
      <div class="text-center py-3">
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
        <p class="mt-2 mb-0">Loading questions...</p>
      </div>
    `;

    fetch("load_questions.php?assessment_id=" + encodeURIComponent(assessmentId), { headers: { 'Accept': 'application/json' } })
      .then(async res => {
        const txt = await res.text();
        try {
          return JSON.parse(txt);
        } catch (e) {
          console.error('load_questions: non-JSON response:', txt);
          throw new Error('Invalid JSON');
        }
      })
      .then(data => {
        // Accept both formats: plain array or {questions: [...]} for backward compatibility
        if (Array.isArray(data)) {
          questions = data;
        } else if (data && Array.isArray(data.questions)) {
          questions = data.questions;
        } else {
          questions = [];
        }
        currentIndex = 0;
        answers = {};
        showQuestion();
        showImportantNote(true);
      })
      .catch((err) => {
        console.error('Failed to load questions', err);
        container.innerHTML = '<p class="text-danger mb-0">Failed to load questions.</p>';
        container.style.opacity = '1';
        showImportantNote(false);
      });
  });
});

// Theme toggle persistence
(function themeInit(){
  const saved = localStorage.getItem('mc-theme');
  if (saved === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
  const toggle = document.getElementById('themeToggle');
  const isDarkInit = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  if (toggle) {
    toggle.innerHTML = isDarkInit ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    toggle.addEventListener('click', () => {
      const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
      document.documentElement.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
      localStorage.setItem('mc-theme', isDark ? 'light' : 'dark');
      toggle.innerHTML = isDark ? '<i class="bi bi-moon-stars"></i>' : '<i class="bi bi-sun"></i>';
    });
  }
})();

// Filters and search
(function filtersInit(){
  const search = document.getElementById('searchAssessments');
  const cards = Array.from(document.querySelectorAll('.assessment-card'));
  let activeFilter = 'all';

  const apply = () => {
    const q = (search?.value || '').toLowerCase().trim();
    cards.forEach(card => {
      const title = (card.getAttribute('data-title') || '').toLowerCase();
      const desc = (card.getAttribute('data-description') || '').toLowerCase();
      const done = card.getAttribute('data-completed') === '1';
      const matchesText = !q || title.includes(q) || desc.includes(q);
      const matchesFilter = activeFilter === 'all' || (activeFilter === 'completed' && done) || (activeFilter === 'pending' && !done);
      card.style.display = (matchesText && matchesFilter) ? '' : 'none';
    });
    const anyVisible = cards.some(c => c.style.display !== 'none');
    const empty = document.getElementById('emptyState');
    if (empty) empty.classList.toggle('d-none', anyVisible);
  };

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      activeFilter = btn.getAttribute('data-filter');
      apply();
    });
  });

  search?.addEventListener('input', apply);
})();

// Collapsible cards: expand/collapse on title click
(function collapsibleCardsInit(){
  document.querySelectorAll('.assessment-card .card-toggle').forEach(btn => {
    const card = btn.closest('.assessment-card');
    btn.setAttribute('aria-expanded','false');
    btn.addEventListener('click', () => {
      const expanded = card.classList.toggle('expanded');
      btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
  });
})();

// Hide important note when assessment modal closes
document.getElementById('assessmentModal')?.addEventListener('hidden.bs.modal', () => {
  showImportantNote(false);
  detachModalKeybindings();
});

// Add keybindings when modal opens
document.getElementById('assessmentModal')?.addEventListener('shown.bs.modal', () => {
  attachModalKeybindings();
});

function showQuestion() {
  const container = document.getElementById('questionsContainer');
  const pb = document.getElementById("progressBar");
  const counter = document.getElementById('questionCounter');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const btnSubmit = document.getElementById('btnSubmit');

  if (!questions || questions.length === 0) {
    container.innerHTML = '<p class="text-muted mb-0">No questions found.</p>';
    pb.style.width = '0%';
    pb.textContent = '0%';
    pb.setAttribute('aria-valuenow', '0');
    if (counter) counter.textContent = '';
    btnPrev.disabled = true;
    btnNext.classList.add('d-none');
    btnSubmit.classList.add('d-none');
    return;
  }

  container.style.opacity = '0';
  setTimeout(() => {
    const q = questions[currentIndex];
    const qTextId = 'questionText';

    container.innerHTML = `
      <div class="mb-2 question-meta">Question ${currentIndex + 1} of ${questions.length}</div>
      <h5 class="mb-3" id="${qTextId}">${escapeHtml(q.question_text)}</h5>
      <div class="list-group" role="radiogroup" aria-labelledby="${qTextId}">
        ${renderOption(q.id, 0, '<span class="text-muted">&#128524;</span> Not at all')}
        ${renderOption(q.id, 1, '<span class="text-warning">&#128528;</span> Several days')}
        ${renderOption(q.id, 2, '<span class="text-orange">&#128533;</span> More than half the days')}
        ${renderOption(q.id, 3, '<span class="text-danger">&#128561;</span> Nearly every day')}
      </div>
    `;

    const percent = Math.round(((currentIndex + 1) / questions.length) * 100);
    pb.style.width = percent + "%";
    pb.textContent = percent + "%";
    pb.setAttribute('aria-valuenow', String(percent));
    if (counter) counter.textContent = `Progress: ${currentIndex + 1} / ${questions.length}`;

    btnPrev.disabled = currentIndex === 0;
    const isLast = currentIndex === questions.length - 1;
    btnNext.classList.toggle('d-none', isLast);
    btnSubmit.classList.toggle('d-none', !isLast);

    updateNavState();

    container.style.opacity = '1';
  }, 80);
}

function renderOption(qid, value, labelHtml) {
  const checked = (answers[qid] == value) ? 'checked' : '';
  const inputId = `q${qid}_${value}`;
  return `
    <label class="list-group-item d-flex align-items-center gap-2" for="${inputId}">
      <input id="${inputId}" type="radio" name="q${qid}" value="${value}" class="form-check-input me-2"
        ${checked} onchange="onAnswerChange(${qid}, ${value})">
      <span>${labelHtml}</span>
    </label>
  `;
}

function onAnswerChange(qid, value) {
  answers[qid] = parseInt(value);
  updateNavState();
}

function updateNavState() {
  const q = questions[currentIndex];
  const hasAnswer = q && typeof answers[q.id] !== 'undefined';
  const btnNext = document.getElementById('btnNext');
  const btnSubmit = document.getElementById('btnSubmit');
  const selectionHint = document.getElementById('selectionHint');

  if (btnNext && !btnNext.classList.contains('d-none')) {
    btnNext.disabled = !hasAnswer;
  }
  if (btnSubmit && !btnSubmit.classList.contains('d-none')) {
    btnSubmit.disabled = !hasAnswer;
  }

  if (!hasAnswer) {
    selectionHint.textContent = 'Select an option to continue';
    selectionHint.classList.remove('text-success');
  } else {
    selectionHint.textContent = 'Great, you can proceed';
    selectionHint.classList.add('text-success');
  }
}

function nextQuestion() {
  if (currentIndex < questions.length - 1) {
    currentIndex++;
    showQuestion();
  }
}
function prevQuestion() {
  if (currentIndex > 0) {
    currentIndex--;
    showQuestion();
  }
}

function submitAssessment() {
  for (const q of questions) {
    if (typeof answers[q.id] === 'undefined') {
      alert('Please answer all questions before submitting.');
      return;
    }
  }

  fetch("submit_assessment.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({ assessment_id: assessmentId, answers: answers })
  })
  .then(async res => {
    const txt = await res.text();
    try {
      return JSON.parse(txt);
    } catch (e) {
      console.error('submit_assessment: non-JSON response:', txt);
      throw new Error('Invalid JSON');
    }
  })
  .then(data => {
    if (data.error && !data.already_completed) {
      alert("Error: " + data.error);
      return;
    }

    const takeModal = bootstrap.Modal.getInstance(document.getElementById('assessmentModal'));
    if (takeModal) takeModal.hide();

    showImportantNote(false);

    const score = typeof data.score !== 'undefined' ? parseInt(data.score, 10) : 0;
    const category = data.category || deriveCategory(score);

    const cardBtn = document.querySelector(`.take-assessment-btn[data-id='${assessmentId}']`);
    if (cardBtn) {
      const card = cardBtn.closest('.assessment-card');
      if (card) {
        const statusHolder = card.querySelector('.badge-not-taken, .text-muted.small');
        if (statusHolder) statusHolder.outerHTML = '<span class="badge badge-completed rounded-pill px-2 py-1"><i class="bi bi-check2-circle"></i> Completed</span>';
        const btnGroup = card.querySelector('.btn-group');
        if (btnGroup) {
          btnGroup.innerHTML = `
            <button class="btn btn-outline-primary btn-sm view-result-btn"
                    aria-label="View result for ${escapeHtml(assessmentTitle)}"
                    data-id="${assessmentId}"
                    data-title="${escapeHtml(assessmentTitle)}"
                    data-score="${score}"
                    data-category="${escapeHtml(category)}"
                    data-bs-toggle="modal"
                    data-bs-target="#resultModal"><i class=\"bi bi-graph-up-arrow\"></i> View Result</button>`;
          btnGroup.querySelector('.view-result-btn').addEventListener('click', onViewResultClick);
        }
        card.setAttribute('data-completed', '1');
      }
    }

    showResult(score, category, assessmentTitle);
  })
  .catch(err => {
    alert('Error submitting: ' + err);
    showImportantNote(false);
  });
}

function onViewResultClick(e) {
  const btn = e.currentTarget;
  const id = btn.getAttribute('data-id');
  const title = btn.getAttribute('data-title');
  const preScore = btn.getAttribute('data-score');
  const preCategory = btn.getAttribute('data-category');

  showImportantNote(false);

  if (preScore !== null && preScore !== '') {
    showResult(parseInt(preScore, 10), preCategory, title);
  } else {
    fetch(`get_assessment_result.php?assessment_id=${encodeURIComponent(id)}`)
      .then(res => res.json())
      .then(data => {
        if (data && !data.error) {
          const sc = parseInt(data.score || 0, 10);
          showResult(sc, data.category || deriveCategory(sc), title);
        } else {
          alert(data.error || 'No result found');
        }
      })
      .catch(() => alert('Failed to load result'));
  }
}

Array.from(document.querySelectorAll('.view-result-btn')).forEach(btn => {
  btn.addEventListener('click', onViewResultClick);
});

/* score is a severity percentage: 0% = minimal/no symptoms, 100% = high symptoms */
function showResult(score, category, title) {
  const circle = document.getElementById('resultCircle');
  const percentSpan = document.getElementById('resultPercent');
  const statusSpan = document.getElementById('resultStatus');
  const msgP = document.getElementById('resultMessage');
  const titleH = document.getElementById('resultAssessmentTitle');

  if (title) titleH.textContent = title + ' — Assessment Complete';

  const pct = Math.max(0, Math.min(100, parseInt(score, 10) || 0));
  circle.style.setProperty('--val', pct);
  percentSpan.textContent = pct + '%';

  const cat = category || deriveCategory(pct);
  statusSpan.textContent = cat;

  // Messages aligned with severity (low = good)
  let msg = 'You may be experiencing some challenges that are worth addressing.';
  if (cat === 'Good') {
    msg = 'Great job! Minimal or no symptoms reported. Keep maintaining healthy habits and awareness.';
  } else if (cat === 'Fair') {
    msg = 'Mild to moderate symptoms reported. Consider applying recommendations and monitoring your well-being.';
  } else {
    msg = 'Higher symptom frequency reported. Consider seeking additional support and applying the recommendations provided.';
  }
  msgP.textContent = msg;

  document.getElementById('btnRetake').disabled = true;

  const modalEl = document.getElementById('resultModal');
  const m = bootstrap.Modal.getOrCreateInstance(modalEl);
  m.show();
}

/* Map severity percentage to category: lower = better */
function deriveCategory(score) {
  const pct = Math.max(0, Math.min(100, parseInt(score, 10) || 0));
  if (pct <= 20) return 'Good';
  if (pct <= 50) return 'Fair';
  return 'Bad';
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/* Keyboard shortcuts inside modal:
   - 1..4 to select options
   - ArrowRight to Next (if allowed)
   - ArrowLeft to Previous
*/
function onModalKeydown(e) {
  const modal = document.getElementById('assessmentModal');
  if (!modal || !modal.classList.contains('show')) return;

  const key = e.key;
  if (['1', '2', '3', '4'].includes(key)) {
    const q = questions[currentIndex];
    if (q) {
      const index = parseInt(key, 10) - 1; // 0..3
      const value = Math.max(0, Math.min(3, index));
      const input = document.getElementById(`q${q.id}_${value}`);
      if (input) {
        input.checked = true;
        onAnswerChange(q.id, value);
        input.focus();
      }
    }
  } else if (key === 'ArrowRight') {
    const nextBtn = document.getElementById('btnNext');
    if (nextBtn && !nextBtn.classList.contains('d-none') && !nextBtn.disabled) {
      nextQuestion();
    }
  } else if (key === 'ArrowLeft') {
    const prevBtn = document.getElementById('btnPrev');
    if (prevBtn && !prevBtn.disabled) {
      prevQuestion();
    }
  }
}

function attachModalKeybindings() { document.addEventListener('keydown', onModalKeydown); }
function detachModalKeybindings() { document.removeEventListener('keydown', onModalKeydown); }
</script>
</body>
</html>