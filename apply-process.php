<?php
require "session.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && (int) ($_SERVER["CONTENT_LENGTH"] ?? 0) > 3 * 1024 * 1024) {
    http_response_code(413);
    exit("The upload is too large. Go back and choose a PDF CV smaller than 2 MB.");
}

check_form();

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$internshipId = filter_var(post_text("internship_id"), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
if ($internshipId === false) {
    http_response_code(400);
    exit("Please choose a valid internship.");
}

$stmt = $conn->prepare("SELECT u.role, s.student_id
                        FROM users u
                        LEFT JOIN students s ON s.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || $user["role"] !== "student" || !$user["student_id"]) {
    http_response_code(403);
    exit("Only students with an active profile can apply for internships.");
}

$coverLetter = post_text("cover_letter");
$error = "";
if (mb_strlen($coverLetter) > 2000) {
    $error = "Keep your cover letter within 2,000 characters.";
}

$cv = $_FILES["cv"] ?? null;
$hasCv = $cv !== null;
if (is_array($cv) && isset($cv["error"]) && $cv["error"] === UPLOAD_ERR_NO_FILE) {
    $hasCv = false;
}
if ($hasCv) {
    if (!is_array($cv) || !isset($cv["error"], $cv["name"], $cv["tmp_name"], $cv["size"]) || is_array($cv["error"])) {
        $error = "Please choose a PDF CV to upload.";
    } elseif ($cv["error"] === UPLOAD_ERR_INI_SIZE || $cv["error"] === UPLOAD_ERR_FORM_SIZE) {
        $error = "Your CV must be 2 MB or smaller.";
    } elseif ($cv["error"] !== UPLOAD_ERR_OK) {
        $error = "Your CV could not be uploaded. Please choose the file again.";
    } elseif ($cv["size"] < 1 || $cv["size"] > 2 * 1024 * 1024) {
        $error = "Choose a PDF CV between 1 byte and 2 MB.";
    } elseif (strtolower(pathinfo($cv["name"], PATHINFO_EXTENSION)) !== "pdf" || !is_uploaded_file($cv["tmp_name"])) {
        $error = "Please upload a PDF file only.";
    } else {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $fileInfo->file($cv["tmp_name"]);
        $fileStart = file_get_contents($cv["tmp_name"], false, null, 0, 5);
        if ($fileType !== "application/pdf" || $fileStart !== "%PDF-") {
            $error = "This file is not a valid PDF. Please export your CV as a PDF and try again.";
        }
    }
}

if ($error !== "") {
    $_SESSION["apply_form"] = [
        "internship_id" => $internshipId,
        "cover_letter" => mb_substr($coverLetter, 0, 2000),
        "error" => $error
    ];
    redirect("apply.php?id=" . $internshipId);
}

$savedFile = "";
$conn->begin_transaction();

try {
    // Lock the student and vacancy until this application has been saved.
    $stmt = $conn->prepare("SELECT s.student_id FROM students s
                            JOIN users u ON u.user_id = s.user_id
                            WHERE u.user_id = ? AND u.role = 'student' AND u.status = 'Active'
                            FOR UPDATE");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT i.internship_id FROM internships i
                            JOIN companies c ON c.company_id = i.company_id
                            JOIN users u ON u.user_id = c.user_id
                            WHERE i.internship_id = ? AND i.status = 'Open' AND i.deadline >= ?
                            AND c.approval_status = 'Approved' AND u.status = 'Active'
                            FOR UPDATE");
    $stmt->bind_param("is", $internshipId, $today);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();

    if (!$student || !$internship) {
        $conn->rollback();
        $_SESSION["apply_form"] = [
            "internship_id" => $internshipId,
            "cover_letter" => $coverLetter,
            "error" => "This internship or your student account is no longer available for applications."
        ];
        redirect("apply.php?id=" . $internshipId);
    }

    $studentId = $student["student_id"];
    $stmt = $conn->prepare("SELECT application_id FROM applications WHERE student_id = ? AND internship_id = ?");
    $stmt->bind_param("ii", $studentId, $internshipId);
    $stmt->execute();
    $existingApplication = $stmt->get_result()->fetch_assoc();

    if ($existingApplication) {
        $conn->rollback();
        redirect("apply.php?id=" . $internshipId);
    }

    $cvPath = null;
    if ($hasCv) {
        $uploadDirectory = __DIR__ . "/uploads/cvs";
        if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
            throw new RuntimeException("CV storage is unavailable.");
        }

        // Use a new random filename instead of the student's original filename.
        $fileName = bin2hex(random_bytes(24)) . ".pdf";
        $cvPath = "uploads/cvs/" . $fileName;
        $savedFile = $uploadDirectory . "/" . $fileName;
        if (!move_uploaded_file($cv["tmp_name"], $savedFile)) {
            throw new RuntimeException("CV could not be saved.");
        }
    }

    $stmt = $conn->prepare("INSERT INTO applications (student_id, internship_id, cover_letter, cv_path, status)
                            VALUES (?, ?, ?, ?, 'Submitted')");
    $stmt->bind_param("iiss", $studentId, $internshipId, $coverLetter, $cvPath);
    $stmt->execute();
    $conn->commit();

    unset($_SESSION["apply_form"]);
    $_SESSION["applied_internship"] = $internshipId;
    redirect("apply.php?id=" . $internshipId);
} catch (Throwable $error) {
    $conn->rollback();
    if ($savedFile !== "" && is_file($savedFile)) {
        unlink($savedFile);
    }

    error_log("Application save failed: " . $error->getMessage());
    $_SESSION["apply_form"] = [
        "internship_id" => $internshipId,
        "cover_letter" => $coverLetter,
        "error" => "Your application could not be saved. Please try again."
    ];
    redirect("apply.php?id=" . $internshipId);
}
