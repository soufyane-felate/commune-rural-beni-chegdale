<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
