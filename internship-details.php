<?php
require "session.php";
require "db.php";
date_default_timezone_set("Asia/Colombo");

$user = null;
if (isset($_SESSION["user_id"])) {
    $stmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        unset($_SESSION["user_id"], $_SESSION["role"]);
    }
}

$internship = null;
$internshipId = false;
if (isset($_GET["id"]) && is_string($_GET["id"])) {
    $internshipId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
}

if ($internshipId !== false) {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT i.*, c.company_name, c.description AS company_description, c.website
                            FROM internships i
                            JOIN companies c ON c.company_id = i.company_id
                            JOIN users u ON u.user_id = c.user_id
                            WHERE i.internship_id = ? AND i.status = 'Open' AND i.deadline >= ?
                            AND c.approval_status = 'Approved' AND u.status = 'Active'");
    $stmt->bind_param("is", $internshipId, $today);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();
}

if (!$internship) {
    http_response_code(404);
}

$application = null;
if ($internship && $user && $user["role"] === "student") {
    $stmt = $conn->prepare("SELECT a.status, a.applied_at
                            FROM applications a
                            JOIN students s ON s.student_id = a.student_id
                            WHERE s.user_id = ? AND a.internship_id = ?");
    $stmt->bind_param("ii", $_SESSION["user_id"], $internshipId);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
}

$companyWebsite = "";
if ($internship && filter_var($internship["website"], FILTER_VALIDATE_URL)) {
    $scheme = strtolower((string) parse_url($internship["website"], PHP_URL_SCHEME));
    if ($scheme === "https" || $scheme === "http") {
        $companyWebsite = $internship["website"];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $internship ? e($internship["title"]) : "Internship unavailable" ?> | SIMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="internship-details.css">
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
    </svg>
    <a class="skip-link" href="#main">Skip to internship details</a>
    <div class="detail-glows" aria-hidden="true">
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
                    <a href="<?= $user["role"] === "student" ? "student-profile.php" : "account.php" ?>"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
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

        <main id="main" class="workspace details-workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="internships.php">← Back to internships</a>
                <?php if ($user): ?>
                    <a class="account-chip surface" href="<?= $user["role"] === "student" ? "student-profile.php" : "account.php" ?>" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <?php if ($internship): ?>
                <section class="detail-hero surface" aria-labelledby="internship-title">
                    <div class="detail-company-line">
                        <span class="company-avatar detail-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($internship["company_name"], 0, 2))) ?></span>
                        <div>
                            <p class="eyebrow">YOUR NEXT CHAPTER</p>
                            <p class="detail-company-name"><?= e($internship["company_name"]) ?></p>
                        </div>
                        <span class="open-label">Open</span>
                    </div>
                    <h1 id="internship-title"><?= e($internship["title"]) ?></h1>
                    <div class="detail-tags">
                        <span class="category-label"><?= e($internship["category"]) ?></span>
                        <span><svg class="icon small-icon" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($internship["location"]) ?></span>
                    </div>
                </section>

                <div class="detail-columns">
                    <div class="detail-main">
                        <section class="detail-section surface" aria-labelledby="about-role">
                            <h2 id="about-role">About this internship</h2>
                            <p class="detail-description"><?= e($internship["description"]) ?></p>
                        </section>
                        <section class="detail-section surface" aria-labelledby="about-company">
                            <h2 id="about-company">About the company</h2>
                            <h3><?= e($internship["company_name"]) ?></h3>
                            <?php if (trim((string) $internship["company_description"]) !== ""): ?>
                                <p class="detail-description"><?= e($internship["company_description"]) ?></p>
                            <?php else: ?>
                                <p class="detail-note">The company has not added an introduction yet.</p>
                            <?php endif; ?>
                            <?php if ($companyWebsite !== ""): ?>
                                <a class="company-website" href="<?= e($companyWebsite) ?>" target="_blank" rel="noopener noreferrer">Visit company website <span aria-hidden="true">↗</span><span class="sr-only"> (opens in a new tab)</span></a>
                            <?php endif; ?>
                        </section>
                    </div>

                    <aside class="detail-summary surface" aria-labelledby="overview-title">
                        <h2 id="overview-title">Internship overview</h2>
                        <dl class="overview-list">
                            <div><dt>Location</dt><dd><?= e($internship["location"]) ?></dd></div>
                            <div><dt>Category</dt><dd><?= e($internship["category"]) ?></dd></div>
                            <div><dt>Posted on</dt><dd><?= e(date("d M Y", strtotime($internship["created_at"]))) ?></dd></div>
                            <div><dt>Apply before</dt><dd class="deadline-date"><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></dd></div>
                        </dl>
                        <?php if ($application): ?>
                            <div class="application-note">
                                <p>You have already applied</p>
                                <span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span>
                                <small>Applied on <?= e(date("d M Y", strtotime($application["applied_at"]))) ?></small>
                            </div>
                            <a class="detail-primary" href="my-applications.php">My applications <span aria-hidden="true">→</span></a>
                        <?php elseif (!$user): ?>
                            <a class="detail-primary" href="apply.php?id=<?= e($internshipId) ?>">Sign in to apply <span aria-hidden="true">→</span></a>
                            <p class="detail-note action-note">Sign in with your student account to submit your CV.</p>
                        <?php elseif ($user["role"] === "student"): ?>
                            <a class="detail-primary" href="apply.php?id=<?= e($internshipId) ?>">Apply now <span aria-hidden="true">→</span></a>
                            <p class="detail-note action-note">Upload your CV and send your application.</p>
                        <?php else: ?>
                            <p class="detail-note action-note">Applications are available to student accounts only.</p>
                        <?php endif; ?>
                        <a class="detail-secondary" href="internships.php">Explore more internships</a>
                    </aside>
                </div>
            <?php else: ?>
                <section class="detail-unavailable surface">
                    <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-search"/></svg></span>
                    <p class="eyebrow">OPPORTUNITY UNAVAILABLE</p>
                    <h1>We couldn't find this internship</h1>
                    <p>It may have closed, expired, or is no longer available.</p>
                    <a class="detail-primary" href="internships.php">Browse open internships <span aria-hidden="true">→</span></a>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
