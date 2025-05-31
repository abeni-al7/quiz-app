<?php
// filepath: /home/abeni/Dev/quiz-app/admin/questions.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
if (!$quizId) {
    header('Location: index.php');
    exit;
}

// Fetch quiz title
$stmt = $mysqli->prepare('SELECT title FROM quizzes WHERE id = ?');
$stmt->bind_param('i', $quizId);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
if (!$quiz) {
    echo '<p>Quiz not found.</p>';
    exit;
}

// Fetch questions
$stmt = $mysqli->prepare('SELECT id, prompt, type FROM questions WHERE quiz_id = ?');
$stmt->bind_param('i', $quizId);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Questions — <?= htmlspecialchars($quiz['title']) ?></title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1>Questions for "<?= htmlspecialchars($quiz['title']) ?>"</h1>
    <div class="btn-group">
      <a href="index.php" class="btn">Back to Quizzes</a>
      <a href="question_edit.php?quiz_id=<?= $quizId ?>" class="btn">Add Question</a>
      <a href="/logout.php" class="btn">Logout</a>
    </div>
    <table class="quiz-table">
      <thead>
        <tr><th>ID</th><th>Prompt</th><th>Type</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($questions as $q): ?>
        <tr>
          <td><?= $q['id'] ?></td>
          <td><?= htmlspecialchars($q['prompt']) ?></td>
          <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($q['type']))) ?></td>
          <td>
            <a href="question_edit.php?quiz_id=<?= $quizId ?>&id=<?= $q['id'] ?>" class="btn">Edit</a>
            <a href="question_delete.php?quiz_id=<?= $quizId ?>&id=<?= $q['id'] ?>" class="btn" onclick="return confirm('Delete this question?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
