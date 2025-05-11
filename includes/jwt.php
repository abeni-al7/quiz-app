<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Secret key for JWT signing (change this to a secure random key)
const JWT_SECRET = 'secure_random_string';
const JWT_ALGO = 'HS256';

// Generate JWT for a user
function generate_jwt($user) {
    $payload = [
        'iat' => time(),
        'exp' => time() + (60*60*24), // 1 day expiration
        'sub' => $user['id'],
        'role' => $user['role'],
        'name' => $user['name'],
        'email' => $user['email']
    ];
    return JWT::encode($payload, JWT_SECRET, JWT_ALGO);
}

// Validate incoming JWT from Authorization header
function validate_jwt() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing Authorization header']);
        exit;
    }
    if (preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Authorization header']);
        exit;
    }
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGO));
        return (array) $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
}
