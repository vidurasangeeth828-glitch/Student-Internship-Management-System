<?php
require "session.php";
check_form();
require "db.php";

$email = post_text("email");
$password = $_POST["password"] ?? "";

 Nuzrath
$stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE email = ? AND status = 'Active'");

$stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ? AND status = 'Active'");
 main
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
        "samesite" => "Lax"
    ]);
} else {
    setcookie("sims_email", "", [
        "expires" => time() - 3600,
        "path" => rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/") . "/",
        "httponly" => true,
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
 Nuzrath
    "token" => bin2hex(random_bytes(32))
];
 Updated upstream


    "role" => $user["role"],
    "token" => bin2hex(random_bytes(32))
];
 main
if ($user["role"] === "student") {
    if ($applyAfterLogin > 0) {
        redirect("apply.php?id=" . $applyAfterLogin);
    }
    redirect("student-dashboard.php");
}
 Nuzrath
 Stashed changes
redirect("account.php");

redirect("company-dashboard.php");
 main
