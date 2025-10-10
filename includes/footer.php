        </div> <!-- End row -->
    </div> <!-- End container-fluid -->

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> Kruit & Kramer - ICT Ticketportaal
                    </span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="text-muted">
                        Versie 1.0.0
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            | <a href="<?php echo getBaseUrl(); ?>/admin/reports.php" class="text-decoration-none">Admin</a>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/main.js"></script>
</body>
</html>
