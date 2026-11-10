 nethmi
<?php
require "session.php";
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "student") {
        redirect("student-dashboard.php");
    }
    redirect("company-dashboard.php");
}
$old = $_SESSION["signup_values"] ?? [];
unset($_SESSION["signup_values"]);
$role = ($old["role"] ?? ($_GET["role"] ?? "")) === "company" ? "company" : "student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up | SIMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <script src="auth.js" defer></script>
</head>
<body>
    <main class="auth-page">
        <section class="auth-content signup-content" aria-labelledby="signup-title">
            <a class="brand auth-brand" href="index.php" aria-label="SIMS home">
                <img class="brand-icon" src="assets/logo.svg" alt="" width="40" height="38">
                <strong>SIMS</strong>
            </a>
            <h1 id="signup-title">Create your account</h1>
            <p class="auth-subtitle">Choose your account type to get started.</p>
            <?php form_message(); ?>

            <form action="signup-process.php" method="post" class="auth-form">
                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                <fieldset class="role-choices">
                    <legend class="sr-only">Account type</legend>
                    <label class="role-choice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5L2 9Zm4 2v6c3 3 9 3 12 0v-6m4-2v7"/></svg>
                        <span>Student</span>
                        <input type="radio" name="role" value="student" <?= $role === "student" ? "checked" : "" ?>>
                    </label>
                    <label class="role-choice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 21h18M5 21V3h10v18m0-12h4v12M8 7h1m2 0h1m-4 4h1m2 0h1m-4 4h1m2 0h1"/></svg>
                        <span>Company</span>
                        <input type="radio" name="role" value="company" <?= $role === "company" ? "checked" : "" ?>>
                    </label>
                </fieldset>

                <div class="field-grid">
                    <label><span id="name-label"><?= $role === "company" ? "Company name" : "Full name" ?></span>
                        <input id="account-name" name="name" required maxlength="120" autocomplete="name" placeholder="<?= $role === "company" ? "Enter your company name" : "Enter your full name" ?>" value="<?= e($old["name"] ?? "") ?>">
                    </label>
                    <label>Email address
                        <input name="email" type="email" required maxlength="180" autocomplete="email" placeholder="Enter your email address" value="<?= e($old["email"] ?? "") ?>">
                    </label>
                </div>

                <fieldset id="company-fields" class="field-grid company-fields" <?= $role === "student" ? "hidden disabled" : "" ?>>
                    <legend class="sr-only">Company contact details</legend>
                    <label>Contact number
                        <input name="phone" type="tel" required maxlength="30" autocomplete="tel" placeholder="Enter contact number" value="<?= e($old["phone"] ?? "") ?>">
                    </label>
                    <label>Business registration no. <small>(optional)</small>
                        <input name="registration" maxlength="80" placeholder="Enter registration number" value="<?= e($old["registration"] ?? "") ?>">
                    </label>
                </fieldset>

                <label>Password
                    <span class="password-field">
                        <input id="new-password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Enter your password">
                        <button class="password-toggle" type="button" aria-label="Show password" aria-controls="new-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </span>
                    <small class="password-help">Use at least 8 characters.</small>
                </label>

                <label>Confirm password
                    <span class="password-field">
                        <input id="confirm-password" name="confirm_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Confirm your password">
                        <button class="password-toggle" type="button" aria-label="Show confirm password" aria-controls="confirm-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </span>
                </label>

                <p id="approval-note" class="approval-note" <?= $role === "student" ? "hidden" : "" ?>>Your company needs admin approval before posting internships.</p>
                <button class="auth-submit" type="submit">Create account</button>
            </form>
            <p class="auth-bottom">Already registered? <a href="signin.php">Sign in</a></p>
            <a class="back-home" href="index.php"><span aria-hidden="true">←</span> Back to home</a>
        </section>
    </main>
</body>
</html>
=======
<?php
require "session.php";
 hashen
 Updated upstream
if (isset($_SESSION["user_id"])) {
=======
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "student") {
        redirect("student-dashboard.php");
    }
 Stashed changes
    redirect("account.php");

if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "student") {
        redirect("student-dashboard.php");
    }
    redirect("company-dashboard.php");
}
$old = $_SESSION["signup_values"] ?? [];
unset($_SESSION["signup_values"]);
$role = ($old["role"] ?? ($_GET["role"] ?? "")) === "company" ? "company" : "student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up | SIMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
    <script src="auth.js" defer></script>
</head>
<body>
    <main class="auth-page">
        <section class="auth-content signup-content" aria-labelledby="signup-title">
            <a class="brand auth-brand" href="index.php" aria-label="SIMS home">
                <img class="brand-icon" src="assets/logo.svg" alt="" width="40" height="38">
                <strong>SIMS</strong>
            </a>
            <h1 id="signup-title">Create your account</h1>
            <p class="auth-subtitle">Choose your account type to get started.</p>
            <?php form_message(); ?>

            <form action="signup-process.php" method="post" class="auth-form">
                <input type="hidden" name="token" value="<?= e($_SESSION["token"]) ?>">
                <fieldset class="role-choices">
                    <legend class="sr-only">Account type</legend>
                    <label class="role-choice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5L2 9Zm4 2v6c3 3 9 3 12 0v-6m4-2v7"/></svg>
                        <span>Student</span>
                        <input type="radio" name="role" value="student" <?= $role === "student" ? "checked" : "" ?>>
                    </label>
                    <label class="role-choice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 21h18M5 21V3h10v18m0-12h4v12M8 7h1m2 0h1m-4 4h1m2 0h1m-4 4h1m2 0h1"/></svg>
                        <span>Company</span>
                        <input type="radio" name="role" value="company" <?= $role === "company" ? "checked" : "" ?>>
                    </label>
                </fieldset>

                <div class="field-grid">
                    <label><span id="name-label"><?= $role === "company" ? "Company name" : "Full name" ?></span>
                        <input id="account-name" name="name" required maxlength="120" autocomplete="name" placeholder="<?= $role === "company" ? "Enter your company name" : "Enter your full name" ?>" value="<?= e($old["name"] ?? "") ?>">
                    </label>
                    <label>Email address
                        <input name="email" type="email" required maxlength="180" autocomplete="email" placeholder="Enter your email address" value="<?= e($old["email"] ?? "") ?>">
                    </label>
                </div>

                <fieldset id="company-fields" class="field-grid company-fields" <?= $role === "student" ? "hidden disabled" : "" ?>>
                    <legend class="sr-only">Company contact details</legend>
                    <label>Contact number
                        <input name="phone" type="tel" required maxlength="30" autocomplete="tel" placeholder="Enter contact number" value="<?= e($old["phone"] ?? "") ?>">
                    </label>
                    <label>Business registration no. <small>(optional)</small>
                        <input name="registration" maxlength="80" placeholder="Enter registration number" value="<?= e($old["registration"] ?? "") ?>">
                    </label>
                </fieldset>

                <label>Password
                    <span class="password-field">
                        <input id="new-password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Enter your password">
                        <button class="password-toggle" type="button" aria-label="Show password" aria-controls="new-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </span>
                    <small class="password-help">Use at least 8 characters.</small>
                </label>

                <label>Confirm password
                    <span class="password-field">
                        <input id="confirm-password" name="confirm_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Confirm your password">
                        <button class="password-toggle" type="button" aria-label="Show confirm password" aria-controls="confirm-password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </span>
                </label>

                <p id="approval-note" class="approval-note" <?= $role === "student" ? "hidden" : "" ?>>Your company needs admin approval before posting internships.</p>
                <button class="auth-submit" type="submit">Create account</button>
            </form>
            <p class="auth-bottom">Already registered? <a href="signin.php">Sign in</a></p>
            <a class="back-home" href="index.php"><span aria-hidden="true">←</span> Back to home</a>
        </section>
    </main>
</body>
</html>
 main
