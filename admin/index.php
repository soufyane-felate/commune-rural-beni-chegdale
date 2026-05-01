<?php
// admin/index.php
$base_path = '../';
require_once $base_path . 'config/db.php';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container mt-5 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="list-group shadow-sm rounded-4">
                <a href="#" class="list-group-item list-group-item-action active py-3" aria-current="true">
                    Dashboard Overview
                </a>
                <a href="#" class="list-group-item list-group-item-action py-3">User Management</a>
                <a href="#" class="list-group-item list-group-item-action py-3">System Settings</a>
                <a href="#" class="list-group-item list-group-item-action py-3">Audit Logs</a>
                <a href="#" class="list-group-item list-group-item-action text-danger py-3">Logout</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Admin Dashboard</h3>
                    <p class="text-muted">Welcome to the administration panel. System configuration and global management can be performed here.</p>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-sm-6 col-lg-4">
                            <div class="p-4 bg-light rounded-4 text-center">
                                <h2 class="fw-bold text-primary">150</h2>
                                <span class="text-muted">Total Employees</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="p-4 bg-light rounded-4 text-center">
                                <h2 class="fw-bold text-success">1,245</h2>
                                <span class="text-muted">Registered Citizens</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="p-4 bg-light rounded-4 text-center">
                                <h2 class="fw-bold text-warning">8</h2>
                                <span class="text-muted">Pending System Alerts</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
