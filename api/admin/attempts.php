<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
$user = require_admin();

$stmt = $pdo->query(
    'SELECT sq.id, u.name AS student_name, q.title AS quiz_title, sq.status
     FROM student_quizzes sq
     JOIN users u ON sq.student_id = u.id
     JOIN quizzes q ON sq.quiz_id = q.id'
);\n$attempts = $stmt->fetchAll();
echo json_encode($attempts);
