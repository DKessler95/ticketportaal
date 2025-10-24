<?php
/**
 * Admin Dashboard
 * 
 * Main dashboard for administrators with system overview and quick actions
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/User.php';

// Initialize session and check authentication
initSession();
requireLogin();
requireRole('admin');

// Get current user info
$currentUser = getCurrentUser();

// Get statistics
$ticketObj = new Ticket();
$userObj = new User();

// Fetch ticket statistics using getTicketCount
$totalTickets = $ticketObj->getTicketCount();
$openTickets = $ticketObj->getTicketCount(['status' => ['open', 'in_progress']]);
$urgentTickets = $ticketObj->getTicketCount(['priority' => 'urgent']);

// Fetch user statistics
$allUsers = $userObj->getAllUsers();
$totalUsers = is_array($allUsers) ? count($allUsers) : 0;
$activeUsers = 0;
if (is_array($allUsers)) {
    foreach ($allUsers as $u) {
        if ($u['is_active']) {
            $activeUsers++;
        }
    }
}

// Get recent tickets (first 10)
$recentTickets = $ticketObj->getAllTickets();
if (is_array($recentTickets) && count($recentTickets) > 10) {
    $recentTickets = array_slice($recentTickets, 0, 10);
}

// Page title
$pageTitle = 'Admin Dashboard';
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
                <h1 class="h2"><?php _e('nav_admin'); ?> <?php _e('nav_dashboard'); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="<?php echo SITE_URL; ?>/admin/email_tickets.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-envelope-fill"></i> Email <?php _e('tickets'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-people"></i> <?php _e('nav_users'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-tags"></i> <?php _e('nav_categories'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-graph-up"></i> <?php _e('nav_reports'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('total_tickets'); ?></h5>
                            <p class="card-text display-4"><?php echo $totalTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('open_tickets'); ?></h5>
                            <p class="card-text display-4"><?php echo $openTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('urgent_tickets'); ?></h5>
                            <p class="card-text display-4"><?php echo $urgentTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('active_users'); ?></h5>
                            <p class="card-text display-4"><?php echo $activeUsers; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php _e('recent_tickets'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($recentTickets && count($recentTickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>Onderwerp</th>
                                        <th>Gebruiker</th>
                                        <th>Prioriteit</th>
                                        <th>Status</th>
                                        <th>Aangemaakt</th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTickets as $ticket): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>">
                                                    <?php echo escapeOutput($ticket['ticket_number']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo escapeOutput($ticket['title']); ?></td>
                                            <td><?php echo escapeOutput(($ticket['user_first_name'] ?? '') . ' ' . ($ticket['user_last_name'] ?? 'Onbekend')); ?></td>
                                            <td>
                                                <?php
                                                $priorityClass = [
                                                    'low' => 'secondary',
                                                    'medium' => 'info',
                                                    'high' => 'warning',
                                                    'urgent' => 'danger'
                                                ];
                                                $class = $priorityClass[$ticket['priority']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $class; ?>">
                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'open' => 'primary',
                                                    'in_progress' => 'warning',
                                                    'resolved' => 'success',
                                                    'closed' => 'secondary'
                                                ];
                                                $class = $statusClass[$ticket['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($ticket['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    Bekijken
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Geen tickets gevonden.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Snelle Acties</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-person-plus"></i> Nieuwe Gebruiker Toevoegen
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-tag"></i> Categorieën Beheren
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/knowledge_base.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-book"></i> Kennisbank Beheren
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-graph-up"></i> Rapporten Bekijken
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Systeeminformatie</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>Totaal Gebruikers:</th>
                                    <td><?php echo $totalUsers; ?></td>
                                </tr>
                                <tr>
                                    <th>Actieve Gebruikers:</th>
                                    <td><?php echo $activeUsers; ?></td>
                                </tr>
                                <tr>
                                    <th>Totaal Tickets:</th>
                                    <td><?php echo $totalTickets; ?></td>
                                </tr>
                                <tr>
                                    <th>Open Tickets:</th>
                                    <td><?php echo $openTickets; ?></td>
                                </tr>
                                <tr>
                                    <th>Urgente Tickets:</th>
                                    <td><?php echo $urgentTickets; ?></td>
                                </tr>
                                <tr>
                                    <th>PHP Versie:</th>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- AI Chat Widget is included in sidebar.php -->
</body>
</html>
