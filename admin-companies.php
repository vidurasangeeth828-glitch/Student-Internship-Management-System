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

$statuses = ["Pending", "Approved", "Rejected"];
$status = "Pending";
if (isset($_GET["status"]) && is_string($_GET["status"]) && ($_GET["status"] === "" || in_array($_GET["status"], $statuses, true))) {
    $status = $_GET["status"];
}

$counts = $conn->query("SELECT COUNT(*) AS total,
                        COALESCE(SUM(c.approval_status = 'Pending'), 0) AS pending,
                        COALESCE(SUM(c.approval_status = 'Approved'), 0) AS approved,
                        COALESCE(SUM(c.approval_status = 'Rejected'), 0) AS rejected
                        FROM companies c JOIN users u ON u.user_id = c.user_id
                        WHERE u.role = 'company'")->fetch_assoc();

$stmt = $conn->prepare("SELECT c.*, u.email, u.status AS account_status, u.created_at
                        FROM companies c JOIN users u ON u.user_id = c.user_id
                        WHERE u.role = 'company' AND (? = '' OR c.approval_status = ?)
                        ORDER BY u.created_at DESC, c.company_id DESC");
$stmt->bind_param("ss", $status, $status);
$stmt->execute();
$companies = $stmt->get_result();
$notice = $_SESSION["admin_company_notice"] ?? null;
unset($_SESSION["admin_company_notice"]);
$statusClasses = ["Pending" => "status-reviewed", "Approved" => "status-shortlisted", "Rejected" => "status-rejected"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Approvals | SIMS Admin</title>
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

    <a class="skip-link" href="#main">Skip to company approvals</a>
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
                <a href="admin-companies.php" aria-current="page"><svg class="icon"><use href="#icon-user"/></svg>Company Approvals</a>
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
                <h1>Company approvals</h1>
                <p>Review company registrations before allowing them to publish internships.</p>
            </section>

            <section class="stats" aria-label="Company approval overview">
                <article class="stat surface admin-stat-pending"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-clock"/></svg></span><div><strong><?= $counts["pending"] ?></strong><p>Pending review</p></div></article>
                <article class="stat surface"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-check"/></svg></span><div><strong><?= $counts["approved"] ?></strong><p>Approved</p></div></article>
                <article class="stat surface admin-stat-rejected"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-close"/></svg></span><div><strong><?= $counts["rejected"] ?></strong><p>Rejected</p></div></article>
            </section>

            <?php if ($notice): ?>
                <p class="<?= $notice["error"] ? 'profile-error-summary' : 'profile-success' ?>" role="status"><?= e($notice["message"]) ?></p>
            <?php endif; ?>

            <form class="applications-filter surface" action="admin-companies.php" method="get">
                <div>
                    <label for="company-status">Approval status</label>
                    <select id="company-status" name="status">
                        <option value="" <?= $status === "" ? 'selected' : '' ?>>All companies</option>
                        <?php foreach ($statuses as $item): ?>
                            <option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="filter-submit" type="submit">Filter companies</button>
                <p class="applications-count"><?= $companies->num_rows ?> <?= $companies->num_rows === 1 ? 'company' : 'companies' ?></p>
            </form>

            <section class="admin-company-list" aria-label="Companies to review">
                <?php if ($companies->num_rows > 0): ?>
                    <?php while ($company = $companies->fetch_assoc()): ?>
                        <article class="panel surface admin-company-card" aria-labelledby="company-<?= e($company["company_id"]) ?>">
                            <div class="application-card-header">
                                <div class="application-role">
                                    <span class="company-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($company["company_name"], 0, 2))) ?></span>
                                    <div>
                                        <h2 id="company-<?= e($company["company_id"]) ?>"><?= e($company["company_name"]) ?></h2>
                                        <p>Registered <?= e(date('d M Y', strtotime($company["created_at"]))) ?></p>
                                    </div>
                                </div>
                                <span class="status <?= e($statusClasses[$company["approval_status"]]) ?>"><?= e($company["approval_status"]) ?></span>
                            </div>
                            <dl class="company-details-grid admin-company-details">
                                <div><dt>Email address</dt><dd><?= e($company["email"]) ?></dd></div>
                                <div><dt>Contact number</dt><dd><?= e($company["phone"]) ?></dd></div>
                                <div><dt>Business registration number</dt><dd><?= $company["business_registration_no"] !== '' ? e($company["business_registration_no"]) : 'Not provided' ?></dd></div>
                                <div><dt>Account status</dt><dd><?= e($company["account_status"]) ?></dd></div>
                            </dl>
                            <?php if (trim((string) $company["description"]) !== '' || $company["contact_person"] !== '' || $company["website"] !== ''): ?>
                                <details class="admin-company-extra">
                                    <summary>More company information</summary>
                                    <?php if ($company["contact_person"] !== ''): ?><p><strong>Contact person:</strong> <?= e($company["contact_person"]) ?></p><?php endif; ?>
                                    <?php if ($company["website"] !== ''): ?><p><strong>Website:</strong> <?= e($company["website"]) ?></p><?php endif; ?>
                                    <?php if (trim((string) $company["description"]) !== ''): ?><p class="admin-company-description"><?= e($company["description"]) ?></p><?php endif; ?>
                                </details>
                            <?php endif; ?>

                            <?php if ($company["account_status"] === 'Active'): ?>
                                <form action="admin-company-process.php" method="post" class="admin-company-actions">
                                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                    <input type="hidden" name="company_id" value="<?= e($company["company_id"]) ?>">
                                    <input type="hidden" name="expected_status" value="<?= e($company["approval_status"]) ?>">
                                    <input type="hidden" name="return_status" value="<?= e($status) ?>">
                                    <p>Review the details before choosing a decision.</p>
                                    <?php if ($company["approval_status"] !== 'Approved'): ?>
                                        <button class="filter-submit" type="submit" name="decision" value="Approved" aria-label="Approve <?= e($company["company_name"]) ?>">Approve <svg class="icon small-icon" aria-hidden="true"><use href="#icon-check"/></svg></button>
                                    <?php endif; ?>
                                    <?php if ($company["approval_status"] !== 'Rejected'): ?>
                                        <button class="admin-reject-button" type="submit" name="decision" value="Rejected" aria-label="Reject <?= e($company["company_name"]) ?>">Reject <svg class="icon small-icon" aria-hidden="true"><use href="#icon-close"/></svg></button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <p class="admin-inactive-note">Approval actions are unavailable while this account is inactive.</p>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="panel surface empty-state applications-empty">
                        <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-check"/></svg></span>
                        <h2><?= $status === 'Pending' ? 'No companies awaiting review' : 'No companies found' ?></h2>
                        <p>Company registrations with the selected approval status will appear here.</p>
                        <?php if ($status !== ''): ?><a href="admin-companies.php?status=">View all companies</a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
