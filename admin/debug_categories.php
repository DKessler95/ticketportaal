<?php
/**
 * Debug Categories - Show raw database data
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

$db = Database::getInstance();
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug: Categories in Database</h1>
        <p>Total: <?php echo count($categories); ?> categories</p>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>SLA Hours</th>
                    <th>Active</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo $cat['category_id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td><?php echo htmlspecialchars($cat['description']); ?></td>
                        <td><?php echo $cat['default_priority']; ?></td>
                        <td><?php echo $cat['sla_hours']; ?></td>
                        <td><?php echo $cat['is_active']; ?></td>
                        <td><?php echo $cat['created_at'] ?? 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <a href="seed_categories.php" class="btn btn-danger">Go to Seed Page</a>
        <a href="category_fields.php" class="btn btn-primary">Go to Category Fields</a>
    </div>
</body>
</html>
