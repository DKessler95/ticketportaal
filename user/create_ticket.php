<?php
/**
 * Create Ticket Page
 * 
 * Form for users to create new support tickets
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and check authentication
initSession();
requireRole('user');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize classes
$ticketClass = new Ticket();
$categoryClass = new Category();

// Get active categories
$categories = $categoryClass->getCategories(true); // true = active only

// Get pre-selected type from URL parameter
$preselectedType = $_GET['type'] ?? null;
$validTypes = ['incident', 'service_request', 'change_request', 'feature_request'];
if ($preselectedType && !in_array($preselectedType, $validTypes)) {
    $preselectedType = null;
}

// Initialize variables
$errors = [];
$success = false;
$ticketNumber = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = $_POST['description'] ?? ''; // Don't sanitize yet, we need original for storage
        $categoryId = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);
        $ticketType = $_POST['ticket_type'] ?? 'incident';
        $priority = $_POST['priority'] ?? 'medium';
        
        // Validation
        if (empty($title)) {
            $errors[] = 'Title is required';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Title is too long (maximum 255 characters)';
        }
        
        if (empty($description)) {
            $errors[] = 'Description is required';
        }
        
        if (empty($categoryId) || $categoryId <= 0) {
            $errors[] = 'Please select a category';
        }
        
        // Validate ticket type
        $validTypes = ['incident', 'service_request', 'change_request', 'feature_request'];
        if (!in_array($ticketType, $validTypes)) {
            $ticketType = 'incident';
        }
        
        // Validate priority
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities)) {
            $priority = 'medium';
        }
        
        // Validate dynamic field values if present
        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            $fieldErrors = $ticketClass->validateFieldValues($categoryId, $_POST['fields']);
            if (!empty($fieldErrors)) {
                $errors = array_merge($errors, $fieldErrors);
            }
        }
        
        // If no errors, create ticket
        if (empty($errors)) {
            $ticketId = $ticketClass->createTicket(
                $userId,
                $title,
                $description,
                $categoryId,
                $priority,
                'web',
                $ticketType
            );
            
            if ($ticketId) {
                // Save dynamic field values if present
                if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                    $ticketClass->saveFieldValues($ticketId, $_POST['fields']);
                }
                
                // Handle file upload if present
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $fileValidation = validateFileUpload($_FILES['attachment']);
                    
                    if ($fileValidation['success']) {
                        $file = $_FILES['attachment'];
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $newFilename = generateRandomFilename($extension);
                        $uploadDir = UPLOAD_PATH . 'tickets/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $uploadPath = $uploadDir . $newFilename;
                        
                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            $ticketClass->addAttachment(
                                $ticketId,
                                $file['name'],
                                'tickets/' . $newFilename,
                                $file['size']
                            );
                        }
                    } else {
                        // File upload failed, but ticket was created
                        $errors[] = 'Ticket created, but file upload failed: ' . $fileValidation['error'];
                    }
                }
                
                // Get ticket details for success message
                $ticket = $ticketClass->getTicketById($ticketId);
                $ticketNumber = $ticket['ticket_number'];
                $success = true;
                
                // TODO: Send confirmation email (will be implemented in task 9)
            } else {
                $errors[] = $ticketClass->getError() ?: 'Failed to create ticket. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create Ticket';
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
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4 mb-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <?php if ($preselectedType): ?>
                    <?php
                    $typeInfo = [
                        'incident' => ['icon' => 'exclamation-triangle-fill', 'color' => 'danger', 'label' => 'Incident'],
                        'service_request' => ['icon' => 'person-plus-fill', 'color' => 'info', 'label' => 'Service Request'],
                        'change_request' => ['icon' => 'arrow-repeat', 'color' => 'warning', 'label' => 'Change Request'],
                        'feature_request' => ['icon' => 'lightbulb-fill', 'color' => 'success', 'label' => 'Wens/Feature']
                    ];
                    $info = $typeInfo[$preselectedType];
                    ?>
                    <div class="alert alert-<?php echo $info['color']; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?php echo $info['icon']; ?>"></i>
                        <strong>Type aanvraag:</strong> <?php echo $info['label']; ?>
                        <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn-close" aria-label="Close"></a>
                    </div>
                <?php endif; ?>
                <h1 class="mb-4"><i class="bi bi-plus-circle"></i> Nieuw Ticket Aanmaken</h1>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Ticket Succesvol Aangemaakt!</h5>
                        <p>Uw ticket is aangemaakt met nummer: <strong><?php echo escapeOutput($ticketNumber); ?></strong></p>
                        <p class="mb-0">U ontvangt binnenkort een bevestigingsmail. U kunt de voortgang van uw ticket volgen vanaf uw dashboard.</p>
                        <hr>
                        <div class="d-flex gap-2">
                            <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn btn-success">Naar Dashboard</a>
                            <a href="<?php echo SITE_URL; ?>/user/create_ticket.php" class="btn btn-outline-success">Nog een Ticket Aanmaken</a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Corrigeer de volgende fouten:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escapeOutput($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data" id="createTicketForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                                <div class="mb-3">
                                    <label for="title" class="form-label">Titel <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control <?php echo isset($errors) && in_array('Title is required', $errors) ? 'is-invalid' : ''; ?>" 
                                           id="title" 
                                           name="title" 
                                           maxlength="255"
                                           value="<?php echo isset($_POST['title']) ? escapeOutput($_POST['title']) : ''; ?>"
                                           required>
                                    <div class="form-text">Geef een korte, beschrijvende titel voor uw probleem</div>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Categorie <span class="text-danger">*</span></label>
                                    <select class="form-select <?php echo isset($errors) && in_array('Please select a category', $errors) ? 'is-invalid' : ''; ?>" 
                                            id="category_id" 
                                            name="category_id" 
                                            required>
                                        <option value="">-- Selecteer een categorie --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" 
                                                    data-priority="<?php echo $category['default_priority']; ?>"
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo escapeOutput($category['name']); ?>
                                                <?php if ($category['description']): ?>
                                                    - <?php echo escapeOutput($category['description']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Selecteer de categorie die uw probleem het beste beschrijft</div>
                                </div>

                                <?php 
                                $selectedType = $_POST['ticket_type'] ?? $preselectedType ?? 'incident';
                                $typeLabels = [
                                    'incident' => 'Incident - Er is iets kapot of werkt niet',
                                    'service_request' => 'Service Request - Account aanvraag, toegang, installatie',
                                    'change_request' => 'Change Request - Wijziging in systeem of configuratie',
                                    'feature_request' => 'Wens/Feature - Nieuwe functionaliteit of software aanvraag'
                                ];
                                ?>
                                
                                <?php if ($preselectedType): ?>
                                    <!-- Type is locked when coming from dashboard -->
                                    <input type="hidden" name="ticket_type" value="<?php echo escapeOutput($selectedType); ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Type Aanvraag <span class="text-danger">*</span></label>
                                        <div class="form-control bg-light" style="cursor: not-allowed;">
                                            <?php echo escapeOutput($typeLabels[$selectedType]); ?>
                                        </div>
                                        <div class="form-text">Type is vastgezet op basis van uw selectie</div>
                                    </div>
                                <?php else: ?>
                                    <!-- Type is selectable when accessing directly -->
                                    <div class="mb-3">
                                        <label for="ticket_type" class="form-label">Type Aanvraag <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ticket_type" name="ticket_type" required>
                                            <option value="incident" <?php echo ($selectedType === 'incident') ? 'selected' : ''; ?>>
                                                Incident - Er is iets kapot of werkt niet
                                            </option>
                                            <option value="service_request" <?php echo ($selectedType === 'service_request') ? 'selected' : ''; ?>>
                                                Service Request - Account aanvraag, toegang, installatie
                                            </option>
                                            <option value="change_request" <?php echo ($selectedType === 'change_request') ? 'selected' : ''; ?>>
                                                Change Request - Wijziging in systeem of configuratie
                                            </option>
                                            <option value="feature_request" <?php echo ($selectedType === 'feature_request') ? 'selected' : ''; ?>>
                                                Wens/Feature - Nieuwe functionaliteit of software aanvraag
                                            </option>
                                        </select>
                                        <div class="form-text">Selecteer het type aanvraag dat het beste past bij uw situatie</div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="priority" class="form-label">Prioriteit <span class="text-danger">*</span></label>
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>Laag</option>
                                        <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>Gemiddeld</option>
                                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>Hoog</option>
                                        <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                    </select>
                                    <div class="form-text">Prioriteit wordt automatisch ingesteld op basis van categorie, maar u kunt deze aanpassen indien nodig</div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Beschrijving <span class="text-danger">*</span></label>
                                    <textarea class="form-control <?php echo isset($errors) && in_array('Description is required', $errors) ? 'is-invalid' : ''; ?>" 
                                              id="description" 
                                              name="description" 
                                              rows="6"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                                    <div class="form-text">Geef gedetailleerde informatie over uw probleem. Vermeld eventuele foutmeldingen, stappen om te reproduceren en wat u al geprobeerd heeft.</div>
                                </div>

                                <!-- Dynamic Category Fields Container -->
                                <div id="dynamicFieldsContainer"></div>

                                <div class="mb-3">
                                    <label for="attachment" class="form-label">Bijlage (Optioneel)</label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="attachment" 
                                           name="attachment"
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt,.zip">
                                    <div class="form-text">
                                        Maximale bestandsgrootte: <?php echo (MAX_FILE_SIZE / 1048576); ?>MB. 
                                        Toegestane types: <?php echo implode(', ', ALLOWED_EXTENSIONS); ?>
                                    </div>
                                    <div id="filePreview" class="mt-2"></div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Annuleren
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Ticket Aanmaken
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-light">
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dynamic Fields -->
    <script src="<?php echo SITE_URL; ?>/assets/js/dynamic-fields.js"></script>
    
    <!-- TinyMCE for Description -->
    <script src="https://cdn.tiny.cloud/1/f5xc5i53b0di57yjmcf5954fyhbtmb9k28r3pu0nn19ol86c/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Initialize TinyMCE for description field
        tinymce.init({
            selector: '#description',
            height: 400,
            menubar: false,
            plugins: ['lists', 'link', 'code', 'table', 'image'],
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | removeformat code',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            statusbar: false,
            resize: true
        });
        
        // Auto-set priority based on category selection
        document.getElementById('category_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const defaultPriority = selectedOption.getAttribute('data-priority');
            
            if (defaultPriority) {
                document.getElementById('priority').value = defaultPriority;
            }
        });

        // File upload preview and validation
        document.getElementById('attachment').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('filePreview');
            const maxSize = <?php echo MAX_FILE_SIZE; ?>;
            
            if (file) {
                // Check file size
                if (file.size > maxSize) {
                    preview.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Bestandsgrootte overschrijdt maximum toegestaan (' + (maxSize / 1048576) + 'MB)</div>';
                    this.value = '';
                    return;
                }
                
                // Show file info
                const fileSize = (file.size / 1024).toFixed(2);
                preview.innerHTML = '<div class="alert alert-info"><i class="bi bi-file-earmark"></i> <strong>' + file.name + '</strong> (' + fileSize + ' KB)</div>';
            } else {
                preview.innerHTML = '';
            }
        });

        // Form validation and submission
        document.getElementById('createTicketForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const title = document.getElementById('title').value.trim();
            const category = document.getElementById('category_id').value;
            
            // Save TinyMCE content to textarea
            if (tinymce.get('description')) {
                tinymce.get('description').save();
                
                // Get TinyMCE content for validation
                const description = tinymce.get('description').getContent({format: 'text'}).trim();
                
                if (!title || !category || !description) {
                    alert('Vul alle verplichte velden in');
                    return false;
                }
            }
            
            // Submit the form
            this.submit();
        });
    </script>
</body>
</html>
