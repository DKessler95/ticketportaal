<?php
/**
 * Logout Handler
 * 
 * Destroys user session and redirects to login page
 * Requirements: 1.2
 */

require_once __DIR__ . '/includes/functions.php';

// Initialize session if not already started
initSession();

// Destroy the session
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
redirectTo('login.php');
