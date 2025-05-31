<?php
// filepath: /home/abeni/Dev/quiz-app/admin/subjects.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

// Fetch all subjects
$result = $mysqli->query('SELECT id, title, description, created_at FROM subjects ORDER BY id DESC');
$subjects = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Subjects</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1>Subjects</h1>
    <p>Welcome, <?= htmlspecialchars($user['name']) ?> | <a href="/logout.php" class="btn">Logout</a></p>
    <p><a href="subject_edit.php" class="btn">Add New Subject</a> | <a href="index.php" class="btn">Back to Quizzes</a></p>
    <table class="quiz-table">
      <thead>
        <tr><th>ID</th><th>Title</th><th>Description</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subjects as $sub): ?>
          <tr>
            <td><?= $sub['id'] ?></td>
            <td><?= htmlspecialchars($sub['title']) ?></td>
            <td><?= htmlspecialchars($sub['description']) ?></td>
            <td>
              <a href="subject_edit.php?id=<?= $sub['id'] ?>">Edit</a> |
              <a href="subject_delete.php?id=<?= $sub['id'] ?>" onclick="return confirm('Delete this subject?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
