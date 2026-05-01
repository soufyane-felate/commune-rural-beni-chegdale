<?php
session_start();
$base_path = '../';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';

$error = '';
$success = '';

// Handle add department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $success = "Département ajouté avec succès.";
            
            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'Added new department')");
            $log_stmt->bind_param("i", $_SESSION['user_id']);
            $log_stmt->execute();
        } else {
            $error = "Erreur lors de l'ajout du département.";
        }
    }
}

// Fetch all departments
$departments = $conn->query("SELECT d.*, (SELECT COUNT(*) FROM users WHERE department_id = d.id AND role='employee') as employee_count FROM departments d ORDER BY d.name ASC");

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
                        <a class="nav-link text-dark" href="dashboard.php">📊 Tableau de bord</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="employees.php">👥 Gestion des employés</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link active" href="departments.php">🏢 Départements</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="complaints.php">📝 Toutes les plaintes</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des Départements</h1>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Add new department -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Ajouter un Département</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="departments.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nom du département</label>
                                    <input type="text" class="form-control" name="name" required placeholder="ex: État Civil">
                                </div>
                                <button type="submit" name="add_department" class="btn btn-primary w-100">Ajouter</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- List departments -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom du Département</th>
                                            <th>Nombre d'employés</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($departments->num_rows > 0): ?>
                                            <?php while($dept = $departments->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $dept['id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                                                <td><span class="badge bg-secondary rounded-pill"><?php echo $dept['employee_count']; ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>Modifier</button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4">Aucun département trouvé.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
