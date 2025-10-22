<?php
/**
 * Configuration Item (CI) Class
 * 
 * Handles Configuration Item management including CRUD operations,
 * history logging, and relationships with tickets and changes
 */

require_once __DIR__ . '/Database.php';

class ConfigurationItem {
    private $db;
    private $error = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get last error message
     * 
     * @return string|null Error message
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Generate unique CI number in format CI-YYYY-XXXX
     * 
     * @return string|false CI number on success, false on failure
     */
    private function generateCINumber() {
        $year = date('Y');
        $prefix = "CI-{$year}-";
        
        try {
            // Get the highest CI number for current year
            $sql = "SELECT ci_number FROM configuration_items 
                    WHERE ci_number LIKE ? 
                    ORDER BY ci_number DESC LIMIT 1";
            $result = $this->db->fetchOne($sql, [$prefix . '%']);
            
            if ($result) {
                // Extract the sequence number and increment
                $lastNumber = intval(substr($result['ci_number'], -4));
                $newNumber = $lastNumber + 1;
            } else {
                // First CI of the year
                $newNumber = 1;
            }
            
            // Format with leading zeros
            $ciNumber = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            // Verify uniqueness
            $checkSql = "SELECT ci_id FROM configuration_items WHERE ci_number = ?";
            $exists = $this->db->fetchOne($checkSql, [$ciNumber]);
            
            if ($exists) {
                // Recursively try next number if collision occurs
                return $this->generateCINumber();
            }
            
            return $ciNumber;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to generate CI number', ['error' => $e->getMessage()]);
            }
            $this->error = 'Failed to generate CI number';
            return false;
        }
    }

    /**
     * Create a new Configuration Item
     * 
     * @param array $data CI data
     * @return int|false CI ID on success, false on failure
     */
    public function createCI($data) {
        try {
            // Generate unique CI number
            $ciNumber = $this->generateCINumber();
            if (!$ciNumber) {
                return false;
            }
            
            // Validate required fields
            if (empty($data['type']) || empty($data['name'])) {
                $this->error = 'Type and name are required';
                return false;
            }
            
            // Validate type
            $validTypes = ['Hardware', 'Software', 'Licentie', 'Overig'];
            if (!in_array($data['type'], $validTypes)) {
                $this->error = 'Invalid CI type';
                return false;
            }
            
            // Validate status
            $validStatuses = ['In gebruik', 'In voorraad', 'Defect', 'Afgeschreven'];
            $status = $data['status'] ?? 'In gebruik';
            if (!in_array($status, $validStatuses)) {
                $status = 'In gebruik';
            }
            
            $sql = "INSERT INTO configuration_items 
                    (ci_number, type, category, brand, model, name, serial_number, status, 
                     owner_id, department, location, purchase_date, purchase_price, supplier, 
                     warranty_expiry, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = $this->db->execute($sql, [
                $ciNumber,
                $data['type'],
                $data['category'] ?? null,
                $data['brand'] ?? null,
                $data['model'] ?? null,
                $data['name'],
                $data['serial_number'] ?? null,
                $status,
                $data['owner_id'] ?? null,
                $data['department'] ?? null,
                $data['location'] ?? null,
                $data['purchase_date'] ?? null,
                $data['purchase_price'] ?? null,
                $data['supplier'] ?? null,
                $data['warranty_expiry'] ?? null,
                $data['notes'] ?? null,
                $data['created_by'] ?? null
            ]);
            
            if ($result) {
                $ciId = $this->db->lastInsertId();
                
                // Log creation in history
                $this->logHistory($ciId, $data['created_by'] ?? 1, 'created', null, null, null);
                
                if (function_exists('logError')) {
                    logError('ConfigurationItem', 'CI created successfully', [
                        'ci_id' => $ciId,
                        'ci_number' => $ciNumber
                    ]);
                }
                
                return $ciId;
            }
            
            $this->error = 'Failed to create CI';
            return false;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to create CI', ['error' => $e->getMessage()]);
            }
            $this->error = 'An error occurred while creating the CI';
            return false;
        }
    }

    /**
     * Get CI by ID with related data
     * 
     * @param int $ciId CI ID
     * @return array|false CI data on success, false on failure
     */
    public function getCIById($ciId) {
        try {
            $sql = "SELECT ci.*, 
                           u.email as owner_email, u.first_name as owner_first_name, u.last_name as owner_last_name,
                           c.email as creator_email, c.first_name as creator_first_name, c.last_name as creator_last_name
                    FROM configuration_items ci
                    LEFT JOIN users u ON ci.owner_id = u.user_id
                    LEFT JOIN users c ON ci.created_by = c.user_id
                    WHERE ci.ci_id = ?";
            
            $ci = $this->db->fetchOne($sql, [$ciId]);
            
            if (!$ci) {
                $this->error = 'CI not found';
                return false;
            }
            
            return $ci;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to fetch CI', ['ci_id' => $ciId, 'error' => $e->getMessage()]);
            }
            $this->error = 'Failed to retrieve CI';
            return false;
        }
    }

    /**
     * Get all CIs with optional filtering
     * 
     * @param array $filters Optional filters (type, status, owner_id)
     * @return array Array of CIs
     */
    public function getAllCIs($filters = []) {
        try {
            $sql = "SELECT ci.*, 
                           u.first_name as owner_first_name, u.last_name as owner_last_name
                    FROM configuration_items ci
                    LEFT JOIN users u ON ci.owner_id = u.user_id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['type'])) {
                $sql .= " AND ci.type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND ci.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['owner_id'])) {
                $sql .= " AND ci.owner_id = ?";
                $params[] = $filters['owner_id'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (ci.ci_number LIKE ? OR ci.name LIKE ? OR ci.serial_number LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY ci.created_at DESC";
            
            $cis = $this->db->fetchAll($sql, $params);
            return $cis ?: [];
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to fetch all CIs', ['error' => $e->getMessage()]);
            }
            $this->error = 'Failed to retrieve CIs';
            return [];
        }
    }

    /**
     * Update a Configuration Item
     * 
     * @param int $ciId CI ID
     * @param array $data Updated CI data
     * @param int $userId User ID performing the update
     * @return bool True on success, false on failure
     */
    public function updateCI($ciId, $data, $userId) {
        try {
            // Get current CI data for history logging
            $currentCI = $this->getCIById($ciId);
            if (!$currentCI) {
                return false;
            }
            
            // Build update query dynamically
            $allowedFields = ['type', 'category', 'brand', 'model', 'name', 'serial_number', 
                            'status', 'owner_id', 'department', 'location', 'purchase_date', 
                            'purchase_price', 'supplier', 'warranty_expiry', 'notes'];
            $updateFields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                    
                    // Log field changes in history
                    if ($currentCI[$field] != $value) {
                        $action = ($field === 'status') ? 'status_changed' : 'updated';
                        $this->logHistory($ciId, $userId, $action, $field, $currentCI[$field], $value);
                    }
                }
            }
            
            if (empty($updateFields)) {
                $this->error = 'No valid fields to update';
                return false;
            }
            
            // Add ci_id to params
            $params[] = $ciId;
            
            $sql = "UPDATE configuration_items SET " . implode(', ', $updateFields) . " WHERE ci_id = ?";
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                if (function_exists('logError')) {
                    logError('ConfigurationItem', 'CI updated successfully', [
                        'ci_id' => $ciId,
                        'fields' => array_keys($data)
                    ]);
                }
                return true;
            }
            
            $this->error = 'Failed to update CI';
            return false;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to update CI', ['ci_id' => $ciId, 'error' => $e->getMessage()]);
            }
            $this->error = 'An error occurred while updating the CI';
            return false;
        }
    }

    /**
     * Delete/Archive a Configuration Item
     * 
     * @param int $ciId CI ID
     * @param int $userId User ID performing the deletion
     * @return bool True on success, false on failure
     */
    public function deleteCI($ciId, $userId) {
        try {
            // Check if CI exists
            $ci = $this->getCIById($ciId);
            if (!$ci) {
                return false;
            }
            
            // Log deletion in history before deleting
            $this->logHistory($ciId, $userId, 'deleted', null, null, null);
            
            // Delete the CI (cascade will handle related records)
            $sql = "DELETE FROM configuration_items WHERE ci_id = ?";
            $result = $this->db->execute($sql, [$ciId]);
            
            if ($result) {
                if (function_exists('logError')) {
                    logError('ConfigurationItem', 'CI deleted successfully', [
                        'ci_id' => $ciId,
                        'ci_number' => $ci['ci_number']
                    ]);
                }
                return true;
            }
            
            $this->error = 'Failed to delete CI';
            return false;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to delete CI', ['ci_id' => $ciId, 'error' => $e->getMessage()]);
            }
            $this->error = 'An error occurred while deleting the CI';
            return false;
        }
    }

    /**
     * Log CI history
     * 
     * @param int $ciId CI ID
     * @param int $userId User ID performing the action
     * @param string $action Action type (created, updated, status_changed, deleted)
     * @param string|null $fieldChanged Field that was changed
     * @param string|null $oldValue Old value
     * @param string|null $newValue New value
     * @return bool True on success, false on failure
     */
    private function logHistory($ciId, $userId, $action, $fieldChanged = null, $oldValue = null, $newValue = null) {
        try {
            $sql = "INSERT INTO ci_history (ci_id, user_id, action, field_changed, old_value, new_value) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            return $this->db->execute($sql, [
                $ciId,
                $userId,
                $action,
                $fieldChanged,
                $oldValue,
                $newValue
            ]);
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to log CI history', [
                    'ci_id' => $ciId,
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    /**
     * Get CI history
     * 
     * @param int $ciId CI ID
     * @return array Array of history entries
     */
    public function getCIHistory($ciId) {
        try {
            $sql = "SELECT h.*, u.first_name, u.last_name, u.email
                    FROM ci_history h
                    JOIN users u ON h.user_id = u.user_id
                    WHERE h.ci_id = ?
                    ORDER BY h.changed_at DESC";
            
            $history = $this->db->fetchAll($sql, [$ciId]);
            return $history ?: [];
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to fetch CI history', [
                    'ci_id' => $ciId,
                    'error' => $e->getMessage()
                ]);
            }
            $this->error = 'Failed to retrieve CI history';
            return [];
        }
    }

    /**
     * Link CI to a ticket
     * 
     * @param int $ciId CI ID
     * @param int $ticketId Ticket ID
     * @return bool True on success, false on failure
     */
    public function linkToTicket($ciId, $ticketId) {
        try {
            // Check if link already exists
            $checkSql = "SELECT relation_id FROM ticket_ci_relations WHERE ticket_id = ? AND ci_id = ?";
            $exists = $this->db->fetchOne($checkSql, [$ticketId, $ciId]);
            
            if ($exists) {
                $this->error = 'CI is already linked to this ticket';
                return false;
            }
            
            $sql = "INSERT INTO ticket_ci_relations (ticket_id, ci_id) VALUES (?, ?)";
            $result = $this->db->execute($sql, [$ticketId, $ciId]);
            
            if ($result) {
                if (function_exists('logError')) {
                    logError('ConfigurationItem', 'CI linked to ticket', [
                        'ci_id' => $ciId,
                        'ticket_id' => $ticketId
                    ]);
                }
                return true;
            }
            
            $this->error = 'Failed to link CI to ticket';
            return false;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to link CI to ticket', [
                    'ci_id' => $ciId,
                    'ticket_id' => $ticketId,
                    'error' => $e->getMessage()
                ]);
            }
            $this->error = 'An error occurred while linking CI to ticket';
            return false;
        }
    }

    /**
     * Get tickets linked to a CI
     * 
     * @param int $ciId CI ID
     * @return array Array of linked tickets
     */
    public function getLinkedTickets($ciId) {
        try {
            $sql = "SELECT t.*, tcr.relation_id,
                           u.first_name as user_first_name, u.last_name as user_last_name,
                           c.name as category_name
                    FROM ticket_ci_relations tcr
                    JOIN tickets t ON tcr.ticket_id = t.ticket_id
                    LEFT JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN categories c ON t.category_id = c.category_id
                    WHERE tcr.ci_id = ?
                    ORDER BY t.created_at DESC";
            
            $tickets = $this->db->fetchAll($sql, [$ciId]);
            return $tickets ?: [];
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to fetch linked tickets', [
                    'ci_id' => $ciId,
                    'error' => $e->getMessage()
                ]);
            }
            $this->error = 'Failed to retrieve linked tickets';
            return [];
        }
    }

    /**
     * Get CIs linked to a ticket
     * 
     * @param int $ticketId Ticket ID
     * @return array Array of linked CIs
     */
    public function getCIsByTicket($ticketId) {
        try {
            $sql = "SELECT ci.*, tcr.relation_id,
                           u.first_name as owner_first_name, u.last_name as owner_last_name
                    FROM ticket_ci_relations tcr
                    JOIN configuration_items ci ON tcr.ci_id = ci.ci_id
                    LEFT JOIN users u ON ci.owner_id = u.user_id
                    WHERE tcr.ticket_id = ?
                    ORDER BY ci.ci_number";
            
            $cis = $this->db->fetchAll($sql, [$ticketId]);
            return $cis ?: [];
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to fetch CIs by ticket', [
                    'ticket_id' => $ticketId,
                    'error' => $e->getMessage()
                ]);
            }
            $this->error = 'Failed to retrieve linked CIs';
            return [];
        }
    }

    /**
     * Unlink CI from a ticket
     * 
     * @param int $ciId CI ID
     * @param int $ticketId Ticket ID
     * @return bool True on success, false on failure
     */
    public function unlinkFromTicket($ciId, $ticketId) {
        try {
            $sql = "DELETE FROM ticket_ci_relations WHERE ticket_id = ? AND ci_id = ?";
            $result = $this->db->execute($sql, [$ticketId, $ciId]);
            
            if ($result) {
                if (function_exists('logError')) {
                    logError('ConfigurationItem', 'CI unlinked from ticket', [
                        'ci_id' => $ciId,
                        'ticket_id' => $ticketId
                    ]);
                }
                return true;
            }
            
            $this->error = 'Failed to unlink CI from ticket';
            return false;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to unlink CI from ticket', [
                    'ci_id' => $ciId,
                    'ticket_id' => $ticketId,
                    'error' => $e->getMessage()
                ]);
            }
            $this->error = 'An error occurred while unlinking CI from ticket';
            return false;
        }
    }

    /**
     * Get CI count with optional filtering
     * 
     * @param array $filters Optional filters
     * @return int CI count
     */
    public function getCICount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as count FROM configuration_items WHERE 1=1";
            $params = [];
            
            if (!empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            if (function_exists('logError')) {
                logError('ConfigurationItem', 'Failed to count CIs', ['error' => $e->getMessage()]);
            }
            $this->error = 'Failed to count CIs';
            return 0;
        }
    }
}
