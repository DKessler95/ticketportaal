<?php
/**
 * User Dashboard
 * 
 * Main dashboard for regular users showing ticket statistics and recent tickets
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';

// Initialize session and check authentication
initSession();
requireRole('user');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize Ticket class
$ticketClass = new Ticket();

// Get all user tickets
$allTickets = $ticketClass->getTicketsByUser($userId);

// Calculate statistics
$stats = [
    'open' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'total' => count($allTickets)
];

foreach ($allTickets as $ticket) {
    if (isset($stats[$ticket['status']])) {
        $stats[$ticket['status']]++;
    }
}

// Get recent tickets (last 5)
$recentTickets = array_slice($allTickets, 0, 5);

$pageTitle = 'Dashboard';
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
                    <h1 class="h2">Welkom, <?php echo escapeOutput($userName); ?></h1>
                </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <a href="<?php echo SITE_URL; ?>/user/my_tickets.php?status=open" class="text-decoration-none">
                    <div class="card text-white bg-primary" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Open Tickets</h6>
                                    <h2 class="mb-0"><?php echo $stats['open']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-folder-open" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="<?php echo SITE_URL; ?>/user/my_tickets.php?status=in_progress" class="text-decoration-none">
                    <div class="card text-white bg-info" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">In Behandeling</h6>
                                    <h2 class="mb-0"><?php echo $stats['in_progress']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-hourglass-split" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="<?php echo SITE_URL; ?>/user/my_tickets.php?status=resolved" class="text-decoration-none">
                    <div class="card text-white bg-success" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Opgelost</h6>
                                    <h2 class="mb-0"><?php echo $stats['resolved']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="<?php echo SITE_URL; ?>/user/my_tickets.php" class="text-decoration-none">
                    <div class="card text-white bg-secondary" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Totaal Tickets</h6>
                                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-ticket-detailed" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Create Ticket by Type -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle-fill"></i> Nieuwe Aanvraag Indienen</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Selecteer het type aanvraag dat het beste past bij uw situatie:</p>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="text-decoration-none">
                                    <div class="card h-100 border-primary" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-plus-circle-fill text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title text-primary">Nieuw Ticket</h5>
                                            <p class="card-text text-muted small">Algemeen ticket zonder specifiek type</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php?type=incident" class="text-decoration-none">
                                    <div class="card h-100 border-danger" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title text-danger">Incident</h5>
                                            <p class="card-text text-muted small">Er is iets kapot of werkt niet zoals verwacht</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php?type=service_request" class="text-decoration-none">
                                    <div class="card h-100 border-info" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-person-plus-fill text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title text-info">Service Request</h5>
                                            <p class="card-text text-muted small">Account aanvraag, toegang of installatie</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php?type=change_request" class="text-decoration-none">
                                    <div class="card h-100 border-warning" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-arrow-repeat text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title text-warning">Change Request</h5>
                                            <p class="card-text text-muted small">Wijziging in systeem of configuratie</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php?type=feature_request" class="text-decoration-none">
                                    <div class="card h-100 border-success" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bi bi-lightbulb-fill text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title text-success">Wens/Feature</h5>
                                            <p class="card-text text-muted small">Nieuwe functionaliteit, software of hardware aanvraag</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="<?php echo SITE_URL; ?>/knowledge_base.php" class="btn btn-outline-primary">
                                <i class="bi bi-book"></i> Kennisbank
                            </a>
                            <a href="<?php echo SITE_URL; ?>/user/my_tickets.php" class="btn btn-outline-secondary">
                                <i class="bi bi-list-ul"></i> Bekijk Al Mijn Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recente Tickets</h5>
                        <a href="<?php echo SITE_URL; ?>/user/my_tickets.php" class="btn btn-sm btn-outline-primary">Bekijk Alles</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentTickets)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Je hebt nog geen tickets aangemaakt. 
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="alert-link">Maak je eerste ticket aan</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Titel</th>
                                            <th>Categorie</th>
                                            <th>Prioriteit</th>
                                            <th>Status</th>
                                            <th>Aangemaakt</th>
                                            <th>Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTickets as $ticket): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo escapeOutput($ticket['ticket_number']); ?></strong>
                                                </td>
                                                <td><?php echo escapeOutput($ticket['title']); ?></td>
                                                <td><?php echo escapeOutput($ticket['category_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                                <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                                <td><?php echo formatDate($ticket['created_at']); ?></td>
                                                <td>
                                                    <a href="<?php echo SITE_URL; ?>/user/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Bekijken
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
