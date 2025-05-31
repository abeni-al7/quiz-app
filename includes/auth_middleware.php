<?php
// Session-based authentication middleware
session_start();

// Enforce user is logged in
function require_auth() {
    if (!isset($_SESSION['user'])) {
        header('Location: /login.php');
        exit;
    }
    return $_SESSION['user'];
}

// Enforce admin role
function require_admin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
    return $_SESSION['user'];
}

// Enforce student role
function require_student() {
    $user = require_auth();
    if ($user['role'] !== 'student') {
        header('Location: /login.php');
        exit;
    }
    return $user;
}