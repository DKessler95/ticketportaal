<?php
/**
 * User Profile Page
 * 
 * Display user information and statistics
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';

// Initialize session and check authentication
initSession();
requireLogin();

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];
$userRole = $_SESSION['role'];

// Initialize classes
$userClass = new User();
$ticketClass = new Ticket();

// Get user details
$db = Database::getInstance();
$user = $db->fetchOne(
    "SELECT u.*, d.name as department_name 
     FROM users u
     LEFT JOIN departments d ON u.department_id = d.department_id
     WHERE u.user_id = ?",
    [$userId]
);

// Get user ticket statistics
$allTickets = $ticketClass->getTicketsByUser($userId);
$stats = [
    'total' => count($allTickets),
    'open' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0
];

foreach ($allTickets as $ticket) {
    if (isset($stats[$ticket['status']])) {
        $stats[$ticket['status']]++;
    }
}

$pageTitle = 'Mijn Profiel';
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
                    <h1 class="h2"><i class="bi bi-person-circle"></i> <?php echo escapeOutput($pageTitle); ?></h1>
                </div>

                <div class="row">
                    <!-- User Information Card -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Persoonlijke Informatie</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 100px; height: 100px; font-size: 3rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                </div>
                                
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Naam:</dt>
                                    <dd class="col-sm-8">
                                        <?php echo escapeOutput($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Email:</dt>
                                    <dd class="col-sm-8">
                                        <a href="mailto:<?php echo escapeOutput($user['email']); ?>">
                                            <?php echo escapeOutput($user['email']); ?>
                                        </a>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Afdeling:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['department_name']): ?>
                                            <span class="badge bg-info"><?php echo escapeOutput($user['department_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Niet ingesteld</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Rol:</dt>
                                    <dd class="col-sm-8">
                                        <?php echo getRoleBadge($user['role']); ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Lid sinds:</dt>
                                    <dd class="col-sm-8">
                                        <?php echo date('d-m-Y', strtotime($user['created_at'])); ?>
                                    </dd>
                                    
                                    <?php if ($user['last_login']): ?>
                                        <dt class="col-sm-4">Laatste login:</dt>
                                        <dd class="col-sm-8">
                                            <?php echo date('d-m-Y H:i', strtotime($user['last_login'])); ?>
                                        </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics and Actions -->
                    <div class="col-lg-8 mb-4">
                        <!-- Ticket Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Ticket Statistieken</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-primary mb-0"><?php echo $stats['total']; ?></h3>
                                            <small class="text-muted">Totaal</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-info mb-0"><?php echo $stats['open']; ?></h3>
                                            <small class="text-muted">Open</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-warning mb-0"><?php echo $stats['in_progress']; ?></h3>
                                            <small class="text-muted">In Behandeling</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-success mb-0"><?php echo $stats['resolved']; ?></h3>
                                            <small class="text-muted">Opgelost</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="<?php echo SITE_URL; ?>/user/my_tickets.php" class="btn btn-primary">
                                        <i class="bi bi-list-task"></i> Bekijk Alle Tickets
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Snelle Acties</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="btn btn-success w-100 py-3">
                                            <i class="bi bi-plus-circle-fill"></i><br>
                                            <strong>Nieuw Ticket</strong>
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="<?php echo SITE_URL; ?>/knowledge_base.php" class="btn btn-info w-100 py-3">
                                            <i class="bi bi-book-fill"></i><br>
                                            <strong>Kennisbank</strong>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Section -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0"><i class="bi bi-key-fill"></i> Wachtwoord Wijzigen</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Wijzig hier uw wachtwoord voor extra beveiliging.</p>
                                <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key"></i> Wachtwoord Wijzigen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key-fill"></i> Wachtwoord Wijzigen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="<?php echo SITE_URL; ?>/user/change_password.php" id="changePasswordForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Huidig Wachtwoord <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nieuw Wachtwoord <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <small class="text-muted">Minimaal 8 karakters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Bevestig Nieuw Wachtwoord <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Wachtwoord Wijzigen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Nieuwe wachtwoorden komen niet overeen!');
                return false;
            }
        });
    </script>
</body>
</html>
