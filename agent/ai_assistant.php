<?php
/**
 * AI Assistant - Agent Level
 * Agents have access to all tickets, KB articles, and CI items
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ai_helper.php';

// Check if agent is logged in
if (!isset($_SESSION['agent_id'])) {
    header('Location: ../login.php');
    exit;
}

$agent_id = $_SESSION['agent_id'];
$page_title = "AI Assistent";

// Get agent info
$stmt = $pdo->prepare("SELECT first_name, last_name FROM agents WHERE agent_id = ?");
$stmt->execute([$agent_id]);
$agent = $stmt->fetch();

include '../includes/header_agent.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-robot"></i> AI Assistent - Agent Portal
                    </h4>
                    <small>Volledige toegang tot tickets, kennisbank en configuratie items</small>
                </div>
                <div class="card-body p-0">
                    <!-- Search Options -->
                    <div class="border-bottom p-3 bg-light">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="mb-2"><strong>Zoek in:</strong></label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="search-tickets" checked>
                                    <label class="form-check-label" for="search-tickets">
                                        <i class="fas fa-ticket-alt"></i> Tickets
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="search-kb" checked>
                                    <label class="form-check-label" for="search-kb">
                                        <i class="fas fa-book"></i> Kennisbank
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="search-ci" checked>
                                    <label class="form-check-label" for="search-ci">
                                        <i class="fas fa-server"></i> CI Items
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Container -->
                    <div id="chat-container" style="height: 500px; overflow-y: auto; padding: 20px; background-color: #f8f9fa;">
                        <!-- Welcome message -->
                        <div class="message-wrapper mb-3">
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Welkom bij de K&K AI Assistent voor Agents!</strong><br>
                                Als agent heb je toegang tot:
                                <ul class="mb-0 mt-2">
                                    <li><strong>Alle tickets</strong> - Zoek in historische tickets en resoluties</li>
                                    <li><strong>Kennisbank</strong> - Volledige KB artikelen en procedures</li>
                                    <li><strong>CI Items</strong> - Hardware, software en netwerk configuraties</li>
                                    <li><strong>Relaties</strong> - Zie verbanden tussen tickets, users en CI items</li>
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
                                <button type="submit" class="btn btn-success" id="send-btn">
                                    <i class="fas fa-paper-plane"></i> Verstuur
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i> 
                                Tip: Vraag naar specifieke ticket nummers, gebruikers of CI items voor gedetailleerde info
                            </small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Example Questions for Agents -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Voorbeeldvragen voor Agents</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-success example-question">
                            Wat zijn veelvoorkomende laptop problemen?
                        </button>
                        <button class="btn btn-sm btn-outline-success example-question">
                            Hoe los ik printer verbindingsproblemen op?
                        </button>
                        <button class="btn btn-sm btn-outline-success example-question">
                            Welke Dell laptops hebben we in voorraad?
                        </button>
                        <button class="btn btn-sm btn-outline-success example-question">
                            Wat is de standaard procedure voor wachtwoord reset?
                        </button>
                        <button class="btn btn-sm btn-outline-success example-question">
                            Toon me tickets over netwerk problemen van deze week
                        </button>
                        <button class="btn btn-sm btn-outline-success example-question">
                            Welke software licenties verlopen binnenkort?
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-ticket-alt fa-2x text-primary mb-2"></i>
                    <h5 id="stat-tickets">-</h5>
                    <small class="text-muted">Tickets in database</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-book fa-2x text-info mb-2"></i>
                    <h5 id="stat-kb">-</h5>
                    <small class="text-muted">KB Artikelen</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-server fa-2x text-warning mb-2"></i>
                    <h5 id="stat-ci">-</h5>
                    <small class="text-muted">CI Items</small>
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
    background-color: #28a745;
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
    padding: 4px 10px;
    background-color: #e9ecef;
    border-radius: 4px;
    font-size: 0.75rem;
    margin: 2px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.source-badge:hover {
    background-color: #dee2e6;
}

.source-badge i {
    margin-right: 4px;
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

.relationship-chain {
    background-color: #f8f9fa;
    border-left: 3px solid #28a745;
    padding: 8px 12px;
    margin-top: 8px;
    font-size: 0.85rem;
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
    
    // Load stats
    loadStats();
    
    function loadStats() {
        $.get('ai_assistant_stats.php', function(data) {
            if (data.success) {
                $('#stat-tickets').text(data.stats.tickets || 0);
                $('#stat-kb').text(data.stats.kb_articles || 0);
                $('#stat-ci').text(data.stats.ci_items || 0);
            }
        });
    }
    
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
        
        // Build sources HTML with icons
        let sourcesHtml = '';
        if (data.sources && data.sources.length > 0) {
            sourcesHtml = '<div class="mt-2"><small class="text-muted"><strong>Bronnen:</strong></small><br>';
            data.sources.slice(0, 5).forEach(source => {
                let icon = 'fa-file';
                if (source.type === 'ticket') icon = 'fa-ticket-alt';
                else if (source.type === 'kb') icon = 'fa-book';
                else if (source.type === 'ci') icon = 'fa-server';
                
                sourcesHtml += `<span class="source-badge" title="${escapeHtml(source.title)}">
                    <i class="fas ${icon}"></i> ${escapeHtml(source.title)}
                </span>`;
            });
            sourcesHtml += '</div>';
        }
        
        // Build relationships HTML
        let relationshipsHtml = '';
        if (data.relationships && data.relationships.length > 0) {
            relationshipsHtml = '<div class="relationship-chain mt-2">';
            relationshipsHtml += '<small><strong><i class="fas fa-project-diagram"></i> Relaties:</strong></small><br>';
            data.relationships.slice(0, 3).forEach(rel => {
                relationshipsHtml += `<small>• ${escapeHtml(rel.description || rel.chain)}</small><br>`;
            });
            relationshipsHtml += '</div>';
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
                    ${relationshipsHtml}
                    <div class="message-time">${time} • ${data.response_time ? data.response_time.toFixed(2) + 's' : ''}</div>
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
    
    // Format AI response
    function formatAIResponse(text) {
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
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
        
        // Get search options
        const searchOptions = {
            query: message,
            search_tickets: $('#search-tickets').is(':checked'),
            search_kb: $('#search-kb').is(':checked'),
            search_ci: $('#search-ci').is(':checked')
        };
        
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
            data: searchOptions,
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
