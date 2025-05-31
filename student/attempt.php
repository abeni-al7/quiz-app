<?php
// filepath: /home/abeni/Dev/quiz-app/student/attempt.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
$user = require_student();

// Get attempt_id
$attemptId = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : (isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0);
if (!$attemptId) {
    header('Location: index.php');
    exit;
}

// Fetch attempt
$stmt = $mysqli->prepare('SELECT * FROM student_quizzes WHERE id = ? AND student_id = ?');
$stmt->bind_param('ii', $attemptId, $user['id']);
$stmt->execute();
attempt: $attempt = $stmt->get_result()->fetch_assoc();
if (!$attempt) {
    echo '<p>Attempt not found.</p>';
    exit;
}

// Fetch quiz info
$stmt = $mysqli->prepare('SELECT id, title FROM quizzes WHERE id = ?');
$stmt->bind_param('i', $attempt['quiz_id']);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

// Handle submission
$result = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grade quiz
    $correctCount = 0;
    $total = 0;
    foreach ($_POST['answers'] as $questionId => $choiceId) {
        $total++;
        // Check correctness
        $stmt = $mysqli->prepare('SELECT is_correct FROM choices WHERE id = ?');
        $stmt->bind_param('i', $choiceId);
        $stmt->execute();
        $isCorrect = $stmt->get_result()->fetch_assoc()['is_correct'];

        // Save answer
        $stmta = $mysqli->prepare(
            'REPLACE INTO student_answers (attempt_id, question_id, chosen_choice_id, is_correct) VALUES (?,?,?,?)'
        );
        $stmta->bind_param('iiii', $attemptId, $questionId, $choiceId, $isCorrect);
        $stmta->execute();

        if ($isCorrect) $correctCount++;
    }
    // Update attempt
    $stmt = $mysqli->prepare('UPDATE student_quizzes SET score = ?, status = "graded", completed_at = NOW() WHERE id = ?');
    $stmt->bind_param('ii', $correctCount, $attemptId);
    $stmt->execute();
    // Redirect to GET to show result once graded
    header('Location: attempt.php?attempt_id=' . $attemptId);
    exit;
}

// Fetch questions and choices
$stmt = $mysqli->prepare(
    'SELECT q.id AS question_id, q.prompt, c.id AS choice_id, c.content, c.is_correct,
            sa.chosen_choice_id
     FROM questions q
     JOIN choices c ON q.id = c.question_id
     LEFT JOIN student_answers sa ON sa.question_id = q.id AND sa.attempt_id = ?
     WHERE q.quiz_id = ?
     ORDER BY q.id, c.id'
);
$stmt->bind_param('ii', $attemptId, $quiz['id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize questions
$questions = [];
foreach ($data as $row) {
    $qid = $row['question_id'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'prompt'   => $row['prompt'],
            'choices'  => [],
            'selected' => $row['chosen_choice_id'],
        ];
    }
    $questions[$qid]['choices'][] = [
        'id'         => $row['choice_id'],
        'content'    => $row['content'],
        'is_correct' => $row['is_correct'],
    ];
}

// Remove duplicate choices
foreach ($questions as &$q) {
    $seen   = [];
    $unique = [];
    foreach ($q['choices'] as $choice) {
        if (!in_array($choice['id'], $seen, true)) {
            $seen[]   = $choice['id'];
            $unique[] = $choice;
        }
    }
    $q['choices'] = $unique;
}
unset($q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Attempt: <?= htmlspecialchars($quiz['title']) ?></title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1><?= htmlspecialchars($quiz['title']) ?></h1>
    <p><a href="index.php" class="btn">Back to Dashboard</a> | <a href="/logout.php" class="btn">Logout</a></p>
    <?php if ($attempt['status'] === 'graded'): ?>
      <div class="result">
        <h2>Your Score: <?= intval($attempt['score']) ?> / <?= count($questions) ?></h2>
      </div>
    <?php endif; ?>
    <?php if ($attempt['status'] !== 'graded'): ?>
      <form method="post" id="attemptForm">
        <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
        <div class="progress-bar"><div class="progress"></div></div>
        <?php foreach ($questions as $qid => $q): ?>
          <div class="question">
            <p><?= htmlspecialchars($q['prompt']) ?></p>
            <?php foreach ($q['choices'] as $choice): ?>
              <div class="choice-option">
                <label>
                  <input type="radio" name="answers[<?= $qid ?>]" value="<?= $choice['id'] ?>" required>
                  <?= htmlspecialchars($choice['content']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <div class="nav-buttons">
          <button type="button" id="prevBtn" style="display:none">Previous</button>
          <button type="button" id="nextBtn" style="display:none">Next</button>
          <button type="submit" id="submitBtn" style="display:none">Submit</button>
        </div>
      </form>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script src="/js/student_attempt.js"></script>
    <?php else: ?>
      <div class="section graded-results">
        <?php foreach ($questions as $qid => $q): ?>
          <div class="graded-question">
            <h3 class="question-prompt"><?= htmlspecialchars($q['prompt']) ?></h3>
            <div class="graded-answers">
            <?php foreach ($q['choices'] as $choice): ?>
              <?php
                $classes = [];
                if ($choice['id'] === $q['selected']) {
                  $classes[] = $choice['is_correct'] ? 'correct-answer' : 'incorrect-answer';
                } elseif ($choice['is_correct']) {
                  $classes[] = 'correct-answer';
                }
                $classAttr = $classes ? ' class="'.implode(' ', $classes).'"' : '';
              ?>
              <?php $answerClasses = array_merge(['graded-answer'], $classes); ?>
              <div class="<?= implode(' ', $answerClasses) ?>">
                <?= htmlspecialchars($choice['content']) ?>
                <?php if ($choice['id'] === $q['selected']): ?>
                  &mdash; Your answer
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <!-- include JS only for interactive mode -->
  <?php if ($attempt['status'] !== 'graded'): ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/student_attempt.js"></script>
  <?php endif; ?>
</body>
</html>
