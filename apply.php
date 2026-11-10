<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    if (isset($_GET["id"]) && is_string($_GET["id"])) {
        $returnId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($returnId !== false) {
            $_SESSION["apply_after_login"] = $returnId;
        }
    }
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

 chamodya
=======

 Nuzrath
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, s.student_id

$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, s.student_id, s.photo_path
 main
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
    exit("Only students with an active profile can apply for internships.");
}

$internshipId = false;
if (isset($_GET["id"]) && is_string($_GET["id"])) {
    $internshipId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
}

$internship = null;
if ($internshipId !== false) {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT i.internship_id, i.title, i.category, i.location, i.deadline, c.company_name
                            FROM internships i
                            JOIN companies c ON c.company_id = i.company_id
                            JOIN users u ON u.user_id = c.user_id
                            WHERE i.internship_id = ? AND i.status = 'Open' AND i.deadline >= ?
                            AND c.approval_status = 'Approved' AND u.status = 'Active'");
    $stmt->bind_param("is", $internshipId, $today);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();
}

$application = null;
if ($internship) {
    $stmt = $conn->prepare("SELECT status, applied_at FROM applications WHERE student_id = ? AND internship_id = ?");
    $stmt->bind_param("ii", $user["student_id"], $internshipId);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
} else {
    http_response_code(404);
}

$error = "";
$coverLetter = "";
$success = false;
if (isset($_SESSION["apply_form"]) && $_SESSION["apply_form"]["internship_id"] === $internshipId) {
    $error = $_SESSION["apply_form"]["error"];
    $coverLetter = $_SESSION["apply_form"]["cover_letter"];
    unset($_SESSION["apply_form"]);
}
if (isset($_SESSION["applied_internship"]) && $_SESSION["applied_internship"] === $internshipId) {
    $success = true;
    unset($_SESSION["applied_internship"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply for internship | SIMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="internship-details.css">
    <link rel="stylesheet" href="apply.css">
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
    <a class="skip-link" href="#main">Skip to application</a>
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
 Nuzrath
                    <a href="account.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
=======
                    <a href="student-profile.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
 main
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
 Nuzrath
                    <a class="account-chip surface" href="account.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>

                    <a class="account-chip surface" href="student-profile.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
 main
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <?php if ($internship): ?>
                <section class="welcome">
                    <p class="eyebrow">TAKE YOUR NEXT STEP</p>
                    <h1><?= $application ? "Your application" : "Apply for this internship" ?></h1>
                    <p><?= $application ? "Follow your progress from your student dashboard." : "Share your CV and introduce yourself to the company." ?></p>
                </section>

                <div class="detail-columns apply-columns">
                    <section class="detail-section surface" aria-labelledby="form-heading">
                        <?php if ($application): ?>
                            <div class="application-result">
                                <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                                <h2 id="form-heading"><?= $success ? "Application submitted!" : "You have already applied" ?></h2>
                                <p <?= $success ? 'role="status"' : '' ?>><?= $success ? "Your CV and application have been saved successfully." : "An application for this internship is already saved to your account." ?></p>
                                <span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span>
                                <p>Applied on <?= e(date("d M Y", strtotime($application["applied_at"]))) ?></p>
                                <a class="detail-primary" href="my-applications.php">View my applications <span aria-hidden="true">→</span></a>
                            </div>
                        <?php else: ?>
                            <h2 id="form-heading">Your application</h2>
                            <?php if ($error !== ""): ?>
                                <div class="apply-error" role="alert"><strong>Application not submitted</strong><p><?= e($error) ?></p></div>
                            <?php endif; ?>
                            <div class="applicant-info">
 Nuzrath
                                <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>
=======
                                <span class="avatar" aria-hidden="true"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
main
                                <div><strong><?= e($user["full_name"]) ?></strong><p><?= e($user["email"]) ?></p></div>
                            </div>
                            <form class="apply-form" action="apply-process.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                <input type="hidden" name="internship_id" value="<?= e($internshipId) ?>">
                                <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

                                <div class="apply-field">
                                    <label for="cv">Upload your CV <span class="field-required">Required</span></label>
                                    <div class="cv-upload">
                                        <svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg>
                                        <p>Choose your latest CV</p>
                                        <input type="file" id="cv" name="cv" accept=".pdf,application/pdf" required aria-describedby="cv-help">
                                        <small id="cv-help">PDF only · Maximum 2 MB. Your CV will be attached to this application.</small>
                                    </div>
                                </div>

                                <div class="apply-field">
                                    <label for="cover-letter">Cover letter <span class="field-optional">Optional</span></label>
                                    <p id="cover-help" class="field-help">Tell the company why you are interested and what you can bring to the team. Maximum 2,000 characters.</p>
                                    <textarea id="cover-letter" name="cover_letter" rows="7" maxlength="2000" aria-describedby="cover-help" placeholder="I am interested in this internship because…"><?= e($coverLetter) ?></textarea>
                                </div>

                                <p class="submission-note">Check your CV before submitting. You can apply only once for this internship.</p>
                                <div class="apply-actions">
                                    <a class="detail-secondary" href="internship-details.php?id=<?= e($internshipId) ?>">Cancel</a>
                                    <button class="detail-primary" type="submit">Submit application <span aria-hidden="true">→</span></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>

                    <aside class="detail-summary surface" aria-labelledby="role-heading">
                        <p class="eyebrow">YOU ARE APPLYING FOR</p>
                        <h2 id="role-heading"><?= e($internship["title"]) ?></h2>
                        <p class="apply-company"><?= e($internship["company_name"]) ?></p>
                        <dl class="overview-list">
                            <div><dt>Location</dt><dd><?= e($internship["location"]) ?></dd></div>
                            <div><dt>Category</dt><dd><?= e($internship["category"]) ?></dd></div>
                            <div><dt>Application deadline</dt><dd class="deadline-date"><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></dd></div>
                        </dl>
                        <a class="detail-secondary" href="internship-details.php?id=<?= e($internshipId) ?>">View internship details</a>
                    </aside>
                </div>
            <?php else: ?>
                <section class="detail-unavailable surface">
                    <h1>This internship is unavailable</h1>
                    <p>It may have closed, expired, or is no longer accepting applications.</p>
                    <a class="detail-primary" href="internships.php">Browse open internships <span aria-hidden="true">→</span></a>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
=======
<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    if (isset($_GET["id"]) && is_string($_GET["id"])) {
        $returnId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($returnId !== false) {
            $_SESSION["apply_after_login"] = $returnId;
        }
    }
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, s.student_id
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
    exit("Only students with an active profile can apply for internships.");
}

$internshipId = false;
if (isset($_GET["id"]) && is_string($_GET["id"])) {
    $internshipId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
}

$internship = null;
if ($internshipId !== false) {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT i.internship_id, i.title, i.category, i.location, i.deadline, c.company_name
                            FROM internships i
                            JOIN companies c ON c.company_id = i.company_id
                            JOIN users u ON u.user_id = c.user_id
                            WHERE i.internship_id = ? AND i.status = 'Open' AND i.deadline >= ?
                            AND c.approval_status = 'Approved' AND u.status = 'Active'");
    $stmt->bind_param("is", $internshipId, $today);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();
}

$application = null;
if ($internship) {
    $stmt = $conn->prepare("SELECT status, applied_at FROM applications WHERE student_id = ? AND internship_id = ?");
    $stmt->bind_param("ii", $user["student_id"], $internshipId);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
} else {
    http_response_code(404);
}

$error = "";
$coverLetter = "";
$success = false;
if (isset($_SESSION["apply_form"]) && $_SESSION["apply_form"]["internship_id"] === $internshipId) {
    $error = $_SESSION["apply_form"]["error"];
    $coverLetter = $_SESSION["apply_form"]["cover_letter"];
    unset($_SESSION["apply_form"]);
}
if (isset($_SESSION["applied_internship"]) && $_SESSION["applied_internship"] === $internshipId) {
    $success = true;
    unset($_SESSION["applied_internship"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply for internship | SIMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="internship-details.css">
    <link rel="stylesheet" href="apply.css">
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
    <a class="skip-link" href="#main">Skip to application</a>
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

        <main id="main" class="workspace details-workspace">
            <header class="topbar">
                <button class="icon-button sidebar-open" type="button" aria-label="Open menu" aria-controls="student-sidebar" aria-expanded="false"><svg class="icon"><use href="#icon-menu"/></svg></button>
                <a class="back-to-home" href="internships.php">← Back to internships</a>
                <?php if ($user): ?>
                    <a class="account-chip surface" href="account.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <?php if ($internship): ?>
                <section class="welcome">
                    <p class="eyebrow">TAKE YOUR NEXT STEP</p>
                    <h1><?= $application ? "Your application" : "Apply for this internship" ?></h1>
                    <p><?= $application ? "Follow your progress from your student dashboard." : "Share your CV and introduce yourself to the company." ?></p>
                </section>

                <div class="detail-columns apply-columns">
                    <section class="detail-section surface" aria-labelledby="form-heading">
                        <?php if ($application): ?>
                            <div class="application-result">
                                <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                                <h2 id="form-heading"><?= $success ? "Application submitted!" : "You have already applied" ?></h2>
                                <p <?= $success ? 'role="status"' : '' ?>><?= $success ? "Your CV and application have been saved successfully." : "An application for this internship is already saved to your account." ?></p>
                                <span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span>
                                <p>Applied on <?= e(date("d M Y", strtotime($application["applied_at"]))) ?></p>
                                <a class="detail-primary" href="student-dashboard.php#recent-applications">View my applications <span aria-hidden="true">→</span></a>
                            </div>
                        <?php else: ?>
                            <h2 id="form-heading">Your application</h2>
                            <?php if ($error !== ""): ?>
                                <div class="apply-error" role="alert"><strong>Application not submitted</strong><p><?= e($error) ?></p></div>
                            <?php endif; ?>
                            <div class="applicant-info">
                                <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?></span>
                                <div><strong><?= e($user["full_name"]) ?></strong><p><?= e($user["email"]) ?></p></div>
                            </div>
                            <form class="apply-form" action="apply-process.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                <input type="hidden" name="internship_id" value="<?= e($internshipId) ?>">
                                <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

                                <div class="apply-field">
                                    <label for="cv">Upload your CV <span class="field-required">Required</span></label>
                                    <div class="cv-upload">
                                        <svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg>
                                        <p>Choose your latest CV</p>
                                        <input type="file" id="cv" name="cv" accept=".pdf,application/pdf" required aria-describedby="cv-help">
                                        <small id="cv-help">PDF only · Maximum 2 MB. Your CV will be attached to this application.</small>
                                    </div>
                                </div>

                                <div class="apply-field">
                                    <label for="cover-letter">Cover letter <span class="field-optional">Optional</span></label>
                                    <p id="cover-help" class="field-help">Tell the company why you are interested and what you can bring to the team. Maximum 2,000 characters.</p>
                                    <textarea id="cover-letter" name="cover_letter" rows="7" maxlength="2000" aria-describedby="cover-help" placeholder="I am interested in this internship because…"><?= e($coverLetter) ?></textarea>
                                </div>

                                <p class="submission-note">Check your CV before submitting. You can apply only once for this internship.</p>
                                <div class="apply-actions">
                                    <a class="detail-secondary" href="internship-details.php?id=<?= e($internshipId) ?>">Cancel</a>
                                    <button class="detail-primary" type="submit">Submit application <span aria-hidden="true">→</span></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>

                    <aside class="detail-summary surface" aria-labelledby="role-heading">
                        <p class="eyebrow">YOU ARE APPLYING FOR</p>
                        <h2 id="role-heading"><?= e($internship["title"]) ?></h2>
                        <p class="apply-company"><?= e($internship["company_name"]) ?></p>
                        <dl class="overview-list">
                            <div><dt>Location</dt><dd><?= e($internship["location"]) ?></dd></div>
                            <div><dt>Category</dt><dd><?= e($internship["category"]) ?></dd></div>
                            <div><dt>Application deadline</dt><dd class="deadline-date"><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></dd></div>
                        </dl>
                        <a class="detail-secondary" href="internship-details.php?id=<?= e($internshipId) ?>">View internship details</a>
                    </aside>
                </div>
            <?php else: ?>
                <section class="detail-unavailable surface">
                    <h1>This internship is unavailable</h1>
                    <p>It may have closed, expired, or is no longer accepting applications.</p>
                    <a class="detail-primary" href="internships.php">Browse open internships <span aria-hidden="true">→</span></a>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
=======
<?php
require "session.php";

if (!isset($_SESSION["user_id"])) {
    if (isset($_GET["id"]) && is_string($_GET["id"])) {
        $returnId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($returnId !== false) {
            $_SESSION["apply_after_login"] = $returnId;
        }
    }
    redirect("signin.php");
}

require "db.php";
date_default_timezone_set("Asia/Colombo");

 main
 main
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, s.student_id, s.photo_path
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
    exit("Only students with an active profile can apply for internships.");
}

$internshipId = false;
if (isset($_GET["id"]) && is_string($_GET["id"])) {
    $internshipId = filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
}

$internship = null;
if ($internshipId !== false) {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT i.internship_id, i.title, i.category, i.location, i.deadline, c.company_name
                            FROM internships i
                            JOIN companies c ON c.company_id = i.company_id
                            JOIN users u ON u.user_id = c.user_id
                            WHERE i.internship_id = ? AND i.status = 'Open' AND i.deadline >= ?
                            AND c.approval_status = 'Approved' AND u.status = 'Active'");
    $stmt->bind_param("is", $internshipId, $today);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();
}

$application = null;
if ($internship) {
    $stmt = $conn->prepare("SELECT status, applied_at FROM applications WHERE student_id = ? AND internship_id = ?");
    $stmt->bind_param("ii", $user["student_id"], $internshipId);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
} else {
    http_response_code(404);
}

$error = "";
$coverLetter = "";
$success = false;
if (isset($_SESSION["apply_form"]) && $_SESSION["apply_form"]["internship_id"] === $internshipId) {
    $error = $_SESSION["apply_form"]["error"];
    $coverLetter = $_SESSION["apply_form"]["cover_letter"];
    unset($_SESSION["apply_form"]);
}
if (isset($_SESSION["applied_internship"]) && $_SESSION["applied_internship"] === $internshipId) {
    $success = true;
    unset($_SESSION["applied_internship"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply for internship | SIMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="internship-details.css">
    <link rel="stylesheet" href="apply.css">
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
    <a class="skip-link" href="#main">Skip to application</a>
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
                    <a href="student-profile.php"><svg class="icon"><use href="#icon-user"/></svg>Profile</a>
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
                    <a class="account-chip surface" href="student-profile.php" aria-label="My account: <?= e($user["full_name"]) ?>">
                        <span class="avatar"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
                        <span class="account-copy"><strong><?= e($user["full_name"]) ?></strong><small><?= e(ucfirst($user["role"])) ?></small></span>
                    </a>
                <?php else: ?>
                    <a class="listing-signin" href="signin.php">Sign in <svg class="icon small-icon"><use href="#icon-arrow"/></svg></a>
                <?php endif; ?>
            </header>

            <?php if ($internship): ?>
                <section class="welcome">
                    <p class="eyebrow">TAKE YOUR NEXT STEP</p>
                    <h1><?= $application ? "Your application" : "Apply for this internship" ?></h1>
                    <p><?= $application ? "Follow your progress from your student dashboard." : "Share your CV and introduce yourself to the company." ?></p>
                </section>

                <div class="detail-columns apply-columns">
                    <section class="detail-section surface" aria-labelledby="form-heading">
                        <?php if ($application): ?>
                            <div class="application-result">
                                <span class="empty-icon"><svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg></span>
                                <h2 id="form-heading"><?= $success ? "Application submitted!" : "You have already applied" ?></h2>
                                <p <?= $success ? 'role="status"' : '' ?>><?= $success ? "Your CV and application have been saved successfully." : "An application for this internship is already saved to your account." ?></p>
                                <span class="status status-<?= e(strtolower($application["status"])) ?>"><?= e($application["status"]) ?></span>
                                <p>Applied on <?= e(date("d M Y", strtotime($application["applied_at"]))) ?></p>
                                <a class="detail-primary" href="my-applications.php">View my applications <span aria-hidden="true">→</span></a>
                            </div>
                        <?php else: ?>
                            <h2 id="form-heading">Your application</h2>
                            <?php if ($error !== ""): ?>
                                <div class="apply-error" role="alert"><strong>Application not submitted</strong><p><?= e($error) ?></p></div>
                            <?php endif; ?>
                            <div class="applicant-info">
                                <span class="avatar" aria-hidden="true"><?php if (!empty($user["photo_path"])): ?><img src="profile-photo.php?v=<?= e(basename($user["photo_path"])) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user["full_name"], 0, 1))) ?><?php endif; ?></span>
                                <div><strong><?= e($user["full_name"]) ?></strong><p><?= e($user["email"]) ?></p></div>
                            </div>
                            <form class="apply-form" action="apply-process.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                                <input type="hidden" name="internship_id" value="<?= e($internshipId) ?>">
                                <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

                                <div class="apply-field">
                                    <label for="cv">Upload your CV <span class="field-required">Required</span></label>
                                    <div class="cv-upload">
                                        <svg class="icon" aria-hidden="true"><use href="#icon-file"/></svg>
                                        <p>Choose your latest CV</p>
                                        <input type="file" id="cv" name="cv" accept=".pdf,application/pdf" required aria-describedby="cv-help">
                                        <small id="cv-help">PDF only · Maximum 2 MB. Your CV will be attached to this application.</small>
                                    </div>
                                </div>

                                <div class="apply-field">
                                    <label for="cover-letter">Cover letter <span class="field-optional">Optional</span></label>
                                    <p id="cover-help" class="field-help">Tell the company why you are interested and what you can bring to the team. Maximum 2,000 characters.</p>
                                    <textarea id="cover-letter" name="cover_letter" rows="7" maxlength="2000" aria-describedby="cover-help" placeholder="I am interested in this internship because…"><?= e($coverLetter) ?></textarea>
                                </div>

                                <p class="submission-note">Check your CV before submitting. You can apply only once for this internship.</p>
                                <div class="apply-actions">
                                    <a class="detail-secondary" href="internship-details.php?id=<?= e($internshipId) ?>">Cancel</a>
                                    <button class="detail-primary" type="submit">Submit application <span aria-hidden="true">→</span></button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>

                    <aside class="detail-summary surface" aria-labelledby="role-heading">
                        <p class="eyebrow">YOU ARE APPLYING FOR</p>
                        <h2 id="role-heading"><?= e($internship["title"]) ?></h2>
                        <p class="apply-company"><?= e($internship["company_name"]) ?></p>
                        <dl class="overview-list">
                            <div><dt>Location</dt><dd><?= e($internship["location"]) ?></dd></div>
                            <div><dt>Category</dt><dd><?= e($internship["category"]) ?></dd></div>
                            <div><dt>Application deadline</dt><dd class="deadline-date"><?= e(date("d M Y", strtotime($internship["deadline"]))) ?></dd></div>
                        </dl>
                        <a class="detail-secondary" href="internship-details.php?id=<?= e($internshipId) ?>">View internship details</a>
                    </aside>
                </div>
            <?php else: ?>
                <section class="detail-unavailable surface">
                    <h1>This internship is unavailable</h1>
                    <p>It may have closed, expired, or is no longer accepting applications.</p>
                    <a class="detail-primary" href="internships.php">Browse open internships <span aria-hidden="true">→</span></a>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
