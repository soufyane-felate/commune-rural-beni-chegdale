<?php
// rh/add_employee.php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';
require_once $base_path . 'includes/lang.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic user info
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department_id = intval($_POST['department_id']);
    
    // Profile info
    $cin = trim($_POST['cin']);
    $matricule = trim($_POST['matricule']);
    $position = trim($_POST['position']);
    $salary = floatval($_POST['base_salary']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $hiring_date = trim($_POST['hiring_date']);
    $status = 'active';

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) { 
        $error_msg = "Cet email existe déjà."; 
    } else {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // 1. Insert into users
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, 'employee', ?)");
            $stmt->bind_param("sssi", $name, $email, $password, $department_id);
            $stmt->execute();
            $new_user_id = $conn->insert_id;

            // 2. Insert into employee_profiles
            $prof_stmt = $conn->prepare("INSERT INTO employee_profiles (user_id, cin, matricule, position, base_salary, phone, address, hiring_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $prof_stmt->bind_param("isssdssss", $new_user_id, $cin, $matricule, $position, $salary, $phone, $address, $hiring_date, $status);
            $prof_stmt->execute();

            // 3. Handle File Uploads
            $upload_dir = "../uploads/hr_documents/";
            $doc_types = ['doc_cin' => 'cin', 'doc_diploma' => 'diploma', 'doc_contract' => 'contract', 'doc_other' => 'other'];
            
            $doc_stmt = $conn->prepare("INSERT INTO employee_documents (user_id, document_type, file_path) VALUES (?, ?, ?)");

            foreach ($doc_types as $input_name => $db_type) {
                if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
                    $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
                    $new_filename = $new_user_id . '_' . $db_type . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $upload_dir . $new_filename)) {
                        $doc_stmt->bind_param("iss", $new_user_id, $db_type, $new_filename);
                        $doc_stmt->execute();
                    }
                }
            }

            // 4. Log activity
            $l = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $a = "Ajout de l'employé: $name"; 
            $l->bind_param("is", $_SESSION['user_id'], $a); 
            $l->execute();

            $conn->commit();
            header("Location: employees.php?success=added"); 
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Erreur système: " . $e->getMessage();
        }
    }
}
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>
<div class="container-fluid mt-4 mb-5">
<div class="row">
    <?php require_once 'sidebar.php'; // We use the common sidebar now ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between">
            <h1 class="h2"><i class="fa-solid fa-user-plus text-primary me-2"></i>Ajouter un Employé (Dossier Complet)</h1>
            <a href="employees.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
        </div>
        
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <h5 class="text-primary mb-3"><i class="fa-solid fa-user me-2"></i>Informations de Connexion</h5>
                    <div class="row bg-light p-3 rounded mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nom complet *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email (Identifiant) *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Mot de passe *</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Département / Service *</label>
                            <select class="form-select" name="department_id" required>
                                <option value="" disabled selected>Choisir...</option>
                                <?php while($d = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <h5 class="text-primary mb-3"><i class="fa-solid fa-address-card me-2"></i>Informations Administratives (Profil RH)</h5>
                    <div class="row bg-light p-3 rounded mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">CIN *</label>
                            <input type="text" class="form-control" name="cin" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Matricule Employé *</label>
                            <input type="text" class="form-control" name="matricule" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Poste / Fonction</label>
                            <input type="text" class="form-control" name="position">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Salaire de Base (MAD)</label>
                            <input type="number" step="0.01" class="form-control" name="base_salary">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Date d'embauche</label>
                            <input type="date" class="form-control" name="hiring_date">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Adresse</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                    </div>

                    <h5 class="text-primary mb-3"><i class="fa-solid fa-file-arrow-up me-2"></i>Documents Administratifs (Upload)</h5>
                    <div class="row bg-light p-3 rounded mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Copie CIN (PDF/Image)</label>
                            <input type="file" class="form-control" name="doc_cin" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Diplômes (PDF/Archive)</label>
                            <input type="file" class="form-control" name="doc_diploma" accept=".pdf,.zip,.rar">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Contrat de Travail (PDF)</label>
                            <input type="file" class="form-control" name="doc_contract" accept=".pdf">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Autre document administratif</label>
                            <input type="file" class="form-control" name="doc_other" accept=".pdf,.jpg,.png">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm"><i class="fa-solid fa-save me-2"></i>Créer le dossier employé</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
