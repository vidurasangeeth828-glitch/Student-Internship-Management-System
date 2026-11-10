<?php
require "session.php";
require "db.php";
date_default_timezone_set("Asia/Colombo");

$user = null;
if (isset($_SESSION["user_id"])) {
 
    $stmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ? AND status = 'Active'");
=======
    $stmt = $conn->prepare("SELECT u.full_name, u.role, s.photo_path FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? AND u.status = 'Active'");

    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        unset($_SESSION["user_id"], $_SESSION["role"]);
    }
}

$categories = [
    "Software Development",
    "Data & Analytics",
    "Design & Creative",
    "Marketing",
    "Business & Management",
    "Engineering",
    "Finance & Accounting",
    "Other"
];

$keyword = "";
$location = "";
$category = "";

if (isset($_GET["keyword"]) && is_string($_GET["keyword"])) {
    $keyword = mb_substr(trim($_GET["keyword"]), 0, 100);
}
if (isset($_GET["location"]) && is_string($_GET["location"])) {
    $location = mb_substr(trim($_GET["location"]), 0, 80);
}
if (isset($_GET["category"]) && is_string($_GET["category"]) && in_array($_GET["category"], $categories, true)) {
    $category = $_GET["category"];
}

$today = date("Y-m-d");
$search = "%" . $keyword . "%";
$placeSearch = "%" . $location . "%";

$stmt = $conn->prepare("SELECT i.*, c.company_name
                        FROM internships i
                        JOIN companies c ON c.company_id = i.company_id
                        JOIN users u ON u.user_id = c.user_id
                        WHERE i.status = 'Open' AND i.deadline >= ?
                        AND c.approval_status = 'Approved' AND u.status = 'Active'
                        AND (i.title LIKE ? OR c.company_name LIKE ? OR i.description LIKE ?)
                        AND i.location LIKE ?
                        AND (? = '' OR i.category = ?)
                        ORDER BY i.created_at DESC, i.internship_id DESC");
$stmt->bind_param("sssssss", $today, $search, $search, $search, $placeSearch, $category, $category);
$stmt->execute();
$internships = $stmt->get_result();
$total = $internships->num_rows;

$stmt = $conn->prepare("SELECT DISTINCT i.location
                        FROM internships i
                        JOIN companies c ON c.company_id = i.company_id
                        JOIN users u ON u.user_id = c.user_id
                        WHERE i.status = 'Open' AND i.deadline >= ?
                        AND c.approval_status = 'Approved' AND u.status = 'Active'
                        ORDER BY i.location");
$stmt->bind_param("s", $today);
$stmt->execute();
$locations = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Internships | SIMS</title>
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
    <a class="skip-link" href="#main">Skip to internships</a>
 Nuzrath
    <div class="ambient ambient-purple" aria-hidden="true"></div>
    <div class="ambient ambient-lavender" aria-hidden="true"></div>
    <div class="ambient ambient-peach" aria-hidden="true"></div>
    <div class="ambient ambient-lilac" aria-hidden="true"></div>

    <div class="applications-glows" aria-hidden="true">
        <div class="ambient ambient-purple" aria-hidden="true"></div>
        <div class="ambient ambient-lavender" aria-hidden="true"></div>
        <div class="ambient ambient-peach" aria-hidden="true"></div>
        <div class="ambient ambient-lilac" aria-hidden="true"></div>
    </div>
 main

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
                <a href="internships.php" aria-current="page"><svg class="icon"><use href="#icon-search"/></svg>Browse Internships</a>
                <?php if ($user && $user["role"] === "student"): ?>
                    <a href="my-applications.php"><svg class="icon"><use href="#icon-file"/></svg>My Applications</a>
                <?php endif; ?>
<?php if ($user): ?>
 
                    <a href="account.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>

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

        <main id="main" class="workspace listing-workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="index.php">← Back to home</a>
                <?php if ($user): ?>
 
                    <a class="account-chip surface" href="account.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>

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
                <h1>Browse internships</h1>
                <p>Find an opportunity that matches your interests.</p>
            </section>

            <form class="listing-filters surface" action="internships.php" method="get" role="search">
                <label for="keyword">Job title or keyword
                    <input id="keyword" name="keyword" maxlength="100" placeholder="Role, company or skill" value="<?= e($keyword) ?>">
                </label>
                <label for="location">Location
                    <input id="location" name="location" maxlength="80" list="location-options" placeholder="All locations" value="<?= e($location) ?>">
                </label>
                <datalist id="location-options">
                    <?php while ($place = $locations->fetch_assoc()): ?>
                        <option value="<?= e($place["location"]) ?>">
                    <?php endwhile; ?>
                </datalist>
                <label for="category">Category
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= e($item) ?>" <?= $category === $item ? "selected" : "" ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="filter-submit" type="submit"><svg class="icon small-icon"><use href="#icon-search"/></svg>Search</button>
                <a class="clear-filters" href="internships.php">Clear</a>
            </form>

            <section class="listing-results" aria-labelledby="results-title">
                <div class="section-heading">
                    <h2 id="results-title"><?= $total ?> <?= $total === 1 ? "internship" : "internships" ?> found</h2>
                    <span class="listing-caption">Open opportunities</span>
                </div>
                <?php if ($total > 0): ?>
                    <div class="internship-grid">
                        <?php while ($internship = $internships->fetch_assoc()): ?>
                            <article class="internship-card surface listing-card">
                                <div class="listing-card-top">
                                    <span class="company-avatar"><?= e(mb_strtoupper(mb_substr($internship["company_name"], 0, 2))) ?></span>
                                    <span class="open-label">Open</span>
                                </div>
                                <h3><?= e($internship["title"]) ?></h3>
                                <p class="listing-company"><?= e($internship["company_name"]) ?></p>
                                <span class="category-label"><?= e($internship["category"]) ?></span>
                                <p class="listing-excerpt"><?= e($internship["description"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-pin"/></svg><?= e($internship["location"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-calendar"/></svg>Apply by <strong><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></strong></p>
                                <a class="filter-submit" href="internship-details.php?id=<?= e($internship["internship_id"]) ?>" aria-label="View details: <?= e($internship["title"]) ?>">View details <span aria-hidden="true">→</span></a>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state surface listing-empty">
                        <span class="empty-icon"><svg class="icon"><use href="#icon-search"/></svg></span>
                        <?php if ($keyword !== "" || $location !== "" || $category !== ""): ?>
                            <h3>No internships match your search</h3>
                            <p>Try another keyword or location, or choose a different category.</p>
                            <a href="internships.php">Clear filters</a>
                        <?php else: ?>
                            <h3>New opportunities are on their way</h3>
                            <p>Open internships from approved companies will appear here.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
=======
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

$categories = [
    "Software Development",
    "Data & Analytics",
    "Design & Creative",
    "Marketing",
    "Business & Management",
    "Engineering",
    "Finance & Accounting",
    "Other"
];

$keyword = "";
$location = "";
$category = "";

if (isset($_GET["keyword"]) && is_string($_GET["keyword"])) {
    $keyword = mb_substr(trim($_GET["keyword"]), 0, 100);
}
if (isset($_GET["location"]) && is_string($_GET["location"])) {
    $location = mb_substr(trim($_GET["location"]), 0, 80);
}
if (isset($_GET["category"]) && is_string($_GET["category"]) && in_array($_GET["category"], $categories, true)) {
    $category = $_GET["category"];
}

$today = date("Y-m-d");
$search = "%" . $keyword . "%";
$placeSearch = "%" . $location . "%";

$stmt = $conn->prepare("SELECT i.*, c.company_name
                        FROM internships i
                        JOIN companies c ON c.company_id = i.company_id
                        JOIN users u ON u.user_id = c.user_id
                        WHERE i.status = 'Open' AND i.deadline >= ?
                        AND c.approval_status = 'Approved' AND u.status = 'Active'
                        AND (i.title LIKE ? OR c.company_name LIKE ? OR i.description LIKE ?)
                        AND i.location LIKE ?
                        AND (? = '' OR i.category = ?)
                        ORDER BY i.created_at DESC, i.internship_id DESC");
$stmt->bind_param("sssssss", $today, $search, $search, $search, $placeSearch, $category, $category);
$stmt->execute();
$internships = $stmt->get_result();
$total = $internships->num_rows;

$stmt = $conn->prepare("SELECT DISTINCT i.location
                        FROM internships i
                        JOIN companies c ON c.company_id = i.company_id
                        JOIN users u ON u.user_id = c.user_id
                        WHERE i.status = 'Open' AND i.deadline >= ?
                        AND c.approval_status = 'Approved' AND u.status = 'Active'
                        ORDER BY i.location");
$stmt->bind_param("s", $today);
$stmt->execute();
$locations = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Internships | SIMS</title>
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
    <a class="skip-link" href="#main">Skip to internships</a>
    <div class="ambient ambient-purple" aria-hidden="true"></div>
    <div class="ambient ambient-lavender" aria-hidden="true"></div>
    <div class="ambient ambient-peach" aria-hidden="true"></div>
    <div class="ambient ambient-lilac" aria-hidden="true"></div>

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
                <a href="internships.php" aria-current="page"><svg class="icon"><use href="#icon-search"/></svg>Browse Internships</a>
                <?php if ($user && $user["role"] === "student"): ?>
                    <a href="student-dashboard.php#recent-applications"><svg class="icon"><use href="#icon-file"/></svg>My Applications</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a href="account.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
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

        <main id="main" class="workspace listing-workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="index.php">← Back to home</a>
                <?php if ($user): ?>
                    <a class="account-chip surface" href="account.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <section class="welcome">
                <p class="eyebrow">EXPLORE YOUR NEXT CHAPTER</p>
                <h1>Browse internships</h1>
                <p>Find an opportunity that matches your interests.</p>
            </section>

            <form class="listing-filters surface" action="internships.php" method="get" role="search">
                <label for="keyword">Job title or keyword
                    <input id="keyword" name="keyword" maxlength="100" placeholder="Role, company or skill" value="<?= e($keyword) ?>">
                </label>
                <label for="location">Location
                    <input id="location" name="location" maxlength="80" list="location-options" placeholder="All locations" value="<?= e($location) ?>">
                </label>
                <datalist id="location-options">
                    <?php while ($place = $locations->fetch_assoc()): ?>
                        <option value="<?= e($place["location"]) ?>">
                    <?php endwhile; ?>
                </datalist>
                <label for="category">Category
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= e($item) ?>" <?= $category === $item ? "selected" : "" ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="filter-submit" type="submit"><svg class="icon small-icon"><use href="#icon-search"/></svg>Search</button>
                <a class="clear-filters" href="internships.php">Clear</a>
            </form>

            <section class="listing-results" aria-labelledby="results-title">
                <div class="section-heading">
                    <h2 id="results-title"><?= $total ?> <?= $total === 1 ? "internship" : "internships" ?> found</h2>
                    <span class="listing-caption">Open opportunities</span>
                </div>
                <?php if ($total > 0): ?>
                    <div class="internship-grid">
                        <?php while ($internship = $internships->fetch_assoc()): ?>
                            <article class="internship-card surface listing-card">
                                <div class="listing-card-top">
                                    <span class="company-avatar"><?= e(mb_strtoupper(mb_substr($internship["company_name"], 0, 2))) ?></span>
                                    <span class="open-label">Open</span>
                                </div>
                                <h3><?= e($internship["title"]) ?></h3>
                                <p class="listing-company"><?= e($internship["company_name"]) ?></p>
                                <span class="category-label"><?= e($internship["category"]) ?></span>
                                <p class="listing-excerpt"><?= e($internship["description"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-pin"/></svg><?= e($internship["location"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-calendar"/></svg>Apply by <strong><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></strong></p>
                                <a class="filter-submit" href="internship-details.php?id=<?= e($internship["internship_id"]) ?>" aria-label="View details: <?= e($internship["title"]) ?>">View details <span aria-hidden="true">→</span></a>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state surface listing-empty">
                        <span class="empty-icon"><svg class="icon"><use href="#icon-search"/></svg></span>
                        <?php if ($keyword !== "" || $location !== "" || $category !== ""): ?>
                            <h3>No internships match your search</h3>
                            <p>Try another keyword or location, or choose a different category.</p>
                            <a href="internships.php">Clear filters</a>
                        <?php else: ?>
                            <h3>New opportunities are on their way</h3>
                            <p>Open internships from approved companies will appear here.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>

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

$categories = [
    "Software Development",
    "Data & Analytics",
    "Design & Creative",
    "Marketing",
    "Business & Management",
    "Engineering",
    "Finance & Accounting",
    "Other"
];

$keyword = "";
$location = "";
$category = "";

if (isset($_GET["keyword"]) && is_string($_GET["keyword"])) {
    $keyword = mb_substr(trim($_GET["keyword"]), 0, 100);
}
if (isset($_GET["location"]) && is_string($_GET["location"])) {
    $location = mb_substr(trim($_GET["location"]), 0, 80);
}
if (isset($_GET["category"]) && is_string($_GET["category"]) && in_array($_GET["category"], $categories, true)) {
    $category = $_GET["category"];
}

$today = date("Y-m-d");
$search = "%" . $keyword . "%";
$placeSearch = "%" . $location . "%";

$stmt = $conn->prepare("SELECT i.*, c.company_name
                        FROM internships i
                        JOIN companies c ON c.company_id = i.company_id
                        JOIN users u ON u.user_id = c.user_id
                        WHERE i.status = 'Open' AND i.deadline >= ?
                        AND c.approval_status = 'Approved' AND u.status = 'Active'
                        AND (i.title LIKE ? OR c.company_name LIKE ? OR i.description LIKE ?)
                        AND i.location LIKE ?
                        AND (? = '' OR i.category = ?)
                        ORDER BY i.created_at DESC, i.internship_id DESC");
$stmt->bind_param("sssssss", $today, $search, $search, $search, $placeSearch, $category, $category);
$stmt->execute();
$internships = $stmt->get_result();
$total = $internships->num_rows;

$stmt = $conn->prepare("SELECT DISTINCT i.location
                        FROM internships i
                        JOIN companies c ON c.company_id = i.company_id
                        JOIN users u ON u.user_id = c.user_id
                        WHERE i.status = 'Open' AND i.deadline >= ?
                        AND c.approval_status = 'Approved' AND u.status = 'Active'
                        ORDER BY i.location");
$stmt->bind_param("s", $today);
$stmt->execute();
$locations = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Internships | SIMS</title>
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
    <a class="skip-link" href="#main">Skip to internships</a>
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
                <a href="internships.php" aria-current="page"><svg class="icon"><use href="#icon-search"/></svg>Browse Internships</a>
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
                <h1>Browse internships</h1>
                <p>Find an opportunity that matches your interests.</p>
            </section>

            <form class="listing-filters surface" action="internships.php" method="get" role="search">
                <label for="keyword">Job title or keyword
                    <input id="keyword" name="keyword" maxlength="100" placeholder="Role, company or skill" value="<?= e($keyword) ?>">
                </label>
                <label for="location">Location
                    <input id="location" name="location" maxlength="80" list="location-options" placeholder="All locations" value="<?= e($location) ?>">
                </label>
                <datalist id="location-options">
                    <?php while ($place = $locations->fetch_assoc()): ?>
                        <option value="<?= e($place["location"]) ?>">
                    <?php endwhile; ?>
                </datalist>
                <label for="category">Category
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= e($item) ?>" <?= $category === $item ? "selected" : "" ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="filter-submit" type="submit"><svg class="icon small-icon"><use href="#icon-search"/></svg>Search</button>
                <a class="clear-filters" href="internships.php">Clear</a>
            </form>

            <section class="listing-results" aria-labelledby="results-title">
                <div class="section-heading">
                    <h2 id="results-title"><?= $total ?> <?= $total === 1 ? "internship" : "internships" ?> found</h2>
                    <span class="listing-caption">Open opportunities</span>
                </div>
                <?php if ($total > 0): ?>
                    <div class="internship-grid">
                        <?php while ($internship = $internships->fetch_assoc()): ?>
                            <article class="internship-card surface listing-card">
                                <div class="listing-card-top">
                                    <span class="company-avatar"><?= e(mb_strtoupper(mb_substr($internship["company_name"], 0, 2))) ?></span>
                                    <span class="open-label">Open</span>
                                </div>
                                <h3><?= e($internship["title"]) ?></h3>
                                <p class="listing-company"><?= e($internship["company_name"]) ?></p>
                                <span class="category-label"><?= e($internship["category"]) ?></span>
                                <p class="listing-excerpt"><?= e($internship["description"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-pin"/></svg><?= e($internship["location"]) ?></p>
                                <p class="internship-meta"><svg class="icon small-icon"><use href="#icon-calendar"/></svg>Apply by <strong><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></strong></p>
                                <a class="filter-submit" href="internship-details.php?id=<?= e($internship["internship_id"]) ?>" aria-label="View details: <?= e($internship["title"]) ?>">View details <span aria-hidden="true">→</span></a>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state surface listing-empty">
                        <span class="empty-icon"><svg class="icon"><use href="#icon-search"/></svg></span>
                        <?php if ($keyword !== "" || $location !== "" || $category !== ""): ?>
                            <h3>No internships match your search</h3>
                            <p>Try another keyword or location, or choose a different category.</p>
                            <a href="internships.php">Clear filters</a>
                        <?php else: ?>
                            <h3>New opportunities are on their way</h3>
                            <p>Open internships from approved companies will appear here.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
