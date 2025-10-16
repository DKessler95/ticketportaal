<?php
/**
 * Email Tickets Management
 * 
 * Special overview for tickets created via email
 * Allows admins to review, assign, and process email-generated tickets
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and check authentication
initSession();
requireLogin();
requireRole('admin');

// Get current user info
$currentUser = getCurrentUser();

// Initialize objects
$ticketObj = new Ticket();
$userObj = new User();

// Get all email-sourced tickets
$emailTickets = $ticketObj->getAllTickets(['source' => 'email']);

// Get agents for assignment
$agents = $userObj->getUsersByRole('agent');
$admins = $userObj->getUsersByRole('admin');
$allAgents = array_merge($agents ?: [], $admins ?: []);

// Handle ticket actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCSRFToken();
    
    $action = $_POST['action'];
    $ticketId = $_POST['ticket_id'] ?? null;
    
    switch ($action) {
        case 'assign':
            $agentId = $_POST['agent_id'] ?? null;
            if ($ticketId && $agentId) {
                if ($ticketObj->assignTicket($ticketId, $agentId)) {
                    $message = 'Ticket succesvol toegewezen';
                    $messageType = 'success';
                    // Refresh tickets
                    $emailTickets = $ticketObj->getAllTickets(['source' => 'email']);
                } else {
                    $message = 'Fout bij toewijzen ticket: ' . $ticketObj->getError();
                    $messageType = 'danger';
                }
            }
            break;
            
        case 'update_status':
            $status = $_POST['status'] ?? null;
            if ($ticketId && $status) {
                if ($ticketObj->updateTicketStatus($ticketId, $status)) {
                    $message = 'Status succesvol bijgewerkt';
                    $messageType = 'success';
                    // Refresh tickets
                    $emailTickets = $ticketObj->getAllTickets(['source' => 'email']);
                } else {
                    $message = 'Fout bij bijwerken status: ' . $ticketObj->getError();
                    $messageType = 'danger';
                }
            }
            break;
            
        case 'update_priority':
            $priority = $_POST['priority'] ?? null;
            if ($ticketId && $priority) {
                if ($ticketObj->updateTicketPriority($ticketId, $priority)) {
                    $message = 'Prioriteit succesvol bijgewerkt';
                    $messageType = 'success';
                    // Refresh tickets
                    $emailTickets = $ticketObj->getAllTickets(['source' => 'email']);
                } else {
                    $message = 'Fout bij bijwerken prioriteit: ' . $ticketObj->getError();
                    $messageType = 'danger';
                }
            }
            break;
    }
}

// Statistics
$totalEmailTickets = is_array($emailTickets) ? count($emailTickets) : 0;
$unassignedCount = 0;
$openCount = 0;

if (is_array($emailTickets)) {
    foreach ($emailTickets as $ticket) {
        if (empty($ticket['assigned_agent_id'])) {
            $unassignedCount++;
        }
        if ($ticket['status'] === 'open') {
            $openCount++;
        }
    }
}

// Page title
$pageTitle = 'Email Tickets Beheer';

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
                <h1 class="h2">
                    <i class="bi bi-envelope-fill me-2"></i>
                    Email Tickets Beheer
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Terug naar Dashboard
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo escapeOutput($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Totaal Email Tickets</h5>
                            <p class="card-text display-4"><?php echo $totalEmailTickets; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Niet Toegewezen</h5>
                            <p class="card-text display-4"><?php echo $unassignedCount; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Open</h5>
                            <p class="card-text display-4"><?php echo $openCount; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Configuration Info -->
            <div class="alert alert-info">
                <h5 class="alert-heading">
                    <i class="bi bi-info-circle"></i> Email Configuratie
                </h5>
                <p class="mb-0">
                    <strong>Mailbox:</strong> <?php echo defined('IMAP_USER') ? escapeOutput(IMAP_USER) : 'Niet geconfigureerd'; ?><br>
                    <strong>Cron Job:</strong> Draait elke 5 minuten om nieuwe emails te verwerken<br>
                    <strong>Laatste Check:</strong> Zie logs voor details
                </p>
                <hr>
                <p class="mb-0">
                    <a href="<?php echo SITE_URL; ?>/EMAIL_INTEGRATION_README.md" target="_blank" class="alert-link">
                        <i class="bi bi-book"></i> Bekijk Email Integratie Documentatie
                    </a>
                </p>
            </div>

            <!-- Email Tickets Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-envelope"></i> Email Tickets
                        <span class="badge bg-primary"><?php echo $totalEmailTickets; ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($emailTickets && count($emailTickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>Titel</th>
                                        <th>Van</th>
                                        <th>Prioriteit</th>
                                        <th>Status</th>
                                        <th>Toegewezen aan</th>
                                        <th>Aangemaakt</th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emailTickets as $ticket): ?>
                                        <tr class="<?php echo empty($ticket['assigned_agent_id']) ? 'table-warning' : ''; ?>">
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>">
                                                    <strong><?php echo escapeOutput($ticket['ticket_number']); ?></strong>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo escapeOutput($ticket['title']); ?>
                                                <?php if (empty($ticket['assigned_agent_id'])): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Nieuw</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo escapeOutput(($ticket['user_first_name'] ?? '') . ' ' . ($ticket['user_last_name'] ?? '')); ?><br>
                                                    <span class="text-muted"><?php echo escapeOutput($ticket['user_email'] ?? ''); ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="update_priority">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                    <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="low" <?php echo $ticket['priority'] === 'low' ? 'selected' : ''; ?>>Laag</option>
                                                        <option value="medium" <?php echo $ticket['priority'] === 'medium' ? 'selected' : ''; ?>>Gemiddeld</option>
                                                        <option value="high" <?php echo $ticket['priority'] === 'high' ? 'selected' : ''; ?>>Hoog</option>
                                                        <option value="urgent" <?php echo $ticket['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Behandeling</option>
                                                        <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>In Afwachting</option>
                                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Opgelost</option>
                                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Gesloten</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="assign">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                    <select name="agent_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="">-- Niet toegewezen --</option>
                                                        <?php foreach ($allAgents as $agent): ?>
                                                            <option value="<?php echo $agent['user_id']; ?>" 
                                                                    <?php echo $ticket['assigned_agent_id'] == $agent['user_id'] ? 'selected' : ''; ?>>
                                                                <?php echo escapeOutput($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                                                (<?php echo ucfirst($agent['role']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <small><?php echo date('d-m-Y H:i', strtotime($ticket['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Bekijken
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Geen email tickets gevonden.
                            <hr>
                            <p class="mb-0">
                                <strong>Tip:</strong> Stuur een email naar <code><?php echo defined('IMAP_USER') ? escapeOutput(IMAP_USER) : 'ict@kruit-en-kramer.nl'; ?></code> 
                                om een test ticket aan te maken.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Help Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-question-circle"></i> Hoe werkt Email-to-Ticket?
                    </h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li><strong>Email Ontvangen:</strong> Klanten sturen een email naar <?php echo defined('IMAP_USER') ? escapeOutput(IMAP_USER) : 'ict@kruit-en-kramer.nl'; ?></li>
                        <li><strong>Automatische Verwerking:</strong> Elke 5 minuten controleert het systeem de mailbox</li>
                        <li><strong>Ticket Aanmaken:</strong> Email wordt automatisch omgezet naar een ticket</li>
                        <li><strong>Beheer:</strong> Op deze pagina kun je tickets toewijzen, prioriteit instellen en status bijwerken</li>
                        <li><strong>Notificaties:</strong> Klant ontvangt automatisch een bevestiging met ticketnummer</li>
                    </ol>
                    
                    <h6 class="mt-3">Email Formaat:</h6>
                    <ul>
                        <li><strong>Onderwerp:</strong> Wordt de ticket titel</li>
                        <li><strong>Body:</strong> Wordt de ticket beschrijving</li>
                        <li><strong>Bijlagen:</strong> Worden automatisch toegevoegd aan het ticket</li>
                        <li><strong>Van adres:</strong> Wordt gekoppeld aan gebruiker (of nieuwe gebruiker aangemaakt)</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
