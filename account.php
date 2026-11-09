<?php
require "session.php";
if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}
require "db.php";

$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, c.approval_status FROM users u
                        LEFT JOIN companies c ON c.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    unset($_SESSION["user_id"]);
    redirect("signin.php");
}
if ($user["role"] === "student") {
    redirect("student-profile.php");
}
redirect("company-profile.php");
