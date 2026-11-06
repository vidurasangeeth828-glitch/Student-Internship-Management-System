<?php
require "session.php";
check_form();

$role = post_text("role");
$name = post_text("name");
$email = post_text("email");
$phone = post_text("phone");
$registration = post_text("registration");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm_password"] ?? "";

$_SESSION["signup_values"] = [
    "role" => $role,
    "name" => $name,
    "email" => $email,
    "phone" => $phone,
    "registration" => $registration
];

if (!in_array($role, ["student", "company"], true)) {
    $_SESSION["message"] = "Choose Student or Company.";
    redirect("signup.php");
}
if ($name === "" || mb_strlen($name) > 120 || strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["message"] = "Enter your name and a valid email address.";
    redirect("signup.php");
}
if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
    $_SESSION["message"] = "Use a password of 8 to 72 bytes.";
    redirect("signup.php");
}
if ($password !== $confirm) {
    $_SESSION["message"] = "The passwords do not match.";
    redirect("signup.php");
}
if ($role === "company" && (!preg_match('/^[+0-9 ()-]{7,30}$/', $phone) ||
    strlen(preg_replace('/[^0-9]/', '', $phone)) < 7 || mb_strlen($registration) > 80)) {
    $_SESSION["message"] = "Enter a valid contact number and a registration number of up to 80 characters.";
    redirect("signup.php");
}

require "db.php";
$hash = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hash, $role);
    $stmt->execute();
    $userId = $conn->insert_id;

    if ($role === "student") {
        $stmt = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare("INSERT INTO companies (user_id, company_name, phone, business_registration_no) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $name, $phone, $registration);
    }
    $stmt->execute();
    $conn->commit();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    $_SESSION["message"] = $error->getCode() === 1062 ? "An account already uses that email address." : "Your account could not be saved. Please try again.";
    redirect("signup.php");
}

unset($_SESSION["signup_values"]);
$_SESSION["message"] = "Account created. Sign in to continue.";
redirect("signin.php");
