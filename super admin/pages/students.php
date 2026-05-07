<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Students';
$active_page = 'students';
$base_path = '../';
$flash = getFlash();
$statusMap = getStatusMap();
$options = getFilterOptions($conn);
$collegeId = (int) ($_GET['college_id'] ?? 0);
$departmentId = (int) ($_GET['department_id'] ?? 0);
$programId = (int) ($_GET['program_id'] ?? 0);
$statusId = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : null;

$conditions = [];
$params = [];
$types = '';
if ($collegeId > 0) { $conditions[] = 's.college_id = ?'; $types .= 'i'; $params[] = $collegeId; }
if ($departmentId > 0) { $conditions[] = 's.department_id = ?'; $types .= 'i'; $params[] = $departmentId; }
if ($programId > 0) { $conditions[] = 's.program_id = ?'; $types .= 'i'; $params[] = $programId; }
if ($statusId !== null) { $conditions[] = 's.status = ?'; $types .= 'i'; $params[] = $statusId; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$students = dbAll(
    $conn,
    "SELECT s.id, s.prn, s.academic_year, s.status, s.created_at, u.name AS student_name, u.email AS student_email,
            c.name AS college_name, d.name AS department_name, p.name AS program_name
     FROM students s
     INNER JOIN users u ON u.id = s.user_id
     INNER JOIN colleges c ON c.id = s.college_id
     INNER JOIN departments d ON d.id = s.department_id
     INNER JOIN programs p ON p.id = s.program_id
     {$where}
     ORDER BY s.created_at DESC, u.name ASC",
    $types,
    $params
);
require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px;background:#d1e7dd;color:#0f5132}</style>
<div class="page-content">
    <div class="top-navbar"><div class="page-title"><h1>Student Monitoring</h1><p>Review student eligibility records using live college, department, program, and status filters.</p></div></div>
    <?php if ($flash): ?><div class="flash-box"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <div class="card-box">
        <form method="GET" class="filter-grid">
            <div class="form-group"><label class="form-label">College</label><select class="form-select" name="college_id"><option value="">All Colleges</option><?php foreach ($options['colleges'] as $item): ?><option value="<?php echo (int) $item['id']; ?>" <?php echo $collegeId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">All Departments</option><?php foreach ($options['departments'] as $item): ?><option value="<?php echo (int) $item['id']; ?>" <?php echo $departmentId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Program</label><select class="form-select" name="program_id"><option value="">All Programs</option><?php foreach ($options['programs'] as $item): ?><option value="<?php echo (int) $item['id']; ?>" <?php echo $programId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All Statuses</option><?php foreach ($statusMap as $value => $label): ?><option value="<?php echo $value; ?>" <?php echo $statusId !== null && $statusId === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option><?php endforeach; ?></select></div>
            <div class="inline-actions"><button class="btn btn-next" type="submit">Apply</button> <a href="students.php" class="btn btn-prev">Reset</a></div>
        </form>
        <div class="table-responsive">
            <table class="admin-table">
                <thead><tr><th>PRN</th><th>Student</th><th>College</th><th>Department</th><th>Program</th><th>Academic Year</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo e($student['prn'] ?: 'Pending'); ?></td>
                            <td><strong><?php echo e($student['student_name']); ?></strong><br><small><?php echo e($student['student_email']); ?></small></td>
                            <td><?php echo e($student['college_name']); ?></td>
                            <td><?php echo e($student['department_name']); ?></td>
                            <td><?php echo e($student['program_name']); ?></td>
                            <td><?php echo e($student['academic_year']); ?></td>
                            <td><span class="status-badge <?php echo getStatusBadgeClass((int) $student['status']); ?>"><?php echo e($statusMap[(int) $student['status']] ?? 'Unknown'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($students === []): ?><tr><td colspan="7" class="text-center">No students match the selected filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
