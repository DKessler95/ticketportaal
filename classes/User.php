<?php
/**
 * User Class
 * 
 * Handles user authentication, registration, and profile management
 * Implements secure password hashing, session management, and role-based access control
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/functions.php';

class User {
    private $db;
    private $error = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new user
     * 
     * @param string $email User email
     * @param string $password User password
     * @param string $firstName User first name
     * @param string $lastName User last name
     * @param string $department User department (optional)
     * @param string $role User role (default: 'user')
     * @return int|false User ID on success, false on failure
     */
    public function register($email, $password, $firstName, $lastName, $department = null, $role = 'user') {
        try {
            // Validate inputs
            $errors = $this->validateRegistration($email, $password, $firstName, $lastName);
            if (!empty($errors)) {
                $this->error = implode(', ', $errors);
                return false;
            }
            
            // Check if email already exists
            $existingUser = $this->db->fetchOne(
                "SELECT user_id FROM users WHERE email = ?",
                [$email]
            );
            
            if ($existingUser) {
                $this->error = 'Email address already registered';
                logError('User Registration', 'Duplicate email attempt', ['email' => $email]);
                return false;
            }
            
            // Hash password using bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Insert user into database
            $sql = "INSERT INTO users (email, password, first_name, last_name, department, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $result = $this->db->execute($sql, [
                $email,
                $hashedPassword,
                $firstName,
                $lastName,
                $department,
                $role
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                logError('User Registration', 'User registered successfully', [
                    'user_id' => $userId,
                    'email' => $email
                ]);
                return $userId;
            }
            
            $this->error = 'Failed to create user account';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Registration failed';
            logError('User Registration', 'Exception during registration', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return false;
        }
    }
    
    /**
     * Authenticate user and create session
     * 
     * @param string $email User email
     * @param string $password User password
     * @return bool True on success, false on failure
     */
    public function login($email, $password) {
        try {
            // Check for account lockout
            if ($this->isAccountLocked($email)) {
                $this->error = 'Account temporarily locked due to multiple failed login attempts. Please try again later.';
                return false;
            }
            
            // Fetch user from database
            $user = $this->db->fetchOne(
                "SELECT user_id, email, password, first_name, last_name, role, is_active 
                 FROM users WHERE email = ?",
                [$email]
            );
            
            // Check if user exists
            if (!$user) {
                $this->recordFailedLogin($email);
                $this->error = 'Invalid email or password';
                logError('User Login', 'Login attempt with non-existent email', ['email' => $email]);
                return false;
            }
            
            // Check if account is active
            if (!$user['is_active']) {
                $this->error = 'Account is deactivated. Please contact administrator.';
                logError('User Login', 'Login attempt on deactivated account', [
                    'user_id' => $user['user_id'],
                    'email' => $email
                ]);
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedLogin($email);
                $this->error = 'Invalid email or password';
                logError('User Login', 'Failed login attempt - invalid password', ['email' => $email]);
                return false;
            }
            
            // Clear failed login attempts
            $this->clearFailedLogins($email);
            
            // Update last login timestamp
            $this->db->execute(
                "UPDATE users SET last_login = NOW() WHERE user_id = ?",
                [$user['user_id']]
            );
            
            // Create session
            $this->createSession($user);
            
            logError('User Login', 'Successful login', [
                'user_id' => $user['user_id'],
                'email' => $email
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->error = 'Login failed';
            logError('User Login', 'Exception during login', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return false;
        }
    }
    
    /**
     * Create user session
     * 
     * @param array $user User data
     * @return void
     */
    private function createSession($user) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            initSession();
        }
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Store user data in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
    }
    
    /**
     * Logout user and destroy session
     * 
     * @return void
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log the logout
        if (isset($_SESSION['user_id'])) {
            logError('User Logout', 'User logged out', ['user_id' => $_SESSION['user_id']]);
        }
        
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|false User data on success, false on failure
     */
    public function getUserById($userId) {
        try {
            $user = $this->db->fetchOne(
                "SELECT user_id, email, first_name, last_name, department, role, 
                        created_at, last_login, is_active 
                 FROM users WHERE user_id = ?",
                [$userId]
            );
            
            return $user ?: false;
            
        } catch (Exception $e) {
            $this->error = 'Failed to fetch user';
            logError('User Fetch', 'Exception fetching user', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Update user information
     * 
     * @param int $userId User ID
     * @param array $data User data to update
     * @return bool True on success, false on failure
     */
    public function updateUser($userId, $data) {
        try {
            // Build update query dynamically based on provided data
            $allowedFields = ['email', 'first_name', 'last_name', 'department', 'role', 'is_active'];
            $updateFields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updateFields)) {
                $this->error = 'No valid fields to update';
                return false;
            }
            
            // Add user_id to params
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                logError('User Update', 'User updated successfully', [
                    'user_id' => $userId,
                    'fields' => array_keys($data)
                ]);
                return true;
            }
            
            $this->error = 'Failed to update user';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Update failed';
            logError('User Update', 'Exception during update', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Validate registration data
     * 
     * @param string $email Email address
     * @param string $password Password
     * @param string $firstName First name
     * @param string $lastName Last name
     * @return array Array of validation errors
     */
    private function validateRegistration($email, $password, $firstName, $lastName) {
        $errors = [];
        
        // Validate email
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Invalid email address';
        }
        
        // Validate password
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain both letters and numbers';
        }
        
        // Validate first name
        if (empty($firstName)) {
            $errors[] = 'First name is required';
        } elseif (strlen($firstName) > 100) {
            $errors[] = 'First name is too long';
        }
        
        // Validate last name
        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        } elseif (strlen($lastName) > 100) {
            $errors[] = 'Last name is too long';
        }
        
        return $errors;
    }
    
    /**
     * Record failed login attempt
     * 
     * @param string $email Email address
     * @return void
     */
    private function recordFailedLogin($email) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'failed_login_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $_SESSION[$key]['attempts']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
    
    /**
     * Check if account is locked due to failed login attempts
     * 
     * @param string $email Email address
     * @return bool True if locked, false otherwise
     */
    private function isAccountLocked($email) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'failed_login_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $data = $_SESSION[$key];
        
        // Check if attempts are within the time window
        $timeSinceFirst = time() - $data['first_attempt'];
        
        if ($timeSinceFirst > LOGIN_ATTEMPT_WINDOW) {
            // Time window expired, clear attempts
            unset($_SESSION[$key]);
            return false;
        }
        
        // Check if account should be locked
        if ($data['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $timeSinceLast = time() - $data['last_attempt'];
            
            if ($timeSinceLast < ACCOUNT_LOCK_DURATION) {
                return true;
            } else {
                // Lock duration expired, clear attempts
                unset($_SESSION[$key]);
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Clear failed login attempts
     * 
     * @param string $email Email address
     * @return void
     */
    private function clearFailedLogins($email) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'failed_login_' . md5($email);
        unset($_SESSION[$key]);
    }
    
    /**
     * Get last error message
     * 
     * @return string|null Error message
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Get role-based redirect URL
     * 
     * @param string $role User role
     * @return string Redirect URL
     */
    public function getRoleRedirectUrl($role) {
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
     * Request password reset
     * Generate secure token and store in database
     * 
     * @param string $email User email
     * @return string|false Reset token on success, false on failure
     */
    public function requestPasswordReset($email) {
        try {
            // Check if user exists
            $user = $this->db->fetchOne(
                "SELECT user_id, email, first_name FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if (!$user) {
                // Don't reveal if email exists or not for security
                $this->error = 'If the email exists, a reset link will be sent';
                logError('Password Reset', 'Reset requested for non-existent email', ['email' => $email]);
                return false;
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            
            // Calculate expiration time (1 hour from now)
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            
            // Delete any existing reset tokens for this user
            $this->db->execute(
                "DELETE FROM password_resets WHERE user_id = ?",
                [$user['user_id']]
            );
            
            // Insert new reset token
            $sql = "INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                    VALUES (?, ?, ?, NOW())";
            
            $result = $this->db->execute($sql, [
                $user['user_id'],
                $token,
                $expiresAt
            ]);
            
            if ($result) {
                logError('Password Reset', 'Reset token generated', [
                    'user_id' => $user['user_id'],
                    'email' => $email
                ]);
                return $token;
            }
            
            $this->error = 'Failed to generate reset token';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Password reset request failed';
            logError('Password Reset', 'Exception during reset request', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return false;
        }
    }
    
    /**
     * Reset password using token
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool True on success, false on failure
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                $this->error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
                return false;
            }
            
            if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                $this->error = 'Password must contain both letters and numbers';
                return false;
            }
            
            // Fetch reset token from database
            $resetData = $this->db->fetchOne(
                "SELECT pr.reset_id, pr.user_id, pr.expires_at, u.email 
                 FROM password_resets pr
                 JOIN users u ON pr.user_id = u.user_id
                 WHERE pr.token = ?",
                [$token]
            );
            
            if (!$resetData) {
                $this->error = 'Invalid or expired reset token';
                logError('Password Reset', 'Invalid token used', ['token' => substr($token, 0, 10) . '...']);
                return false;
            }
            
            // Check if token has expired
            if (strtotime($resetData['expires_at']) < time()) {
                $this->error = 'Reset token has expired';
                logError('Password Reset', 'Expired token used', [
                    'user_id' => $resetData['user_id'],
                    'expires_at' => $resetData['expires_at']
                ]);
                
                // Delete expired token
                $this->db->execute(
                    "DELETE FROM password_resets WHERE reset_id = ?",
                    [$resetData['reset_id']]
                );
                
                return false;
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Update user password
            $result = $this->db->execute(
                "UPDATE users SET password = ? WHERE user_id = ?",
                [$hashedPassword, $resetData['user_id']]
            );
            
            if ($result) {
                // Delete used reset token
                $this->db->execute(
                    "DELETE FROM password_resets WHERE reset_id = ?",
                    [$resetData['reset_id']]
                );
                
                logError('Password Reset', 'Password reset successful', [
                    'user_id' => $resetData['user_id'],
                    'email' => $resetData['email']
                ]);
                
                return true;
            }
            
            $this->error = 'Failed to update password';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Password reset failed';
            logError('Password Reset', 'Exception during password reset', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Validate reset token
     * 
     * @param string $token Reset token
     * @return bool True if valid, false otherwise
     */
    public function validateResetToken($token) {
        try {
            $resetData = $this->db->fetchOne(
                "SELECT reset_id, expires_at FROM password_resets WHERE token = ?",
                [$token]
            );
            
            if (!$resetData) {
                return false;
            }
            
            // Check if token has expired
            if (strtotime($resetData['expires_at']) < time()) {
                // Delete expired token
                $this->db->execute(
                    "DELETE FROM password_resets WHERE reset_id = ?",
                    [$resetData['reset_id']]
                );
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            logError('Password Reset', 'Exception validating token', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Check if user has specific permission based on role
     * 
     * @param int $userId User ID
     * @param string $permission Permission to check (role name)
     * @return bool True if user has permission, false otherwise
     */
    public function checkPermission($userId, $permission) {
        try {
            $user = $this->db->fetchOne(
                "SELECT role, is_active FROM users WHERE user_id = ?",
                [$userId]
            );
            
            if (!$user || !$user['is_active']) {
                return false;
            }
            
            // Admin has all permissions
            if ($user['role'] === 'admin') {
                return true;
            }
            
            // Check if user's role matches the required permission
            return $user['role'] === $permission;
            
        } catch (Exception $e) {
            logError('Permission Check', 'Exception checking permission', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'permission' => $permission
            ]);
            return false;
        }
    }
    
    /**
     * Get all users with a specific role
     * 
     * @param string $role Role to filter by ('user', 'agent', 'admin')
     * @return array|false Array of users on success, false on failure
     */
    public function getUsersByRole($role) {
        try {
            // Validate role
            $validRoles = ['user', 'agent', 'admin'];
            if (!in_array($role, $validRoles)) {
                $this->error = 'Invalid role specified';
                return false;
            }
            
            $users = $this->db->fetchAll(
                "SELECT user_id, email, first_name, last_name, department, role, 
                        created_at, last_login, is_active 
                 FROM users 
                 WHERE role = ? 
                 ORDER BY last_name, first_name",
                [$role]
            );
            
            return $users ?: [];
            
        } catch (Exception $e) {
            $this->error = 'Failed to fetch users by role';
            logError('User Fetch', 'Exception fetching users by role', [
                'error' => $e->getMessage(),
                'role' => $role
            ]);
            return false;
        }
    }
    
    /**
     * Get all users (admin function)
     * 
     * @return array|false Array of all users on success, false on failure
     */
    public function getAllUsers() {
        try {
            $users = $this->db->fetchAll(
                "SELECT user_id, email, first_name, last_name, department, role, 
                        created_at, last_login, is_active 
                 FROM users 
                 ORDER BY last_name, first_name"
            );
            
            return $users ?: [];
            
        } catch (Exception $e) {
            $this->error = 'Failed to fetch all users';
            logError('User Fetch', 'Exception fetching all users', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Create a new user (admin function)
     * 
     * @param string $email User email
     * @param string $password User password
     * @param string $firstName User first name
     * @param string $lastName User last name
     * @param string $department User department (optional)
     * @param string $role User role (default: 'user')
     * @return int|false User ID on success, false on failure
     */
    public function createUser($email, $password, $firstName, $lastName, $department = null, $role = 'user') {
        // Use the existing register method which already handles validation and user creation
        return $this->register($email, $password, $firstName, $lastName, $department, $role);
    }
    
    /**
     * Update user role (admin function)
     * 
     * @param int $userId User ID to update
     * @param string $newRole New role to assign
     * @param int $adminId Admin user ID performing the action
     * @return bool True on success, false on failure
     */
    public function updateUserRole($userId, $newRole, $adminId) {
        try {
            // Validate role
            $validRoles = ['user', 'agent', 'admin'];
            if (!in_array($newRole, $validRoles)) {
                $this->error = 'Invalid role specified';
                return false;
            }
            
            // Prevent admin from changing their own role
            if ($userId == $adminId) {
                $this->error = 'You cannot change your own role';
                logError('User Role Update', 'Admin attempted to change own role', [
                    'admin_id' => $adminId,
                    'user_id' => $userId
                ]);
                return false;
            }
            
            // Check if user exists
            $user = $this->getUserById($userId);
            if (!$user) {
                $this->error = 'User not found';
                return false;
            }
            
            // Update role
            $result = $this->db->execute(
                "UPDATE users SET role = ? WHERE user_id = ?",
                [$newRole, $userId]
            );
            
            if ($result) {
                logError('User Role Update', 'User role updated successfully', [
                    'user_id' => $userId,
                    'old_role' => $user['role'],
                    'new_role' => $newRole,
                    'admin_id' => $adminId
                ]);
                return true;
            }
            
            $this->error = 'Failed to update user role';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Role update failed';
            logError('User Role Update', 'Exception during role update', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'new_role' => $newRole
            ]);
            return false;
        }
    }
    
    /**
     * Deactivate user account (admin function)
     * 
     * @param int $userId User ID to deactivate
     * @param int $adminId Admin user ID performing the action
     * @return bool True on success, false on failure
     */
    public function deactivateUser($userId, $adminId) {
        try {
            // Prevent admin from deactivating their own account
            if ($userId == $adminId) {
                $this->error = 'You cannot deactivate your own account';
                logError('User Deactivation', 'Admin attempted to deactivate own account', [
                    'admin_id' => $adminId,
                    'user_id' => $userId
                ]);
                return false;
            }
            
            // Check if user exists
            $user = $this->getUserById($userId);
            if (!$user) {
                $this->error = 'User not found';
                return false;
            }
            
            // Check if user is already inactive
            if (!$user['is_active']) {
                $this->error = 'User is already deactivated';
                return false;
            }
            
            // Deactivate user
            $result = $this->db->execute(
                "UPDATE users SET is_active = 0 WHERE user_id = ?",
                [$userId]
            );
            
            if ($result) {
                logError('User Deactivation', 'User deactivated successfully', [
                    'user_id' => $userId,
                    'email' => $user['email'],
                    'admin_id' => $adminId
                ]);
                return true;
            }
            
            $this->error = 'Failed to deactivate user';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Deactivation failed';
            logError('User Deactivation', 'Exception during deactivation', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }
    
    /**
     * Reactivate user account (admin function)
     * 
     * @param int $userId User ID to reactivate
     * @param int $adminId Admin user ID performing the action
     * @return bool True on success, false on failure
     */
    public function reactivateUser($userId, $adminId) {
        try {
            // Check if user exists
            $user = $this->getUserById($userId);
            if (!$user) {
                $this->error = 'User not found';
                return false;
            }
            
            // Check if user is already active
            if ($user['is_active']) {
                $this->error = 'User is already active';
                return false;
            }
            
            // Reactivate user
            $result = $this->db->execute(
                "UPDATE users SET is_active = 1 WHERE user_id = ?",
                [$userId]
            );
            
            if ($result) {
                logError('User Reactivation', 'User reactivated successfully', [
                    'user_id' => $userId,
                    'email' => $user['email'],
                    'admin_id' => $adminId
                ]);
                return true;
            }
            
            $this->error = 'Failed to reactivate user';
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Reactivation failed';
            logError('User Reactivation', 'Exception during reactivation', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }
}

