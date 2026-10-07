<?php
/**
 * RESTful API Controller
 * Smart Learning Windows — PT ANTAM Tbk
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/includes/data.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi login tidak valid. Silakan login kembali.']);
    exit();
}

$currentUser = $_SESSION['username'];
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'User';

switch ($action) {

    // 1. Employee Adds New Activity (Usulan Kegiatan Baru)
    case 'add_activity':
        if ($userType === 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Admin tidak dapat mengajukan kegiatan belajar untuk diri sendiri. Admin bertugas menerima dan mengonfirmasi laporan/usulan dari pegawai.']);
            exit();
        }

        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Nama kegiatan wajib diisi.']);
            exit();
        }

        $type = isset($_POST['type']) ? trim($_POST['type']) : 'Wajib K3';
        $category = isset($_POST['category']) ? trim($_POST['category']) : 'Operasional Tambang';
        $duration = isset($_POST['duration']) ? trim($_POST['duration']) : '2 Hari (16 Jam)';
        $level = isset($_POST['level']) ? trim($_POST['level']) : 'Basic';
        $deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : date('d M Y', strtotime('+14 days'));
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        $res = add_new_activity([
            'user_username' => $currentUser,
            'user_name' => $_SESSION['user_name'],
            'title' => $title,
            'type' => $type,
            'category' => $category,
            'duration' => $duration,
            'level' => $level,
            'deadline' => $deadline,
            'notes' => $notes,
            'cert_template' => null
        ]);

        echo json_encode($res);
        break;

    // 2. Admin Confirms / Approves Employee Activity
    case 'confirm_activity':
        if ($userType !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya Admin yang dapat mengonfirmasi kegiatan.']);
            exit();
        }

        $activityId = isset($_POST['activity_id']) ? trim($_POST['activity_id']) : '';
        $decision = isset($_POST['decision']) ? trim($_POST['decision']) : 'setujui'; // 'setujui' or 'tolak'
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        if (empty($activityId)) {
            echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
            exit();
        }

        $res = confirm_activity_status($activityId, $decision, $notes);
        echo json_encode($res);
        break;

    // 2b. Admin Assigns Training to Employee
    case 'assign_activity':
        if ($userType !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya Admin yang dapat menugaskan pelatihan.']);
            exit();
        }

        $targetUsername = isset($_POST['target_username']) ? strtolower(trim($_POST['target_username'])) : 'egy';
        $title = isset($_POST['title']) ? trim($_POST['title']) : 'Pelatihan Baru';
        $deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : '14 Hari';

        if ($targetUsername === 'all' || $targetUsername === 'semua') {
            $allUsers = get_all_users();
            $assignedCount = 0;
            foreach ($allUsers as $u) {
                if (($u['type'] ?? '') !== 'Admin') {
                    assign_training_to_user($u['username'], $title, $deadline);
                    $assignedCount++;
                }
            }
            echo json_encode(['success' => true, 'message' => "Pelatihan \"{$title}\" berhasil ditugaskan ke {$assignedCount} pegawai."]);
            exit();
        }

        $res = assign_training_to_user($targetUsername, $title, $deadline);
        echo json_encode($res);
        break;

    // 3. Get Activities
    case 'get_activities':
        $filterUser = ($userType === 'Admin') ? (isset($_GET['username']) ? $_GET['username'] : null) : $currentUser;
        $filterStatus = isset($_GET['status']) ? $_GET['status'] : null;

        $list = get_activities($filterUser, $filterStatus);
        echo json_encode(['success' => true, 'activities' => $list]);
        break;

    // 4. Update Profile
    // 4. Update Profile
    case 'update_profile':
        $profile = [];
        $allowedFields = ['name', 'nrp', 'dept', 'email', 'phone', 'bio', 'avatar_color', 'reminder_freq'];
        foreach ($allowedFields as $f) {
            if (isset($_POST[$f])) {
                $profile[$f] = trim($_POST[$f]);
            }
        }
        if (isset($_POST['wa_notif'])) {
            $profile['wa_notif'] = ($_POST['wa_notif'] === '1' || $_POST['wa_notif'] === 1 || $_POST['wa_notif'] === 'true' || $_POST['wa_notif'] === true);
        }

        // Handle Avatar Image File Upload
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $fileInfo = pathinfo($_FILES['avatar_file']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowedExts, true)) {
                $safeFileName = 'avatar_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $currentUser) . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $safeFileName;
                if (@move_uploaded_file($_FILES['avatar_file']['tmp_name'], $targetPath)) {
                    $profile['avatar_image'] = $safeFileName;
                }
            }
        }

        if (empty($profile) && !isset($_FILES['avatar_file'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada data untuk diperbarui.']);
            exit();
        }

        if (isset($profile['name']) && empty($profile['name'])) {
            echo json_encode(['success' => false, 'message' => 'Nama lengkap wajib diisi.']);
            exit();
        }

        $res = update_user_profile($currentUser, $profile);
        if ($res['success'] && isset($res['user'])) {
            // Update active session values
            if (!empty($res['user']['name'])) {
                $_SESSION['user_name'] = $res['user']['name'];
            }
            if (!empty($res['user']['dept'])) {
                $_SESSION['user_dept'] = $res['user']['dept'];
            }
            if (!empty($res['user']['avatar_color'])) {
                $_SESSION['avatar_color'] = $res['user']['avatar_color'];
            }
            if (isset($res['user']['avatar_image'])) {
                $_SESSION['avatar_image'] = $res['user']['avatar_image'];
            }
            if (!empty($res['user']['role'])) {
                $_SESSION['user_role'] = $res['user']['role'];
            }
        }
        echo json_encode($res);
        break;

    // 5. Update Password
    case 'update_password':
        $oldPass = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
        $newPass = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirmPass = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

        if (empty($oldPass) || empty($newPass)) {
            echo json_encode(['success' => false, 'message' => 'Kata sandi lama dan baru wajib diisi.']);
            exit();
        }

        if (strlen($newPass) < 5) {
            echo json_encode(['success' => false, 'message' => 'Kata sandi baru minimal 5 karakter.']);
            exit();
        }

        if ($newPass !== $confirmPass) {
            echo json_encode(['success' => false, 'message' => 'Konfirmasi kata sandi baru tidak cocok.']);
            exit();
        }

        $res = update_user_password($currentUser, $oldPass, $newPass);
        echo json_encode($res);
        break;

    // 6. Submit Module Worksheet / Uploaded Report
    case 'submit_module_report':
        $activityId     = isset($_POST['activity_id'])   ? trim($_POST['activity_id'])   : '';
        $courseTitle    = isset($_POST['course_title'])  ? trim($_POST['course_title'])  : '';
        $reportText     = isset($_POST['report_text'])   ? trim($_POST['report_text'])   : '';
        $attachmentName = isset($_POST['attachment_name']) ? trim($_POST['attachment_name']) : '';

        // Handle physical file upload if provided
        if (isset($_FILES['module_file']) && $_FILES['module_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $origFileName = basename($_FILES['module_file']['name']);
            $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origFileName);
            $targetPath   = $uploadDir . $safeFileName;
            if (@move_uploaded_file($_FILES['module_file']['tmp_name'], $targetPath)) {
                $attachmentName = $origFileName;
            } else {
                $attachmentName = $origFileName;
            }
        }

        $targetKey = !empty($activityId) ? $activityId : $courseTitle;
        if (empty($targetKey)) {
            echo json_encode(['success' => false, 'message' => 'ID atau judul modul tidak valid.']);
            exit();
        }

        $res = submit_module_report($targetKey, $currentUser, $reportText, $attachmentName);
        echo json_encode($res);
        break;

    // 7. Admin Reviews & Grades a Worksheet Submission
    case 'review_module_report':
        if ($userType !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya Admin yang dapat menilai laporan.']);
            exit();
        }

        $activityId = isset($_POST['activity_id']) ? trim($_POST['activity_id']) : '';
        $score      = isset($_POST['score'])       ? (int)$_POST['score']       : 0;
        $feedback   = isset($_POST['feedback'])    ? trim($_POST['feedback'])    : '';

        if (empty($activityId)) {
            echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
            exit();
        }

        $adminName = $_SESSION['user_name'] ?? 'Learning Ops Admin';
        $res = review_module_report($activityId, $score, $feedback, $adminName);
        echo json_encode($res);
        break;

    // 8. Submit Quiz / Evaluation Result → Issue Certificate if passed
    case 'submit_quiz_result':
        if ($userType === 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Admin tidak mengikuti kuis pegawai.']);
            exit();
        }

        $courseTitle = isset($_POST['course_title']) ? trim($_POST['course_title']) : 'Sertifikasi K3 Umum & Pertambangan';
        $score       = isset($_POST['score'])        ? (int)$_POST['score']        : 0;

        if ($score < 0 || $score > 100) {
            echo json_encode(['success' => false, 'message' => 'Nilai tidak valid (harus 0–100).']);
            exit();
        }

        $res = submit_quiz_result($currentUser, $courseTitle, $score);
        echo json_encode($res);
        break;

    // Sederhana: Selesaikan pelatihan & klaim sertifikat resmi langsung
    case 'claim_certificate':
        $activityId  = isset($_POST['activity_id']) ? trim($_POST['activity_id']) : '';
        $courseTitle = isset($_POST['course_title']) ? trim($_POST['course_title']) : '';
        $targetId    = !empty($activityId) ? $activityId : $courseTitle;

        if (empty($targetId)) {
            echo json_encode(['success' => false, 'message' => 'ID atau judul kegiatan tidak valid.']);
            exit();
        }

        $res = claim_activity_certificate($targetId, $currentUser);
        echo json_encode($res);
        break;

    // 9. Upload External Certificate
    case 'upload_external_certificate':
        $title      = isset($_POST['title']) ? trim($_POST['title']) : '';
        $regNo      = isset($_POST['reg_no']) ? trim($_POST['reg_no']) : '';
        $issuedBy   = isset($_POST['issued_by']) ? trim($_POST['issued_by']) : 'Lembaga Sertifikasi Eksternal';
        $validUntil = isset($_POST['valid_until']) ? trim($_POST['valid_until']) : date('Y-m-d', strtotime('+3 years'));
        $score      = isset($_POST['score']) ? (int)$_POST['score'] : 100;

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Nama/judul sertifikat wajib diisi.']);
            exit();
        }

        // Handle physical file upload if provided
        $attachmentName = '';
        if (isset($_FILES['cert_file']) && $_FILES['cert_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $origFileName = basename($_FILES['cert_file']['name']);
            $safeFileName = 'cert_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origFileName);
            $targetPath   = $uploadDir . $safeFileName;
            if (@move_uploaded_file($_FILES['cert_file']['tmp_name'], $targetPath)) {
                $attachmentName = $safeFileName;
            }
        }

        $res = save_external_certificate($currentUser, $title, $regNo, $issuedBy, $validUntil, $score, $attachmentName);
        echo json_encode($res);
        break;

    // 10. Upload Template Sertifikat & Generate Batch untuk Peserta Kegiatan
    case 'batch_generate_certificates':
        $courseTitle = isset($_POST['course_title']) ? trim($_POST['course_title']) : '';
        $issuer      = isset($_POST['issued_by']) ? trim($_POST['issued_by']) : 'PT ANTAM (PERSERO) TBK';

        // Parse participants list (array JSON atau comma/newline separated)
        $rawParticipants = isset($_POST['participants']) ? $_POST['participants'] : [];
        if (is_string($rawParticipants)) {
            $decoded = json_decode($rawParticipants, true);
            if (is_array($decoded)) {
                $rawParticipants = $decoded;
            } else {
                $rawParticipants = preg_split('/[\r\n,]+/', $rawParticipants);
            }
        }
        $participants = array_filter(array_map('trim', (array)$rawParticipants));

        if (empty($courseTitle)) {
            echo json_encode(['success' => false, 'message' => 'Judul kegiatan pelatihan wajib diisi.']);
            exit();
        }
        if (empty($participants)) {
            echo json_encode(['success' => false, 'message' => 'Pilih atau masukkan minimal 1 nama peserta kegiatan.']);
            exit();
        }

        // Handle template file upload if uploaded
        $templateFileName = null;
        if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/templates/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $origFileName     = basename($_FILES['template_file']['name']);
            $templateFileName = 'tpl_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origFileName);
            $targetPath       = $uploadDir . $templateFileName;
            if (!@move_uploaded_file($_FILES['template_file']['tmp_name'], $targetPath)) {
                $templateFileName = null;
            }
        }

        $res = batch_generate_certificates($courseTitle, $participants, $templateFileName, $issuer);
        echo json_encode($res);
        break;

    // 10b. Selesaikan Kegiatan dengan Upload Berkas Absensi & Auto-Generate Sertifikat
    case 'complete_activity_with_attendance':
        $activityId = isset($_POST['activity_id']) ? trim($_POST['activity_id']) : '';
        if (empty($activityId)) {
            echo json_encode(['success' => false, 'message' => 'ID kegiatan tidak valid.']);
            exit();
        }

        // Parse attendees list
        $rawAttendees = isset($_POST['attendees']) ? $_POST['attendees'] : [];
        if (is_string($rawAttendees)) {
            $decoded = json_decode($rawAttendees, true);
            if (is_array($decoded)) {
                $rawAttendees = $decoded;
            } else {
                $rawAttendees = preg_split('/[\r\n,]+/', $rawAttendees);
            }
        }
        $attendees = array_filter(array_map('trim', (array)$rawAttendees));

        // Handle attendance file upload
        $attendanceFileName = null;
        if (isset($_FILES['attendance_file']) && $_FILES['attendance_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/attendance/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $origFileName       = basename($_FILES['attendance_file']['name']);
            $attendanceFileName = 'absen_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origFileName);
            $targetPath         = $uploadDir . $attendanceFileName;
            if (!@move_uploaded_file($_FILES['attendance_file']['tmp_name'], $targetPath)) {
                $attendanceFileName = null;
            }
        }

        $res = complete_activity_with_attendance($activityId, $attendees, $attendanceFileName, $currentUser);
        echo json_encode($res);
        break;

    // 11. Get User Certificates
    case 'get_certificates':
        $targetUser = ($userType === 'Admin') ? (isset($_GET['username']) ? $_GET['username'] : '') : $currentUser;
        if (empty($targetUser)) {
            echo json_encode(['success' => false, 'message' => 'Username tidak valid.']);
            exit();
        }
        $certs = get_user_certificates($targetUser);
        echo json_encode(['success' => true, 'certificates' => $certs]);
        break;

    // 10. Mark Notifications as Read
    case 'mark_notifs_read':
        mark_all_notifications_read($currentUser, $userType);
        echo json_encode(['success' => true]);
        break;

    // 11. Resolve / Dismiss Single Notification
    case 'resolve_notif':
        $notifId = isset($_POST['notif_id']) ? trim($_POST['notif_id']) : '';
        if (!empty($notifId)) {
            resolve_notification($notifId);
        }
        echo json_encode(['success' => true]);
        break;

    // 12. Real-time Notification & Activity Polling
    case 'check_notifications':
        $res = check_realtime_notifications($currentUser, $userType);
        echo json_encode($res);
        break;

    // 13. Send WhatsApp Nudge (via Gateway or direct WhatsApp Web)
    case 'send_whatsapp_nudge':
        $employeeName = isset($_POST['employee_name']) ? trim($_POST['employee_name']) : '';
        $courseName   = isset($_POST['course_name']) ? trim($_POST['course_name']) : 'Pelatihan K3 Wajib';
        $phone        = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $savePhone    = !empty($_POST['save_phone']) && ($_POST['save_phone'] === '1' || $_POST['save_phone'] === 'true');

        if (empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Nomor WhatsApp penerima wajib diisi.']);
            exit();
        }

        // Simpan nomor ke profil user jika diminta
        if ($savePhone && !empty($employeeName)) {
            update_user_phone($employeeName, $phone);
        }

        // Format nomor untuk WhatsApp (standar internasional 62...)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strpos($cleanPhone, '0') === 0) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (strpos($cleanPhone, '8') === 0) {
            $cleanPhone = '62' . $cleanPhone;
        }

        // Format pesan resmi bot korporat
        $messageText = "*[PT ANTAM — SMART LEARNING ALARM]*\n"
                     . "Yth. Sdr. *" . $employeeName . "*,\n\n"
                     . "Modul wajib: _\"" . $courseName . "\"_ telah mencapai batas waktu. Harap segera mengakses portal Smart Learning sebelum akses site dibekukan.\n\n"
                     . "Tautan: https://learning.antam.com/auth?token=auth" . rand(1000, 9999) . "\n"
                     . "_— Learning & Development_";

        $waUrl = "https://api.whatsapp.com/send?phone=" . $cleanPhone . "&text=" . rawurlencode($messageText);

        $cfg = get_gateway_config();
        $token = $cfg['token'] ?? '';
        $autoSend = !empty($cfg['auto_send']);

        // Cek jika gateway token terpasang dan auto_send aktif
        if (!empty($token) && $autoSend) {
            $gatewayRes = send_whatsapp_via_gateway($cleanPhone, $messageText, $token);
            if ($gatewayRes['success']) {
                echo json_encode([
                    'success' => true,
                    'mode' => 'gateway',
                    'phone' => $cleanPhone,
                    'message' => 'Pesan otomatis berhasil dikirim ke ' . $cleanPhone . ' melalui WhatsApp Gateway Bot ANTAM.'
                ]);
                exit();
            }
        }

        // Default atau fallback: kembalikan URL direct WhatsApp Web/App
        echo json_encode([
            'success' => true,
            'mode' => 'direct',
            'phone' => $cleanPhone,
            'wa_url' => $waUrl,
            'message' => 'Pesan disiapkan untuk dikirim ke ' . $cleanPhone
        ]);
        break;

    // 14. Save Gateway Config
    case 'save_gateway_config':
        if ($userType !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya Admin yang dapat mengubah konfigurasi gateway.']);
            exit();
        }
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $provider = isset($_POST['provider']) ? trim($_POST['provider']) : 'fonnte';
        $autoSend = !empty($_POST['auto_send']) && ($_POST['auto_send'] === '1' || $_POST['auto_send'] === 'true');

        $ok = save_gateway_config([
            'provider' => $provider,
            'token' => $token,
            'auto_send' => $autoSend,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Konfigurasi WhatsApp Gateway berhasil disimpan.' : 'Gagal menyimpan konfigurasi.'
        ]);
        break;

    // 15. Get Gateway Config
    case 'get_gateway_config':
        $cfg = get_gateway_config();
        echo json_encode(['success' => true, 'config' => $cfg]);
        break;

    // 16. Admin Adds New Course to Catalog Master
    case 'add_catalog_course':
        if ($userType !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya Admin yang dapat menambahkan modul master.']);
            exit();
        }

        $code     = isset($_POST['code'])     ? trim($_POST['code'])     : 'TRC-NEW';
        $title    = isset($_POST['title'])    ? trim($_POST['title'])    : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : 'GENERAL';
        $type     = isset($_POST['type'])     ? trim($_POST['type'])     : 'Inhouse';
        $duration = isset($_POST['duration']) ? trim($_POST['duration']) : '2 Hari (16 Jam)';
        $level    = isset($_POST['level'])    ? trim($_POST['level'])    : 'Basic';
        $desc     = isset($_POST['desc'])     ? trim($_POST['desc'])     : '';

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Nama modul pelatihan wajib diisi.']);
            exit();
        }

        $res = add_catalog_course([
            'code'     => $code,
            'title'    => $title,
            'category' => $category,
            'type'     => $type,
            'duration' => $duration,
            'level'    => $level,
            'desc'     => $desc
        ]);
        echo json_encode($res);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi API tidak dikenali.']);
        break;
}
