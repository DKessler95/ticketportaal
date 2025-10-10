<?php
require_once __DIR__ . '/Database.php';

class KnowledgeBase {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new knowledge base article
     * 
     * @param string $title Article title
     * @param string $content Article content
     * @param int $categoryId Category ID
     * @param string $tags Comma-separated tags
     * @param int $authorId User ID of the author
     * @param bool $isPublished Whether the article is published
     * @return int|false Article ID on success, false on failure
     */
    public function createArticle($title, $content, $categoryId, $tags, $authorId, $isPublished = false) {
        try {
            $sql = "INSERT INTO knowledge_base (title, content, category_id, tags, author_id, is_published, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $result = $this->db->execute($sql, [
                $title,
                $content,
                $categoryId,
                $tags,
                $authorId,
                $isPublished ? 1 : 0
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating KB article: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing knowledge base article
     * 
     * @param int $kbId Article ID
     * @param array $data Associative array of fields to update
     * @return bool Success status
     */
    public function updateArticle($kbId, $data) {
        try {
            $allowedFields = ['title', 'content', 'category_id', 'tags', 'is_published'];
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $params[] = $kbId;
            $sql = "UPDATE knowledge_base SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE kb_id = ?";
            
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log("Error updating KB article: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a knowledge base article (soft delete)
     * 
     * @param int $kbId Article ID
     * @return bool Success status
     */
    public function deleteArticle($kbId) {
        try {
            $sql = "DELETE FROM knowledge_base WHERE kb_id = ?";
            return $this->db->execute($sql, [$kbId]);
        } catch (Exception $e) {
            error_log("Error deleting KB article: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a single article by ID
     * 
     * @param int $kbId Article ID
     * @return array|false Article data or false if not found
     */
    public function getArticleById($kbId) {
        try {
            $sql = "SELECT kb.*, c.name as category_name, u.first_name, u.last_name 
                    FROM knowledge_base kb
                    LEFT JOIN categories c ON kb.category_id = c.category_id
                    LEFT JOIN users u ON kb.author_id = u.user_id
                    WHERE kb.kb_id = ?";
            
            return $this->db->fetchOne($sql, [$kbId]);
        } catch (Exception $e) {
            error_log("Error fetching KB article: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all articles
     * 
     * @param bool $includeUnpublished Whether to include unpublished articles
     * @return array Array of articles
     */
    public function getAllArticles($includeUnpublished = false) {
        try {
            $sql = "SELECT kb.*, c.name as category_name, u.first_name, u.last_name 
                    FROM knowledge_base kb
                    LEFT JOIN categories c ON kb.category_id = c.category_id
                    LEFT JOIN users u ON kb.author_id = u.user_id";
            
            if (!$includeUnpublished) {
                $sql .= " WHERE kb.is_published = 1";
            }
            
            $sql .= " ORDER BY kb.created_at DESC";
            
            return $this->db->fetchAll($sql, []);
        } catch (Exception $e) {
            error_log("Error fetching all KB articles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get only published articles
     * 
     * @return array Array of published articles
     */
    public function getPublishedArticles() {
        return $this->getAllArticles(false);
    }
    
    /**
     * Search articles using FULLTEXT search
     * 
     * @param string $searchTerm Search term
     * @param bool $includeUnpublished Whether to include unpublished articles
     * @return array Array of matching articles
     */
    public function searchArticles($searchTerm, $includeUnpublished = false) {
        try {
            if (empty(trim($searchTerm))) {
                return $this->getAllArticles($includeUnpublished);
            }
            
            $sql = "SELECT kb.*, c.name as category_name, u.first_name, u.last_name,
                    MATCH(kb.title, kb.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                    FROM knowledge_base kb
                    LEFT JOIN categories c ON kb.category_id = c.category_id
                    LEFT JOIN users u ON kb.author_id = u.user_id
                    WHERE (MATCH(kb.title, kb.content) AGAINST(? IN NATURAL LANGUAGE MODE)
                    OR kb.tags LIKE ?)";
            
            if (!$includeUnpublished) {
                $sql .= " AND kb.is_published = 1";
            }
            
            $sql .= " ORDER BY relevance DESC, kb.created_at DESC";
            
            $searchPattern = '%' . $searchTerm . '%';
            return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchPattern]);
        } catch (Exception $e) {
            error_log("Error searching KB articles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get articles by category
     * 
     * @param int $categoryId Category ID
     * @param bool $includeUnpublished Whether to include unpublished articles
     * @return array Array of articles in the category
     */
    public function getArticlesByCategory($categoryId, $includeUnpublished = false) {
        try {
            $sql = "SELECT kb.*, c.name as category_name, u.first_name, u.last_name 
                    FROM knowledge_base kb
                    LEFT JOIN categories c ON kb.category_id = c.category_id
                    LEFT JOIN users u ON kb.author_id = u.user_id
                    WHERE kb.category_id = ?";
            
            if (!$includeUnpublished) {
                $sql .= " AND kb.is_published = 1";
            }
            
            $sql .= " ORDER BY kb.created_at DESC";
            
            return $this->db->fetchAll($sql, [$categoryId]);
        } catch (Exception $e) {
            error_log("Error fetching KB articles by category: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Increment the view counter for an article
     * 
     * @param int $kbId Article ID
     * @return bool Success status
     */
    public function incrementViews($kbId) {
        try {
            $sql = "UPDATE knowledge_base SET views = views + 1 WHERE kb_id = ?";
            return $this->db->execute($sql, [$kbId]);
        } catch (Exception $e) {
            error_log("Error incrementing KB article views: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Publish an article
     * 
     * @param int $kbId Article ID
     * @return bool Success status
     */
    public function publishArticle($kbId) {
        try {
            $sql = "UPDATE knowledge_base SET is_published = 1, updated_at = NOW() WHERE kb_id = ?";
            return $this->db->execute($sql, [$kbId]);
        } catch (Exception $e) {
            error_log("Error publishing KB article: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unpublish an article
     * 
     * @param int $kbId Article ID
     * @return bool Success status
     */
    public function unpublishArticle($kbId) {
        try {
            $sql = "UPDATE knowledge_base SET is_published = 0, updated_at = NOW() WHERE kb_id = ?";
            return $this->db->execute($sql, [$kbId]);
        } catch (Exception $e) {
            error_log("Error unpublishing KB article: " . $e->getMessage());
            return false;
        }
    }
}
