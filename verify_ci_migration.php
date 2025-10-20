<?php
/**
 * CI & Change Management Migration Verification
 * 
 * Web-based verification script to check if the system is ready for migration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI & Change Management Migration Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .test-pass { color: #198754; }
        .test-warn { color: #ffc107; }
        .test-fail { color: #dc3545; }
        .test-item { padding: 10px; margin: 5px 0; border-left: 4px solid #dee2e6; }
        .test-item.pass { border-left-color: #198754; background-color: #d1e7dd; }
        .test-item.warn { border-left-color: #ffc107; background-color: #fff3cd; }
        .test-item.fail { border-left-color: #dc3545; background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-check-circle"></i> Migration Verification</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $allPassed = true;
                        
                        // Test 1: Database Connection
                        echo '<h5>Test 1: Database Connection</h5>';
                        try {
                            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
                            echo '<div class="test-item pass">';
                            echo '<i class="bi bi-check-circle test-pass"></i> ';
                            echo 'Database connection successful<br>';
                            echo '<small>Host: ' . DB_HOST . ' | Database: ' . DB_NAME . '</small>';
                            echo '</div>';
                        } catch (PDOException $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                            $allPassed = false;
                        }
                        
                        // Test 2: Migration File
                        echo '<h5 class="mt-4">Test 2: Migration File</h5>';
                        $migrationFile = 'database/ci_change_migration.sql';
                        if (file_exists($migrationFile)) {
                            $fileSize = filesize($migrationFile);
                            $sql = file_get_contents($migrationFile);
                            $tableCount = preg_match_all('/CREATE TABLE IF NOT EXISTS/i', $sql);
                            $triggerCount = preg_match_all('/CREATE TRIGGER/i', $sql);
                            $viewCount = preg_match_all('/CREATE OR REPLACE VIEW/i', $sql);
                            
                            echo '<div class="test-item pass">';
                            echo '<i class="bi bi-check-circle test-pass"></i> ';
                            echo 'Migration file found<br>';
                            echo '<small>File: ' . $migrationFile . ' (' . number_format($fileSize) . ' bytes)<br>';
                            echo 'Tables: ' . $tableCount . ' | Triggers: ' . $triggerCount . ' | Views: ' . $viewCount . '</small>';
                            echo '</div>';
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo 'Migration file not found: ' . $migrationFile;
                            echo '</div>';
                            $allPassed = false;
                        }
                        
                        // Test 3: Required Tables
                        echo '<h5 class="mt-4">Test 3: Existing Schema</h5>';
                        $requiredTables = ['users', 'tickets', 'categories'];
                        $missingTables = [];
                        
                        foreach ($requiredTables as $table) {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                            if ($stmt->rowCount() === 0) {
                                $missingTables[] = $table;
                            }
                        }
                        
                        if (empty($missingTables)) {
                            echo '<div class="test-item pass">';
                            echo '<i class="bi bi-check-circle test-pass"></i> ';
                            echo 'All required tables found<br>';
                            echo '<small>Tables: ' . implode(', ', $requiredTables) . '</small>';
                            echo '</div>';
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo 'Missing required tables: ' . implode(', ', $missingTables) . '<br>';
                            echo '<small>Please install the base ticketing system first</small>';
                            echo '</div>';
                            $allPassed = false;
                        }
                        
                        // Test 4: CI/Change Tables
                        echo '<h5 class="mt-4">Test 4: CI & Change Tables</h5>';
                        $ciChangeTables = [
                            'configuration_items', 'ci_history', 'ci_attachments',
                            'changes', 'change_history', 'change_attachments',
                            'ticket_ci_relations', 'ticket_change_relations', 'change_ci_relations',
                            'sequences'
                        ];
                        
                        $existingTables = [];
                        foreach ($ciChangeTables as $table) {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                            if ($stmt->rowCount() > 0) {
                                $existingTables[] = $table;
                            }
                        }
                        
                        if (empty($existingTables)) {
                            echo '<div class="test-item pass">';
                            echo '<i class="bi bi-check-circle test-pass"></i> ';
                            echo 'No CI/Change tables exist (clean installation)';
                            echo '</div>';
                        } else {
                            echo '<div class="test-item warn">';
                            echo '<i class="bi bi-exclamation-triangle test-warn"></i> ';
                            echo 'Some CI/Change tables already exist:<br>';
                            echo '<small>' . implode(', ', $existingTables) . '<br>';
                            echo 'Migration will skip existing tables (safe to run)</small>';
                            echo '</div>';
                        }
                        
                        // Test 5: Upload Directories
                        echo '<h5 class="mt-4">Test 5: Upload Directories</h5>';
                        $uploadDirs = [
                            'uploads/ci_attachments',
                            'uploads/change_attachments'
                        ];
                        
                        $allDirsOk = true;
                        foreach ($uploadDirs as $dir) {
                            if (!is_dir($dir)) {
                                echo '<div class="test-item warn">';
                                echo '<i class="bi bi-exclamation-triangle test-warn"></i> ';
                                echo 'Directory missing: ' . $dir . '<br>';
                                echo '<small>Will be created automatically</small>';
                                echo '</div>';
                                $allDirsOk = false;
                            } elseif (!is_writable($dir)) {
                                echo '<div class="test-item warn">';
                                echo '<i class="bi bi-exclamation-triangle test-warn"></i> ';
                                echo 'Directory not writable: ' . $dir;
                                echo '</div>';
                                $allDirsOk = false;
                            }
                        }
                        
                        if ($allDirsOk) {
                            echo '<div class="test-item pass">';
                            echo '<i class="bi bi-check-circle test-pass"></i> ';
                            echo 'All upload directories exist and are writable';
                            echo '</div>';
                        }
                        
                        // Test 6: MySQL Version
                        echo '<h5 class="mt-4">Test 6: MySQL Version</h5>';
                        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                        $versionNumber = floatval($version);
                        
                        if ($versionNumber >= 5.7 || strpos($version, 'MariaDB') !== false) {
                            echo '<div class="test-item pass">';
                            echo '<i class="bi bi-check-circle test-pass"></i> ';
                            echo 'MySQL version compatible<br>';
                            echo '<small>Version: ' . htmlspecialchars($version) . '</small>';
                            echo '</div>';
                        } else {
                            echo '<div class="test-item warn">';
                            echo '<i class="bi bi-exclamation-triangle test-warn"></i> ';
                            echo 'MySQL version may not be fully compatible<br>';
                            echo '<small>Version: ' . htmlspecialchars($version) . ' (5.7+ recommended)</small>';
                            echo '</div>';
                        }
                        
                        // Test 7: Admin Users
                        echo '<h5 class="mt-4">Test 7: Admin Users</h5>';
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                            $adminCount = $stmt->fetchColumn();
                            
                            if ($adminCount > 0) {
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo 'Found ' . $adminCount . ' admin user(s)<br>';
                                echo '<small>Sample data can be inserted</small>';
                                echo '</div>';
                            } else {
                                echo '<div class="test-item warn">';
                                echo '<i class="bi bi-exclamation-triangle test-warn"></i> ';
                                echo 'No admin users found<br>';
                                echo '<small>Sample data insertion will be skipped</small>';
                                echo '</div>';
                            }
                        } catch (PDOException $e) {
                            echo '<div class="test-item warn">';
                            echo '<i class="bi bi-exclamation-triangle test-warn"></i> ';
                            echo 'Could not check admin users';
                            echo '</div>';
                        }
                        
                        // Summary
                        echo '<hr>';
                        if ($allPassed) {
                            echo '<div class="alert alert-success">';
                            echo '<h5><i class="bi bi-check-circle"></i> All Tests Passed!</h5>';
                            echo '<p>The system is ready for migration.</p>';
                            echo '<a href="run_ci_change_migration.php" class="btn btn-primary">';
                            echo '<i class="bi bi-play-circle"></i> Run Migration Now';
                            echo '</a>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<h5><i class="bi bi-exclamation-triangle"></i> Some Issues Found</h5>';
                            echo '<p>Please resolve the issues above before running the migration.</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Quick Links</h6>
                        <ul class="list-unstyled mb-0">
                            <li><a href="run_ci_change_migration.php"><i class="bi bi-play-circle"></i> Run Migration</a></li>
                            <li><a href="database/CI_CHANGE_MIGRATION_README.md" target="_blank"><i class="bi bi-book"></i> Migration Documentation</a></li>
                            <li><a href="admin/index.php"><i class="bi bi-house-door"></i> Admin Dashboard</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
