<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'PRN Management';
$active_page = 'prn_management';
$base_path = '../';
$flash = getFlash();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && postString('action') === 'generate_prn') {
    try {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $student = dbOne(
            $conn,
            "SELECT s.id, s.academic_year, s.status, c.code AS college_code
             FROM students s
             INNER JOIN colleges c ON c.id = s.college_id
             LEFT JOIN prn_generation pg ON pg.student_id = s.id
             WHERE s.id = ? AND s.status = 5 AND pg.id IS NULL LIMIT 1",
            'i',
            [$studentId]
        );
        if (!$student) {
            throw new RuntimeException('Student is not eligible for PRN generation.');
        }

        $prn = generateUniquePrn($conn, (int) $student['id'], $student['college_code'], $student['academic_year']);
        $conn->begin_transaction();
        dbExecute($conn, 'INSERT INTO prn_generation (student_id, prn) VALUES (?, ?)', 'is', [$studentId, $prn]);
        dbExecute($conn, 'UPDATE students SET prn = ?, status = 7 WHERE id = ?', 'si', [$prn, $studentId]);
        logActivity($conn, (int) $_SESSION['user_id'], 'prn_generated', "Generated PRN {$prn} for student ID {$studentId}.");
        $conn->commit();
        setFlash('success', 'PRN generated successfully.');
        redirectTo('prn_management.php');
    } catch (Throwable $e) {
        $conn->rollback();
        $errors[] = $e->getMessage();
    }
}

$eligibleStudents = dbAll(
    $conn,
    "SELECT s.id, u.name AS student_name, c.name AS college_name, d.name AS department_name, p.name AS program_name, s.academic_year
     FROM students s
     INNER JOIN users u ON u.id = s.user_id
     INNER JOIN colleges c ON c.id = s.college_id
     INNER JOIN departments d ON d.id = s.department_id
     INNER JOIN programs p ON p.id = s.program_id
     LEFT JOIN prn_generation pg ON pg.student_id = s.id
     WHERE s.status = 5 AND pg.id IS NULL
     ORDER BY s.created_at ASC"
);
$generatedPrns = dbAll(
    $conn,
    "SELECT pg.prn, pg.generated_at, u.name AS student_name, c.name AS college_name
     FROM prn_generation pg
     INNER JOIN students s ON s.id = pg.student_id
     INNER JOIN users u ON u.id = s.user_id
     INNER JOIN colleges c ON c.id = s.college_id
     ORDER BY pg.generated_at DESC LIMIT 20"
);
require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>.grid-panels{display:grid;grid-template-columns:1.3fr .7fr;gap:20px}.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px}.flash-success{background:#d1e7dd;color:#0f5132}.flash-error{background:#f8d7da;color:#721c24}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px}@media (max-width:980px){.grid-panels{grid-template-columns:1fr}}</style>
<div class="page-content">
    <div class="top-navbar"><div class="page-title"><h1>PRN Management</h1><p>Generate unique PRNs for approved students who do not yet have one.</p></div></div>
    <?php if ($flash): ?><div class="flash-box <?php echo $flash['type'] === 'error' ? 'flash-error' : 'flash-success'; ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <?php foreach ($errors as $error): ?><div class="flash-box flash-error"><?php echo e($error); ?></div><?php endforeach; ?>
    <div class="grid-panels">
        <div class="card-box">
            <h3 style="margin-top:0;color:#3a2359;">Eligible Students</h3>
            <div class="table-responsive" style="margin-top:0;">
                <table class="admin-table">
                    <thead><tr><th>Student</th><th>College</th><th>Department</th><th>Program</th><th>Academic Year</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($eligibleStudents as $student): ?>
                            <tr>
                                <td><?php echo e($student['student_name']); ?></td>
                                <td><?php echo e($student['college_name']); ?></td>
                                <td><?php echo e($student['department_name']); ?></td>
                                <td><?php echo e($student['program_name']); ?></td>
                                <td><?php echo e($student['academic_year']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="generate_prn">
                                        <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                                        <button class="btn btn-next" type="submit">Generate PRN</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($eligibleStudents === []): ?><tr><td colspan="6" class="text-center">No approved students are waiting for PRN generation.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-box">
            <h3 style="margin-top:0;color:#3a2359;">Recently Generated</h3>
            <div class="table-responsive" style="margin-top:0;">
                <table class="admin-table">
                    <thead><tr><th>PRN</th><th>Student</th><th>College</th><th>Generated</th></tr></thead>
                    <tbody>
                        <?php foreach ($generatedPrns as $row): ?>
                            <tr>
                                <td><?php echo e($row['prn']); ?></td>
                                <td><?php echo e($row['student_name']); ?></td>
                                <td><?php echo e($row['college_name']); ?></td>
                                <td><?php echo e(date('d M Y h:i A', strtotime($row['generated_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($generatedPrns === []): ?><tr><td colspan="4" class="text-center">No PRNs generated yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
