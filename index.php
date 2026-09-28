<?php
session_start();
require_once __DIR__ . '/includes/data.php';

// Redirect if already logged in
if (isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    header("Location: app.php?page=dashboard");
    exit();
}

$errorMessage = '';
$successMessage = '';
$prefillRole = isset($_GET['role']) ? $_GET['role'] : 'pegawai';

// Process Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $portal = isset($_POST['portal']) ? trim($_POST['portal']) : 'pegawai';
    
    if (empty($username) || empty($password)) {
        $errorMessage = 'Silakan masukkan username dan kata sandi Anda.';
    } else {
        $auth = authenticate_user($username, $password);
        
        if ($auth['success']) {
            $user = $auth['user'];
            
            // Check portal type match
            if ($portal === 'admin' && $user['type'] !== 'Admin') {
                $errorMessage = 'Akun ini bukan administrator. Silakan masuk melalui tab Pegawai.';
            } else {
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_type'] = $user['type'];
                $_SESSION['user_dept'] = $user['dept'];
                $_SESSION['avatar_color'] = $user['avatar_color'];
                
                header("Location: app.php?page=dashboard");
                exit();
            }
        } else {
            $errorMessage = $auth['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Learning Windows — Login Portal PT ANTAM</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

    <!-- Ambient background glows -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>
    <div class="ambient-glow glow-3"></div>

    <div class="login-wrapper">
        <!-- Top Status Bar -->
        <div class="login-top-badge">
            <span class="live-dot"></span>
            <span>PT ANTAM Tbk · Smart Learning System Site Pongkor</span>
        </div>

        <div class="login-header-block">
            <div class="brand-pill">
                <i class="ph-fill ph-shield-check"></i>
                PORTAL RESMI PEMBELAJARAN & K3
            </div>
            <h1 class="login-main-title">
                Smart <span class="text-gradient-gold">Learning Portal</span>
            </h1>
            <p class="login-main-subtitle">
                Akses modul pembelajaran mandiri, evaluasi kepatuhan K3 ESDM, dan verifikasi sertifikasi kerja site tambang bawah tanah.
            </p>
        </div>

        <!-- Role Switcher Tabs -->
        <div class="portal-tabs">
            <button type="button" class="portal-tab <?php echo $prefillRole !== 'admin' ? 'active' : ''; ?>" id="tab-pegawai" onclick="switchPortalTab('pegawai')">
                <i class="ph-fill ph-users"></i>
                <span>Portal Pegawai</span>
                <span class="tab-chip">Operasional</span>
            </button>
            <button type="button" class="portal-tab <?php echo $prefillRole === 'admin' ? 'active' : ''; ?>" id="tab-admin" onclick="switchPortalTab('admin')">
                <i class="ph-fill ph-shield-star"></i>
                <span>Learning Ops Admin</span>
                <span class="tab-chip chip-gold">Console</span>
            </button>
        </div>

        <!-- Authentication Card Container -->
        <div class="login-card-container glass-panel">
            
            <?php if (!empty($errorMessage)): ?>
            <div class="login-alert alert-danger" id="login-error-alert">
                <i class="ph-fill ph-warning-circle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php" id="login-form" class="auth-form-body">
                <input type="hidden" name="portal" id="input-portal" value="<?php echo htmlspecialchars($prefillRole); ?>">
                
                <div class="auth-form-header">
                    <div class="portal-icon-circle" id="portal-icon-display">
                        <i class="ph-fill ph-user" id="portal-icon-elem"></i>
                    </div>
                    <div>
                        <h2 class="auth-box-title" id="auth-box-title">Login Akun Pegawai</h2>
                        <p class="auth-box-subtitle" id="auth-box-subtitle">Gunakan username dan password terdaftar Anda</p>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" for="username-input">
                        <i class="ph-fill ph-user-circle"></i> USERNAME
                    </label>
                    <div class="input-with-icon">
                        <input type="text" name="username" id="username-input" class="form-input-auth" placeholder="Masukkan username (cth. egy / budi / andi)" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label class="form-label" for="password-input">
                            <i class="ph-fill ph-lock-key"></i> KATA SANDI / PASSWORD
                        </label>
                        <span class="text-xs text-muted">Protokol Keamanan ANTAM</span>
                    </div>
                    <div class="input-with-icon password-input-wrap">
                        <input type="password" name="password" id="password-input" class="form-input-auth" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility()" title="Tampilkan/Sembunyikan Kata Sandi">
                            <i class="ph-bold ph-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit" id="btn-submit-login">
                    <i class="ph-bold ph-sign-in"></i>
                    <span>Masuk ke Dashboard</span>
                    <i class="ph-bold ph-arrow-right"></i>
                </button>

                <!-- Quick-Fill Demo Accounts Helper -->
                <div class="demo-accounts-helper">
                    <div class="demo-helper-title">
                        <i class="ph-fill ph-key"></i>
                        <span>Akun Demo Cepat (Klik untuk mengisi):</span>
                    </div>
                    
                    <div id="demo-chips-pegawai" class="demo-chips-row">
                        <button type="button" class="demo-chip-btn" onclick="fillCredentials('egy', 'password123', 'pegawai')">
                            <span class="demo-chip-av" style="background: #d4af37;">EP</span>
                            <div class="demo-chip-info">
                                <strong>egy</strong>
                                <span>Operasional Tambang</span>
                            </div>
                        </button>
                        
                        <button type="button" class="demo-chip-btn" onclick="fillCredentials('budi', 'password123', 'pegawai')">
                            <span class="demo-chip-av" style="background: #38bdf8;">BS</span>
                            <div class="demo-chip-info">
                                <strong>budi</strong>
                                <span>Processing Plant</span>
                            </div>
                        </button>

                        <button type="button" class="demo-chip-btn" onclick="fillCredentials('andi', 'password123', 'pegawai')">
                            <span class="demo-chip-av" style="background: #a855f7;">AW</span>
                            <div class="demo-chip-info">
                                <strong>andi</strong>
                                <span>Logistik Handak</span>
                            </div>
                        </button>
                    </div>

                    <div id="demo-chips-admin" class="demo-chips-row" style="display: none;">
                        <button type="button" class="demo-chip-btn admin-demo-chip" onclick="fillCredentials('admin', 'admin123', 'admin')">
                            <span class="demo-chip-av" style="background: #10b981;">LO</span>
                            <div class="demo-chip-info">
                                <strong>admin</strong>
                                <span>Learning Ops Admin (Level 4)</span>
                            </div>
                        </button>
                    </div>
                </div>

            </form>
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
                <span>UBPN Pongkor, Bogor</span>
            </div>
        </div>
    </div>

    <!-- Login Interactivity Script -->
    <script>
        function switchPortalTab(role) {
            const tabPegawai = document.getElementById('tab-pegawai');
            const tabAdmin = document.getElementById('tab-admin');
            const portalInput = document.getElementById('input-portal');
            const chipsPegawai = document.getElementById('demo-chips-pegawai');
            const chipsAdmin = document.getElementById('demo-chips-admin');
            const boxTitle = document.getElementById('auth-box-title');
            const boxSub = document.getElementById('auth-box-subtitle');
            const iconElem = document.getElementById('portal-icon-elem');
            const iconDisplay = document.getElementById('portal-icon-display');

            portalInput.value = role;

            if (role === 'pegawai') {
                tabPegawai.classList.add('active');
                tabAdmin.classList.remove('active');
                chipsPegawai.style.display = 'flex';
                chipsAdmin.style.display = 'none';
                boxTitle.textContent = 'Login Akun Pegawai';
                boxSub.textContent = 'Masukkan username dan password pegawai Anda';
                iconElem.className = 'ph-fill ph-user';
                iconDisplay.style.background = 'rgba(212, 175, 55, 0.15)';
                iconDisplay.style.color = '#d4af37';
            } else {
                tabAdmin.classList.add('active');
                tabPegawai.classList.remove('active');
                chipsPegawai.style.display = 'none';
                chipsAdmin.style.display = 'flex';
                boxTitle.textContent = 'Login Admin Console';
                boxSub.textContent = 'Khusus tim Learning & Development / Human Capital';
                iconElem.className = 'ph-fill ph-shield-star';
                iconDisplay.style.background = 'rgba(16, 185, 129, 0.15)';
                iconDisplay.style.color = '#10b981';
            }
        }

        function fillCredentials(username, password, role) {
            document.getElementById('username-input').value = username;
            document.getElementById('password-input').value = password;
            switchPortalTab(role);
            
            // Highlight inputs briefly
            const uInput = document.getElementById('username-input');
            const pInput = document.getElementById('password-input');
            uInput.style.borderColor = '#d4af37';
            pInput.style.borderColor = '#d4af37';
            setTimeout(() => {
                uInput.style.borderColor = '';
                pInput.style.borderColor = '';
            }, 800);
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('password-input');
            const eyeIcon = document.getElementById('eye-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.className = 'ph-bold ph-eye-slash';
            } else {
                passInput.type = 'password';
                eyeIcon.className = 'ph-bold ph-eye';
            }
        }

        // Initialize state based on current selection
        document.addEventListener('DOMContentLoaded', () => {
            const currentPortal = "<?php echo $prefillRole; ?>";
            switchPortalTab(currentPortal === 'admin' ? 'admin' : 'pegawai');
        });
    </script>
</body>
</html>
