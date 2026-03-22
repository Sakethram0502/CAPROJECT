<?php
session_start();
include 'db.php';

// Prevent mysqli from throwing uncaught exceptions (which show as HTTP 500).
mysqli_report(MYSQLI_REPORT_OFF);

// Keep upload payload safe for MySQL packet limits.
// This alternative writes blobs one-by-one (separate UPDATEs).
$maxSingleFileBytes = 8 * 1024 * 1024;   // 8 MB per file

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?view=upload');
    exit;
}

$returnTo = $_POST['return_to'] ?? 'index';

/** Correct ? or & for query string */
function upload_redirect(string $returnTo, string $status): void
{
    if ($returnTo === 'dashboard') {
        header('Location: student_dashboard.php?upload=' . urlencode($status));
    } else {
        header('Location: index.php?view=upload&upload=' . urlencode($status));
    }
    exit;
}

$regNo = trim($_POST['reg_no'] ?? '');
$branch = strtoupper(trim($_POST['branch'] ?? ''));
$academicYear = trim($_POST['academic_year'] ?? '');
$section = trim($_POST['section'] ?? '');
$semester = trim($_POST['semester'] ?? '');

if ($regNo === '') {
    $_SESSION['upload_flash'] = 'Upload failed: registration number missing.';
    upload_redirect($returnTo, 'error');
}

if (!in_array($branch, ['BCA', 'MCA'], true)) {
    $_SESSION['upload_flash'] = 'Please select Branch (BCA or MCA).';
    upload_redirect($returnTo, 'error');
}

if ($academicYear === '' || $semester === '') {
    $_SESSION['upload_flash'] = 'Please select Year and Semester.';
    upload_redirect($returnTo, 'error');
}

if ($branch === 'BCA') {
    if (!in_array($academicYear, ['1st Year', '2nd Year', '3rd Year'], true)) {
        $_SESSION['upload_flash'] = 'Invalid year for BCA.';
        upload_redirect($returnTo, 'error');
    }
    $section = null;
} else {
    if (!in_array($academicYear, ['1st Year', '2nd Year'], true)) {
        $_SESSION['upload_flash'] = 'Invalid year for MCA.';
        upload_redirect($returnTo, 'error');
    }
    if (!in_array($section, ['A', 'B'], true)) {
        $_SESSION['upload_flash'] = 'Please select Section (A or B) for MCA.';
        upload_redirect($returnTo, 'error');
    }
}

if (!in_array($semester, ['I', 'II'], true)) {
    $_SESSION['upload_flash'] = 'Please select Semester I or II.';
    upload_redirect($returnTo, 'error');
}

// Helper to safely read an uploaded file into memory
function read_upload(string $fieldName): ?array
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $size = (int) $_FILES[$fieldName]['size'];
    global $maxSingleFileBytes;
    if ($size > $maxSingleFileBytes) {
        throw new Exception('Upload failed: each file must be 8 MB or smaller.');
    }

    $tmpPath = $_FILES[$fieldName]['tmp_name'];
    $data = file_get_contents($tmpPath);
    if ($data === false) {
        return null;
    }

    return [
        'name' => $_FILES[$fieldName]['name'],
        'type' => $_FILES[$fieldName]['type'],
        'size' => $size,
        'data' => $data,
    ];
}

try {
    $doc      = read_upload('doc_file');
    $ppt      = read_upload('ppt_file');
    $code     = read_upload('code_file');
} catch (Throwable $e) {
    $_SESSION['upload_flash'] = $e->getMessage();
    upload_redirect($returnTo, 'error');
}

// If nothing was uploaded, just redirect back
if ($doc === null && $ppt === null && $code === null) {
    $_SESSION['upload_flash'] = 'No file selected. Please choose at least one file to upload.';
    upload_redirect($returnTo, 'empty');
}

// Prepare values (allow nulls)
$documentName = $doc['name']  ?? null;
$documentType = $doc['type']  ?? null;
$documentSize = isset($doc['size'])  ? (int) $doc['size']  : null;
$documentData = $doc['data']  ?? null;

$pptName      = $ppt['name']  ?? null;
$pptType      = $ppt['type']  ?? null;
$pptSize      = isset($ppt['size'])  ? (int) $ppt['size']  : null;
$pptData      = $ppt['data']  ?? null;

$codeName     = $code['name'] ?? null;
$codeType     = $code['type'] ?? null;
$codeSize     = isset($code['size']) ? (int) $code['size'] : null;
$codeData     = $code['data'] ?? null;

try {
    // Step 1: Save metadata first (no large packet yet)
    $stmt = $conn->prepare('INSERT INTO student_files (
        reg_no,
        branch, academic_year, section, semester,
        document_name, document_type, document_size,
        ppt_name, ppt_type, ppt_size,
        code_name, code_type, code_size
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    if (!$stmt) {
        throw new Exception('DB error (prepare): ' . $conn->error . ' — If columns are missing, run migration_student_files_meta.sql in phpMyAdmin.');
    }

    $stmt->bind_param(
        'sssssssisssiss',
        $regNo,
        $branch,
        $academicYear,
        $section,
        $semester,
        $documentName,
        $documentType,
        $documentSize,
        $pptName,
        $pptType,
        $pptSize,
        $codeName,
        $codeType,
        $codeSize
    );

    if (!$stmt->execute()) {
        throw new Exception('DB error (execute): ' . $stmt->error);
    }

    $rowId = $conn->insert_id;
    $stmt->close();

    // Step 2: Store each blob in separate query to avoid large single packet
    $saveBlob = function (string $column, ?string $data) use ($conn, $rowId): void {
        if ($data === null) {
            return;
        }

        $upd = $conn->prepare("UPDATE student_files SET $column = ? WHERE id = ?");
        if (!$upd) {
            throw new Exception('DB error (blob prepare): ' . $conn->error);
        }
        $upd->bind_param('si', $data, $rowId);
        if (!$upd->execute()) {
            throw new Exception('DB error (blob execute): ' . $upd->error);
        }
        $upd->close();
    };

    $saveBlob('document_data', $documentData);
    $saveBlob('ppt_data', $pptData);
    $saveBlob('code_data', $codeData);

    $secLabel = $section !== null ? (' · Sec ' . $section) : '';
    $_SESSION['upload_flash'] = 'Files uploaded successfully! ' . $branch . ' · ' . $academicYear . $secLabel . ' · Sem ' . $semester . ' · Reg: ' . $regNo;
    $_SESSION['upload_last_meta'] = [
        'branch' => $branch,
        'year' => $academicYear,
        'section' => $section,
        'semester' => $semester,
        'reg_no' => $regNo,
    ];
    upload_redirect($returnTo, 'success');
} catch (Throwable $e) {
    if (stripos($e->getMessage(), 'max_allowed_packet') !== false) {
        $_SESSION['upload_flash'] = 'DB limit reached: file payload is larger than MySQL max_allowed_packet. Increase MySQL packet size or upload smaller files.';
    } else {
        $_SESSION['upload_flash'] = $e->getMessage();
    }
    upload_redirect($returnTo, 'dberror');
}

