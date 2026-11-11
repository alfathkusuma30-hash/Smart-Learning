<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$user_name = $user['name'] ?? session('user_name') ?? 'Pegawai ANTAM';
$user_role = $user['role'] ?? session('user_role') ?? 'Operasional Tambang';
$user_type = $userType ?? session('user_type') ?? 'User';
$first_name = explode(' ', $user_name)[0];
$pendingCountHome = ($user_type === 'Admin') ? count(get_activities(null, 'menunggu_konfirmasi')) : 0;
?>

<div class="home-container">
    <!-- Top Welcome Banner (Clean Executive Style) -->
    <div class="home-hero-banner">
        <!-- Silhouette / Watermark Artwork (Learning & Mining Synthesis) -->
        <div class="hero-banner-watermark" aria-hidden="true">
            <div class="hero-banner-watermark-glow"></div>
            <div class="hero-banner-watermark-img"></div>
        </div>

        <div class="hero-left">
            <div class="hero-tag">
                <i class="ph-fill ph-shield-check text-accent"></i>
                <span>ACADEMY K3 &amp; KOMPETENSI TAMBANG</span>
            </div>
            <h1 class="hero-title">
                Selamat Datang, <span class="text-accent"><?= esc($first_name) ?></span>
            </h1>
            <p class="hero-subtitle text-xs text-muted" style="margin-top: 0.25rem; margin-bottom: 1rem; max-width: 520px; line-height: 1.5; color: rgba(255,255,255,0.75);">
                Pusat peningkatan kompetensi teknis, sertifikasi, dan kepatuhan keselamatan kerja Site Pomalaa.
            </p>
            <div class="hero-badges-row">
                <span class="hero-badge"><i class="ph-fill ph-identification-badge"></i> <strong><?= esc($user_role) ?></strong></span>
                <span class="hero-badge highlight"><i class="ph-fill ph-bell-ringing"></i> <strong id="hero-acc-status"><?= $user_type == 'Admin' ? ($pendingCountHome > 0 ? "{$pendingCountHome} Menunggu Verifikasi" : "Sistem Siap") : '3 Modul Aktif' ?></strong></span>
            </div>
        </div>
        
        <div class="hero-right-widget">
            <?php 
                $progVal = $user_type == 'Admin' ? 88 : 45;
                $progLbl = $user_type == 'Admin' ? 'Compliance' : 'Progres';
            ?>
            <div class="widget-circle-progress" style="background: radial-gradient(circle, rgba(2, 44, 34, 0.92) 58%, transparent 63%), conic-gradient(#34d399 0% <?= $progVal ?>%, rgba(255, 255, 255, 0.15) <?= $progVal ?>% 100%);">
                <div class="progress-info">
                    <span class="progress-val"><?= $progVal ?>%</span>
                    <span class="progress-lbl"><?= $progLbl ?></span>
                </div>
            </div>
            <div class="widget-quick-btn">
                <a href="<?= base_url($user_type == 'Admin' ? 'admin-panel' : 'my-learning') ?>" class="btn-hero-action w-full text-center">
                    <i class="ph-bold ph-arrow-circle-right"></i>
                    <span><?= $user_type == 'Admin' ? 'Buka Panel' : 'Mulai Belajar' ?></span>
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
        <a href="<?= base_url('dashboard') ?>" class="menu-card">
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
        <a href="<?= base_url('learning-bank') ?>" class="menu-card">
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
        <a href="<?= base_url('my-learning') ?>" class="menu-card">
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
        <a href="<?= base_url('notifikasi') ?>" class="menu-card card-pulse-alarm">
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
        <a href="<?= base_url('settings') ?>" class="menu-card">
            <div class="menu-card-header">
                <div class="menu-icon-box icon-teal" style="background: #f1f5f9; color: #334155;">
                    <i class="ph-fill ph-gear"></i>
                </div>
            </div>
            <div class="menu-card-body">
                <h3 class="menu-title">Setting Profil</h3>
            </div>
            <div class="menu-card-footer">
                <span class="footer-stat"><i class="ph-fill ph-lock-key"></i> Sandi &amp; Akun</span>
                <span class="footer-action"><i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <?php if ($user_type == 'Admin'): ?>
        <!-- 6. Admin Panel -->
        <a href="<?= base_url('admin-panel') ?>" class="menu-card admin-exclusive">
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
        <a href="<?= base_url('reports') ?>" class="menu-card admin-exclusive">
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
<?= $this->endSection() ?>

