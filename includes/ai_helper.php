<?php
/**
 * AI Helper Class
 * Provides interface to RAG API for AI-powered ticket assistance
 * 
 * Features:
 * - Health check and availability detection
 * - Get AI suggestions for tickets
 * - Get service statistics
 * - Error handling and graceful degradation
 */

class AIHelper {
    
    private $rag_api_url;
    private $timeout;
    private $enabled;
    private $last_health_check;
    private $health_check_ttl = 300; // 5 minutes
    
    /**
     * Constructor
     * 
     * @param string $rag_api_url RAG API base URL (default: from config)
     * @param int $timeout Request timeout in seconds (default: from config)
     */
    public function __construct($rag_api_url = null, $timeout = null) {
        // Load config if not already loaded
        if (!defined('AI_ENABLED')) {
            require_once __DIR__ . '/../config/ai_config.php';
        }
        
        $this->rag_api_url = rtrim($rag_api_url ?: RAG_API_URL, '/');
        $this->timeout = $timeout ?: RAG_API_TIMEOUT;
        $this->enabled = AI_ENABLED;
        $this->last_health_check = null;
    }
    
    /**
     * Check if AI is enabled and available
     * 
     * @return bool True if AI is enabled and healthy
     */
    public function isEnabled() {
        // Check feature flag
        if (!$this->enabled) {
            return false;
        }
        
        // Check if health check is cached
        if ($this->last_health_check !== null && 
            (time() - $this->last_health_check['timestamp']) < $this->health_check_ttl) {
            return $this->last_health_check['healthy'];
        }
        
        // Perform health check
        $healthy = $this->checkHealth();
        
        // Cache result
        $this->last_health_check = [
            'healthy' => $healthy,
            'timestamp' => time()
        ];
        
        return $healthy;
    }
    
    /**
     * Check RAG API health
     * 
     * @return bool True if API is healthy
     */
    private function checkHealth() {
        try {
            $response = $this->makeRequest('/health', 'GET', null, 5); // 5 second timeout for health check
            
            if ($response && isset($response['status'])) {
                return $response['status'] === 'healthy' || $response['status'] === 'degraded';
            }
            
            return false;
        } catch (Exception $e) {
            error_log("AI Health Check Failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get AI suggestions for a ticket
     * 
     * @param string $query Ticket text (title + description)
     * @param array $options Optional parameters (top_k, include_tickets, include_kb, include_ci)
     * @return array Response with success, ai_answer, sources, etc.
     */
    public function getSuggestions($query, $options = []) {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'AI service is not available',
                'ai_answer' => '',
                'sources' => [],
                'relationships' => [],
                'uncertainties' => [],
                'confidence_score' => 0.0
            ];
        }
        
        // Prepare request data
        $data = [
            'query' => $query,
            'top_k' => isset($options['top_k']) ? $options['top_k'] : 10,
            'include_tickets' => isset($options['include_tickets']) ? $options['include_tickets'] : true,
            'include_kb' => isset($options['include_kb']) ? $options['include_kb'] : true,
            'include_ci' => isset($options['include_ci']) ? $options['include_ci'] : false,
            'use_vector' => isset($options['use_vector']) ? $options['use_vector'] : true,
            'use_bm25' => isset($options['use_bm25']) ? $options['use_bm25'] : true,
            'use_graph' => isset($options['use_graph']) ? $options['use_graph'] : true
        ];
        
        try {
            $response = $this->makeRequest('/rag_query', 'POST', $data);
            
            if ($response && isset($response['success'])) {
                return $response;
            }
            
            return [
                'success' => false,
                'error' => 'Invalid response from AI service',
                'ai_answer' => '',
                'sources' => [],
                'relationships' => [],
                'uncertainties' => [],
                'confidence_score' => 0.0
            ];
            
        } catch (Exception $e) {
            error_log("AI getSuggestions Failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'ai_answer' => '',
                'sources' => [],
                'relationships' => [],
                'uncertainties' => [],
                'confidence_score' => 0.0
            ];
        }
    }
    
    /**
     * Get service statistics
     * 
     * @return array|null Statistics or null on failure
     */
    public function getStats() {
        if (!$this->isEnabled()) {
            return null;
        }
        
        try {
            $response = $this->makeRequest('/stats', 'GET');
            return $response;
        } catch (Exception $e) {
            error_log("AI getStats Failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get detailed health status
     * 
     * @return array|null Health status or null on failure
     */
    public function getHealthStatus() {
        try {
            $response = $this->makeRequest('/health', 'GET', null, 5);
            return $response;
        } catch (Exception $e) {
            error_log("AI getHealthStatus Failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Make HTTP request to RAG API
     * 
     * @param string $endpoint API endpoint (e.g., '/rag_query')
     * @param string $method HTTP method (GET or POST)
     * @param array|null $data Request data for POST requests
     * @param int|null $custom_timeout Custom timeout for this request
     * @return array|null Response data or null on failure
     * @throws Exception On request failure
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $custom_timeout = null) {
        $url = $this->rag_api_url . $endpoint;
        $timeout = $custom_timeout !== null ? $custom_timeout : $this->timeout;
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set common options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json'
            ]);
        }
        
        // Execute request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle errors
        if ($response === false) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($http_code === 429) {
            throw new Exception("Rate limit exceeded. Please try again later.");
        }
        
        if ($http_code === 503) {
            throw new Exception("AI service is under heavy load. Please try again later.");
        }
        
        if ($http_code !== 200) {
            throw new Exception("HTTP Error: " . $http_code);
        }
        
        // Decode JSON response
        $decoded = json_decode($response, true);
        
        if ($decoded === null) {
            throw new Exception("Invalid JSON response");
        }
        
        return $decoded;
    }
    
    /**
     * Format ticket text for AI query
     * 
     * @param array $ticket Ticket data array
     * @return string Formatted query text
     */
    public function formatTicketQuery($ticket) {
        $query = "";
        
        // Add title
        if (isset($ticket['title']) && !empty($ticket['title'])) {
            $query .= $ticket['title'] . "\n\n";
        }
        
        // Add description
        if (isset($ticket['description']) && !empty($ticket['description'])) {
            $query .= $ticket['description'];
        }
        
        return trim($query);
    }
    
    /**
     * Check if user is in beta group (for staged rollout)
     * 
     * @param int $user_id User ID
     * @return bool True if user is in beta group
     */
    public function isUserInBeta($user_id) {
        // Load config if not already loaded
        if (!defined('AI_BETA_USERS')) {
            require_once __DIR__ . '/../config/ai_config.php';
        }
        
        // If beta list is empty, allow all users
        if (empty(AI_BETA_USERS)) {
            return true;
        }
        
        return in_array($user_id, AI_BETA_USERS);
    }
    
    /**
     * Log AI interaction for analytics
     * 
     * @param int $ticket_id Ticket ID
     * @param int $user_id User ID
     * @param bool $success Whether the request was successful
     * @param float $response_time Response time in seconds
     */
    public function logInteraction($ticket_id, $user_id, $success, $response_time) {
        // Load config if not already loaded
        if (!defined('AI_LOG_INTERACTIONS')) {
            require_once __DIR__ . '/../config/ai_config.php';
        }
        
        if (!AI_LOG_INTERACTIONS) {
            return;
        }
        
        $log_entry = sprintf(
            "[%s] Ticket: %d, User: %d, Success: %s, Time: %.2fs\n",
            date('Y-m-d H:i:s'),
            $ticket_id,
            $user_id,
            $success ? 'Yes' : 'No',
            $response_time
        );
        
        $log_file = defined('AI_LOG_FILE') ? AI_LOG_FILE : __DIR__ . '/../ai_module/logs/ai_interactions.log';
        
        // Ensure directory exists
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        error_log($log_entry, 3, $log_file);
    }
}

?>
