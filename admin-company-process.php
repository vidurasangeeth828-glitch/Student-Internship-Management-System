<?php
require "session.php";
check_form();

if (!isset($_SESSION["admin_id"])) {
    redirect("admin-signin.php");
}

require "db.php";
$stmt = $conn->prepare("SELECT admin_id FROM admins WHERE admin_id = ? AND status = 'Active'");
$stmt->bind_param("i", $_SESSION["admin_id"]);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
if (!$admin) {
    unset($_SESSION["admin_id"]);
    redirect("admin-signin.php");
}

$companyId = filter_var(post_text("company_id"), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$decision = post_text("decision");
$expectedStatus = post_text("expected_status");
$returnStatus = post_text("return_status");
$expectedDetails = post_text("expected_details");
$statuses = ["Pending", "Approved", "Rejected"];
if ($companyId === false || !in_array($decision, ["Approved", "Rejected"], true) || !in_array($expectedStatus, $statuses, true)) {
    http_response_code(400);
    exit("Please choose a valid company approval action.");
}
if ($returnStatus !== "" && !in_array($returnStatus, $statuses, true)) {
    $returnStatus = "Pending";
}

$message = "";
$isError = false;
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE admin_id = ? AND status = 'Active' FOR UPDATE");
    $stmt->bind_param("i", $_SESSION["admin_id"]);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $conn->rollback();
        unset($_SESSION["admin_id"]);
        redirect("admin-signin.php");
    }

    $stmt = $conn->prepare("SELECT c.*, u.status FROM companies c
                            JOIN users u ON u.user_id = c.user_id
                            WHERE c.company_id = ? AND u.role = 'company'
                            FOR UPDATE");
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();

    $currentDetails = "";
    if ($company) {
        $currentDetails = hash("sha256", json_encode([
            $company["company_name"], $company["phone"], $company["business_registration_no"],
            $company["website"], $company["description"], $company["contact_person"]
        ]));
    }
    if (!$company) {
        $message = "This company could not be found. Refresh the list and try again.";
        $isError = true;
    } elseif ($company["status"] !== "Active") {
        $message = "This company account is inactive. Its approval status has not been changed.";
        $isError = true;
    } elseif (!hash_equals($currentDetails, $expectedDetails)) {
        $message = "The company details have changed. Review the latest registration information before deciding.";
        $isError = true;
    } elseif ($company["approval_status"] !== $expectedStatus) {
        $message = "The company's status has changed since this page was opened. Review its latest status before trying again.";
        $isError = true;
    } elseif ($company["approval_status"] === $decision) {
        $message = "The company is already " . strtolower($decision) . ".";
    } else {
        $stmt = $conn->prepare("UPDATE companies SET approval_status = ? WHERE company_id = ?");
        $stmt->bind_param("si", $decision, $companyId);
        $stmt->execute();
        $message = "Company " . strtolower($decision) . " successfully.";
    }
    $conn->commit();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log("Company approval update failed: " . $error->getMessage());
    $message = "The company status could not be saved. Please try again.";
    $isError = true;
}

$_SESSION["admin_company_notice"] = ["message" => $message, "error" => $isError];
redirect("admin-companies.php?status=" . urlencode($returnStatus));
