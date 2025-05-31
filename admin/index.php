<?php
// admin/index.php - SSR page for managing quizzes
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/db.php';
$user = require_admin();

// Fetch quizzes with subjects
$query = "SELECT q.id, q.title, q.description, s.title AS subject_title FROM quizzes q JOIN subjects s ON q.subject_id = s.id ORDER BY q.id DESC";
$result = $mysqli->query($query);
$quizzes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quizzes</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Quizzes</h1>
        <p>Welcome, <?= htmlspecialchars($user['name']) ?> | <a href="/logout.php" class="btn">Logout</a></p>
        <p>
            <a href="/admin/quiz_edit.php" class="btn">Create New Quiz</a>
            <button id="toggleSubjectForm" class="btn">Add Subject</button>
        </p>
        <!-- Inline Add Subject Form -->
        <div id="subjectFormContainer" style="display:none; margin-top:20px;">
            <div class="section">
                <h2>Add New Subject</h2>
                <form action="/admin/subject_edit.php" method="post">
                    <input type="hidden" name="id" value="">
                    <label for="subject_title">Title</label>
                    <input type="text" id="subject_title" name="title" required>
                    <label for="subject_description">Description</label>
                    <textarea id="subject_description" name="description" rows="3"></textarea>
                    <div style="margin-top:10px;">
                        <input type="submit" class="btn" value="Add Subject">
                        <button type="button" id="cancelSubjectForm" class="btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <table class="quiz-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?= $quiz['id'] ?></td>
                        <td><?= htmlspecialchars($quiz['title']) ?></td>
                        <td><?= htmlspecialchars($quiz['subject_title']) ?></td>
                        <td><?= htmlspecialchars($quiz['description']) ?></td>
                        <td>
                            <a href="/admin/quiz_edit.php?id=<?= $quiz['id'] ?>" class="btn">Edit</a>
                            <a href="/admin/quiz_delete.php?id=<?= $quiz['id'] ?>" class="btn">Delete</a>
                            <a href="/admin/questions.php?quiz_id=<?= $quiz['id'] ?>" class="btn">Manage Questions</a>
                            <a href="/admin/grade.php?quiz_id=<?= $quiz['id'] ?>" class="btn">View Attempts</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Toggle script -->
    <script>
        document.getElementById('toggleSubjectForm').addEventListener('click', function() {
            document.getElementById('subjectFormContainer').style.display = 'block';
        });
        document.getElementById('cancelSubjectForm').addEventListener('click', function() {
            document.getElementById('subjectFormContainer').style.display = 'none';
        });
    </script>
</body>
</html>
