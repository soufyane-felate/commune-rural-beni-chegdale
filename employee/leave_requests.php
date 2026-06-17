<?php
// employee/leave_requests.php
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

// Get employee info and department name
$emp_stmt = $conn->prepare("SELECT u.name, u.email, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.id=?");
$emp_stmt->bind_param("i", $user_id);
$emp_stmt->execute();
$emp = $emp_stmt->get_result()->fetch_assoc();
$employee_name = $emp['name'];
$department_name = $emp['dept_name'];

// Handle Leave Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_leave'])) {
    $leave_type = $_POST['leave_type'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $reason = trim($_POST['reason']);
    $med_cert = null;

    if ($leave_type == 'sick' && isset($_FILES['medical_cert']) && $_FILES['medical_cert']['error'] == 0) {
        $ext = pathinfo($_FILES['medical_cert']['name'], PATHINFO_EXTENSION);
        $allowed = ['pdf','jpg','jpeg','png'];
        if(in_array(strtolower($ext), $allowed)) {
            $med_cert = $user_id . '_med_' . time() . '.' . $ext;
            if (!is_dir("../uploads/medical/")) mkdir("../uploads/medical/", 0755, true);
            move_uploaded_file($_FILES['medical_cert']['tmp_name'], "../uploads/medical/" . $med_cert);
        }
    }

    $s = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, medical_certificate, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $s->bind_param("isssss", $user_id, $leave_type, $start, $end, $reason, $med_cert);
    if ($s->execute()) {
        $success_msg = "Votre demande de congé a été soumise avec succès aux Ressources Humaines.";
        // Notify RH admins
        $n = $conn->prepare("INSERT INTO notifications (user_id, message) SELECT id, ? FROM users WHERE role='admin'");
        $msg_rh = "📋 Nouvelle demande de congé de {$employee_name} ({$department_name})";
        $n->bind_param("s", $msg_rh); $n->execute();
    } else {
        $error_msg = "Une erreur est survenue lors de la soumission.";
    }
}

// Fetch employee's leave history
$s = $conn->prepare("SELECT * FROM leave_requests WHERE user_id=? ORDER BY created_at DESC");
$s->bind_param("i", $user_id);
$s->execute();
$leaves = $s->get_result();
$all_leaves = [];
while($row = $leaves->fetch_assoc()) $all_leaves[] = $row;

// Stats
$total = count($all_leaves);
$pending = count(array_filter($all_leaves, fn($l) => $l['status']=='pending'));
$approved = count(array_filter($all_leaves, fn($l) => $l['status']=='approved'));
$rejected = count(array_filter($all_leaves, fn($l) => $l['status']=='rejected'));

$leave_labels = ['annual'=>'Congé Annuel','sick'=>'Congé Maladie','personal'=>'Personnel','maternity'=>'Maternité','other'=>'Exceptionnel'];
$leave_icons  = ['annual'=>'fa-sun','sick'=>'fa-notes-medical','personal'=>'fa-user-clock','maternity'=>'fa-baby','other'=>'fa-circle-exclamation'];
$leave_colors = ['annual'=>'#4f46e5','sick'=>'#ef4444','personal'=>'#f59e0b','maternity'=>'#ec4899','other'=>'#6b7280'];

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<style>
    :root {
        --primary: #4f46e5;
        --primary-light: #eef2ff;
        --success: #22c55e;
        --success-light: #f0fdf4;
        --warning: #f59e0b;
        --warning-light: #fffbeb;
        --danger: #ef4444;
        --danger-light: #fef2f2;
        --gray: #6b7280;
        --gray-light: #f9fafb;
    }

    .leave-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
        border-radius: 20px;
        padding: 2rem 2.5rem;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .leave-hero::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .leave-hero::after {
        content: '';
        position: absolute;
        bottom: -40px; left: -40px;
        width: 150px; height: 150px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .leave-hero .avatar {
        width: 64px; height: 64px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.3);
        flex-shrink: 0;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s, box-shadow 0.2s;
        text-align: center;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .stat-card .stat-num { font-size: 2.2rem; font-weight: 800; line-height: 1; }
    .stat-card .stat-label { font-size: 0.8rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.2rem; }

    .form-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }
    .form-card .card-header-custom {
        background: linear-gradient(135deg, #f8fafc, #eef2ff);
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .leave-type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.75rem; }
    .leave-type-card {
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem 0.75rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
        position: relative;
    }
    .leave-type-card:hover { border-color: var(--primary); transform: translateY(-2px); }
    .leave-type-card input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
    .leave-type-card.selected { border-color: var(--primary); background: var(--primary-light); }
    .leave-type-card .type-icon { font-size: 1.8rem; margin-bottom: 0.4rem; display: block; }
    .leave-type-card .type-label { font-size: 0.8rem; font-weight: 600; color: #374151; }
    .leave-type-card.selected .type-label { color: var(--primary); }
    .leave-type-card .checkmark {
        position: absolute; top: 8px; right: 8px;
        width: 20px; height: 20px;
        background: var(--primary);
        border-radius: 50%; display: none;
        align-items: center; justify-content: center;
        color: white; font-size: 0.65rem;
    }
    .leave-type-card.selected .checkmark { display: flex; }

    .history-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }

    .leave-row {
        border-bottom: 1px solid #f1f5f9;
        padding: 1rem 1.5rem;
        transition: background 0.15s;
    }
    .leave-row:hover { background: #fafbff; }
    .leave-row:last-child { border-bottom: none; }

    .leave-type-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .status-pending  { background: var(--warning-light); color: #92400e; }
    .status-approved { background: var(--success-light); color: #166534; }
    .status-rejected { background: var(--danger-light);  color: #991b1b; }

    .date-range { 
        display: inline-flex; align-items: center; gap: 6px; 
        color: #374151; font-size: 0.85rem; font-weight: 500;
    }
    .date-range .arrow { color: #9ca3af; font-size: 0.75rem; }

    .upload-zone {
        border: 2px dashed #ef4444;
        border-radius: 12px;
        padding: 1.2rem;
        text-align: center;
        background: #fff5f5;
        transition: all 0.2s;
        cursor: pointer;
    }
    .upload-zone:hover { background: #fef2f2; }
    .upload-zone input { display: none; }

    .form-control, .form-select {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 0.6rem 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }
    .btn-submit-leave {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border: none;
        border-radius: 12px;
        padding: 0.75rem 2rem;
        font-weight: 700;
        font-size: 1rem;
        color: white;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(79,70,229,0.3);
    }
    .btn-submit-leave:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(79,70,229,0.4); color: white; }

    .section-title { font-weight: 700; font-size: 1.1rem; color: #1e293b; }
    .empty-state { padding: 3rem; text-align: center; color: #9ca3af; }
    .empty-state i { font-size: 3.5rem; margin-bottom: 1rem; opacity: 0.4; }

    .sidebar-link {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px;
        border-radius: 10px;
        text-decoration: none;
        color: #475569;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.15s;
        margin-bottom: 4px;
    }
    .sidebar-link:hover { background: #f1f5f9; color: #1e293b; }
    .sidebar-link.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
    .sidebar-link i { width: 20px; text-align: center; }
    .sidebar-section { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; padding: 0.5rem 14px; margin-top: 0.5rem; }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="bg-white rounded-4 shadow-sm p-3 position-sticky" style="top:80px; border: 1px solid #f1f5f9;">
                <div class="sidebar-section">Navigation</div>
                <a class="sidebar-link" href="dashboard.php">
                    <i class="fa-solid fa-briefcase"></i> Plaintes Département
                </a>
                <?php if (strtoupper($department_name) === 'ETAT CIVIL'): ?>
                <a class="sidebar-link" href="civil_requests.php">
                    <i class="fa-solid fa-file-signature"></i> État Civil
                </a>
                <?php endif; ?>
                <?php if (strtoupper($department_name) === 'LEGALISATION'): ?>
                <a class="sidebar-link" href="legalisation_requests.php">
                    <i class="fa-solid fa-stamp"></i> Légalisation
                </a>
                <?php endif; ?>
                <div class="sidebar-section" style="margin-top:1rem;">Mon espace</div>
                <a class="sidebar-link active" href="leave_requests.php">
                    <i class="fa-solid fa-calendar-days"></i> Mes Congés
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 col-lg-10 px-md-4">

            <!-- Hero Banner -->
            <div class="leave-hero">
                <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
                    <div class="avatar"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <h2 class="fw-bold mb-0 fs-4">Gestion de mes Congés</h2>
                        <p class="mb-0 opacity-75 small"><?php echo htmlspecialchars($employee_name); ?> &bull; Département <?php echo htmlspecialchars($department_name); ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#eef2ff;"><i class="fa-solid fa-list text-primary"></i></div>
                        <div class="stat-num text-primary"><?php echo $total; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fffbeb;"><i class="fa-solid fa-clock" style="color:#f59e0b"></i></div>
                        <div class="stat-num" style="color:#f59e0b"><?php echo $pending; ?></div>
                        <div class="stat-label">En attente</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f0fdf4;"><i class="fa-solid fa-check" style="color:#22c55e"></i></div>
                        <div class="stat-num" style="color:#22c55e"><?php echo $approved; ?></div>
                        <div class="stat-label">Approuvés</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fef2f2;"><i class="fa-solid fa-xmark" style="color:#ef4444"></i></div>
                        <div class="stat-num" style="color:#ef4444"><?php echo $rejected; ?></div>
                        <div class="stat-label">Rejetés</div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if(isset($success_msg)): ?>
            <div class="alert border-0 rounded-3 d-flex align-items-center gap-2 mb-4" style="background:#f0fdf4; color:#166534;" role="alert">
                <i class="fa-solid fa-circle-check fs-5"></i>
                <span><?php echo $success_msg; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
            <div class="alert border-0 rounded-3 d-flex align-items-center gap-2 mb-4" style="background:#fef2f2; color:#991b1b;" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                <span><?php echo $error_msg; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- New Leave Request Form -->
            <div class="form-card mb-4">
                <div class="card-header-custom d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;background:var(--primary);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-plus text-white"></i>
                    </div>
                    <div>
                        <div class="section-title">Nouvelle Demande de Congé</div>
                        <div class="text-muted small">Remplissez le formulaire, votre demande sera traitée par les RH</div>
                    </div>
                </div>
                <div class="p-4">
                    <form method="POST" enctype="multipart/form-data" id="leaveForm">
                        <!-- Leave Type Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-3"><i class="fa-solid fa-tag me-2 text-primary"></i>Type de Congé *</label>
                            <div class="leave-type-grid">
                                <?php
                                $types = [
                                    'annual'   => ['Annuel',      '☀️'],
                                    'sick'     => ['Maladie',     '🏥'],
                                    'personal' => ['Personnel',   '👤'],
                                    'maternity'=> ['Maternité',   '👶'],
                                    'other'    => ['Exceptionnel','⚡'],
                                ];
                                foreach($types as $val => [$label, $emoji]):
                                ?>
                                <label class="leave-type-card" id="card_<?php echo $val; ?>" onclick="selectType('<?php echo $val; ?>')">
                                    <input type="radio" name="leave_type" value="<?php echo $val; ?>" <?php echo $val=='annual' ? 'checked' : ''; ?>>
                                    <div class="checkmark"><i class="fa-solid fa-check"></i></div>
                                    <span class="type-icon"><?php echo $emoji; ?></span>
                                    <span class="type-label"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-day me-2 text-primary"></i>Date de début *</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" required min="<?php echo date('Y-m-d'); ?>" onchange="calcDays()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Date de fin *</label>
                                <input type="date" class="form-control" name="end_date" id="end_date" required min="<?php echo date('Y-m-d'); ?>" onchange="calcDays()">
                            </div>
                        </div>
                        <!-- Days Counter -->
                        <div id="days_counter" class="mb-4" style="display:none;">
                            <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3" style="background:var(--primary-light); border: 1px solid #c7d2fe;">
                                <i class="fa-solid fa-calendar-week text-primary"></i>
                                <span class="fw-bold text-primary" id="days_text">0 jour(s) de congé</span>
                            </div>
                        </div>

                        <!-- Medical Certificate (shown only for sick leave) -->
                        <div id="medical_cert_div" class="mb-4" style="display:none;">
                            <label class="form-label fw-bold text-danger"><i class="fa-solid fa-file-medical me-2"></i>Certificat Médical (Obligatoire)</label>
                            <div class="upload-zone" onclick="document.getElementById('medical_cert').click()">
                                <i class="fa-solid fa-cloud-arrow-up fa-2x text-danger mb-2"></i>
                                <p class="mb-1 fw-semibold text-danger">Cliquez pour uploader</p>
                                <p class="text-muted small mb-2">PDF, JPG, PNG — Max 5MB</p>
                                <span id="file_name" class="badge bg-danger-subtle text-danger" style="display:none;"></span>
                                <input type="file" id="medical_cert" name="medical_cert" accept=".pdf,.jpg,.jpeg,.png" onchange="showFileName(this)">
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Motif <span class="text-muted fw-normal">(Optionnel)</span></label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Précisez la raison de votre demande si nécessaire..."></textarea>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" name="submit_leave" class="btn-submit-leave">
                                <i class="fa-solid fa-paper-plane me-2"></i>Envoyer la demande aux RH
                            </button>
                            <span class="text-muted small"><i class="fa-solid fa-lock me-1"></i>Votre demande sera traitée dans les plus brefs délais</span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leave History -->
            <div class="history-card">
                <div class="d-flex align-items-center justify-content-between p-4 border-bottom" style="background:#f8fafc;">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:36px;height:36px;background:#1e293b;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fa-solid fa-clock-rotate-left text-white small"></i>
                        </div>
                        <div>
                            <div class="section-title">Historique de mes demandes</div>
                            <div class="text-muted small"><?php echo $total; ?> demande(s) au total</div>
                        </div>
                    </div>
                </div>

                <?php if(count($all_leaves) > 0): ?>
                <?php foreach($all_leaves as $lv): ?>
                <?php
                    $ltype = $lv['leave_type'];
                    $color = $leave_colors[$ltype] ?? '#6b7280';
                    $icon  = $leave_icons[$ltype]  ?? 'fa-calendar';
                    $label = $leave_labels[$ltype]  ?? $ltype;
                    $start_d = date('d/m/Y', strtotime($lv['start_date']));
                    $end_d   = date('d/m/Y', strtotime($lv['end_date']));
                    $days = max(1, (strtotime($lv['end_date']) - strtotime($lv['start_date'])) / 86400 + 1);
                ?>
                <div class="leave-row">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <!-- Left: Type + Dates -->
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:46px;height:46px;background:<?php echo $color; ?>1a;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-1"><?php echo $label; ?></div>
                                <div class="date-range">
                                    <i class="fa-regular fa-calendar-days text-muted"></i>
                                    <?php echo $start_d; ?>
                                    <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span>
                                    <?php echo $end_d; ?>
                                    <span class="badge rounded-pill" style="background:<?php echo $color; ?>1a;color:<?php echo $color; ?>;font-size:0.72rem;"><?php echo $days; ?> jour(s)</span>
                                </div>
                                <?php if($lv['reason']): ?>
                                <div class="text-muted small mt-1"><i class="fa-solid fa-quote-left me-1"></i><?php echo htmlspecialchars(substr($lv['reason'], 0, 60)).(strlen($lv['reason'])>60?'...':''); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right: Status + Certificate -->
                        <div class="d-flex flex-column align-items-end gap-2">
                            <?php if($lv['status']=='pending'): ?>
                                <span class="status-badge status-pending"><i class="fa-solid fa-clock"></i> En attente RH</span>
                            <?php elseif($lv['status']=='approved'): ?>
                                <span class="status-badge status-approved"><i class="fa-solid fa-check-circle"></i> Approuvé</span>
                            <?php else: ?>
                                <span class="status-badge status-rejected"><i class="fa-solid fa-times-circle"></i> Rejeté</span>
                            <?php endif; ?>
                            <?php if($lv['medical_certificate']): ?>
                                <a href="../uploads/medical/<?php echo $lv['medical_certificate']; ?>" target="_blank" class="text-decoration-none" style="font-size:0.78rem;color:#ef4444;font-weight:600;">
                                    <i class="fa-solid fa-file-medical me-1"></i>Voir Certificat
                                </a>
                            <?php endif; ?>
                            <span class="text-muted" style="font-size:0.75rem;"><?php echo date('d/m/Y à H:i', strtotime($lv['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark d-block"></i>
                    <p class="fw-semibold text-dark mb-1">Aucune demande de congé</p>
                    <p class="small">Vous n'avez encore soumis aucune demande. Utilisez le formulaire ci-dessus.</p>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script>
// Select leave type visually
function selectType(val) {
    document.querySelectorAll('.leave-type-card').forEach(c => c.classList.remove('selected'));
    const card = document.getElementById('card_' + val);
    if(card) card.classList.add('selected');
    card.querySelector('input[type=radio]').checked = true;
    // Show medical cert if sick
    const medDiv = document.getElementById('medical_cert_div');
    const medInput = document.getElementById('medical_cert');
    if(val === 'sick') {
        medDiv.style.display = 'block';
        medInput.required = true;
    } else {
        medDiv.style.display = 'none';
        medInput.required = false;
    }
}
// Init: mark 'annual' as selected
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('card_annual')?.classList.add('selected');
});

// Calculate days between dates
function calcDays() {
    const s = document.getElementById('start_date').value;
    const e = document.getElementById('end_date').value;
    const counter = document.getElementById('days_counter');
    const text = document.getElementById('days_text');
    if(s && e) {
        const diff = (new Date(e) - new Date(s)) / 86400000 + 1;
        if(diff > 0) {
            text.textContent = diff + ' jour(s) de congé';
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }
}

// Show file name when selected
function showFileName(input) {
    const span = document.getElementById('file_name');
    if(input.files && input.files[0]) {
        span.textContent = input.files[0].name;
        span.style.display = 'inline-block';
    }
}
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>
