<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = validate_jwt();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Admin retrieve all answers for an attempt
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin privileges required']);
        exit;
    }
    if (!isset($_GET['attempt_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing attempt_id']);
        exit;
    }
    $attemptId = intval($_GET['attempt_id']);
    $stmt = $pdo->prepare(
        'SELECT sa.id AS answer_id, sa.question_id, q.prompt, q.type, sa.answer_text, sa.chosen_choice_id, sa.is_correct, c.content AS choice_content
         FROM student_answers sa
         JOIN questions q ON sa.question_id = q.id
         LEFT JOIN choices c ON sa.chosen_choice_id = c.id
         WHERE sa.attempt_id = ?'
    );
    $stmt->execute([$attemptId]);
    $answers = $stmt->fetchAll();
    echo json_encode($answers);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Admin grading for fill_blank questions
    $userAdmin = require_admin();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['attempt_id'], $input['grades']) || !is_array($input['grades'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing attempt_id or grades']);
        exit;
    }
    $attemptId = intval($input['attempt_id']);
    // Update each answer's is_correct
    foreach ($input['grades'] as $grade) {
        if (!isset($grade['answer_id'], $grade['is_correct'])) continue;
        $stmtUpd = $pdo->prepare('UPDATE student_answers SET is_correct = ? WHERE id = ? AND attempt_id = ?');
        $stmtUpd->execute([(bool)$grade['is_correct'], intval($grade['answer_id']), $attemptId]);
    }
    // Recalculate score
    $stmtScore = $pdo->prepare('SELECT COUNT(*) AS correct_count FROM student_answers WHERE attempt_id = ? AND is_correct = 1');
    $stmtScore->execute([$attemptId]);
    $scoreRow = $stmtScore->fetch();
    $newScore = $scoreRow['correct_count'];
    // Update attempt to graded
    $stmtA = $pdo->prepare('UPDATE student_quizzes SET score = ?, status = "graded" WHERE id = ?');
    $stmtA->execute([$newScore, $attemptId]);
    echo json_encode(['success' => true, 'new_score' => $newScore]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Student submitting answers (existing POST)
$student = require_auth();
$studentId = $student['sub'];

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['attempt_id'], $input['answers']) || !is_array($input['answers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing attempt_id or answers']);
    exit;
}
$attemptId = intval($input['attempt_id']);
// Ensure attempt belongs to student and in progress
$stmt = $pdo->prepare('SELECT status FROM student_quizzes WHERE id = ? AND student_id = ?');
$stmt->execute([$attemptId, $studentId]);
$attempt = $stmt->fetch();
if (!$attempt || $attempt['status'] !== 'in_progress') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or unauthorized attempt']);
    exit;
}

$totalScore = 0;
foreach ($input['answers'] as $ans) {
    if (!isset($ans['question_id'])) continue;
    $questionId = intval($ans['question_id']);
    $answerText = $ans['answer_text'] ?? null;
    $choiceId = isset($ans['choice_id']) ? intval($ans['choice_id']) : null;
    $isCorrect = null;
    // Fetch question type
    $stmtQ = $pdo->prepare('SELECT type FROM questions WHERE id = ?');
    $stmtQ->execute([$questionId]);
    $q = $stmtQ->fetch();
    if ($q) {
        if (in_array($q['type'], ['multiple_choice', 'true_false'])) {
            if ($choiceId) {
                $stmtC = $pdo->prepare('SELECT is_correct FROM choices WHERE id = ? AND question_id = ?');
                $stmtC->execute([$choiceId, $questionId]);
                $c = $stmtC->fetch();
                $isCorrect = $c ? (bool)$c['is_correct'] : false;
                if ($isCorrect) $totalScore++;
            }
        } else if ($q['type'] === 'fill_blank') {
            $isCorrect = false;
        }
    }
    $stmtIns = $pdo->prepare('INSERT INTO student_answers (attempt_id, question_id, answer_text, chosen_choice_id, is_correct) VALUES (?, ?, ?, ?, ?)');
    $stmtIns->execute([$attemptId, $questionId, $answerText, $choiceId, $isCorrect]);
}
$stmtUpd = $pdo->prepare('UPDATE student_quizzes SET score = ?, status = "completed", completed_at = NOW() WHERE id = ?');
$stmtUpd->execute([$totalScore, $attemptId]);

echo json_encode(['success' => true, 'score' => $totalScore]);