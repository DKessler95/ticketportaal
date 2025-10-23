<?php
/**
 * AI Configuration
 * Central configuration for AI features
 */

// Enable/disable AI features globally
define('AI_ENABLED', true);

// RAG API Configuration
define('RAG_API_URL', 'http://localhost:5005');
define('RAG_API_TIMEOUT', 30);

// Beta rollout - leave empty array to enable for all users
// Add user IDs to restrict to specific users during testing
define('AI_BETA_USERS', [
    // Example: 1, 2, 3, 5, 8
]);

// Feature flags for different AI components
define('AI_SHOW_SUGGESTIONS', true);      // Show AI suggestions on ticket pages
define('AI_SHOW_SOURCES', true);          // Show source documents
define('AI_SHOW_RELATIONSHIPS', true);    // Show knowledge graph relationships
define('AI_SHOW_CONFIDENCE', true);       // Show confidence scores

// Display settings
define('AI_COMPACT_MODE', false);         // Use compact display mode
define('AI_MAX_SOURCES_DISPLAY', 5);      // Maximum sources to show initially

// Logging
define('AI_LOG_INTERACTIONS', true);      // Log all AI interactions
define('AI_LOG_FILE', __DIR__ . '/../ai_module/logs/ai_interactions.log');

?>
