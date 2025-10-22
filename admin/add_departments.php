<?php
/**
 * Add Departments Migration
 * 
 * Creates departments table and adds department_id to users
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if departments table already exists
            $tableExists = $db->fetchOne("SHOW TABLES LIKE 'departments'");
            
            if ($tableExists) {
                $messages[] = "Departments table already exists";
            } else {
                // Create departments table
                $db->execute("CREATE TABLE IF NOT EXISTS `departments` (
                  `department_id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(100) NOT NULL,
                  `description` TEXT NULL,
                  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`department_id`),
                  UNIQUE KEY `unique_name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $messages[] = "Created departments table";
                
                // Insert departments
                $departments = [
                    ['Financiën', 'Financiële administratie en boekhouding'],
                    ['Service', 'Klantenservice en support'],
                    ['Directie', 'Management en directie'],
                    ['Magazijn', 'Magazijn en voorraad beheer'],
                    ['Transport', 'Transport en logistiek'],
                    ['Planning', 'Planning en coördinatie'],
                    ['ICT', 'ICT afdeling en technische support'],
                    ['Externe partij', 'Externe medewerkers en partners']
                ];
                
                foreach ($departments as $dept) {
                    $db->execute(
                        "INSERT INTO departments (name, description) VALUES (?, ?)",
                        $dept
                    );
                }
                
                $messages[] = "Inserted 8 departments";
            }
            
            // Check if department_id column exists in users table
            $columnExists = $db->fetchOne("SHOW COLUMNS FROM users LIKE 'department_id'");
            
            if ($columnExists) {
                $messages[] = "department_id column already exists in users table";
            } else {
                // Add department_id column to users
                $db->execute("ALTER TABLE users 
                    ADD COLUMN department_id INT(11) NULL AFTER role,
                    ADD KEY idx_department_id (department_id),
                    ADD CONSTRAINT fk_user_department FOREIGN KEY (department_id) REFERENCES departments (department_id) ON DELETE SET NULL");
                
                $messages[] = "Added department_id column to users table";
            }
            
            $executed = true;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Departments - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <h1 class="mb-4"><i class="bi bi-building"></i> Add User Departments</h1>

                <?php if ($executed): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Success!</h5>
                        <p>Departments have been added successfully:</p>
                        <ul>
                            <?php foreach ($messages as $msg): ?>
                                <li><?php echo htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-success">Go to Users</a>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Error</h5>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!$executed): ?>
                    <div class="alert alert-info">
                        <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Information</h5>
                        <p><strong>This migration will:</strong></p>
                        <ul>
                            <li>Create departments table with 8 fixed departments</li>
                            <li>Add department_id column to users table</li>
                            <li>Create foreign key relationship</li>
                        </ul>
                        <p class="mb-0"><strong>Departments to be created:</strong></p>
                        <ol>
                            <li>Financiën</li>
                            <li>Service</li>
                            <li>Directie</li>
                            <li>Magazijn</li>
                            <li>Transport</li>
                            <li>Planning</li>
                            <li>ICT</li>
                            <li>Externe partij</li>
                        </ol>
                    </div>

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-play-fill"></i> Execute Migration</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                    <label class="form-check-label" for="confirmCheck">
                                        I understand that this will modify the database structure
                                    </label>
                                </div>
                                
                                <button type="submit" name="confirm_migration" class="btn btn-primary">
                                    <i class="bi bi-play-fill"></i> Execute Migration
                                </button>
                                <a href="<?php echo SITE_URL; ?>/admin/index.php" class="btn btn-secondary">
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
