<?php
// rh/payslip.php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
if (!isset($_GET['id'])) { header("Location: salary.php"); exit(); }

require_once $base_path . 'config/db.php';
$id = intval($_GET['id']);

// Fetch salary record and employee details
$query = "SELECT sr.*, u.name, u.email, d.name as department, p.cin, p.matricule, p.position, p.hiring_date 
          FROM salary_records sr 
          JOIN users u ON sr.user_id = u.id 
          LEFT JOIN departments d ON u.department_id = d.id 
          LEFT JOIN employee_profiles p ON u.id = p.user_id
          WHERE sr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0) { header("Location: salary.php"); exit(); }
$salary = $res->fetch_assoc();

$months = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

require_once $base_path . 'includes/header.php';
?>
<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="salary.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print me-1"></i>Imprimer / PDF</button>
    </div>

    <div class="card shadow-sm border-0 rounded-4" id="payslip">
        <div class="card-body p-5">
            <!-- Header -->
            <div class="row border-bottom pb-4 mb-4">
                <div class="col-md-6">
                    <h3 class="fw-bold text-primary mb-1">Commune Rurale Béni Chegdale</h3>
                    <p class="text-muted mb-0">Département des Ressources Humaines</p>
                    <p class="text-muted mb-0">Province de Fquih Ben Salah</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h2 class="text-uppercase fw-bold text-secondary mb-1">Fiche de Paie</h2>
                    <h5 class="text-muted">Période : <?php echo $months[$salary['month']] . ' ' . $salary['year']; ?></h5>
                    <?php if($salary['payment_status'] == 'paid'): ?>
                        <span class="badge bg-success mt-2 fs-6">Payé le <?php echo date('d/m/Y', strtotime($salary['payment_date'])); ?></span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark mt-2 fs-6">En attente de paiement</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Employee Info -->
            <div class="row mb-5">
                <div class="col-md-6">
                    <h5 class="fw-bold border-bottom pb-2">Informations de l'Employé</h5>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="ps-0 w-25">Nom:</th><td><?php echo htmlspecialchars($salary['name']); ?></td></tr>
                        <tr><th class="ps-0">Matricule:</th><td><?php echo htmlspecialchars($salary['matricule'] ?? '-'); ?></td></tr>
                        <tr><th class="ps-0">CIN:</th><td><?php echo htmlspecialchars($salary['cin'] ?? '-'); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold border-bottom pb-2">Informations Professionnelles</h5>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="ps-0 w-25">Poste:</th><td><?php echo htmlspecialchars($salary['position'] ?? '-'); ?></td></tr>
                        <tr><th class="ps-0">Département:</th><td><?php echo htmlspecialchars($salary['department']); ?></td></tr>
                        <tr><th class="ps-0">Date d'embauche:</th><td><?php echo $salary['hiring_date'] ? date('d/m/Y', strtotime($salary['hiring_date'])) : '-'; ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Salary Details -->
            <h5 class="fw-bold border-bottom pb-2 mb-3">Détails de la Rémunération</h5>
            <table class="table table-bordered mb-5">
                <thead class="table-light">
                    <tr>
                        <th class="w-50">Description</th>
                        <th class="text-end">Gains (MAD)</th>
                        <th class="text-end">Retenues (MAD)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Salaire de base</td>
                        <td class="text-end"><?php echo number_format($salary['base_salary'], 2); ?></td>
                        <td class="text-end"></td>
                    </tr>
                    <?php if($salary['bonuses'] > 0): ?>
                    <tr>
                        <td>Primes et Indemnités</td>
                        <td class="text-end"><?php echo number_format($salary['bonuses'], 2); ?></td>
                        <td class="text-end"></td>
                    </tr>
                    <?php endif; ?>
                    <?php if($salary['deductions'] > 0): ?>
                    <tr>
                        <td>Déductions (Absences/Retards/Autres)</td>
                        <td class="text-end"></td>
                        <td class="text-end"><?php echo number_format($salary['deductions'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold bg-light">
                        <td class="text-end">Totaux</td>
                        <td class="text-end text-success"><?php echo number_format($salary['base_salary'] + $salary['bonuses'], 2); ?></td>
                        <td class="text-end text-danger"><?php echo number_format($salary['deductions'], 2); ?></td>
                    </tr>
                    <tr class="fw-bold fs-5">
                        <td colspan="2" class="text-end text-uppercase">Net à Payer (MAD)</td>
                        <td class="text-end text-primary bg-primary bg-opacity-10"><?php echo number_format($salary['net_salary'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Footer / Signatures -->
            <div class="row mt-5 pt-4">
                <div class="col-6 text-center">
                    <p class="fw-bold mb-5">Signature de l'Employé</p>
                    <div class="border-bottom w-50 mx-auto"></div>
                </div>
                <div class="col-6 text-center">
                    <p class="fw-bold mb-5">Cachet et Signature de l'Administration</p>
                    <div class="border-bottom w-50 mx-auto"></div>
                </div>
            </div>
            
            <div class="text-center mt-5 pt-3">
                <small class="text-muted">Généré le <?php echo date('d/m/Y à H:i'); ?> par le système de gestion communale.</small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body { background-color: #fff !important; }
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    #payslip { padding: 0 !important; }
}
</style>

<!-- html2pdf Library (optional if we want to add a direct download button instead of print) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<?php require_once $base_path . 'includes/footer.php'; ?>
