<?php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';

// Add salary record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_salary'])) {
    $emp_id = intval($_POST['employee_id']);
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $base = floatval($_POST['base_salary']);
    $ded = floatval($_POST['deductions']);
    $bon = floatval($_POST['bonuses']);
    $net = $base - $ded + $bon;
    $s = $conn->prepare("INSERT INTO salary_records (user_id, month, year, base_salary, deductions, bonuses, net_salary) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE base_salary=VALUES(base_salary), deductions=VALUES(deductions), bonuses=VALUES(bonuses), net_salary=VALUES(net_salary)");
    $s->bind_param("iiidddd", $emp_id, $month, $year, $base, $ded, $bon, $net);
    if ($s->execute()) { $success_msg = "Fiche de salaire enregistrée."; }
    else { $error_msg = "Erreur."; }
}

// Mark as paid
if (isset($_GET['pay'])) {
    $sid = intval($_GET['pay']);
    $conn->query("UPDATE salary_records SET payment_status='paid', payment_date=CURDATE() WHERE id=$sid");
    header("Location: salary.php?success=1"); exit();
}

$filter_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$salaries = $conn->query("SELECT sr.*, u.name, d.name as dept FROM salary_records sr JOIN users u ON sr.user_id=u.id LEFT JOIN departments d ON u.department_id=d.id WHERE sr.month=$filter_month AND sr.year=$filter_year ORDER BY u.name");
$employees = $conn->query("SELECT id, name FROM users WHERE role='employee' ORDER BY name");

$months = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
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
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="attendance.php"><i class="fa-solid fa-clipboard-user me-2"></i>Présence</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="leave_requests.php"><i class="fa-solid fa-calendar-minus me-2"></i>Congés</a></li>
                <li class="nav-item mb-2"><a class="nav-link active" href="salary.php"><i class="fa-solid fa-money-bill-wave me-2"></i>Salaires</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="messages.php"><i class="fa-solid fa-envelope me-2"></i>Messages</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="reports.php"><i class="fa-solid fa-file-lines me-2"></i>Rapports</a></li>
                <hr><li class="nav-item"><a class="nav-link text-dark" href="../admin/dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Admin Panel</a></li>
            </ul>
        </div>
    </div>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-money-bill-wave text-primary me-2"></i>Gestion des Salaires</h1>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSalaryModal"><i class="fa-solid fa-plus me-1"></i>Ajouter</button>
        </div>
        <?php if(isset($success_msg) || isset($_GET['success'])): ?><div class="alert alert-success alert-dismissible fade show">Opération réussie.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
            <div class="card-body p-3">
                <form method="GET" class="row gx-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mois</label>
                        <select name="month" class="form-select form-select-sm">
                            <?php for($i=1;$i<=12;$i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($filter_month==$i)?'selected':''; ?>><?php echo $months[$i]; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Année</label>
                        <input type="number" class="form-control form-control-sm" name="year" value="<?php echo $filter_year; ?>" min="2020" max="2030">
                    </div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary px-4">Filtrer</button></div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><?php echo $months[$filter_month] . ' ' . $filter_year; ?></h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Employé</th><th>Département</th><th>Base (MAD)</th><th>Déductions</th><th>Primes</th><th>Net</th><th>Statut</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if($salaries->num_rows > 0): while($s = $salaries->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($s['dept'] ?? '—'); ?></span></td>
                            <td><?php echo number_format($s['base_salary'], 2); ?></td>
                            <td class="text-danger">-<?php echo number_format($s['deductions'], 2); ?></td>
                            <td class="text-success">+<?php echo number_format($s['bonuses'], 2); ?></td>
                            <td class="fw-bold"><?php echo number_format($s['net_salary'], 2); ?></td>
                            <td><?php echo ($s['payment_status']=='paid') ? '<span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i>Payé</span>' : '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>En attente</span>'; ?></td>
                            <td class="text-nowrap">
                                <a href="payslip.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary" title="Fiche de Paie"><i class="fa-solid fa-file-invoice-dollar"></i></a>
                                <?php if($s['payment_status']=='pending'): ?>
                                <a href="salary.php?pay=<?php echo $s['id']; ?>" class="btn btn-sm btn-success ms-1" onclick="return confirm('Marquer comme payé ?');" title="Payer"><i class="fa-solid fa-check"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Aucune fiche de salaire.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</div>

<!-- Add Salary Modal -->
<div class="modal fade" id="addSalaryModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST">
    <div class="modal-header bg-light"><h5 class="modal-title fw-bold">Ajouter Fiche de Salaire</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-bold">Employé *</label><select class="form-select" name="employee_id" required><option value="" disabled selected>Choisir...</option><?php while($e = $employees->fetch_assoc()): ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['name']); ?></option><?php endwhile; ?></select></div>
        <div class="row">
            <div class="col-6 mb-3"><label class="form-label fw-bold">Mois *</label><select class="form-select" name="month" required><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo $i; ?>" <?php echo ($i==date('m'))?'selected':''; ?>><?php echo $months[$i]; ?></option><?php endfor; ?></select></div>
            <div class="col-6 mb-3"><label class="form-label fw-bold">Année *</label><input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required></div>
        </div>
        <div class="mb-3"><label class="form-label fw-bold">Salaire de base (MAD) *</label><input type="number" step="0.01" class="form-control" name="base_salary" required></div>
        <div class="row">
            <div class="col-6 mb-3"><label class="form-label fw-bold">Déductions</label><input type="number" step="0.01" class="form-control" name="deductions" value="0"></div>
            <div class="col-6 mb-3"><label class="form-label fw-bold">Primes</label><input type="number" step="0.01" class="form-control" name="bonuses" value="0"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button><button type="submit" name="add_salary" class="btn btn-primary">Enregistrer</button></div>
</form>
</div></div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
