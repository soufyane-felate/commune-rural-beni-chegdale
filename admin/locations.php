<?php
// admin/locations.php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

$base_path = '../';
require_once $base_path . 'config/db.php';
require_once $base_path . 'includes/lang.php';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Optional: Get image to delete it from server
    $stmt = $conn->prepare("SELECT image FROM locations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if(!empty($row['image']) && file_exists("../uploads/locations/".$row['image'])) {
            unlink("../uploads/locations/".$row['image']);
        }
    }
    
    $del_stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
    $del_stmt->bind_param("i", $id);
    if($del_stmt->execute()) {
        $success = "Location deleted successfully.";
    }
}

// Fetch all locations
$query = "SELECT * FROM locations ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Manage Map Locations</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_location.php" class="btn btn-sm btn-success shadow-sm">
                        <i class="fa-solid fa-plus me-1"></i> Add New Location
                    </a>
                </div>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Name (FR)</th>
                                    <th>Name (AR)</th>
                                    <th>Type</th>
                                    <th>Coordinates</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if($row['image']): ?>
                                                    <img src="../uploads/locations/<?php echo $row['image']; ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px;">
                                                        <i class="fa-solid fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($row['name_fr']); ?></td>
                                            <td dir="rtl"><?php echo htmlspecialchars($row['name_ar']); ?></td>
                                            <td><span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($row['type']); ?></span></td>
                                            <td><small class="text-muted"><?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?></small></td>
                                            <td>
                                                <a href="edit_location.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                                <a href="locations.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this location?');"><i class="fa-solid fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No locations added yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
