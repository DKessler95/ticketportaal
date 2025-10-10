<?php
/**
 * Registration Page
 * 
 * User registration page with password strength validation and CSRF protection
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
$errors = [];
$success = false;
$formData = [
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'department' => ''
];

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors['csrf'] = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize inputs
        $formData['email'] = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $formData['first_name'] = sanitizeInput($_POST['first_name'] ?? '');
        $formData['last_name'] = sanitizeInput($_POST['last_name'] ?? '');
        $formData['department'] = sanitizeInput($_POST['department'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate email
        if (empty($formData['email'])) {
            $errors['email'] = 'E-mailadres is verplicht';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ongeldig e-mailadres';
        }
        
        // Validate first name
        if (empty($formData['first_name'])) {
            $errors['first_name'] = 'Voornaam is verplicht';
        } elseif (strlen($formData['first_name']) > 100) {
            $errors['first_name'] = 'Voornaam is te lang';
        }
        
        // Validate last name
        if (empty($formData['last_name'])) {
            $errors['last_name'] = 'Achternaam is verplicht';
        } elseif (strlen($formData['last_name']) > 100) {
            $errors['last_name'] = 'Achternaam is te lang';
        }
        
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
        
        // If no validation errors, attempt registration
        if (empty($errors)) {
            $user = new User();
            $userId = $user->register(
                $formData['email'],
                $password,
                $formData['first_name'],
                $formData['last_name'],
                $formData['department']
            );
            
            if ($userId) {
                $success = true;
                // Clear form data on success
                $formData = [
                    'email' => '',
                    'first_name' => '',
                    'last_name' => '',
                    'department' => ''
                ];
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
    <title>Registreren - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3">Account aanmaken</h1>
                            <p class="text-muted">Registreer voor <?php echo SITE_NAME; ?></p>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading">Registratie succesvol!</h5>
                                <p class="mb-0">
                                    Uw account is aangemaakt. U kunt nu 
                                    <a href="login.php" class="alert-link">inloggen</a>.
                                </p>
                            </div>
                        <?php endif; ?>
                        
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
                        
                        <form method="POST" action="register.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mailadres *</label>
                                <input 
                                    type="email" 
                                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo escapeOutput($formData['email']); ?>" 
                                    required 
                                    autofocus
                                    placeholder="naam@voorbeeld.nl"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo escapeOutput($errors['email']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Voornaam *</label>
                                    <input 
                                        type="text" 
                                        class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                        id="first_name" 
                                        name="first_name" 
                                        value="<?php echo escapeOutput($formData['first_name']); ?>" 
                                        required
                                        maxlength="100"
                                        placeholder="Jan"
                                    >
                                    <?php if (isset($errors['first_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo escapeOutput($errors['first_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Achternaam *</label>
                                    <input 
                                        type="text" 
                                        class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                        id="last_name" 
                                        name="last_name" 
                                        value="<?php echo escapeOutput($formData['last_name']); ?>" 
                                        required
                                        maxlength="100"
                                        placeholder="Jansen"
                                    >
                                    <?php if (isset($errors['last_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo escapeOutput($errors['last_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="department" class="form-label">Afdeling</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="department" 
                                    name="department" 
                                    value="<?php echo escapeOutput($formData['department']); ?>" 
                                    maxlength="100"
                                    placeholder="Bijv. Verkoop, IT, HR"
                                >
                                <small class="form-text text-muted">Optioneel</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Wachtwoord *</label>
                                <input 
                                    type="password" 
                                    class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                    id="password" 
                                    name="password" 
                                    required
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
                                <label for="confirm_password" class="form-label">Bevestig wachtwoord *</label>
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
                                    Registreren
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0 small text-muted">
                                Al een account? 
                                <a href="login.php" class="text-decoration-none">
                                    Log hier in
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
