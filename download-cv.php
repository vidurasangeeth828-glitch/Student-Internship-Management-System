<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$stmt = $conn->prepare("SELECT u.full_name, u.role, s.student_id, c.company_id
                        FROM users u
                        LEFT JOIN students s ON s.user_id = u.user_id
                        LEFT JOIN companies c ON c.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    unset($_SESSION["user_id"], $_SESSION["role"]);
    redirect("signin.php");
}

if (($user["role"] === "student" && !$user["student_id"]) || ($user["role"] === "company" && !$user["company_id"])) {
    http_response_code(403);
    exit("Your account profile is not available.");
}

if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
    header("Allow: GET, HEAD");
    http_response_code(405);
    exit("Please use the CV download link.");
}

$applicationId = false;
if (isset($_GET["id"]) && is_string($_GET["id"])) {
    $applicationId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
}

if ($applicationId === false) {
    http_response_code(404);
    exit("CV not available.");
}

// Students can download their own CV. Companies can download CVs sent to their internships.
if ($user["role"] === "student") {
    $stmt = $conn->prepare("SELECT cv_path FROM applications WHERE application_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $applicationId, $user["student_id"]);
} elseif ($user["role"] === "company") {
    $stmt = $conn->prepare("SELECT a.cv_path FROM applications a
                            JOIN internships i ON i.internship_id = a.internship_id
                            WHERE a.application_id = ? AND i.company_id = ?");
    $stmt->bind_param("ii", $applicationId, $user["company_id"]);
} else {
    http_response_code(403);
    exit("CV access is not available for this account.");
}
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application || !preg_match('/\Auploads\/cvs\/[a-f0-9]{48}\.pdf\z/', (string) $application["cv_path"])) {
    http_response_code(404);
    exit("CV not available.");
}

$cvDirectory = realpath(__DIR__ . "/uploads/cvs");
$filePath = realpath(__DIR__ . "/" . $application["cv_path"]);
$directoryPrefix = $cvDirectory . DIRECTORY_SEPARATOR;

// Only serve files inside the CV directory, including when a link is present.
if (!$cvDirectory || !$filePath || strncasecmp($filePath, $directoryPrefix, strlen($directoryPrefix)) !== 0 || !is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit("CV not available.");
}

$file = fopen($filePath, "rb");
if ($file === false) {
    http_response_code(404);
    exit("CV not available.");
}

$fileInfo = fstat($file);
session_write_close();
header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="SIMS-CV-application-' . $applicationId . '.pdf"');
header("Content-Length: " . $fileInfo["size"]);
header("Cache-Control: private, no-store, max-age=0");
header("X-Content-Type-Options: nosniff");

if ($_SERVER["REQUEST_METHOD"] !== "HEAD") {
    fpassthru($file);
}
fclose($file);
