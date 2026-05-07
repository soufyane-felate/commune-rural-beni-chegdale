<?php
session_start();
$base_path = '../';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';

// Get statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'citizen'")->fetch_assoc()['count'],
    'employees' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch_assoc()['count'],
    'complaints' => $conn->query("SELECT COUNT(*) as count FROM complaints")->fetch_assoc()['count'],
    'pending_complaints' => $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'")->fetch_assoc()['count']
];

// Get recent activity logs
$logs = $conn->query("
    SELECT al.action, al.timestamp, u.name as user_name, u.role 
    FROM activity_logs al 
    JOIN users u ON al.user_id = u.id 
    ORDER BY al.timestamp DESC LIMIT 5
");

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container-fluid mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once 'sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Tableau de bord Administrateur</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Exporter</button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary shadow-sm h-100 rounded-4 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Citoyens inscrits</h5>
                            <h2 class="display-5 fw-bold"><?php echo $stats['users']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success shadow-sm h-100 rounded-4 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Employés actifs</h5>
                            <h2 class="display-5 fw-bold"><?php echo $stats['employees']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info shadow-sm h-100 rounded-4 border-0">
                        <div class="card-body">
                            <h5 class="card-title">Total Plaintes</h5>
                            <h2 class="display-5 fw-bold"><?php echo $stats['complaints']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning shadow-sm h-100 rounded-4 border-0">
                        <div class="card-body">
                            <h5 class="card-title text-dark">Plaintes en attente</h5>
                            <h2 class="display-5 fw-bold text-dark"><?php echo $stats['pending_complaints']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Activité Récente</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Action</th>
                                    <th>Date et Heure</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($logs->num_rows > 0): ?>
                                    <?php while($log = $logs->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['role']); ?></span></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-4">Aucune activité récente.</td></tr>
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
