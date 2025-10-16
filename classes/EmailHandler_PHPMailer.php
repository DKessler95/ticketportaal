<?php
/**
 * Email Handler with PHPMailer
 * 
 * Handles email sending using PHPMailer for proper SMTP support
 */

// Load PHPMailer
require_once __DIR__ . '/../includes/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHandler {
    private $mailer;
    private $error = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    /**
     * Configure SMTP settings
     */
    private function configureSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = SMTP_AUTH;
            $this->mailer->Username = SMTP_USER;
            $this->mailer->Password = SMTP_PASS;
            $this->mailer->SMTPSecure = SMTP_SECURE; // 'tls' or 'ssl'
            $this->mailer->Port = SMTP_PORT;
            
            // Sender info
            $this->mailer->setFrom(FROM_EMAIL, FROM_NAME);
            
            // Character set
            $this->mailer->CharSet = 'UTF-8';
            
            // Debug (disable in production)
            $this->mailer->SMTPDebug = 0; // 0 = off, 2 = verbose
            
        } catch (Exception $e) {
            logError('EmailHandler', 'SMTP configuration failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Plain text alternative
     * @return bool True on success, false on failure
     */
    public function sendEmail($to, $subject, $body, $altBody = '') {
        try {
            // Check if email notifications are enabled
            if (!defined('ENABLE_EMAIL_NOTIFICATIONS') || !ENABLE_EMAIL_NOTIFICATIONS) {
                logError('EmailHandler', 'Email notifications disabled');
                return false;
            }
            
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags($body);
            
            // Send
            $result = $this->mailer->send();
            
            if ($result) {
                logError('EmailHandler', 'Email sent successfully', [
                    'to' => $to,
                    'subject' => $subject
                ]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            logError('EmailHandler', 'Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Send ticket creation notification
     * 
     * @param int $ticketId Ticket ID
     * @return bool
     */
    public function sendTicketCreationEmail($ticketId) {
        // Implementation here - fetch ticket details and send
        return true;
    }
    
    /**
     * Send ticket assignment notification
     * 
     * @param int $ticketId Ticket ID
     * @return bool
     */
    public function sendAssignmentEmail($ticketId) {
        // Implementation here
        return true;
    }
    
    /**
     * Send ticket status update notification
     * 
     * @param int $ticketId Ticket ID
     * @param string $newStatus New status
     * @return bool
     */
    public function sendStatusUpdateEmail($ticketId, $newStatus) {
        // Implementation here
        return true;
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
     * Test SMTP connection
     * 
     * @return bool
     */
    public function testConnection() {
        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}
