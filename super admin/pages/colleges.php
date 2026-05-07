<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Colleges';
$active_page = 'colleges';
$base_path = '../';
$flash = getFlash();
$editingId = (int) ($_GET['edit'] ?? 0);
$errors = [];

function loadCollegeForEdit(mysqli $conn, int $collegeId): ?array
{
    return dbOne(
        $conn,
        "SELECT c.*, u.id AS admin_user_id, u.name AS admin_name, u.email AS admin_email
         FROM colleges c
         LEFT JOIN college_admin_mapping cam ON cam.college_id = c.id
         LEFT JOIN users u ON u.id = cam.user_id
         WHERE c.id = ? LIMIT 1",
        'i',
        [$collegeId]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = postString('action');

    try {
        if ($action === 'add_college') {
            $name = postString('name');
            $code = strtoupper(postString('code'));
            $email = postString('email');
            $phone = postString('phone');
            $address = postString('address');
            $city = postString('city');
            $district = postString('district');
            $state = postString('state');
            $pincode = postString('pincode');
            $adminName = postString('admin_name');
            $adminEmail = postString('admin_email');
            $adminPassword = (string) ($_POST['admin_password'] ?? '');

            if ($name === '' || $code === '' || $adminName === '' || $adminEmail === '' || $adminPassword === '') {
                throw new RuntimeException('Please fill all required college and admin fields.');
            }
            if (dbValue($conn, 'SELECT COUNT(*) FROM colleges WHERE code = ?', 's', [$code])) {
                throw new RuntimeException('College code already exists.');
            }
            if (dbValue($conn, 'SELECT COUNT(*) FROM users WHERE email = ?', 's', [$adminEmail])) {
                throw new RuntimeException('Admin email already exists.');
            }

            $conn->begin_transaction();
            dbExecute($conn, 'INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)', 'ssssss', [$adminName, $adminEmail, $phone, password_hash($adminPassword, PASSWORD_DEFAULT), 'college_admin', 'active']);
            $adminId = $conn->insert_id;
            dbExecute($conn, "INSERT INTO colleges (name, code, email, phone, address, city, district, state, pincode, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')", 'sssssssss', [$name, $code, $email, $phone, $address, $city, $district, $state, $pincode]);
            $collegeId = $conn->insert_id;
            dbExecute($conn, 'INSERT INTO college_admin_mapping (user_id, college_id) VALUES (?, ?)', 'ii', [$adminId, $collegeId]);
            logActivity($conn, (int) $_SESSION['user_id'], 'college_created', "Created college {$name} ({$code}) and mapped admin {$adminEmail}.");
            $conn->commit();
            setFlash('success', 'College and college admin created successfully.');
            redirectTo('colleges.php');
        }

        if ($action === 'update_college') {
            $collegeId = (int) ($_POST['college_id'] ?? 0);
            $adminUserId = (int) ($_POST['admin_user_id'] ?? 0);
            $name = postString('name');
            $code = strtoupper(postString('code'));
            $email = postString('email');
            $phone = postString('phone');
            $address = postString('address');
            $city = postString('city');
            $district = postString('district');
            $state = postString('state');
            $pincode = postString('pincode');
            $adminName = postString('admin_name');
            $adminEmail = postString('admin_email');

            if ($collegeId <= 0 || $adminUserId <= 0) {
                throw new RuntimeException('Invalid college selected for update.');
            }
            if ($name === '' || $code === '' || $adminName === '' || $adminEmail === '') {
                throw new RuntimeException('Required fields cannot be empty.');
            }
            if (dbValue($conn, 'SELECT COUNT(*) FROM colleges WHERE code = ? AND id != ?', 'si', [$code, $collegeId])) {
                throw new RuntimeException('College code already exists.');
            }
            if (dbValue($conn, 'SELECT COUNT(*) FROM users WHERE email = ? AND id != ?', 'si', [$adminEmail, $adminUserId])) {
                throw new RuntimeException('Admin email already exists.');
            }

            $conn->begin_transaction();
            dbExecute($conn, 'UPDATE colleges SET name = ?, code = ?, email = ?, phone = ?, address = ?, city = ?, district = ?, state = ?, pincode = ? WHERE id = ?', 'sssssssssi', [$name, $code, $email, $phone, $address, $city, $district, $state, $pincode, $collegeId]);
            dbExecute($conn, 'UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?', 'sssi', [$adminName, $adminEmail, $phone, $adminUserId]);
            logActivity($conn, (int) $_SESSION['user_id'], 'college_updated', "Updated college {$name} ({$code}).");
            $conn->commit();
            setFlash('success', 'College details updated successfully.');
            redirectTo('colleges.php?edit=' . $collegeId);
        }

        if ($action === 'toggle_status') {
            $collegeId = (int) ($_POST['college_id'] ?? 0);
            $status = postString('status') === 'active' ? 'active' : 'inactive';
            $college = loadCollegeForEdit($conn, $collegeId);
            if (!$college) {
                throw new RuntimeException('College not found.');
            }
            $conn->begin_transaction();
            dbExecute($conn, 'UPDATE colleges SET status = ? WHERE id = ?', 'si', [$status, $collegeId]);
            if (!empty($college['admin_user_id'])) {
                dbExecute($conn, 'UPDATE users SET status = ? WHERE id = ?', 'si', [$status, (int) $college['admin_user_id']]);
            }
            logActivity($conn, (int) $_SESSION['user_id'], 'college_status_changed', "Marked college {$college['name']} as {$status}.");
            $conn->commit();
            setFlash('success', 'College status updated successfully.');
            redirectTo('colleges.php');
        }

        if ($action === 'reset_password') {
            $adminUserId = (int) ($_POST['admin_user_id'] ?? 0);
            $newPassword = (string) ($_POST['new_password'] ?? '');
            if ($adminUserId <= 0 || $newPassword === '') {
                throw new RuntimeException('Please provide a new password.');
            }
            dbExecute($conn, 'UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($newPassword, PASSWORD_DEFAULT), $adminUserId]);
            logActivity($conn, (int) $_SESSION['user_id'], 'college_admin_password_reset', 'Reset password for a college admin account.');
            setFlash('success', 'College admin password reset successfully.');
            redirectTo('colleges.php');
        }
    } catch (Throwable $e) {
        if ($conn->errno) {
            $conn->rollback();
        }
        $errors[] = $e->getMessage();
    }
}

$editCollege = $editingId > 0 ? loadCollegeForEdit($conn, $editingId) : null;
$colleges = dbAll(
    $conn,
    "SELECT c.*, u.id AS admin_user_id, u.name AS admin_name, u.email AS admin_email
     FROM colleges c
     LEFT JOIN college_admin_mapping cam ON cam.college_id = c.id
     LEFT JOIN users u ON u.id = cam.user_id
     ORDER BY c.created_at DESC, c.name ASC"
);

require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>
.form-shell{display:grid;grid-template-columns:1.2fr .8fr;gap:20px}.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.section-label{margin:0 0 14px;color:#3a2359}.inline-actions{display:flex;gap:8px;flex-wrap:wrap}.mini-form{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.mini-form input{max-width:150px}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px}.flash-success{background:#d1e7dd;color:#0f5132}.flash-error{background:#f8d7da;color:#721c24}.link-muted{color:#6b5b7d;text-decoration:none}.page-grid{display:grid;gap:20px}@media (max-width:1100px){.form-shell{grid-template-columns:1fr}}
</style>
<div class="page-content">
    <div class="top-navbar">
        <div class="page-title">
            <h1>College Management</h1>
            <p>Create colleges, manage college admin accounts, and control active access.</p>
        </div>
    </div>

    <?php if ($flash): ?><div class="flash-box <?php echo $flash['type'] === 'error' ? 'flash-error' : 'flash-success'; ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <?php foreach ($errors as $error): ?><div class="flash-box flash-error"><?php echo e($error); ?></div><?php endforeach; ?>

    <div class="page-grid">
        <div class="form-shell">
            <div class="card-box">
                <h3 class="section-label"><?php echo $editCollege ? 'Edit College' : 'Add College'; ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editCollege ? 'update_college' : 'add_college'; ?>">
                    <?php if ($editCollege): ?>
                        <input type="hidden" name="college_id" value="<?php echo (int) $editCollege['id']; ?>">
                        <input type="hidden" name="admin_user_id" value="<?php echo (int) $editCollege['admin_user_id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">College Name</label><input class="form-control" name="name" required value="<?php echo e($editCollege['name'] ?? postString('name')); ?>"></div>
                        <div class="form-group"><label class="form-label">College Code</label><input class="form-control" name="code" required value="<?php echo e($editCollege['code'] ?? postString('code')); ?>"></div>
                        <div class="form-group"><label class="form-label">College Email</label><input type="email" class="form-control" name="email" value="<?php echo e($editCollege['email'] ?? postString('email')); ?>"></div>
                        <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo e($editCollege['phone'] ?? postString('phone')); ?>"></div>
                        <div class="form-group full-width"><label class="form-label">Address</label><textarea class="form-control" name="address"><?php echo e($editCollege['address'] ?? postString('address')); ?></textarea></div>
                        <div class="form-group"><label class="form-label">City</label><input class="form-control" name="city" value="<?php echo e($editCollege['city'] ?? postString('city')); ?>"></div>
                        <div class="form-group"><label class="form-label">District</label><input class="form-control" name="district" value="<?php echo e($editCollege['district'] ?? postString('district')); ?>"></div>
                        <div class="form-group"><label class="form-label">State</label><input class="form-control" name="state" value="<?php echo e($editCollege['state'] ?? postString('state')); ?>"></div>
                        <div class="form-group"><label class="form-label">Pincode</label><input class="form-control" name="pincode" value="<?php echo e($editCollege['pincode'] ?? postString('pincode')); ?>"></div>
                    </div>
                    <h4 class="section-label" style="margin-top:8px;">College Admin</h4>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Admin Name</label><input class="form-control" name="admin_name" required value="<?php echo e($editCollege['admin_name'] ?? postString('admin_name')); ?>"></div>
                        <div class="form-group"><label class="form-label">Admin Email</label><input type="email" class="form-control" name="admin_email" required value="<?php echo e($editCollege['admin_email'] ?? postString('admin_email')); ?>"></div>
                        <?php if (!$editCollege): ?>
                            <div class="form-group"><label class="form-label">Admin Password</label><input type="password" class="form-control" name="admin_password" required></div>
                        <?php endif; ?>
                    </div>
                    <div class="inline-actions">
                        <button class="btn btn-next" type="submit"><?php echo $editCollege ? 'Update College' : 'Create College'; ?></button>
                        <?php if ($editCollege): ?><a class="btn btn-prev link-muted" href="colleges.php">Cancel Edit</a><?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card-box">
                <h3 class="section-label">Quick Summary</h3>
                <p><strong>Total Colleges:</strong> <?php echo count($colleges); ?></p>
                <p><strong>Active Colleges:</strong> <?php echo count(array_filter($colleges, fn($row) => $row['status'] === 'active')); ?></p>
                <p><strong>Inactive Colleges:</strong> <?php echo count(array_filter($colleges, fn($row) => $row['status'] === 'inactive')); ?></p>
                <p><strong>Admins Mapped:</strong> <?php echo count(array_filter($colleges, fn($row) => !empty($row['admin_user_id']))); ?></p>
                <h4 class="section-label" style="margin-top:20px;">Reset Admin Password</h4>
                <form method="POST" class="mini-form">
                    <input type="hidden" name="action" value="reset_password">
                    <select class="form-select" name="admin_user_id" required>
                        <option value="">Select Admin</option>
                        <?php foreach ($colleges as $college): if (empty($college['admin_user_id'])) continue; ?>
                            <option value="<?php echo (int) $college['admin_user_id']; ?>"><?php echo e($college['name'] . ' - ' . $college['admin_email']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" type="password" name="new_password" placeholder="New password" required>
                    <button class="btn btn-next" type="submit">Reset</button>
                </form>
            </div>
        </div>

        <div class="card-box">
            <h3 class="section-label">All Colleges</h3>
            <div class="table-responsive" style="margin-top:0;">
                <table class="admin-table">
                    <thead>
                        <tr><th>College</th><th>Admin</th><th>Location</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colleges as $college): ?>
                            <tr>
                                <td><strong><?php echo e($college['name']); ?></strong><br><small><?php echo e($college['code']); ?> | <?php echo e($college['email']); ?></small></td>
                                <td><?php echo e($college['admin_name'] ?: 'Not mapped'); ?><br><small><?php echo e($college['admin_email'] ?: ''); ?></small></td>
                                <td><?php echo e(trim(($college['city'] ?: '') . ', ' . ($college['district'] ?: '') . ', ' . ($college['state'] ?: ''), ' ,')); ?></td>
                                <td><span class="status-badge <?php echo $college['status'] === 'active' ? 'status-approved' : 'status-rejected'; ?>"><?php echo e(ucfirst($college['status'])); ?></span></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-prev link-muted" href="colleges.php?edit=<?php echo (int) $college['id']; ?>">Edit</a>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="college_id" value="<?php echo (int) $college['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $college['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button class="btn <?php echo $college['status'] === 'active' ? 'btn-prev' : 'btn-next'; ?>" type="submit"><?php echo $college['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($colleges === []): ?><tr><td colspan="5" class="text-center">No colleges found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
