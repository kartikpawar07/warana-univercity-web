<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Departments';
$active_page = 'departments';
$base_path = '../';
$flash = getFlash();
$collegeFilter = (int) ($_GET['college_id'] ?? 0);
$colleges = dbAll($conn, "SELECT id, name FROM colleges ORDER BY name");
$params = [];
$types = '';
$where = '';
if ($collegeFilter > 0) {
    $where = 'WHERE d.college_id = ?';
    $types = 'i';
    $params[] = $collegeFilter;
}
$departments = dbAll(
    $conn,
    "SELECT d.id, d.name, d.status, d.created_at, c.name AS college_name, COUNT(p.id) AS program_count
     FROM departments d
     INNER JOIN colleges c ON c.id = d.college_id
     LEFT JOIN programs p ON p.department_id = d.id
     {$where}
     GROUP BY d.id, d.name, d.status, d.created_at, c.name
     ORDER BY c.name, d.name",
    $types,
    $params
);
require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.filter-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px;background:#d1e7dd;color:#0f5132}</style>
<div class="page-content">
    <div class="top-navbar"><div class="page-title"><h1>Departments</h1><p>View departments created by college admins across all colleges.</p></div></div>
    <?php if ($flash): ?><div class="flash-box"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <div class="card-box">
        <form class="filter-row" method="GET">
            <select class="form-select" name="college_id">
                <option value="">All Colleges</option>
                <?php foreach ($colleges as $college): ?>
                    <option value="<?php echo (int) $college['id']; ?>" <?php echo $collegeFilter === (int) $college['id'] ? 'selected' : ''; ?>><?php echo e($college['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-next" type="submit">Apply Filter</button>
            <a href="departments.php" class="btn btn-prev">Reset</a>
        </form>
        <div class="table-responsive" style="margin-top:0;">
            <table class="admin-table">
                <thead><tr><th>Department Name</th><th>College Name</th><th>Programs</th><th>Status</th><th>Created At</th></tr></thead>
                <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?php echo e($department['name']); ?></td>
                            <td><?php echo e($department['college_name']); ?></td>
                            <td><?php echo (int) $department['program_count']; ?></td>
                            <td><span class="status-badge <?php echo $department['status'] === 'active' ? 'status-approved' : 'status-rejected'; ?>"><?php echo e(ucfirst($department['status'])); ?></span></td>
                            <td><?php echo e(date('d M Y', strtotime($department['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($departments === []): ?><tr><td colspan="5" class="text-center">No departments found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
