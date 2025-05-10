<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List questions for a quiz
    if (!isset($_GET['quiz_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing quiz_id']);
        exit;
    }
    $quizId = intval($_GET['quiz_id']);
    // Fetch questions
    $stmt = $pdo->prepare('SELECT id, type, prompt FROM questions WHERE quiz_id = ?');
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll();
    // Attach choices for multiple choice and true/false
    foreach ($questions as &$q) {
        if (in_array($q['type'], ['multiple_choice', 'true_false'])) {
            $stmt2 = $pdo->prepare('SELECT id, content, is_correct FROM choices WHERE question_id = ?');
            $stmt2->execute([$q['id']]);
            $q['choices'] = $stmt2->fetchAll();
        }
    }
    echo json_encode($questions);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admins can create questions
    $admin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['quiz_id'], $input['type'], $input['prompt'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $quizId = intval($input['quiz_id']);
    $type = $input['type'];
    $prompt = trim($input['prompt']);
    // Insert question
    $stmt = $pdo->prepare('INSERT INTO questions (quiz_id, type, prompt) VALUES (?, ?, ?)');
    $stmt->execute([$quizId, $type, $prompt]);
    $qId = $pdo->lastInsertId();
    $result = ['id' => $qId, 'type' => $type, 'prompt' => $prompt];
    // Insert choices if applicable
    if (in_array($type, ['multiple_choice', 'true_false']) && isset($input['choices']) && is_array($input['choices'])) {
        $result['choices'] = [];
        foreach ($input['choices'] as $choice) {
            if (!isset($choice['content'], $choice['is_correct'])) continue;
            $stmt2 = $pdo->prepare('INSERT INTO choices (question_id, content, is_correct) VALUES (?, ?, ?)');
            $stmt2->execute([$qId, trim($choice['content']), (bool)$choice['is_correct']]);
            $result['choices'][] = ['id' => $pdo->lastInsertId(), 'content' => trim($choice['content']), 'is_correct' => (bool)$choice['is_correct']];
        }
    }
    echo json_encode($result);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
