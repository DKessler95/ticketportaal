<?php
/**
 * AI Assistant - Chat interface voor gebruikers
 * Gebruikers kunnen hier vragen stellen over K&K systemen, applicaties en procedures
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ai_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = "AI Assistent";

// Get user info
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include '../includes/header_user.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-robot"></i> AI Assistent
                    </h4>
                    <small>Stel vragen over K&K systemen, applicaties en procedures</small>
                </div>
                <div class="card-body p-0">
                    <!-- Chat Container -->
                    <div id="chat-container" style="height: 500px; overflow-y: auto; padding: 20px; background-color: #f8f9fa;">
                        <!-- Welcome message -->
                        <div class="message-wrapper mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Welkom bij de K&K AI Assistent!</strong><br>
                                Ik kan je helpen met vragen over:
                                <ul class="mb-0 mt-2">
                                    <li>Ticketportaal en procedures</li>
                                    <li>Hardware en software problemen</li>
                                    <li>Netwerk en toegangsvragen</li>
                                    <li>Veelgestelde vragen</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Input Area -->
                    <div class="border-top p-3 bg-white">
                        <form id="chat-form">
                            <div class="input-group">
                                <input type="text" 
                                       id="user-input" 
                                       class="form-control" 
                                       placeholder="Stel je vraag hier..." 
                                       autocomplete="off"
                                       required>
                                <button type="submit" class="btn btn-primary" id="send-btn">
                                    <i class="fas fa-paper-plane"></i> Verstuur
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i> 
                                Tip: Wees specifiek in je vraag voor betere resultaten
                            </small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Example Questions -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Voorbeeldvragen</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-secondary example-question">
                            Hoe reset ik mijn wachtwoord?
                        </button>
                        <button class="btn btn-sm btn-outline-secondary example-question">
                            Mijn laptop start niet op, wat moet ik doen?
                        </button>
                        <button class="btn btn-sm btn-outline-secondary example-question">
                            Hoe vraag ik nieuwe software aan?
                        </button>
                        <button class="btn btn-sm btn-outline-secondary example-question">
                            Wat is het proces voor hardware reparatie?
                        </button>
                        <button class="btn btn-sm btn-outline-secondary example-question">
                            Hoe krijg ik toegang tot een gedeelde map?
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.message-wrapper {
    display: flex;
    margin-bottom: 15px;
}

.message {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 12px;
    word-wrap: break-word;
}

.user-message {
    background-color: #007bff;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.ai-message {
    background-color: white;
    border: 1px solid #dee2e6;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 4px;
}

.typing-indicator {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    max-width: 70px;
}

.typing-indicator span {
    height: 8px;
    width: 8px;
    background-color: #6c757d;
    border-radius: 50%;
    display: inline-block;
    margin: 0 2px;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

.source-badge {
    display: inline-block;
    padding: 2px 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    font-size: 0.75rem;
    margin: 2px;
}

.confidence-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}

.confidence-high {
    background-color: #d4edda;
    color: #155724;
}

.confidence-medium {
    background-color: #fff3cd;
    color: #856404;
}

.confidence-low {
    background-color: #f8d7da;
    color: #721c24;
}

#chat-container::-webkit-scrollbar {
    width: 8px;
}

#chat-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#chat-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

#chat-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<script>
$(document).ready(function() {
    const chatContainer = $('#chat-container');
    const chatForm = $('#chat-form');
    const userInput = $('#user-input');
    const sendBtn = $('#send-btn');
    
    // Auto-scroll to bottom
    function scrollToBottom() {
        chatContainer.animate({
            scrollTop: chatContainer[0].scrollHeight
        }, 300);
    }
    
    // Add user message to chat
    function addUserMessage(message) {
        const time = new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
        const html = `
            <div class="message-wrapper">
                <div class="message user-message">
                    <div>${escapeHtml(message)}</div>
                    <div class="message-time text-end">${time}</div>
                </div>
            </div>
        `;
        chatContainer.append(html);
        scrollToBottom();
    }
    
    // Add AI message to chat
    function addAIMessage(data) {
        const time = new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
        
        // Determine confidence badge
        let confidenceBadge = '';
        let confidenceClass = '';
        if (data.confidence_score >= 0.7) {
            confidenceClass = 'confidence-high';
            confidenceBadge = 'Hoog vertrouwen';
        } else if (data.confidence_score >= 0.5) {
            confidenceClass = 'confidence-medium';
            confidenceBadge = 'Gemiddeld vertrouwen';
        } else {
            confidenceClass = 'confidence-low';
            confidenceBadge = 'Laag vertrouwen';
        }
        
        // Build sources HTML
        let sourcesHtml = '';
        if (data.sources && data.sources.length > 0) {
            sourcesHtml = '<div class="mt-2"><small class="text-muted">Bronnen:</small><br>';
            data.sources.slice(0, 3).forEach(source => {
                sourcesHtml += `<span class="source-badge">${escapeHtml(source.title)}</span>`;
            });
            sourcesHtml += '</div>';
        }
        
        const html = `
            <div class="message-wrapper">
                <div class="message ai-message">
                    <div><strong><i class="fas fa-robot"></i> AI Assistent</strong></div>
                    <div class="mt-2">${formatAIResponse(data.ai_answer)}</div>
                    <div class="mt-2">
                        <span class="confidence-badge ${confidenceClass}">
                            ${confidenceBadge} (${(data.confidence_score * 100).toFixed(0)}%)
                        </span>
                    </div>
                    ${sourcesHtml}
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
        chatContainer.append(html);
        scrollToBottom();
    }
    
    // Add typing indicator
    function addTypingIndicator() {
        const html = `
            <div class="message-wrapper typing-wrapper">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        chatContainer.append(html);
        scrollToBottom();
    }
    
    // Remove typing indicator
    function removeTypingIndicator() {
        $('.typing-wrapper').remove();
    }
    
    // Add error message
    function addErrorMessage(error) {
        const html = `
            <div class="message-wrapper">
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Fout:</strong> ${escapeHtml(error)}
                </div>
            </div>
        `;
        chatContainer.append(html);
        scrollToBottom();
    }
    
    // Format AI response (convert markdown-like formatting)
    function formatAIResponse(text) {
        // Convert newlines to <br>
        text = text.replace(/\n/g, '<br>');
        // Convert **bold** to <strong>
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Convert bullet points
        text = text.replace(/^- (.+)$/gm, '• $1');
        return text;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Handle form submission
    chatForm.on('submit', function(e) {
        e.preventDefault();
        
        const message = userInput.val().trim();
        if (!message) return;
        
        // Add user message
        addUserMessage(message);
        userInput.val('');
        
        // Disable input
        userInput.prop('disabled', true);
        sendBtn.prop('disabled', true);
        
        // Show typing indicator
        addTypingIndicator();
        
        // Send to AI
        $.ajax({
            url: 'ai_assistant_handler.php',
            method: 'POST',
            data: { query: message },
            dataType: 'json',
            success: function(response) {
                removeTypingIndicator();
                
                if (response.success) {
                    addAIMessage(response.data);
                } else {
                    addErrorMessage(response.error || 'Er is een fout opgetreden');
                }
            },
            error: function(xhr, status, error) {
                removeTypingIndicator();
                addErrorMessage('Kan geen verbinding maken met de AI service');
            },
            complete: function() {
                userInput.prop('disabled', false);
                sendBtn.prop('disabled', false);
                userInput.focus();
            }
        });
    });
    
    // Handle example questions
    $('.example-question').on('click', function() {
        const question = $(this).text();
        userInput.val(question);
        chatForm.submit();
    });
    
    // Focus input on load
    userInput.focus();
});
</script>

<?php include '../includes/footer.php'; ?>
