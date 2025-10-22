<?php
/**
 * Agent My Tickets Page
 * 
 * Display tickets assigned to the logged-in agent with filtering and sorting
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and check authentication
initSession();
requireRole(['agent', 'admin']);

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];
$userRole = $_SESSION['role'];

// Initialize classes
$ticketClass = new Ticket();
$categoryClass = new Category();

// Get filter parameters
$filters = [];
$filters['assigned_agent_id'] = $userId; // Only show tickets assigned to this agent

if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}
if (!empty($_GET['category_id'])) {
    $filters['category_id'] = $_GET['category_id'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Get tickets assigned to this agent with filters
$tickets = $ticketClass->getAllTickets($filters);

// Get overdue tickets for this agent
$overdueTickets = $ticketClass->getOverdueTickets();
$overdueTicketIds = array_column($overdueTickets, 'ticket_id');
// Filter overdue tickets to only those assigned to this agent
$myOverdueTickets = array_filter($overdueTickets, function($ticket) use ($userId) {
    return $ticket['assigned_agent_id'] == $userId;
});

// Get categories for filter dropdown
$categories = $categoryClass->getCategories(true); // true = active only

$pageTitle = 'My Assigned Tickets';
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
                    <h1 class="h2">Mijn Toegewezen Tickets</h1>
                </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">Alle Statussen</option>
                                    <option value="open" <?php echo (isset($_GET['status']) && $_GET['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo (isset($_GET['status']) && $_GET['status'] === 'in_progress') ? 'selected' : ''; ?>>In Behandeling</option>
                                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Wachtend</option>
                                    <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] === 'resolved') ? 'selected' : ''; ?>>Opgelost</option>
                                    <option value="closed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'closed') ? 'selected' : ''; ?>>Gesloten</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="priority" class="form-label">Prioriteit</label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="">Alle Prioriteiten</option>
                                    <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'low') ? 'selected' : ''; ?>>Laag</option>
                                    <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'medium') ? 'selected' : ''; ?>>Gemiddeld</option>
                                    <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'high') ? 'selected' : ''; ?>>Hoog</option>
                                    <option value="urgent" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="category_id" class="form-label">Categorie</label>
                                <select name="category_id" id="category_id" class="form-select">
                                    <option value="">Alle Categorieën</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" 
                                                <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo escapeOutput($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Datum Van</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" 
                                       value="<?php echo isset($_GET['date_from']) ? escapeOutput($_GET['date_from']) : ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Datum Tot</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" 
                                       value="<?php echo isset($_GET['date_to']) ? escapeOutput($_GET['date_to']) : ''; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="d-grid gap-2 w-100">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filteren
                                    </button>
                                    <a href="<?php echo SITE_URL; ?>/agent/my_tickets.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Wissen
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Tickets Alert -->
        <?php if (!empty($myOverdueTickets)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>Waarschuwing:</strong> U heeft <?php echo count($myOverdueTickets); ?> verlopen ticket(s) die de SLA overschrijden.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tickets Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Mijn Tickets (<?php echo count($tickets); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen tickets aan u toegewezen die overeenkomen met de geselecteerde filters.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Titel</th>
                                            <th>Gebruiker</th>
                                            <th>Categorie</th>
                                            <th>Prioriteit</th>
                                            <th>Status</th>
                                            <th>Aangemaakt</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): 
                                            $isOverdue = in_array($ticket['ticket_id'], $overdueTicketIds);
                                            $rowClass = $isOverdue ? 'table-warning' : '';
                                        ?>
                                            <tr class="<?php echo $rowClass; ?>">
                                                <td>
                                                    <strong><?php echo escapeOutput($ticket['ticket_number']); ?></strong>
                                                    <?php if ($isOverdue): ?>
                                                        <i class="bi bi-exclamation-triangle text-danger" title="Verlopen"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo escapeOutput($ticket['title']); ?></td>
                                                <td><?php echo escapeOutput($ticket['user_first_name'] . ' ' . $ticket['user_last_name']); ?></td>
                                                <td><?php echo escapeOutput($ticket['category_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                                <td><?php echo getStatusBadge($ticket['status']); ?></td>
                                                <td><?php echo formatDate($ticket['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>" 
                                                           class="btn btn-outline-primary" title="Details Bekijken">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="quickStatusUpdate(<?php echo $ticket['ticket_id']; ?>, 'in_progress')"
                                                                title="Markeer In Behandeling">
                                                            <i class="bi bi-play-circle"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
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

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function quickStatusUpdate(ticketId, status) {
            if (confirm('Ticket status bijwerken naar "In Behandeling"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=' + ticketId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_status';
                form.appendChild(actionInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                form.appendChild(statusInput);
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo generateCSRFToken(); ?>';
                form.appendChild(csrfInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
            </main>
        </div>
    </div>
</body>
</html>
