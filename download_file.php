<?php
// Download or view a stored student file from the database.
// Usage example:
//   download_file.php?id=123&type=document
//   download_file.php?id=123&type=ppt
//   download_file.php?id=123&type=code

include 'db.php';

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = $_GET['type'] ?? '';

if ($id <= 0 || !in_array($type, ['document', 'ppt', 'code'], true)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

// Map type to column names
$nameCol = $type . '_name';
$mimeCol = $type . '_type';
$sizeCol = $type . '_size';
$dataCol = $type . '_data';

$sql = "SELECT $nameCol AS name, $mimeCol AS mime, $sizeCol AS size, $dataCol AS data
        FROM student_files
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo 'DB error (prepare).';
    exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$file   = $result->fetch_assoc();
$stmt->close();

if (!$file || empty($file['data'])) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$filename = $file['name'] ?: ($type . '_file');
$mime     = $file['mime'] ?: 'application/octet-stream';
$size     = (int) $file['size'];
$data     = $file['data'];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . $size);

echo $data;
exit;

