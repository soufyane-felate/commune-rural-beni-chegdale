<?php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}
require_once $base_path . 'config/db.php';

$stats = [
    'total_employees' => $conn->query("SELECT COUNT(*) as c FROM users WHERE role='employee'")->fetch_assoc()['c'],
    'total_citizens' => $conn->query("SELECT COUNT(*) as c FROM users WHERE role='citizen'")->fetch_assoc()['c'],
    'present_today' => $conn->query("SELECT COUNT(*) as c FROM employee_attendance WHERE date=CURDATE() AND status='present'")->fetch_assoc()['c'],
    'on_leave' => $conn->query("SELECT COUNT(*) as c FROM leave_requests WHERE status='approved' AND start_date<=CURDATE() AND end_date>=CURDATE()")->fetch_assoc()['c'],
    'pending_leaves' => $conn->query("SELECT COUNT(*) as c FROM leave_requests WHERE status='pending'")->fetch_assoc()['c'],
];

$recent_logs = $conn->query("SELECT al.action, al.timestamp, u.name, u.role FROM activity_logs al JOIN users u ON al.user_id=u.id ORDER BY al.timestamp DESC LIMIT 10");
$dept_stats = $conn->query("SELECT d.name, COUNT(u.id) as cnt FROM departments d LEFT JOIN users u ON u.department_id=d.id AND u.role='employee' GROUP BY d.id ORDER BY cnt DESC");
$leave_stats = $conn->query("SELECT leave_type, COUNT(*) as cnt FROM leave_requests GROUP BY leave_type");

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container-fluid mt-4 mb-5">
<div class="row">
    <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm rounded-4 mb-4">
        <div class="position-sticky pt-3 pb-3">
            <h6 class="px-3 text-muted text-uppercase small fw-bold mb-3">Ressources Humaines</h6>
            <ul class="nav flex-column nav-pills">
                <li class="nav-item mb-2"><a class="nav-link active" href="dashboard.php"><i class="fa-solid fa-chart-pie me-2"></i>Tableau de bord</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="employees.php"><i class="fa-solid fa-users me-2"></i>Employés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="attendance.php"><i class="fa-solid fa-clipboard-user me-2"></i>Présence</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="leave_requests.php"><i class="fa-solid fa-calendar-minus me-2"></i>Congés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="salary.php"><i class="fa-solid fa-money-bill-wave me-2"></i>Salaires</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="messages.php"><i class="fa-solid fa-envelope me-2"></i>Messages</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="reports.php"><i class="fa-solid fa-file-lines me-2"></i>Rapports</a></li>
                <hr>
                <li class="nav-item"><a class="nav-link text-dark" href="../admin/dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Admin Panel</a></li>
            </ul>
        </div>
    </div>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-users-gear text-primary me-2"></i>Tableau de Bord RH</h1>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm h-100 rounded-4 border-0">
                    <div class="card-body"><h6 class="card-title opacity-75">Total Employés</h6><h2 class="display-5 fw-bold"><?php echo $stats['total_employees']; ?></h2></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm h-100 rounded-4 border-0">
                    <div class="card-body"><h6 class="card-title opacity-75">Présents Aujourd'hui</h6><h2 class="display-5 fw-bold"><?php echo $stats['present_today']; ?></h2></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info shadow-sm h-100 rounded-4 border-0">
                    <div class="card-body"><h6 class="card-title opacity-75">En Congé</h6><h2 class="display-5 fw-bold"><?php echo $stats['on_leave']; ?></h2></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-dark bg-warning shadow-sm h-100 rounded-4 border-0">
                    <div class="card-body"><h6 class="card-title opacity-75">Congés en Attente</h6><h2 class="display-5 fw-bold"><?php echo $stats['pending_leaves']; ?></h2></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-building me-2 text-primary"></i>Employés par Département</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Département</th><th class="text-end">Nombre</th></tr></thead>
                            <tbody>
                            <?php while($d = $dept_stats->fetch_assoc()): ?>
                            <tr><td><?php echo htmlspecialchars($d['name']); ?></td><td class="text-end"><span class="badge bg-primary rounded-pill"><?php echo $d['cnt']; ?></span></td></tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-chart-bar me-2 text-success"></i>Statistiques Congés</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Type</th><th class="text-end">Nombre</th></tr></thead>
                            <tbody>
                            <?php $leave_labels = ['annual'=>'Annuel','sick'=>'Maladie','personal'=>'Personnel','other'=>'Autre'];
                            while($lv = $leave_stats->fetch_assoc()): ?>
                            <tr><td><?php echo $leave_labels[$lv['leave_type']] ?? $lv['leave_type']; ?></td><td class="text-end"><span class="badge bg-info rounded-pill"><?php echo $lv['cnt']; ?></span></td></tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i>Activité Récente</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light"><tr><th>Utilisateur</th><th>Rôle</th><th>Action</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if($recent_logs->num_rows > 0): while($log = $recent_logs->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['name']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo $log['role']; ?></span></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center py-4">Aucune activité.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
