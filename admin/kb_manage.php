<?php
/**
 * Knowledge Base Management
 * Admin interface for managing KB articles
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/KnowledgeBase.php';
require_once __DIR__ . '/../classes/Category.php';

// Initialize session and require admin/agent role
initSession();
requireRole(['admin', 'agent']);

$kb = new KnowledgeBase();
$categoryObj = new Category();

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                if (isset($_POST['kb_id'])) {
                    if ($kb->deleteArticle($_POST['kb_id'])) {
                        $message = 'Article deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete article';
                        $messageType = 'danger';
                    }
                }
                break;
            
            case 'toggle_publish':
                if (isset($_POST['kb_id'])) {
                    $article = $kb->getArticleById($_POST['kb_id']);
                    if ($article) {
                        $newStatus = $article['is_published'] ? 0 : 1;
                        if ($kb->updateArticle($_POST['kb_id'], [
                            'is_published' => $newStatus
                        ])) {
                            $message = $newStatus ? 'Article published' : 'Article unpublished';
                            $messageType = 'success';
                        }
                    }
                }
                break;
        }
    }
}

// Get all articles (including unpublished)
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if (!empty($searchTerm)) {
    $articles = $kb->searchArticles($searchTerm, true);
} elseif ($categoryFilter > 0) {
    $articles = $kb->getArticlesByCategory($categoryFilter, true);
} else {
    $articles = $kb->getAllArticles(true); // Include unpublished articles
}

$categories = $categoryObj->getCategories(true);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Knowledge Base - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Knowledge Base Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="kb_create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create New Article
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo escapeOutput($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search articles..." 
                                       value="<?php echo escapeOutput($searchTerm); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="category">
                                    <option value="0">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"
                                                <?php echo $categoryFilter == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo escapeOutput($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Articles Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($articles)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No articles found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($articles as $article): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo escapeOutput($article['title']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo escapeOutput($article['category_name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($article['is_published']): ?>
                                                        <span class="badge bg-success">Published</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Draft</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $article['views']; ?></td>
                                                <td><?php echo formatDate($article['created_at'], 'd-m-Y'); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../kb_article.php?id=<?php echo $article['kb_id']; ?>" 
                                                           class="btn btn-outline-primary" target="_blank">
                                                            View
                                                        </a>
                                                        <a href="kb_edit.php?id=<?php echo $article['kb_id']; ?>" 
                                                           class="btn btn-outline-secondary">
                                                            Edit
                                                        </a>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_publish">
                                                            <input type="hidden" name="kb_id" value="<?php echo $article['kb_id']; ?>">
                                                            <button type="submit" class="btn btn-outline-info">
                                                                <?php echo $article['is_published'] ? 'Unpublish' : 'Publish'; ?>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this article?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="kb_id" value="<?php echo $article['kb_id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger">
                                                                Delete
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
