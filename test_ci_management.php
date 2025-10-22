<?php
/**
 * CI Management Test Script
 * Tests CRUD operations, history logging, and ticket relationships
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/config.php';
require_once 'classes/ConfigurationItem.php';
require_once 'includes/functions.php';

// Start session
initSession();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('<h1>Access Denied</h1><p>You must be logged in as an admin to run this test.</p><p><a href="login.php">Login</a></p>');
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI Management Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .test-pass { color: #198754; }
        .test-fail { color: #dc3545; }
        .test-item { padding: 15px; margin: 10px 0; border-left: 4px solid #dee2e6; }
        .test-item.pass { border-left-color: #198754; background-color: #d1e7dd; }
        .test-item.fail { border-left-color: #dc3545; background-color: #f8d7da; }
        .code-block { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-check-circle"></i> CI Management Test Suite</h3>
                        <small>Testing CRUD operations, history logging, and relationships</small>
                    </div>
                    <div class="card-body">
                        <?php
                        $ci = new ConfigurationItem();
                        $testResults = [];
                        $testCIId = null;
                        
                        // Test 1: Create CI
                        echo '<h5>Test 1: Create Configuration Item</h5>';
                        try {
                            $testData = [
                                'type' => 'Hardware',
                                'category' => 'Laptop',
                                'brand' => 'Dell',
                                'model' => 'Latitude 5520',
                                'name' => 'Test Laptop - ' . date('Y-m-d H:i:s'),
                                'serial_number' => 'TEST-' . uniqid(),
                                'status' => 'In gebruik',
                                'owner_id' => $_SESSION['user_id'],
                                'department' => 'IT',
                                'location' => 'Office A',
                                'purchase_date' => '2024-01-15',
                                'purchase_price' => 1299.99,
                                'supplier' => 'Dell Direct',
                                'warranty_expiry' => '2027-01-15',
                                'notes' => 'Test CI created by automated test',
                                'created_by' => $_SESSION['user_id']
                            ];
                            
                            $testCIId = $ci->createCI($testData);
                            
                            if ($testCIId) {
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo '<strong>PASS:</strong> CI created successfully<br>';
                                echo '<small>CI ID: ' . $testCIId . '</small>';
                                echo '</div>';
                                $testResults['create'] = true;
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Failed to create CI<br>';
                                echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                echo '</div>';
                                $testResults['create'] = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAIL:</strong> Exception during CI creation<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            $testResults['create'] = false;
                        }
                        
                        // Test 2: Read CI
                        echo '<h5 class="mt-4">Test 2: Read Configuration Item</h5>';
                        if ($testCIId) {
                            try {
                                $ciData = $ci->getCIById($testCIId);
                                
                                if ($ciData && $ciData['ci_id'] == $testCIId) {
                                    echo '<div class="test-item pass">';
                                    echo '<i class="bi bi-check-circle test-pass"></i> ';
                                    echo '<strong>PASS:</strong> CI retrieved successfully<br>';
                                    echo '<div class="code-block mt-2">';
                                    echo 'CI Number: ' . htmlspecialchars($ciData['ci_number']) . '<br>';
                                    echo 'Name: ' . htmlspecialchars($ciData['name']) . '<br>';
                                    echo 'Type: ' . htmlspecialchars($ciData['type']) . '<br>';
                                    echo 'Status: ' . htmlspecialchars($ciData['status']) . '<br>';
                                    echo 'Serial Number: ' . htmlspecialchars($ciData['serial_number']) . '<br>';
                                    echo '</div>';
                                    echo '</div>';
                                    $testResults['read'] = true;
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> Failed to retrieve CI<br>';
                                    echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                    echo '</div>';
                                    $testResults['read'] = false;
                                }
                            } catch (Exception $e) {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Exception during CI retrieval<br>';
                                echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                                echo '</div>';
                                $testResults['read'] = false;
                            }
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-exclamation-triangle test-fail"></i> ';
                            echo '<strong>SKIPPED:</strong> No CI ID from creation test';
                            echo '</div>';
                            $testResults['read'] = false;
                        }
                        
                        // Test 3: CI Number Generation
                        echo '<h5 class="mt-4">Test 3: CI Number Format</h5>';
                        if ($testCIId && isset($ciData)) {
                            $ciNumber = $ciData['ci_number'];
                            $year = date('Y');
                            $pattern = "/^CI-{$year}-\d{4}$/";
                            
                            if (preg_match($pattern, $ciNumber)) {
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo '<strong>PASS:</strong> CI number format is correct<br>';
                                echo '<small>Format: CI-YYYY-XXXX | Generated: ' . htmlspecialchars($ciNumber) . '</small>';
                                echo '</div>';
                                $testResults['ci_number'] = true;
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> CI number format is incorrect<br>';
                                echo '<small>Expected: CI-' . $year . '-XXXX | Got: ' . htmlspecialchars($ciNumber) . '</small>';
                                echo '</div>';
                                $testResults['ci_number'] = false;
                            }
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-exclamation-triangle test-fail"></i> ';
                            echo '<strong>SKIPPED:</strong> No CI data available';
                            echo '</div>';
                            $testResults['ci_number'] = false;
                        }
                        
                        // Test 4: Update CI
                        echo '<h5 class="mt-4">Test 4: Update Configuration Item</h5>';
                        if ($testCIId) {
                            try {
                                $updateData = [
                                    'status' => 'In voorraad',
                                    'location' => 'Warehouse B',
                                    'notes' => 'Updated by automated test - ' . date('Y-m-d H:i:s')
                                ];
                                
                                $updateResult = $ci->updateCI($testCIId, $updateData, $_SESSION['user_id']);
                                
                                if ($updateResult) {
                                    // Verify the update
                                    $updatedCI = $ci->getCIById($testCIId);
                                    
                                    if ($updatedCI['status'] === 'In voorraad' && $updatedCI['location'] === 'Warehouse B') {
                                        echo '<div class="test-item pass">';
                                        echo '<i class="bi bi-check-circle test-pass"></i> ';
                                        echo '<strong>PASS:</strong> CI updated successfully<br>';
                                        echo '<small>Status changed to: ' . htmlspecialchars($updatedCI['status']) . '<br>';
                                        echo 'Location changed to: ' . htmlspecialchars($updatedCI['location']) . '</small>';
                                        echo '</div>';
                                        $testResults['update'] = true;
                                    } else {
                                        echo '<div class="test-item fail">';
                                        echo '<i class="bi bi-x-circle test-fail"></i> ';
                                        echo '<strong>FAIL:</strong> CI update verification failed<br>';
                                        echo '<small>Values not updated correctly</small>';
                                        echo '</div>';
                                        $testResults['update'] = false;
                                    }
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> Failed to update CI<br>';
                                    echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                    echo '</div>';
                                    $testResults['update'] = false;
                                }
                            } catch (Exception $e) {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Exception during CI update<br>';
                                echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                                echo '</div>';
                                $testResults['update'] = false;
                            }
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-exclamation-triangle test-fail"></i> ';
                            echo '<strong>SKIPPED:</strong> No CI ID from creation test';
                            echo '</div>';
                            $testResults['update'] = false;
                        }
                        
                        // Test 5: CI History Logging
                        echo '<h5 class="mt-4">Test 5: CI History Logging</h5>';
                        if ($testCIId) {
                            try {
                                $history = $ci->getCIHistory($testCIId);
                                
                                if (is_array($history) && count($history) > 0) {
                                    // Check for creation and update entries
                                    $hasCreated = false;
                                    $hasStatusChange = false;
                                    
                                    foreach ($history as $entry) {
                                        if ($entry['action'] === 'created') $hasCreated = true;
                                        if ($entry['action'] === 'status_changed') $hasStatusChange = true;
                                    }
                                    
                                    if ($hasCreated && $hasStatusChange) {
                                        echo '<div class="test-item pass">';
                                        echo '<i class="bi bi-check-circle test-pass"></i> ';
                                        echo '<strong>PASS:</strong> CI history logged correctly<br>';
                                        echo '<small>Found ' . count($history) . ' history entries<br>';
                                        echo 'Includes: creation and status change</small>';
                                        echo '<div class="code-block mt-2">';
                                        foreach ($history as $entry) {
                                            echo htmlspecialchars($entry['action']) . ' by ' . 
                                                 htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']) . 
                                                 ' at ' . htmlspecialchars($entry['changed_at']) . '<br>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                        $testResults['history'] = true;
                                    } else {
                                        echo '<div class="test-item fail">';
                                        echo '<i class="bi bi-x-circle test-fail"></i> ';
                                        echo '<strong>FAIL:</strong> Missing expected history entries<br>';
                                        echo '<small>Created: ' . ($hasCreated ? 'Yes' : 'No') . ' | ';
                                        echo 'Status Change: ' . ($hasStatusChange ? 'Yes' : 'No') . '</small>';
                                        echo '</div>';
                                        $testResults['history'] = false;
                                    }
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> No history entries found<br>';
                                    echo '<small>Expected at least creation and update entries</small>';
                                    echo '</div>';
                                    $testResults['history'] = false;
                                }
                            } catch (Exception $e) {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Exception retrieving CI history<br>';
                                echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                                echo '</div>';
                                $testResults['history'] = false;
                            }
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-exclamation-triangle test-fail"></i> ';
                            echo '<strong>SKIPPED:</strong> No CI ID from creation test';
                            echo '</div>';
                            $testResults['history'] = false;
                        }
                        
                        // Test 6: Get All CIs
                        echo '<h5 class="mt-4">Test 6: Get All Configuration Items</h5>';
                        try {
                            $allCIs = $ci->getAllCIs();
                            
                            if (is_array($allCIs) && count($allCIs) > 0) {
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo '<strong>PASS:</strong> Retrieved all CIs successfully<br>';
                                echo '<small>Found ' . count($allCIs) . ' CI(s) in database</small>';
                                echo '</div>';
                                $testResults['get_all'] = true;
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> No CIs found or error occurred<br>';
                                echo '<small>Expected at least the test CI</small>';
                                echo '</div>';
                                $testResults['get_all'] = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAIL:</strong> Exception retrieving all CIs<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            $testResults['get_all'] = false;
                        }
                        
                        // Test 7: Delete CI
                        echo '<h5 class="mt-4">Test 7: Delete Configuration Item</h5>';
                        if ($testCIId) {
                            try {
                                $deleteResult = $ci->deleteCI($testCIId, $_SESSION['user_id']);
                                
                                if ($deleteResult) {
                                    // Verify deletion
                                    $deletedCI = $ci->getCIById($testCIId);
                                    
                                    if ($deletedCI === false) {
                                        echo '<div class="test-item pass">';
                                        echo '<i class="bi bi-check-circle test-pass"></i> ';
                                        echo '<strong>PASS:</strong> CI deleted successfully<br>';
                                        echo '<small>CI no longer exists in database</small>';
                                        echo '</div>';
                                        $testResults['delete'] = true;
                                    } else {
                                        echo '<div class="test-item fail">';
                                        echo '<i class="bi bi-x-circle test-fail"></i> ';
                                        echo '<strong>FAIL:</strong> CI still exists after deletion<br>';
                                        echo '<small>Deletion verification failed</small>';
                                        echo '</div>';
                                        $testResults['delete'] = false;
                                    }
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> Failed to delete CI<br>';
                                    echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                    echo '</div>';
                                    $testResults['delete'] = false;
                                }
                            } catch (Exception $e) {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Exception during CI deletion<br>';
                                echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                                echo '</div>';
                                $testResults['delete'] = false;
                            }
                        } else {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-exclamation-triangle test-fail"></i> ';
                            echo '<strong>SKIPPED:</strong> No CI ID from creation test';
                            echo '</div>';
                            $testResults['delete'] = false;
                        }
                        
                        // Summary
                        echo '<hr>';
                        $passedTests = array_filter($testResults);
                        $totalTests = count($testResults);
                        $passedCount = count($passedTests);
                        
                        if ($passedCount === $totalTests) {
                            echo '<div class="alert alert-success">';
                            echo '<h5><i class="bi bi-check-circle"></i> All Tests Passed!</h5>';
                            echo '<p>All ' . $totalTests . ' tests completed successfully.</p>';
                            echo '<p><strong>Task 2.1 Status:</strong> CI CRUD operations are working correctly.</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<h5><i class="bi bi-exclamation-triangle"></i> Some Tests Failed</h5>';
                            echo '<p>Passed: ' . $passedCount . ' / ' . $totalTests . '</p>';
                            echo '<p>Please review the failed tests above and fix any issues.</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Next Steps</h6>
                        <ul class="list-unstyled mb-0">
                            <li><a href="admin/index.php"><i class="bi bi-house-door"></i> Return to Admin Dashboard</a></li>
                            <li><a href="test_ci_ticket_relations.php"><i class="bi bi-link"></i> Test CI-Ticket Relationships (Task 2.3)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
