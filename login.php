<?php
/**
 * Login Page
 * 
 * User authentication page with CSRF protection and input validation
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/User.php';

// Initialize session
initSession();

// Redirect if already logged in
if (checkLogin()) {
    redirectToDashboard();
}

// Initialize variables
$error = '';
$email = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize inputs
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Validate inputs
        if (empty($email)) {
            $error = 'Email is required';
        } elseif (empty($password)) {
            $error = 'Password is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            // Attempt login
            $user = new User();
            if ($user->login($email, $password)) {
                // Login successful - check if there's a redirect URL stored
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirectUrl = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirectTo($redirectUrl);
                } else {
                    // Redirect to role-specific dashboard
                    redirectToDashboard();
                }
            } else {
                // Login failed
                $error = $user->getError();
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
    <title>Login - <?php echo SITE_NAME; ?></title>
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
                            <h1 class="h3 mb-3"><?php echo SITE_NAME; ?></h1>
                            <p class="text-muted">Log in om door te gaan</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo escapeOutput($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mailadres</label>
                                <input 
                                    type="email" 
                                    class="form-control <?php echo (!empty($error) && !empty($email)) ? 'is-invalid' : ''; ?>" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo escapeOutput($email); ?>" 
                                    required 
                                    autofocus
                                    placeholder="naam@voorbeeld.nl"
                                >
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord</label>
                                <input 
                                    type="password" 
                                    class="form-control <?php echo (!empty($error) && !empty($email)) ? 'is-invalid' : ''; ?>" 
                                    id="password" 
                                    name="password" 
                                    required
                                    placeholder="Voer uw wachtwoord in"
                                >
                            </div>
                            
                            <div class="mb-3">
                                <a href="request_reset.php" class="text-decoration-none small">
                                    Wachtwoord vergeten?
                                </a>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    Inloggen
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0 small text-muted">
                                Nog geen account? 
                                <a href="register.php" class="text-decoration-none">
                                    Registreer hier
                                </a>
                            </p>
                        </div>
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
