<?php
session_start();
require_once __DIR__ . '/classes/KnowledgeBase.php';
require_once __DIR__ . '/includes/functions.php';

$kb = new KnowledgeBase();

// Get article ID from URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($articleId <= 0) {
    header('Location: knowledge_base.php');
    exit;
}

// Get article details
$article = $kb->getArticleById($articleId);

if (!$article) {
    header('Location: knowledge_base.php');
    exit;
}

// Check if article is published (unless user is agent or admin)
$userRole = $_SESSION['role'] ?? 'guest';
if (!$article['is_published'] && !in_array($userRole, ['agent', 'admin'])) {
    header('Location: knowledge_base.php');
    exit;
}

// Increment view counter
$kb->incrementViews($articleId);

// Get related articles from the same category
$relatedArticles = [];
if ($article['category_id']) {
    $allCategoryArticles = $kb->getArticlesByCategory($article['category_id'], false);
    // Filter out current article and limit to 5
    $relatedArticles = array_filter($allCategoryArticles, function($a) use ($articleId) {
        return $a['kb_id'] != $articleId;
    });
    $relatedArticles = array_slice($relatedArticles, 0, 5);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Knowledge Base</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="knowledge_base.php">Back to Knowledge Base</a>
                    </li>
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
            <div class="col-md-8">
                <!-- Article Content -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="mb-2"><?php echo htmlspecialchars($article['title']); ?></h2>
                                <div>
                                    <?php if ($article['category_name']): ?>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($article['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!$article['is_published']): ?>
                                        <span class="badge bg-warning text-dark">Unpublished</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">
                                    <?php echo $article['views'] + 1; ?> views
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="article-content">
                            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                        </div>
                        
                        <?php if (!empty($article['tags'])): ?>
                            <hr>
                            <div class="mt-3">
                                <strong>Tags:</strong>
                                <?php 
                                $tags = explode(',', $article['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <span class="badge bg-light text-dark me-1">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <?php if ($article['first_name'] && $article['last_name']): ?>
                                Written by <?php echo htmlspecialchars($article['first_name'] . ' ' . $article['last_name']); ?> | 
                            <?php endif; ?>
                            Created: <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                            <?php if ($article['updated_at'] != $article['created_at']): ?>
                                | Updated: <?php echo date('F j, Y', strtotime($article['updated_at'])); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <!-- Helpful Feedback -->
                <div class="card">
                    <div class="card-body text-center">
                        <p class="mb-2">Was this article helpful?</p>
                        <p class="text-muted small">
                            If you need further assistance, please 
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="user/create_ticket.php">create a ticket</a>
                            <?php else: ?>
                                <a href="login.php">login</a> to create a ticket
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sidebar with Related Articles -->
            <div class="col-md-4">
                <?php if (!empty($relatedArticles)): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Related Articles</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($relatedArticles as $related): ?>
                                <a href="kb_article.php?id=<?php echo $related['kb_id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($related['title']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $related['views']; ?> views
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="knowledge_base.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                            Browse All Articles
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="user/create_ticket.php" class="btn btn-outline-success btn-sm w-100">
                                Create Support Ticket
                            </a>
                        <?php endif; ?>
                        <?php if (in_array($userRole, ['agent', 'admin'])): ?>
                            <a href="admin/knowledge_base.php?edit=<?php echo $article['kb_id']; ?>" 
                               class="btn btn-outline-warning btn-sm w-100 mt-2">
                                Edit Article
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
