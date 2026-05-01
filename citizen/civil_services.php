<?php
session_start();
$base_path = '../';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'citizen') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $service_type = trim($_POST['service_type']);
    $message = trim($_POST['message']);
    $act_number = trim($_POST['act_number']);
    $act_year = trim($_POST['act_year']);
    $act_commune = trim($_POST['act_commune']);
    
    // File upload logic
    $upload_dir = '../uploads/civil_requests/';
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    $cin_file = '';
    
    if (isset($_FILES['cin_file']) && $_FILES['cin_file']['error'] == 0) {
        $file_type = $_FILES['cin_file']['type'];
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['cin_file']['name'], PATHINFO_EXTENSION);
            $cin_file = time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['cin_file']['tmp_name'], $upload_dir . $cin_file);
        } else {
            $error_msg = "Format de fichier non autorisé. Seuls PDF, JPG, et PNG sont acceptés.";
        }
    } else {
        $error_msg = "Le téléchargement de la CIN est obligatoire.";
    }

    $documents = '';
    if (isset($_FILES['documents']) && $_FILES['documents']['error'] == 0 && empty($error_msg)) {
        $file_type = $_FILES['documents']['type'];
        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION);
            $documents = 'doc_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['documents']['tmp_name'], $upload_dir . $documents);
        }
    }

    if (empty($error_msg) && !empty($service_type)) {
        $stmt = $conn->prepare("INSERT INTO civil_requests (user_id, service_type, message, act_number, act_year, act_commune, cin_file, documents) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $service_type, $message, $act_number, $act_year, $act_commune, $cin_file, $documents);
        
        if ($stmt->execute()) {
            $success_msg = "Votre demande a été soumise avec succès et sera traitée par le service d'état civil.";
            
            // Notify Admin and Employee
            $notif = $conn->prepare("INSERT INTO notifications (user_id, message) SELECT id, 'Nouvelle demande d\'état civil soumise' FROM users WHERE role = 'admin' OR department_id = (SELECT id FROM departments WHERE name='Etat Civil' LIMIT 1)");
            $notif->execute();
        } else {
            $error_msg = "Une erreur est survenue lors de l'enregistrement de votre demande.";
        }
    } elseif(empty($error_msg)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Fetch citizen's requests
$my_requests = $conn->prepare("SELECT * FROM civil_requests WHERE user_id = ? ORDER BY created_at DESC");
$my_requests->bind_param("i", $user_id);
$my_requests->execute();
$requests_result = $my_requests->get_result();

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="zellige-bg min-vh-100 py-5">
    <div class="container mb-5">
        <div class="row text-center mb-5">
            <div class="col-md-12">
                <i class="fa-solid fa-file-signature text-success display-3 mb-3"></i>
                <h2 class="fw-bold text-success text-uppercase">Services de l'État Civil</h2>
                <p class="text-muted lead">Demandez vos documents administratifs en ligne, simplement et rapidement.</p>
            </div>
        </div>
        
        <div class="row">
            <!-- Submit Request Form -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow border-0 rounded-4 moroccan-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4 text-primary">
                            <i class="fa-solid fa-plus-circle fs-3 me-2"></i>
                            <h4 class="fw-bold mb-0">Nouvelle Demande</h4>
                        </div>
                        
                        <?php if(isset($success_msg)): ?>
                            <div class="alert alert-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i><?php echo $success_msg; ?></div>
                        <?php endif; ?>
                        <?php if(isset($error_msg)): ?>
                            <div class="alert alert-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $error_msg; ?></div>
                        <?php endif; ?>
                        
                        <form action="civil_services.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Type de document demandé *</label>
                                <select class="form-select border-primary" name="service_type" required>
                                    <option value="" selected disabled>Choisir un document...</option>
                                    <option value="Extrait d'acte de naissance">Extrait d'acte de naissance</option>
                                    <option value="Copie intégrale de naissance">Copie intégrale de naissance</option>
                                    <option value="Déclaration de naissance">Déclaration de naissance</option>
                                    <option value="Déclaration de décès">Déclaration de décès</option>
                                    <option value="Livret de famille">Demande de livret de famille</option>
                                    <option value="Correction de données">Correction de données d'état civil</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark">N° de l'acte <small>(Optionnel)</small></label>
                                    <input type="text" class="form-control" name="act_number">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-dark">Année de l'acte <small>(Optionnel)</small></label>
                                    <input type="text" class="form-control" name="act_year" placeholder="Ex: 1990">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Commune de l'acte <small>(Optionnel)</small></label>
                                <input type="text" class="form-control" name="act_commune" placeholder="Ex: Beni Chegdal">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Copie de la CIN (PDF/JPG/PNG) *</label>
                                <input type="file" class="form-control border-primary" name="cin_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <small class="text-muted">La carte d'identité nationale est obligatoire.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Autres documents justificatifs <small>(Optionnel)</small></label>
                                <input type="file" class="form-control" name="documents" accept=".pdf,.jpg,.jpeg,.png">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Message ou détails supplémentaires</label>
                                <textarea class="form-control" name="message" rows="3"></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="submit_request" class="btn btn-primary btn-lg shadow-sm fw-bold">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Envoyer la demande
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Citizen's Requests List -->
            <div class="col-lg-7">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-bottom border-primary">
                        <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-list me-2"></i>Suivi de mes demandes d'État Civil</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if($requests_result->num_rows > 0): ?>
                            <div class="list-group list-group-flush rounded-bottom-4">
                                <?php while($r = $requests_result->fetch_assoc()): ?>
                                    <div class="list-group-item p-4 border-bottom">
                                        <div class="d-flex w-100 justify-content-between mb-2">
                                            <h6 class="mb-1 fw-bold text-dark fs-5"><?php echo htmlspecialchars($r['service_type']); ?></h6>
                                            <span class="badge bg-light text-dark border"><small><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></small></span>
                                        </div>
                                        
                                        <div class="text-muted mb-3 small">
                                            <?php if(!empty($r['act_number'])): ?>
                                                <strong>N° Acte:</strong> <?php echo htmlspecialchars($r['act_number']); ?> | 
                                            <?php endif; ?>
                                            <?php if(!empty($r['act_year'])): ?>
                                                <strong>Année:</strong> <?php echo htmlspecialchars($r['act_year']); ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                            <strong class="text-dark small">ID: #<?php echo str_pad($r['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                            <?php 
                                                if($r['status'] == 'pending') echo '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> En attente</span>';
                                                elseif($r['status'] == 'approved') echo '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Approuvée</span>';
                                                elseif($r['status'] == 'rejected') echo '<span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i> Rejetée</span>';
                                                elseif($r['status'] == 'need_presence') echo '<span class="badge bg-info text-dark"><i class="fa-solid fa-user-clock me-1"></i> Présence Requise</span>';
                                            ?>
                                        </div>
                                        
                                        <!-- Administration Response -->
                                        <?php if($r['status'] != 'pending' && (!empty($r['response_message']) || !empty($r['response_file']))): ?>
                                            <div class="mt-3 p-3 bg-white border <?php echo ($r['status'] == 'rejected') ? 'border-danger' : (($r['status'] == 'approved') ? 'border-success' : 'border-info'); ?> rounded">
                                                <h6 class="fw-bold small mb-2"><i class="fa-solid fa-comment-dots"></i> Message de l'Administration :</h6>
                                                <?php if(!empty($r['response_message'])): ?>
                                                    <p class="mb-2 small text-dark"><?php echo nl2br(htmlspecialchars($r['response_message'])); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if(!empty($r['response_file'])): ?>
                                                    <a href="../uploads/civil_requests/<?php echo htmlspecialchars($r['response_file']); ?>" target="_blank" class="btn btn-sm btn-outline-success fw-bold mt-1">
                                                        <i class="fa-solid fa-file-arrow-down me-1"></i> Télécharger le document
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fa-solid fa-folder-open display-4 mb-3 text-light"></i>
                                <p class="mb-0 fs-5">Vous n'avez soumis aucune demande d'état civil pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
