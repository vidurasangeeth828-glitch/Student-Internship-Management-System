<?php
// Administrator accounts are created locally, never through a public web form.
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Run this setup file from the local PHP command line.");
}

echo "SIMS administrator setup\n";
echo "Full name: ";
$name = trim((string) fgets(STDIN));
echo "Email address: ";
$email = trim((string) fgets(STDIN));
echo "Password (input is visible in this terminal): ";
$password = rtrim((string) fgets(STDIN), "\r\n");
echo "Confirm password: ";
$confirmation = rtrim((string) fgets(STDIN), "\r\n");

if ($name === "" || mb_strlen($name) > 120 || strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Enter a name of up to 120 characters and a valid email address.\n");
    exit(1);
}
if (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false || $password !== $confirmation) {
    fwrite(STDERR, "Use matching passwords of 8 to 72 bytes.\n");
    exit(1);
}

require "db.php";
$hash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $conn->prepare("INSERT INTO admins (full_name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hash);
    $stmt->execute();
    echo "Administrator created. Open admin-signin.php to sign in.\n";
} catch (mysqli_sql_exception $error) {
    if ($error->getCode() === 1062) {
        fwrite(STDERR, "An administrator already uses that email. Existing credentials were not changed.\n");
    } else {
        fwrite(STDERR, "The administrator could not be created. Check the database connection.\n");
    }
    exit(1);
}
