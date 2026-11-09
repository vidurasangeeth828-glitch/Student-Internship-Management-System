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

$postedId = $_SESSION["vacancy_posted"] ?? 0;
unset($_SESSION["vacancy_posted"]);

$companyId = $company["company_id"];
$today = date("Y-m-d");
$showAll = isset($_GET["all"]) && $_GET["all"] === "1";

$stmt = $conn->prepare("SELECT COUNT(*) AS total,
                        COALESCE(SUM(status = 'Open' AND deadline >= ?), 0) AS open_count
                        FROM internships WHERE company_id = ?");
$stmt->bind_param("si", $today, $companyId);
$stmt->execute();
$vacancyCounts = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) AS total,
                        COALESCE(SUM(a.status = 'Shortlisted'), 0) AS shortlisted
                        FROM applications a
                        JOIN internships i ON i.internship_id = a.internship_id
                        WHERE i.company_id = ?");
$stmt->bind_param("i", $companyId);
$stmt->execute();
$applicationCounts = $stmt->get_result()->fetch_assoc();

$sql = "SELECT a.status, a.applied_at, u.full_name, i.title
        FROM applications a
        JOIN internships i ON i.internship_id = a.internship_id
        JOIN students s ON s.student_id = a.student_id
        JOIN users u ON u.user_id = s.user_id
        WHERE i.company_id = ?
        ORDER BY a.applied_at DESC, a.application_id DESC";
if (!$showAll) {
    $sql .= " LIMIT 5";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$applications = $stmt->get_result();

$sql = "SELECT internship_id, title, category, location, deadline, status,
        (SELECT COUNT(*) FROM applications a WHERE a.internship_id = internships.internship_id) AS application_count
        FROM internships WHERE company_id = ?
        ORDER BY created_at DESC, internship_id DESC";
if (!$showAll) {
    $sql .= " LIMIT 3";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$vacancies = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Dashboard | SIMS</title>
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

    <a class="skip-link" href="#main">Skip to company dashboard</a>
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
                <a href="company-dashboard.php" aria-current="page"><svg class="icon"><use href="#icon-home"/></svg>Dashboard</a>
                <?php if ($company["approval_status"] === "Approved"): ?>
                    <a href="post-internship.php"><svg class="icon"><use href="#icon-file"/></svg>Post Internship</a>
                <?php endif; ?>
                <a href="company-vacancies.php"><svg class="icon"><use href="#icon-search"/></svg>My Vacancies</a>
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
                <a class="back-to-home" href="index.php">← Back to home</a>
                <a class="account-chip surface" href="company-profile.php" aria-label="Company profile: <?= e($company["company_name"]) ?>">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($company["company_name"], 0, 1))) ?></span>
                    <span class="account-copy"><strong><?= e($company["company_name"]) ?></strong><small>Company</small></span>
                </a>
            </header>

            <section class="welcome company-welcome">
                <div>
                    <p class="eyebrow">YOUR COMPANY WORKSPACE</p>
                    <h1>Welcome, <?= e($company["company_name"]) ?>!</h1>
                    <p class="company-subtitle">Follow your vacancies and meet your next team members.</p>
                </div>
                <div class="company-post-action">
                    <?php if ($company["approval_status"] === "Approved"): ?>
                        <a class="filter-submit company-post-link" href="post-internship.php">Post Internship <span aria-hidden="true">+</span></a>
                    <?php else: ?>
                        <button class="filter-submit" type="button" disabled aria-describedby="posting-note">Post Internship <span aria-hidden="true">+</span></button>
                        <p id="posting-note">Admin approval is required to post internships.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="company-approval surface approval-<?= e(strtolower($company["approval_status"])) ?>" aria-labelledby="approval-title">
                <span class="company-approval-icon"><svg class="icon" aria-hidden="true"><use href="<?= $company["approval_status"] === "Approved" ? '#icon-check' : '#icon-clock' ?>"/></svg></span>
                <div>
                    <h2 id="approval-title">Company approval: <?= e($company["approval_status"]) ?></h2>
                    <?php if ($company["approval_status"] === "Approved"): ?>
                        <p>Your company is approved. Open vacancies within their deadline can appear in the internship listings.</p>
                    <?php elseif ($company["approval_status"] === "Pending"): ?>
                        <p>Your registration is awaiting admin approval. Your vacancies stay hidden from public listings until approval.</p>
                    <?php else: ?>
                        <p>Your company is not approved. Contact the administrator to check your registration details. Your vacancies are hidden from public listings.</p>
                    <?php endif; ?>
                </div>
                <span class="company-approval-badge"><?= e($company["approval_status"]) ?></span>
            </section>

            <?php if ($postedId): ?>
                <div class="profile-success post-success" role="status">
                    <span>Your internship has been published.</span>
                    <a href="internship-details.php?id=<?= e($postedId) ?>">View public listing <span aria-hidden="true">↗</span></a>
                </div>
            <?php endif; ?>

            <section class="stats" aria-label="Company overview">
                <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-search"/></svg></span><div><strong><?= $vacancyCounts["total"] ?></strong><p>Total vacancies</p></div></article>
                <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span><div><strong><?= $applicationCounts["total"] ?></strong><p>Applications received</p></div></article>
                <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-check"/></svg></span><div><strong><?= $applicationCounts["shortlisted"] ?></strong><p>Shortlisted</p></div></article>
            </section>

            <div class="lower-grid">
                <section class="panel surface" id="company-applications">
                    <div class="section-heading">
                        <h2><?= $showAll ? "All applications" : "Recent applications" ?></h2>
                        <?php if ($applicationCounts["total"] > 0): ?>
                            <a href="company-applications.php">View all <span aria-hidden="true">→</span></a>
                        <?php elseif ($showAll): ?>
                            <a href="company-applications.php">Show recent</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($applications->num_rows > 0): ?>
                        <div class="table-scroll" tabindex="0" role="region" aria-label="Company applications">
                            <table>
                                <thead><tr><th>Applicant</th><th>Internship</th><th>Applied</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php while ($application = $applications->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($application["full_name"]) ?></td>
                                            <td><?= e($application["title"]) ?></td>
                                            <td><?= e(date("d M Y", strtotime($application["applied_at"]))) ?></td>
                                            <td><span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                            <h3>No applications yet</h3>
                            <p>Applications sent to your company's internships will appear here.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="panel surface quick-links company-quick-links">
                    <div class="section-heading"><h2>Quick links</h2></div>
                    <a href="company-vacancies.php"><svg class="icon" aria-hidden="true"><use href="#icon-search"/></svg>My vacancies<span aria-hidden="true">›</span></a>
                    <a href="company-applications.php"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg>View applications<span aria-hidden="true">›</span></a>
                    <a href="company-profile.php"><svg class="icon" aria-hidden="true"><use href="#icon-user"/></svg>Company details<span aria-hidden="true">›</span></a>
                </section>
            </div>

            <section class="panel surface" id="company-vacancies">
                <div class="section-heading">
                    <h2>My vacancies</h2>
                    <?php if (!$showAll && $vacancyCounts["total"] > 3): ?>
                        <a href="company-vacancies.php">View all <span aria-hidden="true">→</span></a>
                    <?php elseif ($showAll): ?>
                        <a href="company-vacancies.php">Show recent</a>
                    <?php endif; ?>
                </div>
                <?php if ($vacancies->num_rows > 0): ?>
                    <div class="internship-grid">
                        <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
                            <?php
                            $vacancyLabel = $vacancy["status"];
                            $labelClass = "company-vacancy-muted";
                            $publicVacancy = false;
                            if ($vacancy["status"] === "Open") {
                                if ($vacancy["deadline"] < $today) {
                                    $vacancyLabel = "Expired";
                                } elseif ($company["approval_status"] !== "Approved") {
                                    $vacancyLabel = "Awaiting approval";
                                } else {
                                    $labelClass = "company-vacancy-open";
                                    $publicVacancy = true;
                                }
                            }
                            ?>
                            <article class="internship-card company-vacancy-card">
                                <span class="company-vacancy-status <?= e($labelClass) ?>"><?= e($vacancyLabel) ?></span>
                                <h3><?= e($vacancy["title"]) ?></h3>
                                <a class="company-listing-link" href="company-vacancies.php?keyword=<?= urlencode($vacancy["title"]) ?>">Manage vacancy →</a>
                                <a class="company-listing-link" href="company-applications.php?internship=<?= e($vacancy["internship_id"]) ?>"><?= e($vacancy["application_count"]) ?> applications →</a>
                                <p class="company-vacancy-category"><?= e($vacancy["category"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($vacancy["location"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon" aria-hidden="true"><use href="#icon-calendar"/></svg>Deadline <?= e(date("d M Y", strtotime($vacancy["deadline"]))) ?></p>
                                <?php if ($publicVacancy): ?>
                                    <a class="company-listing-link" href="internship-details.php?id=<?= e($vacancy["internship_id"]) ?>">View public listing <span aria-hidden="true">↗</span></a>
                                <?php endif; ?>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-search"/></svg></span>
                        <h3>No vacancies yet</h3>
                        <p>Your internship opportunities will appear here once they are posted.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel surface company-details" id="company-profile" aria-labelledby="company-details-title">
                <div class="section-heading"><h2 id="company-details-title">Company details</h2></div>
                <dl class="company-details-grid">
                    <div><dt>Company name</dt><dd><?= e($company["company_name"]) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($company["email"]) ?></dd></div>
                    <div><dt>Contact number</dt><dd><?= e($company["phone"]) ?></dd></div>
                    <div><dt>Business registration number</dt><dd><?= $company["business_registration_no"] !== "" ? e($company["business_registration_no"]) : "Not provided" ?></dd></div>
                </dl>
            </section>
        </main>
    </div>
</body>
</html>
