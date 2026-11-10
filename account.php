<?php
require "session.php";
if (!isset($_SESSION["user_id"])) {
    redirect("signin.php");
}
require "db.php";

$stmt = $conn->prepare("SELECT u.full_name, u.role, c.approval_status FROM users u
$stmt = $conn->prepare("SELECT u.full_name, u.email, u.role, c.approval_status FROM users u
                        LEFT JOIN companies c ON c.user_id = u.user_id
                        WHERE u.user_id = ? AND u.status = 'Active'");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    unset($_SESSION["user_id"]);
    redirect("signin.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | SIMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-content signin-content">
            <a class="brand auth-brand" href="index.php"><img class="brand-icon" src="assets/logo.svg" alt="" width="40" height="38"><strong>SIMS</strong></a>
            <h1>Welcome, <?= e($user["full_name"]) ?></h1>
            <p class="auth-subtitle">You are signed in as a <?= e($user["role"]) ?>.</p>
            <?php if ($user["role"] === "company"): ?>
                <p class="form-message">Company approval: <?= e($user["approval_status"]) ?>. Approval is required before posting internships.</p>
            <?php endif; ?>
            <form class="auth-form" action="logout.php" method="post">
                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                <button class="auth-submit" type="submit">Sign out</button>
            </form>
            <a class="back-home" href="index.php">← Back to home</a>
        </section>
    </main>
</body>
</html>

if ($user["role"] === "student") {
    redirect("student-profile.php");
}
redirect("company-dashboard.php");
