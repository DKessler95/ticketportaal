<?php
/**
 * AI Suggestion Widget
 * Displays AI-powered suggestions for tickets
 * 
 * Required variables:
 * - $ai_response: Response from AIHelper->getSuggestions()
 * 
 * Optional variables:
 * - $show_sources: Show source documents (default: true)
 * - $show_relationships: Show relationship chains (default: true)
 * - $compact_mode: Use compact display (default: false)
 */

if (!isset($ai_response) || !$ai_response['success']) {
    // Don't display widget if no valid response
    return;
}

$show_sources = isset($show_sources) ? $show_sources : true;
$show_relationships = isset($show_relationships) ? $show_relationships : true;
$compact_mode = isset($compact_mode) ? $compact_mode : false;

?>

<div class="ai-suggestion-widget" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0;">
    
    <!-- Header -->
    <div class="ai-header" style="display: flex; align-items: center; margin-bottom: 15px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="margin-right: 10px;">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#0066cc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 17L12 22L22 17" stroke="#0066cc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="#0066cc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3 style="margin: 0; color: #0066cc; font-size: 18px;">AI Suggesties</h3>
        
        <?php if (isset($ai_response['confidence_score'])): ?>
        <span class="confidence-badge" style="margin-left: auto; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;
            <?php 
            $confidence = $ai_response['confidence_score'];
            if ($confidence >= 0.8) {
                echo 'background: #d4edda; color: #155724;';
            } elseif ($confidence >= 0.6) {
                echo 'background: #fff3cd; color: #856404;';
            } else {
                echo 'background: #f8d7da; color: #721c24;';
            }
            ?>">
            <?php 
            if ($confidence >= 0.8) {
                echo '✓ Hoog vertrouwen';
            } elseif ($confidence >= 0.6) {
                echo '⚠ Gemiddeld vertrouwen';
            } else {
                echo '⚠ Laag vertrouwen';
            }
            ?>
            (<?php echo round($confidence * 100); ?>%)
        </span>
        <?php endif; ?>
    </div>
    
    <!-- AI Answer -->
    <div class="ai-answer" style="background: white; border-left: 4px solid #0066cc; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
        <?php 
        // Format answer with proper line breaks and styling
        $answer = $ai_response['ai_answer'];
        $answer = nl2br(htmlspecialchars($answer));
        
        // Make bold text work
        $answer = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $answer);
        
        echo $answer;
        ?>
    </div>
    
    <!-- Uncertainties Warning -->
    <?php if (!empty($ai_response['uncertainties'])): ?>
    <div class="ai-uncertainties" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
        <strong style="color: #856404;">⚠ Let op:</strong>
        <ul style="margin: 8px 0 0 0; padding-left: 20px; color: #856404;">
            <?php foreach ($ai_response['uncertainties'] as $uncertainty): ?>
            <li><?php echo htmlspecialchars($uncertainty); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Similar Tickets -->
    <?php if ($show_sources && !empty($ai_response['sources'])): ?>
    <div class="ai-sources" style="margin-top: 20px;">
        <h4 style="font-size: 14px; color: #495057; margin-bottom: 10px; font-weight: 600;">
            📋 Vergelijkbare Tickets & Bronnen
        </h4>
        
        <?php if ($compact_mode): ?>
            <!-- Compact mode: Show only count and top 3 -->
            <div style="font-size: 13px; color: #6c757d; margin-bottom: 8px;">
                <?php echo count($ai_response['sources']); ?> relevante bronnen gevonden
            </div>
            <?php $sources_to_show = array_slice($ai_response['sources'], 0, 3); ?>
        <?php else: ?>
            <?php $sources_to_show = array_slice($ai_response['sources'], 0, 5); ?>
        <?php endif; ?>
        
        <div class="sources-list">
            <?php foreach ($sources_to_show as $index => $source): ?>
            <div class="source-item" style="background: white; border: 1px solid #dee2e6; border-radius: 4px; padding: 12px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <?php if (!empty($source['ticket_number'])): ?>
                        <a href="ticket_detail.php?id=<?php echo urlencode($source['ticket_number']); ?>" 
                           style="color: #0066cc; text-decoration: none; font-weight: 600; font-size: 14px;">
                            <?php echo htmlspecialchars($source['ticket_number']); ?>: 
                            <?php echo htmlspecialchars($source['title']); ?>
                        </a>
                        <?php else: ?>
                        <span style="font-weight: 600; font-size: 14px; color: #495057;">
                            <?php echo htmlspecialchars($source['title']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($source['category'])): ?>
                        <span style="display: inline-block; background: #e9ecef; color: #495057; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 8px;">
                            <?php echo htmlspecialchars($source['category']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!$compact_mode && !empty($source['content'])): ?>
                        <div style="color: #6c757d; font-size: 13px; margin-top: 6px; line-height: 1.4;">
                            <?php 
                            $content = htmlspecialchars($source['content']);
                            echo mb_substr($content, 0, 150) . (mb_strlen($content) > 150 ? '...' : '');
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-left: 12px;">
                        <span style="background: #e7f3ff; color: #0066cc; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; white-space: nowrap;">
                            <?php echo round($source['score'] * 100); ?>% match
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($ai_response['sources']) > count($sources_to_show)): ?>
        <div style="text-align: center; margin-top: 10px;">
            <button onclick="toggleAllSources()" style="background: none; border: 1px solid #0066cc; color: #0066cc; padding: 6px 16px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                Toon alle <?php echo count($ai_response['sources']); ?> bronnen
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Relationship Chains -->
    <?php if ($show_relationships && !empty($ai_response['relationships']) && !$compact_mode): ?>
    <div class="ai-relationships" style="margin-top: 20px;">
        <h4 style="font-size: 14px; color: #495057; margin-bottom: 10px; font-weight: 600;">
            🔗 Relaties in Kennisbank
        </h4>
        
        <div class="relationships-list">
            <?php foreach (array_slice($ai_response['relationships'], 0, 5) as $rel): ?>
            <div style="background: white; border-left: 3px solid #6c757d; padding: 8px 12px; margin-bottom: 6px; font-size: 13px; color: #495057;">
                <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                    <?php echo htmlspecialchars($rel['source']); ?>
                </code>
                <span style="color: #6c757d; margin: 0 6px;">→</span>
                <span style="font-weight: 600; color: #0066cc;">
                    <?php echo htmlspecialchars($rel['relationship']); ?>
                </span>
                <span style="color: #6c757d; margin: 0 6px;">→</span>
                <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                    <?php echo htmlspecialchars($rel['target']); ?>
                </code>
                <span style="color: #6c757d; font-size: 11px; margin-left: 8px;">
                    (<?php echo round($rel['confidence'] * 100); ?>%)
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="ai-footer" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; text-align: center;">
        <span>🤖 Gegenereerd door AI op basis van <?php echo count($ai_response['sources']); ?> bronnen</span>
        <?php if (isset($ai_response['response_time'])): ?>
        <span style="margin-left: 15px;">⚡ <?php echo round($ai_response['response_time'], 2); ?>s</span>
        <?php endif; ?>
        <span style="margin-left: 15px;">
            <a href="#" onclick="provideFeedback('helpful'); return false;" style="color: #28a745; text-decoration: none;">👍 Nuttig</a>
            <span style="margin: 0 8px;">|</span>
            <a href="#" onclick="provideFeedback('not_helpful'); return false;" style="color: #dc3545; text-decoration: none;">👎 Niet nuttig</a>
        </span>
    </div>
</div>

<script>
// Toggle all sources visibility
function toggleAllSources() {
    // This would expand to show all sources
    alert('Functionaliteit om alle bronnen te tonen kan hier worden geïmplementeerd');
}

// Provide feedback on AI suggestion
function provideFeedback(type) {
    // Send feedback to server for analytics
    console.log('Feedback:', type);
    
    // Show thank you message
    if (type === 'helpful') {
        alert('Bedankt voor je feedback! Dit helpt ons de AI te verbeteren.');
    } else {
        alert('Bedankt voor je feedback. We zullen de AI blijven verbeteren.');
    }
    
    // Here you could send an AJAX request to log the feedback
    // fetch('/api/ai_feedback.php', {
    //     method: 'POST',
    //     body: JSON.stringify({ type: type, ticket_id: <?php echo isset($ticket_id) ? $ticket_id : 0; ?> })
    // });
}
</script>

<style>
.ai-suggestion-widget a:hover {
    text-decoration: underline;
}

.ai-suggestion-widget button:hover {
    background: #0066cc !important;
    color: white !important;
}

.source-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}
</style>

<?php
// End of widget
?>
