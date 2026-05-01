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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: employees.php");
    exit();
}

$employee_id = intval($_GET['id']);

// Fetch departments for the dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

// Fetch employee details
$stmt = $conn->prepare("SELECT name, email, department_id FROM users WHERE id = ? AND role = 'employee'");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: employees.php");
    exit();
}

$employee = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department_id = intval($_POST['department_id']);

    if (empty($name) || empty($email) || empty($department_id)) {
        $error = 'Veuillez remplir les champs obligatoires.';
    } else {
        // Check if email already exists for another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $employee_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Cet email est déjà utilisé par un autre utilisateur.';
        } else {
            // Update logic (without changing password unless provided)
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                if (strlen($password) < 6) {
                    $error = 'Le mot de passe doit contenir au moins 6 caractères.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, department_id = ?, password = ? WHERE id = ?");
                    $update_stmt->bind_param("ssisi", $name, $email, $department_id, $hashed_password, $employee_id);
                }
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, department_id = ? WHERE id = ?");
                $update_stmt->bind_param("ssii", $name, $email, $department_id, $employee_id);
            }
            
            if (empty($error) && isset($update_stmt) && $update_stmt->execute()) {
                $success = 'Informations de l\'employé mises à jour avec succès!';
                $employee['name'] = $name;
                $employee['email'] = $email;
                $employee['department_id'] = $department_id;
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'Updated employee #$employee_id')");
                $log_stmt->bind_param("i", $_SESSION['user_id']);
                $log_stmt->execute();
            } elseif (empty($error)) {
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
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
                        <a class="nav-link active" href="employees.php">👥 Gestion des employés</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="departments.php">🏢 Départements</a>
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
                <h1 class="h2">Modifier un Employé</h1>
                <a href="employees.php" class="btn btn-sm btn-outline-secondary">Retour à la liste</a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <?php if(!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($success)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>
                            
                            <form action="edit_employee.php?id=<?php echo $employee_id; ?>" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nom complet</label>
                                    <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($employee['name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Adresse Email</label>
                                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($employee['email']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Département assigné</label>
                                    <select class="form-select" name="department_id" required>
                                        <option value="" disabled>Choisir un département...</option>
                                        <?php 
                                        $departments->data_seek(0);
                                        while($dept = $departments->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo ($employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Nouveau mot de passe <small class="text-muted">(Laisser vide pour ne pas modifier)</small></label>
                                    <input type="password" class="form-control" name="password">
                                </div>
                                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
