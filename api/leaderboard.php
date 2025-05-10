<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
$user = require_auth();

$stmt = $pdo->query('SELECT student_id, name, total_score FROM leaderboard');
$board = $stmt->fetchAll();
echo json_encode($board);