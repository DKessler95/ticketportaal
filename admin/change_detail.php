<?php
/**
 * Change Request Detail & Workflow
 * ITIL-compliant change management with approval workflow
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

initSession();
requireRole(['admin', 'agent']);

$db = Database::getInstance();
$changeId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$changeId) {
    header('Location: ' . SITE_URL . '/admin/change_management.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ongeldig beveiligingstoken';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_status':
                $newStatus = $_POST['status'] ?? '';
                $comments = trim($_POST['comments'] ?? '');
                
                try {
                    // Get current status
                    $current = $db->fetchOne("SELECT status FROM change_requests WHERE id = ?", [$changeId]);
                    
                    // Update status
                    $db->execute("UPDATE change_requests SET status = ?, updated_at = NOW() WHERE id = ?", 
                        [$newStatus, $changeId]);
                    
                    // Log the change
                    $db->execute("INSERT INTO change_logs (change_id, user_id, action, old_value, new_value, comments, created_at) 
                                  VALUES (?, ?, 'status_change', ?, ?, ?, NOW())",
                        [$changeId, $_SESSION['user_id'], $current['status'], $newStatus, $comments]);
                    
                    $_SESSION['success_message'] = 'Status bijgewerkt naar: ' . $newStatus;
                    header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
                    exit;
                } catch (Exception $e) {
                    $error = 'Fout bij bijwerken status: ' . $e->getMessage();
                }
                break;
                
            case 'approve':
                $comments = trim($_POST['comments'] ?? '');
                
                try {
                    // Update change status to approved
                    $db->execute("UPDATE change_requests SET status = 'approved', approved_by = ?, updated_at = NOW() WHERE id = ?",
                        [$_SESSION['user_id'], $changeId]);
                    
                    // Add approval record
                    $db->execute("INSERT INTO change_approvals (change_id, approver_id, status, comments, approved_at) 
                                  VALUES (?, ?, 'approved', ?, NOW())",
                        [$changeId, $_SESSION['user_id'], $comments]);
                    
                    // Log the approval
                    $db->execute("INSERT INTO change_logs (change_id, user_id, action, new_value, comments, created_at) 
                                  VALUES (?, ?, 'approved', 'approved', ?, NOW())",
                        [$changeId, $_SESSION['user_id'], $comments]);
                    
                    $_SESSION['success_message'] = 'Change request goedgekeurd';
                    header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
                    exit;
                } catch (Exception $e) {
                    $error = 'Fout bij goedkeuren: ' . $e->getMessage();
                }
                break;
                
            case 'reject':
                $comments = trim($_POST['comments'] ?? '');
                
                try {
                    $db->execute("UPDATE change_requests SET status = 'rejected', updated_at = NOW() WHERE id = ?", [$changeId]);
                    
                    $db->execute("INSERT INTO change_approvals (change_id, approver_id, status, comments, approved_at) 
                                  VALUES (?, ?, 'rejected', ?, NOW())",
                        [$changeId, $_SESSION['user_id'], $comments]);
                    
                    $db->execute("INSERT INTO change_logs (change_id, user_id, action, new_value, comments, created_at) 
                                  VALUES (?, ?, 'rejected', 'rejected', ?, NOW())",
                        [$changeId, $_SESSION['user_id'], $comments]);
                    
                    $_SESSION['success_message'] = 'Change request afgewezen';
                    header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
                    exit;
                } catch (Exception $e) {
                    $error = 'Fout bij afwijzen: ' . $e->getMessage();
                }
                break;
                
            case 'assign':
                $agentId = filter_var($_POST['agent_id'] ?? 0, FILTER_VALIDATE_INT);
                
                try {
                    $db->execute("UPDATE change_requests SET assigned_agent_id = ?, updated_at = NOW() WHERE id = ?",
                        [$agentId, $changeId]);
                    
                    $agent = $db->fetchOne("SELECT first_name, last_name FROM users WHERE user_id = ?", [$agentId]);
                    $agentName = $agent['first_name'] . ' ' . $agent['last_name'];
                    
                    $db->execute("INSERT INTO change_logs (change_id, user_id, action, new_value, comments, created_at) 
                                  VALUES (?, ?, 'assigned', ?, 'Toegewezen aan agent', NOW())",
                        [$changeId, $_SESSION['user_id'], $agentName]);
                    
                    $_SESSION['success_message'] = 'Change toegewezen aan ' . $agentName;
                    header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
                    exit;
                } catch (Exception $e) {
                    $error = 'Fout bij toewijzen: ' . $e->getMessage();
                }
                break;
                
            case 'add_comment':
                $comment = trim($_POST['comment'] ?? '');
                
                if (!empty($comment)) {
                    try {
                        $db->execute("INSERT INTO change_logs (change_id, user_id, action, comments, created_at) 
                                      VALUES (?, ?, 'comment', ?, NOW())",
                            [$changeId, $_SESSION['user_id'], $comment]);
                        
                        $_SESSION['success_message'] = 'Opmerking toegevoegd';
                        header('Location: ' . SITE_URL . '/admin/change_detail.php?id=' . $changeId);
                        exit;
                    } catch (Exception $e) {
                        $error = 'Fout bij toevoegen opmerking: ' . $e->getMessage();
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

// Get change request details
$change = $db->fetchOne(
    "SELECT cr.*, 
            u.first_name as creator_first_name, u.last_name as creator_last_name, u.email as creator_email,
            a.first_name as agent_first_name, a.last_name as agent_last_name,
            ap.first_name as approver_first_name, ap.last_name as approver_last_name
     FROM change_requests cr
     LEFT JOIN users u ON cr.created_by = u.user_id
     LEFT JOIN users a ON cr.assigned_agent_id = a.user_id
     LEFT JOIN users ap ON cr.approved_by = ap.user_id
     WHERE cr.id = ?",
    [$changeId]
);

if (!$change) {
    header('Location: ' . SITE_URL . '/admin/change_management.php');
    exit;
}

// Get change logs (audit trail)
$logs = $db->fetchAll(
    "SELECT cl.*, u.first_name, u.last_name 
     FROM change_logs cl
     LEFT JOIN users u ON cl.user_id = u.user_id
     WHERE cl.change_id = ?
     ORDER BY cl.created_at DESC",
    [$changeId]
);

// Get all agents for assignment
$agents = $db->fetchAll(
    "SELECT user_id, first_name, last_name FROM users 
     WHERE (role = 'agent' OR role = 'admin') AND is_active = 1 
     ORDER BY first_name, last_name"
);

$pageTitle = 'Change Request: ' . $change['change_number'];

// Status badge function
function getChangeStatusBadge($status) {
    $badges = [
        'draft' => ['class' => 'bg-secondary', 'label' => 'Concept'],
        'submitted' => ['class' => 'bg-info', 'label' => 'Ingediend'],
        'approved' => ['class' => 'bg-success', 'label' => 'Goedgekeurd'],
        'rejected' => ['class' => 'bg-danger', 'label' => 'Afgewezen'],
        'scheduled' => ['class' => 'bg-warning', 'label' => 'Gepland'],
        'in_progress' => ['class' => 'bg-primary', 'label' => 'In Behandeling'],
        'completed' => ['class' => 'bg-success', 'label' => 'Voltooid'],
        'failed' => ['class' => 'bg-danger', 'label' => 'Mislukt'],
        'cancelled' => ['class' => 'bg-dark', 'label' => 'Geannuleerd']
    ];
    
    $badge = $badges[$status] ?? ['class' => 'bg-secondary', 'label' => $status];
    return '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
}

$changeCategories = [
    'infrastructure' => 'Infrastructuur',
    'application' => 'Applicatie',
    'security' => 'Beveiliging',
    'network' => 'Netwerk',
    'hardware' => 'Hardware',
    'software' => 'Software',
    'process' => 'Proces'
];
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
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid #fff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <a href="<?php echo SITE_URL; ?>/admin/change_management.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <?php echo escapeOutput($change['change_number']); ?>
                    </h1>
                    <div>
                        <?php echo getChangeStatusBadge($change['status']); ?>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo escapeOutput($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo escapeOutput($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Change Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Change Details</h5>
                            </div>
                            <div class="card-body">
                                <h4><?php echo escapeOutput($change['title']); ?></h4>
                                <p class="text-muted"><?php echo nl2br(escapeOutput($change['description'])); ?></p>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-5">Categorie:</dt>
                                            <dd class="col-sm-7">
                                                <span class="badge bg-light text-dark">
                                                    <?php echo $changeCategories[$change['category']] ?? $change['category']; ?>
                                                </span>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Prioriteit:</dt>
                                            <dd class="col-sm-7">
                                                <?php
                                                $priorityBadges = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger'];
                                                $priorityLabels = ['low' => 'Laag', 'medium' => 'Gemiddeld', 'high' => 'Hoog', 'urgent' => 'Urgent'];
                                                ?>
                                                <span class="badge bg-<?php echo $priorityBadges[$change['priority']]; ?>">
                                                    <?php echo $priorityLabels[$change['priority']]; ?>
                                                </span>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Impact:</dt>
                                            <dd class="col-sm-7">
                                                <span class="badge bg-<?php echo $priorityBadges[$change['impact']]; ?>">
                                                    <?php echo $priorityLabels[$change['impact']]; ?>
                                                </span>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Risico:</dt>
                                            <dd class="col-sm-7">
                                                <span class="badge bg-<?php echo $priorityBadges[$change['risk']]; ?>">
                                                    <?php echo $priorityLabels[$change['risk']]; ?>
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-5">Aangemaakt door:</dt>
                                            <dd class="col-sm-7">
                                                <?php echo escapeOutput($change['creator_first_name'] . ' ' . $change['creator_last_name']); ?>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Toegewezen aan:</dt>
                                            <dd class="col-sm-7">
                                                <?php if ($change['agent_first_name']): ?>
                                                    <?php echo escapeOutput($change['agent_first_name'] . ' ' . $change['agent_last_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Niet toegewezen</span>
                                                <?php endif; ?>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Goedgekeurd door:</dt>
                                            <dd class="col-sm-7">
                                                <?php if ($change['approver_first_name']): ?>
                                                    <?php echo escapeOutput($change['approver_first_name'] . ' ' . $change['approver_last_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Nog niet goedgekeurd</span>
                                                <?php endif; ?>
                                            </dd>
                                            
                                            <dt class="col-sm-5">Aangemaakt:</dt>
                                            <dd class="col-sm-7"><?php echo formatDate($change['created_at']); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                <?php if ($change['planned_start'] || $change['planned_end']): ?>
                                    <hr>
                                    <h6>Planning</h6>
                                    <div class="row">
                                        <?php if ($change['planned_start']): ?>
                                            <div class="col-md-6">
                                                <strong>Geplande Start:</strong><br>
                                                <?php echo date('d-m-Y H:i', strtotime($change['planned_start'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($change['planned_end']): ?>
                                            <div class="col-md-6">
                                                <strong>Geplande Einde:</strong><br>
                                                <?php echo date('d-m-Y H:i', strtotime($change['planned_end'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Audit Trail / Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Audit Trail</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($logs)): ?>
                                    <p class="text-muted">Nog geen activiteit</p>
                                <?php else: ?>
                                    <div class="timeline">
                                        <?php foreach ($logs as $log): ?>
                                            <div class="timeline-item">
                                                <div class="d-flex justify-content-between">
                                                    <strong><?php echo escapeOutput($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                                                    <small class="text-muted"><?php echo formatDate($log['created_at']); ?></small>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php
                                                    $actionLabels = [
                                                        'status_change' => 'Status gewijzigd',
                                                        'approved' => 'Goedgekeurd',
                                                        'rejected' => 'Afgewezen',
                                                        'assigned' => 'Toegewezen',
                                                        'comment' => 'Opmerking toegevoegd'
                                                    ];
                                                    echo $actionLabels[$log['action']] ?? $log['action'];
                                                    
                                                    if ($log['old_value'] && $log['new_value']) {
                                                        echo ': ' . escapeOutput($log['old_value']) . ' → ' . escapeOutput($log['new_value']);
                                                    } elseif ($log['new_value']) {
                                                        echo ': ' . escapeOutput($log['new_value']);
                                                    }
                                                    ?>
                                                </div>
                                                <?php if ($log['comments']): ?>
                                                    <div class="mt-1">
                                                        <em><?php echo nl2br(escapeOutput($log['comments'])); ?></em>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Actions -->
                    <div class="col-lg-4">
                        <!-- Edit Rejected Change -->
                        <?php if ($change['status'] === 'rejected' && $change['created_by'] == $_SESSION['user_id']): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-warning">
                                    <h6 class="mb-0">Change Aanpassen</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small">Deze change is afgewezen. U kunt deze aanpassen en opnieuw indienen.</p>
                                    <a href="<?php echo SITE_URL; ?>/admin/change_edit.php?id=<?php echo $changeId; ?>" 
                                       class="btn btn-warning w-100">
                                        <i class="bi bi-pencil"></i> Bewerken & Opnieuw Indienen
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Workflow Actions -->
                        <?php if ($change['status'] === 'draft' || $change['status'] === 'submitted'): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Goedkeuring</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <div class="mb-3">
                                            <label class="form-label">Opmerkingen</label>
                                            <textarea name="comments" class="form-control" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-check-circle"></i> Goedkeuren
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <div class="mb-3">
                                            <label class="form-label">Reden afwijzing</label>
                                            <textarea name="comments" class="form-control" rows="3" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bi bi-x-circle"></i> Afwijzen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Assign Agent -->
                        <?php if ($change['status'] === 'approved' || $change['status'] === 'scheduled'): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Toewijzen aan Agent</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="assign">
                                        <div class="mb-3">
                                            <select name="agent_id" class="form-select" required>
                                                <option value="">Selecteer agent...</option>
                                                <?php foreach ($agents as $agent): ?>
                                                    <option value="<?php echo $agent['user_id']; ?>"
                                                            <?php echo ($change['assigned_agent_id'] == $agent['user_id']) ? 'selected' : ''; ?>>
                                                        <?php echo escapeOutput($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-person-plus"></i> Toewijzen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Status Update -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Status Bijwerken</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <div class="mb-3">
                                        <select name="status" class="form-select" required>
                                            <option value="draft" <?php echo $change['status'] === 'draft' ? 'selected' : ''; ?>>Concept</option>
                                            <option value="submitted" <?php echo $change['status'] === 'submitted' ? 'selected' : ''; ?>>Ingediend</option>
                                            <option value="approved" <?php echo $change['status'] === 'approved' ? 'selected' : ''; ?>>Goedgekeurd</option>
                                            <option value="scheduled" <?php echo $change['status'] === 'scheduled' ? 'selected' : ''; ?>>Gepland</option>
                                            <option value="in_progress" <?php echo $change['status'] === 'in_progress' ? 'selected' : ''; ?>>In Behandeling</option>
                                            <option value="completed" <?php echo $change['status'] === 'completed' ? 'selected' : ''; ?>>Voltooid</option>
                                            <option value="failed" <?php echo $change['status'] === 'failed' ? 'selected' : ''; ?>>Mislukt</option>
                                            <option value="cancelled" <?php echo $change['status'] === 'cancelled' ? 'selected' : ''; ?>>Geannuleerd</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <textarea name="comments" class="form-control" rows="2" placeholder="Opmerkingen..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-secondary w-100">
                                        <i class="bi bi-arrow-repeat"></i> Status Bijwerken
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Add Comment -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Opmerking Toevoegen</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="add_comment">
                                    <div class="mb-3">
                                        <textarea name="comment" class="form-control" rows="3" required placeholder="Voeg een opmerking toe..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="bi bi-chat-left-text"></i> Opmerking Toevoegen
                                    </button>
                                </form>
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
