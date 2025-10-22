<?php
/**
 * Fix Category Fields Table
 * 
 * Add missing columns and update field types
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

$executed = false;
$error = null;
$messages = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_fix'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        try {
            $db = Database::getInstance();
            
            // Step 1: Check if conditional_logic column exists
            $columns = $db->fetchAll("SHOW COLUMNS FROM category_fields LIKE 'conditional_logic'");
            
            if (empty($columns)) {
                $db->execute("ALTER TABLE category_fields ADD COLUMN conditional_logic TEXT NULL COMMENT 'JSON for conditional field logic' AFTER help_text");
                $messages[] = "✓ Added 'conditional_logic' column";
            } else {
                $messages[] = "✓ 'conditional_logic' column already exists";
            }
            
            // Step 2: Get current ENUM values for field_type
            $columnInfo = $db->fetchOne("SHOW COLUMNS FROM category_fields WHERE Field = 'field_type'");
            $currentType = $columnInfo['Type'] ?? '';
            
            // Check if 'date' is already in the ENUM
            if (strpos($currentType, "'date'") === false) {
                // Update field_type ENUM to include all types including 'date'
                $db->execute("ALTER TABLE category_fields MODIFY COLUMN field_type ENUM('text', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'email', 'tel') NOT NULL DEFAULT 'text'");
                $messages[] = "✓ Updated field_type ENUM to include 'date' type";
            } else {
                $messages[] = "✓ field_type ENUM already includes 'date' type";
            }
            
            // Step 3: Clear all example fields (they have wrong category IDs)
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM category_fields")['count'] ?? 0;
            
            if ($count > 0) {
                $db->execute("DELETE FROM category_fields");
                $messages[] = "✓ Cleared {$count} existing field(s) (they had wrong category IDs)";
            } else {
                $messages[] = "✓ No existing fields to clear";
            }
            
            $executed = true;
            
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Category Fields Table - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <h1 class="mb-4"><i class="bi bi-wrench"></i> Fix Category Fields Table</h1>

                <?php if ($executed): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Success!</h5>
                        <p>Table has been fixed successfully:</p>
                        <ul>
                            <?php foreach ($messages as $msg): ?>
                                <li><?php echo htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <a href="<?php echo SITE_URL; ?>/admin/category_fields.php" class="btn btn-success">Go to Category Fields</a>
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
                            <li>Add missing 'conditional_logic' column if not exists</li>
                            <li>Update field_type ENUM to include 'date' type</li>
                            <li>Delete all existing category fields (they have wrong category IDs after seed)</li>
                        </ul>
                        <p class="mb-0"><strong>You will need to recreate your fields after this.</strong></p>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Confirm Fix Action</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                    <label class="form-check-label" for="confirmCheck">
                                        I understand that this will modify the table structure and delete existing fields
                                    </label>
                                </div>
                                
                                <button type="submit" name="confirm_fix" class="btn btn-warning">
                                    <i class="bi bi-wrench"></i> Execute Fix
                                </button>
                                <a href="<?php echo SITE_URL; ?>/admin/category_fields.php" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
