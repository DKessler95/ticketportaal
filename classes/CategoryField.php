<?php
/**
 * CategoryField Class
 * 
 * Manages dynamic fields for ticket categories
 */

require_once __DIR__ . '/Database.php';

class CategoryField {
    private $db;
    private $error;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get last error message
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Create a new category field
     * 
     * @param int $categoryId Category ID
     * @param array $fieldData Field data array with keys: field_name, field_label, field_type, etc.
     * @return int|false Field ID on success, false on failure
     */
    public function createField($categoryId, $fieldData) {
        try {
            // Validate field_name is unique for this category
            $existingField = $this->db->fetchOne(
                "SELECT field_id FROM category_fields WHERE category_id = ? AND field_name = ?",
                [$categoryId, $fieldData['field_name']]
            );
            
            if ($existingField) {
                $this->error = 'Field name already exists for this category';
                return false;
            }
            
            // Validate field type
            $allowedTypes = ['text', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'email', 'tel'];
            if (!in_array($fieldData['field_type'], $allowedTypes)) {
                $this->error = 'Invalid field type';
                return false;
            }
            
            // Get next field order if not specified
            if (!isset($fieldData['field_order'])) {
                $maxOrder = $this->db->fetchOne(
                    "SELECT MAX(field_order) as max_order FROM category_fields WHERE category_id = ?",
                    [$categoryId]
                );
                $fieldData['field_order'] = ($maxOrder['max_order'] ?? 0) + 1;
            }
            
            // Encode options as JSON if it's an array
            if (isset($fieldData['field_options']) && is_array($fieldData['field_options'])) {
                $fieldData['field_options'] = json_encode($fieldData['field_options']);
            }
            
            // Encode conditional_logic as JSON if it's an array
            if (isset($fieldData['conditional_logic']) && is_array($fieldData['conditional_logic'])) {
                $fieldData['conditional_logic'] = json_encode($fieldData['conditional_logic']);
            }
            
            $sql = "INSERT INTO category_fields 
                    (category_id, field_name, field_label, field_type, field_options, is_required, 
                     field_order, placeholder, help_text, conditional_logic) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = $this->db->execute($sql, [
                $categoryId,
                $fieldData['field_name'],
                $fieldData['field_label'],
                $fieldData['field_type'],
                $fieldData['field_options'] ?? null,
                isset($fieldData['is_required']) && $fieldData['is_required'] ? 1 : 0,
                $fieldData['field_order'],
                $fieldData['placeholder'] ?? null,
                $fieldData['help_text'] ?? null,
                $fieldData['conditional_logic'] ?? null
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            $this->error = 'Failed to create field';
            return false;
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to create field', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId,
                'field_data' => $fieldData
            ]);
            return false;
        }
    }
    
    /**
     * Update a category field
     * 
     * @param int $fieldId Field ID to update
     * @param array $data Field data to update
     * @return bool True on success, false on failure
     */
    public function updateField($fieldId, $data) {
        try {
            // Validate field exists
            $existingField = $this->getFieldById($fieldId);
            if (!$existingField) {
                $this->error = 'Field not found';
                return false;
            }
            
            // Validate field type if being updated
            if (isset($data['field_type'])) {
                $allowedTypes = ['text', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'email', 'tel'];
                if (!in_array($data['field_type'], $allowedTypes)) {
                    $this->error = 'Invalid field type';
                    return false;
                }
            }
            
            // Check if field_name is unique for category (if being updated)
            if (isset($data['field_name']) && $data['field_name'] !== $existingField['field_name']) {
                $duplicate = $this->db->fetchOne(
                    "SELECT field_id FROM category_fields WHERE category_id = ? AND field_name = ? AND field_id != ?",
                    [$existingField['category_id'], $data['field_name'], $fieldId]
                );
                
                if ($duplicate) {
                    $this->error = 'Field name already exists for this category';
                    return false;
                }
            }
            
            $allowedFields = ['field_name', 'field_label', 'field_type', 'field_options', 
                            'is_required', 'field_order', 'placeholder', 'help_text', 'is_active', 'conditional_logic'];
            
            $updateFields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    // Convert boolean fields
                    if ($field === 'is_required' || $field === 'is_active') {
                        $value = $value ? 1 : 0;
                    }
                    
                    // Encode arrays as JSON
                    if (($field === 'field_options' || $field === 'conditional_logic') && is_array($value)) {
                        $value = json_encode($value);
                    }
                    
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updateFields)) {
                $this->error = 'No valid fields to update';
                return false;
            }
            
            $params[] = $fieldId;
            $sql = "UPDATE category_fields SET " . implode(', ', $updateFields) . " WHERE field_id = ?";
            
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to update field', [
                'error' => $e->getMessage(),
                'field_id' => $fieldId,
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Delete a category field
     * 
     * @param int $fieldId Field ID to delete
     * @return bool True on success, false on failure
     */
    public function deleteField($fieldId) {
        try {
            // Check if field has values in tickets
            $hasValues = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM ticket_field_values WHERE field_id = ?",
                [$fieldId]
            );
            
            if ($hasValues && $hasValues['count'] > 0) {
                // Soft delete - mark as inactive instead of deleting
                $this->error = 'Field has existing values. Marking as inactive instead.';
                return $this->db->execute(
                    "UPDATE category_fields SET is_active = 0 WHERE field_id = ?",
                    [$fieldId]
                );
            }
            
            // No values exist, safe to delete
            $sql = "DELETE FROM category_fields WHERE field_id = ?";
            return $this->db->execute($sql, [$fieldId]);
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to delete field', [
                'error' => $e->getMessage(),
                'field_id' => $fieldId
            ]);
            return false;
        }
    }
    
    /**
     * Get all fields for a category
     * 
     * @param int $categoryId Category ID
     * @param bool $activeOnly Only return active fields
     * @return array Array of fields
     */
    public function getFieldsByCategory($categoryId, $activeOnly = true) {
        try {
            $sql = "SELECT * FROM category_fields WHERE category_id = ?";
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY field_order ASC, field_id ASC";
            
            $fields = $this->db->fetchAll($sql, [$categoryId]);
            
            // Decode JSON fields
            if ($fields) {
                foreach ($fields as &$field) {
                    if (!empty($field['field_options'])) {
                        $decoded = json_decode($field['field_options'], true);
                        if ($decoded !== null) {
                            $field['field_options'] = $decoded;
                        }
                    }
                    if (!empty($field['conditional_logic'])) {
                        $decoded = json_decode($field['conditional_logic'], true);
                        if ($decoded !== null) {
                            $field['conditional_logic'] = $decoded;
                        }
                    }
                }
            }
            
            return $fields ?: [];
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to fetch fields', [
                'error' => $e->getMessage(),
                'category_id' => $categoryId
            ]);
            return [];
        }
    }
    
    /**
     * Get field by ID
     */
    public function getFieldById($fieldId) {
        try {
            $sql = "SELECT * FROM category_fields WHERE field_id = ?";
            return $this->db->fetchOne($sql, [$fieldId]);
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to fetch field', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Save field values for a ticket
     */
    public function saveFieldValues($ticketId, $fieldValues) {
        try {
            foreach ($fieldValues as $fieldId => $value) {
                // Convert arrays to JSON for checkbox fields
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                
                $sql = "INSERT INTO ticket_field_values (ticket_id, field_id, field_value) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE field_value = ?";
                
                $this->db->execute($sql, [$ticketId, $fieldId, $value, $value]);
            }
            
            return true;
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to save field values', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get field values for a ticket
     */
    public function getFieldValues($ticketId) {
        try {
            $sql = "SELECT tfv.*, cf.field_name, cf.field_label, cf.field_type 
                    FROM ticket_field_values tfv
                    JOIN category_fields cf ON tfv.field_id = cf.field_id
                    WHERE tfv.ticket_id = ?
                    ORDER BY cf.field_order ASC";
            
            $values = $this->db->fetchAll($sql, [$ticketId]);
            
            // Convert JSON back to arrays for checkbox fields
            if ($values) {
                foreach ($values as &$value) {
                    if ($value['field_type'] === 'checkbox' && !empty($value['field_value'])) {
                        $decoded = json_decode($value['field_value'], true);
                        if ($decoded !== null) {
                            $value['field_value'] = $decoded;
                        }
                    }
                }
            }
            
            return $values ?: [];
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to fetch field values', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get all categories with their field counts
     */
    public function getCategoriesWithFieldCounts() {
        try {
            $sql = "SELECT c.*, 
                           COUNT(cf.field_id) as field_count,
                           SUM(CASE WHEN cf.is_active = 1 THEN 1 ELSE 0 END) as active_field_count
                    FROM categories c
                    LEFT JOIN category_fields cf ON c.category_id = cf.category_id
                    GROUP BY c.category_id
                    ORDER BY c.name ASC";
            
            $categories = $this->db->fetchAll($sql);
            return $categories ?: [];
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to fetch categories', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Update field order (for drag & drop)
     * 
     * @param array $fieldOrderArray Array with field_id => new_order
     * @return bool True on success, false on failure
     */
    public function updateFieldOrder($fieldOrderArray) {
        try {
            foreach ($fieldOrderArray as $fieldId => $order) {
                $sql = "UPDATE category_fields SET field_order = ? WHERE field_id = ?";
                $result = $this->db->execute($sql, [$order, $fieldId]);
                
                if (!$result) {
                    throw new Exception("Failed to update field order for field_id: $fieldId");
                }
            }
            
            return true;
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('CategoryField', 'Failed to reorder fields', [
                'error' => $e->getMessage(),
                'field_orders' => $fieldOrderArray
            ]);
            return false;
        }
    }
    
    /**
     * Reorder fields (alias for backward compatibility)
     * 
     * @param array $fieldOrders Array with field_id => new_order
     * @return bool True on success, false on failure
     */
    public function reorderFields($fieldOrders) {
        return $this->updateFieldOrder($fieldOrders);
    }
}
