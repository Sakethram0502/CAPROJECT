<?php
session_start();
include 'db.php';
mysqli_report(MYSQLI_REPORT_OFF);

$maxSingleFileBytes = 10 * 1024 * 1024; // 10MB

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_dashboard.php');
    exit;
}

function flash_redirect($type, $msg) {
    $_SESSION['flash'] = [$type, $msg];
    header('Location: student_dashboard.php');
    exit;
}

// ── Get fields ───────────────────────────────────────────────────────
$regNo       = trim($_POST['reg_no'] ?? '');
$studentName = trim($_POST['student_name'] ?? '');
$branch      = strtoupper(trim($_POST['branch'] ?? ''));
$year        = trim($_POST['year'] ?? '');
$section     = trim($_POST['section'] ?? '');
$semester    = trim($_POST['semester'] ?? '');
$domain      = trim($_POST['domain'] ?? '');
$projectTitle = trim($_POST['project_title'] ?? '');
$guideName   = trim($_POST['guide_name'] ?? '');

$semKey      = $year . '|' . $semester;
$type        = 'details';

// Validation
if ($regNo === '' || $studentName === '' || $branch === '' || $year === '' || $semester === '' ||
    $section === '' || $domain === '' || $projectTitle === '' || $guideName === '') {
    flash_redirect('warn', '⚠️ Please fill in all required fields.');
}

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS update_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(50) NOT NULL,
    request_type ENUM('files','details') NOT NULL,
    semester_key VARCHAR(20) DEFAULT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    guide_remark TEXT DEFAULT NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    actioned_at DATETIME DEFAULT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX (reg_no), INDEX (status)
)");

// Check if already submitted for this year/semester
$chk = $conn->prepare("SELECT id FROM student_submissions
                       WHERE reg_no = ? AND year = ? AND semester = ? LIMIT 1");
$chk->bind_param('sss', $regNo, $year, $semester);
$chk->execute();
$exists = $chk->get_result()->num_rows > 0;

if ($exists) {
    // Check approval state
    $reqStmt = $conn->prepare("SELECT id, status, used FROM update_requests
                               WHERE reg_no = ? AND request_type = ? AND semester_key = ?
                               ORDER BY requested_at DESC LIMIT 1");
    $reqStmt->bind_param('sss', $regNo, $type, $semKey);
    $reqStmt->execute();
    $row = $reqStmt->get_result()->fetch_assoc();

    if (!$row || $row['status'] !== 'approved' || $row['used'] == 1) {
        flash_redirect('warn',
            "⚠️ You have already submitted for Year $year Semester $semester. " .
            "Please send an approval request to your guide for this semester to update."
        );
    }
    // Mark this request as used so next update is blocked until new request
    $upd = $conn->prepare("UPDATE update_requests SET used = 1 WHERE id = ?");
    $upd->bind_param('i', $row['id']);
    $upd->execute();
}

// Now decide: insert or update
if ($exists) {
    $stmt = $conn->prepare("UPDATE student_submissions SET
        student_name = ?, branch = ?, section = ?,
        domain = ?, project_title = ?, guide_name = ?
        WHERE reg_no = ? AND year = ? AND semester = ?");
} else {
    $stmt = $conn->prepare("INSERT INTO student_submissions (
        reg_no, student_name, branch, year, section, semester,
        domain, project_title, guide_name
    ) VALUES (?,?,?,?,?,?,?,?,?)");
}

if (!$stmt) {
    flash_redirect('warn', '⚠️ Failed to prepare statement: ' . $conn->error);
}

if (!$exists) {
    $stmt->bind_param('sssssssss',
        $regNo, $studentName, $branch, $year, $section, $semester,
        $domain, $projectTitle, $guideName
    );
} else {
    $stmt->bind_param('sssssssss',
        $studentName, $branch, $section, $domain, $projectTitle, $guideName,
        $regNo, $year, $semester
    );
}

if ($stmt->execute()) {
    flash_redirect('ok', "✅ Project details submitted/updated for Year $year, Semester $semester.");
} else {
    flash_redirect('warn', "⚠️ Failed to save: " . $stmt->error);
}