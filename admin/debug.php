<?php
/**
 * Debug script for admin dashboard
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Dashboard Debug</h1>";
echo "<hr>";

echo "<h2>1. Loading includes/functions.php</h2>";
try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "<p style='color: green;'>✓ functions.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>2. Loading classes/Ticket.php</h2>";
try {
    require_once __DIR__ . '/../classes/Ticket.php';
    echo "<p style='color: green;'>✓ Ticket.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>3. Loading classes/User.php</h2>";
try {
    require_once __DIR__ . '/../classes/User.php';
    echo "<p style='color: green;'>✓ User.php loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>4. Initialize session</h2>";
try {
    initSession();
    echo "<p style='color: green;'>✓ Session initialized</p>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>5. Check login</h2>";
try {
    if (!checkLogin()) {
        echo "<p style='color: orange;'>⚠ Not logged in</p>";
        echo "<p><a href='../login.php'>Go to login</a></p>";
        die();
    }
    echo "<p style='color: green;'>✓ User is logged in</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>6. Check admin role</h2>";
try {
    $currentUser = getCurrentUser();
    echo "<p>Current user:</p>";
    echo "<pre>" . print_r($currentUser, true) . "</pre>";
    
    if ($currentUser['role'] !== 'admin') {
        echo "<p style='color: orange;'>⚠ User is not admin (role: {$currentUser['role']})</p>";
        die();
    }
    echo "<p style='color: green;'>✓ User is admin</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>7. Create Ticket object</h2>";
try {
    $ticketObj = new Ticket();
    echo "<p style='color: green;'>✓ Ticket object created</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>8. Create User object</h2>";
try {
    $userObj = new User();
    echo "<p style='color: green;'>✓ User object created</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>9. Get ticket count</h2>";
try {
    $totalTickets = $ticketObj->getTicketCount();
    echo "<p style='color: green;'>✓ Total tickets: $totalTickets</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>10. Get open tickets count</h2>";
try {
    $openTickets = $ticketObj->getTicketCount(['status' => ['open', 'in_progress']]);
    echo "<p style='color: green;'>✓ Open tickets: $openTickets</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>11. Get urgent tickets count</h2>";
try {
    $urgentTickets = $ticketObj->getTicketCount(['priority' => 'urgent']);
    echo "<p style='color: green;'>✓ Urgent tickets: $urgentTickets</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>12. Get all users</h2>";
try {
    $allUsers = $userObj->getAllUsers();
    $totalUsers = is_array($allUsers) ? count($allUsers) : 0;
    echo "<p style='color: green;'>✓ Total users: $totalUsers</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>13. Get recent tickets</h2>";
try {
    $recentTickets = $ticketObj->getAllTickets();
    $count = is_array($recentTickets) ? count($recentTickets) : 0;
    echo "<p style='color: green;'>✓ Recent tickets: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>✓ All checks passed!</h2>";
echo "<p><a href='index.php'>Try loading admin dashboard</a></p>";
?>
