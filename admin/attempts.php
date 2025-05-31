<?php
// filepath: /home/abeni/Dev/quiz-app/admin/attempts.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

// Fetch all student attempts
$sql = "SELECT sq.id, u.name AS student_name, q.title AS quiz_title, sq.status, sq.score, sq.started_at
        FROM student_quizzes sq
        JOIN users u ON sq.student_id = u.id
        JOIN quizzes q ON sq.quiz_id = q.id
        ORDER BY sq.started_at DESC";
$result = $mysqli->query($sql);

// Initialize attempts array
$attempts = [];
if ($result) {
    $attempts = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Quiz Attempts</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1>Quiz Attempts</h1>
    <p>Welcome, <?= htmlspecialchars($user['name']) ?> | <a href="/logout.php">Logout</a> | <a href="index.php">Back to Quizzes</a></p>
    <table class="quiz-table">
      <thead>
        <tr><th>ID</th><th>Student</th><th>Quiz</th><th>Status</th><th>Score</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($attempts as $att): ?>
          <tr>
            <td><?= $att['id'] ?></td>
            <td><?= htmlspecialchars($att['student_name']) ?></td>
            <td><?= htmlspecialchars($att['quiz_title']) ?></td>
            <td><?= htmlspecialchars($att['status']) ?></td>
            <td><?= intval($att['score']) ?></td>
            <td>
              <a href="grade.php?attempt_id=<?= $att['id'] ?>" class="btn"><?= $att['status']==='graded' ? 'View' : 'Grade' ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
