<?php
require "session.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && (int) ($_SERVER["CONTENT_LENGTH"] ?? 0) > 3 * 1024 * 1024) {
    http_response_code(413);
    exit("The photo is too large. Go back and choose a JPG or PNG smaller than 2 MB.");
}
check_form();

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
$stmt = $conn->prepare("SELECT s.student_id FROM students s
                        JOIN users u ON u.user_id = s.user_id
                        WHERE u.user_id = ? AND u.role = 'student' AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    http_response_code(403);
    exit("Only active student accounts can update a profile photo.");
}

$action = post_text("action");
if (!in_array($action, ["upload", "remove"], true)) {
    http_response_code(400);
    exit("Choose a valid photo action.");
}

$error = "";
$extension = "";
$photo = $_FILES["photo"] ?? null;
if ($action === "upload") {
    if (!is_array($photo) || !isset($photo["error"], $photo["name"], $photo["tmp_name"], $photo["size"]) || is_array($photo["error"])) {
        $error = "Please choose a JPG or PNG photo.";
    } elseif ($photo["error"] === UPLOAD_ERR_INI_SIZE || $photo["error"] === UPLOAD_ERR_FORM_SIZE || $photo["size"] > 2 * 1024 * 1024) {
        $error = "Choose a photo that is 2 MB or smaller.";
    } elseif ($photo["error"] !== UPLOAD_ERR_OK || $photo["size"] < 1 || !is_uploaded_file($photo["tmp_name"])) {
        $error = "Your photo could not be uploaded. Please choose the file again.";
    } else {
        $extension = strtolower(pathinfo($photo["name"], PATHINFO_EXTENSION));
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($photo["tmp_name"]);
        $image = @getimagesize($photo["tmp_name"]);
        if (!$image || !in_array($extension, ["jpg", "jpeg", "png"], true)
            || !(($mime === "image/jpeg" && $image[2] === IMAGETYPE_JPEG && in_array($extension, ["jpg", "jpeg"], true))
            || ($mime === "image/png" && $image[2] === IMAGETYPE_PNG && $extension === "png"))) {
            $error = "Upload a real JPG or PNG image. Other file types are not supported.";
        } elseif ($image[0] < 1 || $image[1] < 1 || $image[0] > 4096 || $image[1] > 4096 || $image[0] * $image[1] > 16000000) {
            $error = "Choose a photo no larger than 4,096 pixels per side and 16 megapixels.";
        }
    }
}

if ($error !== "") {
    $_SESSION["photo_error"] = $error;
    redirect("student-profile.php?photo=1#photo-upload");
}

$newFile = "";
$oldPath = "";
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT s.student_id, s.photo_path FROM students s
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

    $oldPath = $student["photo_path"];
    $photoPath = "";
    if ($action === "upload") {
        $directory = __DIR__ . "/uploads/profile-photos";
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException("Profile photo storage is unavailable.");
        }
        if ($extension === "jpeg") {
            $extension = "jpg";
        }
        $photoPath = "uploads/profile-photos/" . bin2hex(random_bytes(24)) . "." . $extension;
        $newFile = __DIR__ . "/" . $photoPath;
        if (!move_uploaded_file($photo["tmp_name"], $newFile)) {
            throw new RuntimeException("The profile photo could not be saved.");
        }
    }

    $stmt = $conn->prepare("UPDATE students SET photo_path = ? WHERE student_id = ?");
    $stmt->bind_param("si", $photoPath, $student["student_id"]);
    $stmt->execute();
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    if ($newFile !== "" && is_file($newFile)) {
        unlink($newFile);
    }
    error_log("Profile photo save failed: " . $error->getMessage());
    $_SESSION["photo_error"] = "Your photo could not be saved. Please try again.";
    redirect("student-profile.php?photo=1#photo-upload");
}

// Remove only the previous photo after the new database value has been saved.
if (preg_match('/\Auploads\/profile-photos\/[a-f0-9]{48}\.(jpg|png)\z/', $oldPath)) {
    $oldFile = __DIR__ . "/" . $oldPath;
    if (is_file($oldFile) && !is_link($oldFile)) {
        unlink($oldFile);
    }
}
$_SESSION["photo_saved"] = $action === "remove" ? "Your profile photo has been removed." : "Your profile photo has been updated.";
redirect("student-profile.php");
