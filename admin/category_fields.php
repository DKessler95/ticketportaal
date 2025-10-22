<?php
/**
 * Admin Category Fields Management Page
 * 
 * Allows administrators to configure dynamic fields for each ticket category
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/CategoryField.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize CategoryField class
$categoryField = new CategoryField();

// Get all categories with field counts
$categories = $categoryField->getCategoriesWithFieldCounts();

// Page title
$pageTitle = 'Categorie Velden Beheer';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .category-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .field-count-badge {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
        }
        
        /* Drag & Drop Styles */
        .sortable-ghost {
            opacity: 0.4;
            background: #f8f9fa;
        }
        .sortable-chosen {
            background: #e7f3ff;
            border-color: #0d6efd;
        }
        .sortable-drag {
            opacity: 0.8;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .drag-handle {
            cursor: move;
        }
        .drag-handle:hover {
            color: #0d6efd !important;
        }
    </style>
</head>
<body>
    <!-- Global CSRF Token for AJAX requests -->
    <input type="hidden" id="global_csrf_token" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p class="text-muted">Configureer dynamische velden per categorie</p>
                </div>
                <div>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Terug naar Categorieën
                    </a>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Dynamische Velden:</strong> Configureer extra velden die gebruikers moeten invullen bij het aanmaken van een ticket in een specifieke categorie.
                Bijvoorbeeld: voor de categorie "Hardware" kun je velden toevoegen zoals "Type apparaat", "Serienummer", etc.
            </div>

            <!-- Categories Grid -->
            <div class="row">
                <?php if (empty($categories)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Geen categorieën gevonden. <a href="categories.php">Maak eerst een categorie aan</a>.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card category-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </h5>
                                        <?php if ($category['is_active']): ?>
                                            <span class="badge bg-success">Actief</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactief</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($category['description'])): ?>
                                        <p class="card-text text-muted small">
                                            <?php echo htmlspecialchars($category['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="badge field-count-badge <?php echo $category['active_field_count'] > 0 ? 'bg-primary' : 'bg-secondary'; ?>">
                                                <i class="bi bi-input-cursor-text"></i>
                                                <?php echo $category['active_field_count']; ?> 
                                                <?php echo $category['active_field_count'] == 1 ? 'veld' : 'velden'; ?>
                                            </span>
                                            <?php if ($category['field_count'] != $category['active_field_count']): ?>
                                                <small class="text-muted ms-2">
                                                    (<?php echo $category['field_count'] - $category['active_field_count']; ?> inactief)
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-grid gap-2">
                                        <button 
                                            class="btn btn-primary" 
                                            onclick="manageFields(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')">
                                            <i class="bi bi-gear"></i> Velden Beheren
                                        </button>
                                        <?php if ($category['field_count'] > 0): ?>
                                            <button 
                                                class="btn btn-outline-info btn-sm" 
                                                onclick="previewFields(<?php echo $category['category_id']; ?>)">
                                                <i class="bi bi-eye"></i> Preview
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Field Management Modal -->
    <div class="modal fade" id="fieldManagementModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Velden Beheren: <span id="modalCategoryName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Fields List -->
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Bestaande Velden</h6>
                                <button class="btn btn-sm btn-primary" onclick="showFieldForm('create')">
                                    <i class="bi bi-plus-circle"></i> Nieuw Veld
                                </button>
                            </div>
                            
                            <div id="fieldsListContainer">
                                <div class="text-center text-muted py-4">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Laden...</span>
                                    </div>
                                    <p class="mt-2">Velden laden...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Field Form -->
                        <div class="col-md-5">
                            <div id="fieldFormContainer" style="display: none;">
                                <h6 class="mb-3">
                                    <span id="formTitle">Veld Toevoegen</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="hideFieldForm()">
                                        <i class="bi bi-x"></i> Annuleren
                                    </button>
                                </h6>
                                
                                <!-- Preview Section -->
                                <div class="card mb-3 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">
                                            <i class="bi bi-eye"></i> Preview
                                        </h6>
                                        <div id="fieldPreview">
                                            <p class="text-muted small mb-0">Preview wordt hier getoond...</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <form id="fieldForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" id="formAction" value="create">
                                    <input type="hidden" name="category_id" id="formCategoryId">
                                    <input type="hidden" name="field_id" id="formFieldId">
                                    
                                    <div class="mb-3">
                                        <label for="fieldName" class="form-label">Veld Naam (slug) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="fieldName" name="field_name" required
                                               placeholder="bijv: hardware_type">
                                        <small class="text-muted">Alleen kleine letters, cijfers en underscores</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fieldLabel" class="form-label">Label <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="fieldLabel" name="field_label" required
                                               placeholder="bijv: Type Hardware">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fieldType" class="form-label">Veld Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="fieldType" name="field_type" required onchange="handleFieldTypeChange()">
                                            <option value="">-- Selecteer --</option>
                                            <option value="text">Tekst (kort)</option>
                                            <option value="textarea">Tekst (lang)</option>
                                            <option value="select">Dropdown</option>
                                            <option value="radio">Radio buttons</option>
                                            <option value="checkbox">Checkboxes</option>
                                            <option value="date">Datum</option>
                                            <option value="number">Nummer</option>
                                            <option value="email">Email</option>
                                            <option value="tel">Telefoon</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3" id="optionsContainer" style="display: none;">
                                        <label for="fieldOptions" class="form-label">Opties (één per regel)</label>
                                        <textarea class="form-control" id="fieldOptions" rows="4" 
                                                  placeholder="Optie 1&#10;Optie 2&#10;Optie 3"></textarea>
                                        <small class="text-muted">Voor dropdown, radio en checkbox velden</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fieldPlaceholder" class="form-label">Placeholder</label>
                                        <input type="text" class="form-control" id="fieldPlaceholder" name="placeholder"
                                               placeholder="bijv: Voer serienummer in">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fieldHelpText" class="form-label">Help Tekst</label>
                                        <input type="text" class="form-control" id="fieldHelpText" name="help_text"
                                               placeholder="Extra uitleg voor gebruiker">
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="fieldRequired" name="is_required" value="1">
                                        <label class="form-check-label" for="fieldRequired">
                                            Verplicht veld
                                        </label>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Opslaan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div id="fieldFormPlaceholder">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-arrow-left" style="font-size: 2rem;"></i>
                                    <p class="mt-2">Selecteer een veld om te bewerken<br>of klik op "Nieuw Veld"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/category-fields-manager.js"></script>
</body>
</html>
