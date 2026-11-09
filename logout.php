<?php
require "session.php";
check_form();

$wasAdmin = isset($_SESSION["admin_id"]);
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $cookie["path"],
    "httponly" => true,
    "secure" => $cookie["secure"],
    "samesite" => "Lax"
]);
session_destroy();
if ($wasAdmin) {
    redirect("admin-signin.php");
}
redirect("signin.php");
