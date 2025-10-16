<?php
/**
 * Login Debug Script
 * Helps diagnose login issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Debug Information</h1>";
echo "<hr>";

// Test 1: Check if config files exist
echo "<h2>1. Configuration Files</h2>";
$configFiles = [
    'config/config.php',
    'config/database.php',
    'config/session.php'
];

foreach ($configFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $file NOT FOUND</p>";
    }
}

// Test 2: Load configuration
echo "<hr><h2>2. Load Configuration</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p style='color: green;'>✓ Database config loaded</p>";
    echo "<ul>";
    echo "<li>DB_HOST: " . DB_HOST . "</li>";
    echo "<li>DB_NAME: " . DB_NAME . "</li>";
    echo "<li>DB_USER: " . DB_USER . "</li>";
    echo "<li>DB_PASS: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : 'NOT SET') . "</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading config: " . $e->getMessage() . "</p>";
}

// Test 3: Database connection
echo "<hr><h2>3. Database Connection</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    die();
}

// Test 4: Check if users table exists
echo "<hr><h2>4. Users Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total users in database: <strong>" . $result['count'] . "</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Users table does not exist</p>";
        echo "<p>Please import the database schema: <code>mysql -u root -p ticketportaal < database/schema.sql</code></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking users table: " . $e->getMessage() . "</p>";
}

// Test 5: Check admin user
echo "<hr><h2>5. Admin User</h2>";
try {
    $stmt = $pdo->prepare("SELECT user_id, email, password, first_name, last_name, role, is_active FROM users WHERE email = ?");
    $stmt->execute(['admin@kruit-en-kramer.nl']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green;'>✓ Admin user found</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>User ID</td><td>" . $user['user_id'] . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
        echo "<tr><td>First Name</td><td>" . htmlspecialchars($user['first_name']) . "</td></tr>";
        echo "<tr><td>Last Name</td><td>" . htmlspecialchars($user['last_name']) . "</td></tr>";
        echo "<tr><td>Role</td><td>" . htmlspecialchars($user['role']) . "</td></tr>";
        echo "<tr><td>Active</td><td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td></tr>";
        echo "<tr><td>Password Hash</td><td><code style='font-size: 10px;'>" . htmlspecialchars($user['password']) . "</code></td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ Admin user NOT found</p>";
        echo "<p>Please import the seed data: <code>mysql -u root -p ticketportaal < database/seed.sql</code></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error fetching admin user: " . $e->getMessage() . "</p>";
}

// Test 6: Password verification
if (isset($user) && $user) {
    echo "<hr><h2>6. Password Verification Test</h2>";
    
    $testPasswords = [
        'Admin123!',
        'admin123!',
        'Admin123',
    ];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Password</th><th>Result</th></tr>";
    
    foreach ($testPasswords as $testPass) {
        $verified = password_verify($testPass, $user['password']);
        $status = $verified ? "<span style='color: green; font-weight: bold;'>✓ MATCH</span>" : "<span style='color: red;'>✗ NO MATCH</span>";
        echo "<tr><td><code>" . htmlspecialchars($testPass) . "</code></td><td>$status</td></tr>";
    }
    
    echo "</table>";
    
    // Show expected hash
    echo "<h3>Expected Hash for 'Admin123!':</h3>";
    echo "<p><code style='font-size: 10px;'>$2y$12$LQv3c1yycEir3LtJlTjkKuHPqVyJpbGlmKq7FZ8U9XvVkKvPX.Ks6</code></p>";
    echo "<h3>Actual Hash in Database:</h3>";
    echo "<p><code style='font-size: 10px;'>" . htmlspecialchars($user['password']) . "</code></p>";
    
    if ($user['password'] !== '$2y$12$LQv3c1yycEir3LtJlTjkKuHPqVyJpbGlmKq7FZ8U9XvVkKvPX.Ks6') {
        echo "<p style='color: orange; font-weight: bold;'>⚠ WARNING: Hash in database does not match expected hash!</p>";
    }
}

// Test 7: Session test
echo "<hr><h2>7. Session Configuration</h2>";
session_start();
echo "<p style='color: green;'>✓ Session started successfully</p>";
echo "<ul>";
echo "<li>Session ID: " . session_id() . "</li>";
echo "<li>Session Name: " . session_name() . "</li>";
echo "<li>Cookie Secure: " . ini_get('session.cookie_secure') . " (should be 0 for localhost)</li>";
echo "<li>Cookie HTTPOnly: " . ini_get('session.cookie_httponly') . "</li>";
echo "<li>Cookie SameSite: " . ini_get('session.cookie_samesite') . "</li>";
echo "</ul>";

// Test 8: Manual login simulation
if (isset($user) && $user) {
    echo "<hr><h2>8. Manual Login Simulation</h2>";
    
    $testEmail = 'admin@kruit-en-kramer.nl';
    $testPassword = 'Admin123!';
    
    echo "<p>Attempting to login with:</p>";
    echo "<ul>";
    echo "<li>Email: <code>$testEmail</code></li>";
    echo "<li>Password: <code>$testPassword</code></li>";
    echo "</ul>";
    
    // Simulate the login process
    require_once __DIR__ . '/classes/User.php';
    require_once __DIR__ . '/includes/functions.php';
    
    $userObj = new User();
    $loginResult = $userObj->login($testEmail, $testPassword);
    
    if ($loginResult) {
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ LOGIN SUCCESSFUL!</p>";
        echo "<p>Session data:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        echo "<p><a href='user/dashboard.php'>Go to Dashboard</a></p>";
    } else {
        echo "<p style='color: red; font-weight: bold; font-size: 18px;'>✗ LOGIN FAILED</p>";
        echo "<p>Error: " . htmlspecialchars($userObj->getError()) . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
