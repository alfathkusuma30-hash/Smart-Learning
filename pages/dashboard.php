<?php
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];

// Get activities
$allActivities = get_activities();
$allPending = array_filter($allActivities, function($a) { return $a['status'] === 'menunggu_konfirmasi'; });
$allApproved = array_filter($allActivities, function($a) { return $a['status'] === 'disetujui'; });

// User specific activities (for employee)
$userActivities = get_activities($username);
$userApproved = array_filter($userActivities, function($a) { return $a['status'] === 'disetujui'; });
$userPending = array_filter($userActivities, function($a) { return $a['status'] === 'menunggu_konfirmasi'; });

// Calculate dynamic progress for employee
$totalModules = 0;
$completedModules = 0;
foreach ($userApproved as $act) {
    $totalModules += isset($act['total_modules']) ? $act['total_modules'] : 4;
    $completedModules += isset($act['completed_modules']) ? $act['completed_modules'] : 0;
}
$progressPercent = ($totalModules > 0) ? round(($completedModules / $totalModules) * 100) : 0;

$lnaList = get_lna_catalog();
?>

<div class="dashboard-container">
    <!-- Top Header -->
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="page-title"><?php echo $user_type === 'Admin' ? 'Executive Dashboard Monitoring' : 'Personal Dashboard'; ?></h1>
                <span class="badge-role-tag"><?php echo strtoupper($user_type); ?> · SITE PONGKOR</span>
            </div>
        </div>
        <div class="page-top-actions">
            <?php if ($user_type === 'Admin'): ?>
                <a href="?page=admin_panel" class="btn-dark">
                    <i class="ph-bold ph-stamp"></i>
                    <span>Verifikasi Usulan (<?php echo count($allPending); ?>)</span>
                </a>
                <a href="?page=reports" class="btn-outline">
                    <i class="ph-fill ph-chart-bar"></i>
                    <span>Laporan & Audit</span>
                </a>
            <?php else: ?>
                <button class="btn-dark" onclick="openNewActivityModal()">
                    <i class="ph-bold ph-plus-circle"></i>
                    <span>+ Ajukan Kegiatan Baru</span>
                </button>
            <?php endif; ?>
            <span class="status-chip-live">
                <span class="live-dot"></span>
                <span>Normal · Online</span>
            </span>
        </div>
    </div>
    
    <!-- Top Stats 3-Col Grid -->
    <div class="dash-stats-grid mb-6">
        <?php if ($user_type === 'Admin'): ?>
            <!-- Admin Stat 1: Kepatuhan Site -->
            <div class="stat-card">
                <div class="stat-card-title">KEPATUHAN K3 SITE PONGKOR</div>
                <div class="stat-value text-accent">81.3%</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 81.3%; background: #10b981;"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-muted mt-2">
                    <span>Standar Regulasi ESDM & ISO 45001</span>
                    <span class="font-mono text-accent">Target: 80%</span>
                </div>
            </div>
            
            <!-- Admin Stat 2: Usulan Kegiatan Masuk -->
            <div class="stat-card">
                <div class="stat-card-title">USULAN KEGIATAN MASUK</div>
                <div class="stat-value text-amber"><?php echo count($allPending); ?></div>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <?php if (count($allPending) > 0): ?>
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-hourglass-high"></i> <?php echo count($allPending); ?> Menunggu Konfirmasi Anda
                    </span>
                    <?php else: ?>
                    <span class="status-badge status-success text-xs">
                        <i class="ph-fill ph-check-circle"></i> Semua Usulan Telah Diproses
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Admin Stat 3: Kegiatan Pegawai Berjalan -->
            <div class="stat-card">
                <div class="stat-card-title">KEGIATAN PEGAWAI BERJALAN</div>
                <div class="stat-value text-sky"><?php echo count($allApproved); ?></div>
                <div class="flex items-center gap-2 mt-2">
                    <span class="status-badge status-success text-xs">
                        <i class="ph-fill ph-users"></i> Dipantau Realtime Site Pongkor
                    </span>
                </div>
            </div>

        <?php else: ?>
            <!-- Pegawai Stat 1: Progres Belajar -->
            <div class="stat-card">
                <div class="stat-card-title">PROGRES BELAJAR KESELURUHAN</div>
                <div class="stat-value text-accent"><?php echo $progressPercent; ?>%</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $progressPercent; ?>%;"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-muted mt-2">
                    <span><?php echo $completedModules; ?> / <?php echo $totalModules; ?> Modul Tuntas</span>
                    <span class="font-mono text-accent">Target: 100%</span>
                </div>
            </div>
            
            <!-- Pegawai Stat 2: Modul Aktif -->
            <div class="stat-card">
                <div class="stat-card-title">KEGIATAN / MODUL AKTIF</div>
                <div class="stat-value"><?php echo count($userApproved); ?></div>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="status-badge status-success text-xs">
                        <i class="ph-fill ph-check-circle"></i> <?php echo count($userApproved); ?> Disetujui
                    </span>
                    <?php if (count($userPending) > 0): ?>
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-hourglass-high"></i> <?php echo count($userPending); ?> Menunggu Admin
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pegawai Stat 3: Status Alarm & Kepatuhan -->
            <div class="stat-card">
                <div class="stat-card-title">STATUS ALARM K3 & LNA</div>
                <div class="stat-value text-amber">1</div>
                <div class="flex items-center gap-2 mt-2">
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-bell-ringing"></i> Alarm Dua Arah Aktif
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions Bar -->
    <div class="quick-action-strip glass-panel mb-6">
        <div class="quick-action-left">
            <i class="ph-fill ph-lightning text-accent" style="font-size: 1.5rem;"></i>
            <div>
                <strong style="font-size: 0.95rem;"><?php echo $user_type === 'Admin' ? 'Aksi Cepat Admin' : 'Aksi Cepat'; ?></strong>
            </div>
        </div>
        <div class="quick-action-buttons">
            <?php if ($user_type === 'Admin'): ?>
                <a href="?page=admin_panel" class="btn-dark">
                    <i class="ph-bold ph-stamp"></i>
                    <span>Verifikasi Usulan</span>
                </a>
                <a href="?page=admin_panel" class="btn-outline">
                    <i class="ph-fill ph-users"></i>
                    <span>Monitoring Pegawai</span>
                </a>
                <a href="?page=reports" class="btn-outline">
                    <i class="ph-fill ph-chart-bar"></i>
                    <span>Laporan & Audit</span>
                </a>
                <button class="btn-outline" onclick="openAssignTrainingModal()">
                    <i class="ph-fill ph-user-plus"></i>
                    <span>Tugaskan Modul</span>
                </button>
            <?php else: ?>
                <button class="btn-dark" onclick="openNewActivityModal()">
                    <i class="ph-bold ph-plus"></i>
                    <span>Ajukan Kegiatan Baru</span>
                </button>
                <a href="?page=my_learning" class="btn-outline">
                    <i class="ph-fill ph-graduation-cap"></i>
                    <span>My Learning Track</span>
                </a>
                <a href="?page=notifikasi" class="btn-outline">
                    <i class="ph-fill ph-bell"></i>
                    <span>Pusat Notifikasi</span>
                </a>
                <a href="?page=settings" class="btn-outline">
                    <i class="ph-fill ph-gear"></i>
                    <span>Setting Profil</span>
                </a>
            <?php endif; ?>
        </div>
    </div>


    <!-- ======================================================= -->
    <!-- SECTION UNTUK ADMIN: LAPORAN KEGIATAN PEGAWAI SITE      -->
    <!-- ======================================================= -->
    <?php if ($user_type === 'Admin'): ?>
        
        <?php if (count($allPending) > 0): ?>
        <div class="admin-notice-card glass-panel mb-6 p-4 flex items-center justify-between flex-wrap gap-3" style="background: #fffbeb; border: 1px solid #fef08a; border-left: 5px solid #f59e0b; border-radius: var(--radius-md);">
            <div class="flex items-center gap-3">
                <div class="mini-avatar" style="background: #fef3c7; color: #b45309; width: 44px; height: 44px; font-size: 1.4rem;">
                    <i class="ph-fill ph-hourglass-high"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-base text-dark">Terdapat <?php echo count($allPending); ?> Usulan Kegiatan Baru dari Pegawai</h3>
                </div>
            </div>
            <div>
                <a href="?page=admin_panel" class="btn-dark text-xs">
                    <i class="ph-fill ph-stamp"></i>
                    <span>Tinjau & Konfirmasi Sekarang</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="section-divider-title mb-4">
            <div class="divider-left">
                <i class="ph-fill ph-clipboard-text text-accent"></i>
                <h2>Laporan Kegiatan Belajar Pegawai Terkini</h2>
                <span class="section-tag-count"><?php echo count($allActivities); ?> Total Kegiatan</span>
            </div>
            <a href="?page=admin_panel" class="btn-outline text-xs">
                <i class="ph-fill ph-arrow-square-out"></i> Buka Admin Console Penuh
            </a>
        </div>

        <div class="glass-panel mb-6 table-responsive" style="background: white; border-radius: var(--radius-md); overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NAMA PEGAWAI</th>
                        <th>KEGIATAN / MODUL</th>
                        <th>KATEGORI</th>
                        <th>DURASI</th>
                        <th>STATUS LAPORAN</th>
                        <th style="text-align: right;">AKSI ADMIN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allActivities as $act): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <?php
                                $colors = ['admin' => '#10b981', 'egy' => '#d4af37', 'budi' => '#38bdf8', 'andi' => '#a855f7'];
                                $avatar_color = $colors[strtolower($act['user_username'] ?? '')] ?? '#0f766e';
                                $initials = '';
                                $parts = explode(' ', $act['user_name'] ?? '');
                                foreach ($parts as $p) { $initials .= substr($p, 0, 1); }
                                $initials = strtoupper(substr($initials, 0, 2));
                                ?>
                                <div class="mini-avatar" style="background: <?php echo $avatar_color; ?>; width: 30px; height: 30px; font-size: 0.75rem;">
                                    <?php echo $initials ?: 'U'; ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-sm"><?php echo htmlspecialchars($act['user_name']); ?></div>
                                    <div class="text-xs text-muted font-mono">@<?php echo htmlspecialchars($act['user_username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-semibold text-sm text-dark"><?php echo htmlspecialchars($act['title']); ?></div>
                            <div class="text-xs text-muted"><?php echo htmlspecialchars($act['current_module']); ?></div>
                        </td>
                        <td>
                            <span class="tag tag-gray"><?php echo htmlspecialchars($act['category']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($act['duration']); ?></td>
                        <td>
                            <?php if ($act['status'] === 'disetujui'): ?>
                                <span class="status-badge status-success text-xs"><i class="ph-fill ph-check-circle"></i> Berjalan (Disetujui)</span>
                            <?php else: ?>
                                <span class="status-badge status-warning text-xs"><i class="ph-fill ph-hourglass"></i> Menunggu Konfirmasi</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($act['status'] === 'menunggu_konfirmasi'): ?>
                                <a href="?page=admin_panel" class="btn-dark text-xs">
                                    <i class="ph-fill ph-stamp"></i> Konfirmasi
                                </a>
                            <?php else: ?>
                                <button class="btn-outline text-xs" onclick="sendWhatsAppNudge('<?php echo addslashes($act['user_name']); ?>', '<?php echo addslashes($act['title']); ?>')">
                                    <i class="ph-fill ph-whatsapp-logo text-green"></i> Pantau
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <!-- ======================================================= -->
    <!-- SECTION UNTUK PEGAWAI: KEGIATAN BELAJAR SAYA            -->
    <!-- ======================================================= -->
    <?php else: ?>
        <div class="section-divider-title mb-4">
            <div class="divider-left">
                <i class="ph-fill ph-graduation-cap text-accent"></i>
                <h2>Kegiatan Belajar Saya</h2>
                <span class="section-tag-count"><?php echo count($userActivities); ?> Terdaftar</span>
            </div>
            <button class="btn-outline text-xs" onclick="openNewActivityModal()">
                <i class="ph-fill ph-plus"></i> Tambah Kegiatan
            </button>
        </div>

        <?php if (count($userActivities) === 0): ?>
        <div class="empty-state-box glass-panel mb-6 text-center p-6">
            <i class="ph-fill ph-books text-muted" style="font-size: 3rem; margin-bottom: 0.5rem;"></i>
            <h4 class="font-semibold">Belum Ada Kegiatan Belajar</h4>
            <p class="text-sm text-muted mb-4">Pilih materi dari Rekomendasi LNA di bawah atau ajukan kegiatan pelatihan baru.</p>
            <button class="btn-dark" onclick="openNewActivityModal()">
                <i class="ph-bold ph-plus"></i> Ajukan Kegiatan Baru
            </button>
        </div>
        <?php else: ?>
        <div class="active-activities-stream mb-6">
            <?php foreach ($userActivities as $activity): ?>
            <div class="activity-live-card glass-panel <?php echo $activity['status'] === 'menunggu_konfirmasi' ? 'status-border-pending' : 'status-border-approved'; ?>">
                <div class="activity-card-header">
                    <div class="activity-tags-row">
                        <span class="tag <?php echo $activity['status'] === 'disetujui' ? 'tag-teal' : 'tag-amber'; ?>">
                            <i class="ph-fill <?php echo $activity['status'] === 'disetujui' ? 'ph-check-circle' : 'ph-hourglass-medium'; ?>"></i>
                            <?php echo $activity['status'] === 'disetujui' ? 'Aktif & Disetujui' : 'Menunggu Konfirmasi Admin'; ?>
                        </span>
                        <span class="tag tag-gray"><?php echo htmlspecialchars($activity['category']); ?></span>
                        <span class="tag tag-gray font-mono"><i class="ph-fill ph-clock"></i> <?php echo htmlspecialchars($activity['duration']); ?></span>
                    </div>
                    <div class="activity-deadline-tag">
                        <i class="ph-fill ph-calendar"></i>
                        <span>Target: <strong><?php echo htmlspecialchars($activity['deadline']); ?></strong></span>
                    </div>
                </div>

                <div class="activity-card-body">
                    <h3 class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></h3>
                    <p class="activity-notes text-sm text-muted">
                        <?php echo htmlspecialchars($activity['notes']); ?>
                    </p>
                    
                    <div class="activity-progress-wrap mt-3">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-dark">
                                <i class="ph-fill ph-flag-banner text-accent"></i>
                                <?php echo htmlspecialchars($activity['current_module']); ?>
                            </span>
                            <span class="font-mono font-bold text-accent"><?php echo $activity['progress']; ?>%</span>
                        </div>
                        <div class="progress-bar-container" style="height: 7px; margin-top: 0;">
                            <div class="progress-bar" style="width: <?php echo $activity['progress']; ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="activity-card-footer">
                    <span class="text-xs text-muted">
                        Diajukan: <?php echo date('d M Y', strtotime($activity['created_at'])); ?>
                        <?php if (!empty($activity['approved_at'])): ?>
                        · Dikonfirmasi Admin: <?php echo date('d M Y H:i', strtotime($activity['approved_at'])); ?>
                        <?php endif; ?>
                    </span>
                    <div class="flex gap-2">
                        <?php if ($activity['status'] === 'disetujui'): ?>
                        <a href="?page=my_learning" class="btn-dark text-xs">
                            <i class="ph-fill ph-play"></i> Lanjutkan Belajar
                        </a>
                        <?php else: ?>
                        <span class="badge-pending-pill text-xs">
                            <i class="ph-fill ph-shield-check"></i> Menunggu Approval Admin Console
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>


    <!-- ======================================================= -->
    <!-- REKOMENDASI LEARNING NEEDS ANALYSIS (LNA)               -->
    <!-- ======================================================= -->
    <div class="lna-section-header">
        <div class="lna-title-group">
            <div class="lna-icon-badge">
                <i class="ph-fill ph-crosshair text-accent"></i>
            </div>
            <div>
                <h2 class="lna-main-heading">
                    <?php echo $user_type === 'Admin' ? 'Katalog Rekomendasi Kurikulum LNA Site' : 'Rekomendasi LNA (Learning Needs Analysis)'; ?>
                </h2>
            </div>
        </div>
        <div class="lna-actions">
            <span class="lna-compliance-chip">
                <i class="ph-fill ph-shield-star text-gold"></i>
                <span>Kepatuhan Site: <strong>81.3%</strong></span>
            </span>
        </div>
    </div>

    <!-- LNA Gap Analysis Overview Banner -->
    <div class="lna-radar-overview glass-panel mb-6">
        <div class="lna-overview-left">
            <div class="lna-badge-pill">
                <i class="ph-fill ph-chart-polar"></i>
                <span><?php echo $user_type === 'Admin' ? 'STATUS KEPATUHAN KORPORAT' : 'MATRIKS GAP KOMPETENSI ANDA'; ?></span>
            </div>
            <h3 class="lna-radar-title">Pemenuhan Standar K3 & Operasional Site</h3>
        </div>

        <div class="lna-metrics-grid">
            <div class="lna-metric-box">
                <div class="metric-top">
                    <span class="metric-name">K3 Underground</span>
                    <span class="metric-pct text-red">60%</span>
                </div>
                <div class="metric-bar-wrap">
                    <div class="metric-bar bg-red" style="width: 60%;"></div>
                </div>
                <span class="metric-sub text-red"><i class="ph-fill ph-warning"></i> Gap Kritis (Induction)</span>
            </div>

            <div class="lna-metric-box">
                <div class="metric-top">
                    <span class="metric-name">Regulasi ESDM</span>
                    <span class="metric-pct text-amber">70%</span>
                </div>
                <div class="metric-bar-wrap">
                    <div class="metric-bar bg-amber" style="width: 70%;"></div>
                </div>
                <span class="metric-sub text-amber"><i class="ph-fill ph-clock"></i> POP Level 1</span>
            </div>

            <div class="lna-metric-box">
                <div class="metric-top">
                    <span class="metric-name">Lisensi Nasional</span>
                    <span class="metric-pct text-teal">90%</span>
                </div>
                <div class="metric-bar-wrap">
                    <div class="metric-bar bg-teal" style="width: 90%;"></div>
                </div>
                <span class="metric-sub text-teal"><i class="ph-fill ph-check"></i> K3 Umum Valid</span>
            </div>
        </div>
    </div>

    <!-- Category Filter for LNA -->
    <div class="lna-filter-row mb-4">
        <div class="filter-chips-group">
            <button class="lna-filter-chip active" onclick="filterLnaCategory(this, 'all')">
                <i class="ph-fill ph-squares-four"></i> Semua (<?php echo count($lnaList); ?>)
            </button>
            <button class="lna-filter-chip" onclick="filterLnaCategory(this, 'Wajib')">
                <i class="ph-fill ph-warning-circle text-red"></i> Wajib ESDM
            </button>
            <button class="lna-filter-chip" onclick="filterLnaCategory(this, 'Sertifikasi')">
                <i class="ph-fill ph-certificate text-teal"></i> Sertifikasi
            </button>
            <button class="lna-filter-chip" onclick="filterLnaCategory(this, 'Optional')">
                <i class="ph-fill ph-sparkle text-amber"></i> Pengembangan
            </button>
        </div>
    </div>

    <!-- LNA Recommendations Grid (Modern Rich Cards) -->
    <div class="lna-cards-grid mb-6" id="lna-courses-container">
        <?php foreach ($lnaList as $lna): ?>
        <?php 
            $tagColor = ($lna['type'] === 'Wajib') ? 'tag-red' : (($lna['type'] === 'Sertifikasi') ? 'tag-teal' : 'tag-gray');
            $borderAccent = ($lna['type'] === 'Wajib') ? 'border-l-red' : (($lna['type'] === 'Sertifikasi') ? 'border-l-teal' : 'border-l-gold');
        ?>
        <div class="lna-modern-card glass-panel <?php echo $borderAccent; ?>" data-lna-type="<?php echo $lna['type']; ?>">
            
            <div class="lna-card-topbar">
                <div class="flex items-center gap-2">
                    <span class="tag <?php echo $tagColor; ?>">
                        <i class="ph-fill <?php echo $lna['type'] === 'Wajib' ? 'ph-shield-warning' : ($lna['type'] === 'Sertifikasi' ? 'ph-seal-check' : 'ph-book-open'); ?>"></i>
                        <?php echo htmlspecialchars($lna['type']); ?>
                    </span>
                    <span class="lna-code-tag font-mono"><?php echo htmlspecialchars($lna['code']); ?></span>
                </div>
                <div class="lna-gap-indicator <?php echo $lna['type'] === 'Wajib' ? 'gap-high' : 'gap-normal'; ?>" title="<?php echo htmlspecialchars($lna['gap_score']); ?>">
                    <i class="ph-fill <?php echo $lna['type'] === 'Wajib' ? 'ph-warning-circle' : 'ph-check-circle'; ?>"></i>
                    <span><?php echo $lna['type'] === 'Wajib' ? 'Prioritas' : ($lna['type'] === 'Sertifikasi' ? 'Lisensi' : 'Pilihan'); ?></span>
                </div>
            </div>

            <div class="lna-card-main">
                <h3 class="lna-card-title"><?php echo htmlspecialchars($lna['title']); ?></h3>

                <!-- Icon-Driven Metadata (No verbose labels) -->
                <div class="lna-meta-strip">
                    <span class="lna-meta-chip" title="Durasi: <?php echo htmlspecialchars($lna['duration']); ?>">
                        <i class="ph-fill ph-clock"></i>
                        <span><?php echo htmlspecialchars($lna['duration']); ?></span>
                    </span>
                    <span class="lna-meta-chip" title="Tingkat: <?php echo htmlspecialchars($lna['level']); ?>">
                        <i class="ph-fill ph-chart-bar"></i>
                        <span><?php echo htmlspecialchars($lna['level']); ?></span>
                    </span>
                    <span class="lna-meta-chip" title="Kompetensi: <?php echo htmlspecialchars($lna['competency_area']); ?>">
                        <i class="ph-fill ph-target"></i>
                        <span><?php echo htmlspecialchars($lna['competency_area']); ?></span>
                    </span>
                </div>
            </div>

            <!-- Icon-Focused Action Bar -->
            <div class="lna-card-action-bar">
                <div class="lna-category-hint font-mono">
                    <i class="ph-fill ph-tag"></i>
                    <span><?php echo htmlspecialchars($lna['category']); ?></span>
                </div>
                <div class="lna-icon-actions">
                    <button class="btn-card-icon btn-outline" title="Lihat Silabus Lengkap" onclick="openCourseDetailModal('<?php echo addslashes($lna['title']); ?>', '<?php echo $lna['type']; ?>', '<?php echo $lna['duration']; ?>', '<?php echo $lna['level']; ?>', '<?php echo addslashes($lna['desc']); ?>')">
                        <i class="ph-bold ph-info"></i>
                    </button>
                    <?php if ($user_type === 'Admin'): ?>
                    <button class="btn-card-icon btn-dark" title="Tugaskan Modul ke Pegawai" onclick="openAssignTrainingModal('<?php echo addslashes($lna['title']); ?>')">
                        <i class="ph-bold ph-user-plus"></i>
                    </button>
                    <?php else: ?>
                    <button class="btn-card-icon btn-dark" title="Ajukan Kegiatan Ini" onclick="openNewActivityModalWithPreset('<?php echo addslashes($lna['title']); ?>', '<?php echo $lna['type']; ?>', '<?php echo $lna['category']; ?>', '<?php echo $lna['duration']; ?>', '<?php echo $lna['level']; ?>')">
                        <i class="ph-bold ph-plus"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Interactive JavaScript for LNA Filtering -->
<script>
function filterLnaCategory(btn, category) {
    document.querySelectorAll('.lna-filter-chip').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.lna-modern-card');
    cards.forEach(card => {
        const cardType = card.getAttribute('data-lna-type');
        if (category === 'all' || cardType === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
