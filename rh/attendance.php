<?php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin','employee'])) { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';
$is_admin = ($_SESSION['user_role'] === 'admin');
$user_id = $_SESSION['user_id'];

// Employee check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_in'])) {
    $today = date('Y-m-d');
    $check = $conn->prepare("SELECT id FROM employee_attendance WHERE user_id=? AND date=?");
    $check->bind_param("is", $user_id, $today);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        $now = date('Y-m-d H:i:s');
        $status = (date('H:i') > '09:00') ? 'late' : 'present';
        $s = $conn->prepare("INSERT INTO employee_attendance (user_id, check_in, date, status) VALUES (?, ?, ?, ?)");
        $s->bind_param("isss", $user_id, $now, $today, $status);
        $s->execute();
        $success_msg = "Pointage d'entrée enregistré.";
    } else { $error_msg = "Vous avez déjà pointé aujourd'hui."; }
}

// Employee check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_out'])) {
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $s = $conn->prepare("UPDATE employee_attendance SET check_out=? WHERE user_id=? AND date=? AND check_out IS NULL");
    $s->bind_param("sis", $now, $user_id, $today);
    if ($s->execute() && $s->affected_rows > 0) { $success_msg = "Pointage de sortie enregistré."; }
    else { $error_msg = "Aucun pointage d'entrée trouvé ou sortie déjà enregistrée."; }
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($is_admin) {
    $query = "SELECT ea.*, u.name, u.email, d.name as dept FROM employee_attendance ea JOIN users u ON ea.user_id=u.id LEFT JOIN departments d ON u.department_id=d.id WHERE ea.date=? ORDER BY ea.check_in DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $filter_date);
} else {
    $query = "SELECT ea.*, u.name FROM employee_attendance ea JOIN users u ON ea.user_id=u.id WHERE ea.user_id=? ORDER BY ea.date DESC LIMIT 30";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$attendance = $stmt->get_result();

// Check if employee already checked in today
$today_check = $conn->prepare("SELECT * FROM employee_attendance WHERE user_id=? AND date=CURDATE()");
$today_check->bind_param("i", $user_id);
$today_check->execute();
$today_record = $today_check->get_result()->fetch_assoc();

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>
<div class="container-fluid mt-4 mb-5">
<div class="row">
    <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm rounded-4 mb-4">
        <div class="position-sticky pt-3 pb-3">
            <h6 class="px-3 text-muted text-uppercase small fw-bold mb-3">Ressources Humaines</h6>
            <ul class="nav flex-column nav-pills">
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="dashboard.php"><i class="fa-solid fa-chart-pie me-2"></i>Tableau de bord</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="employees.php"><i class="fa-solid fa-users me-2"></i>Employés</a></li>
                <li class="nav-item mb-2"><a class="nav-link active" href="attendance.php"><i class="fa-solid fa-clipboard-user me-2"></i>Présence</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="leave_requests.php"><i class="fa-solid fa-calendar-minus me-2"></i>Congés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="salary.php"><i class="fa-solid fa-money-bill-wave me-2"></i>Salaires</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="messages.php"><i class="fa-solid fa-envelope me-2"></i>Messages</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="reports.php"><i class="fa-solid fa-file-lines me-2"></i>Rapports</a></li>
                <hr><li class="nav-item"><a class="nav-link text-dark" href="../admin/dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Admin Panel</a></li>
            </ul>
        </div>
    </div>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-clipboard-user text-primary me-2"></i>Suivi de Présence</h1>
        </div>
        <?php if(isset($success_msg)): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <?php if($_SESSION['user_role'] === 'employee'): ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-fingerprint me-2 text-primary"></i>Pointage du jour — <?php echo date('d/m/Y'); ?></h5>
                <div class="d-flex gap-3">
                    <?php if(!$today_record): ?>
                    <form method="POST"><button type="submit" name="check_in" class="btn btn-success btn-lg"><i class="fa-solid fa-right-to-bracket me-2"></i>Pointer l'entrée</button></form>
                    <?php elseif(empty($today_record['check_out'])): ?>
                    <div class="text-success fw-bold me-3"><i class="fa-solid fa-check-circle me-1"></i>Entrée : <?php echo date('H:i', strtotime($today_record['check_in'])); ?></div>
                    <form method="POST"><button type="submit" name="check_out" class="btn btn-danger btn-lg"><i class="fa-solid fa-right-from-bracket me-2"></i>Pointer la sortie</button></form>
                    <?php else: ?>
                    <div class="text-muted"><i class="fa-solid fa-check-double me-1 text-success"></i>Entrée : <?php echo date('H:i', strtotime($today_record['check_in'])); ?> — Sortie : <?php echo date('H:i', strtotime($today_record['check_out'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($is_admin): ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
            <div class="card-body p-3">
                <form method="GET" class="row gx-3 align-items-end">
                    <div class="col-md-4"><label class="form-label fw-bold">Date</label><input type="date" class="form-control form-control-sm" name="date" value="<?php echo $filter_date; ?>"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary px-4">Filtrer</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><?php echo $is_admin ? 'Présence du '.date('d/m/Y', strtotime($filter_date)) : 'Mon historique de présence'; ?></h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Employé</th><?php if($is_admin): ?><th>Département</th><?php endif; ?><th>Date</th><th>Entrée</th><th>Sortie</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                        <?php if($attendance->num_rows > 0): while($a = $attendance->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($a['name']); ?></td>
                            <?php if($is_admin): ?><td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($a['dept'] ?? '—'); ?></span></td><?php endif; ?>
                            <td><?php echo date('d/m/Y', strtotime($a['date'])); ?></td>
                            <td><?php echo $a['check_in'] ? date('H:i', strtotime($a['check_in'])) : '—'; ?></td>
                            <td><?php echo $a['check_out'] ? date('H:i', strtotime($a['check_out'])) : '<span class="text-warning">En cours</span>'; ?></td>
                            <td><?php
                                if($a['status']=='present') echo '<span class="badge bg-success">Présent</span>';
                                elseif($a['status']=='late') echo '<span class="badge bg-warning text-dark">Retard</span>';
                                elseif($a['status']=='absent') echo '<span class="badge bg-danger">Absent</span>';
                                elseif($a['status']=='leave') echo '<span class="badge bg-info">Congé</span>';
                            ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Aucun enregistrement trouvé.</td></tr>
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
