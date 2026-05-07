<?php
// rh/reports.php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';

$report_type = isset($_GET['type']) ? $_GET['type'] : 'attendance';
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
$is_export = isset($_GET['export']) && $_GET['export'] === 'csv';

// Queries
if ($report_type == 'attendance') {
    $report_data = $conn->query("SELECT u.name, d.name as dept, COUNT(CASE WHEN ea.status='present' THEN 1 END) as present_days, COUNT(CASE WHEN ea.status='late' THEN 1 END) as late_days, COUNT(CASE WHEN ea.status='absent' THEN 1 END) as absent_days, COUNT(CASE WHEN ea.status='leave' THEN 1 END) as leave_days FROM users u LEFT JOIN departments d ON u.department_id=d.id LEFT JOIN employee_attendance ea ON u.id=ea.user_id AND ea.date BETWEEN '$from' AND '$to' WHERE u.role='employee' GROUP BY u.id ORDER BY u.name");
} elseif ($report_type == 'leave') {
    $report_data = $conn->query("SELECT u.name, d.name as dept, lr.leave_type, lr.start_date, lr.end_date, lr.status, DATEDIFF(lr.end_date, lr.start_date)+1 as days FROM leave_requests lr JOIN users u ON lr.user_id=u.id LEFT JOIN departments d ON u.department_id=d.id WHERE lr.start_date >= '$from' AND lr.end_date <= '$to' ORDER BY lr.start_date DESC");
} elseif ($report_type == 'department') {
    $report_data = $conn->query("SELECT d.name, COUNT(u.id) as total, COUNT(CASE WHEN ea.date=CURDATE() AND ea.status='present' THEN 1 END) as present_today FROM departments d LEFT JOIN users u ON u.department_id=d.id AND u.role='employee' LEFT JOIN employee_attendance ea ON u.id=ea.user_id AND ea.date=CURDATE() GROUP BY d.id ORDER BY d.name");
}

$leave_labels = ['annual'=>'Annuel','sick'=>'Maladie','personal'=>'Personnel','maternity'=>'Maternité','other'=>'Autre'];

// Handle CSV Export
if ($is_export && isset($report_data) && $report_data->num_rows > 0) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Report_' . ucfirst($report_type) . '_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Add BOM to fix UTF-8 in Excel
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

    if ($report_type == 'attendance') {
        fputcsv($output, ['Employé', 'Département', 'Jours Présents', 'Jours Retards', 'Jours Absents', 'Jours Congés']);
        while($r = $report_data->fetch_assoc()) {
            fputcsv($output, [$r['name'], $r['dept'], $r['present_days'], $r['late_days'], $r['absent_days'], $r['leave_days']]);
        }
    } elseif ($report_type == 'leave') {
        fputcsv($output, ['Employé', 'Département', 'Type', 'Date Début', 'Date Fin', 'Nombre de jours', 'Statut']);
        while($r = $report_data->fetch_assoc()) {
            $type = $leave_labels[$r['leave_type']] ?? $r['leave_type'];
            fputcsv($output, [$r['name'], $r['dept'], $type, $r['start_date'], $r['end_date'], $r['days'], $r['status']]);
        }
    } elseif ($report_type == 'department') {
        fputcsv($output, ['Département', 'Total Employés', "Présents Aujourd'hui"]);
        while($r = $report_data->fetch_assoc()) {
            fputcsv($output, [$r['name'], $r['total'], $r['present_today']]);
        }
    }
    fclose($output);
    exit();
}

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>
<div class="container-fluid mt-4 mb-5">
<div class="row">
    <?php require_once 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="printable-area">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
            <h1 class="h2"><i class="fa-solid fa-file-lines text-primary me-2"></i>Rapports & Exports RH</h1>
            <div>
                <a href="?type=<?php echo $report_type; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&export=csv" class="btn btn-sm btn-success me-2 shadow-sm"><i class="fa-solid fa-file-excel me-1"></i>Exporter Excel (CSV)</a>
                <button onclick="window.print()" class="btn btn-sm btn-danger shadow-sm"><i class="fa-solid fa-file-pdf me-1"></i>Imprimer / PDF</button>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light no-print">
            <div class="card-body p-3">
                <form method="GET" class="row gx-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Type de rapport</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="attendance" <?php echo ($report_type=='attendance')?'selected':''; ?>>Présence</option>
                            <option value="leave" <?php echo ($report_type=='leave')?'selected':''; ?>>Congés</option>
                            <option value="department" <?php echo ($report_type=='department')?'selected':''; ?>>Départements</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label fw-bold">De</label><input type="date" class="form-control form-control-sm" name="from" value="<?php echo $from; ?>"></div>
                    <div class="col-md-3"><label class="form-label fw-bold">À</label><input type="date" class="form-control form-control-sm" name="to" value="<?php echo $to; ?>"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary px-4">Générer</button></div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <?php if($report_type=='attendance'): ?>Rapport de Présence
                    <?php elseif($report_type=='leave'): ?>Rapport des Congés
                    <?php else: ?>Rapport par Département<?php endif; ?>
                    <small class="text-muted fw-normal ms-2">(<?php echo date('d/m/Y', strtotime($from)); ?> — <?php echo date('d/m/Y', strtotime($to)); ?>)</small>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <!-- ATTENDANCE -->
                    <?php if($report_type == 'attendance' && $report_data): ?>
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light"><tr><th>Employé</th><th>Département</th><th class="text-center">Présent</th><th class="text-center">Retard</th><th class="text-center">Absent</th><th class="text-center">Congé</th></tr></thead>
                        <tbody>
                        <?php if($report_data->num_rows > 0): ?>
                            <?php $report_data->data_seek(0); while($r = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['dept'] ?? '—'); ?></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $r['present_days']; ?></span></td>
                                <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $r['late_days']; ?></span></td>
                                <td class="text-center"><span class="badge bg-danger"><?php echo $r['absent_days']; ?></span></td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?php echo $r['leave_days']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?><tr><td colspan="6" class="text-center py-4">Aucune donnée</td></tr><?php endif; ?>
                        </tbody>
                    </table>

                    <!-- LEAVE -->
                    <?php elseif($report_type == 'leave' && $report_data): ?>
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light"><tr><th>Employé</th><th>Département</th><th>Type</th><th>Début</th><th>Fin</th><th>Jours</th><th>Statut</th></tr></thead>
                        <tbody>
                        <?php if($report_data->num_rows > 0): ?>
                            <?php $report_data->data_seek(0); while($r = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['dept'] ?? '—'); ?></td>
                                <td><?php echo $leave_labels[$r['leave_type']] ?? $r['leave_type']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($r['start_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($r['end_date'])); ?></td>
                                <td><span class="badge bg-dark"><?php echo $r['days']; ?></span></td>
                                <td><?php if($r['status']=='approved') echo '<span class="badge bg-success">Approuvé</span>'; elseif($r['status']=='rejected') echo '<span class="badge bg-danger">Rejeté</span>'; else echo '<span class="badge bg-warning text-dark">En attente</span>'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?><tr><td colspan="7" class="text-center py-4">Aucune donnée.</td></tr><?php endif; ?>
                        </tbody>
                    </table>

                    <!-- DEPARTMENT -->
                    <?php elseif($report_type == 'department' && $report_data): ?>
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light"><tr><th>Département</th><th class="text-center">Total Employés</th><th class="text-center">Présents Aujourd'hui</th></tr></thead>
                        <tbody>
                        <?php if($report_data->num_rows > 0): ?>
                            <?php $report_data->data_seek(0); while($r = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['name']); ?></td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill"><?php echo $r['total']; ?></span></td>
                                <td class="text-center"><span class="badge bg-success rounded-pill"><?php echo $r['present_today']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?><tr><td colspan="3" class="text-center py-4">Aucune donnée</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</div>
<style>
@media print {
    .no-print, .sidebar { display: none !important; }
    main { width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
<?php require_once $base_path . 'includes/footer.php'; ?>
