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
            <i class="fa-solid fa-landmark text-white mx-2 fs-4"></i>
            Commune Beni Chegdal
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav <?php echo ($_SESSION['lang'] == 'ar') ? 'me-auto' : 'ms-auto'; ?> align-items-center">
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $base_path; ?>index.php"><i class="fa-solid fa-house mx-1"></i> <?php echo __('home'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $base_path; ?>pages/history.php"><i class="fa-solid fa-clock-rotate-left mx-1"></i> <?php echo __('history'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $base_path; ?>pages/map.php"><i class="fa-solid fa-map-location-dot mx-1"></i> <?php echo __('map'); ?></a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <li class="nav-item"><a class="nav-link px-3" href="<?php echo $base_path; ?>admin/dashboard.php"><i class="fa-solid fa-chart-line mx-1"></i> <?php echo __('admin_dashboard'); ?></a></li>
                        <li class="nav-item"><a class="nav-link px-3" href="<?php echo $base_path; ?>rh/dashboard.php"><i class="fa-solid fa-users-gear mx-1"></i> RH</a></li>
                    <?php elseif($_SESSION['user_role'] == 'employee'): ?>
                        <li class="nav-item"><a class="nav-link px-3" href="<?php echo $base_path; ?>employee/dashboard.php"><i class="fa-solid fa-briefcase mx-1"></i> <?php echo __('employee_space'); ?></a></li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle px-3" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user mx-1"></i> <?php echo __('citizen_space'); ?>
                            </a>
                            <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>citizen/home.php"><i class="fa-solid fa-envelope-open-text mx-2 text-success"></i> <?php echo __('complaints'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>citizen/civil_services.php"><i class="fa-solid fa-file-signature mx-2 text-primary"></i> <?php echo __('civil_status'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_path; ?>citizen/legalisation.php"><i class="fa-solid fa-stamp mx-2 text-danger"></i> <?php echo __('legalisation'); ?></a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item mx-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-outline-light rounded-pill px-4" href="<?php echo $base_path; ?>pages/logout.php">
                            <i class="fa-solid fa-right-from-bracket mx-1"></i> <?php echo __('logout'); ?> (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $base_path; ?>pages/login.php"><i class="fa-solid fa-right-to-bracket mx-1"></i> <?php echo __('login'); ?></a>
                    </li>
                    <li class="nav-item mx-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-success text-white rounded-pill px-4 shadow-sm" href="<?php echo $base_path; ?>pages/register.php">
                            <i class="fa-solid fa-user-plus mx-1"></i> <?php echo __('register'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Language Switcher -->
                <li class="nav-item mx-lg-2 mt-3 mt-lg-0">
                    <?php if($_SESSION['lang'] == 'ar'): ?>
                        <a href="<?php echo $base_path; ?>switch_lang.php?lang=fr" class="btn btn-sm btn-light fw-bold text-primary px-3 rounded-pill">Français</a>
                    <?php else: ?>
                        <a href="<?php echo $base_path; ?>switch_lang.php?lang=ar" class="btn btn-sm btn-light fw-bold text-primary px-3 rounded-pill" style="font-family: Arial, sans-serif;">العربية</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>
