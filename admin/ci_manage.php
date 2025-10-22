<?php
/**
 * CI Management Page
 * Admin interface for managing Configuration Items
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/ConfigurationItem.php';

initSession();
requireRole(['admin', 'agent']);

$ci = new ConfigurationItem();

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['ci_id'])) {
        if ($ci->deleteCI($_POST['ci_id'], getCurrentUserId())) {
            $message = 'CI deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete CI';
            $messageType = 'danger';
        }
    }
}

// Get filters
$filters = [];
if (!empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get all CIs
$cis = $ci->getAllCIs($filters);

$pageTitle = 'CI Management';
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
                    <h1 class="h2"><i class="bi bi-hdd-rack"></i> Configuration Items</h1>
                    <a href="ci_create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nieuw CI
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo escapeOutput($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Zoek op naam, serial, asset tag..." 
                                       value="<?php echo escapeOutput($_GET['search'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="type">
                                    <option value="">Alle Types</option>
                                    <option value="Hardware" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Hardware') ? 'selected' : ''; ?>>Hardware</option>
                                    <option value="Software" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Software') ? 'selected' : ''; ?>>Software</option>
                                    <option value="Licentie" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Licentie') ? 'selected' : ''; ?>>Licentie</option>
                                    <option value="Overig" <?php echo (isset($_GET['type']) && $_GET['type'] === 'Overig') ? 'selected' : ''; ?>>Overig</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">Alle Statussen</option>
                                    <option value="In gebruik" <?php echo (isset($_GET['status']) && $_GET['status'] === 'In gebruik') ? 'selected' : ''; ?>>In gebruik</option>
                                    <option value="In voorraad" <?php echo (isset($_GET['status']) && $_GET['status'] === 'In voorraad') ? 'selected' : ''; ?>>In voorraad</option>
                                    <option value="Defect" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Defect') ? 'selected' : ''; ?>>Defect</option>
                                    <option value="Afgeschreven" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Afgeschreven') ? 'selected' : ''; ?>>Afgeschreven</option>
                                    <option value="Onderhoud" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Onderhoud') ? 'selected' : ''; ?>>Onderhoud</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Zoeken</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CI Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>CI Nummer</th>
                                        <th>Naam</th>
                                        <th>Type</th>
                                        <th>Serienummer</th>
                                        <th>Status</th>
                                        <th>Eigenaar</th>
                                        <th>Afdeling</th>
                                        <th>Locatie</th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cis)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Geen CIs gevonden</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($cis as $item): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($item['ci_number']); ?></strong></td>
                                                <td><?php echo escapeOutput($item['name']); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo escapeOutput($item['type']); ?></span></td>
                                                <td><small><?php echo escapeOutput($item['serial_number'] ?? '-'); ?></small></td>
                                                <td>
                                                    <?php
                                                    $statusColors = [
                                                        'In gebruik' => 'success',
                                                        'In voorraad' => 'info',
                                                        'Defect' => 'danger',
                                                        'Afgeschreven' => 'secondary',
                                                        'Onderhoud' => 'warning'
                                                    ];
                                                    $color = $statusColors[$item['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?>"><?php echo escapeOutput($item['status']); ?></span>
                                                </td>
                                                <td><?php echo isset($item['owner_name']) && $item['owner_name'] ? escapeOutput($item['owner_name']) : '-'; ?></td>
                                                <td><?php echo escapeOutput($item['department'] ?? '-'); ?></td>
                                                <td><?php echo escapeOutput($item['location'] ?? '-'); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="ci_edit.php?id=<?php echo $item['ci_id']; ?>" class="btn btn-outline-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Weet je zeker dat je dit CI wilt verwijderen?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="ci_id" value="<?php echo $item['ci_id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
