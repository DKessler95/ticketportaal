<?php
/**
 * Floating AI Chat Widget
 * A persistent, floating chatbot that works across all pages
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only show if user is logged in
if (!isset($_SESSION['user_id'])) {
    return;
}

$userRole = $_SESSION['role'] ?? 'user';
$userName = $_SESSION['full_name'] ?? 'Gebruiker';
$widgetId = 'ai-widget-' . uniqid(); // Unique ID to prevent conflicts
?>

<!-- AI Chat Widget -->
<div id="<?php echo $widgetId; ?>" class="ai-chat-widget-container" style="position: fixed !important; bottom: 20px !important; right: 20px !important; z-index: 999999 !important;">
    <!-- Floating Button -->
    <div id="ai-chat-toggle-btn" class="ai-chat-toggle-btn" style="width: 60px !important; height: 60px !important; border-radius: 50% !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; display: flex !important; align-items: center !important; justify-content: center !important; cursor: pointer !important; box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;">
        <span class="ai-chat-icon" style="font-size: 28px !important;">🤖</span>
    </div>
    
    <!-- Chat Window -->
    <div id="ai-chat-window-panel" class="ai-chat-window-panel" style="display: none;">
        <!-- Header -->
        <div class="ai-chat-header-bar">
            <div class="ai-chat-title">
                <span class="ai-chat-icon">🤖</span>
                <div>
                    <strong>AI Assistent</strong>
                    <br><small>K&K Ticketportaal</small>
                </div>
            </div>
            <div class="ai-chat-controls">
                <button type="button" class="ai-chat-control-btn" id="ai-chat-clear-btn" title="Wis geschiedenis">🗑️</button>
                <button type="button" class="ai-chat-control-btn" id="ai-chat-minimize-btn" title="Minimaliseer">➖</button>
                <button type="button" class="ai-chat-control-btn" id="ai-chat-close-btn" title="Sluit">✖️</button>
            </div>
        </div>
        
        <!-- Messages Container -->
        <div class="ai-chat-messages-area" id="ai-chat-messages-area">
            <!-- Welcome Message -->
            <div class="ai-msg-wrapper">
                <div class="ai-msg ai-msg-bot">
                    <div class="ai-msg-avatar">🤖</div>
                    <div class="ai-msg-content">
                        <strong>AI Assistent</strong>
                        <p>Hallo <?php echo htmlspecialchars($userName); ?>! 👋</p>
                        <p>Ik kan je helpen met vragen over K&K systemen, procedures en tickets.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="ai-chat-input-wrapper">
            <form id="ai-chat-input-form">
                <div class="ai-chat-input-group">
                    <input type="text" 
                           id="ai-chat-input-field" 
                           class="ai-chat-input-field" 
                           placeholder="Stel je vraag..." 
                           autocomplete="off">
                    <button type="submit" class="ai-chat-send-btn" id="ai-chat-send-btn">📤</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Container */
.ai-chat-widget-container {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    left: auto !important;
    top: auto !important;
    z-index: 999999 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Toggle Button */
.ai-chat-toggle-btn {
    width: 60px !important;
    height: 60px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
    transition: transform 0.2s !important;
    user-select: none !important;
}

.ai-chat-toggle-btn:hover {
    transform: scale(1.1) !important;
}

.ai-chat-toggle-btn:active {
    transform: scale(0.95) !important;
}

.ai-chat-icon {
    font-size: 28px !important;
    line-height: 1 !important;
}

/* Chat Window */
.ai-chat-window-panel {
    position: absolute !important;
    bottom: 70px !important;
    right: 0 !important;
    width: 400px !important;
    height: 600px !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

/* Header */
.ai-chat-header-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 16px !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.ai-chat-title {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}

.ai-chat-title small {
    color: rgba(255,255,255,0.8) !important;
    font-size: 12px !important;
}

.ai-chat-controls {
    display: flex !important;
    gap: 8px !important;
}

.ai-chat-control-btn {
    background: rgba(255,255,255,0.2) !important;
    border: none !important;
    color: white !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    font-size: 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: background 0.2s !important;
}

.ai-chat-control-btn:hover {
    background: rgba(255,255,255,0.3) !important;
}

/* Messages */
.ai-chat-messages-area {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 16px !important;
    background: #f8f9fa !important;
}

.ai-msg-wrapper {
    margin-bottom: 16px !important;
}

.ai-msg {
    display: flex !important;
    gap: 12px !important;
}

.ai-msg-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 20px !important;
    flex-shrink: 0 !important;
}

.ai-msg-user .ai-msg-avatar {
    background: #28a745 !important;
}

.ai-msg-content {
    flex: 1 !important;
    background: white !important;
    padding: 12px !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.ai-msg-user .ai-msg-content {
    background: #28a745 !important;
    color: white !important;
}

.ai-msg-content p {
    margin: 0 !important;
    line-height: 1.5 !important;
}

.ai-msg-content p + p {
    margin-top: 8px !important;
}

/* Input */
.ai-chat-input-wrapper {
    padding: 16px !important;
    background: white !important;
    border-top: 1px solid #dee2e6 !important;
}

.ai-chat-input-group {
    display: flex !important;
    gap: 8px !important;
}

.ai-chat-input-field {
    flex: 1 !important;
    padding: 10px 12px !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    outline: none !important;
}

.ai-chat-input-field:focus {
    border-color: #667eea !important;
}

.ai-chat-send-btn {
    width: 40px !important;
    height: 40px !important;
    background: #667eea !important;
    border: none !important;
    border-radius: 8px !important;
    color: white !important;
    font-size: 20px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: background 0.2s !important;
}

.ai-chat-send-btn:hover {
    background: #5568d3 !important;
}

/* Scrollbar */
.ai-chat-messages-area::-webkit-scrollbar {
    width: 6px !important;
}

.ai-chat-messages-area::-webkit-scrollbar-track {
    background: #f1f1f1 !important;
}

.ai-chat-messages-area::-webkit-scrollbar-thumb {
    background: #888 !important;
    border-radius: 3px !important;
}

/* Typing Indicator */
.ai-typing-indicator {
    display: flex !important;
    gap: 4px !important;
    padding: 8px !important;
}

.ai-typing-dot {
    width: 8px !important;
    height: 8px !important;
    background: #6c757d !important;
    border-radius: 50% !important;
    animation: typing 1.4s infinite !important;
}

.ai-typing-dot:nth-child(2) { animation-delay: 0.2s !important; }
.ai-typing-dot:nth-child(3) { animation-delay: 0.4s !important; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-10px); }
}

/* Responsive */
@media (max-width: 768px) {
    .ai-chat-window-panel {
        width: calc(100vw - 40px) !important;
        height: calc(100vh - 120px) !important;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    console.log('🤖 AI Chat Widget initializing...');
    
    // Wait for DOM
    function init() {
        var toggleBtn = document.getElementById('ai-chat-toggle-btn');
        var chatWindow = document.getElementById('ai-chat-window-panel');
        var closeBtn = document.getElementById('ai-chat-close-btn');
        var minimizeBtn = document.getElementById('ai-chat-minimize-btn');
        var clearBtn = document.getElementById('ai-chat-clear-btn');
        var chatForm = document.getElementById('ai-chat-input-form');
        var chatInput = document.getElementById('ai-chat-input-field');
        var chatMessages = document.getElementById('ai-chat-messages-area');
        
        console.log('🔍 Elements:', {
            toggleBtn: !!toggleBtn,
            chatWindow: !!chatWindow,
            closeBtn: !!closeBtn,
            minimizeBtn: !!minimizeBtn,
            clearBtn: !!clearBtn
        });
        
        if (!toggleBtn || !chatWindow) {
            console.error('❌ Required elements not found!');
            return;
        }
        
        var isOpen = false;
        
        // Toggle button click
        toggleBtn.addEventListener('click', function(e) {
            console.log('🖱️ Toggle button clicked! isOpen:', isOpen);
            e.stopPropagation();
            if (isOpen) {
                chatWindow.style.cssText = 'display: none !important;';
                isOpen = false;
                localStorage.setItem('aiChatOpen', 'false');
                console.log('✅ Chat closed');
            } else {
                chatWindow.style.cssText = 'display: flex !important; position: absolute !important; bottom: 70px !important; right: 0 !important; width: 400px !important; height: 600px !important; background: white !important; border-radius: 16px !important; box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important; flex-direction: column !important; overflow: hidden !important;';
                isOpen = true;
                localStorage.setItem('aiChatOpen', 'true');
                console.log('✅ Chat opened');
                setTimeout(function() {
                    if (chatInput) chatInput.focus();
                }, 100);
            }
        });
        
        // Close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                console.log('🖱️ Close button clicked');
                e.stopPropagation();
                chatWindow.style.cssText = 'display: none !important;';
                isOpen = false;
                localStorage.setItem('aiChatOpen', 'false');
                console.log('✅ Chat closed via close button');
            });
        }
        
        // Minimize button
        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', function(e) {
                console.log('🖱️ Minimize button clicked');
                e.stopPropagation();
                chatWindow.style.cssText = 'display: none !important;';
                isOpen = false;
                localStorage.setItem('aiChatOpen', 'false');
                console.log('✅ Chat closed via minimize button');
            });
        }
        
        // Clear button
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                console.log('🖱️ Clear button clicked');
                e.stopPropagation();
                if (confirm('Chat geschiedenis wissen?')) {
                    localStorage.removeItem('aiChatHistory');
                    location.reload();
                }
            });
        }
        
        // Form submission
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var message = chatInput.value.trim();
                if (!message) return;
                
                console.log('📤 Sending message:', message);
                
                // Add user message
                addUserMessage(message);
                chatInput.value = '';
                
                // Show typing indicator
                addTypingIndicator();
                
                // Send to API
                fetch('<?php echo SITE_URL; ?>/api/ai_chat_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    credentials: 'same-origin',
                    body: new URLSearchParams({
                        query: message,
                        search_tickets: '<?php echo $userRole !== "user" ? "true" : "false"; ?>',
                        search_kb: 'true',
                        search_ci: 'true'
                    })
                })
                .then(function(response) { 
                    console.log('📥 Response status:', response.status);
                    // Clone response to read it twice
                    return response.clone().text().then(function(text) {
                        console.log('📥 Raw response:', text.substring(0, 500));
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('❌ JSON parse error:', e);
                            console.error('❌ Response was:', text);
                            throw new Error('Server returned invalid JSON (mogelijk een PHP error)');
                        }
                    });
                })
                .then(function(data) {
                    console.log('📥 Response data:', data);
                    removeTypingIndicator();
                    if (data && data.success) {
                        if (data.data && data.data.ai_answer) {
                            addBotMessage(data.data.ai_answer);
                        } else {
                            console.error('❌ Missing ai_answer in response:', data);
                            addErrorMessage('De AI service gaf een onvolledig antwoord terug');
                        }
                    } else {
                        console.error('❌ API Error:', data);
                        var errorMsg = 'Er is een fout opgetreden bij het verwerken van je vraag';
                        if (data && data.error) {
                            errorMsg = data.error;
                        }
                        addErrorMessage(errorMsg);
                    }
                })
                .catch(function(error) {
                    console.error('❌ Fetch error:', error);
                    removeTypingIndicator();
                    addErrorMessage('Kan geen verbinding maken met de AI service. Probeer het later opnieuw.');
                });
            });
        }
        
        function addUserMessage(text) {
            var html = '<div class="ai-msg-wrapper"><div class="ai-msg ai-msg-user">' +
                '<div class="ai-msg-content"><p>' + escapeHtml(text) + '</p></div>' +
                '<div class="ai-msg-avatar">👤</div></div></div>';
            chatMessages.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }
        
        function addBotMessage(text) {
            var html = '<div class="ai-msg-wrapper"><div class="ai-msg ai-msg-bot">' +
                '<div class="ai-msg-avatar">🤖</div>' +
                '<div class="ai-msg-content"><strong>AI Assistent</strong><p>' + formatText(text) + '</p></div>' +
                '</div></div>';
            chatMessages.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }
        
        function addTypingIndicator() {
            var html = '<div class="ai-msg-wrapper typing-indicator-wrapper"><div class="ai-msg ai-msg-bot">' +
                '<div class="ai-msg-avatar">🤖</div>' +
                '<div class="ai-msg-content"><div class="ai-typing-indicator">' +
                '<div class="ai-typing-dot"></div><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div>' +
                '</div></div></div></div>';
            chatMessages.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }
        
        function removeTypingIndicator() {
            var indicator = chatMessages.querySelector('.typing-indicator-wrapper');
            if (indicator) indicator.remove();
        }
        
        function addErrorMessage(text) {
            console.log('⚠️ Adding error message:', text);
            var html = '<div class="ai-msg-wrapper"><div class="ai-msg ai-msg-bot">' +
                '<div class="ai-msg-avatar">⚠️</div>' +
                '<div class="ai-msg-content" style="background: #fff3cd !important; border-left: 4px solid #ffc107 !important;"><strong style="color: #856404;">Fout</strong><p style="color: #856404;">' + escapeHtml(text) + '</p></div>' +
                '</div></div>';
            chatMessages.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }
        
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatText(text) {
            text = text.replace(/\n/g, '<br>');
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Convert ticket numbers to clickable links (opens in same window)
            text = text.replace(/T-(\d{4})-(\d{3})/g, '<a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?number=T-$1-$2" style="color: #667eea; text-decoration: underline;">T-$1-$2</a>');
            // Convert KB article references to clickable links (opens in same window)
            text = text.replace(/KB-(\d+)/g, '<a href="<?php echo SITE_URL; ?>/knowledge_base.php?id=$1" style="color: #667eea; text-decoration: underline;">KB-$1</a>');
            return text;
        }
        
        // Restore state
        if (localStorage.getItem('aiChatOpen') === 'true') {
            chatWindow.style.cssText = 'display: flex !important; position: absolute !important; bottom: 70px !important; right: 0 !important; width: 400px !important; height: 600px !important; background: white !important; border-radius: 16px !important; box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important; flex-direction: column !important; overflow: hidden !important;';
            isOpen = true;
            console.log('✅ Chat restored from localStorage');
        }
        
        // Force position fix for container
        var container = toggleBtn.parentElement;
        if (container) {
            // Get computed styles to debug
            var computedStyle = window.getComputedStyle(container);
            console.log('📍 Container computed position BEFORE fix:', {
                position: computedStyle.position,
                right: computedStyle.right,
                left: computedStyle.left,
                bottom: computedStyle.bottom,
                top: computedStyle.top,
                transform: computedStyle.transform
            });
            
            // Remove the widget from its current parent and append to body
            // This ensures no parent CSS affects it
            if (container.parentElement && container.parentElement.tagName !== 'BODY') {
                console.log('📦 Moving widget to body to avoid parent CSS conflicts');
                document.body.appendChild(container);
            }
            
            // Force the position with maximum specificity
            container.style.setProperty('position', 'fixed', 'important');
            container.style.setProperty('right', '20px', 'important');
            container.style.setProperty('bottom', '20px', 'important');
            container.style.setProperty('left', 'auto', 'important');
            container.style.setProperty('top', 'auto', 'important');
            container.style.setProperty('z-index', '999999', 'important');
            container.style.setProperty('margin', '0', 'important');
            container.style.setProperty('padding', '0', 'important');
            container.style.setProperty('transform', 'none', 'important');
            
            // Force a reflow
            container.offsetHeight;
            
            // Check again after fix
            setTimeout(function() {
                computedStyle = window.getComputedStyle(container);
                console.log('📍 Container computed position AFTER fix:', {
                    position: computedStyle.position,
                    right: computedStyle.right,
                    left: computedStyle.left,
                    bottom: computedStyle.bottom,
                    top: computedStyle.top,
                    transform: computedStyle.transform
                });
            }, 100);
            
            console.log('✅ Container position fixed');
        }
        
        console.log('✅ AI Chat Widget initialized successfully!');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
