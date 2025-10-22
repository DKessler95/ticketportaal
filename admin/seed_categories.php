<?php
/**
 * Seed Categories - Admin Tool
 * 
 * Clean and repopulate categories table
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

$executed = false;
$error = null;
$categories = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_seed'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        try {
            $db = Database::getInstance();
            
            // Disable foreign key checks temporarily
            $db->execute("SET FOREIGN_KEY_CHECKS = 0");
            
            // Truncate table (faster and resets auto increment)
            $db->execute("TRUNCATE TABLE categories");
            
            // Re-enable foreign key checks
            $db->execute("SET FOREIGN_KEY_CHECKS = 1");
            
            // Insert standard ICT categories
            $categoriesToInsert = [
                ['Hardware', 'Hardware problemen zoals computers, printers, monitors, etc.', 'medium', 24],
                ['Software', 'Software installaties, licenties en applicatie problemen', 'medium', 24],
                ['Netwerk', 'Netwerk connectiviteit, WiFi, VPN en internet problemen', 'high', 8],
                ['Account', 'Account aanvragen, wachtwoord resets en toegangsbeheer', 'low', 48],
                ['Email', 'Email problemen, configuratie en Outlook issues', 'medium', 24],
                ['Telefonie', 'Telefoon systemen, mobiele telefoons en voicemail', 'medium', 24],
                ['Beveiliging', 'Security incidents, virus meldingen en verdachte activiteiten', 'urgent', 4],
                ['Backup & Recovery', 'Data backup, restore requests en file recovery', 'high', 8]
            ];
            
            foreach ($categoriesToInsert as $cat) {
                $db->execute(
                    "INSERT INTO categories (name, description, default_priority, sla_hours, is_active) VALUES (?, ?, ?, ?, 1)",
                    $cat
                );
            }
            
            $executed = true;
            
            // Get results
            $categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_id");
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get current categories
if (!$executed) {
    try {
        $db = Database::getInstance();
        $categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_id");
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function getPriorityColor($priority) {
    $colors = [
        'low' => 'secondary',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    return $colors[$priority] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Categories - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-database"></i> Seed Categories</h1>
                    <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Categories
                    </a>
                </div>

                <?php if ($executed): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Success!</h5>
                        <p>Categories have been cleaned and reseeded successfully.</p>
                        <hr>
                        <div class="d-flex gap-2">
                            <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-success">Go to Categories</a>
                            <a href="<?php echo SITE_URL; ?>/admin/category_fields.php" class="btn btn-primary">Manage Category Fields</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Error</h5>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!$executed): ?>
                    <div class="alert alert-warning">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Warning</h5>
                        <p><strong>This action will:</strong></p>
                        <ul>
                            <li>Delete ALL existing categories</li>
                            <li>Delete ALL category fields</li>
                            <li>Reset the categories table</li>
                            <li>Insert 8 standard ICT categories</li>
                        </ul>
                        <p class="mb-0"><strong>This action cannot be undone!</strong></p>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Current Categories (<?php echo count($categories); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($categories)): ?>
                                <p class="text-muted">No categories found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Priority</th>
                                                <th>SLA</th>
                                                <th>Active</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $cat): ?>
                                                <tr>
                                                    <td><?php echo $cat['category_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getPriorityColor($cat['default_priority']); ?>">
                                                            <?php echo $cat['default_priority']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $cat['sla_hours']; ?>h</td>
                                                    <td>
                                                        <?php if ($cat['is_active']): ?>
                                                            <span class="badge bg-success">Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Confirm Seed Action</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                    <label class="form-check-label" for="confirmCheck">
                                        I understand that this will delete all existing categories and cannot be undone
                                    </label>
                                </div>
                                
                                <button type="submit" name="confirm_seed" class="btn btn-danger">
                                    <i class="bi bi-database"></i> Execute Seed
                                </button>
                                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">New Categories (<?php echo count($categories); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Priority</th>
                                            <th>SLA</th>
                                            <th>Active</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td><?php echo $cat['category_id']; ?></td>
                                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                                <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getPriorityColor($cat['default_priority']); ?>">
                                                        <?php echo $cat['default_priority']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $cat['sla_hours']; ?>h</td>
                                                <td>
                                                    <span class="badge bg-success">Yes</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
