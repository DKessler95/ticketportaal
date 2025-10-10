<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/KnowledgeBase.php';
require_once __DIR__ . '/../classes/Category.php';

// Check if user is logged in and has agent or admin role
requireLogin();
requireRole(['agent', 'admin']);

$kb = new KnowledgeBase();
$categoryObj = new Category();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $title = sanitizeInput($_POST['title'] ?? '');
                $content = sanitizeInput($_POST['content'] ?? '');
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $tags = sanitizeInput($_POST['tags'] ?? '');
                $isPublished = isset($_POST['is_published']) ? 1 : 0;
                
                if (empty($title) || empty($content)) {
                    $error = 'Title and content are required.';
                } else {
                    $result = $kb->createArticle($title, $content, $categoryId, $tags, $_SESSION['user_id'], $isPublished);
                    if ($result) {
                        $success = 'Article created successfully.';
                    } else {
                        $error = 'Failed to create article.';
                    }
                }
                break;
                
            case 'update':
                $kbId = (int)($_POST['kb_id'] ?? 0);
                $data = [
                    'title' => sanitizeInput($_POST['title'] ?? ''),
                    'content' => sanitizeInput($_POST['content'] ?? ''),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'tags' => sanitizeInput($_POST['tags'] ?? ''),
                    'is_published' => isset($_POST['is_published']) ? 1 : 0
                ];
                
                if (empty($data['title']) || empty($data['content'])) {
                    $error = 'Title and content are required.';
                } else {
                    $result = $kb->updateArticle($kbId, $data);
                    if ($result) {
                        $success = 'Article updated successfully.';
                    } else {
                        $error = 'Failed to update article.';
                    }
                }
                break;
                
            case 'delete':
                $kbId = (int)($_POST['kb_id'] ?? 0);
                $result = $kb->deleteArticle($kbId);
                if ($result) {
                    $success = 'Article deleted successfully.';
                } else {
                    $error = 'Failed to delete article.';
                }
                break;
                
            case 'publish':
                $kbId = (int)($_POST['kb_id'] ?? 0);
                $result = $kb->publishArticle($kbId);
                if ($result) {
                    $success = 'Article published successfully.';
                } else {
                    $error = 'Failed to publish article.';
                }
                break;
                
            case 'unpublish':
                $kbId = (int)($_POST['kb_id'] ?? 0);
                $result = $kb->unpublishArticle($kbId);
                if ($result) {
                    $success = 'Article unpublished successfully.';
                } else {
                    $error = 'Failed to unpublish article.';
                }
                break;
        }
    }
}

// Get article to edit if specified
$editArticle = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editArticle = $kb->getArticleById($editId);
}

// Get all articles (including unpublished)
$articles = $kb->getAllArticles(true);

// Get all categories
$categories = $categoryObj->getCategories(true);

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base Management - ICT Ticketportaal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">ICT Ticketportaal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $_SESSION['role'] === 'admin' ? 'index.php' : '../agent/dashboard.php'; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../knowledge_base.php">View KB</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">Knowledge Base Management</h1>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Article Form -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <?php echo $editArticle ? 'Edit Article' : 'Create New Article'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="<?php echo $editArticle ? 'update' : 'create'; ?>">
                                    <?php if ($editArticle): ?>
                                        <input type="hidden" name="kb_id" value="<?php echo $editArticle['kb_id']; ?>">
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label for="title" class="form-label">Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo $editArticle ? htmlspecialchars($editArticle['title']) : ''; ?>" 
                                               required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="0">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['category_id']; ?>"
                                                        <?php echo ($editArticle && $editArticle['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="content" class="form-label">Content *</label>
                                        <textarea class="form-control" id="content" name="content" rows="10" required><?php 
                                            echo $editArticle ? htmlspecialchars($editArticle['content']) : ''; 
                                        ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tags" class="form-label">Tags (comma-separated)</label>
                                        <input type="text" class="form-control" id="tags" name="tags" 
                                               value="<?php echo $editArticle ? htmlspecialchars($editArticle['tags']) : ''; ?>"
                                               placeholder="e.g. password, email, network">
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published"
                                               <?php echo ($editArticle && $editArticle['is_published']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_published">
                                            Published (visible to users)
                                        </label>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <?php echo $editArticle ? 'Update Article' : 'Create Article'; ?>
                                        </button>
                                        <?php if ($editArticle): ?>
                                            <a href="knowledge_base.php" class="btn btn-secondary">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Articles List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">All Articles (<?php echo count($articles); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($articles)): ?>
                                    <p class="text-muted">No articles found. Create your first article using the form.</p>
                                <?php else: ?>
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
                                                <?php foreach ($articles as $article): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="../kb_article.php?id=<?php echo $article['kb_id']; ?>" 
                                                               target="_blank">
                                                                <?php echo htmlspecialchars($article['title']); ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo htmlspecialchars($article['category_name'] ?? 'None'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($article['is_published']): ?>
                                                                <span class="badge bg-success">Published</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">Draft</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $article['views']; ?></td>
                                                        <td>
                                                            <small><?php echo date('M j, Y', strtotime($article['created_at'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="?edit=<?php echo $article['kb_id']; ?>" 
                                                                   class="btn btn-outline-primary" title="Edit">
                                                                    Edit
                                                                </a>
                                                                
                                                                <?php if ($article['is_published']): ?>
                                                                    <form method="POST" style="display: inline;" 
                                                                          onsubmit="return confirm('Unpublish this article?');">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                        <input type="hidden" name="action" value="unpublish">
                                                                        <input type="hidden" name="kb_id" value="<?php echo $article['kb_id']; ?>">
                                                                        <button type="submit" class="btn btn-outline-warning" title="Unpublish">
                                                                            Unpublish
                                                                        </button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                        <input type="hidden" name="action" value="publish">
                                                                        <input type="hidden" name="kb_id" value="<?php echo $article['kb_id']; ?>">
                                                                        <button type="submit" class="btn btn-outline-success" title="Publish">
                                                                            Publish
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                
                                                                <form method="POST" style="display: inline;" 
                                                                      onsubmit="return confirm('Are you sure you want to delete this article?');">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="kb_id" value="<?php echo $article['kb_id']; ?>">
                                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
