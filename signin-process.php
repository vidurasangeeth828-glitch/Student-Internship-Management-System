<?php
require "session.php";
check_form();
require "db.php";

$email = post_text("email");
$password = $_POST["password"] ?? "";

$stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ? AND status = 'Active'");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !is_string($password) || strlen($password) > 72 || !password_verify($password, $user["password_hash"])) {
    $_SESSION["message"] = "Invalid email or password.";
    redirect("signin.php");
}

if (post_text("remember_email") === "1") {
    setcookie("sims_email", $email, [
        "expires" => time() + 30 * 24 * 60 * 60,
        "path" => rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/") . "/",
        "httponly" => true,
        "secure" => $secureConnection,
        "samesite" => "Lax"
    ]);
} else {
    setcookie("sims_email", "", [
        "expires" => time() - 3600,
        "path" => rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/") . "/",
        "httponly" => true,
        "secure" => $secureConnection,
        "samesite" => "Lax"
    ]);
}

$applyAfterLogin = 0;
if (isset($_SESSION["apply_after_login"]) && is_int($_SESSION["apply_after_login"])) {
    $applyAfterLogin = $_SESSION["apply_after_login"];
}

session_regenerate_id(true);
$_SESSION = [
    "user_id" => (int) $user["user_id"],
    "role" => $user["role"],
    "token" => bin2hex(random_bytes(32))
];
if ($user["role"] === "student") {
    if ($applyAfterLogin > 0) {
        redirect("apply.php?id=" . $applyAfterLogin);
    }
    redirect("student-dashboard.php");
}
redirect("company-dashboard.php");
