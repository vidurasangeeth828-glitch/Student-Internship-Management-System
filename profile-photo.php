<?php
require "session.php";
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    exit();
}
require "db.php";

// Serve only the photo belonging to the student who is signed in.
$stmt = $conn->prepare("SELECT s.photo_path FROM students s
                        JOIN users u ON u.user_id = s.user_id
                        WHERE u.user_id = ? AND u.role = 'student' AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student || !preg_match('/\Auploads\/profile-photos\/[a-f0-9]{48}\.(jpg|png)\z/', $student["photo_path"])) {
    http_response_code(404);
    exit();
}

$directory = realpath(__DIR__ . "/uploads/profile-photos");
$filePath = realpath(__DIR__ . "/" . $student["photo_path"]);
$prefix = $directory . DIRECTORY_SEPARATOR;
if (!$directory || !$filePath || strncasecmp($filePath, $prefix, strlen($prefix)) !== 0 || !is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit();
}

$image = @getimagesize($filePath);
if (!$image || !in_array($image[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
    http_response_code(404);
    exit();
}

session_write_close();
header("Content-Type: " . $image["mime"]);
header("Cache-Control: private, no-store, max-age=0");
header("X-Content-Type-Options: nosniff");
readfile($filePath);
