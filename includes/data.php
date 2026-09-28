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
        if (!isset($row['name']) && isset($row['nama'])) {
            $row['name'] = $row['nama'];
        }
        if (!isset($row['dept']) && isset($row['unit_kerja'])) {
            $row['dept'] = $row['unit_kerja'];
        }
        if (!isset($row['type'])) {
            $roleLower = strtolower($row['role'] ?? '');
            $row['type'] = ($roleLower === 'admin') ? 'Admin' : 'User';
        }
        if (!isset($row['user_id'])) {
            $row['user_id'] = strtoupper(substr($row['username'], 0, 2));
        }
        if (!isset($row['avatar_color'])) {
            $colors = ['admin' => '#10b981', 'egy' => '#d4af37', 'budi' => '#38bdf8', 'andi' => '#a855f7'];
            $row['avatar_color'] = $colors[$row['username']] ?? '#0f766e';
        }
        if (empty($row['role']) || $row['role'] === 'user') {
            $roles = [
                'egy' => 'Operasional Tambang',
                'budi' => 'Processing Plant',
                'andi' => 'Logistik & Gudang',
                'admin' => 'Learning & Development'
            ];
            $row['role'] = $roles[$row['username']] ?? ($row['dept'] ?? 'Operasional Tambang');
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
    $key = strtolower(trim($username));

    $user = get_user_by_username($key);
    if (!$user) {
        return ['success' => false, 'message' => 'User tidak ditemukan.'];
    }

    $fields = ['name','nrp','dept','email','phone','bio','avatar_color','reminder_freq'];
    $sets   = [];
    $vals   = [];
    $types  = '';

    foreach ($fields as $f) {
        if (isset($profileData[$f])) {
            $sets[]  = "`{$f}` = ?";
            $vals[]  = trim($profileData[$f]);
            $types  .= 's';
        }
    }
    if (isset($profileData['wa_notif'])) {
        $sets[]  = '`wa_notif` = ?';
        $vals[]  = (int)(bool)$profileData['wa_notif'];
        $types  .= 'i';
    }

    if (empty($sets)) {
        return ['success' => false, 'message' => 'Tidak ada data yang diperbarui.'];
    }

    $vals[]  = $key;
    $types  .= 's';

    $sql  = 'UPDATE `users` SET ' . implode(', ', $sets) . ' WHERE `username` = ?';
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt->close();
    }

    return ['success' => true, 'user' => get_user_by_username($key)];
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
        return ['success' => false, 'message' => 'Kata sandi saat ini tidak sesuai.'];
    }
    if (strlen($newPassword) < 5) {
        return ['success' => false, 'message' => 'Kata sandi baru minimal 5 karakter.'];
    }

    $db   = get_db();
    if (!$db) return ['success' => false, 'message' => 'Database tidak terhubung.'];
    $key  = strtolower(trim($username));
    $stmt = $db->prepare("UPDATE `users` SET `password` = ? WHERE `username` = ?");
    if ($stmt) {
        $stmt->bind_param('ss', $newPassword, $key);
        $stmt->execute();
        $stmt->close();
    }

    return ['success' => true, 'message' => 'Kata sandi berhasil diperbarui.'];
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

    $stmt = $db->prepare("INSERT INTO `activities`
        (`id`,`user_username`,`user_name`,`title`,`type`,`category`,`duration`,`level`,`deadline`,
         `status`,`progress`,`current_module`,`total_modules`,`completed_modules`,`notes`,`created_at`)
        VALUES (?,?,?,?,?,?,?,?,?,'menunggu_konfirmasi',0,'Menunggu Konfirmasi Admin',?,0,?,NOW())");
    if ($stmt) {
        $stmt->bind_param('sssssssssis', $newId,$uname,$uname2,$title,$type,$category,$duration,$level,$deadline,$totMod,$notes);
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
        $uname2 . ' mengajukan kegiatan baru: "' . $title . '". Menunggu konfirmasi Anda.',
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
// BACKWARD-COMPAT SHIM (agar tidak ada error di file lain)
// ==========================================================

/**
 * Tidak lagi digunakan — dipertahankan agar tidak muncul fatal error
 * jika ada file yang masih memanggil fungsi ini.
 */
function get_store_data(): array  { return []; }
function save_store_data($d): bool { return true; }
