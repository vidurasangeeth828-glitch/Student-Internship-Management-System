<?php
require "session.php";
check_form();

$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $cookie["path"],
    "httponly" => true,
    "samesite" => "Lax"
]);
session_destroy();
redirect("signin.php");
