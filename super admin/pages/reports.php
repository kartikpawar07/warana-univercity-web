<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Reports';
$active_page = 'reports';
$base_path = '../';
$statusMap = getStatusMap();
$options = getFilterOptions($conn);
$reportType = getString('report_type', 'college');
$collegeId = (int) ($_GET['college_id'] ?? 0);
$programId = (int) ($_GET['program_id'] ?? 0);
$academicYear = getString('academic_year');
$statusId = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : null;

$baseConditions = [];
$params = [];
$types = '';
if ($collegeId > 0) { $baseConditions[] = 's.college_id = ?'; $types .= 'i'; $params[] = $collegeId; }
if ($programId > 0) { $baseConditions[] = 's.program_id = ?'; $types .= 'i'; $params[] = $programId; }
if ($academicYear !== '') { $baseConditions[] = 's.academic_year = ?'; $types .= 's'; $params[] = $academicYear; }
if ($statusId !== null) { $baseConditions[] = 's.status = ?'; $types .= 'i'; $params[] = $statusId; }
$where = $baseConditions ? 'WHERE ' . implode(' AND ', $baseConditions) : '';

$reportRows = [];
$headers = [];
$title = '';
if ($reportType === 'program') {
    $title = 'Program-wise Report';
    $headers = ['Program', 'Students', 'Approved', 'Rejected', 'PRN Generated'];
    $reportRows = dbAll($conn, "SELECT p.name AS label, COUNT(s.id) AS total_students, SUM(CASE WHEN s.status = 5 THEN 1 ELSE 0 END) AS approved_count, SUM(CASE WHEN s.status = 6 THEN 1 ELSE 0 END) AS rejected_count, SUM(CASE WHEN s.status = 7 THEN 1 ELSE 0 END) AS prn_count FROM students s INNER JOIN programs p ON p.id = s.program_id {$where} GROUP BY p.id, p.name ORDER BY p.name", $types, $params);
}
elseif ($reportType === 'eligibility') {
    $title = 'Eligibility Report';
    $headers = ['Status', 'Students'];
    $reportRows = dbAll($conn, "SELECT s.status AS label, COUNT(s.id) AS total_students FROM students s {$where} GROUP BY s.status ORDER BY s.status", $types, $params);
    foreach ($reportRows as &$row) { $row['label'] = $statusMap[(int) $row['label']] ?? 'Unknown'; }
    unset($row);
}
elseif ($reportType === 'prn') {
    $title = 'PRN Report';
    $headers = ['PRN', 'Student', 'College', 'Program', 'Generated At'];
    $reportRows = dbAll($conn, "SELECT pg.prn, u.name AS student_name, c.name AS college_name, p.name AS program_name, pg.generated_at FROM prn_generation pg INNER JOIN students s ON s.id = pg.student_id INNER JOIN users u ON u.id = s.user_id INNER JOIN colleges c ON c.id = s.college_id INNER JOIN programs p ON p.id = s.program_id {$where} ORDER BY pg.generated_at DESC", $types, $params);
}
else {
    $reportType = 'college';
    $title = 'College-wise Report';
    $headers = ['College', 'Students', 'Approved', 'Rejected', 'PRN Generated'];
    $reportRows = dbAll($conn, "SELECT c.name AS label, COUNT(s.id) AS total_students, SUM(CASE WHEN s.status = 5 THEN 1 ELSE 0 END) AS approved_count, SUM(CASE WHEN s.status = 6 THEN 1 ELSE 0 END) AS rejected_count, SUM(CASE WHEN s.status = 7 THEN 1 ELSE 0 END) AS prn_count FROM students s INNER JOIN colleges c ON c.id = s.college_id {$where} GROUP BY c.id, c.name ORDER BY c.name", $types, $params);
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($reportRows as $row) {
        if ($reportType === 'prn') {
            fputcsv($output, [$row['prn'], $row['student_name'], $row['college_name'], $row['program_name'], $row['generated_at']]);
        } elseif ($reportType === 'eligibility') {
            fputcsv($output, [$row['label'], $row['total_students']]);
        } else {
            fputcsv($output, [$row['label'], $row['total_students'], $row['approved_count'], $row['rejected_count'], $row['prn_count']]);
        }
    }
    fclose($output);
    exit;
}

$academicYears = dbAll($conn, 'SELECT DISTINCT academic_year FROM students ORDER BY academic_year DESC');
require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end}.report-title{margin:18px 0 10px;color:#3a2359}</style>
<div class="page-content">
    <div class="top-navbar"><div class="page-title"><h1>Reports Module</h1><p>Generate filtered operational reports and export the result to CSV.</p></div></div>
    <div class="card-box">
        <form method="GET" class="filter-grid">
            <div class="form-group"><label class="form-label">Report Type</label><select class="form-select" name="report_type"><option value="college" <?php echo $reportType === 'college' ? 'selected' : ''; ?>>College-wise</option><option value="program" <?php echo $reportType === 'program' ? 'selected' : ''; ?>>Program-wise</option><option value="eligibility" <?php echo $reportType === 'eligibility' ? 'selected' : ''; ?>>Eligibility</option><option value="prn" <?php echo $reportType === 'prn' ? 'selected' : ''; ?>>PRN</option></select></div>
            <div class="form-group"><label class="form-label">College</label><select class="form-select" name="college_id"><option value="">All Colleges</option><?php foreach ($options['colleges'] as $item): ?><option value="<?php echo (int) $item['id']; ?>" <?php echo $collegeId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Program</label><select class="form-select" name="program_id"><option value="">All Programs</option><?php foreach ($options['programs'] as $item): ?><option value="<?php echo (int) $item['id']; ?>" <?php echo $programId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Academic Year</label><select class="form-select" name="academic_year"><option value="">All Years</option><?php foreach ($academicYears as $year): ?><option value="<?php echo e($year['academic_year']); ?>" <?php echo $academicYear === $year['academic_year'] ? 'selected' : ''; ?>><?php echo e($year['academic_year']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All Statuses</option><?php foreach ($statusMap as $value => $label): ?><option value="<?php echo $value; ?>" <?php echo $statusId !== null && $statusId === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option><?php endforeach; ?></select></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-next" type="submit">Generate</button><a class="btn btn-prev" href="reports.php">Reset</a><button class="btn btn-submit" type="submit" name="export" value="csv">Export CSV</button></div>
        </form>

        <h3 class="report-title"><?php echo e($title); ?></h3>
        <div class="table-responsive" style="margin-top:0;">
            <table class="admin-table">
                <thead><tr><?php foreach ($headers as $header): ?><th><?php echo e($header); ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                    <?php foreach ($reportRows as $row): ?>
                        <tr>
                            <?php if ($reportType === 'prn'): ?>
                                <td><?php echo e($row['prn']); ?></td><td><?php echo e($row['student_name']); ?></td><td><?php echo e($row['college_name']); ?></td><td><?php echo e($row['program_name']); ?></td><td><?php echo e(date('d M Y h:i A', strtotime($row['generated_at']))); ?></td>
                            <?php elseif ($reportType === 'eligibility'): ?>
                                <td><?php echo e($row['label']); ?></td><td><?php echo (int) $row['total_students']; ?></td>
                            <?php else: ?>
                                <td><?php echo e($row['label']); ?></td><td><?php echo (int) $row['total_students']; ?></td><td><?php echo (int) $row['approved_count']; ?></td><td><?php echo (int) $row['rejected_count']; ?></td><td><?php echo (int) $row['prn_count']; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($reportRows === []): ?><tr><td colspan="<?php echo count($headers); ?>" class="text-center">No report data available for the selected filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
