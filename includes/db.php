<?php
/**
 * Database Connection — MySQLi Singleton
 * Smart Learning — PT ANTAM Tbk
 *
 * Konfigurasi koneksi MySQL. Ubah DB_PASS jika MySQL Anda menggunakan password.
 */

// --- AUTO-DETECT ENVIRONMENT ---
$is_localhost = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $is_localhost = true;
    }
} else {
    // Fallback for CLI or unknown
    $is_localhost = true;
}

if ($is_localhost) {
    // --- KONFIGURASI LOCALHOST (XAMPP) ---
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');          // Kosongkan jika XAMPP default (tanpa password)
    define('DB_NAME', 'smart_learning');
    define('DB_PORT', 3306);
} else {
    // --- KONFIGURASI HOSTING (InfinityFree) ---
    define('DB_HOST', 'sql111.infinityfree.com');
    define('DB_USER', 'if0_42854978');
    // PENTING: Ganti dengan 'vPanel Account Password' dari InfinityFree Client Area (bukan password email/login)
    define('DB_PASS', 'alfath2005');
    define('DB_NAME', 'if0_42854978_smart_learning');
    define('DB_PORT', 3306);
}

function _auto_init_tables_if_needed(mysqli $conn): void {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    $requiredTables = ['users', 'activities', 'certificates', 'lna_catalog', 'lna_catalog_tags', 'notifications'];
    $missingTables = [];

    foreach ($requiredTables as $tbl) {
        $check = $conn->query("SHOW TABLES LIKE '{$tbl}'");
        if (!$check || $check->num_rows === 0) {
            $missingTables[] = $tbl;
        }
    }

    if (empty($missingTables)) {
        return; // Semua tabel sudah lengkap
    }

    // Jika seluruh tabel hilang dan file SQL tersedia, jalankan multi_query dari file SQL
    $sqlFile = __DIR__ . '/../smart_learning.sql';
    if (count($missingTables) === count($requiredTables) && file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);
        if ($sqlContent && $conn->multi_query($sqlContent)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            return;
        }
    }

    // Buat tabel yang hilang secara spesifik (agar tidak merusak data tabel lain yang sudah ada)
    if (in_array('users', $missingTables, true)) {
        $conn->query("CREATE TABLE IF NOT EXISTS `users` (
          `username` VARCHAR(50) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `user_id` VARCHAR(20) DEFAULT NULL,
          `nrp` VARCHAR(50) DEFAULT NULL,
          `name` VARCHAR(100) NOT NULL,
          `role` VARCHAR(100) DEFAULT NULL,
          `type` ENUM('User','Admin') NOT NULL DEFAULT 'User',
          `dept` VARCHAR(100) DEFAULT NULL,
          `email` VARCHAR(100) DEFAULT NULL,
          `phone` VARCHAR(50) DEFAULT NULL,
          `avatar_color` VARCHAR(20) DEFAULT '#0f766e',
          `bio` TEXT DEFAULT NULL,
          `reminder_freq` VARCHAR(50) DEFAULT '24 Jam',
          `wa_notif` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT IGNORE INTO `users` (`username`, `password`, `user_id`, `nrp`, `name`, `role`, `type`, `dept`, `email`, `phone`, `avatar_color`, `bio`, `reminder_freq`, `wa_notif`) VALUES
        ('egy', 'password123', 'EP', 'ANTAM-08912', 'Egy Pratama', 'Operasional Tambang', 'User', 'Mining Operations Underground', 'egy.pratama@antam.com', '0812-3456-7890', '#d4af37', 'Operator Tambang Bawah Tanah Site Pongkor. Fokus pada pemenuhan K3 dan standar operasional ESDM.', '24 Jam', 1),
        ('budi', 'password123', 'BS', 'ANTAM-07844', 'Budi Santoso', 'Processing Plant', 'User', 'Plant & Smelter', 'budi.santoso@antam.com', '0813-9876-5432', '#38bdf8', 'Teknisi Pengolahan Emas & Logam Mulia. Pengawas K3 Pabrik.', '12 Jam', 1),
        ('andi', 'password123', 'AW', 'ANTAM-06519', 'Andi Wijaya', 'Logistik & Gudang', 'User', 'Supply Chain & Bahan Peledak', 'andi.wijaya@antam.com', '0811-2233-4455', '#a855f7', 'Koordinator Pergudangan dan Distribusi Handak Site Pongkor.', '24 Jam', 0),
        ('admin', 'admin123', 'LO', 'ANTAM-HC001', 'Learning Ops Admin', 'Learning & Development', 'Admin', 'Human Capital & Corporate University', 'admin.lnd@antam.com', '0811-9988-7766', '#10b981', 'Super Administrator Sistem Pembelajaran & Evaluasi Kepatuhan K3 Terintegrasi PT ANTAM Tbk.', 'Realtime', 1)");
    }

    if (in_array('activities', $missingTables, true)) {
        $conn->query("CREATE TABLE IF NOT EXISTS `activities` (
          `id` VARCHAR(50) NOT NULL,
          `user_username` VARCHAR(50) NOT NULL,
          `user_name` VARCHAR(100) NOT NULL,
          `title` VARCHAR(255) NOT NULL,
          `type` VARCHAR(50) DEFAULT 'Pengembangan Mandiri',
          `category` VARCHAR(100) DEFAULT 'Operasional Tambang',
          `duration` VARCHAR(50) DEFAULT '2 Hari (16 Jam)',
          `level` VARCHAR(50) DEFAULT 'Basic',
          `deadline` VARCHAR(50) DEFAULT NULL,
          `status` ENUM('menunggu_konfirmasi','disetujui','ditolak') NOT NULL DEFAULT 'menunggu_konfirmasi',
          `progress` INT NOT NULL DEFAULT 0,
          `current_module` VARCHAR(255) DEFAULT 'Menunggu Konfirmasi Admin',
          `total_modules` INT NOT NULL DEFAULT 4,
          `completed_modules` INT NOT NULL DEFAULT 0,
          `notes` TEXT DEFAULT NULL,
          `admin_notes` TEXT DEFAULT NULL,
          `last_report` TEXT DEFAULT NULL,
          `last_attachment` VARCHAR(255) DEFAULT NULL,
          `last_submitted_at` DATETIME DEFAULT NULL,
          `grade` INT DEFAULT NULL,
          `admin_feedback` TEXT DEFAULT NULL,
          `graded_at` DATETIME DEFAULT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `approved_at` DATETIME DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_user_username` (`user_username`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT IGNORE INTO `activities` (`id`, `user_username`, `user_name`, `title`, `type`, `category`, `duration`, `level`, `deadline`, `status`, `progress`, `current_module`, `total_modules`, `completed_modules`, `notes`, `created_at`, `approved_at`) VALUES
        ('act_1', 'egy', 'Egy Pratama', 'Safety Induction Site Tambang Pongkor', 'Wajib K3', 'Keselamatan Kerja', '1 Hari (8 Jam)', 'Basic', '12 Agu 2026', 'disetujui', 60, 'Modul 4: Penanganan Bahaya Longsor & Rock Burst', 5, 3, 'Pelatihan wajib kepatuhan ESDM sebelum memasuki terowongan utama.', '2026-08-01 08:00:00', '2026-08-01 09:00:00'),
        ('act_2', 'egy', 'Egy Pratama', 'POP Level 1 — Pengawas Operasional Pertama', 'Wajib ESDM', 'Regulasi ESDM', '3 Hari (24 Jam)', 'Basic', '14 Agu 2026', 'disetujui', 30, 'Unit 3: Pelaksanaan Inspeksi & Identifikasi Bahaya', 6, 2, 'Sertifikasi pengawas operasional lapangan wajib perpanjangan.', '2026-08-05 10:00:00', '2026-08-05 11:30:00'),
        ('act_3', 'budi', 'Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', 'Sertifikasi', 'K3 Nasional', '5 Hari (40 Jam)', 'Advanced', '17 Agu 2026', 'disetujui', 94, 'Selesai - Menunggu Penerbitan Lisensi Resmi', 5, 5, 'Lulus ujian kuis online dengan nilai 94/100.', '2026-08-02 09:00:00', '2026-08-02 10:00:00')");
    }

    if (in_array('certificates', $missingTables, true)) {
        $conn->query("CREATE TABLE IF NOT EXISTS `certificates` (
          `id` VARCHAR(50) NOT NULL,
          `user_username` VARCHAR(50) NOT NULL,
          `user_name` VARCHAR(100) NOT NULL,
          `course_title` VARCHAR(255) NOT NULL,
          `reg_no` VARCHAR(100) NOT NULL,
          `score` INT NOT NULL DEFAULT 80,
          `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `valid_until` DATE NOT NULL,
          `issued_by` VARCHAR(100) NOT NULL DEFAULT 'Learning Ops Admin',
          `status` VARCHAR(50) NOT NULL DEFAULT 'active',
          PRIMARY KEY (`id`),
          KEY `idx_cert_username` (`user_username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT IGNORE INTO `certificates` (`id`, `user_username`, `user_name`, `course_title`, `reg_no`, `score`, `issued_at`, `valid_until`, `issued_by`, `status`) VALUES
        ('cert_initial_budi', 'budi', 'Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', 'ANTAM/K3U/2026/8842', 94, '2026-08-11 16:45:00', '2029-08-11', 'Learning Ops Admin', 'active')");
    }

    if (in_array('lna_catalog', $missingTables, true)) {
        $conn->query("CREATE TABLE IF NOT EXISTS `lna_catalog` (
          `id` VARCHAR(50) NOT NULL,
          `title` VARCHAR(255) NOT NULL,
          `code` VARCHAR(50) NOT NULL,
          `category` VARCHAR(100) NOT NULL,
          `type` VARCHAR(50) NOT NULL,
          `duration` VARCHAR(50) NOT NULL,
          `level` VARCHAR(50) NOT NULL,
          `gap_score` VARCHAR(100) DEFAULT NULL,
          `target_role` VARCHAR(255) DEFAULT NULL,
          `competency_area` VARCHAR(255) DEFAULT NULL,
          `description` TEXT DEFAULT NULL,
          `prerequisites` TEXT DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT IGNORE INTO `lna_catalog` (`id`, `title`, `code`, `category`, `type`, `duration`, `level`, `gap_score`, `target_role`, `competency_area`, `description`, `prerequisites`) VALUES
        ('lna_1', 'POP Level 1 — Pengawas Operasional Pertama', 'ESDM-POP-01', 'Kepatuhan ESDM', 'Wajib', '3 Hari (24 Jam)', 'Basic', 'Gap Tinggi (Prioritas 1)', 'Semua Foreman & Pengawas Tambang', 'Regulasi ESDM No. 1827', 'Standar kompetensi pengawas operasional pertambangan mineral dan batubara. Membekali pengawas dalam inspeksi bahaya bawah tanah, K3, dan kepatuhan hukum.', 'Pengalaman minimal 1 tahun di site'),
        ('lna_2', 'Safety Induction Site Tambang Pongkor', 'ANTAM-SAF-02', 'K3 & Keselamatan', 'Wajib', '1 Hari (8 Jam)', 'Basic', 'Kritis (Izin Masuk Site)', 'Seluruh Pegawai Site', 'Protokol Underground K3', 'Pengenalan komprehensif bahaya bawah tanah, prosedur evakuasi darurat, deteksi gas metana/CO, penggunaan respirator SCSR, dan standar kelayakan APD.', 'Medical Check Up Fit to Work'),
        ('lna_3', 'Sertifikasi K3 Umum & Pertambangan', 'KEMNAKER-K3U-03', 'Sertifikasi Nasional', 'Sertifikasi', '5 Hari (40 Jam)', 'Advanced', 'Gap Sedang (Lisensi)', 'Officer, Supervisor & K3 Team', 'Manajemen Risiko K3 Korporat', 'Program sertifikasi lisensi resmi pemenuhan syarat regulasi Kemnaker & ESDM. Dilengkapi uji materi hukum K3, inspeksi praktis, dan investigasi insiden.', 'Pendidikan min. D3 / S1 dengan pengalaman'),
        ('lna_4', 'Technical Skill A — Heavy Hauling 777D', 'TECH-OPR-04', 'Teknik Operasional', 'Wajib', '4 Hari (32 Jam)', 'Advanced', 'Gap Spesialis (Hauling)', 'Operator Alat Berat & Hauler', 'Operasional Alat Tambang', 'Manuver tanjakan ekstrem, inspeksi rem retarder hidrolik, penanganan blindspot, dan keselamatan loading point di pit tambang.', 'SIM B2 Umum & SIO Alat Berat'),
        ('lna_5', 'Leadership & Supervisory Underground', 'LD-SUP-05', 'Leadership & Manajemen', 'Optional', '2 Hari (16 Jam)', 'Intermediate', 'Pengembangan Mandiri', 'Team Leader & Asisten Foreman', 'Manajemen Regu Kerja', 'Pembekalan kepemimpinan regu kerja di terowongan bawah tanah, komunikasi radio darurat, manajemen konflik shif, dan coaching keselamatan berkala.', 'Pengalaman memimpin minimal 6 bulan'),
        ('lna_6', 'Effective Radio Communication & Mine Dispatch', 'COMM-DSP-06', 'Komunikasi & Logistik', 'Optional', '2 Hari (12 Jam)', 'Basic', 'Standardisasi Komunikasi', 'Dispatch, Logistik, Operator', 'Protokol Komunikasi Radio', 'Kode fonetik standar keselamatan, alokasi channel radio darurat underground, koordinasi dispatch armada, dan etika komunikasi radio tambang.', 'Semua personel lapangan')");
    }

    if (in_array('lna_catalog_tags', $missingTables, true)) {
        $conn->query("CREATE TABLE IF NOT EXISTS `lna_catalog_tags` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `catalog_id` VARCHAR(50) NOT NULL,
          `tag` VARCHAR(50) NOT NULL,
          KEY `idx_cat_id` (`catalog_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT IGNORE INTO `lna_catalog_tags` (`catalog_id`, `tag`) VALUES
        ('lna_1', 'Wajib'), ('lna_1', 'ESDM'), ('lna_1', 'K3 Tambang'),
        ('lna_2', 'Wajib'), ('lna_2', 'Underground'), ('lna_2', 'K3 Dasar'),
        ('lna_3', 'Sertifikasi'), ('lna_3', 'Lisensi'), ('lna_3', 'Kemnaker'),
        ('lna_4', 'Teknik'), ('lna_4', 'Alat Berat'), ('lna_4', 'Operasional'),
        ('lna_5', 'Leadership'), ('lna_5', 'Manajemen'), ('lna_5', 'Soft Skill'),
        ('lna_6', 'Komunikasi'), ('lna_6', 'Dispatch'), ('lna_6', 'Protokol')");
    }

    if (in_array('notifications', $missingTables, true)) {
        $conn->query("CREATE TABLE IF NOT EXISTS `notifications` (
          `id` VARCHAR(50) NOT NULL,
          `user_target` VARCHAR(50) NOT NULL,
          `type` VARCHAR(50) NOT NULL,
          `title` VARCHAR(255) NOT NULL,
          `message` TEXT NOT NULL,
          `related_user` VARCHAR(100) DEFAULT NULL,
          `related_course` VARCHAR(255) DEFAULT NULL,
          `activity_id` VARCHAR(50) DEFAULT NULL,
          `status` VARCHAR(20) NOT NULL DEFAULT 'unread',
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_notif_target` (`user_target`),
          KEY `idx_notif_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT IGNORE INTO `notifications` (`id`, `user_target`, `type`, `title`, `message`, `related_user`, `related_course`, `activity_id`, `status`, `created_at`) VALUES
        ('notif_1', 'admin', 'escalation', 'Eskalasi Level 1: Egy Pratama', 'Safety Induction Overdue (+1 Hari). Izin masuk site underground terancam dibekukan.', 'Egy Pratama', 'Safety Induction Site Tambang Pongkor', NULL, 'unread', '2026-08-12 08:30:00'),
        ('notif_2', 'admin', 'verification', 'Verifikasi Ujian: Budi Santoso', 'Budi Santoso menyelesaikan evaluasi Sertifikasi K3 Umum dengan skor 94/100.', 'Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', NULL, 'unread', '2026-08-11 16:45:00'),
        ('notif_3', 'admin', 'request', 'Pengajuan Modul: Andi Wijaya', 'Andi Wijaya mengajukan penugasan modul Technical Skill A (Hauling Truck 777D).', 'Andi Wijaya', 'Technical Skill A — Heavy Hauling 777D', NULL, 'unread', '2026-08-10 11:20:00'),
        ('notif_4', 'egy', 'approval', 'Kegiatan Dikonfirmasi: Safety Induction Site Tambang Pongkor', 'Admin telah menyetujui kegiatan Safety Induction Site Tambang Pongkor. Silakan lanjutkan modul pembelajaran.', 'Learning Ops Admin', 'Safety Induction Site Tambang Pongkor', 'act_1', 'unread', '2026-08-01 09:00:00')");
    }
}

function get_db(): ?mysqli {
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conn->connect_errno) {
            // Auto-create database on localhost jika belum ada (errno 1049)
            if ($conn->connect_errno === 1049) {
                $serverConn = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
                if (!$serverConn->connect_errno) {
                    $serverConn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $serverConn->close();
                    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                }
            }
        }

        if ($conn->connect_error) {
            // JANGAN gunakan http_response_code(500) agar server InfinityFree tidak memblokir dengan layar error 500
            $errMsg = htmlspecialchars($conn->connect_error);
            $dbUser = htmlspecialchars(DB_USER);
            $dbHost = htmlspecialchars(DB_HOST);
            $dbName = htmlspecialchars(DB_NAME);

            die("<!DOCTYPE html>
            <html lang='id'>
            <head>
                <meta charset='UTF-8'>
                <title>Koneksi Database Gagal — Smart Learning</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; display: flex; justify-content: center; align-items: center; min-height: 80vh; margin: 0; }
                    .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 30px; max-width: 600px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                    h2 { color: #f87171; margin-top: 0; display: flex; align-items: center; gap: 10px; }
                    .code-box { background: #0f172a; border-radius: 8px; padding: 12px 16px; font-family: monospace; color: #fbbf24; margin: 15px 0; word-break: break-all; }
                    ul { padding-left: 20px; line-height: 1.6; color: #cbd5e1; }
                    li strong { color: #38bdf8; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <h2>⚠️ Koneksi Database Gagal</h2>
                    <p>Sistem tidak dapat terhubung ke server MySQL di hosting:</p>
                    <div class='code-box'>Error: {$errMsg}</div>
                    <ul>
                        <li><strong>Host:</strong> {$dbHost}</li>
                        <li><strong>User:</strong> {$dbUser}</li>
                        <li><strong>Database:</strong> {$dbName}</li>
                    </ul>
                    <p><strong>Cara Mengatasi:</strong></p>
                    <ul>
                        <li>Pastikan <strong>DB_PASS</strong> di file <code>includes/db.php</code> diisi dengan <em>vPanel Account Password</em> dari InfinityFree Client Area (bukan password email/login).</li>
                        <li>Pastikan database <code>{$dbName}</code> sudah dibuat di menu <strong>MySQL Databases</strong> pada Control Panel InfinityFree.</li>
                    </ul>
                </div>
            </body>
            </html>");
        }

        $conn->set_charset('utf8mb4');
        _auto_init_tables_if_needed($conn);
    }

    return $conn;
}
