<?php
// filepath: /home/abeni/Dev/quiz-app/admin/quiz_edit.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();
$error = '';

// Fetch subjects for dropdown
$subjects = [];
$res = $mysqli->query('SELECT id, title FROM subjects');
if ($res) { $subjects = $res->fetch_all(MYSQLI_ASSOC); }

// Initialize quiz data
$quiz = ['id'=>'', 'title'=>'', 'description'=>'', 'subject_id'=>''];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subjectId = intval($_POST['subject_id']);
    if (!$title || !$subjectId) {
        $error = 'Title and subject are required.';
    } else {
        if (!empty($_POST['id'])) {
            // Update
            $stmt = $mysqli->prepare('UPDATE quizzes SET title=?, description=?, subject_id=? WHERE id=?');
            $stmt->bind_param('ssii', $title, $description, $subjectId, $_POST['id']);
            $stmt->execute();
        } else {
            // Create
            $stmt = $mysqli->prepare('INSERT INTO quizzes (title, description, subject_id, created_by) VALUES (?,?,?,?)');
            $stmt->bind_param('ssii', $title, $description, $subjectId, $user['id']);
            $stmt->execute();
        }
        header('Location: index.php');
        exit;
    }
}

// Load existing quiz if editing
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare('SELECT id, title, description, subject_id FROM quizzes WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $quiz['id'] ? 'Edit' : 'Add' ?> Quiz</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1><?= $quiz['id'] ? 'Edit' : 'Add' ?> Quiz</h1>
    <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($quiz['id']) ?>">
      <label>Title:<input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required></label><br>
      <label>Subject:
        <select name="subject_id" required>
          <option value="">Select Subject</option>
          <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['id'] ?>" <?= ($quiz['subject_id']==$sub['id'])?'selected':'' ?>>
              <?= htmlspecialchars($sub['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label><br>
      <label>Description:<textarea name="description"><?= htmlspecialchars($quiz['description']) ?></textarea></label><br>
      <button type="submit"><?= $quiz['id'] ? 'Update' : 'Create' ?> Quiz</button>
    </form>
    <p><a href="index.php">Cancel</a></p>
  </div>
</body>
</html>
