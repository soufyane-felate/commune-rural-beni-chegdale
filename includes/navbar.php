<?php
// includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $base_path = '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo $base_path; ?>index.php">
            <i class="fa-solid fa-landmark text-white me-2 fs-4"></i>
            Commune Beni Chegdal
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $base_path; ?>index.php"><i class="fa-solid fa-house me-1"></i> Accueil</a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <li class="nav-item"><a class="nav-link px-3" href="<?php echo $base_path; ?>admin/dashboard.php"><i class="fa-solid fa-chart-line me-1"></i> Dashboard Admin</a></li>
                    <?php elseif($_SESSION['user_role'] == 'employee'): ?>
                        <li class="nav-item"><a class="nav-link px-3" href="<?php echo $base_path; ?>employee/dashboard.php"><i class="fa-solid fa-briefcase me-1"></i> Espace Employé</a></li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle px-3" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user me-1"></i> Espace Citoyen
                            </a>
                            <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>citizen/home.php"><i class="fa-solid fa-envelope-open-text me-2 text-success"></i> Plaintes (Chikayat)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>citizen/civil_services.php"><i class="fa-solid fa-file-signature me-2 text-primary"></i> État Civil</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-outline-light rounded-pill px-4" href="<?php echo $base_path; ?>pages/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Déconnexion (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $base_path; ?>pages/login.php"><i class="fa-solid fa-right-to-bracket me-1"></i> Connexion</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-success text-white rounded-pill px-4 shadow-sm" href="<?php echo $base_path; ?>pages/register.php">
                            <i class="fa-solid fa-user-plus me-1"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
