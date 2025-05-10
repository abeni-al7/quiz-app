<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

$user = require_auth();
$studentId = $user['sub'];

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        // Fetch single attempt if specified
        if (isset($_GET['attempt_id'])) {
            $attemptId = intval($_GET['attempt_id']);
            $stmt = $pdo->prepare('SELECT id, quiz_id, status, score FROM student_quizzes WHERE id = ? AND student_id = ?');
            $stmt->execute([$attemptId, $studentId]);
            $attempt = $stmt->fetch();
            if (!$attempt) {
                http_response_code(404);
                echo json_encode(['error' => 'Attempt not found']);
                exit;
            }
            echo json_encode($attempt);
            exit;
        }
        // List student's attempts with optional filters
        $status = $_GET['status'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $sql = "SELECT sq.id, sq.quiz_id, q.title AS quiz_title, s.id AS subject_id, s.title AS subject_title, sq.status, sq.score, sq.started_at, sq.completed_at
                FROM student_quizzes sq
                JOIN quizzes q ON sq.quiz_id = q.id
                JOIN subjects s ON q.subject_id = s.id
                WHERE sq.student_id = ?";
        $params = [$studentId];
        if ($status) {
            $sql .= ' AND sq.status = ?';
            $params[] = $status;
        }
        if ($subjectId) {
            $sql .= ' AND s.id = ?';
            $params[] = intval($subjectId);
        }
        if ($search) {
            $sql .= ' AND q.title LIKE ?';
            $params[] = "%{$search}%";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;
    case 'POST':
        // Start a new attempt
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['quiz_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing quiz_id']);
            exit;
        }
        $quizId = intval($input['quiz_id']);
        $stmt = $pdo->prepare('INSERT INTO student_quizzes (student_id, quiz_id) VALUES (?, ?)');
        $stmt->execute([$studentId, $quizId]);
        $attemptId = $pdo->lastInsertId();
        echo json_encode(['attempt_id' => $attemptId]);
        break;
    case 'PUT':
        // Complete an attempt
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['attempt_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing attempt_id']);
            exit;
        }
        $attemptId = intval($input['attempt_id']);
        // Ensure belongs to student
        $stmt = $pdo->prepare('SELECT id FROM student_quizzes WHERE id = ? AND student_id = ?');
        $stmt->execute([$attemptId, $studentId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized attempt']);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE student_quizzes SET status = "completed", completed_at = NOW() WHERE id = ?');
        $stmt->execute([$attemptId]);
        echo json_encode(['success' => true]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
