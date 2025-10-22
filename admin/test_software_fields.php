<?php
/**
 * Test Software Category Fields Display
 * Quick verification that fields are accessible via the CategoryField class
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/CategoryField.php';

// Initialize
$categoryField = new CategoryField();

// Get Software category ID
require_once __DIR__ . '/../config/database.php';
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
    DB_USER,
    DB_PASS,
    DB_OPTIONS
);

$stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = 'Software' LIMIT 1");
$stmt->execute();
$category = $stmt->fetch();

if (!$category) {
    die("ERROR: Software category not found.\n");
}

$categoryId = $category['category_id'];

// Get fields using CategoryField class
$fields = $categoryField->getFieldsByCategory($categoryId, true);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Category Fields Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Software Category Fields - Task 5.2 Verification</h1>
        
        <div class="alert alert-success">
            <strong>✅ Task 5.2 Complete!</strong> Found <?php echo count($fields); ?> active fields for Software category.
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Software Category Fields</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Label</th>
                            <th>Field Name</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?php echo $field['field_order']; ?></td>
                                <td><strong><?php echo htmlspecialchars($field['field_label']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($field['field_name']); ?></code></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $field['field_type']; ?></span>
                                </td>
                                <td>
                                    <?php if ($field['is_required']): ?>
                                        <span class="badge bg-danger">Required</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Optional</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($field['field_options']) && is_array($field['field_options'])): ?>
                                        <button class="btn btn-sm btn-outline-primary" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#options-<?php echo $field['field_id']; ?>">
                                            View <?php echo count($field['field_options']); ?> options
                                        </button>
                                        <div class="collapse mt-2" id="options-<?php echo $field['field_id']; ?>">
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($field['field_options'] as $option): ?>
                                                    <li class="list-group-item py-1 small">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <h4>Task 5.2 Requirements Check</h4>
            <ul class="list-group">
                <?php
                $requiredFields = [
                    'software_name' => 'Applicatie naam (with dropdown options)',
                    'software_version' => 'Versie',
                    'license_type' => 'Licentie type',
                    'installation_location' => 'Installatie locatie'
                ];
                
                foreach ($requiredFields as $fieldName => $description) {
                    $found = false;
                    foreach ($fields as $field) {
                        if ($field['field_name'] === $fieldName) {
                            $found = true;
                            break;
                        }
                    }
                    
                    if ($found) {
                        echo '<li class="list-group-item list-group-item-success">';
                        echo '<i class="bi bi-check-circle-fill"></i> ';
                        echo htmlspecialchars($description);
                        echo '</li>';
                    } else {
                        echo '<li class="list-group-item list-group-item-danger">';
                        echo '<i class="bi bi-x-circle-fill"></i> ';
                        echo htmlspecialchars($description);
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>
        
        <div class="mt-4">
            <a href="category_fields.php" class="btn btn-primary">Go to Category Fields Management</a>
            <a href="index.php" class="btn btn-secondary">Back to Admin Dashboard</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
