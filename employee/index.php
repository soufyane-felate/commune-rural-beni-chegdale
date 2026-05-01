<?php
// employee/index.php
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
                    My Tasks
                </a>
                <a href="#" class="list-group-item list-group-item-action py-3">Citizen Requests</a>
                <a href="#" class="list-group-item list-group-item-action py-3">Internal Memos</a>
                <a href="#" class="list-group-item list-group-item-action py-3">My Profile</a>
                <a href="#" class="list-group-item list-group-item-action text-danger py-3">Logout</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Employee Workspace</h3>
                    <p class="text-muted">Manage daily tasks, process citizen requests, and update records.</p>
                    
                    <h5 class="mt-5 mb-3 fw-bold">Recent Tasks</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Task Type</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#1042</td>
                                    <td>Birth Certificate Processing</td>
                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                    <td>Today</td>
                                    <td><button class="btn btn-sm btn-outline-primary">Review</button></td>
                                </tr>
                                <tr>
                                    <td>#1040</td>
                                    <td>Pothole Report Verification</td>
                                    <td><span class="badge bg-info text-dark">In Progress</span></td>
                                    <td>Tomorrow</td>
                                    <td><button class="btn btn-sm btn-outline-primary">Update</button></td>
                                </tr>
                                <tr>
                                    <td>#1035</td>
                                    <td>Marriage Registration</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td>Yesterday</td>
                                    <td><button class="btn btn-sm btn-outline-secondary">View</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
