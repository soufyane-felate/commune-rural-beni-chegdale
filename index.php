<?php
// index.php
session_start();
$base_path = '';
require_once 'config/db.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="zellige-bg min-vh-100 pb-5">
    <!-- Welcome Section -->
    <div class="p-5 mb-4 bg-white shadow-sm border-bottom border-success border-4">
        <div class="container-fluid py-5 text-center">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2c/Coat_of_arms_of_Morocco.svg/1200px-Coat_of_arms_of_Morocco.svg.png" alt="Royaume du Maroc" style="height: 120px;" class="mb-4">
            <h1 class="display-4 fw-bold text-success mb-3 text-uppercase"><?php echo __('welcome'); ?></h1>
            <p class="col-md-8 mx-auto fs-5 text-dark mb-5">
                <?php echo __('welcome_sub'); ?>
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="citizen/home.php" class="btn btn-success btn-lg px-5 py-3 shadow-sm fw-bold"><?php echo __('citizen_services'); ?></a>
                <a href="pages/login.php" class="btn btn-outline-primary btn-lg px-5 py-3 fw-bold"><?php echo __('login_portal'); ?></a>
            </div>
        </div>
    </div>
    
    <!-- Quick Links Section -->
    <div class="container">
        <div class="row align-items-md-stretch mt-4 text-center">
          <div class="col-md-4 mb-4">
            <div class="h-100 p-5 bg-white rounded-4 shadow-sm moroccan-card">
              <div class="text-success mb-3">
                 <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                   <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                   <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2.5a1 1 0 0 0 1 1H13v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                 </svg>
              </div>
              <h3 class="fw-bold text-dark">État Civil</h3>
              <p class="text-muted">Demande d'actes de naissance, de mariage et autres documents d'état civil en ligne ou sur place.</p>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="h-100 p-5 bg-white rounded-4 shadow-sm moroccan-card">
              <div class="text-success mb-3">
                 <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-chat-left-text" viewBox="0 0 16 16">
                    <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                    <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                  </svg>
              </div>
              <h3 class="fw-bold text-primary">Plaintes (Chikayat)</h3>
              <p class="text-muted">Soumettre et suivre vos plaintes concernant les infrastructures, la propreté, l'éclairage, etc.</p>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="h-100 p-5 bg-white rounded-4 shadow-sm moroccan-card">
              <div class="text-success mb-3">
                 <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-pen" viewBox="0 0 16 16">
                   <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001zm-.644.766a.5.5 0 0 0-.707 0L1.95 11.756l-.764 3.057 3.057-.764L14.44 3.854a.5.5 0 0 0 0-.708l-1.585-1.585z"/>
                 </svg>
              </div>
              <h3 class="fw-bold text-dark">Légalisation</h3>
              <p class="text-muted">Services de légalisation des signatures et certification conforme des documents locaux.</p>
            </div>
          </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
