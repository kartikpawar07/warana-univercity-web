<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

initializeApplication($conn);

function initializeApplication(mysqli $conn): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    bootstrapSchema($conn);
    $initialized = true;
}

function bootstrapSchema(mysqli $conn): void
{
    static $schemaLoaded = false;

    if ($schemaLoaded) {
        return;
    }

    $schemaPath = dirname(__DIR__) . '/database.sql';
    if (!is_file($schemaPath)) {
        throw new RuntimeException('database.sql not found.');
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        throw new RuntimeException('Unable to read database.sql.');
    }

    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    if ($statements === false) {
        throw new RuntimeException('Failed to parse schema.');
    }

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }

        if (!$conn->query($statement)) {
            throw new RuntimeException('Schema error: ' . $conn->error);
        }
    }

    $schemaLoaded = true;
}

function bindParams(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '' || $params === []) {
        return;
    }

    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }

    array_unshift($refs, $types);
    $stmt->bind_param(...$refs);
}

function dbStatement(mysqli $conn, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    bindParams($stmt, $types, $params);

    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }

    return $stmt;
}

function dbAll(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = dbStatement($conn, $sql, $types, $params);
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

function dbOne(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $rows = dbAll($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function dbValue(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $row = dbOne($conn, $sql, $types, $params);
    if ($row === null) {
        return null;
    }

    return reset($row);
}

function dbExecute(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = dbStatement($conn, $sql, $types, $params);
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function postString(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function getString(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function logActivity(mysqli $conn, ?int $userId, string $action, string $description): void
{
    dbExecute(
        $conn,
        'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
        'iss',
        [$userId, $action, $description]
    );
}

function getSettings(mysqli $conn): array
{
    $rows = dbAll($conn, 'SELECT setting_key, setting_value FROM system_settings');
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function saveSetting(mysqli $conn, string $key, string $value): void
{
    dbExecute(
        $conn,
        'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        'ss',
        [$key, $value]
    );
}

function getStatusMap(): array
{
    return [
        0 => 'Draft',
        1 => 'Submitted',
        2 => 'College Verified',
        3 => 'University Pending',
        4 => 'Query',
        5 => 'Approved',
        6 => 'Rejected',
        7 => 'PRN Generated',
    ];
}

function getStatusBadgeClass(int $status): string
{
    return match ($status) {
        5, 7 => 'status-approved',
        6 => 'status-rejected',
        default => 'status-pending',
    };
}

function getCurrentAcademicLabel(array $settings): string
{
    $start = $settings['academic_start_date'] ?? '';
    $end = $settings['academic_end_date'] ?? '';

    if ($start !== '' && $end !== '') {
        return date('Y', strtotime($start)) . ' - ' . date('Y', strtotime($end));
    }

    return 'Not configured';
}

function uploadSettingLogo(array $file, string $existingPath = ''): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return $existingPath;
    }

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Logo must be a PNG, JPG, JPEG, or WEBP file.');
    }

    $directory = dirname(__DIR__) . '/uploads/settings';
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $filename = 'logo_' . time() . '.' . $extension;
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to upload logo.');
    }

    return 'uploads/settings/' . $filename;
}

function generateUniquePrn(mysqli $conn, int $studentId, string $collegeCode, string $academicYear): string
{
    $yearPart = preg_replace('/\D/', '', $academicYear);
    $yearPart = substr($yearPart ?: date('Y'), 0, 4);
    $collegePart = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $collegeCode), 0, 4));
    $collegePart = str_pad($collegePart, 4, 'X');

    do {
        $prn = $yearPart . $collegePart . str_pad((string) $studentId, 6, '0', STR_PAD_LEFT) . random_int(10, 99);
        $exists = dbValue($conn, 'SELECT COUNT(*) FROM prn_generation WHERE prn = ?', 's', [$prn]);
    } while ((int) $exists > 0);

    return $prn;
}

function getSuperAdminProfile(mysqli $conn): array
{
    $user = getCurrentUser($conn) ?? [];
    $joinedAt = $user['created_at'] ?? null;
    $lastLogin = dbValue(
        $conn,
        "SELECT created_at FROM system_logs WHERE user_id = ? AND action = 'login' ORDER BY id DESC LIMIT 1",
        'i',
        [(int) ($_SESSION['user_id'] ?? 0)]
    );

    return [
        'name' => $user['name'] ?? 'Super Admin',
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? 'super_admin',
        'joined_at' => $joinedAt ? date('d M Y', strtotime($joinedAt)) : 'N/A',
        'last_login' => $lastLogin ? date('d M Y h:i A', strtotime((string) $lastLogin)) : 'First login',
    ];
}

function getFilterOptions(mysqli $conn): array
{
    return [
        'colleges' => dbAll($conn, "SELECT id, name FROM colleges WHERE status = 'active' ORDER BY name"),
        'departments' => dbAll($conn, "SELECT id, name FROM departments WHERE status = 'active' ORDER BY name"),
        'programs' => dbAll($conn, "SELECT id, name FROM programs WHERE status = 'active' ORDER BY name"),
    ];
}
?>
