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
            
            if (empty(trim($comment))) {
                $errors[] = 'Comment cannot be empty';
            } else {
                $commentId = $ticketClass->addComment($ticketId, $userId, $comment, false);
                
                if ($commentId) {
                    $success = 'Comment added successfully';
                    // Refresh comments
                    $comments = $ticketClass->getComments($ticketId, false);
                    // TODO: Send notification email (will be implemented in task 9)
                } else {
                    $errors[] = $ticketClass->getError() ?: 'Failed to add comment';
                }
            }
        } elseif ($_POST['action'] === 'submit_rating') {
            $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Please select a valid rating (1-5 stars)';
            } else {
                // Update ticket with satisfaction rating and close it
                $sql = "UPDATE tickets SET satisfaction_rating = ?, status = 'closed' WHERE ticket_id = ?";
                $result = $db->execute($sql, [$rating, $ticketId]);
                
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
    <style>
        .comment-box {
            border-left: 3px solid #0066cc;
            background-color: #f8f9fa;
        }
        .comment-internal {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .star-rating {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
        }
        .star-rating .bi-star-fill {
            color: #ffc107;
        }
        .star-rating .bi-star:hover,
        .star-rating .bi-star-fill:hover {
            color: #ffb300;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/user/dashboard.php">
                <i class="bi bi-ticket-perforated"></i> <?php echo escapeOutput(SITE_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/user/dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/user/my_tickets.php">
                            <i class="bi bi-list-ul"></i> My Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/user/create_ticket.php">
                            <i class="bi bi-plus-circle"></i> Create Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/knowledge_base.php">
                            <i class="bi bi-book"></i> Knowledge Base
                        </a>
                    </li>
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

    <div class="container mt-4 mb-5">
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
                                    <p class="mb-0 text-break"><?php echo nl2br(escapeOutput($comment['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Add Comment Form -->
                        <?php if ($ticket['status'] !== 'closed'): ?>
                            <hr>
                            <h6>Add a Comment</h6>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="add_comment">
                                
                                <div class="mb-3">
                                    <textarea class="form-control" 
                                              name="comment" 
                                              rows="4" 
                                              placeholder="Type your comment here..."
                                              required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Add Comment
                                </button>
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
                                
                                <div class="star-rating mb-3" id="starRating">
                                    <i class="bi bi-star" data-rating="1"></i>
                                    <i class="bi bi-star" data-rating="2"></i>
                                    <i class="bi bi-star" data-rating="3"></i>
                                    <i class="bi bi-star" data-rating="4"></i>
                                    <i class="bi bi-star" data-rating="5"></i>
                                </div>
                                
                                <p class="text-muted mb-3">
                                    <small>1 = Very Dissatisfied, 5 = Very Satisfied</small>
                                </p>
                                
                                <button type="submit" class="btn btn-success" id="submitRating" disabled>
                                    <i class="bi bi-check-circle"></i> Submit Rating
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($ticket['satisfaction_rating']): ?>
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-star-fill"></i> Your Rating</h5>
                        </div>
                        <div class="card-body">
                            <p>Thank you for rating this ticket!</p>
                            <div class="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i <= $ticket['satisfaction_rating'] ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
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

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>
