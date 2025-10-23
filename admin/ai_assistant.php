<?php
/**
 * AI Assistant - Admin Level
 * Admins have full access + system management capabilities
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/ai_helper.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$page_title = "AI Assistent - Admin";

include '../includes/header_admin.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-robot"></i> AI Assistent - Admin Portal
                    </h4>
                    <small>Volledige toegang + systeem management en K&K bedrijfsinformatie</small>
                </div>
                <div class="card-body p-0">
                    <!-- Advanced Search Options -->
                    <div class="border-bottom p-3 bg-light">
                        <div class="row">
                            <div class="col-md-8">
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
                            <div class="col-md-4 text-end">
                                <button class="btn btn-sm btn-outline-danger" id="clear-chat">
                                    <i class="fas fa-trash"></i> Wis Chat
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" id="export-chat">
                                    <i class="fas fa-download"></i> Exporteer
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Container -->
                    <div id="chat-container" style="height: 550px; overflow-y: auto; padding: 20px; background-color: #f8f9fa;">
                        <!-- Welcome message -->
                        <div class="message-wrapper mb-3">
                            <div class="alert alert-danger">
                                <i class="fas fa-crown"></i> 
                                <strong>Welkom bij de K&K AI Assistent voor Administrators!</strong><br>
                                Als admin heb je toegang tot:
                                <ul class="mb-0 mt-2">
                                    <li><strong>Alle data</strong> - Tickets, KB, CI items, users, agents</li>
                                    <li><strong>Systeem informatie</strong> - K&K bedrijfsprocessen en procedures</li>
                                    <li><strong>Analytics</strong> - Trends, patronen en statistieken</li>
                                    <li><strong>Management vragen</strong> - Rapportages en beslissingsondersteuning</li>
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
                                <button type="submit" class="btn btn-danger" id="send-btn">
                                    <i class="fas fa-paper-plane"></i> Verstuur
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i> 
                                Tip: Vraag naar trends, statistieken of complexe analyses
                            </small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Example Questions -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Voorbeeldvragen voor Administrators</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Operationeel:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2 mb-3">
                                <button class="btn btn-sm btn-outline-danger example-question">
                                    Wat zijn de meest voorkomende ticket categorieën deze maand?
                                </button>
                                <button class="btn btn-sm btn-outline-danger example-question">
                                    Welke agents hebben de hoogste resolutie rate?
                                </button>
                                <button class="btn btn-sm btn-outline-danger example-question">
                                    Toon me alle open high priority tickets
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Strategisch:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-2 mb-3">
                                <button class="btn btn-sm btn-outline-danger example-question">
                                    Welke hardware moet binnenkort vervangen worden?
                                </button>
                                <button class="btn btn-sm btn-outline-danger example-question">
                                    Wat zijn de trends in software problemen?
                                </button>
                                <button class="btn btn-sm btn-outline-danger example-question">
                                    Welke KB artikelen worden het meest gebruikt?
                                </button>
                            </div>
                        </div>
                    </div>
                    <strong>K&K Specifiek:</strong>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button class="btn btn-sm btn-outline-info example-question">
                            Wat zijn de standaard procedures voor nieuwe medewerkers?
                        </button>
                        <button class="btn btn-sm btn-outline-info example-question">
                            Welke systemen gebruiken we bij K&K?
                        </button>
                        <button class="btn btn-sm btn-outline-info example-question">
                            Hoe werkt de Ecoro integratie?
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Stats Dashboard -->
    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-ticket-alt fa-2x text-primary mb-2"></i>
                    <h5 id="stat-tickets">-</h5>
                    <small class="text-muted">Tickets</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-book fa-2x text-info mb-2"></i>
                    <h5 id="stat-kb">-</h5>
                    <small class="text-muted">KB Artikelen</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-server fa-2x text-warning mb-2"></i>
                    <h5 id="stat-ci">-</h5>
                    <small class="text-muted">CI Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-success mb-2"></i>
                    <h5 id="stat-users">-</h5>
                    <small class="text-muted">Gebruikers</small>
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
    max-width: 75%;
    padding: 12px 16px;
    border-radius: 12px;
    word-wrap: break-word;
}

.user-message {
    background-color: #dc3545;
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
    border-left: 3px solid #dc3545;
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
    let chatHistory = [];
    
    // Load stats
    loadStats();
    
    function loadStats() {
        $.get('ai_assistant_stats.php', function(data) {
            if (data.success) {
                $('#stat-tickets').text(data.stats.tickets || 0);
                $('#stat-kb').text(data.stats.kb_articles || 0);
                $('#stat-ci').text(data.stats.ci_items || 0);
                $('#stat-users').text(data.stats.users || 0);
            }
        });
    }
    
    function scrollToBottom() {
        chatContainer.animate({
            scrollTop: chatContainer[0].scrollHeight
        }, 300);
    }
    
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
        chatHistory.push({ type: 'user', message: message, time: time });
        scrollToBottom();
    }
    
    function addAIMessage(data) {
        const time = new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
        
        let confidenceClass = '';
        let confidenceBadge = '';
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
        
        let sourcesHtml = '';
        if (data.sources && data.sources.length > 0) {
            sourcesHtml = '<div class="mt-2"><small class="text-muted"><strong>Bronnen:</strong></small><br>';
            data.sources.slice(0, 5).forEach(source => {
                let icon = 'fa-file';
                if (source.type === 'ticket') icon = 'fa-ticket-alt';
                else if (source.type === 'kb') icon = 'fa-book';
                else if (source.type === 'ci') icon = 'fa-server';
                
                sourcesHtml += `<span class="source-badge">
                    <i class="fas ${icon}"></i> ${escapeHtml(source.title)}
                </span>`;
            });
            sourcesHtml += '</div>';
        }
        
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
                    <div><strong><i class="fas fa-robot"></i> AI Assistent (Admin)</strong></div>
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
        chatHistory.push({ type: 'ai', data: data, time: time });
        scrollToBottom();
    }
    
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
    
    function removeTypingIndicator() {
        $('.typing-wrapper').remove();
    }
    
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
    
    function formatAIResponse(text) {
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/^- (.+)$/gm, '• $1');
        return text;
    }
    
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
        
        const searchOptions = {
            query: message,
            search_tickets: $('#search-tickets').is(':checked'),
            search_kb: $('#search-kb').is(':checked'),
            search_ci: $('#search-ci').is(':checked')
        };
        
        addUserMessage(message);
        userInput.val('');
        userInput.prop('disabled', true);
        sendBtn.prop('disabled', true);
        addTypingIndicator();
        
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
            error: function() {
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
    
    // Clear chat
    $('#clear-chat').on('click', function() {
        if (confirm('Weet je zeker dat je de chat wilt wissen?')) {
            chatContainer.find('.message-wrapper').not(':first').remove();
            chatHistory = [];
        }
    });
    
    // Export chat
    $('#export-chat').on('click', function() {
        if (chatHistory.length === 0) {
            alert('Geen chat geschiedenis om te exporteren');
            return;
        }
        
        let exportText = 'K&K AI Assistant Chat Export\n';
        exportText += '================================\n';
        exportText += 'Datum: ' + new Date().toLocaleString('nl-NL') + '\n\n';
        
        chatHistory.forEach(item => {
            if (item.type === 'user') {
                exportText += `[${item.time}] Gebruiker: ${item.message}\n\n`;
            } else {
                exportText += `[${item.time}] AI: ${item.data.ai_answer}\n`;
                exportText += `Vertrouwen: ${(item.data.confidence_score * 100).toFixed(0)}%\n\n`;
            }
        });
        
        const blob = new Blob([exportText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ai_chat_export_' + new Date().toISOString().slice(0,10) + '.txt';
        a.click();
    });
    
    // Example questions
    $('.example-question').on('click', function() {
        userInput.val($(this).text());
        chatForm.submit();
    });
    
    userInput.focus();
});
</script>

<?php include '../includes/footer.php'; ?>
