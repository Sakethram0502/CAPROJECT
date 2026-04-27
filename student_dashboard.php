<?php
session_start();
include('db.php');

if (!isset($_SESSION['student_reg_no'])) {
    header('Location: index.php?view=student');
    exit;
}

$regNo = $_SESSION['student_reg_no'];

// ── Ensure approval table exists ─────────────────────────────────────
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

// ── Student sends approval request ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_approval'])) {
    $type   = $_POST['request_type'];
    $semKey = trim($_POST['semester_key'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // Block if same pending request already exists
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
        $_SESSION['flash'] = ['warn', 'You already have a pending request for this semester. Wait for your guide to respond.'];
    }
    header("Location: student_dashboard.php");
    exit;
}

// ── Fetch project submissions ────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM student_submissions WHERE reg_no = ? ORDER BY year, semester ASC");
$stmt->bind_param("s", $regNo);
$stmt->execute();
$existing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$existingByKey = [];
foreach ($existing as $row) {
    $existingByKey[$row['year'] . '|' . $row['semester']] = $row;
}
$prefill = !empty($existing) ? $existing[count($existing)-1] : [];

// ── Fetch approval requests ──────────────────────────────────────────
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
function reqRow($reqIndex, $type, $semKey) {
    return $reqIndex[$type.'|'.$semKey] ?? null;
}

$guides = [
    'Dr. K. Gayatri','Dr. K. Santhi Sri','Dr. M. Srikanth Yadav',
    'Dr. N. Veeranjaneyulu','Dr. R.S. Padma Priya',
    'Dr. Siva Koteswararao Chinnam','Mrs. R. Swathika','R. Naga Sirisha',
];

$prefillKey   = ($prefill['year'] ?? '').'|'.($prefill['semester'] ?? '');
$prefillExist = isset($existingByKey[$prefillKey]) && $prefillKey !== '|';

// ── File Upload Keys (student_uploads table) ──────────────────────────
$fileUploadKeys = [];
$uploadStmt = $conn->prepare("SELECT academic_year, semester FROM student_uploads WHERE reg_no = ?");
$uploadStmt->bind_param("s", $regNo);
$uploadStmt->execute();
$uploads = $uploadStmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($uploads as $u) {
    $y = ($u['academic_year'] == 'Year 1') ? '1' : '2';
    $s = strlen($u['semester']) <= 2 ? strtoupper($u['semester']) : $u['semester'];
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

        .file-hint  { font-size:0.75em; color:var(--text-muted); margin-top:4px; }

        select:disabled {
            opacity:0.45;
            cursor:not-allowed;
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
                    If a record exists for that semester, you will see an update screen.
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
                            <option value="">-- Select --</option>
                            <option value="BCA" <?php echo ($prefill['branch']??'')==='BCA'?'selected':''; ?>>BCA</option>
                            <option value="MCA" <?php echo ($prefill['branch']??'')==='MCA'||empty($prefill)?'selected':''; ?>>MCA</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="year">Year</label>
                        <select id="year" name="year" required onchange="updateSemester()">
                            <option value="">-- Select Year --</option>
                            <option value="1" <?php echo ($prefill['year']??'')==='1'?'selected':''; ?>>Year 1</option>
                            <option value="2" <?php echo ($prefill['year']??'')==='2'?'selected':''; ?>>Year 2</option>
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
                    Submitting will open the <strong>Update</strong> screen with your existing details pre-filled.
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

        <!-- File Upload Form -->
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
                <input type="hidden" name="branch"   value="<?php echo htmlspecialchars($prefill['branch'] ?? 'MCA'); ?>">
                <input type="hidden" name="section"  value="<?php echo htmlspecialchars($prefill['section'] ?? ''); ?>">

                <div style="display:flex;gap:14px;">
                    <div class="form-group" style="flex:1;">
                        <label for="ul_year">Year</label>
                        <select id="ul_year" name="year" required onchange="updateUploadSem()">
                            <option value="">-- Select --</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
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

// --- Project Details Form logic ---

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
        const exists = projectSubs.includes(key); // ✅ FIXED: was 'submitted', now 'projectSubs'
        const label  = 'Semester ' + s + (exists ? '' : '');
        const opt    = new Option(label, s);
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

// --- File Upload Form logic ---

function updateUploadSem() {
    const year = document.getElementById('ul_year').value;
    const semSel = document.getElementById('ul_sem');

    while (semSel.options.length > 0) {
        semSel.remove(0);
    }

    semSel.disabled = true;
    clearNotice('files');

    if (!year) {
        semSel.add(new Option('-- Select Year first --', ''));
        semSel.disabled = false;
        return;
    }

    semSel.add(new Option('-- Select Semester --', ''));
    ['I', 'II'].forEach(function(s) {
        const label = 'Semester ' + s;
        const opt = new Option(label, s);
        semSel.add(opt);
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

// --- Show notice for details or files ---

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
        box.innerHTML = '<div class="an-title" style="color:#00ff88;">Guide Approved</div>'
            + '<div class="an-sub">Your guide approved this update. You can submit the form below.</div>';
        enableSubmit(form);
    }
    else if (status === 'approved' && used === 1) {
        box.classList.add('warn');
        const semLabel = semKey.replace('|', ' — Semester ');
        const label = form === 'details' ? 'update project details' : 'resubmit files';
        box.innerHTML = '<div class="an-title" style="color:#ffcc00;">You have already used your guide approval for Year ' + semLabel + '</div>'
            + '<div class="an-sub">To ' + label + ', please send a new approval request to your guide.</div>'
            + '<form method="POST">'
            + '<input type="hidden" name="request_approval" value="1">'
            + '<input type="hidden" name="request_type" value="' + form + '">'
            + '<input type="hidden" name="semester_key" value="' + semKey + '">'
            + '<textarea class="an-textarea" name="reason" required placeholder="Explain why you need to update again..."></textarea>'
            + '<button type="submit" class="an-btn warn-btn">Send New Request to Guide</button>'
            + '</form>';
        disableSubmit(form);
    }
    else if (status === 'pending') {
        box.classList.add('pending-state');
        box.innerHTML = '<div class="an-title" style="color:#ffcc00;">Approval Pending</div>'
            + '<div class="an-sub">Your request is waiting for guide approval. Sent on ' + req.date + '</div>';
        disableSubmit(form);
    }
    else if (status === 'rejected') {
        box.classList.add('rejected-state');
        const remark = req.remark ? '<div class="an-remark">Guide\'s reason: "' + escHtml(req.remark) + '"</div>' : '';
        box.innerHTML = '<div class="an-title" style="color:#ff8080;">Request Rejected by Guide</div>'
            + remark
            + '<div class="an-sub">You can send a new request explaining why you need to '
            + (form==='details'?'update details':'resubmit files')
            + '.</div>'
            + '<form method="POST">'
            + '<input type="hidden" name="request_approval" value="1">'
            + '<input type="hidden" name="request_type" value="' + form + '">'
            + '<input type="hidden" name="semester_key" value="' + semKey + '">'
            + '<textarea class="an-textarea" name="reason" required placeholder="Explain your reason..."></textarea>'
            + '<button type="submit" class="an-btn reject-btn">Send New Request to Guide</button>'
            + '</form>';
        disableSubmit(form);
    }
    else {
        box.classList.add('warn');
        const semLabel = semKey.replace('|', ' — Semester ');
        const label = form === 'details' ? 'update project details' : 'resubmit files';
        box.innerHTML = '<div class="an-title" style="color:#ffcc00;">You have already submitted for Year ' + semLabel + '</div>'
            + '<div class="an-sub">You have already ' + (form==='details'?'submitted project details':'uploaded files') + ' for this semester. '
            + 'To ' + label + ', please provide a reason and request your guide\'s approval.</div>'
            + '<form method="POST">'
            + '<input type="hidden" name="request_approval" value="1">'
            + '<input type="hidden" name="request_type" value="' + form + '">'
            + '<input type="hidden" name="semester_key" value="' + semKey + '">'
            + '<textarea class="an-textarea" name="reason" required placeholder="e.g. Wrong title entered, updated report after feedback..."></textarea>'
            + '<button type="submit" class="an-btn warn-btn">Send Request to Guide</button>'
            + '</form>';
        disableSubmit(form);
    }
}

function clearNotice(form) {
    const box = document.getElementById(form + '-notice');
    box.style.display = 'none';
    box.innerHTML = '';
    enableSubmit(form);
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

// On page load: populate semester if year is pre-filled
window.addEventListener('DOMContentLoaded', function() {
    const yr = document.getElementById('year').value;
    if (yr) updateSemester();
});
</script>
</body>
</html>