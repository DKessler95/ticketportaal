<?php
/**
 * CI-Ticket Relationship Test Script
 * Tests linking CIs to tickets and viewing relationships
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/config.php';
require_once 'classes/ConfigurationItem.php';
require_once 'classes/Ticket.php';
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
    <title>CI-Ticket Relationship Test</title>
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
                        <h3 class="mb-0"><i class="bi bi-link"></i> CI-Ticket Relationship Test</h3>
                        <small>Testing CI and Ticket linking functionality</small>
                    </div>
                    <div class="card-body">
                        <?php
                        $ci = new ConfigurationItem();
                        $ticket = new Ticket();
                        $testResults = [];
                        $testCIId = null;
                        $testTicketId = null;
                        
                        // Setup: Create a test CI
                        echo '<h5>Setup: Create Test CI</h5>';
                        try {
                            $ciData = [
                                'type' => 'Hardware',
                                'category' => 'Desktop',
                                'brand' => 'HP',
                                'model' => 'EliteDesk 800',
                                'name' => 'Test Desktop for Relationship - ' . date('Y-m-d H:i:s'),
                                'serial_number' => 'REL-TEST-' . uniqid(),
                                'status' => 'In gebruik',
                                'owner_id' => $_SESSION['user_id'],
                                'department' => 'IT',
                                'location' => 'Office C',
                                'notes' => 'Test CI for relationship testing',
                                'created_by' => $_SESSION['user_id']
                            ];
                            
                            $testCIId = $ci->createCI($ciData);
                            
                            if ($testCIId) {
                                $ciInfo = $ci->getCIById($testCIId);
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo '<strong>SUCCESS:</strong> Test CI created<br>';
                                echo '<small>CI ID: ' . $testCIId . ' | CI Number: ' . htmlspecialchars($ciInfo['ci_number']) . '</small>';
                                echo '</div>';
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAILED:</strong> Could not create test CI<br>';
                                echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                echo '</div>';
                                die('Cannot continue without test CI');
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAILED:</strong> Exception creating test CI<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            die('Cannot continue without test CI');
                        }
                        
                        // Setup: Create a test ticket
                        echo '<h5 class="mt-4">Setup: Create Test Ticket</h5>';
                        try {
                            // Get a category ID
                            $db = Database::getInstance();
                            $category = $db->fetchOne("SELECT category_id FROM categories LIMIT 1");
                            
                            if (!$category) {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAILED:</strong> No categories found in database<br>';
                                echo '<small>Please create at least one category first</small>';
                                echo '</div>';
                                die('Cannot continue without category');
                            }
                            
                            $testTicketId = $ticket->createTicket(
                                $_SESSION['user_id'],
                                'Test Ticket for CI Relationship - ' . date('Y-m-d H:i:s'),
                                'This is a test ticket to verify CI-Ticket relationship functionality.',
                                $category['category_id'],
                                'medium',
                                'web'
                            );
                            
                            if ($testTicketId) {
                                $ticketInfo = $ticket->getTicketById($testTicketId);
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo '<strong>SUCCESS:</strong> Test ticket created<br>';
                                echo '<small>Ticket ID: ' . $testTicketId . ' | Ticket Number: ' . htmlspecialchars($ticketInfo['ticket_number']) . '</small>';
                                echo '</div>';
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAILED:</strong> Could not create test ticket<br>';
                                echo '<small>Error: ' . htmlspecialchars($ticket->getError()) . '</small>';
                                echo '</div>';
                                die('Cannot continue without test ticket');
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAILED:</strong> Exception creating test ticket<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            die('Cannot continue without test ticket');
                        }
                        
                        // Test 1: Link CI to Ticket
                        echo '<h5 class="mt-4">Test 1: Link CI to Ticket</h5>';
                        try {
                            $linkResult = $ci->linkToTicket($testCIId, $testTicketId);
                            
                            if ($linkResult) {
                                echo '<div class="test-item pass">';
                                echo '<i class="bi bi-check-circle test-pass"></i> ';
                                echo '<strong>PASS:</strong> CI linked to ticket successfully<br>';
                                echo '<small>CI ID ' . $testCIId . ' linked to Ticket ID ' . $testTicketId . '</small>';
                                echo '</div>';
                                $testResults['link'] = true;
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Failed to link CI to ticket<br>';
                                echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                echo '</div>';
                                $testResults['link'] = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAIL:</strong> Exception linking CI to ticket<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            $testResults['link'] = false;
                        }
                        
                        // Test 2: View Linked Tickets on CI Detail
                        echo '<h5 class="mt-4">Test 2: View Linked Tickets on CI Detail</h5>';
                        try {
                            $linkedTickets = $ci->getLinkedTickets($testCIId);
                            
                            if (is_array($linkedTickets) && count($linkedTickets) > 0) {
                                $foundTestTicket = false;
                                foreach ($linkedTickets as $linkedTicket) {
                                    if ($linkedTicket['ticket_id'] == $testTicketId) {
                                        $foundTestTicket = true;
                                        break;
                                    }
                                }
                                
                                if ($foundTestTicket) {
                                    echo '<div class="test-item pass">';
                                    echo '<i class="bi bi-check-circle test-pass"></i> ';
                                    echo '<strong>PASS:</strong> Linked tickets retrieved successfully<br>';
                                    echo '<small>Found ' . count($linkedTickets) . ' linked ticket(s)</small>';
                                    echo '<div class="code-block mt-2">';
                                    foreach ($linkedTickets as $linkedTicket) {
                                        echo 'Ticket #' . htmlspecialchars($linkedTicket['ticket_number']) . ': ' . 
                                             htmlspecialchars($linkedTicket['title']) . '<br>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    $testResults['view_tickets'] = true;
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> Test ticket not found in linked tickets<br>';
                                    echo '<small>Expected ticket ID ' . $testTicketId . ' not in results</small>';
                                    echo '</div>';
                                    $testResults['view_tickets'] = false;
                                }
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> No linked tickets found<br>';
                                echo '<small>Expected at least the test ticket</small>';
                                echo '</div>';
                                $testResults['view_tickets'] = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAIL:</strong> Exception retrieving linked tickets<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            $testResults['view_tickets'] = false;
                        }
                        
                        // Test 3: View Linked CIs on Ticket Detail
                        echo '<h5 class="mt-4">Test 3: View Linked CIs on Ticket Detail</h5>';
                        try {
                            $linkedCIs = $ci->getCIsByTicket($testTicketId);
                            
                            if (is_array($linkedCIs) && count($linkedCIs) > 0) {
                                $foundTestCI = false;
                                foreach ($linkedCIs as $linkedCI) {
                                    if ($linkedCI['ci_id'] == $testCIId) {
                                        $foundTestCI = true;
                                        break;
                                    }
                                }
                                
                                if ($foundTestCI) {
                                    echo '<div class="test-item pass">';
                                    echo '<i class="bi bi-check-circle test-pass"></i> ';
                                    echo '<strong>PASS:</strong> Linked CIs retrieved successfully<br>';
                                    echo '<small>Found ' . count($linkedCIs) . ' linked CI(s)</small>';
                                    echo '<div class="code-block mt-2">';
                                    foreach ($linkedCIs as $linkedCI) {
                                        echo 'CI #' . htmlspecialchars($linkedCI['ci_number']) . ': ' . 
                                             htmlspecialchars($linkedCI['name']) . ' (' . 
                                             htmlspecialchars($linkedCI['type']) . ')<br>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    $testResults['view_cis'] = true;
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> Test CI not found in linked CIs<br>';
                                    echo '<small>Expected CI ID ' . $testCIId . ' not in results</small>';
                                    echo '</div>';
                                    $testResults['view_cis'] = false;
                                }
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> No linked CIs found<br>';
                                echo '<small>Expected at least the test CI</small>';
                                echo '</div>';
                                $testResults['view_cis'] = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAIL:</strong> Exception retrieving linked CIs<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            $testResults['view_cis'] = false;
                        }
                        
                        // Test 4: Unlink CI from Ticket
                        echo '<h5 class="mt-4">Test 4: Unlink CI from Ticket</h5>';
                        try {
                            $unlinkResult = $ci->unlinkFromTicket($testCIId, $testTicketId);
                            
                            if ($unlinkResult) {
                                // Verify unlink
                                $linkedTicketsAfter = $ci->getLinkedTickets($testCIId);
                                $stillLinked = false;
                                
                                foreach ($linkedTicketsAfter as $linkedTicket) {
                                    if ($linkedTicket['ticket_id'] == $testTicketId) {
                                        $stillLinked = true;
                                        break;
                                    }
                                }
                                
                                if (!$stillLinked) {
                                    echo '<div class="test-item pass">';
                                    echo '<i class="bi bi-check-circle test-pass"></i> ';
                                    echo '<strong>PASS:</strong> CI unlinked from ticket successfully<br>';
                                    echo '<small>Relationship removed from database</small>';
                                    echo '</div>';
                                    $testResults['unlink'] = true;
                                } else {
                                    echo '<div class="test-item fail">';
                                    echo '<i class="bi bi-x-circle test-fail"></i> ';
                                    echo '<strong>FAIL:</strong> CI still linked after unlink operation<br>';
                                    echo '<small>Unlink verification failed</small>';
                                    echo '</div>';
                                    $testResults['unlink'] = false;
                                }
                            } else {
                                echo '<div class="test-item fail">';
                                echo '<i class="bi bi-x-circle test-fail"></i> ';
                                echo '<strong>FAIL:</strong> Failed to unlink CI from ticket<br>';
                                echo '<small>Error: ' . htmlspecialchars($ci->getError()) . '</small>';
                                echo '</div>';
                                $testResults['unlink'] = false;
                            }
                        } catch (Exception $e) {
                            echo '<div class="test-item fail">';
                            echo '<i class="bi bi-x-circle test-fail"></i> ';
                            echo '<strong>FAIL:</strong> Exception unlinking CI from ticket<br>';
                            echo '<small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                            echo '</div>';
                            $testResults['unlink'] = false;
                        }
                        
                        // Cleanup: Delete test data
                        echo '<h5 class="mt-4">Cleanup: Delete Test Data</h5>';
                        $cleanupSuccess = true;
                        
                        // Delete test CI
                        if ($testCIId) {
                            $deleteCI = $ci->deleteCI($testCIId, $_SESSION['user_id']);
                            if ($deleteCI) {
                                echo '<div class="alert alert-info">Test CI deleted successfully</div>';
                            } else {
                                echo '<div class="alert alert-warning">Failed to delete test CI (ID: ' . $testCIId . ')</div>';
                                $cleanupSuccess = false;
                            }
                        }
                        
                        // Note: We don't delete the test ticket as it might be useful for other tests
                        echo '<div class="alert alert-info">Test ticket kept for reference (ID: ' . $testTicketId . ')</div>';
                        
                        // Summary
                        echo '<hr>';
                        $passedTests = array_filter($testResults);
                        $totalTests = count($testResults);
                        $passedCount = count($passedTests);
                        
                        if ($passedCount === $totalTests) {
                            echo '<div class="alert alert-success">';
                            echo '<h5><i class="bi bi-check-circle"></i> All Tests Passed!</h5>';
                            echo '<p>All ' . $totalTests . ' tests completed successfully.</p>';
                            echo '<p><strong>Task 2.3 Status:</strong> CI-Ticket relationships are working correctly.</p>';
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
                        <h6>Test Summary</h6>
                        <p>This test verified:</p>
                        <ul>
                            <li>Linking CI to ticket</li>
                            <li>Viewing linked tickets on CI detail page</li>
                            <li>Viewing linked CIs on ticket detail page</li>
                            <li>Unlinking CI from ticket</li>
                        </ul>
                        <h6 class="mt-3">Next Steps</h6>
                        <ul class="list-unstyled mb-0">
                            <li><a href="admin/index.php"><i class="bi bi-house-door"></i> Return to Admin Dashboard</a></li>
                            <li><a href="test_ci_management.php"><i class="bi bi-arrow-left"></i> Back to CI CRUD Tests</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
