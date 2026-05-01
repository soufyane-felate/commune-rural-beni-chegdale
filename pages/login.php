<?php
session_start();
$base_path = '../';
require_once $base_path . 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, department_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];

                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'User logged in')");
                $log_stmt->bind_param("i", $user['id']);
                $log_stmt->execute();

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] === 'employee') {
                    header("Location: ../employee/dashboard.php");
                } else {
                    header("Location: ../citizen/home.php");
                }
                exit();
            } else {
                $error = 'Mot de passe incorrect.';
            }
        } else {
            $error = 'Aucun compte trouvé avec cet email.';
        }
    }
}

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-primary text-white text-center py-3 rounded-top-4">
                    <h4 class="mb-0 fw-bold">Connexion / System Login</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Adresse Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="nom@exemple.com">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Mot de passe</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
                        </div>
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">Se connecter</button>
                            <a href="register.php" class="btn btn-outline-secondary">Créer un compte Citoyen</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
