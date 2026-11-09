<?php
require "session.php";
check_form();

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
$stmt = $conn->prepare("SELECT u.role, s.student_id FROM users u
                        LEFT JOIN students s ON s.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user["role"] !== "student" || !$user["student_id"]) {
    http_response_code(403);
    exit("This page is available to active student accounts only.");
}

$name = post_text("full_name");
$phone = post_text("phone");
$field = post_text("field_of_interest");
$about = post_text("about_me");
$education = post_text("education_status");
$skills = post_text("skills");
$linkedin = post_text("linkedin_url");
$github = post_text("github_url");
$errors = [];

if ($name === "" || mb_strlen($name) > 120) {
    $errors["full_name"] = "Enter your full name using up to 120 characters.";
}
if ($phone !== "" && (!preg_match('/^[+0-9 ()-]{7,30}$/', $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 7)) {
    $errors["phone"] = "Enter a valid contact number using 7 to 30 characters.";
}
if (mb_strlen($field) > 100) {
    $errors["field_of_interest"] = "Keep your field of interest within 100 characters.";
}
if (mb_strlen($about) > 1000) {
    $errors["about_me"] = "Keep your introduction within 1,000 characters.";
}
if ($education !== "" && !in_array($education, ["Undergraduate", "Graduate", "Postgraduate", "Other"], true)) {
    $errors["education_status"] = "Choose an education status from the list.";
}
if (mb_strlen($skills) > 500) {
    $errors["skills"] = "Keep your skills within 500 characters.";
}

foreach (["linkedin_url" => $linkedin, "github_url" => $github] as $key => $url) {
    $host = $key === "linkedin_url" ? "linkedin.com" : "github.com";
    if ($url !== "" && (strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)
        || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== "https"
        || !in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), [$host, "www." . $host], true)
        || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null)) {
        $errors[$key] = "Enter an https:// profile link on " . $host . ".";
    }
}

// Keep form values if validation or saving fails.
$values = [
    "full_name" => mb_substr($name, 0, 120),
    "phone" => mb_substr($phone, 0, 30),
    "field_of_interest" => mb_substr($field, 0, 100),
    "about_me" => mb_substr($about, 0, 1000),
    "education_status" => mb_substr($education, 0, 30),
    "skills" => mb_substr($skills, 0, 500),
    "linkedin_url" => mb_substr($linkedin, 0, 255),
    "github_url" => mb_substr($github, 0, 255)
];

if ($errors) {
    $_SESSION["profile_form"] = ["values" => $values, "errors" => $errors];
    redirect("student-profile.php");
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT s.student_id FROM students s
                            JOIN users u ON u.user_id = s.user_id
                            WHERE u.user_id = ? AND u.role = 'student' AND u.status = 'Active'
                            FOR UPDATE");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    if (!$student) {
        $conn->rollback();
        http_response_code(403);
        exit("Your student account is no longer available.");
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
    $stmt->bind_param("si", $name, $_SESSION["user_id"]);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE students SET field_of_interest = ?, phone = ?, about_me = ?,
                            education_status = ?, skills = ?, linkedin_url = ?, github_url = ?
                            WHERE student_id = ?");
    $stmt->bind_param("sssssssi", $field, $phone, $about, $education, $skills, $linkedin, $github, $student["student_id"]);
    $stmt->execute();
    $conn->commit();

    unset($_SESSION["profile_form"]);
    $_SESSION["profile_saved"] = true;
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log("Student profile update failed: " . $error->getMessage());
    $errors["save"] = "Your profile could not be saved. Please try again.";
    $_SESSION["profile_form"] = ["values" => $values, "errors" => $errors];
}

redirect("student-profile.php");
