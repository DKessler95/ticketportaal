<?php
/**
 * Reset Password Page
 * 
 * Allows users to reset their password using a valid token
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

// Get token from URL
$token = $_GET['token'] ?? '';

// Initialize variables
$errors = [];
$success = false;
$validToken = false;

// Validate token
if (empty($token)) {
    $errors['general'] = 'Ongeldige reset link';
} else {
    $user = new User();
    $validToken = $user->validateResetToken($token);
    
    if (!$validToken) {
        $errors['general'] = 'Deze reset link is ongeldig of verlopen. Vraag een nieuwe aan.';
    }
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors['csrf'] = 'Invalid security token. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($password)) {
            $errors['password'] = 'Wachtwoord is verplicht';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Wachtwoord moet minimaal ' . PASSWORD_MIN_LENGTH . ' tekens bevatten';
        } elseif (!preg_match('/[A-Za-z]/', $password)) {
            $errors['password'] = 'Wachtwoord moet letters bevatten';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Wachtwoord moet cijfers bevatten';
        }
        
        // Validate password confirmation
        if (empty($confirmPassword)) {
            $errors['confirm_password'] = 'Bevestig uw wachtwoord';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Wachtwoorden komen niet overeen';
        }
        
        // If no validation errors, reset password
        if (empty($errors)) {
            $user = new User();
            if ($user->resetPassword($token, $password)) {
                $success = true;
            } else {
                $errors['general'] = $user->getError();
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
    <title>Wachtwoord resetten - <?php echo SITE_NAME; ?></title>
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
                            <h1 class="h3 mb-3">Nieuw wachtwoord instellen</h1>
                            <p class="text-muted">Voer uw nieuwe wachtwoord in</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading">Wachtwoord gereset!</h5>
                                <p class="mb-0">
                                    Uw wachtwoord is succesvol gewijzigd. U kunt nu inloggen met uw nieuwe wachtwoord.
                                </p>
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">
                                    Ga naar login
                                </a>
                            </div>
                        <?php elseif (!$validToken): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo escapeOutput($errors['general']); ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="request_reset.php" class="btn btn-primary">
                                    Nieuwe reset link aanvragen
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (isset($errors['general'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo escapeOutput($errors['general']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($errors['csrf'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo escapeOutput($errors['csrf']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="reset_password.php?token=<?php echo escapeOutput($token); ?>" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nieuw wachtwoord</label>
                                    <input 
                                        type="password" 
                                        class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                        id="password" 
                                        name="password" 
                                        required
                                        autofocus
                                        placeholder="Minimaal <?php echo PASSWORD_MIN_LENGTH; ?> tekens"
                                    >
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo escapeOutput($errors['password']); ?>
                                        </div>
                                    <?php else: ?>
                                        <small class="form-text text-muted">
                                            Minimaal <?php echo PASSWORD_MIN_LENGTH; ?> tekens, met letters en cijfers
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Bevestig nieuw wachtwoord</label>
                                    <input 
                                        type="password" 
                                        class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        required
                                        placeholder="Herhaal uw wachtwoord"
                                    >
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo escapeOutput($errors['confirm_password']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        Wachtwoord resetten
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
