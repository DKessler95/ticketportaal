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

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="<?php echo SITE_URL; ?>/admin/email_tickets.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-envelope-fill"></i> Email Tickets
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Tickets</h5>
                            <p class="card-text display-4"><?php echo $totalTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Open Tickets</h5>
                            <p class="card-text display-4"><?php echo $openTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Urgent Tickets</h5>
                            <p class="card-text display-4"><?php echo $urgentTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Active Users</h5>
                            <p class="card-text display-4"><?php echo $activeUsers; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Tickets</h5>
                </div>
                <div class="card-body">
                    <?php if ($recentTickets && count($recentTickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>Subject</th>
                                        <th>User</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
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
                                            <td><?php echo escapeOutput(($ticket['user_first_name'] ?? '') . ' ' . ($ticket['user_last_name'] ?? 'Unknown')); ?></td>
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
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No tickets found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-person-plus"></i> Add New User
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-tag"></i> Manage Categories
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/knowledge_base.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-book"></i> Manage Knowledge Base
                                </a>
                                <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-graph-up"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>Total Users:</th>
                                    <td><?php echo $totalUsers; ?></td>
                                </tr>
                                <tr>
                                    <th>Active Users:</th>
                                    <td><?php echo $activeUsers; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Tickets:</th>
                                    <td><?php echo $totalTickets; ?></td>
                                </tr>
                                <tr>
                                    <th>Open Tickets:</th>
                                    <td><?php echo $openTickets; ?></td>
                                </tr>
                                <tr>
                                    <th>Urgent Tickets:</th>
                                    <td><?php echo $urgentTickets; ?></td>
                                </tr>
                                <tr>
                                    <th>PHP Version:</th>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
