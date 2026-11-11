<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$user_name = $user['name'] ?? session('user_name') ?? 'Pegawai ANTAM';
$user_role = $user['role'] ?? session('user_role') ?? 'Operasional Tambang';
$user_type = $userType ?? session('user_type') ?? 'User';
$username  = $username ?? session('username') ?? 'user';

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
    <!-- Top Header (Clean Minimalist Hierarchy) -->
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="page-title"><?= $user_type === 'Admin' ? 'Executive Dashboard' : 'Dashboard Belajar' ?></h1>
                <span class="badge-role-tag"><?= strtoupper(esc($user_type)) ?> · SITE POMALAA</span>
            </div>
            <p class="text-xs text-muted mt-1">Ringkasan kepatuhan K3 &amp; progres kompetensi pegawai Site Pomalaa</p>
        </div>
        <div class="page-top-actions">
            <?php if ($user_type === 'Admin'): ?>
                <a href="<?= base_url('admin-panel') ?>" class="btn-dark">
                    <i class="ph-bold ph-stamp"></i>
                    <span>Verifikasi Usulan <?= count($allPending) > 0 ? '(' . count($allPending) . ')' : '' ?></span>
                </a>
                <a href="<?= base_url('reports') ?>" class="btn-outline">
                    <i class="ph-fill ph-chart-bar"></i>
                    <span>Laporan &amp; Audit</span>
                </a>
            <?php else: ?>
                <button class="btn-dark" onclick="openNewActivityModal()">
                    <i class="ph-bold ph-plus-circle"></i>
                    <span>+ Ajukan Kegiatan</span>
                </button>
                <a href="<?= base_url('learning-bank') ?>" class="btn-outline">
                    <i class="ph-fill ph-books"></i>
                    <span>Katalog Modul</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Stats 3-Col Grid (80/20 Metric Focus) -->
    <div class="dash-stats-grid mb-6">
        <?php if ($user_type === 'Admin'): ?>
            <!-- Admin Stat 1: Kepatuhan Site -->
            <div class="stat-card">
                <div class="stat-card-title">Kepatuhan K3 Site</div>
                <div class="stat-value text-accent">81.3%</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 81.3%; background: #10b981;"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-muted mt-2">
                    <span>Standar ESDM &amp; ISO 45001</span>
                    <span class="font-mono text-accent font-semibold">Target: 80%</span>
                </div>
            </div>
            
            <!-- Admin Stat 2: Usulan Kegiatan Masuk -->
            <div class="stat-card">
                <div class="stat-card-title">Usulan Masuk</div>
                <div class="stat-value text-amber"><?= count($allPending) ?></div>
                <div class="flex items-center gap-2 mt-2">
                    <?php if (count($allPending) > 0): ?>
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-hourglass-high"></i> Menunggu Verifikasi
                    </span>
                    <?php else: ?>
                    <span class="status-badge status-success text-xs">
                        <i class="ph-fill ph-check-circle"></i> Semua Terverifikasi
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Admin Stat 3: Kegiatan Pegawai Berjalan -->
            <div class="stat-card">
                <div class="stat-card-title">Pelatihan Berjalan</div>
                <div class="stat-value text-sky"><?= count($allApproved) ?></div>
                <div class="flex items-center gap-2 mt-2">
                    <span class="status-badge status-success text-xs">
                        <i class="ph-fill ph-users"></i> <?= count($allApproved) ?> Modul Aktif di Lapangan
                    </span>
                </div>
            </div>

        <?php else: ?>
            <!-- Pegawai Stat 1: Progres Belajar -->
            <div class="stat-card">
                <div class="stat-card-title">Progres Belajar</div>
                <div class="stat-value text-accent"><?= $progressPercent ?>%</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?= $progressPercent ?>%;"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-muted mt-2">
                    <span><?= $completedModules ?> dari <?= $totalModules ?> modul tuntas</span>
                    <span class="font-mono text-accent font-semibold">Target: 100%</span>
                </div>
            </div>
            
            <!-- Pegawai Stat 2: Modul Aktif -->
            <div class="stat-card">
                <div class="stat-card-title">Modul Aktif</div>
                <div class="stat-value"><?= count($userApproved) ?></div>
                <div class="flex items-center gap-2 mt-2 flex-wrap">
                    <span class="status-badge status-success text-xs">
                        <i class="ph-fill ph-check-circle"></i> <?= count($userApproved) ?> Disetujui
                    </span>
                    <?php if (count($userPending) > 0): ?>
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-hourglass-high"></i> <?= count($userPending) ?> Menunggu
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pegawai Stat 3: Status Alarm & Kepatuhan -->
            <div class="stat-card">
                <div class="stat-card-title">Status Alarm K3</div>
                <div class="stat-value text-amber">1</div>
                <div class="flex items-center gap-2 mt-2">
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-bell-ringing"></i> Alarm Aktif
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions Strip (Clean & Focused) -->
    <div class="quick-action-strip glass-panel mb-6">
        <div class="quick-action-left">
            <i class="ph-fill ph-lightning text-accent" style="font-size: 1.35rem;"></i>
            <div>
                <strong style="font-size: 0.9rem;"><?= $user_type === 'Admin' ? 'Aksi Cepat Admin' : 'Aksi Cepat' ?></strong>
            </div>
        </div>
        <div class="quick-action-buttons">
            <?php if ($user_type === 'Admin'): ?>
                <a href="<?= base_url('admin-panel') ?>" class="btn-dark text-xs">
                    <i class="ph-bold ph-stamp"></i>
                    <span>Verifikasi Usulan</span>
                </a>
                <a href="<?= base_url('admin-panel') ?>" class="btn-outline text-xs">
                    <i class="ph-fill ph-users"></i>
                    <span>Monitoring Pegawai</span>
                </a>
                <a href="<?= base_url('reports') ?>" class="btn-outline text-xs">
                    <i class="ph-fill ph-chart-bar"></i>
                    <span>Laporan &amp; Audit</span>
                </a>
                <button class="btn-outline text-xs" onclick="openAssignTrainingModal()">
                    <i class="ph-fill ph-user-plus"></i>
                    <span>Tugaskan Modul</span>
                </button>
            <?php else: ?>
                <button class="btn-dark text-xs" onclick="openNewActivityModal()">
                    <i class="ph-bold ph-plus"></i>
                    <span>Ajukan Kegiatan Baru</span>
                </button>
                <a href="<?= base_url('my-learning') ?>" class="btn-outline text-xs">
                    <i class="ph-fill ph-graduation-cap"></i>
                    <span>My Learning</span>
                </a>
                <a href="<?= base_url('notifikasi') ?>" class="btn-outline text-xs">
                    <i class="ph-fill ph-bell"></i>
                    <span>Notifikasi</span>
                </a>
                <a href="<?= base_url('settings') ?>" class="btn-outline text-xs">
                    <i class="ph-fill ph-gear"></i>
                    <span>Pengaturan</span>
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
                    <h3 class="font-semibold text-base text-dark">Terdapat <?= count($allPending) ?> Usulan Kegiatan Baru dari Pegawai</h3>
                </div>
            </div>
            <div>
                <a href="<?= base_url('admin-panel') ?>" class="btn-dark text-xs">
                    <i class="ph-fill ph-stamp"></i>
                    <span>Tinjau &amp; Konfirmasi Sekarang</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="section-divider-title mb-4">
            <div class="divider-left">
                <i class="ph-fill ph-clipboard-text text-accent"></i>
                <h2>Laporan Kegiatan Belajar Pegawai Terkini</h2>
                <span class="section-tag-count"><?= count($allActivities) ?> Total Kegiatan</span>
            </div>
            <a href="<?= base_url('admin-panel') ?>" class="btn-outline text-xs">
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
                                $actUser = get_user_by_username($act['user_username'] ?? '');
                                ?>
                                <div class="mini-avatar" style="background: <?= $avatar_color ?>; width: 30px; height: 30px; font-size: 0.75rem; overflow: hidden; padding: 0;">
                                    <?php if ($actUser && !empty($actUser['avatar_image']) && file_exists(FCPATH . 'uploads/avatars/' . $actUser['avatar_image'])): ?>
                                        <img src="<?= base_url('uploads/avatars/' . esc($actUser['avatar_image'])) ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                                    <?php else: ?>
                                        <?= $initials ?: 'U' ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-sm"><?= esc($act['user_name']) ?></div>
                                    <div class="text-xs text-muted font-mono">@<?= esc($act['user_username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-semibold text-sm text-dark"><?= esc($act['title']) ?></div>
                            <div class="text-xs text-muted"><?= esc($act['current_module']) ?></div>
                        </td>
                        <td>
                            <span class="tag tag-gray"><?= esc($act['category']) ?></span>
                        </td>
                        <td><?= esc($act['duration']) ?></td>
                        <td>
                            <?php if ($act['status'] === 'disetujui'): ?>
                                <span class="status-badge status-success text-xs"><i class="ph-fill ph-check-circle"></i> Berjalan (Disetujui)</span>
                            <?php else: ?>
                                <span class="status-badge status-warning text-xs"><i class="ph-fill ph-hourglass"></i> Menunggu Konfirmasi</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($act['status'] === 'menunggu_konfirmasi'): ?>
                                <a href="<?= base_url('admin-panel') ?>" class="btn-dark text-xs">
                                    <i class="ph-fill ph-stamp"></i> Konfirmasi
                                </a>
                            <?php else: ?>
                                <button class="btn-outline text-xs" onclick="sendWhatsAppNudge('<?= addslashes($act['user_name']) ?>', '<?= addslashes($act['title']) ?>', null, '<?= addslashes($act['user_username'] ?? '') ?>')">
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
                <span class="section-tag-count"><?= count($userActivities) ?> Terdaftar</span>
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
            <div class="activity-live-card glass-panel <?= $activity['status'] === 'menunggu_konfirmasi' ? 'status-border-pending' : 'status-border-approved' ?>">
                <div class="activity-card-header">
                    <div class="activity-tags-row">
                        <span class="tag <?= $activity['status'] === 'disetujui' ? 'tag-teal' : 'tag-amber' ?>">
                            <i class="ph-fill <?= $activity['status'] === 'disetujui' ? 'ph-check-circle' : 'ph-hourglass-medium' ?>"></i>
                            <?= $activity['status'] === 'disetujui' ? 'Aktif &amp; Disetujui' : 'Menunggu Konfirmasi Admin' ?>
                        </span>
                        <span class="tag tag-gray"><?= esc($activity['category']) ?></span>
                        <span class="tag tag-gray font-mono"><i class="ph-fill ph-clock"></i> <?= esc($activity['duration']) ?></span>
                    </div>
                    <div class="activity-deadline-tag">
                        <i class="ph-fill ph-calendar"></i>
                        <span>Target: <strong><?= esc($activity['deadline']) ?></strong></span>
                    </div>
                </div>

                <div class="activity-card-body">
                    <h3 class="activity-title"><?= esc($activity['title']) ?></h3>
                    <p class="activity-notes text-sm text-muted">
                        <?= esc($activity['notes']) ?>
                    </p>
                    
                    <div class="activity-progress-wrap mt-3">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-dark">
                                <i class="ph-fill ph-flag-banner text-accent"></i>
                                <?= esc($activity['current_module']) ?>
                            </span>
                            <span class="font-mono font-bold text-accent"><?= $activity['progress'] ?>%</span>
                        </div>
                        <div class="progress-bar-container" style="height: 7px; margin-top: 0;">
                            <div class="progress-bar" style="width: <?= $activity['progress'] ?>%;"></div>
                        </div>
                    </div>

                    <?php 
                    $attFileDash = !empty($activity['attendance_file']) ? $activity['attendance_file'] : (!empty($activity['last_attachment']) && (strpos($activity['last_attachment'], 'absen') !== false) ? $activity['last_attachment'] : null);
                    if ($attFileDash):
                    ?>
                    <div class="mt-2.5 p-2 rounded text-xs flex items-center justify-between" style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534;">
                        <div class="flex items-center gap-1.5 font-semibold">
                            <i class="ph-fill ph-file-check" style="font-size:1.15rem; color:#0f766e;"></i>
                            <span>Berkas Absensi HC: <strong class="font-mono text-xs"><?= esc($attFileDash) ?></strong></span>
                        </div>
                        <a href="<?= base_url('uploads/attendance/' . esc($attFileDash)) ?>" target="_blank" class="tag tag-teal text-xs" style="text-decoration:none; padding:2px 8px; font-weight:700;">
                            <i class="ph-bold ph-eye"></i> Lihat Absensi
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="activity-card-footer">
                    <span class="text-xs text-muted">
                        Diajukan: <?= date('d M Y', strtotime($activity['created_at'])) ?>
                        <?php if (!empty($activity['approved_at'])): ?>
                        · Dikonfirmasi Admin: <?= date('d M Y H:i', strtotime($activity['approved_at'])) ?>
                        <?php endif; ?>
                    </span>
                    <div class="flex gap-2">
                        <?php if ($activity['status'] === 'disetujui'): ?>
                        <a href="<?= base_url('my-learning') ?>" class="btn-dark text-xs">
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
                    <?= $user_type === 'Admin' ? 'Rekomendasi Kurikulum LNA' : 'Rekomendasi Modul LNA' ?>
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
                <span><?= $user_type === 'Admin' ? 'Status Kepatuhan Site' : 'Matriks Gap Kompetensi' ?></span>
            </div>
            <h3 class="lna-radar-title">Pemetaan Standar K3 Site Pomalaa</h3>
        </div>

        <div class="lna-metrics-grid">
            <div class="lna-metric-box">
                <div class="metric-top">
                    <span class="metric-name">K3 Bawah Tanah</span>
                    <span class="metric-pct text-red">60%</span>
                </div>
                <div class="metric-bar-wrap">
                    <div class="metric-bar bg-red" style="width: 60%;"></div>
                </div>
                <span class="metric-sub text-red"><i class="ph-fill ph-warning"></i> Gap Kritis</span>
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
                <i class="ph-fill ph-squares-four"></i> Semua (<?= count($lnaList) ?>)
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
        <div class="lna-modern-card glass-panel <?= $borderAccent ?>" data-lna-type="<?= $lna['type'] ?>">
            
            <div class="lna-card-topbar">
                <div class="flex items-center gap-2">
                    <span class="tag <?= $tagColor ?>">
                        <i class="ph-fill <?= $lna['type'] === 'Wajib' ? 'ph-shield-warning' : ($lna['type'] === 'Sertifikasi' ? 'ph-seal-check' : 'ph-book-open') ?>"></i>
                        <?= esc($lna['type']) ?>
                    </span>
                    <span class="lna-code-tag font-mono"><?= esc($lna['code']) ?></span>
                </div>
                <div class="lna-gap-indicator <?= $lna['type'] === 'Wajib' ? 'gap-high' : 'gap-normal' ?>" title="<?= esc($lna['gap_score'] ?? '') ?>">
                    <i class="ph-fill <?= $lna['type'] === 'Wajib' ? 'ph-warning-circle' : 'ph-check-circle' ?>"></i>
                    <span><?= $lna['type'] === 'Wajib' ? 'Prioritas' : ($lna['type'] === 'Sertifikasi' ? 'Lisensi' : 'Pilihan') ?></span>
                </div>
            </div>

            <div class="lna-card-main">
                <h3 class="lna-card-title"><?= esc($lna['title']) ?></h3>

                <!-- Icon-Driven Metadata (No verbose labels) -->
                <div class="lna-meta-strip">
                    <span class="lna-meta-chip" title="Durasi: <?= esc($lna['duration']) ?>">
                        <i class="ph-fill ph-clock"></i>
                        <span><?= esc($lna['duration']) ?></span>
                    </span>
                    <span class="lna-meta-chip" title="Tingkat: <?= esc($lna['level']) ?>">
                        <i class="ph-fill ph-chart-bar"></i>
                        <span><?= esc($lna['level']) ?></span>
                    </span>
                    <span class="lna-meta-chip" title="Kompetensi: <?= esc($lna['competency_area']) ?>">
                        <i class="ph-fill ph-target"></i>
                        <span><?= esc($lna['competency_area']) ?></span>
                    </span>
                </div>
            </div>

            <!-- Icon-Focused Action Bar -->
            <div class="lna-card-action-bar">
                <div class="lna-category-hint font-mono">
                    <i class="ph-fill ph-tag"></i>
                    <span><?= esc($lna['category']) ?></span>
                </div>
                <div class="lna-icon-actions">
                    <button class="btn-card-icon btn-outline" title="Lihat Silabus Lengkap" onclick="openCourseDetailModal(<?= htmlspecialchars(json_encode($lna['title']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['type']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['duration']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['level']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['desc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)">
                        <i class="ph-bold ph-info"></i>
                    </button>
                    <?php if ($user_type === 'Admin'): ?>
                    <button class="btn-card-icon btn-dark" title="Tugaskan Modul ke Pegawai" onclick="openAssignTrainingModal(<?= htmlspecialchars(json_encode($lna['title']), ENT_QUOTES, 'UTF-8') ?>)">
                        <i class="ph-bold ph-user-plus"></i>
                    </button>
                    <?php else: ?>
                    <button class="btn-card-icon btn-dark" title="Ajukan Kegiatan Ini" onclick="openNewActivityModalWithPreset(<?= htmlspecialchars(json_encode($lna['title']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['type']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['category']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['duration']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($lna['level']), ENT_QUOTES, 'UTF-8') ?>)">
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
<?= $this->endSection() ?>

