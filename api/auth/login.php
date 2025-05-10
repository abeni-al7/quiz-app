<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/jwt.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['email'], $input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

// Fetch user by email
$stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Unset password before token
unset($user['password']);

// Generate token
$token = generate_jwt($user);

echo json_encode(['token' => $token]);