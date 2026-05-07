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
$regNo    = trim($_POST['reg_no']   ?? '');
$branch   = strtoupper(trim($_POST['branch']   ?? ''));
$year     = trim($_POST['year']     ?? '');
$section  = trim($_POST['section']  ?? '');
$semester = trim($_POST['semester'] ?? '');

// Basic validation
if (empty($regNo) || empty($year) || empty($semester)) {
    upload_flash('Please fill Year and Semester.');
}

// Enforce branch/year from registration series:
// FJ => BCA (1,2,3), FD => MCA (1,2)
$letters      = strtolower(preg_replace('/[^a-z]/i', '', $regNo));
$lettersArray = str_split($letters);
sort($lettersArray);
$track          = implode('', $lettersArray);
$expectedBranch = ($track === 'fj') ? 'BCA' : 'MCA';
$allowedYears   = ($expectedBranch === 'BCA') ? ['1', '2', '3'] : ['1', '2'];

if ($branch !== $expectedBranch || !in_array($year, $allowedYears, true)) {
    upload_flash('Invalid branch/year for this registration number.');
}

if (!in_array($semester, ['I', 'II'], true)) {
    upload_flash('Invalid semester selected.');
}

// Normalize year label to match DB format
$yearLabel = ($year === '1') ? 'Year 1' :
             (($year === '2') ? 'Year 2' :
             (($year === '3') ? 'Year 3' : $year));

// Normalize semester to match DB format
$semDbFormat = trim($semester);

$semKey = $year . '|' . $semester;

// Check existing upload for this slot
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_uploads WHERE reg_no = ? AND academic_year = ? AND semester = ?");
$stmt->bind_param('sss', $regNo, $yearLabel, $semDbFormat);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result->fetch_assoc();
$alreadyUploaded = $row['count'] > 0;
$stmt->close();

if ($alreadyUploaded) {
    $reqStmt = $conn->prepare("SELECT status, used FROM update_requests WHERE reg_no = ? AND request_type = 'files' AND semester_key = ? ORDER BY requested_at DESC LIMIT 1");
    $reqStmt->bind_param('ss', $regNo, $semKey);
    $reqStmt->execute();
    $reqResult   = $reqStmt->get_result();
    $approvalRow = $reqResult->fetch_assoc();
    $reqStmt->close();

    if (!$approvalRow || $approvalRow['status'] !== 'approved' || $approvalRow['used']) {
        upload_flash("You have already uploaded files for Year $year $semester. To resubmit, please request your guide's approval.");
    }
}

// Process files (at least one required)
$doc  = (isset($_FILES['doc_file'])  && $_FILES['doc_file']['error']  == 0) ? $_FILES['doc_file']  : null;
$ppt  = (isset($_FILES['ppt_file'])  && $_FILES['ppt_file']['error']  == 0) ? $_FILES['ppt_file']  : null;
$code = (isset($_FILES['code_file']) && $_FILES['code_file']['error'] == 0) ? $_FILES['code_file'] : null;
$cert = (isset($_FILES['cert_file']) && $_FILES['cert_file']['error'] == 0) ? $_FILES['cert_file'] : null;

if (!$doc && !$ppt && !$code && !$cert) {
    upload_flash('Please select at least one file to upload.');
}

// Validate certificate type if uploaded
if ($cert) {
    $allowedCertMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    if (!in_array($cert['type'], $allowedCertMimes)) {
        upload_flash('Certificate must be a PDF or Word document (.doc/.docx).');
    }
}

// Insert metadata (now with certificate fields)
$insertStmt = $conn->prepare("INSERT INTO student_uploads 
    (reg_no, branch, course, academic_year, section, semester,
     document_name, document_type, document_size,
     ppt_name, ppt_type, ppt_size,
     code_name, code_type, code_size,
     certificate_name, certificate_type, certificate_size)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$dName = $doc  ? $doc['name']        : null;
$dType = $doc  ? $doc['type']        : null;
$dSize = $doc  ? (string)$doc['size'] : null;

$pName = $ppt  ? $ppt['name']        : null;
$pType = $ppt  ? $ppt['type']        : null;
$pSize = $ppt  ? (string)$ppt['size'] : null;

$cName = $code ? $code['name']        : null;
$cType = $code ? $code['type']        : null;
$cSize = $code ? (string)$code['size'] : null;

$certName = $cert ? $cert['name']           : null;
$certType = $cert ? $cert['type']           : null;
$certSize = $cert ? (string)$cert['size']   : null;

$insertStmt->bind_param(
    "ssssssssssssssssss",
    $regNo, $branch, $branch, $yearLabel, $section, $semDbFormat,
    $dName, $dType, $dSize,
    $pName, $pType, $pSize,
    $cName, $cType, $cSize,
    $certName, $certType, $certSize
);

if (!$insertStmt->execute()) {
    upload_flash('Upload failed: Database error.');
}

$rowId = $conn->insert_id;
$insertStmt->close();

// Save BLOB data for all file types (including certificate)
$blobs = [
    'document_data'    => $doc  ? file_get_contents($doc['tmp_name'])  : null,
    'ppt_data'         => $ppt  ? file_get_contents($ppt['tmp_name'])  : null,
    'code_data'        => $code ? file_get_contents($code['tmp_name']) : null,
    'certificate_data' => $cert ? file_get_contents($cert['tmp_name']) : null,
];

foreach ($blobs as $field => $data) {
    if ($data !== null && strlen($data) > 0) {
        $blobStmt = $conn->prepare("UPDATE student_uploads SET $field = ? WHERE id = ?");
        $null = null;
        $blobStmt->bind_param('bi', $null, $rowId);
        $blobStmt->send_long_data(0, $data);
        $blobStmt->execute();
        $blobStmt->close();
    }
}

// Mark approval as used (if this was a resubmission)
if ($alreadyUploaded) {
    $updateReq = $conn->prepare("UPDATE update_requests SET used = 1, actioned_at = NOW() WHERE reg_no = ? AND request_type = 'files' AND semester_key = ? AND status = 'approved' AND used = 0");
    $updateReq->bind_param('ss', $regNo, $semKey);
    $updateReq->execute();
    $updateReq->close();
}

upload_flash("✅ Files uploaded successfully for Year $year $semester!");