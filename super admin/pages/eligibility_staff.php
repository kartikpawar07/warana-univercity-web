<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Eligibility Staff';
$active_page = 'eligibility_staff';
$base_path = '../';
$flash = getFlash();
$editingId = (int) ($_GET['edit'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = postString('action');
    try {
        if ($action === 'add_staff') {
            $name = postString('name');
            $email = postString('email');
            $phone = postString('phone');
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || $email === '' || $password === '') {
                throw new RuntimeException('Name, email, and password are required.');
            }
            if (dbValue($conn, 'SELECT COUNT(*) FROM users WHERE email = ?', 's', [$email])) {
                throw new RuntimeException('Staff email already exists.');
            }
            dbExecute($conn, 'INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)', 'ssssss', [$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'eligibility_staff', 'active']);
            logActivity($conn, (int) $_SESSION['user_id'], 'eligibility_staff_created', "Created eligibility staff {$email}.");
            setFlash('success', 'Eligibility staff created successfully.');
            redirectTo('eligibility_staff.php');
        }
        if ($action === 'update_staff') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $name = postString('name');
            $email = postString('email');
            $phone = postString('phone');
            if ($userId <= 0 || $name === '' || $email === '') {
                throw new RuntimeException('Invalid staff record.');
            }
            if (dbValue($conn, 'SELECT COUNT(*) FROM users WHERE email = ? AND id != ?', 'si', [$email, $userId])) {
                throw new RuntimeException('Staff email already exists.');
            }
            dbExecute($conn, 'UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = ?', 'sssis', [$name, $email, $phone, $userId, 'eligibility_staff']);
            logActivity($conn, (int) $_SESSION['user_id'], 'eligibility_staff_updated', "Updated eligibility staff {$email}.");
            setFlash('success', 'Eligibility staff updated successfully.');
            redirectTo('eligibility_staff.php?edit=' . $userId);
        }
        if ($action === 'toggle_status') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $status = postString('status') === 'active' ? 'active' : 'inactive';
            dbExecute($conn, 'UPDATE users SET status = ? WHERE id = ? AND role = ?', 'sis', [$status, $userId, 'eligibility_staff']);
            logActivity($conn, (int) $_SESSION['user_id'], 'eligibility_staff_status_changed', "Marked eligibility staff {$userId} as {$status}.");
            setFlash('success', 'Staff status updated successfully.');
            redirectTo('eligibility_staff.php');
        }
        if ($action === 'reset_password') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $newPassword = (string) ($_POST['new_password'] ?? '');
            if ($userId <= 0 || $newPassword === '') {
                throw new RuntimeException('Please provide a new password.');
            }
            dbExecute($conn, 'UPDATE users SET password = ? WHERE id = ? AND role = ?', 'sis', [password_hash($newPassword, PASSWORD_DEFAULT), $userId, 'eligibility_staff']);
            logActivity($conn, (int) $_SESSION['user_id'], 'eligibility_staff_password_reset', "Reset eligibility staff password for {$userId}.");
            setFlash('success', 'Staff password reset successfully.');
            redirectTo('eligibility_staff.php');
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$editStaff = $editingId > 0 ? dbOne($conn, "SELECT id, name, email, phone, status FROM users WHERE id = ? AND role = 'eligibility_staff' LIMIT 1", 'i', [$editingId]) : null;
$staffList = dbAll($conn, "SELECT id, name, email, phone, status, created_at FROM users WHERE role = 'eligibility_staff' ORDER BY created_at DESC");
require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>.layout-grid{display:grid;grid-template-columns:1fr 1.3fr;gap:20px}.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px}.flash-success{background:#d1e7dd;color:#0f5132}.flash-error{background:#f8d7da;color:#721c24}.inline-actions{display:flex;gap:8px;flex-wrap:wrap}@media (max-width:980px){.layout-grid{grid-template-columns:1fr}}</style>
<div class="page-content">
    <div class="top-navbar"><div class="page-title"><h1>Eligibility Staff Management</h1><p>Add, edit, activate, deactivate, and reset passwords for eligibility staff users.</p></div></div>
    <?php if ($flash): ?><div class="flash-box <?php echo $flash['type'] === 'error' ? 'flash-error' : 'flash-success'; ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <?php foreach ($errors as $error): ?><div class="flash-box flash-error"><?php echo e($error); ?></div><?php endforeach; ?>
    <div class="layout-grid">
        <div class="card-box">
            <h3 style="margin-top:0;color:#3a2359;"><?php echo $editStaff ? 'Edit Staff' : 'Add Staff'; ?></h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editStaff ? 'update_staff' : 'add_staff'; ?>">
                <?php if ($editStaff): ?><input type="hidden" name="user_id" value="<?php echo (int) $editStaff['id']; ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Name</label><input class="form-control" name="name" required value="<?php echo e($editStaff['name'] ?? postString('name')); ?>"></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required value="<?php echo e($editStaff['email'] ?? postString('email')); ?>"></div>
                    <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo e($editStaff['phone'] ?? postString('phone')); ?>"></div>
                    <?php if (!$editStaff): ?><div class="form-group"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div><?php endif; ?>
                    <div class="form-group"><label class="form-label">Role</label><input class="form-control" value="eligibility_staff" readonly></div>
                </div>
                <div class="inline-actions"><button class="btn btn-next" type="submit"><?php echo $editStaff ? 'Update Staff' : 'Create Staff'; ?></button><?php if ($editStaff): ?><a href="eligibility_staff.php" class="btn btn-prev">Cancel</a><?php endif; ?></div>
            </form>
            <h4 style="color:#3a2359;margin-top:22px;">Reset Password</h4>
            <form method="POST" class="inline-actions">
                <input type="hidden" name="action" value="reset_password">
                <select class="form-select" name="user_id" required>
                    <option value="">Select Staff</option>
                    <?php foreach ($staffList as $staff): ?><option value="<?php echo (int) $staff['id']; ?>"><?php echo e($staff['name'] . ' - ' . $staff['email']); ?></option><?php endforeach; ?>
                </select>
                <input class="form-control" type="password" name="new_password" placeholder="New password" required>
                <button class="btn btn-next" type="submit">Reset</button>
            </form>
        </div>
        <div class="card-box">
            <h3 style="margin-top:0;color:#3a2359;">Staff Directory</h3>
            <div class="table-responsive" style="margin-top:0;">
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Contact</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($staffList as $staff): ?>
                            <tr>
                                <td><?php echo e($staff['name']); ?></td>
                                <td><?php echo e($staff['email']); ?><br><small><?php echo e($staff['phone']); ?></small></td>
                                <td><span class="status-badge <?php echo $staff['status'] === 'active' ? 'status-approved' : 'status-rejected'; ?>"><?php echo e(ucfirst($staff['status'])); ?></span></td>
                                <td><?php echo e(date('d M Y', strtotime($staff['created_at']))); ?></td>
                                <td><div class="inline-actions"><a href="eligibility_staff.php?edit=<?php echo (int) $staff['id']; ?>" class="btn btn-prev">Edit</a><form method="POST"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="user_id" value="<?php echo (int) $staff['id']; ?>"><input type="hidden" name="status" value="<?php echo $staff['status'] === 'active' ? 'inactive' : 'active'; ?>"><button class="btn <?php echo $staff['status'] === 'active' ? 'btn-prev' : 'btn-next'; ?>" type="submit"><?php echo $staff['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button></form></div></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($staffList === []): ?><tr><td colspan="5" class="text-center">No eligibility staff records found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
