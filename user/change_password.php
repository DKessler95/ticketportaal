<?php
/**
 * Change Password Handler
 * 
 * Processes password change requests
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

// Initialize session and check authentication
initSession();
requireLogin();

// Get user information
$userId = $_SESSION['user_id'];

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Alle velden zijn verplicht';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Nieuw wachtwoord moet minimaal 8 karakters zijn';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Nieuwe wachtwoorden komen niet overeen';
        } else {
            // Get user from database
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT user_id, password FROM users WHERE user_id = ?",
                [$userId]
            );
            
            if (!$user) {
                $error = 'Gebruiker niet gevonden';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $error = 'Huidig wachtwoord is onjuist';
            } else {
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Update password
                $result = $db->execute(
                    "UPDATE users SET password = ? WHERE user_id = ?",
                    [$hashedPassword, $userId]
                );
                
                if ($result) {
                    $success = true;
                    
                    // Log the password change
                    logError('Password Change', 'User changed password', [
                        'user_id' => $userId
                    ]);
                } else {
                    $error = 'Fout bij wijzigen wachtwoord';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord Wijzigen - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Succes!</h5>
                        <p>Uw wachtwoord is succesvol gewijzigd.</p>
                        <hr>
                        <a href="<?php echo SITE_URL; ?>/user/profile.php" class="btn btn-success">
                            <i class="bi bi-arrow-left"></i> Terug naar Profiel
                        </a>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Fout</h5>
                        <p><?php echo escapeOutput($error); ?></p>
                        <hr>
                        <a href="<?php echo SITE_URL; ?>/user/profile.php" class="btn btn-danger">
                            <i class="bi bi-arrow-left"></i> Terug naar Profiel
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Bezig met verwerken...
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = '<?php echo SITE_URL; ?>/user/profile.php';
                        }, 2000);
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
