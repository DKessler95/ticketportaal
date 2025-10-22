<?php
/**
 * Change Management - ITIL Change Requests
 * Admin interface for managing system changes
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

$db = Database::getInstance();
$pageTitle = 'Change Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ongeldig beveiligingstoken';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_change':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category = $_POST['category'] ?? '';
                $priority = $_POST['priority'] ?? 'medium';
                $impact = $_POST['impact'] ?? 'medium';
                $risk = $_POST['risk'] ?? 'medium';
                $planned_start = $_POST['planned_start'] ?? '';
                $planned_end = $_POST['planned_end'] ?? '';
                
                if (empty($title) || empty($description) || empty($category)) {
                    $error = 'Titel, beschrijving en categorie zijn verplicht';
                } else {
                    try {
                        $changeNumber = 'CHG-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                        
                        $sql = "INSERT INTO change_requests (change_number, title, description, category, priority, impact, risk, planned_start, planned_end, status, created_by, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())";
                        
                        $db->execute($sql, [
                            $changeNumber, $title, $description, $category, $priority, 
                            $impact, $risk, $planned_start, $planned_end, $_SESSION['user_id']
                        ]);
                        
                        $_SESSION['success_message'] = 'Change request succesvol aangemaakt: ' . $changeNumber;
                        header('Location: ' . SITE_URL . '/admin/change_management.php');
                        exit;
                    } catch (Exception $e) {
                        $error = 'Fout bij aanmaken change request: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Check for session success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Check if table exists and get all change requests
try {
    // First check if table exists
    $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'change_requests'");
    
    if (!$tableCheck) {
        $error = 'Database tabel "change_requests" bestaat nog niet. Voer eerst de migratie uit via phpMyAdmin: database/migrations/create_change_requests_table.sql';
        $changes = [];
    } else {
        $sql = "SELECT cr.*, u.first_name, u.last_name 
                FROM change_requests cr 
                LEFT JOIN users u ON cr.created_by = u.user_id 
                ORDER BY cr.created_at DESC";
        $changes = $db->fetchAll($sql);
        
        // Handle case where fetchAll returns false
        if ($changes === false) {
            $changes = [];
        }
    }
} catch (Exception $e) {
    $changes = [];
    $error = 'Fout bij laden change requests: ' . $e->getMessage();
}

// Get change categories
$changeCategories = [
    'infrastructure' => 'Infrastructuur',
    'application' => 'Applicatie',
    'security' => 'Beveiliging',
    'network' => 'Netwerk',
    'hardware' => 'Hardware',
    'software' => 'Software',
    'process' => 'Proces'
];

// Status colors
function getChangeStatusBadge($status) {
    $badges = [
        'draft' => 'bg-secondary',
        'submitted' => 'bg-info',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'scheduled' => 'bg-warning',
        'in_progress' => 'bg-primary',
        'completed' => 'bg-success',
        'failed' => 'bg-danger',
        'cancelled' => 'bg-dark'
    ];
    
    $statusLabels = [
        'draft' => 'Concept',
        'submitted' => 'Ingediend',
        'approved' => 'Goedgekeurd',
        'rejected' => 'Afgewezen',
        'scheduled' => 'Gepland',
        'in_progress' => 'In Behandeling',
        'completed' => 'Voltooid',
        'failed' => 'Mislukt',
        'cancelled' => 'Geannuleerd'
    ];
    
    $class = $badges[$status] ?? 'bg-secondary';
    $label = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

// Priority colors
function getChangePriorityBadge($priority) {
    $badges = [
        'low' => 'bg-success',
        'medium' => 'bg-warning',
        'high' => 'bg-danger',
        'urgent' => 'bg-danger'
    ];
    
    $labels = [
        'low' => 'Laag',
        'medium' => 'Gemiddeld',
        'high' => 'Hoog',
        'urgent' => 'Urgent'
    ];
    
    $class = $badges[$priority] ?? 'bg-secondary';
    $label = $labels[$priority] ?? ucfirst($priority);
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}
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
                    <h1 class="h2"><i class="bi bi-arrow-repeat"></i> Change Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createChangeModal">
                        <i class="bi bi-plus-circle"></i> Nieuwe Change Request
                    </button>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo escapeOutput($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo escapeOutput($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Debug info -->
                <?php if (defined('DEBUG') && DEBUG): ?>
                    <div class="alert alert-info">
                        <strong>Debug:</strong> Aantal change requests: <?php echo count($changes); ?>
                    </div>
                <?php endif; ?>

                <!-- Change Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Change Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($changes)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-2">Geen change requests gevonden.</p>
                                <small class="text-muted">
                                    Tip: Zorg ervoor dat de database tabellen zijn aangemaakt via de migratie SQL.
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Change #</th>
                                            <th>Titel</th>
                                            <th>Categorie</th>
                                            <th>Prioriteit</th>
                                            <th>Status</th>
                                            <th>Aangemaakt Door</th>
                                            <th>Aangemaakt</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($changes as $change): ?>
                                            <tr>
                                                <td>
                                                    <code><?php echo escapeOutput($change['change_number']); ?></code>
                                                </td>
                                                <td>
                                                    <strong><?php echo escapeOutput($change['title']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo escapeOutput($changeCategories[$change['category']] ?? $change['category']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo getChangePriorityBadge($change['priority']); ?></td>
                                                <td><?php echo getChangeStatusBadge($change['status']); ?></td>
                                                <td><?php echo escapeOutput($change['first_name'] . ' ' . $change['last_name']); ?></td>
                                                <td><?php echo formatDate($change['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?php echo SITE_URL; ?>/admin/change_detail.php?id=<?php echo $change['id']; ?>" 
                                                           class="btn btn-outline-primary" title="Details Bekijken">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
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
            </main>
        </div>
    </div>

    <!-- Create Change Modal -->
    <div class="modal fade" id="createChangeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Nieuwe Change Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_change">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Change Titel *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Categorie *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Selecteer categorie...</option>
                                        <?php foreach ($changeCategories as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschrijving *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Prioriteit</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">Laag</option>
                                        <option value="medium" selected>Gemiddeld</option>
                                        <option value="high">Hoog</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="impact" class="form-label">Impact</label>
                                    <select class="form-select" id="impact" name="impact">
                                        <option value="low">Laag</option>
                                        <option value="medium" selected>Gemiddeld</option>
                                        <option value="high">Hoog</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="risk" class="form-label">Risico</label>
                                    <select class="form-select" id="risk" name="risk">
                                        <option value="low">Laag</option>
                                        <option value="medium" selected>Gemiddeld</option>
                                        <option value="high">Hoog</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="planned_start" class="form-label">Geplande Start</label>
                                    <input type="datetime-local" class="form-control" id="planned_start" name="planned_start">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="planned_end" class="form-label">Geplande Einde</label>
                                    <input type="datetime-local" class="form-control" id="planned_end" name="planned_end">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-primary">Change Request Aanmaken</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
