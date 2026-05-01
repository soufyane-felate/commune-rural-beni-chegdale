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

// Fetch departments for the dropdown
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $department_id = intval($_POST['department_id']);

    if (empty($name) || empty($email) || empty($password) || empty($department_id)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            // Hash password and insert employee
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'employee';
            
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssi", $name, $email, $hashed_password, $role, $department_id);
            
            if ($insert_stmt->execute()) {
                $success = 'Employé ajouté avec succès!';
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'Added new employee')");
                $log_stmt->bind_param("i", $_SESSION['user_id']);
                $log_stmt->execute();
            } else {
                $error = 'Erreur lors de l\'ajout de l\'employé.';
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
                <h1 class="h2">Ajouter un Employé</h1>
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
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="employees.php">Voir la liste</a></div>
                            <?php endif; ?>
                            
                            <form action="add_employee.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nom complet</label>
                                    <input type="text" class="form-control" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Adresse Email</label>
                                    <input type="email" class="form-control" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Département assigné</label>
                                    <select class="form-select" name="department_id" required>
                                        <option value="" selected disabled>Choisir un département...</option>
                                        <?php 
                                        $departments->data_seek(0);
                                        while($dept = $departments->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Mot de passe temporaire</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Créer le compte employé</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
