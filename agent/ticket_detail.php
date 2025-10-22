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
require_once __DIR__ . '/../classes/TemplateParser.php';

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

// Get ticket details first (needed for form processing)
$ticket = $ticketClass->getTicketById($ticketId);

if (!$ticket) {
    $_SESSION['error'] = 'Ticket not found';
    redirectTo(SITE_URL . '/agent/dashboard.php');
}

// Get templates for comment and resolution
$templates = $db->fetchAll("SELECT * FROM ticket_templates WHERE is_active = 1 AND template_type IN ('comment', 'resolution') ORDER BY name");

// Get user data for template parsing
$ticketUser = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$ticket['user_id']]);

// Get agent data for template parsing
$ticketAgent = null;
if (!empty($ticket['assigned_agent_id'])) {
    $ticketAgent = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$ticket['assigned_agent_id']]);
}

// Parse templates with ticket data
foreach ($templates as &$template) {
    $template['content'] = TemplateParser::parse($template['content'], $ticket, $ticketUser, $ticketAgent);
}
unset($template); // Break reference

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        switch ($_POST['action']) {
            case 'delete_comment':
                // Only admins can delete comments
                if ($userRole === 'admin') {
                    $commentId = filter_var($_POST['comment_id'] ?? 0, FILTER_VALIDATE_INT);
                    
                    if (!$commentId) {
                        $error = 'Invalid comment ID';
                    } else {
                        $result = $ticketClass->deleteComment($commentId, $ticketId);
                        
                        if ($result) {
                            $success = 'Comment deleted successfully';
                            // Refresh comments
                            $comments = $ticketClass->getComments($ticketId, true);
                        } else {
                            $error = $ticketClass->getError() ?: 'Failed to delete comment';
                        }
                    }
                } else {
                    $error = 'You do not have permission to delete comments';
                }
                break;
                
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
                
            case 'update_ticket':
                // Combined update for both assignment and status
                $assignedAgentId = filter_var($_POST['assigned_agent_id'] ?? 0, FILTER_VALIDATE_INT);
                $status = $_POST['status'] ?? '';
                $resolution = trim($_POST['resolution'] ?? '');
                
                $updated = false;
                $messages = [];
                
                // Update assignment if changed
                if ($assignedAgentId > 0 && $assignedAgentId != $ticket['assigned_agent_id']) {
                    if ($ticketClass->assignTicket($ticketId, $assignedAgentId)) {
                        $messages[] = 'Assignment updated';
                        $updated = true;
                    }
                }
                
                // Update status if changed
                if (!empty($status) && $status != $ticket['status']) {
                    if ($ticketClass->updateStatus($ticketId, $status, $resolution)) {
                        $messages[] = 'Status updated';
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    $success = implode(' and ', $messages) . ' successfully';
                    $ticket = $ticketClass->getTicketById($ticketId);
                } elseif (empty($messages)) {
                    $error = 'No changes detected';
                } else {
                    $error = 'Failed to update ticket';
                }
                break;
                
            case 'add_comment':
                $comment = trim($_POST['comment'] ?? '');
                $isInternal = isset($_POST['is_internal']) && $_POST['is_internal'] == '1';
                $closeTicket = isset($_POST['close_ticket']) && $_POST['close_ticket'] == '1';
                
                if (empty($comment)) {
                    $error = 'Comment cannot be empty';
                } else {
                    $result = $ticketClass->addComment($ticketId, $userId, $comment, $isInternal);
                    
                    // If close_ticket is checked, also close the ticket with comment as resolution
                    if ($result && $closeTicket) {
                        $ticketClass->updateStatus($ticketId, 'closed', $comment);
                        $success = 'Comment added and ticket closed successfully';
                    } else if ($result) {
                        $success = 'Comment added successfully';
                    }
                    
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

// Refresh ticket details after any updates
$ticket = $ticketClass->getTicketById($ticketId);

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
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="pt-3">
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

                        <!-- Dynamic Field Values -->
                        <?php
                        $fieldValues = $ticketClass->getFieldValues($ticketId);
                        if (!empty($fieldValues)):
                        ?>
                        <div class="mb-3">
                            <strong>Aanvullende Informatie:</strong>
                            <div class="border rounded p-3 mt-2 bg-light">
                                <dl class="row mb-0">
                                    <?php foreach ($fieldValues as $field): ?>
                                        <dt class="col-sm-4"><?php echo escapeOutput($field['field_label']); ?></dt>
                                        <dd class="col-sm-8">
                                            <?php
                                            // Format value based on field type
                                            $value = $field['field_value'];
                                            
                                            switch ($field['field_type']) {
                                                case 'checkbox':
                                                    if (is_array($value)) {
                                                        echo escapeOutput(implode(', ', $value));
                                                    } else {
                                                        echo $value ? 'Ja' : 'Nee';
                                                    }
                                                    break;
                                                    
                                                case 'date':
                                                    $date = DateTime::createFromFormat('Y-m-d', $value);
                                                    echo $date ? $date->format('d-m-Y') : escapeOutput($value);
                                                    break;
                                                    
                                                case 'email':
                                                    echo '<a href="mailto:' . escapeOutput($value) . '">' . escapeOutput($value) . '</a>';
                                                    break;
                                                    
                                                case 'tel':
                                                    echo '<a href="tel:' . escapeOutput($value) . '">' . escapeOutput($value) . '</a>';
                                                    break;
                                                    
                                                default:
                                                    echo escapeOutput($value);
                                            }
                                            ?>
                                        </dd>
                                    <?php endforeach; ?>
                                </dl>
                            </div>
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
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                                <?php if ($userRole === 'admin'): ?>
                                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                        <input type="hidden" name="action" value="delete_comment">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete comment">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-0"><?php echo $comment['comment']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Add Comment Form -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Add Comment</h6>
                                <form method="POST" action="" id="commentForm">
                                    <input type="hidden" name="action" value="add_comment">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <?php if (!empty($templates)): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-file-text"></i> Sjabloon gebruiken (optioneel)</label>
                                        <select class="form-select" id="templateSelect" onchange="loadTemplate(this.value, 'comment')">
                                            <option value="">-- Selecteer een sjabloon --</option>
                                            <?php foreach ($templates as $tpl): ?>
                                                <?php if ($tpl['template_type'] === 'comment'): ?>
                                                <option value="<?php echo $tpl['template_id']; ?>" 
                                                        data-content="<?php echo htmlspecialchars($tpl['content'], ENT_QUOTES); ?>">
                                                    <?php echo escapeOutput($tpl['name']); ?>
                                                </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Selecteer een sjabloon om de inhoud automatisch in te vullen</small>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-info alert-sm">
                                        <small><i class="bi bi-info-circle"></i> Geen sjablonen beschikbaar. Maak eerst sjablonen aan in Admin → Sjablonen</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <textarea name="comment" id="comment" class="form-control" rows="8" 
                                                  placeholder="Enter your comment..."></textarea>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="is_internal" id="is_internal" class="form-check-input" value="1">
                                        <label for="is_internal" class="form-check-label">
                                            <i class="bi bi-lock"></i> Internal comment (not visible to user)
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="close_ticket" id="close_ticket" class="form-check-input" value="1">
                                        <label for="close_ticket" class="form-check-label text-danger">
                                            <i class="bi bi-x-circle"></i> <strong>Close ticket with this comment as resolution</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">Deze comment wordt gebruikt als afsluitreden en verstuurd via email</small>
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
                <!-- Update Ticket (Assignment & Status) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Update Ticket</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="updateTicketForm">
                            <input type="hidden" name="action" value="update_ticket">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="assigned_agent_id" class="form-label">
                                    <i class="bi bi-person-plus"></i> Assign to Agent
                                </label>
                                <select name="assigned_agent_id" id="assigned_agent_id" class="form-select">
                                    <option value="">Select Agent...</option>
                                    <?php foreach ($agents as $agent): ?>
                                        <option value="<?php echo $agent['user_id']; ?>" 
                                                <?php echo ($ticket['assigned_agent_id'] == $agent['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo escapeOutput($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($ticket['assigned_agent_id']): ?>
                                    <small class="text-muted">
                                        Current: <?php echo escapeOutput($ticket['agent_first_name'] . ' ' . $ticket['agent_last_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="bi bi-arrow-repeat"></i> Status
                                </label>
                                <select name="status" id="status" class="form-select" onchange="toggleResolution()">
                                    <option value="">Keep current status</option>
                                    <option value="open" <?php echo ($ticket['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo ($ticket['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="pending" <?php echo ($ticket['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="resolved" <?php echo ($ticket['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                </select>
                                <div class="mt-2">
                                    <small class="text-muted d-block">Current status:</small>
                                    <div class="mt-1"><?php echo getStatusBadge($ticket['status']); ?></div>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="resolutionField" style="display: none;">
                                <?php if (!empty($templates)): ?>
                                <div class="mb-3">
                                    <label class="form-label"><i class="bi bi-file-text"></i> Sjabloon gebruiken (optioneel)</label>
                                    <select class="form-select" id="resolutionTemplateSelect" onchange="loadTemplate(this.value, 'resolution')">
                                        <option value="">-- Selecteer een sjabloon --</option>
                                        <?php foreach ($templates as $tpl): ?>
                                            <?php if ($tpl['template_type'] === 'resolution'): ?>
                                            <option value="<?php echo $tpl['template_id']; ?>" 
                                                    data-content="<?php echo htmlspecialchars($tpl['content'], ENT_QUOTES); ?>">
                                                <?php echo escapeOutput($tpl['name']); ?>
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Selecteer een sjabloon om de inhoud automatisch in te vullen</small>
                                </div>
                                <?php endif; ?>
                                
                                <label for="resolution" class="form-label">Resolution <span class="text-danger">*</span></label>
                                <textarea name="resolution" id="resolution" class="form-control" rows="4" 
                                          placeholder="Describe how the issue was resolved..."></textarea>
                                <small class="text-muted">Required when marking as resolved or closed</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Update Ticket
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- TinyMCE for Comments -->
    <script src="https://cdn.tiny.cloud/1/f5xc5i53b0di57yjmcf5954fyhbtmb9k28r3pu0nn19ol86c/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Initialize TinyMCE for comment field
        tinymce.init({
            selector: '#comment',
            height: 300,
            menubar: false,
            plugins: ['lists', 'link', 'code', 'table'],
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat code',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            statusbar: false,
            resize: false,
            setup: function(editor) {
                editor.on('init', function() {
                    // Form submit handler to save TinyMCE content
                    document.getElementById('commentForm').addEventListener('submit', function(e) {
                        // Trigger TinyMCE to save content to textarea
                        tinymce.triggerSave();
                        
                        // Check if comment is empty
                        var content = tinymce.get('comment').getContent({format: 'text'}).trim();
                        if (content === '') {
                            e.preventDefault();
                            alert('Please enter a comment before submitting.');
                            return false;
                        }
                    });
                });
            }
        });
        
        // Initialize TinyMCE for resolution field
        tinymce.init({
            selector: '#resolution',
            height: 250,
            menubar: false,
            plugins: ['lists', 'link', 'code', 'table'],
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat code',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            statusbar: false,
            resize: false
        });
        
        function toggleResolution() {
            const statusSelect = document.getElementById('status');
            const resolutionField = document.getElementById('resolutionField');
            const resolutionTextarea = document.getElementById('resolution');
            const resolutionLabel = resolutionField.querySelector('label');
            
            if (statusSelect.value === 'resolved' || statusSelect.value === 'closed') {
                resolutionField.style.display = 'block';
                resolutionTextarea.required = true;
                
                // Update label based on status
                if (statusSelect.value === 'closed') {
                    resolutionLabel.innerHTML = 'Closure Reason <span class="text-danger">*</span>';
                    resolutionTextarea.placeholder = 'Describe why the ticket is being closed...';
                } else {
                    resolutionLabel.innerHTML = 'Resolution <span class="text-danger">*</span>';
                    resolutionTextarea.placeholder = 'Describe how the issue was resolved...';
                }
            } else {
                resolutionField.style.display = 'none';
                resolutionTextarea.required = false;
            }
        }
        
        // Initialize on page load
        toggleResolution();
        
        // Load template into textarea or TinyMCE
        function loadTemplate(templateId, targetField) {
            if (!templateId) return;
            
            const select = targetField === 'resolution' ? 
                document.getElementById('resolutionTemplateSelect') : 
                document.getElementById('templateSelect');
            
            const option = select.options[select.selectedIndex];
            const content = option.getAttribute('data-content');
            
            if (content) {
                // Check if TinyMCE is initialized for this field
                if (tinymce.get(targetField)) {
                    tinymce.get(targetField).setContent(content);
                } else {
                    // Fallback to textarea
                    const textarea = document.getElementById(targetField);
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = content;
                    textarea.value = tempDiv.textContent || tempDiv.innerText || '';
                }
            }
        }
    </script>
            </main>
        </div>
    </div>
</body>
</html>
