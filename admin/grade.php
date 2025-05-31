<?php
// filepath: /home/abeni/Dev/quiz-app/admin/grade.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

// Get attempt_id
$attemptId = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;
if (!$attemptId) {
    header('Location: attempts.php');
    exit;
}

// Fetch attempt details
$stmt = $mysqli->prepare(
    'SELECT sq.id, sq.score, sq.status, sq.started_at, sq.completed_at,
            u.name AS student_name, q.title AS quiz_title, q.id AS quiz_id
     FROM student_quizzes sq
     JOIN users u ON sq.student_id = u.id
     JOIN quizzes q ON sq.quiz_id = q.id
     WHERE sq.id = ?'
);
$stmt->bind_param('i', $attemptId);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();
if (!$attempt) {
    echo '<p>Attempt not found.</p>';
    exit;
}

// Fetch questions with choices and student's selected choice
$stmt = $mysqli->prepare(
    'SELECT q.id AS question_id, q.prompt, c.id AS choice_id, c.content, c.is_correct,
            sa.chosen_choice_id
     FROM questions q
     JOIN choices c ON c.question_id = q.id
     LEFT JOIN student_answers sa ON sa.question_id = q.id AND sa.attempt_id = ?
     WHERE q.quiz_id = ?
     ORDER BY q.id, c.id'
);
$stmt->bind_param('ii', $attemptId, $attempt['quiz_id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize by question
$questions = [];
foreach ($data as $row) {
    $qid = $row['question_id'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'prompt' => $row['prompt'],
            'choices' => [],
            'selected' => $row['chosen_choice_id']
        ];
    }
    $questions[$qid]['choices'][] = [
        'id' => $row['choice_id'],
        'content' => $row['content'],
        'is_correct' => $row['is_correct']
    ];
}

// Remove duplicate choices per question
foreach ($questions as &$q_item) {
    $seen = [];
    $unique = [];
    foreach ($q_item['choices'] as $ch) {
        if (!in_array($ch['id'], $seen, true)) {
            $seen[] = $ch['id'];
            $unique[] = $ch;
        }
    }
    $q_item['choices'] = $unique;
}
unset($q_item);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Grade Attempt — <?= htmlspecialchars($attempt['quiz_title']) ?></title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Grade Attempt</h1>
      <div class="btn-group">
        <a href="attempts.php" class="btn">Back to Attempts</a>
        <a href="/logout.php" class="btn">Logout</a>
      </div>
      <div class="attempt-info">
        <span><strong>Quiz:</strong> <?= htmlspecialchars($attempt['quiz_title']) ?></span>
        <span><strong>Student:</strong> <?= htmlspecialchars($attempt['student_name']) ?></span>
        <span><strong>Score:</strong> <?= intval($attempt['score']) ?>/<?= count($questions) ?></span>
        <span><strong>Status:</strong> <?= htmlspecialchars($attempt['status']) ?></span>
      </div>
    </div>
    <div class="section graded-results">
      <?php foreach ($questions as $qid => $q): ?>
        <div class="quiz-card">
          <h3 class="question-prompt"><?= htmlspecialchars($q['prompt']) ?></h3>
          <div class="graded-answers">
            <?php foreach ($q['choices'] as $choice): ?>
              <?php
                // Highlight correct choices green, and wrong student selection red
                $classes = ['graded-answer'];
                if ($choice['is_correct']) {
                    $classes[] = 'correct-answer';
                }
                if ($choice['id'] === $q['selected'] && !$choice['is_correct']) {
                    $classes[] = 'incorrect-answer';
                }
              ?>
              <div class="<?= implode(' ', $classes) ?>">
                <?= htmlspecialchars($choice['content']) ?>
                <?php if ($choice['id'] === $q['selected'] && !$choice['is_correct']): ?>
                  <div class="your-answer">Your selection was incorrect</div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
