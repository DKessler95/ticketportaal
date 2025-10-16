<?php
/**
 * Session Configuration
 * 
 * Secure session settings for the ICT Ticketportaal
 * This file should be included before any session operations
 */

// Prevent direct access
if (!defined('SESSION_TIMEOUT')) {
    require_once __DIR__ . '/config.php';
}

// Configure session settings before session_start()
// These settings enhance security and prevent common session attacks

// Session cookie parameters
ini_set('session.cookie_httponly', '1');        // Prevent JavaScript access to session cookie (XSS protection)
ini_set('session.use_only_cookies', '1');       // Only use cookies for session ID (no URL parameters)
ini_set('session.cookie_secure', '0');          // Only send cookie over HTTPS (set to 0 for development without SSL)
ini_set('session.use_strict_mode', '1');        // Reject uninitialized session IDs
ini_set('session.cookie_samesite', 'Lax');      // Prevent CSRF attacks (Lax for development, Strict for production)

// Session lifetime and garbage collection
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);  // Session lifetime in seconds
ini_set('session.gc_probability', 1);                // Probability of garbage collection
ini_set('session.gc_divisor', 100);                  // 1% chance of GC on each request

// Session ID settings
ini_set('session.entropy_length', '32');        // Length of session ID (more entropy = more secure)
ini_set('session.hash_function', 'sha256');     // Hash function for session ID
ini_set('session.hash_bits_per_character', 5);  // Bits per character in session ID

// Session name (avoid default PHPSESSID which reveals PHP usage)
ini_set('session.name', 'ICT_PORTAL_SESSION');

// Prevent session fixation by regenerating ID on privilege escalation
// This is handled in the login process

// Session save path (optional - uncomment and set if needed)
// ini_set('session.save_path', __DIR__ . '/../sessions/');

