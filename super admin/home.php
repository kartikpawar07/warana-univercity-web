<?php
require_once __DIR__ . '/includes/app.php';

if (!isLoggedIn()) {
    redirectTo('login.php');
}

redirectTo('pages/dashboard.php');
