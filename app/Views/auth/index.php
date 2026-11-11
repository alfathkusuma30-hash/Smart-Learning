<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Smart Learning — Portal Pembelajaran PT ANTAM Tbk') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/logo-antam-icon.png') ?>">
</head>
<body class="login-body">

    <!-- Aesthetic Background with subtle blur effect -->
    <div class="login-bg-backdrop">
        <div class="login-bg-image" style="background-image: url('<?= base_url('assets/img/antam-pomalaa-bg.jpg') ?>');"></div>
        <div class="login-bg-overlay"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-header-block">
            <!-- Logo Resmi PT ANTAM Tbk & Smart Learning Bersanding -->
            <div class="login-logo-hero-container">
                <div class="hero-logo-halo"></div>
                <div class="hero-logos-pair">
                    <img src="<?= base_url('assets/img/logo-antam.png') ?>" alt="Logo PT ANTAM Tbk" class="hero-dual-logo hero-logo-antam">
                    <div class="hero-logos-divider"></div>
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Smart Learning Logo PT ANTAM" class="hero-dual-logo hero-logo-smart">
                </div>
            </div>
            
            <h1 class="login-main-title">
                Smart <span class="text-gradient-teal">Learning Portal</span>
            </h1>
            <p class="login-main-subtitle">
                Sistem Terpadu Pembelajaran Mandiri, Evaluasi Kepatuhan K3 ESDM &amp; Sertifikasi Kompetensi Site Pomalaa
            </p>
        </div>

        <?php if (!$isWaiting2FA): ?>
        <!-- Role Switcher Tabs (Pegawai HC vs Admin Biro) -->
        <div class="portal-tabs">
            <button type="button" class="portal-tab <?= $prefillRole !== 'admin' ? 'active' : '' ?>" id="tab-pegawai" onclick="switchPortalTab('pegawai')">
                <i class="ph-fill ph-users"></i>
                <span>Portal Pegawai (HC)</span>
                <span class="tab-chip">Human Capital</span>
            </button>
            <button type="button" class="portal-tab <?= $prefillRole === 'admin' ? 'active' : '' ?>" id="tab-admin" onclick="switchPortalTab('admin')">
                <i class="ph-fill ph-shield-star"></i>
                <span>Portal Admin (Biro)</span>
                <span class="tab-chip chip-gold">Konsol Biro</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Authentication Card Container -->
        <div class="login-card-container glass-panel">
            
            <!-- Alert Messages -->
            <?php if (!empty($errorMessage)): ?>
            <div class="login-alert alert-danger" id="login-error-alert">
                <i class="ph-fill ph-warning-circle"></i>
                <span><?= esc($errorMessage) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
            <div class="login-alert alert-success" id="login-success-alert">
                <i class="ph-fill ph-check-circle"></i>
                <span><?= esc($successMessage) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($isWaiting2FA): ?>
            <!-- ======================================================== -->
            <!-- STEP 2: VERIFIKASI KODE OTP (2FA)                        -->
            <!-- ======================================================== -->
            <div id="section-2fa">
                <div class="auth-form-header">
                    <div class="portal-icon-circle" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">
                        <i class="ph-fill ph-shield-check"></i>
                    </div>
                    <div>
                        <h2 class="auth-box-title">Verifikasi Kode OTP</h2>
                        <p class="auth-box-subtitle">Masukkan 6-digit kode verifikasi keamanan akun Anda</p>
                    </div>
                </div>

                <!-- Account Identity Badge -->
                <div class="auth-2fa-badge-card">
                    <div class="auth-2fa-avatar" style="background: <?= esc($pendingUser['avatar_color'] ?? '#0f766e') ?>; overflow: hidden; padding: 0;">
                        <?php if (!empty($pendingUser['avatar_image']) && file_exists(FCPATH . 'uploads/avatars/' . $pendingUser['avatar_image'])): ?>
                            <img src="<?= base_url('uploads/avatars/' . esc($pendingUser['avatar_image'])) ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                        <?php else: ?>
                            <?= esc($pendingUser['user_id'] ?? 'AT') ?>
                        <?php endif; ?>
                    </div>
                    <div class="auth-2fa-user-info">
                        <div class="auth-2fa-user-name"><?= esc($pendingUser['name'] ?? 'Pengguna ANTAM') ?></div>
                        <div class="auth-2fa-user-meta">
                            <span><i class="ph-fill ph-identification-card"></i> <?= esc($pendingUser['username']) ?></span>
                            <span>•</span>
                            <span class="auth-2fa-role-tag">
                                <?= ($pendingUser['type'] === 'Admin') ? 'Admin (Biro)' : 'Pegawai (HC)' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Email Notice -->
                <div class="auth-2fa-email-notice">
                    <p>Kode telah dikirim ke:</p>
                    <div class="auth-masked-email-pill">
                        <i class="ph-fill ph-envelope-simple"></i>
                        <span><?= esc($maskedEmail) ?></span>
                    </div>
                </div>

                <!-- Status Pengiriman Email (2FA) -->
                <?php if (!empty($pendingUser['mail_sent'])): ?>
                <div class="mb-4" style="background: #f0fdfa; border: 1px solid #99f6e4; padding: 1rem 1.25rem; border-radius: 12px; text-align: left; display: flex; align-items: flex-start; gap: 0.75rem;">
                    <i class="ph-fill ph-check-circle" style="font-size: 1.5rem; color: #0f766e; flex-shrink: 0; margin-top: 2px;"></i>
                    <div>
                        <div style="font-weight: 700; color: #0f766e; font-size: 0.9rem;">Email OTP Berhasil Dikirim</div>
                        <div style="font-size: 0.8rem; color: #334155; margin-top: 3px; line-height: 1.5;">
                            6-digit kode OTP keamanan telah dikirimkan ke kotak masuk email Anda. Silakan periksa folder <strong>Inbox</strong> atau folder <strong>Spam</strong>.
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="mb-4" style="background: #fffbeb; border: 1px solid #fde68a; padding: 1rem 1.25rem; border-radius: 12px; text-align: left; display: flex; align-items: flex-start; gap: 0.75rem;">
                    <i class="ph-bold ph-warning-circle" style="font-size: 1.5rem; color: #d97706; flex-shrink: 0; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: #92400e; font-size: 0.9rem;">Email OTP Belum Terkirim ke Inbox</div>
                        <div style="font-size: 0.8rem; color: #78350f; margin-top: 3px; line-height: 1.5;">
                            Google Mail (SMTP) menolak otentikasi karena mewajibkan <strong>Sandi Aplikasi (16 karakter)</strong>. Kode OTP tidak dapat dikirim sebelum sandi aplikasi diatur.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- OTP 6-Boxes Form -->
                <form method="POST" action="<?= base_url('verify-2fa') ?>" id="form-2fa-verify">
                    <input type="hidden" name="otp_code" id="input-full-otp" value="">

                    <div class="otp-boxes-grid">
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit-box" id="otp-1" autocomplete="one-time-code" required autofocus>
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit-box" id="otp-2" required>
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit-box" id="otp-3" required>
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit-box" id="otp-4" required>
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit-box" id="otp-5" required>
                        <input type="text" inputmode="numeric" maxlength="1" class="otp-digit-box" id="otp-6" required>
                    </div>

                    <button type="submit" class="btn-auth-submit" id="btn-submit-otp">
                        <i class="ph-bold ph-sign-in"></i>
                        <span>Verifikasi &amp; Masuk ke Menu Home</span>
                        <i class="ph-bold ph-arrow-right"></i>
                    </button>
                </form>

                <!-- Resend & Cancel Controls -->
                <div class="otp-actions-bar">
                    <form method="POST" action="<?= base_url('resend-otp') ?>" id="form-resend-otp" style="margin: 0;">
                        <button type="submit" class="btn-resend-otp" id="btn-resend" disabled>
                            <i class="ph-bold ph-arrows-clockwise"></i>
                            <span id="resend-text">Kirim Ulang Kode (<span id="resend-timer">60</span>s)</span>
                        </button>
                    </form>

                    <a href="<?= base_url('cancel-2fa') ?>" class="btn-cancel-auth">
                        <i class="ph-bold ph-arrow-counter-clockwise"></i>
                        <span>Batal &amp; Ganti Akun</span>
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- ======================================================== -->
            <!-- STEP 1: PILIHAN MASUK AKUN ATAU BUAT AKUN BARU           -->
            <!-- ======================================================== -->

            <!-- Mode Selector Nav -->
            <div class="auth-mode-nav">
                <button type="button" class="auth-mode-btn <?= $authMode !== 'register' ? 'active' : '' ?>" id="btn-mode-login" onclick="switchAuthMode('login')">
                    <i class="ph-fill ph-sign-in"></i>
                    <span>Masuk Akun</span>
                </button>
                <button type="button" class="auth-mode-btn <?= $authMode === 'register' ? 'active' : '' ?>" id="btn-mode-register" onclick="switchAuthMode('register')">
                    <i class="ph-fill ph-user-plus"></i>
                    <span>Buat Akun Baru</span>
                </button>
            </div>

            <!-- PANEL 1: LOGIN FORM -->
            <div id="panel-login" style="<?= $authMode === 'register' ? 'display: none;' : '' ?>">
                <form method="POST" action="<?= base_url('login') ?>" id="login-form" class="auth-form-body">
                    <input type="hidden" name="portal" id="input-portal" value="<?= esc($prefillRole) ?>">
                    
                    <div class="auth-form-header">
                        <div class="portal-icon-circle" id="portal-icon-display">
                            <i class="ph-fill ph-user" id="portal-icon-elem"></i>
                        </div>
                        <div>
                            <h2 class="auth-box-title" id="auth-box-title">Login Pegawai (HC)</h2>
                            <p class="auth-box-subtitle" id="auth-box-subtitle">Gunakan username atau email resmi ANTAM Anda</p>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="username-input">
                            <i class="ph-fill ph-user-circle"></i> USERNAME ATAU EMAIL
                        </label>
                        <div class="input-with-icon">
                            <input type="text" name="username" id="username-input" class="form-input-auth" 
                                   placeholder="Username atau email (cth. egy / egy.pratama@antam.com)" 
                                   value="<?= esc($prefillUser) ?>" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="password-input">
                            <i class="ph-fill ph-lock-key"></i> KATA SANDI
                        </label>
                        <div class="input-with-icon password-input-wrap">
                            <input type="password" name="password" id="password-input" class="form-input-auth" placeholder="Masukkan kata sandi akun Anda" required autocomplete="current-password">
                            <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password-input', 'eye-icon-login')" title="Tampilkan/Sembunyikan Kata Sandi">
                                <i class="ph-bold ph-eye" id="eye-icon-login"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth-submit" id="btn-submit-login">
                        <i class="ph-bold ph-sign-in"></i>
                        <span>Masuk ke Menu Home</span>
                        <i class="ph-bold ph-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- PANEL 2: REGISTRATION FORM (Otomatis Sesuai Pilihan HC / Biro di Atas) -->
            <div id="panel-register" style="<?= $authMode !== 'register' ? 'display: none;' : '' ?>">
                <form method="POST" action="<?= base_url('register') ?>" id="register-form" class="auth-form-body">
                    <input type="hidden" name="portal" id="register-portal-input" value="<?= esc($prefillRole) ?>">

                    <div class="auth-form-header">
                        <div class="portal-icon-circle" id="reg-icon-display" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">
                            <i class="ph-fill ph-user-plus" id="reg-icon-elem"></i>
                        </div>
                        <div>
                            <h2 class="auth-box-title" id="reg-box-title">
                                <?= $prefillRole === 'admin' ? 'Registrasi Admin (Biro)' : 'Registrasi Pegawai (HC)' ?>
                            </h2>
                            <p class="auth-box-subtitle" id="reg-box-subtitle">
                                <?= $prefillRole === 'admin' ? 'Daftarkan akun konsol Biro Human Capital & Diklat' : 'Daftarkan akun pegawai Human Capital & operasional' ?>
                            </p>
                        </div>
                    </div>

                    <div class="register-form-grid">
                        <div class="form-group mb-3">
                            <label class="form-label" for="reg-name">
                                <i class="ph-fill ph-user"></i> NAMA LENGKAP *
                            </label>
                            <input type="text" name="name" id="reg-name" class="form-input-auth" placeholder="cth. Fajar Pratama" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="reg-username">
                                <i class="ph-fill ph-at"></i> USERNAME UNIK *
                            </label>
                            <input type="text" name="username" id="reg-username" class="form-input-auth" placeholder="cth. fajar_pratama" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="reg-nrp">
                                <i class="ph-fill ph-cardholder"></i> NPP LENGKAP
                            </label>
                            <input type="text" name="nrp" id="reg-nrp" class="form-input-auth" placeholder="cth. NPP-09124">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="reg-phone">
                                <i class="ph-fill ph-phone"></i> NO. TELEPON / WA
                            </label>
                            <input type="text" name="phone" id="reg-phone" class="form-input-auth" placeholder="cth. 0812-3456-7890">
                        </div>

                        <div class="form-group mb-3 grid-col-full">
                            <label class="form-label" for="reg-email">
                                <i class="ph-fill ph-envelope"></i> ALAMAT EMAIL AKTIF (UNTUK PENERIMAAN KODE OTP 2FA) *
                            </label>
                            <input type="email" name="email" id="reg-email" class="form-input-auth" placeholder="cth. nama.anda@gmail.com atau nama@antam.com" required>
                            <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block; line-height: 1.4;">
                                🔒 <strong>Validasi Keamanan:</strong> Sistem akan memeriksa domain email via DNS MX record dan mengirimkan 6-digit kode OTP masuk ke email ini. Pastikan Anda memiliki akses ke inbox email tersebut.
                            </span>
                        </div>

                        <div class="form-group mb-3 grid-col-full">
                            <label class="form-label" for="reg-dept">
                                <i class="ph-fill ph-buildings"></i> DEPARTEMEN / UNIT KERJA
                            </label>
                            <select name="dept" id="reg-dept" class="form-select-auth">
                                <option value="Mining Operations Underground">Mining Operations Underground (Tambang Bawah Tanah)</option>
                                <option value="Plant & Smelter Pomalaa">Plant & Smelter Pomalaa (Pabrik Pengolahan Feni)</option>
                                <option value="Supply Chain & Bahan Peledak">Supply Chain & Logistik Bahan Peledak</option>
                                <option value="Human Capital & Corporate University">Human Capital & Corporate University (Biro HC)</option>
                                <option value="K3, Lingkungan Hidup & ESDM">K3, Lingkungan Hidup & Kepatuhan ESDM</option>
                                <option value="Maintenance & Power Plant">Maintenance & Power Plant Pomalaa</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="reg-pass">
                                <i class="ph-fill ph-lock"></i> KATA SANDI *
                            </label>
                            <div class="input-with-icon password-input-wrap">
                                <input type="password" name="password" id="reg-pass" class="form-input-auth" placeholder="Min. 6 Karakter" required minlength="6">
                                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('reg-pass', 'eye-reg-1')">
                                    <i class="ph-bold ph-eye" id="eye-reg-1"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label" for="reg-pass-confirm">
                                <i class="ph-fill ph-check-square-offset"></i> KONFIRMASI KATA SANDI *
                            </label>
                            <div class="input-with-icon password-input-wrap">
                                <input type="password" name="password_confirm" id="reg-pass-confirm" class="form-input-auth" placeholder="Ulangi kata sandi" required minlength="6">
                                <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('reg-pass-confirm', 'eye-reg-2')">
                                    <i class="ph-bold ph-eye" id="eye-reg-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth-submit" id="btn-submit-register">
                        <i class="ph-bold ph-user-plus"></i>
                        <span>Daftar Akun Baru</span>
                        <i class="ph-bold ph-arrow-right"></i>
                    </button>

                    <div class="text-center mt-3 text-xs text-muted">
                        Sudah memiliki akun terdaftar? 
                        <a href="javascript:void(0)" onclick="switchAuthMode('login')" style="color: #0f766e; font-weight: 700; text-decoration: underline;">
                            Masuk di sini
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        </div>

        <!-- Footer -->
        <div class="login-footer-bar">
            <div class="footer-item">
                <i class="ph-fill ph-shield-check text-accent"></i>
                <span>ISO 45001 &amp; ESDM No. 1827/2018</span>
            </div>
            <div class="footer-separator">•</div>
            <div class="footer-item">
                <i class="ph-fill ph-arrows-left-right"></i>
                <span>Alarm K3 Dua Arah</span>
            </div>
            <div class="footer-separator">•</div>
            <div class="footer-item">
                <i class="ph-fill ph-buildings"></i>
                <span>PT ANTAM Tbk Pomalaa &bull; CI4 Architecture</span>
            </div>
        </div>
    </div>

    <!-- Login & 2FA Interactive Scripts -->
    <script>
        function switchPortalTab(role) {
            const tabPegawai = document.getElementById('tab-pegawai');
            const tabAdmin = document.getElementById('tab-admin');
            const portalInput = document.getElementById('input-portal');
            const regPortalInput = document.getElementById('register-portal-input');
            
            const boxTitle = document.getElementById('auth-box-title');
            const boxSub = document.getElementById('auth-box-subtitle');
            const iconElem = document.getElementById('portal-icon-elem');
            const iconDisplay = document.getElementById('portal-icon-display');

            const regBoxTitle = document.getElementById('reg-box-title');
            const regBoxSub = document.getElementById('reg-box-subtitle');
            const regIconDisplay = document.getElementById('reg-icon-display');

            if (portalInput) portalInput.value = role;
            if (regPortalInput) regPortalInput.value = role;

            if (role === 'pegawai') {
                if (tabPegawai) tabPegawai.classList.add('active');
                if (tabAdmin) tabAdmin.classList.remove('active');

                if (boxTitle) boxTitle.textContent = 'Login Pegawai (HC)';
                if (boxSub) boxSub.textContent = 'Gunakan username atau email resmi ANTAM Anda';
                if (iconElem) iconElem.className = 'ph-fill ph-user';
                if (iconDisplay) {
                    iconDisplay.style.background = 'rgba(212, 175, 55, 0.15)';
                    iconDisplay.style.color = '#d4af37';
                }

                if (regBoxTitle) regBoxTitle.textContent = 'Registrasi Pegawai (HC)';
                if (regBoxSub) regBoxSub.textContent = 'Daftarkan akun pegawai Human Capital & operasional';
                if (regIconDisplay) {
                    regIconDisplay.style.background = 'rgba(212, 175, 55, 0.15)';
                    regIconDisplay.style.color = '#d4af37';
                }
            } else {
                if (tabAdmin) tabAdmin.classList.add('active');
                if (tabPegawai) tabPegawai.classList.remove('active');

                if (boxTitle) boxTitle.textContent = 'Login Admin (Biro)';
                if (boxSub) boxSub.textContent = 'Konsol Biro Human Capital, L&D & K3 Management';
                if (iconElem) iconElem.className = 'ph-fill ph-shield-star';
                if (iconDisplay) {
                    iconDisplay.style.background = 'rgba(16, 185, 129, 0.15)';
                    iconDisplay.style.color = '#10b981';
                }

                if (regBoxTitle) regBoxTitle.textContent = 'Registrasi Admin (Biro)';
                if (regBoxSub) regBoxSub.textContent = 'Daftarkan akun konsol Biro Human Capital & Diklat';
                if (regIconDisplay) {
                    regIconDisplay.style.background = 'rgba(16, 185, 129, 0.15)';
                    regIconDisplay.style.color = '#10b981';
                }
            }
        }

        function switchAuthMode(mode) {
            const btnLogin = document.getElementById('btn-mode-login');
            const btnRegister = document.getElementById('btn-mode-register');
            const panelLogin = document.getElementById('panel-login');
            const panelRegister = document.getElementById('panel-register');

            if (mode === 'register') {
                if (btnLogin) btnLogin.classList.remove('active');
                if (btnRegister) btnRegister.classList.add('active');
                if (panelLogin) panelLogin.style.display = 'none';
                if (panelRegister) panelRegister.style.display = 'block';
            } else {
                if (btnRegister) btnRegister.classList.remove('active');
                if (btnLogin) btnLogin.classList.add('active');
                if (panelRegister) panelRegister.style.display = 'none';
                if (panelLogin) panelLogin.style.display = 'block';
            }
        }

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ph-bold ph-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'ph-bold ph-eye';
            }
        }

        // 2FA OTP 6-Box Interactivity
        document.addEventListener('DOMContentLoaded', () => {
            const otpInputs = [
                document.getElementById('otp-1'),
                document.getElementById('otp-2'),
                document.getElementById('otp-3'),
                document.getElementById('otp-4'),
                document.getElementById('otp-5'),
                document.getElementById('otp-6')
            ];

            const form2FA = document.getElementById('form-2fa-verify');
            const fullOtpInput = document.getElementById('input-full-otp');

            if (otpInputs[0]) {
                otpInputs.forEach((input, index) => {
                    if (!input) return;

                    input.addEventListener('input', (e) => {
                        const val = input.value;
                        if (val.length > 0) {
                            input.value = val.charAt(val.length - 1);
                            input.classList.add('filled');
                            if (index < otpInputs.length - 1) {
                                otpInputs[index + 1].focus();
                            }
                        } else {
                            input.classList.remove('filled');
                        }
                        updateFullOtp();
                    });

                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && input.value === '' && index > 0) {
                            otpInputs[index - 1].focus();
                        } else if (e.key === 'ArrowLeft' && index > 0) {
                            otpInputs[index - 1].focus();
                        } else if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    });

                    input.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                        if (/^\d{6}$/.test(pasteData)) {
                            pasteData.split('').forEach((char, i) => {
                                if (otpInputs[i]) {
                                    otpInputs[i].value = char;
                                    otpInputs[i].classList.add('filled');
                                }
                            });
                            updateFullOtp();
                            otpInputs[5].focus();
                        }
                    });
                });

                function updateFullOtp() {
                    let full = '';
                    otpInputs.forEach(i => { if (i) full += i.value; });
                    if (fullOtpInput) fullOtpInput.value = full;
                }

                // Resend Timer Countdown
                let timeLeft = 60;
                const timerSpan = document.getElementById('resend-timer');
                const resendBtn = document.getElementById('btn-resend');
                const resendText = document.getElementById('resend-text');

                const countdown = setInterval(() => {
                    timeLeft--;
                    if (timerSpan) timerSpan.textContent = timeLeft;
                    if (timeLeft <= 0) {
                        clearInterval(countdown);
                        if (resendBtn) resendBtn.disabled = false;
                        if (resendText) resendText.innerHTML = 'Kirim Ulang Kode';
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>

