<?php
/**
 * Template Class
 * 
 * Handles template management for ticket resolutions, comments, and emails
 */

require_once __DIR__ . '/Database.php';

class Template {
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
     * Create a new template
     */
    public function createTemplate($name, $description, $content, $templateType, $createdBy) {
        try {
            // Validate inputs
            if (empty($name) || empty($content)) {
                $this->error = 'Name and content are required';
                return false;
            }
            
            $validTypes = ['resolution', 'comment', 'email'];
            if (!in_array($templateType, $validTypes)) {
                $this->error = 'Invalid template type';
                return false;
            }
            
            $sql = "INSERT INTO ticket_templates (name, description, content, template_type, created_by) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $result = $this->db->execute($sql, [
                $name,
                $description,
                $content,
                $templateType,
                $createdBy
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            $this->error = 'Failed to create template';
            return false;
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('Template', 'Failed to create template', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Update a template
     */
    public function updateTemplate($templateId, $name, $description, $content, $templateType, $isActive) {
        try {
            // Validate inputs
            if (empty($name) || empty($content)) {
                $this->error = 'Name and content are required';
                return false;
            }
            
            $validTypes = ['resolution', 'comment', 'email'];
            if (!in_array($templateType, $validTypes)) {
                $this->error = 'Invalid template type';
                return false;
            }
            
            $sql = "UPDATE ticket_templates 
                    SET name = ?, description = ?, content = ?, template_type = ?, is_active = ? 
                    WHERE template_id = ?";
            
            $result = $this->db->execute($sql, [
                $name,
                $description,
                $content,
                $templateType,
                $isActive ? 1 : 0,
                $templateId
            ]);
            
            if ($result) {
                return true;
            }
            
            $this->error = 'Failed to update template';
            return false;
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('Template', 'Failed to update template', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Delete a template
     */
    public function deleteTemplate($templateId) {
        try {
            $sql = "DELETE FROM ticket_templates WHERE template_id = ?";
            return $this->db->execute($sql, [$templateId]);
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('Template', 'Failed to delete template', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get all templates
     */
    public function getTemplates($templateType = null, $activeOnly = false) {
        try {
            $sql = "SELECT t.*, u.first_name, u.last_name 
                    FROM ticket_templates t
                    LEFT JOIN users u ON t.created_by = u.user_id
                    WHERE 1=1";
            
            $params = [];
            
            if ($templateType) {
                $sql .= " AND t.template_type = ?";
                $params[] = $templateType;
            }
            
            if ($activeOnly) {
                $sql .= " AND t.is_active = 1";
            }
            
            $sql .= " ORDER BY t.name ASC";
            
            $templates = $this->db->fetchAll($sql, $params);
            return $templates ?: [];
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('Template', 'Failed to fetch templates', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get template by ID
     */
    public function getTemplateById($templateId) {
        try {
            $sql = "SELECT t.*, u.first_name, u.last_name 
                    FROM ticket_templates t
                    LEFT JOIN users u ON t.created_by = u.user_id
                    WHERE t.template_id = ?";
            
            return $this->db->fetchOne($sql, [$templateId]);
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('Template', 'Failed to fetch template', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Toggle template active status
     */
    public function toggleActive($templateId) {
        try {
            $sql = "UPDATE ticket_templates SET is_active = NOT is_active WHERE template_id = ?";
            return $this->db->execute($sql, [$templateId]);
        } catch (Exception $e) {
            $this->error = 'Database error: ' . $e->getMessage();
            logError('Template', 'Failed to toggle template status', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
