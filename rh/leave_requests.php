<?php
// rh/leave_requests.php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';
$is_admin = true;
$user_id = $_SESSION['user_id'];
// Only Admin Actions below

// Admin approve/reject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_leave']) && $is_admin) {
    $lid = intval($_POST['leave_id']);
    $status = $_POST['leave_status'];
    $s = $conn->prepare("UPDATE leave_requests SET status=?, approved_by=? WHERE id=?");
    $s->bind_param("sii", $status, $user_id, $lid);
    if ($s->execute()) {
        $success_msg = "Congé mis à jour.";
        // Notify employee
        $emp = $conn->query("SELECT user_id FROM leave_requests WHERE id=$lid")->fetch_assoc();
        $msg = "Votre demande de congé a été " . ($status=='approved' ? 'approuvée' : 'rejetée');
        $n = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $n->bind_param("is", $emp['user_id'], $msg);
        $n->execute();
    }
}

$filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = !empty($filter) ? "WHERE lr.status='$filter'" : "";
$leaves = $conn->query("SELECT lr.*, u.name, d.name as dept FROM leave_requests lr JOIN users u ON lr.user_id=u.id LEFT JOIN departments d ON u.department_id=d.id $where ORDER BY lr.created_at DESC");

$leave_labels = ['annual'=>'Annuel','sick'=>'Maladie','personal'=>'Personnel','maternity'=>'Maternité','other'=>'Autre'];
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>
<div class="container-fluid mt-4 mb-5">
<div class="row">
    <?php require_once 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-calendar-minus text-primary me-2"></i>Gestion des Congés & Médical</h1>
        </div>
        <?php if(isset($success_msg)): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>


        <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
            <div class="card-body p-3">
                <form method="GET" class="row gx-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Statut</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            <option value="pending" <?php echo ($filter=='pending')?'selected':''; ?>>En attente</option>
                            <option value="approved" <?php echo ($filter=='approved')?'selected':''; ?>>Approuvé</option>
                            <option value="rejected" <?php echo ($filter=='rejected')?'selected':''; ?>>Rejeté</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary px-4">Filtrer</button><a href="leave_requests.php" class="btn btn-sm btn-outline-secondary ms-2">Réinitialiser</a></div>
                </form>
            </div>
        </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Employé</th><th>Département</th><th>Type</th><th>Début</th><th>Fin</th><th>Motif / Doc</th><th>Statut</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php if($leaves->num_rows > 0): while($lv = $leaves->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold">
                                <?php echo htmlspecialchars($lv['name']); ?><br>
                                <a href="employee_dossier.php?id=<?php echo $lv['user_id']; ?>" class="small text-decoration-none">Dossier <i class="fa-solid fa-external-link-alt"></i></a>
                            </td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($lv['dept'] ?? '—'); ?></span></td>
                            <td>
                                <?php echo $leave_labels[$lv['leave_type']] ?? $lv['leave_type']; ?>
                                <?php if($lv['leave_type'] == 'sick'): ?>
                                    <i class="fa-solid fa-notes-medical text-danger" title="Congé de maladie"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($lv['start_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($lv['end_date'])); ?></td>
                            <td>
                                <small class="d-block text-truncate" style="max-width:150px;" title="<?php echo htmlspecialchars($lv['reason'] ?? '—'); ?>"><?php echo htmlspecialchars($lv['reason'] ?? '—'); ?></small>
                                <?php if($lv['medical_certificate']): ?>
                                    <a href="../uploads/medical/<?php echo $lv['medical_certificate']; ?>" target="_blank" class="badge bg-danger text-decoration-none mt-1"><i class="fa-solid fa-file-medical me-1"></i>Certificat</a>
                                <?php endif; ?>
                            </td>
                            <td><?php
                                if($lv['status']=='pending') echo '<span class="badge bg-warning text-dark">En attente</span>';
                                elseif($lv['status']=='approved') echo '<span class="badge bg-success">Approuvé</span>';
                                else echo '<span class="badge bg-danger">Rejeté</span>';
                            ?></td>
                            <?php if($lv['status']=='pending'): ?>
                            <td class="text-nowrap">
                                <form method="POST" class="d-inline"><input type="hidden" name="leave_id" value="<?php echo $lv['id']; ?>"><input type="hidden" name="action_leave" value="1"><input type="hidden" name="leave_status" value="approved"><button class="btn btn-sm btn-success" title="Approuver"><i class="fa-solid fa-check"></i></button></form>
                                <form method="POST" class="d-inline"><input type="hidden" name="leave_id" value="<?php echo $lv['id']; ?>"><input type="hidden" name="action_leave" value="1"><input type="hidden" name="leave_status" value="rejected"><button class="btn btn-sm btn-danger" title="Rejeter"><i class="fa-solid fa-xmark"></i></button></form>
                            </td>
                            <?php else: ?><td>—</td><?php endif; ?>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Aucune demande de congé.</td></tr>
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
