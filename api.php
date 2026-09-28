<?php
/**
 * RESTful API Controller
 * Smart Learning Windows — PT ANTAM Tbk
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

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
            'notes' => $notes
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
    case 'update_profile':
        $profile = [
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
            'nrp' => isset($_POST['nrp']) ? trim($_POST['nrp']) : '',
            'dept' => isset($_POST['dept']) ? trim($_POST['dept']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
            'bio' => isset($_POST['bio']) ? trim($_POST['bio']) : '',
            'avatar_color' => isset($_POST['avatar_color']) ? trim($_POST['avatar_color']) : '',
            'reminder_freq' => isset($_POST['reminder_freq']) ? trim($_POST['reminder_freq']) : '',
            'wa_notif' => isset($_POST['wa_notif']) ? ($_POST['wa_notif'] == '1') : true
        ];

        $res = update_user_profile($currentUser, $profile);
        if ($res['success']) {
            // Update active session values
            $_SESSION['user_name'] = $res['user']['name'];
            $_SESSION['user_dept'] = $res['user']['dept'];
            if (!empty($res['user']['avatar_color'])) {
                $_SESSION['avatar_color'] = $res['user']['avatar_color'];
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

    // 9. Get User Certificates
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

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi API tidak dikenali.']);
        break;
}
