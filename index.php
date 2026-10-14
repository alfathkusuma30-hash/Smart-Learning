<?php
session_start();
require_once __DIR__ . '/includes/data.php';

// Redirect if already logged in
if (isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    header("Location: app.php?page=home");
    exit();
}

// Handle Cancel 2FA
if (isset($_GET['action']) && $_GET['action'] === 'cancel_2fa') {
    unset($_SESSION['pending_2fa']);
    header("Location: index.php");
    exit();
}

$errorMessage = '';
$successMessage = '';
$authMode = isset($_POST['auth_mode']) ? trim($_POST['auth_mode']) : (isset($_GET['mode']) ? trim($_GET['mode']) : 'login');
$prefillRole = isset($_POST['portal']) ? trim($_POST['portal']) : (isset($_GET['role']) ? trim($_GET['role']) : 'pegawai');
$prefillUsername = isset($_POST['username']) ? trim($_POST['username']) : '';

// Check if currently waiting for 2FA verification
$isWaiting2FA = isset($_SESSION['pending_2fa']) && !empty($_SESSION['pending_2fa']['username']);
$pendingUser = $isWaiting2FA ? $_SESSION['pending_2fa'] : null;

// Process Form Submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : 'login';

    // ----------------------------------------------------
    // 1. SUBMIT KREDENSIAL LOGIN (STEP 1 -> 2FA)
    // ----------------------------------------------------
    if ($action === 'login') {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $portal   = isset($_POST['portal']) ? trim($_POST['portal']) : 'pegawai';

        if (empty($username) || empty($password)) {
            $errorMessage = 'Silakan masukkan username/email dan kata sandi Anda.';
        } else {
            $auth = authenticate_user($username, $password);

            if ($auth['success']) {
                $user = $auth['user'];

                // Verifikasi kesesuaian portal
                if ($portal === 'admin' && ($user['type'] ?? '') !== 'Admin') {
                    $errorMessage = 'Akun ini terdaftar sebagai Pegawai HC. Silakan beralih dan masuk melalui Portal Pegawai (HC).';
                } elseif ($portal === 'pegawai' && ($user['type'] ?? '') === 'Admin') {
                    $errorMessage = 'Akun ini adalah Administrator Biro. Silakan beralih dan masuk melalui Portal Admin (Biro).';
                } else {
                    // Kredensial valid! Generate & kirim OTP 2FA
                    $otpRes = generate_and_send_2fa_otp($user);
                    if ($otpRes['success']) {
                        $isWaiting2FA = true;
                        $pendingUser  = $_SESSION['pending_2fa'];
                        $successMessage = 'Kredensial valid. Kode OTP verifikasi telah dikirim ke email terdaftar Anda.';
                    } else {
                        $errorMessage = 'Gagal memproses kode OTP. Silakan coba kembali.';
                    }
                }
            } else {
                $errorMessage = $auth['message'];
            }
        }
    }

    // ----------------------------------------------------
    // 2. VERIFIKASI KODE OTP 2FA (STEP 2 -> DASHBOARD)
    // ----------------------------------------------------
    elseif ($action === 'verify_2fa') {
        if (!$isWaiting2FA) {
            $errorMessage = 'Sesi verifikasi telah kedaluwarsa. Silakan masukkan kredensial login kembali.';
            $authMode = 'login';
        } else {
            $enteredOtp = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
            
            // Gabungkan jika dikirim via 6 digit terpisah
            if (empty($enteredOtp) && isset($_POST['digit_1'])) {
                $enteredOtp = trim($_POST['digit_1'] . $_POST['digit_2'] . $_POST['digit_3'] . $_POST['digit_4'] . $_POST['digit_5'] . $_POST['digit_6']);
            }

            if (empty($enteredOtp)) {
                $errorMessage = 'Silakan masukkan 6-digit kode OTP verifikasi.';
            } else {
                $verify = verify_2fa_otp($pendingUser['username'], $enteredOtp);

                if ($verify['success']) {
                    $user = $verify['user'];

                    // Inisialisasi sesi login resmi ANTAM
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['user_id']      = $user['user_id'];
                    $_SESSION['user_name']    = $user['name'];
                    $_SESSION['user_role']    = $user['role'];
                    $_SESSION['user_type']    = $user['type'];
                    $_SESSION['user_dept']    = $user['dept'];
                    $_SESSION['avatar_color'] = $user['avatar_color'];
                    $_SESSION['avatar_image'] = $user['avatar_image'] ?? null;

                    // Hapus sesi 2FA sementara
                    unset($_SESSION['pending_2fa']);

                    header("Location: app.php?page=home");
                    exit();
                } else {
                    $errorMessage = $verify['message'];
                }
            }
        }
    }

    // ----------------------------------------------------
    // 3. KIRIM ULANG KODE OTP (RESEND OTP)
    // ----------------------------------------------------
    elseif ($action === 'resend_otp') {
        if (!$isWaiting2FA) {
            $errorMessage = 'Sesi verifikasi telah kedaluwarsa. Silakan login kembali.';
        } else {
            $u = get_user_by_username($pendingUser['username']);
            if ($u) {
                $otpRes = generate_and_send_2fa_otp($u);
                $pendingUser = $_SESSION['pending_2fa'];
                $successMessage = 'Kode OTP baru telah berhasil dibuat dan dikirimkan ke email terdaftar Anda.';
            } else {
                $errorMessage = 'Pengguna tidak ditemukan. Silakan login kembali.';
            }
        }
    }

    // ----------------------------------------------------
    // 4. BUAT AKUN BARU (REGISTRASI USER / BIRO)
    // ----------------------------------------------------
    elseif ($action === 'register') {
        $regPassword = isset($_POST['password']) ? trim($_POST['password']) : '';
        $regConfirm  = isset($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
        $portal      = isset($_POST['portal']) ? trim($_POST['portal']) : 'pegawai';

        // Pastikan peran/portal type mengikuti portal yang aktif di atas
        $_POST['portal_type'] = ($portal === 'admin') ? 'Admin' : 'User';

        $_POST['avatar_image'] = null;

        if ($regPassword !== $regConfirm) {
            $errorMessage = 'Konfirmasi kata sandi tidak cocok. Pastikan kedua kolom kata sandi sama.';
            $authMode = 'register';
        } else {
            $regRes = register_new_user($_POST);

            if ($regRes['success']) {
                $successMessage  = 'Pendaftaran akun berhasil! Silakan masuk dengan kredensial baru Anda.';
                $authMode        = 'login';
                $prefillUsername = $regRes['username'];
                $prefillRole     = $regRes['portal'];
            } else {
                $errorMessage = $regRes['message'];
                $authMode     = 'register';
            }
        }
    }
}

// Mask email jika sedang dalam mode 2FA
$maskedEmail = ($isWaiting2FA && !empty($pendingUser['email'])) ? mask_email($pendingUser['email']) : 'email terdaftar Anda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Learning — Portal Pembelajaran PT ANTAM Tbk</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <link rel="icon" type="image/png" href="assets/img/logo-antam-icon.png">
</head>
<body class="login-body">

    <!-- Ambient background glows -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>
    <div class="ambient-glow glow-3"></div>

    <div class="login-wrapper">
        <div class="login-header-block">
            <!-- Logo Resmi PT ANTAM Tbk & Smart Learning Bersanding (Presisi & Rapi) -->
            <div class="login-logo-hero-container">
                <div class="hero-logo-halo"></div>
                <div class="hero-logos-pair">
                    <img src="assets/img/logo-antam.png" alt="Logo PT ANTAM Tbk" class="hero-dual-logo hero-logo-antam">
                    <div class="hero-logos-divider"></div>
                    <img src="assets/img/logo.png" alt="Smart Learning Logo PT ANTAM" class="hero-dual-logo hero-logo-smart">
                </div>
            </div>
            
            <h1 class="login-main-title">
                Smart <span class="text-gradient-teal">Learning Portal</span>
            </h1>
            <p class="login-main-subtitle">
                Sistem Terpadu Pembelajaran Mandiri, Evaluasi Kepatuhan K3 ESDM & Sertifikasi Kompetensi Site Pomalaa
            </p>
        </div>

        <?php if (!$isWaiting2FA): ?>
        <!-- Role Switcher Tabs (Pegawai HC vs Admin Biro) -->
        <div class="portal-tabs">
            <button type="button" class="portal-tab <?php echo $prefillRole !== 'admin' ? 'active' : ''; ?>" id="tab-pegawai" onclick="switchPortalTab('pegawai')">
                <i class="ph-fill ph-users"></i>
                <span>Portal Pegawai (HC)</span>
                <span class="tab-chip">Human Capital</span>
            </button>
            <button type="button" class="portal-tab <?php echo $prefillRole === 'admin' ? 'active' : ''; ?>" id="tab-admin" onclick="switchPortalTab('admin')">
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
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
            <div class="login-alert alert-success" id="login-success-alert">
                <i class="ph-fill ph-check-circle"></i>
                <span><?php echo htmlspecialchars($successMessage); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($isWaiting2FA): ?>
            <!-- ======================================================== -->
            <!-- STEP 2: VERIFIKASI KODE OTP                              -->
            <!-- ======================================================== -->
            <div id="section-2fa">
                <div class="auth-form-header">
                    <div class="portal-icon-circle" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">
                        <i class="ph-fill ph-shield-check"></i>
                    </div>
                    <div>
                        <h2 class="auth-box-title">Verifikasi Kode OTP</h2>
                        <p class="auth-box-subtitle">Masukkan 6-digit kode verifikasi akun Anda</p>
                    </div>
                </div>

                <!-- Account Identity Badge -->
                <div class="auth-2fa-badge-card">
                    <div class="auth-2fa-avatar" style="background: <?php echo htmlspecialchars($pendingUser['avatar_color'] ?? '#0f766e'); ?>; overflow: hidden; padding: 0;">
                        <?php if (!empty($pendingUser['avatar_image']) && file_exists(__DIR__ . '/uploads/avatars/' . $pendingUser['avatar_image'])): ?>
                            <img src="uploads/avatars/<?php echo htmlspecialchars($pendingUser['avatar_image']); ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%;">
                        <?php else: ?>
                            <?php echo htmlspecialchars($pendingUser['user_id'] ?? 'AT'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="auth-2fa-user-info">
                        <div class="auth-2fa-user-name"><?php echo htmlspecialchars($pendingUser['name'] ?? 'Pengguna ANTAM'); ?></div>
                        <div class="auth-2fa-user-meta">
                            <span><i class="ph-fill ph-identification-card"></i> <?php echo htmlspecialchars($pendingUser['username']); ?></span>
                            <span>•</span>
                            <span class="auth-2fa-role-tag">
                                <?php echo ($pendingUser['type'] === 'Admin') ? 'Admin (Biro)' : 'Pegawai (HC)'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Email Notice -->
                <div class="auth-2fa-email-notice">
                    <p>Kode telah dikirim ke:</p>
                    <div class="auth-masked-email-pill">
                        <i class="ph-fill ph-envelope-simple"></i>
                        <span><?php echo htmlspecialchars($maskedEmail); ?></span>
                    </div>
                </div>

                <!-- Mode Uji Coba / Localhost Helper Banner -->
                <?php if (isset($pendingUser['otp'])): ?>
                <div class="otp-simulation-card" id="otp-dev-card">
                    <div class="flex items-center gap-2">
                        <i class="ph-fill ph-info text-amber-500" style="font-size: 1.25rem;"></i>
                        <div>
                            <strong>Mode Pengujian (Localhost):</strong>
                            <div>Kode OTP: <span style="font-family: var(--font-mono); font-weight: 800; font-size: 0.95rem; letter-spacing: 2px; color: #b45309;"><?php echo htmlspecialchars($pendingUser['otp']); ?></span></div>
                        </div>
                    </div>
                    <button type="button" class="otp-sim-fill-btn" onclick="autoFillOtp('<?php echo htmlspecialchars($pendingUser['otp']); ?>')">
                        <i class="ph-bold ph-lightning"></i>
                        <span>Isi Otomatis</span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- OTP 6-Boxes Form -->
                <form method="POST" action="index.php" id="form-2fa-verify">
                    <input type="hidden" name="action" value="verify_2fa">
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
                        <span>Verifikasi & Masuk ke Menu Home</span>
                        <i class="ph-bold ph-arrow-right"></i>
                    </button>
                </form>

                <!-- Resend & Cancel Controls -->
                <div class="otp-actions-bar">
                    <form method="POST" action="index.php" id="form-resend-otp" style="margin: 0;">
                        <input type="hidden" name="action" value="resend_otp">
                        <button type="submit" class="btn-resend-otp" id="btn-resend" disabled>
                            <i class="ph-bold ph-arrows-clockwise"></i>
                            <span id="resend-text">Kirim Ulang Kode (<span id="resend-timer">60</span>s)</span>
                        </button>
                    </form>

                    <a href="index.php?action=cancel_2fa" class="btn-cancel-auth">
                        <i class="ph-bold ph-arrow-counter-clockwise"></i>
                        <span>Batal & Ganti Akun</span>
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- ======================================================== -->
            <!-- STEP 1: PILIHAN MASUK AKUN ATAU BUAT AKUN BARU           -->
            <!-- ======================================================== -->

            <!-- Mode Selector Nav -->
            <div class="auth-mode-nav">
                <button type="button" class="auth-mode-btn <?php echo $authMode !== 'register' ? 'active' : ''; ?>" id="btn-mode-login" onclick="switchAuthMode('login')">
                    <i class="ph-fill ph-sign-in"></i>
                    <span>Masuk Akun</span>
                </button>
                <button type="button" class="auth-mode-btn <?php echo $authMode === 'register' ? 'active' : ''; ?>" id="btn-mode-register" onclick="switchAuthMode('register')">
                    <i class="ph-fill ph-user-plus"></i>
                    <span>Buat Akun Baru</span>
                </button>
            </div>

            <!-- PANEL 1: LOGIN FORM -->
            <div id="panel-login" style="<?php echo $authMode === 'register' ? 'display: none;' : ''; ?>">
                <form method="POST" action="index.php" id="login-form" class="auth-form-body">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="auth_mode" value="login">
                    <input type="hidden" name="portal" id="input-portal" value="<?php echo htmlspecialchars($prefillRole); ?>">
                    
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
                                   value="<?php echo htmlspecialchars($prefillUsername); ?>" required autocomplete="username">
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
            <div id="panel-register" style="<?php echo $authMode !== 'register' ? 'display: none;' : ''; ?>">
                <form method="POST" action="index.php" id="register-form" class="auth-form-body">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="auth_mode" value="register">
                    <input type="hidden" name="portal" id="register-portal-input" value="<?php echo htmlspecialchars($prefillRole); ?>">
                    <input type="hidden" name="portal_type" id="register-portal-type" value="<?php echo $prefillRole === 'admin' ? 'Admin' : 'User'; ?>">

                    <div class="auth-form-header">
                        <div class="portal-icon-circle" id="reg-icon-display" style="background: rgba(15, 118, 110, 0.15); color: #0f766e;">
                            <i class="ph-fill ph-user-plus" id="reg-icon-elem"></i>
                        </div>
                        <div>
                            <h2 class="auth-box-title" id="reg-box-title">
                                <?php echo $prefillRole === 'admin' ? 'Registrasi Admin (Biro)' : 'Registrasi Pegawai (HC)'; ?>
                            </h2>
                            <p class="auth-box-subtitle" id="reg-box-subtitle">
                                <?php echo $prefillRole === 'admin' ? 'Daftarkan akun konsol Biro Human Capital & Diklat' : 'Daftarkan akun pegawai Human Capital & operasional'; ?>
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
                                <i class="ph-fill ph-envelope"></i> ALAMAT EMAIL *
                            </label>
                            <input type="email" name="email" id="reg-email" class="form-input-auth" placeholder="cth. fajar.pratama@antam.com" required>
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
                <span>ISO 45001 & ESDM No. 1827/2018</span>
            </div>
            <div class="footer-separator">•</div>
            <div class="footer-item">
                <i class="ph-fill ph-arrows-left-right"></i>
                <span>Alarm K3 Dua Arah</span>
            </div>
            <div class="footer-separator">•</div>
            <div class="footer-item">
                <i class="ph-fill ph-buildings"></i>
                <span>PT ANTAM Tbk Pomalaa</span>
            </div>
        </div>
    </div>

    <!-- Login & 2FA Interactive Scripts -->
    <script>
        // ----------------------------------------------------
        // SWITCH PORTAL (Pegawai HC vs Admin Biro)
        // ----------------------------------------------------
        function switchPortalTab(role) {
            const tabPegawai = document.getElementById('tab-pegawai');
            const tabAdmin = document.getElementById('tab-admin');
            const portalInput = document.getElementById('input-portal');
            const regPortalInput = document.getElementById('register-portal-input');
            const regPortalType = document.getElementById('register-portal-type');
            const chipsPegawai = document.getElementById('demo-chips-pegawai');
            const chipsAdmin = document.getElementById('demo-chips-admin');
            
            // Login Titles
            const boxTitle = document.getElementById('auth-box-title');
            const boxSub = document.getElementById('auth-box-subtitle');
            const iconElem = document.getElementById('portal-icon-elem');
            const iconDisplay = document.getElementById('portal-icon-display');

            // Register Titles
            const regBoxTitle = document.getElementById('reg-box-title');
            const regBoxSub = document.getElementById('reg-box-subtitle');
            const regIconDisplay = document.getElementById('reg-icon-display');

            if (portalInput) portalInput.value = role;
            if (regPortalInput) regPortalInput.value = role;
            if (regPortalType) regPortalType.value = (role === 'admin') ? 'Admin' : 'User';

            if (role === 'pegawai') {
                if (tabPegawai) tabPegawai.classList.add('active');
                if (tabAdmin) tabAdmin.classList.remove('active');
                if (chipsPegawai) chipsPegawai.style.display = 'flex';
                if (chipsAdmin) chipsAdmin.style.display = 'none';

                // Login texts
                if (boxTitle) boxTitle.textContent = 'Login Pegawai (HC)';
                if (boxSub) boxSub.textContent = 'Gunakan username atau email resmi ANTAM Anda';
                if (iconElem) iconElem.className = 'ph-fill ph-user';
                if (iconDisplay) {
                    iconDisplay.style.background = 'rgba(212, 175, 55, 0.15)';
                    iconDisplay.style.color = '#d4af37';
                }

                // Register texts (otomatis sesuai HC)
                if (regBoxTitle) regBoxTitle.textContent = 'Registrasi Pegawai (HC)';
                if (regBoxSub) regBoxSub.textContent = 'Daftarkan akun pegawai Human Capital & operasional';
                if (regIconDisplay) {
                    regIconDisplay.style.background = 'rgba(212, 175, 55, 0.15)';
                    regIconDisplay.style.color = '#d4af37';
                }
            } else {
                if (tabAdmin) tabAdmin.classList.add('active');
                if (tabPegawai) tabPegawai.classList.remove('active');
                if (chipsPegawai) chipsPegawai.style.display = 'none';
                if (chipsAdmin) chipsAdmin.style.display = 'flex';

                // Login texts
                if (boxTitle) boxTitle.textContent = 'Login Admin (Biro)';
                if (boxSub) boxSub.textContent = 'Konsol Biro Human Capital, L&D & K3 Management';
                if (iconElem) iconElem.className = 'ph-fill ph-shield-star';
                if (iconDisplay) {
                    iconDisplay.style.background = 'rgba(16, 185, 129, 0.15)';
                    iconDisplay.style.color = '#10b981';
                }

                // Register texts (otomatis sesuai Biro)
                if (regBoxTitle) regBoxTitle.textContent = 'Registrasi Admin (Biro)';
                if (regBoxSub) regBoxSub.textContent = 'Daftarkan akun konsol Biro Human Capital & Diklat';
                if (regIconDisplay) {
                    regIconDisplay.style.background = 'rgba(16, 185, 129, 0.15)';
                    regIconDisplay.style.color = '#10b981';
                }
            }
        }

        // ----------------------------------------------------
        // SWITCH MODE (Masuk vs Buat Akun)
        // ----------------------------------------------------
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

        // ----------------------------------------------------
        // DEMO CREDENTIALS QUICK FILL
        // ----------------------------------------------------
        function fillCredentials(username, password, role) {
            const uInput = document.getElementById('username-input');
            const pInput = document.getElementById('password-input');
            if (uInput) uInput.value = username;
            if (pInput) pInput.value = password;
            switchPortalTab(role);
            
            if (uInput && pInput) {
                uInput.style.borderColor = '#0f766e';
                pInput.style.borderColor = '#0f766e';
                setTimeout(() => {
                    uInput.style.borderColor = '';
                    pInput.style.borderColor = '';
                }, 800);
            }
        }

        // ----------------------------------------------------
        // TOGGLE PASSWORD VISIBILITY
        // ----------------------------------------------------
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

        // ----------------------------------------------------
        // 2FA OTP 6-BOX INTERACTIVITY & AUTO-JUMP
        // ----------------------------------------------------
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

                    // Handle input event
                    input.addEventListener('input', (e) => {
                        const val = input.value;
                        if (val.length > 0) {
                            input.value = val.charAt(val.length - 1); // Keep last char
                            input.classList.add('filled');
                            // Focus next
                            if (index < otpInputs.length - 1) {
                                otpInputs[index + 1].focus();
                            }
                        } else {
                            input.classList.remove('filled');
                        }
                        updateFullOtp();
                    });

                    // Handle keydown event (Backspace & Navigation)
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && input.value === '' && index > 0) {
                            otpInputs[index - 1].focus();
                        } else if (e.key === 'ArrowLeft' && index > 0) {
                            otpInputs[index - 1].focus();
                        } else if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    });

                    // Handle paste event (Ctrl+V entire 6-digit code)
                    input.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                        if (pasteData.length >= 6) {
                            const digits = pasteData.replace(/\D/g, '').slice(0, 6);
                            digits.split('').forEach((d, i) => {
                                if (otpInputs[i]) {
                                    otpInputs[i].value = d;
                                    otpInputs[i].classList.add('filled');
                                }
                            });
                            updateFullOtp();
                            if (otpInputs[5]) otpInputs[5].focus();
                        }
                    });
                });

                function updateFullOtp() {
                    let code = '';
                    otpInputs.forEach(inp => {
                        if (inp) code += inp.value;
                    });
                    if (fullOtpInput) fullOtpInput.value = code;

                    // Auto-submit if all 6 digits are filled
                    if (code.length === 6 && form2FA) {
                        form2FA.submit();
                    }
                }

                // Handle submit gathering
                if (form2FA) {
                    form2FA.addEventListener('submit', (e) => {
                        updateFullOtp();
                        if (fullOtpInput && fullOtpInput.value.length < 6) {
                            e.preventDefault();
                            alert('Silakan masukkan 6 digit kode OTP secara lengkap.');
                        }
                    });
                }

                // Resend Countdown Timer
                let countdown = 60;
                const timerElem = document.getElementById('resend-timer');
                const btnResend = document.getElementById('btn-resend');
                const resendText = document.getElementById('resend-text');

                if (btnResend && timerElem) {
                    const timerInterval = setInterval(() => {
                        countdown--;
                        timerElem.textContent = countdown;
                        if (countdown <= 0) {
                            clearInterval(timerInterval);
                            btnResend.disabled = false;
                            resendText.textContent = 'Kirim Ulang Kode OTP';
                        }
                    }, 1000);
                }
            }
        });

        // Auto-fill from dev helper
        function autoFillOtp(code) {
            const digits = code.toString().split('');
            digits.forEach((d, i) => {
                const inp = document.getElementById('otp-' + (i + 1));
                if (inp) {
                    inp.value = d;
                    inp.classList.add('filled');
                }
            });
            const fullOtpInput = document.getElementById('input-full-otp');
            if (fullOtpInput) fullOtpInput.value = code;
            
            // Auto submit
            const form2FA = document.getElementById('form-2fa-verify');
            if (form2FA) {
                form2FA.submit();
            }
        }


    </script>
</body>
</html>
