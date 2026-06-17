<?php
// rh/export_employee.php
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

// Fetch Leaves
$leaves_stmt = $conn->prepare("SELECT leave_type, start_date, end_date, status, reason FROM leave_requests WHERE user_id = ? ORDER BY start_date DESC");
$leaves_stmt->bind_param("i", $id);
$leaves_stmt->execute();
$leaves = $leaves_stmt->get_result();

// Fetch Attendance (Last 30 days)
$att_stmt = $conn->prepare("SELECT date, check_in, check_out, status FROM employee_attendance WHERE user_id = ? ORDER BY date DESC LIMIT 30");
$att_stmt->bind_param("i", $id);
$att_stmt->execute();
$attendance = $att_stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Dossier_Employe_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $emp['name']) . '_' . date('Ymd') . '.csv');
$output = fopen('php://output', 'w');

// Add BOM to fix UTF-8 in Excel
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Section: Informations Personnelles
fputcsv($output, ['--- INFORMATIONS PERSONNELLES ---']);
fputcsv($output, ['Nom complet', $emp['name']]);
fputcsv($output, ['Email', $emp['email']]);
fputcsv($output, ['CIN', $emp['cin']]);
fputcsv($output, ['Téléphone', $emp['phone']]);
fputcsv($output, ['Adresse', $emp['address']]);
fputcsv($output, ['']);

// Section: Informations Professionnelles
fputcsv($output, ['--- INFORMATIONS PROFESSIONNELLES ---']);
fputcsv($output, ['Matricule', $emp['matricule']]);
fputcsv($output, ['Département', $emp['department']]);
fputcsv($output, ['Poste', $emp['position']]);
fputcsv($output, ['Date d\'embauche', $emp['hiring_date']]);
fputcsv($output, ['Salaire de base (MAD)', $emp['base_salary']]);
fputcsv($output, ['Statut', $emp['status']]);
fputcsv($output, ['']);

// Section: Historique des Congés
fputcsv($output, ['--- HISTORIQUE DES CONGES ---']);
fputcsv($output, ['Type', 'Date Début', 'Date Fin', 'Statut', 'Motif']);
$leave_labels = ['annual'=>'Annuel','sick'=>'Maladie','personal'=>'Personnel','maternity'=>'Maternité','other'=>'Autre'];
while($l = $leaves->fetch_assoc()) {
    $type = $leave_labels[$l['leave_type']] ?? $l['leave_type'];
    fputcsv($output, [$type, $l['start_date'], $l['end_date'], $l['status'], $l['reason']]);
}
fputcsv($output, ['']);

// Section: Pointage (30 derniers jours)
fputcsv($output, ['--- POINTAGE (30 DERNIERS JOURS) ---']);
fputcsv($output, ['Date', 'Heure d\'entrée', 'Heure de sortie', 'Statut']);
while($a = $attendance->fetch_assoc()) {
    fputcsv($output, [$a['date'], $a['check_in'], $a['check_out'], $a['status']]);
}

fclose($output);
exit();
?>
