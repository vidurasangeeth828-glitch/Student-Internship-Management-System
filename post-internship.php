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

$approved = $company["approval_status"] === "Approved";
if (!$approved) {
    http_response_code(403);
}
$categories = [
    "Software Development", "Data & Analytics", "Design & Creative", "Marketing",
    "Business & Management", "Engineering", "Finance & Accounting", "Other"
];
$values = ["title" => "", "category" => "", "location" => "", "deadline" => "", "description" => ""];
$errors = [];
if (isset($_SESSION["vacancy_form"])) {
    $values = $_SESSION["vacancy_form"]["values"];
    $errors = $_SESSION["vacancy_form"]["errors"];
    unset($_SESSION["vacancy_form"]);
}
if ($approved && empty($_SESSION["posting_token"])) {
    $_SESSION["posting_token"] = bin2hex(random_bytes(32));
}
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Post Internship | SIMS</title>
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

    <a class="skip-link" href="#main">Skip to internship form</a>
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
                    <a href="post-internship.php" aria-current="page"><svg class="icon"><use href="#icon-file"/></svg>Post Internship</a>
                <?php endif; ?>
                <a href="company-dashboard.php#company-vacancies"><svg class="icon"><use href="#icon-search"/></svg>My Vacancies</a>
                <a href="company-applications.php"><svg class="icon"><use href="#icon-file"/></svg>Applications</a>
                <a href="company-dashboard.php#company-profile"><svg class="icon"><use href="#icon-user"/></svg>Company Profile</a>
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
                <a class="account-chip surface" href="company-dashboard.php#company-profile" aria-label="Company profile: <?= e($company["company_name"]) ?>">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($company["company_name"], 0, 1))) ?></span>
                    <span class="account-copy"><strong><?= e($company["company_name"]) ?></strong><small>Company</small></span>
                </a>
            </header>

            <section class="welcome">
                <p class="eyebrow">GROW YOUR TEAM</p>
                <h1>Post an internship</h1>
                <p>Connect with students who are ready to learn and contribute.</p>
            </section>

            <?php if ($approved): ?>
                <div class="post-layout">
                    <section class="panel surface profile-editor" aria-labelledby="vacancy-form-title">
                        <h2 id="vacancy-form-title">Internship details</h2>
                        <p class="profile-form-intro">All fields are required. Publishing makes this opportunity visible to students.</p>
                        <?php if ($errors): ?>
                            <div class="profile-error-summary post-error-summary" role="alert">
                                <strong>Your internship has not been posted.</strong>
                                <p>Check the highlighted fields below.</p>
                                <?php if (isset($errors["save"])): ?><p><?= e($errors["save"]) ?></p><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <form action="post-internship-process.php" method="post" class="post-internship-form">
                            <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                            <input type="hidden" name="posting_token" value="<?= e($_SESSION["posting_token"]) ?>">
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
                                    <input id="vacancy-deadline" type="date" name="deadline" min="<?= e($today) ?>" max="9999-12-31" value="<?= e($values["deadline"]) ?>" required <?= isset($errors["deadline"]) ? 'aria-invalid="true" aria-describedby="deadline-error"' : '' ?>>
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
                                <a class="profile-cancel" href="company-dashboard.php#company-vacancies">Cancel</a>
                                <button class="filter-submit" type="submit">Publish internship <span aria-hidden="true">→</span></button>
                            </div>
                        </form>
                    </section>

                    <aside class="panel surface post-guidance" aria-labelledby="posting-guide-title">
                        <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                        <h2 id="posting-guide-title">Help students find their fit</h2>
                        <p>A clear description helps the right students understand your opportunity.</p>
                        <ul>
                            <li>Use a specific role title.</li>
                            <li>Explain what the intern will learn and do.</li>
                            <li>List the skills and experience you need.</li>
                            <li>Add the duration and work arrangement.</li>
                        </ul>
                        <div class="post-company-note"><strong><?= e($company["company_name"]) ?></strong><span>Your company is approved to publish.</span></div>
                    </aside>
                </div>
            <?php else: ?>
                <section class="panel surface empty-state post-approval-empty">
                    <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-clock"/></svg></span>
                    <h2>Company approval is required</h2>
                    <p>Your company status is <?= e($company["approval_status"]) ?>. Only approved companies can publish internships.</p>
                    <a class="filter-submit company-post-link" href="company-dashboard.php">Back to dashboard</a>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
