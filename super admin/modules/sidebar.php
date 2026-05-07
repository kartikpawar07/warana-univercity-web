<?php
$active_page = isset($active_page) ? $active_page : 'dashboard';
$base_path = isset($base_path) ? $base_path : '';
$profile = isset($conn) ? getSuperAdminProfile($conn) : [
    'name' => $_SESSION['user_name'] ?? 'Super Admin',
    'email' => '',
    'role' => 'super_admin',
    'joined_at' => 'N/A',
    'last_login' => 'N/A',
];
?>
<aside class="sidebar">
    <div class="sidebar-profile">
        <img src="<?php echo $base_path; ?>images/default-avatar.png" alt="User Profile" class="profile-pic" onerror="this.src='https://ui-avatars.com/api/?name=User&background=3A2359&color=fff';">
        <div class="profile-info">
            <span class="profile-name"><?php echo e($profile['name']); ?></span>
            <span class="profile-role"><?php echo e(ucwords(str_replace('_', ' ', $profile['role']))); ?></span>
        </div>
        <div class="profile-info-icon" tabindex="0" role="button" aria-label="Profile details">
            <i class="ph ph-info"></i>
            <div class="profile-tooltip">
                <div class="tooltip-header">Profile Details</div>
                <div class="tooltip-row">
                    <span class="tooltip-label">Joined:</span>
                    <span class="tooltip-value"><?php echo e($profile['joined_at']); ?></span>
                </div>
                <div class="tooltip-row">
                    <span class="tooltip-label">Last Login:</span>
                    <span class="tooltip-value"><?php echo e($profile['last_login']); ?></span>
                </div>
                <div class="tooltip-row">
                    <span class="tooltip-label">Email:</span>
                    <span class="tooltip-value"><?php echo e($profile['email']); ?></span>
                </div>
                <div class="tooltip-row">
                    <span class="tooltip-label">Access:</span>
                    <span class="tooltip-value" style="color:#d32f2f;"><?php echo e(ucwords(str_replace('_', ' ', $profile['role']))); ?></span>
                </div>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li><a href="<?php echo $base_path; ?>pages/dashboard.php" class="nav-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>"><i class="ph ph-squares-four"></i><span>Dashboard</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/colleges.php" class="nav-item <?php echo $active_page === 'colleges' ? 'active' : ''; ?>"><i class="ph ph-buildings"></i><span>Colleges</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/departments.php" class="nav-item <?php echo $active_page === 'departments' ? 'active' : ''; ?>"><i class="ph ph-books"></i><span>Departments</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/students.php" class="nav-item <?php echo $active_page === 'students' ? 'active' : ''; ?>"><i class="ph ph-users"></i><span>Students</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/prn_management.php" class="nav-item <?php echo $active_page === 'prn_management' ? 'active' : ''; ?>"><i class="ph ph-identification-card"></i><span>PRN Management</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/eligibility_staff.php" class="nav-item <?php echo $active_page === 'eligibility_staff' ? 'active' : ''; ?>"><i class="ph ph-user-gear"></i><span>Eligibility Staff</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/reports.php" class="nav-item <?php echo $active_page === 'reports' ? 'active' : ''; ?>"><i class="ph ph-chart-bar"></i><span>Reports</span></a></li>
            <li><a href="<?php echo $base_path; ?>pages/settings.php" class="nav-item <?php echo $active_page === 'settings' ? 'active' : ''; ?>"><i class="ph ph-gear"></i><span>Settings</span></a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="<?php echo $base_path; ?>logout.php" class="nav-item logout"><i class="ph ph-sign-out"></i><span>Logout</span></a>
    </div>
</aside>
