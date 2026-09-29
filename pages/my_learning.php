<?php
$user_name = $_SESSION['user_name'] ?? 'Pegawai';
$user_type = $_SESSION['user_type'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Operasional Tambang';
$username  = $_SESSION['username'] ?? '';

// Get activities dynamically
$userActivities = get_activities($username);
$approvedActivities = array_filter($userActivities, function($a) { return $a['status'] === 'disetujui'; });
$pendingActivities = array_filter($userActivities, function($a) { return $a['status'] === 'menunggu_konfirmasi'; });

// Dynamic counts
$activeCount = count($approvedActivities);
$pendingCount = count($pendingActivities);
// Get certificates from DB
$userCertificates = ($user_type !== 'Admin') ? get_user_certificates($username) : [];
$certByTitle = [];
foreach ($userCertificates as $c) {
    $certByTitle[strtolower(trim($c['course_title']))] = $c;
}
$certCount = count($userCertificates);

$totalModules = 0;
$completedModules = 0;
foreach ($approvedActivities as $act) {
    $totalModules += isset($act['total_modules']) ? $act['total_modules'] : 4;
    $completedModules += isset($act['completed_modules']) ? $act['completed_modules'] : 0;
}
$completionRate = ($totalModules > 0) ? round(($completedModules / $totalModules) * 100) : 0;
?>

<div class="mylearning-container">
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="page-title"><?php echo $user_type === 'Admin' ? 'Learning Track Monitoring' : 'My Learning Track'; ?></h1>
                <span class="badge-role-tag"><?php echo htmlspecialchars($user_role); ?></span>
            </div>
        </div>
        <div class="page-top-actions">
            <?php if ($user_type !== 'Admin'): ?>
            <button class="btn-dark" onclick="openNewActivityModal()">
                <i class="ph-bold ph-plus-circle"></i>
                <span>+ Ajukan Kegiatan Baru</span>
            </button>
            <?php else: ?>
            <a href="?page=admin_panel" class="btn-dark">
                <i class="ph-fill ph-stamp"></i>
                <span>Panel Verifikasi Admin</span>
            </a>
            <?php endif; ?>
            <a href="?page=learning_bank" class="btn-outline">
                <i class="ph-fill ph-books"></i>
                <span>Katalog Modul</span>
            </a>
            <?php if ($user_type !== 'Admin'): ?>
            <button class="btn-dark" onclick="openBatchCertModal()">
                <i class="ph-fill ph-stamp"></i>
                <span>Upload Template &amp; Terbitkan Batch</span>
            </button>
            <button class="btn-outline" onclick="switchCourseTab(document.querySelectorAll('.sub-tab')[2], 'completed-courses')">
                <i class="ph-fill ph-certificate text-accent"></i>
                <span>Portofolio Sertifikat (<?php echo $certCount; ?>)</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="mylearning-stats-bar">
        <div class="mylearn-stat">
            <div class="mylearn-stat-icon bg-amber-light"><i class="ph-fill ph-play-circle text-amber"></i></div>
            <div>
                <div class="stat-big-num"><?php echo $activeCount; ?></div>
                <div class="stat-label-sub">Kegiatan Aktif</div>
            </div>
        </div>
        <div class="mylearn-stat">
            <div class="mylearn-stat-icon bg-red-light"><i class="ph-fill ph-hourglass text-red"></i></div>
            <div>
                <div class="stat-big-num"><?php echo $pendingCount; ?></div>
                <div class="stat-label-sub">Menunggu Admin</div>
            </div>
        </div>
        <div class="mylearn-stat">
            <div class="mylearn-stat-icon bg-green-light"><i class="ph-fill ph-certificate text-green"></i></div>
            <div>
                <div class="stat-big-num"><?php echo $certCount; ?></div>
                <div class="stat-label-sub">Sertifikat Resmi</div>
            </div>
        </div>
        <div class="mylearn-stat">
            <div class="mylearn-stat-icon bg-blue-light"><i class="ph-fill ph-chart-donut text-blue"></i></div>
            <div>
                <div class="stat-big-num"><?php echo $completionRate; ?>%</div>
                <div class="stat-label-sub">Ketuntasan Materi</div>
            </div>
        </div>
    </div>

    <!-- Tabs Filter -->
    <div class="sub-nav-tabs">
        <button class="sub-tab active" onclick="switchCourseTab(this, 'active-courses')">
            <i class="ph-fill ph-play-circle"></i> Berjalan & Disetujui (<?php echo $activeCount; ?>)
        </button>
        <button class="sub-tab" onclick="switchCourseTab(this, 'pending-courses')">
            <i class="ph-fill ph-hourglass-high text-amber"></i> Menunggu Konfirmasi (<?php echo $pendingCount; ?>)
        </button>
        <button class="sub-tab" onclick="switchCourseTab(this, 'completed-courses')">
            <i class="ph-fill ph-seal-check text-green"></i> Sertifikat (<?php echo $certCount; ?>)
        </button>
        <button class="sub-tab" onclick="switchCourseTab(this, 'recommend-courses')">
            <i class="ph-fill ph-lightbulb text-accent"></i> Rekomendasi LNA
        </button>
    </div>

    <!-- Tab 1: Active Courses (Berjalan & Disetujui) -->
    <div id="active-courses" class="tab-content-panel active">
        <?php if (count($approvedActivities) === 0): ?>
        <div class="empty-state-box glass-panel text-center p-6">
            <i class="ph-fill ph-chalkboard-teacher text-muted" style="font-size: 3rem; margin-bottom: 0.5rem;"></i>
            <h4 class="font-semibold">Belum Ada Kegiatan Pembelajaran yang Aktif</h4>
            <p class="text-sm text-muted mb-4">Ajukan kegiatan baru atau tunggu konfirmasi dari Learning Ops Admin.</p>
            <button class="btn-dark" onclick="openNewActivityModal()">
                <i class="ph-bold ph-plus"></i> Ajukan Kegiatan Baru
            </button>
        </div>
        <?php else: ?>
        <div class="courses-active-grid">
            <?php foreach ($approvedActivities as $act): 
                $actTitleKey = strtolower(trim($act['title']));
                $hasCert     = isset($certByTitle[$actTitleKey]);
                $isDone      = ($act['progress'] >= 100) || $hasCert;
                $certInfo    = $certByTitle[$actTitleKey] ?? null;
                $displayProg = $isDone ? 100 : (int)$act['progress'];
            ?>
            <div class="course-active-card <?php echo strpos(strtolower($act['type']), 'wajib') !== false ? 'border-urgent' : ''; ?>" style="<?php echo $isDone ? 'border-color: #10b981;' : ''; ?>">
                <div class="card-head-row">
                    <span class="tag <?php echo $isDone ? 'tag-teal' : (strpos(strtolower($act['type']), 'wajib') !== false ? 'tag-red' : 'tag-teal'); ?>">
                        <i class="ph-fill <?php echo $isDone ? 'ph-seal-check' : (strpos(strtolower($act['type']), 'wajib') !== false ? 'ph-warning-circle' : 'ph-clock'); ?>"></i>
                        <?php echo $isDone ? 'Selesai &amp; Bersertifikat' : htmlspecialchars($act['type']); ?>
                    </span>
                    <span class="<?php echo $isDone ? 'badge-deadline-success' : (strpos(strtolower($act['type']), 'wajib') !== false ? 'badge-deadline-urgent' : 'badge-deadline-warning'); ?> font-mono">
                        <i class="ph-fill <?php echo $isDone ? 'ph-check-circle' : 'ph-clock'; ?>"></i> 
                        <?php echo $isDone ? 'Tuntas' : 'Target: ' . htmlspecialchars($act['deadline']); ?>
                    </span>
                </div>
                
                <h3 class="course-main-title"><?php echo htmlspecialchars($act['title']); ?></h3>
                <p class="course-main-desc"><?php echo htmlspecialchars($act['notes']); ?></p>

                <div class="course-progress-block">
                    <div class="progress-labels">
                        <span class="font-medium text-sm text-dark font-mono font-bold"><?php echo $displayProg; ?>% Tuntas</span>
                        <span class="text-xs <?php echo $isDone ? 'text-green' : 'text-muted'; ?>">
                            <?php echo $isDone ? '✓ Bersertifikat Resmi' : 'Kegiatan Terdaftar'; ?>
                        </span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $displayProg; ?>%; <?php echo $isDone ? 'background: #10b981;' : ''; ?>"></div>
                    </div>
                </div>

                <!-- Info Banner Sederhana: Ikut Kegiatan Pasti Dapat Sertifikat -->
                <?php if ($isDone): ?>
                <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; margin: 0.85rem 0; padding: 0.75rem 1rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                    <div class="flex items-center gap-2" style="color: #065f46;">
                        <i class="ph-fill ph-seal-check text-green" style="font-size:1.35rem;"></i>
                        <span style="font-size:0.8rem; font-weight:600;">Sertifikat Resmi Telah Diterbitkan</span>
                    </div>
                    <?php if ($certInfo): ?>
                    <span class="font-mono text-xs" style="color:#047857; background:#d1fae5; padding:2px 8px; border-radius:4px; font-weight:600;">No: <?php echo htmlspecialchars($certInfo['reg_no']); ?></span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:8px; margin: 0.85rem 0; padding: 0.75rem 1rem; display:flex; align-items:center; gap:0.75rem;">
                    <i class="ph-fill ph-medal text-accent" style="font-size:1.4rem; flex-shrink:0;"></i>
                    <div style="font-size:0.785rem; color:#115e59; line-height:1.4;">
                        <strong>Jaminan Sertifikat Resmi:</strong> Anda terdaftar dalam kegiatan ini. Setelah menyelesaikan materi / kehadiran, klik tombol di bawah untuk langsung menerbitkan E-Sertifikat resmi kelulusan Anda.
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($act['last_attachment'])): ?>
                <div class="last-submission-badge" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin: 0.5rem 0 0.85rem; padding: 0.45rem 0.75rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.775rem;">
                    <div class="flex items-center gap-2" style="color: #334155; font-weight: 500;">
                        <i class="ph-fill ph-file-doc" style="font-size:1.1rem; color:#0f766e;"></i>
                        <span>Lampiran: <strong><?php echo htmlspecialchars($act['last_attachment']); ?></strong></span>
                    </div>
                    <span class="text-muted font-mono" style="font-size:0.725rem;"><?php echo !empty($act['last_submitted_at']) ? date('d M H:i', strtotime($act['last_submitted_at'])) : 'Tersimpan'; ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($act['cert_template'])): ?>
                <div class="template-badge" style="background:#fefce8; border:1px solid #fef08a; border-radius:6px; margin: 0.35rem 0 0.6rem; padding: 0.35rem 0.65rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color:#854d0e;">
                    <div class="flex items-center gap-2">
                        <i class="ph-fill ph-file-image" style="color:#d97706; font-size:1.1rem;"></i>
                        <span>Template Sertifikat: <strong><?php echo htmlspecialchars($act['cert_template']); ?></strong></span>
                    </div>
                    <span class="tag tag-amber text-xs" style="font-size:0.68rem; padding:2px 6px;">Kustom</span>
                </div>
                <?php endif; ?>

                <!-- Tombol Aksi Sederhana & Selesaikan Kegiatan -->
                <div class="card-actions-row">
                    <?php if ($isDone): ?>
                    <button class="btn-dark" style="background:#0f766e; border-color:#0f766e; width: 100%;" onclick="openCertViewer(
                        '<?php echo addslashes($act['title']); ?>',
                        '<?php echo addslashes($certInfo['reg_no'] ?? ('ANTAM/K3U/' . date('Y') . '/' . rand(1000, 9999))); ?>',
                        '<?php echo $act['grade'] ?? 100; ?>',
                        '<?php echo date('d M Y', strtotime($act['last_submitted_at'] ?? 'now')); ?>',
                        '<?php echo date('d M Y', strtotime('+3 years', strtotime($act['last_submitted_at'] ?? 'now'))); ?>',
                        '<?php echo addslashes($user['name']); ?>',
                        '<?php echo addslashes($act['cert_template'] ?? ''); ?>'
                    )">
                        <i class="ph-fill ph-certificate"></i> Lihat &amp; Cetak E-Sertifikat
                    </button>
                    <?php else: ?>
                    <button class="btn-dark" onclick="openCompleteActivityAttendanceModal('<?php echo $act['id']; ?>', '<?php echo addslashes($act['title']); ?>', '<?php echo addslashes($act['cert_template'] ?? ''); ?>')">
                        <i class="ph-fill ph-clipboard-text"></i> Selesaikan &amp; Upload Absensi
                    </button>
                    <button class="btn-outline" onclick="handleClaimCertificate('<?php echo $act['id']; ?>', '<?php echo addslashes($act['title']); ?>', this)" title="Klaim Mandiri Langsung">
                        <i class="ph-fill ph-seal-check"></i> Klaim Mandiri
                    </button>
                    <?php endif; ?>
                </div>
                <?php if (!$isDone): ?>
                <div class="mt-2 text-center">
                    <button type="button" class="btn-outline text-xs w-full" style="border-style:dashed; color:#0f766e; font-size:0.75rem; padding:0.45rem;" onclick="openCompleteActivityAttendanceModal('<?php echo $act['id']; ?>', '<?php echo addslashes($act['title']); ?>', '<?php echo addslashes($act['cert_template'] ?? ''); ?>')">
                        <i class="ph-fill ph-users-three"></i> Terbitkan Sertifikat Otomatis dari Daftar Hadir Karyawan
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab 2: Pending Approval Courses (Menunggu Konfirmasi Admin) -->
    <div id="pending-courses" class="tab-content-panel" style="display: none;">
        <?php if (count($pendingActivities) === 0): ?>
        <div class="empty-state-box glass-panel text-center p-6">
            <i class="ph-fill ph-check-circle text-green" style="font-size: 3rem; margin-bottom: 0.5rem;"></i>
            <h4 class="font-semibold">Tidak Ada Kegiatan Menunggu Konfirmasi</h4>
            <p class="text-sm text-muted mb-4">Semua usulan kegiatan Anda telah diproses oleh admin.</p>
            <button class="btn-dark" onclick="openNewActivityModal()">
                <i class="ph-bold ph-plus"></i> Buat Pengajuan Baru
            </button>
        </div>
        <?php else: ?>
        <div class="pending-activities-list">
            <?php foreach ($pendingActivities as $pAct): ?>
            <div class="pending-act-card glass-panel mb-4">
                <div class="pending-badge-row">
                    <span class="status-badge status-warning text-xs">
                        <i class="ph-fill ph-hourglass-medium"></i> Menunggu Konfirmasi Admin L&D
                    </span>
                    <span class="text-xs text-muted font-mono">Diajukan: <?php echo date('d M Y H:i', strtotime($pAct['created_at'])); ?></span>
                </div>
                
                <h3 class="pending-act-title mt-2"><?php echo htmlspecialchars($pAct['title']); ?></h3>
                
                <div class="pending-meta-grid mt-2 mb-3">
                    <div><strong>Kategori:</strong> <?php echo htmlspecialchars($pAct['category']); ?></div>
                    <div><strong>Tipe:</strong> <?php echo htmlspecialchars($pAct['type']); ?></div>
                    <div><strong>Estimasi Durasi:</strong> <?php echo htmlspecialchars($pAct['duration']); ?></div>
                    <div><strong>Target Penyelesaian:</strong> <?php echo htmlspecialchars($pAct['deadline']); ?></div>
                    <?php if (!empty($pAct['cert_template'])): ?>
                    <div style="grid-column: span 2;"><strong>Template Sertifikat:</strong> <span class="tag tag-teal text-xs font-mono"><i class="ph-fill ph-image"></i> <?php echo htmlspecialchars($pAct['cert_template']); ?></span></div>
                    <?php else: ?>
                    <div style="grid-column: span 2;"><strong>Template Sertifikat:</strong> <span class="tag tag-amber text-xs font-mono"><i class="ph-fill ph-medal"></i> Standar Resmi PT ANTAM Gold</span></div>
                    <?php endif; ?>
                </div>

                <p class="text-sm text-muted bg-gray-50 p-3 rounded-md border" style="background:#f8fafc; border: 1px solid #e2e8f0;">
                    <strong>Alasan / Catatan Pengajuan:</strong><br>
                    <?php echo htmlspecialchars($pAct['notes']); ?>
                </p>

                <div class="flex items-center justify-between mt-4 text-xs text-muted">
                    <span><i class="ph-fill ph-info"></i> Kegiatan akan otomatis aktif di My Learning dan Dashboard Anda segera setelah admin mengonfirmasi.</span>
                    <button class="btn-outline text-xs" onclick="showToast('Pemberitahuan telah dikirimkan ke Admin Console.', 'info')">
                        <i class="ph-fill ph-paper-plane-tilt"></i> Ingatkan Admin
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab 3: Completed Courses & Certificates -->
    <div id="completed-courses" class="tab-content-panel" style="display: none;">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3 pb-3 border-b">
            <div>
                <h3 class="font-bold text-base">Portofolio Sertifikat &amp; Lisensi Kompetensi</h3>
                <p class="text-xs text-muted">Sertifikat resmi terbit otomatis dari kegiatan yang Anda selesaikan, serta dokumen lisensi eksternal (ESDM/Kemnaker/BNSP).</p>
            </div>
            <?php if ($user_type !== 'Admin'): ?>
            <div class="flex items-center gap-2">
                <button class="btn-dark text-xs" onclick="openBatchCertModal()">
                    <i class="ph-fill ph-stamp"></i> Upload Template &amp; Terbitkan Batch
                </button>
                <button class="btn-outline text-xs" onclick="openUploadExternalCertModal()">
                    <i class="ph-fill ph-upload-simple"></i> + Upload Sertifikat Saya
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="completed-grid">
        <?php if (empty($userCertificates)): ?>
            <div class="glass-panel p-6 text-center" style="grid-column: 1/-1;">
                <i class="ph-fill ph-certificate" style="font-size:2.5rem; color:#d4af37; opacity:0.5;"></i>
                <p class="text-muted mt-2">Belum ada sertifikat terdaftar di portofolio Anda.</p>
                <p class="text-xs text-muted mt-1">Sertifikat resmi akan otomatis muncul di sini begitu Anda menyelesaikan kegiatan pelatihan di tab "Berjalan &amp; Disetujui", atau melalui upload sertifikat mandiri.</p>
                <?php if ($user_type !== 'Admin'): ?>
                <div class="flex justify-center gap-2 mt-4 flex-wrap">
                    <button class="btn-dark text-sm" onclick="openUploadExternalCertModal()">
                        <i class="ph-fill ph-upload-simple"></i> Upload Sertifikat Saya
                    </button>
                    <button class="btn-outline text-sm" onclick="switchCourseTab(document.querySelectorAll('.sub-tab')[0], 'active-courses')">
                        <i class="ph-fill ph-play-circle"></i> Lihat Pelatihan Aktif
                    </button>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($userCertificates as $cert): ?>
            <?php
                $issuedFmt   = date('d M Y', strtotime($cert['issued_at']));
                $validFmt    = date('d M Y', strtotime($cert['valid_until']));
                $statusTag   = ($cert['status'] === 'active') ? 'tag-teal' : 'tag-red';
                $statusLabel = ($cert['status'] === 'active') ? 'Terverifikasi Sah' : ucfirst($cert['status']);
                $certTitle   = htmlspecialchars($cert['course_title']);
                $regNo       = htmlspecialchars($cert['reg_no']);
                $score       = (int)$cert['score'];
                $issuedBy    = htmlspecialchars($cert['issued_by'] ?? 'Kemnaker RI & Inspektur Tambang ESDM');
            ?>
            <div class="cert-card glass-panel">
                <div class="cert-header">
                    <div class="cert-badge-ribbon"><i class="ph-fill ph-seal-check"></i> RESMI ANTAM</div>
                    <div class="cert-icon-box"><i class="ph-fill ph-certificate text-accent"></i></div>
                    <div class="cert-meta">
                        <span class="tag tag-teal">Sertifikasi Kompetensi</span>
                        <h4 class="cert-title"><?php echo $certTitle; ?></h4>
                        <span class="text-xs text-muted">Diterbitkan: <?php echo $issuedFmt; ?> &bull; Skor: <?php echo $score; ?>/100</span>
                    </div>
                </div>
                <div class="cert-body">
                    <div class="cert-spec-list">
                        <div><strong>No. Registrasi:</strong> <span class="font-mono"><?php echo $regNo; ?></span></div>
                        <div><strong>Masa Berlaku:</strong> s/d <?php echo $validFmt; ?></div>
                        <div><strong>Lembaga Akreditasi:</strong> <?php echo $issuedBy; ?></div>
                        <div><strong>Status:</strong> <span class="tag <?php echo $statusTag; ?>"><i class="ph-fill ph-check"></i> <?php echo $statusLabel; ?></span></div>
                    </div>
                </div>
                <div class="cert-footer">
                    <?php if (!empty($cert['attachment'])): ?>
                    <a href="uploads/<?php echo htmlspecialchars($cert['attachment']); ?>" target="_blank" class="btn-outline w-full mb-2 text-center text-xs" style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                        <i class="ph-fill ph-file-arrow-down"></i> Unduh File Asli yang Diunggah
                    </a>
                    <?php endif; ?>
                    <button class="btn-dark w-full"
                        onclick="openCertViewer(
                            '<?php echo addslashes($certTitle); ?>',
                            '<?php echo addslashes($regNo); ?>',
                            '<?php echo $score; ?>',
                            '<?php echo addslashes($issuedFmt); ?>',
                            '<?php echo addslashes($validFmt); ?>',
                            '<?php echo addslashes(htmlspecialchars($cert['user_name'])); ?>',
                            '<?php echo addslashes($cert['template_file'] ?? $cert['attachment'] ?? ''); ?>'
                        )">
                        <i class="ph-fill ph-file-pdf"></i> Lihat &amp; Cetak E-Sertifikat
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>

    <!-- Tab 4: Recommendations -->
    <div id="recommend-courses" class="tab-content-panel" style="display: none;">
        <div class="recom-grid">
            <div class="course-card glass-panel p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="tag tag-red">Wajib ESDM</span>
                    <span class="text-xs text-muted font-mono"><i class="ph-fill ph-clock"></i> 4 Hari</span>
                </div>
                <h4 class="font-semibold text-base mb-1">Hauling Truck 777D (Technical Skill A)</h4>
                <p class="text-xs text-muted mb-3">Manuver tanjakan ekstrem dan penanganan rem darurat di loading pit underground.</p>
                <div class="flex gap-2">
                    <?php if ($user_type === 'Admin'): ?>
                    <button class="btn-dark text-xs" onclick="openAssignTrainingModal('Technical Skill A — Heavy Hauling 777D')">
                        <i class="ph-fill ph-user-plus"></i> Tugaskan ke Pegawai
                    </button>
                    <?php else: ?>
                    <button class="btn-dark text-xs" onclick="openNewActivityModalWithPreset('Technical Skill A — Heavy Hauling 777D', 'Wajib ESDM', 'Teknik Operasional', '4 Hari (32 Jam)', 'Advanced')">
                        <i class="ph-fill ph-plus"></i> Ajukan Kegiatan Ini
                    </button>
                    <?php endif; ?>
                    <button class="btn-outline text-xs" onclick="openCourseDetailModal('Technical Skill A', 'Wajib', '4 Hari', 'Advanced')">
                        <i class="ph-fill ph-info"></i> Detail
                    </button>
                </div>
            </div>

            <div class="course-card glass-panel p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="tag tag-gray">Leadership</span>
                    <span class="text-xs text-muted font-mono"><i class="ph-fill ph-clock"></i> 2 Hari</span>
                </div>
                <h4 class="font-semibold text-base mb-1">Leadership & Coaching untuk Foreman</h4>
                <p class="text-xs text-muted mb-3">Membekali supervisi regu kerja bawah tanah dan komunikasi keselamatan berkala.</p>
                <div class="flex gap-2">
                    <?php if ($user_type === 'Admin'): ?>
                    <button class="btn-dark text-xs" onclick="openAssignTrainingModal('Leadership & Supervisory Underground')">
                        <i class="ph-fill ph-user-plus"></i> Tugaskan ke Pegawai
                    </button>
                    <?php else: ?>
                    <button class="btn-dark text-xs" onclick="openNewActivityModalWithPreset('Leadership & Supervisory Underground', 'Optional', 'Leadership & Manajemen', '2 Hari (16 Jam)', 'Intermediate')">
                        <i class="ph-fill ph-plus"></i> Ajukan Kegiatan Ini
                    </button>
                    <?php endif; ?>
                    <button class="btn-outline text-xs" onclick="openCourseDetailModal('Leadership & Coaching', 'Optional', '2 Hari', 'Intermediate')">
                        <i class="ph-fill ph-info"></i> Detail
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Switcher for sub-tabs -->
<script>
function switchCourseTab(btn, tabId) {
    document.querySelectorAll('.sub-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    document.querySelectorAll('.tab-content-panel').forEach(panel => {
        panel.style.display = 'none';
        panel.classList.remove('active');
    });
    
    const activePanel = document.getElementById(tabId);
    if(activePanel) {
        activePanel.style.display = 'block';
        activePanel.classList.add('active');
    }
}
</script>
