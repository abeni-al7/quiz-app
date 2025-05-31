<?php
// SSR Login Page (login.php)
session_start();
require_once __DIR__ . '/includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid credentials.';
        } else {
            unset($user['password']);
            $_SESSION['user'] = $user;
            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: student/index.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Quiz App</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label>Email: <input type="email" name="email" required></label><br>
                <label>Password: <input type="password" name="password" required></label><br>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</body>
</html>
