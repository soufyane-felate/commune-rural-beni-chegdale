<?php
session_start();
$base_path = '../';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin','employee'])) { header("Location: ../pages/login.php"); exit(); }
require_once $base_path . 'config/db.php';
$user_id = $_SESSION['user_id'];

// Send message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_msg'])) {
    $receiver = intval($_POST['receiver_id']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    if (!empty($receiver) && !empty($subject) && !empty($body)) {
        $s = $conn->prepare("INSERT INTO internal_messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)");
        $s->bind_param("iiss", $user_id, $receiver, $subject, $body);
        if ($s->execute()) { $success_msg = "Message envoyé."; }
        else { $error_msg = "Erreur d'envoi."; }
    }
}

// Mark as read
if (isset($_GET['read'])) {
    $mid = intval($_GET['read']);
    $conn->query("UPDATE internal_messages SET is_read=1 WHERE id=$mid AND receiver_id=$user_id");
}

$view = isset($_GET['view']) ? $_GET['view'] : 'inbox';

if ($view == 'inbox') {
    $msgs = $conn->prepare("SELECT im.*, u.name as sender_name FROM internal_messages im JOIN users u ON im.sender_id=u.id WHERE im.receiver_id=? ORDER BY im.created_at DESC");
    $msgs->bind_param("i", $user_id);
} else {
    $msgs = $conn->prepare("SELECT im.*, u.name as receiver_name FROM internal_messages im JOIN users u ON im.receiver_id=u.id WHERE im.sender_id=? ORDER BY im.created_at DESC");
    $msgs->bind_param("i", $user_id);
}
$msgs->execute();
$messages = $msgs->get_result();

// View single message
$single_msg = null;
if (isset($_GET['read'])) {
    $mid = intval($_GET['read']);
    $sm = $conn->prepare("SELECT im.*, s.name as sender_name, r.name as receiver_name FROM internal_messages im JOIN users u ON im.sender_id=u.id JOIN users s ON im.sender_id=s.id JOIN users r ON im.receiver_id=r.id WHERE im.id=? AND (im.sender_id=? OR im.receiver_id=?)");
    $sm->bind_param("iii", $mid, $user_id, $user_id);
    $sm->execute();
    $single_msg = $sm->get_result()->fetch_assoc();
}

$employees_list = $conn->query("SELECT id, name FROM users WHERE (role='employee' OR role='admin') AND id != $user_id ORDER BY name");
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
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="employees.php"><i class="fa-solid fa-users me-2"></i>Employés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="attendance.php"><i class="fa-solid fa-clipboard-user me-2"></i>Présence</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="leave_requests.php"><i class="fa-solid fa-calendar-minus me-2"></i>Congés</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="salary.php"><i class="fa-solid fa-money-bill-wave me-2"></i>Salaires</a></li>
                <li class="nav-item mb-2"><a class="nav-link active" href="messages.php"><i class="fa-solid fa-envelope me-2"></i>Messages</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-dark" href="reports.php"><i class="fa-solid fa-file-lines me-2"></i>Rapports</a></li>
                <hr><li class="nav-item"><a class="nav-link text-dark" href="../admin/dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Admin Panel</a></li>
            </ul>
        </div>
    </div>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fa-solid fa-envelope text-primary me-2"></i>Messagerie Interne</h1>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal"><i class="fa-solid fa-pen me-1"></i>Nouveau message</button>
        </div>
        <?php if(isset($success_msg)): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <?php if($single_msg): ?>
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between">
                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($single_msg['subject']); ?></h5>
                <a href="messages.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div><strong>De :</strong> <?php echo htmlspecialchars($single_msg['sender_name']); ?> <strong class="ms-3">À :</strong> <?php echo htmlspecialchars($single_msg['receiver_name']); ?></div>
                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($single_msg['created_at'])); ?></small>
                </div>
                <div class="p-3 bg-light rounded"><?php echo nl2br(htmlspecialchars($single_msg['body'])); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link <?php echo ($view=='inbox')?'active':''; ?>" href="messages.php?view=inbox"><i class="fa-solid fa-inbox me-1"></i>Boîte de réception</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($view=='sent')?'active':''; ?>" href="messages.php?view=sent"><i class="fa-solid fa-paper-plane me-1"></i>Envoyés</a></li>
        </ul>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if($messages->num_rows > 0): while($m = $messages->fetch_assoc()): ?>
                    <a href="messages.php?read=<?php echo $m['id']; ?>" class="list-group-item list-group-item-action p-3 <?php echo ($view=='inbox' && !$m['is_read']) ? 'bg-light fw-bold' : ''; ?>">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?php if($view=='inbox' && !$m['is_read']): ?><i class="fa-solid fa-circle text-primary me-2" style="font-size:8px;"></i><?php endif; ?>
                                <strong><?php echo ($view=='inbox') ? htmlspecialchars($m['sender_name']) : htmlspecialchars($m['receiver_name']); ?></strong>
                                <span class="ms-2 text-muted">— <?php echo htmlspecialchars($m['subject']); ?></span>
                            </div>
                            <small class="text-muted"><?php echo date('d/m H:i', strtotime($m['created_at'])); ?></small>
                        </div>
                        <small class="text-muted"><?php echo mb_substr(strip_tags($m['body']), 0, 80) . '...'; ?></small>
                    </a>
                    <?php endwhile; else: ?>
                    <div class="p-4 text-center text-muted">Aucun message.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</div>

<!-- Compose Modal -->
<div class="modal fade" id="composeModal" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST">
    <div class="modal-header bg-light"><h5 class="modal-title fw-bold">Nouveau Message</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-bold">Destinataire *</label><select class="form-select" name="receiver_id" required><option value="" disabled selected>Choisir...</option><?php while($e = $employees_list->fetch_assoc()): ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['name']); ?></option><?php endwhile; ?></select></div>
        <div class="mb-3"><label class="form-label fw-bold">Objet *</label><input type="text" class="form-control" name="subject" required></div>
        <div class="mb-3"><label class="form-label fw-bold">Message *</label><textarea class="form-control" name="body" rows="5" required></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="submit" name="send_msg" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Envoyer</button></div>
</form>
</div></div>
</div>
<?php require_once $base_path . 'includes/footer.php'; ?>
