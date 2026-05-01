<?php
session_start();
$base_path = '../';

// Check if logged in and is employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header("Location: ../pages/login.php");
    exit();
}

require_once $base_path . 'config/db.php';
$department_id = $_SESSION['department_id'];
$user_id = $_SESSION['user_id'];

// Get department name
$dept_stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
$dept_stmt->bind_param("i", $department_id);
$dept_stmt->execute();
$department_name = $dept_stmt->get_result()->fetch_assoc()['name'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $complaint_id = intval($_POST['complaint_id']);
    $new_status = $_POST['status'];
    
    // Verify this complaint belongs to this employee's department
    $check_stmt = $conn->prepare("SELECT id FROM complaints WHERE id = ? AND department_id = ?");
    $check_stmt->bind_param("ii", $complaint_id, $department_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $complaint_id);
        if ($update_stmt->execute()) {
            // Log activity
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $action = "Updated complaint #$complaint_id status to $new_status";
            $log_stmt->bind_param("is", $user_id, $action);
            $log_stmt->execute();
            
            $success_msg = "Statut mis à jour avec succès.";
        }
    }
}

// Handle adding note
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_note') {
    $complaint_id = intval($_POST['complaint_id']);
    $note = trim($_POST['note']);
    
    if (!empty($note)) {
        // Verify this complaint belongs to this employee's department
        $check_stmt = $conn->prepare("SELECT id FROM complaints WHERE id = ? AND department_id = ?");
        $check_stmt->bind_param("ii", $complaint_id, $department_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $insert_note = $conn->prepare("INSERT INTO complaint_notes (complaint_id, user_id, note) VALUES (?, ?, ?)");
            $insert_note->bind_param("iis", $complaint_id, $user_id, $note);
            if ($insert_note->execute()) {
                $success_msg = "Note ajoutée avec succès.";
            }
        }
    }
}

// Fetch complaints for this department
$query = "
    SELECT c.id, c.message, c.status, c.created_at, u.name as citizen_name, u.email as citizen_email 
    FROM complaints c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.department_id = ? 
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$complaints = $stmt->get_result();

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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fa-solid fa-briefcase me-2"></i> Plaintes (Département)
                        </a>
                    </li>
                    <?php if (strtoupper($department_name) === 'ETAT CIVIL'): ?>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-dark" href="civil_requests.php">
                            <i class="fa-solid fa-file-signature me-2"></i> Demandes État Civil
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-primary">Espace Employé</h2>
                    <h5 class="text-muted">Département : <span class="badge bg-info text-dark"><?php echo htmlspecialchars($department_name); ?></span></h5>
                </div>
            </div>

    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Plaintes assignées à votre département</h5>
        </div>
        <div class="card-body p-0">
            <div class="accordion accordion-flush" id="complaintsAccordion">
                <?php if($complaints->num_rows > 0): ?>
                    <?php while($c = $complaints->fetch_assoc()): ?>
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="heading<?php echo $c['id']; ?>">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $c['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $c['id']; ?>">
                                <div class="d-flex justify-content-between w-100 pe-3 align-items-center">
                                    <div>
                                        <strong>#<?php echo $c['id']; ?> - <?php echo htmlspecialchars($c['citizen_name']); ?></strong>
                                        <div class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></div>
                                    </div>
                                    <div>
                                        <?php 
                                            if($c['status'] == 'pending') echo '<span class="badge bg-warning text-dark">En attente</span>';
                                            elseif($c['status'] == 'in_progress') echo '<span class="badge bg-primary">En cours</span>';
                                            else echo '<span class="badge bg-success">Résolu</span>';
                                        ?>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $c['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $c['id']; ?>" data-bs-parent="#complaintsAccordion">
                            <div class="accordion-body bg-light">
                                <div class="row">
                                    <div class="col-md-7">
                                        <h6 class="fw-bold">Message Original :</h6>
                                        <div class="p-3 bg-white rounded border mb-3">
                                            <?php echo nl2br(htmlspecialchars($c['message'])); ?>
                                        </div>

                                        <h6 class="fw-bold mt-4">Mettre à jour le statut :</h6>
                                        <form action="dashboard.php" method="POST" class="d-flex align-items-center gap-2 mb-3">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                                            <select name="status" class="form-select form-select-sm w-auto" <?php echo ($c['status'] == 'resolved') ? 'disabled' : ''; ?>>
                                                <option value="pending" <?php echo ($c['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                                <option value="in_progress" <?php echo ($c['status'] == 'in_progress') ? 'selected' : ''; ?>>En cours</option>
                                                <option value="resolved" <?php echo ($c['status'] == 'resolved') ? 'selected' : ''; ?>>Résolu</option>
                                            </select>
                                            <?php if($c['status'] != 'resolved'): ?>
                                                <button type="submit" class="btn btn-sm btn-primary">Mettre à jour</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                    <div class="col-md-5">
                                        <h6 class="fw-bold">Notes Internes :</h6>
                                        <div class="notes-container p-3 bg-white rounded border mb-3" style="max-height: 200px; overflow-y: auto;">
                                            <?php
                                            $notes_stmt = $conn->prepare("SELECT cn.note, cn.created_at, u.name FROM complaint_notes cn JOIN users u ON cn.user_id = u.id WHERE cn.complaint_id = ? ORDER BY cn.created_at ASC");
                                            $notes_stmt->bind_param("i", $c['id']);
                                            $notes_stmt->execute();
                                            $notes_res = $notes_stmt->get_result();
                                            
                                            if($notes_res->num_rows > 0) {
                                                while($note = $notes_res->fetch_assoc()) {
                                                    echo '<div class="mb-2 pb-2 border-bottom">';
                                                    echo '<strong class="text-primary small">'.htmlspecialchars($note['name']).'</strong>';
                                                    echo '<span class="text-muted ms-2" style="font-size:0.75rem;">'.date('d/m/Y H:i', strtotime($note['created_at'])).'</span>';
                                                    echo '<p class="mb-0 small">'.nl2br(htmlspecialchars($note['note'])).'</p>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<p class="text-muted small mb-0">Aucune note pour cette plainte.</p>';
                                            }
                                            ?>
                                        </div>

                                        <form action="dashboard.php" method="POST">
                                            <input type="hidden" name="action" value="add_note">
                                            <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                                            <div class="input-group">
                                                <textarea class="form-control form-control-sm" name="note" required placeholder="Ajouter une note interne..." rows="2"></textarea>
                                                <button class="btn btn-outline-secondary btn-sm" type="submit">Ajouter</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">Aucune plainte pour votre département.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
