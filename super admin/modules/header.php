<?php
$page_title = isset($page_title) ? $page_title : 'Dashboard';
$base_path = isset($base_path) ? $base_path : '';
$settings = isset($conn) ? getSettings($conn) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo e($settings['university_name'] ?? 'Warana University'); ?> - <?php echo e($page_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/global.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/form.css">
</head>
<body>
    <div class="mobile-overlay" onclick="closeSidebar()"></div>

    <header class="main-header">
        <div class="header-main-content">
            <div class="header-left-content">
                <img src="<?php echo $base_path . e($settings['logo'] ?? 'images/warana.png'); ?>" alt="University Logo" class="logo" onerror="this.src='<?php echo $base_path; ?>images/warana.png'">
                <div class="header-center">
                    <div class="trust-name">Super Admin Control Panel</div>
                    <h1 class="university-name"><?php echo e($settings['university_name'] ?? 'Warana University, Warananagar'); ?></h1>
                    <div class="university-status"><?php echo e(getCurrentAcademicLabel($settings)); ?></div>
                </div>
                <img src="<?php echo $base_path; ?>images/founder.png" alt="Founder" class="founder-photo" onerror="this.style.display='none'">
            </div>
            <div class="header-right-content">
                <div class="quick-menu-wrap">
                    <button class="quick-menu-btn" id="quickMenuBtn" onclick="toggleQuickMenu(event)" aria-haspopup="true" aria-expanded="false">
                        <i class="ph ph-squares-four"></i>
                        <span>Quick Menu</span>
                        <i class="ph ph-caret-down qm-caret"></i>
                    </button>
                    <div class="quick-menu-dropdown" id="quickMenuDropdown" role="menu">
                        <div class="qm-section-label">Management</div>
                        <a href="<?php echo $base_path; ?>pages/colleges.php" class="qm-item" role="menuitem"><i class="ph ph-buildings"></i> Colleges</a>
                        <a href="<?php echo $base_path; ?>pages/departments.php" class="qm-item" role="menuitem"><i class="ph ph-books"></i> Departments</a>
                        <a href="<?php echo $base_path; ?>pages/students.php" class="qm-item" role="menuitem"><i class="ph ph-student"></i> Students</a>
                        <div class="qm-divider"></div>
                        <div class="qm-section-label">Operations</div>
                        <a href="<?php echo $base_path; ?>pages/prn_management.php" class="qm-item" role="menuitem"><i class="ph ph-identification-card"></i> PRN Management</a>
                        <a href="<?php echo $base_path; ?>pages/eligibility_staff.php" class="qm-item" role="menuitem"><i class="ph ph-user-gear"></i> Eligibility Staff</a>
                        <a href="<?php echo $base_path; ?>pages/reports.php" class="qm-item" role="menuitem"><i class="ph ph-chart-bar"></i> Reports</a>
                        <div class="qm-divider"></div>
                        <div class="qm-section-label">System</div>
                        <a href="<?php echo $base_path; ?>pages/settings.php" class="qm-item" role="menuitem"><i class="ph ph-gear"></i> Settings</a>
                        <a href="<?php echo $base_path; ?>pages/dashboard.php" class="qm-item" role="menuitem"><i class="ph ph-house"></i> Dashboard</a>
                        <a href="<?php echo $base_path; ?>logout.php" class="qm-item" role="menuitem"><i class="ph ph-sign-out"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <button class="mobile-toggle" onclick="toggleMobileSidebar()">
            <i class="ph ph-list"></i>
            <span class="menu-text">Menu</span>
        </button>
    </header>

    <div class="app-container">

    <script>
        function toggleQuickMenu(event) {
            event.stopPropagation();
            const btn = document.getElementById('quickMenuBtn');
            const dropdown = document.getElementById('quickMenuDropdown');
            if (!btn || !dropdown) return;
            const isOpen = dropdown.classList.contains('open');
            dropdown.classList.toggle('open', !isOpen);
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('quickMenuDropdown');
            const btn = document.getElementById('quickMenuBtn');
            if (!dropdown || !btn) return;
            if (dropdown.classList.contains('open') && !btn.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        function toggleMobileSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.body.classList.remove('sidebar-open');
                document.querySelectorAll('.profile-info-icon.tooltip-open').forEach(function(el) {
                    el.classList.remove('tooltip-open');
                });
                const dropdown = document.getElementById('quickMenuDropdown');
                const btn = document.getElementById('quickMenuBtn');
                if (dropdown && btn) {
                    dropdown.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });

        document.addEventListener('click', function(event) {
            const isMobile = window.innerWidth <= 850;
            const clickedIcon = event.target.closest('.profile-info-icon');
            if (!isMobile) return;

            document.querySelectorAll('.profile-info-icon.tooltip-open').forEach(function(el) {
                if (el !== clickedIcon) {
                    el.classList.remove('tooltip-open');
                }
            });

            if (clickedIcon) {
                clickedIcon.classList.toggle('tooltip-open');
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);
    </script>
