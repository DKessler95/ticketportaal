<?php
/**
 * Request Password Reset Page
 * 
 * Allows users to request a password reset link
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/User.php';

// Initialize session
initSession();

// Redirect if already logged in
if (checkLogin()) {
    $role = $_SESSION['role'] ?? 'user';
    $user = new User();
    redirectTo($user->getRoleRedirectUrl($role));
}

// Initialize variables
$error = '';
$success = false;
$email = '';

// Process password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize email
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        // Validate email
        if (empty($email)) {
            $error = 'E-mailadres is verplicht';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ongeldig e-mailadres';
        } else {
            // Request password reset
            $user = new User();
            $token = $user->requestPasswordReset($email);
            
            // Always show success message for security (don't reveal if email exists)
            $success = true;
            
            // In a real implementation, send email with reset link here
            // For now, we'll just log the token
            if ($token) {
                $resetLink = SITE_URL . '/reset_password.php?token=' . $token;
                logError('Password Reset', 'Reset link generated', [
                    'email' => $email,
                    'reset_link' => $resetLink
                ]);
                
                // TODO: Send email with reset link
                // Example: sendPasswordResetEmail($email, $resetLink);
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord vergeten - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3">Wachtwoord vergeten</h1>
                            <p class="text-muted">Voer uw e-mailadres in om een reset link te ontvangen</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading">Aanvraag verzonden!</h5>
                                <p class="mb-0">
                                    Als het e-mailadres in ons systeem bestaat, ontvangt u binnen enkele minuten 
                                    een e-mail met instructies om uw wachtwoord te resetten.
                                </p>
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">
                                    Terug naar login
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo escapeOutput($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="request_reset.php" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mailadres</label>
                                    <input 
                                        type="email" 
                                        class="form-control <?php echo !empty($error) ? 'is-invalid' : ''; ?>" 
                                        id="email" 
                                        name="email" 
                                        value="<?php echo escapeOutput($email); ?>" 
                                        required 
                                        autofocus
                                        placeholder="naam@voorbeeld.nl"
                                    >
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        Reset link verzenden
                                    </button>
                                </div>
                            </form>
                            
                            <hr class="my-4">
                            
                            <div class="text-center">
                                <p class="mb-0 small text-muted">
                                    Weet u uw wachtwoord weer? 
                                    <a href="login.php" class="text-decoration-none">
                                        Log hier in
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none text-muted small">
                        &larr; Terug naar home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
