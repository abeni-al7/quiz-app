<?php
// filepath: /home/abeni/Dev/quiz-app/admin/subject_edit.php
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();
$error = '';

// Handle create or update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    if (!$title) {
        $error = 'Title is required.';
    } else {
        if (!empty($_POST['id'])) {
            // Update
            $stmt = $mysqli->prepare('UPDATE subjects SET title=?, description=? WHERE id=?');
            $stmt->bind_param('ssi', $title, $description, $_POST['id']);
            $stmt->execute();
        } else {
            // Create
            $stmt = $mysqli->prepare('INSERT INTO subjects (title, description) VALUES (?,?)');
            $stmt->bind_param('ss', $title, $description);
            $stmt->execute();
        }
        header('Location: subjects.php');
        exit;
    }
}

// Load for edit
$subject = ['id'=>'', 'title'=>'', 'description'=>''];
if (isset($_GET['id'])) {
    $stmt = $mysqli->prepare('SELECT id,title,description FROM subjects WHERE id=?');
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $subject['id'] ? 'Edit' : 'Add' ?> Subject</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
  <div class="container">
    <h1><?= $subject['id'] ? 'Edit' : 'Add' ?> Subject</h1>
    <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="id" value="<?= $subject['id'] ?>">
      <label>Title:<input type="text" name="title" value="<?= htmlspecialchars($subject['title']) ?>" required></label><br>
      <label>Description:<textarea name="description"><?= htmlspecialchars($subject['description']) ?></textarea></label><br>
      <button type="submit"><?= $subject['id'] ? 'Update' : 'Create' ?></button>
    </form>
    <p><a href="subjects.php">Cancel</a></p>
  </div>
</body>
</html>
