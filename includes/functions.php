<?php
/**
 * Helper Functions
 * 
 * Centralized utility functions for the ICT Ticketportaal
 * Includes error logging, input sanitization, CSRF protection, and authentication helpers
 */

// Load configuration files
require_once __DIR__ . '/../config/config.php';

// Load language support
require_once __DIR__ . '/language.php';

// Handle language switching
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    // Redirect to remove lang parameter from URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $params);
        unset($params['lang']);
        if (!empty($params)) {
            $redirect_url .= '?' . http_build_query($params);
        }
    }
    header('Location: ' . $redirect_url);
    exit;
}

/**
 * Log error to file with context
 * 
 * @param string $context Error context (e.g., 'Database', 'Authentication')
 * @param string $message Error message
 * @param array $data Additional data to log
 * @return void
 */
function logError($context, $message, $data = []) {
    // Ensure logs directory exists
    if (!file_exists(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'context' => $context,
        'message' => $message,
        'data' => $data,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $logFile = LOG_PATH . 'app_' . date('Y-m-d') . '.log';
    $logLine = json_encode($logEntry) . PHP_EOL;
    
    error_log($logLine, 3, $logFile);
}

/**
 * Sanitize user input
 * 
 * @param string $input Raw input string
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    // Trim whitespace
    $input = trim($input);
    
    // Remove null bytes
    $input = str_replace(chr(0), '', $input);
    
    // Convert special characters to HTML entities
    $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $input;
}

/**
 * Validate email address
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    // Sanitize email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Additional validation: check for valid domain
    $domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
        return false;
    }
    
    return true;
}

/**
 * Validate required field
 * 
 * @param mixed $value Value to validate
 * @param string $fieldName Field name for error message
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateRequired($value, $fieldName = 'Field') {
    if (empty($value) && $value !== '0') {
        return ['valid' => false, 'error' => $fieldName . ' is required'];
    }
    return ['valid' => true, 'error' => null];
}

/**
 * Validate field length
 * 
 * @param string $value Value to validate
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @param string $fieldName Field name for error message
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateLength($value, $min, $max, $fieldName = 'Field') {
    $length = strlen($value);
    
    if ($length < $min) {
        return ['valid' => false, 'error' => $fieldName . ' must be at least ' . $min . ' characters'];
    }
    
    if ($length > $max) {
        return ['valid' => false, 'error' => $fieldName . ' must not exceed ' . $max . ' characters'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validatePassword($password) {
    // Minimum 8 characters
    if (strlen($password) < 8) {
        return ['valid' => false, 'error' => 'Password must be at least 8 characters'];
    }
    
    // Must contain at least one letter
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one letter'];
    }
    
    // Must contain at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one number'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validate integer value
 * 
 * @param mixed $value Value to validate
 * @param string $fieldName Field name for error message
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateInteger($value, $fieldName = 'Field') {
    if (!filter_var($value, FILTER_VALIDATE_INT)) {
        return ['valid' => false, 'error' => $fieldName . ' must be a valid integer'];
    }
    return ['valid' => true, 'error' => null];
}

/**
 * Validate enum value
 * 
 * @param mixed $value Value to validate
 * @param array $allowedValues Allowed values
 * @param string $fieldName Field name for error message
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateEnum($value, $allowedValues, $fieldName = 'Field') {
    if (!in_array($value, $allowedValues, true)) {
        return ['valid' => false, 'error' => $fieldName . ' must be one of: ' . implode(', ', $allowedValues)];
    }
    return ['valid' => true, 'error' => null];
}

/**
 * Sanitize text for database storage (removes HTML but preserves line breaks)
 * 
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function sanitizeText($text) {
    // Remove null bytes
    $text = str_replace(chr(0), '', $text);
    
    // Trim whitespace
    $text = trim($text);
    
    // Strip tags but preserve line breaks
    $text = strip_tags($text);
    
    return $text;
}

/**
 * Sanitize HTML content (for rich text fields like KB articles)
 * 
 * @param string $html HTML content to sanitize
 * @return string Sanitized HTML
 */
function sanitizeHTML($html) {
    // Allow safe HTML tags including images, tables, and formatting
    $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><a><h1><h2><h3><h4><h5><h6>' .
                   '<blockquote><code><pre><img><table><thead><tbody><tr><th><td>' .
                   '<span><div><hr><sup><sub>';
    
    // Strip disallowed tags
    $html = strip_tags($html, $allowedTags);
    
    // Remove javascript: and data: protocols from links and images
    $html = preg_replace('/(<a[^>]+href=[\"\']?)javascript:/i', '$1', $html);
    $html = preg_replace('/(<a[^>]+href=[\"\']?)data:/i', '$1', $html);
    $html = preg_replace('/(<img[^>]+src=[\"\']?)javascript:/i', '$1', $html);
    
    // Remove event handlers
    $html = preg_replace('/(<[^>]+)on\w+\s*=\s*["\'][^"\']*["\']/i', '$1', $html);
    
    return $html;
}

/**
 * Generate CSRF token and store in session
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token hidden input field
 * 
 * @return void
 */
function outputCSRFField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . escapeOutput($token) . '">';
}

/**
 * Verify CSRF token from POST request
 * Dies with error message if token is invalid
 * 
 * @return void
 */
function verifyCSRFToken() {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logError('Security', 'CSRF token validation failed', [
            'post_data' => array_keys($_POST),
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ]);
        die('Security validation failed. Please try again.');
    }
}

/**
 * Redirect to specified URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code (default: 302)
 * @return void
 */
function redirectTo($url, $statusCode = 302) {
    // Prevent header injection
    $url = str_replace(["\r", "\n"], '', $url);
    
    header("Location: $url", true, $statusCode);
    exit();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function checkLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user_id exists in session
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        
        if ($inactiveTime > SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Require user to be logged in, redirect to login if not
 * 
 * @param string $redirectUrl URL to redirect to after login (optional)
 * @return void
 */
function requireLogin($redirectUrl = null) {
    if (!checkLogin()) {
        if ($redirectUrl) {
            $_SESSION['redirect_after_login'] = $redirectUrl;
        }
        redirectTo(SITE_URL . '/login.php');
    }
}

/**
 * Check if user has specific role
 * 
 * @param string|array $allowedRoles Role(s) to check
 * @return bool True if user has role, false otherwise
 */
function checkRole($allowedRoles) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    if (is_array($allowedRoles)) {
        return in_array($_SESSION['role'], $allowedRoles);
    }
    
    return $_SESSION['role'] === $allowedRoles;
}

/**
 * Require user to have specific role, redirect if not
 * 
 * @param string|array $allowedRoles Role(s) required
 * @param string $redirectUrl URL to redirect to if unauthorized (default: dashboard)
 * @return void
 */
function requireRole($allowedRoles, $redirectUrl = null) {
    requireLogin();
    
    if (!checkRole($allowedRoles)) {
        if ($redirectUrl === null) {
            // Redirect to appropriate dashboard based on user's role
            $role = $_SESSION['role'] ?? 'user';
            switch ($role) {
                case 'admin':
                    $redirectUrl = SITE_URL . '/admin/index.php';
                    break;
                case 'agent':
                    $redirectUrl = SITE_URL . '/agent/dashboard.php';
                    break;
                default:
                    $redirectUrl = SITE_URL . '/user/dashboard.php';
            }
        }
        redirectTo($redirectUrl);
    }
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format (default: 'd-m-Y H:i')
 * @return string Formatted date
 */
function formatDate($date, $format = 'd-m-Y H:i') {
    if (empty($date)) {
        return '-';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

/**
 * Get status badge HTML
 * 
 * @param string $status Ticket status
 * @return string HTML badge
 */
function getStatusBadge($status) {
    $badges = [
        'open' => '<span class="badge bg-primary">Open</span>',
        'in_progress' => '<span class="badge bg-info">In Behandeling</span>',
        'pending' => '<span class="badge bg-warning">Wachtend</span>',
        'resolved' => '<span class="badge bg-success">Opgelost</span>',
        'closed' => '<span class="badge bg-secondary">Gesloten</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get priority badge HTML
 * 
 * @param string $priority Ticket priority
 * @return string HTML badge
 */
function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge bg-secondary">Laag</span>',
        'medium' => '<span class="badge bg-info">Gemiddeld</span>',
        'high' => '<span class="badge bg-warning">Hoog</span>',
        'urgent' => '<span class="badge bg-danger">Urgent</span>'
    ];
    
    return $badges[$priority] ?? '<span class="badge bg-secondary">' . ucfirst($priority) . '</span>';
}

/**
 * Get role badge HTML
 * 
 * @param string $role User role
 * @return string HTML badge
 */
function getRoleBadge($role) {
    $badges = [
        'user' => '<span class="badge bg-primary">Gebruiker</span>',
        'agent' => '<span class="badge bg-success">Agent</span>',
        'admin' => '<span class="badge bg-danger">Beheerder</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">' . ucfirst($role) . '</span>';
}

/**
 * Escape output for safe HTML display
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Generate random filename for uploads
 * 
 * @param string $extension File extension
 * @return string Random filename
 */
function generateRandomFilename($extension) {
    return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @return array ['success' => bool, 'error' => string|null]
 */
function validateFileUpload($file) {
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed (' . (MAX_FILE_SIZE / 1048576) . 'MB)'];
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    return ['success' => true, 'error' => null];
}

/**
 * Initialize secure session
 * 
 * @return void
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Load session configuration BEFORE starting session
        require_once __DIR__ . '/../config/session.php';
        
        // Start the session (session name is already set in session.php)
        session_start();
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // Regenerate every 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Initialize last activity timestamp for timeout
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
    }
}

/**
 * Redirect user to role-specific dashboard
 * 
 * @param string $role User role (optional, uses session role if not provided)
 * @return void
 */
function redirectToDashboard($role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Use provided role or get from session
    if ($role === null) {
        $role = $_SESSION['role'] ?? 'user';
    }
    
    // Determine dashboard URL based on role
    switch ($role) {
        case 'admin':
            $dashboardUrl = SITE_URL . '/admin/index.php';
            break;
        case 'agent':
            $dashboardUrl = SITE_URL . '/agent/dashboard.php';
            break;
        case 'user':
        default:
            $dashboardUrl = SITE_URL . '/user/dashboard.php';
            break;
    }
    
    redirectTo($dashboardUrl);
}

/**
 * Get dashboard URL for specific role
 * 
 * @param string $role User role
 * @return string Dashboard URL
 */
function getDashboardUrl($role) {
    switch ($role) {
        case 'admin':
            return SITE_URL . '/admin/index.php';
        case 'agent':
            return SITE_URL . '/agent/dashboard.php';
        case 'user':
        default:
            return SITE_URL . '/user/dashboard.php';
    }
}

/**
 * Get current logged-in user information
 * 
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * 
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['role'] ?? null;
}

/**
 * Get base URL of the application
 * 
 * @return string Base URL
 */
function getBaseUrl() {
    return SITE_URL;
}

/**
 * Check if current page is active for navigation highlighting
 * 
 * @param string $page Page filename to check
 * @param string|null $folder Optional folder name (user, agent, admin)
 * @return string 'active' if current page matches, empty string otherwise
 */
function isActive($page, $folder = null) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentFolder = basename(dirname($_SERVER['PHP_SELF']));
    
    // Check if page matches
    $pageMatches = ($currentPage === $page);
    
    // Check if folder matches (if specified)
    $folderMatches = ($folder === null || $currentFolder === $folder);
    
    return ($pageMatches && $folderMatches) ? 'active' : '';
}
