<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/KnowledgeBase.php';
require_once __DIR__ . '/classes/Category.php';

// Initialize session
initSession();

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
    <title>Kennisbank - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php if (checkLogin()): ?>
                <?php include __DIR__ . '/includes/sidebar.php'; ?>
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Kennisbank</h1>
                        <?php if (checkRole(['admin', 'agent'])): ?>
                            <a href="admin/kb_manage.php" class="btn btn-primary">
                                <i class="bi bi-pencil-square"></i> Artikelen Beheren
                            </a>
                        <?php endif; ?>
                    </div>
            <?php else: ?>
                <main class="col-12">
                    <div class="container mt-4">
                        <h1 class="mb-4">Kennisbank</h1>
            <?php endif; ?>
                
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="knowledge_base.php" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Zoek artikelen..." 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="category">
                                    <option value="0">Alle Categorieën</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"
                                                <?php echo $categoryFilter == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Zoeken</button>
                            </div>
                        </form>
                        <?php if (!empty($searchTerm) || $categoryFilter > 0): ?>
                            <div class="mt-2">
                                <a href="knowledge_base.php" class="btn btn-sm btn-secondary">Filters Wissen</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Articles Display -->
                <?php if (empty($articles)): ?>
                    <div class="alert alert-info">
                        Geen artikelen gevonden. <?php echo !empty($searchTerm) ? 'Probeer een andere zoekterm.' : ''; ?>
                    </div>
                <?php else: ?>
                    <?php if (!empty($searchTerm) || $categoryFilter > 0): ?>
                        <!-- List view for search results -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Zoekresultaten (<?php echo count($articles); ?>)</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($articles as $article): ?>
                                    <a href="kb_article.php?id=<?php echo $article['kb_id']; ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($article['title']); ?></h5>
                                            <small class="text-muted">
                                                <i class="bi bi-eye"></i> <?php echo $article['views']; ?> weergaven
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($article['category_name'] ?? 'Ongecategoriseerd'); ?>
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
                                                    <?php echo $article['views']; ?> weergaven
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
                </main>
            </div>
        </div>
    <?php if (!checkLogin()): ?>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
