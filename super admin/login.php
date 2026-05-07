<?php
require_once __DIR__ . '/includes/app.php';

if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'super_admin') {
    redirectTo('pages/dashboard.php');
}

$settings = getSettings($conn);
$flash = getFlash();
$error = '';

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'Only super administrators can access this module.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = postString('username');
    $password = (string) ($_POST['password'] ?? '');

    $user = dbOne(
        $conn,
        "SELECT id, name, email, password, role FROM users WHERE email = ? AND status = 'active' LIMIT 1",
        's',
        [$email]
    );

    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] !== 'super_admin') {
            $error = 'Access denied. This panel is restricted to Super Admin users.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            logActivity($conn, (int) $user['id'], 'login', 'Super Admin logged in successfully.');
            redirectTo('pages/dashboard.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($settings['university_name'] ?? 'Warana University'); ?> - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <header class="main-header">
        <div class="header-row">
            <div class="header-left-content">
                <img src="<?php echo e($settings['logo'] ?? 'images/warana.png'); ?>" alt="University Logo" class="logo" onerror="this.src='images/warana.png'">
                <div class="header-center">
                    <div class="trust-name">Super Admin Authentication</div>
                    <h1 class="university-name"><?php echo e($settings['university_name'] ?? 'Warana University, Warananagar'); ?></h1>
                    <div class="university-status"><?php echo e(getCurrentAcademicLabel($settings)); ?></div>
                </div>
                <img src="images/founder.png" alt="Founder" class="founder-photo" onerror="this.style.display='none';">
            </div>
        </div>
    </header>

    <main class="login-main">
        <div class="login-container">
            <div class="login-card">
                <div class="login-card-header">
                    <h2>Welcome Back</h2>
                    <p>Login to continue to the Super Admin module</p>
                </div>
                <?php if ($flash): ?>
                    <div style="background:#d1e7dd;color:#0f5132;padding:10px;border-radius:5px;margin-bottom:15px;font-size:0.85rem;">
                        <?php echo e($flash['message']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;font-size:0.85rem;">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>
                <form class="login-form" method="POST" action="login.php">
                    <div class="input-group">
                        <label for="username">Email</label>
                        <input type="email" id="username" name="username" placeholder="Enter your email" required value="<?php echo e(postString('username')); ?>">
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="login-btn">Log In</button>
                    <div class="login-footer">
                        <p>Access is managed by the university system administrator.</p>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
