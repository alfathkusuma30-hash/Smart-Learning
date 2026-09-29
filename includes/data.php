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

function authenticate_user(string $username, string $password): array {
    $user = get_user_by_username($username);

    if (!$user) {
        return ['success' => false, 'message' => 'Username tidak ditemukan. Periksa kembali username Anda.'];
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

    $fields = ['name','nrp','dept','email','phone','bio','avatar_color','reminder_freq'];
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
    $notifTplText = $certTpl ? ' (Template Sertifikat terlampir)' : '';
    _insert_notification(
        'admin', 'request',
        'Pengajuan Kegiatan: ' . $uname2,
        $uname2 . ' mengajukan kegiatan baru: "' . $title . '"' . $notifTplText . '. Menunggu persetujuan Anda.',
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
        SET `progress` = 100, `completed_modules` = ?, `current_module` = 'Selesai — Bersertifikat Resmi', `grade` = 100,
            `last_attachment` = COALESCE(?, `last_attachment`), `last_submitted_at` = NOW()
        WHERE `id` = ?");
    if ($stmtUp) {
        $stmtUp->bind_param('iss', $totalMods, $attendanceFile, $act['id']);
        $stmtUp->execute();
        $stmtUp->close();
    }

    // 2. Jika peserta absensi kosong, tambahkan nama pemilik kegiatan
    if (empty($attendees)) {
        $attendees = [$act['user_name']];
    }

    // 3. Terbitkan sertifikat otomatis untuk seluruh peserta yang ada di daftar absensi
    $batchRes = batch_generate_certificates($courseTitle, $attendees, $templateFile, 'PT ANTAM (PERSERO) TBK — Site Pongkor');

    return [
        'success'         => true,
        'message'         => 'Kegiatan selesai! ' . count($batchRes['generated']) . ' sertifikat resmi berhasil diterbitkan otomatis dari daftar absensi peserta.',
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

