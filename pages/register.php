<?php
session_start();
$base_path = '../';
require_once $base_path . 'config/db.php';

$error = '';
$success = '';

// Default registration is for Citizens only as per requirements
// Admin and Employees should be created by Admin

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
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
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'citizen';
            
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'User registered as citizen')");
                $log_stmt->bind_param("i", $user_id);
                $log_stmt->execute();

                $success = 'Compte créé avec succès! Vous pouvez maintenant vous connecter.';
            } else {
                $error = 'Erreur lors de la création du compte. Veuillez réessayer.';
            }
        }
    }
}

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-success text-white text-center py-3 rounded-top-4">
                    <h4 class="mb-0 fw-bold">Inscription Citoyen / Citizen Registration</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if(!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Se connecter</a></div>
                    <?php endif; ?>
                    
                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nom complet</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Votre nom complet" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Adresse Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="nom@exemple.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Mot de passe</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">Confirmer le mot de passe</label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg shadow-sm">Créer un compte</button>
                            <a href="login.php" class="text-center mt-3 text-decoration-none">Vous avez déjà un compte ? Connectez-vous</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
