<?php
session_start();
$base_path = '../';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';

// Fetch employees
$query = "
    SELECT u.id, u.name, u.email, d.name as department_name, u.created_at 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.role = 'employee'
    ORDER BY u.created_at DESC
";
$employees = $conn->query($query);

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'employee'");
    
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'Deleted an employee')");
    $log_stmt->bind_param("i", $_SESSION['user_id']);
    $log_stmt->execute();
    
    header("Location: employees.php?success=deleted");
    exit();
}

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container-fluid mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm rounded-4 mb-4">
            <div class="position-sticky pt-3 pb-3">
                <ul class="nav flex-column nav-pills">
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="dashboard.php">
                            📊 Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link active" aria-current="page" href="employees.php">
                            👥 Gestion des employés
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="departments.php">
                            🏢 Départements
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="complaints.php">
                            📝 Toutes les plaintes
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="../rh/dashboard.php">
                            👨‍💼 Ressources Humaines
                        </a>
                    </li>
                    <hr>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../pages/logout.php">
                            🚪 Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des Employés</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_employee.php" class="btn btn-sm btn-primary">
                        + Ajouter un employé
                    </a>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Opération réussie.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Département</th>
                                    <th>Date d'ajout</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($employees->num_rows > 0): ?>
                                    <?php while($emp = $employees->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $emp['id']; ?></td>
                                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($emp['department_name'] ?? 'Non assigné'); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($emp['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                            <a href="employees.php?delete=<?php echo $emp['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?');">Supprimer</a>
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
