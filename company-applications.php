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
$statuses = ["Submitted", "Reviewed", "Shortlisted", "Accepted", "Rejected"];
$reviewStatuses = ["Reviewed", "Shortlisted", "Accepted", "Rejected"];

// Process status changes before loading the page details.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_form();
    $applicationId = filter_var(post_text("application_id"), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $newStatus = post_text("status");
    $expectedStatus = post_text("expected_status");
    $returnStatus = post_text("return_status");
    $returnInternship = filter_var(post_text("return_internship"), FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);

    if ($applicationId === false || !in_array($newStatus, $reviewStatuses, true) || !in_array($expectedStatus, $statuses, true)) {
        http_response_code(400);
        exit("Please choose a valid application and review status.");
    }
    if (!in_array($returnStatus, $statuses, true)) {
        $returnStatus = "";
    }
    if ($returnInternship === false) {
        $returnInternship = 0;
    }

    $message = "";
    $isError = false;
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT c.company_id FROM companies c
                                JOIN users u ON u.user_id = c.user_id
                                WHERE u.user_id = ? AND u.role = 'company' AND u.status = 'Active'
                                FOR UPDATE");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $activeCompany = $stmt->get_result()->fetch_assoc();
        if (!$activeCompany) {
            $conn->rollback();
            http_response_code(403);
            exit("Your company account is no longer available.");
        }

        $stmt = $conn->prepare("SELECT a.status FROM applications a
                                JOIN internships i ON i.internship_id = a.internship_id
                                WHERE a.application_id = ? AND i.company_id = ?
                                FOR UPDATE");
        $stmt->bind_param("ii", $applicationId, $companyId);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();
        if (!$application) {
            $conn->rollback();
            http_response_code(404);
            exit("Application not available.");
        }

        if ($application["status"] !== $expectedStatus) {
            $message = "This application's status has changed. Review the latest status before updating it again.";
            $isError = true;
        } elseif ($application["status"] === $newStatus) {
            $message = "The application is already marked as " . $newStatus . ".";
        } else {
            $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
            $stmt->bind_param("si", $newStatus, $applicationId);
            $stmt->execute();
            $message = "Application status updated to " . $newStatus . ".";
        }
        $conn->commit();
    } catch (mysqli_sql_exception $error) {
        $conn->rollback();
        error_log("Application review failed: " . $error->getMessage());
        $message = "The application status could not be saved. Please try again.";
        $isError = true;
    }

    $_SESSION["company_application_notice"] = ["message" => $message, "error" => $isError];
    redirect("company-applications.php?status=" . urlencode($returnStatus) . "&internship=" . $returnInternship);
}
if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
    header("Allow: GET, HEAD, POST");
    http_response_code(405);
    exit("Please use the application review form.");
}

$status = "";
if (isset($_GET["status"]) && is_string($_GET["status"]) && in_array($_GET["status"], $statuses, true)) {
    $status = $_GET["status"];
}
$internshipId = 0;
if (isset($_GET["internship"]) && is_string($_GET["internship"])) {
    $requestedId = filter_var($_GET["internship"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($requestedId !== false) {
        $internshipId = $requestedId;
    }
}

$stmt = $conn->prepare("SELECT internship_id, title FROM internships WHERE company_id = ? ORDER BY created_at DESC, internship_id DESC");
$stmt->bind_param("i", $companyId);
$stmt->execute();
$vacancies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ownInternship = false;
foreach ($vacancies as $vacancy) {
    if ((int) $vacancy["internship_id"] === $internshipId) {
        $ownInternship = true;
    }
}
if (!$ownInternship) {
    $internshipId = 0;
}

$stmt = $conn->prepare("SELECT a.application_id, a.status, a.applied_at, a.cover_letter, a.cv_path,
                        i.title, i.location, u.full_name, u.email,
                        s.phone, s.field_of_interest, s.education_status, s.skills, s.about_me
                        FROM applications a
                        JOIN internships i ON i.internship_id = a.internship_id
                        JOIN students s ON s.student_id = a.student_id
                        JOIN users u ON u.user_id = s.user_id
                        WHERE i.company_id = ? AND (? = '' OR a.status = ?)
                        AND (? = 0 OR i.internship_id = ?)
                        ORDER BY a.applied_at DESC, a.application_id DESC");
$stmt->bind_param("issii", $companyId, $status, $status, $internshipId, $internshipId);
$stmt->execute();
$applications = $stmt->get_result();
$total = $applications->num_rows;
$notice = $_SESSION["company_application_notice"] ?? null;
unset($_SESSION["company_application_notice"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Applications | SIMS Company</title>
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

    <a class="skip-link" href="#main">Skip to company applications</a>
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
                <a href="company-dashboard.php#company-vacancies"><svg class="icon"><use href="#icon-search"/></svg>My Vacancies</a>
                <a href="company-applications.php" aria-current="page"><svg class="icon"><use href="#icon-file"/></svg>Applications</a>
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
                <p class="eyebrow">MEET YOUR NEXT TEAM MEMBERS</p>
                <h1>Applications</h1>
                <p>Review the students who have applied to your internships.</p>
            </section>

            <?php if ($notice): ?>
                <p class="<?= $notice["error"] ? 'profile-error-summary' : 'profile-success' ?>" role="status"><?= e($notice["message"]) ?></p>
            <?php endif; ?>

            <form class="applications-filter surface company-application-filters" action="company-applications.php" method="get">
                <div>
                    <label for="review-status">Application status</label>
                    <select id="review-status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach ($statuses as $item): ?>
                            <option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="review-internship">Internship</label>
                    <select id="review-internship" name="internship">
                        <option value="0">All internships</option>
                        <?php foreach ($vacancies as $vacancy): ?>
                            <option value="<?= e($vacancy["internship_id"]) ?>" <?= $internshipId === (int) $vacancy["internship_id"] ? 'selected' : '' ?>><?= e($vacancy["title"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="filter-submit" type="submit">Filter applications</button>
                <?php if ($status !== '' || $internshipId !== 0): ?><a class="clear-filters" href="company-applications.php">Clear</a><?php endif; ?>
                <p class="applications-count"><?= $total ?> <?= $total === 1 ? 'application' : 'applications' ?></p>
            </form>

            <section class="applications-list" aria-label="Applications received by your company">
                <?php if ($total > 0): ?>
                    <?php while ($application = $applications->fetch_assoc()): ?>
                        <article class="panel surface application-card company-application-card" id="application-<?= e($application["application_id"]) ?>" aria-labelledby="applicant-<?= e($application["application_id"]) ?>">
                            <div class="application-card-header">
                                <div class="application-role">
                                    <span class="company-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($application["full_name"], 0, 1))) ?></span>
                                    <div>
                                        <h2 id="applicant-<?= e($application["application_id"]) ?>"><?= e($application["full_name"]) ?></h2>
                                        <p><?= e($application["title"]) ?></p>
                                    </div>
                                </div>
                                <span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span>
                            </div>
                            <div class="application-metadata">
                                <p><svg class="icon small-icon" aria-hidden="true"><use href="#icon-calendar"/></svg>Applied <?= e(date('d M Y', strtotime($application["applied_at"]))) ?></p>
                                <p><svg class="icon small-icon" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($application["location"]) ?></p>
                                <p>Application #<?= e($application["application_id"]) ?></p>
                            </div>
                            <dl class="company-details-grid company-applicant-details">
                                <div><dt>Email address</dt><dd><?= e($application["email"]) ?></dd></div>
                                <div><dt>Contact number</dt><dd><?= $application["phone"] !== '' ? e($application["phone"]) : 'Not provided' ?></dd></div>
                                <div><dt>Field of interest</dt><dd><?= $application["field_of_interest"] !== '' ? e($application["field_of_interest"]) : 'Not provided' ?></dd></div>
                                <div><dt>Education status</dt><dd><?= $application["education_status"] !== '' ? e($application["education_status"]) : 'Not provided' ?></dd></div>
                            </dl>

                            <div class="company-application-documents">
                                <details class="application-letter">
                                    <summary>View cover letter</summary>
                                    <p><?= trim((string) $application["cover_letter"]) !== '' ? e($application["cover_letter"]) : 'No cover letter was included with this application.' ?></p>
                                </details>
                                <?php if (!empty($application["cv_path"])): ?>
                                    <a class="filter-submit application-download" href="download-cv.php?id=<?= e($application["application_id"]) ?>" aria-label="Download CV for <?= e($application["full_name"]) ?>">Download CV <span class="download-format">PDF</span></a>
                                <?php else: ?>
                                    <p class="application-no-cv">No CV attached</p>
                                <?php endif; ?>
                            </div>
                            <?php if (trim((string) $application["about_me"]) !== '' || $application["skills"] !== ''): ?>
                                <details class="admin-company-extra">
                                    <summary>More about the applicant</summary>
                                    <?php if ($application["skills"] !== ''): ?><p><strong>Skills:</strong> <?= e($application["skills"]) ?></p><?php endif; ?>
                                    <?php if (trim((string) $application["about_me"]) !== ''): ?><p class="admin-company-description"><?= e($application["about_me"]) ?></p><?php endif; ?>
                                </details>
                            <?php endif; ?>

                            <form class="company-review-form" action="company-applications.php" method="post">
                                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                <input type="hidden" name="application_id" value="<?= e($application["application_id"]) ?>">
                                <input type="hidden" name="expected_status" value="<?= e($application["status"]) ?>">
                                <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                <input type="hidden" name="return_internship" value="<?= e($internshipId) ?>">
                                <p>The student will see the saved status in My Applications.</p>
                                <div class="profile-field">
                                    <label for="status-<?= e($application["application_id"]) ?>">Review status</label>
                                    <select id="status-<?= e($application["application_id"]) ?>" name="status" required>
                                        <option value="">Choose a status</option>
                                        <?php foreach ($reviewStatuses as $item): ?>
                                            <option value="<?= e($item) ?>" <?= $application["status"] === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="filter-submit" type="submit">Save status</button>
                            </form>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="panel surface empty-state applications-empty">
                        <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                        <h2><?= $status !== '' || $internshipId !== 0 ? 'No matching applications' : 'No applications yet' ?></h2>
                        <p><?= $status !== '' || $internshipId !== 0 ? 'Try another status or internship, or clear the filters.' : 'Applications sent to your internships will appear here.' ?></p>
                        <?php if ($status !== '' || $internshipId !== 0): ?><a href="company-applications.php">Clear filters</a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
