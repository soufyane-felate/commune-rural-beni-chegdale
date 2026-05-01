<?php
session_start();
require_once '../config/db.php';

// Log logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'User logged out')");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect to home page
header("Location: ../index.php");
exit;
?>
