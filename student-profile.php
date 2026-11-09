<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, s.*
                        FROM users u
                        LEFT JOIN students s ON s.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    unset($_SESSION["user_id"], $_SESSION["role"]);
    redirect("signin.php");
}

if ($user["role"] !== "student" || !$user["student_id"]) {
    http_response_code(403);
    exit("This page is available to active student accounts only.");
}

$educationStatuses = ["Undergraduate", "Graduate", "Postgraduate", "Other"];
$fields = ["full_name", "phone", "field_of_interest", "about_me", "education_status", "skills", "linkedin_url", "github_url"];
$values = [];
foreach ($fields as $field) {
    $values[$field] = (string) $user[$field];
}

$errors = [];
if (isset($_SESSION["profile_form"])) {
    $values = $_SESSION["profile_form"]["values"];
    $errors = $_SESSION["profile_form"]["errors"];
    unset($_SESSION["profile_form"]);
}
$saved = !empty($_SESSION["profile_saved"]);
unset($_SESSION["profile_saved"]);

$socialLinks = [];
foreach (["linkedin_url" => "linkedin.com", "github_url" => "github.com"] as $field => $host) {
    $url = (string) $user[$field];
    if (filter_var($url, FILTER_VALIDATE_URL) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === "https"
        && in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), [$host, "www." . $host], true)) {
        $socialLinks[$field] = $url;
    }
}
$editing = isset($_GET["edit"]) && $_GET["edit"] === "1";
if ($errors) {
    $editing = true;
}

$skillList = [];
foreach (explode(",", $user["skills"]) as $skill) {
    $skill = trim($skill);
    if ($skill !== "") {
        $skillList[] = $skill;
    }
}
$photoError = $_SESSION["photo_error"] ?? "";
$photoSaved = $_SESSION["photo_saved"] ?? "";
unset($_SESSION["photo_error"], $_SESSION["photo_saved"]);
$showPhotoForm = (isset($_GET["photo"]) && $_GET["photo"] === "1") || $photoError !== "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Profile | SIMS</title>
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
        <symbol id="icon-pin" viewBox="0 0 24 24"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/></symbol>
        <symbol id="icon-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v5m10-5v5M3 11h18"/></symbol>
        <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
        <symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
        <symbol id="icon-close" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></symbol>
        <symbol id="icon-edit" viewBox="0 0 24 24"><path d="m15 5 4 4M4 20l1-5L17 3l4 4L9 19z"/></symbol>
        <symbol id="icon-code" viewBox="0 0 24 24"><path d="m8 6-6 6 6 6m8-12 6 6-6 6m-3-15-2 18"/></symbol>
        <symbol id="icon-link" viewBox="0 0 24 24"><path d="m10 14 4-4m-6 2-3 3a4 4 0 0 0 6 6l3-3m-4-12 3-3a4 4 0 0 1 6 6l-3 3"/></symbol>
        <symbol id="icon-id" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M4 17a4 4 0 0 1 8 0m3-8h4m-4 4h4m-4 4h3"/></symbol>
    </svg>
    <a class="skip-link" href="#main">Skip to profile</a>
    <div class="applications-glows" aria-hidden="true">
        <div class="ambient ambient-purple" aria-hidden="true"></div>
        <div class="ambient ambient-lavender" aria-hidden="true"></div>
        <div class="ambient ambient-peach" aria-hidden="true"></div>
        <div class="ambient ambient-lilac" aria-hidden="true"></div>

    </div>

    <div class="dashboard-layout">
        <aside class="sidebar surface" id="student-sidebar" aria-label="SIMS navigation">
            <div class="sidebar-header">
                <a class="brand" href="index.php"><img src="assets/logo.svg" width="44" height="42" alt=""><strong>SIMS</strong></a>
                <button class="icon-button sidebar-close" type="button" aria-label="Close menu"><svg class="icon"><use href="#icon-close"/></svg></button>
            </div>

            <nav class="sidebar-nav" aria-label="Main navigation">
                <?php if ($user && $user["role"] === "student"): ?>
                    <a href="student-dashboard.php"><svg class="icon"><use href="#icon-home"/></svg>Dashboard</a>
                <?php else: ?>
                    <a href="index.php"><svg class="icon"><use href="#icon-home"/></svg>Home</a>
                <?php endif; ?>
                <a href="internships.php"><svg class="icon"><use href="#icon-search"/></svg>Browse Internships</a>
                <?php if ($user && $user["role"] === "student"): ?>
                    <a href="my-applications.php"><svg class="icon"><use href="#icon-file"/></svg>My Applications</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a href="student-profile.php" aria-current="page"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
                <?php else: ?>
                    <a href="signin.php"><svg class="icon"><use href="#icon-user"/></svg>Sign In / Sign Up</a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-bottom">
                <p>A new opportunity.<br>A new beginning.</p>
                <?php if ($user): ?>
                    <form action="logout.php" method="post">
                        <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                        <button type="submit"><svg class="icon"><use href="#icon-logout"/></svg>Logout</button>
                    </form>
                <?php else: ?>
                    <a class="join-link" href="signup.php">Create your account <span aria-hidden="true">→</span></a>
                <?php endif; ?>
            </div>
        </aside>
        <div class="sidebar-overlay" hidden></div>

        <main id="main" class="workspace applications-workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="student-dashboard.php">← Back to dashboard</a>
                <?php if ($user): ?>
                    <a class="account-chip surface" href="student-profile.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <section class="welcome">
                <p class="eyebrow">INTRODUCE YOURSELF</p>
                <h1>My profile</h1>
                <p>Your interests, skills and personal details, all in one place.</p>
            </section>

            <?php if ($saved): ?>
                <p class="profile-success" role="status">Your profile has been updated.</p>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="profile-error-summary" role="alert">
                    <strong>Your profile has not been saved.</strong>
                    <p>Check the highlighted fields below. Your details are still in the form.</p>
                    <?php if (isset($errors["save"])): ?><p><?= e($errors["save"]) ?></p><?php endif; ?>
                </div>
            <?php endif; ?>

            <section class="panel surface profile-header" aria-label="Profile summary">
                <div class="profile-portrait"><span class="avatar profile-avatar" aria-hidden="true"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
                    <a class="profile-photo-change" href="student-profile.php?photo=1#photo-upload">Change photo</a>
                </div>
                <div class="profile-header-copy">
                    <h2><?= e($user["full_name"]) ?></h2>
                    <p><?= $user["field_of_interest"] !== "" ? e($user["field_of_interest"]) : "Student" ?></p>
                    <?php if ($user["education_status"] !== ""): ?>
                        <span class="profile-education"><?= e($user["education_status"]) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!$editing): ?>
                    <a class="filter-submit profile-edit-button" href="student-profile.php?edit=1"><svg class="icon small-icon" aria-hidden="true"><use href="#icon-edit"/></svg>Edit Profile</a>
                <?php else: ?>
                    <a class="profile-cancel profile-edit-button" href="student-profile.php">← Back to profile</a>
                <?php endif; ?>
            </section>

            <?php if ($photoSaved !== ""): ?>
                <p class="profile-success" role="status"><?= e($photoSaved) ?></p>
            <?php endif; ?>
            <?php if ($showPhotoForm): ?>
                <section class="panel surface profile-photo-editor" id="photo-upload" aria-labelledby="photo-title">
                    <h2 id="photo-title">Profile photo</h2>
                    <p class="profile-field-help" id="photo-help">Choose a JPG or PNG up to 2 MB. Maximum 4,096 pixels per side and 16 megapixels. It will be displayed in a circle.</p>
                    <?php if ($photoError !== ""): ?><p class="profile-field-error" role="alert"><?= e($photoError) ?></p><?php endif; ?>
                    <form action="profile-photo-process.php" method="post" enctype="multipart/form-data" class="profile-photo-form">
                        <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                        <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
                        <div class="profile-field">
                            <label for="photo">Choose profile photo</label>
                            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,.jpg,.jpeg,.png" aria-describedby="photo-help">
                        </div>
                        <div class="profile-photo-actions">
                            <button class="filter-submit" type="submit" name="action" value="upload">Save photo</button>
                            <?php if (!empty($user["photo_path"])): ?>
                                <button class="profile-photo-remove" type="submit" name="action" value="remove">Remove photo</button>
                            <?php endif; ?>
                            <a class="profile-cancel" href="student-profile.php">Cancel</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($editing): ?>
                <section class="panel surface profile-editor" aria-labelledby="edit-profile-title">
                    <h2 id="edit-profile-title">Edit profile</h2>
                    <p class="profile-form-intro">Only your full name is required. Add the other details when you are ready.</p>
                    <form class="profile-form" action="student-profile-process.php" method="post">
                        <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                        <div class="profile-form-grid">
                            <div class="profile-field">
                                <label for="full-name">Full name <span>Required</span></label>
                                <input id="full-name" name="full_name" value="<?= e($values["full_name"]) ?>" maxlength="120" autocomplete="name" required <?= isset($errors["full_name"]) ? 'aria-invalid="true" aria-describedby="full_name-error"' : '' ?>>
                                <?php if (isset($errors["full_name"])): ?><p class="profile-field-error" id="full_name-error"><?= e($errors["full_name"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="profile-email">Email address</label>
                                <input id="profile-email" type="email" value="<?= e($user["email"]) ?>" readonly aria-describedby="email-help" autocomplete="email">
                                <p class="profile-field-help" id="email-help">Your sign-in email. It cannot be changed from this form.</p>
                            </div>
                            <div class="profile-field">
                                <label for="phone">Contact number</label>
                                <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" placeholder="e.g. +94 77 123 4567" value="<?= e($values["phone"]) ?>" <?= isset($errors["phone"]) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                                <?php if (isset($errors["phone"])): ?><p class="profile-field-error" id="phone-error"><?= e($errors["phone"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="education-status">Education status</label>
                                <select id="education-status" name="education_status" <?= isset($errors["education_status"]) ? 'aria-invalid="true" aria-describedby="education_status-error"' : '' ?>>
                                    <option value="">Select your status</option>
                                    <?php foreach ($educationStatuses as $item): ?>
                                        <option value="<?= e($item) ?>" <?= $values["education_status"] === $item ? "selected" : "" ?>><?= e($item) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors["education_status"])): ?><p class="profile-field-error" id="education_status-error"><?= e($errors["education_status"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field profile-field-wide">
                                <label for="field">Field of interest</label>
                                <input id="field" name="field_of_interest" maxlength="100" list="profile-fields" placeholder="e.g. Software Development" value="<?= e($values["field_of_interest"]) ?>" <?= isset($errors["field_of_interest"]) ? 'aria-invalid="true" aria-describedby="field_of_interest-error"' : '' ?>>
                                <datalist id="profile-fields">
                                    <option value="Software Development"><option value="UI/UX Design"><option value="Data Analytics"><option value="Marketing"><option value="Business Management"><option value="Engineering"><option value="Finance & Accounting">
                                </datalist>
                                <?php if (isset($errors["field_of_interest"])): ?><p class="profile-field-error" id="field_of_interest-error"><?= e($errors["field_of_interest"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field profile-field-wide">
                                <label for="about-me">About me</label>
                                <textarea id="about-me" name="about_me" rows="5" maxlength="1000" placeholder="Tell us about your interests, experience and career goals." aria-describedby="about-help<?= isset($errors["about_me"]) ? ' about_me-error' : '' ?>" <?= isset($errors["about_me"]) ? 'aria-invalid="true"' : '' ?>><?= e($values["about_me"]) ?></textarea>
                                <p class="profile-field-help" id="about-help">A short introduction. Maximum 1,000 characters.</p>
                                <?php if (isset($errors["about_me"])): ?><p class="profile-field-error" id="about_me-error"><?= e($errors["about_me"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field profile-field-wide">
                                <label for="skills">Skills</label>
                                <input id="skills" name="skills" maxlength="500" placeholder="e.g. HTML, CSS, PHP, SQL" value="<?= e($values["skills"]) ?>" aria-describedby="skills-help<?= isset($errors["skills"]) ? ' skills-error' : '' ?>" <?= isset($errors["skills"]) ? 'aria-invalid="true"' : '' ?>>
                                <p class="profile-field-help" id="skills-help">Separate your skills with commas. Maximum 500 characters.</p>
                                <?php if (isset($errors["skills"])): ?><p class="profile-field-error" id="skills-error"><?= e($errors["skills"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="linkedin">LinkedIn profile</label>
                                <input id="linkedin" name="linkedin_url" type="url" maxlength="255" placeholder="https://www.linkedin.com/in/your-name" value="<?= e($values["linkedin_url"]) ?>" <?= isset($errors["linkedin_url"]) ? 'aria-invalid="true" aria-describedby="linkedin_url-error"' : '' ?>>
                                <?php if (isset($errors["linkedin_url"])): ?><p class="profile-field-error" id="linkedin_url-error"><?= e($errors["linkedin_url"]) ?></p><?php endif; ?>
                            </div>
                            <div class="profile-field">
                                <label for="github">GitHub profile</label>
                                <input id="github" name="github_url" type="url" maxlength="255" placeholder="https://github.com/your-name" value="<?= e($values["github_url"]) ?>" <?= isset($errors["github_url"]) ? 'aria-invalid="true" aria-describedby="github_url-error"' : '' ?>>
                                <?php if (isset($errors["github_url"])): ?><p class="profile-field-error" id="github_url-error"><?= e($errors["github_url"]) ?></p><?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-form-actions">
                            <a class="profile-cancel" href="student-profile.php">Cancel</a>
                            <button class="filter-submit" type="submit">Save changes <span aria-hidden="true">→</span></button>
                        </div>
                    </form>
                </section>
            <?php else: ?>
                <div class="profile-content-grid">
                    <div class="profile-content-stack">
                        <section class="panel surface profile-info-card" aria-labelledby="about-title">
                            <h2 id="about-title"><svg class="icon" aria-hidden="true"><use href="#icon-user"/></svg>About me</h2>
                            <?php if (trim((string) $user["about_me"]) !== ""): ?>
                                <p class="profile-bio"><?= e($user["about_me"]) ?></p>
                            <?php else: ?>
                                <p class="profile-empty-copy">Add a short introduction about your interests and career goals using Edit Profile.</p>
                            <?php endif; ?>
                        </section>

                        <section class="panel surface profile-info-card" aria-labelledby="skills-title">
                            <h2 id="skills-title"><svg class="icon" aria-hidden="true"><use href="#icon-code"/></svg>Skills</h2>
                            <?php if ($skillList): ?>
                                <ul class="profile-skill-list" aria-label="Your skills">
                                    <?php foreach ($skillList as $skill): ?>
                                        <li><?= e($skill) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="profile-empty-copy">Your skills will appear here once you add them to your profile.</p>
                            <?php endif; ?>
                        </section>
                    </div>

                    <section class="panel surface profile-info-card" aria-labelledby="personal-title">
                        <h2 id="personal-title"><svg class="icon" aria-hidden="true"><use href="#icon-id"/></svg>Personal details</h2>
                        <dl class="profile-details-list">
                            <div><dt>Full name</dt><dd><?= e($user["full_name"]) ?></dd></div>
                            <div><dt>Email</dt><dd><?= e($user["email"]) ?></dd></div>
                            <div><dt>Contact number</dt><dd><?= $user["phone"] !== "" ? e($user["phone"]) : "Not added" ?></dd></div>
                            <div><dt>Field of interest</dt><dd><?= $user["field_of_interest"] !== "" ? e($user["field_of_interest"]) : "Not added" ?></dd></div>
                            <div><dt>Education status</dt><dd><?= $user["education_status"] !== "" ? e($user["education_status"]) : "Not added" ?></dd></div>
                        </dl>
                    </section>
                </div>

                <section class="panel surface profile-info-card" aria-labelledby="links-title">
                    <h2 id="links-title"><svg class="icon" aria-hidden="true"><use href="#icon-link"/></svg>Professional links</h2>
                    <?php if ($socialLinks): ?>
                        <div class="profile-professional-links">
                            <?php foreach ($socialLinks as $field => $url): ?>
                                <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">
                                    <span class="profile-social-label"><?= $field === "linkedin_url" ? "LinkedIn" : "GitHub" ?></span>
                                    <span><?= e(preg_replace('~^https://~i', '', $url)) ?></span>
                                    <span aria-hidden="true">↗</span><span class="sr-only"> (opens in a new tab)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="profile-empty-copy">Add your LinkedIn or GitHub profile to share more of your work.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
