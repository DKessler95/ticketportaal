<?php
/**
 * Admin Category Management Page
 * 
 * Allows administrators to create, edit, and manage ticket categories
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize Category class
$categoryClass = new Category();

// Handle form submissions
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errorMessage = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $defaultPriority = $_POST['default_priority'] ?? 'medium';
                $slaHours = (int)($_POST['sla_hours'] ?? 24);
                
                if (empty($name)) {
                    $errorMessage = 'Category name is required.';
                } elseif ($slaHours < 1) {
                    $errorMessage = 'SLA hours must be at least 1.';
                } else {
                    $categoryId = $categoryClass->createCategory($name, $description, $defaultPriority, $slaHours);
                    if ($categoryId) {
                        $successMessage = 'Category created successfully!';
                    } else {
                        $errorMessage = 'Failed to create category: ' . $categoryClass->getError();
                    }
                }
                break;
                
            case 'edit':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $defaultPriority = $_POST['default_priority'] ?? 'medium';
                $slaHours = (int)($_POST['sla_hours'] ?? 24);
                
                if (empty($name)) {
                    $errorMessage = 'Category name is required.';
                } elseif ($slaHours < 1) {
                    $errorMessage = 'SLA hours must be at least 1.';
                } else {
                    $updateData = [
                        'name' => $name,
                        'description' => $description,
                        'default_priority' => $defaultPriority,
                        'sla_hours' => $slaHours
                    ];
                    
                    if ($categoryClass->updateCategory($categoryId, $updateData)) {
                        $successMessage = 'Category updated successfully!';
                    } else {
                        $errorMessage = 'Failed to update category: ' . $categoryClass->getError();
                    }
                }
                break;
                
            case 'deactivate':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                if ($categoryClass->deactivateCategory($categoryId)) {
                    $successMessage = 'Category deactivated successfully!';
                } else {
                    $errorMessage = 'Failed to deactivate category: ' . $categoryClass->getError();
                }
                break;
                
            case 'activate':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                if ($categoryClass->activateCategory($categoryId)) {
                    $successMessage = 'Category activated successfully!';
                } else {
                    $errorMessage = 'Failed to activate category: ' . $categoryClass->getError();
                }
                break;
        }
    }
}

// Get all categories
$categories = $categoryClass->getCategories(false); // Get all including inactive

$pageTitle = 'Category Management';
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
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/index.php">
                <i class="bi bi-ticket-perforated"></i> <?php echo escapeOutput(SITE_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo SITE_URL; ?>/admin/categories.php">
                            <i class="bi bi-tags"></i> Categories
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="bi bi-tags"></i> Category Management</h1>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo escapeOutput($successMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo escapeOutput($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Create Category Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="default_priority" class="form-label">Default Priority <span class="text-danger">*</span></label>
                                    <select class="form-select" id="default_priority" name="default_priority" required>
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="sla_hours" class="form-label">SLA Hours <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="sla_hours" name="sla_hours" value="24" min="1" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Create Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Categories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No categories found. Create your first category above.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Default Priority</th>
                                            <th>SLA Hours</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($category['name']); ?></strong></td>
                                                <td><?php echo escapeOutput($category['description'] ?? ''); ?></td>
                                                <td><?php echo getPriorityBadge($category['default_priority']); ?></td>
                                                <td><?php echo escapeOutput($category['sla_hours']); ?> hours</td>
                                                <td>
                                                    <?php if ($category['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal<?php echo $category['category_id']; ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    
                                                    <?php if ($category['is_active']): ?>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to deactivate this category?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                                <i class="bi bi-x-circle"></i> Deactivate
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-check-circle"></i> Activate
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $category['category_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Category</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" name="name" 
                                                                           value="<?php echo escapeOutput($category['name']); ?>" 
                                                                           required maxlength="100">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea class="form-control" name="description" rows="2"><?php echo escapeOutput($category['description'] ?? ''); ?></textarea>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Default Priority <span class="text-danger">*</span></label>
                                                                    <select class="form-select" name="default_priority" required>
                                                                        <option value="low" <?php echo $category['default_priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                                                        <option value="medium" <?php echo $category['default_priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                                        <option value="high" <?php echo $category['default_priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                                                        <option value="urgent" <?php echo $category['default_priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">SLA Hours <span class="text-danger">*</span></label>
                                                                    <input type="number" class="form-control" name="sla_hours" 
                                                                           value="<?php echo escapeOutput($category['sla_hours']); ?>" 
                                                                           min="1" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="bi bi-save"></i> Save Changes
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
