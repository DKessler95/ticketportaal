<?php
/**
 * User Reviews Page (Admin)
 * 
 * View and analyze user satisfaction ratings and feedback
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/Database.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize classes
$db = Database::getInstance();

// Get filter parameters
$filterRating = $_GET['rating'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Build query
$sql = "SELECT t.ticket_id, t.ticket_number, t.title, t.satisfaction_rating, t.satisfaction_comment, 
               t.resolved_at, t.created_at,
               u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email,
               a.first_name as agent_first_name, a.last_name as agent_last_name,
               c.name as category_name
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN users a ON t.assigned_agent_id = a.user_id
        LEFT JOIN categories c ON t.category_id = c.category_id
        WHERE t.satisfaction_rating IS NOT NULL";

$params = [];

if (!empty($filterRating)) {
    $sql .= " AND t.satisfaction_rating = ?";
    $params[] = $filterRating;
}

if (!empty($filterDateFrom)) {
    $sql .= " AND DATE(t.resolved_at) >= ?";
    $params[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $sql .= " AND DATE(t.resolved_at) <= ?";
    $params[] = $filterDateTo;
}

$sql .= " ORDER BY t.resolved_at DESC";

$reviews = $db->fetchAll($sql, $params);

// Handle case where fetchAll returns false
if ($reviews === false) {
    $reviews = [];
}

// Calculate statistics
$totalReviews = count($reviews);
$avgRating = 0;
$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

if ($totalReviews > 0) {
    $sumRating = 0;
    foreach ($reviews as $review) {
        $sumRating += $review['satisfaction_rating'];
        $ratingCounts[$review['satisfaction_rating']]++;
    }
    $avgRating = round($sumRating / $totalReviews, 2);
}

$pageTitle = 'User Reviews';
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
    <style>
        .star-display {
            color: #FFB627;
            font-size: 1.2rem;
        }
        .star-rating-large {
            color: #FFB627;
            font-size: 2rem;
        }
        .review-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid transparent;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .review-card.rating-5 { border-left-color: #28a745; }
        .review-card.rating-4 { border-left-color: #48BB78; }
        .review-card.rating-3 { border-left-color: #FFB627; }
        .review-card.rating-2 { border-left-color: #FF6B35; }
        .review-card.rating-1 { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-star-fill text-warning"></i> Gebruikersbeoordelingen</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h6 class="card-title">Totaal Beoordelingen</h6>
                                <h2 class="mb-0"><?php echo $totalReviews; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h6 class="card-title">Gemiddelde Beoordeling</h6>
                                <h2 class="mb-0"><?php echo $avgRating; ?> <i class="bi bi-star-fill"></i></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h6 class="card-title">5 Sterren Beoordelingen</h6>
                                <h2 class="mb-0"><?php echo $ratingCounts[5]; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h6 class="card-title">Lage Beoordelingen (1-2)</h6>
                                <h2 class="mb-0"><?php echo $ratingCounts[1] + $ratingCounts[2]; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rating Distribution -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Beoordelingsverdeling</h5>
                    </div>
                    <div class="card-body">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php 
                            $percentage = $totalReviews > 0 ? round(($ratingCounts[$i] / $totalReviews) * 100) : 0;
                            ?>
                            <div class="row align-items-center mb-2">
                                <div class="col-2">
                                    <span><?php echo $i; ?> <i class="bi bi-star-fill text-warning"></i></span>
                                </div>
                                <div class="col-8">
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <span><?php echo $ratingCounts[$i]; ?> (<?php echo $percentage; ?>%)</span>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="rating" class="form-label">Beoordeling</label>
                                <select class="form-select" id="rating" name="rating">
                                    <option value="">Alle Beoordelingen</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $filterRating == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> Sterren
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Van Datum</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo escapeOutput($filterDateFrom); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Tot Datum</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo escapeOutput($filterDateTo); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-funnel"></i> Filteren
                                </button>
                                <a href="<?php echo SITE_URL; ?>/admin/reviews.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Wissen
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Beoordelingen (<?php echo count($reviews); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviews)): ?>
                            <p class="text-muted">Geen beoordelingen gevonden.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card review-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-0">
                                                            <a href="<?php echo SITE_URL; ?>/agent/ticket_detail.php?id=<?php echo $review['ticket_id']; ?>">
                                                                <?php echo escapeOutput($review['ticket_number']); ?>
                                                            </a>
                                                        </h6>
                                                        <small class="text-muted"><?php echo escapeOutput($review['title']); ?></small>
                                                    </div>
                                                    <div class="star-display">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?php echo $i <= $review['satisfaction_rating'] ? '-fill' : ''; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($review['satisfaction_comment'])): ?>
                                                    <div class="alert alert-light mb-2">
                                                        <small><?php echo nl2br(escapeOutput($review['satisfaction_comment'])); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <hr>
                                                
                                                <div class="row small text-muted">
                                                    <div class="col-6">
                                                        <strong>Gebruiker:</strong><br>
                                                        <?php echo escapeOutput($review['user_first_name'] . ' ' . $review['user_last_name']); ?>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Agent:</strong><br>
                                                        <?php echo $review['agent_first_name'] ? escapeOutput($review['agent_first_name'] . ' ' . $review['agent_last_name']) : 'N/A'; ?>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <strong>Categorie:</strong><br>
                                                        <?php echo escapeOutput($review['category_name']); ?>
                                                    </div>
                                                    <div class="col-6 mt-2">
                                                        <strong>Opgelost:</strong><br>
                                                        <?php echo formatDate($review['resolved_at']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
