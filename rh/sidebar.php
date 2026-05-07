<?php
// rh/sidebar.php
// A common sidebar for the HR module
?>
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm rounded-4 mb-4">
    <div class="position-sticky pt-3 pb-3">
        <h6 class="px-3 text-muted text-uppercase small fw-bold mb-3">Ressources Humaines</h6>
        <ul class="nav flex-column nav-pills">
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : 'text-dark'; ?>" href="dashboard.php">
                    <i class="fa-solid fa-chart-pie me-2"></i> Tableau de bord
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['employees.php', 'add_employee.php', 'edit_employee.php', 'employee_dossier.php']) ? 'active' : 'text-dark'; ?>" href="employees.php">
                    <i class="fa-solid fa-users me-2"></i> Employés (Dossiers)
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : 'text-dark'; ?>" href="attendance.php">
                    <i class="fa-solid fa-clipboard-user me-2"></i> Présence & Pointage
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'leave_requests.php' ? 'active' : 'text-dark'; ?>" href="leave_requests.php">
                    <i class="fa-solid fa-calendar-minus me-2"></i> Congés & Médical
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'salary.php' ? 'active' : 'text-dark'; ?>" href="salary.php">
                    <i class="fa-solid fa-money-bill-wave me-2"></i> Salaires
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : 'text-dark'; ?>" href="messages.php">
                    <i class="fa-solid fa-envelope me-2"></i> Comm. Interne
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : 'text-dark'; ?>" href="reports.php">
                    <i class="fa-solid fa-file-lines me-2"></i> Rapports & Exports
                </a>
            </li>
            <hr>
            <li class="nav-item">
                <a class="nav-link text-dark" href="../admin/dashboard.php">
                    <i class="fa-solid fa-arrow-left me-2"></i> Admin Panel
                </a>
            </li>
        </ul>
    </div>
</div>
