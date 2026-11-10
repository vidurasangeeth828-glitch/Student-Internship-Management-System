<?php
session_start([
    "use_strict_mode" => 1,
    "cookie_httponly" => true,
    "cookie_samesite" => "Lax"
]);

if (empty($_SESSION["token"])) {
    $_SESSION["token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function post_text($name) {
    if (isset($_POST[$name]) && is_string($_POST[$name])) {
        return trim($_POST[$name]);
    }
    return "";
}

function redirect($page) {
    header("Location: " . $page);
    exit();
}

function check_form() {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        exit("Please submit the form.");
    }
    if (!hash_equals($_SESSION["token"], post_text("token"))) {
        http_response_code(403);
        exit("Your form expired. Please reload the page.");
    }
}

function form_message() {
    if (isset($_SESSION["message"])) {
        echo '<p class="form-message" role="status">' . e($_SESSION["message"]) . '</p>';
        unset($_SESSION["message"]);
    }
}
