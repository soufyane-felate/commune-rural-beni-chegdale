<?php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: ../pages/login.php");
    exit();
}
require_once $base_path . 'config/db.php';
$department_id = $_SESSION['department_id'];
$user_id = $_SESSION['user_id'];

$dept_stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$dept_stmt->bind_param("i", $department_id);
$dept_stmt->execute();
$department_name = $dept_stmt->get_result()->fetch_assoc()['name'];

if (strtoupper($department_name) !== 'LEGALISATION') {
    die("<div style='padding:20px;font-family:sans-serif;'><h2>Accès refusé</h2><p>Vous n'êtes pas assigné au département Légalisation.</p><a href='dashboard.php'>Retour</a></div>");
}

// Handle action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_request'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['status'];
    $response_message = trim($_POST['response_message']);
    $citizen_user_id = intval($_POST['citizen_user_id']);
    $upload_dir = '../uploads/legalisation/';
    $response_file = '';
    if (isset($_FILES['response_file']) && $_FILES['response_file']['error'] == 0) {
        $allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
        if (in_array($_FILES['response_file']['type'], $allowed)) {
            $ext = pathinfo($_FILES['response_file']['name'], PATHINFO_EXTENSION);
            $response_file = 'resp_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['response_file']['tmp_name'], $upload_dir . $response_file);
        }
    }
    if (!empty($response_file)) {
        $u = $conn->prepare("UPDATE legalisation_requests SET status=?, response_message=?, response_file=? WHERE id=?");
        $u->bind_param("sssi", $new_status, $response_message, $response_file, $request_id);
    } else {
        $u = $conn->prepare("UPDATE legalisation_requests SET status=?, response_message=? WHERE id=?");
        $u->bind_param("ssi", $new_status, $response_message, $request_id);
    }
    if ($u->execute()) {
        $success_msg = "Demande #$request_id mise à jour.";
        $notif_msg = "Votre demande de légalisation #$request_id : " . ucfirst(str_replace('_',' ',$new_status));
        $n = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $n->bind_param("is", $citizen_user_id, $notif_msg);
        $n->execute();
        $l = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $action = "Updated legalisation request #$request_id to $new_status";
        $l->bind_param("is", $user_id, $action);
        $l->execute();
    } else { $error_msg = "Erreur lors de la mise à jour."; }
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$where_sql = "";
$params = []; $types = '';
if (!empty($filter_status)) { $where_sql = "WHERE lr.status = ?"; $params[] = $filter_status; $types .= 's'; }

$query = "SELECT lr.*, u.name as citizen_name, u.email as citizen_email FROM legalisation_requests lr JOIN users u ON lr.user_id = u.id $where_sql ORDER BY lr.created_at DESC";
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $requests = $stmt->get_result();
} else { $requests = $conn->query($query); }

$service_labels = ['signature'=>'Légalisation de signature','certification'=>'Certification conforme','procuration'=>'Procuration','document_legalization'=>'Légalisation de document'];

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container-fluid mt-4 mb-5">
<div class="row">
    <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm rounded-4 mb-4">
        <div class="position-sticky pt-3 pb-3">
            <ul class="nav flex-column nav-pills">
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="dashboard.php"><i class="fa-solid fa-briefcase me-2"></i>Plaintes</a></li>
                <?php if(strtoupper($department_name)==='ETAT CIVIL'): ?>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="civil_requests.php"><i class="fa-solid fa-file-signature me-2"></i>État Civil</a></li>
                <?php endif; ?>
                <li class="nav-item mb-2"><a class="nav-link active" href="legalisation_requests.php"><i class="fa-solid fa-stamp me-2"></i>Légalisation</a></li>
                <hr>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="leave_requests.php"><i class="fa-solid fa-calendar-minus me-2"></i>Mes Congés</a></li>
            </ul>
        </div>
    </div>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-stamp text-primary me-2"></i>Gestion des Demandes de Légalisation</h1>
        </div>
        <?php if(isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-check-circle me-1"></i><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
            <div class="card-body p-3">
                <form action="legalisation_requests.php" method="GET" class="row gx-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Filtrer par statut</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Toutes</option>
                            <option value="pending" <?php echo ($filter_status=='pending')?'selected':''; ?>>En attente</option>
                            <option value="approved" <?php echo ($filter_status=='approved')?'selected':''; ?>>Approuvée</option>
                            <option value="need_presence" <?php echo ($filter_status=='need_presence')?'selected':''; ?>>Présence Requise</option>
                            <option value="rejected" <?php echo ($filter_status=='rejected')?'selected':''; ?>>Rejetée</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sm btn-primary fw-bold px-4">Filtrer</button>
                        <a href="legalisation_requests.php" class="btn btn-sm btn-outline-secondary ms-2">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>ID</th><th>Citoyen</th><th>Service</th><th>RDV</th><th>Pièces</th><th>Statut</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php if($requests->num_rows > 0): ?>
                            <?php while($r = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $r['id']; ?></strong></td>
                                <td><div class="fw-bold"><?php echo htmlspecialchars($r['citizen_name']); ?></div><div class="small text-muted"><?php echo htmlspecialchars($r['citizen_email']); ?></div></td>
                                <td><span class="badge bg-primary text-wrap" style="width:140px;"><?php echo $service_labels[$r['service_type']] ?? $r['service_type']; ?></span><br><small class="text-muted"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></small></td>
                                <td><?php echo !empty($r['appointment_date']) ? date('d/m/Y', strtotime($r['appointment_date'])).'<br><small>'.date('H:i', strtotime($r['appointment_time'])).'</small>' : '<span class="text-muted">—</span>'; ?></td>
                                <td>
                                    <a href="../uploads/legalisation/<?php echo htmlspecialchars($r['cin_file']); ?>" target="_blank" class="btn btn-sm btn-outline-dark mb-1 d-block"><i class="fa-solid fa-id-card"></i> CIN</a>
                                    <?php if(!empty($r['documents'])): ?>
                                    <a href="../uploads/legalisation/<?php echo htmlspecialchars($r['documents']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary d-block"><i class="fa-solid fa-paperclip"></i> Doc</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php if($r['status']=='pending') echo '<span class="badge bg-warning text-dark">En attente</span>';
                                      elseif($r['status']=='approved') echo '<span class="badge bg-success">Approuvée</span>';
                                      elseif($r['status']=='rejected') echo '<span class="badge bg-danger">Rejetée</span>';
                                      elseif($r['status']=='need_presence') echo '<span class="badge bg-info text-dark">Présence Requise</span>'; ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal<?php echo $r['id']; ?>">Traiter</button>
                                    <div class="modal fade" id="modal<?php echo $r['id']; ?>" tabindex="-1">
                                      <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content">
                                          <form action="legalisation_requests.php" method="POST" enctype="multipart/form-data">
                                            <div class="modal-header bg-light"><h5 class="modal-title fw-bold">Traitement #<?php echo $r['id']; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                <div class="mb-4 bg-white border p-3 rounded">
                                                    <h6><strong>Détails :</strong></h6>
                                                    <p class="mb-1"><strong>Service :</strong> <?php echo $service_labels[$r['service_type']] ?? $r['service_type']; ?></p>
                                                    <p class="mb-0"><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($r['description'])); ?></p>
                                                </div>
                                                <input type="hidden" name="action_request" value="1">
                                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="citizen_user_id" value="<?php echo $r['user_id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Nouveau Statut</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="pending" <?php echo ($r['status']=='pending')?'selected':''; ?>>En attente</option>
                                                        <option value="approved" <?php echo ($r['status']=='approved')?'selected':''; ?>>Approuvée</option>
                                                        <option value="need_presence" <?php echo ($r['status']=='need_presence')?'selected':''; ?>>Présence Requise</option>
                                                        <option value="rejected" <?php echo ($r['status']=='rejected')?'selected':''; ?>>Rejetée</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Document de réponse <small class="text-muted">(Si approuvée)</small></label>
                                                    <input type="file" class="form-control" name="response_file" accept=".pdf,.jpg,.jpeg,.png">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Message au citoyen</label>
                                                    <textarea class="form-control" name="response_message" rows="3"><?php echo htmlspecialchars($r['response_message'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                            </div>
                                          </form>
                                        </div>
                                      </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Aucune demande trouvée.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
