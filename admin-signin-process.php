<?php
require "session.php";
check_form();

$email = post_text("email");
$password = $_POST["password"] ?? "";
$_SESSION["admin_login_email"] = mb_substr($email, 0, 180);

if (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !is_string($password) || $password === "" || strlen($password) > 72) {
    $_SESSION["admin_message"] = "Invalid admin email or password.";
    redirect("admin-signin.php");
}

require "db.php";
$stmt = $conn->prepare("SELECT admin_id, password_hash FROM admins WHERE email = ? AND status = 'Active'");
$stmt->bind_param("s", $email);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin || !password_verify($password, $admin["password_hash"])) {
    $_SESSION["admin_message"] = "Invalid admin email or password.";
    redirect("admin-signin.php");
}

session_regenerate_id(true);
$_SESSION = [
    "admin_id" => (int) $admin["admin_id"],
    "token" => bin2hex(random_bytes(32))
];
redirect("admin-signin.php");
