<?php
// filepath: /home/abeni/Dev/quiz-app/admin/question_edit.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

$quizId = intval($_GET['quiz_id'] ?? 0);
if (!$quizId) header('Location: questions.php');
$error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = trim($_POST['prompt']);
    $type = $_POST['type'];
    if (!$prompt || !$type) {
        $error = 'All fields are required.';
    } else {
        if (!empty($_POST['id'])) {
            $stmt = $mysqli->prepare('UPDATE questions SET prompt=?, type=? WHERE id=?');
            $stmt->bind_param('ssi', $prompt, $type, $_POST['id']);
            $stmt->execute();
            $qid = $_POST['id'];
        } else {
            $stmt = $mysqli->prepare('INSERT INTO questions (quiz_id,prompt,type) VALUES (?,?,?)');
            $stmt->bind_param('iss', $quizId, $prompt, $type);
            $stmt->execute();
            $qid = $stmt->insert_id;
        }
        header("Location: questions.php?quiz_id=$quizId");
        exit;
    }
}

// Load existing if edit
$question = ['id'=>'','prompt'=>'','type'=>'multiple_choice'];
if (!empty($_GET['id'])) {
    $stmt = $mysqli->prepare('SELECT id,prompt,type FROM questions WHERE id=?');
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $question = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $question['id']?'Edit':'Add' ?> Question</title>
<link rel="stylesheet" href="/css/styles.css"></head><body><div class="container">
<h1><?= $question['id']?'Edit':'Add' ?> Question</h1>
<?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="id" value="<?= $question['id'] ?>">
  <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
  <label>Prompt:<textarea name="prompt" required><?= htmlspecialchars($question['prompt']) ?></textarea></label><br>
  <label>Type:
    <select name="type">
      <option value="multiple_choice" <?= $question['type']==='multiple_choice'?'selected':'' ?>>Multiple Choice</option>
      <option value="true_false" <?= $question['type']==='true_false'?'selected':'' ?>>True/False</option>
    </select>
  </label><br>
  <button type="submit" class="btn"><?= $question['id']?'Update':'Create' ?> Question</button>
</form>
<?php if ($question['id']): ?>
  <p><a href="choice_edit.php?question_id=<?= $question['id'] ?>" class="btn">Manage Choices</a></p>
<?php endif; ?>
<p><a href="questions.php?quiz_id=<?= $quizId ?>" class="btn">Cancel</a></p>
</div></body></html>
