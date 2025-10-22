<?php
/**
 * Ticket Detail Page (User View)
 * 
 * Display full ticket information with comments, attachments, and satisfaction rating
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/TemplateParser.php';

// Initialize session and check authentication
initSession();
requireRole('user');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Get ticket ID from URL
$ticketId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$ticketId) {
    $_SESSION['error'] = 'Invalid ticket ID';
    redirectTo(SITE_URL . '/user/my_tickets.php');
}

// Initialize classes
$ticketClass = new Ticket();
$db = Database::getInstance();

// Get ticket details
$ticket = $ticketClass->getTicketById($ticketId);

if (!$ticket) {
    $_SESSION['error'] = 'Ticket not found';
    redirectTo(SITE_URL . '/user/my_tickets.php');
}

// Verify ticket belongs to logged-in user
if ($ticket['user_id'] != $userId) {
    $_SESSION['error'] = 'You do not have permission to view this ticket';
    redirectTo(SITE_URL . '/user/my_tickets.php');
}

// Get comments (only public comments for users)
$comments = $ticketClass->getComments($ticketId, false);

// Get templates for comments (only comment type, not resolution)
$templates = $db->fetchAll("SELECT * FROM ticket_templates WHERE is_active = 1 AND template_type = 'comment' ORDER BY name");

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

// Get attachments
$attachments = $ticketClass->getAttachments($ticketId);

// Initialize variables
$errors = [];
$success = '';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        if ($_POST['action'] === 'add_comment') {
            $comment = $_POST['comment'] ?? '';
            
            // Strip tags to get plain text for validation
            $plainComment = strip_tags($comment);
            
            if (empty(trim($plainComment))) {
                $errors[] = 'Comment cannot be empty';
            } else {
                $commentId = $ticketClass->addComment($ticketId, $userId, $comment, false);
                
                if ($commentId) {
                    $success = 'Comment added successfully';
                    // Refresh comments
                    $comments = $ticketClass->getComments($ticketId, false);
                    // Clear POST data to prevent resubmission
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $ticketClass->getError() ?: 'Failed to add comment';
                }
            }
        } elseif ($_POST['action'] === 'mark_resolved') {
            // User marks ticket as resolved
            $result = $ticketClass->updateStatus($ticketId, 'resolved', 'Marked as resolved by user');
            
            if ($result) {
                $success = 'Ticket marked as resolved';
                // Refresh ticket data
                $ticket = $ticketClass->getTicketById($ticketId);
            } else {
                $errors[] = $ticketClass->getError() ?: 'Failed to update ticket status';
            }
        } elseif ($_POST['action'] === 'submit_rating') {
            $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);
            $reviewComment = $_POST['review_comment'] ?? '';
            
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Please select a valid rating (1-5 stars)';
            } else {
                // Update ticket with satisfaction rating, comment and close it
                $sql = "UPDATE tickets SET satisfaction_rating = ?, satisfaction_comment = ?, status = 'closed' WHERE ticket_id = ?";
                $result = $db->execute($sql, [$rating, $reviewComment, $ticketId]);
                
                if ($result) {
                    $success = 'Thank you for your feedback! The ticket has been closed.';
                    // Refresh ticket data
                    $ticket = $ticketClass->getTicketById($ticketId);
                } else {
                    $errors[] = 'Failed to submit rating';
                }
            }
        }
    }
}

$pageTitle = 'Ticket Details';
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
    <script src="https://cdn.tiny.cloud/1/f5xc5i53b0di57yjmcf5954fyhbtmb9k28r3pu0nn19ol86c/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .comment-box {
            border-left: 3px solid #FF6B35;
            background-color: #f8f9fa;
        }
        .comment-internal {
            border-left-color: #FFB627;
            background-color: #fff3cd;
        }
        .star-rating {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
        }
        .star-rating .bi-star-fill {
            color: #FFB627;
        }
        .star-rating .bi-star:hover,
        .star-rating .bi-star-fill:hover {
            color: #FF6B35;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4 mb-5">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/user/my_tickets.php">My Tickets</a></li>
                        <li class="breadcrumb-item active"><?php echo escapeOutput($ticket['ticket_number']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Error</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escapeOutput($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo escapeOutput($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Ticket Information -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-ticket-detailed"></i> 
                            <?php echo escapeOutput($ticket['ticket_number']); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo escapeOutput($ticket['title']); ?></h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Status:</strong> <?php echo getStatusBadge($ticket['status']); ?></p>
                                <p class="mb-1"><strong>Priority:</strong> <?php echo getPriorityBadge($ticket['priority']); ?></p>
                                <p class="mb-1"><strong>Category:</strong> <?php echo escapeOutput($ticket['category_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Created:</strong> <?php echo formatDate($ticket['created_at']); ?></p>
                                <p class="mb-1"><strong>Last Updated:</strong> <?php echo formatDate($ticket['updated_at']); ?></p>
                                <?php if ($ticket['resolved_at']): ?>
                                    <p class="mb-1"><strong>Resolved:</strong> <?php echo formatDate($ticket['resolved_at']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <h6><strong>Description:</strong></h6>
                        <p class="text-break"><?php echo nl2br(escapeOutput($ticket['description'])); ?></p>

                        <?php if ($ticket['resolution']): ?>
                            <hr>
                            <div class="alert alert-success">
                                <h6><strong><i class="bi bi-check-circle"></i> Resolution:</strong></h6>
                                <p class="mb-0"><?php echo nl2br(escapeOutput($ticket['resolution'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dynamic Field Values -->
                <?php
                $fieldValues = $ticketClass->getFieldValues($ticketId);
                if (!empty($fieldValues)):
                ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Aanvullende Informatie</h5>
                        </div>
                        <div class="card-body">
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
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-paperclip"></i> Attachments</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($attachments as $attachment): ?>
                                    <a href="<?php echo SITE_URL; ?>/uploads/<?php echo escapeOutput($attachment['filepath']); ?>" 
                                       class="list-group-item list-group-item-action" 
                                       download="<?php echo escapeOutput($attachment['filename']); ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-file-earmark"></i>
                                                <strong><?php echo escapeOutput($attachment['filename']); ?></strong>
                                            </div>
                                            <div>
                                                <span class="badge bg-secondary"><?php echo number_format($attachment['filesize'] / 1024, 2); ?> KB</span>
                                                <i class="bi bi-download ms-2"></i>
                                            </div>
                                        </div>
                                        <small class="text-muted">Uploaded: <?php echo formatDate($attachment['uploaded_at']); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Comments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Comments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted">No comments yet.</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-box p-3 mb-3 rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong>
                                                <?php echo escapeOutput($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                            </strong>
                                            <?php if ($comment['role'] === 'agent' || $comment['role'] === 'admin'): ?>
                                                <span class="badge bg-info">Support Team</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                    </div>
                                    <div class="mb-0 text-break"><?php echo $comment['comment']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Add Comment Form -->
                        <?php if ($ticket['status'] !== 'closed'): ?>
                            <hr>
                            <h6>Add a Comment</h6>
                            <form method="POST" action="" id="commentForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="add_comment">
                                
                                <?php if (!empty($templates)): ?>
                                <div class="mb-2">
                                    <label class="form-label">Sjabloon gebruiken (optioneel)</label>
                                    <select class="form-select" id="templateSelect" onchange="loadTemplate(this.value)">
                                        <option value="">-- Selecteer sjabloon --</option>
                                        <?php foreach ($templates as $tpl): ?>
                                            <option value="<?php echo $tpl['template_id']; ?>" 
                                                    data-content="<?php echo htmlspecialchars($tpl['content'], ENT_QUOTES); ?>">
                                                <?php echo escapeOutput($tpl['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <textarea class="form-control" 
                                              id="comment"
                                              name="comment" 
                                              rows="8" 
                                              placeholder="Type your comment here..."></textarea>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Add Comment
                                    </button>
                                    
                                    <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                                        <button type="button" class="btn btn-success" onclick="markAsResolved()">
                                            <i class="bi bi-check-circle"></i> Mark as Resolved
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="bi bi-info-circle"></i> This ticket is closed. No further comments can be added.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Satisfaction Rating -->
                <?php if ($ticket['status'] === 'resolved' && empty($ticket['satisfaction_rating'])): ?>
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-star"></i> Rate Your Experience</h5>
                        </div>
                        <div class="card-body">
                            <p>Your ticket has been resolved. Please rate your experience with our support team:</p>
                            
                            <form method="POST" action="" id="ratingForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="submit_rating">
                                <input type="hidden" name="rating" id="ratingValue" value="0">
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Rating:</strong></label>
                                    <div class="star-rating" id="starRating">
                                        <i class="bi bi-star" data-rating="1"></i>
                                        <i class="bi bi-star" data-rating="2"></i>
                                        <i class="bi bi-star" data-rating="3"></i>
                                        <i class="bi bi-star" data-rating="4"></i>
                                        <i class="bi bi-star" data-rating="5"></i>
                                    </div>
                                    <small class="text-muted">1 = Very Dissatisfied, 5 = Very Satisfied</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="review_comment" class="form-label"><strong>Your Feedback (Optional):</strong></label>
                                    <textarea class="form-control" 
                                              id="review_comment" 
                                              name="review_comment" 
                                              rows="4" 
                                              placeholder="Tell us about your experience..."></textarea>
                                    <small class="text-muted">Share your thoughts about the support you received</small>
                                </div>
                                
                                <button type="submit" class="btn btn-success" id="submitRating" disabled>
                                    <i class="bi bi-check-circle"></i> Submit Review
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($ticket['satisfaction_rating']): ?>
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-star-fill"></i> Your Review</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Rating:</strong></p>
                            <div class="star-rating mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i <= $ticket['satisfaction_rating'] ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if (!empty($ticket['satisfaction_comment'])): ?>
                                <p><strong>Your Feedback:</strong></p>
                                <div class="alert alert-light">
                                    <?php echo nl2br(escapeOutput($ticket['satisfaction_comment'])); ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-muted mb-0"><small>Thank you for your feedback!</small></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Ticket Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Assigned Agent:</strong><br>
                            <?php if ($ticket['agent_first_name']): ?>
                                <?php echo escapeOutput($ticket['agent_first_name'] . ' ' . $ticket['agent_last_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">Awaiting assignment</span>
                            <?php endif; ?>
                        </p>
                        
                        <p class="mb-2">
                            <strong>Source:</strong><br>
                            <span class="badge bg-secondary"><?php echo ucfirst($ticket['source']); ?></span>
                        </p>
                        
                        <p class="mb-0">
                            <strong>SLA Hours:</strong><br>
                            <?php echo $ticket['sla_hours']; ?> hours
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-question-circle"></i> Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <p>If you have questions about this ticket, you can:</p>
                        <ul>
                            <li>Add a comment above</li>
                            <li>Browse our <a href="<?php echo SITE_URL; ?>/knowledge_base.php">Knowledge Base</a></li>
                            <li>Create a <a href="<?php echo SITE_URL; ?>/user/create_ticket.php">new ticket</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TinyMCE for Comments -->
    <script src="https://cdn.tiny.cloud/1/f5xc5i53b0di57yjmcf5954fyhbtmb9k28r3pu0nn19ol86c/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Initialize TinyMCE for comment field
        <?php if ($ticket['status'] !== 'closed'): ?>
        let commentEditor;
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
            resize: true,
            init_instance_callback: function(editor) {
                commentEditor = editor;
            }
        });
        
        // Handle form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('commentForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Get TinyMCE content
                    if (commentEditor) {
                        const content = commentEditor.getContent({format: 'text'}).trim();
                        
                        if (!content) {
                            alert('Comment cannot be empty');
                            return false;
                        }
                        
                        // Save TinyMCE content to textarea
                        commentEditor.save();
                    }
                    
                    // Submit the form
                    form.submit();
                });
            }
        });
        <?php endif; ?>
        
        // Initialize TinyMCE for comment field
        <?php if ($ticket['status'] !== 'closed'): ?>
        tinymce.init({
            selector: '#comment',
            height: 250,
            menubar: false,
            plugins: ['lists', 'link'],
            toolbar: 'undo redo | bold italic underline | bullist numlist | removeformat',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            statusbar: false,
            resize: false,
            setup: function(editor) {
                editor.on('init', function() {
                    document.getElementById('commentForm').addEventListener('submit', function(e) {
                        tinymce.triggerSave();
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
        <?php endif; ?>
        
        // Mark ticket as resolved
        function markAsResolved() {
            if (confirm('Are you sure you want to mark this ticket as resolved? This indicates that your issue has been fixed.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = 'csrf_token';
                csrfToken.value = '<?php echo generateCSRFToken(); ?>';
                form.appendChild(csrfToken);
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'mark_resolved';
                form.appendChild(action);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Star rating functionality
        const stars = document.querySelectorAll('#starRating i');
        const ratingValue = document.getElementById('ratingValue');
        const submitButton = document.getElementById('submitRating');
        
        if (stars.length > 0) {
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingValue.value = rating;
                    
                    // Update star display
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= rating) {
                            s.classList.remove('bi-star');
                            s.classList.add('bi-star-fill');
                        } else {
                            s.classList.remove('bi-star-fill');
                            s.classList.add('bi-star');
                        }
                    });
                    
                    // Enable submit button
                    submitButton.disabled = false;
                });
                
                // Hover effect
                star.addEventListener('mouseenter', function() {
                    const rating = this.getAttribute('data-rating');
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if (starRating <= rating) {
                            s.style.color = '#ffb300';
                        }
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    stars.forEach(s => {
                        s.style.color = '';
                    });
                });
            });
        }
        
        // Load template into comment textarea
        function loadTemplate(templateId) {
            if (!templateId) return;
            
            const select = document.getElementById('templateSelect');
            const option = select.options[select.selectedIndex];
            const content = option.getAttribute('data-content');
            
            if (content) {
                // Strip HTML tags from template content
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = content;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';
                
                // Check if TinyMCE is initialized
                if (tinymce.get('comment')) {
                    tinymce.get('comment').setContent(textContent);
                } else {
                    document.getElementById('comment').value = textContent;
                }
            }
        }
    </script>
</body>
</html>
