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

$typeMap = [
    'document' => 'document',
    'ppt'      => 'ppt',
    'code'     => 'code'
];

if ($id <= 0 || !array_key_exists($type, $typeMap)) {
    die('Invalid request.');
}

$realType = $typeMap[$type];

// 5. Fetch File
$nameCol = $realType . '_name';
$mimeCol = $realType . '_type';
$sizeCol = $realType . '_size';
$dataCol = $realType . '_data';

$sql = "SELECT $nameCol AS name, $mimeCol AS mime, $sizeCol AS size, $dataCol AS data
        FROM student_uploads 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$file   = $result->fetch_assoc();
$stmt->close();

if (!$file || empty($file['data'])) {
    die('File content is empty in database.');
}

// 6. Mandatory Headers for Binary Files
$filename = $file['name'];
$mime     = $file['mime'] ?: 'application/octet-stream';
$size     = strlen($file['data']);

// These headers tell the browser: "This is a pure file, don't try to read it as text"
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $size);

// 7. Output the data and STOP everything else immediately
echo $file['data'];
exit; // CRITICAL: This prevents any trailing spaces in this file from being added to the PPT