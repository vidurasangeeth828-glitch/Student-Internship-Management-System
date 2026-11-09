<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$stmt = $conn->prepare("SELECT u.full_name, u.role, s.student_id, s.photo_path
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

$statuses = ["Submitted", "Reviewed", "Shortlisted", "Accepted", "Rejected"];
$status = "";
if (isset($_GET["status"]) && is_string($_GET["status"]) && in_array($_GET["status"], $statuses, true)) {
    $status = $_GET["status"];
}

$studentId = $user["student_id"];
$stmt = $conn->prepare("SELECT a.application_id, a.cover_letter, a.cv_path, a.status, a.applied_at,
                        i.title, i.location, c.company_name
                        FROM applications a
                        JOIN internships i ON i.internship_id = a.internship_id
                        JOIN companies c ON c.company_id = i.company_id
                        WHERE a.student_id = ? AND (? = '' OR a.status = ?)
                        ORDER BY a.applied_at DESC, a.application_id DESC");
$stmt->bind_param("iss", $studentId, $status, $status);
$stmt->execute();
$applications = $stmt->get_result();
$total = $applications->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Applications | SIMS</title>
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
    </svg>
    <a class="skip-link" href="#main">Skip to my applications</a>
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
                    <a href="my-applications.php" aria-current="page"><svg class="icon"><use href="#icon-file"/></svg>My Applications</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a href="student-profile.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
                <?php else: ?>
                    <a href="signin.php"><svg class="icon"><use href="#icon-user"/></svg>Sign In / Sign Up</a>
                <?php endif; ?>
                <a href="companies.php"><svg class="icon"><use href="#icon-user"/></svg>Companies</a>
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
                <p class="eyebrow">YOUR CAREER JOURNEY</p>
                <h1>My applications</h1>
                <p>Keep track of the opportunities you have applied for.</p>
            </section>

            <form class="applications-filter surface" action="my-applications.php" method="get">
                <div>
                    <label for="application-status">Application status</label>
                    <select id="application-status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($statuses as $item): ?>
                            <option value="<?= e($item) ?>" <?= $status === $item ? "selected" : "" ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="filter-submit" type="submit">Filter applications</button>
                <?php if ($status !== ""): ?>
                    <a class="clear-filters" href="my-applications.php">Clear filter</a>
                <?php endif; ?>
                <p class="applications-count" role="status"><?= $total ?> <?= $total === 1 ? "application" : "applications" ?></p>
            </form>

            <section class="applications-list" aria-label="Your applications">
                <?php if ($total > 0): ?>
                    <?php while ($application = $applications->fetch_assoc()): ?>
                        <article class="panel surface application-card" aria-labelledby="application-<?= e($application["application_id"]) ?>">
                            <div class="application-card-header">
                                <div class="application-role">
                                    <span class="company-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($application["company_name"], 0, 2))) ?></span>
                                    <div>
                                        <h2 id="application-<?= e($application["application_id"]) ?>"><?= e($application["title"]) ?></h2>
                                        <p><?= e($application["company_name"]) ?></p>
                                    </div>
                                </div>
                                <span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span>
                            </div>
                            <div class="application-metadata">
                                <p><svg class="icon small-icon" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($application["location"]) ?></p>
                                <p><svg class="icon small-icon" aria-hidden="true"><use href="#icon-calendar"/></svg>Applied on <?= e(date("d M Y", strtotime($application["applied_at"]))) ?></p>
                                <p>Application #<?= e($application["application_id"]) ?></p>
                            </div>
                            <div class="application-actions">
                                <details class="application-letter">
                                    <summary>View cover letter</summary>
                                    <?php if (trim((string) $application["cover_letter"]) !== ""): ?>
                                        <p><?= e($application["cover_letter"]) ?></p>
                                    <?php else: ?>
                                        <p>No cover letter was included with this application.</p>
                                    <?php endif; ?>
                                </details>
                                <?php if (!empty($application["cv_path"])): ?>
                                    <a class="filter-submit application-download" href="download-cv.php?id=<?= e($application["application_id"]) ?>" aria-label="Download CV for <?= e($application["title"]) ?>">Download CV <span class="download-format">PDF</span></a>
                                <?php else: ?>
                                    <p class="application-no-cv">No CV attached</p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="panel surface empty-state applications-empty">
                        <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                        <h2><?= $status !== "" ? "No applications with this status" : "Your next chapter starts here" ?></h2>
                        <p><?= $status !== "" ? "Choose another status or clear the filter to see all your applications." : "You haven't applied for an internship yet. Explore opportunities and send your first application." ?></p>
                        <?php if ($status !== ""): ?>
                            <a class="filter-submit" href="my-applications.php">Show all applications</a>
                        <?php else: ?>
                            <a class="filter-submit" href="internships.php">Browse internships <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
