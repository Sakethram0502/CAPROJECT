<?php
session_start();
include 'db.php';
mysqli_report(MYSQLI_REPORT_OFF);

$maxSingleFileBytes = 10 * 1024 * 1024; // 10MB

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_dashboard.php');
    exit;
}

function upload_redirect(string $status): void {
    header('Location: student_dashboard.php?upload=' . urlencode($status));
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
}

// ── Read uploaded files ──────────────────────────────────────────────
function read_upload(string $field): ?array {
    global $maxSingleFileBytes;
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $size = (int)$_FILES[$field]['size'];
    if ($size > $maxSingleFileBytes) throw new Exception("File '$field' exceeds 10MB limit.");
    return [
        'name' => $_FILES[$field]['name'],
        'type' => $_FILES[$field]['type'],
        'size' => $size,
        'data' => file_get_contents($_FILES[$field]['tmp_name']),
    ];
}

try {
    $doc  = read_upload('doc_file');
    $ppt  = read_upload('ppt_file');
    $code = read_upload('code_file');

    if (!$doc && !$ppt && !$code) {
        $_SESSION['upload_flash'] = 'No files selected. Please choose at least one file.';
        upload_redirect('empty');
    }

    // ── INSERT metadata row ──────────────────────────────────────────
    // Table columns: reg_no, branch, course, academic_year, section, semester,
    //                document_name, document_type, document_size,
    //                ppt_name, ppt_type, ppt_size,
    //                code_name, code_type, code_size
    $stmt = $conn->prepare(
        "INSERT INTO student_uploads
            (reg_no, branch, course, academic_year, section, semester,
             document_name, document_type, document_size,
             ppt_name,      ppt_type,      ppt_size,
             code_name,     code_type,     code_size)
         VALUES (?,?,?,?,?,?, ?,?,?, ?,?,?, ?,?,?)"
    );

    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $dName = $doc['name']  ?? null; $dType = $doc['type']  ?? null; $dSize = isset($doc["size"])  ? (string)$doc["size"]  : null;
    $pName = $ppt['name']  ?? null; $pType = $ppt['type']  ?? null; $pSize = isset($ppt["size"])  ? (string)$ppt["size"]  : null;
    $cName = $code['name'] ?? null; $cType = $code['type'] ?? null; $cSize = isset($code["size"]) ? (string)$code["size"] : null;

    // 6 strings + (s,s,i) + (s,s,i) + (s,s,i) = ssssss ssi ssi ssi
    $stmt->bind_param("sssssssssssssss",
        $regNo, $branch, $branch, $yearLabel, $section, $semester,
        $dName, $dType, $dSize,
        $pName, $pType, $pSize,
        $cName, $cType, $cSize
    );

    if (!$stmt->execute()) throw new Exception("Insert failed: " . $stmt->error);

    $rowId = $conn->insert_id;
    $stmt->close();

    // ── Save BLOB data ───────────────────────────────────────────────
    $blobs = [
        'document_data' => $doc['data']  ?? null,
        'ppt_data'      => $ppt['data']  ?? null,
        'code_data'     => $code['data'] ?? null,
    ];

    foreach ($blobs as $col => $data) {
        if ($data === null) continue;
        $upd = $conn->prepare("UPDATE student_uploads SET $col = ? WHERE id = ?");
        if (!$upd) throw new Exception("Prepare blob failed: " . $conn->error);
        $null = null;
        $upd->bind_param("bi", $null, $rowId);
        $upd->send_long_data(0, $data);
        if (!$upd->execute()) throw new Exception("Blob save failed for $col: " . $upd->error);
        $upd->close();
    }

    $_SESSION['upload_flash'] = "Files uploaded successfully for $regNo (Year $year, Semester $semester).";
    upload_redirect('success');

} catch (Exception $e) {
    $_SESSION['upload_flash'] = "Upload failed: " . $e->getMessage();
    upload_redirect('error');
}