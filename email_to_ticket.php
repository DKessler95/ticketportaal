<?php
/**
 * Email to Ticket Processor
 * 
 * Cron script that connects to the email server, processes unread emails,
 * creates tickets, and sends auto-replies
 * 
 * Usage: Run this script via cron every 5 minutes
 * Example cron entry:
 * */5 * * * * /usr/bin/php /path/to/ticketportaal/email_to_ticket.php >> /path/to/logs/cron.log 2>&1
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/classes/EmailHandler.php';
require_once __DIR__ . '/includes/functions.php';

// Check if email processing is enabled
if (!defined('EMAIL_PROCESSING_ENABLED') || !EMAIL_PROCESSING_ENABLED) {
    logError('EmailProcessor', 'Email processing is disabled');
    exit(0);
}

// Log script start
logError('EmailProcessor', 'Email processing started');

try {
    // Connect to IMAP mailbox
    $mailbox = @imap_open(IMAP_HOST, IMAP_USER, IMAP_PASS);
    
    if (!$mailbox) {
        $error = imap_last_error();
        logError('EmailProcessor', 'Failed to connect to mailbox', ['error' => $error]);
        exit(1);
    }
    
    logError('EmailProcessor', 'Connected to mailbox successfully');
    
    // Search for unread emails
    $emails = imap_search($mailbox, 'UNSEEN');
    
    if (!$emails) {
        logError('EmailProcessor', 'No unread emails found');
        imap_close($mailbox);
        exit(0);
    }
    
    $processedCount = 0;
    $errorCount = 0;
    
    // Process each email
    foreach ($emails as $emailNumber) {
        try {
            logError('EmailProcessor', 'Processing email', ['email_number' => $emailNumber]);
            
            // Fetch email structure and headers
            $header = imap_headerinfo($mailbox, $emailNumber);
            $structure = imap_fetchstructure($mailbox, $emailNumber);
            
            // Extract email data
            $emailData = [
                'from' => isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '',
                'subject' => isset($header->subject) ? $header->subject : 'No Subject',
                'body' => getEmailBody($mailbox, $emailNumber, $structure),
                'attachments' => getEmailAttachments($mailbox, $emailNumber, $structure)
            ];
            
            // Parse sender name
            if (isset($header->from[0]->personal)) {
                $senderName = imap_mime_header_decode($header->from[0]->personal);
                $emailData['sender_name'] = '';
                foreach ($senderName as $part) {
                    $charset = ($part->charset === 'default') ? 'UTF-8' : $part->charset;
                    $emailData['sender_name'] .= iconv($charset, 'UTF-8//IGNORE', $part->text);
                }
            } else {
                $emailData['sender_name'] = '';
            }
            
            // Initialize EmailHandler
            $emailHandler = new EmailHandler();
            
            // Parse email
            $parsedEmail = $emailHandler->parseEmail($emailData);
            
            if (!$parsedEmail) {
                logError('EmailProcessor', 'Failed to parse email', [
                    'email_number' => $emailNumber,
                    'error' => $emailHandler->getError()
                ]);
                $errorCount++;
                continue;
            }
            
            // Create ticket from email
            $ticketId = $emailHandler->createTicketFromEmail($parsedEmail);
            
            if (!$ticketId) {
                logError('EmailProcessor', 'Failed to create ticket from email', [
                    'email_number' => $emailNumber,
                    'sender' => $parsedEmail['sender'],
                    'error' => $emailHandler->getError()
                ]);
                $errorCount++;
                continue;
            }
            
            // Get ticket number for auto-reply
            $ticket = new Ticket();
            $ticketData = $ticket->getTicketById($ticketId);
            
            if ($ticketData) {
                // Send auto-reply with ticket number
                $emailHandler->sendAutoReply($parsedEmail['sender'], $ticketData['ticket_number']);
                
                logError('EmailProcessor', 'Ticket created successfully from email', [
                    'ticket_id' => $ticketId,
                    'ticket_number' => $ticketData['ticket_number'],
                    'sender' => $parsedEmail['sender']
                ]);
            }
            
            // Mark email as read if configured
            if (defined('EMAIL_MARK_AS_READ') && EMAIL_MARK_AS_READ) {
                imap_setflag_full($mailbox, $emailNumber, "\\Seen");
            }
            
            $processedCount++;
            
        } catch (Exception $e) {
            logError('EmailProcessor', 'Exception processing email', [
                'email_number' => $emailNumber,
                'error' => $e->getMessage()
            ]);
            $errorCount++;
        }
    }
    
    // Close mailbox connection
    imap_close($mailbox);
    
    logError('EmailProcessor', 'Email processing completed', [
        'processed' => $processedCount,
        'errors' => $errorCount,
        'total' => count($emails)
    ]);
    
} catch (Exception $e) {
    logError('EmailProcessor', 'Fatal exception in email processor', [
        'error' => $e->getMessage()
    ]);
    exit(1);
}

/**
 * Extract email body from IMAP message
 * 
 * @param resource $mailbox IMAP mailbox resource
 * @param int $emailNumber Email number
 * @param object $structure Email structure
 * @return string Email body
 */
function getEmailBody($mailbox, $emailNumber, $structure) {
    $body = '';
    
    // Check if email has parts (multipart)
    if (isset($structure->parts) && count($structure->parts)) {
        // Multipart email
        foreach ($structure->parts as $partNum => $part) {
            // Look for text/plain part
            if ($part->subtype === 'PLAIN') {
                $body = imap_fetchbody($mailbox, $emailNumber, $partNum + 1);
                
                // Decode based on encoding
                if ($part->encoding == 3) { // Base64
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) { // Quoted-printable
                    $body = quoted_printable_decode($body);
                }
                
                break;
            }
        }
        
        // If no plain text found, try HTML
        if (empty($body)) {
            foreach ($structure->parts as $partNum => $part) {
                if ($part->subtype === 'HTML') {
                    $body = imap_fetchbody($mailbox, $emailNumber, $partNum + 1);
                    
                    // Decode based on encoding
                    if ($part->encoding == 3) { // Base64
                        $body = base64_decode($body);
                    } elseif ($part->encoding == 4) { // Quoted-printable
                        $body = quoted_printable_decode($body);
                    }
                    
                    // Strip HTML tags
                    $body = strip_tags($body);
                    break;
                }
            }
        }
    } else {
        // Simple email (no parts)
        $body = imap_body($mailbox, $emailNumber);
        
        // Decode based on encoding
        if ($structure->encoding == 3) { // Base64
            $body = base64_decode($body);
        } elseif ($structure->encoding == 4) { // Quoted-printable
            $body = quoted_printable_decode($body);
        }
    }
    
    return $body;
}

/**
 * Extract email attachments from IMAP message
 * 
 * @param resource $mailbox IMAP mailbox resource
 * @param int $emailNumber Email number
 * @param object $structure Email structure
 * @return array Array of attachments
 */
function getEmailAttachments($mailbox, $emailNumber, $structure) {
    $attachments = [];
    
    // Check if email has parts
    if (!isset($structure->parts) || !count($structure->parts)) {
        return $attachments;
    }
    
    foreach ($structure->parts as $partNum => $part) {
        // Check if part is an attachment
        $isAttachment = false;
        $filename = '';
        
        if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
            $isAttachment = true;
        }
        
        // Get filename
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    $filename = $param->value;
                    $isAttachment = true;
                    break;
                }
            }
        }
        
        if (!$isAttachment && isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    $filename = $param->value;
                    $isAttachment = true;
                    break;
                }
            }
        }
        
        if ($isAttachment && !empty($filename)) {
            // Fetch attachment content
            $content = imap_fetchbody($mailbox, $emailNumber, $partNum + 1);
            
            // Decode based on encoding
            if ($part->encoding == 3) { // Base64
                $content = base64_decode($content);
            } elseif ($part->encoding == 4) { // Quoted-printable
                $content = quoted_printable_decode($content);
            }
            
            // Validate file type and size
            $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'zip'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if (in_array($extension, $allowedExtensions) && strlen($content) <= $maxSize) {
                $attachments[] = [
                    'filename' => $filename,
                    'content' => $content
                ];
            } else {
                logError('EmailProcessor', 'Attachment skipped (invalid type or too large)', [
                    'filename' => $filename,
                    'size' => strlen($content)
                ]);
            }
        }
    }
    
    return $attachments;
}

exit(0);
