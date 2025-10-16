<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EmailHandler.php';

class Ticket {
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
     * Generate unique ticket number in format KK-YYYY-XXXX
     */
    private function generateTicketNumber() {
        $year = date('Y');
        $prefix = "KK-{$year}-";
        
        try {
            // Get the highest ticket number for current year
            $sql = "SELECT ticket_number FROM tickets 
                    WHERE ticket_number LIKE ? 
                    ORDER BY ticket_number DESC LIMIT 1";
            $result = $this->db->fetchOne($sql, [$prefix . '%']);
            
            if ($result) {
                // Extract the sequence number and increment
                $lastNumber = intval(substr($result['ticket_number'], -4));
                $newNumber = $lastNumber + 1;
            } else {
                // First ticket of the year
                $newNumber = 1;
            }
            
            // Format with leading zeros
            $ticketNumber = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            // Verify uniqueness
            $checkSql = "SELECT ticket_id FROM tickets WHERE ticket_number = ?";
            $exists = $this->db->fetchOne($checkSql, [$ticketNumber]);
            
            if ($exists) {
                // Recursively try next number if collision occurs
                return $this->generateTicketNumber();
            }
            
            return $ticketNumber;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to generate ticket number', ['error' => $e->getMessage()]);
            $this->error = 'Failed to generate ticket number';
            return false;
        }
    }

    /**
     * Create a new ticket
     */
    public function createTicket($userId, $title, $description, $categoryId, $priority = 'medium', $source = 'web') {
        try {
            // Generate unique ticket number
            $ticketNumber = $this->generateTicketNumber();
            if (!$ticketNumber) {
                return false;
            }
            
            // Validate inputs
            if (empty($title) || empty($description) || empty($categoryId)) {
                $this->error = 'Title, description, and category are required';
                return false;
            }
            
            // Validate priority
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($priority, $validPriorities)) {
                $priority = 'medium';
            }
            
            // Validate source
            $validSources = ['web', 'email', 'phone'];
            if (!in_array($source, $validSources)) {
                $source = 'web';
            }
            
            $sql = "INSERT INTO tickets (ticket_number, user_id, category_id, title, description, priority, source, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'open')";
            
            $result = $this->db->execute($sql, [
                $ticketNumber,
                $userId,
                $categoryId,
                $title,
                $description,
                $priority,
                $source
            ]);
            
            if ($result) {
                $ticketId = $this->db->lastInsertId();
                logError('Ticket', 'Ticket created successfully', [
                    'ticket_id' => $ticketId,
                    'ticket_number' => $ticketNumber,
                    'user_id' => $userId
                ]);
                
                // Send confirmation email
                $this->sendTicketConfirmationEmail($ticketId);
                
                return $ticketId;
            }
            
            $this->error = 'Failed to create ticket';
            return false;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to create ticket', ['error' => $e->getMessage()]);
            $this->error = 'An error occurred while creating the ticket';
            return false;
        }
    }

    /**
     * Get ticket by ID with related data (user, agent, category)
     */
    public function getTicketById($ticketId) {
        try {
            $sql = "SELECT t.*, 
                           u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                           a.email as agent_email, a.first_name as agent_first_name, a.last_name as agent_last_name,
                           c.name as category_name, c.sla_hours
                    FROM tickets t
                    LEFT JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN users a ON t.assigned_agent_id = a.user_id
                    LEFT JOIN categories c ON t.category_id = c.category_id
                    WHERE t.ticket_id = ?";
            
            $ticket = $this->db->fetchOne($sql, [$ticketId]);
            
            if (!$ticket) {
                $this->error = 'Ticket not found';
                return false;
            }
            
            return $ticket;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to fetch ticket', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'Failed to retrieve ticket';
            return false;
        }
    }

    /**
     * Get all tickets created by a specific user
     */
    public function getTicketsByUser($userId) {
        try {
            $sql = "SELECT t.*, 
                           c.name as category_name,
                           a.first_name as agent_first_name, a.last_name as agent_last_name
                    FROM tickets t
                    LEFT JOIN categories c ON t.category_id = c.category_id
                    LEFT JOIN users a ON t.assigned_agent_id = a.user_id
                    WHERE t.user_id = ?
                    ORDER BY t.created_at DESC";
            
            $tickets = $this->db->fetchAll($sql, [$userId]);
            return $tickets ?: [];
        } catch (Exception $e) {
            logError('Ticket', 'Failed to fetch user tickets', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->error = 'Failed to retrieve tickets';
            return [];
        }
    }

    /**
     * Get all tickets with optional filtering
     * Filters: status, priority, category_id, assigned_agent_id, date_from, date_to
     */
    public function getAllTickets($filters = []) {
        try {
            $sql = "SELECT t.*, 
                           u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                           a.first_name as agent_first_name, a.last_name as agent_last_name,
                           c.name as category_name
                    FROM tickets t
                    LEFT JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN users a ON t.assigned_agent_id = a.user_id
                    LEFT JOIN categories c ON t.category_id = c.category_id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority'])) {
                $sql .= " AND t.priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['category_id'])) {
                $sql .= " AND t.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['assigned_agent_id'])) {
                $sql .= " AND t.assigned_agent_id = ?";
                $params[] = $filters['assigned_agent_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND t.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND t.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            if (!empty($filters['source'])) {
                $sql .= " AND t.source = ?";
                $params[] = $filters['source'];
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $tickets = $this->db->fetchAll($sql, $params);
            return $tickets ?: [];
        } catch (Exception $e) {
            logError('Ticket', 'Failed to fetch all tickets', ['error' => $e->getMessage()]);
            $this->error = 'Failed to retrieve tickets';
            return [];
        }
    }

    /**
     * Get ticket count with optional filtering
     * Filters: status (array or string), priority, category_id, assigned_agent_id, date_from, date_to
     * 
     * @param array $filters Optional filters
     * @return int Ticket count
     */
    public function getTicketCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM tickets t
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                if (is_array($filters['status'])) {
                    $placeholders = str_repeat('?,', count($filters['status']) - 1) . '?';
                    $sql .= " AND t.status IN ($placeholders)";
                    $params = array_merge($params, $filters['status']);
                } else {
                    $sql .= " AND t.status = ?";
                    $params[] = $filters['status'];
                }
            }
            
            if (!empty($filters['priority'])) {
                $sql .= " AND t.priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['category_id'])) {
                $sql .= " AND t.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['assigned_agent_id'])) {
                $sql .= " AND t.assigned_agent_id = ?";
                $params[] = $filters['assigned_agent_id'];
            }
            
            if (!empty($filters['user_id'])) {
                $sql .= " AND t.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND t.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND t.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to count tickets', ['error' => $e->getMessage()]);
            $this->error = 'Failed to count tickets';
            return 0;
        }
    }

    /**
     * Assign ticket to an agent
     */
    public function assignTicket($ticketId, $agentId) {
        try {
            // Verify agent exists and has agent or admin role
            $agentSql = "SELECT user_id, role FROM users WHERE user_id = ? AND (role = 'agent' OR role = 'admin')";
            $agent = $this->db->fetchOne($agentSql, [$agentId]);
            
            if (!$agent) {
                $this->error = 'Invalid agent ID or user is not an agent';
                return false;
            }
            
            $sql = "UPDATE tickets SET assigned_agent_id = ?, updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?";
            $result = $this->db->execute($sql, [$agentId, $ticketId]);
            
            if ($result) {
                logError('Ticket', 'Ticket assigned', ['ticket_id' => $ticketId, 'agent_id' => $agentId]);
                
                // Send assignment notification
                $this->sendAssignmentNotificationEmail($ticketId);
                
                return true;
            }
            
            $this->error = 'Failed to assign ticket';
            return false;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to assign ticket', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'An error occurred while assigning the ticket';
            return false;
        }
    }

    /**
     * Update ticket status with optional resolution text
     */
    public function updateStatus($ticketId, $status, $resolution = null) {
        try {
            // Validate status
            $validStatuses = ['open', 'in_progress', 'pending', 'resolved', 'closed'];
            if (!in_array($status, $validStatuses)) {
                $this->error = 'Invalid status';
                return false;
            }
            
            // Require resolution text when marking as resolved
            if ($status === 'resolved' && empty($resolution)) {
                $this->error = 'Resolution text is required when marking ticket as resolved';
                return false;
            }
            
            // Build SQL based on status
            if ($status === 'resolved') {
                $sql = "UPDATE tickets 
                        SET status = ?, resolution = ?, resolved_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                        WHERE ticket_id = ?";
                $params = [$status, $resolution, $ticketId];
            } else {
                $sql = "UPDATE tickets 
                        SET status = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE ticket_id = ?";
                $params = [$status, $ticketId];
            }
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                logError('Ticket', 'Ticket status updated', [
                    'ticket_id' => $ticketId, 
                    'status' => $status,
                    'has_resolution' => !empty($resolution)
                ]);
                
                // Send status update notification
                $this->sendStatusUpdateEmail($ticketId);
                
                // Send resolution notification if ticket is resolved
                if ($status === 'resolved') {
                    $this->sendResolutionNotificationEmail($ticketId);
                }
                
                return true;
            }
            
            $this->error = 'Failed to update ticket status';
            return false;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to update ticket status', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'An error occurred while updating the ticket status';
            return false;
        }
    }

    /**
     * Add a comment to a ticket
     */
    public function addComment($ticketId, $userId, $comment, $isInternal = false) {
        try {
            if (empty($comment)) {
                $this->error = 'Comment cannot be empty';
                return false;
            }
            
            $sql = "INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal) 
                    VALUES (?, ?, ?, ?)";
            
            $result = $this->db->execute($sql, [
                $ticketId,
                $userId,
                $comment,
                $isInternal ? 1 : 0
            ]);
            
            if ($result) {
                $commentId = $this->db->lastInsertId();
                
                // Update ticket's updated_at timestamp
                $updateSql = "UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?";
                $this->db->execute($updateSql, [$ticketId]);
                
                logError('Ticket', 'Comment added', [
                    'comment_id' => $commentId,
                    'ticket_id' => $ticketId,
                    'is_internal' => $isInternal
                ]);
                
                // Send comment notification (skip if internal)
                if (!$isInternal) {
                    $this->sendCommentNotificationEmail($ticketId);
                }
                
                return $commentId;
            }
            
            $this->error = 'Failed to add comment';
            return false;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to add comment', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'An error occurred while adding the comment';
            return false;
        }
    }

    /**
     * Get comments for a ticket
     * If includeInternal is false, only public comments are returned
     */
    public function getComments($ticketId, $includeInternal = true) {
        try {
            $sql = "SELECT tc.*, u.first_name, u.last_name, u.email, u.role
                    FROM ticket_comments tc
                    JOIN users u ON tc.user_id = u.user_id
                    WHERE tc.ticket_id = ?";
            
            if (!$includeInternal) {
                $sql .= " AND tc.is_internal = 0";
            }
            
            $sql .= " ORDER BY tc.created_at ASC";
            
            $comments = $this->db->fetchAll($sql, [$ticketId]);
            return $comments ?: [];
        } catch (Exception $e) {
            logError('Ticket', 'Failed to fetch comments', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'Failed to retrieve comments';
            return [];
        }
    }

    /**
     * Add an attachment to a ticket
     */
    public function addAttachment($ticketId, $filename, $filepath, $filesize) {
        try {
            // Validate file size (max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if ($filesize > $maxSize) {
                $this->error = 'File size exceeds maximum limit of 10MB';
                return false;
            }
            
            // Validate file type
            $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'zip'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                $this->error = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
                return false;
            }
            
            $sql = "INSERT INTO ticket_attachments (ticket_id, filename, filepath, filesize) 
                    VALUES (?, ?, ?, ?)";
            
            $result = $this->db->execute($sql, [
                $ticketId,
                $filename,
                $filepath,
                $filesize
            ]);
            
            if ($result) {
                $attachmentId = $this->db->lastInsertId();
                logError('Ticket', 'Attachment added', [
                    'attachment_id' => $attachmentId,
                    'ticket_id' => $ticketId,
                    'filename' => $filename
                ]);
                return $attachmentId;
            }
            
            $this->error = 'Failed to add attachment';
            return false;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to add attachment', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'An error occurred while adding the attachment';
            return false;
        }
    }

    /**
     * Get attachments for a ticket
     */
    public function getAttachments($ticketId) {
        try {
            $sql = "SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY uploaded_at ASC";
            $attachments = $this->db->fetchAll($sql, [$ticketId]);
            return $attachments ?: [];
        } catch (Exception $e) {
            logError('Ticket', 'Failed to fetch attachments', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'Failed to retrieve attachments';
            return [];
        }
    }

    /**
     * Check if ticket is within SLA
     * Returns array with 'within_sla' (boolean), 'hours_elapsed', 'sla_hours', 'hours_remaining'
     */
    public function checkSLA($ticketId) {
        try {
            $ticket = $this->getTicketById($ticketId);
            
            if (!$ticket) {
                return false;
            }
            
            $slaHours = $ticket['sla_hours'] ?? 24; // Default to 24 hours if not set
            $createdAt = strtotime($ticket['created_at']);
            $resolvedAt = $ticket['resolved_at'] ? strtotime($ticket['resolved_at']) : time();
            
            $hoursElapsed = ($resolvedAt - $createdAt) / 3600; // Convert seconds to hours
            $hoursRemaining = $slaHours - $hoursElapsed;
            $withinSLA = $hoursElapsed <= $slaHours;
            
            return [
                'within_sla' => $withinSLA,
                'hours_elapsed' => round($hoursElapsed, 2),
                'sla_hours' => $slaHours,
                'hours_remaining' => round($hoursRemaining, 2),
                'is_overdue' => !$withinSLA && $ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'
            ];
        } catch (Exception $e) {
            logError('Ticket', 'Failed to check SLA', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            $this->error = 'Failed to check SLA status';
            return false;
        }
    }

    /**
     * Get all overdue tickets (exceeding SLA and not resolved)
     */
    public function getOverdueTickets() {
        try {
            $sql = "SELECT t.*, 
                           u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                           a.first_name as agent_first_name, a.last_name as agent_last_name,
                           c.name as category_name, c.sla_hours,
                           TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as hours_elapsed
                    FROM tickets t
                    LEFT JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN users a ON t.assigned_agent_id = a.user_id
                    LEFT JOIN categories c ON t.category_id = c.category_id
                    WHERE t.status NOT IN ('resolved', 'closed')
                    HAVING hours_elapsed > c.sla_hours
                    ORDER BY hours_elapsed DESC";
            
            $tickets = $this->db->fetchAll($sql, []);
            return $tickets ?: [];
        } catch (Exception $e) {
            logError('Ticket', 'Failed to fetch overdue tickets', ['error' => $e->getMessage()]);
            $this->error = 'Failed to retrieve overdue tickets';
            return [];
        }
    }
    
    /**
     * Send ticket confirmation email
     * 
     * @param int $ticketId Ticket ID
     * @return void
     */
    private function sendTicketConfirmationEmail($ticketId) {
        try {
            $ticketData = $this->getTicketById($ticketId);
            if ($ticketData && !empty($ticketData['user_email'])) {
                $emailHandler = new EmailHandler();
                $emailHandler->sendTicketConfirmation($ticketId, $ticketData['user_email']);
            }
        } catch (Exception $e) {
            // Log but don't fail the ticket creation
            logError('Ticket', 'Failed to send confirmation email', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send assignment notification email
     * 
     * @param int $ticketId Ticket ID
     * @return void
     */
    private function sendAssignmentNotificationEmail($ticketId) {
        try {
            $ticketData = $this->getTicketById($ticketId);
            if ($ticketData && !empty($ticketData['agent_email']) && !empty($ticketData['user_email'])) {
                $emailHandler = new EmailHandler();
                $emailHandler->sendAssignmentNotification(
                    $ticketId,
                    $ticketData['agent_email'],
                    $ticketData['user_email']
                );
            }
        } catch (Exception $e) {
            // Log but don't fail the assignment
            logError('Ticket', 'Failed to send assignment notification', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send status update email
     * 
     * @param int $ticketId Ticket ID
     * @return void
     */
    private function sendStatusUpdateEmail($ticketId) {
        try {
            $ticketData = $this->getTicketById($ticketId);
            if ($ticketData && !empty($ticketData['user_email'])) {
                $emailHandler = new EmailHandler();
                $emailHandler->sendStatusUpdate($ticketId, $ticketData['user_email']);
            }
        } catch (Exception $e) {
            // Log but don't fail the status update
            logError('Ticket', 'Failed to send status update email', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send resolution notification email
     * 
     * @param int $ticketId Ticket ID
     * @return void
     */
    private function sendResolutionNotificationEmail($ticketId) {
        try {
            $ticketData = $this->getTicketById($ticketId);
            if ($ticketData && !empty($ticketData['user_email'])) {
                $emailHandler = new EmailHandler();
                $emailHandler->sendResolutionNotification($ticketId, $ticketData['user_email']);
            }
        } catch (Exception $e) {
            // Log but don't fail the resolution
            logError('Ticket', 'Failed to send resolution notification', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send comment notification email
     * 
     * @param int $ticketId Ticket ID
     * @return void
     */
    private function sendCommentNotificationEmail($ticketId) {
        try {
            $ticketData = $this->getTicketById($ticketId);
            if ($ticketData && !empty($ticketData['user_email'])) {
                $emailHandler = new EmailHandler();
                $emailHandler->sendCommentNotification($ticketId, $ticketData['user_email']);
            }
        } catch (Exception $e) {
            // Log but don't fail the comment addition
            logError('Ticket', 'Failed to send comment notification', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update ticket status (simplified version for admin panel)
     * 
     * @param int $ticketId Ticket ID
     * @param string $status New status
     * @return bool True on success, false on failure
     */
    public function updateTicketStatus($ticketId, $status) {
        return $this->updateStatus($ticketId, $status);
    }
    
    /**
     * Update ticket priority
     * 
     * @param int $ticketId Ticket ID
     * @param string $priority New priority (low, medium, high, urgent)
     * @return bool True on success, false on failure
     */
    public function updateTicketPriority($ticketId, $priority) {
        try {
            // Validate priority
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($priority, $validPriorities)) {
                $this->error = 'Invalid priority';
                return false;
            }
            
            $sql = "UPDATE tickets SET priority = ?, updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?";
            $result = $this->db->execute($sql, [$priority, $ticketId]);
            
            if ($result) {
                logError('Ticket', 'Ticket priority updated', [
                    'ticket_id' => $ticketId,
                    'priority' => $priority
                ]);
                return true;
            }
            
            $this->error = 'Failed to update ticket priority';
            return false;
        } catch (Exception $e) {
            logError('Ticket', 'Failed to update ticket priority', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            $this->error = 'An error occurred while updating the ticket priority';
            return false;
        }
    }
}
