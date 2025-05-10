<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List subjects
    $stmt = $pdo->query('SELECT id, title, description FROM subjects');
    $subjects = $stmt->fetchAll();
    echo json_encode($subjects);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins can create subjects
    $admin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing title']);
        exit;
    }
    $title = trim($input['title']);
    $description = trim($input['description'] ?? '');
    $stmt = $pdo->prepare('INSERT INTO subjects (title, description) VALUES (?, ?)');
    $stmt->execute([$title, $description]);
    $id = $pdo->lastInsertId();
    echo json_encode(['id' => $id, 'title' => $title, 'description' => $description]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
