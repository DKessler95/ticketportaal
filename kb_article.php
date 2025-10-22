<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/KnowledgeBase.php';

// Initialize session
initSession();

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
if (!$article['is_published'] && !checkRole(['agent', 'admin'])) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .article-content {
            line-height: 1.8;
            font-size: 16px;
        }
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .article-content h1, .article-content h2, .article-content h3 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .article-content h1 { font-size: 2rem; }
        .article-content h2 { font-size: 1.75rem; }
        .article-content h3 { font-size: 1.5rem; }
        .article-content p {
            margin-bottom: 15px;
        }
        .article-content ul, .article-content ol {
            margin-bottom: 15px;
            padding-left: 30px;
        }
        .article-content li {
            margin-bottom: 8px;
        }
        .article-content code {
            background-color: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .article-content pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .article-content blockquote {
            border-left: 4px solid #0066cc;
            padding-left: 20px;
            margin: 20px 0;
            color: #666;
            font-style: italic;
        }
        .article-content table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .article-content table th,
        .article-content table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .article-content table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php if (checkLogin()): ?>
                <?php include __DIR__ . '/includes/sidebar.php'; ?>
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php else: ?>
                <main class="col-12">
                    <div class="container mt-4">
            <?php endif; ?>
                    
                    <!-- Back Button -->
                    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3">
                        <a href="knowledge_base.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Terug naar Knowledge Base
                        </a>
                        <?php if (checkRole(['admin', 'agent'])): ?>
                            <a href="admin/kb_edit.php?id=<?php echo $article['kb_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i> Bewerken
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-9">
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
                            <?php 
                            // Display HTML content (already sanitized when saved)
                            echo $article['content']; 
                            ?>
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
                        <?php if (checkLogin()): ?>
                            <p class="text-muted small">
                                If you need further assistance, please 
                                <a href="<?php echo SITE_URL; ?>/user/create_ticket.php">create a ticket</a>
                            </p>
                        <?php endif; ?>
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
                    </div>
                </div>
                        </div>
                        <?php if (checkLogin()): ?>
                            <div class="col-lg-3">
                                <!-- Sidebar space for logged in users -->
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <!-- Sidebar space for guests -->
                            </div>
                        <?php endif; ?>
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
