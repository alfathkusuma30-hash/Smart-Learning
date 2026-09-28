<?php
session_start();
require_once __DIR__ . '/includes/data.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Keep active session aligned with persistent data
$activeUser = get_user_by_username($_SESSION['username']);
if ($activeUser) {
    $_SESSION['user_name'] = $activeUser['name'];
    $_SESSION['user_role'] = $activeUser['role'];
    $_SESSION['user_dept'] = $activeUser['dept'];
    $_SESSION['avatar_color'] = $activeUser['avatar_color'];
    $_SESSION['user_type'] = $activeUser['type'];
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['home', 'dashboard', 'learning_bank', 'my_learning', 'notifikasi', 'admin_panel', 'reports', 'settings'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Count pending requests or notifications for badge
$badgeCount = 2;
if ($_SESSION['user_type'] === 'Admin') {
    $pendingActs = get_activities(null, 'menunggu_konfirmasi');
    $badgeCount = 2 + count($pendingActs);
} else {
    $userNotifs = get_notifications_for_user($_SESSION['username'], 'User');
    $userPending = get_activities($_SESSION['username'], 'menunggu_konfirmasi');
    $badgeCount = 2 + count($userNotifs) + count($userPending);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Learning Windows — <?php echo ucfirst(str_replace('_', ' ', $page)); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="os-body">

    <!-- Ambient Desktop Background -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>

    <div class="desktop-env">
        <!-- Topbar (SunFish / ANTAM WorkPlaze Style) -->
        <header class="topbar">
            <div class="topbar-left">
                <a href="?page=dashboard" class="brand-link" title="Ke Dashboard Utama">
                    <div class="brand-icon-sunfish">
                        <i class="ph-bold ph-leaf"></i>
                    </div>
                    <span class="brand-title">Smart Learning</span>
                </a>

                <!-- Global Search Input (SunFish Style) -->
                <div class="header-search-bar">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="global-search-input" placeholder="Search for Employees & Functions...">
                    <span class="kbd-shortcut">Ctrl+K</span>
                </div>
            </div>
            
            <div class="topbar-center">
                <!-- Clock & Date Display -->
                <div class="system-clock-box">
                    <i class="ph-bold ph-calendar-blank text-accent"></i>
                    <span id="sys-time" class="font-mono">12 Agu 2026</span>
                </div>
            </div>
            
            <div class="topbar-right">
                <!-- App Grid Launcher -->
                <a href="?page=home" class="topbar-action-icon" title="Menu Sistem">
                    <i class="ph-bold ph-squares-four"></i>
                </a>

                <!-- Notification Mail / Alarm with Red Badge Pill -->
                <a href="?page=notifikasi" class="topbar-action-icon" title="Pusat Notifikasi & Alarm">
                    <i class="ph-bold ph-bell"></i>
                    <span class="topbar-notif-badge" id="topbar-notif-pill"><?php echo $badgeCount > 9 ? '99+' : $badgeCount; ?></span>
                </a>

                <!-- ANTAM Corporate Brand -->
                <div class="antam-topbar-brand">
                    <i class="ph-fill ph-triangle antam-mountain-icon"></i>
                    <div class="antam-text-logo">antam<span>.</span></div>
                </div>

                <!-- User Profile Badge & Quick Settings -->
                <a href="?page=settings" class="user-profile-badge" title="Pengaturan Profil & Password">
                    <div class="user-meta-compact">
                        <span class="user-fullname"><?php echo strtoupper(htmlspecialchars($_SESSION['user_name'])); ?></span>
                        <span class="user-division">PT ANTAM Tbk</span>
                    </div>
                    <div class="mini-avatar" style="background: <?php echo isset($_SESSION['avatar_color']) ? $_SESSION['avatar_color'] : 'var(--accent)'; ?>">
                        <?php 
                        $nameParts = explode(' ', $_SESSION['user_name']);
                        echo count($nameParts) > 1 ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1)) : strtoupper(substr($_SESSION['user_name'], 0, 2));
                        ?>
                    </div>
                </a>

                <a href="logout.php" class="logout-btn" title="Ganti Akun / Logout">
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
                    <a href="?page=home" class="dock-item <?php echo $page == 'home' ? 'active' : ''; ?>" title="Home Desktop">
                        <div class="dock-icon">
                            <i class="ph-bold ph-house"></i>
                        </div>
                        <span class="dock-label">Home</span>
                    </a>

                    <!-- 2. Dashboard -->
                    <a href="?page=dashboard" class="dock-item <?php echo $page == 'dashboard' ? 'active' : ''; ?>" title="Personal Dashboard">
                        <div class="dock-icon">
                            <i class="ph-bold ph-user"></i>
                        </div>
                        <span class="dock-label">Profile</span>
                    </a>
                    
                    <!-- 3. Learning Bank (Catalog) -->
                    <a href="?page=learning_bank" class="dock-item <?php echo $page == 'learning_bank' ? 'active' : ''; ?>" title="Manage Training Course">
                        <div class="dock-icon">
                            <i class="ph-bold ph-clock"></i>
                        </div>
                        <span class="dock-label">Catalog</span>
                    </a>
                    
                    <!-- 4. My Learning Track (Graduation Cap) -->
                    <a href="?page=my_learning" class="dock-item <?php echo $page == 'my_learning' ? 'active' : ''; ?>" title="My Training Track">
                        <div class="dock-icon">
                            <i class="ph-bold ph-graduation-cap"></i>
                        </div>
                        <span class="dock-label">Training</span>
                    </a>
                    
                    <!-- 5. Notifikasi (Alarm Hub) -->
                    <a href="?page=notifikasi" class="dock-item <?php echo $page == 'notifikasi' ? 'active' : ''; ?>" title="Pusat Notifikasi & Alarm">
                        <div class="dock-icon">
                            <i class="ph-bold ph-bell"></i>
                            <div class="badge" id="dock-notif-badge">
                                <?php echo $badgeCount; ?>
                            </div>
                        </div>
                        <span class="dock-label">Notif</span>
                    </a>

                    <!-- 6. Setting (Available for ALL users) -->
                    <a href="?page=settings" class="dock-item <?php echo $page == 'settings' ? 'active' : ''; ?>" title="Pengaturan Profil, Password & Preferensi">
                        <div class="dock-icon">
                            <i class="ph-bold ph-gear"></i>
                        </div>
                        <span class="dock-label">Setting</span>
                    </a>
                    
                    <!-- Admin Only Items -->
                    <?php if($_SESSION['user_type'] == 'Admin'): ?>
                    <div class="dock-separator"></div>

                    <!-- 7. Admin Panel -->
                    <a href="?page=admin_panel" class="dock-item <?php echo $page == 'admin_panel' ? 'active' : ''; ?>" title="Admin Console & Verification">
                        <div class="dock-icon">
                            <i class="ph-bold ph-shield-check"></i>
                        </div>
                        <span class="dock-label">Admin</span>
                    </a>
                    
                    <!-- 8. Reports -->
                    <a href="?page=reports" class="dock-item <?php echo $page == 'reports' ? 'active' : ''; ?>" title="Reports & Compliance">
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
                                    if($page == 'home') echo "Portal Overview";
                                    elseif($page == 'dashboard') echo "Personal Dashboard";
                                    elseif($page == 'learning_bank') echo "Manage Training Course";
                                    elseif($page == 'my_learning') echo "My Training Track";
                                    elseif($page == 'notifikasi') echo "Notifications & Alert Center";
                                    elseif($page == 'settings') echo "Profile & Preferences";
                                    elseif($page == 'admin_panel') echo "Compliance & Verification";
                                    elseif($page == 'reports') echo "Training & Compliance Reports";
                                ?></span>
                            </div>
                            <div class="sunfish-top-actions">
                                <?php if ($_SESSION['user_type'] === 'Admin'): ?>
                                    <button class="btn-pill-teal" onclick="window.location.href='?page=learning_bank'"><i class="ph-bold ph-plus"></i> + Add</button>
                                    <button class="btn-pill-outline" onclick="window.location.href='?page=reports'"><i class="ph-bold ph-dots-three"></i> More</button>
                                <?php else: ?>
                                    <button class="btn-pill-teal" onclick="openNewActivityModal()"><i class="ph-bold ph-plus"></i> + Add</button>
                                    <button class="btn-pill-outline" onclick="window.location.href='?page=learning_bank'"><i class="ph-bold ph-dots-three"></i> More</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Live ACC Alert Banner (Menampilkan Notifikasi Bahwa Ada Kegiatan Masuk & Harus di-ACC) -->
                    <?php if ($_SESSION['user_type'] === 'Admin'): ?>
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
                                <a href="?page=admin_panel" class="btn-acc-secondary">
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
                        <?php include "pages/{$page}.php"; ?>
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
        window.CURRENT_USER_TYPE = <?php echo json_encode($_SESSION['user_type'] ?? 'User'); ?>;
        window.CURRENT_USERNAME = <?php echo json_encode($_SESSION['username'] ?? ''); ?>;
        window.INITIAL_PENDING_ACTIVITIES = <?php echo json_encode(($_SESSION['user_type'] === 'Admin') ? get_activities(null, 'menunggu_konfirmasi') : []); ?>;
    </script>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
</body>
</html>
