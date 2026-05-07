<?php
// api/get_locations.php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

$query = "SELECT * FROM locations ORDER BY created_at DESC";
$result = $conn->query($query);

$locations = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

echo json_encode(['status' => 'success', 'data' => $locations]);
