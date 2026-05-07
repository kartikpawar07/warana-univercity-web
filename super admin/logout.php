<?php
require_once __DIR__ . '/includes/app.php';

if (isLoggedIn()) {
    logActivity($conn, (int) $_SESSION['user_id'], 'logout', 'Super Admin logged out.');
}

$_SESSION = [];
session_destroy();
session_start();
setFlash('success', 'You have been logged out successfully.');
header('Location: login.php');
exit;
?>
