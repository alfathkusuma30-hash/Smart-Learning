<?php
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];

// Fetch notifications and pending requests with proper variable names
$notifList = get_notifications_for_user($username, $user_type);
$pendingRequests = ($user_type === 'Admin') ? get_activities(null, 'menunggu_konfirmasi') : [];
$userPending = ($user_type !== 'Admin') ? get_activities($username, 'menunggu_konfirmasi') : [];

// Counters for Admin tabs
$countReq = count($pendingRequests);
$countEsc = 1;
$countVerif = 1;
$countAllAdmin = $countReq + $countEsc + $countVerif;
?>

<div class="notif-center-container">
    <!-- Top Header -->
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="page-title">Pusat Alarm & Notifikasi</h1>
                <span class="badge-role-tag"><?php echo $user_type === 'Admin' ? 'ADMIN CONSOLE' : 'PERSONAL HUB'; ?></span>
            </div>
        </div>
        
        <div class="page-top-actions">
            <?php if($user_type !== 'Admin'): ?>
                <button class="btn-outline" onclick="markAllAsRead()">
                    <i class="ph-fill ph-checks"></i>
                    <span>Tandai Dibaca</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- TAMPILAN ADMIN: PUSAT ALARM & VERIFIKASI LAPORAN           -->
    <!-- ========================================================== -->
    <?php if ($user_type === 'Admin'): ?>
    
    <!-- Filter Tabs for Admin -->
    <div class="notif-filter-tabs mb-4">
        <button class="notif-tab active" onclick="filterAdminNotifs(this, 'all')">
            <i class="ph-fill ph-list-bullets"></i> Semua (<span id="count-all"><?php echo $countAllAdmin; ?></span>)
        </button>
        <button class="notif-tab" onclick="filterAdminNotifs(this, 'request')">
            <i class="ph-fill ph-file-plus text-amber"></i> Usulan (<span id="count-req"><?php echo $countReq; ?></span>)
        </button>
        <button class="notif-tab" onclick="filterAdminNotifs(this, 'escalation')">
            <i class="ph-fill ph-siren text-red"></i> Eskalasi (<span id="count-esc"><?php echo $countEsc; ?></span>)
        </button>
        <button class="notif-tab" onclick="filterAdminNotifs(this, 'verification')">
            <i class="ph-fill ph-seal-check text-sky"></i> Verifikasi (<span id="count-verif"><?php echo $countVerif; ?></span>)
        </button>
    </div>

    <!-- Admin Notification Stream -->
    <div class="admin-notif-stream" id="admin-notif-container">
        
        <!-- PENDING REQUESTS FROM EMPLOYEES (EGY, BUDI, ANDI) -->
        <?php foreach ($pendingRequests as $pReq): 
            $pName = !empty($pReq['user_name']) ? $pReq['user_name'] : 'Pegawai';
            $pCat = !empty($pReq['category']) ? $pReq['category'] : 'Operasional Tambang';
            $pDur = !empty($pReq['duration']) ? $pReq['duration'] : '2 Hari';
            $pTyp = !empty($pReq['type']) ? $pReq['type'] : 'Pengembangan Mandiri';
            $pDdl = !empty($pReq['deadline']) ? $pReq['deadline'] : 'Segera';
            $pNot = !empty($pReq['notes']) ? $pReq['notes'] : 'Usulan pemenuhan kompetensi.';
            $pDate = !empty($pReq['created_at']) ? date('d M Y H:i', strtotime($pReq['created_at'])) : date('d M Y H:i');
        ?>
        <div class="notif-item-card request-card" data-category="request" id="notif-act-<?php echo $pReq['id']; ?>">
            <div class="notif-card-indicator bg-amber"></div>
            <div class="notif-badge-type bg-amber-soft text-amber">
                <i class="ph-fill ph-hourglass-medium"></i>
                <span>USULAN KEGIATAN PEGAWAI BARU</span>
            </div>
            
            <div class="notif-main-grid">
                <div class="emp-avatar-box">
                    <?php
                    $colors = ['admin' => '#10b981', 'egy' => '#d4af37', 'budi' => '#38bdf8', 'andi' => '#a855f7'];
                    $notif_color = $colors[strtolower($pReq['user_username'] ?? '')] ?? '#0f766e';
                    ?>
                    <div class="emp-av-circle" style="background: <?php echo $notif_color; ?>;">
                        <?php 
                        $in = ''; $parts = explode(' ', $pName);
                        foreach($parts as $p) { if(!empty($p)) $in .= substr($p, 0, 1); }
                        echo strtoupper(substr($in, 0, 2)) ?: 'PG';
                        ?>
                    </div>
                    <span class="emp-tag-under">Tambang</span>
                </div>

                <div class="notif-details-col">
                    <div class="notif-time-badge font-mono"><i class="ph-fill ph-clock"></i> <?php echo $pDate; ?> · Usulan Masuk</div>
                    <h3 class="notif-headline">
                        <?php echo htmlspecialchars($pName); ?> — <span class="text-accent"><?php echo htmlspecialchars($pReq['title']); ?></span>
                    </h3>

                    <div class="escalation-metadata-box">
                        <div class="meta-field">
                            <span class="meta-label">Kategori:</span>
                            <span class="meta-val"><?php echo htmlspecialchars($pCat); ?></span>
                        </div>
                        <div class="meta-field">
                            <span class="meta-label">Durasi / Tipe:</span>
                            <span class="meta-val"><?php echo htmlspecialchars($pDur); ?> (<?php echo htmlspecialchars($pTyp); ?>)</span>
                        </div>
                        <div class="meta-field">
                            <span class="meta-label">Target Deadline:</span>
                            <span class="meta-val font-mono text-dark font-semibold"><?php echo htmlspecialchars($pDdl); ?></span>
                        </div>
                    </div>

                    <p class="text-sm text-muted mb-3"><strong>Alasan / Keterangan:</strong> <?php echo htmlspecialchars($pNot); ?></p>

                    <!-- Direct Action Buttons for Admin -->
                    <div class="notif-action-row">
                        <button class="btn-action-primary bg-amber-solid" onclick="adminConfirmActivity('<?php echo $pReq['id']; ?>', 'setujui', '<?php echo addslashes($pReq['title']); ?>', '<?php echo addslashes($pName); ?>')">
                            <i class="ph-fill ph-check-circle"></i>
                            <span>Konfirmasi & Setujui</span>
                        </button>
                        <button class="btn-action-outline" onclick="adminConfirmActivity('<?php echo $pReq['id']; ?>', 'tolak', '<?php echo addslashes($pReq['title']); ?>', '<?php echo addslashes($pName); ?>')">
                            <i class="ph-fill ph-x-circle text-red"></i>
                            <span>Tolak Usulan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Empty State Container when filtered -->
        <div class="empty-notif-box glass-panel text-center p-6" id="empty-state-notif" style="display: none; background: white; border-radius: var(--radius-md); width: 100%;">
            <div style="font-size: 2.25rem; color: #16a34a; margin-bottom: 0.5rem;"><i class="ph-fill ph-check-circle"></i></div>
            <h4 class="font-semibold text-base mb-1" id="empty-state-title">Semua Notifikasi Telah Ditangani</h4>
            <p class="text-sm text-muted" id="empty-state-desc">Tidak ada notifikasi aktif dalam kategori yang dipilih saat ini.</p>
        </div>

        <!-- ITEM 1: ESKALASI KETERLAMBATAN -->
        <div class="notif-item-card escalation-card" data-category="escalation" id="notif-item-1">
            <div class="notif-card-indicator bg-red"></div>
            <div class="notif-badge-type bg-red-soft text-red">
                <i class="ph-fill ph-siren"></i>
                <span>ESKALASI · OVERDUE</span>
            </div>
            
            <div class="notif-main-grid">
                <div class="emp-avatar-box">
                    <div class="emp-av-circle" style="background: #d4af37;">EP</div>
                    <span class="emp-tag-under">Tambang</span>
                </div>

                <div class="notif-details-col">
                    <div class="notif-time-badge font-mono"><i class="ph-fill ph-clock"></i> 08:30 WIB · Alarm Otomatis</div>
                    <h3 class="notif-headline">
                        Egy Pratama — <span class="text-underline">Safety Induction Site Tambang Pongkor</span>
                    </h3>

                    <div class="escalation-metadata-box">
                        <div class="meta-field">
                            <span class="meta-label">Unit:</span>
                            <span class="meta-val">Operasional Underground</span>
                        </div>
                        <div class="meta-field">
                            <span class="meta-label">Risiko:</span>
                            <span class="meta-val text-red font-semibold">Tinggi (Izin Terancam)</span>
                        </div>
                        <div class="meta-field">
                            <span class="meta-label">Batas:</span>
                            <span class="meta-val">12 Agu 2026</span>
                        </div>
                    </div>

                    <!-- Direct Action Buttons for Admin -->
                    <div class="notif-action-row">
                        <button class="btn-action-primary bg-green-solid" onclick="sendWhatsAppNudge('Egy Pratama', 'Safety Induction Site Tambang Pongkor', 'notif-item-1')">
                            <i class="ph-fill ph-whatsapp-logo"></i>
                            <span>Nudge WA</span>
                        </button>
                        <button class="btn-action-outline" onclick="openRescheduleModal('Egy Pratama', 'Safety Induction Site Tambang Pongkor')">
                            <i class="ph-fill ph-calendar-plus"></i>
                            <span>Dispensasi</span>
                        </button>
                        <button class="btn-action-ghost" onclick="resolveNotif('notif-item-1', 'Eskalasi Egy Pratama ditandai selesai ditangani.')">
                            <i class="ph-fill ph-check"></i>
                            <span>Selesai</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ITEM 2: PERMINTAAN VERIFIKASI SERTIFIKAT K3 -->
        <div class="notif-item-card verification-card" data-category="verification" id="notif-item-2">
            <div class="notif-card-indicator bg-sky"></div>
            <div class="notif-badge-type bg-sky-soft text-sky">
                <i class="ph-fill ph-seal-check"></i>
                <span>VERIFIKASI SERTIFIKAT</span>
            </div>

            <div class="notif-main-grid">
                <div class="emp-avatar-box">
                    <div class="emp-av-circle" style="background: #38bdf8;">BS</div>
                    <span class="emp-tag-under">Smelter</span>
                </div>

                <div class="notif-details-col">
                    <div class="notif-time-badge font-mono"><i class="ph-fill ph-clock"></i> Kemarin, 16:45 WIB</div>
                    <h3 class="notif-headline">
                        Budi Santoso — <span class="text-accent">Sertifikasi K3 Umum & Pertambangan</span>
                    </h3>

                    <div class="escalation-metadata-box">
                        <div class="meta-field">
                            <span class="meta-label">Unit:</span>
                            <span class="meta-val">Processing Plant & Smelter</span>
                        </div>
                        <div class="meta-field">
                            <span class="meta-label">Nilai Kuis:</span>
                            <span class="meta-val text-green font-semibold">94 / 100</span>
                        </div>
                        <div class="meta-field">
                            <span class="meta-label">Masa Berlaku:</span>
                            <span class="meta-val">3 Tahun</span>
                        </div>
                    </div>

                    <div class="notif-action-row">
                        <button class="btn-action-primary bg-sky-solid" onclick="approveCertification('Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', 'notif-item-2')">
                            <i class="ph-fill ph-certificate"></i>
                            <span>Verifikasi</span>
                        </button>
                        <button class="btn-action-outline" onclick="openEvaluationProofModal('Budi Santoso', 'Sertifikasi K3 Umum', 94)">
                            <i class="ph-fill ph-magnifying-glass"></i>
                            <span>Lembar Kuis</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================== -->
    <!-- TAMPILAN PEGAWAI: NOTIFIKASI PRIBADI, STATUS USULAN & ALARM-->
    <!-- ========================================================== -->
    <?php else: ?>
    <div class="notif-list">
        
        <!-- 1. NOTIFIKASI PERSUTUJUAN DARI ADMIN (JIKA ADA) -->
        <?php if (count($notifList) > 0): ?>
            <?php foreach ($notifList as $nt): ?>
            <div class="notif-card glass-panel mb-3 p-4" style="background: white; border-radius: var(--radius-md); border-left: 5px solid #10b981;">
                <div class="flex items-center gap-3">
                    <div class="mini-avatar" style="background: #dcfce7; color: #15803d; width: 44px; height: 44px; font-size: 1.35rem; flex-shrink: 0;">
                        <i class="ph-fill ph-seal-check"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-semibold text-base text-dark"><?php echo htmlspecialchars($nt['title']); ?></h4>
                            <span class="text-xs text-muted font-mono"><?php echo date('d M Y H:i', strtotime($nt['created_at'])); ?></span>
                        </div>
                        <p class="text-sm text-muted mb-2"><?php echo htmlspecialchars($nt['message']); ?></p>
                        <div class="flex gap-2">
                            <a href="?page=my_learning" class="btn-dark text-xs"><i class="ph-fill ph-play"></i> Buka My Learning</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- 2. STATUS USULAN KEGIATAN YANG SEDANG MENUNGGU KONFIRMASI -->
        <?php if (count($userPending) > 0): ?>
            <?php foreach ($userPending as $uPend): ?>
            <div class="notif-card glass-panel mb-3 p-4" style="background: white; border-radius: var(--radius-md); border-left: 5px solid #f59e0b;">
                <div class="flex items-center gap-3">
                    <div class="mini-avatar" style="background: #fef3c7; color: #b45309; width: 44px; height: 44px; font-size: 1.35rem; flex-shrink: 0;">
                        <i class="ph-fill ph-hourglass-medium"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-base text-dark">Usulan Kegiatan: <?php echo htmlspecialchars($uPend['title']); ?></h4>
                                <span class="tag tag-amber text-xs">Menunggu Konfirmasi Admin</span>
                            </div>
                            <span class="text-xs text-muted font-mono"><?php echo date('d M Y H:i', strtotime($uPend['created_at'])); ?></span>
                        </div>
                        <p class="text-sm text-muted mb-2">
                            Pengajuan Anda telah diterima sistem dan sedang dalam antrean verifikasi oleh Learning Ops Administrator.
                        </p>
                        <div class="text-xs text-muted font-mono">
                            Target Deadline: <strong><?php echo htmlspecialchars($uPend['deadline']); ?></strong> · Durasi: <?php echo htmlspecialchars($uPend['duration']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- 3. ALARM DEADLINE K3 SITE PONGKOR -->
        <div class="notif-card urgent glass-panel mb-3 p-4" id="emp-notif-1" style="background: white; border-radius: var(--radius-md); border-left: 5px solid #ef4444;">
            <div class="flex items-start gap-3">
                <div class="mini-avatar" style="background: #fee2e2; color: #b91c1c; width: 44px; height: 44px; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="ph-fill ph-alarm"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-base text-dark" id="emp-notif-1-title">Safety Induction Site Tambang Pongkor</h4>
                            <span class="tag tag-red text-xs" id="emp-notif-1-tag">Deadline Hari Ini!</span>
                        </div>
                        <span class="text-xs text-muted font-mono">12 Agu 2026</span>
                    </div>
                    <p class="text-sm text-muted mb-3">Tenggat waktu modul wajib K3 telah tiba. Segera selesaikan sebelum izin masuk underground terpengaruh pembekuan otomatis.</p>
                    <div class="notif-actions flex gap-2">
                        <a href="?page=my_learning" class="btn-dark text-xs">
                            <i class="ph-fill ph-play"></i> Selesaikan Sekarang
                        </a>
                        <button class="btn-outline text-xs" onclick="snoozeNotif('emp-notif-1', 'Safety Induction')">
                            <i class="ph-fill ph-clock-counter-clockwise"></i> Tunda 2 Jam
                        </button>
                        <button class="btn-outline text-xs" onclick="dismissNotif('emp-notif-1')">
                            <i class="ph-fill ph-check"></i> Tandai Dibaca
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 4. ALARM REMINDER POP LEVEL 1 -->
        <div class="notif-card warning glass-panel mb-3 p-4" id="emp-notif-2" style="background: white; border-radius: var(--radius-md); border-left: 5px solid #f59e0b;">
            <div class="flex items-start gap-3">
                <div class="mini-avatar" style="background: #fef3c7; color: #b45309; width: 44px; height: 44px; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="ph-fill ph-hourglass-high"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-base text-dark" id="emp-notif-2-title">POP Level 1 — Pengawas Operasional Pertama</h4>
                            <span class="tag tag-gray text-xs" id="emp-notif-2-tag">2 Hari Lagi</span>
                        </div>
                        <span class="text-xs text-muted font-mono">14 Agu 2026</span>
                    </div>
                    <p class="text-sm text-muted mb-3">Regulasi ESDM No. 1827/2018. Wajib bagi seluruh asisten foreman & supervisor lapangan Site Pongkor.</p>
                    <div class="notif-actions flex gap-2">
                        <a href="?page=my_learning" class="btn-dark text-xs">
                            <i class="ph-fill ph-books"></i> Lanjutkan Belajar
                        </a>
                        <button class="btn-outline text-xs" onclick="snoozeNotif('emp-notif-2', 'POP Level 1')">
                            <i class="ph-fill ph-clock-counter-clockwise"></i> Tunda
                        </button>
                        <button class="btn-outline text-xs" onclick="dismissNotif('emp-notif-2')">
                            <i class="ph-fill ph-check"></i> Tandai Dibaca
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

</div>

<script>
function filterAdminNotifs(btn, category) {
    document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    const items = document.querySelectorAll('.notif-item-card');
    let visibleCount = 0;
    items.forEach(item => {
        if(category === 'all' || item.getAttribute('data-category') === category) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyBox = document.getElementById('empty-state-notif');
    if (emptyBox) {
        if (visibleCount === 0) {
            emptyBox.style.display = 'block';
            const title = document.getElementById('empty-state-title');
            const desc = document.getElementById('empty-state-desc');
            if (category === 'request') {
                if (title) title.textContent = 'Tidak Ada Usulan Kegiatan Baru';
                if (desc) desc.textContent = 'Seluruh pengajuan kegiatan dari pegawai telah dikonfirmasi atau disetujui.';
            } else if (category === 'escalation') {
                if (title) title.textContent = 'Tidak Ada Eskalasi K3 Aktif';
                if (desc) desc.textContent = 'Seluruh alarm keterlambatan kepatuhan K3 site dalam batas normal.';
            } else if (category === 'verification') {
                if (title) title.textContent = 'Tidak Ada Antrean Verifikasi Sertifikat';
                if (desc) desc.textContent = 'Semua sertifikat dan kuis pegawai telah selesai diverifikasi.';
            } else {
                if (title) title.textContent = 'Semua Notifikasi Bersih';
                if (desc) desc.textContent = 'Tidak ada notifikasi yang membutuhkan tindak lanjut saat ini.';
            }
        } else {
            emptyBox.style.display = 'none';
        }
    }
}

function resolveNotif(elementId, msg) {
    const el = document.getElementById(elementId);
    if(el) {
        el.style.opacity = '0.3';
        el.style.pointerEvents = 'none';
        setTimeout(() => {
            el.style.display = 'none';
            showToast(msg, 'success');
            if (typeof updateBadgeCount === 'function') updateBadgeCount(-1);

            const escCount = document.getElementById('count-esc');
            if (escCount) {
                let c = parseInt(escCount.textContent) || 0;
                escCount.textContent = Math.max(0, c - 1);
            }
            const allCount = document.getElementById('count-all');
            if (allCount) {
                let c = parseInt(allCount.textContent) || 0;
                allCount.textContent = Math.max(0, c - 1);
            }
        }, 300);
    }
}

function dismissNotif(elementId) {
    const el = document.getElementById(elementId);
    if(el) {
        el.style.opacity = '0.3';
        setTimeout(() => {
            el.style.display = 'none';
            showToast('Notifikasi ditandai telah dibaca.', 'info');
            if (typeof updateBadgeCount === 'function') updateBadgeCount(-1);
        }, 300);
    }
}

function snoozeNotif(elementId, course) {
    const el = document.getElementById(elementId);
    if(el) {
        el.style.opacity = '0.6';
        const tag = document.getElementById(`${elementId}-tag`);
        if (tag) {
            tag.className = 'tag tag-gray text-xs';
            tag.textContent = 'Ditunda (2 Jam)';
        }
    }
    showToast(`Alarm untuk ${course} ditunda selama 2 jam.`, 'warning');
}
</script>
