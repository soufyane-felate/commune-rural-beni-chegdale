<?php
// admin/edit_location.php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: locations.php');
    exit();
}

$id = (int)$_GET['id'];
$base_path = '../';
require_once $base_path . 'config/db.php';
require_once $base_path . 'includes/lang.php';

$error = '';
$success = '';

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0) {
    header('Location: locations.php');
    exit();
}
$loc = $res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name_fr = trim($_POST['name_fr']);
    $name_ar = trim($_POST['name_ar']);
    $type = trim($_POST['type']);
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $desc_fr = trim($_POST['description_fr']);
    $desc_ar = trim($_POST['description_ar']);
    $hours_fr = trim($_POST['working_hours_fr']);
    $hours_ar = trim($_POST['working_hours_ar']);
    $contact = trim($_POST['contact_info']);
    $link = trim($_POST['service_link']);
    
    $image_name = $loc['image']; // Keep existing

    if (empty($name_fr) || empty($name_ar) || empty($lat) || empty($lng)) {
        $error = "Name and coordinates are required.";
    } else {
        // Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $image_name = time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/locations/" . $image_name);
                
                // Delete old image
                if(!empty($loc['image']) && file_exists("../uploads/locations/".$loc['image'])) {
                    unlink("../uploads/locations/".$loc['image']);
                }
            } else {
                $error = "Invalid image format.";
            }
        }

        if(empty($error)) {
            $update_stmt = $conn->prepare("UPDATE locations SET name_fr=?, name_ar=?, type=?, latitude=?, longitude=?, description_fr=?, description_ar=?, working_hours_fr=?, working_hours_ar=?, contact_info=?, image=?, service_link=? WHERE id=?");
            $update_stmt->bind_param("ssddssssssssi", $name_fr, $name_ar, $type, $lat, $lng, $desc_fr, $desc_ar, $hours_fr, $hours_ar, $contact, $image_name, $link, $id);
            
            if ($update_stmt->execute()) {
                $success = "Location updated successfully.";
                // Refresh data
                $stmt->execute();
                $loc = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>#map-picker { height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ccc; }</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Location</h1>
                <a href="locations.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
            </div>

            <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <?php if(!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name (French) *</label>
                                <input type="text" class="form-control" name="name_fr" value="<?php echo htmlspecialchars($loc['name_fr']); ?>" required>
                            </div>
                            <div class="col-md-6" dir="rtl">
                                <label class="form-label fw-bold">الاسم (بالعربية) *</label>
                                <input type="text" class="form-control" name="name_ar" value="<?php echo htmlspecialchars($loc['name_ar']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Type of Location *</label>
                                <select class="form-select" name="type">
                                    <?php
                                    $types = ['headquarters'=>'Commune HQ', 'etat_civil'=>'Etat Civil', 'legalisation'=>'Legalisation', 'tax'=>'Tax Service', 'social'=>'Social Service', 'school'=>'School', 'health'=>'Health Center', 'mosque'=>'Mosque', 'other'=>'Other'];
                                    foreach($types as $k => $v) {
                                        $sel = ($loc['type'] == $k) ? 'selected' : '';
                                        echo "<option value='$k' $sel>$v</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contact Phone/Email</label>
                                <input type="text" class="form-control" name="contact_info" value="<?php echo htmlspecialchars($loc['contact_info']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Update Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <?php if($loc['image']): ?>
                                    <small class="text-muted d-block mt-1">Current: <?php echo $loc['image']; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12 mb-2">
                                <label class="form-label fw-bold">Update Coordinates on Map *</label>
                                <div id="map-picker"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Latitude</label>
                                <input type="text" class="form-control bg-light" id="lat" name="latitude" value="<?php echo $loc['latitude']; ?>" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Longitude</label>
                                <input type="text" class="form-control bg-light" id="lng" name="longitude" value="<?php echo $loc['longitude']; ?>" required readonly>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Description (French)</label>
                                <textarea class="form-control" name="description_fr" rows="3"><?php echo htmlspecialchars($loc['description_fr']); ?></textarea>
                            </div>
                            <div class="col-md-6" dir="rtl">
                                <label class="form-label fw-bold">الوصف (بالعربية)</label>
                                <textarea class="form-control" name="description_ar" rows="3"><?php echo htmlspecialchars($loc['description_ar']); ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Working Hours (French)</label>
                                <input type="text" class="form-control" name="working_hours_fr" value="<?php echo htmlspecialchars($loc['working_hours_fr']); ?>">
                            </div>
                            <div class="col-md-6" dir="rtl">
                                <label class="form-label fw-bold">أوقات العمل (بالعربية)</label>
                                <input type="text" class="form-control" name="working_hours_ar" value="<?php echo htmlspecialchars($loc['working_hours_ar']); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Service Redirect Link (Optional)</label>
                            <input type="text" class="form-control" name="service_link" value="<?php echo htmlspecialchars($loc['service_link']); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg px-5">Update Location</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const initLat = <?php echo $loc['latitude']; ?>;
    const initLng = <?php echo $loc['longitude']; ?>;
    
    const map = L.map('map-picker').setView([initLat, initLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    let marker = L.marker([initLat, initLng]).addTo(map);
    
    map.on('click', function(e) {
        if(marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        document.getElementById('lat').value = e.latlng.lat.toFixed(6);
        document.getElementById('lng').value = e.latlng.lng.toFixed(6);
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>
