<?php
$userType = $userType ?? session('user_type') ?? 'User';
$username = $username ?? session('username') ?? '';
$userObj = $user ?? get_user_by_username($username) ?? [];
$userName = $userObj['name'] ?? session('user_name') ?? 'Pegawai ANTAM';
$userRole = $userObj['role'] ?? session('user_role') ?? 'Mining Operations';
$userDept = $userObj['dept'] ?? session('user_dept') ?? 'UBPN Kolaka';
$avatarColor = $userObj['avatar_color'] ?? session('avatar_color') ?? '#0f766e';
$avatarImage = $userObj['avatar_image'] ?? session('avatar_image') ?? null;
$activeNav = $activeNav ?? 'home';

// Calculate Badge Count
if ($userType === 'Admin') {
    $pendingActs = get_activities(null, 'menunggu_konfirmasi');
    $badgeCount = 2 + count($pendingActs);
} else {
    $userNotifs = get_notifications_for_user($username, 'User');
    $userPending = get_activities($username, 'menunggu_konfirmasi');
    $badgeCount = 2 + count($userNotifs) + count($userPending);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Smart Learning Portal — PT ANTAM Tbk') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Caveat:wght@600;700&family=Kalam:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/style.css') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-antam-icon.png') ?>">
</head>
<body class="os-body">

    <!-- Ambient Desktop Background -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>

    <div class="desktop-env">
        <!-- Topbar (SunFish / ANTAM WorkPlaze Style) -->
        <header class="topbar">
            <div class="topbar-left">
                <a href="<?= base_url('home') ?>" class="brand-link" title="PT ANTAM Tbk - Smart Learning Portal">
                    <!-- Logo PT ANTAM Tbk -->
                    <div class="topbar-antam-badge">
                        <img src="<?= base_url('assets/img/logo-antam.png') ?>" alt="Logo PT ANTAM Tbk" class="topbar-antam-logo">
                    </div>

                    <!-- Vertical Divider Bersanding -->
                    <div class="topbar-brand-divider"></div>

                    <!-- Logo & Brand Smart Learning -->
                    <div class="brand-smart-learning-unit">
                        <div class="brand-logo-emblem">
                            <img src="<?= base_url('assets/img/logo-icon.png') ?>" alt="Smart Learning Emblem" class="brand-emblem-img">
                        </div>
                        <div class="brand-text-block">
                            <div class="brand-title-row">
                                <span class="brand-title">Smart Learning</span>
                                <span class="brand-k3-badge">PORTAL</span>
                            </div>
                            <span class="brand-subtitle">Mining &amp; Safety Academy</span>
                        </div>
                    </div>
                </a>

                <!-- Global Search Input (SunFish Style) -->
                <div class="header-search-bar">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="global-search-input" placeholder="Cari Pegawai, Modul K3, atau Pelatihan...">
                    <span class="kbd-shortcut">Ctrl+K</span>
                </div>
            </div>
            
            <div class="topbar-center">
                <!-- Clock & Date Display -->
                <div class="system-clock-box">
                    <i class="ph-bold ph-calendar-blank text-accent"></i>
                    <span id="sys-time" class="font-mono"><?= get_realtime_indonesian_date('short') . ' · ' . date('H:i:s') ?></span>
                </div>
            </div>
            
            <div class="topbar-right">
                <!-- App Grid Launcher -->
                <a href="<?= base_url('home') ?>" class="topbar-action-icon" title="Menu Sistem">
                    <i class="ph-bold ph-squares-four"></i>
                </a>

                <!-- Notification Mail / Alarm with Red Badge Pill -->
                <a href="<?= base_url('notifikasi') ?>" class="topbar-action-icon" title="Pusat Notifikasi &amp; Alarm">
                    <i class="ph-bold ph-bell"></i>
                    <span class="topbar-notif-badge" id="topbar-notif-pill"><?= $badgeCount > 9 ? '99+' : $badgeCount ?></span>
                </a>

                <!-- User Profile Badge & Quick Settings -->
                <a href="<?= base_url('settings') ?>" class="user-profile-badge" title="Pengaturan Profil &amp; Password">
                    <div class="user-meta-compact">
                        <span class="user-fullname"><?= strtoupper(esc($userName)) ?></span>
                        <span class="user-division"><?= esc($userRole) ?></span>
                    </div>
                    <div class="mini-avatar" style="background: <?= esc($avatarColor) ?>; overflow: hidden; padding: 0;">
                        <?php if (!empty($avatarImage) && file_exists(FCPATH . 'uploads/avatars/' . $avatarImage)): ?>
                            <img src="<?= base_url('uploads/avatars/' . esc($avatarImage)) ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                        <?php else: ?>
                            <?php 
                            $nameParts = explode(' ', $userName);
                            echo count($nameParts) > 1 ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)) : strtoupper(substr($userName, 0, 2));
                            ?>
                        <?php endif; ?>
                    </div>
                </a>

                <a href="<?= base_url('logout') ?>" class="logout-btn" title="Ganti Akun / Logout">
                    <i class="ph ph-sign-out"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </header>
        
        <div class="workspace">
            <!-- Dock (Slim Clean Sidebar) -->
            <nav class="dock" id="main-dock">
                <div class="dock-nav-items">
                    <!-- 1. Home / Desktop -->
                    <a href="<?= base_url('home') ?>" class="dock-item <?= $activeNav == 'home' ? 'active' : '' ?>" title="Home Desktop">
                        <div class="dock-icon">
                            <i class="ph-bold ph-house"></i>
                        </div>
                        <span class="dock-label">Home</span>
                    </a>

                    <!-- 2. Dashboard -->
                    <a href="<?= base_url('dashboard') ?>" class="dock-item <?= $activeNav == 'dashboard' ? 'active' : '' ?>" title="<?= $userType === 'Admin' ? 'Executive Dashboard Monitoring' : 'Personal Dashboard' ?>">
                        <div class="dock-icon">
                            <i class="ph-bold ph-chart-pie-slice"></i>
                        </div>
                        <span class="dock-label">Dashboard</span>
                    </a>
                    
                    <!-- 3. Learning Bank (Catalog) -->
                    <a href="<?= base_url('learning-bank') ?>" class="dock-item <?= $activeNav == 'learning_bank' ? 'active' : '' ?>" title="Manage Training Course">
                        <div class="dock-icon">
                            <i class="ph-bold ph-clock"></i>
                        </div>
                        <span class="dock-label">Catalog</span>
                    </a>
                    
                    <!-- 4. My Learning Track (Graduation Cap) -->
                    <a href="<?= base_url('my-learning') ?>" class="dock-item <?= $activeNav == 'my_learning' ? 'active' : '' ?>" title="My Training Track">
                        <div class="dock-icon">
                            <i class="ph-bold ph-graduation-cap"></i>
                        </div>
                        <span class="dock-label">Training</span>
                    </a>
                    
                    <!-- 5. Notifikasi (Alarm Hub) -->
                    <a href="<?= base_url('notifikasi') ?>" class="dock-item <?= $activeNav == 'notifikasi' ? 'active' : '' ?>" title="Pusat Notifikasi &amp; Alarm">
                        <div class="dock-icon">
                            <i class="ph-bold ph-bell"></i>
                            <div class="badge" id="dock-notif-badge">
                                <?= $badgeCount ?>
                            </div>
                        </div>
                        <span class="dock-label">Notif</span>
                    </a>

                    <!-- 6. Setting (Available for ALL users) -->
                    <a href="<?= base_url('settings') ?>" class="dock-item <?= $activeNav == 'settings' ? 'active' : '' ?>" title="Pengaturan Profil, Password &amp; Preferensi">
                        <div class="dock-icon">
                            <i class="ph-bold ph-gear"></i>
                        </div>
                        <span class="dock-label">Setting</span>
                    </a>
                    
                    <!-- Admin Only Items -->
                    <?php if ($userType === 'Admin'): ?>
                    <div class="dock-separator"></div>

                    <!-- 7. Admin Panel -->
                    <a href="<?= base_url('admin-panel') ?>" class="dock-item <?= $activeNav == 'admin_panel' ? 'active' : '' ?>" title="Admin Console &amp; Verification">
                        <div class="dock-icon">
                            <i class="ph-bold ph-shield-check"></i>
                        </div>
                        <span class="dock-label">Admin</span>
                    </a>
                    
                    <!-- 8. Reports -->
                    <a href="<?= base_url('reports') ?>" class="dock-item <?= $activeNav == 'reports' ? 'active' : '' ?>" title="Reports &amp; Compliance">
                        <div class="dock-icon">
                            <i class="ph-bold ph-chart-bar"></i>
                        </div>
                        <span class="dock-label">Reports</span>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Dock Footer Widget -->
                <div class="dock-status-widget">
                    <div class="pulse-dot"></div>
                    <span class="dock-status-txt">ONLINE</span>
                </div>
            </nav>
            
            <!-- App Content Area -->
            <main class="app-area">
                <div class="app-window" id="main-app-window">
                    <!-- Clean Breadcrumb Sub-Header Bar (SunFish Style) -->
                    <div class="window-header">
                        <div class="sunfish-breadcrumb-bar w-full" style="margin-bottom: 0;">
                            <div class="breadcrumb-path">
                                <span>Training</span>
                                <span class="breadcrumb-sep">&gt;</span>
                                <span class="breadcrumb-current"><?php 
                                    if ($activeNav == 'home') echo "Portal Overview";
                                    elseif ($activeNav == 'dashboard') echo ($userType === 'Admin') ? "Executive Dashboard Monitoring" : "Personal Dashboard";
                                    elseif ($activeNav == 'learning_bank') echo "Manage Training Course";
                                    elseif ($activeNav == 'my_learning') echo "My Training Track";
                                    elseif ($activeNav == 'notifikasi') echo "Notifications & Alert Center";
                                    elseif ($activeNav == 'settings') echo "Profile & Preferences";
                                    elseif ($activeNav == 'admin_panel') echo "Compliance & Verification";
                                    elseif ($activeNav == 'reports') echo "Training & Compliance Reports";
                                ?></span>
                            </div>
                            <div class="sunfish-top-actions">
                                <?php if ($userType === 'Admin'): ?>
                                    <button class="btn-pill-teal" onclick="window.location.href='<?= base_url('learning-bank') ?>'"><i class="ph-bold ph-plus"></i> Add</button>
                                    <button class="btn-pill-outline" onclick="window.location.href='<?= base_url('reports') ?>'"><i class="ph-bold ph-dots-three"></i> More</button>
                                <?php else: ?>
                                    <button class="btn-pill-teal" onclick="openNewActivityModal()"><i class="ph-bold ph-plus"></i> Add</button>
                                    <button class="btn-pill-outline" onclick="window.location.href='<?= base_url('learning-bank') ?>'"><i class="ph-bold ph-dots-three"></i> More</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Live ACC Alert Banner -->
                    <?php if ($userType === 'Admin'): ?>
                    <div id="admin-acc-banner-container" class="admin-acc-banner-container" style="display: none;">
                        <div class="admin-acc-alert-box">
                            <div class="acc-bell-wrapper">
                                <i class="ph-fill ph-bell-ringing acc-bell-swing"></i>
                                <span class="acc-bell-pulse-ring"></span>
                            </div>
                            <div class="acc-content-wrapper">
                                <div class="acc-tag-line">
                                    <span class="acc-tag-badge"><i class="ph-bold ph-warning"></i> PERLU ACC ADMIN</span>
                                    <span class="acc-time-text" id="acc-banner-time">Baru saja diajukan</span>
                                </div>
                                <h4 class="acc-headline" id="acc-banner-headline">
                                    Pengajuan Kegiatan Baru Memerlukan Persetujuan (ACC)
                                </h4>
                                <p class="acc-subtext" id="acc-banner-subtext">
                                    Pegawai <strong id="acc-emp-name">Pegawai</strong> baru saja mengajukan kegiatan <strong id="acc-act-title">"Pelatihan Baru"</strong>. Kegiatan ini harus di-ACC agar modul pembelajaran dapat diakses oleh pegawai.
                                </p>
                            </div>
                            <div class="acc-actions-wrapper">
                                <button class="btn-acc-primary" id="btn-quick-acc" onclick="handleQuickAcc()">
                                    <i class="ph-fill ph-check-circle"></i> ACC / Setujui Sekarang
                                </button>
                                <a href="<?= base_url('admin-panel') ?>" class="btn-acc-secondary">
                                    <i class="ph-fill ph-arrow-square-out"></i> Buka Admin Console
                                </a>
                                <button class="btn-acc-close" onclick="dismissAccBanner()" title="Tutup Notifikasi">
                                    <i class="ph ph-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="window-content">
                        <?= $this->renderSection('content') ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Global Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Global Modal Container -->
    <div id="global-modal" class="modal-backdrop" style="display: none;">
        <div class="modal-box glass-panel">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <i id="modal-icon" class="ph-fill ph-info"></i>
                    <h3 id="modal-title">Judul Modal</h3>
                </div>
                <button class="modal-close-btn" onclick="closeModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Dynamic Content -->
            </div>
            <div class="modal-footer" id="modal-footer">
                <button class="btn-light" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Initial Environment & Pending Activities for Realtime Notifications -->
    <script>
        window.BASE_URL = <?= json_encode(rtrim(base_url(), '/') . '/') ?>;
        window.CURRENT_USER_TYPE = <?= json_encode($userType) ?>;
        window.CURRENT_USERNAME = <?= json_encode($username) ?>;
        window.CURRENT_USER_NAME = <?= json_encode($userName) ?>;
        window.ALL_SYSTEM_USERS = <?= json_encode(array_values(get_all_users())) ?>;
        window.INITIAL_PENDING_ACTIVITIES = <?= json_encode(($userType === 'Admin') ? get_activities(null, 'menunggu_konfirmasi') : []) ?>;
        window.USER_CONTACTS = <?php
            $uList = get_all_users();
            $contacts = [];
            foreach ($uList as $u) {
                $c = [
                    'username' => $u['username'],
                    'name'     => $u['name'],
                    'phone'    => $u['phone'] ?? '',
                    'dept'     => $u['dept'] ?? '',
                    'role'     => $u['role'] ?? ''
                ];
                $contacts[$u['username']] = $c;
                $contacts[$u['name']]     = $c;
            }
            echo json_encode($contacts);
        ?>;
        window.GATEWAY_CONFIG = <?= json_encode(get_gateway_config()) ?>;
    </script>

    <!-- Scripts -->
    <script src="<?= base_url('assets/js/script.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/script.js') ?>"></script>
</body>
</html>

