<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, c.company_id, c.company_name,
                        c.phone, c.business_registration_no, c.approval_status, c.website, c.description, c.contact_person
                        FROM users u
                        LEFT JOIN companies c ON c.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    unset($_SESSION["user_id"], $_SESSION["role"]);
    redirect("signin.php");
}
if ($company["role"] !== "company") {
    redirect("student-dashboard.php");
}
if (!$company["company_id"]) {
    http_response_code(403);
    exit("Your company profile could not be found. Please contact the administrator.");
}

$companyId = $company["company_id"];
$errors = [];
$notice = $_SESSION["company_profile_notice"] ?? "";
unset($_SESSION["company_profile_notice"]);
$editing = isset($_GET["edit"]) && $_GET["edit"] === "1";
$fields = ["company_name", "phone", "business_registration_no", "website", "contact_person", "description"];
$values = $company;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_form();
    $action = post_text("action");
    if (!in_array($action, ["save", "review"], true)) {
        http_response_code(400);
        exit("Invalid profile action.");
    }
    $editing = $action === "save";
    if ($editing) {
        foreach ($fields as $field) {
            $values[$field] = post_text($field);
        }
        if (mb_strlen($values["company_name"]) < 2 || mb_strlen($values["company_name"]) > 120) {
            $errors["company_name"] = "Enter a company name of 2 to 120 characters.";
        }
        if (!preg_match('/^[+0-9 ()-]{7,30}$/', $values["phone"]) || strlen(preg_replace('/[^0-9]/', '', $values["phone"])) < 7) {
            $errors["phone"] = "Enter a valid contact number.";
        }
        if (mb_strlen($values["business_registration_no"]) > 80) {
            $errors["business_registration_no"] = "Use up to 80 characters.";
        }
        if (mb_strlen($values["contact_person"]) > 100) {
            $errors["contact_person"] = "Use up to 100 characters.";
        }
        if (mb_strlen($values["description"]) > 3000) {
            $errors["description"] = "Keep the description within 3,000 characters.";
        }
        if ($values["website"] !== "" && (strlen($values["website"]) > 255 || !filter_var($values["website"], FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($values["website"], PHP_URL_SCHEME)), ["http", "https"], true))) {
            $errors["website"] = "Enter a complete http:// or https:// website address.";
        }
    }
    if (!$errors) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT c.company_name, c.phone, c.business_registration_no, c.website,
                                    c.description, c.contact_person, c.approval_status
                                    FROM companies c JOIN users u ON u.user_id = c.user_id
                                    WHERE c.company_id = ? AND u.user_id = ? AND u.status = 'Active' AND u.role = 'company'
                                    FOR UPDATE");
            $stmt->bind_param("ii", $companyId, $_SESSION["user_id"]);
            $stmt->execute();
            $current = $stmt->get_result()->fetch_assoc();
            if (!$current) {
                $conn->rollback();
                http_response_code(403);
                exit("Your company account is unavailable.");
            }
            if ($action === "review") {
                if ($current["approval_status"] !== "Rejected") {
                    $notice = "Your company is already approved or awaiting review.";
                } else {
                    $stmt = $conn->prepare("UPDATE companies SET approval_status = 'Pending' WHERE company_id = ?");
                    $stmt->bind_param("i", $companyId);
                    $stmt->execute();
                    $notice = "Your registration has been sent for admin review.";
                }
            } else {
                $editToken = post_text("edit_token");
                if (!isset($_SESSION["company_profile_edit"][$editToken]) || $_SESSION["company_profile_edit"][$editToken] != $current) {
                    $conn->rollback();
                    $_SESSION["company_profile_notice"] = "Your profile or approval status has changed. Open Edit Profile again.";
                    redirect("company-profile.php");
                }
                $approval = $current["approval_status"];
                if ($values["company_name"] !== $current["company_name"] || $values["business_registration_no"] !== $current["business_registration_no"]) {
                    $approval = "Pending";
                }
                $stmt = $conn->prepare("UPDATE companies SET company_name = ?, phone = ?, business_registration_no = ?,
                                        website = ?, contact_person = ?, description = ?, approval_status = ? WHERE company_id = ?");
                $stmt->bind_param("sssssssi", $values["company_name"], $values["phone"], $values["business_registration_no"], $values["website"], $values["contact_person"], $values["description"], $approval, $companyId);
                $stmt->execute();
                $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
                $stmt->bind_param("si", $values["company_name"], $_SESSION["user_id"]);
                $stmt->execute();
                $notice = "Company profile saved.";
                if ($approval === "Pending" && $current["approval_status"] !== "Pending") {
                    $notice .= " Your updated company identity needs admin approval before publishing vacancies.";
                }
                unset($_SESSION["company_profile_edit"][$editToken]);
            }
            $conn->commit();
            $_SESSION["company_profile_notice"] = $notice;
            redirect("company-profile.php");
        } catch (mysqli_sql_exception $error) {
            $conn->rollback();
            error_log("Company profile update failed: " . $error->getMessage());
            $errors["save"] = "Your profile could not be saved. Please try again.";
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
    header("Allow: GET, HEAD, POST");
    http_response_code(405);
    exit("Please use the profile form.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" && $editing) {
    $editToken = bin2hex(random_bytes(24));
    $_SESSION["company_profile_edit"] = [];
    $snapshot = [];
    foreach ($fields as $field) {
        $snapshot[$field] = $company[$field];
    }
    $snapshot["approval_status"] = $company["approval_status"];
    $_SESSION["company_profile_edit"][$editToken] = $snapshot;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $editToken = post_text("edit_token");
}
$labels = [
    "company_name" => "Company name", "phone" => "Contact number",
    "business_registration_no" => "Business registration number", "website" => "Company website",
    "contact_person" => "Contact person", "description" => "About the company"
];
$limits = ["company_name" => 120, "phone" => 30, "business_registration_no" => 80, "website" => 255, "contact_person" => 100, "description" => 3000];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company profile | SIMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="dashboard.js" defer></script>
</head>
<body>
    <svg class="icon-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="icon-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10H3zM9 20v-7h6v7"/></symbol>
        <symbol id="icon-search" viewBox="0 0 24 24"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></symbol>
        <symbol id="icon-file" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6m-6 5h6m-6 5h4"/></symbol>
        <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2z"/></symbol>
        <symbol id="icon-logout" viewBox="0 0 24 24"><path d="M10 3H4v18h6m-2-9h13m-5-5 5 5-5 5"/></symbol>
        <symbol id="icon-check" viewBox="0 0 24 24"><path d="m4 12 5 5L20 6"/></symbol>
        <symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></symbol>
        <symbol id="icon-pin" viewBox="0 0 24 24"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/></symbol>
        <symbol id="icon-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v5m10-5v5M3 11h18"/></symbol>
        <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
        <symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
        <symbol id="icon-close" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></symbol>
    </svg>

    <a class="skip-link" href="#main">Skip to main content</a>
    <div class="applications-glows" aria-hidden="true">
        <div class="ambient ambient-purple"></div>
        <div class="ambient ambient-lavender"></div>
        <div class="ambient ambient-peach"></div>
        <div class="ambient ambient-lilac"></div>
    </div>

    <div class="dashboard-layout">
        <aside class="sidebar surface" id="student-sidebar" aria-label="Company workspace">
            <div class="sidebar-header">
                <a class="brand" href="index.php"><img src="assets/logo.svg" width="40" height="38" alt=""><strong>SIMS</strong></a>
                <button class="icon-button sidebar-close" type="button" aria-label="Close menu"><svg class="icon"><use href="#icon-close"/></svg></button>
            </div>
            <nav class="sidebar-nav" aria-label="Company navigation">
                <a href="company-dashboard.php"><svg class="icon"><use href="#icon-home"/></svg>Dashboard</a>
                <?php if ($company["approval_status"] === "Approved"): ?>
                    <a href="post-internship.php"><svg class="icon"><use href="#icon-file"/></svg>Post Internship</a>
                <?php endif; ?>
                <a href="company-vacancies.php"><svg class="icon"><use href="#icon-search"/></svg>My Vacancies</a>
                <a href="company-applications.php"><svg class="icon"><use href="#icon-file"/></svg>Applications</a>
                <a href="company-profile.php" aria-current="page"><svg class="icon"><use href="#icon-user"/></svg>Company Profile</a>
            </nav>
            <div class="sidebar-bottom">
                <p>Find potential.<br>Build your team.</p>
                <form action="logout.php" method="post">
                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                    <button type="submit"><svg class="icon"><use href="#icon-logout"/></svg>Logout</button>
                </form>
            </div>
        </aside>
        <div class="sidebar-overlay" hidden></div>

        <main class="workspace company-workspace" id="main">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="company-dashboard.php">← Back to dashboard</a>
                <a class="account-chip surface" href="company-profile.php" aria-label="Company profile: <?= e($company["company_name"]) ?>">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($company["company_name"], 0, 1))) ?></span>
                    <span class="account-copy"><strong><?= e($company["company_name"]) ?></strong><small>Company</small></span>
                </a>
            </header>

            <section class="welcome">
                <p class="eyebrow">GROW YOUR TEAM</p>
                <h1>Company profile</h1>
                <p>Introduce your company and keep your registration details up to date.</p>
            </section>


            <?php if ($notice): ?><p class="profile-success" role="status"><?= e($notice) ?></p><?php endif; ?>
            <section class="panel surface profile-editor">
                <div class="panel-heading">
                    <h2><?= $editing ? 'Edit company profile' : 'Company information' ?></h2>
                    <?php if (!$editing): ?><a class="filter-submit company-post-link" href="company-profile.php?edit=1">Edit Profile</a><?php endif; ?>
                </div>
                <p class="profile-form-intro">Approval status: <strong><?= e($company["approval_status"]) ?></strong>. Changing the company name or registration number sends the company for review again.</p>
                <?php if ($errors): ?>
                    <div class="profile-error-summary" role="alert">
                        <strong>Your profile has not been saved.</strong>
                        <?php foreach ($errors as $message): ?><p><?= e($message) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($editing): ?>
                    <form method="post" action="company-profile.php">
                        <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="edit_token" value="<?= e($editToken) ?>">
                        <div class="profile-form-grid">
                            <?php foreach ($labels as $field => $label): ?>
                                <div class="profile-field <?= $field === 'description' ? 'profile-field-wide' : '' ?>">
                                    <label for="<?= e($field) ?>"><?= e($label) ?></label>
                                    <?php if ($field === 'description'): ?>
                                        <textarea id="<?= e($field) ?>" name="<?= e($field) ?>" rows="7" maxlength="3000"><?= e($values[$field]) ?></textarea>
                                    <?php else: ?>
                                        <input id="<?= e($field) ?>" name="<?= e($field) ?>" type="<?= $field === 'website' ? 'url' : 'text' ?>" maxlength="<?= $limits[$field] ?>" value="<?= e($values[$field]) ?>" <?= in_array($field, ['company_name', 'phone'], true) ? 'required' : '' ?> <?= isset($errors[$field]) ? 'aria-invalid="true"' : '' ?>>
                                    <?php endif; ?>
                                    <?php if (isset($errors[$field])): ?><p class="profile-field-error"><?= e($errors[$field]) ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="profile-form-actions">
                            <a class="profile-cancel" href="company-profile.php">Cancel</a>
                            <button class="filter-submit" type="submit">Save profile</button>
                        </div>
                    </form>
                <?php else: ?>
                    <dl class="company-details-grid">
                        <?php foreach ($labels as $field => $label): ?>
                            <div><dt><?= e($label) ?></dt><dd><?= trim((string) $company[$field]) !== '' ? nl2br(e($company[$field])) : 'Not provided' ?></dd></div>
                        <?php endforeach; ?>
                        <div><dt>Sign-in email</dt><dd><?= e($company["email"]) ?></dd></div>
                    </dl>
                    <?php if ($company["approval_status"] === 'Rejected'): ?>
                        <form class="vacancy-actions" method="post" action="company-profile.php">
                            <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                            <input type="hidden" name="action" value="review">
                            <p class="profile-field-help">Check and update your registration details before requesting another review.</p>
                            <button class="filter-submit" type="submit">Request admin review</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
