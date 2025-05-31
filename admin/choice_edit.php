<?php
// filepath: /home/abeni/Dev/quiz-app/admin/choice_edit.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

$questionId = intval($_GET['question_id'] ?? $_POST['question_id'] ?? 0);
if (!$questionId) {
    header('Location: questions.php'); exit;
}
$error = '';

// Fetch question prompt
$stmt = $mysqli->prepare('SELECT prompt, quiz_id FROM questions WHERE id = ?');
$stmt->bind_param('i', $questionId);
$stmt->execute();
$question = $stmt->get_result()->fetch_assoc();
if (!$question) { echo '<p>Question not found.</p>'; exit; }
// store quiz context for back link
$quizId = $question['quiz_id'];

// Handle POST create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $isCorrect = isset($_POST['is_correct']) ? 1 : 0;
    $id = intval($_POST['id'] ?? 0);
    if (!$content) {
        $error = 'Content is required.';
    } else {
        if ($id) {
            $stmt = $mysqli->prepare('UPDATE choices SET content = ?, is_correct = ? WHERE id = ? AND question_id = ?');
            $stmt->bind_param('siii', $content, $isCorrect, $id, $questionId);
            $stmt->execute();
        } else {
            $stmt = $mysqli->prepare('INSERT INTO choices (question_id, content, is_correct) VALUES (?,?,?)');
            $stmt->bind_param('isi', $questionId, $content, $isCorrect);
            $stmt->execute();
        }
        header("Location: choice_edit.php?question_id=$questionId"); exit;
    }
}

// Load existing choice for edit
$choice = ['id'=>'','content'=>'','is_correct'=>0];
if (!empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $mysqli->prepare('SELECT id, content, is_correct FROM choices WHERE id = ? AND question_id = ?');
    $stmt->bind_param('ii', $id, $questionId);
    $stmt->execute();
    $choice = $stmt->get_result()->fetch_assoc();
    if (!$choice) { echo '<p>Choice not found.</p>'; exit; }
}

// Fetch all choices
$stmt = $mysqli->prepare('SELECT id, content, is_correct FROM choices WHERE question_id = ? ORDER BY id');
$stmt->bind_param('i', $questionId);
$stmt->execute();
$choices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Choices — <?= htmlspecialchars($question['prompt']) ?></title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1>Choices for Question</h1>
    <div class="btn-group">
      <a href="/admin/questions.php?quiz_id=<?= $quizId ?>" class="btn">Back to Questions</a>
      <a href="/logout.php" class="btn">Logout</a>
    </div>
    <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <section class="section">
      <h2><?= $choice['id'] ? 'Edit' : 'Add' ?> Choice</h2>
      <form method="post">
        <input type="hidden" name="id" value="<?= $choice['id'] ?>">
        <input type="hidden" name="question_id" value="<?= $questionId ?>">
        <label>Content:<input type="text" name="content" required value="<?= htmlspecialchars($choice['content']) ?>"></label><br>
        <label><input type="checkbox" name="is_correct" <?= $choice['is_correct'] ? 'checked' : '' ?>> Mark as correct</label><br>
        <button type="submit" class="btn"><?= $choice['id'] ? 'Update' : 'Create' ?> Choice</button>
      </form>
    </section>

    <section class="section">
      <h2>Existing Choices</h2>
      <table class="quiz-table">
        <thead><tr><th>ID</th><th>Content</th><th>Correct</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($choices as $ch): ?>
          <tr>
            <td><?= $ch['id'] ?></td>
            <td><?= htmlspecialchars($ch['content']) ?></td>
            <td><?= $ch['is_correct'] ? 'Yes' : 'No' ?></td>
            <td>
              <a href="choice_edit.php?question_id=<?= $questionId ?>&id=<?= $ch['id'] ?>" class="btn">Edit</a>
              <a href="choice_delete.php?question_id=<?= $questionId ?>&id=<?= $ch['id'] ?>" class="btn" onclick="return confirm('Delete this choice?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>
</body>
</html>
