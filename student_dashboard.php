<?php
session_start();
include('db.php');

if (!isset($_SESSION['student_reg_no'])) {
    header('Location: index.php?view=student');
    exit;
}

$regNo = $_SESSION['student_reg_no'];

<<<<<<< Updated upstream
// Fetch existing submissions for pre-fill and duplicate check
=======
// Identify track from login-reg format:
// FJ => BCA (Years 1-3), FD => MCA (Years 1-2)
$track = $_SESSION['student_track'] ?? '';
if ($track !== 'fj' && $track !== 'df') {
    $letters = strtolower(preg_replace('/[^a-z]/i', '', $regNo));
    $lettersArray = str_split($letters);
    sort($lettersArray);
    $track = implode('', $lettersArray);
}
$allowedBranch = ($track === 'fj') ? 'BCA' : 'MCA';
$allowedYears = ($allowedBranch === 'BCA') ? ['1', '2', '3'] : ['1', '2'];

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
>>>>>>> Stashed changes
$stmt = $conn->prepare("SELECT * FROM student_submissions WHERE reg_no = ? ORDER BY year, semester ASC");
$stmt->bind_param("s", $regNo);
$stmt->execute();
$existing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Map by "year|semester" for duplicate check e.g. "1|I", "2|II"
$existingByKey = [];
foreach ($existing as $row) {
    $existingByKey[$row['year'] . '|' . $row['semester']] = $row;
}

// Pre-fill from latest record
$prefill = !empty($existing) ? $existing[count($existing)-1] : [];

// Existing uploads for enforcing upload order in UI
$uploadStmt = $conn->prepare("SELECT academic_year, semester FROM student_uploads WHERE reg_no = ? ORDER BY id ASC");
$uploadStmt->bind_param("s", $regNo);
$uploadStmt->execute();
<<<<<<< Updated upstream
$uploadRows = $uploadStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$uploadedSlots = [];
foreach ($uploadRows as $row) {
    $academicYear = (string)($row['academic_year'] ?? '');
    $semesterVal  = strtoupper(trim((string)($row['semester'] ?? '')));
    if (preg_match('/(\d+)/', $academicYear, $m) && in_array($semesterVal, ['I', 'II'], true)) {
        $uploadedSlots[] = $m[1] . '|' . $semesterVal;
    }
=======
$uploads = $uploadStmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($uploads as $u) {
    $ay = trim((string)($u['academic_year'] ?? ''));
    if (preg_match('/year\s*([1-3])/i', $ay, $m)) {
        $y = $m[1];
    } elseif (in_array($ay, ['1', '2', '3'], true)) {
        $y = $ay;
    } else {
        $y = '2';
    }
    $s = strlen($u['semester']) <= 2 ? strtoupper($u['semester']) : $u['semester'];
    $fileUploadKeys[] = $y . '|' . $s;
>>>>>>> Stashed changes
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Project Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .alert-warn{ background:var(--gold-pale); border:1px solid #7dd3fc; color:#0e4a72;
                     border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:0.88em; display:none; }
        .file-hint { font-size:0.75em; color:var(--text-muted); margin-top:4px; }
        select:disabled { opacity:0.45; cursor:not-allowed; }
        .dashboard-main.centered { display:flex; align-items:flex-start; justify-content:center; flex-wrap:wrap; gap:24px; }
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
        <!-- ── PROJECT DETAILS FORM ──────────────────────────────── -->
        <div class="glass-panel floating">
            <div class="app-header">
                <h1>Submit / Update Project Details</h1>
                <p style="color:var(--text-muted);font-size:0.84em;">
                    Select Year first — Semester options will update automatically.
                    If a record exists for that semester, you will see an update screen.
                </p>
            </div>

            <form method="post" action="thankyou.php" class="form-glass">
                <input type="hidden" name="initial_submit" value="1">

                <!-- Reg No -->
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="reg_no"
                           value="<?php echo htmlspecialchars($regNo); ?>"
                           readonly style="opacity:0.7;">
                </div>

                <!-- Student Name -->
                <div class="form-group">
                    <label for="student_name">Student Name</label>
                    <input type="text" id="student_name" name="student_name" required
                           placeholder="Your Full Name"
                           value="<?php echo htmlspecialchars($prefill['student_name'] ?? ''); ?>">
                </div>

                <!-- Branch + Year -->
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

                <!-- Section -->
                <div class="form-group">
                    <label for="section">Section</label>
                    <select id="section" name="section" required>
                        <option value="">-- Select Section --</option>
                        <option value="A" <?php echo ($prefill['section']??'')==='A'?'selected':''; ?>>Section A</option>
                        <option value="B" <?php echo ($prefill['section']??'')==='B'?'selected':''; ?>>Section B</option>
                    </select>
                </div>

                <!-- Semester — populated by JS based on Year selection -->
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required onchange="checkDuplicate()" disabled>
                        <option value="">-- Select Year first --</option>
                    </select>
                </div>

                <!-- Duplicate warning -->
                <div id="dup-warn" class="alert-warn">
                    ⚠️ You already submitted a project for this semester.
                    Submitting will open the <strong>Update</strong> screen with your existing details pre-filled.
                </div>

                <!-- Domain -->
                <div class="form-group">
                    <label for="domain">Project Domain</label>
                    <input type="text" id="domain" name="domain" required
                           placeholder="e.g. Machine Learning, Web Dev, Cyber Security">
                </div>

                <!-- Project Title -->
                <div class="form-group">
                    <label for="project_title">Project Title</label>
                    <input type="text" id="project_title" name="project_title" required
                           placeholder="Full project name">
                </div>

                <!-- Guide -->
                <div class="form-group">
                    <label for="guide_name">Project Guide</label>
                    <select id="guide_name" name="guide_name" required>
                        <option value="">-- Select Guide --</option>
                        <?php
                        $guides = [
                            'Dr. K. Gayatri',
                            'Dr. K. Santhi Sri',
                            'Dr. M. Srikanth Yadav',
                            'Dr. N. Veeranjaneyulu',
                            'Dr. R.S. Padma Priya',
                            'Dr. Siva Koteswararao Chinnam',
                            'Mrs. R. Swathika',
                            'R. Naga Sirisha',
                        ];
                        foreach ($guides as $g):
                            $sel = ($prefill['guide_name'] ?? '') === $g ? 'selected' : '';
                        ?>
                        <option value="<?php echo $g; ?>" <?php echo $sel; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-gradient" style="width:100%;">Submit / Update Project</button>
            </form>
        </div>

        <!-- ── FILE UPLOAD ────────────────────────────────────────── -->
        <div class="glass-panel" style="margin-top:28px;">
            <div class="app-header">
                <h2>Upload Project Files</h2>
                <p style="color:var(--text-muted);font-size:0.84em;">Upload is allowed only in order (Year 1 Sem I, then Year 1 Sem II, and so on).</p>
            </div>

            <?php if (!empty($_SESSION['upload_flash'])): ?>
                <div style="padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:0.88em;
                    <?php echo strpos($_SESSION['upload_flash'],'success')!==false||strpos($_SESSION['upload_flash'],'Success')!==false
                        ? 'background:var(--green-pale);border:1px solid var(--border);color:var(--green-dark);'
                        : 'background:rgba(255,80,80,0.1);border:1px solid #ff5050;color:#ff5050;'; ?>">
                    <?php echo htmlspecialchars($_SESSION['upload_flash']); unset($_SESSION['upload_flash']); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="student_upload.php" class="form-glass" enctype="multipart/form-data">

                <!-- Hidden fields the upload script needs -->
                <input type="hidden" name="reg_no"   value="<?php echo htmlspecialchars($regNo); ?>">
                <input type="hidden" name="section"  value="<?php echo htmlspecialchars($prefill['section'] ?? ''); ?>">

<<<<<<< Updated upstream
                <!-- Year + Semester visible to student -->
=======
                <div class="form-group">
                    <label for="ul_branch">Branch</label>
                    <select id="ul_branch" name="branch" required>
                        <option value="<?php echo $allowedBranch; ?>" selected><?php echo $allowedBranch; ?></option>
                    </select>
                </div>

>>>>>>> Stashed changes
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
                        <select id="ul_sem" name="semester" required disabled>
                            <option value="">-- Select Year first --</option>
                        </select>
                    </div>
                </div>

                <!-- Files -->
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

                <button type="submit" class="btn-gradient" style="width:100%;">Upload Files</button>
            </form>
        </div>

    </main>
</div>

<script>
// Existing submissions keyed by "year|semester"
const submitted = <?php echo json_encode(array_keys($existingByKey)); ?>;
const uploadedSlots = <?php echo json_encode(array_values(array_unique($uploadedSlots))); ?>;

function updateSemester() {
    const year    = document.getElementById('year').value;
    const semSel  = document.getElementById('semester');
    const dupWarn = document.getElementById('dup-warn');

    // Reset
    semSel.innerHTML = '';
    semSel.disabled  = true;
    dupWarn.style.display = 'none';

    if (!year) {
        semSel.add(new Option('-- Select Year first --', ''));
        return;
    }

    // Add Semester I and II for the chosen year
    semSel.add(new Option('-- Select Semester --', ''));
    ['I', 'II'].forEach(function(s) {
        const key    = year + '|' + s;
        const exists = submitted.includes(key);
        const label  = 'Semester ' + s + (exists ? '  ✓ (Already submitted — will update)' : '');
        const opt    = new Option(label, s);
        if (exists) opt.setAttribute('data-exists', '1');
        semSel.add(opt);
    });

    semSel.disabled = false;
}

function checkDuplicate() {
    const year    = document.getElementById('year').value;
    const semSel  = document.getElementById('semester');
    const opt     = semSel.options[semSel.selectedIndex];
    const dupWarn = document.getElementById('dup-warn');
    dupWarn.style.display = (opt && opt.getAttribute('data-exists') === '1') ? 'block' : 'none';
}

// On page load: if year is pre-filled, populate semester dropdown
window.addEventListener('DOMContentLoaded', function() {
    const yr = document.getElementById('year').value;
    if (yr) updateSemester();
});

// Upload form semester dropdown
function updateUploadSem() {
    const year   = document.getElementById('ul_year').value;
    const semSel = document.getElementById('ul_sem');
    semSel.innerHTML = '';
    semSel.disabled  = true;
    if (!year) { semSel.add(new Option('-- Select Year first --', '')); return; }
    semSel.add(new Option('-- Select Semester --', ''));

    const seq = [
        { year: '1', sem: 'I' },
        { year: '1', sem: 'II' },
        { year: '2', sem: 'I' },
        { year: '2', sem: 'II' },
        { year: '3', sem: 'I' },
        { year: '3', sem: 'II' }
    ];

    let nextAllowed = null;
    for (let i = 0; i < seq.length; i++) {
        const key = seq[i].year + '|' + seq[i].sem;
        if (!uploadedSlots.includes(key)) {
            nextAllowed = seq[i];
            break;
        }
    }

    ['I', 'II'].forEach(function(sem) {
        const opt = new Option('Semester ' + sem, sem);
        const isAllowed = nextAllowed && nextAllowed.year === year && nextAllowed.sem === sem;
        if (!isAllowed) opt.disabled = true;
        semSel.add(opt);
    });

    if (nextAllowed && nextAllowed.year === year) {
        semSel.value = nextAllowed.sem;
    }

    semSel.disabled = false;
}
</script>
</body>
</html>