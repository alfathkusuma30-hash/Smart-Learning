<?php
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];
?>

<div class="reports-container">
    <div class="page-top-header">
        <div>
            <h1 class="page-title">Reports & Analytics</h1>
        </div>
        <div class="page-top-actions">
            <button class="btn-outline" onclick="showToast('Ekspor Excel siap.', 'info')">
                <i class="ph-fill ph-file-xls text-green"></i>
                <span>Excel</span>
            </button>
            <button class="btn-dark" onclick="showToast('Dokumen Audit (PDF) berhasil diunduh.', 'success')">
                <i class="ph-fill ph-file-pdf"></i>
                <span>PDF</span>
            </button>
        </div>
    </div>

    <!-- Analytics Top Stat Cards -->
    <div class="dash-stats-grid mb-6">
        <div class="stat-card">
            <div class="stat-card-title">KEPATUHAN KORPORAT</div>
            <div class="stat-value text-accent">78.4%</div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: 78.4%;"></div>
            </div>
            <div class="text-xs text-muted mt-2">Target ISO: 80%</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-title">TOTAL PEGAWAI</div>
            <div class="stat-value">340 <span class="text-sm font-normal text-muted">/ 380</span></div>
            <div class="flex items-center gap-2 mt-2">
                <span class="status-badge status-success text-xs">89.4% Aktif</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-title">ESKALASI OVERDUE</div>
            <div class="stat-value text-red" id="reports-overdue-val">1</div>
            <div class="flex items-center gap-2 mt-2">
                <span class="status-badge status-danger text-xs">1 Overdue</span>
            </div>
        </div>
    </div>

    <!-- Department Matrix -->
    <div class="section-title">
        <i class="ph-fill ph-buildings text-accent"></i>
        <span>Kepatuhan per Unit</span>
    </div>

    <div class="glass-panel table-responsive" style="background: white; border-radius: var(--radius-md); overflow-x: auto; margin-bottom: 2rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>DEPARTEMEN</th>
                    <th>PEGAWAI</th>
                    <th>SELESAI</th>
                    <th>COMPLIANCE</th>
                    <th>STATUS</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="font-semibold">Operasional Tambang Bawah Tanah</div>
                    </td>
                    <td>145</td>
                    <td>118 (81.3%)</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-semibold">81.3%</span>
                            <div class="progress-bar-container mt-0" style="height: 8px; width: 80px;">
                                <div class="progress-bar" style="width: 81.3%; background: #10b981;"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="status-badge status-warning text-xs">1 Overdue</span></td>
                    <td>
                        <button class="btn-outline text-xs" onclick="showToast('Membuka detail unit...', 'info')">
                            Detail
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="font-semibold">Processing Plant & Smelter</div>
                    </td>
                    <td>110</td>
                    <td>98 (89.0%)</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-semibold">89.0%</span>
                            <div class="progress-bar-container mt-0" style="height: 8px; width: 80px;">
                                <div class="progress-bar" style="width: 89%; background: #10b981;"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="status-badge status-success text-xs">Normal</span></td>
                    <td>
                        <button class="btn-outline text-xs" onclick="showToast('Membuka detail unit...', 'info')">
                            Detail
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="font-semibold">Logistik & Supply Chain</div>
                    </td>
                    <td>85</td>
                    <td>46 (54.1%)</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-semibold text-red">54.1%</span>
                            <div class="progress-bar-container mt-0" style="height: 8px; width: 80px; background:#fee2e2;">
                                <div class="progress-bar" style="width: 54.1%; background: #ef4444;"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="status-badge status-danger text-xs">Kritis</span></td>
                    <td>
                        <button class="btn-dark text-xs" onclick="showToast('Nudge terkirim.', 'success')">
                            <i class="ph-fill ph-paper-plane-tilt"></i> Nudge
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Quick Action / Broadcast Row -->
    <div class="reports-bottom-box glass-panel">
        <div class="flex items-center gap-4">
            <div class="box-icon-accent"><i class="ph-fill ph-broadcast text-accent" style="font-size: 1.6rem;"></i></div>
            <div>
                <h4 class="font-semibold text-base mb-1">Laporan Otomatis</h4>
                <span class="text-xs text-muted">Jadwal mingguan via email</span>
            </div>
        </div>
        <button class="btn-outline" onclick="showToast('Jadwal laporan disimpan.', 'success')">
            <i class="ph-fill ph-sliders"></i> Atur
        </button>
    </div>
</div>
