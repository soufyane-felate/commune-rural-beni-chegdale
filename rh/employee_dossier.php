<?php
// rh/employee_dossier.php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header("Location: ../pages/login.php"); exit(); }
if (!isset($_GET['id'])) { header("Location: employees.php"); exit(); }

require_once $base_path . 'config/db.php';
$id = intval($_GET['id']);

// Fetch full profile
$query = "SELECT u.name, u.email, u.created_at, d.name as department, 
          p.cin, p.matricule, p.position, p.base_salary, p.phone, p.address, p.hiring_date, p.status 
          FROM users u 
          LEFT JOIN departments d ON u.department_id = d.id 
          LEFT JOIN employee_profiles p ON u.id = p.user_id
          WHERE u.id = ? AND u.role = 'employee'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0) { header("Location: employees.php"); exit(); }
$emp = $res->fetch_assoc();

// Fetch Documents
$docs_stmt = $conn->prepare("SELECT * FROM employee_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$docs_stmt->bind_param("i", $id);
$docs_stmt->execute();
$documents = $docs_stmt->get_result();

// Fetch Leave History (last 5)
$leaves_stmt = $conn->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$leaves_stmt->bind_param("i", $id);
$leaves_stmt->execute();
$leaves = $leaves_stmt->get_result();

// Fetch Attendance (last 5 days)
$att_stmt = $conn->prepare("SELECT * FROM employee_attendance WHERE user_id = ? ORDER BY date DESC LIMIT 5");
$att_stmt->bind_param("i", $id);
$att_stmt->execute();
$attendance = $att_stmt->get_result();

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>
<div class="container-fluid mt-4 mb-5">
<div class="row">
    <?php require_once 'sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center">
            <h1 class="h2"><i class="fa-solid fa-address-card text-primary me-2"></i>Dossier Administratif</h1>
            <div>
                <a href="edit_employee.php?id=<?php echo $id; ?>" class="btn btn-primary shadow-sm"><i class="fa-solid fa-pen me-1"></i>Modifier Profil</a>
                <a href="employees.php" class="btn btn-outline-secondary shadow-sm"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Personal Info -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;font-size:32px;">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($emp['name']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($emp['position'] ?? 'Poste non défini'); ?></p>
                        <span class="badge bg-primary mb-3"><?php echo htmlspecialchars($emp['department']); ?></span>
                        
                        <hr>
                        
                        <div class="text-start">
                            <p class="mb-2"><i class="fa-solid fa-id-badge text-muted me-2 w-20px"></i> <strong>Matricule:</strong> <?php echo htmlspecialchars($emp['matricule'] ?? '-'); ?></p>
                            <p class="mb-2"><i class="fa-solid fa-id-card text-muted me-2 w-20px"></i> <strong>CIN:</strong> <?php echo htmlspecialchars($emp['cin'] ?? '-'); ?></p>
                            <p class="mb-2"><i class="fa-solid fa-envelope text-muted me-2 w-20px"></i> <strong>Email:</strong> <?php echo htmlspecialchars($emp['email']); ?></p>
                            <p class="mb-2"><i class="fa-solid fa-phone text-muted me-2 w-20px"></i> <strong>Tél:</strong> <?php echo htmlspecialchars($emp['phone'] ?? '-'); ?></p>
                            <p class="mb-2"><i class="fa-solid fa-location-dot text-muted me-2 w-20px"></i> <strong>Adresse:</strong> <?php echo htmlspecialchars($emp['address'] ?? '-'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-file-contract text-warning me-2"></i>Contrat & Statut</h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="mb-2"><strong>Date d'embauche:</strong> <?php echo $emp['hiring_date'] ? date('d/m/Y', strtotime($emp['hiring_date'])) : '-'; ?></p>
                        <p class="mb-2"><strong>Salaire de base:</strong> <?php echo $emp['base_salary'] ? number_format($emp['base_salary'], 2).' MAD' : '-'; ?></p>
                        <p class="mb-0"><strong>Statut Actuel:</strong> 
                            <?php 
                                if($emp['status'] == 'active') echo '<span class="badge bg-success">Actif</span>';
                                elseif($emp['status'] == 'suspended') echo '<span class="badge bg-warning text-dark">Suspendu</span>';
                                else echo '<span class="badge bg-danger">Terminé</span>';
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Documents & History -->
            <div class="col-md-8">
                <!-- Documents Uploadés -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-folder-open text-info me-2"></i>Documents Administratifs</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush rounded-bottom-4">
                            <?php if($documents->num_rows > 0): ?>
                                <?php while($doc = $documents->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <i class="fa-solid fa-file-pdf text-danger fs-4 me-3 align-middle"></i>
                                            <span class="fw-bold text-uppercase"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                                            <br>
                                            <small class="text-muted">Ajouté le: <?php echo date('d/m/Y H:i', strtotime($doc['uploaded_at'])); ?></small>
                                        </div>
                                        <a href="../uploads/hr_documents/<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm"><i class="fa-solid fa-download me-1"></i>Télécharger</a>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">Aucun document téléchargé pour cet employé.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Leave History -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-minus text-danger me-2"></i>Historique des Congés (Récent)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Période</th>
                                        <th>Statut</th>
                                        <th>Certificat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($leaves->num_rows > 0): ?>
                                        <?php while($l = $leaves->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo ucfirst($l['leave_type']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($l['start_date'])) . ' au ' . date('d/m/Y', strtotime($l['end_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                        if($l['status'] == 'approved') echo '<span class="badge bg-success">Approuvé</span>';
                                                        elseif($l['status'] == 'rejected') echo '<span class="badge bg-danger">Refusé</span>';
                                                        else echo '<span class="badge bg-warning text-dark">En attente</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if($l['medical_certificate']): ?>
                                                        <a href="../uploads/medical/<?php echo $l['medical_certificate']; ?>" target="_blank" class="text-decoration-none"><i class="fa-solid fa-file-medical text-primary"></i> Voir</a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-3 text-muted">Aucun congé demandé.</td></tr>
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
