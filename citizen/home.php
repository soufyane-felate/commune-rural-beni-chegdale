<?php
session_start();
$base_path = '../';

// Check if logged in and is citizen
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'citizen') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_complaint'])) {
    $department_id = intval($_POST['department_id']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);
    
    if (!empty($department_id) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, department_id, phone, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $department_id, $phone, $message);
        
        if ($stmt->execute()) {
            $complaint_id = $conn->insert_id;
            $success_msg = "Votre requête a été soumise. Votre Numéro de Suivi est : <strong>#" . str_pad($complaint_id, 6, '0', STR_PAD_LEFT) . "</strong>";
            
            // Notification for admin
            $admin_notif = $conn->prepare("INSERT INTO notifications (user_id, message) SELECT id, 'Nouvelle plainte soumise par un citoyen' FROM users WHERE role = 'admin'");
            $admin_notif->execute();
        } else {
            $error_msg = "Une erreur est survenue. Veuillez réessayer.";
        }
    } else {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Handle Tracking Search
$tracking_results = null;
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['track_id']) && !empty($_GET['track_id'])) {
    $track_id = intval(ltrim($_GET['track_id'], '#0'));
    $track_stmt = $conn->prepare("
        SELECT c.id, c.message, c.status, c.created_at, d.name as dept_name 
        FROM complaints c 
        JOIN departments d ON c.department_id = d.id 
        WHERE c.user_id = ? AND c.id = ?
    ");
    $track_stmt->bind_param("ii", $user_id, $track_id);
    $track_stmt->execute();
    $tracking_results = $track_stmt->get_result();
}

// Fetch departments for the form
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

// Fetch ALL citizen's complaints
$my_complaints = $conn->prepare("
    SELECT c.id, c.message, c.status, c.created_at, d.name as dept_name 
    FROM complaints c 
    JOIN departments d ON c.department_id = d.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC
");
$my_complaints->bind_param("i", $user_id);
$my_complaints->execute();
$complaints_result = $my_complaints->get_result();

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="zellige-bg min-vh-100 py-5">
    <div class="container mb-5">
        <div class="row text-center mb-5">
            <div class="col-md-12">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2c/Coat_of_arms_of_Morocco.svg/1200px-Coat_of_arms_of_Morocco.svg.png" alt="Royaume du Maroc" style="height: 100px;" class="mb-3">
                <h2 class="fw-bold text-success text-uppercase">Portail Citoyen</h2>
                <h4 class="text-primary fw-bold">Commune Rurale Beni Chegdal</h4>
                <p class="text-muted lead">Marhaba (Bienvenue) <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>. Soumettez et suivez vos requêtes (Chikayat) en ligne.</p>
            </div>
        </div>
        
        <div class="row">
            <!-- Submit Complaint Form -->
            <div class="col-md-5 mb-4">
                <div class="card shadow border-0 rounded-4 moroccan-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4 text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-envelope-paper me-2" viewBox="0 0 16 16">
                                <path d="M4 0a2 2 0 0 0-2 2v1.133l-.941.502A2 2 0 0 0 0 5.4V14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5.4a2 2 0 0 0-1.059-1.765L14 3.133V2a2 2 0 0 0-2-2H4Zm10 4.267.47.25A1 1 0 0 1 15 5.4v.817l-1 .6v-2.55Zm-1 3.15-3.75 2.25L8 8.917l-1.25.75L3 7.417V2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v5.417Zm-11-.6-1-.6V5.4a1 1 0 0 1 .53-.882L2 4.267v2.55Zm13 .566v5.734l-4.778-2.867L15 7.383Zm-.035 6.88A1 1 0 0 1 14 15H2a1 1 0 0 1-.965-.738L8 10.083l6.965 4.18ZM1 13.116V7.383l4.778 2.867L1 13.117Z"/>
                            </svg>
                            <h4 class="fw-bold mb-0">Nouvelle Requête</h4>
                        </div>
                        
                        <?php if(isset($success_msg)): ?>
                            <div class="alert alert-success fw-bold"><?php echo $success_msg; ?></div>
                        <?php endif; ?>
                        <?php if(isset($error_msg)): ?>
                            <div class="alert alert-danger fw-bold"><?php echo $error_msg; ?></div>
                        <?php endif; ?>
                        
                        <form action="home.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark">Nom Complet</label>
                                <input type="text" class="form-control border-success" disabled value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-bold text-dark">Téléphone / Email <span class="text-muted fw-normal">(Optionnel)</span></label>
                                <input type="text" class="form-control border-success" id="phone" name="phone" placeholder="Ex: 0600000000">
                            </div>
                            <div class="mb-3">
                                <label for="department_id" class="form-label fw-bold text-dark">Service concerné *</label>
                                <select class="form-select border-success" id="department_id" name="department_id" required>
                                    <option value="" selected disabled>Choisir le service...</option>
                                    <?php while($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label fw-bold text-dark">Détails de la requête (Chikaya) *</label>
                                <textarea class="form-control border-success" id="message" name="message" rows="5" required placeholder="Veuillez décrire votre problème avec le maximum de détails..."></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="submit_complaint" class="btn btn-success btn-lg shadow-sm fw-bold">Envoyer la requête</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tracking and List -->
            <div class="col-md-7">
                <!-- Track Specific Complaint -->
                <div class="card shadow border-0 rounded-4 mb-4">
                    <div class="card-body p-4 bg-light rounded-4 border border-primary">
                        <h5 class="fw-bold text-primary mb-3">Suivre une requête avec l'ID</h5>
                        <form action="home.php" method="GET" class="d-flex gap-2">
                            <input type="text" class="form-control border-primary" name="track_id" placeholder="Entrez le numéro de suivi (ex: 000123)" required>
                            <button type="submit" class="btn btn-primary fw-bold px-4">Rechercher</button>
                            <?php if(isset($_GET['track_id'])): ?>
                                <a href="home.php" class="btn btn-outline-secondary">Effacer</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Citizen's Complaints List -->
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-bottom border-success">
                        <h5 class="mb-0 fw-bold text-success">
                            <?php echo (isset($_GET['track_id'])) ? 'Résultat de la recherche' : 'Historique de mes Requêtes'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php 
                        $display_result = (isset($tracking_results) && $tracking_results !== null) ? $tracking_results : $complaints_result;
                        if($display_result->num_rows > 0): 
                        ?>
                            <div class="list-group list-group-flush rounded-bottom-4">
                                <?php while($c = $display_result->fetch_assoc()): ?>
                                    <div class="list-group-item p-4 border-bottom">
                                        <div class="d-flex w-100 justify-content-between mb-2">
                                            <h6 class="mb-1 fw-bold text-primary">Service : <?php echo htmlspecialchars($c['dept_name']); ?></h6>
                                            <span class="badge bg-light text-dark border"><small><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></small></span>
                                        </div>
                                        <p class="mb-3 text-dark"><?php echo nl2br(htmlspecialchars($c['message'])); ?></p>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                            <strong class="text-muted small">Numéro de suivi: #<?php echo str_pad($c['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            <?php 
                                                if($c['status'] == 'pending') echo '<span class="badge bg-danger">En attente</span>';
                                                elseif($c['status'] == 'in_progress') echo '<span class="badge bg-warning text-dark">En cours de traitement</span>';
                                                else echo '<span class="badge bg-success">Traitée / Résolu</span>';
                                            ?>
                                        </div>
                                        
                                        <!-- Show comments/notes to Citizen if any exist -->
                                        <?php
                                        $notes_stmt = $conn->prepare("SELECT note, created_at FROM complaint_notes WHERE complaint_id = ? ORDER BY created_at ASC");
                                        $notes_stmt->bind_param("i", $c['id']);
                                        $notes_stmt->execute();
                                        $notes = $notes_stmt->get_result();
                                        if($notes->num_rows > 0):
                                        ?>
                                        <div class="mt-3 p-3 bg-white border border-success rounded">
                                            <h6 class="fw-bold text-success small mb-2"><i class="bi bi-chat-dots"></i> Réponses de l'administration :</h6>
                                            <?php while($n = $notes->fetch_assoc()): ?>
                                                <div class="mb-2">
                                                    <div class="text-muted" style="font-size:0.75rem;"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></div>
                                                    <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($n['note'])); ?></p>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-inbox mb-3 text-light" viewBox="0 0 16 16">
                                    <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625A.5.5 0 0 1 16 8.5V13a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.5a.5.5 0 0 1 .19-.391l3.7-4.625zM1 8.5v4.5A1 1 0 0 0 2 14h12a1 1 0 0 0 1-1V8.5H11a.5.5 0 0 1-.5.5 2.5 2.5 0 0 1-5 0 .5.5 0 0 1-.5-.5H1z"/>
                                </svg>
                                <p class="mb-0 fs-5">Aucune requête trouvée.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
