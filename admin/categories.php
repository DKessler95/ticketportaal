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
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-tags"></i> Categoriebeheer</h1>
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
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nieuwe Categorie Aanmaken</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Categorienaam <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="default_priority" class="form-label">Standaard Prioriteit <span class="text-danger">*</span></label>
                                    <select class="form-select" id="default_priority" name="default_priority" required>
                                        <option value="low">Laag</option>
                                        <option value="medium" selected>Gemiddeld</option>
                                        <option value="high">Hoog</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="sla_hours" class="form-label">SLA Uren <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="sla_hours" name="sla_hours" value="24" min="1" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Beschrijving</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Categorie Aanmaken
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
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Alle Categorieën</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen categorieën gevonden. Maak hierboven je eerste categorie aan.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Naam</th>
                                            <th>Beschrijving</th>
                                            <th>Standaard Prioriteit</th>
                                            <th>SLA Uren</th>
                                            <th>Status</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($category['name']); ?></strong></td>
                                                <td><?php echo escapeOutput($category['description'] ?? ''); ?></td>
                                                <td><?php echo getPriorityBadge($category['default_priority']); ?></td>
                                                <td><?php echo escapeOutput($category['sla_hours']); ?> uur</td>
                                                <td>
                                                    <?php if ($category['is_active']): ?>
                                                        <span class="badge bg-success">Actief</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactief</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editModal<?php echo $category['category_id']; ?>">
                                                        <i class="bi bi-pencil"></i> Bewerken
                                                    </button>
                                                    
                                                    <?php if ($category['is_active']): ?>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Weet je zeker dat je deze categorie wilt deactiveren?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                                <i class="bi bi-x-circle"></i> Deactiveren
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-check-circle"></i> Activeren
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
                                                            <h5 class="modal-title">Categorie Bewerken</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Categorienaam <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" name="name" 
                                                                           value="<?php echo escapeOutput($category['name']); ?>" 
                                                                           required maxlength="100">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Beschrijving</label>
                                                                    <textarea class="form-control" name="description" rows="2"><?php echo escapeOutput($category['description'] ?? ''); ?></textarea>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Standaard Prioriteit <span class="text-danger">*</span></label>
                                                                    <select class="form-select" name="default_priority" required>
                                                                        <option value="low" <?php echo $category['default_priority'] === 'low' ? 'selected' : ''; ?>>Laag</option>
                                                                        <option value="medium" <?php echo $category['default_priority'] === 'medium' ? 'selected' : ''; ?>>Gemiddeld</option>
                                                                        <option value="high" <?php echo $category['default_priority'] === 'high' ? 'selected' : ''; ?>>Hoog</option>
                                                                        <option value="urgent" <?php echo $category['default_priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">SLA Uren <span class="text-danger">*</span></label>
                                                                    <input type="number" class="form-control" name="sla_hours" 
                                                                           value="<?php echo escapeOutput($category['sla_hours']); ?>" 
                                                                           min="1" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="bi bi-save"></i> Wijzigingen Opslaan
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
