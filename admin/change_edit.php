<?php
/**
 * Edit Rejected Change Request
 * 
 * Allows users to edit and resubmit rejected change requests
 */

require_once __DIR__ . '/../includes/functions.php';

// Initialize session and check authentication
initSession();
requireLogin();

// Get change ID
$changeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$changeId) {
    header('Location: ' . SITE_URL . '/admin/change_management.php');
    exit;
}

$db = Database::getInstance();

// Get change request details
$change = $db->fetchOne(
    "SELECT * FROM change_requests WHERE id = ?",
    [$changeId]
);

if (!$change) {
    header('Location: ' . SITE_URL . '/admin/change_management.php');
    exit;
}

// Check if user is the creator
if ($change['created_by'] != $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'U heeft geen toegang om deze change te bewerken.';
    header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
    exit;
}

// Check if change is rejected
if ($change['status'] !== 'rejected') {
    $_SESSION['error_message'] = 'Alleen afgewezen changes kunnen worden bewerkt.';
    header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
    exit;
}

// Change categories
$changeCategories = [
    'infrastructure' => 'Infrastructuur',
    'application' => 'Applicatie',
    'database' => 'Database',
    'network' => 'Netwerk',
    'security' => 'Beveiliging',
    'other' => 'Overig'
];

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $impact = $_POST['impact'] ?? 'medium';
        $risk = $_POST['risk'] ?? 'medium';
        $plannedStart = !empty($_POST['planned_start']) ? $_POST['planned_start'] : null;
        $plannedEnd = !empty($_POST['planned_end']) ? $_POST['planned_end'] : null;
        
        if (empty($title) || empty($description) || empty($category)) {
            $error = 'Titel, beschrijving en categorie zijn verplicht.';
        } else {
            // Update change request and set status back to submitted
            $sql = "UPDATE change_requests 
                    SET title = ?, description = ?, category = ?, priority = ?, 
                        impact = ?, risk = ?, planned_start = ?, planned_end = ?,
                        status = 'submitted', updated_at = NOW()
                    WHERE id = ?";
            
            $result = $db->execute($sql, [
                $title, $description, $category, $priority,
                $impact, $risk, $plannedStart, $plannedEnd,
                $changeId
            ]);
            
            if ($result) {
                // Log the resubmission
                $db->execute(
                    "INSERT INTO change_logs (change_id, user_id, action, comments, created_at)
                     VALUES (?, ?, 'resubmitted', 'Change aangepast en opnieuw ingediend', NOW())",
                    [$changeId, $_SESSION['user_id']]
                );
                
                $_SESSION['success_message'] = 'Change succesvol aangepast en opnieuw ingediend!';
                header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
                exit;
            } else {
                $error = 'Fout bij het opslaan van de wijzigingen.';
            }
        }
    }
}

$pageTitle = 'Change Bewerken';
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
                    <h1 class="h2"><i class="bi bi-pencil"></i> Change Bewerken & Opnieuw Indienen</h1>
                    <a href="<?php echo SITE_URL; ?>/admin/change_detail.php?id=<?php echo $changeId; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Terug
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo escapeOutput($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">Change Request Aanpassen</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Deze change is afgewezen. Pas de gegevens aan en dien opnieuw in.
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Change Titel *</label>
                                                <input type="text" class="form-control" id="title" name="title" 
                                                       value="<?php echo escapeOutput($change['title']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="category" class="form-label">Categorie *</label>
                                                <select class="form-select" id="category" name="category" required>
                                                    <option value="">Selecteer categorie...</option>
                                                    <?php foreach ($changeCategories as $key => $label): ?>
                                                        <option value="<?php echo $key; ?>" 
                                                                <?php echo $change['category'] === $key ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Beschrijving *</label>
                                        <textarea class="form-control" id="description" name="description" rows="6" required><?php echo escapeOutput($change['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="priority" class="form-label">Prioriteit</label>
                                                <select class="form-select" id="priority" name="priority">
                                                    <option value="low" <?php echo $change['priority'] === 'low' ? 'selected' : ''; ?>>Laag</option>
                                                    <option value="medium" <?php echo $change['priority'] === 'medium' ? 'selected' : ''; ?>>Gemiddeld</option>
                                                    <option value="high" <?php echo $change['priority'] === 'high' ? 'selected' : ''; ?>>Hoog</option>
                                                    <option value="urgent" <?php echo $change['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="impact" class="form-label">Impact</label>
                                                <select class="form-select" id="impact" name="impact">
                                                    <option value="low" <?php echo $change['impact'] === 'low' ? 'selected' : ''; ?>>Laag</option>
                                                    <option value="medium" <?php echo $change['impact'] === 'medium' ? 'selected' : ''; ?>>Gemiddeld</option>
                                                    <option value="high" <?php echo $change['impact'] === 'high' ? 'selected' : ''; ?>>Hoog</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="risk" class="form-label">Risico</label>
                                                <select class="form-select" id="risk" name="risk">
                                                    <option value="low" <?php echo $change['risk'] === 'low' ? 'selected' : ''; ?>>Laag</option>
                                                    <option value="medium" <?php echo $change['risk'] === 'medium' ? 'selected' : ''; ?>>Gemiddeld</option>
                                                    <option value="high" <?php echo $change['risk'] === 'high' ? 'selected' : ''; ?>>Hoog</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="planned_start" class="form-label">Geplande Start</label>
                                                <input type="datetime-local" class="form-control" id="planned_start" name="planned_start"
                                                       value="<?php echo $change['planned_start'] ? date('Y-m-d\TH:i', strtotime($change['planned_start'])) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="planned_end" class="form-label">Geplande Einde</label>
                                                <input type="datetime-local" class="form-control" id="planned_end" name="planned_end"
                                                       value="<?php echo $change['planned_end'] ? date('Y-m-d\TH:i', strtotime($change['planned_end'])) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-check-circle"></i> Opslaan & Opnieuw Indienen
                                        </button>
                                        <a href="<?php echo SITE_URL; ?>/admin/change_detail.php?id=<?php echo $changeId; ?>" 
                                           class="btn btn-secondary">
                                            Annuleren
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">Afwijzingsreden</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get rejection reason from logs
                                $rejectionLog = $db->fetchOne(
                                    "SELECT cl.*, u.first_name, u.last_name 
                                     FROM change_logs cl
                                     LEFT JOIN users u ON cl.user_id = u.user_id
                                     WHERE cl.change_id = ? AND cl.action = 'rejected'
                                     ORDER BY cl.created_at DESC
                                     LIMIT 1",
                                    [$changeId]
                                );
                                ?>
                                <?php if ($rejectionLog): ?>
                                    <p class="mb-2"><strong>Afgewezen door:</strong> 
                                        <?php echo escapeOutput($rejectionLog['first_name'] . ' ' . $rejectionLog['last_name']); ?>
                                    </p>
                                    <p class="mb-2"><strong>Datum:</strong> 
                                        <?php echo date('d-m-Y H:i', strtotime($rejectionLog['created_at'])); ?>
                                    </p>
                                    <p class="mb-0"><strong>Reden:</strong></p>
                                    <p class="text-muted"><?php echo nl2br(escapeOutput($rejectionLog['comments'] ?? 'Geen reden opgegeven')); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Geen afwijzingsreden beschikbaar.</p>
                                <?php endif; ?>
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
