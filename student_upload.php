<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_dashboard.php');
    exit;
}

$regNo = trim($_POST['reg_no'] ?? '');

if ($regNo === '') {
    header('Location: student_dashboard.php?upload=error');
    exit;
}

$uploadBase = __DIR__ . '/uploads';
if (!is_dir($uploadBase)) {
    mkdir($uploadBase, 0777, true);
}

$studentDir = $uploadBase . '/' . preg_replace('/[^A-Za-z0-9_-]/', '_', $regNo);
if (!is_dir($studentDir)) {
    mkdir($studentDir, 0777, true);
}

function handle_upload(string $fieldName, string $targetDir): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $originalName = basename($_FILES[$fieldName]['name']);
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    $targetPath = $targetDir . '/' . (time() . '_' . $safeName);

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return null;
    }

    // Store relative path from CAPROJECT folder
    return 'uploads/' . basename($targetDir) . '/' . basename($targetPath);
}

$docPath  = handle_upload('doc_file', $studentDir);
$pptPath  = handle_upload('ppt_file', $studentDir);
$codePath = handle_upload('code_file', $studentDir);

// If nothing was uploaded, just redirect back
if ($docPath === null && $pptPath === null && $codePath === null) {
    header('Location: student_dashboard.php?upload=empty');
    exit;
}

$stmt = $conn->prepare('INSERT INTO student_files (reg_no, document_path, ppt_path, code_path) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $regNo, $docPath, $pptPath, $codePath);
$stmt->execute();
$stmt->close();

header('Location: student_dashboard.php?upload=success');
exit;

