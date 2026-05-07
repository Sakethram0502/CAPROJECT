<?php
// 1. Database Connection
include 'db.php';
session_start();

// 2. Security Check
if (!isset($_SESSION['staff_name'])) {
    http_response_code(403);
    die('Unauthorized access.');
}

// 3. Clear ANY previous output/errors to prevent corruption
if (ob_get_length()) ob_end_clean();

// 4. Get Inputs
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = $_GET['type'] ?? '';

if ($id <= 0) {
    die('Invalid request ID.');
}

// --- ZIP LOGIC ---
if ($type === 'zip') {
    $sql = "SELECT reg_no, 
                   document_name, document_data, 
                   ppt_name, ppt_data, 
                   code_name, code_data,
                   certificate_name, certificate_data
            FROM student_uploads WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fileSet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fileSet) die('Record not found.');

    $zip = new ZipArchive();
    $zipName = "Project_Files_" . $fileSet['reg_no'] . ".zip";
    $tempFile = tempnam(sys_get_temp_dir(), 'zip');

    if ($zip->open($tempFile, ZipArchive::CREATE) !== TRUE) {
        die("Could not create ZIP archive.");
    }

    // Add files to ZIP only if they exist in the database
    if (!empty($fileSet['document_data'])) {
        $zip->addFromString($fileSet['document_name'], $fileSet['document_data']);
    }
    if (!empty($fileSet['ppt_data'])) {
        $zip->addFromString($fileSet['ppt_name'], $fileSet['ppt_data']);
    }
    if (!empty($fileSet['code_data'])) {
        $zip->addFromString($fileSet['code_name'], $fileSet['code_data']);
    }
    if (!empty($fileSet['certificate_data'])) {
        $zip->addFromString($fileSet['certificate_name'], $fileSet['certificate_data']);
    }

    $zip->close();

    // Stream the ZIP to browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tempFile);
    unlink($tempFile);
    exit;
}

// --- SINGLE FILE DOWNLOAD LOGIC ---
$typeMap = [
    'document'    => 'document',
    'ppt'         => 'ppt',
    'code'        => 'code',
    'certificate' => 'certificate'   // ← new type
];

if (!array_key_exists($type, $typeMap)) {
    die('Invalid request type.');
}

$realType = $typeMap[$type];

// Fetch file
$nameCol = $realType . '_name';
$dataCol = $realType . '_data';

$sql = "SELECT $nameCol AS name, $dataCol AS data FROM student_uploads WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file || empty($file['data'])) {
    die('File content is empty in database.');
}

// Determine MIME type
$filename = $file['name'];
$mime = 'application/octet-stream'; // default
if ($realType === 'certificate' && $filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'pdf') $mime = 'application/pdf';
    elseif ($ext === 'doc') $mime = 'application/msword';
    elseif ($ext === 'docx') $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
}
$size = strlen($file['data']);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $size);

echo $file['data'];
exit;