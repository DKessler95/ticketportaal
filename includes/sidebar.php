<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['role'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Helper function to check if menu item is active
function isActive($page, $dir = null) {
    global $currentPage, $currentDir;
    if ($dir !== null) {
        return ($currentPage === $page && $currentDir === $dir) ? 'active' : '';
    }
    return ($currentPage === $page) ? 'active' : '';
}
?>

<!-- Sidebar Navigation -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse show">
    <div class="position-sticky pt-3">
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
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('create_ticket.php', 'user'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/user/create_ticket.php">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nieuw Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('my_tickets.php', 'user'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/user/my_tickets.php">
                            <i class="bi bi-list-task me-2"></i>
                            Mijn Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                            <i class="bi bi-book me-2"></i>
                            Kennisbank
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Snelle Acties</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link text-primary" href="<?php echo getBaseUrl(); ?>/user/create_ticket.php">
                            <i class="bi bi-lightning-fill me-2"></i>
                            Ticket Aanmaken
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
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('my_tickets.php', 'agent'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/agent/my_tickets.php">
                            <i class="bi bi-person-check me-2"></i>
                            Mijn Toegewezen Tickets
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Kennisbeheer</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                            <i class="bi bi-book me-2"></i>
                            Kennisbank
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/knowledge_base.php">
                            <i class="bi bi-pencil-square me-2"></i>
                            KB Beheren
                        </a>
                    </li>
                </ul>

            <?php elseif ($userRole === 'admin'): ?>
                <!-- Admin Role Sidebar -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('reports.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/reports.php">
                            <i class="bi bi-speedometer2 me-2"></i>
                            Dashboard & Rapporten
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Beheer</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('users.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/users.php">
                            <i class="bi bi-people me-2"></i>
                            Gebruikers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('categories.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/categories.php">
                            <i class="bi bi-tags me-2"></i>
                            Categorieën
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php', 'admin'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/admin/knowledge_base.php">
                            <i class="bi bi-book me-2"></i>
                            Kennisbank Beheer
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Overzicht</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('dashboard.php', 'agent'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/agent/dashboard.php">
                            <i class="bi bi-list-check me-2"></i>
                            Alle Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('knowledge_base.php'); ?>" 
                           href="<?php echo getBaseUrl(); ?>/knowledge_base.php">
                            <i class="bi bi-book-half me-2"></i>
                            Publieke Kennisbank
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
