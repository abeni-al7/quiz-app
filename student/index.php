<?php
// filepath: /home/abeni/Dev/quiz-app/student/index.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
$user = require_student();

// Handle Start Quiz POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_sel'])) {
    $quizId = intval($_POST['quiz_sel']);
    // Prevent multiple scored attempts: if student already graded this quiz, redirect to first graded attempt
    $checkStmt = $mysqli->prepare('SELECT id FROM student_quizzes WHERE student_id = ? AND quiz_id = ? AND status = "graded" LIMIT 1');
    $checkStmt->bind_param('ii', $user['id'], $quizId);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    if ($existing) {
        header('Location: attempt.php?attempt_id=' . $existing['id']);
        exit;
    }
    // No prior graded attempt; create new attempt
    $stmt = $mysqli->prepare('INSERT INTO student_quizzes (student_id, quiz_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user['id'], $quizId);
    if ($stmt->execute()) {
        $attemptId = $stmt->insert_id;
        header('Location: attempt.php?attempt_id=' . $attemptId);
        exit;
    } else {
        $error = 'Failed to start quiz. Please try again.';
    }
}

// Fetch all quizzes for start form
$quizzes = [];
$res = $mysqli->query('SELECT q.id, q.title, s.title AS subject_title FROM quizzes q JOIN subjects s ON q.subject_id = s.id');
if ($res) {
    $quizzes = $res->fetch_all(MYSQLI_ASSOC);
}

// Fetch student attempts
// Apply filters if provided
$where = ['sq.student_id = ?'];
$params = ['i', $user['id']];

if (!empty($_GET['subject_id'])) {
    $where[] = 'q.subject_id = ?';
    $params[0] .= 'i';
    $params[] = intval($_GET['subject_id']);
}
if (!empty($_GET['status'])) {
    $where[] = 'sq.status = ?';
    $params[0] .= 's';
    $params[] = $_GET['status'];
}
if (!empty($_GET['search'])) {
    $where[] = 'q.title LIKE ?';
    $params[0] .= 's';
    $params[] = '%' . $_GET['search'] . '%';
}
$whereSql = implode(' AND ', $where);
$sql = "SELECT sq.id AS attempt_id, q.title, s.title AS subject_title, sq.status, sq.score, q.id AS quiz_id
        FROM student_quizzes sq
        JOIN quizzes q ON sq.quiz_id = q.id
        JOIN subjects s ON q.subject_id = s.id
        WHERE $whereSql
        ORDER BY sq.started_at DESC";
$stmt = $mysqli->prepare($sql);
// Bind parameters dynamically
call_user_func_array([$stmt, 'bind_param'], $params);
$stmt->execute();
$result = $stmt->get_result();
$attempts = $result->fetch_all(MYSQLI_ASSOC);

// Fetch subjects for filter select
$subjects = [];
$res2 = $mysqli->query('SELECT id, title FROM subjects');
if ($res2) { $subjects = $res2->fetch_all(MYSQLI_ASSOC); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Student Dashboard — Quiz App</title>
  <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Student Dashboard</h1>
    <a href="leaderboard.php" class="btn">Leaderboard</a>
    <a href="/logout.php" class="btn">Logout</a>
  </div>
  <?php if (!empty($error)): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="section">
    <h2>Start a Quiz</h2>
    <form method="post">
      <select name="quiz_sel" required>
        <option value="">Select Quiz</option>
        <?php foreach ($quizzes as $q): ?>
          <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['subject_title'] . ' - ' . $q['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Start Quiz</button>
    </form>
  </div>

  <div class="section">
    <h2>My Quiz Attempts</h2>
    <form method="get" class="filter-form">
      <select name="subject_id">
        <option value="">All Subjects</option>
        <?php foreach ($subjects as $sub): ?>
          <option value="<?= $sub['id'] ?>" <?= (!empty($_GET['subject_id']) && $_GET['subject_id']==$sub['id'])?'selected':'' ?>>
            <?= htmlspecialchars($sub['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="status">
        <option value="">All Statuses</option>
        <option value="in_progress" <?= ($_GET['status']=='in_progress')?'selected':'' ?>>In Progress</option>
        <option value="completed" <?= ($_GET['status']=='completed')?'selected':'' ?>>Completed</option>
        <option value="graded" <?= ($_GET['status']=='graded')?'selected':'' ?>>Graded</option>
      </select>
      <input type="text" name="search" placeholder="Search quizzes" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button type="submit">Apply</button>
    </form>

    <table class="quiz-table">
      <thead>
        <tr><th>Quiz</th><th>Subject</th><th>Status</th><th>Score</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($attempts as $att): ?>
          <tr>
            <td><?= htmlspecialchars($att['title']) ?></td>
            <td><?= htmlspecialchars($att['subject_title']) ?></td>
            <td><?= htmlspecialchars($att['status']) ?></td>
            <td><?= intval($att['score']) ?></td>
            <td>
              <?php if ($att['status']==='in_progress'): ?>
                <a href="attempt.php?attempt_id=<?= $att['attempt_id'] ?>">Resume</a>
              <?php else: ?>
                <a href="attempt.php?attempt_id=<?= $att['attempt_id'] ?>">View</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
