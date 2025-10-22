<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['role'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Note: isActive() function is now defined in includes/functions.php
?>

<!-- Sidebar Navigation -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse show">
    <div class="position-sticky">
        <!-- Logo -->
        <div class="logo-container text-center">
            <?php
            // Get user location for logo
            $userLocation = $_SESSION['location'] ?? 'Kruit en Kramer';
            $logoPath = getBaseUrl() . '/assets/images/logo/';
            
            // Determine logo based on location
            if ($userLocation === 'Pronto') {
                $logoPath .= 'Pronto/logo.jpg';
            } elseif (strpos($userLocation, 'Profijt') !== false) {
                $logoPath .= 'Profijt/logo.png';
            } elseif (strpos($userLocation, 'Henders') !== false) {
                $logoPath .= 'Henders/logo.png';
            } else {
                $logoPath .= 'Kruit/logo.svg';
            }
            ?>
            <a href="<?php echo getBaseUrl(); ?>/<?php echo $userRole === 'admin' ? 'agent' : $userRole; ?>/dashboard.php">
                <img src="<?php echo $logoPath; ?>" 
                     alt="<?php echo escapeOutput($userLocation); ?>" 
                     class="img-fluid" 
                     style="max-width: 140px;">
            </a>
        </div>

        <!-- Sidebar Toggle Button (Mobile) -->
        <button class="btn btn-sm btn-outline-secondary d-md-none mb-3 w-100" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-expanded="true">
            <i class="bi bi-list"></i> Menu
        </button>

        <div class="collapse show" id="sidebarMenu">
            <?php if ($userRole === 'user'): ?>
                <!-- User Role Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('dashboard.php', 'user'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/user/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            <?php _e('nav_dashboard'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('create_ticket.php', 'user'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/user/create_ticket.php">
                            <i class="bi bi-plus-circle me-2"></i>
                            <?php _e('nav_create_ticket'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('my_tickets.php', 'user'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/user/my_tickets.php">
                            <i class="bi bi-list-task me-2"></i>
                            <?php _e('nav_my_tickets'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                            <i class="bi bi-book me-2"></i>
                            <?php _e('nav_knowledge_base'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('profile.php', 'user'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/user/profile.php">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php _e('nav_profile'); ?>
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span><?php _e('quick_actions'); ?></span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link text-primary" href="<?php echo getBaseUrl(); ?>/user/create_ticket.php">
                            <i class="bi bi-lightning-fill me-2"></i>
                            <?php _e('create_ticket'); ?>
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <hr class="my-3">

                <!-- Language Switcher -->
                <div class="px-3 mb-2">
                    <small class="text-muted d-block mb-2">Taal / Language</small>
                    <div class="btn-group w-100" role="group">
                        <a href="?lang=nl" class="btn btn-sm <?php echo getCurrentLanguage() === 'nl' ? 'btn-primary' : 'btn-outline-secondary'; ?>">NL</a>
                        <a href="?lang=en" class="btn btn-sm <?php echo getCurrentLanguage() === 'en' ? 'btn-primary' : 'btn-outline-secondary'; ?>">EN</a>
                    </div>
                </div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo getBaseUrl(); ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            <?php _e('nav_logout'); ?>
                        </a>
                    </li>
                </ul>

            <?php elseif ($userRole === 'agent'): ?>
                <!-- Agent Role Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('dashboard.php', 'agent'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/agent/dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            <?php _e('nav_dashboard'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('my_tickets.php', 'agent'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/agent/my_tickets.php">
                            <i class="bi bi-person-check me-2"></i>
                            <?php _e('nav_assigned_tickets'); ?>
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo getBaseUrl(); ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            <?php _e('nav_logout'); ?>
                        </a>
                    </li>
                </ul>

            <?php elseif ($userRole === 'admin'): ?>
                <!-- Admin Role Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('index.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/index.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            <?php _e('nav_admin'); ?> <?php _e('nav_dashboard'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('dashboard.php', 'agent'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/agent/dashboard.php">
                            <i class="bi bi-list-check me-2"></i>
                            <?php _e('nav_all_tickets'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('my_tickets.php', 'agent'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/agent/my_tickets.php">
                            <i class="bi bi-person-check me-2"></i>
                            <?php _e('nav_assigned_tickets'); ?>
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span><?php _e('management'); ?></span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('users.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/users.php">
                            <i class="bi bi-people me-2"></i>
                            <?php _e('nav_users'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('categories.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/categories.php">
                            <i class="bi bi-tags me-2"></i>
                            <?php _e('nav_categories'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('category_fields.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/category_fields.php">
                            <i class="bi bi-input-cursor-text me-2"></i>
                            <?php _e('nav_category_fields'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('ci_manage.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/ci_manage.php">
                            <i class="bi bi-hdd-rack me-2"></i>
                            CI Beheer
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('change_management.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/change_management.php">
                            <i class="bi bi-arrow-repeat me-2"></i>
                            Change Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('templates.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/templates.php">
                            <i class="bi bi-file-text me-2"></i>
                            Sjablonen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                            <i class="bi bi-book me-2"></i>
                            Kennisbank
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('kb_manage.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/kb_manage.php">
                            <i class="bi bi-book-half me-2"></i>
                            KB Beheren
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span><?php _e('reporting'); ?></span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('reports.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/reports.php">
                            <i class="bi bi-graph-up me-2"></i>
                            <?php _e('nav_reports'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('reviews.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/reviews.php">
                            <i class="bi bi-star-fill me-2"></i>
                            <?php _e('nav_reviews'); ?>
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo getBaseUrl(); ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Uitloggen
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
