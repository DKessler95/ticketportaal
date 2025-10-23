<?php
/**
 * AI Validation Interface
 * Human-in-the-loop validation UI for entity and relationship extraction
 * 
 * Allows admins to review and validate extracted entities and relationships
 * to improve extraction quality and calculate precision/recall metrics.
 */

require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Use the global $pdo connection from database.php
$conn = $pdo;

// Check if validation tables exist
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'validation_samples'");
    $table_exists = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $table_exists = false;
}

if (!$table_exists) {
    // Show friendly message that feature is not yet set up
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AI Validatie - <?php echo SITE_NAME; ?></title>
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
                        <h1 class="h2"><i class="bi bi-check2-square"></i> AI Validatie</h1>
                    </div>
                    
                    <div class="alert alert-info">
                        <h4><i class="bi bi-info-circle"></i> Feature Nog Niet Beschikbaar</h4>
                        <p>De AI Validatie feature vereist extra database tables die nog niet zijn aangemaakt.</p>
                        <p class="mb-0">Deze feature wordt in een latere fase geactiveerd wanneer de validation workflow volledig is geïmplementeerd.</p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Wat is AI Validatie?</h5>
                        </div>
                        <div class="card-body">
                            <p>AI Validatie is een human-in-the-loop systeem waarmee je:</p>
                            <ul>
                                <li>Geëxtraheerde entities kunt reviewen en valideren</li>
                                <li>Relaties tussen entities kunt controleren</li>
                                <li>Precision en recall metrics kunt berekenen</li>
                                <li>De kwaliteit van entity extraction kunt verbeteren</li>
                            </ul>
                            <p class="mb-0">Deze feature wordt automatisch beschikbaar na het uitvoeren van de validation workflow setup.</p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Handle validation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'validate_entity') {
        $validation_id = intval($_POST['validation_id']);
        $is_correct = intval($_POST['is_correct']);
        $should_be_type = $_POST['should_be_type'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        $stmt = $conn->prepare("
            UPDATE entity_validations 
            SET is_correct = ?, should_be_type = ?, notes = ?, validated_at = NOW()
            WHERE validation_id = ?
        ");
        $stmt->bind_param('issi', $is_correct, $should_be_type, $notes, $validation_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'validate_relationship') {
        $validation_id = intval($_POST['validation_id']);
        $is_correct = intval($_POST['is_correct']);
        $should_be_type = $_POST['should_be_type'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        $stmt = $conn->prepare("
            UPDATE relationship_validations 
            SET is_correct = ?, should_be_type = ?, notes = ?, validated_at = NOW()
            WHERE validation_id = ?
        ");
        $stmt->bind_param('issi', $is_correct, $should_be_type, $notes, $validation_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'mark_sample_complete') {
        $sample_id = intval($_POST['sample_id']);
        
        $stmt = $conn->prepare("
            UPDATE validation_samples 
            SET validated = TRUE, validated_at = NOW(), validated_by = ?
            WHERE sample_id = ?
        ");
        $stmt->bind_param('ii', $_SESSION['user_id'], $sample_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get validation progress
$progress_query = "
    SELECT 
        COUNT(*) as total_samples,
        SUM(CASE WHEN validated = TRUE THEN 1 ELSE 0 END) as validated_samples
    FROM validation_samples
";
$progress_result = $conn->query($progress_query);
$progress = $progress_result->fetch_assoc();
$completion_pct = $progress['total_samples'] > 0 
    ? round(($progress['validated_samples'] / $progress['total_samples']) * 100, 1) 
    : 0;

// Get current sample to validate
$sample_id = isset($_GET['sample_id']) ? intval($_GET['sample_id']) : null;

if (!$sample_id) {
    // Get next unvalidated sample
    $next_sample_query = "
        SELECT sample_id 
        FROM validation_samples 
        WHERE validated = FALSE 
        ORDER BY sample_id 
        LIMIT 1
    ";
    $next_result = $conn->query($next_sample_query);
    if ($next_result && $next_result->num_rows > 0) {
        $sample_id = $next_result->fetch_assoc()['sample_id'];
    }
}

// Get sample details
$sample = null;
$ticket = null;
$entities = [];
$relationships = [];

if ($sample_id) {
    $sample_query = "
        SELECT vs.*, t.title, t.description, t.resolution, t.status
        FROM validation_samples vs
        JOIN tickets t ON vs.ticket_id = t.ticket_id
        WHERE vs.sample_id = ?
    ";
    $stmt = $conn->prepare($sample_query);
    $stmt->bind_param('i', $sample_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sample = $result->fetch_assoc();
    
    if ($sample) {
        // Get ticket details
        $ticket = [
            'ticket_id' => $sample['ticket_id'],
            'ticket_number' => $sample['ticket_number'],
            'title' => $sample['title'],
            'description' => $sample['description'],
            'resolution' => $sample['resolution'],
            'status' => $sample['status'],
            'category' => $sample['category'],
            'priority' => $sample['priority']
        ];
        
        // Get entities for validation
        $entities_query = "
            SELECT * FROM entity_validations 
            WHERE sample_id = ? 
            ORDER BY entity_type, entity_text
        ";
        $stmt = $conn->prepare($entities_query);
        $stmt->bind_param('i', $sample_id);
        $stmt->execute();
        $entities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get relationships for validation
        $relationships_query = "
            SELECT * FROM relationship_validations 
            WHERE sample_id = ? 
            ORDER BY edge_type
        ";
        $stmt = $conn->prepare($relationships_query);
        $stmt->bind_param('i', $sample_id);
        $stmt->execute();
        $relationships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Get list of all samples for navigation
$samples_list_query = "
    SELECT sample_id, ticket_number, category, validated 
    FROM validation_samples 
    ORDER BY sample_id
";
$samples_list = $conn->query($samples_list_query)->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<style>
.validation-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.progress-bar-container {
    background: #e0e0e0;
    border-radius: 10px;
    height: 30px;
    margin: 20px 0;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, #4CAF50, #45a049);
    height: 100%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.ticket-display {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.ticket-field {
    margin: 10px 0;
}

.ticket-field label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}

.ticket-field .content {
    background: white;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.validation-section {
    margin: 30px 0;
}

.validation-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    transition: all 0.3s ease;
}

.validation-item.validated {
    background: #f0f8f0;
    border-color: #4CAF50;
}

.validation-item.incorrect {
    background: #fff0f0;
    border-color: #f44336;
}

.entity-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    margin-right: 10px;
}

.entity-badge.product { background: #2196F3; color: white; }
.entity-badge.error { background: #f44336; color: white; }
.entity-badge.person { background: #9C27B0; color: white; }
.entity-badge.organization { background: #FF9800; color: white; }
.entity-badge.location { background: #4CAF50; color: white; }
.entity-badge.misc { background: #607D8B; color: white; }

.validation-buttons {
    margin-top: 10px;
}

.validation-buttons button {
    margin-right: 10px;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.btn-correct {
    background: #4CAF50;
    color: white;
}

.btn-incorrect {
    background: #f44336;
    color: white;
}

.btn-skip {
    background: #9E9E9E;
    color: white;
}

.confidence-score {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.confidence-high { background: #4CAF50; color: white; }
.confidence-medium { background: #FF9800; color: white; }
.confidence-low { background: #f44336; color: white; }

.sample-navigation {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
}

.sample-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.sample-link {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s ease;
}

.sample-link:hover {
    background: #f0f0f0;
}

.sample-link.active {
    background: #2196F3;
    color: white;
    border-color: #2196F3;
}

.sample-link.validated {
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
}

.notes-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 10px;
    font-family: inherit;
}

.complete-sample-btn {
    background: #4CAF50;
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
    margin-top: 20px;
}

.complete-sample-btn:hover {
    background: #45a049;
}
</style>

<div class="validation-container">
    <h1>🔍 AI Extraction Validation</h1>
    
    <!-- Progress Bar -->
    <div class="progress-bar-container">
        <div class="progress-bar" style="width: <?php echo $completion_pct; ?>%;">
            <?php echo $progress['validated_samples']; ?> / <?php echo $progress['total_samples']; ?> 
            (<?php echo $completion_pct; ?>%)
        </div>
    </div>
    
    <?php if ($sample && $ticket): ?>
        
        <!-- Sample Navigation -->
        <div class="sample-navigation">
            <h3>Sample Navigation</h3>
            <div class="sample-list">
                <?php foreach ($samples_list as $s): ?>
                    <a href="?sample_id=<?php echo $s['sample_id']; ?>" 
                       class="sample-link <?php echo $s['sample_id'] == $sample_id ? 'active' : ''; ?> <?php echo $s['validated'] ? 'validated' : ''; ?>">
                        <?php echo htmlspecialchars($s['ticket_number']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Ticket Display -->
        <div class="ticket-display">
            <h2>Ticket: <?php echo htmlspecialchars($ticket['ticket_number']); ?></h2>
            
            <div class="ticket-field">
                <label>Category:</label>
                <span><?php echo htmlspecialchars($ticket['category']); ?></span>
                <span style="margin-left: 20px;">Priority:</span>
                <span><?php echo htmlspecialchars($ticket['priority']); ?></span>
            </div>
            
            <div class="ticket-field">
                <label>Title:</label>
                <div class="content"><?php echo htmlspecialchars($ticket['title']); ?></div>
            </div>
            
            <div class="ticket-field">
                <label>Description:</label>
                <div class="content"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></div>
            </div>
            
            <?php if ($ticket['resolution']): ?>
            <div class="ticket-field">
                <label>Resolution:</label>
                <div class="content"><?php echo nl2br(htmlspecialchars($ticket['resolution'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Entity Validation -->
        <div class="validation-section">
            <h2>📋 Extracted Entities (<?php echo count($entities); ?>)</h2>
            <p>Review each extracted entity and mark if it's correctly identified.</p>
            
            <?php foreach ($entities as $entity): ?>
                <div class="validation-item <?php echo $entity['is_correct'] === '1' ? 'validated' : ($entity['is_correct'] === '0' ? 'incorrect' : ''); ?>" 
                     id="entity-<?php echo $entity['validation_id']; ?>">
                    <div>
                        <span class="entity-badge <?php echo strtolower($entity['entity_type']); ?>">
                            <?php echo htmlspecialchars($entity['entity_type']); ?>
                        </span>
                        <strong><?php echo htmlspecialchars($entity['entity_text']); ?></strong>
                        <span class="confidence-score <?php 
                            $conf = floatval($entity['extracted_confidence']);
                            echo $conf >= 0.8 ? 'confidence-high' : ($conf >= 0.6 ? 'confidence-medium' : 'confidence-low');
                        ?>">
                            Confidence: <?php echo number_format($conf * 100, 0); ?>%
                        </span>
                    </div>
                    
                    <?php if ($entity['is_correct'] === null): ?>
                    <div class="validation-buttons">
                        <button class="btn-correct" onclick="validateEntity(<?php echo $entity['validation_id']; ?>, 1)">
                            ✓ Correct
                        </button>
                        <button class="btn-incorrect" onclick="validateEntity(<?php echo $entity['validation_id']; ?>, 0)">
                            ✗ Incorrect
                        </button>
                        <input type="text" class="notes-input" id="entity-notes-<?php echo $entity['validation_id']; ?>" 
                               placeholder="Optional: Add notes or correct type...">
                    </div>
                    <?php else: ?>
                    <div style="margin-top: 10px; color: <?php echo $entity['is_correct'] === '1' ? '#4CAF50' : '#f44336'; ?>;">
                        <strong><?php echo $entity['is_correct'] === '1' ? '✓ Validated as Correct' : '✗ Marked as Incorrect'; ?></strong>
                        <?php if ($entity['notes']): ?>
                            <br><em>Notes: <?php echo htmlspecialchars($entity['notes']); ?></em>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($entities)): ?>
                <p><em>No entities extracted for this ticket.</em></p>
            <?php endif; ?>
        </div>
        
        <!-- Relationship Validation -->
        <div class="validation-section">
            <h2>🔗 Extracted Relationships (<?php echo count($relationships); ?>)</h2>
            <p>Review each extracted relationship and mark if it's correctly identified.</p>
            
            <?php foreach ($relationships as $rel): ?>
                <div class="validation-item <?php echo $rel['is_correct'] === '1' ? 'validated' : ($rel['is_correct'] === '0' ? 'incorrect' : ''); ?>" 
                     id="relationship-<?php echo $rel['validation_id']; ?>">
                    <div>
                        <code><?php echo htmlspecialchars($rel['source_entity']); ?></code>
                        <strong> --<?php echo htmlspecialchars($rel['edge_type']); ?>--> </strong>
                        <code><?php echo htmlspecialchars($rel['target_entity']); ?></code>
                        <span class="confidence-score <?php 
                            $conf = floatval($rel['extracted_confidence']);
                            echo $conf >= 0.8 ? 'confidence-high' : ($conf >= 0.6 ? 'confidence-medium' : 'confidence-low');
                        ?>">
                            Confidence: <?php echo number_format($conf * 100, 0); ?>%
                        </span>
                    </div>
                    
                    <?php if ($rel['is_correct'] === null): ?>
                    <div class="validation-buttons">
                        <button class="btn-correct" onclick="validateRelationship(<?php echo $rel['validation_id']; ?>, 1)">
                            ✓ Correct
                        </button>
                        <button class="btn-incorrect" onclick="validateRelationship(<?php echo $rel['validation_id']; ?>, 0)">
                            ✗ Incorrect
                        </button>
                        <input type="text" class="notes-input" id="relationship-notes-<?php echo $rel['validation_id']; ?>" 
                               placeholder="Optional: Add notes or correct type...">
                    </div>
                    <?php else: ?>
                    <div style="margin-top: 10px; color: <?php echo $rel['is_correct'] === '1' ? '#4CAF50' : '#f44336'; ?>;">
                        <strong><?php echo $rel['is_correct'] === '1' ? '✓ Validated as Correct' : '✗ Marked as Incorrect'; ?></strong>
                        <?php if ($rel['notes']): ?>
                            <br><em>Notes: <?php echo htmlspecialchars($rel['notes']); ?></em>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($relationships)): ?>
                <p><em>No relationships extracted for this ticket.</em></p>
            <?php endif; ?>
        </div>
        
        <!-- Complete Sample Button -->
        <?php if (!$sample['validated']): ?>
        <button class="complete-sample-btn" onclick="completeSample(<?php echo $sample_id; ?>)">
            ✓ Mark Sample as Complete
        </button>
        <?php else: ?>
        <div style="text-align: center; padding: 20px; background: #4CAF50; color: white; border-radius: 8px; font-size: 18px; font-weight: bold;">
            ✓ This sample has been validated
        </div>
        <?php endif; ?>
        
    <?php elseif ($progress['validated_samples'] >= $progress['total_samples']): ?>
        <div style="text-align: center; padding: 40px;">
            <h2>🎉 All Samples Validated!</h2>
            <p>You have completed validation of all <?php echo $progress['total_samples']; ?> samples.</p>
            <p><a href="../admin/ai_dashboard.php" class="btn btn-primary">View Metrics Dashboard</a></p>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px;">
            <h2>No Validation Samples Available</h2>
            <p>Please generate validation samples first using the sampling script.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function validateEntity(validationId, isCorrect) {
    const notes = document.getElementById('entity-notes-' + validationId)?.value || '';
    
    fetch('ai_validation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=validate_entity&validation_id=${validationId}&is_correct=${isCorrect}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('entity-' + validationId);
            item.classList.add(isCorrect ? 'validated' : 'incorrect');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function validateRelationship(validationId, isCorrect) {
    const notes = document.getElementById('relationship-notes-' + validationId)?.value || '';
    
    fetch('ai_validation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=validate_relationship&validation_id=${validationId}&is_correct=${isCorrect}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('relationship-' + validationId);
            item.classList.add(isCorrect ? 'validated' : 'incorrect');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function completeSample(sampleId) {
    if (!confirm('Mark this sample as complete? You can still edit it later.')) {
        return;
    }
    
    fetch('ai_validation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_sample_complete&sample_id=${sampleId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Go to next sample
            const currentIndex = <?php echo array_search($sample_id, array_column($samples_list, 'sample_id')); ?>;
            const nextSample = <?php echo json_encode($samples_list); ?>[currentIndex + 1];
            if (nextSample) {
                window.location.href = '?sample_id=' + nextSample.sample_id;
            } else {
                location.reload();
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php include '../includes/footer.php'; ?>
