<?php
/**
 * CI Create Page
 * Create new Configuration Items
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/ConfigurationItem.php';
require_once __DIR__ . '/../classes/User.php';

initSession();
requireRole(['admin', 'agent']);

$ci = new ConfigurationItem();
$userObj = new User();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    
    $data = [
        'type' => $_POST['type'] ?? '',
        'category' => sanitizeInput($_POST['category'] ?? ''),
        'brand' => sanitizeInput($_POST['brand'] ?? ''),
        'model' => sanitizeInput($_POST['model'] ?? ''),
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'serial_number' => sanitizeInput($_POST['serial_number'] ?? ''),
        'asset_tag' => sanitizeInput($_POST['asset_tag'] ?? ''),
        'status' => $_POST['status'] ?? 'In voorraad',
        'owner_id' => !empty($_POST['owner_id']) ? (int)$_POST['owner_id'] : null,
        'department' => sanitizeInput($_POST['department'] ?? ''),
        'location' => sanitizeInput($_POST['location'] ?? ''),
        'purchase_date' => $_POST['purchase_date'] ?? null,
        'purchase_price' => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
        'supplier' => sanitizeInput($_POST['supplier'] ?? ''),
        'warranty_expiry' => $_POST['warranty_expiry'] ?? null,
        'notes' => sanitizeText($_POST['notes'] ?? '')
    ];
    
    if (empty($data['name'])) {
        $error = 'Naam is verplicht';
    } elseif (empty($data['type'])) {
        $error = 'Type is verplicht';
    } else {
        $ciId = $ci->createCI($data, getCurrentUserId());
        
        if ($ciId) {
            $success = 'CI succesvol aangemaakt';
            header("refresh:2;url=ci_edit.php?id=$ciId");
        } else {
            $error = $ci->getError() ?: 'Fout bij aanmaken CI';
        }
    }
}

// Get all users for owner dropdown
$users = $userObj->getAllUsers();

$pageTitle = 'Nieuw CI';
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
                    <h1 class="h2">Nieuw Configuration Item</h1>
                    <a href="ci_manage.php" class="btn btn-secondary">Terug naar lijst</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <?php outputCSRFField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Naam *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo escapeOutput($_POST['name'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="type" class="form-label">Type *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Selecteer type</option>
                                        <option value="Hardware" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Hardware') ? 'selected' : ''; ?>>Hardware</option>
                                        <option value="Software" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Software') ? 'selected' : ''; ?>>Software</option>
                                        <option value="Licentie" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Licentie') ? 'selected' : ''; ?>>Licentie</option>
                                        <option value="Overig" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Overig') ? 'selected' : ''; ?>>Overig</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="In voorraad" selected>In voorraad</option>
                                        <option value="In gebruik">In gebruik</option>
                                        <option value="Defect">Defect</option>
                                        <option value="Onderhoud">Onderhoud</option>
                                        <option value="Afgeschreven">Afgeschreven</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="category" class="form-label">Categorie</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           value="<?php echo escapeOutput($_POST['category'] ?? ''); ?>"
                                           placeholder="Laptop, Monitor, Office 365, etc.">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="brand" class="form-label">Merk</label>
                                    <input type="text" class="form-control" id="brand" name="brand" 
                                           value="<?php echo escapeOutput($_POST['brand'] ?? ''); ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="model" class="form-label">Model</label>
                                    <input type="text" class="form-control" id="model" name="model" 
                                           value="<?php echo escapeOutput($_POST['model'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="serial_number" class="form-label">Serienummer</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                           value="<?php echo escapeOutput($_POST['serial_number'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="asset_tag" class="form-label">Asset Tag</label>
                                    <input type="text" class="form-control" id="asset_tag" name="asset_tag" 
                                           value="<?php echo escapeOutput($_POST['asset_tag'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="owner_id" class="form-label">Eigenaar</label>
                                    <select class="form-select" id="owner_id" name="owner_id">
                                        <option value="">Geen eigenaar</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['user_id']; ?>"
                                                    <?php echo (isset($_POST['owner_id']) && $_POST['owner_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                                <?php echo escapeOutput($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Afdeling</label>
                                    <select class="form-select" id="department" name="department">
                                        <option value="">Selecteer afdeling</option>
                                        <option value="IT" <?php echo (isset($_POST['department']) && $_POST['department'] === 'IT') ? 'selected' : ''; ?>>IT</option>
                                        <option value="HR" <?php echo (isset($_POST['department']) && $_POST['department'] === 'HR') ? 'selected' : ''; ?>>HR</option>
                                        <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                        <option value="Sales" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Operations" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                        <option value="Management" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Management') ? 'selected' : ''; ?>>Management</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Locatie</label>
                                <select class="form-select" id="location" name="location">
                                    <option value="">Selecteer locatie</option>
                                    <option value="Hoofdkantoor" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Hoofdkantoor') ? 'selected' : ''; ?>>Hoofdkantoor</option>
                                    <option value="Magazijn" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Magazijn') ? 'selected' : ''; ?>>Magazijn</option>
                                    <option value="Serverruimte" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Serverruimte') ? 'selected' : ''; ?>>Serverruimte</option>
                                    <option value="Thuiswerken" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Thuiswerken') ? 'selected' : ''; ?>>Thuiswerken</option>
                                    <option value="Pronto Groningen" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Pronto Groningen') ? 'selected' : ''; ?>>Pronto Groningen</option>
                                    <option value="Profijt Assen" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Profijt Assen') ? 'selected' : ''; ?>>Profijt Assen</option>
                                    <option value="Profijt Hoogeveen" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Profijt Hoogeveen') ? 'selected' : ''; ?>>Profijt Hoogeveen</option>
                                    <option value="K&K Winkel Groningen" <?php echo (isset($_POST['location']) && $_POST['location'] === 'K&K Winkel Groningen') ? 'selected' : ''; ?>>K&K Winkel Groningen</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="purchase_date" class="form-label">Aankoopdatum</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                           value="<?php echo escapeOutput($_POST['purchase_date'] ?? ''); ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="purchase_price" class="form-label">Aankoopprijs (€)</label>
                                    <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" 
                                           value="<?php echo escapeOutput($_POST['purchase_price'] ?? ''); ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="warranty_expiry" class="form-label">Garantie verloopt</label>
                                    <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                           value="<?php echo escapeOutput($_POST['warranty_expiry'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="supplier" class="form-label">Leverancier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" 
                                       value="<?php echo escapeOutput($_POST['supplier'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notities</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo escapeOutput($_POST['notes'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">CI Aanmaken</button>
                                <a href="ci_manage.php" class="btn btn-secondary">Annuleren</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
