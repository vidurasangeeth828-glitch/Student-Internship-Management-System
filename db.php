<?php
$servername = "127.0.0.1";
$username = "root";
$dbPassword = "";
$database = "sims";
$port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $dbPassword, $database, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $error) {
    http_response_code(503);
    exit("Database unavailable. Start MySQL and import database.sql using phpMyAdmin.");
}
