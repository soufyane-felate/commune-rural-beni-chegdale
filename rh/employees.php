<?php
// rh/employees.php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Optional: Delete physical files associated with this user from employee_documents
    $docs = $conn->query("SELECT file_path FROM employee_documents WHERE user_id = $del_id");
    while($doc = $docs->fetch_assoc()) {
        if(file_exists("../uploads/hr_documents/".$doc['file_path'])) {
            unlink("../uploads/hr_documents/".$doc['file_path']);
        }
    }
    
    $del = $conn->prepare("DELETE FROM users WHERE id=? AND role='employee'");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $l = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'Suppression employé ID: $del_id')");
        $l->bind_param("i", $_SESSION['user_id']); $l->execute();
        header("Location: employees.php?success=deleted"); exit();
    }
}

$dept_filter = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$query = "SELECT u.id, u.name, u.email, d.name as department, p.matricule, p.position, p.status 
          FROM users u 
          LEFT JOIN departments d ON u.department_id = d.id 
          LEFT JOIN employee_profiles p ON u.id = p.user_id
          WHERE u.role='employee'";
if ($dept_filter > 0) { $query .= " AND u.department_id = $dept_filter"; }
$query .= " ORDER BY u.name";
$employees = $conn->query($query);
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>
<div class="container-fluid mt-4 mb-5">
<div class="row">
    <?php require_once 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-users text-primary me-2"></i>Gestion des Employés</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="add_employee.php" class="btn btn-sm btn-success shadow-sm"><i class="fa-solid fa-plus me-1"></i> Nouveau Dossier Employé</a>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">Opération réussie. <button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto"><label class="col-form-label fw-bold">Filtrer par Département:</label></div>
                    <div class="col-auto">
                        <select name="dept" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">Tous les départements</option>
                            <?php while($d = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php if($dept_filter==$d['id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Matricule</th>
                                <th>Nom complet</th>
                                <th>Département</th>
                                <th>Poste</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($employees->num_rows > 0): ?>
                                <?php while($emp = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($emp['matricule'] ?? 'N/A'); ?></span></td>
                                    <td class="fw-bold">
                                        <?php echo htmlspecialchars($emp['name']); ?><br>
                                        <small class="text-muted fw-normal"><?php echo htmlspecialchars($emp['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                            $st = $emp['status'] ?? 'active';
                                            if($st == 'active') echo '<span class="badge bg-success">Actif</span>';
                                            elseif($st == 'suspended') echo '<span class="badge bg-warning text-dark">Suspendu</span>';
                                            else echo '<span class="badge bg-danger">Terminé</span>';
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="employee_dossier.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-info text-white" title="Voir Dossier">
                                            <i class="fa-solid fa-folder-open"></i> Dossier
                                        </a>
                                        <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="employees.php?delete=<?php echo $emp['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cet employé et tous ses documents ?');" title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4">Aucun employé trouvé.</td></tr>
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
