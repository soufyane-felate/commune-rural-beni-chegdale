<?php
require_once 'config/db.php';

$email = 'admin@gmail.com';
$password = 'admin123';
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Check if user exists, if not insert, if yes update
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $admin = $result->fetch_assoc();
    $update = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
    $update->bind_param("ssi", $email, $hashed, $admin['id']);
    $update->execute();
    echo "Admin updated successfully. Hash: " . $hashed;
} else {
    // Insert new admin
    $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Super Admin', ?, ?, 'admin')");
    $insert->bind_param("ss", $email, $hashed);
    $insert->execute();
    echo "Admin created successfully. Hash: " . $hashed;
}
?>
