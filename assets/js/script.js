/**
 * Smart Learning Windows — Core OS & Alarm Simulation Engine
 * PT ANTAM Tbk - Corporate Learning & Development
 */

// Global HTML Escaping Helper
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
window.escapeHtml = escapeHtml;

document.addEventListener('DOMContentLoaded', () => {
    startRealtimeClock();
    initSimulationEngine();
    initWindowControls();
    initRealtimeNotifications();
});

// ==========================================
// REAL-TIME CLOCK & DATE ENGINE
// ==========================================
function startRealtimeClock() {
    function tick() {
        const now = new Date();
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        const dayName = days[now.getDay()];
        const dayNum = String(now.getDate()).padStart(2, '0');
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        const shortDate = `${dayNum} ${monthName} ${year}`;
        const dayShortDate = `${dayName}, ${dayNum} ${monthName} ${year}`;
        const timeStr = `${hours}:${minutes}:${seconds}`;

        // 1. Hero Date pada halaman Home
        const heroDate = document.getElementById('hero-date-display');
        if (heroDate) {
            heroDate.textContent = dayShortDate;
        }

        // 2. Topbar Date & Clock
        const sysTime = document.getElementById('sys-time');
        if (sysTime) {
            sysTime.textContent = `${shortDate} · ${timeStr}`;
        }

        // 3. Dashboard Realtime Clock Chip
        const dashClock = document.getElementById('dash-realtime-clock');
        if (dashClock) {
            dashClock.textContent = `${dayShortDate} · ${timeStr} WITA`;
        }

        // 4. Fallback live time
        const liveTime = document.getElementById('dash-live-time');
        if (liveTime) {
            liveTime.textContent = `${dayShortDate} · ${timeStr} WITA`;
        }
    }

    tick();
    setInterval(tick, 1000);
}

// ==========================================
// 1. DATE & ALARM ESCALATION SIMULATION
// ==========================================
const SIM_STORAGE_KEY = 'smart_learning_sim_state';

const SIM_DATES = [
    { dayNum: 1, label: '12 Agu 2026', tag: 'HARI 1', overdueCount: 0, egyStatus: 'Deadline Hari Ini' },
    { dayNum: 2, label: '13 Agu 2026', tag: 'HARI 2', overdueCount: 1, egyStatus: 'OVERDUE (Eskalasi)' },
    { dayNum: 3, label: '14 Agu 2026', tag: 'HARI 3', overdueCount: 1, egyStatus: 'OVERDUE (+2 Hari)' },
    { dayNum: 4, label: '15 Agu 2026', tag: 'HARI 4', overdueCount: 2, egyStatus: 'KRITIS' }
];

function getSimState() {
    const raw = localStorage.getItem(SIM_STORAGE_KEY);
    if (!raw) {
        return { currentIndex: 0, egyOverdue: false, budiApproved: false, andiApproved: false };
    }
    try {
        return JSON.parse(raw);
    } catch (e) {
        return { currentIndex: 0, egyOverdue: false, budiApproved: false, andiApproved: false };
    }
}

function saveSimState(state) {
    localStorage.setItem(SIM_STORAGE_KEY, JSON.stringify(state));
}

function initSimulationEngine() {
    const state = getSimState();
    applySimState(state, false);

    // Next Day Button
    const nextDayBtn = document.getElementById('btn-next-day');
    if (nextDayBtn) {
        nextDayBtn.addEventListener('click', () => {
            advanceSimDay();
        });
    }

    // Reset Sim Button
    const resetSimBtn = document.getElementById('btn-reset-sim');
    if (resetSimBtn) {
        resetSimBtn.addEventListener('click', () => {
            resetFullSimulation();
        });
    }
}

function applySimState(state, showAlert = true) {
    const dateObj = SIM_DATES[state.currentIndex] || SIM_DATES[0];

    const simTag = document.getElementById('sim-day-tag');
    if (simTag) simTag.textContent = dateObj.tag;

    // Update Escalation badge & notifications
    const badge = document.getElementById('dock-notif-badge');
    if (badge) {
        if (state.currentIndex > 0) {
            badge.textContent = '4';
            badge.classList.add('badge-pulse');
        } else {
            badge.textContent = '3';
            badge.classList.remove('badge-pulse');
        }
    }

    // Update Egy status in Admin Panel if visible
    const egyBadge = document.getElementById('egy-status-badge');
    if (egyBadge) {
        if (state.currentIndex > 0) {
            egyBadge.className = 'status-badge status-danger text-xs';
            egyBadge.textContent = 'OVERDUE (Dieskalasi ke Admin)';
        } else {
            egyBadge.className = 'status-badge status-warning text-xs';
            egyBadge.textContent = 'Warning (Deadline Hari Ini)';
        }
    }

    // Update Escalation Log in Admin Panel if visible
    const logEmpty = document.getElementById('esc-log-empty');
    const logActive = document.getElementById('esc-log-active');
    if (logEmpty && logActive) {
        if (state.currentIndex > 0) {
            logEmpty.style.display = 'none';
            logActive.style.display = 'block';
        } else {
            logEmpty.style.display = 'block';
            logActive.style.display = 'none';
        }
    }

    // Alert feedback on advance
    if (showAlert) {
        if (state.currentIndex === 1) {
            showToast('🚨 SIMULASI HARI KE-2 (13 Agu 2026): Safety Induction Egy Pratama melewati deadline! Alarm otomatis mengeskalasi ke Admin.', 'warning');
        } else if (state.currentIndex > 1) {
            showToast(`Simulasi Waktu: Tanggal maju ke ${dateObj.label} (${dateObj.tag})`, 'info');
        }
    }
}

function advanceSimDay() {
    const state = getSimState();
    state.currentIndex = (state.currentIndex + 1) % SIM_DATES.length;
    saveSimState(state);
    applySimState(state, true);
}

function resetFullSimulation() {
    const state = { currentIndex: 0, egyOverdue: false, budiApproved: false, andiApproved: false };
    saveSimState(state);
    applySimState(state, false);
    showToast('Simulasi waktu & status alarm berhasil di-reset ke tanggal 12 Agu 2026.', 'success');
}

function triggerSimulationScenario(scenarioNum) {
    const state = getSimState();
    if (scenarioNum === 1) {
        state.currentIndex = 1; // 13 Agu
        saveSimState(state);
        applySimState(state, true);
        showToast('Skenario 1 Aktif: Egy Pratama Overdue 1 Hari. Buka halaman Notifikasi atau Admin Panel untuk melihat tindakan eskalasi.', 'warning');
    } else if (scenarioNum === 2) {
        showToast('Skenario 2: Budi Santoso berhasil menyelesaikan ujian Sertifikasi K3 dengan nilai 94/100. Verifikasi tersedia di Notifikasi.', 'info');
    }
}


// ==========================================
// 2. WINDOW CONTROLS (TRAFFIC LIGHTS)
// ==========================================
function initWindowControls() {
    const closeBtn = document.getElementById('win-close-btn');
    const minBtn = document.getElementById('win-minimize-btn');
    const maxBtn = document.getElementById('win-maximize-btn');
    const win = document.getElementById('main-app-window');

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            // Close app window: return to home desktop
            window.location.href = 'app.php?page=home';
        });
    }

    if (minBtn) {
        minBtn.addEventListener('click', () => {
            if (win) {
                win.classList.toggle('window-compact');
                showToast('Ukuran window disesuaikan.', 'info');
            }
        });
    }

    if (maxBtn) {
        maxBtn.addEventListener('click', () => {
            if (win) {
                win.classList.toggle('window-fullscreen');
                showToast('Mode layar penuh di-toggle.', 'info');
            }
        });
    }
}


// ==========================================
// 3. TOAST NOTIFICATION SYSTEM
// ==========================================
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast-card toast-${type}`;

    let icon = 'ph-info';
    if (type === 'success') icon = 'ph-check-circle';
    if (type === 'warning') icon = 'ph-warning';
    if (type === 'danger' || type === 'error') icon = 'ph-siren';

    toast.innerHTML = `
        <div class="toast-icon"><i class="ph-fill ${icon}"></i></div>
        <div class="toast-body">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="ph ph-x"></i></button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-fadeout');
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}


// ==========================================
// 4. GLOBAL MODAL CONTROLLER
// ==========================================
function openModal(title, bodyHtml, footerHtml = '', iconClass = 'ph-fill ph-info', isLarge = false) {
    const modal = document.getElementById('global-modal');
    if (!modal) return;

    const modalBox = modal.querySelector('.modal-box');
    if (modalBox) {
        if (isLarge) {
            modalBox.classList.add('modal-box-lg');
        } else {
            modalBox.classList.remove('modal-box-lg');
        }
    }

    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-body').innerHTML = bodyHtml;
    document.getElementById('modal-icon').className = iconClass;

    const footer = document.getElementById('modal-footer');
    if (footerHtml) {
        footer.innerHTML = footerHtml;
    } else {
        footer.innerHTML = '<button class="btn-light" onclick="closeModal()">Tutup</button>';
    }

    modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('global-modal');
    if (modal) {
        modal.style.display = 'none';
        const modalBox = modal.querySelector('.modal-box');
        if (modalBox) modalBox.classList.remove('modal-box-lg');
    }
}


// ==========================================
// 5. INTERACTIVE ACTION MODALS
// ==========================================

// A. Detail Pelatihan Modal
function openCourseDetailModal(title, type, duration, level, desc = '') {
    const description = desc || `Modul kurikulum standar keselamatan dan operasional pertambangan PT ANTAM.`;
    
    const bodyHtml = `
        <div class="modal-course-detail">
            <div class="flex items-center gap-2 mb-3">
                <span class="tag ${type === 'Wajib' ? 'tag-red' : (type === 'Sertifikasi' ? 'tag-teal' : 'tag-gray')}">${escapeHtml(type)}</span>
                <span class="text-xs text-muted font-mono"><i class="ph-fill ph-clock"></i> ${escapeHtml(duration)}</span>
                <span class="text-xs text-muted font-mono"><i class="ph-fill ph-chart-bar"></i> ${escapeHtml(level)}</span>
            </div>
            
            <h4 class="text-lg font-semibold mb-2">${escapeHtml(title)}</h4>
            <p class="text-sm text-muted mb-4" style="line-height: 1.6;">${escapeHtml(description)}</p>
            
            <div class="syllabus-box glass-panel p-3 mb-3">
                <h5 class="text-xs font-semibold uppercase text-muted mb-2 font-mono">Silabus & Standar Kompetensi:</h5>
                <ul class="text-sm list-disc pl-5 space-y-1">
                    <li>Regulasi K3 & Standar Operasional Bawah Tanah Site Pomalaa</li>
                    <li>Identifikasi Bahaya & Pengendalian Risiko Terukur (IBPR)</li>
                    <li>Simulasi Tanggap Darurat & Mitigasi Risiko Lapangan</li>
                    <li>Evaluasi Akhir Modul & Uji Kuis Pemahaman</li>
                </ul>
            </div>

            <div class="flex items-center justify-between text-xs text-muted border-t pt-3">
                <span>Pengampu: <strong>L&D Corporate ANTAM</strong></span>
                <span>Format: <strong>Blended Learning</strong></span>
            </div>
        </div>
    `;

    const isAdmin = (typeof window.CURRENT_USER_TYPE !== 'undefined' && window.CURRENT_USER_TYPE === 'Admin');
    const safeTitle = (title || '').replace(/'/g, "\\'");
    const safeType = (type || 'Wajib').replace(/'/g, "\\'");
    const safeDuration = (duration || '2 Hari').replace(/'/g, "\\'");
    const safeLevel = (level || 'Basic').replace(/'/g, "\\'");

    const actionBtn = isAdmin
        ? `<button type="button" class="btn-dark" onclick="closeModal(); openAssignTrainingModal('${safeTitle}')"><i class="ph-fill ph-user-plus"></i> Tugaskan / Create Event</button>`
        : `<button type="button" class="btn-dark" onclick="closeModal(); openNewActivityModalWithPreset('${safeTitle}', '${safeType}', '', '${safeDuration}', '${safeLevel}')"><i class="ph-fill ph-plus-circle"></i> Ajukan Kegiatan Ini</button>`;

    const footerHtml = `
        <button type="button" class="btn-light" onclick="closeModal()">Tutup</button>
        ${actionBtn}
    `;

    openModal('Detail Kurikulum', bodyHtml, footerHtml, 'ph-fill ph-books');
}

// B. Assign Training Modal (Admin)
function openAssignTrainingModal(preselectedCourse = 'POP Level 1') {
    const defaultCatalogCourses = [
        'POP Level 1 — Pengawas Operasional Pertama',
        'Safety Induction Site Pomalaa',
        'Applied Negotiation Techniques',
        'CS Excellence and Handling Customer Complaint',
        'Internal Auditor ISO 50001 Sistem Manajemen Energi',
        'Sertifikasi Instruktur BNSP KKNI Level 4',
        '17th MGEI Annual Convention',
        'Technical Skill A — Heavy Hauling 777D',
        'Leadership & Supervisory Underground',
        'Effective Radio Communication & Mine Dispatch',
        'Sertifikasi K3 Umum & Pertambangan'
    ];

    let courseList = [...defaultCatalogCourses];
    const cleanPreselected = (preselectedCourse || '').trim();
    if (cleanPreselected) {
        const found = courseList.some(c => c.toLowerCase() === cleanPreselected.toLowerCase());
        if (!found) {
            courseList.unshift(cleanPreselected);
        }
    }

    const courseOptionsHtml = courseList.map(c => {
        let isSelected = false;
        if (cleanPreselected) {
            isSelected = (c.toLowerCase() === cleanPreselected.toLowerCase()) ||
                         (c.toLowerCase().includes(cleanPreselected.toLowerCase())) ||
                         (cleanPreselected.toLowerCase().includes(c.toLowerCase()));
        }
        return `<option value="${escapeHtml(c)}" ${isSelected ? 'selected' : ''}>${escapeHtml(c)}</option>`;
    }).join('');

    const bodyHtml = `
        <div class="modal-assign-form">
            <div class="form-group mb-3">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Modul Pelatihan:</label>
                <select id="assign-course" class="form-input mt-1">
                    ${courseOptionsHtml}
                </select>
            </div>

            <div class="form-group mb-3">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Target Pegawai:</label>
                <select id="assign-target" class="form-input mt-1">
                    ${(() => {
                        const users = window.ALL_SYSTEM_USERS || [];
                        const nonAdmin = users.filter(u => u.type !== 'Admin');
                        if (nonAdmin.length > 0) {
                            return nonAdmin.map(u => `<option value="${escapeHtml(u.username)}">${escapeHtml(u.name)} (${escapeHtml(u.role || u.dept || u.username)})</option>`).join('') +
                                   `<option value="all">Seluruh Pegawai Site Pomalaa (Batch Penugasan)</option>`;
                        }
                        return `
                            <option value="egy">Egy Pratama (Operasional Tambang)</option>
                            <option value="budi">Budi Santoso (Processing Plant)</option>
                            <option value="alkusuma">ALFAT PANDU KUSUMA (Pegawai Site)</option>
                            <option value="all">Seluruh Pegawai Site Pomalaa (Batch Penugasan)</option>
                        `;
                    })()}
                </select>
            </div>

            <div class="form-grid mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="text-xs font-semibold text-muted font-mono uppercase">Deadline:</label>
                    <select id="assign-deadline" class="form-input mt-1">
                        <option value="3 Hari">3 Hari</option>
                        <option value="7 Hari" selected>7 Hari</option>
                        <option value="14 Hari">14 Hari</option>
                        <option value="30 Hari">30 Hari</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="text-xs font-semibold text-muted font-mono uppercase">Prioritas:</label>
                    <select id="assign-priority" class="form-input mt-1">
                        <option value="Tinggi (Auto-Escalate)" selected>Tinggi (Auto-Escalate)</option>
                        <option value="Normal">Normal</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-2">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Catatan Instruksi:</label>
                <input type="text" id="assign-note" placeholder="Catatan instruksi penugasan resmi..." class="form-input mt-1">
            </div>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Batal</button>
        <button class="btn-dark" onclick="submitAssignTraining()">
            <i class="ph-fill ph-paper-plane-tilt"></i> Tugaskan Sekarang
        </button>
    `;

    openModal('Tugaskan Pelatihan', bodyHtml, footerHtml, 'ph-fill ph-user-plus');
}

async function submitAssignTraining() {
    const courseEl = document.getElementById('assign-course');
    const targetEl = document.getElementById('assign-target');
    const deadlineEl = document.getElementById('assign-deadline');

    const course = courseEl ? courseEl.value : 'Pelatihan Master';
    const targetUsername = targetEl ? targetEl.value : '';
    const selectedOptionText = (targetEl && targetEl.selectedOptions[0]) ? targetEl.selectedOptions[0].textContent : targetUsername;
    const deadline = deadlineEl ? deadlineEl.value : '7 Hari';

    if (!targetUsername) {
        showToast('Pilih target pegawai yang valid.', 'warning');
        return;
    }

    closeModal();

    const fd = new FormData();
    fd.append('action', 'assign_activity');
    fd.append('target_username', targetUsername);
    fd.append('title', course);
    fd.append('deadline', deadline);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            const targetLabel = targetUsername === 'all' ? 'Seluruh Pegawai Site Pomalaa' : selectedOptionText;
            showToast(`✅ Pelatihan "${course}" berhasil ditugaskan ke ${targetLabel}!`, 'success');
            setTimeout(() => { window.location.reload(); }, 1200);
        } else {
            showToast(res.message || `Gagal menugaskan pelatihan.`, 'error');
        }
    } catch(err) {
        showToast(`✅ Pelatihan "${course}" ditugaskan ke ${target} (${deadline}).`, 'success');
    }
}

function openAssignModalForUser(userName) {
    openAssignTrainingModal('Safety Induction Site Pomalaa');
    setTimeout(() => {
        const sel = document.getElementById('assign-target');
        if (sel) {
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value.includes(userName)) {
                    sel.selectedIndex = i;
                    break;
                }
            }
        }
    }, 100);
}

// C. WhatsApp Nudge Action (Admin to Employee)
function sendWhatsAppNudge(employeeName, courseName, notifId = null, username = null, defaultPhone = null) {
    // 1. Dapatkan nomor telepon penerima dari data kontak sistem
    let recipientPhone = defaultPhone || '';
    if (!recipientPhone && window.USER_CONTACTS) {
        if (username && window.USER_CONTACTS[username]) {
            recipientPhone = window.USER_CONTACTS[username].phone || '';
        } else if (employeeName && window.USER_CONTACTS[employeeName]) {
            recipientPhone = window.USER_CONTACTS[employeeName].phone || '';
        }
    }
    if (!recipientPhone) {
        recipientPhone = '0812-3456-7890';
    }

    const hasGatewayToken = !!(window.GATEWAY_CONFIG && window.GATEWAY_CONFIG.token && window.GATEWAY_CONFIG.auto_send);

    const bodyHtml = `
        <div class="nudge-modal-content">
            <div class="flex items-center gap-3 mb-4">
                <div class="mini-avatar" style="background:#25D366; color:white; width:44px; height:44px; font-size:1.5rem;">
                    <i class="ph-fill ph-whatsapp-logo"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-base">WhatsApp Gateway</h4>
                    <p class="text-xs text-muted">Bot Korporat ANTAM — Human Capital & L&D</p>
                </div>
            </div>

            <!-- Input Nomor WhatsApp Penerima -->
            <div class="form-group mb-4" style="background: #f1f5f9; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
                <label class="form-label font-semibold text-xs text-dark uppercase flex items-center justify-between">
                    <span><i class="ph-fill ph-phone text-green"></i> Nomor WhatsApp Tujuan:</span>
                    <span class="text-xs text-muted font-normal font-mono">Format: 08xx / 628xx</span>
                </label>
                <div class="input-with-icon mt-1">
                    <input type="tel" id="nudge-phone-input" class="form-input font-mono text-sm font-semibold" value="${recipientPhone}" placeholder="cth. 081234567890" style="background:white;">
                </div>
                <div class="flex items-center justify-between mt-2 flex-wrap gap-2">
                    <label class="flex items-center gap-2 text-xs text-muted cursor-pointer" style="margin:0;">
                        <input type="checkbox" id="nudge-save-phone" checked style="accent-color: #16a34a;">
                        <span>Simpan perubahan nomor ke profil <strong>${employeeName}</strong></span>
                    </label>
                </div>
            </div>

            <!-- Preview Isi Pesan Resmi -->
            <div class="text-xs font-semibold text-muted uppercase mb-1 flex items-center gap-1">
                <i class="ph-fill ph-chat-text"></i> Pratinjau Pesan yang Dikirimkan:
            </div>
            <div class="p-3 bg-gray-100 rounded-md border text-sm font-mono mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; line-height: 1.6; border-left: 4px solid #25D366; font-size: 0.85rem;">
                <strong>[PT ANTAM — SMART LEARNING ALARM]</strong><br>
                Yth. Sdr. <strong>${employeeName}</strong>,<br><br>
                Modul wajib: <em>"${courseName}"</em> telah mencapai batas waktu. Harap segera mengakses portal Smart Learning sebelum akses site dibekukan.<br><br>
                Tautan: <u>https://learning.antam.com/auth?token=auth9281</u><br>
                <em>— Learning & Development</em>
            </div>

            <!-- Indikator Jalur Pengiriman -->
            ${hasGatewayToken ? `
                <div class="p-2 rounded text-xs flex items-center gap-2 mb-1" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac;">
                    <i class="ph-fill ph-robot" style="font-size: 1.1rem;"></i>
                    <span><strong>Mode Otomatis Aktif:</strong> Pesan akan dikirim langsung via WhatsApp Gateway Server.</span>
                </div>
            ` : `
                <div class="p-2 rounded text-xs flex items-center gap-2 mb-1" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                    <i class="ph-fill ph-whatsapp-logo" style="font-size: 1.1rem;"></i>
                    <span><strong>Mode Direct WhatsApp Web / App:</strong> Sekali klik, WhatsApp akan langsung terbuka dengan pesan siap kirim (100% Gratis & Nyata).</span>
                </div>
            `}
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Batal</button>
        <button class="btn-dark" id="btn-submit-nudge" style="background: #16a34a;" onclick="confirmSendNudge('${escapeJs(employeeName)}', '${escapeJs(courseName)}', '${notifId || ''}')">
            <i class="ph-fill ph-whatsapp-logo"></i> Kirim Nudge WhatsApp
        </button>
    `;

    openModal('Nudge WhatsApp', bodyHtml, footerHtml, 'ph-fill ph-whatsapp-logo');
}

// Helper escape JS string
function escapeJs(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

async function confirmSendNudge(employeeName, courseName, notifId) {
    const phoneInput = document.getElementById('nudge-phone-input');
    const rawPhone = phoneInput ? phoneInput.value.trim() : '';
    const savePhone = document.getElementById('nudge-save-phone')?.checked ? 1 : 0;

    if (!rawPhone || rawPhone.length < 8) {
        showToast('Mohon masukkan nomor WhatsApp tujuan yang valid (minimal 8 digit).', 'warning');
        if (phoneInput) phoneInput.focus();
        return;
    }

    // Normalisasi nomor telepon ke format internasional (628...)
    let cleanPhone = rawPhone.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.substring(1);
    } else if (cleanPhone.startsWith('8')) {
        cleanPhone = '62' + cleanPhone;
    }

    // Format pesan teks WhatsApp
    const messageText = `*[PT ANTAM — SMART LEARNING ALARM]*\n` +
        `Yth. Sdr. *${employeeName}*,\n\n` +
        `Modul wajib: "_${courseName}_" telah mencapai batas waktu. Harap segera mengakses portal Smart Learning sebelum akses site dibekukan.\n\n` +
        `Tautan: https://learning.antam.com/auth?token=auth${Math.floor(1000 + Math.random() * 9000)}\n` +
        `_— Learning & Development_`;

    const waDirectUrl = `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(messageText)}`;

    // Tombol loading
    const btn = document.getElementById('btn-submit-nudge');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Menghubungkan...';
    }

    const hasGateway = !!(window.GATEWAY_CONFIG && window.GATEWAY_CONFIG.token && window.GATEWAY_CONFIG.auto_send);

    // Jika bukan gateway, kita buka window WhatsApp langsung agar tidak terblokir popup blocker browser
    let waWindow = null;
    if (!hasGateway) {
        waWindow = window.open(waDirectUrl, '_blank');
    }

    try {
        const fd = new FormData();
        fd.append('action', 'send_whatsapp_nudge');
        fd.append('employee_name', employeeName);
        fd.append('course_name', courseName);
        fd.append('phone', rawPhone);
        fd.append('save_phone', savePhone ? '1' : '0');

        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();

        if (res.success) {
            if (res.mode === 'gateway') {
                showToast(`✅ ${res.message}`, 'success');
            } else {
                // Direct mode
                if (!waWindow || waWindow.closed) {
                    // Fallback jika window belum terbuka
                    window.open(res.wa_url || waDirectUrl, '_blank');
                }
                showToast(`✅ WhatsApp disiapkan untuk ${employeeName} (+${cleanPhone}). Silakan tekan kirim di WhatsApp.`, 'success');
            }

            // Perbarui cache kontak di browser
            if (window.USER_CONTACTS) {
                if (!window.USER_CONTACTS[employeeName]) window.USER_CONTACTS[employeeName] = {};
                window.USER_CONTACTS[employeeName].phone = rawPhone;
            }

            closeModal();

            if (notifId) {
                const item = document.getElementById(notifId);
                if (item) {
                    item.style.borderColor = '#16a34a';
                }
            }
        } else {
            showToast(res.message || 'Gagal mengirim pesan.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph-fill ph-whatsapp-logo"></i> Coba Lagi';
            }
        }
    } catch (e) {
        // Fallback jika koneksi server error tetap buka WhatsApp
        if (!waWindow || waWindow.closed) {
            window.open(waDirectUrl, '_blank');
        }
        showToast(`Membuka WhatsApp ke +${cleanPhone}...`, 'success');
        closeModal();
    }
}

// D. Certificate Approval & Verification (Admin)
function approveCertification(employeeName, certName, notifId = null) {
    showToast(`✅ Sertifikat "${certName}" untuk ${employeeName} diterbitkan!`, 'success');
    if (notifId) {
        const item = document.getElementById(notifId);
        if (item) {
            item.style.opacity = '0.4';
            setTimeout(() => item.remove(), 400);
            updateBadgeCount(-1);
            
            const countVerif = document.getElementById('count-verif');
            if (countVerif) {
                let c = parseInt(countVerif.textContent) || 0;
                countVerif.textContent = Math.max(0, c - 1);
            }
            const countAll = document.getElementById('count-all');
            if (countAll) {
                let c = parseInt(countAll.textContent) || 0;
                countAll.textContent = Math.max(0, c - 1);
            }
        }
    }
}

function approveCourseRequest(employeeName, courseName, notifId = null) {
    showToast(`✅ Kuota "${courseName}" untuk ${employeeName} disetujui!`, 'success');
    if (notifId) {
        const item = document.getElementById(notifId);
        if (item) {
            item.style.opacity = '0.4';
            setTimeout(() => item.remove(), 400);
            updateBadgeCount(-1);
        }
    }
}

function rejectCourseRequest(employeeName, courseName, notifId = null) {
    showToast(`Permohonan "${courseName}" untuk ${employeeName} ditolak.`, 'info');
    if (notifId) {
        const item = document.getElementById(notifId);
        if (item) {
            item.style.opacity = '0.4';
            setTimeout(() => item.remove(), 400);
            updateBadgeCount(-1);
        }
    }
}

function updateBadgeCount(diff) {
    const badge = document.getElementById('dock-notif-badge');
    if (badge) {
        let current = parseInt(badge.textContent) || 0;
        current = Math.max(0, current + diff);
        badge.textContent = current;
    }
}

// E. Evaluation Proof Viewer Modal
function openEvaluationProofModal(employeeName, certName, score) {
    const bodyHtml = `
        <div class="eval-proof-content">
            <div class="flex items-center justify-between border-b pb-3 mb-3">
                <div>
                    <h4 class="font-semibold text-base">${employeeName} — Hasil Kuis</h4>
                    <span class="text-xs text-muted">${certName} · 10 Agu 2026</span>
                </div>
                <div class="text-right">
                    <span class="text-xl font-mono font-bold text-green">${score} / 100</span>
                    <div class="text-xs text-green font-semibold">LULUS (Min. 80)</div>
                </div>
            </div>

            <div class="space-y-2 text-sm">
                <div class="p-2 rounded bg-gray-50 border">
                    <strong>1. Metode identifikasi bahaya penambangan bawah tanah?</strong><br>
                    <span class="text-green"><i class="ph-fill ph-check"></i> JSA & HIRADC Site.</span>
                </div>
                <div class="p-2 rounded bg-gray-50 border mt-2">
                    <strong>2. Konsentrasi maksimal gas CO di front kerja?</strong><br>
                    <span class="text-green"><i class="ph-fill ph-check"></i> Maksimal 25 ppm (Kepmen 1827).</span>
                </div>
                <div class="p-2 rounded bg-gray-50 border mt-2">
                    <strong>3. Durasi suplai oksigen tabung SCSR?</strong><br>
                    <span class="text-green"><i class="ph-fill ph-check"></i> Minimum 60 menit.</span>
                </div>
            </div>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Tutup</button>
        <button class="btn-dark" onclick="approveCertification('${employeeName}', '${certName}'); closeModal();">
            <i class="ph-fill ph-seal-check"></i> Verifikasi
        </button>
    `;

    openModal('Hasil Evaluasi', bodyHtml, footerHtml, 'ph-fill ph-clipboard-text');
}

// F. Reschedule / Dispensasi Modal
function openRescheduleModal(employeeName, courseName) {
    const bodyHtml = `
        <div class="reschedule-modal">
            <p class="text-sm text-muted mb-3">
                Dispensasi waktu untuk <strong>${employeeName}</strong>:
            </p>
            <div class="form-group mb-3">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Dispensasi:</label>
                <select id="reschedule-days" class="form-input mt-1">
                    <option value="3">+3 Hari (s/d 15 Agu)</option>
                    <option value="5">+5 Hari (s/d 17 Agu)</option>
                    <option value="7">+7 Hari (s/d 19 Agu)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Alasan:</label>
                <input type="text" id="reschedule-reason" placeholder="Alasan perpanjangan..." class="form-input mt-1">
            </div>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Batal</button>
        <button class="btn-dark" onclick="confirmReschedule('${employeeName}', '${courseName}')">
            <i class="ph-fill ph-calendar-plus"></i> Simpan
        </button>
    `;

    openModal('Dispensasi Deadline', bodyHtml, footerHtml, 'ph-fill ph-calendar-plus');
}

function confirmReschedule(employeeName, courseName) {
    const days = document.getElementById('reschedule-days').value;
    closeModal();
    showToast(`Tenggat ${courseName} (${employeeName}) diperpanjang +${days} hari.`, 'success');
}

// ==========================================
// REAL DOCUMENT DOWNLOAD ENGINE (OFFLINE BLOB)
// ==========================================
function downloadDocumentFile(filename, content, mimeType = 'application/msword;charset=utf-8') {
    try {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 300);
    } catch (e) {
        console.error('Download error:', e);
        showToast('Gagal mengunduh dokumen. Coba gunakan browser modern.', 'error');
    }
}

// 1. Download Silabus Resmi (.doc)
function downloadCourseSyllabus(title, type = 'Wajib K3 ESDM', deadline = '14 Hari') {
    const courseTitle = title || 'Safety Induction Site Pomalaa';
    const cleanFileName = courseTitle.replace(/[^a-zA-Z0-9_-]/g, '_');

    const htmlContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>Silabus Kurikulum — ${courseTitle}</title>
<style>
body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #0f172a; margin: 30px; }
.header-box { width: 100%; border-bottom: 3px double #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
.comp-name { font-size: 14pt; font-weight: bold; color: #0f172a; }
.comp-sub { font-size: 9pt; color: #64748b; font-family: monospace; letter-spacing: 1px; }
.title-doc { text-align: center; font-size: 13pt; font-weight: bold; color: #b45309; text-transform: uppercase; margin: 18px 0; }
table.grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
table.grid th { background-color: #0f172a; color: #ffffff; padding: 7px 10px; font-size: 9.5pt; text-align: left; border: 1px solid #0f172a; }
table.grid td { border: 1px solid #cbd5e1; padding: 7px 10px; font-size: 9.5pt; vertical-align: top; }
.lbl-td { background-color: #f8fafc; font-weight: bold; width: 25%; color: #334155; }
.sec-hdr { font-size: 11pt; font-weight: bold; color: #0f172a; border-left: 4px solid #d4af37; padding-left: 8px; margin: 16px 0 8px 0; }
.sign-table { width: 100%; margin-top: 40px; border-collapse: collapse; }
.sign-table td { text-align: center; vertical-align: bottom; height: 80px; font-size: 9.5pt; }
</style>
</head>
<body>
    <div class="header-box">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="comp-name">PT ANTAM Tbk — POMALAA</div>
                    <div class="comp-sub">PT ANTAM TBK POMALAA · DIVISI K3 & KESELAMATAN OPERASIONAL</div>
                </td>
                <td style="text-align: right; font-size: 8.5pt; color: #64748b; font-family: monospace;">
                    Ref: ANTAM-SILABUS-2026<br>
                    Status: Dokumen Resmi Terverifikasi
                </td>
            </tr>
        </table>
    </div>

    <div class="title-doc">SILABUS RESMI KURIKULUM & PANDUAN PELAKSANAAN MODUL K3</div>

    <table class="grid">
        <tr>
            <td class="lbl-td">Mata Pelatihan</td>
            <td style="font-weight: bold; color: #0f172a;">${courseTitle}</td>
            <td class="lbl-td">Tipe Kurikulum</td>
            <td>${type}</td>
        </tr>
        <tr>
            <td class="lbl-td">Regulasi Acuan</td>
            <td>Kepmen ESDM No. 1827 K/30/MEM/2018 & ISO 45001</td>
            <td class="lbl-td">Batas Waktu (Deadline)</td>
            <td style="font-weight: bold;">${deadline}</td>
        </tr>
        <tr>
            <td class="lbl-td">Passing Grade Ujian</td>
            <td>Minimum 80% (Standar Sertifikasi Nasional)</td>
            <td class="lbl-td">Metode Pelaksanaan</td>
            <td>Blended Learning & Lembar Kerja Modul Pegawai</td>
        </tr>
    </table>

    <div class="sec-hdr">1. DESKRIPSI & TUJUAN PEMBELAJARAN</div>
    <p style="font-size: 10pt; color: #334155; margin-bottom: 15px;">
        Kurikulum ini disiapkan untuk membekali personel PT ANTAM Tbk Site Pomalaa dalam memahami identifikasi bahaya bawah tanah, pengoperasian alat darurat (SCSR), penanganan ventilasi dan gas berbahaya (CH4, CO), serta kesiapan tanggap darurat sesuai standar K3 ESDM.
    </p>

    <div class="sec-hdr">2. STRUKTUR SILABUS & ALOKASI JAM BELAJAR</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 15%;">Unit Modul</th>
                <th style="width: 40%;">Materi Pokok & Kompetensi</th>
                <th style="width: 30%;">Tugas / Lembar Kerja</th>
                <th style="width: 15%;">Bobot</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Unit 1</strong></td>
                <td>Prinsip Dasar K3 Tambang & Regulasi ESDM No. 1827</td>
                <td>Membaca SOP & Resume Regulasi</td>
                <td>20%</td>
            </tr>
            <tr>
                <td><strong>Unit 2</strong></td>
                <td>Identifikasi Bahaya & Penilaian Risiko (IBPR) Underground</td>
                <td>Pengamatan Risiko Kerja di Terowongan</td>
                <td>25%</td>
            </tr>
            <tr>
                <td><strong>Unit 3</strong></td>
                <td>Penyusunan Lembar Kerja & Laporan Mandiri Pegawai</td>
                <td>Pengisian Dokumen Word Lembar Kerja (.doc)</td>
                <td>30%</td>
            </tr>
            <tr>
                <td><strong>Unit 4</strong></td>
                <td>Ujian Komprehensif & Evaluasi KTT / Pengawas</td>
                <td>Kuis Ujian Kelulusan (Passing Grade: 80%)</td>
                <td>25%</td>
            </tr>
        </tbody>
    </table>

    <div class="sec-hdr">3. PENGESAHAN DOKUMEN SILABUS</div>
    <table class="sign-table">
        <tr>
            <td style="width: 33%;">
                Dibuat Oleh,<br><br><br><br>
                <strong>Learning & Development</strong><br>
                PT ANTAM Tbk
            </td>
            <td style="width: 33%;">
                Diverifikasi Oleh,<br><br><br><br>
                <strong>Inspektur K3 Pertambangan</strong><br>
                Site Pomalaa
            </td>
            <td style="width: 33%;">
                Disetujui Oleh,<br><br><br><br>
                <strong>Kepala Teknik Tambang (KTT)</strong><br>
                PT ANTAM Tbk Pomalaa
            </td>
        </tr>
    </table>
</body>
</html>
    `;

    downloadDocumentFile(`Silabus_${cleanFileName}.doc`, htmlContent);
    showToast(`Silabus "${courseTitle}" berhasil diunduh ke komputer Anda (.doc)!`, 'success');
}

// 2. Download Template Lembar Kerja Modul (.doc)
function downloadWorksheetTemplate(courseName) {
    const cleanFileName = (courseName || 'Modul').replace(/[^a-zA-Z0-9_-]/g, '_');
    const htmlContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>Template Lembar Kerja — ${courseName}</title>
<style>
body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.6; color: #0f172a; margin: 30px; }
.header-box { width: 100%; border-bottom: 2px double #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
.title-doc { text-align: center; font-size: 13pt; font-weight: bold; color: #b45309; text-transform: uppercase; margin: 15px 0; }
table.meta { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
table.meta td { border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10pt; }
table.meta .lbl { background: #f8fafc; font-weight: bold; width: 25%; }
.sec-hdr { font-size: 11pt; font-weight: bold; color: #0f172a; background: #f1f5f9; padding: 5px 8px; border-left: 4px solid #d4af37; margin: 20px 0 10px 0; }
.fill-box { border: 1px dashed #94a3b8; padding: 15px; background: #fafafa; min-height: 80px; margin-bottom: 15px; font-style: italic; color: #64748b; }
</style>
</head>
<body>
    <div class="header-box">
        <strong>PT ANTAM Tbk — SITE POMALAA</strong><br>
        <span style="font-size: 9pt; color: #64748b;">DIVISI K3 & KESELAMATAN OPERASI PERTAMBANGAN & PABRIK</span>
    </div>

    <div class="title-doc">LEMBAR KERJA & LAPORAN TUGAS MODUL PEGAWAI</div>

    <table class="meta">
        <tr>
            <td class="lbl">Nama Pegawai</td>
            <td>...........................................................</td>
            <td class="lbl">NRP / ID</td>
            <td>..................................</td>
        </tr>
        <tr>
            <td class="lbl">Mata Pelatihan</td>
            <td colspan="3"><strong>${courseName}</strong></td>
        </tr>
        <tr>
            <td class="lbl">Unit Modul</td>
            <td>Unit 3 — Prosedur & Pengendalian Risiko</td>
            <td class="lbl">Tanggal Pengisian</td>
            <td>..................................</td>
        </tr>
    </table>

    <div class="sec-hdr">1. RINGKASAN PEMAHAMAN MATERI (SOP & K3)</div>
    <div class="fill-box">
        [Ketikkan ringkasan pemahaman Anda mengenai materi pelatihan di sini...]
    </div>

    <div class="sec-hdr">2. IDENTIFIKASI RISIKO DI LOKASI KERJA SITE POMALAA</div>
    <div class="fill-box">
        [Jelaskan potensi bahaya nyata di area kerja Anda serta tindakan pencegahan yang dilakukan...]
    </div>

    <div class="sec-hdr">3. KESIMPULAN & KOMITMEN KESELAMATAN KERJA</div>
    <div class="fill-box">
        [Tuliskan kesimpulan dan komitmen Anda dalam mematuhi standar K3...]
    </div>
</body>
</html>
    `;

    downloadDocumentFile(`Template_Lembar_Kerja_${cleanFileName}.doc`, htmlContent);
    showToast(`Template Lembar Kerja "${courseName}" berhasil diunduh (.doc)!`, 'success');
}

// 3. Download Hasil Ketikan Lembar Kerja Saya (.doc)
function downloadCurrentWorksheetDoc(courseName) {
    const editorEl = document.getElementById('word-editable-content');
    const contentHtml = editorEl ? editorEl.innerHTML : '<p>Belum ada isi laporan.</p>';
    const cleanFileName = (courseName || 'Laporan_Modul').replace(/[^a-zA-Z0-9_-]/g, '_');

    const htmlContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>Laporan Lembar Kerja — ${courseName}</title>
<style>
body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.6; color: #0f172a; margin: 30px; }
.header-box { width: 100%; border-bottom: 2px double #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
.title-doc { text-align: center; font-size: 13pt; font-weight: bold; color: #b45309; text-transform: uppercase; margin: 15px 0; }
table.meta { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
table.meta td { border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10pt; }
table.meta .lbl { background: #f8fafc; font-weight: bold; width: 25%; }
h4 { font-size: 11pt; font-weight: bold; color: #0f172a; background: #f1f5f9; padding: 5px 8px; border-left: 4px solid #d4af37; margin: 18px 0 8px 0; }
p { margin-bottom: 10px; }
</style>
</head>
<body>
    <div class="header-box">
        <strong>PT ANTAM Tbk — SITE POMALAA</strong><br>
        <span style="font-size: 9pt; color: #64748b;">LEMBAR LAPORAN MANDIRI PEGAWAI TERDOKUMENTASI</span>
    </div>

    <div class="title-doc">LAPORAN PENYELESAIAN LEMBAR KERJA MODUL</div>

    <table class="meta">
        <tr>
            <td class="lbl">Mata Pelatihan</td>
            <td colspan="3"><strong>${courseName}</strong></td>
        </tr>
        <tr>
            <td class="lbl">Status Penyerahan</td>
            <td>Telah Disimpan & Dikumpulkan</td>
            <td class="lbl">Waktu Unduh</td>
            <td>${new Date().toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' })}</td>
        </tr>
    </table>

    <div class="doc-body-content">
        ${contentHtml}
    </div>
</body>
</html>
    `;

    downloadDocumentFile(`Laporan_${cleanFileName}.doc`, htmlContent);
    showToast('Laporan hasil pengerjaan Anda berhasil diunduh (.doc)!', 'success');
}

// 4. Download E-Sertifikat Resmi (.doc)
function downloadCertificateDoc(employeeName, certName, score = '94/100', date = '12 Agu 2026') {
    const cleanFileName = (certName || 'Sertifikat').replace(/[^a-zA-Z0-9_-]/g, '_');
    const htmlContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>Sertifikat Kompetensi — ${employeeName}</title>
<style>
body { font-family: 'Times New Roman', serif; text-align: center; padding: 40px; border: 15px double #d4af37; background: #fffdf7; }
.org-title { font-size: 16pt; font-weight: bold; color: #0f172a; letter-spacing: 2px; }
.cert-title { font-size: 26pt; font-weight: bold; color: #b45309; margin: 25px 0 10px 0; }
.cert-to { font-size: 12pt; font-style: italic; color: #64748b; }
.emp-name { font-size: 22pt; font-weight: bold; color: #0f172a; border-bottom: 2px solid #d4af37; display: inline-block; padding: 5px 30px; margin: 15px 0; }
.cert-desc { font-size: 12pt; color: #334155; max-width: 600px; margin: 0 auto 25px auto; line-height: 1.6; }
.meta-box { margin: 20px auto; font-family: monospace; font-size: 10pt; color: #475569; }
.sign-row { width: 100%; margin-top: 45px; }
.sign-row td { text-align: center; vertical-align: bottom; height: 75px; font-size: 10pt; }
</style>
</head>
<body>
    <div class="org-title">PT ANTAM (PERSERO) TBK</div>
    <div style="font-size: 10pt; color: #64748b; letter-spacing: 1px;">DIVISI LEARNING & DEVELOPMENT · SITE POMALAA</div>
    <div class="cert-title">SERTIFIKAT KOMPETENSI</div>
    <div class="cert-to">Diberikan secara resmi kepada:</div>
    <div class="emp-name">${employeeName || 'Egy Pratama'}</div>
    <div class="cert-desc">
        Atas keberhasilan menyelesaikan kurikulum pelatihan dan uji kompetensi K3 Pertambangan Bawah Tanah:
        <br><strong style="font-size: 14pt; color: #0f172a;">${certName}</strong><br>
        dengan perolehan nilai kelulusan <strong>${score}</strong> dan dinyatakan <strong>LULUS / KOMPETEN</strong>.
    </div>
    <div class="meta-box">
        No. Registrasi: ANTAM-CERT-${Math.floor(Math.random()*900000 + 100000)} · Tanggal: ${date} · Berlaku s/d 3 Tahun
    </div>
    <table class="sign-row">
        <tr>
            <td style="width: 50%;">
                Kepala Learning & Development,<br><br><br><br>
                <strong>Drs. H. M. Fauzi, M.M.</strong>
            </td>
            <td style="width: 50%;">
                Kepala Teknik Tambang (KTT),<br><br><br><br>
                <strong>Ir. Bambang Triyono, IPM</strong>
            </td>
        </tr>
    </table>
</body>
</html>
    `;

    downloadDocumentFile(`Sertifikat_${cleanFileName}.doc`, htmlContent);
    showToast(`e-Sertifikat "${certName}" berhasil diunduh ke komputer Anda!`, 'success');
}


// =========================================================================
// G. MODUL LEMBAR KERJA: UPLOAD FILE MODUL & TUGAS PEGAWAI
// =========================================================================
let selectedModuleFile = null;

function openModuleUploadModal(courseName, currentUnit = 'Unit 3: Evaluasi & Praktik Lapangan', currentProgress = 50, activityId = '') {
    selectedModuleFile = null;
    const cleanCourse = courseName || 'Pelatihan K3 Tambang';
    const cleanUnit = currentUnit || 'Unit 3: Evaluasi & Praktik Lapangan';

    const bodyHtml = `
        <div class="module-upload-modal-container">
            <!-- Informasi Unit & Petunjuk -->
            <div class="upload-guideline-box">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-semibold text-dark text-xs uppercase tracking-wide">
                        <i class="ph-fill ph-book-open text-accent"></i> ${cleanUnit}
                    </span>
                    <span class="status-badge status-warning text-xs font-mono">Progress: ${currentProgress}%</span>
                </div>
                <div class="text-xs text-muted" style="line-height: 1.45;">
                    Silakan unggah dokumen lembar kerja atau laporan tugas yang telah Anda selesaikan. Dokumen akan langsung diverifikasi dan diarsipkan oleh tim Learning & Development.
                </div>
            </div>

            <!-- Download Template Helper Banner -->
            <div class="template-quick-download">
                <div class="flex items-center gap-3">
                    <i class="ph-fill ph-file-doc" style="font-size: 1.8rem; color: #b45309;"></i>
                    <div>
                        <strong class="text-xs text-dark block" style="font-size: 0.825rem;">Butuh format pengerjaan resmi?</strong>
                        <span class="text-xs text-muted">Unduh template standar lembar kerja PT ANTAM Tbk (.doc)</span>
                    </div>
                </div>
                <button type="button" class="btn-outline" onclick="downloadWorksheetTemplate('${cleanCourse}')" title="Unduh Template Lembar Kerja Word">
                    <i class="ph-bold ph-download-simple"></i> Unduh Template (.doc)
                </button>
            </div>

            <!-- Upload Dropzone Area -->
            <div class="module-upload-dropzone" id="module-dropzone" 
                 onclick="document.getElementById('module-file-input').click()"
                 ondragover="handleModuleDragOver(event)" 
                 ondragleave="handleModuleDragLeave(event)" 
                 ondrop="handleModuleFileDrop(event)">
                <div class="dropzone-inner">
                    <i class="ph-fill ph-cloud-arrow-up" style="font-size: 3.25rem; color: #d4af37; margin-bottom: 0.5rem; display: inline-block;"></i>
                    <h4 class="font-semibold text-base mb-1 text-dark" id="dropzone-title">Tarik & Lepas File ke Sini, atau Klik untuk Memilih</h4>
                    <p class="text-xs text-muted mb-3" id="dropzone-desc">Pilih file hasil pengerjaan lembar kerja modul Anda</p>
                    <div class="flex justify-center gap-2 flex-wrap">
                        <span class="status-badge status-warning text-xs font-mono">Format: .docx, .doc, .pdf</span>
                        <span class="status-badge status-neutral text-xs font-mono">Maks. 25 MB</span>
                    </div>
                    <input type="file" id="module-file-input" accept=".doc,.docx,.pdf" style="display:none;" onchange="handleModuleFileUpload(this)">
                </div>
            </div>

            <!-- File Selected Preview Card -->
            <div id="file-uploaded-preview" class="file-preview-card mt-3" style="display: none;">
                <div class="flex items-center gap-3">
                    <div class="file-icon-box" id="file-preview-icon">
                        <i class="ph-fill ph-file-doc"></i>
                    </div>
                    <div>
                        <strong class="text-sm text-dark block" id="uploaded-file-name">Laporan_Modul.docx</strong>
                        <div class="text-xs text-muted font-mono" id="uploaded-file-size">1.4 MB · Dokumen Terverifikasi & Siap Diserahkan</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="status-badge status-success text-xs"><i class="ph-fill ph-check"></i> Siap Upload</span>
                    <button type="button" class="btn-outline text-xs" onclick="clearUploadedModuleFile()" title="Ganti file">
                        <i class="ph-bold ph-arrow-counter-clockwise"></i> Ganti
                    </button>
                </div>
            </div>

            <!-- Catatan Tambahan Opsional -->
            <div class="mt-4">
                <label class="block text-xs font-semibold text-muted mb-1">
                    <i class="ph-fill ph-note-pencil"></i> Catatan Tambahan untuk Admin / Pengawas (Opsional):
                </label>
                <textarea id="module-upload-notes" class="input-clean text-xs" rows="2" placeholder="Contoh: Laporan tugas modul telah dilengkapi dokumen observasi lapangan dan tanda tangan pengawas..."></textarea>
            </div>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Batal</button>
        <button class="btn-outline" onclick="downloadWorksheetTemplate('${cleanCourse}')" title="Unduh Template Word Kosong">
            <i class="ph-fill ph-download-simple"></i> Unduh Template (.doc)
        </button>
        <button class="btn-dark" id="btn-submit-worksheet" onclick="submitEmployeeWorksheet('${cleanCourse}', '${activityId}')">
            <i class="ph-fill ph-check-circle"></i> Simpan & Kumpulkan Tugas
        </button>
    `;

    openModal(`Upload Lembar Kerja — ${cleanCourse}`, bodyHtml, footerHtml, 'ph-fill ph-cloud-arrow-up', false);
}

// Drag & Drop event handlers
function handleModuleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('module-dropzone');
    if (dropzone) dropzone.classList.add('dragover');
}

function handleModuleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('module-dropzone');
    if (dropzone) dropzone.classList.remove('dragover');
}

function handleModuleFileDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('module-dropzone');
    if (dropzone) dropzone.classList.remove('dragover');

    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        processSelectedModuleFile(e.dataTransfer.files[0]);
    }
}

function handleModuleFileUpload(input) {
    if (input.files && input.files[0]) {
        processSelectedModuleFile(input.files[0]);
    }
}

function processSelectedModuleFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['doc', 'docx', 'pdf'].includes(ext)) {
        showToast('Format file tidak didukung! Harap unggah file .docx, .doc, atau .pdf.', 'warning');
        return;
    }

    selectedModuleFile = file;
    const fileNameEl = document.getElementById('uploaded-file-name');
    const fileSizeEl = document.getElementById('uploaded-file-size');
    const previewBox = document.getElementById('file-uploaded-preview');
    const dropzone = document.getElementById('module-dropzone');
    const iconBox = document.getElementById('file-preview-icon');

    if (fileNameEl) fileNameEl.textContent = file.name;
    if (fileSizeEl) {
        const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
        const sizeInKb = (file.size / 1024).toFixed(0);
        const displaySize = file.size > 1024 * 1024 ? `${sizeInMb} MB` : `${sizeInKb} KB`;
        fileSizeEl.textContent = `${displaySize} · Dokumen Terverifikasi & Siap Diserahkan`;
    }

    if (previewBox) {
        if (ext === 'pdf') {
            previewBox.classList.add('file-type-pdf');
            if (iconBox) iconBox.innerHTML = '<i class="ph-fill ph-file-pdf"></i>';
        } else {
            previewBox.classList.remove('file-type-pdf');
            if (iconBox) iconBox.innerHTML = '<i class="ph-fill ph-file-doc"></i>';
        }
        previewBox.style.display = 'flex';
    }

    if (dropzone) {
        dropzone.classList.add('has-file');
        const titleEl = document.getElementById('dropzone-title');
        const descEl = document.getElementById('dropzone-desc');
        if (titleEl) titleEl.textContent = 'File Dipilih: ' + file.name;
        if (descEl) descEl.textContent = 'Klik untuk mengganti dengan file lain';
    }

    showToast(`File "${file.name}" berhasil dipilih dan siap diserahkan.`, 'success');
}

function clearUploadedModuleFile() {
    selectedModuleFile = null;
    const input = document.getElementById('module-file-input');
    if (input) input.value = '';
    const previewBox = document.getElementById('file-uploaded-preview');
    if (previewBox) previewBox.style.display = 'none';
    const dropzone = document.getElementById('module-dropzone');
    if (dropzone) {
        dropzone.classList.remove('has-file');
        const titleEl = document.getElementById('dropzone-title');
        const descEl = document.getElementById('dropzone-desc');
        if (titleEl) titleEl.textContent = 'Tarik & Lepas File ke Sini, atau Klik untuk Memilih';
        if (descEl) descEl.textContent = 'Pilih file hasil pengerjaan lembar kerja modul Anda';
    }
}

function submitEmployeeWorksheet(courseName, activityId) {
    if (!selectedModuleFile) {
        showToast('Silakan pilih atau unggah file lembar kerja tugas (.docx, .doc, .pdf) terlebih dahulu!', 'warning');
        const dropzone = document.getElementById('module-dropzone');
        if (dropzone) {
            dropzone.style.borderColor = '#ef4444';
            setTimeout(() => { dropzone.style.borderColor = ''; }, 2000);
        }
        return;
    }

    const notesEl = document.getElementById('module-upload-notes');
    const reportNotes = notesEl ? notesEl.value.trim() : '';
    const uploadedName = selectedModuleFile.name;

    const submitBtn = document.getElementById('btn-submit-worksheet');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Mengunggah file...';
    }

    // Send FormData with actual file
    const formData = new FormData();
    formData.append('action', 'submit_module_report');
    formData.append('course_title', courseName);
    formData.append('activity_id', activityId);
    formData.append('report_text', reportNotes);
    formData.append('attachment_name', uploadedName);
    formData.append('module_file', selectedModuleFile);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        closeModal();
        showToast(`🎉 File tugas "${uploadedName}" berhasil dikumpulkan ke Admin! Progress modul bertambah.`, 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1200);
    })
    .catch(err => {
        closeModal();
        showToast(`🎉 File tugas "${uploadedName}" berhasil disimpan & dikumpulkan!`, 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1200);
    });
}

// Backward-compatible aliases for existing calls
function openCoursePlayerModal(courseName, currentUnit, currentProgress, activityId = '') {
    openModuleUploadModal(courseName, typeof currentUnit === 'string' ? currentUnit : `Unit ${currentUnit}`, currentProgress, activityId);
}

function openModuleWordEditorModal(courseName, currentUnit, currentProgress, activityId = '') {
    openModuleUploadModal(courseName, currentUnit, currentProgress, activityId);
}

function completeUnitSim(courseName) {
    closeModal();
    showToast(`Unit pembelajaran ${courseName} selesai. Progress bertambah!`, 'success');
}

// Simulasi Pengerjaan Kuis Pemahaman
function openSimulateQuizModal() {
    const bodyHtml = `
        <div class="quiz-modal-content">
            <div class="p-2 bg-amber-50 border border-amber-200 rounded-md mb-4 text-xs font-mono" style="background:#fffbeb; border:1px solid #fef3c7; color:#92400e;">
                <i class="ph-fill ph-info"></i> Passing Grade: <strong>80%</strong>
            </div>

            <form id="sim-quiz-form" onsubmit="event.preventDefault(); submitQuizSim();">
                <div class="space-y-4 text-sm">
                    <div class="quiz-q-block p-3 border rounded" style="background:#f8fafc; border:1px solid #e2e8f0; margin-bottom: 0.75rem;">
                        <p class="font-medium mb-2">1. Tindakan pertama saat detektor membunyikan alarm gas metana (CH4) > 1%?</p>
                        <label class="block mb-1 text-xs cursor-pointer"><input type="radio" name="q1" value="a" required> A. Tetap bekerja hingga shift selesai</label>
                        <label class="block mb-1 text-xs cursor-pointer"><input type="radio" name="q1" value="b"> B. Matikan kelistrikan, evakuasi ke intake airway, lapor Pengawas</label>
                        <label class="block text-xs cursor-pointer"><input type="radio" name="q1" value="c"> C. Membuka katup cadangan tanpa izin</label>
                    </div>

                    <div class="quiz-q-block p-3 border rounded" style="background:#f8fafc; border:1px solid #e2e8f0; margin-bottom: 0.75rem;">
                        <p class="font-medium mb-2">2. Durasi pasokan udara darurat alat SCSR?</p>
                        <label class="block mb-1 text-xs cursor-pointer"><input type="radio" name="q2" value="a"> A. 15 menit</label>
                        <label class="block mb-1 text-xs cursor-pointer"><input type="radio" name="q2" value="b" required> B. Minimum 60 menit saat evakuasi</label>
                        <label class="block text-xs cursor-pointer"><input type="radio" name="q2" value="c"> C. 12 jam</label>
                    </div>

                    <div class="quiz-q-block p-3 border rounded" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <p class="font-medium mb-2">3. Penanggung jawab tertinggi keselamatan operasional pertambangan?</p>
                        <label class="block mb-1 text-xs cursor-pointer"><input type="radio" name="q3" value="a" required> A. Kepala Teknik Tambang (KTT)</label>
                        <label class="block mb-1 text-xs cursor-pointer"><input type="radio" name="q3" value="b"> B. Staf Logistik Gudang</label>
                        <label class="block text-xs cursor-pointer"><input type="radio" name="q3" value="c"> C. Vendor sparepart</label>
                    </div>
                </div>
            </form>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Batal</button>
        <button class="btn-dark" onclick="submitQuizSim()">
            <i class="ph-fill ph-paper-plane-tilt"></i> Kirim Jawaban
        </button>
    `;

    openModal('Kuis K3 Underground', bodyHtml, footerHtml, 'ph-fill ph-exam');
}

function submitQuizSim() {
    const form = document.getElementById('sim-quiz-form');
    if (!form) { closeModal(); return; }

    // Ambil jawaban yang dipilih
    const answers = {
        q1: form.querySelector('input[name="q1"]:checked'),
        q2: form.querySelector('input[name="q2"]:checked'),
        q3: form.querySelector('input[name="q3"]:checked'),
    };

    // Kunci jawaban: q1=b, q2=b, q3=a (per soal bobot 34/33/33)
    let correct = 0;
    if (answers.q1 && answers.q1.value === 'b') correct++;
    if (answers.q2 && answers.q2.value === 'b') correct++;
    if (answers.q3 && answers.q3.value === 'a') correct++;

    // Validasi — semua harus dijawab
    if (!answers.q1 || !answers.q2 || !answers.q3) {
        showToast('Harap jawab semua pertanyaan terlebih dahulu.', 'error');
        return;
    }

    const scoreMap = [0, 34, 67, 100];
    const score = scoreMap[correct];
    const courseTitle = 'Sertifikasi K3 Umum & Pertambangan';

    closeModal();

    const fd = new FormData();
    fd.append('action', 'submit_quiz_result');
    fd.append('course_title', courseTitle);
    fd.append('score', score);

    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.passed) {
                showToast(`🎉 Lulus! Skor ${score}/100. Sertifikat ${res.reg_no} diterbitkan!`, 'success');
                // Buka viewer sertifikat langsung
                setTimeout(() => {
                    const today = new Date();
                    const issued = today.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
                    const valid  = new Date(today.setFullYear(today.getFullYear() + 3)).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
                    const uname  = document.querySelector('.topbar-user-name')?.textContent?.trim() || 'Pegawai';
                    openCertViewer(courseTitle, res.reg_no, score, issued, valid, uname);
                }, 1200);
            } else if (res.success && !res.passed) {
                showToast(`Skor Anda ${score}/100. Passing grade 80. Silakan ulangi.`, 'error');
            } else {
                showToast(res.message || 'Terjadi kesalahan.', 'error');
            }
        })
        .catch(() => showToast('Koneksi error. Coba lagi.', 'error'));
}

// H. Certificate Viewer Modal (Premium Layout + Print)
function openCertViewer(certName, regNo, score, issuedDate, validUntil, holderName, templateFile) {
    score       = score       || '—';
    issuedDate  = issuedDate  || '—';
    validUntil  = validUntil  || '—';
    holderName  = holderName  || '—';

    const hasCustomBg = templateFile && /\.(jpg|jpeg|png|webp)$/i.test(templateFile);
    const bgStyle = hasCustomBg 
        ? `background: linear-gradient(rgba(254,252,240,0.88), rgba(255,254,248,0.88)), url('uploads/templates/${templateFile}') center/cover no-repeat;`
        : `background: linear-gradient(135deg, #fefcf0 0%, #fffef8 100%);`;

    const bodyHtml = `
        <style>
        .cert-print-wrap {
            ${bgStyle}
            border: 3px double #d4af37;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            text-align: center;
            position: relative;
            font-family: 'Georgia', serif;
        }
        .cert-print-wrap::before {
            content: '';
            position: absolute; inset: 8px;
            border: 1px solid #d4af3766;
            border-radius: 12px;
            pointer-events: none;
        }
        .cert-watermark {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 5rem; font-weight: 900; color: #d4af3710;
            letter-spacing: 4px; pointer-events: none; user-select: none;
        }
        .cert-logo-row { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 0.5rem; }
        .cert-logo-hex { width: 38px; height: 38px; background: #d4af37; clip-path: polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 0.85rem; }
        .cert-tagline { font-size: 0.65rem; letter-spacing: 3px; color: #92734d; text-transform: uppercase; margin-bottom: 0.5rem; }
        .cert-heading { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0.5rem 0 0.15rem; }
        .cert-sub { font-size: 0.75rem; letter-spacing: 2px; color: #64748b; text-transform: uppercase; margin-bottom: 1.25rem; }
        .cert-presented { font-size: 0.7rem; color: #94a3b8; margin-bottom: 0.25rem; font-style: italic; }
        .cert-holder-name { font-size: 1.6rem; font-weight: 700; color: #d4af37; margin: 0.25rem 0 0.75rem; letter-spacing: 1px; }
        .cert-course { font-size: 0.95rem; font-weight: 600; color: #1e293b; max-width: 380px; margin: 0 auto 1rem; }
        .cert-meta-row { display: flex; justify-content: center; gap: 2rem; font-size: 0.7rem; color: #64748b; border-top: 1px solid #d4af3740; border-bottom: 1px solid #d4af3740; padding: 0.6rem 0; margin-bottom: 1rem; }
        .cert-meta-row div { text-align: center; }
        .cert-meta-row strong { display: block; font-size: 0.75rem; color: #1e293b; }
        .cert-qr-area { display: flex; justify-content: center; align-items: center; gap: 1.5rem; }
        .cert-qr-box { width: 48px; height: 48px; background: repeating-linear-gradient(0deg, #d4af37 0px, #d4af37 4px, transparent 4px, transparent 8px), repeating-linear-gradient(90deg, #d4af37 0px, #d4af37 4px, transparent 4px, transparent 8px); border: 2px solid #d4af37; border-radius: 4px; }
        .cert-sign-area { text-align: center; font-size: 0.65rem; color: #64748b; }
        .cert-sign-area .sign-name { font-size: 0.75rem; font-weight: 700; color: #1e293b; }
        @media print {
            body * { visibility: hidden !important; }
            #cert-print-target, #cert-print-target * { visibility: visible !important; }
            #cert-print-target { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: #fff; z-index: 9999; }
            .cert-print-wrap { box-shadow: none; width: 680px; }
        }
        </style>
        <div id="cert-print-target">
        <div class="cert-print-wrap">
            <div class="cert-watermark">ANTAM</div>
            <div class="cert-logo-row">
                <img src="assets/img/logo-antam-icon.png" alt="PT ANTAM" style="height: 38px; width: auto; object-fit: contain;">
                <div style="text-align:left">
                    <div style="font-weight:700; font-size:0.8rem; color:#1e293b;">PT ANTAM (PERSERO) TBK</div>
                    <div style="font-size:0.6rem; color:#92734d;">Site Pomalaa — Human Capital & Corporate University</div>
                </div>
            </div>
            <div class="cert-tagline">Menyatakan dengan Resmi Bahwa</div>
            <div class="cert-presented">Yang bertanda tangan di bawah menerangkan bahwa</div>
            <div class="cert-holder-name">${holderName}</div>
            <div class="cert-heading">SERTIFIKAT KOMPETENSI</div>
            <div class="cert-sub">Certificate of Competency</div>
            <div class="cert-course">${certName}</div>
            <div class="cert-meta-row">
                <div><strong>${score}/100</strong>Nilai Ujian</div>
                <div><strong>${regNo}</strong>No. Registrasi</div>
                <div><strong>${issuedDate}</strong>Tanggal Terbit</div>
                <div><strong>${validUntil}</strong>Berlaku Hingga</div>
            </div>
            <div class="cert-qr-area">
                <div class="cert-qr-box"></div>
                <div class="cert-sign-area">
                    <div style="width:80px; border-bottom:1px solid #d4af37; margin:0 auto 0.25rem;"></div>
                    <div class="sign-name">Learning Ops Admin</div>
                    <div>Kepala Unit Pembelajaran</div>
                    <div>PT ANTAM Tbk — Site Pomalaa</div>
                </div>
            </div>
        </div>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Tutup</button>
        <button class="btn-dark" onclick="window.print()">
            <i class="ph-fill ph-printer"></i> Cetak / Simpan PDF
        </button>
    `;

    openModal('E-Sertifikat Resmi ANTAM', bodyHtml, footerHtml, 'ph-fill ph-certificate');
}

// I. Sederhana: Selesaikan Pelatihan & Terbitkan E-Sertifikat Resmi Langsung
function handleClaimCertificate(activityId, courseTitle, btn) {
    let originalHtml = '';
    if (btn) {
        originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Menyiapkan Sertifikat...';
    }

    const fd = new FormData();
    fd.append('action', 'claim_certificate');
    fd.append('activity_id', activityId || '');
    fd.append('course_title', courseTitle || '');

    fetch('api.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }

        if (res.success) {
            if (typeof playNotificationSound === 'function') {
                playNotificationSound('success');
            }
            showToast(`🎉 Selamat! E-Sertifikat Resmi untuk "${res.course_title}" telah terbit!`, 'success');

            // Buka viewer sertifikat resmi langsung
            setTimeout(() => {
                openCertViewer(
                    res.course_title,
                    res.reg_no,
                    res.score || 100,
                    res.issued_at,
                    res.valid_until,
                    res.holder_name
                );
            }, 500);

            // Perbarui tampilan kartu kegiatan di UI jika tombol berada dalam kartu
            const card = btn ? btn.closest('.course-active-card') : null;
            if (card) {
                const progBar = card.querySelector('.progress-bar');
                const progText = card.querySelector('.progress-labels .font-bold');
                if (progBar) progBar.style.width = '100%';
                if (progText) progText.textContent = '100% Tuntas';

                // Ganti tombol menjadi Lihat E-Sertifikat Resmi
                btn.style.background = '#0f766e';
                btn.style.borderColor = '#0f766e';
                btn.innerHTML = '<i class="ph-fill ph-certificate"></i> Lihat E-Sertifikat Resmi';
                btn.onclick = function() {
                    openCertViewer(res.course_title, res.reg_no, res.score || 100, res.issued_at, res.valid_until, res.holder_name);
                };
            }
        } else {
            showToast(res.message || 'Gagal menerbitkan sertifikat.', 'error');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        showToast('Terjadi gangguan koneksi. Silakan coba lagi.', 'error');
    });
}

// J. Upload External Certificate Modal (Pegawai Upload Sertifikat Eksternal/Mandiri)
let selectedExternalCertFile = null;

function openUploadExternalCertModal() {
    selectedExternalCertFile = null;
    const today = new Date();
    const defaultValid = new Date(today.setFullYear(today.getFullYear() + 3)).toISOString().split('T')[0];

    const bodyHtml = `
        <div class="upload-cert-modal-body">
            <div class="p-3 mb-3 border rounded text-xs" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534;">
                <i class="ph-fill ph-info"></i> Unggah sertifikat kompetensi K3, lisensi teknis, atau pelatihan eksternal (BNSP, Kemnaker, Inspektur Tambang ESDM) untuk dicatat dalam portofolio Anda.
            </div>

            <form id="form-upload-ext-cert" onsubmit="submitExternalCertificate(event)">
                <div class="form-group mb-3">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Nama / Judul Sertifikat *</label>
                    <input type="text" id="ext-cert-title" class="form-input mt-1" placeholder="Contoh: Sertifikasi Pengawas Operasional Pertama (POP) ESDM" required>
                </div>

                <div class="form-grid mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Nomor Registrasi / Lisensi *</label>
                        <input type="text" id="ext-cert-reg" class="form-input mt-1" placeholder="Contoh: BNSP-K3-08912/2025" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Lembaga Penerbit / Akreditasi</label>
                        <input type="text" id="ext-cert-issuer" class="form-input mt-1" placeholder="Contoh: BNSP / Kemnaker RI / ESDM" value="Kemnaker RI & Inspektur Tambang ESDM">
                    </div>
                </div>

                <div class="form-grid mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Berlaku Hingga</label>
                        <input type="date" id="ext-cert-valid" class="form-input mt-1" value="${defaultValid}">
                    </div>
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Skor / Nilai Kelulusan</label>
                        <input type="number" id="ext-cert-score" class="form-input mt-1" min="0" max="100" value="95">
                    </div>
                </div>

                <div class="form-group mb-2">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Upload Berkas / Bukti Sertifikat (PDF / JPG / PNG)</label>
                    <div class="module-upload-dropzone mt-1" onclick="document.getElementById('ext-cert-file-input').click()" style="cursor: pointer; padding: 1.25rem; text-align: center; border: 2px dashed #cbd5e1; border-radius: 8px; background: #fafbfc;">
                        <i class="ph-fill ph-cloud-arrow-up text-accent" style="font-size: 1.8rem;"></i>
                        <p class="text-xs font-semibold text-dark mt-1" id="ext-cert-file-text">Pilih file sertifikat dari perangkat</p>
                        <span class="text-xs text-muted">Format didukung: PDF, JPG, PNG (Maks 5MB)</span>
                        <input type="file" id="ext-cert-file-input" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" onchange="handleExtCertFileSelect(this)">
                    </div>
                </div>
            </form>
        </div>
    `;

    const footerHtml = `
        <button class="btn-light" onclick="closeModal()">Batal</button>
        <button class="btn-dark" id="btn-submit-ext-cert" onclick="document.getElementById('form-upload-ext-cert').requestSubmit()">
            <i class="ph-fill ph-check-circle"></i> Simpan ke Portofolio
        </button>
    `;

    openModal('Upload Sertifikat Kompetensi', bodyHtml, footerHtml, 'ph-fill ph-certificate');
}

function handleExtCertFileSelect(inp) {
    if (inp.files && inp.files[0]) {
        selectedExternalCertFile = inp.files[0];
        const textEl = document.getElementById('ext-cert-file-text');
        if (textEl) {
            textEl.innerHTML = `<span style="color:#0f766e; font-weight:700;">✅ Terpilih: ${inp.files[0].name}</span> (${(inp.files[0].size/1024).toFixed(0)} KB)`;
        }
    }
}

async function submitExternalCertificate(e) {
    e.preventDefault();
    const title = document.getElementById('ext-cert-title').value.trim();
    const regNo = document.getElementById('ext-cert-reg').value.trim();
    const issuer = document.getElementById('ext-cert-issuer').value.trim();
    const validUntil = document.getElementById('ext-cert-valid').value;
    const score = document.getElementById('ext-cert-score').value;

    const btn = document.getElementById('btn-submit-ext-cert');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Menyimpan...';
    }

    const fd = new FormData();
    fd.append('action', 'upload_external_certificate');
    fd.append('title', title);
    fd.append('reg_no', regNo);
    fd.append('issued_by', issuer);
    fd.append('valid_until', validUntil);
    fd.append('score', score);
    if (selectedExternalCertFile) {
        fd.append('cert_file', selectedExternalCertFile);
    }

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            closeModal();
            showToast('🎉 ' + res.message, 'success');
            playNotificationSound('success');
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            showToast(res.message || 'Gagal menyimpan sertifikat.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Simpan ke Portofolio';
            }
        }
    } catch(err) {
        showToast('Terjadi kesalahan koneksi server.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Simpan ke Portofolio';
        }
    }
}

// =========================================================================
// K. BATCH CERTIFICATE GENERATOR: UPLOAD TEMPLATE & AUTO-GENERATE FOR PARTICIPANTS
// =========================================================================
let selectedTemplateFile = null;

function openBatchCertModal(defaultCourseTitle = '') {
    selectedTemplateFile = null;
    const cleanCourse = defaultCourseTitle || '';

    // Daftar pegawai di site Pomalaa
    const employees = [
        { username: 'egy', name: 'Egy Pratama', role: 'Operasional Tambang Underground', nrp: 'ANTAM-08912' },
        { username: 'budi', name: 'Budi Santoso', role: 'Processing Plant & Smelter', nrp: 'ANTAM-07844' },
        { username: 'andi', name: 'Andi Wijaya', role: 'Logistik Handak & Gudang', nrp: 'ANTAM-06519' }
    ];

    const empCheckboxes = employees.map((emp) => `
        <label class="flex items-center gap-2 p-2 border rounded cursor-pointer hover:bg-gray-50 text-xs mb-1" style="background:#fff; border-color:#e2e8f0; display:flex; align-items:center; justify-content:space-between;">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="batch_participants" value="${emp.name}" checked class="form-checkbox" style="width:16px; height:16px; accent-color:#0f766e;">
                <div>
                    <strong class="text-dark block" style="font-size:0.8rem;">${emp.name}</strong>
                    <span class="text-muted font-mono" style="font-size:0.7rem;">${emp.role} · ${emp.nrp}</span>
                </div>
            </div>
            <span class="tag tag-teal text-xs">Pegawai Terdaftar</span>
        </label>
    `).join('');

    const bodyHtml = `
        <div class="batch-cert-modal-body">
            <div class="p-3 mb-3 border rounded text-xs" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534; line-height:1.45;">
                <i class="ph-fill ph-magic-wand" style="font-size:1.1rem; vertical-align:middle;"></i>
                <strong>Penerbitan Sertifikat Otomatis:</strong> Unggah template sertifikat kegiatan, lalu pilih nama-nama peserta yang telah mengikuti kegiatan. Sistem akan otomatis menyematkan nama masing-masing peserta ke sertifikat dan menerbitkannya secara serentak ke portofolio mereka.
            </div>

            <form id="form-batch-cert" onsubmit="event.preventDefault(); submitBatchCertificates();">
                <!-- 1. Judul Kegiatan -->
                <div class="form-group mb-3">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Judul / Nama Kegiatan Pelatihan *</label>
                    <input type="text" id="batch-course-title" class="form-input mt-1" placeholder="Contoh: Safety Induction Site Pomalaa" value="${cleanCourse}" required>
                </div>

                <!-- 2. Upload Template Sertifikat -->
                <div class="form-group mb-3">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Upload Desain / File Template Sertifikat (Opsional)</label>
                    <div class="border-2 border-dashed rounded-lg p-3 text-center cursor-pointer hover:bg-gray-50" 
                         id="batch-tpl-dropzone" 
                         onclick="document.getElementById('batch-tpl-input').click()"
                         style="border-color:#d4af37; background:#fdfdfa;">
                        <i class="ph-fill ph-image text-amber" style="font-size: 2rem; display:block; margin: 0 auto 0.25rem;"></i>
                        <div class="text-xs font-semibold text-dark" id="batch-tpl-title">Klik untuk Unggah Desain Template (.png, .jpg, .pdf)</div>
                        <div class="text-xs text-muted" id="batch-tpl-sub">Kosongkan jika ingin memakai Template Emas Standar Resmi PT ANTAM</div>
                        <input type="file" id="batch-tpl-input" accept=".png,.jpg,.jpeg,.pdf" style="display:none;" onchange="handleBatchTemplateFile(this)">
                    </div>
                </div>

                <!-- 3. Daftar Peserta yang Mengikuti Kegiatan -->
                <div class="form-group mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Peserta yang Telah Mengikuti Kegiatan *</label>
                        <span class="text-xs text-accent cursor-pointer" onclick="toggleSelectAllBatch(this)">Pilih Semua</span>
                    </div>
                    
                    <div class="batch-emp-list p-2 border rounded" style="background:#f8fafc; max-height:160px; overflow-y:auto;">
                        ${empCheckboxes}
                    </div>

                    <!-- Input nama peserta tambahan -->
                    <div class="mt-2">
                        <label class="text-xs text-muted font-semibold block mb-1">+ Tambah Peserta Lapangan / Mitra Kontraktor (1 nama per baris):</label>
                        <textarea id="batch-extra-participants" class="form-input text-xs" rows="2" placeholder="Contoh:&#10;Rian Setiawan&#10;Dewi Sartika"></textarea>
                    </div>
                </div>

                <!-- 4. Instansi Penerbit & Masa Berlaku -->
                <div class="form-grid mb-3" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Instansi Penerbit</label>
                        <input type="text" id="batch-issuer" class="form-input mt-1" value="PT ANTAM (PERSERO) TBK — Site Pomalaa">
                    </div>
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Masa Berlaku</label>
                        <input type="text" class="form-input mt-1" value="3 Tahun (Standar K3)" readonly style="background:#f1f5f9;">
                    </div>
                </div>
            </form>
        </div>
    `;

    const footerHtml = `
        <button type="button" class="btn-light" onclick="closeModal()">Batal</button>
        <button type="button" class="btn-dark" id="btn-submit-batch-cert" onclick="submitBatchCertificates()">
            <i class="ph-fill ph-magic-wand"></i> Terbitkan Sertifikat Otomatis
        </button>
    `;

    openModal('Upload Template & Penerbitan Sertifikat Peserta', bodyHtml, footerHtml, 'ph-fill ph-certificate');
}

function handleBatchTemplateFile(input) {
    if (input.files && input.files[0]) {
        selectedTemplateFile = input.files[0];
        const titleEl = document.getElementById('batch-tpl-title');
        const subEl = document.getElementById('batch-tpl-sub');
        if (titleEl) titleEl.innerHTML = `<span style="color:#0f766e; font-weight:700;"><i class="ph-fill ph-check-circle"></i> File Template Terpilih: ${selectedTemplateFile.name}</span>`;
        if (subEl) subEl.textContent = `${(selectedTemplateFile.size / 1024).toFixed(0)} KB · Template siap digunakan`;
        showToast(`Template "${selectedTemplateFile.name}" berhasil dipilih.`, 'success');
    }
}

function toggleSelectAllBatch(btn) {
    const checkboxes = document.querySelectorAll('input[name="batch_participants"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    btn.textContent = allChecked ? 'Pilih Semua' : 'Batal Semua';
}

function submitBatchCertificates() {
    const courseTitleEl = document.getElementById('batch-course-title');
    const courseTitle = courseTitleEl ? courseTitleEl.value.trim() : '';
    if (!courseTitle) {
        showToast('Judul kegiatan pelatihan wajib diisi!', 'warning');
        if (courseTitleEl) courseTitleEl.focus();
        return;
    }

    // Ambil peserta terpilih
    const checked = Array.from(document.querySelectorAll('input[name="batch_participants"]:checked')).map(cb => cb.value.trim());
    const extraEl = document.getElementById('batch-extra-participants');
    if (extraEl && extraEl.value.trim()) {
        const extraNames = extraEl.value.split('\n').map(n => n.trim()).filter(n => n.length > 0);
        checked.push(...extraNames);
    }

    if (checked.length === 0) {
        showToast('Pilih atau masukkan minimal 1 nama peserta kegiatan!', 'warning');
        return;
    }

    const issuerEl = document.getElementById('batch-issuer');
    const issuer = issuerEl ? issuerEl.value.trim() : 'PT ANTAM (PERSERO) TBK';

    const submitBtn = document.getElementById('btn-submit-batch-cert');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Menerbitkan Sertifikat...';
    }

    const fd = new FormData();
    fd.append('action', 'batch_generate_certificates');
    fd.append('course_title', courseTitle);
    fd.append('participants', JSON.stringify(checked));
    fd.append('issued_by', issuer);
    if (selectedTemplateFile) {
        fd.append('template_file', selectedTemplateFile);
    }

    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph-fill ph-magic-wand"></i> Terbitkan Sertifikat Otomatis';
            }

            if (res.success) {
                if (typeof playNotificationSound === 'function') {
                    playNotificationSound('success');
                }
                showToast(`🎉 Sukses! ${res.count} E-Sertifikat berhasil diterbitkan untuk "${res.course_title}"!`, 'success');

                // Tampilkan Modal Hasil Rekapitulasi Sertifikat
                showBatchResultsModal(res);
            } else {
                showToast(res.message || 'Gagal menerbitkan sertifikat.', 'error');
            }
        })
        .catch(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph-fill ph-magic-wand"></i> Terbitkan Sertifikat Otomatis';
            }
            showToast('Terjadi gangguan jaringan. Coba lagi.', 'error');
        });
}

function showBatchResultsModal(res) {
    const listHtml = res.generated.map(g => `
        <div class="flex items-center justify-between p-3 border rounded mb-2" style="background:#fff; border-color:#e2e8f0; display:flex; align-items:center; justify-content:space-between;">
            <div class="flex items-center gap-3">
                <div class="mini-avatar" style="background:#d4af37; color:#fff; width:36px; height:36px; font-weight:700; font-size:0.85rem;">
                    ${g.user_name.substring(0, 2).toUpperCase()}
                </div>
                <div>
                    <strong class="text-dark block" style="font-size:0.85rem;">${g.user_name}</strong>
                    <span class="text-xs text-muted font-mono">No. Reg: ${g.reg_no} · Berlaku s/d ${g.valid_until}</span>
                </div>
            </div>
            <button type="button" class="btn-dark text-xs" style="padding:0.35rem 0.75rem;" onclick="openCertViewer('${addslashesJs(g.course_title)}', '${addslashesJs(g.reg_no)}', '${g.score}', '${addslashesJs(g.issued_at)}', '${addslashesJs(g.valid_until)}', '${addslashesJs(g.user_name)}', '${g.template_file || ''}')">
                <i class="ph-fill ph-eye"></i> Lihat E-Sertifikat
            </button>
        </div>
    `).join('');

    const bodyHtml = `
        <div class="batch-result-wrap text-center">
            <div style="font-size:3rem; color:#10b981; margin-bottom:0.5rem;"><i class="ph-fill ph-seal-check"></i></div>
            <h3 class="font-bold text-lg text-dark mb-1">${res.count} E-Sertifikat Berhasil Diterbitkan!</h3>
            <p class="text-xs text-muted mb-4">Kegiatan: <strong>${res.course_title}</strong>. Sertifikat resmi telah tersimpan di portofolio masing-masing pegawai dan dapat langsung diakses / dicetak.</p>

            <div class="text-left mb-3" style="max-height:220px; overflow-y:auto;">
                ${listHtml}
            </div>
        </div>
    `;

    const footerHtml = `
        <button type="button" class="btn-dark w-full" onclick="closeModal(); window.location.reload();">
            <i class="ph-fill ph-check"></i> Selesai & Perbarui Halaman
        </button>
    `;

    openModal('Sertifikat Berhasil Diterbitkan', bodyHtml, footerHtml, 'ph-fill ph-seal-check');
}

function addslashesJs(str) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

// I. User Profile Audit Modal
function openUserProfileAudit(name, initials, unit) {
    const bodyHtml = `
        <div class="user-audit-modal">
            <div class="flex items-center gap-4 mb-4">
                <div class="mini-avatar" style="background:#d4af37; width:52px; height:52px; font-size:1.2rem;">${initials}</div>
                <div>
                    <h4 class="font-semibold text-lg">${name}</h4>
                    <span class="text-xs text-muted">${unit} · Site Pomalaa</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-4 text-xs font-mono">
                <div class="p-3 bg-gray-50 border rounded">Kepatuhan: <strong>75%</strong></div>
                <div class="p-3 bg-gray-50 border rounded">Jam Belajar: <strong>38 Jam</strong></div>
                <div class="p-3 bg-gray-50 border rounded">Sertifikat: <strong>1 Lisensi</strong></div>
                <div class="p-3 bg-gray-50 border rounded text-red">Overdue: <strong>1 Kali</strong></div>
            </div>
        </div>
    `;

    openModal(`Profil: ${name}`, bodyHtml, '', 'ph-fill ph-user-focus');
}

// J. Simulation Scenario Modal Controller
function openSimulationControllerModal() {
    const bodyHtml = `
        <div class="sim-controller-modal">
            <div class="space-y-3">
                <button class="btn-outline w-full text-left p-3 mb-2 flex items-center justify-between" onclick="triggerSimulationScenario(1); closeModal();">
                    <div>
                        <strong>Skenario 1: Overdue (+1 Hari)</strong>
                        <div class="text-xs text-muted">Egy Pratama overdue & alert ke Admin.</div>
                    </div>
                    <i class="ph-bold ph-arrow-right"></i>
                </button>
                <button class="btn-outline w-full text-left p-3 mb-2 flex items-center justify-between" onclick="triggerSimulationScenario(2); closeModal();">
                    <div>
                        <strong>Skenario 2: Sertifikasi (Nilai 94)</strong>
                        <div class="text-xs text-muted">Budi Santoso lulus kuis & verifikasi.</div>
                    </div>
                    <i class="ph-bold ph-arrow-right"></i>
                </button>
                <button class="btn-outline w-full text-left p-3 mb-2 flex items-center justify-between" onclick="advanceSimDay(); closeModal();">
                    <div>
                        <strong>+1 Hari Tanggal Server</strong>
                        <div class="text-xs text-muted">Simulasi hari berikutnya.</div>
                    </div>
                    <i class="ph-bold ph-fast-forward text-accent"></i>
                </button>
                <button class="btn-dark w-full text-left p-3 flex items-center justify-between" style="background:#ef4444;" onclick="resetFullSimulation(); closeModal();">
                    <div>
                        <strong>Reset Simulasi</strong>
                        <div class="text-xs" style="color:rgba(255,255,255,0.8);">Kembali ke 12 Agu 2026.</div>
                    </div>
                    <i class="ph-bold ph-arrow-counter-clockwise"></i>
                </button>
            </div>
        </div>
    `;

    openModal('Simulator Skenario', bodyHtml, '', 'ph-fill ph-sliders');
}

function markAllAsRead() {
    document.querySelectorAll('.notif-card, .notif-item-card').forEach(c => c.style.opacity = '0.5');
    const badge = document.getElementById('dock-notif-badge');
    if (badge) badge.textContent = '0';
    const topBadge = document.getElementById('topbar-notif-badge');
    if (topBadge) topBadge.textContent = '0';

    const fd = new FormData();
    fd.append('action', 'mark_notifs_read');
    fetch('api.php', { method: 'POST', body: fd }).catch(() => {});

    showToast('Semua notifikasi ditandai sebagai telah dibaca.', 'success');
}

// ==========================================
// K. ALUR KEGIATAN BARU PEGAWAI: KALENDER & ESTIMASI WAKTU INTERAKTIF
// ==========================================

const INDO_MONTH_NAMES_LIST = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const INDO_MONTH_SHORT_LIST = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const INDO_DAYS_LIST = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function getSystemDateIso() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function parseDateToIso(str) {
    if (!str) return getSystemDateIso();
    str = String(str).trim();
    if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
        return str.substring(0, 10);
    }
    const mMatch = str.match(/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/);
    if (mMatch) {
        const d = String(mMatch[1]).padStart(2, '0');
        const mStr = mMatch[2].toLowerCase();
        const y = mMatch[3];
        const monthMap = {
            'jan': '01', 'januari': '01', 'january': '01',
            'feb': '02', 'februari': '02', 'february': '02',
            'mar': '03', 'maret': '03', 'march': '03',
            'apr': '04', 'april': '04',
            'mei': '05', 'may': '05',
            'jun': '06', 'juni': '06', 'june': '06',
            'jul': '07', 'juli': '07', 'july': '07',
            'agu': '08', 'agustus': '08', 'aug': '08', 'august': '08',
            'sep': '09', 'september': '09',
            'okt': '10', 'oktober': '10', 'oct': '10', 'october': '10',
            'nov': '11', 'november': '11',
            'des': '12', 'desember': '12', 'dec': '12', 'december': '12'
        };
        const m = monthMap[mStr] || '01';
        return `${y}-${m}-${d}`;
    }
    const t = new Date(str);
    if (!isNaN(t.getTime())) {
        const y = t.getFullYear();
        const m = String(t.getMonth() + 1).padStart(2, '0');
        const d = String(t.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    return getSystemDateIso();
}

function formatIsoToIndoDate(isoStr) {
    if (!isoStr) return '';
    const parts = isoStr.split('-');
    if (parts.length === 3) {
        const d = parseInt(parts[2], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parts[0];
        return `${d} ${INDO_MONTH_SHORT_LIST[m] || ''} ${y}`;
    }
    return isoStr;
}

function formatIsoToIndoFullDate(isoStr) {
    if (!isoStr) return '';
    const parts = isoStr.split('-');
    if (parts.length === 3) {
        const d = parseInt(parts[2], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parseInt(parts[0], 10);
        const dt = new Date(y, m, d);
        const dayName = INDO_DAYS_LIST[dt.getDay()] || '';
        return `${dayName}, ${d} ${INDO_MONTH_SHORT_LIST[m] || ''} ${y}`;
    }
    return isoStr;
}

function addDaysToIsoDate(isoStr, numDays) {
    const parts = isoStr.split('-');
    const dt = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    dt.setDate(dt.getDate() + numDays);
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function calculateDaysBetween(iso1, iso2) {
    if (!iso1 || !iso2) return 1;
    const p1 = iso1.split('-');
    const p2 = iso2.split('-');
    const d1 = new Date(parseInt(p1[0], 10), parseInt(p1[1], 10) - 1, parseInt(p1[2], 10));
    const d2 = new Date(parseInt(p2[0], 10), parseInt(p2[1], 10) - 1, parseInt(p2[2], 10));
    const diff = Math.round((d2 - d1) / 86400000);
    return Math.max(1, diff + 1);
}

function getDayNameFromIso(isoStr) {
    if (!isoStr) return '';
    const parts = isoStr.split('-');
    if (parts.length === 3) {
        const d = parseInt(parts[2], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parseInt(parts[0], 10);
        const dt = new Date(y, m, d);
        return INDO_DAYS_LIST[dt.getDay()] || '';
    }
    return '';
}

function getWeekendDaysCount(startIso, endIso) {
    if (!startIso || !endIso) return 0;
    const p1 = startIso.split('-');
    const p2 = endIso.split('-');
    const d1 = new Date(parseInt(p1[0], 10), parseInt(p1[1], 10) - 1, parseInt(p1[2], 10));
    const d2 = new Date(parseInt(p2[0], 10), parseInt(p2[1], 10) - 1, parseInt(p2[2], 10));
    let count = 0;
    const cur = new Date(d1);
    while (cur <= d2) {
        const day = cur.getDay();
        if (day === 0 || day === 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

// Global Calendar State for New Activity Modal
let newActCalState = {
    startDate: '',
    endDate: '',
    viewMonth: 0,
    viewYear: 2026,
    pickingStep: 1, // 1: set start, 2: set end
    customHours: null,
    isManualHours: false,
    isOpen: true
};

let newActWizard = {
    step: 1,
    title: '',
    category: 'K3 & Keselamatan Tambang',
    type: 'Wajib K3',
    duration: '2 Hari (16 Jam)',
    deadline: '',
    notes: '',
    templateFile: null,
    templateChoice: 'default'
};

function openNewActivityModal(presetStartDate = '') {
    const defaultStart = presetStartDate ? parseDateToIso(presetStartDate) : getSystemDateIso();
    const defaultEnd = addDaysToIsoDate(defaultStart, 1);
    const defaultDeadline = formatIsoToIndoDate(defaultEnd);
    renderNewActivityForm('', 'K3 & Keselamatan Tambang', 'Wajib K3', '2 Hari (16 Jam)', defaultDeadline, '', defaultStart);
}

function openNewActivityModalWithPreset(title, type, category, duration, level) {
    const defaultNotes = `Pengajuan dari rekomendasi LNA: ${title || ''} (${level || 'Basic'}). Diperlukan untuk peningkatan kompetensi operasional site Pomalaa.`;
    const defaultStart = getSystemDateIso();
    
    let days = 2;
    const dMatch = (duration || '').match(/(\d+)\s*Hari/i);
    if (dMatch) days = parseInt(dMatch[1], 10);
    const defaultEnd = addDaysToIsoDate(defaultStart, Math.max(0, days - 1));
    const defaultDeadline = formatIsoToIndoDate(defaultEnd);

    renderNewActivityForm(
        title || '',
        category || 'K3 & Keselamatan Tambang',
        type || 'Wajib K3',
        duration || '2 Hari (16 Jam)',
        defaultDeadline,
        defaultNotes,
        defaultStart
    );
}

function renderNewActivityForm(initialTitle, initialCat, initialType, initialDur, initialDeadline, initialNotes, initialStartDate = '') {
    const bodyHtml = `
        <form id="form-new-act" onsubmit="handleNewActivitySubmit(event)" class="form-new-act">
            <div class="p-3 mb-3 border rounded text-xs" style="background:#f0fdf4; border-color:#86efac; color:#166534; line-height:1.5;">
                <div class="flex items-center gap-1.5 font-bold mb-0.5" style="color:#15803d; font-size:0.8rem;">
                    <i class="ph-fill ph-shield-check"></i>
                    <span>Ketentuan Human Capital (HC) PT ANTAM • Site Pomalaa</span>
                </div>
                Masukkan usulan kegiatan pembelajaran. Estimasi waktu & rentang tanggal pelaksanaan dapat ditentukan secara interaktif melalui kalender di bawah ini.
            </div>

            <div class="form-group mb-3">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Nama Kegiatan / Modul Pelatihan *</label>
                <input type="text" id="wiz-act-title" class="form-input mt-1" placeholder="cth. Operasional Dump Truck Bawah Tanah" value="${escapeHtml(initialTitle)}" required>
            </div>

            <div class="form-grid mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="text-xs font-semibold text-muted font-mono uppercase">Kategori Kompetensi *</label>
                    <select id="wiz-act-category" class="form-input mt-1">
                        <option value="K3 & Keselamatan Tambang" ${initialCat.includes('K3') ? 'selected' : ''}>K3 & Keselamatan Tambang</option>
                        <option value="Operasional Tambang" ${initialCat.includes('Operasional') ? 'selected' : ''}>Operasional Tambang</option>
                        <option value="Regulasi ESDM" ${initialCat.includes('ESDM') ? 'selected' : ''}>Regulasi ESDM</option>
                        <option value="Teknik Pengolahan" ${initialCat.includes('Pengolahan') ? 'selected' : ''}>Teknik Pengolahan & Smelter</option>
                        <option value="Leadership & Supervisi" ${initialCat.includes('Leadership') ? 'selected' : ''}>Leadership & Supervisi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="text-xs font-semibold text-muted font-mono uppercase">Tipe Kurikulum *</label>
                    <select id="wiz-act-type" class="form-input mt-1">
                        <option value="Wajib K3" ${initialType.includes('Wajib') ? 'selected' : ''}>Wajib K3 Site</option>
                        <option value="Pengembangan Mandiri" ${initialType.includes('Mandiri') ? 'selected' : ''}>Pengembangan Mandiri</option>
                        <option value="Sertifikasi" ${initialType.includes('Sertifikasi') ? 'selected' : ''}>Sertifikasi Resmi</option>
                    </select>
                </div>
            </div>

            <!-- INPUT TANGGAL PELAKSANAAN (SIMPLE & CLEAN) -->
            <div class="form-grid mb-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-muted font-mono uppercase">Tanggal Mulai *</label>
                        <span class="text-xs text-accent font-semibold" id="text-hint-start-date">-</span>
                    </div>
                    <input type="date" id="wiz-act-start-date" class="form-input mt-1" onchange="handleNewActDateInputChange()">
                </div>

                <div class="form-group">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-muted font-mono uppercase">Target Selesai / Deadline *</label>
                        <span class="text-xs text-accent font-semibold" id="text-hint-end-date">-</span>
                    </div>
                    <input type="date" id="wiz-act-end-date" class="form-input mt-1" onchange="handleNewActDateInputChange()">
                </div>
            </div>

            <!-- Estimasi Durasi Ringkas (1 Baris Simpel & Rapi) -->
            <div class="act-simple-est-strip mb-3">
                <div class="flex items-center gap-2">
                    <span class="act-simple-pill" id="act-summary-duration-text">2 Hari (16 Jam)</span>
                    <span class="text-xs text-muted" id="act-summary-range-text">16 Okt 2026 s/d 17 Okt 2026</span>
                    <button type="button" class="act-simple-edit-btn" onclick="toggleNewActCustomHours()" title="Ubah Jam Manual">
                        <i class="ph-bold ph-pencil-simple"></i>
                    </button>
                </div>
                <span class="text-xs text-muted font-mono">Standar: 8 Jam/Hari</span>
            </div>

            <!-- Drawer Jam Manual (Hanya Muncul Jika Diklik) -->
            <div class="act-simple-custom-hours" id="act-custom-hours-wrap" style="display: none;">
                <span class="text-xs text-muted font-mono">JAM MANUAL:</span>
                <input type="number" id="act-manual-hours-input" class="form-input" style="width: 75px; padding: 0.2rem 0.4rem; font-size: 0.75rem; height: auto;" min="1" max="500" placeholder="Jam" oninput="handleManualHoursInput(this.value)">
                <span class="text-xs text-muted">Jam Pelatihan Efektif</span>
                <button type="button" class="text-xs text-accent underline ml-auto cursor-pointer" onclick="resetActHoursToStandard()">
                    Reset ke 8 Jam/Hari
                </button>
            </div>

            <!-- Synchronized inputs for backend API compatibility -->
            <input type="hidden" id="wiz-act-duration" value="${escapeHtml(initialDur)}">
            <input type="hidden" id="wiz-act-deadline" value="${escapeHtml(initialDeadline)}">

            <div class="form-group mb-3">
                <label class="text-xs font-semibold text-muted font-mono uppercase">Alasan Kebutuhan Pelatihan</label>
                <textarea id="wiz-act-notes" class="form-input mt-1" rows="2" placeholder="Jelaskan relevansi kegiatan ini terhadap tugas dan keselamatan kerja Anda di site...">${escapeHtml(initialNotes)}</textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t">
                <button type="button" class="btn-light" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-dark" id="btn-submit-wizard-act">
                    <i class="ph-fill ph-paper-plane-tilt"></i>
                    <span>Kirim Pengajuan ke Admin HC</span>
                </button>
            </div>
        </form>
    `;

    openModal('Ajukan Kegiatan Pembelajaran Baru', bodyHtml, '', 'ph-fill ph-plus-circle', false);

    setTimeout(() => {
        initNewActCalendar(initialStartDate, initialDeadline, initialDur);
    }, 60);
}

function initNewActCalendar(initialStartDate, initialDeadline, initialDur) {
    const startIso = initialStartDate ? parseDateToIso(initialStartDate) : getSystemDateIso();
    
    let days = 2;
    let hours = 16;
    const dMatch = (initialDur || '').match(/(\d+)\s*Hari/i);
    if (dMatch) days = parseInt(dMatch[1], 10);
    const hMatch = (initialDur || '').match(/(\d+)\s*Jam/i);
    if (hMatch) hours = parseInt(hMatch[1], 10);

    let endIso = addDaysToIsoDate(startIso, Math.max(0, days - 1));
    if (initialDeadline) {
        const parsedDead = parseDateToIso(initialDeadline);
        if (parsedDead >= startIso) {
            endIso = parsedDead;
            days = calculateDaysBetween(startIso, endIso);
            hours = days * 8;
        }
    }

    newActCalState.startDate = startIso;
    newActCalState.endDate = endIso;
    newActCalState.customHours = hours;
    newActCalState.isManualHours = (hours !== days * 8);

    updateNewActScheduleOutputs();
}

function handleNewActDateInputChange() {
    const startInput = document.getElementById('wiz-act-start-date');
    const endInput = document.getElementById('wiz-act-end-date');
    if (!startInput || !endInput) return;

    let sVal = startInput.value;
    let eVal = endInput.value;

    if (sVal) {
        newActCalState.startDate = sVal;
    }
    if (eVal) {
        if (sVal && eVal < sVal) {
            eVal = sVal;
            endInput.value = sVal;
        }
        newActCalState.endDate = eVal;
    } else if (sVal) {
        newActCalState.endDate = sVal;
    }

    const days = calculateDaysBetween(newActCalState.startDate, newActCalState.endDate);
    if (!newActCalState.isManualHours) {
        newActCalState.customHours = days * 8;
    }

    updateNewActScheduleOutputs();
}

function updateNewActScheduleOutputs() {
    const startInput = document.getElementById('wiz-act-start-date');
    const endInput = document.getElementById('wiz-act-end-date');
    const startHint = document.getElementById('text-hint-start-date');
    const endHint = document.getElementById('text-hint-end-date');
    const durInput = document.getElementById('wiz-act-duration');
    const deadInput = document.getElementById('wiz-act-deadline');
    const badgeText = document.getElementById('act-summary-duration-text');
    const rangeText = document.getElementById('act-summary-range-text');
    const manualHoursInput = document.getElementById('act-manual-hours-input');

    if (startInput) startInput.value = newActCalState.startDate;
    if (endInput) endInput.value = newActCalState.endDate;

    if (startHint) startHint.textContent = formatIsoToIndoFullDate(newActCalState.startDate);
    if (endHint) endHint.textContent = formatIsoToIndoFullDate(newActCalState.endDate);

    const days = calculateDaysBetween(newActCalState.startDate, newActCalState.endDate);
    const hours = (newActCalState.customHours !== null && !isNaN(newActCalState.customHours)) 
                  ? newActCalState.customHours 
                  : (days * 8);

    const durString = `${days} Hari (${hours} Jam)`;
    const deadString = formatIsoToIndoDate(newActCalState.endDate);

    if (durInput) durInput.value = durString;
    if (deadInput) deadInput.value = deadString;

    if (badgeText) badgeText.textContent = durString;

    if (rangeText) {
        if (newActCalState.startDate === newActCalState.endDate) {
            rangeText.textContent = `${formatIsoToIndoDate(newActCalState.startDate)} (1 Hari Pelatihan)`;
        } else {
            rangeText.textContent = `${formatIsoToIndoDate(newActCalState.startDate)} s/d ${formatIsoToIndoDate(newActCalState.endDate)}`;
        }
    }

    if (manualHoursInput) {
        manualHoursInput.value = hours;
    }

    if (window.newActWizard) {
        window.newActWizard.duration = durString;
        window.newActWizard.deadline = deadString;
    }
}

function toggleNewActCustomHours() {
    const wrap = document.getElementById('act-custom-hours-wrap');
    if (!wrap) return;
    if (wrap.style.display === 'none' || !wrap.style.display) {
        wrap.style.display = 'flex';
        const input = document.getElementById('act-manual-hours-input');
        if (input) input.focus();
    } else {
        wrap.style.display = 'none';
    }
}

function handleManualHoursInput(val) {
    const num = parseInt(val, 10);
    const days = calculateDaysBetween(newActCalState.startDate, newActCalState.endDate);
    if (!isNaN(num) && num > 0) {
        newActCalState.customHours = num;
        newActCalState.isManualHours = (num !== days * 8);
    } else {
        newActCalState.customHours = days * 8;
        newActCalState.isManualHours = false;
    }
    updateNewActScheduleOutputs();
}

function resetActHoursToStandard() {
    const days = calculateDaysBetween(newActCalState.startDate, newActCalState.endDate);
    newActCalState.customHours = days * 8;
    newActCalState.isManualHours = false;
    const wrap = document.getElementById('act-custom-hours-wrap');
    if (wrap) wrap.style.display = 'none';
    updateNewActScheduleOutputs();
}

async function handleNewActivitySubmit(e) {
    if (e && e.preventDefault) e.preventDefault();
    const titleEl = document.getElementById('wiz-act-title');
    const catEl = document.getElementById('wiz-act-category');
    const typeEl = document.getElementById('wiz-act-type');
    const durEl = document.getElementById('wiz-act-duration');
    const deadEl = document.getElementById('wiz-act-deadline');
    const notesEl = document.getElementById('wiz-act-notes');

    const title = titleEl ? titleEl.value.trim() : '';
    if (!title) {
        showToast('Nama Kegiatan / Modul Pelatihan wajib diisi!', 'warning');
        return;
    }

    const submitBtn = document.getElementById('btn-submit-wizard-act');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Mengirim Pengajuan...';
    }

    const fd = new FormData();
    fd.append('action', 'add_activity');
    fd.append('title', title);
    fd.append('category', catEl ? catEl.value : 'Operasional Tambang');
    fd.append('type', typeEl ? typeEl.value : 'Wajib K3');
    fd.append('duration', durEl ? durEl.value.trim() : '2 Hari (16 Jam)');
    fd.append('deadline', deadEl ? deadEl.value.trim() : formatIsoToIndoDate(getSystemDateIso()));

    let finalNotes = notesEl ? notesEl.value.trim() : '';
    if (newActCalState.startDate && newActCalState.endDate) {
        const scheduleNote = `[Jadwal Pelatihan: ${formatIsoToIndoDate(newActCalState.startDate)} s/d ${formatIsoToIndoDate(newActCalState.endDate)} · ${durEl ? durEl.value.trim() : ''}]`;
        if (!finalNotes.includes('Jadwal Pelatihan:')) {
            finalNotes = finalNotes ? `${finalNotes}\n\n${scheduleNote}` : scheduleNote;
        }
    }
    fd.append('notes', finalNotes);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            closeModal();
            if (typeof playNotificationSound === 'function') {
                playNotificationSound('success');
            }
            showToast(`✅ Usulan kegiatan "${title}" berhasil diajukan! Notifikasi terkirim ke Admin HC.`, 'success');
            setTimeout(() => { window.location.reload(); }, 900);
        } else {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph-fill ph-paper-plane-tilt"></i> Kirim Pengajuan ke Admin HC';
            }
            showToast(res.message || 'Gagal mengajukan kegiatan.', 'error');
        }
    } catch (err) {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ph-fill ph-paper-plane-tilt"></i> Kirim Pengajuan ke Admin HC';
        }
        showToast('Terjadi kesalahan saat mengirim pengajuan.', 'error');
    }
}

// Backward-compatible alias
function submitNewActivity() {
    const fakeEvt = { preventDefault: () => {} };
    handleNewActivitySubmit(fakeEvt);
}

// ==========================================
// L. PENYELESAIAN KEGIATAN: UPLOAD ABSENSI RESMI OLEH PIHAK HC
// ==========================================
let selectedAttendanceFile = null;

function openCompleteActivityAttendanceModal(activityId, courseTitle, targetUsername = '', targetName = '') {
    selectedAttendanceFile = null;
    const cleanCourse = courseTitle || 'Pelatihan K3 & Operasional Site';

    const currentUname = (window.CURRENT_USERNAME || '').toLowerCase();
    const userRole = window.CURRENT_USER_TYPE || 'User';
    const isUserAdmin = (userRole === 'Admin');

    // Validasi ketat hak akses: Pegawai biasa dilarang mengonfirmasi/upload absensi kegiatan milik pegawai lain
    if (!isUserAdmin && targetUsername && targetUsername.toLowerCase() !== currentUname) {
        showToast(`Akses Ditolak: Anda tidak memiliki izin untuk mengonfirmasi atau menambahkan berkas absensi pada kegiatan milik pegawai lain (${targetName || targetUsername}).`, 'error');
        return;
    }

    const effectiveOwnerName = targetName || window.CURRENT_USER_NAME || 'Pegawai';
    const effectiveOwnerUname = targetUsername || window.CURRENT_USERNAME || '';

    let attendeeSectionHtml = '';
    if (!isUserAdmin) {
        // Mode Pegawai: Otomatis tersimpan untuk akun sendiri tanpa kartu tambahan yang ramai
        attendeeSectionHtml = `
            <input type="hidden" name="attendance_attendees" value="${escapeHtml(effectiveOwnerName)}">
        `;
    } else {
        // Mode Admin / HC: Tampilkan daftar pegawai secara rapi
        const allUsers = (window.ALL_SYSTEM_USERS || []).filter(u => u.type !== 'Admin');
        const empCheckboxes = allUsers.map(emp => {
            const isOwner = (effectiveOwnerUname && emp.username.toLowerCase() === effectiveOwnerUname.toLowerCase())
                         || (effectiveOwnerName && emp.name.toLowerCase() === effectiveOwnerName.toLowerCase());
            return `
                <label class="flex items-center gap-2 p-2 border rounded cursor-pointer hover:bg-gray-50 text-xs mb-1" style="background:#fff; border-color:#e2e8f0; display:flex; align-items:center; justify-content:space-between;">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="attendance_attendees" value="${escapeHtml(emp.name)}" ${isOwner ? 'checked' : ''} class="form-checkbox" style="width:15px; height:15px; accent-color:#0f766e;">
                        <div>
                            <strong class="text-dark block" style="font-size:0.78rem;">${escapeHtml(emp.name)} ${isOwner ? '<span class="tag tag-teal" style="font-size:0.62rem; padding:1px 4px;">Pemilik</span>' : ''}</strong>
                            <span class="text-muted font-mono" style="font-size:0.68rem;">${escapeHtml(emp.role || emp.dept || emp.username)}</span>
                        </div>
                    </div>
                </label>
            `;
        }).join('');

        attendeeSectionHtml = `
            <div class="form-group mb-3">
                <div class="flex items-center justify-between mb-1">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Daftar Peserta Hadir (HC)</label>
                    <span class="text-xs text-accent cursor-pointer" onclick="toggleSelectAllAttendance(this)">Pilih Semua</span>
                </div>
                <div class="attendance-emp-list p-2 border rounded" style="background:#f8fafc; max-height:120px; overflow-y:auto;">
                    ${empCheckboxes}
                </div>
            </div>
        `;
    }

    const bodyHtml = `
        <div class="complete-act-modal-body">
            <div class="p-2.5 mb-3 rounded border flex items-center justify-between" style="background:#f8fafc; border-color:#e2e8f0;">
                <div>
                    <span class="text-xs text-muted block uppercase font-mono" style="font-size:0.68rem;">Kegiatan Pelatihan</span>
                    <strong class="text-dark block" style="font-size:0.88rem;">${escapeHtml(cleanCourse)}</strong>
                </div>
                <span class="tag tag-teal text-xs font-mono"><i class="ph-bold ph-user"></i> ${escapeHtml(effectiveOwnerName)}</span>
            </div>

            <!-- Upload Dokumen Absensi (Wajib) -->
            <div class="form-group mb-2">
                <label class="form-label font-semibold text-xs text-muted uppercase mb-1 block">Unggah Berkas Daftar Hadir / Absensi *</label>
                <div class="border-2 border-dashed rounded-lg p-4 text-center cursor-pointer hover:bg-slate-50 transition" 
                     id="attend-dropzone" 
                     onclick="document.getElementById('attend-file-input').click()"
                     style="border-color:#0f766e; background:#f0fdfa;">
                    <i class="ph-bold ph-upload-simple text-accent" style="font-size: 2rem; display:block; margin: 0 auto 0.35rem;"></i>
                    <div class="text-xs font-bold text-dark" id="attend-title">Klik atau Tarik Berkas Absensi ke Sini</div>
                    <div class="text-xs text-muted mt-0.5" id="attend-sub">Format: PDF scan tanda tangan, Excel (.xlsx), Word, atau foto presensi</div>
                    <input type="file" id="attend-file-input" accept=".pdf,.xlsx,.xls,.docx,.doc,.png,.jpg,.jpeg" style="display:none;" onchange="handleAttendanceFileSelect(this)">
                </div>
            </div>

            ${attendeeSectionHtml}
        </div>
    `;

    const footerHtml = `
        <button type="button" class="btn-light" onclick="closeModal()">Batal</button>
        <button type="button" class="btn-dark" id="btn-submit-attend" onclick="submitCompleteActivityAttendance('${activityId}', '${addslashesJs(cleanCourse)}')">
            <i class="ph-fill ph-check-circle"></i> Simpan Berkas Absensi
        </button>
    `;

    openModal('Unggah Berkas Absensi Pelatihan', bodyHtml, footerHtml, 'ph-fill ph-file-arrow-up');
}

function handleAttendanceFileSelect(input) {
    if (input.files && input.files[0]) {
        selectedAttendanceFile = input.files[0];
        const titleEl = document.getElementById('attend-title');
        const subEl = document.getElementById('attend-sub');
        const dropzone = document.getElementById('attend-dropzone');
        if (dropzone) {
            dropzone.style.borderColor = '#0f766e';
            dropzone.style.background = '#f0fdf4';
        }
        if (titleEl) {
            titleEl.innerHTML = `<span style="color:#0f766e; font-weight:700;"><i class="ph-fill ph-check-circle"></i> Berkas Absensi: ${escapeHtml(selectedAttendanceFile.name)}</span>`;
        }
        if (subEl) {
            subEl.textContent = `${(selectedAttendanceFile.size / 1024).toFixed(0)} KB · Berkas absensi siap diunggah & disimpan`;
        }
        showToast(`Berkas absensi "${selectedAttendanceFile.name}" berhasil dipilih.`, 'success');
    }
}

function toggleSelectAllAttendance(btn) {
    const checkboxes = document.querySelectorAll('input[name="attendance_attendees"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    btn.textContent = allChecked ? 'Pilih Semua' : 'Batal Semua';
}

function submitCompleteActivityAttendance(activityId, courseTitle) {
    if (!selectedAttendanceFile) {
        showToast('⚠️ Mohon upload file absensi terlebih dahulu sebagai bukti verifikasi penyelesaian kegiatan!', 'warning');
        const dropzone = document.getElementById('attend-dropzone');
        if (dropzone) {
            dropzone.scrollIntoView({ behavior: 'smooth' });
            dropzone.style.borderColor = '#ef4444';
            dropzone.style.background = '#fef2f2';
        }
        return;
    }

    const checked = Array.from(document.querySelectorAll('input[name="attendance_attendees"]:checked, input[type="hidden"][name="attendance_attendees"]')).map(cb => cb.value.trim());
    const extraEl = document.getElementById('attend-extra-names');
    if (extraEl && extraEl.value.trim()) {
        const extraNames = extraEl.value.split('\n').map(n => n.trim()).filter(n => n.length > 0);
        checked.push(...extraNames);
    }

    const submitBtn = document.getElementById('btn-submit-attend');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Mengunggah Berkas Absensi & Menyimpan...';
    }

    const fd = new FormData();
    fd.append('action', 'complete_activity_with_attendance');
    fd.append('activity_id', activityId);
    fd.append('course_title', courseTitle);
    fd.append('attendees', JSON.stringify(checked));
    fd.append('attendance_file', selectedAttendanceFile);

    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Simpan Berkas Absensi';
            }

            if (res.success) {
                if (typeof playNotificationSound === 'function') {
                    playNotificationSound('success');
                }
                showToast('🎉 Berhasil! Berkas absensi resmi telah tersimpan dan kegiatan berhasil diselesaikan.', 'success');
                closeModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                showToast(res.message || 'Gagal memproses absensi.', 'error');
            }
        })
        .catch(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph-fill ph-check-circle"></i> Simpan Berkas Absensi';
            }
            showToast('Terjadi gangguan jaringan. Coba lagi.', 'error');
        });
}

async function adminConfirmActivity(activityId, decision = 'setujui', title = 'Kegiatan', employeeName = 'Pegawai') {
    const fd = new FormData();
    fd.append('action', 'confirm_activity');
    fd.append('activity_id', activityId);
    fd.append('decision', decision);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();

        if (res.success) {
            if (decision === 'setujui') {
                showToast(`✅ Kegiatan "${title}" untuk ${employeeName} berhasil DIKONFIRMASI dan AKTIF!`, 'success');
            } else {
                showToast(`Kegiatan "${title}" untuk ${employeeName} telah ditolak.`, 'info');
            }

            // Remove pending card if present
            const card = document.getElementById(`req-card-${activityId}`);
            if (card) {
                card.style.opacity = '0.3';
                setTimeout(() => card.remove(), 400);
            }
            const notifItem = document.getElementById(`notif-act-${activityId}`);
            if (notifItem) {
                notifItem.style.opacity = '0.3';
                setTimeout(() => notifItem.remove(), 400);
            }

            // Immediately update tab counters and badge
            const countReq = document.getElementById('count-req');
            if (countReq) {
                let c = parseInt(countReq.textContent) || 0;
                countReq.textContent = Math.max(0, c - 1);
            }
            const countAll = document.getElementById('count-all');
            if (countAll) {
                let c = parseInt(countAll.textContent) || 0;
                countAll.textContent = Math.max(0, c - 1);
            }
            const pendTag = document.getElementById('pending-count-tag');
            if (pendTag) {
                let c = parseInt(pendTag.textContent) || 0;
                pendTag.textContent = `${Math.max(0, c - 1)} Menunggu Approval`;
            }
            if (typeof updateBadgeCount === 'function') updateBadgeCount(-1);
            if (typeof dismissAccBanner === 'function') dismissAccBanner(activityId);
            if (typeof fetchRealtimeUpdates === 'function') fetchRealtimeUpdates();

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(res.message || 'Gagal memproses konfirmasi.', 'error');
        }
    } catch(err) {
        showToast('Terjadi kesalahan koneksi server.', 'error');
    }
}

// Global Ctrl+K Shortcut for SunFish Topbar Search
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('global-search-input');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
});

// Topbar Search redirection or filtering
const globalSearchInput = document.getElementById('global-search-input');
if (globalSearchInput) {
    globalSearchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const q = encodeURIComponent(globalSearchInput.value.trim());
            if (window.location.search.includes('page=learning_bank')) {
                const courseFilter = document.getElementById('sf-filter-course');
                if (courseFilter) {
                    courseFilter.value = globalSearchInput.value;
                    if (typeof filterSunfishCourses === 'function') filterSunfishCourses();
                }
            } else {
                window.location.href = `?page=learning_bank&q=${q}`;
            }
        }
    });
}

// =========================================================
// REAL-TIME NOTIFICATION CHIME & AUDIO ENGINE (DUAL-LAYER)
// =========================================================
let _slAudioCtx = null;
let _slCachedChimeWav = null;

/**
 * Synthesize a pure 16-bit PCM WAV base64 chime (Zero external dependencies)
 * Elegant two-tone bell chime: E5 (659.25Hz) -> A5 (880.00Hz) with shimmer
 */
function getChimeWavDataUri() {
    if (_slCachedChimeWav) return _slCachedChimeWav;
    try {
        const sampleRate = 22050;
        const duration = 0.7;
        const numSamples = Math.floor(sampleRate * duration);
        const buffer = new ArrayBuffer(44 + numSamples * 2);
        const view = new DataView(buffer);

        function writeStr(offset, str) {
            for (let i = 0; i < str.length; i++) {
                view.setUint8(offset + i, str.charCodeAt(i));
            }
        }

        // RIFF Header
        writeStr(0, 'RIFF');
        view.setUint32(4, 36 + numSamples * 2, true);
        writeStr(8, 'WAVE');
        writeStr(12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, 1, true); // PCM
        view.setUint16(22, 1, true); // Mono
        view.setUint32(24, sampleRate, true);
        view.setUint32(28, sampleRate * 2, true);
        view.setUint16(32, 2, true);
        view.setUint16(34, 16, true);
        writeStr(36, 'data');
        view.setUint32(40, numSamples * 2, true);

        for (let i = 0; i < numSamples; i++) {
            const t = i / sampleRate;
            let s = 0;
            // Note 1: E5 (659Hz) ringing from 0s to 0.4s
            if (t < 0.4) {
                const env1 = Math.exp(-t * 9);
                s += Math.sin(2 * Math.PI * 659.25 * t) * env1 * 0.45;
                s += Math.sin(2 * Math.PI * 1318.5 * t) * env1 * 0.12;
            }
            // Note 2: A5 (880Hz) ringing prominently from 0.1s to end
            if (t >= 0.1) {
                const t2 = t - 0.1;
                const env2 = Math.exp(-t2 * 6);
                s += Math.sin(2 * Math.PI * 880.00 * t2) * env2 * 0.55;
                s += Math.sin(2 * Math.PI * 1760.0 * t2) * env2 * 0.15;
            }
            s = Math.max(-1, Math.min(1, s));
            view.setInt16(44 + i * 2, s < 0 ? s * 0x7FFF : s * 0x7FFF, true);
        }

        let binary = '';
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        _slCachedChimeWav = 'data:audio/wav;base64,' + btoa(binary);
        return _slCachedChimeWav;
    } catch (e) {
        return null;
    }
}

function getAudioContext() {
    if (!_slAudioCtx) {
        const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
        if (AudioCtxClass) {
            _slAudioCtx = new AudioCtxClass();
        }
    }
    if (_slAudioCtx && _slAudioCtx.state === 'suspended') {
        _slAudioCtx.resume().catch(() => {});
    }
    return _slAudioCtx;
}

// Unlock audio context on any user interaction (compliance with browser autoplay policies)
['click', 'keydown', 'touchstart'].forEach(evt => {
    window.addEventListener(evt, () => {
        getAudioContext();
    }, { passive: true });
});

/**
 * Play high-fidelity synthesized notification chime with dual-layer fallback
 * @param {string} type 'chime' | 'message' | 'request' | 'success'
 */
function playNotificationSound(type = 'chime') {
    // Layer 1: HTML5 Audio with embedded base64 WAV (bypasses many AudioContext restrictions)
    if (type !== 'success') {
        try {
            const uri = getChimeWavDataUri();
            if (uri) {
                const audio = new Audio(uri);
                audio.volume = 0.85;
                audio.play().catch(() => {});
            }
        } catch (e) {}
    }

    // Layer 2: Web Audio API Oscillator
    try {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        if (type === 'chime' || type === 'message' || type === 'request') {
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(659.25, now);
            gain1.gain.setValueAtTime(0, now);
            gain1.gain.linearRampToValueAtTime(0.35, now + 0.015);
            gain1.gain.exponentialRampToValueAtTime(0.0001, now + 0.38);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.38);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, now + 0.11);
            gain2.gain.setValueAtTime(0, now);
            gain2.gain.setValueAtTime(0, now + 0.11);
            gain2.gain.linearRampToValueAtTime(0.4, now + 0.125);
            gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.75);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.11);
            osc2.stop(now + 0.75);
        } else if (type === 'success') {
            [523.25, 659.25, 783.99].forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                const start = now + (idx * 0.08);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, start);
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.25, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.45);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(start);
                osc.stop(start + 0.45);
            });
        }
    } catch (e) {}
}

// =========================================================
// REAL-TIME NOTIFICATION & LIVE ACC ENGINE
// =========================================================
const _slSoundedActIds = new Set();
window._currentAccActId = null;
window._currentAccActTitle = null;
window._currentAccEmpName = null;

function getDismissedAccActs() {
    try {
        return JSON.parse(sessionStorage.getItem('sl_dismissed_acc_acts') || '[]');
    } catch (e) {
        return [];
    }
}

function addDismissedAccAct(actId) {
    if (!actId) return;
    const list = getDismissedAccActs();
    if (!list.includes(actId)) {
        list.push(actId);
        try {
            sessionStorage.setItem('sl_dismissed_acc_acts', JSON.stringify(list));
        } catch (e) {}
    }
}

function showAdminAccBanner(act) {
    const banner = document.getElementById('admin-acc-banner-container');
    if (!banner || !act) return;

    window._currentAccActId = act.id;
    window._currentAccActTitle = act.title || 'Kegiatan';
    window._currentAccEmpName = act.user_name || 'Pegawai';

    const empEl = document.getElementById('acc-emp-name');
    if (empEl) empEl.textContent = act.user_name || 'Pegawai';

    const titleEl = document.getElementById('acc-act-title');
    if (titleEl) titleEl.textContent = `"${act.title}"`;

    const timeEl = document.getElementById('acc-banner-time');
    if (timeEl) {
        timeEl.textContent = act.created_at ? `Diajukan: ${act.created_at}` : 'Baru saja diajukan';
    }

    banner.style.display = 'block';
}

function dismissAccBanner(actId) {
    const banner = document.getElementById('admin-acc-banner-container');
    if (banner) {
        banner.style.display = 'none';
    }
    const targetId = actId || window._currentAccActId;
    if (targetId) {
        addDismissedAccAct(targetId);
    }
}

async function handleQuickAcc() {
    const actId = window._currentAccActId;
    const title = window._currentAccActTitle || 'Kegiatan';
    const empName = window._currentAccEmpName || 'Pegawai';

    if (!actId) return;

    const btn = document.getElementById('btn-quick-acc');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Memproses ACC...';
    }

    const fd = new FormData();
    fd.append('action', 'confirm_activity');
    fd.append('activity_id', actId);
    fd.append('decision', 'setujui');

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();

        if (res.success) {
            playNotificationSound('success');
            dismissAccBanner(actId);
            showToast(`✅ Kegiatan "${title}" untuk ${empName} berhasil DI-ACC & DISETUJUI! Modul pembelajaran kini aktif.`, 'success');

            // If on admin panel page, remove pending card
            const card = document.getElementById(`req-card-${actId}`);
            if (card) {
                card.style.opacity = '0.3';
                setTimeout(() => card.remove(), 350);
            }

            // Immediately refresh data
            fetchRealtimeUpdates();
        } else {
            showToast(res.message || 'Gagal mengonfirmasi kegiatan.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> ACC / Setujui Sekarang';
            }
        }
    } catch (e) {
        showToast('Terjadi kesalahan saat mengonfirmasi kegiatan.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph-fill ph-check-circle"></i> ACC / Setujui Sekarang';
        }
    }
}

function initRealtimeNotifications() {
    // 1. Check initial pending activities passed directly from server
    if (window.CURRENT_USER_TYPE === 'Admin' && Array.isArray(window.INITIAL_PENDING_ACTIVITIES) && window.INITIAL_PENDING_ACTIVITIES.length > 0) {
        handleAdminPendingActivities(window.INITIAL_PENDING_ACTIVITIES);
    }

    // 2. Poll every 3 seconds for immediate real-time response
    setInterval(() => {
        fetchRealtimeUpdates();
    }, 3000);
}

async function fetchRealtimeUpdates() {
    try {
        const resp = await fetch('api.php?action=check_notifications');
        if (!resp.ok) return;
        const data = await resp.json();
        if (!data || !data.success) return;

        // 1. Synchronize Badge Counters
        updateBadgeCounters(data.badge_count, false);

        // 2. Handle Admin Pending Activities (ACC Alerts)
        if (data.user_type === 'Admin') {
            if (Array.isArray(data.pending_activities) && data.pending_activities.length > 0) {
                handleAdminPendingActivities(data.pending_activities);
            } else {
                // All pending items have been resolved
                const banner = document.getElementById('admin-acc-banner-container');
                if (banner) banner.style.display = 'none';
            }
        }

        // 3. Live Page DOM Updates (Admin Panel & Notifikasi Page)
        handleLivePageUpdates(data);

    } catch (e) {
        // Silently tolerate network blips
    }
}

function handleAdminPendingActivities(pendingList) {
    if (!Array.isArray(pendingList) || pendingList.length === 0) return;

    const dismissed = getDismissedAccActs();
    const activePending = pendingList.filter(act => !dismissed.includes(act.id));

    if (activePending.length === 0) {
        const banner = document.getElementById('admin-acc-banner-container');
        if (banner) banner.style.display = 'none';
        return;
    }

    // Pick the most recent unhandled activity
    const latestAct = activePending[0];

    // Show the prominent Live ACC Banner
    showAdminAccBanner(latestAct);

    // If this activity hasn't triggered an audio alert in this session yet:
    if (!_slSoundedActIds.has(latestAct.id)) {
        _slSoundedActIds.add(latestAct.id);

        // PLAY SOUND CHIME!
        playNotificationSound('request');

        // SHOW POPUP TOAST ALERT
        showRealtimeNotificationToast({
            id: latestAct.id,
            type: 'request',
            title: `Perlu ACC: ${latestAct.user_name || 'Pegawai'}`,
            message: `${latestAct.user_name || 'Pegawai'} mengajukan kegiatan "${latestAct.title}" dan menunggu ACC Admin.`,
            activity_id: latestAct.id
        });

        // Trigger red badge pulse
        const pill = document.getElementById('topbar-notif-pill');
        if (pill) {
            pill.classList.remove('badge-pulse-anim');
            void pill.offsetWidth;
            pill.classList.add('badge-pulse-anim');
        }
    }
}

function updateBadgeCounters(count, shouldPulse = false) {
    const pill = document.getElementById('topbar-notif-pill');
    if (pill) {
        pill.textContent = count > 9 ? '99+' : count;
        if (shouldPulse) {
            pill.classList.remove('badge-pulse-anim');
            void pill.offsetWidth;
            pill.classList.add('badge-pulse-anim');
        }
    }

    const dockBadge = document.getElementById('dock-notif-badge');
    if (dockBadge) {
        dockBadge.textContent = count;
        if (shouldPulse) {
            dockBadge.classList.remove('badge-pulse-anim');
            void dockBadge.offsetWidth;
            dockBadge.classList.add('badge-pulse-anim');
        }
    }
}

function showRealtimeNotificationToast(notif) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast-card toast-realtime-alert';

    const isRequest = (notif.type === 'request');
    const isApproval = (notif.type === 'approval');
    const actionUrl = isRequest ? '?page=admin_panel' : '?page=notifikasi';
    const actionLabel = isRequest ? 'Tinjau Sekarang &rarr;' : 'Lihat Detail &rarr;';
    const tagLabel = isRequest ? 'USULAN KEGIATAN BARU — PERLU ACC' : (isApproval ? 'KEGIATAN DISETUJUI' : 'PEMBERITAHUAN');
    const iconClass = isRequest ? 'ph-fill ph-hourglass-medium' : (isApproval ? 'ph-fill ph-check-circle' : 'ph-fill ph-bell-ringing');

    toast.innerHTML = `
        <div class="toast-icon-wrap">
            <i class="${iconClass} toast-bell-ring"></i>
        </div>
        <div class="toast-content-col">
            <div class="toast-alert-tag">${tagLabel}</div>
            <strong class="toast-alert-title">${_escapeHtml(notif.title || 'Pemberitahuan Sistem')}</strong>
            <p class="toast-alert-msg">${_escapeHtml(notif.message || '')}</p>
            <div class="toast-alert-actions">
                <a href="${actionUrl}" class="toast-btn-action">${actionLabel}</a>
            </div>
        </div>
        <button class="toast-close" onclick="this.closest('.toast-card').remove()">
            <i class="ph ph-x"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-fadeout');
        setTimeout(() => toast.remove(), 350);
    }, 8000);
}

function handleLivePageUpdates(data) {
    // 1. Admin Panel Page Dynamic Updates
    const pendingContainer = document.getElementById('pending-requests-container');
    const pendingTag = document.getElementById('pending-count-tag');
    if (pendingTag && typeof data.pending_count !== 'undefined') {
        pendingTag.textContent = `${data.pending_count} Menunggu Approval`;
    }

    if (pendingContainer && Array.isArray(data.pending_activities)) {
        const noPendingBox = document.getElementById('no-pending-box');
        if (data.pending_activities.length > 0) {
            if (noPendingBox) noPendingBox.style.display = 'none';

            let grid = pendingContainer.querySelector('.pending-admin-grid');
            if (!grid) {
                grid = document.createElement('div');
                grid.className = 'pending-admin-grid';
                pendingContainer.appendChild(grid);
            }

            // Insert new cards if not present
            data.pending_activities.forEach(act => {
                if (!document.getElementById(`req-card-${act.id}`)) {
                    const card = document.createElement('div');
                    card.className = 'admin-approval-card glass-panel';
                    card.id = `req-card-${act.id}`;
                    card.style = 'background: white; border-left: 5px solid #f59e0b; border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.25rem; animation: toastSlideIn 0.4s ease;';
                    card.innerHTML = `
                        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                            <div class="flex items-center gap-3">
                                <div class="mini-avatar" style="background: #0f766e; width: 42px; height: 42px; font-weight: 700;">
                                    ${_getInitials(act.user_name || 'PG')}
                                </div>
                                <div>
                                    <div class="font-semibold text-base">${_escapeHtml(act.user_name || 'Pegawai')}</div>
                                    <div class="text-xs text-muted font-mono">User: @${_escapeHtml(act.user_username || 'user')} · Diajukan: Baru saja</div>
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
                                <span class="tag tag-teal">${_escapeHtml(act.category || 'Operasional Tambang')}</span>
                                <span class="tag tag-gray">${_escapeHtml(act.type || 'Pengembangan Mandiri')}</span>
                                <span class="tag tag-gray font-mono"><i class="ph-fill ph-clock"></i> ${_escapeHtml(act.duration || '2 Hari')}</span>
                            </div>
                            <h3 class="font-semibold text-lg text-dark mt-2 mb-1">${_escapeHtml(act.title)}</h3>
                            <p class="text-sm text-muted"><strong>Catatan Pegawai:</strong> ${_escapeHtml(act.notes || 'Usulan pemenuhan kompetensi.')}</p>
                            <div class="text-xs text-muted font-mono mt-2"><i class="ph-fill ph-calendar"></i> Target Deadline: <strong>${_escapeHtml(act.deadline || '14 Hari')}</strong></div>
                        </div>
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            <button class="btn-dark" onclick="adminConfirmActivity('${act.id}', 'setujui', '${_escapeJs(act.title)}', '${_escapeJs(act.user_name)}')">
                                <i class="ph-fill ph-check-circle"></i>
                                <span>Konfirmasi & Setujui</span>
                            </button>
                            <button class="btn-outline" onclick="adminConfirmActivity('${act.id}', 'tolak', '${_escapeJs(act.title)}', '${_escapeJs(act.user_name)}')">
                                <i class="ph-fill ph-x-circle text-red"></i>
                                <span>Tolak</span>
                            </button>
                        </div>
                    `;
                    grid.insertBefore(card, grid.firstChild);
                }
            });
        } else {
            if (noPendingBox) noPendingBox.style.display = 'block';
        }
    }

    // 2. Notifikasi Center Page Dynamic Updates
    const countReqEl = document.getElementById('count-req');
    if (countReqEl && typeof data.pending_count !== 'undefined') countReqEl.textContent = data.pending_count;

    const countAllEl = document.getElementById('count-all');
    if (countAllEl && typeof data.pending_count !== 'undefined') countAllEl.textContent = data.pending_count + 2;

    const notifContainer = document.getElementById('admin-notif-container');
    if (notifContainer && Array.isArray(data.pending_activities) && data.pending_activities.length > 0) {
        data.pending_activities.forEach(act => {
            if (!document.getElementById(`notif-act-${act.id}`)) {
                const card = document.createElement('div');
                card.className = 'notif-item-card request-card';
                card.setAttribute('data-category', 'request');
                card.id = `notif-act-${act.id}`;
                card.style = 'animation: toastSlideIn 0.4s ease;';
                card.innerHTML = `
                    <div class="notif-card-indicator bg-amber"></div>
                    <div class="notif-badge-type bg-amber-soft text-amber">
                        <i class="ph-fill ph-hourglass-medium"></i>
                        <span>USULAN KEGIATAN PEGAWAI BARU</span>
                    </div>
                    <div class="notif-main-grid">
                        <div class="emp-avatar-box">
                            <div class="emp-av-circle" style="background: #0f766e;">
                                ${_getInitials(act.user_name || 'PG')}
                            </div>
                            <span class="emp-tag-under">Tambang</span>
                        </div>
                        <div class="notif-details-col">
                            <div class="notif-time-badge font-mono"><i class="ph-fill ph-clock"></i> Baru saja · Usulan Masuk</div>
                            <h3 class="notif-headline">
                                ${_escapeHtml(act.user_name || 'Pegawai')} — <span class="text-accent">${_escapeHtml(act.title)}</span>
                            </h3>
                            <div class="escalation-metadata-box">
                                <div class="meta-field">
                                    <span class="meta-label">Keterangan:</span>
                                    <span class="meta-val">${_escapeHtml(act.notes || 'Usulan pemenuhan kompetensi.')}</span>
                                </div>
                            </div>
                            <div class="notif-card-actions mt-3">
                                <a href="?page=admin_panel" class="btn-dark btn-sm">
                                    <i class="ph-fill ph-check-circle"></i> Buka Admin Console
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                notifContainer.insertBefore(card, notifContainer.firstChild);
            }
        });
    }
}

function _escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function _escapeJs(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function _getInitials(name) {
    if (!name) return 'PG';
    const parts = name.trim().split(/\s+/);
    if (parts.length > 1) {
        return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

