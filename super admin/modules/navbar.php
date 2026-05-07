<?php
$base_path = isset($base_path) ? $base_path : '';
?>
<!-- Top Navbar -->
<div class="top-navbar">
    <div class="search-bar">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" placeholder="Search...">
    </div>
    <div class="navbar-actions" style="display: flex; gap: 10px;">

        <?php if (isset($navbar_actions)) echo $navbar_actions; ?>
    </div>
</div>
