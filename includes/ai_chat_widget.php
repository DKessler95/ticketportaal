<?php
/**
 * Floating AI Chat Widget
 * A persistent, floating chatbot that works across all pages
 * 
 * Features:
 * - Floating button (bottom-right)
 * - Slide-up chat window
 * - Session storage for chat history
 * - Minimizable/Expandable
 * - Role-aware (user/agent/admin)
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
?>

<!-- AI Chat Widget -->
<div id="ai-chat-widget">
    <!-- Floating Button -->
    <button id="ai-chat-toggle" class="ai-chat-button" title="Open AI Assistent">
        <i class="bi bi-robot">🤖</i>
        <span class="ai-chat-badge" id="ai-unread-badge" style="display: none;">0</span>
    </button>
    
    <!-- Chat Window -->
    <div id="ai-chat-window" class="ai-chat-window" style="display: none;">
        <!-- Header -->
        <div class="ai-chat-header">
            <div class="d-flex align-items-center">
                <i class="bi bi-robot me-2"></i>
                <div>
                    <strong>AI Assistent</strong>
                    <br><small class="text-white-50">K&K Ticketportaal</small>
                </div>
            </div>
            <div class="ai-chat-actions">
                <button class="ai-chat-action-btn" id="ai-chat-clear" title="Wis geschiedenis" onclick="if(confirm('Chat geschiedenis wissen?')) clearChatHistory();">
                    <i class="bi bi-trash"></i>
                </button>
                <button class="ai-chat-action-btn" id="ai-chat-minimize" title="Minimaliseer">
                    <i class="bi bi-dash-lg"></i>
                </button>
                <button class="ai-chat-action-btn" id="ai-chat-close" title="Sluit">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        
        <!-- Messages Container -->
        <div class="ai-chat-messages" id="ai-chat-messages">
            <!-- Welcome Message -->
            <div class="ai-message-wrapper">
                <div class="ai-message ai-message-bot">
                    <div class="ai-message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="ai-message-content">
                        <strong>AI Assistent</strong>
                        <p>Hallo <?php echo htmlspecialchars($userName); ?>! 👋</p>
                        <p>Ik kan je helpen met vragen over K&K systemen, procedures en tickets.</p>
                        <?php if ($userRole === 'admin'): ?>
                        <p><small class="text-muted">Als admin heb je toegang tot alle data en analytics.</small></p>
                        <?php elseif ($userRole === 'agent'): ?>
                        <p><small class="text-muted">Als agent heb je toegang tot alle tickets en KB artikelen.</small></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="ai-chat-input-area">
            <form id="ai-chat-form">
                <div class="input-group">
                    <input type="text" 
                           id="ai-chat-input" 
                           class="form-control" 
                           placeholder="Stel je vraag..." 
                           autocomplete="off">
                    <button type="submit" class="btn btn-primary" id="ai-chat-send">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </form>
            <div class="ai-chat-suggestions" id="ai-chat-suggestions">
                <!-- Quick suggestions will be added here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Floating Button */
.ai-chat-button {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    width: 60px !important;
    height: 60px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: white !important;
    font-size: 24px !important;
    cursor: pointer !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease !important;
    z-index: 999999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    pointer-events: auto !important;
}

.ai-chat-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.ai-chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

/* Chat Window */
.ai-chat-window {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 400px;
    height: 600px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    z-index: 999998;
    animation: slideUp 0.3s ease;
    pointer-events: auto !important;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header */
.ai-chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-chat-actions {
    display: flex;
    gap: 8px;
}

.ai-chat-action-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.ai-chat-action-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Messages */
.ai-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8f9fa;
}

.ai-message-wrapper {
    margin-bottom: 16px;
}

.ai-message {
    display: flex;
    gap: 12px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
}

.ai-message-bot .ai-message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.ai-message-user .ai-message-avatar {
    background: #28a745;
    color: white;
}

.ai-message-content {
    flex: 1;
    background: white;
    padding: 12px;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.ai-message-user .ai-message-content {
    background: #28a745;
    color: white;
}

.ai-message-content p {
    margin: 0;
    line-height: 1.5;
}

.ai-message-content p + p {
    margin-top: 8px;
}

/* Message Footer */
.ai-message-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f0f0f0;
}

/* Feedback Buttons */
.ai-feedback-buttons {
    display: flex;
    gap: 8px;
}

.ai-feedback-btn {
    background: transparent;
    border: 1px solid #dee2e6;
    color: #6c757d;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.ai-feedback-btn:hover:not(:disabled) {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: scale(1.1);
}

.ai-feedback-btn:disabled {
    cursor: not-allowed;
}

/* Typing Indicator */
.ai-typing-indicator {
    display: flex;
    gap: 4px;
    padding: 8px;
}

.ai-typing-dot {
    width: 8px;
    height: 8px;
    background: #6c757d;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.ai-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-10px); }
}

/* Input Area */
.ai-chat-input-area {
    padding: 16px;
    background: white;
    border-radius: 0 0 16px 16px;
    border-top: 1px solid #dee2e6;
}

.ai-chat-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.ai-suggestion-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.ai-suggestion-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

/* Scrollbar */
.ai-chat-messages::-webkit-scrollbar {
    width: 6px;
}

.ai-chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.ai-chat-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

/* Responsive */
@media (max-width: 768px) {
    .ai-chat-window {
        width: calc(100vw - 40px);
        height: calc(100vh - 120px);
        right: 20px;
        bottom: 90px;
    }
}

/* Minimized State */
.ai-chat-window.minimized {
    height: 60px;
    overflow: hidden;
}

.ai-chat-window.minimized .ai-chat-messages,
.ai-chat-window.minimized .ai-chat-input-area {
    display: none;
}
</style>

<script>
// AI Chat Widget JavaScript
console.log('AI Chat Widget loaded!');
(function() {
    const chatToggle = document.getElementById('ai-chat-toggle');
    console.log('Chat toggle button:', chatToggle);
    const chatWindow = document.getElementById('ai-chat-window');
    const chatClose = document.getElementById('ai-chat-close');
    const chatMinimize = document.getElementById('ai-chat-minimize');
    const chatForm = document.getElementById('ai-chat-form');
    const chatInput = document.getElementById('ai-chat-input');
    const chatMessages = document.getElementById('ai-chat-messages');
    const chatSend = document.getElementById('ai-chat-send');
    
    let isOpen = false;
    let isMinimized = false;
    
    // Load chat state from localStorage (persists across pages)
    const savedState = localStorage.getItem('aiChatState');
    if (savedState === 'open') {
        openChat();
    }
    
    // Load chat history from localStorage
    loadChatHistory();
    
    // Toggle chat
    chatToggle.addEventListener('click', () => {
        if (isOpen) {
            closeChat();
        } else {
            openChat();
        }
    });
    
    // Close chat
    chatClose.addEventListener('click', closeChat);
    
    // Minimize chat
    chatMinimize.addEventListener('click', () => {
        if (isMinimized) {
            chatWindow.classList.remove('minimized');
            isMinimized = false;
        } else {
            chatWindow.classList.add('minimized');
            isMinimized = true;
        }
    });
    
    function openChat() {
        chatWindow.style.display = 'flex';
        isOpen = true;
        localStorage.setItem('aiChatState', 'open');
        chatInput.focus();
    }
    
    function closeChat() {
        chatWindow.style.display = 'none';
        isOpen = false;
        isMinimized = false;
        chatWindow.classList.remove('minimized');
        localStorage.setItem('aiChatState', 'closed');
    }
    
    function saveChatHistory() {
        const messages = [];
        chatMessages.querySelectorAll('.ai-message-wrapper').forEach(wrapper => {
            const isUser = wrapper.querySelector('.ai-message-user') !== null;
            const content = wrapper.querySelector('.ai-message-content p');
            if (content) {
                messages.push({
                    role: isUser ? 'user' : 'assistant',
                    content: content.textContent
                });
            }
        });
        localStorage.setItem('aiChatHistory', JSON.stringify(messages));
    }
    
    function loadChatHistory() {
        const history = localStorage.getItem('aiChatHistory');
        if (history) {
            try {
                const messages = JSON.parse(history);
                // Clear welcome message if we have history
                if (messages.length > 0) {
                    chatMessages.innerHTML = '';
                }
                messages.forEach(msg => {
                    if (msg.role === 'user') {
                        addUserMessage(msg.content, false); // false = don't save again
                    } else {
                        addBotMessageSimple(msg.content);
                    }
                });
            } catch (e) {
                console.error('Error loading chat history:', e);
            }
        }
    }
    
    function clearChatHistory() {
        localStorage.removeItem('aiChatHistory');
        localStorage.removeItem('aiChatState');
        location.reload();
    }
    
    // Handle form submission
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const message = chatInput.value.trim();
        if (!message) return;
        
        console.log('Sending message:', message);
        
        // Add user message
        addUserMessage(message);
        chatInput.value = '';
        
        // Show typing indicator
        addTypingIndicator();
        
        // Send to AI
        const apiUrl = '<?php echo SITE_URL; ?>/api/ai_chat_handler.php';
        console.log('API URL:', apiUrl);
        
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'same-origin', // Include cookies/session
                body: new URLSearchParams({
                    query: message,
                    search_tickets: '<?php echo $userRole !== "user" ? "true" : "false"; ?>',
                    search_kb: 'true',
                    search_ci: 'true'
                })
            });
            
            const data = await response.json();
            console.log('API Response:', data);
            
            removeTypingIndicator();
            
            if (data.success) {
                addBotMessage(data.data);
            } else {
                console.error('API Error:', data);
                addErrorMessage(data.error || 'Er is een fout opgetreden');
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            removeTypingIndicator();
            addErrorMessage('Kan geen verbinding maken met de AI service');
        }
    });
    
    function addUserMessage(message, save = true) {
        const html = `
            <div class="ai-message-wrapper">
                <div class="ai-message ai-message-user">
                    <div class="ai-message-content">
                        <p>${escapeHtml(message)}</p>
                    </div>
                    <div class="ai-message-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
        if (save) saveChatHistory();
    }
    
    function addBotMessageSimple(text) {
        const html = `
            <div class="ai-message-wrapper">
                <div class="ai-message ai-message-bot">
                    <div class="ai-message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="ai-message-content">
                        <strong>AI Assistent</strong>
                        <p>${formatResponse(text)}</p>
                    </div>
                </div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }
    
    function addBotMessage(data) {
        const confidence = (data.confidence_score * 100).toFixed(0);
        let confidenceColor = confidence >= 70 ? '#28a745' : confidence >= 50 ? '#ffc107' : '#dc3545';
        const messageId = 'msg-' + Date.now();
        
        const html = `
            <div class="ai-message-wrapper" data-message-id="${messageId}">
                <div class="ai-message ai-message-bot">
                    <div class="ai-message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="ai-message-content">
                        <strong>AI Assistent</strong>
                        <p>${formatResponse(data.ai_answer)}</p>
                        <div class="ai-message-footer">
                            <small style="color: ${confidenceColor};">
                                <i class="bi bi-check-circle"></i> ${confidence}% vertrouwen
                            </small>
                            <div class="ai-feedback-buttons" id="feedback-${messageId}">
                                <button class="ai-feedback-btn" onclick="submitFeedback('${messageId}', 1)" title="Nuttig">
                                    <i class="bi bi-hand-thumbs-up"></i>
                                </button>
                                <button class="ai-feedback-btn" onclick="submitFeedback('${messageId}', -1)" title="Niet nuttig">
                                    <i class="bi bi-hand-thumbs-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
        saveChatHistory();
    }
    
    function addTypingIndicator() {
        const html = `
            <div class="ai-message-wrapper typing-indicator-wrapper">
                <div class="ai-message ai-message-bot">
                    <div class="ai-message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="ai-message-content">
                        <div class="ai-typing-indicator">
                            <div class="ai-typing-dot"></div>
                            <div class="ai-typing-dot"></div>
                            <div class="ai-typing-dot"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }
    
    function removeTypingIndicator() {
        const indicator = chatMessages.querySelector('.typing-indicator-wrapper');
        if (indicator) indicator.remove();
    }
    
    function addErrorMessage(error) {
        const html = `
            <div class="ai-message-wrapper">
                <div class="ai-message ai-message-bot">
                    <div class="ai-message-avatar">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="ai-message-content">
                        <strong>Fout</strong>
                        <p>${escapeHtml(error)}</p>
                    </div>
                </div>
            </div>
        `;
        chatMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }
    
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatResponse(text) {
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Convert ticket numbers to clickable links
        text = text.replace(/T-(\d{4})-(\d{3})/g, '<a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?number=T-$1-$2" target="_blank" style="color: #667eea; text-decoration: underline;">T-$1-$2</a>');
        
        // Convert KB article references to clickable links
        text = text.replace(/KB-(\d+)/g, '<a href="<?php echo SITE_URL; ?>/knowledge_base.php?id=$1" target="_blank" style="color: #667eea; text-decoration: underline;">KB-$1</a>');
        
        return text;
    }
    
    // Make submitFeedback global
    window.submitFeedback = async function(messageId, score) {
        console.log('Feedback:', messageId, score);
        
        const feedbackDiv = document.getElementById('feedback-' + messageId);
        if (!feedbackDiv) return;
        
        // Visual feedback
        const buttons = feedbackDiv.querySelectorAll('.ai-feedback-btn');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        });
        
        // Highlight selected button
        const selectedBtn = score > 0 ? buttons[0] : buttons[1];
        selectedBtn.style.color = score > 0 ? '#28a745' : '#dc3545';
        selectedBtn.style.opacity = '1';
        
        // Send feedback to server
        try {
            await fetch('<?php echo SITE_URL; ?>/api/ai_feedback.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                credentials: 'same-origin',
                body: new URLSearchParams({
                    message_id: messageId,
                    feedback_score: score,
                    timestamp: new Date().toISOString()
                })
            });
            
            // Show thank you message
            feedbackDiv.innerHTML = '<small style="color: #6c757d;"><i class="bi bi-check"></i> Bedankt voor je feedback!</small>';
        } catch (error) {
            console.error('Feedback error:', error);
        }
    };
    
    // Make clearChatHistory global
    window.clearChatHistory = clearChatHistory;
})();
</script>
