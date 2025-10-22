<?php
/**
 * My Tickets Page
 * 
 * Display all tickets created by the logged-in user with sorting and filtering
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

// Get sorting parameters
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'desc';

// Validate sort parameters
$validSortFields = ['created_at', 'status', 'priority', 'ticket_number'];
if (!in_array($sortBy, $validSortFields)) {
    $sortBy = 'created_at';
}

$validSortOrders = ['asc', 'desc'];
if (!in_array($sortOrder, $validSortOrders)) {
    $sortOrder = 'desc';
}

// Get filter parameters
$filterStatus = $_GET['status'] ?? '';

// Get all user tickets
$allTickets = $ticketClass->getTicketsByUser($userId);

// Apply status filter if set
if (!empty($filterStatus)) {
    $allTickets = array_filter($allTickets, function($ticket) use ($filterStatus) {
        return $ticket['status'] === $filterStatus;
    });
}

// Sort tickets
usort($allTickets, function($a, $b) use ($sortBy, $sortOrder) {
    $aVal = $a[$sortBy];
    $bVal = $b[$sortBy];
    
    // Handle date sorting
    if ($sortBy === 'created_at') {
        $aVal = strtotime($aVal);
        $bVal = strtotime($bVal);
    }
    
    // Handle status and priority sorting (use predefined order)
    if ($sortBy === 'status') {
        $statusOrder = ['open' => 1, 'in_progress' => 2, 'pending' => 3, 'resolved' => 4, 'closed' => 5];
        $aVal = $statusOrder[$aVal] ?? 999;
        $bVal = $statusOrder[$bVal] ?? 999;
    }
    
    if ($sortBy === 'priority') {
        $priorityOrder = ['urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        $aVal = $priorityOrder[$aVal] ?? 999;
        $bVal = $priorityOrder[$bVal] ?? 999;
    }
    
    if ($sortOrder === 'asc') {
        return $aVal <=> $bVal;
    } else {
        return $bVal <=> $aVal;
    }
});

$pageTitle = 'My Tickets';
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
                    <h1 class="h2"><i class="bi bi-list-task"></i> Mijn Tickets</h1>
                    <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nieuw Ticket Aanmaken
                    </a>
                </div>

    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <div class="col-12">

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Filter op Status</label>
                                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                    <option value="">Alle Statussen</option>
                                    <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In Behandeling</option>
                                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Wachtend</option>
                                    <option value="resolved" <?php echo $filterStatus === 'resolved' ? 'selected' : ''; ?>>Opgelost</option>
                                    <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Gesloten</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort" class="form-label">Sorteren Op</label>
                                <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Aanmaakdatum</option>
                                    <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Status</option>
                                    <option value="priority" <?php echo $sortBy === 'priority' ? 'selected' : ''; ?>>Prioriteit</option>
                                    <option value="ticket_number" <?php echo $sortBy === 'ticket_number' ? 'selected' : ''; ?>>Ticketnummer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="order" class="form-label">Volgorde</label>
                                <select class="form-select" id="order" name="order" onchange="this.form.submit()">
                                    <option value="desc" <?php echo $sortOrder === 'desc' ? 'selected' : ''; ?>>Aflopend</option>
                                    <option value="asc" <?php echo $sortOrder === 'asc' ? 'selected' : ''; ?>>Oplopend</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <?php if (!empty($filterStatus)): ?>
                                    <a href="<?php echo SITE_URL; ?>/user/my_tickets.php" class="btn btn-secondary w-100">
                                        <i class="bi bi-x-circle"></i> Filters Wissen
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tickets Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-ticket-detailed"></i> 
                            <?php echo count($allTickets); ?> ticket(s) weergegeven
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($allTickets)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <?php if (!empty($filterStatus)): ?>
                                    Geen tickets gevonden met status "<?php echo escapeOutput($filterStatus); ?>".
                                <?php else: ?>
                                    Je hebt nog geen tickets aangemaakt. 
                                    <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="alert-link">Maak je eerste ticket aan</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <a href="?sort=ticket_number&order=<?php echo ($sortBy === 'ticket_number' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?><?php echo !empty($filterStatus) ? '&status=' . $filterStatus : ''; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    Ticket # 
                                                    <?php if ($sortBy === 'ticket_number'): ?>
                                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Titel</th>
                                            <th>Categorie</th>
                                            <th>
                                                <a href="?sort=priority&order=<?php echo ($sortBy === 'priority' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?><?php echo !empty($filterStatus) ? '&status=' . $filterStatus : ''; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    Prioriteit 
                                                    <?php if ($sortBy === 'priority'): ?>
                                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?sort=status&order=<?php echo ($sortBy === 'status' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?><?php echo !empty($filterStatus) ? '&status=' . $filterStatus : ''; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    Status 
                                                    <?php if ($sortBy === 'status'): ?>
                                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>
                                                <a href="?sort=created_at&order=<?php echo ($sortBy === 'created_at' && $sortOrder === 'asc') ? 'desc' : 'asc'; ?><?php echo !empty($filterStatus) ? '&status=' . $filterStatus : ''; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    Aangemaakt 
                                                    <?php if ($sortBy === 'created_at'): ?>
                                                        <i class="bi bi-arrow-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allTickets as $ticket): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo SITE_URL; ?>/user/ticket_detail.php?id=<?php echo $ticket['ticket_id']; ?>" class="text-decoration-none">
                                                        <strong><?php echo escapeOutput($ticket['ticket_number']); ?></strong>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $title = $ticket['title'];
                                                    echo escapeOutput(strlen($title) > 50 ? substr($title, 0, 50) . '...' : $title); 
                                                    ?>
                                                </td>
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
