<?php
/**
 * Admin Reports Dashboard
 * 
 * Displays analytics and reporting for ticket system performance
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Report.php';

// Initialize session and check authentication
initSession();
requireRole('admin');

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize Report class
$report = new Report();

// Get date range from request or use defaults (last 30 days)
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'day';

// Validate dates
if (!strtotime($startDate) || !strtotime($endDate)) {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate = date('Y-m-d');
}

// Ensure start date is before end date
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Get report data
$ticketVolume = $report->getTicketVolumeByPeriod($period, $startDate, $endDate);
$resolutionTimeByCategory = $report->getAverageResolutionTimeByCategory($startDate, $endDate);
$agentPerformance = $report->getAgentPerformance($startDate, $endDate);
$satisfactionMetrics = $report->getSatisfactionMetrics($startDate, $endDate);
$categoryAnalysis = $report->getCategoryAnalysis($startDate, $endDate);

$pageTitle = 'Reports & Analytics';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/index.php">
                <i class="bi bi-ticket-perforated"></i> <?php echo escapeOutput(SITE_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/categories.php">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/knowledge_base.php">
                            <i class="bi bi-book"></i> Knowledge Base
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo SITE_URL; ?>/admin/reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo escapeOutput($userName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="bi bi-graph-up"></i> Reports & Analytics</h1>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo escapeOutput($startDate); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo escapeOutput($endDate); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="period" class="form-label">Group By</label>
                                <select class="form-select" id="period" name="period">
                                    <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Day</option>
                                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Week</option>
                                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Month</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i> Apply Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Volume Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Ticket Volume by Period</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ticketVolume)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No ticket data available for the selected period.
                            </div>
                        <?php else: ?>
                            <canvas id="ticketVolumeChart" height="80"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Satisfaction Metrics -->
        <?php if ($satisfactionMetrics && $satisfactionMetrics['total_ratings'] > 0): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Satisfaction Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-md-4">
                                <h2 class="text-primary mb-0">
                                    <?php echo number_format($satisfactionMetrics['avg_rating'], 2); ?>
                                </h2>
                                <small class="text-muted">Average Rating</small>
                            </div>
                            <div class="col-md-4">
                                <h2 class="text-success mb-0">
                                    <?php echo number_format($satisfactionMetrics['total_ratings']); ?>
                                </h2>
                                <small class="text-muted">Total Ratings</small>
                            </div>
                            <div class="col-md-4">
                                <h2 class="text-info mb-0">
                                    <?php echo number_format($satisfactionMetrics['response_rate'], 1); ?>%
                                </h2>
                                <small class="text-muted">Response Rate</small>
                            </div>
                        </div>
                        <canvas id="satisfactionChart" height="150"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-star-fill"></i> Rating Distribution</h5>
                    </div>
                    <div class="card-body">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></span>
                                    <span>
                                        <?php echo $satisfactionMetrics["rating_{$i}_count"]; ?> 
                                        (<?php echo number_format($satisfactionMetrics["rating_{$i}_percentage"], 1); ?>%)
                                    </span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?php echo $i >= 4 ? 'bg-success' : ($i == 3 ? 'bg-warning' : 'bg-danger'); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $satisfactionMetrics["rating_{$i}_percentage"]; ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Resolution Time by Category -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Average Resolution Time by Category</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($resolutionTimeByCategory)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No resolution data available for the selected period.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>SLA Hours</th>
                                            <th>Total Tickets</th>
                                            <th>Resolved</th>
                                            <th>Avg Resolution (Hours)</th>
                                            <th>Min/Max (Hours)</th>
                                            <th>SLA Compliance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resolutionTimeByCategory as $category): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($category['category_name']); ?></strong></td>
                                                <td><?php echo escapeOutput($category['sla_hours']); ?></td>
                                                <td><?php echo escapeOutput($category['total_tickets']); ?></td>
                                                <td><?php echo escapeOutput($category['resolved_tickets']); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($category['avg_resolution_hours'] !== null) {
                                                        echo number_format($category['avg_resolution_hours'], 1);
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($category['min_resolution_hours'] !== null && $category['max_resolution_hours'] !== null) {
                                                        echo number_format($category['min_resolution_hours'], 1) . ' / ' . 
                                                             number_format($category['max_resolution_hours'], 1);
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($category['resolved_tickets'] > 0): ?>
                                                        <span class="badge <?php echo $category['sla_compliance_percentage'] >= 80 ? 'bg-success' : 
                                                            ($category['sla_compliance_percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                            <?php echo number_format($category['sla_compliance_percentage'], 1); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
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

        <!-- Agent Performance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Agent Performance</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($agentPerformance)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No agent performance data available for the selected period.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Agent</th>
                                            <th>Email</th>
                                            <th>Total Assigned</th>
                                            <th>In Progress</th>
                                            <th>Resolved</th>
                                            <th>Avg Resolution (Hours)</th>
                                            <th>Avg Satisfaction</th>
                                            <th>Rated Tickets</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($agentPerformance as $agent): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($agent['agent_name']); ?></strong></td>
                                                <td><?php echo escapeOutput($agent['email']); ?></td>
                                                <td><?php echo escapeOutput($agent['total_assigned']); ?></td>
                                                <td><?php echo escapeOutput($agent['in_progress_count']); ?></td>
                                                <td><?php echo escapeOutput($agent['resolved_count']); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($agent['avg_resolution_hours'] !== null) {
                                                        echo number_format($agent['avg_resolution_hours'], 1);
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($agent['avg_satisfaction_rating'] !== null) {
                                                        $rating = $agent['avg_satisfaction_rating'];
                                                        $badgeClass = $rating >= 4 ? 'bg-success' : ($rating >= 3 ? 'bg-warning' : 'bg-danger');
                                                        echo '<span class="badge ' . $badgeClass . '">' . 
                                                             number_format($rating, 2) . ' <i class="bi bi-star-fill"></i></span>';
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo escapeOutput($agent['rated_tickets']); ?></td>
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

        <!-- Category Analysis -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Category Analysis</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categoryAnalysis)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No category data available for the selected period.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Total Tickets</th>
                                            <th>% of Total</th>
                                            <th>Open</th>
                                            <th>In Progress</th>
                                            <th>Resolved</th>
                                            <th>Resolution Rate</th>
                                            <th>Avg Resolution (Hours)</th>
                                            <th>Avg Satisfaction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryAnalysis as $category): ?>
                                            <tr>
                                                <td><strong><?php echo escapeOutput($category['category_name']); ?></strong></td>
                                                <td><?php echo escapeOutput($category['total_tickets']); ?></td>
                                                <td><?php echo number_format($category['percentage_of_total'], 1); ?>%</td>
                                                <td><?php echo escapeOutput($category['open_tickets']); ?></td>
                                                <td><?php echo escapeOutput($category['in_progress_tickets']); ?></td>
                                                <td><?php echo escapeOutput($category['resolved_tickets']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $category['resolution_rate'] >= 80 ? 'bg-success' : 
                                                        ($category['resolution_rate'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo number_format($category['resolution_rate'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($category['avg_resolution_hours'] !== null) {
                                                        echo number_format($category['avg_resolution_hours'], 1);
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($category['avg_satisfaction'] !== null) {
                                                        echo number_format($category['avg_satisfaction'], 2) . ' <i class="bi bi-star-fill"></i>';
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
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

    <footer class="mt-5 py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ticket Volume Chart
        <?php if (!empty($ticketVolume)): ?>
        const ticketVolumeCtx = document.getElementById('ticketVolumeChart').getContext('2d');
        new Chart(ticketVolumeCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($ticketVolume, 'period')); ?>,
                datasets: [
                    {
                        label: 'Total Tickets',
                        data: <?php echo json_encode(array_column($ticketVolume, 'ticket_count')); ?>,
                        borderColor: 'rgb(13, 110, 253)',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Open',
                        data: <?php echo json_encode(array_column($ticketVolume, 'open_count')); ?>,
                        borderColor: 'rgb(255, 193, 7)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'In Progress',
                        data: <?php echo json_encode(array_column($ticketVolume, 'in_progress_count')); ?>,
                        borderColor: 'rgb(13, 202, 240)',
                        backgroundColor: 'rgba(13, 202, 240, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Resolved',
                        data: <?php echo json_encode(array_column($ticketVolume, 'resolved_count')); ?>,
                        borderColor: 'rgb(25, 135, 84)',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Satisfaction Chart
        <?php if ($satisfactionMetrics && $satisfactionMetrics['total_ratings'] > 0): ?>
        const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
        new Chart(satisfactionCtx, {
            type: 'bar',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Number of Ratings',
                    data: [
                        <?php echo $satisfactionMetrics['rating_1_count']; ?>,
                        <?php echo $satisfactionMetrics['rating_2_count']; ?>,
                        <?php echo $satisfactionMetrics['rating_3_count']; ?>,
                        <?php echo $satisfactionMetrics['rating_4_count']; ?>,
                        <?php echo $satisfactionMetrics['rating_5_count']; ?>
                    ],
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(253, 126, 20, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(32, 201, 151, 0.8)',
                        'rgba(25, 135, 84, 0.8)'
                    ],
                    borderColor: [
                        'rgb(220, 53, 69)',
                        'rgb(253, 126, 20)',
                        'rgb(255, 193, 7)',
                        'rgb(32, 201, 151)',
                        'rgb(25, 135, 84)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
