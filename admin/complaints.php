<?php
session_start();
$base_path = '../';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';

// Fetch departments for filter
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

// Handle Filters
$where_clauses = [];
$params = [];
$types = '';

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_clauses[] = "c.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $where_clauses[] = "c.department_id = ?";
    $params[] = intval($_GET['department_id']);
    $types .= 'i';
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch all complaints with details
$query = "
    SELECT c.id, c.message, c.status, c.created_at, 
           u.name as citizen_name, u.email as citizen_email,
           d.name as department_name
    FROM complaints c 
    JOIN users u ON c.user_id = u.id 
    JOIN departments d ON c.department_id = d.id
    $where_sql
    ORDER BY c.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $complaints = $stmt->get_result();
} else {
    $complaints = $conn->query($query);
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
                        <a class="nav-link text-dark" href="dashboard.php">📊 Tableau de bord</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="employees.php">👥 Gestion des employés</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="departments.php">🏢 Départements</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link active" href="complaints.php">📝 Toutes les plaintes</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Supervision des Plaintes</h1>
            </div>

            <!-- Filter Section -->
            <div class="card shadow-sm border-0 rounded-4 mb-4 bg-light">
                <div class="card-body p-3">
                    <form action="complaints.php" method="GET" class="row gx-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filtrer par Statut</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Tous les statuts</option>
                                <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                <option value="in_progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'in_progress') ? 'selected' : ''; ?>>En cours</option>
                                <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'resolved') ? 'selected' : ''; ?>>Résolu</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filtrer par Département</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">Tous les départements</option>
                                <?php while($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-sm btn-primary">Appliquer les filtres</button>
                            <a href="complaints.php" class="btn btn-sm btn-outline-secondary ms-2">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Citoyen</th>
                                    <th>Département</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($complaints->num_rows > 0): ?>
                                    <?php while($c = $complaints->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $c['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($c['citizen_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($c['citizen_email']); ?></small>
                                        </td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($c['department_name']); ?></span></td>
                                        <td>
                                            <div style="max-height: 80px; overflow-y: auto; font-size: 0.9em;">
                                                <?php echo nl2br(htmlspecialchars($c['message'])); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                                if($c['status'] == 'pending') echo '<span class="badge bg-warning text-dark">En attente</span>';
                                                elseif($c['status'] == 'in_progress') echo '<span class="badge bg-primary">En cours</span>';
                                                else echo '<span class="badge bg-success">Résolu</span>';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4">Aucune plainte trouvée.</td></tr>
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
