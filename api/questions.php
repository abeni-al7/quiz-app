<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List questions for a quiz
    if (!isset($_GET['quiz_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing quiz_id']);
        exit;
    }
    $quizId = intval($_GET['quiz_id']);
    // Fetch questions including correct_answer for fill_blank questions
    $stmt = $pdo->prepare('SELECT id, type, prompt, correct_answer FROM questions WHERE quiz_id = ?');
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
    // Gather correct answer if fill_blank
    $correctAnswer = $type === 'fill_blank' && isset($input['correct_answer']) ? trim($input['correct_answer']) : null;
    // Insert question including correct_answer
    $stmt = $pdo->prepare('INSERT INTO questions (quiz_id, type, prompt, correct_answer) VALUES (?, ?, ?, ?)');
    $stmt->execute([$quizId, $type, $prompt, $correctAnswer]);
    $qId = $pdo->lastInsertId();
    $result = ['id' => $qId, 'type' => $type, 'prompt' => $prompt, 'correct_answer' => $correctAnswer];
    // Insert choices if applicable
    if (in_array($type, ['multiple_choice', 'true_false']) && isset($input['choices']) && is_array($input['choices'])) {
        $result['choices'] = [];
        foreach ($input['choices'] as $choice) {
            if (!isset($choice['content'], $choice['is_correct'])) continue;
            $isCorrect = !empty($choice['is_correct']) ? 1 : 0;
            $stmt2 = $pdo->prepare('INSERT INTO choices (question_id, content, is_correct) VALUES (?, ?, ?)');
            $stmt2->execute([$qId, trim($choice['content']), $isCorrect]);
            $result['choices'][] = ['id' => $pdo->lastInsertId(), 'content' => trim($choice['content']), 'is_correct' => $isCorrect];
        }
    }
    echo json_encode($result);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $admin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'], $input['prompt'], $input['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id, prompt or type']);
        exit;
    }
    $id = intval($input['id']);
    $prompt = trim($input['prompt']);
    $type = $input['type'];
    // Update correct_answer if fill_blank
    $correctAnswer = $type === 'fill_blank' && isset($input['correct_answer']) ? trim($input['correct_answer']) : null;
    // Update question fields
    $stmt = $pdo->prepare('UPDATE questions SET prompt = ?, type = ?, correct_answer = ? WHERE id = ?');
    $stmt->execute([$prompt, $type, $correctAnswer, $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $admin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }
    $id = intval($input['id']);
    $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
