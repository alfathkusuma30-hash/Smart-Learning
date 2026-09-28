<?php
if ($_SESSION['user_type'] != 'Admin') {
    echo "<div class='p-6 text-center'><h2 class='text-red'>Akses ditolak</h2><p>Halaman ini khusus untuk Learning Ops Administrator.</p></div>";
    exit();
}

$allActivities = get_activities();
$pendingRequests = array_filter($allActivities, function($a) { return $a['status'] === 'menunggu_konfirmasi'; });
$approvedActivities = array_filter($allActivities, function($a) { return $a['status'] === 'disetujui'; });
$users = get_all_users();
?>

<div class="admin-panel-container">
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="page-title">Admin Console & Monitoring</h1>
                <span class="badge-role-tag">LEVEL 4 · L&D OPERATIONS</span>
            </div>
        </div>
        <div class="page-top-actions">
            <button class="btn-outline" onclick="openAssignTrainingModal()">
                <i class="ph-fill ph-user-plus"></i>
                <span>Tugaskan Modul</span>
            </button>
            <a href="?page=learning_bank" class="btn-dark">
                <i class="ph-fill ph-plus-circle"></i>
                <span>+ Modul Baru</span>
            </a>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- SECTION BARU: KONFIRMASI USULAN KEGIATAN PEGAWAI (EGY, DLL) -->
    <!-- ========================================================== -->
    <div class="section-divider-title mb-4">
        <div class="divider-left">
            <i class="ph-fill ph-stamp text-accent"></i>
            <h2>Konfirmasi Usulan Kegiatan Pegawai</h2>
            <span class="section-tag-count" id="pending-count-tag"><?php echo count($pendingRequests); ?> Menunggu Approval</span>
        </div>
    </div>

    <div id="pending-requests-container" class="mb-6">
        <?php if (count($pendingRequests) === 0): ?>
        <div class="glass-panel text-center p-6" id="no-pending-box" style="background: white; border-radius: var(--radius-md);">
            <div style="font-size: 2.25rem; color: #16a34a; margin-bottom: 0.5rem;"><i class="ph-fill ph-check-circle"></i></div>
            <h4 class="font-semibold text-base mb-1">Seluruh Usulan Kegiatan Pegawai Telah Dikonfirmasi</h4>
            <p class="text-sm text-muted">Tidak ada pengajuan kegiatan baru yang tertunda saat ini.</p>
        </div>
        <?php else: ?>
        <div class="pending-admin-grid">
            <?php foreach ($pendingRequests as $req): ?>
            <div class="admin-approval-card glass-panel" id="req-card-<?php echo $req['id']; ?>" style="background: white; border-left: 5px solid #f59e0b; border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.25rem;">
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <?php
                        $colors = ['admin' => '#10b981', 'egy' => '#d4af37', 'budi' => '#38bdf8', 'andi' => '#a855f7'];
                        $req_avatar_color = $colors[strtolower($req['user_username'] ?? '')] ?? '#0f766e';
                        ?>
                        <div class="mini-avatar" style="background: <?php echo $req_avatar_color; ?>; width: 42px; height: 42px; font-weight: 700;">
                            <?php 
                            $initials = '';
                            $parts = explode(' ', $req['user_name']);
                            foreach ($parts as $p) { $initials .= substr($p, 0, 1); }
                            echo strtoupper(substr($initials, 0, 2));
                            ?>
                        </div>
                        <div>
                            <div class="font-semibold text-base"><?php echo htmlspecialchars($req['user_name']); ?></div>
                            <div class="text-xs text-muted font-mono">User: @<?php echo htmlspecialchars($req['user_username']); ?> · Diajukan: <?php echo date('d M Y H:i', strtotime($req['created_at'])); ?></div>
                        </div>
                    </div>
                    <div>
                        <span class="status-badge status-warning text-xs">
                            <i class="ph-fill ph-hourglass-medium"></i> Menunggu Konfirmasi Admin
                        </span>
                    </div>
                </div>

                <div class="approval-course-info p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="tag tag-teal"><?php echo htmlspecialchars($req['category']); ?></span>
                        <span class="tag tag-gray"><?php echo htmlspecialchars($req['type']); ?></span>
                        <span class="tag tag-gray font-mono"><i class="ph-fill ph-clock"></i> <?php echo htmlspecialchars($req['duration']); ?></span>
                    </div>
                    <h3 class="font-semibold text-lg text-dark mt-2 mb-1"><?php echo htmlspecialchars($req['title']); ?></h3>
                    <p class="text-sm text-muted"><strong>Catatan Pegawai:</strong> <?php echo htmlspecialchars($req['notes']); ?></p>
                    <div class="text-xs text-muted font-mono mt-2"><i class="ph-fill ph-calendar"></i> Target Deadline: <strong><?php echo htmlspecialchars($req['deadline']); ?></strong></div>
                </div>

                <div class="flex items-center justify-end gap-2 flex-wrap">
                    <button class="btn-dark" onclick="adminConfirmActivity('<?php echo $req['id']; ?>', 'setujui', '<?php echo addslashes($req['title']); ?>', '<?php echo addslashes($req['user_name']); ?>')">
                        <i class="ph-fill ph-check-circle"></i>
                        <span>Konfirmasi & Setujui</span>
                    </button>
                    <button class="btn-outline" onclick="adminConfirmActivity('<?php echo $req['id']; ?>', 'tolak', '<?php echo addslashes($req['title']); ?>', '<?php echo addslashes($req['user_name']); ?>')">
                        <i class="ph-fill ph-x-circle text-red"></i>
                        <span>Tolak</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Monitoring Pegawai Table -->
    <div class="section-divider-title mb-4">
        <div class="divider-left">
            <i class="ph-fill ph-users-three text-accent"></i>
            <h2>Monitoring Pegawai Site Pongkor</h2>
        </div>
    </div>
    
    <div class="glass-panel table-responsive" style="background: white; border-radius: var(--radius-md); overflow-x: auto; margin-bottom: 2rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NAMA PEGAWAI</th>
                    <th>UNIT / DEPARTEMEN</th>
                    <th>PROGRESS BELAJAR</th>
                    <th>KEGIATAN AKTIF</th>
                    <th>STATUS ALARM</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $monitoredUsers = ['egy', 'budi', 'andi'];
                foreach ($monitoredUsers as $uKey): 
                    if (!isset($users[$uKey])) continue;
                    $u = $users[$uKey];
                    $uActs = get_activities($uKey, 'disetujui');
                    $uPending = get_activities($uKey, 'menunggu_konfirmasi');
                    
                    // compute progress
                    $uTotal = 0; $uDone = 0;
                    foreach ($uActs as $act) {
                        $uTotal += isset($act['total_modules']) ? $act['total_modules'] : 4;
                        $uDone += isset($act['completed_modules']) ? $act['completed_modules'] : 0;
                    }
                    $uPct = ($uTotal > 0) ? round(($uDone / $uTotal) * 100) : 0;
                    if ($uKey === 'egy' && $uPct === 0) $uPct = 45; // default initial demo
                    if ($uKey === 'budi' && $uPct === 0) $uPct = 94;
                ?>
                <tr id="user-row-<?php echo $uKey; ?>">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="mini-avatar" style="background: <?php echo $u['avatar_color']; ?>; width: 36px; height: 36px; font-size: 0.85rem; font-weight: 700;">
                                <?php echo $u['user_id']; ?>
                            </div>
                            <div>
                                <div class="font-semibold"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div class="text-xs text-muted font-mono"><?php echo htmlspecialchars($u['nrp']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm"><?php echo htmlspecialchars($u['role']); ?></div>
                        <div class="text-xs text-muted"><?php echo htmlspecialchars($u['dept']); ?></div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-semibold"><?php echo $uPct; ?>%</span>
                            <div class="progress-bar-container mt-0" style="height: 8px; width: 80px;">
                                <div class="progress-bar" style="width: <?php echo $uPct; ?>%;"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="tag tag-teal"><?php echo count($uActs); ?> Disetujui</span>
                        <?php if (count($uPending) > 0): ?>
                        <span class="tag tag-amber mt-1"><i class="ph-fill ph-hourglass"></i> <?php echo count($uPending); ?> Menunggu</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($uKey === 'egy'): ?>
                        <span class="status-badge status-warning text-xs" id="egy-status-badge">Deadline Hari Ini</span>
                        <?php elseif ($uKey === 'budi'): ?>
                        <span class="status-badge status-success text-xs"><i class="ph-fill ph-check"></i> Ujian Lulus</span>
                        <?php else: ?>
                        <span class="status-badge status-danger text-xs">Perlu Perhatian</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn-outline text-xs" onclick="sendWhatsAppNudge('<?php echo addslashes($u['name']); ?>', 'Modul K3 Site Pongkor')">
                                <i class="ph-fill ph-whatsapp-logo text-green"></i> Nudge WA
                            </button>
                            <button class="btn-outline text-xs" onclick="openAssignModalForUser('<?php echo addslashes($u['name']); ?>')">
                                <i class="ph-fill ph-plus"></i> Assign
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Section 3: Log Kegiatan Terkonfirmasi (Audit Trail) -->
    <div class="section-divider-title mb-4">
        <div class="divider-left">
            <i class="ph-fill ph-list-checks text-accent"></i>
            <h2>Log Kegiatan Pegawai Terkonfirmasi</h2>
            <span class="section-tag-count"><?php echo count($approvedActivities); ?> Aktif</span>
        </div>
    </div>

    <div class="glass-panel mb-6 table-responsive" style="background: white; border-radius: var(--radius-md); overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NAMA KEGIATAN</th>
                    <th>PEGAWAI</th>
                    <th>TIPE & KATEGORI</th>
                    <th>DURASI</th>
                    <th>STATUS</th>
                    <th>TANGGAL DISETUJUI</th>
                </tr>
            </thead>
            <tbody id="approved-activities-tbody">
                <?php foreach ($approvedActivities as $act): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($act['title']); ?></strong>
                        <div class="text-xs text-muted"><?php echo htmlspecialchars($act['current_module']); ?></div>
                        <?php if (!empty($act['last_attachment'])): ?>
                        <div class="mt-1 flex items-center gap-1 text-xs" style="color: #059669; font-weight: 500;">
                            <i class="ph-fill ph-file-doc"></i>
                            <span>Tugas Masuk: <strong><?php echo htmlspecialchars($act['last_attachment']); ?></strong></span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="font-semibold text-sm"><?php echo htmlspecialchars($act['user_name']); ?></div>
                        <div class="text-xs text-muted font-mono">@<?php echo htmlspecialchars($act['user_username']); ?></div>
                    </td>
                    <td>
                        <span class="tag tag-teal"><?php echo htmlspecialchars($act['category']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($act['duration']); ?></td>
                    <td>
                        <span class="status-badge status-success text-xs"><i class="ph-fill ph-check-circle"></i> Aktif (<?php echo isset($act['progress']) ? $act['progress'] : 0; ?>%)</span>
                        <?php if (!empty($act['last_attachment'])): ?>
                        <span class="status-badge status-warning text-xs mt-1 block" style="font-size: 0.7rem;"><i class="ph-fill ph-paperclip"></i> Ada Lampiran</span>
                        <?php endif; ?>
                    </td>
                    <td class="font-mono text-xs"><?php echo !empty($act['approved_at']) ? date('d M Y H:i', strtotime($act['approved_at'])) : 'Otomatis'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
