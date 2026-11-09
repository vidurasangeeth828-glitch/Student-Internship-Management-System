<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, c.company_id, c.company_name,
                        c.phone, c.business_registration_no, c.approval_status
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
$approved = $company["approval_status"] === "Approved";
$today = date("Y-m-d");
$categories = [
    "Software Development", "Data & Analytics", "Design & Creative", "Marketing",
    "Business & Management", "Engineering", "Finance & Accounting", "Other"
];
$errors = [];
$values = [];
$editId = 0;
$editToken = "";
$notice = $_SESSION["vacancy_notice"] ?? "";
unset($_SESSION["vacancy_notice"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_form();
    $vacancyId = filter_var(post_text("vacancy_id"), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $action = post_text("action");
    if ($vacancyId === false || !in_array($action, ["save", "close", "reopen"], true)) {
        http_response_code(400);
        exit("Please choose a valid vacancy action.");
    }

    $conn->begin_transaction();
    try {
        // Recheck account approval while locking the vacancy for this update.
        $stmt = $conn->prepare("SELECT c.approval_status FROM companies c
                                JOIN users u ON u.user_id = c.user_id
                                WHERE c.company_id = ? AND u.user_id = ?
                                AND u.role = 'company' AND u.status = 'Active' FOR UPDATE");
        $stmt->bind_param("ii", $companyId, $_SESSION["user_id"]);
        $stmt->execute();
        $currentCompany = $stmt->get_result()->fetch_assoc();
        if (!$currentCompany) {
            $conn->rollback();
            http_response_code(403);
            exit("Your company account is no longer available.");
        }
        $approved = $currentCompany["approval_status"] === "Approved";
        $stmt = $conn->prepare("SELECT internship_id, title, category, location, deadline, description, status
                                FROM internships WHERE internship_id = ? AND company_id = ? FOR UPDATE");
        $stmt->bind_param("ii", $vacancyId, $companyId);
        $stmt->execute();
        $vacancy = $stmt->get_result()->fetch_assoc();
        if (!$vacancy) {
            $conn->rollback();
            http_response_code(404);
            exit("Vacancy not found.");
        }

        if ($action === "save") {
            $editId = $vacancyId;
            $editToken = post_text("edit_token");
            $snapshot = $_SESSION["vacancy_edits"][$editToken] ?? null;
            if (!$snapshot || $snapshot !== $vacancy) {
                $conn->rollback();
                $_SESSION["vacancy_notice"] = "This vacancy or form has changed. Open Edit again before saving.";
                redirect("company-vacancies.php");
            }
            $values = [
                "title" => post_text("title"),
                "category" => post_text("category"),
                "location" => post_text("location"),
                "deadline" => post_text("deadline"),
                "description" => post_text("description")
            ];
            if (!$approved || $vacancy["status"] === "Archived") {
                $errors["save"] = "Only approved companies can edit vacancies. Archived vacancies cannot be edited.";
            }
            if (mb_strlen($values["title"]) < 5 || mb_strlen($values["title"]) > 120) {
                $errors["title"] = "Enter a title of 5 to 120 characters.";
            }
            if (!in_array($values["category"], $categories, true)) {
                $errors["category"] = "Choose a category from the list.";
            }
            if (mb_strlen($values["location"]) < 2 || mb_strlen($values["location"]) > 80) {
                $errors["location"] = "Enter a location of 2 to 80 characters.";
            }
            if (mb_strlen($values["description"]) < 20 || mb_strlen($values["description"]) > 10000) {
                $errors["description"] = "Enter a description of 20 to 10,000 characters.";
            }
            $validDate = false;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $values["deadline"])) {
                $parts = explode("-", $values["deadline"]);
                $validDate = checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
            }
            if (!$validDate || ($vacancy["status"] === "Open" && $values["deadline"] < $today)) {
                $errors["deadline"] = "Choose a valid date. Open vacancies need today or a future deadline.";
            }
            if (!$errors) {
                $stmt = $conn->prepare("UPDATE internships SET title = ?, category = ?, location = ?, deadline = ?, description = ?
                                        WHERE internship_id = ? AND company_id = ?");
                $stmt->bind_param("sssssii", $values["title"], $values["category"], $values["location"], $values["deadline"], $values["description"], $vacancyId, $companyId);
                $stmt->execute();
                unset($_SESSION["vacancy_edits"][$editToken]);
                $notice = "Vacancy details saved. Existing applications have been kept.";
            }
        } else {
            $expectedStatus = post_text("expected_status");
            if ($vacancy["status"] !== $expectedStatus) {
                $notice = "This vacancy's status has changed. Review the latest details before trying again.";
            } elseif ($action === "close" && $vacancy["status"] === "Open") {
                $stmt = $conn->prepare("UPDATE internships SET status = 'Closed' WHERE internship_id = ? AND company_id = ?");
                $stmt->bind_param("ii", $vacancyId, $companyId);
                $stmt->execute();
                $notice = "Vacancy closed. Existing applications are still available to review.";
            } elseif ($action === "reopen" && $vacancy["status"] === "Closed" && $approved && $vacancy["deadline"] >= $today) {
                $stmt = $conn->prepare("UPDATE internships SET status = 'Open' WHERE internship_id = ? AND company_id = ?");
                $stmt->bind_param("ii", $vacancyId, $companyId);
                $stmt->execute();
                $notice = "Vacancy reopened and available for applications.";
            } else {
                $notice = "This action is unavailable. Reopening requires approval, a closed vacancy and a current deadline.";
            }
        }
        if ($errors) {
            $conn->rollback();
        } else {
            $conn->commit();
        }
    } catch (mysqli_sql_exception $error) {
        $conn->rollback();
        error_log("Vacancy update failed: " . $error->getMessage());
        $errors["save"] = "The vacancy could not be saved. Please try again.";
    }
    if (!$errors) {
        $_SESSION["vacancy_notice"] = $notice;
        redirect("company-vacancies.php");
    }
} elseif ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
    header("Allow: GET, HEAD, POST");
    http_response_code(405);
    exit("Please use the vacancy form.");
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["edit"])) {
    $editId = is_string($_GET["edit"]) ? filter_var($_GET["edit"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) : false;
    if ($editId === false) {
        http_response_code(400);
        exit("Invalid vacancy.");
    }
    $stmt = $conn->prepare("SELECT internship_id, title, category, location, deadline, description, status
                            FROM internships WHERE internship_id = ? AND company_id = ?");
    $stmt->bind_param("ii", $editId, $companyId);
    $stmt->execute();
    $values = $stmt->get_result()->fetch_assoc();
    if (!$values) {
        http_response_code(404);
        exit("Vacancy not found.");
    }
    if (!$approved || $values["status"] === "Archived") {
        http_response_code(403);
        exit("This vacancy cannot be edited.");
    }
    // Keep a small set of form snapshots to prevent older tabs overwriting newer edits.
    if (!isset($_SESSION["vacancy_edits"])) {
        $_SESSION["vacancy_edits"] = [];
    }
    if (count($_SESSION["vacancy_edits"]) >= 10) {
        array_shift($_SESSION["vacancy_edits"]);
    }
    $editToken = bin2hex(random_bytes(24));
    $_SESSION["vacancy_edits"][$editToken] = $values;
}

$status = "";
if (isset($_GET["status"]) && is_string($_GET["status"]) && in_array($_GET["status"], ["Open", "Closed", "Archived", "Expired"], true)) {
    $status = $_GET["status"];
}
$stmt = $conn->prepare("SELECT i.*, (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.internship_id) AS application_count
                        FROM internships i WHERE i.company_id = ?
                        AND (? = '' OR (i.status = ? AND (? <> 'Open' OR i.deadline >= ?))
                        OR (? = 'Expired' AND i.status = 'Open' AND i.deadline < ?))
                        ORDER BY i.created_at DESC, i.internship_id DESC");
$stmt->bind_param("issssss", $companyId, $status, $status, $status, $today, $status, $today);
$stmt->execute();
$vacancies = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Vacancies | SIMS</title>
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

    <a class="skip-link" href="#main">Skip to vacancies</a>
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
                <a href="company-vacancies.php" aria-current="page"><svg class="icon"><use href="#icon-search"/></svg>My Vacancies</a>
                <a href="company-applications.php"><svg class="icon"><use href="#icon-file"/></svg>Applications</a>
                <a href="company-profile.php"><svg class="icon"><use href="#icon-user"/></svg>Company Profile</a>
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
                <p class="eyebrow">MANAGE YOUR OPPORTUNITIES</p>
                <h1>My vacancies</h1>
                <p>Update your internships, manage availability and review applications.</p>
            </section>


            <?php if ($notice): ?><p class="profile-success" role="status"><?= e($notice) ?></p><?php endif; ?>
            <?php if (!$approved): ?>
                <p class="profile-error-summary">Your company status is <?= e($company["approval_status"]) ?>. You can view or close vacancies; editing and reopening require approval.</p>
            <?php endif; ?>
            <?php if ($editId): ?>
                    <section class="panel surface profile-editor" aria-labelledby="vacancy-form-title">
                        <h2 id="vacancy-form-title">Edit vacancy</h2>
                        <p class="profile-form-intro">Changes update the listing immediately. Existing applications are kept.</p>
                        <?php if ($errors): ?>
                            <div class="profile-error-summary post-error-summary" role="alert">
                                <strong>Your changes have not been saved.</strong>
                                <p>Check the highlighted fields below.</p>
                                <?php if (isset($errors["save"])): ?><p><?= e($errors["save"]) ?></p><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <form action="company-vacancies.php" method="post" class="post-internship-form">
                            <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                            <input type="hidden" name="vacancy_id" value="<?= e($editId) ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="edit_token" value="<?= e($editToken) ?>">
                            <div class="profile-form-grid">
                                <div class="profile-field profile-field-wide">
                                    <label for="vacancy-title">Internship title</label>
                                    <input id="vacancy-title" name="title" minlength="5" maxlength="120" placeholder="e.g. Software Engineering Intern" value="<?= e($values["title"]) ?>" required <?= isset($errors["title"]) ? 'aria-invalid="true" aria-describedby="title-error"' : '' ?>>
                                    <?php if (isset($errors["title"])): ?><p class="profile-field-error" id="title-error"><?= e($errors["title"]) ?></p><?php endif; ?>
                                </div>
                                <div class="profile-field">
                                    <label for="vacancy-category">Category</label>
                                    <select id="vacancy-category" name="category" required <?= isset($errors["category"]) ? 'aria-invalid="true" aria-describedby="category-error"' : '' ?>>
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $item): ?>
                                            <option value="<?= e($item) ?>" <?= $values["category"] === $item ? "selected" : "" ?>><?= e($item) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors["category"])): ?><p class="profile-field-error" id="category-error"><?= e($errors["category"]) ?></p><?php endif; ?>
                                </div>
                                <div class="profile-field">
                                    <label for="vacancy-location">Location</label>
                                    <input id="vacancy-location" name="location" minlength="2" maxlength="80" placeholder="e.g. Colombo or Remote" value="<?= e($values["location"]) ?>" required <?= isset($errors["location"]) ? 'aria-invalid="true" aria-describedby="location-error"' : '' ?>>
                                    <?php if (isset($errors["location"])): ?><p class="profile-field-error" id="location-error"><?= e($errors["location"]) ?></p><?php endif; ?>
                                </div>
                                <div class="profile-field profile-field-wide">
                                    <label for="vacancy-deadline">Application deadline</label>
                                    <input id="vacancy-deadline" type="date" name="deadline" max="9999-12-31" value="<?= e($values["deadline"]) ?>" required <?= isset($errors["deadline"]) ? 'aria-invalid="true" aria-describedby="deadline-error"' : '' ?>>
                                    <?php if (isset($errors["deadline"])): ?><p class="profile-field-error" id="deadline-error"><?= e($errors["deadline"]) ?></p><?php endif; ?>
                                </div>
                                <div class="profile-field profile-field-wide">
                                    <label for="vacancy-description">Internship description</label>
                                    <textarea id="vacancy-description" name="description" rows="10" minlength="20" maxlength="10000" required aria-describedby="description-help<?= isset($errors["description"]) ? ' description-error' : '' ?>" <?= isset($errors["description"]) ? 'aria-invalid="true"' : '' ?> placeholder="About the internship&#10;&#10;Responsibilities&#10;&#10;Requirements&#10;&#10;Duration and work arrangement"><?= e($values["description"]) ?></textarea>
                                    <p class="profile-field-help" id="description-help">Include responsibilities, required skills, duration and work arrangements. Use plain text and line breaks. Maximum 10,000 characters.</p>
                                    <?php if (isset($errors["description"])): ?><p class="profile-field-error" id="description-error"><?= e($errors["description"]) ?></p><?php endif; ?>
                                </div>
                            </div>
                            <div class="profile-form-actions">
                                <a class="profile-cancel" href="company-vacancies.php">Cancel</a>
                                <button class="filter-submit" type="submit">Save changes <span aria-hidden="true">→</span></button>
                            </div>
                        </form>
                    </section>


            <?php else: ?>
                <?php if ($errors): ?><p class="profile-error-summary" role="alert"><?= e($errors["save"] ?? "Please try again.") ?></p><?php endif; ?>
                <form class="applications-filter surface" method="get" action="company-vacancies.php">
                    <div>
                        <label for="vacancy-status">Vacancy status</label>
                        <select name="status" id="vacancy-status">
                            <option value="">All vacancies</option>
                            <?php foreach (["Open", "Closed", "Expired", "Archived"] as $item): ?>
                                <option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="filter-submit" type="submit">Filter vacancies</button>
                    <?php if ($status !== ''): ?><a class="clear-filters" href="company-vacancies.php">Clear</a><?php endif; ?>
                    <p class="applications-count"><?= $vacancies->num_rows ?> vacancies</p>
                    <?php if ($approved): ?><a class="filter-submit company-post-link" href="post-internship.php">Post Internship +</a><?php endif; ?>
                </form>
                <section class="applications-list" aria-label="Your vacancies">
                    <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
                        <?php
                        $expired = $vacancy["deadline"] < $today;
                        $label = $vacancy["status"];
                        if ($label === "Open" && $expired) {
                            $label = "Expired";
                        } elseif ($label === "Open" && !$approved) {
                            $label = "Awaiting approval";
                        }
                        ?>
                        <article class="panel surface application-card managed-vacancy" id="vacancy-<?= e($vacancy["internship_id"]) ?>">
                            <div class="application-card-header">
                                <div class="application-role">
                                    <span class="company-avatar" aria-hidden="true"><svg class="icon"><use href="#icon-file"/></svg></span>
                                    <div><h2><?= e($vacancy["title"]) ?></h2><p><?= e($vacancy["category"]) ?></p></div>
                                </div>
                                <span class="company-vacancy-status <?= $label === 'Open' ? 'company-vacancy-open' : 'company-vacancy-muted' ?>"><?= e($label) ?></span>
                            </div>
                            <div class="application-metadata">
                                <p><svg class="icon small-icon" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($vacancy["location"]) ?></p>
                                <p><svg class="icon small-icon" aria-hidden="true"><use href="#icon-calendar"/></svg>Deadline <?= e(date("d M Y", strtotime($vacancy["deadline"]))) ?></p>
                                <p><?= e($vacancy["application_count"]) ?> applications</p>
                            </div>
                            <details class="application-letter"><summary>View description</summary><p><?= nl2br(e($vacancy["description"])) ?></p></details>
                            <div class="vacancy-actions">
                                <?php if ($approved && $vacancy["status"] !== 'Archived'): ?>
                                    <a class="profile-cancel" href="company-vacancies.php?edit=<?= e($vacancy["internship_id"]) ?>">Edit vacancy</a>
                                <?php endif; ?>
                                <a class="profile-cancel" href="company-applications.php?internship=<?= e($vacancy["internship_id"]) ?>">View applications</a>
                                <?php if ($vacancy["status"] === 'Open' || ($vacancy["status"] === 'Closed' && $approved && !$expired)): ?>
                                    <form method="post" action="company-vacancies.php">
                                        <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                        <input type="hidden" name="vacancy_id" value="<?= e($vacancy["internship_id"]) ?>">
                                        <input type="hidden" name="expected_status" value="<?= e($vacancy["status"]) ?>">
                                        <input type="hidden" name="action" value="<?= $vacancy["status"] === 'Open' ? 'close' : 'reopen' ?>">
                                        <button class="filter-submit <?= $vacancy["status"] === 'Open' ? 'vacancy-close' : '' ?>" type="submit"><?= $vacancy["status"] === 'Open' ? 'Close vacancy' : 'Reopen vacancy' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($label === 'Open'): ?><a class="company-listing-link" href="internship-details.php?id=<?= e($vacancy["internship_id"]) ?>">View public listing ↗</a><?php endif; ?>
                            </div>
                            <?php if ($vacancy["status"] === 'Closed' && $expired && $approved): ?><p class="profile-field-help">Edit the deadline before reopening this vacancy.</p><?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                    <?php if ($vacancies->num_rows === 0): ?>
                        <div class="panel surface empty-state"><span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-search"/></svg></span><h2>No vacancies found</h2><p>Your vacancies will appear here. Try another filter or post your first internship.</p></div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
