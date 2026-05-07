<?php
require_once __DIR__ . '/includes/app.php';

if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'super_admin') {
    redirectTo('pages/dashboard.php');
}

redirectTo('login.php');
