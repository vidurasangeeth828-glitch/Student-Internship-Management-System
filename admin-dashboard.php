<?php
require "session.php";

if (!isset($_SESSION["admin_id"])) {
    redirect("admin-signin.php");
}

require "db.php";
$stmt = $conn->prepare("SELECT full_name, email FROM admins WHERE admin_id = ? AND status = 'Active'");
$stmt->bind_param("i", $_SESSION["admin_id"]);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
if (!$admin) {
    unset($_SESSION["admin_id"]);
    redirect("admin-signin.php");
}

date_default_timezone_set("Asia/Colombo");
$today = date("Y-m-d");
$tabs = ["overview" => "Dashboard", "users" => "Manage users", "vacancies" => "Manage vacancies", "applications" => "Applications"];
$tab = "overview";
if (isset($_GET["tab"]) && is_string($_GET["tab"]) && isset($tabs[$_GET["tab"]])) {
    $tab = $_GET["tab"];
}
$notice = $_SESSION["admin_notice"] ?? "";
unset($_SESSION["admin_notice"]);
$applicationStatuses = ["Submitted", "Reviewed", "Shortlisted", "Accepted", "Rejected"];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_form();
    $action = post_text("action");
    $recordId = filter_var(post_text("record_id"), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $newStatus = post_text("new_status");
    $expectedStatus = post_text("expected_status");
    if ($recordId === false) {
        http_response_code(400);
        exit("Invalid record.");
    }
    $returnTab = "overview";
    $notice = "";
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
        if ($action === "user") {
            $returnTab = "users";
            if (!in_array($newStatus, ["Active", "Inactive"], true) || !in_array($expectedStatus, ["Active", "Inactive"], true)) {
                $conn->rollback();
                http_response_code(400);
                exit("Invalid account status.");
            }
            $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ? FOR UPDATE");
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();
            if (!$record) {
                $conn->rollback();
                http_response_code(404);
                exit("Account not found.");
            }
            if ($record["status"] !== $expectedStatus) {
                $notice = "The account status has changed. Review the latest status before trying again.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $stmt->bind_param("si", $newStatus, $recordId);
                $stmt->execute();
                $notice = "Account marked as " . $newStatus . ". Existing profiles and applications have been kept.";
            }
        } elseif ($action === "vacancy") {
            $returnTab = "vacancies";
            if (!in_array($newStatus, ["Open", "Closed", "Archived"], true) || !in_array($expectedStatus, ["Open", "Closed", "Archived"], true)) {
                $conn->rollback();
                http_response_code(400);
                exit("Invalid vacancy status.");
            }
            $stmt = $conn->prepare("SELECT i.status, i.deadline, c.approval_status, u.status AS account_status
                                    FROM internships i JOIN companies c ON c.company_id = i.company_id
                                    JOIN users u ON u.user_id = c.user_id
                                    WHERE i.internship_id = ? FOR UPDATE");
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();
            if (!$record) {
                $conn->rollback();
                http_response_code(404);
                exit("Vacancy not found.");
            }
            if ($record["status"] !== $expectedStatus) {
                $notice = "The vacancy status has changed. Review its latest status before trying again.";
            } elseif ($newStatus === "Open" && ($record["status"] !== "Closed" || $record["approval_status"] !== "Approved" || $record["account_status"] !== "Active" || $record["deadline"] < $today)) {
                $notice = "Reopening requires a closed vacancy, a current deadline and an active, approved company.";
            } else {
                $stmt = $conn->prepare("UPDATE internships SET status = ? WHERE internship_id = ?");
                $stmt->bind_param("si", $newStatus, $recordId);
                $stmt->execute();
                $notice = "Vacancy marked as " . $newStatus . ". Application history has been kept.";
            }
        } elseif ($action === "application") {
            $returnTab = "applications";
            if (!in_array($newStatus, $applicationStatuses, true) || !in_array($expectedStatus, $applicationStatuses, true)) {
                $conn->rollback();
                http_response_code(400);
                exit("Invalid application status.");
            }
            $stmt = $conn->prepare("SELECT status FROM applications WHERE application_id = ? FOR UPDATE");
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();
            if (!$record) {
                $conn->rollback();
                http_response_code(404);
                exit("Application not found.");
            }
            if ($record["status"] !== $expectedStatus) {
                $notice = "The application status has changed. Review the latest status before saving again.";
            } else {
                $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
                $stmt->bind_param("si", $newStatus, $recordId);
                $stmt->execute();
                $notice = "Application status updated to " . $newStatus . ".";
            }
        } else {
            $conn->rollback();
            http_response_code(400);
            exit("Invalid administrator action.");
        }
        $conn->commit();
    } catch (mysqli_sql_exception $error) {
        $conn->rollback();
        error_log("Admin update failed: " . $error->getMessage());
        $notice = "This change could not be saved. Please try again.";
    }
    $_SESSION["admin_notice"] = $notice;
    redirect("admin-dashboard.php?tab=" . $returnTab);
}
if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
    header("Allow: GET, HEAD, POST");
    http_response_code(405);
    exit("Please use the administrator form.");
}

$userCounts = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'Active'), 0) AS active FROM users")->fetch_assoc();
$companyCounts = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(approval_status = 'Pending'), 0) AS pending FROM companies")->fetch_assoc();
$vacancyCounts = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'Open'), 0) AS open_count FROM internships")->fetch_assoc();
$applicationCounts = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'Accepted'), 0) AS accepted FROM applications")->fetch_assoc();
// The report contains totals only; applicant contact details and CVs are not exported.
if (isset($_GET["export"]) && is_string($_GET["export"]) && $_GET["export"] === "summary") {
    header("Content-Type: text/csv; charset=UTF-8");
    header('Content-Disposition: attachment; filename="SIMS-summary-' . $today . '.csv"');
    header("Cache-Control: private, no-store, max-age=0");
    $output = fopen("php://output", "w");
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ["Group", "Metric", "Count"], ",", '"', "");
    fputcsv($output, ["Report", "Generated in Asia/Colombo", date("Y-m-d H:i:s")], ",", '"', "");
    fputcsv($output, ["Accounts", "All registered accounts", $userCounts["total"]], ",", '"', "");
    fputcsv($output, ["Accounts", "Active accounts", $userCounts["active"]], ",", '"', "");
    $rows = $conn->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role");
    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, ["Account roles", ucfirst($row["role"]), $row["total"]], ",", '"', "");
    }
    $rows = $conn->query("SELECT approval_status, COUNT(*) AS total FROM companies GROUP BY approval_status");
    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, ["Company approvals", $row["approval_status"], $row["total"]], ",", '"', "");
    }
    $rows = $conn->query("SELECT status, COUNT(*) AS total FROM internships GROUP BY status");
    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, ["Vacancies", $row["status"], $row["total"]], ",", '"', "");
    }
    $rows = $conn->query("SELECT status, COUNT(*) AS total FROM applications GROUP BY status");
    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, ["Applications", $row["status"], $row["total"]], ",", '"', "");
    }
    fclose($output);
    exit();
}
$recentUsers = $conn->query("SELECT full_name, role, created_at FROM users ORDER BY user_id DESC LIMIT 6");
$statusCounts = $conn->query("SELECT status, COUNT(*) AS total FROM applications GROUP BY status");
$keyword = "";
if (isset($_GET["keyword"]) && is_string($_GET["keyword"])) {
    $keyword = mb_substr(trim($_GET["keyword"]), 0, 100);
}
$search = "%" . $keyword . "%";
$filterOptions = ["users" => ["Active", "Inactive"], "vacancies" => ["Open", "Closed", "Expired", "Archived"], "applications" => $applicationStatuses];
$filter = "";
if (isset($_GET["status"]) && is_string($_GET["status"]) && in_array($_GET["status"], $filterOptions[$tab] ?? [], true)) {
    $filter = $_GET["status"];
}
$page = 1;
if (isset($_GET["page"]) && is_string($_GET["page"])) {
    $requestedPage = filter_var($_GET["page"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 100000]]);
    if ($requestedPage !== false) {
        $page = $requestedPage;
    }
}
$pageSize = 10;
$fetchLimit = $pageSize + 1;
$offset = ($page - 1) * $pageSize;
$records = null;
if ($tab === "users") {
    $stmt = $conn->prepare("SELECT user_id, full_name, email, role, status, created_at FROM users
                            WHERE (? = '' OR status = ?) AND (full_name LIKE ? OR email LIKE ?)
                            ORDER BY user_id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssssii", $filter, $filter, $search, $search, $fetchLimit, $offset);
    $stmt->execute();
    $records = $stmt->get_result();
}
if ($tab === "vacancies") {
    $stmt = $conn->prepare("SELECT i.*, c.company_name, c.approval_status, u.status AS account_status,
                            (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.internship_id) AS application_count
                            FROM internships i JOIN companies c ON c.company_id = i.company_id
                            JOIN users u ON u.user_id = c.user_id
                            WHERE (i.title LIKE ? OR c.company_name LIKE ?)
                            AND (? = '' OR (i.status = ? AND (? <> 'Open' OR i.deadline >= ?))
                            OR (? = 'Expired' AND i.status = 'Open' AND i.deadline < ?))
                            ORDER BY i.internship_id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssssssssii", $search, $search, $filter, $filter, $filter, $today, $filter, $today, $fetchLimit, $offset);
    $stmt->execute();
    $records = $stmt->get_result();
}
$internshipId = 0;
if (isset($_GET["internship"]) && is_string($_GET["internship"])) {
    $requestedId = filter_var($_GET["internship"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($requestedId !== false) {
        $internshipId = $requestedId;
    }
}
if ($tab === "applications") {
    $stmt = $conn->prepare("SELECT a.*, i.title, c.company_name, u.full_name, u.email,
                            s.phone, s.field_of_interest, s.education_status, s.skills, s.about_me
                            FROM applications a JOIN internships i ON i.internship_id = a.internship_id
                            JOIN companies c ON c.company_id = i.company_id
                            JOIN students s ON s.student_id = a.student_id JOIN users u ON u.user_id = s.user_id
                            WHERE (? = '' OR a.status = ?) AND (? = 0 OR a.internship_id = ?)
                            AND (u.full_name LIKE ? OR u.email LIKE ? OR i.title LIKE ? OR c.company_name LIKE ?)
                            ORDER BY a.application_id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssiissssii", $filter, $filter, $internshipId, $internshipId, $search, $search, $search, $search, $fetchLimit, $offset);
    $stmt->execute();
    $records = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | SIMS</title>
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

    <a class="skip-link" href="#main">Skip to admin workspace</a>
    <div class="applications-glows" aria-hidden="true">
        <div class="ambient ambient-purple"></div>
        <div class="ambient ambient-lavender"></div>
        <div class="ambient ambient-peach"></div>
        <div class="ambient ambient-lilac"></div>
    </div>
    <div class="dashboard-layout">
        <aside class="sidebar surface" id="student-sidebar" aria-label="Admin workspace">
            <div class="sidebar-header">
                <a class="brand" href="index.php"><img src="assets/logo.svg" width="40" height="38" alt=""><strong>SIMS</strong></a>
                <button class="icon-button sidebar-close" type="button" aria-label="Close menu"><svg class="icon"><use href="#icon-close"/></svg></button>
            </div>
            <nav class="sidebar-nav" aria-label="Admin navigation">
                <?php foreach ($tabs as $key => $label): ?>
                    <a href="admin-dashboard.php?tab=<?= e($key) ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><svg class="icon"><use href="#icon-<?= $key === 'overview' ? 'home' : 'file' ?>"/></svg><?= e($label) ?></a>
                <?php endforeach; ?>
                <a href="admin-companies.php"><svg class="icon"><use href="#icon-user"/></svg>Company Approvals</a>
                <a href="index.php"><svg class="icon"><use href="#icon-home"/></svg>Website Home</a>
            </nav>
            <div class="sidebar-bottom">
                <p>Review registrations.<br>Support new opportunities.</p>
                <form action="logout.php" method="post">
                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                    <button type="submit"><svg class="icon"><use href="#icon-logout"/></svg>Logout</button>
                </form>
            </div>
        </aside>
        <div class="sidebar-overlay" hidden></div>

        <main class="workspace applications-workspace" id="main">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="index.php">← Back to home</a>
                <div class="account-chip surface">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($admin["full_name"], 0, 1))) ?></span>
                    <span class="account-copy"><strong><?= e($admin["full_name"]) ?></strong><small>Administrator</small></span>
                </div>
            </header>


            <section class="welcome">
                <p class="eyebrow">ADMIN WORKSPACE</p>
                <h1><?= e($tabs[$tab]) ?></h1>
                <p>Support students, review companies and manage internship opportunities.</p>
            </section>
            <?php if ($notice): ?><p class="profile-success" role="status"><?= e($notice) ?></p><?php endif; ?>
            <?php if ($tab === 'overview'): ?>
                <section class="stats admin-summary-stats" aria-label="Platform overview">
                    <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-user"/></svg></span><div><strong><?= $userCounts["total"] ?></strong><p>Registered users</p></div></article>
                    <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-home"/></svg></span><div><strong><?= $companyCounts["total"] ?></strong><p>Companies</p></div></article>
                    <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span><div><strong><?= $vacancyCounts["total"] ?></strong><p>Internships</p></div></article>
                    <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-check"/></svg></span><div><strong><?= $applicationCounts["total"] ?></strong><p>Applications</p></div></article>
                </section>
                <div class="admin-overview-grid">
                    <section class="panel surface">
                        <div class="section-heading"><h2>Application progress</h2></div>
                        <?php while ($row = $statusCounts->fetch_assoc()): ?>
                            <div class="admin-progress-row">
                                <div><span class="status status-<?= e(strtolower($row["status"])) ?>"><?= e($row["status"]) ?></span><strong><?= e($row["total"]) ?></strong></div>
                                <div class="admin-progress-track" aria-hidden="true"><span style="width: <?= $applicationCounts["total"] > 0 ? round(100 * $row["total"] / $applicationCounts["total"]) : 0 ?>%"></span></div>
                            </div>
                        <?php endwhile; ?>
                        <?php if ($applicationCounts["total"] == 0): ?><p class="profile-field-help">Application progress will appear once students start applying.</p><?php endif; ?>
                    </section>
                    <section class="panel surface">
                        <div class="section-heading"><h2>Quick actions</h2></div>
                        <div class="admin-quick-links">
                            <a href="admin-companies.php">Review companies <strong><?= $companyCounts["pending"] ?> pending →</strong></a>
                            <a href="admin-dashboard.php?tab=users">Manage accounts <strong><?= $userCounts["active"] ?> active →</strong></a>
                            <a href="admin-dashboard.php?tab=vacancies">Manage vacancies <strong>Review listings →</strong></a>
                            <a href="admin-dashboard.php?tab=applications">Review applications <strong><?= $applicationCounts["accepted"] ?> accepted →</strong></a>
                        </div>
                    </section>
                </div>
                <section class="panel surface admin-recent">
                    <div class="section-heading"><h2>Recent registrations</h2><a href="admin-dashboard.php?export=summary">Download summary CSV</a></div>
                    <?php while ($row = $recentUsers->fetch_assoc()): ?>
                        <div class="admin-registration"><strong><?= e($row["full_name"]) ?></strong><span><?= e(ucfirst($row["role"])) ?></span><span><?= e(date('d M Y', strtotime($row["created_at"]))) ?></span></div>
                    <?php endwhile; ?>
                    <?php if ($recentUsers->num_rows === 0): ?><p class="profile-field-help">No registered accounts yet.</p><?php endif; ?>
                </section>
            <?php else: ?>
                <form class="applications-filter surface admin-filter" method="get" action="admin-dashboard.php" role="search">
                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                    <?php if ($tab === 'applications' && $internshipId): ?><input type="hidden" name="internship" value="<?= e($internshipId) ?>"><?php endif; ?>
                    <div class="profile-field">
                        <label for="admin-search">Search records</label>
                        <input id="admin-search" name="keyword" maxlength="100" value="<?= e($keyword) ?>" placeholder="Name, role title or company">
                    </div>
                    <div>
                        <label for="admin-status">Status</label>
                        <select id="admin-status" name="status">
                            <option value="">All statuses</option>
                            <?php foreach ($filterOptions[$tab] as $option): ?>
                                <option value="<?= e($option) ?>" <?= $option === $filter ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="filter-submit" type="submit">Search</button>
                    <a class="clear-filters" href="admin-dashboard.php?tab=<?= e($tab) ?>">Clear</a>
                    <p class="applications-count"><?= min($records->num_rows, $pageSize) ?> records on this page</p>
                </form>
                <section class="applications-list" aria-label="System records">
                    <?php $shown = 0; ?>
                    <?php while ($shown < $pageSize && ($record = $records->fetch_assoc())): ?>
                        <?php $shown++; ?>
                        <?php if ($tab === 'users'): ?>
                            <article class="panel surface application-card" id="record-<?= e($record["user_id"]) ?>">
                                <div class="application-card-header"><div><h2><?= e($record["full_name"]) ?></h2><p class="profile-field-help"><?= e($record["email"]) ?></p></div><span class="status <?= $record['status'] === 'Active' ? 'status-shortlisted' : 'status-rejected' ?>"><?= e($record["status"]) ?></span></div>
                                <p class="profile-form-intro"><?= e(ucfirst($record["role"])) ?> · Joined <?= e(date('d M Y', strtotime($record["created_at"]))) ?></p>
                                <form method="post" action="admin-dashboard.php" class="vacancy-actions">
                                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                    <input type="hidden" name="action" value="user">
                                    <input type="hidden" name="record_id" value="<?= e($record["user_id"]) ?>">
                                    <input type="hidden" name="expected_status" value="<?= e($record["status"]) ?>">
                                    <input type="hidden" name="new_status" value="<?= $record["status"] === 'Active' ? 'Inactive' : 'Active' ?>">
                                    <button class="<?= $record["status"] === 'Active' ? 'admin-reject-button' : 'filter-submit' ?>" type="submit"><?= $record["status"] === 'Active' ? 'Deactivate account' : 'Activate account' ?></button>
                                    <p class="profile-field-help">Inactive accounts cannot sign in or use protected pages.</p>
                                </form>
                            </article>
                        <?php elseif ($tab === 'vacancies'): ?>
                            <article class="panel surface application-card" id="record-<?= e($record["internship_id"]) ?>">
                                <div class="application-card-header"><div><h2><?= e($record["title"]) ?></h2><p class="profile-field-help"><?= e($record["company_name"]) ?></p></div><span class="status status-reviewed"><?= e($record["status"] === 'Open' && $record["deadline"] < $today ? 'Expired' : $record["status"]) ?></span></div>
                                <div class="application-metadata"><p><?= e($record["location"]) ?></p><p>Deadline <?= e(date('d M Y', strtotime($record["deadline"]))) ?></p><p><?= e($record["application_count"]) ?> applications</p></div>
                                <details class="application-letter"><summary>View description</summary><p><?= nl2br(e($record["description"])) ?></p></details>
                                <div class="vacancy-actions">
                                    <a class="profile-cancel" href="admin-dashboard.php?tab=applications&amp;internship=<?= e($record["internship_id"]) ?>">Review applications</a>
                                    <?php
                                    $actions = [];
                                    if ($record["status"] === 'Open') {
                                        $actions["Closed"] = "Close vacancy";
                                    }
                                    if ($record["status"] !== 'Archived') {
                                        $actions["Archived"] = "Archive vacancy";
                                    } else {
                                        $actions["Closed"] = "Restore as closed";
                                    }
                                    if ($record["status"] === 'Closed' && $record["deadline"] >= $today && $record["approval_status"] === 'Approved' && $record["account_status"] === 'Active') {
                                        $actions["Open"] = "Reopen vacancy";
                                    }
                                    ?>
                                    <?php foreach ($actions as $value => $label): ?>
                                        <form method="post" action="admin-dashboard.php">
                                            <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                            <input type="hidden" name="action" value="vacancy">
                                            <input type="hidden" name="record_id" value="<?= e($record["internship_id"]) ?>">
                                            <input type="hidden" name="expected_status" value="<?= e($record["status"]) ?>">
                                            <input type="hidden" name="new_status" value="<?= e($value) ?>">
                                            <button class="<?= $value === 'Archived' ? 'admin-reject-button' : 'filter-submit' ?>" type="submit"><?= e($label) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                        <?php elseif ($tab === 'applications'): ?>
                            <article class="panel surface application-card" id="record-<?= e($record["application_id"]) ?>">
                                <div class="application-card-header"><div><h2><?= e($record["full_name"]) ?></h2><p class="profile-field-help"><?= e($record["title"]) ?> · <?= e($record["company_name"]) ?></p></div><span class="status status-<?= e(strtolower($record["status"])) ?>"><?= e($record["status"]) ?></span></div>
                                <div class="application-metadata"><p>Application #<?= e($record["application_id"]) ?></p><p>Applied <?= e(date('d M Y', strtotime($record["applied_at"]))) ?></p></div>
                                <dl class="company-details-grid company-applicant-details">
                                    <div><dt>Email address</dt><dd><?= e($record["email"]) ?></dd></div>
                                    <div><dt>Contact number</dt><dd><?= $record["phone"] !== '' ? e($record["phone"]) : 'Not provided' ?></dd></div>
                                    <div><dt>Field of interest</dt><dd><?= $record["field_of_interest"] !== '' ? e($record["field_of_interest"]) : 'Not provided' ?></dd></div>
                                    <div><dt>Education status</dt><dd><?= $record["education_status"] !== '' ? e($record["education_status"]) : 'Not provided' ?></dd></div>
                                </dl>
                                <details class="application-letter"><summary>Applicant introduction and cover letter</summary><p><?= trim((string) $record["about_me"]) !== '' ? nl2br(e($record["about_me"])) : 'No introduction provided.' ?></p><p><?= trim((string) $record["skills"]) !== '' ? e($record["skills"]) : 'No skills provided.' ?></p><p><?= trim((string) $record["cover_letter"]) !== '' ? nl2br(e($record["cover_letter"])) : 'No cover letter provided.' ?></p></details>
                                <?php if (!empty($record["cv_path"])): ?>
                                    <div class="vacancy-actions"><a class="profile-cancel" href="download-cv.php?id=<?= e($record["application_id"]) ?>">Download applicant CV</a></div>
                                <?php else: ?><p class="profile-field-help">No CV attached.</p><?php endif; ?>
                                <form class="company-review-form" method="post" action="admin-dashboard.php">
                                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                    <input type="hidden" name="action" value="application">
                                    <input type="hidden" name="record_id" value="<?= e($record["application_id"]) ?>">
                                    <input type="hidden" name="expected_status" value="<?= e($record["status"]) ?>">
                                    <div class="profile-field"><label for="application-status-<?= e($record["application_id"]) ?>">Application status</label><select id="application-status-<?= e($record["application_id"]) ?>" name="new_status">
                                        <?php foreach ($applicationStatuses as $option): ?><option value="<?= e($option) ?>" <?= $record["status"] === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
                                    </select></div>
                                    <button class="filter-submit" type="submit">Save status</button>
                                    <p>Students can see the updated status in My Applications.</p>
                                </form>
                            </article>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    <?php if ($records->num_rows === 0): ?><div class="panel surface empty-state"><h2>No records found</h2><p>Try another keyword or status.</p></div><?php endif; ?>
                </section>
                <nav class="record-pagination" aria-label="Record pages">
                    <?php if ($page > 1): ?>
                        <a class="profile-cancel" href="admin-dashboard.php?<?= e(http_build_query(["tab" => $tab, "keyword" => $keyword, "status" => $filter, "internship" => $internshipId, "page" => $page - 1])) ?>">← Previous</a>
                    <?php endif; ?>
                    <span aria-current="page">Page <?= $page ?></span>
                    <?php if ($records->num_rows > $pageSize): ?>
                        <a class="profile-cancel" href="admin-dashboard.php?<?= e(http_build_query(["tab" => $tab, "keyword" => $keyword, "status" => $filter, "internship" => $internshipId, "page" => $page + 1])) ?>">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
