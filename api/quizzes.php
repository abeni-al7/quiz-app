<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List quizzes, optional subject filter
    $subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
    $sql = 'SELECT q.id, q.title, q.description, q.subject_id, s.title AS subject_title, q.created_by FROM quizzes q JOIN subjects s ON q.subject_id = s.id';
    $params = [];
    if ($subjectId) {
        $sql .= ' WHERE q.subject_id = ?';
        $params[] = $subjectId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll();
    echo json_encode($quizzes);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins can create quizzes
    $admin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['title'], $input['subject_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $title = trim($input['title']);
    $description = trim($input['description'] ?? '');
    $subjectId = intval($input['subject_id']);

    $stmt = $pdo->prepare('INSERT INTO quizzes (title, description, subject_id, created_by) VALUES (?, ?, ?, ?)');
    $stmt->execute([$title, $description, $subjectId, $admin['sub']]);
    $id = $pdo->lastInsertId();
    echo json_encode(['id' => $id, 'title' => $title, 'description' => $description, 'subject_id' => $subjectId]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);