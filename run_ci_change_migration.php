<?php
/**
 * CI & Change Management Migration Runner
 * 
 * Web-based migration script to set up database tables for CI and Change Management
 * 
 * SECURITY: This file should be deleted after successful migration
 */

// Prevent direct access in production
$migration_password = 'migrate2025'; // Change this password!

session_start();

// Simple authentication
if (!isset($_POST['password']) && !isset($_SESSION['migration_authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CI & Change Management Migration</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Database Migration Authentication</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Migration Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Enter the migration password to proceed</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Authenticate</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Check password
if (isset($_POST['password'])) {
    if ($_POST['password'] === $migration_password) {
        $_SESSION['migration_authenticated'] = true;
    } else {
        die('<div class="alert alert-danger">Invalid password</div>');
    }
}

// Include database configuration
require_once 'config/database.php';

// Initialize results array
$results = [
    'success' => [],
    'errors' => [],
    'warnings' => []
];

// Function to execute SQL statements
function executeSQLFile($pdo, $filepath) {
    global $results;
    
    if (!file_exists($filepath)) {
        $results['errors'][] = "Migration file not found: $filepath";
        return false;
    }
    
    $sql = file_get_contents($filepath);
    
    if ($sql === false) {
        $results['errors'][] = "Failed to read migration file: $filepath";
        return false;
    }
    
    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Split into statements, handling DELIMITER changes for triggers
    $statements = [];
    $inDelimiterBlock = false;
    $currentStatement = '';
    $delimiter = ';';
    
    // Split by lines to handle DELIMITER commands
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        // Check for DELIMITER command
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            $delimiter = trim($matches[1]);
            continue;
        }
        
        // Add line to current statement
        $currentStatement .= $line . "\n";
        
        // Check if statement is complete
        if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
            // Remove the delimiter from the end
            $currentStatement = substr($currentStatement, 0, -strlen($delimiter) - 1);
            $currentStatement = trim($currentStatement);
            
            if (!empty($currentStatement)) {
                $statements[] = $currentStatement;
            }
            
            $currentStatement = '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    $executed = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Log specific operations
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                $results['success'][] = "Created table: {$matches[1]}";
            } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                $results['success'][] = "Created view: {$matches[1]}";
            } elseif (preg_match('/CREATE.*?TRIGGER.*?`?(\w+)`?/i', $statement, $matches)) {
                $results['success'][] = "Created trigger: {$matches[1]}";
            } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                $results['success'][] = "Inserted data into: {$matches[1]}";
            } elseif (preg_match('/DROP TRIGGER.*?`?(\w+)`?/i', $statement, $matches)) {
                $results['success'][] = "Dropped trigger: {$matches[1]}";
            }
        } catch (PDOException $e) {
            $failed++;
            // Check if it's a "table already exists" error or "trigger doesn't exist"
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results['warnings'][] = $e->getMessage();
            } elseif (strpos($e->getMessage(), "doesn't exist") !== false && strpos($statement, 'DROP') !== false) {
                // Ignore "doesn't exist" errors for DROP statements
                $results['warnings'][] = $e->getMessage();
                $failed--; // Don't count as failure
            } else {
                $results['errors'][] = "SQL Error: " . $e->getMessage();
            }
        }
    }
    
    $results['success'][] = "Executed $executed statements successfully" . ($failed > 0 ? " ($failed failed)" : "");
    
    return $failed === 0;
}

// Function to insert sample data
function insertSampleData($pdo) {
    global $results;
    
    try {
        // Get an admin user for created_by fields
        $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            $results['warnings'][] = "No admin user found. Sample data will not be inserted.";
            return false;
        }
        
        $adminId = $admin['user_id'];
        
        // Sample Configuration Items
        $sampleCIs = [
            [
                'type' => 'Hardware',
                'category' => 'Laptop',
                'brand' => 'Dell',
                'model' => 'Latitude 5520',
                'name' => 'Dell Latitude 5520 - Laptop',
                'serial_number' => 'DL5520-2024-001',
                'asset_tag' => 'KK-LAP-001',
                'status' => 'In gebruik',
                'department' => 'ICT',
                'location' => 'Kantoor Amsterdam',
                'purchase_date' => '2024-01-15',
                'purchase_price' => 899.00,
                'supplier' => 'Dell Direct',
                'warranty_expiry' => '2027-01-15',
                'notes' => 'Standaard laptop voor ICT medewerkers'
            ],
            [
                'type' => 'Hardware',
                'category' => 'Desktop',
                'brand' => 'HP',
                'model' => 'EliteDesk 800 G6',
                'name' => 'HP EliteDesk 800 - Desktop',
                'serial_number' => 'HP800-2024-002',
                'asset_tag' => 'KK-DES-001',
                'status' => 'In gebruik',
                'department' => 'Finance',
                'location' => 'Kantoor Amsterdam',
                'purchase_date' => '2024-02-20',
                'purchase_price' => 1299.00,
                'supplier' => 'HP Store',
                'warranty_expiry' => '2027-02-20',
                'notes' => 'Desktop voor finance afdeling'
            ],
            [
                'type' => 'Software',
                'category' => 'Productiviteit',
                'brand' => 'Microsoft',
                'model' => 'Office 365 Business',
                'name' => 'Microsoft Office 365 Business',
                'serial_number' => 'O365-BUS-2024-001',
                'status' => 'In gebruik',
                'department' => 'Algemeen',
                'purchase_date' => '2024-01-01',
                'purchase_price' => 1200.00,
                'supplier' => 'Microsoft',
                'warranty_expiry' => '2025-01-01',
                'notes' => 'Jaarlijks abonnement voor 10 gebruikers'
            ],
            [
                'type' => 'Hardware',
                'category' => 'Monitor',
                'brand' => 'Dell',
                'model' => 'P2422H',
                'name' => 'Dell P2422H 24" Monitor',
                'serial_number' => 'DLP2422-2024-003',
                'asset_tag' => 'KK-MON-001',
                'status' => 'In voorraad',
                'location' => 'Magazijn',
                'purchase_date' => '2024-03-10',
                'purchase_price' => 199.00,
                'supplier' => 'Dell Direct',
                'warranty_expiry' => '2027-03-10',
                'notes' => 'Reserve monitor'
            ],
            [
                'type' => 'Licentie',
                'category' => 'Ontwikkeling',
                'brand' => 'JetBrains',
                'model' => 'IntelliJ IDEA',
                'name' => 'JetBrains IntelliJ IDEA License',
                'serial_number' => 'JB-IDEA-2024-001',
                'status' => 'In gebruik',
                'department' => 'ICT',
                'purchase_date' => '2024-01-05',
                'purchase_price' => 149.00,
                'supplier' => 'JetBrains',
                'warranty_expiry' => '2025-01-05',
                'notes' => 'Jaarlijkse licentie voor ontwikkelaar'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO configuration_items 
            (ci_number, type, category, brand, model, name, serial_number, asset_tag, status, 
             owner_id, department, location, purchase_date, purchase_price, supplier, 
             warranty_expiry, notes, created_by)
            VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleCIs as $ci) {
            $stmt->execute([
                $ci['type'],
                $ci['category'],
                $ci['brand'],
                $ci['model'],
                $ci['name'],
                $ci['serial_number'],
                $ci['asset_tag'] ?? null,
                $ci['status'],
                $ci['department'] ?? null,
                $ci['location'] ?? null,
                $ci['purchase_date'] ?? null,
                $ci['purchase_price'] ?? null,
                $ci['supplier'] ?? null,
                $ci['warranty_expiry'] ?? null,
                $ci['notes'] ?? null,
                $adminId
            ]);
        }
        
        $results['success'][] = "Inserted " . count($sampleCIs) . " sample configuration items";
        
        // Sample Changes
        $sampleChanges = [
            [
                'title' => 'Upgrade Office 365 to E3 Plan',
                'type' => 'Software',
                'priority' => 'Normaal',
                'impact' => 'Middel',
                'status' => 'Nieuw',
                'description' => 'Upgrade current Office 365 Business to E3 plan for advanced security features',
                'reason' => 'Need advanced threat protection and compliance features',
                'expected_result' => 'All users will have access to E3 features including ATP and DLP',
                'affected_systems' => 'Office 365 tenant',
                'affected_users' => 10,
                'downtime_expected' => false,
                'risk_assessment' => 'Low risk - upgrade is seamless'
            ],
            [
                'title' => 'Replace aging network switch',
                'type' => 'Hardware',
                'priority' => 'Hoog',
                'impact' => 'Hoog',
                'status' => 'In beoordeling',
                'description' => 'Replace 10-year-old network switch with modern gigabit switch',
                'reason' => 'Current switch is end-of-life and causing intermittent connectivity issues',
                'expected_result' => 'Improved network stability and performance',
                'affected_systems' => 'Main office network',
                'affected_users' => 25,
                'downtime_expected' => true,
                'downtime_duration' => 120,
                'risk_assessment' => 'Medium risk - requires careful planning and backup configuration',
                'implementation_plan' => '1. Order new switch\n2. Configure new switch offline\n3. Schedule maintenance window\n4. Swap switches\n5. Verify connectivity',
                'rollback_plan' => 'Keep old switch available for immediate rollback if issues occur'
            ],
            [
                'title' => 'Implement backup solution for file server',
                'type' => 'Infrastructuur',
                'priority' => 'Urgent',
                'impact' => 'Hoog',
                'status' => 'Goedgekeurd',
                'description' => 'Implement automated backup solution for critical file server',
                'reason' => 'Currently no backup solution in place - high risk of data loss',
                'expected_result' => 'Daily automated backups with 30-day retention',
                'affected_systems' => 'File server FS-01',
                'affected_users' => 30,
                'downtime_expected' => false,
                'risk_assessment' => 'Low risk - backup runs during off-hours',
                'planned_start_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'planned_end_date' => date('Y-m-d H:i:s', strtotime('+14 days'))
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO changes 
            (change_number, title, requested_by, type, priority, impact, status, description, 
             reason, expected_result, affected_systems, affected_users, downtime_expected, 
             downtime_duration, risk_assessment, implementation_plan, rollback_plan, 
             planned_start_date, planned_end_date)
            VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleChanges as $change) {
            $stmt->execute([
                $change['title'],
                $adminId,
                $change['type'],
                $change['priority'],
                $change['impact'],
                $change['status'],
                $change['description'],
                $change['reason'],
                $change['expected_result'],
                $change['affected_systems'] ?? null,
                $change['affected_users'] ?? null,
                $change['downtime_expected'] ? 1 : 0,
                $change['downtime_duration'] ?? null,
                $change['risk_assessment'] ?? null,
                $change['implementation_plan'] ?? null,
                $change['rollback_plan'] ?? null,
                $change['planned_start_date'] ?? null,
                $change['planned_end_date'] ?? null
            ]);
        }
        
        $results['success'][] = "Inserted " . count($sampleChanges) . " sample changes";
        
        return true;
        
    } catch (PDOException $e) {
        $results['errors'][] = "Error inserting sample data: " . $e->getMessage();
        return false;
    }
}

// Function to verify tables
function verifyTables($pdo) {
    global $results;
    
    $requiredTables = [
        'configuration_items',
        'ci_history',
        'ci_attachments',
        'changes',
        'change_history',
        'change_attachments',
        'ticket_ci_relations',
        'ticket_change_relations',
        'change_ci_relations',
        'sequences'
    ];
    
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                $missingTables[] = $table;
            } else {
                $results['success'][] = "Verified table exists: $table";
            }
        } catch (PDOException $e) {
            $results['errors'][] = "Error checking table $table: " . $e->getMessage();
        }
    }
    
    if (!empty($missingTables)) {
        $results['errors'][] = "Missing tables: " . implode(', ', $missingTables);
        return false;
    }
    
    return true;
}

// Main execution
try {
    // Create PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
    
    $results['success'][] = "Database connection established";
    
    // Execute migration
    if (isset($_POST['run_migration'])) {
        $results['success'][] = "Starting migration...";
        
        // Run migration SQL
        executeSQLFile($pdo, 'database/ci_change_migration.sql');
        
        // Verify tables
        verifyTables($pdo);
        
        // Insert sample data if requested
        if (isset($_POST['insert_sample_data'])) {
            insertSampleData($pdo);
        }
        
        $results['success'][] = "Migration completed!";
    }
    
} catch (PDOException $e) {
    $results['errors'][] = "Database connection failed: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI & Change Management Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .result-box {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            background-color: #f8f9fa;
        }
        .result-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
        }
        .result-success {
            background-color: #d1e7dd;
            border-left: 4px solid #0f5132;
        }
        .result-error {
            background-color: #f8d7da;
            border-left: 4px solid #842029;
        }
        .result-warning {
            background-color: #fff3cd;
            border-left: 4px solid #997404;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-database-gear"></i> CI & Change Management Migration</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!isset($_POST['run_migration'])): ?>
                            <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle"></i> Migration Information</h5>
                                <p>This script will create the following database objects:</p>
                                <ul>
                                    <li><strong>10 Tables:</strong> configuration_items, ci_history, ci_attachments, changes, change_history, change_attachments, and relationship tables</li>
                                    <li><strong>2 Triggers:</strong> Auto-numbering for CIs and Changes</li>
                                    <li><strong>5 Views:</strong> Reporting views for CIs and Changes</li>
                                    <li><strong>Sample Data:</strong> Optional test data for development</li>
                                </ul>
                                <p class="mb-0"><strong>Note:</strong> Existing tables will not be dropped. The migration is safe to run multiple times.</p>
                            </div>
                            
                            <form method="POST">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="insert_sample_data" name="insert_sample_data" checked>
                                    <label class="form-check-label" for="insert_sample_data">
                                        Insert sample test data (5 CIs and 3 Changes)
                                    </label>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                        <i class="bi bi-play-circle"></i> Run Migration
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="result-box">
                                <?php if (!empty($results['success'])): ?>
                                    <h5 class="text-success"><i class="bi bi-check-circle"></i> Success Messages</h5>
                                    <?php foreach ($results['success'] as $message): ?>
                                        <div class="result-item result-success">
                                            <i class="bi bi-check"></i> <?php echo htmlspecialchars($message); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($results['warnings'])): ?>
                                    <h5 class="text-warning mt-3"><i class="bi bi-exclamation-triangle"></i> Warnings</h5>
                                    <?php foreach ($results['warnings'] as $message): ?>
                                        <div class="result-item result-warning">
                                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($results['errors'])): ?>
                                    <h5 class="text-danger mt-3"><i class="bi bi-x-circle"></i> Errors</h5>
                                    <?php foreach ($results['errors'] as $message): ?>
                                        <div class="result-item result-error">
                                            <i class="bi bi-x"></i> <?php echo htmlspecialchars($message); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-3">
                                <?php if (empty($results['errors'])): ?>
                                    <div class="alert alert-success">
                                        <h5><i class="bi bi-check-circle"></i> Migration Completed Successfully!</h5>
                                        <p>The CI & Change Management module has been installed.</p>
                                        <p class="mb-0"><strong>Next Steps:</strong></p>
                                        <ol>
                                            <li>Delete this migration file (run_ci_change_migration.php) for security</li>
                                            <li>Navigate to the admin panel to start using CI Management</li>
                                            <li>Configure user permissions as needed</li>
                                        </ol>
                                    </div>
                                    <a href="admin/index.php" class="btn btn-success">
                                        <i class="bi bi-house-door"></i> Go to Admin Dashboard
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <h5><i class="bi bi-x-circle"></i> Migration Failed</h5>
                                        <p>Please review the errors above and try again.</p>
                                    </div>
                                    <a href="run_ci_change_migration.php" class="btn btn-primary">
                                        <i class="bi bi-arrow-clockwise"></i> Try Again
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Database Information</h6>
                        <ul class="list-unstyled mb-0">
                            <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                            <li><strong>Database:</strong> <?php echo DB_NAME; ?></li>
                            <li><strong>User:</strong> <?php echo DB_USER; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
