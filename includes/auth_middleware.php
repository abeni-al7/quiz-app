<?php
require_once __DIR__ . '/jwt.php';

// Enforce valid JWT and return decoded payload
function require_auth() {
    return validate_jwt();
}

// Enforce admin role
function require_admin() {
    $user = validate_jwt();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin privileges required']);
        exit;
    }
    return $user;
}