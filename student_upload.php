<?php
session_start();
include 'db.php';

// Prevent mysqli from throwing uncaught exceptions to handle them in catch block
mysqli_report(MYSQLI_REPORT_OFF);

// 10 MB per file limit (Increased slightly for safety)
$maxSingleFileBytes = 10 * 1024 * 1024; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?view=upload');
    exit;
}

$returnTo = $_POST['return_to'] ?? 'index';

/** Helper for redirects **/
function upload_redirect(string $returnTo, string $status): void
{
    if ($returnTo === 'dashboard') {
        header('Location: student_dashboard.php?upload=' . urlencode($status));
    } else {
        header('Location: index.php?view=upload&upload=' . urlencode($status));
    }
    exit;
}

// Collect form data
$regNo         = trim($_POST['reg_no'] ?? '');
$branch        = strtoupper(trim($_POST['branch'] ?? '')); 
$academicYear  = trim($_POST['academic_year'] ?? '');
$section       = trim($_POST['section'] ?? '');
$semester      = trim($_POST['semester'] ?? '');

// Validation
if ($regNo === '' || $branch === '' || $academicYear === '' || $semester === '') {
    $_SESSION['upload_flash'] = 'Please fill all required fields.';
    upload_redirect($returnTo, 'error');
}

// Clean section for BCA
if ($branch === 'BCA') {
    $section = null;
}

/** Helper to read file into memory safely **/
function read_upload(string $fieldName) {
    global $maxSingleFileBytes;
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $size = (int) $_FILES[$fieldName]['size'];
    if ($size > $maxSingleFileBytes) {
        throw new Exception("File $fieldName exceeds 10MB limit.");
    }
    return [
        'name' => $_FILES[$fieldName]['name'],
        'type' => $_FILES[$fieldName]['type'],
        'size' => $size,
        'data' => file_get_contents($_FILES[$fieldName]['tmp_name']),
    ];
}

try {
    $doc  = read_upload('doc_file');
    $ppt  = read_upload('ppt_file');
    $code = read_upload('code_file');

    if (!$doc && !$ppt && !$code) {
        $_SESSION['upload_flash'] = 'No files selected for upload.';
        upload_redirect($returnTo, 'empty');
    }

    // Step 1: Insert Metadata (Exactly 14 columns and 14 question marks)
    $stmt = $conn->prepare("INSERT INTO student_uploads (
        reg_no, branch, academic_year, section, semester,
        document_name, document_type, document_size,
        ppt_name, ppt_type, ppt_size,
        code_name, code_type, code_size
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("SQL Prepare Error: " . $conn->error);
    }

    // Map nulls for missing files
    $dName = $doc['name'] ?? null; $dType = $doc['type'] ?? null; $dSize = $doc['size'] ?? null;
    $pName = $ppt['name'] ?? null; $pType = $ppt['type'] ?? null; $pSize = $ppt['size'] ?? null;
    $cName = $code['name'] ?? null; $cType = $code['type'] ?? null; $cSize = $code['size'] ?? null;

    // "sssssssisssiss" = 14 types matching 14 values
    $stmt->bind_param("sssssssisssiss", 
        $regNo, $branch, $academicYear, $section, $semester,
        $dName, $dType, $dSize,
        $pName, $pType, $pSize,
        $cName, $cType, $cSize
    );

    if (!$stmt->execute()) {
        throw new Exception("Metadata Insert Error: " . $stmt->error);
    }

    $rowId = $conn->insert_id;
    $stmt->close();

    // Step 2: Update BLOBs using send_long_data (Safest for binary files)
    $blobs = [
        'document_data' => $doc['data'] ?? null,
        'ppt_data'      => $ppt['data'] ?? null,
        'code_data'     => $code['data'] ?? null
    ];

    foreach ($blobs as $column => $data) {
        if ($data !== null) {
            $upd = $conn->prepare("UPDATE student_uploads SET $column = ? WHERE id = ?");
            if ($upd) {
                $null = NULL; 
                $upd->bind_param("bi", $null, $rowId); // 'b' for blob
                $upd->send_long_data(0, $data);        // Send the file content
                
                if(!$upd->execute()){
                    throw new Exception("Error saving binary data for $column: " . $upd->error);
                }
                $upd->close();
            }
        }
    }

    $_SESSION['upload_flash'] = "Success! All files for $regNo have been secured.";
    upload_redirect($returnTo, 'success');

} catch (Exception $e) {
    $_SESSION['upload_flash'] = "Upload Failed: " . $e->getMessage();
    upload_redirect($returnTo, 'error');
}