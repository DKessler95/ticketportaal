<?php
/**
 * Test Session Debug Script
 * Check if session is working correctly
 */

require_once '../config/config.php';
require_once '../config/session.php';
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'session_status' => session_status(),
    'session_name' => session_name()
], JSON_PRETTY_PRINT);
