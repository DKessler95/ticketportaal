<?php
// Simple session test - no includes
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

// Use PHPSESSID to match existing session
session_name('PHPSESSID');
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'has_user_id' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'role' => $_SESSION['role'] ?? 'NOT SET'
], JSON_PRETTY_PRINT);
