<?php
session_start();
include 'db.php';
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_dashboard.php');
    exit;
}

function upload_flash($msg) {
    $_SESSION['upload_flash'] = $msg;
    header('Location: student_dashboard.php');
    exit;
}


// ── Collect posted fields ────────────────────────────────────────────
$regNo       = trim($_POST['reg_no']       ?? '');
$branch      = strtoupper(trim($_POST['branch']   ?? ''));
$year        = trim($_POST['year']         ?? '');   // "1" or "2"
$section     = trim($_POST['section']      ?? '');
$semester    = trim($_POST['semester']     ?? '');   // "I" or "II"


// academic_year built from year  e.g. "2nd Year"
$yearLabel   = ($year === '1') ? '1st Year' : (($year === '2') ? '2nd Year' : $year);

// Validation
if ($regNo === '' || $branch === '' || $year === '' || $semester === '') {
    $_SESSION['upload_flash'] = 'Please fill in Year and Semester before uploading.';
    upload_redirect('error');

// Enforce branch/year from registration series:
// FJ => BCA (1,2,3), FD => MCA (1,2)
$letters = strtolower(preg_replace('/[^a-z]/i', '', $regNo));
$lettersArray = str_split($letters);
sort($lettersArray);
$track = implode('', $lettersArray);
$expectedBranch = ($track === 'fj') ? 'BCA' : 'MCA';
$allowedYears = ($expectedBranch === 'BCA') ? ['1', '2', '3'] : ['1', '2'];

// Basic validation
if (empty($regNo) || empty($year) || empty($semester)) {
    upload_flash('Please fill Year and Semester.');

}
if ($branch !== $expectedBranch || !in_array($year, $allowedYears, true)) {
    upload_flash('Invalid branch/year for this registration number.');
}



if (!in_array($semester, ['I', 'II'], true) || !in_array($year, ['1', '2', '3'], true)) {
    $_SESSION['upload_flash'] = 'Invalid Year/Semester selected.';
    upload_redirect('error');


if (!in_array($semester, ['I', 'II'], true) || !in_array($year, ['1', '2', '3'], true)) {
    $_SESSION['upload_flash'] = 'Invalid Year/Semester selected.';
    upload_redirect('error');

// ✅ PERFECT Semester/Year normalization to match ALL database formats
$yearLabel = ($year == '1' || $year == 'Year 1') ? 'Year 1' :
             (($year == '2' || $year == 'Year 2') ? 'Year 2' :
             (($year == '3' || $year == 'Year 3') ? 'Year 3' : $year));

// Get form data
$regNo = trim($_POST['reg_no'] ?? '');
$year = trim($_POST['year'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$branch = strtoupper(trim($_POST['branch'] ?? ''));
$section = trim($_POST['section'] ?? '');

// Basic validation
if (empty($regNo) || empty($year) || empty($semester)) {
    upload_flash('Please fill Year and Semester.');
}

// ✅ PERFECT Semester/Year normalization to match ALL database formats
$yearLabel = ($year == '1' || $year == 'Year 1') ? 'Year 1' : 
             (($year == '2' || $year == 'Year 2') ? 'Year 2' : $year);


$semDbFormat = '';
if (strpos($semester, 'Semester') !== false) {
    $semDbFormat = str_replace('Semester ', 'Sem ', $semester); // "Semester I" → "Sem I"
} elseif (strlen($semester) <= 3 && ctype_alpha($semester)) {
    $semDbFormat = strtoupper($semester); // "I", "II" → "II"
} else {
    $semDbFormat = trim($semester);

}

$semKey = $year . '|' . $semester; // For update_requests table

// DEBUG (remove in production)
error_log("DEBUG UPLOAD: regNo=$regNo, year=$yearLabel, semInput=$semester, semDB=$semDbFormat");

// ✅ Check ACTUAL student_uploads table (NOT student_submissions)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_uploads WHERE reg_no = ? AND academic_year = ? AND semester = ?");
$stmt->bind_param('sss', $regNo, $yearLabel, $semDbFormat);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$alreadyUploaded = $row['count'] > 0;
$stmt->close();

error_log("UPLOAD CHECK: alreadyUploaded = " . ($alreadyUploaded ? 'YES' : 'NO'));

if ($alreadyUploaded) {
    // Check if guide approved resubmission
    $reqStmt = $conn->prepare("SELECT status, used FROM update_requests WHERE reg_no = ? AND request_type = 'files' AND semester_key = ? ORDER BY requested_at DESC LIMIT 1");
    $reqStmt->bind_param('ss', $regNo, $semKey);
    $reqStmt->execute();
    $reqResult = $reqStmt->get_result();
    $approvalRow = $reqResult->fetch_assoc();
    $reqStmt->close();
    
    if (!$approvalRow || $approvalRow['status'] !== 'approved' || $approvalRow['used']) {
        upload_flash("You have already uploaded files for Year $year $semester. To resubmit files, please provide a reason and request your guide's approval.");
    }
    // If approved and not used → CONTINUE to upload
}

// Process files (at least one required)
$doc = (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] == 0) ? $_FILES['doc_file'] : null;
$ppt = (isset($_FILES['ppt_file']) && $_FILES['ppt_file']['error'] == 0) ? $_FILES['ppt_file'] : null;
$code = (isset($_FILES['code_file']) && $_FILES['code_file']['error'] == 0) ? $_FILES['code_file'] : null;

if (!$doc && !$ppt && !$code) {
    upload_flash('Please select at least one file to upload.');
}

// ✅ FIXED: Insert metadata with CORRECT 15 parameters
$insertStmt = $conn->prepare("INSERT INTO student_uploads (reg_no, branch, course, academic_year, section, semester, document_name, document_type, document_size, ppt_name, ppt_type, ppt_size, code_name, code_type, code_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$dName = $doc ? $doc['name'] : null;
$dType = $doc ? $doc['type'] : null;
$dSize = $doc ? (string)$doc['size'] : null;

$pName = $ppt ? $ppt['name'] : null;
$pType = $ppt ? $ppt['type'] : null;
$pSize = $ppt ? (string)$ppt['size'] : null;

$cName = $code ? $code['name'] : null;
$cType = $code ? $code['type'] : null;
$cSize = $code ? (string)$code['size'] : null;

// ✅ PERFECT: 15 's' parameters (5 + 3 + 3 + 3 + 1)
$insertStmt->bind_param("sssssssssssssss", 
    $regNo, $branch, $branch, $yearLabel, $section, $semDbFormat,
    $dName, $dType, $dSize, $pName, $pType, $pSize, $cName, $cType, $cSize
);

if (!$insertStmt->execute()) {
    error_log("INSERT ERROR: " . $insertStmt->error);
    upload_flash('Upload failed: Database error.');
}

$rowId = $conn->insert_id;
$insertStmt->close();

// Save BLOB data SAFELY
$blobs = [
    'document_data' => $doc ? file_get_contents($doc['tmp_name']) : null,
    'ppt_data' => $ppt ? file_get_contents($ppt['tmp_name']) : null,
    'code_data' => $code ? file_get_contents($code['tmp_name']) : null
];

foreach ($blobs as $field => $data) {
    if ($data !== null && strlen($data) > 0) {
        $blobStmt = $conn->prepare("UPDATE student_uploads SET $field = ? WHERE id = ?");
        $null = null;
        $blobStmt->bind_param('bi', $null, $rowId);
        $blobStmt->send_long_data(0, $data);
        if (!$blobStmt->execute()) {
            error_log("BLOB ERROR $field: " . $blobStmt->error);
        }
        $blobStmt->close();
    }
}

// Mark approval as used (if this was resubmission)
if ($alreadyUploaded) {
    $updateReq = $conn->prepare("UPDATE update_requests SET used = 1, actioned_at = NOW() WHERE reg_no = ? AND request_type = 'files' AND semester_key = ? AND status = 'approved' AND used = 0");
    $updateReq->bind_param('ss', $regNo, $semKey);
    $updateReq->execute();
    $updateReq->close();
}

upload_flash("✅ Files uploaded successfully for Year $year $semester!");
?>