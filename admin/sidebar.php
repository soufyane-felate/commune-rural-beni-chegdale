<!-- admin/sidebar.php -->
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm rounded-4 mb-4">
    <div class="position-sticky pt-3 pb-3">
        <ul class="nav flex-column nav-pills">
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : 'text-dark'; ?>" href="dashboard.php">
                    📊 Tableau de bord
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : 'text-dark'; ?>" href="employees.php">
                    👥 Gestion des employés
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : 'text-dark'; ?>" href="departments.php">
                    🏢 Départements
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : 'text-dark'; ?>" href="complaints.php">
                    📝 Toutes les plaintes
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['locations.php', 'add_location.php', 'edit_location.php']) ? 'active' : 'text-dark'; ?>" href="locations.php">
                    🗺️ Lieux sur la Carte
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-dark" href="../rh/dashboard.php">
                    👨‍💼 Ressources Humaines
                </a>
            </li>
            <hr>
            <li class="nav-item">
                <a class="nav-link text-danger" href="../pages/logout.php">
                    🚪 Déconnexion
                </a>
            </li>
        </ul>
    </div>
</div>
