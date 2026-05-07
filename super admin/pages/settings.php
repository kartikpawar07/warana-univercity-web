<?php
require_once __DIR__ . '/../includes/app.php';
requireSuperAdmin('../');

$page_title = 'Settings';
$active_page = 'settings';
$base_path = '../';
$flash = getFlash();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = getSettings($conn);
        $logoPath = uploadSettingLogo($_FILES['logo'] ?? [], $settings['logo'] ?? 'images/warana.png');
        saveSetting($conn, 'university_name', postString('university_name'));
        saveSetting($conn, 'logo', $logoPath);
        saveSetting($conn, 'address', postString('address'));
        saveSetting($conn, 'contact', postString('contact'));
        saveSetting($conn, 'academic_start_date', postString('academic_start_date'));
        saveSetting($conn, 'academic_end_date', postString('academic_end_date'));
        logActivity($conn, (int) $_SESSION['user_id'], 'settings_updated', 'Updated university settings.');
        setFlash('success', 'Settings updated successfully.');
        redirectTo('settings.php');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$settings = getSettings($conn);
require_once __DIR__ . '/../modules/header.php';
require_once __DIR__ . '/../modules/sidebar.php';
require_once __DIR__ . '/../modules/topbar.php';
?>
<style>.card-box{background:#fff;border:1px solid #e8dff2;border-radius:14px;padding:18px;max-width:980px}.preview-logo{max-height:84px;display:block;margin-top:10px}.flash-box{padding:12px 14px;border-radius:10px;margin-bottom:18px}.flash-success{background:#d1e7dd;color:#0f5132}.flash-error{background:#f8d7da;color:#721c24}</style>
<div class="page-content">
    <div class="top-navbar"><div class="page-title"><h1>Settings</h1><p>Maintain university branding and the active academic period for the super admin module.</p></div></div>
    <?php if ($flash): ?><div class="flash-box <?php echo $flash['type'] === 'error' ? 'flash-error' : 'flash-success'; ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <?php foreach ($errors as $error): ?><div class="flash-box flash-error"><?php echo e($error); ?></div><?php endforeach; ?>
    <div class="card-box">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group"><label class="form-label">University Name</label><input class="form-control" name="university_name" required value="<?php echo e($settings['university_name'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label">Contact</label><input class="form-control" name="contact" value="<?php echo e($settings['contact'] ?? ''); ?>"></div>
                <div class="form-group full-width"><label class="form-label">Address</label><textarea class="form-control" name="address"><?php echo e($settings['address'] ?? ''); ?></textarea></div>
                <div class="form-group"><label class="form-label">Academic Start Date</label><input type="date" class="form-control" name="academic_start_date" value="<?php echo e($settings['academic_start_date'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label">Academic End Date</label><input type="date" class="form-control" name="academic_end_date" value="<?php echo e($settings['academic_end_date'] ?? ''); ?>"></div>
                <div class="form-group"><label class="form-label">University Logo</label><input type="file" class="form-control" name="logo" accept=".png,.jpg,.jpeg,.webp"><img class="preview-logo" src="<?php echo $base_path . e($settings['logo'] ?? 'images/warana.png'); ?>" alt="University Logo"></div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-next" type="submit">Save Settings</button></div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../modules/footer.php'; ?>
