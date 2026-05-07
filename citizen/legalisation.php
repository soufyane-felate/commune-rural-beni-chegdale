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
    $description = trim($_POST['description']);
    $appointment_date = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $appointment_time = !empty($_POST['appointment_time']) ? $_POST['appointment_time'] : null;
    $error_msg = '';
    $upload_dir = '../uploads/legalisation/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $allowed_types = ['application/pdf','image/jpeg','image/png','image/jpg'];
    $cin_file = '';
    if (isset($_FILES['cin_file']) && $_FILES['cin_file']['error'] == 0) {
        if (in_array($_FILES['cin_file']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['cin_file']['name'], PATHINFO_EXTENSION);
            $cin_file = 'cin_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['cin_file']['tmp_name'], $upload_dir . $cin_file);
        } else { $error_msg = "Format non autorisé. Seuls PDF, JPG, PNG acceptés."; }
    } else { $error_msg = "Le téléchargement de la CIN est obligatoire."; }
    $documents = '';
    if (isset($_FILES['documents']) && $_FILES['documents']['error'] == 0 && empty($error_msg)) {
        if (in_array($_FILES['documents']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION);
            $documents = 'doc_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['documents']['tmp_name'], $upload_dir . $documents);
        }
    }
    if (empty($error_msg) && !empty($service_type)) {
        $stmt = $conn->prepare("INSERT INTO legalisation_requests (user_id, service_type, description, cin_file, documents, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $service_type, $description, $cin_file, $documents, $appointment_date, $appointment_time);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $success_msg = "Demande soumise avec succès. N° de suivi : <strong>#" . str_pad($new_id, 5, '0', STR_PAD_LEFT) . "</strong>";
            if (!empty($appointment_date) && !empty($appointment_time)) {
                $labels = ['signature'=>'Légalisation de signature','certification'=>'Certification conforme','procuration'=>'Procuration','document_legalization'=>'Légalisation de document'];
                $purpose = $labels[$service_type] ?? $service_type;
                $a = $conn->prepare("INSERT INTO appointments (user_id, request_id, department, appointment_date, appointment_time, purpose) VALUES (?, ?, 'Legalisation', ?, ?, ?)");
                $a->bind_param("iisss", $user_id, $new_id, $appointment_date, $appointment_time, $purpose);
                $a->execute();
            }
            $notif = $conn->prepare("INSERT INTO notifications (user_id, message) SELECT id, 'Nouvelle demande de légalisation soumise' FROM users WHERE role = 'admin' OR department_id = (SELECT id FROM departments WHERE name='Legalisation' LIMIT 1)");
            $notif->execute();
        } else { $error_msg = "Erreur lors de l'enregistrement."; }
    } elseif(empty($error_msg)) { $error_msg = "Veuillez remplir tous les champs obligatoires."; }
}

$my_requests = $conn->prepare("SELECT * FROM legalisation_requests WHERE user_id = ? ORDER BY created_at DESC");
$my_requests->bind_param("i", $user_id);
$my_requests->execute();
$requests_result = $my_requests->get_result();

$my_appts = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC");
$my_appts->bind_param("i", $user_id);
$my_appts->execute();
$appts_result = $my_appts->get_result();

$notifs = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notifs->bind_param("i", $user_id);
$notifs->execute();
$notifs_result = $notifs->get_result();

$service_labels = ['signature'=>'Légalisation de signature','certification'=>'Certification conforme','procuration'=>'Procuration','document_legalization'=>'Légalisation de document administratif'];

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="zellige-bg min-vh-100 py-5">
<div class="container mb-5">
    <div class="row text-center mb-5">
        <div class="col-md-12">
            <i class="fa-solid fa-stamp text-primary display-3 mb-3"></i>
            <h2 class="fw-bold text-primary text-uppercase">Service de Légalisation</h2>
            <p class="text-muted lead">Légalisez vos documents, demandez des certifications et des procurations en ligne.</p>
        </div>
    </div>

    <?php if($notifs_result->num_rows > 0): ?>
    <div class="row mb-4"><div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-warning">
            <div class="card-body p-3">
                <h6 class="fw-bold text-warning mb-2"><i class="fa-solid fa-bell me-1"></i> Notifications</h6>
                <?php while($n = $notifs_result->fetch_assoc()): ?>
                <div class="d-flex align-items-center mb-1">
                    <i class="fa-solid fa-circle text-warning me-2" style="font-size:6px;"></i>
                    <small><?php echo htmlspecialchars($n['message']); ?></small>
                    <small class="text-muted ms-auto"><?php echo date('d/m H:i', strtotime($n['created_at'])); ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div></div>
    <?php endif; ?>
    
    <div class="row">
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
                    <?php if(isset($error_msg) && !empty($error_msg)): ?>
                        <div class="alert alert-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    <form action="legalisation.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Type de service *</label>
                            <select class="form-select border-primary" name="service_type" required>
                                <option value="" selected disabled>Choisir un service...</option>
                                <option value="signature">Légalisation de signature</option>
                                <option value="certification">Certification conforme</option>
                                <option value="procuration">Procuration</option>
                                <option value="document_legalization">Légalisation de document administratif</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Copie de la CIN (PDF/JPG/PNG) *</label>
                            <input type="file" class="form-control border-primary" name="cin_file" accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="text-muted">La carte d'identité nationale est obligatoire.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Documents à légaliser <small>(Optionnel)</small></label>
                            <input type="file" class="form-control" name="documents" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-dark"><i class="fa-solid fa-calendar me-1"></i> Date RDV</label>
                                <input type="date" class="form-control border-primary" name="appointment_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-dark"><i class="fa-solid fa-clock me-1"></i> Heure</label>
                                <input type="time" class="form-control border-primary" name="appointment_time" min="08:00" max="16:00">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Description supplémentaire</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Précisez votre demande..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="submit_request" class="btn btn-primary btn-lg shadow-sm fw-bold">
                                <i class="fa-solid fa-paper-plane me-1"></i> Envoyer la demande
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php if($appts_result->num_rows > 0): ?>
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom border-success">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-calendar-check me-2"></i>Mes Rendez-vous</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while($appt = $appts_result->fetch_assoc()): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($appt['purpose']); ?></strong>
                                    <div class="small text-muted">
                                        <i class="fa-solid fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($appt['appointment_date'])); ?>
                                        <i class="fa-solid fa-clock ms-2 me-1"></i><?php echo date('H:i', strtotime($appt['appointment_time'])); ?>
                                    </div>
                                </div>
                                <?php if($appt['status']=='booked') echo '<span class="badge bg-primary">Réservé</span>';
                                      elseif($appt['status']=='completed') echo '<span class="badge bg-success">Effectué</span>';
                                      else echo '<span class="badge bg-secondary">Annulé</span>'; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-7">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom border-primary">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-list me-2"></i>Suivi de mes demandes</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($requests_result->num_rows > 0): ?>
                    <div class="list-group list-group-flush rounded-bottom-4">
                        <?php while($r = $requests_result->fetch_assoc()): ?>
                        <div class="list-group-item p-4 border-bottom">
                            <div class="d-flex w-100 justify-content-between mb-2">
                                <h6 class="mb-1 fw-bold text-dark fs-5"><?php echo $service_labels[$r['service_type']] ?? $r['service_type']; ?></h6>
                                <span class="badge bg-light text-dark border"><small><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></small></span>
                            </div>
                            <?php if(!empty($r['description'])): ?>
                            <p class="text-muted mb-2 small"><?php echo nl2br(htmlspecialchars($r['description'])); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($r['appointment_date'])): ?>
                            <div class="mb-2"><span class="badge bg-light text-dark border"><i class="fa-solid fa-calendar-check me-1 text-success"></i>RDV: <?php echo date('d/m/Y', strtotime($r['appointment_date'])); ?> à <?php echo date('H:i', strtotime($r['appointment_time'])); ?></span></div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                <strong class="text-dark small">ID: #<?php echo str_pad($r['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                <?php if($r['status']=='pending') echo '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>En attente</span>';
                                      elseif($r['status']=='approved') echo '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Approuvée</span>';
                                      elseif($r['status']=='rejected') echo '<span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>Rejetée</span>';
                                      elseif($r['status']=='need_presence') echo '<span class="badge bg-info text-dark"><i class="fa-solid fa-user-clock me-1"></i>Présence Requise</span>'; ?>
                            </div>
                            <?php if($r['status'] != 'pending' && (!empty($r['response_message']) || !empty($r['response_file']))): ?>
                            <div class="mt-3 p-3 bg-white border <?php echo ($r['status']=='rejected')?'border-danger':(($r['status']=='approved')?'border-success':'border-info'); ?> rounded">
                                <h6 class="fw-bold small mb-2"><i class="fa-solid fa-comment-dots"></i> Réponse :</h6>
                                <?php if(!empty($r['response_message'])): ?>
                                <p class="mb-2 small"><?php echo nl2br(htmlspecialchars($r['response_message'])); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($r['response_file'])): ?>
                                <a href="../uploads/legalisation/<?php echo htmlspecialchars($r['response_file']); ?>" target="_blank" class="btn btn-sm btn-outline-success fw-bold mt-1"><i class="fa-solid fa-file-arrow-down me-1"></i>Télécharger</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fa-solid fa-folder-open display-4 mb-3 text-light"></i>
                        <p class="mb-0 fs-5">Aucune demande de légalisation soumise.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
