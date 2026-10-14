<?php
/**
 * Data Access Layer — MySQL Backend
 * Smart Learning — PT ANTAM Tbk
 *
 * Semua interaksi database terpusat di file ini.
 * Interface publik fungsi tidak berubah agar api.php & pages tidak perlu dimodifikasi.
 */

require_once __DIR__ . '/db.php';

// ==========================================================
// HELPER INTERNAL
// ==========================================================

/**
 * Format tanggal Indonesia secara real-time.
 */
function get_realtime_indonesian_date(string $format = 'day_short', ?int $timestamp = null): string {
    if ($timestamp === null) $timestamp = time();
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = ['','Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $monthsLong = ['','Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $w = (int)date('w', $timestamp);
    $d = date('d', $timestamp);
    $m = (int)date('m', $timestamp);
    $y = date('Y', $timestamp);
    $time = date('H:i:s', $timestamp);

    if ($format === 'short') {
        return "{$d} {$months[$m]} {$y}";
    } elseif ($format === 'time') {
        return "{$d} {$months[$m]} {$y} · {$time}";
    } elseif ($format === 'full_time') {
        return "{$days[$w]}, {$d} {$months[$m]} {$y} · {$time} WITA";
    } elseif ($format === 'long') {
        return "{$days[$w]}, {$d} {$monthsLong[$m]} {$y}";
    } else {
        return "{$days[$w]}, {$d} {$months[$m]} {$y}";
    }
}

/**
 * Ubah mysqli_result row menjadi array asosiatif bersih.
 * Konversi tipe: TINYINT(1) -> bool, angka -> int/float.
 */
function _row_to_array(array $row): array {
    // Kolom yang harus diperlakukan sebagai boolean
    static $boolCols = ['wa_notif'];
    // Kolom yang harus diperlakukan sebagai integer
    static $intCols  = ['progress','total_modules','completed_modules','grade','score','file_size'];

    foreach ($row as $k => &$v) {
        if ($v === null) continue;
        if (in_array($k, $boolCols, true)) { $v = (bool)(int)$v; continue; }
        if (in_array($k, $intCols, true))  { $v = (int)$v; continue; }
    }
    unset($v);

    // Otomatis sesuaikan jika tabel di hosting menggunakan skema (id, username, password, nama, unit_kerja, role)
    if (isset($row['username'])) {
        $uLower = strtolower($row['username']);
        if (!isset($row['name']) || $row['name'] === '') {
            $row['name'] = $row['nama'] ?? ucfirst($row['username']);
        }
        if (!isset($row['dept']) || $row['dept'] === '') {
            $row['dept'] = $row['unit_kerja'] ?? 'PT ANTAM';
        }
        if (!isset($row['type']) || empty($row['type'])) {
            $roleLower = strtolower($row['role'] ?? '');
            $row['type'] = ($roleLower === 'admin') ? 'Admin' : 'User';
        }
        if (!isset($row['user_id']) || empty($row['user_id'])) {
            $nameParts = explode(' ', trim($row['name']));
            if (count($nameParts) > 1 && !empty($nameParts[0]) && !empty($nameParts[1])) {
                $row['user_id'] = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
            } else {
                $row['user_id'] = strtoupper(substr($row['username'], 0, 2));
            }
        }
        if (!isset($row['avatar_color']) || empty($row['avatar_color'])) {
            $colors = ['admin' => '#10b981', 'egy' => '#d4af37', 'budi' => '#38bdf8', 'andi' => '#a855f7'];
            $row['avatar_color'] = $colors[$uLower] ?? '#0f766e';
        }
        if (!isset($row['avatar_image'])) {
            $row['avatar_image'] = null;
        }
        if (!isset($row['nrp']) || $row['nrp'] === null) {
            $nrps = ['egy' => 'ANTAM-08912', 'budi' => 'ANTAM-07844', 'andi' => 'ANTAM-06519', 'admin' => 'ANTAM-HC001'];
            $row['nrp'] = $nrps[$uLower] ?? '';
        }
        if (!isset($row['email']) || $row['email'] === null) {
            $emails = ['egy' => 'egy.pratama@antam.com', 'budi' => 'budi.santoso@antam.com', 'andi' => 'andi.wijaya@antam.com', 'admin' => 'admin.lnd@antam.com'];
            $row['email'] = $emails[$uLower] ?? '';
        }
        if (!isset($row['phone']) || $row['phone'] === null) {
            $phones = ['egy' => '0812-3456-7890', 'budi' => '0813-9876-5432', 'andi' => '0811-2233-4455', 'admin' => '0811-9988-7766'];
            $row['phone'] = $phones[$uLower] ?? '';
        }
        if (!isset($row['bio']) || $row['bio'] === null) {
            $row['bio'] = '';
        }
        if (!isset($row['reminder_freq']) || empty($row['reminder_freq'])) {
            $row['reminder_freq'] = ($row['type'] === 'Admin') ? 'Realtime' : '24 Jam';
        }
        if (!isset($row['wa_notif'])) {
            $row['wa_notif'] = true;
        }
        if (empty($row['role']) || $row['role'] === 'user') {
            $roles = [
                'egy' => 'Operasional Tambang',
                'budi' => 'Processing Plant',
                'andi' => 'Logistik & Gudang',
                'admin' => 'Learning & Development'
            ];
            $row['role'] = $roles[$uLower] ?? ($row['dept'] ?? 'Operasional Tambang');
        }
    }

    return $row;
}

if (!function_exists('_parse_indonesian_date_to_iso')) {
function _parse_indonesian_date_to_iso($str) {
    if (empty($str)) return null;
    $str = trim($str);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $str, $m)) {
        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
    $months = [
        'jan' => '01', 'januari' => '01', 'january' => '01',
        'feb' => '02', 'februari' => '02', 'february' => '02',
        'mar' => '03', 'maret' => '03', 'march' => '03',
        'apr' => '04', 'april' => '04',
        'mei' => '05', 'may' => '05',
        'jun' => '06', 'juni' => '06', 'june' => '06',
        'jul' => '07', 'juli' => '07', 'july' => '07',
        'agu' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08',
        'sep' => '09', 'september' => '09',
        'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10',
        'nov' => '11', 'november' => '11',
        'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
    ];
    if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/', $str, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mLower = strtolower($m[2]);
        $mon = $months[$mLower] ?? null;
        if ($mon) return "{$m[3]}-{$mon}-{$day}";
    }
    $ts = strtotime($str);
    if ($ts !== false && $ts > 0) return date('Y-m-d', $ts);
    return null;
}
}

// ==========================================================
// USER AUTHENTICATION & MANAGEMENT
// ==========================================================

function get_all_users(): array {
    $db = get_db();
    if (!$db) return [];
    $res = $db->query("SELECT * FROM `users` ORDER BY `username`");
    if (!$res || !($res instanceof mysqli_result)) return [];
    $out  = [];
    while ($row = $res->fetch_assoc()) {
        $row = _row_to_array($row);
        $out[$row['username']] = $row;
    }
    return $out;
}

function get_user_by_username(string $username): ?array {
    $db = get_db();
    if (!$db) return null;
    $key  = strtolower(trim($username));
    $stmt = $db->prepare("SELECT * FROM `users` WHERE `username` = ?");
    if (!$stmt) return null;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res  = $stmt->get_result();
    if (!$res || !($res instanceof mysqli_result)) {
        $stmt->close();
        return null;
    }
    $row  = $res->fetch_assoc();
    $stmt->close();
    return $row ? _row_to_array($row) : null;
}

function get_user_by_identifier(string $identifier): ?array {
    $db = get_db();
    if (!$db) return null;
    $key  = strtolower(trim($identifier));
    $stmt = $db->prepare("SELECT * FROM `users` WHERE `username` = ? OR LOWER(`email`) = ?");
    if (!$stmt) return null;
    $stmt->bind_param('ss', $key, $key);
    $stmt->execute();
    $res  = $stmt->get_result();
    if (!$res || !($res instanceof mysqli_result)) {
        $stmt->close();
        return null;
    }
    $row  = $res->fetch_assoc();
    $stmt->close();
    return $row ? _row_to_array($row) : null;
}

function authenticate_user(string $identifier, string $password): array {
    $user = get_user_by_identifier($identifier);

    if (!$user) {
        return ['success' => false, 'message' => 'Username atau email tidak ditemukan. Periksa kembali kredensial Anda.'];
    }

    $stored = $user['password'] ?? '';
    $isMatch = false;

    // 1. Kecocokan langsung (Plaintext: admin123, password123)
    if ($stored === $password) {
        $isMatch = true;
    }
    // 2. MD5 hash match (contoh: md5('admin123') = '0192023a7bbd73250516f069df18b500')
    elseif (strtolower($stored) === md5($password)) {
        $isMatch = true;
    }
    // 3. Khusus hash di InfinityFree: '6ad14ba9986e3615423dfca256d04e3f' (MD5 dari 'user123'),
    //    izinkan jika user mengetik 'password123' atau 'user123'
    elseif (strtolower($stored) === '6ad14ba9986e3615423dfca256d04e3f' && in_array($password, ['password123', 'user123'])) {
        $isMatch = true;
    }
    // 4. PHP standard password_hash (bcrypt)
    elseif (password_verify($password, $stored)) {
        $isMatch = true;
    }

    if (!$isMatch) {
        return ['success' => false, 'message' => 'Kata sandi salah. Silakan coba lagi.'];
    }

    return ['success' => true, 'user' => $user];
}

/**
 * Sensor alamat email untuk tampilan keamanan yang rapi (cth: eg*******ma@antam.com)
 */
function mask_email(string $email): string {
    $email = trim($email);
    if (empty($email) || strpos($email, '@') === false) {
        return 'email terdaftar Anda';
    }
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = substr($user, 0, 1) . '***';
    } else {
        $maskedUser = substr($user, 0, 2) . str_repeat('*', max(3, $len - 4)) . substr($user, -2);
    }
    return $maskedUser . '@' . $domain;
}

/**
 * Kirim email resmi PT ANTAM Tbk berisi kode OTP verifikasi 2FA
 */
function send_2fa_email(string $toEmail, string $name, string $otpCode, string $roleTitle): bool {
    if (empty($toEmail)) return false;

    $subject = "Kode OTP Keamanan (2FA): " . $otpCode . " — Smart Learning PT ANTAM Tbk";
    
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Smart Learning ANTAM <security@antam.com>\r\n";
    $headers .= "Reply-To: Human Capital & Biro L&D <lnd-support@antam.com>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $message = "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
      <meta charset='utf-8'>
      <title>Kode OTP Smart Learning ANTAM</title>
      <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 24px; color: #1e293b; }
        .email-card { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #0f766e, #0d9488); padding: 32px 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .header p { margin: 8px 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 32px 28px; }
        .badge { display: inline-block; background: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 5px 14px; border-radius: 99px; margin-bottom: 16px; letter-spacing: 1px; }
        .greeting { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
        .desc { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
        .otp-box { background: #f8fafc; border: 2px dashed #0f766e; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0; }
        .otp-label { font-size: 12px; font-weight: 700; color: #64748b; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
        .otp-code { font-family: 'Consolas', 'Courier New', monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #0f766e; margin: 0; }
        .otp-timer { font-size: 12px; color: #64748b; margin-top: 10px; font-weight: 500; }
        .security-notice { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 14px 18px; font-size: 12px; color: #92400e; line-height: 1.6; margin-top: 24px; }
        .footer { background: #f8fafc; padding: 20px 24px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; }
      </style>
    </head>
    <body>
      <div class='email-card'>
        <div class='header'>
          <h1>PT ANTAM Tbk &bull; Smart Learning</h1>
          <p>Sistem Terintegrasi Pembelajaran & Kepatuhan K3 ESDM Site Pomalaa</p>
        </div>
        <div class='content'>
          <div class='badge'>Autentikasi Dua Faktor (2FA)</div>
          <div class='greeting'>Halo, " . htmlspecialchars($name) . " (" . htmlspecialchars($roleTitle) . ")</div>
          <div class='desc'>
            Permintaan autentikasi masuk ke akun Anda terdeteksi. Silakan gunakan 6-digit kode OTP (One-Time Password) di bawah ini untuk memverifikasi identitas Anda:
          </div>
          
          <div class='otp-box'>
            <div class='otp-label'>KODE VERIFIKASI RESMI</div>
            <div class='otp-code'>" . htmlspecialchars($otpCode) . "</div>
            <div class='otp-timer'>⏱ Masa berlaku kode: 10 Menit</div>
          </div>
          
          <div class='security-notice'>
            <strong>Peringatan Keamanan ANTAM:</strong> Jangan pernah memberikan kode OTP ini kepada siapa pun, termasuk administrator atau pihak yang mengaku dari Human Capital. Sistem ANTAM tidak pernah meminta kode OTP Anda melalui pesan telepon atau chat.
          </div>
        </div>
        <div class='footer'>
          &copy; " . date('Y') . " PT ANTAM Tbk &bull; Human Capital & Corporate University Pomalaa<br>
          Pesan otomatis ini di-generate oleh Sistem Smart Learning Kepatuhan K3 ESDM.
        </div>
      </div>
    </body>
    </html>";

    return @mail($toEmail, $subject, $message, $headers);
}

/**
 * Generate dan kirim kode OTP 2FA untuk user
 */
function generate_and_send_2fa_otp(array $user): array {
    $db = get_db();
    $username = $user['username'];
    $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    if ($db) {
        $stmt = $db->prepare("UPDATE `users` SET `otp_code` = ?, `otp_expires_at` = ? WHERE `username` = ?");
        if ($stmt) {
            $stmt->bind_param('sss', $otpCode, $expiresAt, $username);
            $stmt->execute();
            $stmt->close();
        }
    }

    $email = !empty($user['email']) ? $user['email'] : ($username . '@antam.com');
    $name = !empty($user['name']) ? $user['name'] : ucfirst($username);
    $roleTitle = ($user['type'] === 'Admin') ? 'Biro Human Capital Admin' : 'Pegawai HC Operasional';

    // Kirim email
    $mailSent = send_2fa_email($email, $name, $otpCode, $roleTitle);

    // Simpan ke sesi untuk verifikasi cepat dan fallback
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start();
    }

    $_SESSION['pending_2fa'] = [
        'username'     => $username,
        'otp'          => $otpCode,
        'expires_at'   => $expiresAt,
        'email'        => $email,
        'name'         => $name,
        'type'         => $user['type'] ?? 'User',
        'role'         => $user['role'] ?? '',
        'dept'         => $user['dept'] ?? '',
        'user_id'      => $user['user_id'] ?? '',
        'avatar_color' => $user['avatar_color'] ?? '#0f766e',
        'avatar_image' => $user['avatar_image'] ?? null,
        'created_at'   => time()
    ];

    return [
        'success'      => true,
        'otp'          => $otpCode,
        'email'        => $email,
        'masked_email' => mask_email($email),
        'expires_at'   => $expiresAt,
        'mail_sent'    => $mailSent
    ];
}

/**
 * Verifikasi kode OTP 2FA
 */
function verify_2fa_otp(string $username, string $enteredOtp): array {
    $user = get_user_by_username($username);
    if (!$user) {
        return ['success' => false, 'message' => 'Data pengguna tidak ditemukan. Silakan login kembali.'];
    }

    $storedOtp = $user['otp_code'] ?? null;
    $expiresAt = $user['otp_expires_at'] ?? null;

    // Check session fallback
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start();
    }
    if ((!$storedOtp || empty($storedOtp)) && isset($_SESSION['pending_2fa']['otp'])) {
        $storedOtp = $_SESSION['pending_2fa']['otp'];
        $expiresAt = $_SESSION['pending_2fa']['expires_at'] ?? null;
    }

    if (empty($storedOtp)) {
        return ['success' => false, 'message' => 'Kode OTP belum dibuat atau sesi telah kedaluwarsa. Silakan minta kode baru.'];
    }

    if ($expiresAt && strtotime($expiresAt) < time()) {
        return ['success' => false, 'message' => 'Kode OTP telah kedaluwarsa (masa berlaku 10 menit telah habis). Silakan klik Kirim Ulang Kode.'];
    }

    if (trim($enteredOtp) !== trim((string)$storedOtp)) {
        return ['success' => false, 'message' => 'Kode OTP yang Anda masukkan salah. Periksa kembali kotak masuk email Anda.'];
    }

    // Success! Clear OTP in database
    $db = get_db();
    if ($db) {
        $stmt = $db->prepare("UPDATE `users` SET `otp_code` = NULL, `otp_expires_at` = NULL WHERE `username` = ?");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->close();
        }
    }

    return ['success' => true, 'user' => $user];
}

/**
 * Pendaftaran Akun Pengguna Baru (Pegawai HC atau Biro Admin)
 */
function register_new_user(array $data): array {
    $db = get_db();
    if (!$db) {
        return ['success' => false, 'message' => 'Koneksi ke database gagal.'];
    }

    $name = trim($data['name'] ?? '');
    $username = strtolower(trim($data['username'] ?? ''));
    $email = strtolower(trim($data['email'] ?? ''));
    $password = trim($data['password'] ?? '');
    $nrp = trim($data['nrp'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $dept = trim($data['dept'] ?? 'Operasional Tambang Underground');
    $portalType = trim($data['portal_type'] ?? 'User'); // 'User' (Pegawai HC) or 'Admin' (Biro)
    $role = trim($data['role'] ?? '');

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Nama lengkap, username, email, dan kata sandi wajib diisi.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email tidak valid. Gunakan format email yang benar (contoh: nama@antam.com).'];
    }

    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        return ['success' => false, 'message' => 'Username minimal 3 karakter dan hanya boleh berisi huruf, angka, titik, atau strip.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Kata sandi minimal 6 karakter demi keamanan akun Anda.'];
    }

    // Check duplicate username
    $checkUser = get_user_by_username($username);
    if ($checkUser) {
        return ['success' => false, 'message' => 'Username "' . htmlspecialchars($username) . '" sudah digunakan. Silakan gunakan username lain.'];
    }

    // Check duplicate email
    $checkEmail = get_user_by_identifier($email);
    if ($checkEmail) {
        return ['success' => false, 'message' => 'Alamat email "' . htmlspecialchars($email) . '" sudah terdaftar. Silakan langsung masuk.'];
    }

    // Inspect columns
    $existingCols = [];
    $colRes = $db->query("SHOW COLUMNS FROM `users`");
    if ($colRes) {
        while ($c = $colRes->fetch_assoc()) {
            $existingCols[strtolower($c['Field'])] = true;
        }
    }

    // Generate initials for user_id
    $nameParts = preg_split('/\s+/', $name);
    $userId = '';
    if (count($nameParts) > 1 && !empty($nameParts[0]) && !empty($nameParts[1])) {
        $userId = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $userId = strtoupper(substr($username, 0, 2));
    }

    if (empty($nrp)) {
        $nrp = 'ANTAM-' . rand(10000, 99999);
    }

    if (empty($role)) {
        $role = ($portalType === 'Admin') ? 'Biro Human Capital & Diklat' : 'HC Pegawai Operasional';
    }

    $avatarColors = ['#0f766e', '#0284c7', '#d97706', '#7c3aed', '#e11d48', '#059669', '#2563eb', '#d4af37'];
    $avatarColor = $avatarColors[array_rand($avatarColors)];
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $fields = [
        'username'     => $username,
        'password'     => $passwordHash,
        'user_id'      => $userId,
        'nrp'          => $nrp,
        'name'         => $name,
        'role'         => $role,
        'type'         => ($portalType === 'Admin') ? 'Admin' : 'User',
        'dept'         => $dept,
        'email'        => $email,
        'phone'        => $phone,
        'avatar_color' => $avatarColor,
        'avatar_image' => $data['avatar_image'] ?? null,
        'bio'          => 'Pegawai terdaftar Sistem Smart Learning PT ANTAM Tbk Site Pomalaa.',
        'reminder_freq'=> ($portalType === 'Admin') ? 'Realtime' : '24 Jam',
        'wa_notif'     => 1
    ];

    // Legacy columns sync
    if (isset($existingCols['nama'])) {
        $fields['nama'] = $name;
    }
    if (isset($existingCols['unit_kerja'])) {
        $fields['unit_kerja'] = $dept;
    }

    $insertCols = [];
    $insertPlaceholders = [];
    $vals = [];
    $types = '';

    foreach ($fields as $col => $val) {
        if (isset($existingCols[$col])) {
            $insertCols[] = "`{$col}`";
            $insertPlaceholders[] = "?";
            $vals[] = $val;
            $types .= is_int($val) ? 'i' : 's';
        }
    }

    $sql = "INSERT INTO `users` (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Gagal menyiapkan query pembuatan akun: ' . $db->error];
    }

    $stmt->bind_param($types, ...$vals);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        return [
            'success'  => true,
            'message'  => 'Pendaftaran akun berhasil! Silakan masuk dengan kredensial baru Anda.',
            'username' => $username,
            'portal'   => ($portalType === 'Admin') ? 'admin' : 'pegawai'
        ];
    } else {
        return ['success' => false, 'message' => 'Gagal mendaftarkan akun ke database: ' . $db->error];
    }
}

function update_user_profile(string $username, array $profileData): array {
    $db  = get_db();
    if (!$db) {
        return ['success' => false, 'message' => 'Koneksi ke database gagal.'];
    }
    $key = strtolower(trim($username));

    $user = get_user_by_username($key);
    if (!$user) {
        return ['success' => false, 'message' => 'User tidak ditemukan.'];
    }

    // Periksa kolom tabel users yang tersedia
    $existingCols = [];
    $colRes = $db->query("SHOW COLUMNS FROM `users`");
    if ($colRes) {
        while ($c = $colRes->fetch_assoc()) {
            $existingCols[strtolower($c['Field'])] = true;
        }
    }

    $fields = ['name','nrp','dept','email','phone','bio','avatar_color','avatar_image','reminder_freq'];
    $sets   = [];
    $vals   = [];
    $types  = '';

    foreach ($fields as $f) {
        if (isset($profileData[$f])) {
            $val = trim($profileData[$f]);
            if (isset($existingCols[$f])) {
                $sets[]  = "`{$f}` = ?";
                $vals[]  = $val;
                $types  .= 's';
            }
            // Sinkronisasi kolom warisan (legacy) jika ada
            if ($f === 'name' && isset($existingCols['nama'])) {
                $sets[]  = "`nama` = ?";
                $vals[]  = $val;
                $types  .= 's';
            }
            if ($f === 'dept' && isset($existingCols['unit_kerja'])) {
                $sets[]  = "`unit_kerja` = ?";
                $vals[]  = $val;
                $types  .= 's';
            }
        }
    }
    if (isset($profileData['wa_notif']) && isset($existingCols['wa_notif'])) {
        $sets[]  = '`wa_notif` = ?';
        $vals[]  = (int)(bool)$profileData['wa_notif'];
        $types  .= 'i';
    }

    if (empty($sets)) {
        return ['success' => false, 'message' => 'Tidak ada data yang perlu diperbarui.'];
    }

    $vals[]  = $key;
    $types  .= 's';

    $sql  = 'UPDATE `users` SET ' . implode(', ', $sets) . ' WHERE `username` = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Gagal mempersiapkan query: ' . $db->error];
    }
    $stmt->bind_param($types, ...$vals);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'message' => 'Gagal memperbarui profil di database: ' . $db->error];
    }

    return ['success' => true, 'message' => 'Profil berhasil diperbarui.', 'user' => get_user_by_username($key)];
}

function update_user_password(string $username, string $oldPassword, string $newPassword): array {
    $user = get_user_by_username($username);
    if (!$user) {
        return ['success' => false, 'message' => 'User tidak ditemukan.'];
    }
    $stored = $user['password'] ?? '';
    $isMatch = ($stored === $oldPassword)
            || (strtolower($stored) === md5($oldPassword))
            || (strtolower($stored) === '6ad14ba9986e3615423dfca256d04e3f' && in_array($oldPassword, ['password123', 'user123']))
            || password_verify($oldPassword, $stored);

    if (!$isMatch) {
        return ['success' => false, 'message' => 'Kata sandi saat ini tidak sesuai. Silakan periksa kembali.'];
    }
    if (strlen($newPassword) < 5) {
        return ['success' => false, 'message' => 'Kata sandi baru minimal 5 karakter.'];
    }

    $db   = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $key  = strtolower(trim($username));
    $stmt = $db->prepare("UPDATE `users` SET `password` = ? WHERE `username` = ?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Gagal memperbarui kata sandi: ' . $db->error];
    }
    $stmt->bind_param('ss', $newPassword, $key);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'message' => 'Gagal memperbarui kata sandi di database.'];
    }

    return ['success' => true, 'message' => 'Kata sandi berhasil diperbarui. Silakan gunakan kata sandi baru saat login berikutnya.'];
}

// ==========================================================
// ACTIVITIES (KEGIATAN BELAJAR)
// ==========================================================

function get_activities(?string $username = null, ?string $status = null): array {
    $db    = get_db();
    if (!$db) return [];
    $sql   = 'SELECT * FROM `activities` WHERE 1=1';
    $vals  = [];
    $types = '';

    if ($username) {
        $uname  = strtolower(trim($username));
        $sql   .= ' AND `user_username` = ?';
        $vals[] = $uname;
        $types .= 's';
    }
    if ($status) {
        $sql   .= ' AND `status` = ?';
        $vals[] = $status;
        $types .= 's';
    }
    $sql .= ' ORDER BY `created_at` DESC';

    $out = [];
    if ($vals) {
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$vals);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($res instanceof mysqli_result)) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = _row_to_array($row);
                }
            }
            $stmt->close();
        }
    } else {
        $res = $db->query($sql);
        if ($res && ($res instanceof mysqli_result)) {
            while ($row = $res->fetch_assoc()) {
                $out[] = _row_to_array($row);
            }
        }
    }

    return $out;
}

function add_new_activity(array $activity): array {
    $db    = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $newId = 'act_' . time() . '_' . rand(100, 999);

    $deadline = (!empty($activity['deadline'])) ? $activity['deadline'] : date('d M Y', strtotime('+14 days'));
    $type     = $activity['type']     ?? 'Pengembangan Mandiri';
    $category = $activity['category'] ?? 'Operasional Tambang';
    $duration = $activity['duration'] ?? '2 Hari (16 Jam)';
    $level    = $activity['level']    ?? 'Intermediate';
    $notes    = $activity['notes']    ?? 'Diajukan oleh pegawai untuk pemenuhan kompetensi.';
    $totMod   = isset($activity['total_modules']) ? (int)$activity['total_modules'] : 4;
    $uname    = $activity['user_username'];
    $uname2   = $activity['user_name'];
    $title    = trim($activity['title']);
    $certTpl  = $activity['cert_template'] ?? null;

    $stmt = $db->prepare("INSERT INTO `activities`
        (`id`,`user_username`,`user_name`,`title`,`type`,`category`,`duration`,`level`,`deadline`,
         `status`,`progress`,`current_module`,`total_modules`,`completed_modules`,`notes`,`cert_template`,`created_at`)
        VALUES (?,?,?,?,?,?,?,?,?,'menunggu_konfirmasi',0,'Menunggu Konfirmasi Admin',?,0,?,?,NOW())");
    if ($stmt) {
        $stmt->bind_param('sssssssssiss', $newId,$uname,$uname2,$title,$type,$category,$duration,$level,$deadline,$totMod,$notes,$certTpl);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            return ['success' => false, 'message' => 'Gagal menyimpan usulan kegiatan: ' . $db->error];
        }
    } else {
        return ['success' => false, 'message' => 'Query database error: ' . $db->error];
    }

    // Notifikasi ke Admin
    _insert_notification(
        'admin', 'request',
        'Pengajuan Kegiatan: ' . $uname2,
        $uname2 . ' mengajukan kegiatan baru: "' . $title . '". Menunggu persetujuan Anda.',
        $uname2, $title, $newId
    );

    $act = get_activities_by_id($newId);
    return ['success' => true, 'activity' => $act];
}

function assign_training_to_user(string $targetUsername, string $title, string $deadline): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $targetUser = get_user_by_username($targetUsername);
    if (!$targetUser) {
        return ['success' => false, 'message' => 'Target pegawai tidak ditemukan.'];
    }

    $newId = 'act_' . time() . '_' . rand(100, 999);
    $type = 'Wajib K3';
    $category = 'Penugasan Resmi Admin';
    $duration = '3 Hari (24 Jam)';
    $level = 'Intermediate';
    $status = 'disetujui';
    $progress = 0;
    $currentModule = 'Modul 1: Pengantar Penugasan Resmi';
    $totalModules = 4;
    $completedModules = 0;
    $notes = 'Ditugaskan langsung oleh Learning Ops Administrator.';

    $stmt = $db->prepare("INSERT INTO `activities`
        (`id`,`user_username`,`user_name`,`title`,`type`,`category`,`duration`,`level`,`deadline`,
         `status`,`progress`,`current_module`,`total_modules`,`completed_modules`,`notes`,`created_at`,`approved_at`)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
    if ($stmt) {
        $stmt->bind_param(
            'ssssssssssisiis',
            $newId,
            $targetUser['username'],
            $targetUser['name'],
            $title,
            $type,
            $category,
            $duration,
            $level,
            $deadline,
            $status,
            $progress,
            $currentModule,
            $totalModules,
            $completedModules,
            $notes
        );
        $stmt->execute();
        $stmt->close();
    }

    // Notifikasi ke pegawai
    _insert_notification(
        $targetUser['username'],
        'assignment',
        'Penugasan Baru: ' . $title,
        'Learning Ops Admin telah menugaskan pelatihan "' . $title . '" kepada Anda (Batas: ' . $deadline . ').',
        'Learning Ops Admin',
        $title,
        $newId
    );

    $act = get_activities_by_id($newId);
    return ['success' => true, 'activity' => $act];
}

function get_activities_by_id(string $actId): ?array {
    $db = get_db();
    if (!$db) return null;
    $stmt = $db->prepare("SELECT * FROM `activities` WHERE `id` = ?");
    if (!$stmt) return null;
    $stmt->bind_param('s', $actId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $row  = ($res && ($res instanceof mysqli_result)) ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? _row_to_array($row) : null;
}

function confirm_activity_status(string $activityId, string $action = 'setujui', string $adminNotes = ''): array {
    $db  = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $act = get_activities_by_id($activityId);
    if (!$act) {
        return ['success' => false, 'message' => 'Kegiatan tidak ditemukan.'];
    }

    if ($action === 'setujui') {
        $notes = $adminNotes ?: 'Disetujui oleh Learning Ops Administrator.';
        $stmt  = $db->prepare("UPDATE `activities`
            SET `status`='disetujui', `approved_at`=NOW(),
                `current_module`='Modul 1: Pengenalan & Dasar Kompetensi',
                `admin_notes`=?
            WHERE `id`=?");
        if ($stmt) {
            $stmt->bind_param('ss', $notes, $activityId);
            $stmt->execute();
            $stmt->close();
        }

        _insert_notification(
            $act['user_username'], 'approval',
            'Kegiatan Dikonfirmasi: ' . $act['title'],
            'Admin telah menyetujui kegiatan "' . $act['title'] . '". Modul kini aktif di My Learning dan Dashboard Anda.',
            'Learning Ops Admin', $act['title'], $activityId
        );
    } else {
        $notes = $adminNotes ?: 'Pengajuan belum disetujui.';
        $stmt  = $db->prepare("UPDATE `activities` SET `status`='ditolak', `admin_notes`=? WHERE `id`=?");
        if ($stmt) {
            $stmt->bind_param('ss', $notes, $activityId);
            $stmt->execute();
            $stmt->close();
        }
    }

    return ['success' => true, 'status' => ($action === 'setujui' ? 'disetujui' : 'ditolak')];
}

function submit_module_report(string $activityIdOrTitle, string $username, string $reportText, string $attachmentName = ''): array {
    $db    = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $uname = strtolower(trim($username));

    // Cari activity berdasarkan ID atau title
    $stmt  = $db->prepare("SELECT * FROM `activities` WHERE (`id`=? OR LOWER(`title`)=LOWER(?)) AND `user_username`=? LIMIT 1");
    if (!$stmt) return ['success' => false, 'message' => 'Query database error.'];
    $stmt->bind_param('sss', $activityIdOrTitle, $activityIdOrTitle, $uname);
    $stmt->execute();
    $res   = $stmt->get_result();
    $act   = ($res && ($res instanceof mysqli_result)) ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$act) {
        return ['success' => false, 'message' => 'Data kegiatan tidak ditemukan.'];
    }
    $act = _row_to_array($act);

    $currentProg    = (int)$act['progress'];
    $newProg        = min(100, $currentProg + 25);
    $totalMods      = (int)$act['total_modules'];
    $completedMods  = min($totalMods, (int)$act['completed_modules'] + 1);
    $currentModule  = ($newProg >= 100)
        ? 'Selesai (Lembar Kerja Diserahkan)'
        : 'Unit ' . ($completedMods + 1) . ': Evaluasi & Praktik Lapangan';

    $attachVal = $attachmentName ?: null;
    $stmt = $db->prepare("UPDATE `activities`
        SET `progress`=?, `completed_modules`=?, `current_module`=?,
            `last_report`=?, `last_attachment`=?, `last_submitted_at`=NOW()
        WHERE `id`=?");
    if ($stmt) {
        $stmt->bind_param('iissss', $newProg, $completedMods, $currentModule, $reportText, $attachVal, $act['id']);
        $stmt->execute();
        $stmt->close();
    }

    // Notifikasi ke Admin
    _insert_notification(
        'admin', 'submission',
        'Lembar Kerja Masuk: ' . $activityIdOrTitle,
        'Pegawai @' . $username . ' telah mengumpulkan laporan lembar kerja modul: "' . $activityIdOrTitle . '".',
        $username, $activityIdOrTitle, $act['id']
    );

    return ['success' => true, 'message' => 'Laporan lembar kerja modul berhasil disimpan & dikumpulkan!'];
}

/**
 * Admin memberikan nilai + feedback ke lembar kerja pegawai.
 */
function review_module_report(string $activityId, int $score, string $feedback, string $adminName = 'Learning Ops Admin'): array {
    $db  = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $act = get_activities_by_id($activityId);
    if (!$act) {
        return ['success' => false, 'message' => 'Kegiatan tidak ditemukan.'];
    }

    $score    = max(0, min(100, $score));
    $progress = $score; // Skor = progress baru setelah dinilai

    $stmt = $db->prepare("UPDATE `activities`
        SET `grade`=?, `admin_feedback`=?, `graded_at`=NOW(), `progress`=?
        WHERE `id`=?");
    if ($stmt) {
        $stmt->bind_param('isis', $score, $feedback, $progress, $activityId);
        $stmt->execute();
        $stmt->close();
    }

    // Notifikasi ke pegawai
    $msg = ($score >= 75)
        ? "Lembar kerja Anda pada \"" . $act['title'] . "\" telah dinilai. Skor: {$score}/100. Selamat! ✅"
        : "Lembar kerja Anda pada \"" . $act['title'] . "\" telah dinilai. Skor: {$score}/100. Tindak lanjut diperlukan.";

    _insert_notification(
        $act['user_username'], 'graded',
        'Lembar Kerja Dinilai: ' . $act['title'],
        $msg . ($feedback ? ' Catatan Admin: ' . $feedback : ''),
        $adminName, $act['title'], $activityId
    );

    return ['success' => true, 'score' => $score, 'message' => 'Penilaian berhasil disimpan.'];
}

/**
 * Logika Sederhana: Selesaikan kegiatan pelatihan & langsung terbitkan sertifikat resmi.
 * Setiap pegawai yang mengikuti kegiatan berhak langsung menerima sertifikat kelulusan.
 */
function claim_activity_certificate(string $activityId, string $username): array {
    $db    = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $uname = strtolower(trim($username));
    $user  = get_user_by_username($uname);
    if (!$user) return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];

    // Ambil data kegiatan
    $stmt = $db->prepare("SELECT * FROM `activities` WHERE (`id` = ? OR `title` = ? OR LOWER(`title`) = LOWER(?)) AND `user_username` = ? LIMIT 1");
    if (!$stmt) return ['success' => false, 'message' => 'Gagal mengambil data kegiatan.'];
    $stmt->bind_param('ssss', $activityId, $activityId, $activityId, $uname);
    $stmt->execute();
    $res = $stmt->get_result();
    $act = ($res && ($res instanceof mysqli_result)) ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$act) {
        // Cek jika sertifikat untuk judul ini sudah ada di portofolio
        $stmtCert = $db->prepare("SELECT * FROM `certificates` WHERE `user_username` = ? AND (LOWER(`course_title`) = LOWER(?) OR `id` = ?) LIMIT 1");
        if ($stmtCert) {
            $stmtCert->bind_param('sss', $uname, $activityId, $activityId);
            $stmtCert->execute();
            $rCert = $stmtCert->get_result();
            if ($rCert && ($rCert instanceof mysqli_result) && $rCert->num_rows > 0) {
                $existingCert = _row_to_array($rCert->fetch_assoc());
                $stmtCert->close();
                return [
                    'success'      => true,
                    'message'      => 'Sertifikat resmi sudah tersedia di portofolio Anda.',
                    'cert_id'      => $existingCert['id'],
                    'reg_no'       => $existingCert['reg_no'],
                    'course_title' => $existingCert['course_title'],
                    'score'        => $existingCert['score'] ?? 100,
                    'issued_at'    => date('d M Y', strtotime($existingCert['issued_at'])),
                    'valid_until'  => date('d M Y', strtotime($existingCert['valid_until'])),
                    'holder_name'  => $existingCert['user_name'] ?? $user['name']
                ];
            }
            $stmtCert->close();
        }
        return ['success' => false, 'message' => 'Kegiatan pelatihan tidak ditemukan.'];
    }
    $act = _row_to_array($act);
    $courseTitle = $act['title'];

    // 1. Update kegiatan: langsung tuntas 100% dan status selesai bersertifikat
    $totalMods = (int)($act['total_modules'] ?? 4);
    $stmtUp = $db->prepare("UPDATE `activities` 
        SET `progress` = 100, `completed_modules` = ?, `current_module` = 'Selesai — Bersertifikat Resmi', `grade` = 100 
        WHERE `id` = ?");
    if ($stmtUp) {
        $stmtUp->bind_param('is', $totalMods, $act['id']);
        $stmtUp->execute();
        $stmtUp->close();
    }

    // 2. Cek apakah sertifikat untuk kegiatan ini sudah pernah terbit
    $stmtCert = $db->prepare("SELECT * FROM `certificates` WHERE `user_username` = ? AND LOWER(`course_title`) = LOWER(?) LIMIT 1");
    $existingCert = null;
    if ($stmtCert) {
        $stmtCert->bind_param('ss', $uname, $courseTitle);
        $stmtCert->execute();
        $rCert = $stmtCert->get_result();
        if ($rCert && ($rCert instanceof mysqli_result) && $rCert->num_rows > 0) {
            $existingCert = $rCert->fetch_assoc();
        }
        $stmtCert->close();
    }

    if ($existingCert) {
        $existingCert = _row_to_array($existingCert);
        return [
            'success'      => true,
            'message'      => 'Sertifikat resmi untuk pelatihan ini sudah tersedia di portofolio Anda.',
            'cert_id'      => $existingCert['id'],
            'reg_no'       => $existingCert['reg_no'],
            'course_title' => $existingCert['course_title'],
            'score'        => $existingCert['score'],
            'issued_at'    => date('d M Y', strtotime($existingCert['issued_at'])),
            'valid_until'  => date('d M Y', strtotime($existingCert['valid_until'])),
            'holder_name'  => $user['name']
        ];
    }

    // 3. Buat sertifikat resmi baru secara instan
    $certId       = 'cert_' . time() . '_' . rand(100, 999);
    $regNo        = 'ANTAM/K3U/' . date('Y') . '/' . rand(1000, 9999);
    $validDate    = date('Y-m-d', strtotime('+3 years'));
    $score        = 100;
    $issuer       = 'Learning Ops Admin - PT ANTAM Tbk';
    $templateFile = !empty($act['cert_template']) ? $act['cert_template'] : null;

    $stmtNew = $db->prepare("INSERT INTO `certificates` 
        (`id`, `user_username`, `user_name`, `course_title`, `reg_no`, `score`, `issued_at`, `valid_until`, `issued_by`, `status`, `template_file`, `attachment`) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'active', ?, ?)");
    if ($stmtNew) {
        $stmtNew->bind_param('sssssissss', $certId, $uname, $user['name'], $courseTitle, $regNo, $score, $validDate, $issuer, $templateFile, $templateFile);
        $stmtNew->execute();
        $stmtNew->close();
    }

    // 4. Kirim notifikasi instan
    _insert_notification(
        $uname, 'certificate',
        'Sertifikat Resmi Terbit: ' . $courseTitle,
        'Selamat ' . $user['name'] . '! Anda telah menyelesaikan pelatihan "' . $courseTitle . '" dan Sertifikat Resmi nomor ' . $regNo . ' telah diterbitkan.',
        'Learning Ops Admin', $courseTitle, $act['id']
    );

    _insert_notification(
        'admin', 'certificate',
        'Pelatihan Tuntas: ' . $user['name'],
        'Pegawai ' . $user['name'] . ' telah menyelesaikan pelatihan "' . $courseTitle . '" (Sertifikat ' . $regNo . ' diterbitkan).',
        $user['name'], $courseTitle, $act['id']
    );

    return [
        'success'      => true,
        'message'      => 'Selamat! Pelatihan berhasil dituntaskan dan Sertifikat Resmi telah diterbitkan.',
        'cert_id'      => $certId,
        'reg_no'       => $regNo,
        'course_title' => $courseTitle,
        'score'        => $score,
        'issued_at'    => date('d M Y'),
        'valid_until'  => date('d M Y', strtotime('+3 years')),
        'holder_name'  => $user['name'],
        'template_file'=> $templateFile
    ];
}

/**
 * Logika Batch: Upload Template Sertifikat & Generate Otomatis untuk Seluruh Peserta Kegiatan.
 * Menerima judul kegiatan, template sertifikat, dan daftar nama/username peserta.
 */
function batch_generate_certificates(string $courseTitle, array $participants, ?string $templateFile = null, string $issuer = 'PT ANTAM (PERSERO) TBK'): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $cleanCourse = trim($courseTitle);
    if (empty($cleanCourse)) return ['success' => false, 'message' => 'Judul kegiatan pelatihan wajib diisi.'];
    if (empty($participants)) return ['success' => false, 'message' => 'Daftar nama peserta yang mengikuti kegiatan tidak boleh kosong.'];

    // Ambil seluruh user terdaftar untuk pencocokan nama atau username
    $allUsers = get_all_users();
    $nameMap = [];
    foreach ($allUsers as $uKey => $uData) {
        $nameMap[strtolower(trim($uData['name']))] = $uData;
        $nameMap[strtolower(trim($uKey))] = $uData;
    }

    $generated = [];
    $validDate = date('Y-m-d', strtotime('+3 years'));
    $issuedDateFmt = date('d M Y');
    $validDateFmt  = date('d M Y', strtotime('+3 years'));

    foreach ($participants as $p) {
        $rawName = is_array($p) ? ($p['name'] ?? '') : trim($p);
        if (empty($rawName)) continue;

        // Cek apakah cocok dengan pegawai di database
        $matchedUser = $nameMap[strtolower($rawName)] ?? null;
        $username = $matchedUser ? $matchedUser['username'] : 'peserta_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawName));
        $cleanName = $matchedUser ? $matchedUser['name'] : $rawName;

        $certId = 'cert_batch_' . time() . '_' . rand(100, 999);
        $regNo  = 'ANTAM/K3U/' . date('Y') . '/' . rand(1000, 9999);
        $score  = 100;

        // Cek jika sertifikat untuk peserta dan course ini sudah ada
        $stmtCheck = $db->prepare("SELECT `id`, `reg_no`, `score`, `issued_at`, `valid_until` FROM `certificates` WHERE `user_username` = ? AND LOWER(`course_title`) = LOWER(?) LIMIT 1");
        $existing = null;
        if ($stmtCheck) {
            $stmtCheck->bind_param('ss', $username, $cleanCourse);
            $stmtCheck->execute();
            $r = $stmtCheck->get_result();
            if ($r && $r->num_rows > 0) {
                $existing = $r->fetch_assoc();
            }
            $stmtCheck->close();
        }

        if ($existing) {
            $regNo   = $existing['reg_no'];
            $certId  = $existing['id'];
            $score   = (int)($existing['score'] ?? 100);
            $issDate = date('d M Y', strtotime($existing['issued_at']));
            $valDate = date('d M Y', strtotime($existing['valid_until']));

            // Update template_file jika ada
            if ($templateFile) {
                $stmtUp = $db->prepare("UPDATE `certificates` SET `attachment` = ? WHERE `id` = ?");
                if ($stmtUp) {
                    $stmtUp->bind_param('ss', $templateFile, $certId);
                    $stmtUp->execute();
                    $stmtUp->close();
                }
            }
        } else {
            $issDate = $issuedDateFmt;
            $valDate = $validDateFmt;

            $stmtIns = $db->prepare("INSERT INTO `certificates` 
                (`id`, `user_username`, `user_name`, `course_title`, `reg_no`, `score`, `issued_at`, `valid_until`, `issued_by`, `status`, `attachment`, `template_file`) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'active', ?, ?)");
            if ($stmtIns) {
                $stmtIns->bind_param('sssssissss', $certId, $username, $cleanName, $cleanCourse, $regNo, $score, $validDate, $issuer, $templateFile, $templateFile);
                $stmtIns->execute();
                $stmtIns->close();
            }

            // Kirim notifikasi instan ke peserta jika terdaftar
            if ($matchedUser) {
                _insert_notification(
                    $username,
                    'certificate',
                    'Sertifikat Resmi Terbit: ' . $cleanCourse,
                    'Selamat ' . $cleanName . '! Sertifikat resmi kegiatan "' . $cleanCourse . '" (Nomor: ' . $regNo . ') telah diterbitkan otomatis dan tersimpan di portofolio Anda.',
                    'Learning Ops Admin',
                    $cleanCourse,
                    $certId
                );
            }
        }

        // Update kegiatan yang bersesuaian menjadi 100% jika ada di akun peserta
        $stmtAct = $db->prepare("UPDATE `activities` 
            SET `progress` = 100, `completed_modules` = `total_modules`, `current_module` = 'Selesai — Bersertifikat Resmi', `grade` = 100 
            WHERE `user_username` = ? AND (LOWER(`title`) = LOWER(?) OR `id` = ?)");
        if ($stmtAct) {
            $stmtAct->bind_param('sss', $username, $cleanCourse, $cleanCourse);
            $stmtAct->execute();
            $stmtAct->close();
        }

        $generated[] = [
            'cert_id'      => $certId,
            'user_name'    => $cleanName,
            'username'     => $username,
            'course_title' => $cleanCourse,
            'reg_no'       => $regNo,
            'score'        => $score,
            'issued_at'    => $issDate,
            'valid_until'  => $valDate,
            'template_file'=> $templateFile
        ];
    }

    return [
        'success'      => true,
        'message'      => count($generated) . ' sertifikat resmi berhasil diterbitkan secara otomatis!',
        'count'        => count($generated),
        'course_title' => $cleanCourse,
        'template_file'=> $templateFile,
        'generated'    => $generated
    ];
}

/**
 * Penyelesaian Kegiatan: Upload Dokumen Absensi & Otomatis Terbitkan Sertifikat untuk Seluruh Peserta Hadir.
 */
function complete_activity_with_attendance(string $activityId, array $attendees, ?string $attendanceFile = null, string $currentUser = ''): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];

    $act = get_activities_by_id($activityId);
    if (!$act) {
        // Coba cari berdasar title
        $stmtF = $db->prepare("SELECT * FROM `activities` WHERE LOWER(`title`) = LOWER(?) LIMIT 1");
        if ($stmtF) {
            $stmtF->bind_param('s', $activityId);
            $stmtF->execute();
            $res = $stmtF->get_result();
            if ($res && $res->num_rows > 0) {
                $act = _row_to_array($res->fetch_assoc());
            }
            $stmtF->close();
        }
    }

    if (!$act) {
        return ['success' => false, 'message' => 'Data kegiatan pelatihan tidak ditemukan.'];
    }

    $courseTitle   = $act['title'];
    $templateFile  = $act['cert_template'] ?? null;
    $totalMods     = (int)($act['total_modules'] ?? 4);

    // 1. Update kegiatan menjadi 100% tuntas dan simpan file absensi
    $stmtUp = $db->prepare("UPDATE `activities` 
        SET `progress` = 100, `completed_modules` = ?, `current_module` = 'Selesai — Terverifikasi Absensi HC', `grade` = 100,
            `attendance_file` = COALESCE(?, `attendance_file`),
            `last_attachment` = COALESCE(?, `last_attachment`), `last_submitted_at` = NOW()
        WHERE `id` = ?");
    if ($stmtUp) {
        $stmtUp->bind_param('isss', $totalMods, $attendanceFile, $attendanceFile, $act['id']);
        $stmtUp->execute();
        $stmtUp->close();
    }

    // 2. Jika bukan admin yang mengonfirmasi, atau jika daftar peserta kosong, batasi peserta hanya pada pemilik kegiatan
    $isUserAdmin = false;
    if (!empty($currentUser)) {
        $uInfo = get_user_by_username($currentUser);
        if ($uInfo && ($uInfo['type'] ?? '') === 'Admin') {
            $isUserAdmin = true;
        }
    }
    if (!$isUserAdmin || empty($attendees)) {
        // Pegawai biasa hanya menyelesaikan untuk dirinya sendiri
        $attendees = [$act['user_name']];
    }

    // 3. Terbitkan sertifikat otomatis untuk peserta yang hadir
    $batchRes = batch_generate_certificates($courseTitle, $attendees, $templateFile, 'PT ANTAM (PERSERO) TBK — Site Pomalaa');

    // 4. Catat notifikasi bahwa absensi telah diunggah oleh HC
    _insert_notification(
        'admin', 'verification',
        'Verifikasi Absensi: ' . $courseTitle,
        'Pihak HC telah mengonfirmasi penyelesaian kegiatan "' . $courseTitle . '" dan mengunggah berkas absensi resmi.',
        'Human Capital PT ANTAM', $courseTitle, $act['id']
    );

    return [
        'success'         => true,
        'message'         => 'Kegiatan selesai diverifikasi! Berkas absensi resmi telah tersimpan dan dapat diakses oleh Biro & Admin.',
        'activity_id'     => $act['id'],
        'course_title'    => $courseTitle,
        'attendance_file' => $attendanceFile,
        'count'           => count($batchRes['generated']),
        'generated'       => $batchRes['generated']
    ];
}

/**
 * Simpan hasil kuis, terbitkan sertifikat jika lulus (>= 80).
 */
function submit_quiz_result(string $username, string $courseTitle, int $score): array {
    $db    = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $uname = strtolower(trim($username));
    $user  = get_user_by_username($uname);

    if (!$user) {
        return ['success' => false, 'message' => 'User tidak ditemukan.'];
    }

    $passed = $score >= 80;

    // Update progress kegiatan terkait
    $stmt = $db->prepare("UPDATE `activities`
        SET `progress`=?, `current_module`=?
        WHERE `user_username`=? AND LOWER(`title`)=LOWER(?) LIMIT 1");
    if ($stmt) {
        $newModule = $passed ? 'Selesai — Sertifikat Diterbitkan' : 'Remedial — Belum Memenuhi Passing Grade';
        $prog      = $score;
        $stmt->bind_param('isss', $prog, $newModule, $uname, $courseTitle);
        $stmt->execute();
        $stmt->close();
    }

    $certId = null;
    $regNo  = null;

    if ($passed) {
        // Terbitkan sertifikat
        $certId    = 'cert_' . time() . '_' . rand(100, 999);
        $regNo     = 'ANTAM/K3U/' . date('Y') . '/' . rand(1000, 9999);
        $validDate = date('Y-m-d', strtotime('+3 years'));

        $stmt2 = $db->prepare("INSERT INTO `certificates`
            (`id`,`user_username`,`user_name`,`course_title`,`reg_no`,`score`,`issued_at`,`valid_until`,`issued_by`,`status`)
            VALUES (?,?,?,?,?,?,NOW(),?,'Learning Ops Admin','active')");
        if ($stmt2) {
            $stmt2->bind_param('sssssss', $certId, $uname, $user['name'], $courseTitle, $regNo, $score, $validDate);
            $stmt2->execute();
            $stmt2->close();
        }

        // Notifikasi ke admin
        _insert_notification(
            'admin', 'certificate',
            'Sertifikat Baru: ' . $user['name'],
            $user['name'] . ' lulus evaluasi "' . $courseTitle . '" (Skor: ' . $score . '/100). Sertifikat ' . $regNo . ' diterbitkan.',
            $user['name'], $courseTitle, null
        );

        // Notifikasi ke user
        _insert_notification(
            $uname, 'certificate',
            'Selamat! Sertifikat Anda Terbit',
            'Anda lulus evaluasi "' . $courseTitle . '" dengan skor ' . $score . '/100. Sertifikat resmi ' . $regNo . ' kini tersedia di tab Sertifikat.',
            'Learning Ops Admin', $courseTitle, null
        );
    }

    return [
        'success'  => true,
        'passed'   => $passed,
        'score'    => $score,
        'cert_id'  => $certId,
        'reg_no'   => $regNo,
        'message'  => $passed
            ? "Selamat! Anda lulus dengan skor {$score}/100. Sertifikat telah diterbitkan."
            : "Skor Anda {$score}/100. Passing grade adalah 80. Silakan ulangi.",
    ];
}

/**
 * Ambil semua sertifikat milik user.
 */
function get_user_certificates(string $username): array {
    $db    = get_db();
    if (!$db) return [];
    $uname = strtolower(trim($username));
    $stmt  = $db->prepare("SELECT * FROM `certificates` WHERE `user_username`=? ORDER BY `issued_at` DESC");
    if (!$stmt) return [];
    $stmt->bind_param('s', $uname);
    $stmt->execute();
    $res   = $stmt->get_result();
    $out = [];
    if ($res && ($res instanceof mysqli_result)) {
        while ($row = $res->fetch_assoc()) {
            $out[] = _row_to_array($row);
        }
    }
    $stmt->close();
    return $out;
}

/**
 * Simpan sertifikat eksternal / mandiri yang diunggah pegawai.
 */
function save_external_certificate(string $username, string $title, string $regNo, string $issuedBy, string $validUntil, int $score = 100, string $attachmentName = ''): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Koneksi database gagal.'];
    $uname = strtolower(trim($username));
    $user  = get_user_by_username($uname);
    if (!$user) return ['success' => false, 'message' => 'User tidak ditemukan.'];

    $certId = 'cert_ext_' . time() . '_' . rand(100, 999);
    if (empty($regNo)) {
        $regNo = 'EXT/K3/' . date('Y') . '/' . rand(1000, 9999);
    }
    if (empty($issuedBy)) {
        $issuedBy = 'Lembaga Sertifikasi Eksternal';
    }

    // Cek apakah tabel memiliki kolom attachment
    $hasAttach = false;
    $resCol = $db->query("SHOW COLUMNS FROM `certificates` LIKE 'attachment'");
    if ($resCol && $resCol->num_rows > 0) {
        $hasAttach = true;
    }

    if ($hasAttach) {
        $stmt = $db->prepare("INSERT INTO `certificates` 
            (`id`, `user_username`, `user_name`, `course_title`, `reg_no`, `score`, `issued_at`, `valid_until`, `issued_by`, `status`, `attachment`) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'active', ?)");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Gagal query: ' . $db->error];
        }
        $stmt->bind_param('sssssisss', $certId, $uname, $user['name'], $title, $regNo, $score, $validUntil, $issuedBy, $attachmentName);
    } else {
        $stmt = $db->prepare("INSERT INTO `certificates` 
            (`id`, `user_username`, `user_name`, `course_title`, `reg_no`, `score`, `issued_at`, `valid_until`, `issued_by`, `status`) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'active')");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Gagal query: ' . $db->error];
        }
        $stmt->bind_param('sssssiss', $certId, $uname, $user['name'], $title, $regNo, $score, $validUntil, $issuedBy);
    }

    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'message' => 'Gagal menyimpan sertifikat ke database: ' . $db->error];
    }

    // Notifikasi ke Admin
    _insert_notification(
        'admin', 'certificate',
        'Unggahan Sertifikat: ' . $user['name'],
        $user['name'] . ' mengunggah sertifikat kompetensi "' . $title . '" (No: ' . $regNo . ').',
        $user['name'], $title, null
    );

    return [
        'success'  => true,
        'message'  => 'Sertifikat berhasil diunggah dan ditambahkan ke portofolio kompetensi.',
        'cert_id'  => $certId,
        'reg_no'   => $regNo
    ];
}

// ==========================================================
// LNA CATALOG
// ==========================================================

function get_lna_catalog(): array {
    $db  = get_db();
    if (!$db) return [];
    $res = $db->query("SELECT c.*, GROUP_CONCAT(t.tag ORDER BY t.id SEPARATOR '||') AS tags_str
        FROM `lna_catalog` c
        LEFT JOIN `lna_catalog_tags` t ON t.catalog_id = c.id
        GROUP BY c.id
        ORDER BY c.id");

    if (!$res || !($res instanceof mysqli_result)) return [];

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $row['tags'] = !empty($row['tags_str']) ? explode('||', $row['tags_str']) : [];
        unset($row['tags_str']);
        // Alias 'desc' agar kompatibel dengan kode lama
        $row['desc'] = $row['description'] ?? '';
        $out[] = $row;
    }
    return $out;
}

function add_catalog_course(array $course): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];

    $code     = trim($course['code'] ?? 'TRC-NEW');
    $title    = trim($course['title'] ?? '');
    $category = trim($course['category'] ?? 'GENERAL');
    $type     = trim($course['type'] ?? 'Inhouse');
    $duration = trim($course['duration'] ?? '2 Hari (16 Jam)');
    $level    = trim($course['level'] ?? 'Basic');
    $desc     = trim($course['desc'] ?? '');

    if (empty($title)) {
        return ['success' => false, 'message' => 'Nama modul pelatihan wajib diisi.'];
    }

    $id = 'lna_' . time() . '_' . rand(10, 99);
    $gapScore = 'Peningkatan Kompetensi';
    $targetRole = 'Seluruh Pegawai Terkait';
    $competencyArea = $category;
    $prereq = 'Sesuai Arahan Supervisor';

    $stmt = $db->prepare("INSERT INTO `lna_catalog` 
        (`id`, `title`, `code`, `category`, `type`, `duration`, `level`, `gap_score`, `target_role`, `competency_area`, `description`, `prerequisites`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Gagal menyiapkan query: ' . $db->error];
    }

    $stmt->bind_param('ssssssssssss', $id, $title, $code, $category, $type, $duration, $level, $gapScore, $targetRole, $competencyArea, $desc, $prereq);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        return ['success' => true, 'id' => $id, 'message' => 'Modul berhasil ditambahkan ke katalog master.'];
    } else {
        return ['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $db->error];
    }
}

// ==========================================================
// NOTIFICATIONS
// ==========================================================

function get_notifications_for_user(string $username, string $userType = 'User'): array {
    $db    = get_db();
    if (!$db) return [];
    $uname = strtolower(trim($username));
    $out   = [];

    if ($userType === 'Admin') {
        $res = $db->query("SELECT * FROM `notifications` WHERE `user_target`='admin' ORDER BY `created_at` DESC");
        if ($res && ($res instanceof mysqli_result)) {
            while ($row = $res->fetch_assoc()) {
                $out[] = $row;
            }
        }
    } else {
        $stmt = $db->prepare("SELECT * FROM `notifications` WHERE `user_target`=? ORDER BY `created_at` DESC");
        if ($stmt) {
            $stmt->bind_param('s', $uname);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($res instanceof mysqli_result)) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = $row;
                }
            }
            $stmt->close();
        }
    }

    return $out;
}

// ==========================================================
// INTERNAL: Insert Notification
// ==========================================================

function _insert_notification(
    string $target,
    string $type,
    string $title,
    string $message,
    string $relatedUser  = '',
    string $relatedCourse = '',
    ?string $activityId  = null
): void {
    $db  = get_db();
    if (!$db) return;
    $id  = 'notif_' . time() . '_' . rand(100, 999);
    $stmt = $db->prepare("INSERT INTO `notifications`
        (`id`,`user_target`,`type`,`title`,`message`,`related_user`,`related_course`,`activity_id`,`status`,`created_at`)
        VALUES (?,?,?,?,?,?,?,?,'unread',NOW())");
    if ($stmt) {
        $stmt->bind_param('ssssssss', $id, $target, $type, $title, $message, $relatedUser, $relatedCourse, $activityId);
        $stmt->execute();
        $stmt->close();
    }
}

function mark_all_notifications_read(string $username, string $userType = 'User'): bool {
    $db = get_db();
    if (!$db) return false;
    if ($userType === 'Admin') {
        $db->query("UPDATE `notifications` SET `status`='read' WHERE `user_target`='admin'");
    } else {
        $uname = strtolower(trim($username));
        $stmt = $db->prepare("UPDATE `notifications` SET `status`='read' WHERE `user_target`=?");
        if ($stmt) {
            $stmt->bind_param('s', $uname);
            $stmt->execute();
            $stmt->close();
        }
    }
    return true;
}

function resolve_notification(string $notifId): bool {
    $db = get_db();
    if (!$db) return false;
    $stmt = $db->prepare("UPDATE `notifications` SET `status`='resolved' WHERE `id`=?");
    if ($stmt) {
        $stmt->bind_param('s', $notifId);
        $stmt->execute();
        $stmt->close();
    }
    return true;
}

/**
 * Polling real-time notifications & pending activities (Admin & User)
 */
function check_realtime_notifications(string $username, string $userType): array {
    $db = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];

    $uname = strtolower(trim($username));
    $isAdmin = ($userType === 'Admin');
    $target = $isAdmin ? 'admin' : $uname;

    // 1. Pending activities (menunggu_konfirmasi)
    $pendingActivities = [];
    if ($isAdmin) {
        $res = $db->query("SELECT * FROM `activities` WHERE `status` = 'menunggu_konfirmasi' ORDER BY `created_at` DESC");
        if ($res && ($res instanceof mysqli_result)) {
            while ($row = $res->fetch_assoc()) {
                $pendingActivities[] = _row_to_array($row);
            }
        }
    } else {
        $stmt = $db->prepare("SELECT * FROM `activities` WHERE `user_username` = ? AND `status` = 'menunggu_konfirmasi' ORDER BY `created_at` DESC");
        if ($stmt) {
            $stmt->bind_param('s', $uname);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($res instanceof mysqli_result)) {
                while ($row = $res->fetch_assoc()) {
                    $pendingActivities[] = _row_to_array($row);
                }
            }
            $stmt->close();
        }
    }

    $pendingCount = count($pendingActivities);

    // 2. Unread notifications
    $unreadNotifications = [];
    $stmt = $db->prepare("SELECT * FROM `notifications` WHERE `user_target` = ? AND `status` = 'unread' ORDER BY `created_at` DESC LIMIT 10");
    if ($stmt) {
        $stmt->bind_param('s', $target);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($res instanceof mysqli_result)) {
            while ($row = $res->fetch_assoc()) {
                $unreadNotifications[] = $row;
            }
        }
        $stmt->close();
    }
    $unreadCount = count($unreadNotifications);

    // Badge count aligns with app.php logic
    $badgeCount = $isAdmin ? (2 + $pendingCount) : (2 + $unreadCount + $pendingCount);

    return [
        'success'               => true,
        'user_type'             => $userType,
        'badge_count'           => $badgeCount,
        'pending_count'         => $pendingCount,
        'unread_count'          => $unreadCount,
        'pending_activities'    => $pendingActivities,
        'unread_notifications'  => $unreadNotifications,
        'latest_pending'        => !empty($pendingActivities) ? $pendingActivities[0] : null,
        'server_time'           => date('Y-m-d H:i:s')
    ];
}

// ==========================================================
// WHATSAPP GATEWAY & CONTACT HELPERS
// ==========================================================

function get_gateway_config(): array {
    $file = __DIR__ . '/../data/gateway_config.json';
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) {
            return array_merge([
                'provider' => 'fonnte',
                'token' => '',
                'auto_send' => false
            ], $data);
        }
    }
    return [
        'provider' => 'fonnte',
        'token' => '',
        'auto_send' => false
    ];
}

function save_gateway_config(array $cfg): bool {
    $file = __DIR__ . '/../data/gateway_config.json';
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return @file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT)) !== false;
}

function update_user_phone(string $nameOrUsername, string $phone): bool {
    $db = get_db();
    if (!$db) return false;

    $nameOrUsername = trim($nameOrUsername);
    $phone = trim($phone);

    // Cari user terlebih dahulu
    $u = get_user_by_username($nameOrUsername);
    if (!$u) {
        $all = get_all_users();
        foreach ($all as $item) {
            if (strcasecmp($item['name'] ?? '', $nameOrUsername) === 0) {
                $u = $item;
                break;
            }
        }
    }
    if (!$u) return false;

    $username = $u['username'];
    $stmt = $db->prepare("UPDATE `users` SET `phone` = ? WHERE `username` = ?");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $phone, $username);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function send_whatsapp_via_gateway(string $phone, string $message, ?string $token = null): array {
    if (!$token) {
        $cfg = get_gateway_config();
        $token = $cfg['token'] ?? '';
    }

    if (empty($token)) {
        return [
            'success' => false,
            'message' => 'Token WhatsApp Gateway belum dikonfigurasi.'
        ];
    }

    // Normalisasi nomor telepon ke format Fonnte (bisa 08... atau 628...)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($cleanPhone, '0') === 0) {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    } elseif (strpos($cleanPhone, '8') === 0) {
        $cleanPhone = '62' . $cleanPhone;
    }

    if (function_exists('curl_init')) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'target' => $cleanPhone,
                'message' => $message,
                'countryCode' => '62',
            ],
            CURLOPT_HTTPHEADER => [
                "Authorization: $token"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $err
            ];
        }

        $resData = json_decode($response, true);
        if (is_array($resData) && !empty($resData['status'])) {
            return [
                'success' => true,
                'message' => 'Pesan WhatsApp berhasil dikirim via Fonnte Gateway.',
                'raw' => $resData
            ];
        } else {
            $reason = $resData['reason'] ?? ($resData['message'] ?? 'Respon gateway gagal.');
            return [
                'success' => false,
                'message' => 'Gateway error: ' . $reason,
                'raw' => $resData
            ];
        }
    }

    return [
        'success' => false,
        'message' => 'Ekstensi PHP cURL tidak aktif di server ini.'
    ];
}

// ==========================================================
// BACKWARD-COMPAT SHIM (agar tidak ada error di file lain)
// ==========================================================

/**
 * Tidak lagi digunakan — dipertahankan agar tidak muncul fatal error
 * jika ada file yang masih memanggil fungsi ini.
 */
function get_store_data(): array  { return []; }
function save_store_data($d): bool { return true; }

