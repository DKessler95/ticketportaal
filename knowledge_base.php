<?php
session_start();
require_once __DIR__ . '/classes/KnowledgeBase.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/includes/functions.php';

$kb = new KnowledgeBase();
$categoryObj = new Category();

// Get search term if provided
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get articles based on search or category filter
if (!empty($searchTerm)) {
    $articles = $kb->searchArticles($searchTerm, false);
} elseif ($categoryFilter > 0) {
    $articles = $kb->getArticlesByCategory($categoryFilter, false);
} else {
    $articles = $kb->getPublishedArticles();
}

// Get all active categories for filter
$categories = $categoryObj->getCategories(true);

// Group articles by category for display
$articlesByCategory = [];
foreach ($articles as $article) {
    $catName = $article['category_name'] ?? 'Uncategorized';
    if (!isset($articlesByCategory[$catName])) {
        $articlesByCategory[$catName] = [];
    }
    $articlesByCategory[$catName][] = $article;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">ICT Ticketportaal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">Knowledge Base</h1>
                
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="knowledge_base.php" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search articles..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="category">
                                    <option value="0">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"
                                                <?php echo $categoryFilter == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                        <?php if (!empty($searchTerm) || $categoryFilter > 0): ?>
                            <div class="mt-2">
                                <a href="knowledge_base.php" class="btn btn-sm btn-secondary">Clear Filters</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Articles Display -->
                <?php if (empty($articles)): ?>
                    <div class="alert alert-info">
                        No articles found. <?php echo !empty($searchTerm) ? 'Try a different search term.' : ''; ?>
                    </div>
                <?php else: ?>
                    <?php if (!empty($searchTerm) || $categoryFilter > 0): ?>
                        <!-- List view for search results -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Search Results (<?php echo count($articles); ?>)</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($articles as $article): ?>
                                    <a href="kb_article.php?id=<?php echo $article['kb_id']; ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($article['title']); ?></h5>
                                            <small class="text-muted">
                                                <i class="bi bi-eye"></i> <?php echo $article['views']; ?> views
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($article['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo substr(strip_tags($article['content']), 0, 150); ?>...
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Category-organized view -->
                        <?php foreach ($articlesByCategory as $categoryName => $categoryArticles): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0"><?php echo htmlspecialchars($categoryName); ?></h4>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($categoryArticles as $article): ?>
                                        <a href="kb_article.php?id=<?php echo $article['kb_id']; ?>" 
                                           class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($article['title']); ?></h5>
                                                <small class="text-muted">
                                                    <?php echo $article['views']; ?> views
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo substr(strip_tags($article['content']), 0, 150); ?>...
                                            </small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
