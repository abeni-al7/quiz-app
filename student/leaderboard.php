<?php
// filepath: /home/abeni/Dev/quiz-app/student/leaderboard.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
$user = require_student();

// Fetch top students, summing only first graded attempt per quiz
$sql = <<<SQL
SELECT u.id AS student_id, u.name, COALESCE(SUM(sq.score), 0) AS total_score
FROM users u
LEFT JOIN (
    SELECT student_id, quiz_id, score
    FROM student_quizzes
    WHERE status = 'graded' AND id IN (
        SELECT MIN(id) FROM student_quizzes WHERE status = 'graded' GROUP BY student_id, quiz_id
    )
) sq ON u.id = sq.student_id
WHERE u.role = 'student'
GROUP BY u.id, u.name
ORDER BY total_score DESC
LIMIT 15;
SQL;
$result = $mysqli->query($sql);
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Leaderboard — Quiz App</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Leaderboard</h1>
      <div>
        <a href="index.php" class="btn">Dashboard</a>
        <a href="/logout.php" class="btn">Logout</a>
      </div>
    </div>
    <div class="section">
      <table>
        <thead>
          <tr><th>Rank</th><th>Student</th><th>Score</th></tr>
        </thead>
        <tbody>
          <?php $rank = 1; foreach ($rows as $row): ?>
            <tr>
              <td><?= $rank++ ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= intval($row['total_score']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
