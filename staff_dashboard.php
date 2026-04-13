<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include('db.php');

if (!isset($_SESSION['staff_name'])) {
    header('Location: index.php?view=staff');
    exit;
}

$username = $_SESSION['staff_name'];
$view     = $_GET['view'] ?? 'overview';

// ── SAVE MARKS & NOTES ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_marks'])) {
    $reg_no    = $_POST['reg_no'];
    $semester  = $_POST['semester_filter'] ?? '';
    $phase     = $_POST['review_phase'];
    $marks     = $_POST['marks'];
    $notes     = $_POST['notes'];
    $marks_col = $phase . '_marks';
    $notes_col = $phase . '_notes';
    $sql  = "UPDATE student_submissions SET $marks_col = ?, $notes_col = ? WHERE reg_no = ? AND semester = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isss', $marks, $notes, $reg_no, $semester);
    $stmt->execute();
    header("Location: staff_dashboard.php?view=$view");
    exit;
}

// ── FETCH: student_submissions for this guide ─────────────────────────
// Submissions are keyed by guide_name
if ($view === 'bca' || $view === 'mca') {
    $stmt = $conn->prepare(
        "SELECT * FROM student_submissions
         WHERE guide_name = ? AND branch = ?
         ORDER BY reg_no, year, semester"
    );
    $stmt->bind_param('ss', $username, strtoupper($view));
} else {
    $stmt = $conn->prepare(
        "SELECT * FROM student_submissions
         WHERE guide_name = ?
         ORDER BY reg_no, year, semester"
    );
    $stmt->bind_param('s', $username);
}
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── FETCH: ALL uploads from student_uploads, keyed by reg_no ──────────
// We query ALL uploads — no guide filter — match by reg_no
$uploadMap = [];  // reg_no => latest upload row
$uRes = $conn->query(
    "SELECT id, reg_no, semester, document_name, ppt_name, code_name
     FROM student_uploads
     ORDER BY id DESC"
);
while ($uRow = $uRes->fetch_assoc()) {
    // Keep the latest upload per reg_no (first one seen since ORDER BY id DESC)
    if (!isset($uploadMap[$uRow['reg_no']])) {
        $uploadMap[$uRow['reg_no']] = $uRow;
    }
}

// ── MERGE: attach upload info into each submission row ────────────────
foreach ($submissions as &$s) {
    $reg = $s['reg_no'];
    if (isset($uploadMap[$reg])) {
        $s['upload_id']     = $uploadMap[$reg]['id'];
        $s['document_name'] = $uploadMap[$reg]['document_name'];
        $s['ppt_name']      = $uploadMap[$reg]['ppt_name'];
        $s['code_name']     = $uploadMap[$reg]['code_name'];
    } else {
        $s['upload_id']     = null;
        $s['document_name'] = null;
        $s['ppt_name']      = null;
        $s['code_name']     = null;
    }
}
unset($s);

$semLabels = [
    'I'   => 'Sem I',
    'II'  => 'Sem II',
    '1-1' => 'Year 1 Sem 1', '1-2' => 'Year 1 Sem 2',
    '2-1' => 'Year 2 Sem 1', '2-2' => 'Year 2 Sem 2',
    'I-I' => 'Year I Sem I', 'I-II'=> 'Year I Sem II',
    'II-I'=> 'Year II Sem I','II-II'=> 'Year II Sem II',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Project Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        td[title]        { cursor:help; border-bottom:1px dashed rgba(255,255,255,0.3); }
        .project-subtext { display:block; font-size:0.82em; color:#00d4ff; margin-top:3px; }
        .domain-tag      { display:block; font-size:0.73em; color:#ffcc00; text-transform:uppercase; margin-top:2px; }
        .sem-badge       { display:inline-block; padding:2px 8px; border-radius:12px; font-size:0.72em; font-weight:600;
                           background:rgba(0,212,255,0.12); color:#00d4ff; border:1px solid rgba(0,212,255,0.3); }
        textarea         { resize:none; width:100%; padding:10px; border-radius:5px;
                           background:rgba(255,255,255,0.1); color:white; border:1px solid rgba(255,255,255,0.2); }
        .view-btn        { background:rgba(0,212,255,0.18); color:#00d4ff; border:1px solid #00d4ff;
                           padding:4px 12px; border-radius:4px; cursor:pointer; font-size:0.8em; white-space:nowrap; }
        .view-btn:hover  { background:rgba(0,212,255,0.35); }
        .pending-tag     { opacity:0.4; font-size:0.8em; }
        /* Files modal download buttons */
        .fm-btn          { display:block; width:100%; padding:11px 16px; border-radius:8px;
                           font-size:0.9em; text-decoration:none; margin-bottom:8px;
                           border:1px solid; transition:opacity 0.2s; }
        .fm-btn:hover    { opacity:0.75; }
        .fm-doc          { background:rgba(0,212,255,0.12); color:#00d4ff; border-color:#00d4ff; }
        .fm-ppt          { background:rgba(255,204,0,0.10); color:#ffcc00; border-color:#ffcc00; }
        .fm-code         { background:rgba(0,255,136,0.08); color:#00ff88; border-color:#00ff88; }
        .fm-zip          { background:rgba(180,100,255,0.15); color:#c084fc; border-color:#c084fc; margin-top:4px; }
    </style>
</head>
<body>
<div class="background-overlay"></div>
<div class="water-animation"></div>

<div class="dashboard-wrapper">
    <header class="top-nav">
        <div class="top-nav-left"><span class="brand-title">Department of Computer Applications</span></div>
        <div class="top-nav-right">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="btn-link nav-logout">Logout</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-title">Menu</div>
            <a href="staff_dashboard.php?view=overview" class="sidebar-link <?php echo $view==='overview'?'active':''; ?>">My Students</a>
            <a href="staff_dashboard.php?view=mca"      class="sidebar-link <?php echo $view==='mca'     ?'active':''; ?>">MCA Students</a>
            <a href="staff_dashboard.php?view=bca"      class="sidebar-link <?php echo $view==='bca'     ?'active':''; ?>">BCA Students</a>
            <div style="margin-top:40px;border-top:1px solid rgba(255,255,255,0.1);padding-top:20px;">
                <p style="font-size:0.7em;color:#888;padding-left:15px;letter-spacing:1px;margin-bottom:10px;">EXPORTS</p>
                <?php $rp = ($view==='overview')?'ALL':strtoupper($view); ?>
                <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=pdf"   target="_blank" class="sidebar-link" style="color:#00d4ff;">📄 PDF Report</a>
                <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=excel" class="sidebar-link" style="color:#00ff88;">📊 Excel Sheet</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h2 class="section-heading">
                <?php echo $view==='overview' ? 'My Students' : strtoupper($view).' Students'; ?>
                <span style="font-size:0.6em;color:#aaa;margin-left:12px;"><?php echo count($submissions); ?> records</span>
            </h2>

            <div class="table-container">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Student & Project</th>
                            <th>Year / Sem</th>
                            <th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th>
                            <th>Files</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                <span class="domain-tag">Domain: <?php echo htmlspecialchars($row['domain'] ?? '—'); ?></span>
                                <span class="project-subtext"><?php echo htmlspecialchars($row['project_title']); ?></span>
                            </td>
                            <td>
                                <span class="sem-badge">
                                    Yr <?php echo htmlspecialchars($row['year'] ?? '?'); ?> /
                                    <?php echo $semLabels[$row['semester']] ?? htmlspecialchars($row['semester']); ?>
                                </span>
                            </td>
                            <td title="<?php echo htmlspecialchars($row['r1_notes']??''); ?>"><?php echo $row['r1_marks']?:'-'; ?></td>
                            <td title="<?php echo htmlspecialchars($row['r2_notes']??''); ?>"><?php echo $row['r2_marks']?:'-'; ?></td>
                            <td title="<?php echo htmlspecialchars($row['r3_notes']??''); ?>"><?php echo $row['r3_marks']?:'-'; ?></td>
                            <td title="<?php echo htmlspecialchars($row['r4_notes']??''); ?>"><?php echo $row['r4_marks']?:'-'; ?></td>
                            <td title="<?php echo htmlspecialchars($row['r5_notes']??''); ?>"><?php echo $row['r5_marks']?:'-'; ?></td>

                            <!-- ── FILES COLUMN ── -->
                            <td>
                                <?php if (!empty($row['upload_id'])): ?>
                                    <button class="view-btn"
                                        onclick="openFilesModal(
                                            '<?php echo $row['upload_id']; ?>',
                                            '<?php echo htmlspecialchars(addslashes($row['reg_no'])); ?>',
                                            '<?php echo htmlspecialchars(addslashes($row['student_name'])); ?>',
                                            <?php echo $row['document_name'] ? 1 : 0; ?>,
                                            <?php echo $row['ppt_name']      ? 1 : 0; ?>,
                                            <?php echo $row['code_name']     ? 1 : 0; ?>
                                        )">
                                        📁 View Files
                                    </button>
                                <?php else: ?>
                                    <span class="pending-tag">Pending</span>
                                <?php endif; ?>
                            </td>

                            <!-- ── UPDATE BUTTON ── -->
                            <td>
                                <button class="btn-view btn-update"
                                    data-reg="<?php echo htmlspecialchars($row['reg_no']); ?>"
                                    data-sem="<?php echo htmlspecialchars($row['semester']); ?>"
                                    data-year="<?php echo htmlspecialchars($row['year'] ?? ''); ?>"
                                    data-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                    data-domain="<?php echo htmlspecialchars($row['domain'] ?? '—'); ?>"
                                    data-project="<?php echo htmlspecialchars($row['project_title']); ?>">
                                    Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($submissions)): ?>
                        <tr><td colspan="10" style="text-align:center;color:#888;padding:30px;">No students assigned yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- ── UPDATE MODAL ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="updateModal">
    <div class="modal-card floating">
        <h3>Update Marks & Remarks</h3>
        <form method="POST" action="staff_dashboard.php?view=<?php echo $view; ?>" class="form-glass modal-form">
            <input type="hidden" name="reg_no"          id="modalRegNo">
            <input type="hidden" name="semester_filter" id="modalSem">
            <div class="form-group">
                <p style="margin:0;font-size:0.9em;color:#ccc;">Student: <span id="dispName" style="color:white;font-weight:bold;"></span></p>
                <p style="margin:2px 0;font-size:0.78em;color:#ffcc00;">Domain: <span id="dispDomain"></span></p>
                <p style="margin:4px 0;font-size:0.78em;color:#aaa;">Year / Sem: <span id="dispSem" style="color:#00d4ff;"></span></p>
                <p style="margin:4px 0 14px;font-size:0.83em;color:#00d4ff;">Project: <span id="dispProject"></span></p>
            </div>
            <div class="form-group">
                <label>Review Phase</label>
                <select name="review_phase" required>
                    <option value="r1">Review 1</option>
                    <option value="r2">Review 2</option>
                    <option value="r3">Review 3</option>
                    <option value="r4">Review 4</option>
                    <option value="r5">Review 5</option>
                </select>
            </div>
            <div class="form-group">
                <label>Marks (0–100)</label>
                <input type="number" name="marks" min="0" max="100" required>
            </div>
            <div class="form-group">
                <label>Faculty Remarks</label>
                <textarea name="notes" rows="3" placeholder="Enter review feedback..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-link modal-close">Cancel</button>
                <button type="submit" name="update_marks" class="btn-gradient">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ── FILES MODAL ────────────────────────────────────────────────── -->
<div class="modal-overlay" id="filesModal">
    <div class="modal-card floating" style="max-width:400px;">
        <h3 style="margin-bottom:4px;">📁 Student Files</h3>
        <p id="fmStudent" style="color:#aaa;font-size:0.85em;margin:0 0 18px;"></p>
        <div id="fmButtons"></div>
        <div style="margin-top:18px;text-align:right;">
            <button type="button" id="filesModalClose" style="background:none;border:none;color:#aaa;cursor:pointer;font-size:0.9em;">Close</button>
        </div>
    </div>
</div>

<script>
// ── Update modal ─────────────────────────────────────────────────────
const updateModal = document.getElementById('updateModal');
document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', () => {
        const sem  = btn.getAttribute('data-sem');
        const year = btn.getAttribute('data-year');
        document.getElementById('modalRegNo').value      = btn.getAttribute('data-reg');
        document.getElementById('modalSem').value        = sem;
        document.getElementById('dispName').innerText    = btn.getAttribute('data-name');
        document.getElementById('dispDomain').innerText  = btn.getAttribute('data-domain');
        document.getElementById('dispProject').innerText = btn.getAttribute('data-project');
        document.getElementById('dispSem').innerText     = 'Year ' + year + ' / Sem ' + sem;
        updateModal.classList.add('open');
    });
});
document.querySelectorAll('.modal-close').forEach(b =>
    b.addEventListener('click', () => updateModal.classList.remove('open'))
);
window.addEventListener('click', e => {
    if (e.target === updateModal) updateModal.classList.remove('open');
});

// ── Files modal ──────────────────────────────────────────────────────
const filesModal = document.getElementById('filesModal');

function openFilesModal(id, reg, name, hasDoc, hasPpt, hasCode) {
    document.getElementById('fmStudent').innerText = reg + '  —  ' + name;

    const container = document.getElementById('fmButtons');
    container.innerHTML = '';

    if (parseInt(hasDoc)) {
        const a = document.createElement('a');
        a.href      = 'download_file.php?id=' + id + '&type=document';
        a.className = 'fm-btn fm-doc';
        a.innerHTML = '📄 &nbsp;Download Document';
        container.appendChild(a);
    }
    if (parseInt(hasPpt)) {
        const a = document.createElement('a');
        a.href      = 'download_file.php?id=' + id + '&type=ppt';
        a.className = 'fm-btn fm-ppt';
        a.innerHTML = '📊 &nbsp;Download Presentation';
        container.appendChild(a);
    }
    if (parseInt(hasCode)) {
        const a = document.createElement('a');
        a.href      = 'download_file.php?id=' + id + '&type=code';
        a.className = 'fm-btn fm-code';
        a.innerHTML = '💻 &nbsp;Download Source Code';
        container.appendChild(a);
    }

    // ZIP — always shown
    const zip = document.createElement('a');
    zip.href      = 'download_file.php?id=' + id + '&type=zip&reg=' + encodeURIComponent(reg);
    zip.className = 'fm-btn fm-zip';
    zip.innerHTML = '⬇ &nbsp;Download All as ZIP';
    container.appendChild(zip);

    filesModal.classList.add('open');
}

document.getElementById('filesModalClose').addEventListener('click', () =>
    filesModal.classList.remove('open')
);
window.addEventListener('click', e => {
    if (e.target === filesModal) filesModal.classList.remove('open');
});
</script>
</body>
</html>