<?php
require "session.php";
require "db.php";
date_default_timezone_set("Asia/Colombo");

$user = null;
if (isset($_SESSION["user_id"])) {
    $stmt = $conn->prepare("SELECT u.full_name, u.role, s.photo_path FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? AND u.status = 'Active'");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        unset($_SESSION["user_id"], $_SESSION["role"]);
    }
}

$keyword = "";
if (isset($_GET["keyword"]) && is_string($_GET["keyword"])) {
    $keyword = mb_substr(trim($_GET["keyword"]), 0, 100);
}
$companyId = 0;
if (isset($_GET["id"])) {
    $companyId = is_string($_GET["id"]) ? filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) : false;
    if ($companyId === false) {
        http_response_code(404);
        exit("Company not available.");
    }
}
$today = date("Y-m-d");
$search = "%" . $keyword . "%";
$stmt = $conn->prepare("SELECT c.company_id, c.company_name, c.description, c.website,
                        (SELECT COUNT(*) FROM internships i WHERE i.company_id = c.company_id
                        AND i.status = 'Open' AND i.deadline >= ?) AS open_count
                        FROM companies c JOIN users u ON u.user_id = c.user_id
                        WHERE c.approval_status = 'Approved' AND u.status = 'Active'
                        AND (? = 0 OR c.company_id = ?)
                        AND (c.company_name LIKE ? OR c.description LIKE ?)
                        ORDER BY c.company_name");
$stmt->bind_param("siiss", $today, $companyId, $companyId, $search, $search);
$stmt->execute();
$companies = $stmt->get_result();
if ($companyId && $companies->num_rows === 0) {
    http_response_code(404);
    exit("Company not available.");
}
$vacancies = null;
if ($companyId) {
    $stmt = $conn->prepare("SELECT internship_id, title, category, location, deadline FROM internships
                            WHERE company_id = ? AND status = 'Open' AND deadline >= ?
                            ORDER BY created_at DESC, internship_id DESC");
    $stmt->bind_param("is", $companyId, $today);
    $stmt->execute();
    $vacancies = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Companies | SIMS</title>
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
    <a class="skip-link" href="#main">Skip to companies</a>
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
                    <a href="<?= $user["role"] === "student" ? "student-profile.php" : "account.php" ?>"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
                <?php else: ?>
                    <a href="signin.php"><svg class="icon"><use href="#icon-user"/></svg>Sign In / Sign Up</a>
                <?php endif; ?>
                <a href="companies.php" aria-current="page"><svg class="icon"><use href="#icon-user"/></svg>Companies</a>
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

        <main id="main" class="workspace listing-workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="index.php">← Back to home</a>
                <?php if ($user): ?>
                    <a class="account-chip surface" href="<?= $user["role"] === "student" ? "student-profile.php" : "account.php" ?>" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <section class="welcome">
                <p class="eyebrow">EXPLORE YOUR NEXT CHAPTER</p>
                <h1>Discover companies</h1>
                <p>Meet the companies offering your next learning opportunity.</p>
            </section>


            <?php if (!$companyId): ?>
                <form class="applications-filter surface" method="get" action="companies.php" role="search">
                    <div class="profile-field">
                        <label for="company-search">Company name or keyword</label>
                        <input id="company-search" name="keyword" maxlength="100" value="<?= e($keyword) ?>" placeholder="Search companies">
                    </div>
                    <button class="filter-submit" type="submit">Search companies</button>
                    <?php if ($keyword !== ''): ?><a class="clear-filters" href="companies.php">Clear</a><?php endif; ?>
                    <p class="applications-count"><?= $companies->num_rows ?> companies</p>
                </form>
            <?php else: ?>
                <p class="directory-back"><a href="companies.php">← All companies</a></p>
            <?php endif; ?>
            <section class="company-directory" aria-label="Company profiles">
                <?php while ($company = $companies->fetch_assoc()): ?>
                    <article class="panel surface directory-card">
                        <span class="company-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($company["company_name"], 0, 2))) ?></span>
                        <h2><?= e($company["company_name"]) ?></h2>
                        <span class="status status-shortlisted">Approved company</span>
                        <p class="directory-description"><?= trim((string) $company["description"]) !== '' ? nl2br(e($companyId ? $company["description"] : mb_strimwidth($company["description"], 0, 220, '…'))) : 'Explore this company’s available internships.' ?></p>
                        <p><strong><?= e($company["open_count"]) ?></strong> open internships</p>
                        <div class="vacancy-actions">
                            <?php if (!$companyId): ?><a class="filter-submit company-post-link" href="companies.php?id=<?= e($company["company_id"]) ?>">View company</a><?php endif; ?>
                            <?php if (filter_var($company["website"], FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($company["website"], PHP_URL_SCHEME)), ['http', 'https'], true)): ?>
                                <a class="profile-cancel" href="<?= e($company["website"]) ?>" target="_blank" rel="noopener noreferrer">Visit website ↗</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
                <?php if ($companies->num_rows === 0): ?>
                    <div class="panel surface empty-state"><h2>No companies found</h2><p>Try another company name or check back for new registrations.</p></div>
                <?php endif; ?>
            </section>
            <?php if ($vacancies): ?>
                <section class="panel surface directory-vacancies">
                    <div class="section-heading"><h2>Available internships</h2></div>
                    <div class="internship-grid">
                        <?php while ($vacancy = $vacancies->fetch_assoc()): ?>
                            <article class="internship-card">
                                <h3><?= e($vacancy["title"]) ?></h3>
                                <p class="company-vacancy-category"><?= e($vacancy["category"]) ?></p>
                                <p class="internship-meta"><?= e($vacancy["location"]) ?></p>
                                <p class="internship-meta">Deadline <?= e(date('d M Y', strtotime($vacancy["deadline"]))) ?></p>
                                <a class="company-listing-link" href="internship-details.php?id=<?= e($vacancy["internship_id"]) ?>">View internship →</a>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    <?php if ($vacancies->num_rows === 0): ?><p class="profile-field-help">This company has no open internships at the moment.</p><?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
