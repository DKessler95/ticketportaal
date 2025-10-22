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
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
    <style>
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-card-modern {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-orange-light) 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            transition: transform 0.3s ease;
        }
        .stat-card-modern:hover {
            transform: translateY(-4px);
        }
        .stat-card-modern h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        .stat-card-modern p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-graph-up"></i> Rapporten & Analyses</h1>
                </div>

        <!-- Date Range Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Startdatum</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo escapeOutput($startDate); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Einddatum</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo escapeOutput($endDate); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="period" class="form-label">Groeperen Op</label>
                                <select class="form-select" id="period" name="period">
                                    <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Dag</option>
                                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Week</option>
                                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Maand</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i> Filter Toepassen
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
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Ticketvolume per Periode</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ticketVolume)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen ticketgegevens beschikbaar voor de geselecteerde periode.
                            </div>
                        <?php else: ?>
                            <div id="ticketVolumeChart" style="min-height: 350px;"></div>
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
                        <h5 class="mb-0"><i class="bi bi-star"></i> Tevredenheidsmetrieken</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-md-4">
                                <h2 class="text-primary mb-0">
                                    <?php echo number_format($satisfactionMetrics['avg_rating'], 2); ?>
                                </h2>
                                <small class="text-muted">Gemiddelde Beoordeling</small>
                            </div>
                            <div class="col-md-4">
                                <h2 class="text-success mb-0">
                                    <?php echo number_format($satisfactionMetrics['total_ratings']); ?>
                                </h2>
                                <small class="text-muted">Totaal Beoordelingen</small>
                            </div>
                            <div class="col-md-4">
                                <h2 class="text-info mb-0">
                                    <?php echo number_format($satisfactionMetrics['response_rate'], 1); ?>%
                                </h2>
                                <small class="text-muted">Responspercentage</small>
                            </div>
                        </div>
                        <div id="satisfactionChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-star-fill"></i> Beoordelingsverdeling</h5>
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
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Gemiddelde Oplostijd per Categorie</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($resolutionTimeByCategory)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen oplosgegevens beschikbaar voor de geselecteerde periode.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Categorie</th>
                                            <th>SLA Uren</th>
                                            <th>Totaal Tickets</th>
                                            <th>Opgelost</th>
                                            <th>Gem. Oplostijd (Uren)</th>
                                            <th>Min/Max (Uren)</th>
                                            <th>SLA Naleving</th>
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
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Agentprestaties</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($agentPerformance)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen agentprestatiegegevens beschikbaar voor de geselecteerde periode.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Agent</th>
                                            <th>E-mail</th>
                                            <th>Totaal Toegewezen</th>
                                            <th>In Behandeling</th>
                                            <th>Opgelost</th>
                                            <th>Gem. Oplostijd (Uren)</th>
                                            <th>Gem. Tevredenheid</th>
                                            <th>Beoordeelde Tickets</th>
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
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Categorieanalyse</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categoryAnalysis)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Geen categoriegegevens beschikbaar voor de geselecteerde periode.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Categorie</th>
                                            <th>Totaal Tickets</th>
                                            <th>% van Totaal</th>
                                            <th>Open</th>
                                            <th>In Behandeling</th>
                                            <th>Opgelost</th>
                                            <th>Oplospercentage</th>
                                            <th>Gem. Oplostijd (Uren)</th>
                                            <th>Gem. Tevredenheid</th>
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
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo escapeOutput(COMPANY_NAME); ?>. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ticket Volume Chart - ApexCharts with Kruit & Kramer Styling
        <?php if (!empty($ticketVolume)): ?>
        var ticketVolumeOptions = {
            series: [
                {
                    name: 'Total Tickets',
                    data: <?php echo json_encode(array_column($ticketVolume, 'ticket_count')); ?>
                },
                {
                    name: 'Open',
                    data: <?php echo json_encode(array_column($ticketVolume, 'open_count')); ?>
                },
                {
                    name: 'In Progress',
                    data: <?php echo json_encode(array_column($ticketVolume, 'in_progress_count')); ?>
                },
                {
                    name: 'Resolved',
                    data: <?php echo json_encode(array_column($ticketVolume, 'resolved_count')); ?>
                }
            ],
            chart: {
                type: 'area',
                height: 350,
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            colors: ['#004E89', '#FFB627', '#FF6B35', '#28a745'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: <?php echo json_encode(array_column($ticketVolume, 'period')); ?>,
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '13px',
                fontWeight: 500,
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            tooltip: {
                theme: 'dark',
                x: {
                    show: true
                },
                y: {
                    formatter: function(value) {
                        return value + ' tickets';
                    }
                }
            }
        };

        var ticketVolumeChart = new ApexCharts(document.querySelector("#ticketVolumeChart"), ticketVolumeOptions);
        ticketVolumeChart.render();
        <?php endif; ?>

        // Satisfaction Chart - ApexCharts Radial Bar
        <?php if ($satisfactionMetrics && $satisfactionMetrics['total_ratings'] > 0): ?>
        var satisfactionOptions = {
            series: [
                <?php echo $satisfactionMetrics['rating_1_count']; ?>,
                <?php echo $satisfactionMetrics['rating_2_count']; ?>,
                <?php echo $satisfactionMetrics['rating_3_count']; ?>,
                <?php echo $satisfactionMetrics['rating_4_count']; ?>,
                <?php echo $satisfactionMetrics['rating_5_count']; ?>
            ],
            chart: {
                type: 'bar',
                height: 300,
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                toolbar: {
                    show: true
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    distributed: true,
                    horizontal: false,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: ['#dc3545', '#FF6B35', '#FFB627', '#48BB78', '#28a745'],
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val;
                },
                offsetY: -20,
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    colors: ['#304758']
                }
            },
            xaxis: {
                categories: ['⭐ 1 Star', '⭐⭐ 2 Stars', '⭐⭐⭐ 3 Stars', '⭐⭐⭐⭐ 4 Stars', '⭐⭐⭐⭐⭐ 5 Stars'],
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function(value, { seriesIndex, dataPointIndex, w }) {
                        var total = w.globals.series.reduce((a, b) => a + b, 0);
                        var percentage = ((value / total) * 100).toFixed(1);
                        return value + ' ratings (' + percentage + '%)';
                    }
                }
            }
        };

        var satisfactionChart = new ApexCharts(document.querySelector("#satisfactionChart"), satisfactionOptions);
        satisfactionChart.render();
        <?php endif; ?>
    </script>
            </main>
        </div>
    </div>
</body>
</html>
