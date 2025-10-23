<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['full_name'] ?? 'Guest';
$userEmail = $_SESSION['email'] ?? '';

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ICT Ticketportaal'; ?> - Kruit & Kramer</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo getBaseUrl(); ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <!-- Logo and Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo getBaseUrl(); ?>/index.php">
                <i class="bi bi-ticket-perforated-fill me-2 fs-4"></i>
                <span class="fw-bold">ICT Ticketportaal</span>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <?php if ($isLoggedIn): ?>
                    <!-- Role-based Navigation Links -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <?php if ($userRole === 'user'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/user/dashboard.php">
                                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'create_ticket.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/user/create_ticket.php">
                                    <i class="bi bi-plus-circle me-1"></i>Nieuw Ticket
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'my_tickets.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/user/my_tickets.php">
                                    <i class="bi bi-list-task me-1"></i>Mijn Tickets
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'ai_assistant.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/user/ai_assistant.php">
                                    <i class="bi bi-robot me-1"></i>AI Assistent
                                </a>
                            </li>
                        <?php elseif ($userRole === 'agent'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/agent/dashboard.php">
                                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'my_tickets.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/agent/my_tickets.php">
                                    <i class="bi bi-person-check me-1"></i>Mijn Toegewezen
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'ai_assistant.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/agent/ai_assistant.php">
                                    <i class="bi bi-robot me-1"></i>AI Assistent
                                </a>
                            </li>
                        <?php elseif ($userRole === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/admin/reports.php">
                                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/admin/users.php">
                                    <i class="bi bi-people me-1"></i>Gebruikers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/admin/categories.php">
                                    <i class="bi bi-tags me-1"></i>Categorieën
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'ai_assistant.php' ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>/admin/ai_assistant.php">
                                    <i class="bi bi-robot me-1"></i>AI Assistent
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Knowledge Base (All Roles) -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'knowledge_base.php' ? 'active' : ''; ?>" 
                               href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                                <i class="bi bi-book me-1"></i>Kennisbank
                            </a>
                        </li>
                    </ul>

                    <!-- Right Side: User Menu -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Notifications (placeholder for future) -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="notificationsDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell fs-5"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" 
                                      id="notificationBadge">
                                    0
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <li><h6 class="dropdown-header">Meldingen</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><span class="dropdown-item-text text-muted">Geen nieuwe meldingen</span></li>
                            </ul>
                        </li>

                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                               id="userMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5 me-2"></i>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuDropdown">
                                <li><h6 class="dropdown-header"><?php echo htmlspecialchars($userEmail); ?></h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <span class="dropdown-item-text">
                                        <small class="text-muted">Rol: 
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst(htmlspecialchars($userRole)); ?>
                                            </span>
                                        </small>
                                    </span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo getBaseUrl(); ?>/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Uitloggen
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- Not Logged In -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                                <i class="bi bi-book me-1"></i>Kennisbank
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>/login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Inloggen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-light btn-sm ms-2" href="<?php echo getBaseUrl(); ?>/register.php">
                                Registreren
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid">
        <div class="row">
