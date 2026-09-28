<?php
$username = $_SESSION['username'];
$user = get_user_by_username($username);

if (!$user) {
    echo "<div class='p-6 text-center text-red'>Data pengguna tidak ditemukan.</div>";
    return;
}
?>

<div class="settings-container">
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="page-title">Pengaturan Akun & Keamanan</h1>
                <span class="badge-role-tag"><?php echo strtoupper($user['type']); ?></span>
            </div>
        </div>
        <div class="page-top-actions">
            <span class="status-chip-live">
                <span class="live-dot"></span>
                <span>Akun Terverifikasi</span>
            </span>
        </div>
    </div>

    <!-- User Mini Profile Banner -->
    <div class="settings-profile-banner glass-panel mb-6">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="settings-avatar-preview" id="settings-avatar-circle" style="background: <?php echo htmlspecialchars($user['avatar_color']); ?>;">
                <?php echo htmlspecialchars($user['user_id']); ?>
            </div>
            <div>
                <h2 class="text-xl font-bold" id="banner-user-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                <div class="flex items-center gap-3 text-xs text-muted mt-1 flex-wrap font-mono">
                    <span><i class="ph-fill ph-identification-card"></i> NRP: <?php echo htmlspecialchars($user['nrp']); ?></span>
                    <span>•</span>
                    <span><i class="ph-fill ph-hard-hat"></i> <?php echo htmlspecialchars($user['role']); ?></span>
                    <span>•</span>
                    <span><i class="ph-fill ph-buildings"></i> <?php echo htmlspecialchars($user['dept']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Sub Tabs -->
    <div class="sub-nav-tabs mb-6">
        <button class="sub-tab active" onclick="switchSettingsTab(this, 'tab-profile')">
            <i class="ph-fill ph-user-gear"></i> Profil Pengguna
        </button>
        <button class="sub-tab" onclick="switchSettingsTab(this, 'tab-password')">
            <i class="ph-fill ph-lock-key"></i> Ganti Password
        </button>
        <button class="sub-tab" onclick="switchSettingsTab(this, 'tab-alarm')">
            <i class="ph-fill ph-bell-ringing"></i> Preferensi Alarm & Notifikasi
        </button>
    </div>

    <!-- TAB 1: PROFIL PENGGUNA -->
    <div id="tab-profile" class="tab-settings-panel active">
        <div class="glass-panel" style="background: white; border-radius: var(--radius-md); padding: 2rem;">
            <form id="form-profile-settings" onsubmit="handleSaveProfile(event)">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Nama Lengkap *</label>
                        <input type="text" id="profile-name" class="form-input mt-1" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">NRP / ID Pegawai</label>
                        <input type="text" id="profile-nrp" class="form-input mt-1" value="<?php echo htmlspecialchars($user['nrp']); ?>" placeholder="cth. ANTAM-08912">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Departemen / Unit Kerja</label>
                        <input type="text" id="profile-dept" class="form-input mt-1" value="<?php echo htmlspecialchars($user['dept']); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Email Korporat</label>
                        <input type="email" id="profile-email" class="form-input mt-1" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="nama@antam.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">No. WhatsApp / Telepon</label>
                        <input type="tel" id="profile-phone" class="form-input mt-1" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="0812-xxxx-xxxx">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Warna Tema Avatar</label>
                        <div class="color-picker-row mt-1 flex items-center gap-3">
                            <input type="color" id="profile-avatar-color" value="<?php echo htmlspecialchars($user['avatar_color']); ?>" onchange="previewAvatarColor(this.value)" class="color-input">
                            <span class="text-xs text-muted">Pilih warna aksen lencana profil</span>
                        </div>
                    </div>

                    <div class="form-group full-width" style="grid-column: span 2;">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Deskripsi / Catatan Kompetensi</label>
                        <textarea id="profile-bio" class="form-input mt-1" rows="3" placeholder="Fokus kompetensi lapangan atau sertifikasi yang sedang dikejar..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>

                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t">
                    <span class="text-xs text-muted"><i class="ph-fill ph-info"></i> Perubahan profil langsung disimpan ke sistem pusat PT ANTAM.</span>
                    <button type="submit" class="btn-dark" id="btn-save-profile">
                        <i class="ph-fill ph-floppy-disk"></i>
                        <span>Simpan Perubahan Profil</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 2: GANTI PASSWORD -->
    <div id="tab-password" class="tab-settings-panel" style="display: none;">
        <div class="glass-panel" style="background: white; border-radius: var(--radius-md); padding: 2rem; max-width: 680px;">
            <div class="mb-4">
                <h3 class="font-semibold text-base mb-1">Perbarui Kata Sandi</h3>
                <p class="text-xs text-muted">Pastikan kata sandi baru memenuhi standar keamanan korporat (minimal 5 karakter).</p>
            </div>

            <form id="form-password-settings" onsubmit="handleSavePassword(event)">
                <div class="form-group mb-4">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Kata Sandi Saat Ini *</label>
                    <div class="input-with-icon password-input-wrap mt-1">
                        <input type="password" id="old-password" class="form-input" placeholder="Masukkan kata sandi saat ini" required>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Kata Sandi Baru *</label>
                    <div class="input-with-icon password-input-wrap mt-1">
                        <input type="password" id="new-password" class="form-input" placeholder="Minimal 5 karakter" required onkeyup="checkPasswordStrength(this.value)">
                    </div>
                    <div class="password-strength-indicator mt-2" id="pass-strength-bar" style="height: 4px; border-radius: 4px; background: #e2e8f0; width: 100%;">
                        <div id="pass-strength-fill" style="width: 0%; height: 100%; transition: all 0.3s; border-radius: 4px;"></div>
                    </div>
                </div>

                <div class="form-group mb-6">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Konfirmasi Kata Sandi Baru *</label>
                    <div class="input-with-icon password-input-wrap mt-1">
                        <input type="password" id="confirm-password" class="form-input" placeholder="Ketik ulang kata sandi baru" required>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t">
                    <span class="text-xs text-muted">Protokol Keamanan Antam v2.4</span>
                    <button type="submit" class="btn-dark" id="btn-save-password">
                        <i class="ph-fill ph-lock-key"></i>
                        <span>Perbarui Kata Sandi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 3: PREFERENSI ALARM & NOTIFIKASI -->
    <div id="tab-alarm" class="tab-settings-panel" style="display: none;">
        <div class="glass-panel" style="background: white; border-radius: var(--radius-md); padding: 2rem;">
            <div class="mb-4">
                <h3 class="font-semibold text-base mb-1">Preferensi Alarm Dua Arah & Reminder K3</h3>
                <p class="text-xs text-muted">Atur bagaimana sistem Smart Learning memberi sinyal pengingat ketika mendekati deadline modul.</p>
            </div>

            <form id="form-alarm-settings" onsubmit="handleSavePreferences(event)">
                <div class="pref-items-list space-y-4">
                    
                    <div class="pref-item p-3 border rounded-md mb-3 flex items-center justify-between" style="border: 1px solid #e2e8f0;">
                        <div>
                            <strong class="text-sm">Frekuensi Pengingat Reminder</strong>
                            <p class="text-xs text-muted">Berapa sering alarm memeriksa tenggat waktu pelatihan Anda</p>
                        </div>
                        <div>
                            <select id="pref-reminder-freq" class="form-input text-xs" style="width: 140px;">
                                <option value="Realtime" <?php echo $user['reminder_freq'] === 'Realtime' ? 'selected' : ''; ?>>Realtime</option>
                                <option value="12 Jam" <?php echo $user['reminder_freq'] === '12 Jam' ? 'selected' : ''; ?>>Setiap 12 Jam</option>
                                <option value="24 Jam" <?php echo $user['reminder_freq'] === '24 Jam' ? 'selected' : ''; ?>>Setiap 24 Jam</option>
                            </select>
                        </div>
                    </div>

                    <div class="pref-item p-3 border rounded-md mb-3 flex items-center justify-between" style="border: 1px solid #e2e8f0;">
                        <div>
                            <strong class="text-sm">Notifikasi WhatsApp Gateway</strong>
                            <p class="text-xs text-muted">Menerima peringatan langsung dari bot resmi L&D ANTAM ke nomor WhatsApp</p>
                        </div>
                        <div>
                            <label class="switch-toggle">
                                <input type="checkbox" id="pref-wa-notif" <?php echo (!empty($user['wa_notif'])) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="pref-item p-3 border rounded-md mb-3 flex items-center justify-between" style="border: 1px solid #e2e8f0;">
                        <div>
                            <strong class="text-sm">Sinyal Peringatan Visual (Pulse Glow)</strong>
                            <p class="text-xs text-muted">Kedipkan lencana merah di desktop saat terdapat modul kritis atau overdue</p>
                        </div>
                        <div>
                            <label class="switch-toggle">
                                <input type="checkbox" checked id="pref-visual-glow">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t">
                    <span class="text-xs text-muted">Terhubung dengan server Site Pongkor.</span>
                    <button type="submit" class="btn-dark">
                        <i class="ph-fill ph-check-circle"></i>
                        <span>Simpan Preferensi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts for Settings Interactivity -->
<script>
function switchSettingsTab(btn, tabId) {
    document.querySelectorAll('.sub-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.tab-settings-panel').forEach(p => {
        p.style.display = 'none';
        p.classList.remove('active');
    });

    const target = document.getElementById(tabId);
    if (target) {
        target.style.display = 'block';
        target.classList.add('active');
    }
}

function previewAvatarColor(color) {
    const av = document.getElementById('settings-avatar-circle');
    if (av) av.style.background = color;
}

function checkPasswordStrength(val) {
    const bar = document.getElementById('pass-strength-fill');
    if (!bar) return;
    if (val.length === 0) {
        bar.style.width = '0%';
    } else if (val.length < 5) {
        bar.style.width = '30%';
        bar.style.background = '#ef4444';
    } else if (val.length < 8) {
        bar.style.width = '65%';
        bar.style.background = '#f59e0b';
    } else {
        bar.style.width = '100%';
        bar.style.background = '#10b981';
    }
}

async function handleSaveProfile(e) {
    e.preventDefault();
    const name = document.getElementById('profile-name').value;
    const nrp = document.getElementById('profile-nrp').value;
    const dept = document.getElementById('profile-dept').value;
    const email = document.getElementById('profile-email').value;
    const phone = document.getElementById('profile-phone').value;
    const avatarColor = document.getElementById('profile-avatar-color').value;
    const bio = document.getElementById('profile-bio').value;

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('name', name);
    fd.append('nrp', nrp);
    fd.append('dept', dept);
    fd.append('email', email);
    fd.append('phone', phone);
    fd.append('avatar_color', avatarColor);
    fd.append('bio', bio);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            showToast('✅ Profil berhasil diperbarui!', 'success');
            document.getElementById('banner-user-name').textContent = name;
            // Update topbar name if present
            const topName = document.querySelector('.user-fullname');
            if (topName) topName.textContent = name;
            const topAv = document.querySelector('.topbar .mini-avatar');
            if (topAv) topAv.style.background = avatarColor;
        } else {
            showToast(res.message || 'Gagal menyimpan profil.', 'error');
        }
    } catch(err) {
        showToast('Terjadi kesalahan koneksi server.', 'error');
    }
}

async function handleSavePassword(e) {
    e.preventDefault();
    const oldPass = document.getElementById('old-password').value;
    const newPass = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;

    if (newPass !== confirmPass) {
        showToast('Konfirmasi kata sandi baru tidak cocok!', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'update_password');
    fd.append('old_password', oldPass);
    fd.append('new_password', newPass);
    fd.append('confirm_password', confirmPass);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            showToast('✅ Kata sandi berhasil diubah! Gunakan kata sandi baru saat login berikutnya.', 'success');
            document.getElementById('form-password-settings').reset();
            checkPasswordStrength('');
        } else {
            showToast(res.message || 'Gagal mengubah kata sandi.', 'error');
        }
    } catch(err) {
        showToast('Terjadi kesalahan koneksi server.', 'error');
    }
}

async function handleSavePreferences(e) {
    e.preventDefault();
    const freq = document.getElementById('pref-reminder-freq').value;
    const wa = document.getElementById('pref-wa-notif').checked ? '1' : '0';

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('reminder_freq', freq);
    fd.append('wa_notif', wa);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            showToast('✅ Preferensi alarm & pengingat berhasil disimpan.', 'success');
        } else {
            showToast('Gagal menyimpan preferensi.', 'error');
        }
    } catch(err) {
        showToast('Terjadi kesalahan koneksi.', 'error');
    }
}
</script>
