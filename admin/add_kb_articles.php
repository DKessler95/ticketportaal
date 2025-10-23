<?php
/**
 * Add K&K Knowledge Base Articles - Web Interface
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_articles'])) {
    try {
        // Read SQL file
        $sql = file_get_contents(__DIR__ . '/../database/migrations/008_add_kk_knowledge_base_articles.sql');
        
        // Execute SQL
        $pdo->exec($sql);
        
        $message = 'KB articles succesvol toegevoegd!';
        
    } catch (Exception $e) {
        $error = 'Fout bij toevoegen: ' . $e->getMessage();
    }
}

// Get current KB count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM knowledge_base WHERE is_published = 1");
$kb_count = $stmt->fetch()['count'];

$page_title = "KB Articles Toevoegen";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-book"></i> K&K Knowledge Base Articles Toevoegen
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p>Huidige aantal KB articles: <strong><?php echo $kb_count; ?></strong></p>
                    
                    <p>Deze actie voegt de volgende K&K specifieke KB articles toe:</p>
                    <ul>
                        <li><strong>Kruit & Kramer - Bedrijfsinformatie</strong><br>
                            <small class="text-muted">Vestigingen, organisatie, kernactiviteiten</small>
                        </li>
                        <li><strong>ICT Systemen bij Kruit & Kramer</strong><br>
                            <small class="text-muted">Ticketportaal, Ecoro, Email, VPN, File Server</small>
                        </li>
                        <li><strong>Hardware Standaarden K&K</strong><br>
                            <small class="text-muted">Laptops, desktops, monitoren, printers, netwerk</small>
                        </li>
                        <li><strong>Netwerk en Toegang Procedures</strong><br>
                            <small class="text-muted">VLANs, WiFi, wachtwoorden, VPN, beveiliging</small>
                        </li>
                        <li><strong>Veelgestelde Vragen (FAQ)</strong><br>
                            <small class="text-muted">Account, hardware, software, Ecoro, VPN problemen</small>
                        </li>
                        <li><strong>Nieuwe Medewerker - ICT Onboarding</strong><br>
                            <small class="text-muted">Setup, trainingen, procedures, checklist</small>
                        </li>
                    </ul>
                    
                    <form method="POST">
                        <button type="submit" name="add_articles" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Voeg KB Articles Toe
                        </button>
                        <a href="ai_dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Terug naar Dashboard
                        </a>
                    </form>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-info mt-4">
                            <h5><i class="bi bi-info-circle"></i> Volgende Stap</h5>
                            <p>De KB articles zijn toegevoegd aan de database. Nu moet je ze synchroniseren naar de vector database:</p>
                            <ol>
                                <li>Open PowerShell in <code>ai_module/scripts</code></li>
                                <li>Run: <code>python sync_tickets_to_vector_db.py</code></li>
                                <li>Wacht tot sync compleet is</li>
                                <li>Test de AI Assistant met vragen over K&K</li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
