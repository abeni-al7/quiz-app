<?php
// SSR Registration Page (register.php)
session_start();
require_once __DIR__ . '/includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hash);
            if ($stmt->execute()) {
                $_SESSION['user'] = [
                    'id' => $stmt->insert_id,
                    'name' => $name,
                    'email' => $email,
                    'role' => 'student'
                ];
                header('Location: student/index.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Quiz App</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Register</h2>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label>Name: <input type="text" name="name" required></label><br>
                <label>Email: <input type="email" name="email" required></label><br>
                <label>Password: <input type="password" name="password" required></label><br>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
