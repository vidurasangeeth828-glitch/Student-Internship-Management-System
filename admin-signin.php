<?php
require "session.php";

$admin = null;
if (isset($_SESSION["admin_id"])) {
    require "db.php";
    $stmt = $conn->prepare("SELECT full_name, email FROM admins WHERE admin_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $_SESSION["admin_id"]);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin) {
        redirect("admin-companies.php");
    }
    unset($_SESSION["admin_id"]);
}

$message = $_SESSION["admin_message"] ?? "";
$email = $_SESSION["admin_login_email"] ?? "";
unset($_SESSION["admin_message"], $_SESSION["admin_login_email"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Sign In | SIMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <script src="auth.js" defer></script>
</head>
<body>
    <main class="auth-page">
        <section class="auth-content signin-content" aria-labelledby="admin-title">
            <a class="brand auth-brand" href="index.php" aria-label="SIMS home">
                <img class="brand-icon" src="assets/logo.svg" alt="" width="40" height="38">
                <strong>SIMS</strong>
            </a>

                <h1 id="admin-title">Admin sign in</h1>
                <p class="auth-subtitle">Sign in with your administrator account.</p>
                <?php if ($message !== ""): ?>
                    <p class="form-message" role="alert"><?= e($message) ?></p>
                <?php endif; ?>

                <form action="admin-signin-process.php" method="post" class="auth-form">
                    <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                    <label class="sr-only" for="admin-email">Admin email address</label>
                    <input id="admin-email" name="email" type="email" placeholder="Admin email address" value="<?= e($email) ?>" required maxlength="180" autocomplete="username">

                    <div class="password-field">
                        <label class="sr-only" for="admin-password">Password</label>
                        <input id="admin-password" name="password" type="password" placeholder="Password" required maxlength="72" autocomplete="current-password">
                        <button class="password-toggle" type="button" aria-label="Show password" aria-controls="admin-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <button class="auth-submit" type="submit">Sign in as admin</button>
                </form>
                <p class="auth-bottom">Student or company? <a href="signin.php">Sign in here</a></p>

            <a class="back-home" href="index.php"><span aria-hidden="true">←</span> Back to home</a>
        </section>
    </main>
</body>
</html>
