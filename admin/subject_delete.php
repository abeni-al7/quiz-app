<?php
// filepath: /home/abeni/Dev/quiz-app/admin/subject_delete.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

if (!isset($_GET['id'])) {
    header('Location: subjects.php');
    exit;
}
$id = intval($_GET['id']);
$stmt = $mysqli->prepare('DELETE FROM subjects WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

header('Location: subjects.php');
exit;
