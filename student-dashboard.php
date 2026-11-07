<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$userId = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT u.full_name, u.role, s.student_id
                        FROM users u
                        LEFT JOIN students s ON s.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    unset($_SESSION["user_id"], $_SESSION["role"]);
    redirect("signin.php");
}

if ($user["role"] !== "student") {
    redirect("account.php");
}

if (!$user["student_id"]) {
    http_response_code(403);
    exit("Your student profile could not be found. Please contact the administrator.");
}

$studentId = $user["student_id"];
$nameParts = explode(" ", trim($user["full_name"]));
$firstName = $nameParts[0];
$initial = mb_strtoupper(mb_substr($user["full_name"], 0, 1));

$stmt = $conn->prepare("SELECT COUNT(*) AS total,
                        COALESCE(SUM(status = 'Shortlisted'), 0) AS shortlisted,
                        COALESCE(SUM(status = 'Reviewed'), 0) AS reviewed
                        FROM applications WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();

$keyword = "";
if (isset($_GET["q"]) && is_string($_GET["q"])) {
    $keyword = mb_substr(trim($_GET["q"]), 0, 100);
}
$search = "%" . $keyword . "%";
$showAll = isset($_GET["all"]);

$sql = "SELECT i.internship_id, i.title, i.description, i.location, i.deadline, c.company_name
        FROM internships i
        JOIN companies c ON c.company_id = i.company_id
        JOIN users u ON u.user_id = c.user_id
        WHERE i.status = 'Open' AND i.deadline >= ?
        AND c.approval_status = 'Approved' AND u.status = 'Active'
        AND (i.title LIKE ? OR c.company_name LIKE ? OR i.description LIKE ?)
        ORDER BY i.deadline, i.internship_id DESC";

if (!$showAll) {
    $sql .= " LIMIT 3";
}

$today = date("Y-m-d");
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $today, $search, $search, $search);
$stmt->execute();
$internships = $stmt->get_result();

$sql = "SELECT a.status, a.applied_at, i.title, c.company_name
        FROM applications a
        JOIN internships i ON i.internship_id = a.internship_id
        JOIN companies c ON c.company_id = i.company_id
        WHERE a.student_id = ?
        ORDER BY a.applied_at DESC, a.application_id DESC";

if (!$showAll) {
    $sql .= " LIMIT 5";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$applications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard | SIMS</title>
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

    <a class="skip-link" href="#main">Skip to dashboard</a>
    <div class="ambient ambient-purple" aria-hidden="true"></div>
    <div class="ambient ambient-mint" aria-hidden="true"></div>
    <div class="ambient ambient-peach" aria-hidden="true"></div>
    <div class="ambient ambient-lilac" aria-hidden="true"></div>

    <div class="dashboard-layout">
        <aside class="sidebar glass" id="student-sidebar" aria-label="Student workspace">
            <div class="sidebar-header">
                <a class="brand" href="index.php"><img src="assets/logo.svg" width="44" height="42" alt=""><strong>SIMS</strong></a>
                <button class="icon-button sidebar-close" type="button" aria-label="Close menu"><svg class="icon"><use href="#icon-close"/></svg></button>
            </div>
            <p class="brand-description">Student Internship<br>Management System</p>

            <nav class="sidebar-nav" aria-label="Student navigation">
                <a href="student-dashboard.php" aria-current="page"><svg class="icon"><use href="#icon-home"/></svg>Dashboard</a>
                <a href="#available-internships"><svg class="icon"><use href="#icon-search"/></svg>Browse Internships</a>
                <a href="#recent-applications"><svg class="icon"><use href="#icon-file"/></svg>My Applications</a>
                <a href="account.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
            </nav>

            <div class="sidebar-bottom">
                <p>Your next chapter<br>starts here</p>
                <form action="logout.php" method="post">
                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                    <button type="submit"><svg class="icon"><use href="#icon-logout"/></svg>Logout</button>
                </form>
            </div>
        </aside>

        <div class="sidebar-overlay" hidden></div>

        <main id="main" class="workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <form class="search-box glass" action="student-dashboard.php#available-internships" method="get" role="search">
                    <svg class="icon"><use href="#icon-search"/></svg>
                    <label class="sr-only" for="internship-search">Search internships</label>
                    <input id="internship-search" name="q" placeholder="Search internships..." maxlength="100" value="<?= e($keyword) ?>">
                    <button type="submit" aria-label="Search"><svg class="icon small-icon"><use href="#icon-arrow"/></svg></button>
                </form>
                <a class="account-chip glass" href="account.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                    <span class="avatar"><?= e($initial) ?></span>
                    <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small>Student</small></span>
                </a>
            </header>

            <section class="welcome">
                <p class="eyebrow">YOUR STUDENT WORKSPACE</p>
                <h1>Welcome back, <?= e($firstName) ?>!</h1>
                <p>Find your next opportunity and follow your applications.</p>
            </section>

            <section class="stats" aria-label="Application overview">
                <article class="stat glass stat-purple"><span class="stat-icon"><svg class="icon"><use href="#icon-file"/></svg></span><div><strong><?= $counts["total"] ?></strong><p>Applications sent</p></div></article>
                <article class="stat glass stat-mint"><span class="stat-icon"><svg class="icon"><use href="#icon-check"/></svg></span><div><strong><?= $counts["shortlisted"] ?></strong><p>Shortlisted</p></div></article>
                <article class="stat glass stat-peach"><span class="stat-icon"><svg class="icon"><use href="#icon-clock"/></svg></span><div><strong><?= $counts["reviewed"] ?></strong><p>In review</p></div></article>
            </section>

            <section class="panel glass" id="available-internships">
                <div class="section-heading">
                    <h2>Available internships</h2>
                    <?php if (!$showAll && $internships->num_rows === 3): ?>
                        <a href="?all=1&amp;q=<?= urlencode($keyword) ?>#available-internships">View all <span aria-hidden="true">→</span></a>
                    <?php elseif ($showAll): ?>
                        <a href="student-dashboard.php#available-internships">Show recent</a>
                    <?php endif; ?>
                </div>

                <?php if ($internships->num_rows > 0): ?>
                    <div class="internship-grid">
                        <?php while ($internship = $internships->fetch_assoc()): ?>
                            <article class="internship-card">
                                <div class="internship-heading">
                                    <span class="company-avatar"><?= e(mb_strtoupper(mb_substr($internship["company_name"], 0, 2))) ?></span>
                                    <div><h3><?= e($internship["title"]) ?></h3><p><?= e($internship["company_name"]) ?></p></div>
                                </div>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-pin"/></svg><?= e($internship["location"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-calendar"/></svg>Apply by <strong><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></strong></p>
                                <details class="vacancy-details">
                                    <summary>View details</summary>
                                    <p><?= e($internship["description"]) ?></p>
                                </details>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="empty-icon"><svg class="icon"><use href="#icon-search"/></svg></span>
                        <h3><?= $keyword !== "" ? "No matching internships" : "Your next opportunity is on its way" ?></h3>
                        <p><?= $keyword !== "" ? "Try another role, company or keyword." : "New internships will appear here when approved companies publish them." ?></p>
                        <?php if ($keyword !== ""): ?><a href="student-dashboard.php#available-internships">Clear search</a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="lower-grid">
                <section class="panel glass" id="recent-applications">
                    <div class="section-heading">
                        <h2>Recent applications</h2>
                        <?php if (!$showAll && $applications->num_rows === 5): ?><a href="?all=1#recent-applications">View all</a><?php endif; ?>
                    </div>
                    <?php if ($applications->num_rows > 0): ?>
                        <div class="table-scroll" tabindex="0" role="region" aria-label="Recent applications">
                            <table>
                                <thead><tr><th>Internship</th><th>Company</th><th>Applied</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php while ($application = $applications->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($application["title"]) ?></td>
                                            <td><?= e($application["company_name"]) ?></td>
                                            <td><?= e(date("d M Y", strtotime($application["applied_at"]))) ?></td>
                                            <td><span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state compact">
                            <span class="empty-icon"><svg class="icon"><use href="#icon-file"/></svg></span>
                            <h3>No applications yet</h3>
                            <p>Your submitted applications and their latest status will appear here.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel glass quick-links">
                    <div class="section-heading"><h2>Quick links</h2></div>
                    <a href="#available-internships"><svg class="icon"><use href="#icon-search"/></svg>Browse internships<span aria-hidden="true">›</span></a>
                    <a href="account.php"><svg class="icon"><use href="#icon-user"/></svg>My account<span aria-hidden="true">›</span></a>
                    <a href="#recent-applications"><svg class="icon"><use href="#icon-file"/></svg>My applications<span aria-hidden="true">›</span></a>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
