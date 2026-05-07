<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(string $base_path = '../'): void
{
    if (!isLoggedIn()) {
        header("Location: {$base_path}login.php");
        exit;
    }
}

function requireSuperAdmin(string $base_path = '../'): void
{
    requireLogin($base_path);

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
        header("Location: {$base_path}login.php?error=unauthorized");
        exit;
    }
}

function getCurrentUser(mysqli $conn): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $id = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}
?>
