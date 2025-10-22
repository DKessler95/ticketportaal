<?php
/**
 * Category Fields Preview
 * 
 * Shows a preview of how the dynamic fields will appear in the ticket creation form
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/CategoryField.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get category ID
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if (!$categoryId) {
    die('Invalid category ID');
}

// Initialize classes
$categoryField = new CategoryField();
$categoryObj = new Category();

// Get category details
$category = $categoryObj->getCategoryById($categoryId);

if (!$category) {
    die('Category not found');
}

// Get fields for this category
$fields = $categoryField->getFieldsByCategory($categoryId);

$pageTitle = 'Velden Preview - ' . $category['name'];
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
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-eye"></i> Velden Preview
                            </h5>
                            <button onclick="window.close()" class="btn btn-sm btn-light">
                                <i class="bi bi-x-lg"></i> Sluiten
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Preview voor categorie:</strong> <?php echo escapeOutput($category['name']); ?>
                            <br>
                            <small>Dit is hoe de dynamische velden eruit zien in het ticket aanmaak formulier.</small>
                        </div>

                        <?php if (empty($fields)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                Geen velden geconfigureerd voor deze categorie.
                            </div>
                        <?php else: ?>
                            <form class="needs-validation" novalidate>
                                <h6 class="mb-3">Dynamische Velden:</h6>
                                
                                <?php foreach ($fields as $field): ?>
                                    <?php if ($field['is_active']): ?>
                                        <div class="mb-3">
                                            <label for="field_<?php echo $field['field_id']; ?>" class="form-label">
                                                <?php echo escapeOutput($field['field_label']); ?>
                                                <?php if ($field['is_required']): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                            </label>
                                            
                                            <?php
                                            $fieldName = 'field_' . $field['field_id'];
                                            $fieldId = 'field_' . $field['field_id'];
                                            $placeholder = $field['placeholder'] ?? '';
                                            $required = $field['is_required'] ? 'required' : '';
                                            
                                            switch ($field['field_type']):
                                                case 'text':
                                                case 'email':
                                                case 'tel':
                                                case 'number':
                                            ?>
                                                    <input type="<?php echo $field['field_type']; ?>" 
                                                           class="form-control" 
                                                           id="<?php echo $fieldId; ?>" 
                                                           name="<?php echo $fieldName; ?>"
                                                           placeholder="<?php echo escapeOutput($placeholder); ?>"
                                                           <?php echo $required; ?>>
                                            <?php
                                                    break;
                                                    
                                                case 'textarea':
                                            ?>
                                                    <textarea class="form-control" 
                                                              id="<?php echo $fieldId; ?>" 
                                                              name="<?php echo $fieldName; ?>"
                                                              rows="3"
                                                              placeholder="<?php echo escapeOutput($placeholder); ?>"
                                                              <?php echo $required; ?>></textarea>
                                            <?php
                                                    break;
                                                    
                                                case 'select':
                                                    $options = $field['field_options'] ?? [];
                                            ?>
                                                    <select class="form-select" 
                                                            id="<?php echo $fieldId; ?>" 
                                                            name="<?php echo $fieldName; ?>"
                                                            <?php echo $required; ?>>
                                                        <option value="">-- Selecteer --</option>
                                                        <?php foreach ($options as $option): ?>
                                                            <option value="<?php echo escapeOutput($option); ?>">
                                                                <?php echo escapeOutput($option); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                            <?php
                                                    break;
                                                    
                                                case 'radio':
                                                    $options = $field['field_options'] ?? [];
                                                    foreach ($options as $index => $option):
                                            ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" 
                                                                   type="radio" 
                                                                   name="<?php echo $fieldName; ?>" 
                                                                   id="<?php echo $fieldId . '_' . $index; ?>"
                                                                   value="<?php echo escapeOutput($option); ?>"
                                                                   <?php echo $required; ?>>
                                                            <label class="form-check-label" for="<?php echo $fieldId . '_' . $index; ?>">
                                                                <?php echo escapeOutput($option); ?>
                                                            </label>
                                                        </div>
                                            <?php
                                                    endforeach;
                                                    break;
                                                    
                                                case 'checkbox':
                                                    $options = $field['field_options'] ?? [];
                                                    foreach ($options as $index => $option):
                                            ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="<?php echo $fieldName; ?>[]" 
                                                                   id="<?php echo $fieldId . '_' . $index; ?>"
                                                                   value="<?php echo escapeOutput($option); ?>">
                                                            <label class="form-check-label" for="<?php echo $fieldId . '_' . $index; ?>">
                                                                <?php echo escapeOutput($option); ?>
                                                            </label>
                                                        </div>
                                            <?php
                                                    endforeach;
                                                    break;
                                                    
                                                case 'date':
                                            ?>
                                                    <input type="date" 
                                                           class="form-control" 
                                                           id="<?php echo $fieldId; ?>" 
                                                           name="<?php echo $fieldName; ?>"
                                                           <?php echo $required; ?>>
                                            <?php
                                                    break;
                                            endswitch;
                                            ?>
                                            
                                            <?php if (!empty($field['help_text'])): ?>
                                                <small class="form-text text-muted">
                                                    <?php echo escapeOutput($field['help_text']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <div class="alert alert-secondary mt-4">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Let op:</strong> Dit is alleen een preview. Het formulier kan niet worden verzonden.
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <button onclick="window.close()" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Sluiten
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
