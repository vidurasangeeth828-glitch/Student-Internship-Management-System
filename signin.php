<?php
require "session.php";
if (isset($_SESSION["user_id"])) {
 nethmi
=======
 Nuzrath
    redirect("account.php");
    if (($_SESSION["role"] ?? "") === "student") {
        redirect("student-dashboard.php");
    }
    redirect("company-dashboard.php");
 main
}
$rememberedEmail = $_COOKIE["sims_email"] ?? "";
if (!is_string($rememberedEmail) || !filter_var($rememberedEmail, FILTER_VALIDATE_EMAIL)) {
    $rememberedEmail = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | SIMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <script src="auth.js" defer></script>
</head>
<body>
    <main class="auth-page">
        <section class="auth-content signin-content" aria-labelledby="signin-title">
            <a class="brand auth-brand" href="index.php" aria-label="SIMS home">
                <img class="brand-icon" src="assets/logo.svg" alt="" width="40" height="38">
                <strong>SIMS</strong>
            </a>
            <h1 id="signin-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to continue your internship journey.</p>
            <?php form_message(); ?>

            <form action="signin-process.php" method="post" class="auth-form">
                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                <label class="sr-only" for="email">Email address</label>
                <input id="email" name="email" type="email" placeholder="Email address" required maxlength="180" autocomplete="username" value="<?= e($rememberedEmail) ?>">

                <div class="password-field">
                    <label class="sr-only" for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password" required maxlength="72" autocomplete="current-password">
                    <button class="password-toggle" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <div class="form-options">
                    <label class="remember"><input type="checkbox" name="remember_email" value="1" <?= $rememberedEmail !== "" ? "checked" : "" ?>> Remember email</label>
                    <a href="signup.php">Sign up</a>
                </div>
                <button class="auth-submit" type="submit">Sign in</button>
            </form>

            <p class="auth-bottom">New to SIMS? <a href="signup.php">Create an account</a></p>
            <a class="back-home" href="index.php"><span aria-hidden="true">←</span> Back to home</a>
        </section>
    </main>
</body>
</html>

<?php
require "session.php";
 
if (isset($_SESSION["user_id"])) {

if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "student") {
        redirect("student-dashboard.php");
    }
    redirect("account.php");

if (isset($_SESSION["user_id"])) {
 main
    if (($_SESSION["role"] ?? "") === "student") {
        redirect("student-dashboard.php");
    }
    redirect("company-dashboard.php");
}
$rememberedEmail = $_COOKIE["sims_email"] ?? "";
if (!is_string($rememberedEmail) || !filter_var($rememberedEmail, FILTER_VALIDATE_EMAIL)) {
    $rememberedEmail = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | SIMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <script src="auth.js" defer></script>
</head>
<body>
    <main class="auth-page">
        <section class="auth-content signin-content" aria-labelledby="signin-title">
            <a class="brand auth-brand" href="index.php" aria-label="SIMS home">
                <img class="brand-icon" src="assets/logo.svg" alt="" width="40" height="38">
                <strong>SIMS</strong>
            </a>
            <h1 id="signin-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to continue your internship journey.</p>
            <?php form_message(); ?>

            <form action="signin-process.php" method="post" class="auth-form">
                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                <label class="sr-only" for="email">Email address</label>
                <input id="email" name="email" type="email" placeholder="Email address" required maxlength="180" autocomplete="username" value="<?= e($rememberedEmail) ?>">

                <div class="password-field">
                    <label class="sr-only" for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Password" required maxlength="72" autocomplete="current-password">
                    <button class="password-toggle" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <div class="form-options">
                    <label class="remember"><input type="checkbox" name="remember_email" value="1" <?= $rememberedEmail !== "" ? "checked" : "" ?>> Remember email</label>
                    <a href="signup.php">Sign up</a>
                </div>
                <button class="auth-submit" type="submit">Sign in</button>
            </form>

            <p class="auth-bottom">New to SIMS? <a href="signup.php">Create an account</a></p>
            <a class="back-home" href="index.php"><span aria-hidden="true">←</span> Back to home</a>
        </section>
    </main>
</body>
</html>
