<?php
/**
 * Admin User Management Page
 * 
 * Allows administrators to create, edit, and manage user accounts
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize User class
$userClass = new User();

// Initialize Database
$db = Database::getInstance();

// Handle form submissions
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errorMessage = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
                $location = $_POST['location'] ?? 'Kruit en Kramer';
                $role = $_POST['role'] ?? 'user';
                
                if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
                    $errorMessage = 'Email, password, first name, and last name are required.';
                } else {
                    $newUserId = $userClass->createUser($email, $password, $firstName, $lastName, $departmentId, $role);
                    if ($newUserId) {
                        // Update location separately
                        $db->execute("UPDATE users SET location = ? WHERE user_id = ?", [$location, $newUserId]);
                        $successMessage = 'User created successfully!';
                    } else {
                        $errorMessage = 'Failed to create user: ' . $userClass->getError();
                    }
                }
                break;
                
            case 'update_role':
                $targetUserId = (int)($_POST['user_id'] ?? 0);
                $newRole = $_POST['role'] ?? '';
                $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
                $location = $_POST['location'] ?? 'Kruit en Kramer';
                
                // Update role
                if ($userClass->updateUserRole($targetUserId, $newRole, $userId)) {
                    // Also update department and location
                    $db = Database::getInstance();
                    $db->execute("UPDATE users SET department_id = ?, location = ? WHERE user_id = ?", [$departmentId, $location, $targetUserId]);
                    
                    $successMessage = 'User role, department and location updated successfully!';
                } else {
                    $errorMessage = 'Failed to update user role: ' . $userClass->getError();
                }
                break;
                
            case 'deactivate':
                $targetUserId = (int)($_POST['user_id'] ?? 0);
                if ($userClass->deactivateUser($targetUserId, $userId)) {
                    $successMessage = 'User deactivated successfully!';
                } else {
                    $errorMessage = 'Failed to deactivate user: ' . $userClass->getError();
                }
                break;
                
            case 'reactivate':
                $targetUserId = (int)($_POST['user_id'] ?? 0);
                if ($userClass->reactivateUser($targetUserId, $userId)) {
                    $successMessage = 'User reactivated successfully!';
                } else {
                    $errorMessage = 'Failed to reactivate user: ' . $userClass->getError();
                }
                break;
                
            case 'reset_password':
                $targetUserId = (int)($_POST['user_id'] ?? 0);
                
                // Generate random password
                $newPassword = generateRandomPassword(8);
                
                if ($userClass->resetPasswordByAdmin($targetUserId, $newPassword)) {
                    // Store password in session to display with HTML
                    $_SESSION['reset_password_success'] = $newPassword;
                    $successMessage = 'PASSWORD_RESET_SUCCESS';
                } else {
                    $errorMessage = 'Failed to reset password: ' . $userClass->getError();
                }
                break;
        }
    }
}

// Get all users with location
$users = $userClass->getAllUsers();

// Get all departments
$db = Database::getInstance();
$departments = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name ASC");

$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeOutput($pageTitle . ' - ' . SITE_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people"></i> Gebruikersbeheer</h1>
                </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> 
                <?php 
                if ($successMessage === 'PASSWORD_RESET_SUCCESS' && isset($_SESSION['reset_password_success'])) {
                    $newPassword = $_SESSION['reset_password_success'];
                    unset($_SESSION['reset_password_success']);
                    echo 'Wachtwoord succesvol gereset! Nieuw wachtwoord: <strong>' . escapeOutput($newPassword) . '</strong> (Bewaar dit en deel het veilig met de gebruiker)';
                } else {
                    echo escapeOutput($successMessage);
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo escapeOutput($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Create User Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nieuwe Gebruiker Aanmaken</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required maxlength="255">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Wachtwoord <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                    <small class="form-text text-muted">Minimaal 8 tekens, moet letters en cijfers bevatten</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="first_name" class="form-label">Voornaam <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="100">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="last_name" class="form-label">Achternaam <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="100">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="department_id" class="form-label">Afdeling</label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">-- Selecteer afdeling --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['department_id']; ?>">
                                                <?php echo escapeOutput($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="location" class="form-label">Locatie</label>
                                    <select class="form-select" id="location" name="location">
                                        <option value="Kruit en Kramer">Kruit en Kramer</option>
                                        <option value="Pronto">Pronto</option>
                                        <option value="Profijt Groningen">Profijt Groningen</option>
                                        <option value="Profijt Hoogeveen">Profijt Hoogeveen</option>
                                        <option value="Profijt Assen">Profijt Assen</option>
                                        <option value="Henders & Hazel Assen">Henders & Hazel Assen</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user" selected>Gebruiker</option>
                                    <option value="agent">Agent</option>
                                    <option value="admin">Beheerder</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Gebruiker Aanmaken
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Alle Gebruikers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen gebruikers gevonden.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Naam</th>
                                            <th>E-mail</th>
                                            <th>Afdeling</th>
                                            <th>Locatie</th>
                                            <th>Rol</th>
                                            <th>Laatste Login</th>
                                            <th>Status</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo escapeOutput($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                    <?php if ($user['user_id'] == $userId): ?>
                                                        <span class="badge bg-info">Jij</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo escapeOutput($user['email']); ?></td>
                                                <td><?php echo escapeOutput($user['department_name'] ?? '-'); ?></td>
                                                <td><?php echo escapeOutput($user['location'] ?? 'Kruit en Kramer'); ?></td>
                                                <td><?php echo getRoleBadge($user['role']); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($user['last_login']) {
                                                        echo escapeOutput(date('d-m-Y H:i', strtotime($user['last_login'])));
                                                    } else {
                                                        echo '<span class="text-muted">Nooit</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge bg-success">Actief</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactief</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['user_id'] != $userId): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal<?php echo $user['user_id']; ?>">
                                                            <i class="bi bi-pencil"></i> Bewerken
                                                        </button>
                                                        
                                                        <?php if ($user['is_active']): ?>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt deactiveren?');">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="action" value="deactivate">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                                    <i class="bi bi-x-circle"></i> Deactiveren
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="action" value="reactivate">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                                    <i class="bi bi-check-circle"></i> Reactiveren
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            
                                            <!-- Edit Role Modal -->
                                            <?php if ($user['user_id'] != $userId): ?>
                                                <div class="modal fade" id="editModal<?php echo $user['user_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Gebruikersrol & Afdeling Bewerken</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                    <input type="hidden" name="action" value="update_role">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                                    
                                                                    <p><strong>Gebruiker:</strong> <?php echo escapeOutput($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                                                    <p><strong>E-mail:</strong> <?php echo escapeOutput($user['email']); ?></p>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                                                                        <select class="form-select" name="role" required>
                                                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Gebruiker</option>
                                                                            <option value="agent" <?php echo $user['role'] === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Beheerder</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Afdeling</label>
                                                                        <select class="form-select" name="department_id">
                                                                            <option value="">-- Selecteer afdeling --</option>
                                                                            <?php foreach ($departments as $dept): ?>
                                                                                <option value="<?php echo $dept['department_id']; ?>" 
                                                                                    <?php echo $user['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                                                                    <?php echo escapeOutput($dept['name']); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Locatie</label>
                                                                        <select class="form-select" name="location">
                                                                            <option value="Kruit en Kramer" <?php echo ($user['location'] ?? 'Kruit en Kramer') === 'Kruit en Kramer' ? 'selected' : ''; ?>>Kruit en Kramer</option>
                                                                            <option value="Pronto" <?php echo ($user['location'] ?? '') === 'Pronto' ? 'selected' : ''; ?>>Pronto</option>
                                                                            <option value="Profijt Groningen" <?php echo ($user['location'] ?? '') === 'Profijt Groningen' ? 'selected' : ''; ?>>Profijt Groningen</option>
                                                                            <option value="Profijt Hoogeveen" <?php echo ($user['location'] ?? '') === 'Profijt Hoogeveen' ? 'selected' : ''; ?>>Profijt Hoogeveen</option>
                                                                            <option value="Profijt Assen" <?php echo ($user['location'] ?? '') === 'Profijt Assen' ? 'selected' : ''; ?>>Profijt Assen</option>
                                                                            <option value="Henders & Hazel Assen" <?php echo ($user['location'] ?? '') === 'Henders & Hazel Assen' ? 'selected' : ''; ?>>Henders & Hazel Assen</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer d-flex justify-content-between">
                                                                    <div>
                                                                        <button type="button" class="btn btn-warning" 
                                                                                onclick="resetUserPassword(<?php echo $user['user_id']; ?>)">
                                                                            <i class="bi bi-key"></i> Wachtwoord Resetten
                                                                        </button>
                                                                    </div>
                                                                    <div>
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                                                                        <button type="submit" class="btn btn-primary">
                                                                            <i class="bi bi-save"></i> Wijzigingen Opslaan
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function resetUserPassword(userId) {
        if (confirm('Weet je zeker dat je het wachtwoord wilt resetten? Er wordt een nieuw willekeurig wachtwoord gegenereerd.')) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo generateCSRFToken(); ?>';
            form.appendChild(csrfInput);
            
            // Add action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reset_password';
            form.appendChild(actionInput);
            
            // Add user_id
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            form.appendChild(userIdInput);
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
