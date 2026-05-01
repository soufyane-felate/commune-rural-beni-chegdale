<?php
// includes/footer.php
if (!isset($base_path)) {
    $base_path = '';
}
?>
    <footer class="bg-light text-center py-4 mt-auto border-top">
        <div class="container">
            <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Municipal Management System. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo $base_path; ?>assets/js/script.js"></script>
</body>
</html>
