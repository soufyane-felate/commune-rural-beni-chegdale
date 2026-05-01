<?php
require_once 'config/db.php';

$query = "ALTER TABLE complaints ADD COLUMN phone VARCHAR(20) NULL AFTER department_id;";

if ($conn->query($query) === TRUE) {
    echo "Column phone added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
