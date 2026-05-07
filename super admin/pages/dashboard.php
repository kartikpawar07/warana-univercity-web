<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Dashboard';
$active_page = 'dashboard';
$base_path = '../';
$flash = getFlash();
$statusMap = getStatusMap();

$totalColleges = (int) dbValue($conn, 'SELECT COUNT(*) FROM colleges');
$totalStudents = (int) dbValue($conn, 'SELECT COUNT(*) FROM students');
$pendingEligibility = (int) dbValue($conn, 'SELECT COUNT(*) FROM students WHERE status = 3');
$approved = (int) dbValue($conn, 'SELECT COUNT(*) FROM students WHERE status = 5');
$rejected = (int) dbValue($conn, 'SELECT COUNT(*) FROM students WHERE status = 6');
$prnGenerated = (int) dbValue($conn, 'SELECT COUNT(*) FROM prn_generation');

$statusCounts = dbAll(
    $conn,
    'SELECT status, COUNT(*) AS total FROM students GROUP BY status ORDER BY status'
);
$monthlyActivity = dbAll(
    $conn,
    "SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label, COUNT(*) AS total FROM students WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at), MONTH(created_at)"
);
$collegeOverview = dbAll(
    $conn,
    "SELECT c.name, c.code, COUNT(s.id) AS total_students, SUM(CASE WHEN s.status = 5 THEN 1 ELSE 0 END) AS approved_students, SUM(CASE WHEN s.status = 7 THEN 1 ELSE 0 END) AS prn_students FROM colleges c LEFT JOIN students s ON s.college_id = c.id GROUP BY c.id, c.name, c.code ORDER BY total_students DESC, c.name ASC LIMIT 8"
);
$recentActivity = dbAll(
    $conn,
    "SELECT sl.action, sl.description, sl.created_at, u.name AS user_name FROM system_logs sl LEFT JOIN users u ON u.id = sl.user_id ORDER BY sl.id DESC LIMIT 8"
);

require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>
.dashboard-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}.metric-card{background:linear-gradient(135deg,#fff,#f7f3fb);border:1px solid #e8dff2;border-radius:14px;padding:18px;box-shadow:0 8px 20px rgba(58,35,89,.06)}.metric-card h3{margin:0 0 8px;font-size:.78rem;color:#6b5b7d;text-transform:uppercase;letter-spacing:.6px}.metric-card .metric-value{font-size:1.9rem;font-weight:700;color:#3a2359}.metric-card .metric-note{font-size:.78rem;color:#6b5b7d}.dashboard-panels{display:grid;grid-template-columns:2fr 1fr;gap:20px}.panel-card{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.panel-card h3{margin:0 0 14px;color:#3a2359}.bar-list{display:grid;gap:12px}.bar-row{display:grid;grid-template-columns:140px 1fr 48px;gap:12px;align-items:center}.bar-track{background:#f4f0f9;border-radius:999px;height:10px;overflow:hidden}.bar-fill{height:100%;background:linear-gradient(135deg,#3a2359,#5a3a82)}.activity-list{display:grid;gap:12px}.activity-item{border:1px solid #eee;border-radius:10px;padding:12px}.activity-item strong{display:block;color:#2d1a42}.activity-item span{display:block;color:#6b5b7d;font-size:.8rem}.overview-table td,.overview-table th{font-size:.8rem}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px}.flash-success{background:#d1e7dd;color:#0f5132}.flash-error{background:#f8d7da;color:#721c24}@media (max-width:980px){.dashboard-panels{grid-template-columns:1fr}.bar-row{grid-template-columns:100px 1fr 40px}}
</style>
<div class="page-content">
    <div class="top-navbar">
        <div class="page-title">
            <h1>Super Admin Dashboard</h1>
            <p>Live university overview sourced directly from the database.</p>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="flash-box <?php echo $flash['type'] === 'error' ? 'flash-error' : 'flash-success'; ?>"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="metric-card"><h3>Total Colleges</h3><div class="metric-value"><?php echo $totalColleges; ?></div><div class="metric-note">Registered institutions</div></div>
        <div class="metric-card"><h3>Total Students</h3><div class="metric-value"><?php echo $totalStudents; ?></div><div class="metric-note">All student records</div></div>
        <div class="metric-card"><h3>Pending Eligibility</h3><div class="metric-value"><?php echo $pendingEligibility; ?></div><div class="metric-note">University pending cases</div></div>
        <div class="metric-card"><h3>Approved</h3><div class="metric-value"><?php echo $approved; ?></div><div class="metric-note">Eligibility approved</div></div>
        <div class="metric-card"><h3>Rejected</h3><div class="metric-value"><?php echo $rejected; ?></div><div class="metric-note">Rejected applications</div></div>
        <div class="metric-card"><h3>PRN Generated</h3><div class="metric-value"><?php echo $prnGenerated; ?></div><div class="metric-note">Issued PRN records</div></div>
    </div>

    <div class="dashboard-panels">
        <div class="panel-card">
            <h3>Status Distribution</h3>
            <div class="bar-list">
                <?php $maxStatusCount = 0; foreach ($statusCounts as $row) { $maxStatusCount = max($maxStatusCount, (int) $row['total']); } ?>
                <?php foreach ($statusCounts as $row): ?>
                    <?php $width = $maxStatusCount > 0 ? (((int) $row['total'] / $maxStatusCount) * 100) : 0; ?>
                    <div class="bar-row">
                        <span><?php echo e($statusMap[(int) $row['status']] ?? 'Unknown'); ?></span>
                        <div class="bar-track"><div class="bar-fill" style="width:<?php echo number_format($width, 2, '.', ''); ?>%"></div></div>
                        <strong><?php echo (int) $row['total']; ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if ($statusCounts === []): ?>
                    <p>No student records available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel-card">
            <h3>Recent Activity</h3>
            <div class="activity-list">
                <?php foreach ($recentActivity as $item): ?>
                    <div class="activity-item">
                        <strong><?php echo e($item['action']); ?></strong>
                        <span><?php echo e($item['description']); ?></span>
                        <span><?php echo e($item['user_name'] ?: 'System'); ?> | <?php echo e(date('d M Y h:i A', strtotime($item['created_at']))); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($recentActivity === []): ?>
                    <p>No system activity recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-panels" style="margin-top:20px;">
        <div class="panel-card">
            <h3>Monthly Student Registrations</h3>
            <div class="bar-list">
                <?php $maxMonthly = 0; foreach ($monthlyActivity as $row) { $maxMonthly = max($maxMonthly, (int) $row['total']); } ?>
                <?php foreach ($monthlyActivity as $row): ?>
                    <?php $width = $maxMonthly > 0 ? (((int) $row['total'] / $maxMonthly) * 100) : 0; ?>
                    <div class="bar-row">
                        <span><?php echo e($row['month_label']); ?></span>
                        <div class="bar-track"><div class="bar-fill" style="width:<?php echo number_format($width, 2, '.', ''); ?>%"></div></div>
                        <strong><?php echo (int) $row['total']; ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if ($monthlyActivity === []): ?>
                    <p>No registrations found for the recent period.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel-card">
            <h3>College Overview</h3>
            <div class="table-responsive" style="margin-top:0;">
                <table class="admin-table overview-table">
                    <thead>
                        <tr><th>College</th><th>Students</th><th>Approved</th><th>PRN</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collegeOverview as $row): ?>
                            <tr>
                                <td><?php echo e($row['name']); ?> <br><small><?php echo e($row['code']); ?></small></td>
                                <td><?php echo (int) $row['total_students']; ?></td>
                                <td><?php echo (int) $row['approved_students']; ?></td>
                                <td><?php echo (int) $row['prn_students']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($collegeOverview === []): ?>
                            <tr><td colspan="4" class="text-center">No college data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
