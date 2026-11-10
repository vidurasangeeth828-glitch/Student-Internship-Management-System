<?php
require "session.php";
check_form();

 chamodya
$wasAdmin = isset($_SESSION["admin_id"]);
=======
 main
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $cookie["path"],
    "httponly" => true,
    "samesite" => "Lax"
]);
session_destroy();
 chamodya
if ($wasAdmin) {
    redirect("admin-signin.php");
}
=======
 main
redirect("signin.php");
