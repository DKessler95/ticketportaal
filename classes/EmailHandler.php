<?php
/**
 * EmailHandler Class
 * 
 * Handles email sending, parsing, and ticket creation from emails
 * Integrates with Collax email server for notifications and email-to-ticket functionality
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Ticket.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../includes/functions.php';

class EmailHandler {
    private $db;
    private $error = null;
    
    /**
     * Constructor
     */
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
     * Send ticket confirmation email to user
     * 
     * @param int $ticketId Ticket ID
     * @param string $recipientEmail Recipient email address
     * @return bool True on success, false on failure
     */
    public function sendTicketConfirmation($ticketId, $recipientEmail) {
        try {
            $ticket = new Ticket();
            $ticketData = $ticket->getTicketById($ticketId);
            
            if (!$ticketData) {
                $this->error = 'Ticket not found';
                return false;
            }
            
            $subject = "Ticket Created: {$ticketData['ticket_number']} - {$ticketData['title']}";
            
            $message = "Dear {$ticketData['user_first_name']} {$ticketData['user_last_name']},\n\n";
            $message .= "Your support ticket has been created successfully.\n\n";
            $message .= "Ticket Details:\n";
            $message .= "Ticket Number: {$ticketData['ticket_number']}\n";
            $message .= "Title: {$ticketData['title']}\n";
            $message .= "Priority: " . ucfirst($ticketData['priority']) . "\n";
            $message .= "Status: " . ucfirst(str_replace('_', ' ', $ticketData['status'])) . "\n";
            $message .= "Category: {$ticketData['category_name']}\n";
            $message .= "Created: {$ticketData['created_at']}\n\n";
            $message .= "Description:\n{$ticketData['description']}\n\n";
            $message .= "You can track your ticket status at: " . SITE_URL . "/user/ticket_detail.php?id={$ticketId}\n\n";
            $message .= "We will notify you of any updates to your ticket.\n\n";
            $message .= "Best regards,\n";
            $message .= FROM_NAME;
            
            return $this->sendEmail($recipientEmail, $subject, $message);
            
        } catch (Exception $e) {
            $this->error = 'Failed to send confirmation email';
            logError('EmailHandler', 'Exception sending ticket confirmation', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    /**
     * Send status update notification email
     * 
     * @param int $ticketId Ticket ID
     * @param string $recipientEmail Recipient email address
     * @return bool True on success, false on failure
     */
    public function sendStatusUpdate($ticketId, $recipientEmail) {
        try {
            $ticket = new Ticket();
            $ticketData = $ticket->getTicketById($ticketId);
            
            if (!$ticketData) {
                $this->error = 'Ticket not found';
                return false;
            }
            
            $subject = "Ticket Status Updated: {$ticketData['ticket_number']} - {$ticketData['title']}";
            
            $message = "Dear {$ticketData['user_first_name']} {$ticketData['user_last_name']},\n\n";
            $message .= "The status of your support ticket has been updated.\n\n";
            $message .= "Ticket Details:\n";
            $message .= "Ticket Number: {$ticketData['ticket_number']}\n";
            $message .= "Title: {$ticketData['title']}\n";
            $message .= "New Status: " . ucfirst(str_replace('_', ' ', $ticketData['status'])) . "\n";
            $message .= "Priority: " . ucfirst($ticketData['priority']) . "\n";
            
            if (!empty($ticketData['agent_first_name'])) {
                $message .= "Assigned Agent: {$ticketData['agent_first_name']} {$ticketData['agent_last_name']}\n";
            }
            
            $message .= "Last Updated: {$ticketData['updated_at']}\n\n";
            
            if ($ticketData['status'] === 'resolved' && !empty($ticketData['resolution'])) {
                $message .= "Resolution:\n{$ticketData['resolution']}\n\n";
            }
            
            $message .= "View ticket details at: " . SITE_URL . "/user/ticket_detail.php?id={$ticketId}\n\n";
            $message .= "Best regards,\n";
            $message .= FROM_NAME;
            
            return $this->sendEmail($recipientEmail, $subject, $message);
            
        } catch (Exception $e) {
            $this->error = 'Failed to send status update email';
            logError('EmailHandler', 'Exception sending status update', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    /**
     * Send assignment notification email to agent and user
     * 
     * @param int $ticketId Ticket ID
     * @param string $agentEmail Agent email address
     * @param string $userEmail User email address
     * @return bool True on success, false on failure
     */
    public function sendAssignmentNotification($ticketId, $agentEmail, $userEmail) {
        try {
            $ticket = new Ticket();
            $ticketData = $ticket->getTicketById($ticketId);
            
            if (!$ticketData) {
                $this->error = 'Ticket not found';
                return false;
            }
            
            // Send notification to agent
            $agentSubject = "Ticket Assigned: {$ticketData['ticket_number']} - {$ticketData['title']}";
            
            $agentMessage = "Dear {$ticketData['agent_first_name']} {$ticketData['agent_last_name']},\n\n";
            $agentMessage .= "A support ticket has been assigned to you.\n\n";
            $agentMessage .= "Ticket Details:\n";
            $agentMessage .= "Ticket Number: {$ticketData['ticket_number']}\n";
            $agentMessage .= "Title: {$ticketData['title']}\n";
            $agentMessage .= "Priority: " . ucfirst($ticketData['priority']) . "\n";
            $agentMessage .= "Status: " . ucfirst(str_replace('_', ' ', $ticketData['status'])) . "\n";
            $agentMessage .= "Category: {$ticketData['category_name']}\n";
            $agentMessage .= "Requester: {$ticketData['user_first_name']} {$ticketData['user_last_name']} ({$ticketData['user_email']})\n";
            $agentMessage .= "Created: {$ticketData['created_at']}\n\n";
            $agentMessage .= "Description:\n{$ticketData['description']}\n\n";
            $agentMessage .= "View and manage this ticket at: " . SITE_URL . "/agent/ticket_detail.php?id={$ticketId}\n\n";
            $agentMessage .= "Best regards,\n";
            $agentMessage .= FROM_NAME;
            
            $agentSent = $this->sendEmail($agentEmail, $agentSubject, $agentMessage);
            
            // Send notification to user
            $userSubject = "Ticket Assigned: {$ticketData['ticket_number']} - {$ticketData['title']}";
            
            $userMessage = "Dear {$ticketData['user_first_name']} {$ticketData['user_last_name']},\n\n";
            $userMessage .= "Your support ticket has been assigned to an agent.\n\n";
            $userMessage .= "Ticket Details:\n";
            $userMessage .= "Ticket Number: {$ticketData['ticket_number']}\n";
            $userMessage .= "Title: {$ticketData['title']}\n";
            $userMessage .= "Assigned Agent: {$ticketData['agent_first_name']} {$ticketData['agent_last_name']}\n";
            $userMessage .= "Status: " . ucfirst(str_replace('_', ' ', $ticketData['status'])) . "\n\n";
            $userMessage .= "Your ticket is now being handled by our support team.\n\n";
            $userMessage .= "View ticket details at: " . SITE_URL . "/user/ticket_detail.php?id={$ticketId}\n\n";
            $userMessage .= "Best regards,\n";
            $userMessage .= FROM_NAME;
            
            $userSent = $this->sendEmail($userEmail, $userSubject, $userMessage);
            
            return $agentSent && $userSent;
            
        } catch (Exception $e) {
            $this->error = 'Failed to send assignment notification';
            logError('EmailHandler', 'Exception sending assignment notification', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    /**
     * Send comment notification email
     * 
     * @param int $ticketId Ticket ID
     * @param string $recipientEmail Recipient email address
     * @return bool True on success, false on failure
     */
    public function sendCommentNotification($ticketId, $recipientEmail) {
        try {
            $ticket = new Ticket();
            $ticketData = $ticket->getTicketById($ticketId);
            
            if (!$ticketData) {
                $this->error = 'Ticket not found';
                return false;
            }
            
            // Get the latest comment
            $comments = $ticket->getComments($ticketId, false); // Only public comments
            if (empty($comments)) {
                $this->error = 'No comments found';
                return false;
            }
            
            $latestComment = end($comments);
            
            $subject = "New Comment on Ticket: {$ticketData['ticket_number']} - {$ticketData['title']}";
            
            $message = "Dear User,\n\n";
            $message .= "A new comment has been added to your support ticket.\n\n";
            $message .= "Ticket Details:\n";
            $message .= "Ticket Number: {$ticketData['ticket_number']}\n";
            $message .= "Title: {$ticketData['title']}\n";
            $message .= "Status: " . ucfirst(str_replace('_', ' ', $ticketData['status'])) . "\n\n";
            $message .= "Comment by {$latestComment['first_name']} {$latestComment['last_name']}:\n";
            $message .= "{$latestComment['comment']}\n\n";
            $message .= "View full conversation at: " . SITE_URL . "/user/ticket_detail.php?id={$ticketId}\n\n";
            $message .= "Best regards,\n";
            $message .= FROM_NAME;
            
            return $this->sendEmail($recipientEmail, $subject, $message);
            
        } catch (Exception $e) {
            $this->error = 'Failed to send comment notification';
            logError('EmailHandler', 'Exception sending comment notification', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    /**
     * Send resolution notification email
     * 
     * @param int $ticketId Ticket ID
     * @param string $recipientEmail Recipient email address
     * @return bool True on success, false on failure
     */
    public function sendResolutionNotification($ticketId, $recipientEmail) {
        try {
            $ticket = new Ticket();
            $ticketData = $ticket->getTicketById($ticketId);
            
            if (!$ticketData) {
                $this->error = 'Ticket not found';
                return false;
            }
            
            $subject = "Ticket Resolved: {$ticketData['ticket_number']} - {$ticketData['title']}";
            
            $message = "Dear {$ticketData['user_first_name']} {$ticketData['user_last_name']},\n\n";
            $message .= "Your support ticket has been resolved.\n\n";
            $message .= "Ticket Details:\n";
            $message .= "Ticket Number: {$ticketData['ticket_number']}\n";
            $message .= "Title: {$ticketData['title']}\n";
            $message .= "Resolved By: {$ticketData['agent_first_name']} {$ticketData['agent_last_name']}\n";
            $message .= "Resolved At: {$ticketData['resolved_at']}\n\n";
            
            if (!empty($ticketData['resolution'])) {
                $message .= "Resolution:\n{$ticketData['resolution']}\n\n";
            }
            
            $message .= "We hope this resolves your issue. If you need further assistance, please feel free to reopen this ticket or create a new one.\n\n";
            $message .= "Please rate your experience at: " . SITE_URL . "/user/ticket_detail.php?id={$ticketId}\n\n";
            $message .= "Best regards,\n";
            $message .= FROM_NAME;
            
            return $this->sendEmail($recipientEmail, $subject, $message);
            
        } catch (Exception $e) {
            $this->error = 'Failed to send resolution notification';
            logError('EmailHandler', 'Exception sending resolution notification', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    /**
     * Send email using PHP mail() function with SMTP configuration
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email message body
     * @param array $headers Additional headers (optional)
     * @return bool True on success, false on failure
     */
    private function sendEmail($to, $subject, $message, $headers = []) {
        try {
            // Validate email address
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $this->error = 'Invalid recipient email address';
                logError('EmailHandler', 'Invalid email address', ['email' => $to]);
                return false;
            }
            
            // Build email headers
            $defaultHeaders = [
                'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
                'Reply-To: ' . REPLY_TO_EMAIL,
                'X-Mailer: PHP/' . phpversion(),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8'
            ];
            
            $allHeaders = array_merge($defaultHeaders, $headers);
            $headerString = implode("\r\n", $allHeaders);
            
            // Configure SMTP settings for mail() function
            // Note: This requires proper PHP mail configuration in php.ini
            // For production, consider using a library like PHPMailer or SwiftMailer
            ini_set('SMTP', SMTP_HOST);
            ini_set('smtp_port', SMTP_PORT);
            
            // Send email
            $result = mail($to, $subject, $message, $headerString);
            
            if ($result) {
                logError('EmailHandler', 'Email sent successfully', [
                    'to' => $to,
                    'subject' => $subject
                ]);
                return true;
            } else {
                $this->error = 'Failed to send email';
                logError('EmailHandler', 'Failed to send email', [
                    'to' => $to,
                    'subject' => $subject
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            $this->error = 'Exception while sending email';
            logError('EmailHandler', 'Exception sending email', [
                'error' => $e->getMessage(),
                'to' => $to
            ]);
            return false;
        }
    }
    
    /**
     * Parse email content to extract subject, body, sender, and attachments
     * 
     * @param array $emailData Raw email data from IMAP
     * @return array|false Parsed email data on success, false on failure
     */
    public function parseEmail($emailData) {
        try {
            $parsed = [
                'subject' => '',
                'body' => '',
                'sender' => '',
                'sender_name' => '',
                'attachments' => []
            ];
            
            // Extract sender email
            if (isset($emailData['from'])) {
                $from = $emailData['from'];
                if (preg_match('/<(.+?)>/', $from, $matches)) {
                    $parsed['sender'] = $matches[1];
                    $parsed['sender_name'] = trim(str_replace('<' . $matches[1] . '>', '', $from));
                } else {
                    $parsed['sender'] = trim($from);
                }
            }
            
            // Extract subject
            $parsed['subject'] = isset($emailData['subject']) ? $this->decodeEmailHeader($emailData['subject']) : 'No Subject';
            
            // Extract body
            if (isset($emailData['body'])) {
                $parsed['body'] = $this->extractEmailBody($emailData['body']);
            }
            
            // Extract attachments if present
            if (isset($emailData['attachments']) && is_array($emailData['attachments'])) {
                $parsed['attachments'] = $emailData['attachments'];
            }
            
            return $parsed;
            
        } catch (Exception $e) {
            $this->error = 'Failed to parse email';
            logError('EmailHandler', 'Exception parsing email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Decode email header (handles MIME encoding)
     * 
     * @param string $header Email header string
     * @return string Decoded header
     */
    private function decodeEmailHeader($header) {
        $decoded = imap_mime_header_decode($header);
        $result = '';
        
        foreach ($decoded as $part) {
            $charset = ($part->charset === 'default') ? 'UTF-8' : $part->charset;
            $result .= iconv($charset, 'UTF-8//IGNORE', $part->text);
        }
        
        return $result;
    }
    
    /**
     * Extract plain text body from email
     * Strips signatures and quoted replies
     * 
     * @param string $body Email body
     * @return string Cleaned email body
     */
    private function extractEmailBody($body) {
        // Remove HTML tags if present
        $body = strip_tags($body);
        
        // Remove email signatures (common patterns)
        $signaturePatterns = [
            '/--\s*\n.*$/s',  // Standard signature delimiter
            '/_{5,}.*$/s',     // Underscore line
            '/Sent from my .*/i',
            '/Get Outlook for .*/i'
        ];
        
        foreach ($signaturePatterns as $pattern) {
            $body = preg_replace($pattern, '', $body);
        }
        
        // Remove quoted replies (lines starting with >)
        $lines = explode("\n", $body);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            if (!preg_match('/^>/', trim($line))) {
                $cleanedLines[] = $line;
            } else {
                break; // Stop at first quoted line
            }
        }
        
        $body = implode("\n", $cleanedLines);
        
        // Trim excessive whitespace
        $body = trim($body);
        
        return $body;
    }
    
    /**
     * Create ticket from parsed email data
     * 
     * @param array $emailData Parsed email data
     * @return int|false Ticket ID on success, false on failure
     */
    public function createTicketFromEmail($emailData) {
        try {
            // Find or create user
            $userId = $this->findOrCreateUser($emailData['sender'], $emailData['sender_name']);
            
            if (!$userId) {
                $this->error = 'Failed to find or create user';
                return false;
            }
            
            // Create ticket
            $ticket = new Ticket();
            
            // Use default category (first active category or create "Email" category)
            $categoryId = $this->getDefaultEmailCategory();
            
            if (!$categoryId) {
                $this->error = 'No default category available';
                return false;
            }
            
            $ticketId = $ticket->createTicket(
                $userId,
                $emailData['subject'],
                $emailData['body'],
                $categoryId,
                'medium',
                'email'
            );
            
            if (!$ticketId) {
                $this->error = 'Failed to create ticket: ' . $ticket->getError();
                return false;
            }
            
            // Handle attachments if present
            if (!empty($emailData['attachments'])) {
                $this->saveEmailAttachments($ticketId, $emailData['attachments']);
            }
            
            logError('EmailHandler', 'Ticket created from email', [
                'ticket_id' => $ticketId,
                'sender' => $emailData['sender']
            ]);
            
            return $ticketId;
            
        } catch (Exception $e) {
            $this->error = 'Exception creating ticket from email';
            logError('EmailHandler', 'Exception creating ticket from email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Find existing user by email or create new basic user account
     * 
     * @param string $email User email address
     * @param string $name User name (optional)
     * @return int|false User ID on success, false on failure
     */
    public function findOrCreateUser($email, $name = '') {
        try {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error = 'Invalid email address';
                return false;
            }
            
            // Check if user exists
            $existingUser = $this->db->fetchOne(
                "SELECT user_id FROM users WHERE email = ?",
                [$email]
            );
            
            if ($existingUser) {
                return $existingUser['user_id'];
            }
            
            // Parse name into first and last name
            $nameParts = explode(' ', trim($name), 2);
            $firstName = $nameParts[0] ?? 'User';
            $lastName = $nameParts[1] ?? '';
            
            // If no name provided, use email prefix
            if (empty($firstName) || $firstName === 'User') {
                $emailParts = explode('@', $email);
                $firstName = ucfirst($emailParts[0]);
            }
            
            // Create new user with basic account
            $user = new User();
            
            // Generate random password (user will need to reset)
            $randomPassword = bin2hex(random_bytes(16));
            
            $userId = $user->register(
                $email,
                $randomPassword,
                $firstName,
                $lastName,
                'Email User',
                'user'
            );
            
            if ($userId) {
                logError('EmailHandler', 'New user created from email', [
                    'user_id' => $userId,
                    'email' => $email
                ]);
                return $userId;
            }
            
            $this->error = 'Failed to create user: ' . $user->getError();
            return false;
            
        } catch (Exception $e) {
            $this->error = 'Exception finding or creating user';
            logError('EmailHandler', 'Exception finding or creating user', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return false;
        }
    }
    
    /**
     * Get default category for email tickets
     * 
     * @return int|false Category ID on success, false on failure
     */
    private function getDefaultEmailCategory() {
        try {
            // Try to find "Email" or "General" category
            $category = $this->db->fetchOne(
                "SELECT category_id FROM categories 
                 WHERE (name LIKE '%Email%' OR name LIKE '%General%' OR name LIKE '%Other%') 
                 AND is_active = 1 
                 ORDER BY name LIMIT 1",
                []
            );
            
            if ($category) {
                return $category['category_id'];
            }
            
            // If no suitable category found, get first active category
            $firstCategory = $this->db->fetchOne(
                "SELECT category_id FROM categories WHERE is_active = 1 ORDER BY category_id LIMIT 1",
                []
            );
            
            if ($firstCategory) {
                return $firstCategory['category_id'];
            }
            
            return false;
            
        } catch (Exception $e) {
            logError('EmailHandler', 'Exception getting default category', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Save email attachments to ticket
     * 
     * @param int $ticketId Ticket ID
     * @param array $attachments Array of attachment data
     * @return bool True on success, false on failure
     */
    private function saveEmailAttachments($ticketId, $attachments) {
        try {
            $ticket = new Ticket();
            $uploadDir = __DIR__ . '/../uploads/tickets/';
            
            // Create upload directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($attachments as $attachment) {
                // Generate random filename
                $extension = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
                $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
                $filepath = $uploadDir . $newFilename;
                
                // Save attachment file
                if (file_put_contents($filepath, $attachment['content'])) {
                    // Add to database
                    $ticket->addAttachment(
                        $ticketId,
                        $attachment['filename'],
                        'uploads/tickets/' . $newFilename,
                        strlen($attachment['content'])
                    );
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            logError('EmailHandler', 'Exception saving email attachments', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    /**
     * Send auto-reply with ticket number
     * 
     * @param string $recipientEmail Recipient email address
     * @param string $ticketNumber Ticket number
     * @return bool True on success, false on failure
     */
    public function sendAutoReply($recipientEmail, $ticketNumber) {
        try {
            $subject = "Auto-Reply: Your ticket has been received - {$ticketNumber}";
            
            $message = "Dear User,\n\n";
            $message .= "Thank you for contacting " . FROM_NAME . ".\n\n";
            $message .= "We have received your email and created a support ticket for you.\n\n";
            $message .= "Ticket Number: {$ticketNumber}\n\n";
            $message .= "You can track your ticket status at: " . SITE_URL . "\n\n";
            $message .= "Our support team will review your request and respond as soon as possible.\n\n";
            $message .= "Best regards,\n";
            $message .= FROM_NAME;
            
            return $this->sendEmail($recipientEmail, $subject, $message);
            
        } catch (Exception $e) {
            $this->error = 'Failed to send auto-reply';
            logError('EmailHandler', 'Exception sending auto-reply', [
                'error' => $e->getMessage(),
                'recipient' => $recipientEmail
            ]);
            return false;
        }
    }
}
