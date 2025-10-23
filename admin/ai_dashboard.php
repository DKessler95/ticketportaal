<?php
/**
 * AI Dashboard for Administrators
 * Monitor AI services, view statistics, and manage AI features
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai_config.php';
require_once __DIR__ . '/../includes/ai_helper.php';

// Initialize session and require admin role
initSession();
requireRole(['admin']);

$ai = new AIHelper();

// Handle actions
$action_message = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_kb_articles':
                // Add K&K KB articles
                try {
                    $sql = file_get_contents(__DIR__ . '/../database/migrations/008_add_kk_knowledge_base_articles.sql');
                    $pdo->exec($sql);
                    $action_message = '✓ K&K KB articles succesvol toegevoegd! Run nu de sync om ze naar de vector database te synchroniseren.';
                    $action_type = 'success';
                } catch (Exception $e) {
                    $action_message = '✗ Fout bij toevoegen: ' . $e->getMessage();
                    $action_type = 'danger';
                }
                break;
            
            case 'sync_now':
                $action_message = 'Sync moet handmatig gestart worden via PowerShell: python ai_module/scripts/sync_tickets_to_vector_db.py';
                $action_type = 'info';
                break;
                
            case 'restart_services':
                $action_message = 'Services herstarten vereist administrator rechten op de server. Gebruik PowerShell: Restart-Service TicketportaalRAG';
                $action_type = 'warning';
                break;
                
            case 'clear_cache':
                $action_message = 'Cache functionaliteit nog niet geïmplementeerd.';
                $action_type = 'info';
                break;
        }
    }
}

// Get health status and stats
$health = $ai->getHealthStatus();
$stats = $ai->getStats();

// Get KB counts
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM knowledge_base WHERE is_published = 1");
    $kb_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM knowledge_base WHERE title LIKE '%Kruit & Kramer%' OR title LIKE '%K&K%'");
    $kk_count = $stmt->fetch()['count'];
} catch (Exception $e) {
    $kb_count = 0;
    $kk_count = 0;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-robot"></i> AI Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="ai_assistant.php" class="btn btn-primary">
                            <i class="bi bi-chat-dots"></i> AI Assistent
                        </a>
                    </div>
                </div>

                <?php if ($action_message): ?>
                    <div class="alert alert-<?php echo $action_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $action_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Service Status -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-activity"></i> Service Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <?php if ($health['ollama_available']): ?>
                                                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill text-danger fs-2"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">Ollama LLM</h6>
                                                <small class="text-muted">
                                                    <?php echo $health['ollama_available'] ? 'Online' : 'Offline'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <?php if ($health['chromadb_available']): ?>
                                                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill text-danger fs-2"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">ChromaDB</h6>
                                                <small class="text-muted">
                                                    <?php echo $health['chromadb_available'] ? 'Online' : 'Offline'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <?php if ($health['graph_available']): ?>
                                                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill text-danger fs-2"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">Knowledge Graph</h6>
                                                <small class="text-muted">
                                                    <?php echo $health['graph_available'] ? 'Online' : 'Offline'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <?php if (isset($stats['success']) && $stats['success']): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-ticket-perforated text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $stats['tickets_embedded'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Tickets in Vector DB</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-book text-info" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $stats['kb_embedded'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">KB Articles</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-hdd-rack text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $stats['ci_embedded'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">CI Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-chat-dots text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?php echo $stats['queries_today'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Queries Vandaag</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Acties</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="sync_now">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-arrow-repeat"></i> Sync Nu
                                        </button>
                                    </form>
                                    
                                    <a href="../ai_module/logs/" class="btn btn-secondary" target="_blank">
                                        <i class="bi bi-file-text"></i> Bekijk Logs
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit" class="btn btn-secondary">
                                            <i class="bi bi-trash"></i> Wis Cache
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je de services wilt herstarten?');">
                                        <input type="hidden" name="action" value="restart_services">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-arrow-clockwise"></i> Herstart Services
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Knowledge Base Management -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-book-half"></i> Knowledge Base Management</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Voeg K&K specifieke KB articles toe om de AI slimmer te maken</p>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                            <span>Totaal KB Articles</span>
                                            <span class="badge bg-primary"><?php echo $kb_count; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                            <span>K&K Specifieke Articles</span>
                                            <span class="badge bg-info"><?php echo $kk_count; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($kk_count < 6): ?>
                                <div class="alert alert-warning">
                                    <h6><i class="bi bi-exclamation-triangle"></i> K&K Articles Ontbreken</h6>
                                    <p class="mb-2">Voeg 6 K&K specifieke KB articles toe voor betere AI antwoorden over:</p>
                                    <ul class="mb-3">
                                        <li>Bedrijfsinformatie (vestigingen, organisatie)</li>
                                        <li>ICT Systemen (Ticketportaal, Ecoro, VPN)</li>
                                        <li>Hardware Standaarden (Dell, HP, Cisco)</li>
                                        <li>Netwerk en Toegang (WiFi, VLANs)</li>
                                        <li>Veelgestelde Vragen (FAQ)</li>
                                        <li>Nieuwe Medewerker Onboarding</li>
                                    </ul>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_kb_articles">
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Dit voegt 6 K&K KB articles toe. Doorgaan?');">
                                            <i class="bi bi-plus-circle"></i> Voeg K&K Articles Toe
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-success">
                                    <h6><i class="bi bi-check-circle"></i> K&K Articles Aanwezig</h6>
                                    <p class="mb-0">De K&K specifieke KB articles zijn toegevoegd. Vergeet niet om de sync te draaien!</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-gear"></i> Configuratie</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>AI Enabled</strong></td>
                                        <td><?php echo AI_ENABLED ? '<span class="badge bg-success">Ja</span>' : '<span class="badge bg-danger">Nee</span>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>RAG API URL</strong></td>
                                        <td><code><?php echo RAG_API_URL; ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beta Users</strong></td>
                                        <td><?php echo empty(AI_BETA_USERS) ? 'Alle gebruikers' : count(AI_BETA_USERS) . ' gebruikers'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Show Suggestions</strong></td>
                                        <td><?php echo AI_SHOW_SUGGESTIONS ? '<span class="badge bg-success">Ja</span>' : '<span class="badge bg-danger">Nee</span>'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
