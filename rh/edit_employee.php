<?php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id == 0) { header("Location: employees.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='employee'");
$stmt->bind_param("i", $id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
if (!$employee) { header("Location: employees.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department_id = intval($_POST['department_id']);
    $password = trim($_POST['password']);
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $u = $conn->prepare("UPDATE users SET name=?, email=?, department_id=?, password=? WHERE id=?");
        $u->bind_param("ssisi", $name, $email, $department_id, $hashed, $id);
    } else {
        $u = $conn->prepare("UPDATE users SET name=?, email=?, department_id=? WHERE id=?");
        $u->bind_param("ssii", $name, $email, $department_id, $id);
    }
    if ($u->execute()) {
        $l = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $a = "Modification employé: $name"; $l->bind_param("is", $_SESSION['user_id'], $a); $l->execute();
        header("Location: employees.php?success=updated"); exit();
    } else { $error_msg = "Erreur lors de la modification."; }
}
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
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
                <li class="nav-item mb-2"><a class="nav-link active" href="employees.php"><i class="fa-solid fa-users me-2"></i>Employés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="attendance.php"><i class="fa-solid fa-clipboard-user me-2"></i>Présence</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="leave_requests.php"><i class="fa-solid fa-calendar-minus me-2"></i>Congés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="salary.php"><i class="fa-solid fa-money-bill-wave me-2"></i>Salaires</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="messages.php"><i class="fa-solid fa-envelope me-2"></i>Messages</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="reports.php"><i class="fa-solid fa-file-lines me-2"></i>Rapports</a></li>
                <hr><li class="nav-item"><a class="nav-link text-dark" href="../admin/dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Admin Panel</a></li>
            </ul>
        </div>
    </div>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="pt-3 pb-2 mb-3 border-bottom"><h1 class="h2"><i class="fa-solid fa-user-pen text-primary me-2"></i>Modifier l'Employé</h1></div>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nom complet *</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nouveau mot de passe <small class="text-muted">(laisser vide pour garder l'actuel)</small></label>
                            <input type="password" class="form-control" name="password" minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Département *</label>
                            <select class="form-select" name="department_id" required>
                                <?php while($d = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($d['id']==$employee['department_id'])?'selected':''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Enregistrer</button>
                        <a href="employees.php" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
