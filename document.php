<?php
// Sends an applicant's uploaded documents to an admin. The documents folder itself
// is closed to the web, so this page is the only way to open them.
require_once 'auth.php';
require_role([ROLE_ADMIN]);

const DOCUMENT_TYPES = [
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];

$temp_id = (string) ($_GET['temp_id'] ?? '');
$stmt = $conn->prepare("SELECT Document_Path FROM applications WHERE Temp_ID = ? ORDER BY Application_ID DESC LIMIT 1");
$stmt->bind_param("s", $temp_id);
$stmt->execute();
$stored = (string) ($stmt->get_result()->fetch_assoc()['Document_Path'] ?? '');

// basename() keeps the lookup inside the documents folder.
$path = __DIR__ . '/documents/' . basename($stored);
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if ($stored === '' || !isset(DOCUMENT_TYPES[$ext]) || !is_file($path)) {
    http_response_code(404);
    exit("No uploaded documents were found for this applicant.");
}

header("Content-Type: " . DOCUMENT_TYPES[$ext]);
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
header('Content-Disposition: ' . ($ext === 'docx' ? 'attachment' : 'inline') . '; filename="application-' . preg_replace('/[^A-Za-z0-9._-]/', '', $temp_id) . ".$ext\"");
readfile($path);
exit;
?>
