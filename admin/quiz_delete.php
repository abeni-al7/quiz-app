<?php
// filepath: /home/abeni/Dev/quiz-app/admin/quiz_delete.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = intval($_GET['id']);

// Delete quiz and cascade to questions and related data
$stmt = $mysqli->prepare('DELETE FROM quizzes WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

header('Location: index.php');
exit;
