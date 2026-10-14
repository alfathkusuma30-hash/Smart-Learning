<?php
$username = $_SESSION['username'] ?? '';
$user = get_user_by_username($username);

if (!$user) {
    echo "<div class='p-6 text-center text-red font-semibold'>Data pengguna tidak ditemukan. Silakan login kembali.</div>";
    return;
}

// Inisial avatar pengguna
$initials = !empty($user['user_id']) ? $user['user_id'] : '';
if (empty($initials)) {
    $nameParts = explode(' ', trim($user['name'] ?? ''));
    if (count($nameParts) > 1 && !empty($nameParts[0]) && !empty($nameParts[1])) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $initials = strtoupper(substr($user['name'] ?? $username, 0, 2));
    }
}
$avatarColor = !empty($user['avatar_color']) ? $user['avatar_color'] : '#0f766e';
?>

<div class="settings-container">
    <div class="page-top-header">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="page-title">Pengaturan Akun & Keamanan</h1>
                <span class="badge-role-tag"><?php echo strtoupper(htmlspecialchars($user['type'] ?? 'USER')); ?></span>
            </div>
            <p class="text-xs text-muted mt-1">Kelola data profil personel, kredensial login, dan preferensi alarm K3</p>
        </div>
        <div class="page-top-actions">
            <span class="status-chip-live">
                <span class="live-dot"></span>
                <span>Akun Terverifikasi</span>
            </span>
        </div>
    </div>

    <!-- User Mini Profile Banner (With Security & Mining Watermark Artwork) -->
    <div class="settings-profile-banner glass-panel mb-6">
        <!-- Silhouette / Watermark Artwork (Personnel Security & Mining Identity) -->
        <div class="settings-banner-watermark" aria-hidden="true">
            <div class="settings-banner-watermark-glow"></div>
            <div class="settings-banner-watermark-img"></div>
        </div>

        <div class="settings-banner-left">
            <div class="settings-avatar-wrapper" onclick="document.getElementById('settings-avatar-input').click()" title="Klik untuk langsung mengganti foto profil">
                <div class="settings-avatar-preview settings-avatar-interactive" id="settings-avatar-circle" style="background: <?php echo htmlspecialchars($avatarColor); ?>; overflow: hidden; padding: 0;">
                    <?php if (!empty($user['avatar_image']) && file_exists(__DIR__ . '/../uploads/avatars/' . $user['avatar_image'])): ?>
                        <img src="uploads/avatars/<?php echo htmlspecialchars($user['avatar_image']); ?>" alt="Foto Profil" id="banner-avatar-img" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                    <?php else: ?>
                        <span id="banner-avatar-initials"><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                    <div class="avatar-hover-hint">
                        <i class="ph-bold ph-camera"></i>
                    </div>
                </div>
                <div class="avatar-camera-badge" title="Klik untuk mengganti foto profil">
                    <i class="ph-bold ph-camera"></i>
                </div>
            </div>
            <!-- Input Berkas Foto Tersembunyi (Langsung terbuka saat avatar ditekan) -->
            <input type="file" id="settings-avatar-input" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;" onchange="handleDirectAvatarUpload(this)">
            
            <div class="settings-user-info-block">
                <div class="settings-banner-tag">
                    <i class="ph-fill ph-shield-check text-accent"></i>
                    <span>IDENTITAS RESMI PERSONEL &bull; SITE POMALAA</span>
                </div>
                <h2 class="settings-banner-name" id="banner-user-name"><?php echo htmlspecialchars($user['name'] ?? $username); ?></h2>
                <div class="settings-badges-row font-mono">
                    <span class="settings-badge"><i class="ph-fill ph-identification-card"></i> NPP: <strong id="banner-user-nrp"><?php echo htmlspecialchars($user['nrp'] ?? '-'); ?></strong></span>
                    <span class="settings-badge"><i class="ph-fill ph-hard-hat"></i> <strong id="banner-user-role"><?php echo htmlspecialchars($user['role'] ?? 'Operasional Tambang'); ?></strong></span>
                    <span class="settings-badge"><i class="ph-fill ph-buildings"></i> <strong id="banner-user-dept"><?php echo htmlspecialchars($user['dept'] ?? 'PT ANTAM Tbk'); ?></strong></span>
                </div>
            </div>
        </div>

        <div class="settings-banner-right">
            <div class="settings-security-pill">
                <div class="sec-pill-icon">
                    <i class="ph-fill ph-shield-check"></i>
                </div>
                <div class="sec-pill-content">
                    <div class="sec-pill-title">Keamanan Akun K3</div>
                    <div class="sec-pill-subtitle">
                        <span class="live-dot"></span>
                        <span>Terverifikasi &bull; 2FA Aktif</span>
                    </div>
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
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                    
                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Nama Lengkap *</label>
                        <input type="text" id="profile-name" class="form-input mt-1" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">NPP Lengkap</label>
                        <input type="text" id="profile-nrp" class="form-input mt-1" value="<?php echo htmlspecialchars($user['nrp'] ?? ''); ?>" placeholder="cth. ANTAM-08912">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Departemen / Unit Kerja</label>
                        <input type="text" id="profile-dept" class="form-input mt-1" value="<?php echo htmlspecialchars($user['dept'] ?? ''); ?>" placeholder="cth. Mining Operations Underground">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Email Korporat</label>
                        <input type="email" id="profile-email" class="form-input mt-1" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="nama@antam.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">No. WhatsApp / Telepon</label>
                        <input type="tel" id="profile-phone" class="form-input mt-1" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0812-xxxx-xxxx">
                    </div>

                    <div class="form-group">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Warna Tema Avatar</label>
                        <div class="color-picker-row mt-1 flex items-center gap-3">
                            <input type="color" id="profile-avatar-color" value="<?php echo htmlspecialchars($avatarColor); ?>" onchange="previewAvatarColor(this.value)" class="color-input">
                            <span class="text-xs text-muted">Pilih warna aksen lencana profil Anda</span>
                        </div>
                    </div>

                    <div class="form-group full-width" style="grid-column: 1 / -1;">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Deskripsi / Catatan Kompetensi</label>
                        <textarea id="profile-bio" class="form-input mt-1" rows="3" placeholder="Fokus kompetensi lapangan atau sertifikasi yang sedang dikejar..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t flex-wrap gap-3">
                    <span class="text-xs text-muted"><i class="ph-fill ph-info"></i> Perubahan profil langsung disinkronkan ke sistem Smart Learning ANTAM.</span>
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
                        <input type="password" id="old-password" class="form-input" placeholder="Masukkan kata sandi saat ini" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('old-password', this)" title="Lihat kata sandi">
                            <i class="ph ph-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Kata Sandi Baru *</label>
                    <div class="input-with-icon password-input-wrap mt-1">
                        <input type="password" id="new-password" class="form-input" placeholder="Minimal 5 karakter" required minlength="5" onkeyup="checkPasswordStrength(this.value)" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('new-password', this)" title="Lihat kata sandi">
                            <i class="ph ph-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength-indicator mt-2" id="pass-strength-bar" style="height: 4px; border-radius: 4px; background: #e2e8f0; width: 100%;">
                        <div id="pass-strength-fill" style="width: 0%; height: 100%; transition: all 0.3s; border-radius: 4px;"></div>
                    </div>
                    <span id="pass-strength-text" class="text-xs text-muted mt-1 block"></span>
                </div>

                <div class="form-group mb-6">
                    <label class="form-label font-semibold text-xs text-muted uppercase">Konfirmasi Kata Sandi Baru *</label>
                    <div class="input-with-icon password-input-wrap mt-1">
                        <input type="password" id="confirm-password" class="form-input" placeholder="Ketik ulang kata sandi baru" required minlength="5" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm-password', this)" title="Lihat kata sandi">
                            <i class="ph ph-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t flex-wrap gap-3">
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
                            <select id="pref-reminder-freq" class="form-input text-xs" style="width: 150px;">
                                <option value="Realtime" <?php echo ($user['reminder_freq'] ?? '') === 'Realtime' ? 'selected' : ''; ?>>Realtime</option>
                                <option value="12 Jam" <?php echo ($user['reminder_freq'] ?? '') === '12 Jam' ? 'selected' : ''; ?>>Setiap 12 Jam</option>
                                <option value="24 Jam" <?php echo ($user['reminder_freq'] ?? '24 Jam') === '24 Jam' ? 'selected' : ''; ?>>Setiap 24 Jam</option>
                            </select>
                        </div>
                    </div>

                    <div class="pref-item p-3 border rounded-md mb-3 flex items-center justify-between" style="border: 1px solid #e2e8f0;">
                        <div>
                            <strong class="text-sm">Notifikasi WhatsApp Gateway</strong>
                            <p class="text-xs text-muted">Menerima peringatan langsung dari bot resmi L&D ANTAM ke nomor WhatsApp</p>
                        </div>
                        <div>
                            <label class="switch-toggle" title="Aktifkan/Nonaktifkan notifikasi WhatsApp">
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
                            <label class="switch-toggle" title="Aktifkan efek visual peringatan">
                                <input type="checkbox" checked id="pref-visual-glow">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t flex-wrap gap-3">
                    <span class="text-xs text-muted">Terhubung dengan server Site Pomalaa.</span>
                    <button type="submit" class="btn-dark" id="btn-save-pref">
                        <i class="ph-fill ph-check-circle"></i>
                        <span>Simpan Preferensi</span>
                    </button>
                </div>
            </form>

            <?php if (($user['type'] ?? '') === 'Admin'): ?>
            <div class="mt-6 pt-6 border-t">
                <div class="flex items-center gap-3 mb-3">
                    <div class="mini-avatar" style="background:#25D366; color:white; width:36px; height:36px; font-size:1.25rem;">
                        <i class="ph-fill ph-whatsapp-logo"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sm">Integrasi Bot WhatsApp Gateway (Fonnte API)</h4>
                        <p class="text-xs text-muted">Opsional: Hubungkan Token API jika ingin bot korporat mengirim peringatan otomatis di background tanpa perlu membuka WhatsApp Web.</p>
                    </div>
                </div>
                
                <div class="p-4 rounded-md" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="form-group mb-3">
                        <label class="form-label font-semibold text-xs text-muted uppercase">Token API Fonnte (Opsional)</label>
                        <input type="text" id="cfg-gateway-token" class="form-input text-xs font-mono mt-1" placeholder="cth. aB12cDeFgHijK123..." value="<?php $gw = get_gateway_config(); echo htmlspecialchars($gw['token'] ?? ''); ?>">
                        <small class="text-xs text-muted mt-1 block">Dapatkan token gratis Anda di <a href="https://fonnte.com" target="_blank" style="color: #0d9488; text-decoration: underline;">fonnte.com</a>. Jika token kosong, pengiriman tetap berjalan 100% via WhatsApp Web langsung.</small>
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-3 pt-2">
                        <label class="flex items-center gap-2 text-xs cursor-pointer">
                            <input type="checkbox" id="cfg-gateway-autosend" <?php echo !empty($gw['auto_send']) ? 'checked' : ''; ?> style="accent-color: #16a34a;">
                            <span>Kirim pesan otomatis via Gateway API saat tombol Nudge ditekan</span>
                        </label>
                        <button type="button" class="btn-outline text-xs" onclick="handleSaveGatewayConfig()">
                            <i class="ph-fill ph-floppy-disk"></i> Simpan Pengaturan Gateway
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts for Settings Interactivity -->
<script>
function safeNotify(message, type = 'info') {
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        alert(message);
    }
}

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

function togglePasswordVisibility(inputId, btn) {
    const inp = document.getElementById(inputId);
    if (!inp) return;
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        if (icon) {
            icon.className = 'ph ph-eye-slash';
        }
    } else {
        inp.type = 'password';
        if (icon) {
            icon.className = 'ph ph-eye';
        }
    }
}

function checkPasswordStrength(val) {
    const bar = document.getElementById('pass-strength-fill');
    const txt = document.getElementById('pass-strength-text');
    if (!bar) return;
    if (val.length === 0) {
        bar.style.width = '0%';
        if (txt) txt.textContent = '';
    } else if (val.length < 5) {
        bar.style.width = '30%';
        bar.style.background = '#ef4444';
        if (txt) {
            txt.textContent = 'Terlalu pendek (minimal 5 karakter)';
            txt.style.color = '#ef4444';
        }
    } else if (val.length < 8) {
        bar.style.width = '65%';
        bar.style.background = '#f59e0b';
        if (txt) {
            txt.textContent = 'Kekuatan sedang';
            txt.style.color = '#f59e0b';
        }
    } else {
        bar.style.width = '100%';
        bar.style.background = '#10b981';
        if (txt) {
            txt.textContent = 'Kuat & Aman';
            txt.style.color = '#10b981';
        }
    }
}

async function handleSaveProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-profile');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Menyimpan...';
    }

    const name = document.getElementById('profile-name').value.trim();
    const nrp = document.getElementById('profile-nrp').value.trim();
    const dept = document.getElementById('profile-dept').value.trim();
    const email = document.getElementById('profile-email').value.trim();
    const phone = document.getElementById('profile-phone').value.trim();
    const avatarColor = document.getElementById('profile-avatar-color').value;
    const bio = document.getElementById('profile-bio').value.trim();

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('name', name);
    fd.append('nrp', nrp);
    fd.append('dept', dept);
    fd.append('email', email);
    fd.append('phone', phone);
    fd.append('avatar_color', avatarColor);
    fd.append('bio', bio);

    // Append avatar file if selected
    const avatarInput = document.getElementById('settings-avatar-input');
    if (avatarInput && avatarInput.files && avatarInput.files[0]) {
        fd.append('avatar_file', avatarInput.files[0]);
    }

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            safeNotify('✅ Profil berhasil diperbarui!', 'success');
            // Update live preview banner
            const bName = document.getElementById('banner-user-name');
            if (bName) bName.textContent = name;
            const bNrp = document.getElementById('banner-user-nrp');
            if (bNrp) bNrp.textContent = nrp || '-';
            const bDept = document.getElementById('banner-user-dept');
            if (bDept) bDept.textContent = dept || 'PT ANTAM';

            // Update topbar elements
            const topName = document.querySelector('.user-fullname');
            if (topName) topName.textContent = name.toUpperCase();
            const topAv = document.querySelector('.topbar .mini-avatar');
            if (topAv) topAv.style.background = avatarColor;

            // Update avatar images if updated
            if (res.user && res.user.avatar_image) {
                const newImgUrl = 'uploads/avatars/' + res.user.avatar_image + '?v=' + Date.now();
                const bImg = document.getElementById('banner-avatar-img');
                const bInit = document.getElementById('banner-avatar-initials');
                if (bImg) {
                    bImg.src = newImgUrl;
                    bImg.style.display = 'block';
                } else if (bInit) {
                    bInit.outerHTML = '<img src="' + newImgUrl + '" alt="Foto Profil" id="banner-avatar-img" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">';
                }
                if (topAv) {
                    topAv.innerHTML = '<img src="' + newImgUrl + '" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">';
                }
            }
        } else {
            safeNotify(res.message || 'Gagal menyimpan profil.', 'error');
        }
    } catch(err) {
        safeNotify('Terjadi kesalahan koneksi server.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function handleDirectAvatarUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Validasi format file gambar
    const validExts = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/jpg'];
    if (!validExts.includes(file.type.toLowerCase()) && !file.name.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
        safeNotify('Format file harus berupa gambar (JPG, PNG, atau WEBP).', 'error');
        input.value = '';
        return;
    }

    // Validasi ukuran berkas (maksimal 5MB)
    if (file.size > 5 * 1024 * 1024) {
        safeNotify('Ukuran gambar maksimal 5MB.', 'error');
        input.value = '';
        return;
    }

    // Pratinjau lokal instan
    const reader = new FileReader();
    reader.onload = function(e) {
        const bImg = document.getElementById('banner-avatar-img');
        const bInit = document.getElementById('banner-avatar-initials');
        if (bImg) {
            bImg.src = e.target.result;
            bImg.style.display = 'block';
        } else if (bInit) {
            bInit.outerHTML = '<img src="' + e.target.result + '" alt="Foto Profil" id="banner-avatar-img" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">';
        }
    };
    reader.readAsDataURL(file);

    // Animasi feedback upload pada avatar circle
    const avCircle = document.getElementById('settings-avatar-circle');
    if (avCircle) {
        avCircle.style.opacity = '0.5';
    }

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('avatar_file', file);

    const nameInput = document.getElementById('profile-name');
    if (nameInput && nameInput.value.trim()) {
        fd.append('name', nameInput.value.trim());
    }

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            safeNotify('✅ Foto profil berhasil diperbarui!', 'success');
            if (res.user && res.user.avatar_image) {
                const newImgUrl = 'uploads/avatars/' + res.user.avatar_image + '?v=' + Date.now();
                const bImg = document.getElementById('banner-avatar-img');
                if (bImg) {
                    bImg.src = newImgUrl;
                    bImg.style.display = 'block';
                }
                const topAv = document.querySelector('.topbar .mini-avatar');
                if (topAv) {
                    topAv.innerHTML = '<img src="' + newImgUrl + '" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">';
                }
            }
        } else {
            safeNotify(res.message || 'Gagal mengunggah foto profil.', 'error');
        }
    } catch(err) {
        safeNotify('Terjadi kesalahan koneksi saat mengunggah foto.', 'error');
    } finally {
        if (avCircle) {
            avCircle.style.opacity = '1';
        }
        input.value = '';
    }
}

async function handleSavePassword(e) {
    e.preventDefault();
    const oldPass = document.getElementById('old-password').value;
    const newPass = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;

    if (newPass.length < 5) {
        safeNotify('Kata sandi baru minimal 5 karakter!', 'error');
        return;
    }

    if (newPass !== confirmPass) {
        safeNotify('Konfirmasi kata sandi baru tidak cocok!', 'error');
        return;
    }

    const btn = document.getElementById('btn-save-password');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Memproses...';
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
            safeNotify(res.message || '✅ Kata sandi berhasil diubah! Gunakan kata sandi baru saat login berikutnya.', 'success');
            document.getElementById('form-password-settings').reset();
            checkPasswordStrength('');
        } else {
            safeNotify(res.message || 'Gagal mengubah kata sandi.', 'error');
        }
    } catch(err) {
        safeNotify('Terjadi kesalahan koneksi server.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function handleSavePreferences(e) {
    e.preventDefault();
    const freq = document.getElementById('pref-reminder-freq').value;
    const wa = document.getElementById('pref-wa-notif').checked ? '1' : '0';

    const btn = document.getElementById('btn-save-pref');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Menyimpan...';
    }

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('reminder_freq', freq);
    fd.append('wa_notif', wa);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            safeNotify('✅ Preferensi alarm & pengingat berhasil disimpan.', 'success');
        } else {
            safeNotify(res.message || 'Gagal menyimpan preferensi.', 'error');
        }
    } catch(err) {
        safeNotify('Terjadi kesalahan koneksi.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

async function handleSaveGatewayConfig() {
    const token = document.getElementById('cfg-gateway-token') ? document.getElementById('cfg-gateway-token').value.trim() : '';
    const autoSend = document.getElementById('cfg-gateway-autosend') ? document.getElementById('cfg-gateway-autosend').checked : false;

    const fd = new FormData();
    fd.append('action', 'save_gateway_config');
    fd.append('token', token);
    fd.append('auto_send', autoSend ? '1' : '0');

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            safeNotify('✅ ' + res.message, 'success');
            if (window.GATEWAY_CONFIG) {
                window.GATEWAY_CONFIG.token = token;
                window.GATEWAY_CONFIG.auto_send = autoSend;
            }
        } else {
            safeNotify(res.message || 'Gagal menyimpan pengaturan gateway.', 'error');
        }
    } catch(err) {
        safeNotify('Terjadi kesalahan koneksi server.', 'error');
    }
}
</script>
