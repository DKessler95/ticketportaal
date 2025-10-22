<?php
/**
 * Template Management Page (Admin)
 * 
 * Manage ticket resolution templates
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Template.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize Template class
$templateClass = new Template();

// Initialize variables
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $content = $_POST['content'] ?? '';
            $templateType = $_POST['template_type'] ?? 'resolution';
            
            if (empty($name) || empty($content)) {
                $errors[] = 'Name and content are required';
            } else {
                $templateId = $templateClass->createTemplate($name, $description, $content, $templateType, $userId);
                
                if ($templateId) {
                    $success = 'Template created successfully';
                } else {
                    $errors[] = $templateClass->getError() ?: 'Failed to create template';
                }
            }
        } elseif ($action === 'update') {
            $templateId = filter_var($_POST['template_id'] ?? 0, FILTER_VALIDATE_INT);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $content = $_POST['content'] ?? '';
            $templateType = $_POST['template_type'] ?? 'resolution';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name) || empty($content)) {
                $errors[] = 'Name and content are required';
            } else {
                $result = $templateClass->updateTemplate($templateId, $name, $description, $content, $templateType, $isActive);
                
                if ($result) {
                    $success = 'Template updated successfully';
                } else {
                    $errors[] = $templateClass->getError() ?: 'Failed to update template';
                }
            }
        } elseif ($action === 'delete') {
            $templateId = filter_var($_POST['template_id'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($templateClass->deleteTemplate($templateId)) {
                $success = 'Template deleted successfully';
            } else {
                $errors[] = $templateClass->getError() ?: 'Failed to delete template';
            }
        } elseif ($action === 'toggle') {
            $templateId = filter_var($_POST['template_id'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($templateClass->toggleActive($templateId)) {
                $success = 'Template status updated';
            } else {
                $errors[] = $templateClass->getError() ?: 'Failed to update template status';
            }
        }
    }
}

// Get all templates
$templates = $templateClass->getTemplates();

// Get template for editing if ID is provided
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $editTemplate = $templateClass->getTemplateById($editId);
    }
}

$pageTitle = 'Template Management';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeOutput($pageTitle . ' - ' . SITE_NAME); ?></title>
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
                    <h1 class="h2"><i class="bi bi-file-text"></i> Sjabloonbeheer</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="resetForm()">
                        <i class="bi bi-plus-circle"></i> Nieuw Sjabloon
                    </button>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Fout</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escapeOutput($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo escapeOutput($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Info Alert -->
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Hoe sjablonen te gebruiken</h5>
                    <p class="mb-2"><strong>Sjablonen maken:</strong></p>
                    <ol class="mb-2">
                        <li>Klik op "Nieuw Sjabloon"</li>
                        <li>Vul de naam, type (Comment of Resolution) en inhoud in</li>
                        <li>Klik op "Sjabloon Opslaan"</li>
                    </ol>
                    <p class="mb-2"><strong>Sjablonen gebruiken in tickets:</strong></p>
                    <ul class="mb-0">
                        <li><strong>Bij comments:</strong> Ga naar een ticket → Scroll naar "Add Comment" → Selecteer een sjabloon uit de dropdown boven het tekstveld</li>
                        <li><strong>Bij resolution:</strong> Ga naar een ticket → Wijzig status naar "Resolved" → Selecteer een sjabloon uit de dropdown boven het resolution veld</li>
                        <li>De sjabloon inhoud wordt automatisch ingevuld en kan nog aangepast worden</li>
                    </ul>
                </div>

                <!-- Templates List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sjablonen</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($templates)): ?>
                            <p class="text-muted">Geen sjablonen gevonden.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Naam</th>
                                            <th>Type</th>
                                            <th>Beschrijving</th>
                                            <th>Status</th>
                                            <th>Aangemaakt Door</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($templates as $template): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($template['name']); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo ucfirst($template['template_type']); ?></span>
                                                </td>
                                                <td><?php echo escapeOutput(substr($template['description'], 0, 50)) . (strlen($template['description']) > 50 ? '...' : ''); ?></td>
                                                <td>
                                                    <?php if ($template['is_active']): ?>
                                                        <span class="badge bg-success">Actief</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactief</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo escapeOutput($template['first_name'] . ' ' . $template['last_name']); ?></td>
                                                <td style="min-width: 200px;">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-outline-primary" onclick="editTemplate(<?php echo $template['template_id']; ?>)" title="Bewerken">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-outline-info" onclick="previewTemplate(<?php echo $template['template_id']; ?>)" title="Voorbeeld">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="toggle">
                                                            <input type="hidden" name="template_id" value="<?php echo $template['template_id']; ?>">
                                                            <button type="submit" class="btn btn-outline-warning" title="Toggle actief/inactief">
                                                                <i class="bi bi-toggle-<?php echo $template['is_active'] ? 'on' : 'off'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-outline-danger" onclick="deleteTemplate(<?php echo $template['template_id']; ?>)" title="Verwijderen">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="" id="templateForm" onsubmit="return saveTemplate(event)">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nieuw Sjabloon</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="template_id" id="templateId" value="">

                        <div class="mb-3">
                            <label for="name" class="form-label">Sjabloonnaam <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="template_type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="template_type" name="template_type" required>
                                <option value="resolution">Oplossing</option>
                                <option value="comment">Opmerking</option>
                                <option value="email">E-mail</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Beschrijving</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Inhoud <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="10"></textarea>
                        </div>

                        <div class="mb-3 form-check" id="activeCheckContainer" style="display: none;">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Actief</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-primary">Sjabloon Opslaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sjabloon Voorbeeld</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="previewContent">
                    <!-- Preview content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Verwijderen Bevestigen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="template_id" id="deleteTemplateId">
                        <p>Weet je zeker dat je dit sjabloon wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-danger">Verwijderen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TinyMCE for Content -->
    <script src="https://cdn.tiny.cloud/1/f5xc5i53b0di57yjmcf5954fyhbtmb9k28r3pu0nn19ol86c/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        const templates = <?php echo json_encode($templates); ?>;
        
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: ['lists', 'link', 'code', 'table'],
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat code',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            statusbar: false,
            resize: true,
            setup: function(editor) {
                editor.on('init', function() {
                    // Form submit handler is added outside of TinyMCE init
                });
            }
        });
        
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Nieuw Sjabloon';
            document.getElementById('formAction').value = 'create';
            document.getElementById('templateId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('template_type').value = 'resolution';
            document.getElementById('description').value = '';
            tinymce.get('content').setContent('');
            document.getElementById('activeCheckContainer').style.display = 'none';
        }
        
        function editTemplate(templateId) {
            const template = templates.find(t => t.template_id == templateId);
            if (!template) return;
            
            document.getElementById('modalTitle').textContent = 'Sjabloon Bewerken';
            document.getElementById('formAction').value = 'update';
            document.getElementById('templateId').value = template.template_id;
            document.getElementById('name').value = template.name;
            document.getElementById('template_type').value = template.template_type;
            document.getElementById('description').value = template.description || '';
            tinymce.get('content').setContent(template.content);
            document.getElementById('is_active').checked = template.is_active == 1;
            document.getElementById('activeCheckContainer').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('templateModal')).show();
        }
        
        function previewTemplate(templateId) {
            const template = templates.find(t => t.template_id == templateId);
            if (!template) return;
            
            document.getElementById('previewContent').innerHTML = template.content;
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        }
        
        function deleteTemplate(templateId) {
            document.getElementById('deleteTemplateId').value = templateId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Form submit handler - trigger TinyMCE save before submit
        function saveTemplate(event) {
            // Trigger TinyMCE to save content to textarea
            if (tinymce.get('content')) {
                tinymce.get('content').save();
            }
            
            // Validate content is not empty
            const content = document.getElementById('content').value.trim();
            if (!content || content === '') {
                alert('Inhoud is verplicht. Vul de inhoud van het sjabloon in.');
                return false;
            }
            
            // Allow form to submit normally
            return true;
        }
    </script>
</body>
</html>
