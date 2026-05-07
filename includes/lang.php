<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ar'; // Default to Arabic
}

$current_lang = $_SESSION['lang'];
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (file_exists($lang_file)) {
    $lang = include $lang_file;
} else {
    $lang = include __DIR__ . '/../lang/fr.php'; // fallback
}

function __($key) {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $key;
}
?>
