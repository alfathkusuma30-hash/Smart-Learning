<?php
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_type = $_SESSION['user_type'];
$first_name = explode(' ', $user_name)[0];
$pendingCountHome = ($user_type === 'Admin') ? count(get_activities(null, 'menunggu_konfirmasi')) : 0;
?>

<div class="home-container">
    <!-- Top Welcome Banner -->
    <div class="home-hero-banner">
        <div class="hero-left">
            <div class="hero-tag">
                <i class="ph-fill ph-sparkle text-accent"></i>
                <span>PT ANTAM • CORPORATE LEARNING & DEVELOPMENT</span>
            </div>
            <h1 class="hero-title">
                Selamat Datang, <span class="text-accent"><?php echo htmlspecialchars($user_name); ?></span>
            </h1>
            <div class="hero-badges-row">
                <span class="hero-badge"><i class="ph-fill ph-identification-badge"></i> <strong><?php echo $user_role; ?></strong></span>
                <span class="hero-badge"><i class="ph-fill ph-calendar"></i> <strong id="hero-date-display">12 Agu 2026</strong></span>
                <span class="hero-badge highlight"><i class="ph-fill ph-bell-ringing"></i> <strong id="hero-acc-status"><?php echo $user_type == 'Admin' ? ($pendingCountHome > 0 ? "{$pendingCountHome} Menunggu ACC" : "Sistem Siap") : '3 Modul'; ?></strong></span>
            </div>
        </div>
        
        <div class="hero-right-widget">
            <div class="widget-circle-progress">
                <div class="progress-info">
                    <span class="progress-val"><?php echo $user_type == 'Admin' ? '88%' : '45%'; ?></span>
                    <span class="progress-lbl"><?php echo $user_type == 'Admin' ? 'Compliance' : 'Progres'; ?></span>
                </div>
            </div>
            <div class="widget-quick-btn">
                <a href="?page=<?php echo $user_type == 'Admin' ? 'admin_panel' : 'my_learning'; ?>" class="btn-dark w-full text-center">
                    <i class="ph-bold ph-arrow-circle-right"></i>
                    <span><?php echo $user_type == 'Admin' ? 'Audit' : 'Belajar'; ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Section Title -->
    <div class="section-divider-title">
        <div class="divider-left">
            <i class="ph-fill ph-squares-four text-accent"></i>
            <h2>Menu Sistem</h2>
        </div>
    </div>

    <!-- Clean, Icon-Driven Mega Menu Grid -->
    <div class="mega-menu-grid">
        <!-- 1. Dashboard -->
        <a href="?page=dashboard" class="menu-card">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-indigo">
                    <i class="ph-fill ph-squares-four"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Dashboard</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-chart-line-up"></i> Ringkasan</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <!-- 2. Learning Bank -->
        <a href="?page=learning_bank" class="menu-card">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-emerald">
                    <i class="ph-fill ph-books"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Learning Bank</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-stack"></i> 6 Materi</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <!-- 3. My Learning -->
        <a href="?page=my_learning" class="menu-card">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-blue">
                    <i class="ph-fill ph-graduation-cap"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">My Learning</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-check-circle"></i> 2 Aktif</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <!-- 4. Notifikasi Center -->
        <a href="?page=notifikasi" class="menu-card card-pulse-alarm">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-amber">
                    <i class="ph-fill ph-bell-ringing"></i>
                </div>
                <div class="menu-chip chip-amber">
                    <span class="blinking-dot"></span>
                    <span>3</span>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Notifikasi</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat text-amber font-semibold"><i class="ph-fill ph-siren"></i> Alarm</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <!-- 5. Settings Menu -->
        <a href="?page=settings" class="menu-card">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-teal" style="background: #f1f5f9; color: #334155;">
                    <i class="ph-fill ph-gear"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Setting Profil</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-lock-key"></i> Sandi & Akun</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <?php if($user_type == 'Admin'): ?>
        <!-- 6. Admin Panel -->
        <a href="?page=admin_panel" class="menu-card admin-exclusive">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-teal">
                    <i class="ph-fill ph-shield-check"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Admin Panel</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-users"></i> Monitoring</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <!-- 7. Reports -->
        <a href="?page=reports" class="menu-card admin-exclusive">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-purple">
                    <i class="ph-fill ph-chart-bar"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Reports</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-file-arrow-down"></i> Ekspor</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>
