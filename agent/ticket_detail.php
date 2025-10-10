<?php
/**
 * Agent Ticket Detail Page
 * 
 * Display full ticket information with all comments (including internal)
 * Allow ticket assignment, status updates, and adding comments
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Database.php';

// Initialize session and check authentication
initSession();
requireRole(['agent', 'admin']);

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];
$userRole = $_SESSION['role'];

// Get ticket ID from URL
$ticketId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$ticketId) {
    $_SESSION['error'] = 'Invalid ticket ID';
    redirectTo(SITE_URL . '/agent/dashboard.php');
}

// Initialize classes
$ticketClass = new Ticket();
$db = Database::getInstance();

// Initialize variables
$errors = [];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        switch ($_POST['action']) {
            case 'assign':
                $assignedAgentId = filter_var($_POST['assigned_agent_id'] ?? 0, FILTER_VALIDATE_INT);
                
                if (!$assignedAgentId) {
                    $error = 'Please select an agent';
                } else {
                    $result = $ticketClass->assignTicket($ticketId, $assignedAgentId);
                    
                    if ($result) {
                        $success = 'Ticket assigned successfully';
                        // Refresh ticket data
                        $ticket = $ticketClass->getTicketById($ticketId);
                    } else {
                        $error = $ticketClass->getError() ?: 'Failed to assign ticket';
                    }
                }
                break;
                
            case 'update_status':
                $status = $_POST['status'] ?? '';
                $resolution = trim($_POST['resolution'] ?? '');
                
                if (empty($status)) {
                    $error = 'Please select a status';
                } else {
                    $result = $ticketClass->updateStatus($ticketId, $status, $resolution);
                    
                    if ($result) {
                        $success = 'Ticket status updated successfully';
                        // Refresh ticket data
                        $ticket = $ticketClass->getTicketById($ticketId);
                    } else {
                        $error = $ticketClass->getError() ?: 'Failed to update ticket status';
                    }
                }
                break;
                
            case 'add_comment':
                $comment = trim($_POST['comment'] ?? '');
                $isInternal = isset($_POST['is_internal']) && $_POST['is_internal'] == '1';
                
                if (empty($comment)) {
                    $error = 'Comment cannot be empty';
                } else {
                    $result = $ticketClass->addComment($ticketId, $userId, $comment, $isInternal);
                    
                    if ($result) {
                        $success = 'Comment added successfully';
                        // Refresh comments
                        $comments = $ticketClass->getComments($ticketId, true);
                    } else {
                        $error = $ticketClass->getError() ?: 'Failed to add comment';
                    }
                }
                break;
                
            default:
                $error = 'Invalid action';
        }
    }
}

// Get ticket details
$ticket = $ticketClass->getTicketById($ticketId);

if (!$ticket) {
    $_SESSION['error'] = 'Ticket not found';
    redirectTo(SITE_URL . '/agent/dashboard.php');
}

// Get ticket comments (including internal)
$comments = $ticketClass->getComments($ticketId, true);

// Get ticket attachments
$attachments = $ticketClass->getAttachments($ticketId);

// Get SLA status
$slaStatus = $ticketClass->checkSLA($ticketId);

// Get all agents for assignment dropdown
$agents = $db->fetchAll(
    "SELECT user_id, first_name, last_name FROM users WHERE (role = 'agent' OR role = 'admin') AND is_active = 1 ORDER BY first_name, last_name",
    []
);

$pageTitle = 'Ticket Details - ' . $ticket['ticket_number'];
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/agent/dashboard.php">
                <i class="bi bi-ticket-perforated"></i> <?php echo escapeOutput(SITE_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/agent/dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/agent/my_tickets.php">
                            <i class="bi bi-person-check"></i> My Assigned Tickets
                        </a>
                    </li>
                    <?php if ($userRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/index.php">
                            <i class="bi bi-gear"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo escapeOutput($userName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/agent/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ticket <?php echo escapeOutput($ticket['ticket_number']); ?></li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo escapeOutput($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo escapeOutput($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Ticket Details -->
            <div class="col-lg-8">
                <!-- Ticket Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-ticket-detailed"></i> <?php echo escapeOutput($ticket['ticket_number']); ?>
                            <?php if ($slaStatus && $slaStatus['is_overdue']): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <h5><?php echo escapeOutput($ticket['title']); ?></h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Status:</strong> <?php echo getStatusBadge($ticket['status']); ?></p>
                                <p class="mb-1"><strong>Priority:</strong> <?php echo getPriorityBadge($ticket['priority']); ?></p>
                                <p class="mb-1"><strong>Category:</strong> <?php echo escapeOutput($ticket['category_name'] ?? 'N/A'); ?></p>
                                <p class="mb-1"><strong>Source:</strong> <?php echo escapeOutput(ucfirst($ticket['source'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Created:</strong> <?php echo formatDate($ticket['created_at']); ?></p>
                                <p class="mb-1"><strong>Updated:</strong> <?php echo formatDate($ticket['updated_at']); ?></p>
                                <p class="mb-1"><strong>User:</strong> <?php echo escapeOutput($ticket['user_first_name'] . ' ' . $ticket['user_last_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?php echo escapeOutput($ticket['user_email']); ?>"><?php echo escapeOutput($ticket['user_email']); ?></a></p>
                            </div>
                        </div>

                        <!-- SLA Status -->
                        <?php if ($slaStatus): ?>
                        <div class="alert <?php echo $slaStatus['is_overdue'] ? 'alert-danger' : 'alert-info'; ?> mb-3">
                            <strong>SLA Status:</strong>
                            <?php if ($slaStatus['is_overdue']): ?>
                                <i class="bi bi-exclamation-triangle"></i> Overdue by <?php echo abs($slaStatus['hours_remaining']); ?> hours
                            <?php else: ?>
                                <i class="bi bi-clock"></i> <?php echo $slaStatus['hours_remaining']; ?> hours remaining (<?php echo $slaStatus['sla_hours']; ?> hours SLA)
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <strong>Description:</strong>
                            <div class="border rounded p-3 mt-2 bg-light">
                                <?php echo nl2br(escapeOutput($ticket['description'])); ?>
                            </div>
                        </div>

                        <?php if ($ticket['resolution']): ?>
                        <div class="mb-3">
                            <strong>Resolution:</strong>
                            <div class="border rounded p-3 mt-2 bg-success bg-opacity-10">
                                <?php echo nl2br(escapeOutput($ticket['resolution'])); ?>
                            </div>
                            <p class="text-muted mt-1"><small>Resolved: <?php echo formatDate($ticket['resolved_at']); ?></small></p>
                        </div>
                        <?php endif; ?>

                        <!-- Attachments -->
                        <?php if (!empty($attachments)): ?>
                        <div class="mb-3">
                            <strong>Attachments:</strong>
                            <ul class="list-group mt-2">
                                <?php foreach ($attachments as $attachment): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="bi bi-paperclip"></i> <?php echo escapeOutput($attachment['filename']); ?>
                                            <small class="text-muted">(<?php echo number_format($attachment['filesize'] / 1024, 2); ?> KB)</small>
                                        </span>
                                        <a href="<?php echo SITE_URL; ?>/uploads/tickets/<?php echo escapeOutput(basename($attachment['filepath'])); ?>" 
                                           class="btn btn-sm btn-outline-primary" download>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Comments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted">No comments yet.</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="card mb-3 <?php echo $comment['is_internal'] ? 'border-warning' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong><?php echo escapeOutput($comment['first_name'] . ' ' . $comment['last_name']); ?></strong>
                                                <span class="badge bg-secondary ms-2"><?php echo escapeOutput(ucfirst($comment['role'])); ?></span>
                                                <?php if ($comment['is_internal']): ?>
                                                    <span class="badge bg-warning text-dark ms-1">Internal</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(escapeOutput($comment['comment'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Add Comment Form -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Add Comment</h6>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="add_comment">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div class="mb-3">
                                        <textarea name="comment" class="form-control" rows="4" required 
                                                  placeholder="Enter your comment..."></textarea>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="is_internal" id="is_internal" class="form-check-input" value="1">
                                        <label for="is_internal" class="form-check-label">
                                            <i class="bi bi-lock"></i> Internal comment (not visible to user)
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Add Comment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Actions -->
            <div class="col-lg-4">
                <!-- Assignment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Assignment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="assign">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="assigned_agent_id" class="form-label">Assign to Agent</label>
                                <select name="assigned_agent_id" id="assigned_agent_id" class="form-select" required>
                                    <option value="">Select Agent...</option>
                                    <?php foreach ($agents as $agent): ?>
                                        <option value="<?php echo $agent['user_id']; ?>" 
                                                <?php echo ($ticket['assigned_agent_id'] == $agent['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo escapeOutput($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Assign Ticket
                            </button>
                        </form>
                        
                        <?php if ($ticket['assigned_agent_id']): ?>
                            <div class="alert alert-info mt-3 mb-0">
                                <small>
                                    <strong>Currently assigned to:</strong><br>
                                    <?php echo escapeOutput($ticket['agent_first_name'] . ' ' . $ticket['agent_last_name']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Update -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="statusForm">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">New Status</label>
                                <select name="status" id="status" class="form-select" required onchange="toggleResolution()">
                                    <option value="">Select Status...</option>
                                    <option value="open" <?php echo ($ticket['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo ($ticket['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="pending" <?php echo ($ticket['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="resolved" <?php echo ($ticket['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="resolutionField" style="display: none;">
                                <label for="resolution" class="form-label">Resolution <span class="text-danger">*</span></label>
                                <textarea name="resolution" id="resolution" class="form-control" rows="4" 
                                          placeholder="Describe how the issue was resolved..."></textarea>
                                <small class="text-muted">Required when marking as resolved</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleResolution() {
            const statusSelect = document.getElementById('status');
            const resolutionField = document.getElementById('resolutionField');
            const resolutionTextarea = document.getElementById('resolution');
            
            if (statusSelect.value === 'resolved') {
                resolutionField.style.display = 'block';
                resolutionTextarea.required = true;
            } else {
                resolutionField.style.display = 'none';
                resolutionTextarea.required = false;
            }
        }
        
        // Initialize on page load
        toggleResolution();
    </script>
</body>
</html>
