<?php
header('Content-Type: application/json');
// Enable verbose errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/jwt.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['name'], $input['email'], $input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $password = $input['password'];

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email or password too short']);
        exit;
    }

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user. Default role is student
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);
    $userId = $pdo->lastInsertId();

    // Fetch inserted user
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Generate token
    $token = generate_jwt($user);

    echo json_encode(['token' => $token]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}