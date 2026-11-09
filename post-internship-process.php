<?php
require "session.php";
check_form();

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");
$stmt = $conn->prepare("SELECT c.company_id FROM companies c
                        JOIN users u ON u.user_id = c.user_id
                        WHERE u.user_id = ? AND u.role = 'company' AND u.status = 'Active'
                        AND c.approval_status = 'Approved'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
if (!$company) {
    http_response_code(403);
    exit("Only approved, active companies can post internships.");
}

// A saved form cannot be submitted a second time.
if (empty($_SESSION["posting_token"]) || !hash_equals($_SESSION["posting_token"], post_text("posting_token"))) {
    http_response_code(403);
    exit("This form has expired or was already submitted. Open Post Internship again.");
}

$title = post_text("title");
$category = post_text("category");
$location = post_text("location");
$deadline = post_text("deadline");
$description = post_text("description");
$categories = [
    "Software Development",
    "Data & Analytics",
    "Design & Creative",
    "Marketing",
    "Business & Management",
    "Engineering",
    "Finance & Accounting",
    "Other"
];
$errors = [];

if (mb_strlen($title) < 5 || mb_strlen($title) > 120) {
    $errors["title"] = "Enter an internship title of 5 to 120 characters.";
}
if (!in_array($category, $categories, true)) {
    $errors["category"] = "Choose a category from the list.";
}
if (mb_strlen($location) < 2 || mb_strlen($location) > 80) {
    $errors["location"] = "Enter a location of 2 to 80 characters, or use Remote.";
}
if (mb_strlen($description) < 20 || mb_strlen($description) > 10000) {
    $errors["description"] = "Enter a description of 20 to 10,000 characters.";
}

$validDate = false;
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
    $dateParts = explode("-", $deadline);
    $validDate = checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0]);
}
if (!$validDate || $deadline < date("Y-m-d")) {
    $errors["deadline"] = "Choose today or a future application deadline.";
}

$values = [
    "title" => mb_substr($title, 0, 120),
    "category" => mb_substr($category, 0, 80),
    "location" => mb_substr($location, 0, 80),
    "deadline" => mb_substr($deadline, 0, 10),
    "description" => mb_substr($description, 0, 10000)
];
if ($errors) {
    $_SESSION["vacancy_form"] = ["values" => $values, "errors" => $errors];
    redirect("post-internship.php");
}

$conn->begin_transaction();
try {
    // Approval can change while the company is filling out the form.
    $stmt = $conn->prepare("SELECT c.company_id FROM companies c
                            JOIN users u ON u.user_id = c.user_id
                            WHERE u.user_id = ? AND u.role = 'company' AND u.status = 'Active'
                            AND c.approval_status = 'Approved'
                            FOR UPDATE");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    if (!$company) {
        $conn->rollback();
        http_response_code(403);
        exit("Your company is no longer approved to post internships.");
    }
    if ($deadline < date("Y-m-d")) {
        $conn->rollback();
        $_SESSION["vacancy_form"] = ["values" => $values, "errors" => ["deadline" => "Choose today or a future deadline."]];
        redirect("post-internship.php");
    }

    $companyId = $company["company_id"];
    $stmt = $conn->prepare("INSERT INTO internships (company_id, title, description, category, location, deadline, status)
                            VALUES (?, ?, ?, ?, ?, ?, 'Open')");
    $stmt->bind_param("isssss", $companyId, $title, $description, $category, $location, $deadline);
    $stmt->execute();
    $internshipId = $conn->insert_id;
    $conn->commit();

    unset($_SESSION["vacancy_form"], $_SESSION["posting_token"]);
    $_SESSION["vacancy_posted"] = $internshipId;
    redirect("company-dashboard.php#company-vacancies");
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log("Internship posting failed: " . $error->getMessage());
    $_SESSION["vacancy_form"] = ["values" => $values, "errors" => ["save" => "Your internship could not be saved. Please try again."]];
    redirect("post-internship.php");
}
