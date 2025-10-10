<?php
/**
 * Category Class
 * 
 * Handles category management operations including CRUD operations
 * for ticket categories with SLA settings and active/inactive status
 */

require_once __DIR__ . '/Database.php';

class Category {
    private $db;
    private $error;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get last error message
     * 
     * @return string|null
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Create a new category
     * 
     * @param string $name Category name
     * @param string $description Category description
     * @param string $defaultPriority Default priority (low, medium, high, urgent)
     * @param int $slaHours SLA hours for this category
     * @return int|false Category ID on success, false on failure
     */
    public function createCategory($name, $description, $defaultPriority, $slaHours) {
        // Validate inputs
        if (empty($name)) {
            $this->error = "Category name is required";
            return false;
        }
        
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($defaultPriority, $validPriorities)) {
            $this->error = "Invalid priority level";
            return false;
        }
        
        if (!is_numeric($slaHours) || $slaHours < 1) {
            $this->error = "SLA hours must be a positive number";
            return false;
        }
        
        try {
            $sql = "INSERT INTO categories (name, description, default_priority, sla_hours, is_active) 
                    VALUES (?, ?, ?, ?, 1)";
            
            $result = $this->db->execute($sql, [
                trim($name),
                trim($description),
                $defaultPriority,
                (int)$slaHours
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            $this->error = "Failed to create category";
            return false;
            
        } catch (Exception $e) {
            $this->error = "Database error: " . $e->getMessage();
            $this->logError('Category Create', 'Failed to create category', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update an existing category
     * 
     * @param int $categoryId Category ID
     * @param array $data Associative array of fields to update
     * @return bool
     */
    public function updateCategory($categoryId, $data) {
        if (empty($categoryId) || !is_numeric($categoryId)) {
            $this->error = "Invalid category ID";
            return false;
        }
        
        // Check if category exists
        $category = $this->getCategoryById($categoryId);
        if (!$category) {
            $this->error = "Category not found";
            return false;
        }
        
        $allowedFields = ['name', 'description', 'default_priority', 'sla_hours', 'is_active'];
        $updateFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                // Validate specific fields
                if ($field === 'default_priority') {
                    $validPriorities = ['low', 'medium', 'high', 'urgent'];
                    if (!in_array($value, $validPriorities)) {
                        $this->error = "Invalid priority level";
                        return false;
                    }
                }
                
                if ($field === 'sla_hours' && (!is_numeric($value) || $value < 1)) {
                    $this->error = "SLA hours must be a positive number";
                    return false;
                }
                
                if ($field === 'is_active') {
                    $value = $value ? 1 : 0;
                }
                
                $updateFields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updateFields)) {
            $this->error = "No valid fields to update";
            return false;
        }
        
        try {
            $params[] = $categoryId;
            $sql = "UPDATE categories SET " . implode(', ', $updateFields) . " WHERE category_id = ?";
            
            return $this->db->execute($sql, $params);
            
        } catch (Exception $e) {
            $this->error = "Database error: " . $e->getMessage();
            $this->logError('Category Update', 'Failed to update category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Delete (deactivate) a category
     * Soft delete - sets is_active to false instead of removing from database
     * 
     * @param int $categoryId Category ID
     * @return bool
     */
    public function deleteCategory($categoryId) {
        if (empty($categoryId) || !is_numeric($categoryId)) {
            $this->error = "Invalid category ID";
            return false;
        }
        
        try {
            $sql = "UPDATE categories SET is_active = 0 WHERE category_id = ?";
            return $this->db->execute($sql, [$categoryId]);
            
        } catch (Exception $e) {
            $this->error = "Database error: " . $e->getMessage();
            $this->logError('Category Delete', 'Failed to delete category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get all categories
     * 
     * @param bool $activeOnly If true, return only active categories
     * @return array|false
     */
    public function getCategories($activeOnly = false) {
        try {
            if ($activeOnly) {
                $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC";
            } else {
                $sql = "SELECT * FROM categories ORDER BY name ASC";
            }
            
            $result = $this->db->fetchAll($sql);
            return $result !== false ? $result : [];
            
        } catch (Exception $e) {
            $this->error = "Database error: " . $e->getMessage();
            $this->logError('Category GetAll', 'Failed to fetch categories', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get category by ID
     * 
     * @param int $categoryId Category ID
     * @return array|false
     */
    public function getCategoryById($categoryId) {
        if (empty($categoryId) || !is_numeric($categoryId)) {
            $this->error = "Invalid category ID";
            return false;
        }
        
        try {
            $sql = "SELECT * FROM categories WHERE category_id = ?";
            return $this->db->fetchOne($sql, [$categoryId]);
            
        } catch (Exception $e) {
            $this->error = "Database error: " . $e->getMessage();
            $this->logError('Category GetById', 'Failed to fetch category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Activate a category
     * 
     * @param int $categoryId Category ID
     * @return bool
     */
    public function activateCategory($categoryId) {
        return $this->updateCategory($categoryId, ['is_active' => 1]);
    }
    
    /**
     * Deactivate a category
     * 
     * @param int $categoryId Category ID
     * @return bool
     */
    public function deactivateCategory($categoryId) {
        return $this->updateCategory($categoryId, ['is_active' => 0]);
    }
    
    /**
     * Log errors
     * 
     * @param string $context Error context
     * @param string $message Error message
     * @param array $data Additional data
     */
    private function logError($context, $message, $data = []) {
        if (function_exists('logError')) {
            logError($context, $message, $data);
        } else {
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'context' => $context,
                'message' => $message,
                'data' => $data
            ];
            error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/../logs/category.log');
        }
    }
}
