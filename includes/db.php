<?php

// Database connection using mysqli
$host = '127.0.0.1';
$db   = 'quiz_app';
$user = 'root';
$pass = 'mypasstonewgen';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    // For SSR, show a simple error page
    echo '<h2>Database connection failed</h2>';
    echo '<p>' . htmlspecialchars($mysqli->connect_error) . '</p>';
    exit;
}
$mysqli->set_charset($charset);