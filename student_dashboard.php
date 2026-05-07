<?php
session_start();
include('db.php');

if (!isset($_SESSION['student_reg_no'])) {
    header('Location: index.php?view=student');
    exit;
}

$regNo = $_SESSION['student_reg_no'];

// Identify track from login-reg format
$track = $_SESSION['student_track'] ?? '';
if ($track !== 'fj' && $track !== 'df') {
    $letters = strtolower(preg_replace('/[^a-z]/i', '', $regNo));
    $lettersArray = str_split($letters);
    sort($lettersArray);
    $track = implode('', $lettersArray);
}
$allowedBranch = ($track === 'fj') ? 'BCA' : 'MCA';
$allowedYears = ($allowedBranch === 'BCA') ? ['1', '2', '3'] : ['1', '2'];

// Ensure approval table exists
$conn->query("CREATE TABLE IF NOT EXISTS update_requests (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    reg_no        VARCHAR(50) NOT NULL,
    request_type  ENUM('files','details') NOT NULL,
    semester_key  VARCHAR(20) DEFAULT NULL,
    reason        TEXT NOT NULL,
    status        ENUM('pending','approved','rejected') DEFAULT 'pending',
    guide_remark  TEXT DEFAULT NULL,
    requested_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    actioned_at   DATETIME DEFAULT NULL,
    used          TINYINT(1) DEFAULT 0,
    INDEX (reg_no), INDEX (status)
)");

// Handle approval request submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_approval'])) {
    $type   = $_POST['request_type'];
    $semKey = trim($_POST['semester_key'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $chk = $conn->prepare("SELECT id FROM update_requests
                           WHERE reg_no = ? AND request_type = ? AND semester_key = ? AND status = 'pending' AND used = 0");
    $chk->bind_param('sss', $regNo, $type, $semKey);
    $chk->execute();

    if ($chk->get_result()->num_rows === 0 && $reason !== '') {
        $ins = $conn->prepare("INSERT INTO update_requests (reg_no, request_type, semester_key, reason)
                               VALUES (?,?,?,?)");
        $ins->bind_param('ssss', $regNo, $type, $semKey, $reason);
        $ins->execute();
        $_SESSION['flash'] = ['ok', 'Request sent to your guide. You can update once they approve.'];
    } else {
        $_SESSION['flash'] = ['warn', 'You already have a pending request for this semester.'];
    }
    header("Location: student_dashboard.php");
    exit;
}

// Fetch existing submissions
$stmt = $conn->prepare("SELECT * FROM student_submissions WHERE reg_no = ? ORDER BY year, semester ASC");
$stmt->bind_param("s", $regNo);
$stmt->execute();
$existing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$existingByKey = [];
foreach ($existing as $row) {
    $existingByKey[$row['year'] . '|' . $row['semester']] = $row;
}
$prefill = !empty($existing) ? $existing[count($existing)-1] : [];

// Fetch approval requests
$reqStmt = $conn->prepare("SELECT * FROM update_requests WHERE reg_no=? ORDER BY requested_at DESC");
$reqStmt->bind_param('s', $regNo);
$reqStmt->execute();
$allRequests = $reqStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$reqIndex = [];
foreach ($allRequests as $r) {
    $k = $r['request_type'] . '|' . $r['semester_key'];
    if (!isset($reqIndex[$k])) $reqIndex[$k] = $r;
}

function reqState($reqIndex, $type, $semKey) {
    $r = $reqIndex[$type.'|'.$semKey] ?? null;
    if (!$r) return null;
    if ($r['status'] === 'approved' && !$r['used']) return 'approved';
    return $r['status'];
}

$guides = [
    'Dr. K. Gayatri','Dr. K. Santhi Sri','Dr. M. Srikanth Yadav',
    'Dr. N. Veeranjaneyulu','Dr. R.S. Padma Priya',
    'Dr. Siva Koteswararao Chinnam','Mrs. R. Swathika','R. Naga Sirisha',
];

$prefillKey   = ($prefill['year'] ?? '').'|'.($prefill['semester'] ?? '');
$prefillExist = isset($existingByKey[$prefillKey]) && $prefillKey !== '|';

// File upload keys for duplicate detection
$fileUploadKeys = [];
$uploadStmt = $conn->prepare("SELECT academic_year, semester FROM student_uploads WHERE reg_no = ?");
$uploadStmt->bind_param("s", $regNo);
$uploadStmt->execute();
$uploadStmt->bind_result($academic_year, $semester);
while ($uploadStmt->fetch()) {
    $ay = trim((string)$academic_year);
    if (preg_match('/year\s*([1-3])/i', $ay, $m)) {
        $y = $m[1];
    } elseif (in_array($ay, ['1','2','3'], true)) {
        $y = $ay;
    } else {
        $y = '2';
    }
    $s = strlen((string)$semester) <= 2 ? strtoupper((string)$semester) : (string)$semester;
    $fileUploadKeys[] = $y . '|' . $s;
}
$uploadStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Project Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-main.centered {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: center;
            gap: 28px;
            padding: 32px 24px 60px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .dashboard-main.centered .glass-panel {
            flex: 1 1 420px;
            max-width: 560px;
            margin-top: 0 !important;
        }

        .approval-notice {
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 0.88em;
            display: none;
            width: 100%;
            box-sizing: border-box;
        }
        .approval-notice.warn          { background: #3a2d00; border: 1.5px solid #ffcc00; }
        .approval-notice.pending-state { background: #2d2500; border: 1.5px solid #e6b800; }
        .approval-notice.rejected-state{ background: #3a0a0a; border: 1.5px solid #ff5050; }
        .approval-notice.approved-state{ background: #003a1a; border: 1.5px solid #00cc66; }

        .an-title  { font-weight: 700; margin-bottom: 5px; font-size: 0.94em; }
        .an-sub    { color: rgba(255,255,255,0.88); margin-bottom: 10px; font-size: 0.85em; line-height: 1.55; }
        .an-remark { font-style: italic; color: rgba(255,255,255,0.72); margin-bottom: 10px; font-size: 0.84em; }

        .an-textarea {
            width: 100%; padding: 9px 12px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.30);
            background: rgba(0,0,0,0.45); color: #fff;
            font-family: inherit; font-size: 0.88rem; outline: none;
            resize: none; min-height: 68px; margin-bottom: 8px;
            transition: border-color 0.15s;
            box-sizing: border-box;
        }
        .an-textarea:focus { border-color: rgba(255,204,0,0.8); }
        .an-textarea::placeholder { color: rgba(255,255,255,0.45); }

        .an-btn {
            width: 100%; padding: 10px; border-radius: 8px; cursor: pointer;
            font-weight: 700; font-size: 0.88rem; font-family: inherit;
            transition: filter 0.15s;
            box-sizing: border-box;
        }
        .an-btn:hover { filter: brightness(1.15); }
        .an-btn.warn-btn   { border: 1.5px solid #ffcc00; background: #4a3800; color: #ffdd33; }
        .an-btn.reject-btn { border: 1.5px solid #ff5050; background: #4a0f0f; color: #ff8080; }

        .alert-warn {
            background:rgba(255,204,0,0.1);
            border:1px solid #ffcc00;
            color:#ffcc00;
            border-radius:10px;
            padding:12px 16px;
            margin-bottom:16px;
            font-size:0.88em;
            display:none;
            width: 100%;
            box-sizing: border-box;
        }

        .flash-ok {
            padding:12px 16px;
            border-radius:8px;
            margin-bottom:8px;
            font-size:0.88em;
            background:rgba(0,255,136,0.1);
            border:1px solid rgba(0,255,136,0.3);
            color:#00ff88;
            width:100%;
            max-width:900px;
            box-sizing: border-box;
        }
        .flash-warn {
            padding:12px 16px;
            border-radius:8px;
            margin-bottom:8px;
            font-size:0.88em;
            background:rgba(255,204,0,0.1);
            border:1px solid rgba(255,204,0,0.35);
            color:#ffcc00;
            width:100%;
            max-width:900px;
            box-sizing: border-box;
        }

        /* Title Assistant */
        #title-assistant {
            transition: opacity 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        #title-assistant .keyword-chip {
            cursor: pointer;
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            display: inline-block;
            margin: 2px;
            transition: background 0.15s;
        }
        #title-assistant .keyword-chip:hover {
            background: rgba(255,255,255,0.25);
        }

        /* Originality badge pill */
        #originality-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 4px 13px;
            border-radius: 20px;
            letter-spacing: 0.03em;
            transition: background 0.25s, color 0.25s, border-color 0.25s;
        }
        #originality-badge.level-high {
            background: rgba(0, 204, 102, 0.18);
            color: #00ff88;
            border: 1.5px solid #00cc66;
        }
        #originality-badge.level-moderate {
            background: rgba(255, 204, 0, 0.15);
            color: #ffdd33;
            border: 1.5px solid #ffcc00;
        }
        #originality-badge.level-low {
            background: rgba(255, 68, 68, 0.18);
            color: #ff7070;
            border: 1.5px solid #ff4444;
        }

        /* Originality meter bar */
        .originality-bar-wrap {
            width: 100%;
            height: 5px;
            background: rgba(255,255,255,0.10);
            border-radius: 4px;
            margin: 8px 0 12px;
            overflow: hidden;
        }
        .originality-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.45s ease, background 0.3s;
        }

        .file-hint { font-size: 0.75em; color: var(--text-muted); margin-top: 4px; }

        @media (max-width: 900px) {
            .dashboard-main.centered {
                flex-direction: column;
                align-items: center;
            }
            .dashboard-main.centered .glass-panel {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="background-overlay"></div>
<div class="water-animation"></div>

<div class="dashboard-wrapper">
    <header class="top-nav">
        <div class="top-nav-left"><span class="brand-title">Department of Computer Applications — VFSTR</span></div>
        <div class="top-nav-right">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($regNo); ?></span>
            <a href="logout.php" class="btn-link nav-logout">Logout</a>
        </div>
    </header>

    <main class="dashboard-main centered">

        <?php if (!empty($_SESSION['flash'])): ?>
        <?php [$ft, $fm] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash-<?php echo $ft==='ok' ? 'ok' : 'warn'; ?>" style="width:100%;max-width:900px;">
            <?php echo htmlspecialchars($fm); ?>
        </div>
        <?php endif; ?>

        <!-- Project Details Form -->
        <div class="glass-panel floating">
            <div class="app-header">
                <h1>Submit / Update Project Details</h1>
                <p style="color:var(--text-muted);font-size:0.84em;">
                    Select Year first — Semester options will update automatically.
                </p>
            </div>

            <div id="details-notice" class="approval-notice"></div>

            <form method="post" action="thankyou.php" class="form-glass" id="details-form">
                <input type="hidden" name="initial_submit" value="1">

                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="reg_no" value="<?php echo htmlspecialchars($regNo); ?>" readonly style="opacity:0.7;">
                </div>

                <div class="form-group">
                    <label for="student_name">Student Name</label>
                    <input type="text" id="student_name" name="student_name" required
                           placeholder="Your Full Name"
                           value="<?php echo htmlspecialchars($prefill['student_name'] ?? ''); ?>">
                </div>

                <div style="display:flex;gap:14px;">
                    <div class="form-group" style="flex:1;">
                        <label for="branch">Branch</label>
                        <select id="branch" name="branch" required>
                            <option value="<?php echo $allowedBranch; ?>" selected><?php echo $allowedBranch; ?></option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="year">Year</label>
                        <select id="year" name="year" required onchange="updateSemester()">
                            <option value="">-- Select Year --</option>
                            <?php foreach ($allowedYears as $yr): ?>
                            <option value="<?php echo $yr; ?>" <?php echo ($prefill['year'] ?? '') === $yr ? 'selected' : ''; ?>>Year <?php echo $yr; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="section">Section</label>
                    <select id="section" name="section" required>
                        <option value="">-- Select Section --</option>
                        <option value="A" <?php echo ($prefill['section']??'')==='A'?'selected':''; ?>>Section A</option>
                        <option value="B" <?php echo ($prefill['section']??'')==='B'?'selected':''; ?>>Section B</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required onchange="onSemChange()" disabled>
                        <option value="">-- Select Year first --</option>
                    </select>
                </div>

                <div id="dup-warn" class="alert-warn">
                    You have already submitted a project for this semester.
                    To update, request guide approval.
                </div>

                <div class="form-group">
                    <label for="domain">Project Domain</label>
                    <input type="text" id="domain" name="domain" required
                           placeholder="e.g. Machine Learning, Web Development, Cyber Security"
                           value="<?php echo htmlspecialchars($prefill['domain'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="project_title">Project Title</label>
                    <input type="text" id="project_title" name="project_title" required
                           placeholder="Full project name"
                           value="<?php echo htmlspecialchars($prefill['project_title'] ?? ''); ?>">
                    <!-- Title Assistant Container -->
                    <div id="title-assistant" style="display:none; margin-top: 8px; background: rgba(0,0,0,0.35); border-radius: 12px; padding: 14px 16px; border: 1px solid rgba(255,255,255,0.18); color: #fff;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-weight:700; font-size:0.9rem;">🧠 Title Assistant</span>
                            <span id="originality-badge">—</span>
                        </div>
                        <!-- Originality progress bar -->
                        <div class="originality-bar-wrap">
                            <div class="originality-bar-fill" id="originality-bar" style="width:0%;"></div>
                        </div>
                        <div id="sim-titles" style="font-size:0.8rem; margin-bottom:6px;"></div>
                        <div id="suggest-keywords" style="font-size:0.78rem; color:rgba(255,255,255,0.7);"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="guide_name">Project Guide</label>
                    <select id="guide_name" name="guide_name" required>
                        <option value="">-- Select Guide --</option>
                        <?php foreach ($guides as $g): ?>
                        <?php $sel = ($prefill['guide_name'] ?? '') === $g ? 'selected' : ''; ?>
                        <option value="<?php echo $g; ?>" <?php echo $sel; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" id="details-submit-btn" class="btn-gradient" style="width:100%;">Submit / Update Project</button>
            </form>
        </div>

        <!-- File Upload Form (with Certificate input) -->
        <div class="glass-panel">
            <div class="app-header">
                <h2>Upload Project Files</h2>
                <p style="color:var(--text-muted);font-size:0.84em;">Select the year and semester you are uploading for, then attach your files.</p>
            </div>

            <div id="files-notice" class="approval-notice"></div>

            <?php if (!empty($_SESSION['upload_flash'])): ?>
            <div style="padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:0.88em;
                <?php echo stripos($_SESSION['upload_flash'],'success')!==false
                    ? 'background:rgba(0,255,136,0.1);border:1px solid rgba(0,255,136,0.3);color:#00ff88;'
                    : 'background:rgba(255,80,80,0.1);border:1px solid rgba(255,80,80,0.3);color:#ff8080;'; ?>">
                <?php echo htmlspecialchars($_SESSION['upload_flash']); unset($_SESSION['upload_flash']); ?>
            </div>
            <?php endif; ?>

            <form method="post" action="student_upload.php" class="form-glass" enctype="multipart/form-data" id="files-form">
                <input type="hidden" name="reg_no"   value="<?php echo htmlspecialchars($regNo); ?>">
                <input type="hidden" name="section"  value="<?php echo htmlspecialchars($prefill['section'] ?? ''); ?>">

                <div class="form-group">
                    <label for="ul_branch">Branch</label>
                    <select id="ul_branch" name="branch" required>
                        <option value="<?php echo $allowedBranch; ?>" selected><?php echo $allowedBranch; ?></option>
                    </select>
                </div>

                <div style="display:flex;gap:14px;">
                    <div class="form-group" style="flex:1;">
                        <label for="ul_year">Year</label>
                        <select id="ul_year" name="year" required onchange="updateUploadSem()">
                            <option value="">-- Select --</option>
                            <?php foreach ($allowedYears as $yr): ?>
                            <option value="<?php echo $yr; ?>">Year <?php echo $yr; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="ul_sem">Semester</label>
                        <select id="ul_sem" name="semester" required onchange="onUploadSemChange()" disabled>
                            <option value="">-- Select Year first --</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="doc_file">Document</label>
                    <input type="file" id="doc_file" name="doc_file" accept=".pdf,.doc,.docx">
                    <div class="file-hint">PDF, DOC, DOCX only</div>
                </div>

                <div class="form-group">
                    <label for="ppt_file">Presentation</label>
                    <input type="file" id="ppt_file" name="ppt_file" accept=".ppt,.pptx">
                    <div class="file-hint">PPT, PPTX only</div>
                </div>

                <div class="form-group">
                    <label for="code_file">Source Code / Report</label>
                    <input type="file" id="code_file" name="code_file" accept=".pdf,.doc,.docx">
                    <div class="file-hint">PDF, DOC, DOCX only</div>
                </div>

                <!-- NEW: Certificate Upload -->
                <div class="form-group">
                    <label for="cert_file">Certificate (Completion / Approval)</label>
                    <input type="file" id="cert_file" name="cert_file" accept=".pdf,.doc,.docx">
                    <div class="file-hint">Only PDF or Word files (.pdf, .doc, .docx)</div>
                </div>

                <button type="submit" id="files-submit-btn" class="btn-gradient" style="width:100%;">Upload Files</button>
            </form>
        </div>

    </main>
</div>

<script>
const projectSubs = <?php echo json_encode(array_keys($existingByKey)); ?>;
const fileUploads = <?php echo json_encode($fileUploadKeys); ?>;
const reqStates  = <?php
    $map = [];
    foreach ($reqIndex as $k => $r) {
        $state = ($r['status']==='approved' && !$r['used']) ? 'approved' : $r['status'];
        $map[$k] = [
            'status'  => $state,
            'remark'  => $r['guide_remark'] ?? '',
            'date'    => date('d M Y, h:i A', strtotime($r['requested_at'])),
            'used'    => (int)$r['used']
        ];
    }
    echo json_encode($map);
?>;

// --- Semester / duplicate logic for details form ---
function updateSemester() {
    const year    = document.getElementById('year').value;
    const semSel  = document.getElementById('semester');
    const dupWarn = document.getElementById('dup-warn');
    semSel.innerHTML = '';
    semSel.disabled  = true;
    dupWarn.style.display = 'none';
    clearNotice('details');

    if (!year) {
        semSel.add(new Option('-- Select Year first --', ''));
        return;
    }

    semSel.add(new Option('-- Select Semester --', ''));
    ['I','II'].forEach(function(s) {
        const key    = year + '|' + s;
        const exists = projectSubs.includes(key);
        const opt    = new Option('Semester ' + s + (exists ? '' : ''), s);
        if (exists) opt.setAttribute('data-exists','1');
        semSel.add(opt);
    });
    semSel.disabled = false;
}

function onSemChange() {
    const year   = document.getElementById('year').value;
    const semSel = document.getElementById('semester');
    const opt    = semSel.options[semSel.selectedIndex];
    const dupWarn = document.getElementById('dup-warn');
    const semKey = year + '|' + semSel.value;

    dupWarn.style.display = (opt && opt.getAttribute('data-exists') === '1') ? 'block' : 'none';
    if (opt && opt.getAttribute('data-exists') === '1') {
        showNotice('details', semKey);
    } else {
        clearNotice('details');
    }
}

// --- Upload form semester logic ---
function updateUploadSem() {
    const year = document.getElementById('ul_year').value;
    const semSel = document.getElementById('ul_sem');
    semSel.innerHTML = '';
    semSel.disabled = true;
    clearNotice('files');

    if (!year) {
        semSel.add(new Option('-- Select Year first --', ''));
        semSel.disabled = false;
        return;
    }

    semSel.add(new Option('-- Select Semester --', ''));
    ['I','II'].forEach(function(s) {
        semSel.add(new Option('Semester ' + s, s));
    });
    semSel.disabled = false;
}

function onUploadSemChange() {
    const year   = document.getElementById('ul_year').value;
    const semSel = document.getElementById('ul_sem');
    const semKey = year + '|' + semSel.value;
    if (year && semSel.value && fileUploads.includes(semKey)) {
        showNotice('files', semKey);
    } else {
        clearNotice('files');
    }
}

// --- Approval notice rendering ---
function showNotice(form, semKey) {
    const box = document.getElementById(form + '-notice');
    const stateKey = form + '|' + semKey;
    const req = reqStates[stateKey] || null;
    const status = req ? req.status : null;
    const used = req ? req.used : 0;

    box.className = 'approval-notice';
    box.style.display = 'block';
    box.innerHTML = '';

    if (status === 'approved' && used === 0) {
        box.classList.add('approved-state');
        box.innerHTML = '<div class="an-title" style="color:#00ff88;">Guide Approved</div>' +
            '<div class="an-sub">Your guide approved this update. You can submit the form below.</div>';
        enableSubmit(form);
    } else if (status === 'approved' && used === 1) {
        box.classList.add('warn');
        const semLabel = semKey.replace('|', ' — Semester ');
        const label = form === 'details' ? 'update project details' : 'resubmit files';
        box.innerHTML = '<div class="an-title" style="color:#ffcc00;">Approval already used for Year ' + semLabel + '</div>' +
            '<div class="an-sub">To ' + label + ', send a new request.</div>' +
            reqFormHTML(form, semKey);
        disableSubmit(form);
    } else if (status === 'pending') {
        box.classList.add('pending-state');
        box.innerHTML = '<div class="an-title" style="color:#ffcc00;">Approval Pending</div>' +
            '<div class="an-sub">Your request is waiting. Sent on ' + req.date + '</div>';
        disableSubmit(form);
    } else if (status === 'rejected') {
        box.classList.add('rejected-state');
        const remarkHtml = req.remark ? '<div class="an-remark">Guide\'s reason: "' + escHtml(req.remark) + '"</div>' : '';
        box.innerHTML = '<div class="an-title" style="color:#ff8080;">Rejected by Guide</div>' + remarkHtml +
            '<div class="an-sub">You can send a new request.</div>' + reqFormHTML(form, semKey);
        disableSubmit(form);
    } else {
        // Regular duplicate with no approval yet
        box.classList.add('warn');
        const semLabel = semKey.replace('|', ' — Semester ');
        const label = form === 'details' ? 'update project details' : 'resubmit files';
        box.innerHTML = '<div class="an-title" style="color:#ffcc00;">Already submitted for Year ' + semLabel + '</div>' +
            '<div class="an-sub">To ' + label + ', provide a reason and request guide approval.</div>' +
            reqFormHTML(form, semKey);
        disableSubmit(form);
    }
}

function clearNotice(form) {
    const box = document.getElementById(form + '-notice');
    box.style.display = 'none';
    box.innerHTML = '';
    enableSubmit(form);
}

function reqFormHTML(form, semKey) {
    return '<form method="POST">' +
        '<input type="hidden" name="request_approval" value="1">' +
        '<input type="hidden" name="request_type" value="' + form + '">' +
        '<input type="hidden" name="semester_key" value="' + semKey + '">' +
        '<textarea class="an-textarea" name="reason" required placeholder="Explain why you need to update..."></textarea>' +
        '<button type="submit" class="an-btn warn-btn">Send Request to Guide</button>' +
        '</form>';
}

function disableSubmit(form) {
    const btn = document.getElementById(form + '-submit-btn');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.35';
        btn.style.cursor = 'not-allowed';
    }
}
function enableSubmit(form) {
    const btn = document.getElementById(form + '-submit-btn');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─────── Title Assistant (Smart Originality + Suggestion) ───────
let titleDebounceTimer;
const titleInput = document.getElementById('project_title');
const assistantDiv = document.getElementById('title-assistant');
const simTitlesDiv = document.getElementById('sim-titles');
const suggestKwDiv = document.getElementById('suggest-keywords');
const originalityBadge = document.getElementById('originality-badge');
const branchInput = document.getElementById('branch');
const studentBranch = branchInput ? branchInput.value : '';

titleInput.addEventListener('input', function() {
    const val = this.value.trim();
    if (val.length < 5) {
        assistantDiv.style.display = 'none';
        return;
    }
    clearTimeout(titleDebounceTimer);
    titleDebounceTimer = setTimeout(() => fetchAssistant(val), 400);
});

function fetchAssistant(query) {
    const xhr = new XMLHttpRequest();
    const url = `title-assistant.php?q=${encodeURIComponent(query)}&branch=${encodeURIComponent(studentBranch)}`;
    xhr.open('GET', url, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                renderAssistant(data);
            } catch(e) {
                assistantDiv.style.display = 'none';
            }
        } else {
            assistantDiv.style.display = 'none';
        }
    };
    xhr.onerror = function() {
        assistantDiv.style.display = 'none';
    };
    xhr.send();
}

function renderAssistant(data) {
    if (!data || data.error) {
        assistantDiv.style.display = 'none';
        return;
    }

    assistantDiv.style.display = 'block';

    // --- Originality badge ---
    const score = data.originality_score ?? 0;
    const level = data.originality_level ?? (score >= 75 ? 'high' : score >= 50 ? 'moderate' : 'low');
    const label = data.originality_label ?? (level === 'high' ? 'High' : level === 'moderate' ? 'Moderate' : 'Low');

    const icons = { high: '🟢', moderate: '🟡', low: '🔴' };

    originalityBadge.textContent = icons[level] + ' ' + label + ' (' + score + '%)';
    originalityBadge.className   = 'level-' + level;   // triggers CSS pill color

    // --- Originality progress bar ---
    const bar = document.getElementById('originality-bar');
    if (bar) {
        bar.style.width = score + '%';
        bar.style.background = level === 'high' ? '#00cc66' : level === 'moderate' ? '#ffcc00' : '#ff4444';
    }

    // --- Update assistant panel border color to match ---
    const borderColors = { high: 'rgba(0,204,102,0.45)', moderate: 'rgba(255,204,0,0.45)', low: 'rgba(255,68,68,0.45)' };
    assistantDiv.style.borderColor = borderColors[level];

    // --- Similar titles ---
    if (data.similar_titles && data.similar_titles.length > 0) {
        let html = '<div style="margin-bottom:8px; font-weight:600; font-size:0.85rem;">🔍 Similar existing titles:</div>';
        data.similar_titles.forEach(item => {
            const simColor = item.similarity >= 70 ? '#ff7070' : item.similarity >= 40 ? '#ffcc00' : '#aaa';
            html += `<div style="padding:4px 0; color:rgba(255,255,255,0.82); display:flex; justify-content:space-between; align-items:center; gap:8px;">
                        <span>• ${escHtml(item.title)}</span>
                        <span style="color:${simColor}; font-size:0.75rem; white-space:nowrap; font-weight:600;">${item.similarity}% match</span>
                     </div>`;
        });
        simTitlesDiv.innerHTML = html;
    } else {
        simTitlesDiv.innerHTML = '<div style="color:rgba(255,255,255,0.45); font-size:0.8rem;">✅ No close matches found in existing titles.</div>';
    }

    // --- Suggested keywords ---
    if (data.suggested_keywords && data.suggested_keywords.length > 0) {
        suggestKwDiv.innerHTML = `<div style="font-weight:600; font-size:0.8rem; margin:8px 0 4px;">💡 Add keywords to improve uniqueness:</div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                ${data.suggested_keywords.map(kw =>
                    `<span class="keyword-chip" onclick="appendKeyword('${escHtml(kw)}')">+ ${escHtml(kw)}</span>`
                ).join('')}
            </div>`;
    } else {
        suggestKwDiv.innerHTML = '';
    }
}

function appendKeyword(word) {
    const input = document.getElementById('project_title');
    const current = input.value.trim();
    if (current && !current.endsWith(' ')) {
        input.value = current + ' ' + word;
    } else {
        input.value = current + word;
    }
    input.dispatchEvent(new Event('input'));
}

// Preload semester if year is already selected
window.addEventListener('DOMContentLoaded', function() {
    const yr = document.getElementById('year').value;
    if (yr) updateSemester();
});
</script>
</body>
</html>