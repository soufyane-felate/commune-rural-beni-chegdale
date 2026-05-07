<?php
// config/db.php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'municipal_system';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure UTF-8 Encoding
$conn->set_charset("utf8mb4");
?>
