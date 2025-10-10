<?php
/**
 * Create Ticket Page
 * 
 * Form for users to create new support tickets
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and check authentication
initSession();
requireRole('user');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize classes
$ticketClass = new Ticket();
$categoryClass = new Category();

// Get active categories
$categories = $categoryClass->getCategories(true); // true = active only

// Initialize variables
$errors = [];
$success = false;
$ticketNumber = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = $_POST['description'] ?? ''; // Don't sanitize yet, we need original for storage
        $categoryId = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);
        $priority = $_POST['priority'] ?? 'medium';
        
        // Validation
        if (empty($title)) {
            $errors[] = 'Title is required';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Title is too long (maximum 255 characters)';
        }
        
        if (empty($description)) {
            $errors[] = 'Description is required';
        }
        
        if (empty($categoryId) || $categoryId <= 0) {
            $errors[] = 'Please select a category';
        }
        
        // Validate priority
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities)) {
            $priority = 'medium';
        }
        
        // If no errors, create ticket
        if (empty($errors)) {
            $ticketId = $ticketClass->createTicket(
                $userId,
                $title,
                $description,
                $categoryId,
                $priority,
                'web'
            );
            
            if ($ticketId) {
                // Handle file upload if present
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $fileValidation = validateFileUpload($_FILES['attachment']);
                    
                    if ($fileValidation['success']) {
                        $file = $_FILES['attachment'];
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $newFilename = generateRandomFilename($extension);
                        $uploadDir = UPLOAD_PATH . 'tickets/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $uploadPath = $uploadDir . $newFilename;
                        
                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            $ticketClass->addAttachment(
                                $ticketId,
                                $file['name'],
                                'tickets/' . $newFilename,
                                $file['size']
                            );
                        }
                    } else {
                        // File upload failed, but ticket was created
                        $errors[] = 'Ticket created, but file upload failed: ' . $fileValidation['error'];
                    }
                }
                
                // Get ticket details for success message
                $ticket = $ticketClass->getTicketById($ticketId);
                $ticketNumber = $ticket['ticket_number'];
                $success = true;
                
                // TODO: Send confirmation email (will be implemented in task 9)
            } else {
                $errors[] = $ticketClass->getError() ?: 'Failed to create ticket. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create Ticket';
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
                        <a class="nav-link active" href="<?php echo SITE_URL; ?>/user/create_ticket.php">
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
            <div class="col-lg-8 offset-lg-2">
                <h1 class="mb-4"><i class="bi bi-plus-circle"></i> Create New Ticket</h1>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Ticket Created Successfully!</h5>
                        <p>Your ticket has been created with number: <strong><?php echo escapeOutput($ticketNumber); ?></strong></p>
                        <p class="mb-0">You will receive a confirmation email shortly. You can track your ticket's progress from your dashboard.</p>
                        <hr>
                        <div class="d-flex gap-2">
                            <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn btn-success">Go to Dashboard</a>
                            <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="btn btn-outline-success">Create Another Ticket</a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Please correct the following errors:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escapeOutput($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data" id="createTicketForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control <?php echo isset($errors) && in_array('Title is required', $errors) ? 'is-invalid' : ''; ?>" 
                                           id="title" 
                                           name="title" 
                                           maxlength="255"
                                           value="<?php echo isset($_POST['title']) ? escapeOutput($_POST['title']) : ''; ?>"
                                           required>
                                    <div class="form-text">Provide a brief, descriptive title for your issue</div>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select <?php echo isset($errors) && in_array('Please select a category', $errors) ? 'is-invalid' : ''; ?>" 
                                            id="category_id" 
                                            name="category_id" 
                                            required>
                                        <option value="">-- Select a category --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" 
                                                    data-priority="<?php echo $category['default_priority']; ?>"
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo escapeOutput($category['name']); ?>
                                                <?php if ($category['description']): ?>
                                                    - <?php echo escapeOutput($category['description']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select the category that best describes your issue</div>
                                </div>

                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                                        <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                    </select>
                                    <div class="form-text">Priority will be automatically set based on category, but you can adjust it if needed</div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control <?php echo isset($errors) && in_array('Description is required', $errors) ? 'is-invalid' : ''; ?>" 
                                              id="description" 
                                              name="description" 
                                              rows="6" 
                                              required><?php echo isset($_POST['description']) ? escapeOutput($_POST['description']) : ''; ?></textarea>
                                    <div class="form-text">Provide detailed information about your issue. Include any error messages, steps to reproduce, and what you've already tried.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="attachment" class="form-label">Attachment (Optional)</label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="attachment" 
                                           name="attachment"
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt,.zip">
                                    <div class="form-text">
                                        Maximum file size: <?php echo (MAX_FILE_SIZE / 1048576); ?>MB. 
                                        Allowed types: <?php echo implode(', ', ALLOWED_EXTENSIONS); ?>
                                    </div>
                                    <div id="filePreview" class="mt-2"></div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Create Ticket
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
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
        // Auto-set priority based on category selection
        document.getElementById('category_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const defaultPriority = selectedOption.getAttribute('data-priority');
            
            if (defaultPriority) {
                document.getElementById('priority').value = defaultPriority;
            }
        });

        // File upload preview and validation
        document.getElementById('attachment').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('filePreview');
            const maxSize = <?php echo MAX_FILE_SIZE; ?>;
            
            if (file) {
                // Check file size
                if (file.size > maxSize) {
                    preview.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> File size exceeds maximum allowed (' + (maxSize / 1048576) + 'MB)</div>';
                    this.value = '';
                    return;
                }
                
                // Show file info
                const fileSize = (file.size / 1024).toFixed(2);
                preview.innerHTML = '<div class="alert alert-info"><i class="bi bi-file-earmark"></i> <strong>' + file.name + '</strong> (' + fileSize + ' KB)</div>';
            } else {
                preview.innerHTML = '';
            }
        });

        // Form validation
        document.getElementById('createTicketForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const category = document.getElementById('category_id').value;
            const description = document.getElementById('description').value.trim();
            
            if (!title || !category || !description) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });
    </script>
</body>
</html>
