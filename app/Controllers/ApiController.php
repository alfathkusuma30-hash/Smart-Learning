<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\CertificateModel;
use App\Models\LnaModel;
use App\Models\NotificationModel;

class ApiController extends BaseController
{
    protected UserModel $userModel;
    protected ActivityModel $activityModel;
    protected CertificateModel $certificateModel;
    protected LnaModel $lnaModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityModel = new ActivityModel();
        $this->certificateModel = new CertificateModel();
        $this->lnaModel = new LnaModel();
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Router API Utama untuk melayani semua request AJAX
     */
    public function index()
    {
        $session = session();
        $currentUser = $session->get('username') ?? '';
        $userType    = $session->get('user_type') ?? 'User';
        $userName    = $session->get('user_name') ?? $currentUser;

        if (empty($currentUser)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sesi login tidak valid.']);
        }

        $action = $this->request->getPost('action') ?? $this->request->getGet('action') ?? '';

        switch ($action) {
            case 'add_activity':
            case 'apply_activity':
                return $this->applyActivity($currentUser, $userName);

            case 'confirm_activity':
                $actDecision = $this->request->getPost('action_type') ?? $this->request->getPost('decision') ?? 'setujui';
                if ($actDecision === 'tolak' || $actDecision === 'reject') {
                    return $this->rejectActivity($currentUser, $userType);
                } else {
                    return $this->approveActivity($currentUser, $userType);
                }

            case 'approve_activity':
                return $this->approveActivity($currentUser, $userType);

            case 'reject_activity':
                return $this->rejectActivity($currentUser, $userType);

            case 'assign_activity':
            case 'assign_training':
                return $this->assignTraining($currentUser, $userType);

            case 'claim_certificate':
                return $this->claimCertificate($currentUser, $userName);

            case 'submit_quiz':
            case 'submit_quiz_result':
                return $this->submitQuiz($currentUser, $userName);

            case 'upload_external_certificate':
                return $this->uploadExternalCertificate($currentUser, $userName);

            case 'batch_generate_certificates':
                return $this->batchGenerateCertificates($currentUser, $userType);

            case 'complete_activity_with_attendance':
                return $this->completeActivityWithAttendance($currentUser, $userType, $userName);

            case 'submit_module_report':
                return $this->submitModuleReport($currentUser, $userName);

            case 'review_module_report':
                return $this->reviewModuleReport($currentUser, $userType, $userName);

            case 'get_activities':
                $targetUser = $this->request->getGet('user') ?? $this->request->getPost('user') ?? ($userType === 'Admin' ? null : $currentUser);
                $acts = $targetUser ? $this->activityModel->getByUser($targetUser) : $this->activityModel->orderBy('created_at', 'DESC')->findAll();
                return $this->response->setJSON(['success' => true, 'activities' => $acts]);

            case 'get_certificates':
                $targetUser = $this->request->getGet('user') ?? $this->request->getPost('user') ?? $currentUser;
                $certs = $this->certificateModel->getByUser($targetUser);
                return $this->response->setJSON(['success' => true, 'certificates' => $certs]);

            case 'mark_notifs_read':
                $this->notificationModel->where('user_target', $currentUser)->set(['status' => 'read'])->update();
                if ($userType === 'Admin') {
                    $this->notificationModel->where('user_target', 'admin')->set(['status' => 'read'])->update();
                }
                return $this->response->setJSON(['success' => true]);

            case 'resolve_notif':
                $notifId = trim($this->request->getPost('notif_id') ?? '');
                if (!empty($notifId)) {
                    $this->notificationModel->where('id', $notifId)->set(['status' => 'resolved'])->update();
                }
                return $this->response->setJSON(['success' => true]);

            case 'check_notifications':
                return $this->checkNotifications($currentUser, $userType);

            case 'update_profile':
                return $this->updateProfile($currentUser);

            case 'update_avatar':
                return $this->updateAvatar($currentUser);

            case 'update_password':
                return $this->updatePassword($currentUser);

            case 'send_whatsapp_nudge':
                return $this->sendWhatsAppNudge();

            case 'save_gateway_config':
                return $this->saveGatewayConfig($userType);

            case 'get_gateway_config':
                return $this->response->setJSON(['success' => true, 'config' => get_gateway_config()]);

            case 'add_catalog_course':
                return $this->addCatalogCourse($userType);

            default:
                return $this->response->setJSON(['success' => false, 'message' => "Aksi API '{$action}' tidak dikenal."]);
        }
    }

    private function applyActivity(string $username, string $name)
    {
        $title    = trim($this->request->getPost('title') ?? '');
        $type     = trim($this->request->getPost('type') ?? 'Wajib K3');
        $category = trim($this->request->getPost('category') ?? 'K3 & Keselamatan Operasi Tambang');
        $duration = trim($this->request->getPost('duration') ?? '2 Hari (16 Jam)');
        $level    = trim($this->request->getPost('level') ?? 'Basic');
        $notes    = trim($this->request->getPost('notes') ?? '');
        $deadline = trim($this->request->getPost('deadline') ?? date('d M Y', strtotime('+14 days')));

        if (empty($title)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Judul kegiatan wajib diisi.']);
        }

        $actId = 'act_' . time() . '_' . rand(100, 999);
        $this->activityModel->insert([
            'id'                => $actId,
            'user_username'     => $username,
            'user_name'         => $name,
            'title'             => $title,
            'type'              => $type,
            'category'          => $category,
            'duration'          => $duration,
            'level'             => $level,
            'deadline'          => $deadline,
            'status'            => 'menunggu_konfirmasi',
            'progress'          => 0,
            'current_module'    => 'Menunggu Persetujuan Biro',
            'total_modules'     => 4,
            'completed_modules' => 0,
            'notes'             => $notes,
            'created_at'        => date('Y-m-d H:i:s')
        ]);

        $this->notificationModel->sendNotification('admin', 'request', 'Pengajuan Kegiatan: ' . $title, "Pegawai {$name} mengajukan permohonan modul '{$title}'.", $name, $title, $actId);

        return $this->response->setJSON(['success' => true, 'message' => 'Pengajuan kegiatan berhasil dikirim ke Biro L&D!', 'activity_id' => $actId]);
    }

    private function approveActivity(string $currentUser, string $userType)
    {
        if ($userType !== 'Admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya Administrator Biro yang berhak menyetujui kegiatan.']);
        }

        $activityId = trim($this->request->getPost('activity_id') ?? '');
        $act = $this->activityModel->find($activityId);
        if (!$act) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
        }

        $this->activityModel->update($activityId, [
            'status'         => 'disetujui',
            'current_module' => 'Modul 1: Pengenalan & Regulasi K3',
            'approved_at'    => date('Y-m-d H:i:s')
        ]);

        $this->notificationModel->sendNotification($act['user_username'], 'approval', 'Kegiatan Disetujui: ' . $act['title'], "Selamat! Pengajuan kegiatan '{$act['title']}' telah disetujui oleh Biro.", 'Learning Ops Admin', $act['title'], $activityId);

        return $this->response->setJSON(['success' => true, 'message' => 'Kegiatan berhasil disetujui!']);
    }

    private function rejectActivity(string $currentUser, string $userType)
    {
        if ($userType !== 'Admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        $activityId = trim($this->request->getPost('activity_id') ?? '');
        $reason     = trim($this->request->getPost('reason') ?? 'Jadwal belum memenuhi kuota minimum.');
        $act = $this->activityModel->find($activityId);
        if (!$act) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
        }

        $this->activityModel->update($activityId, [
            'status'      => 'ditolak',
            'admin_notes' => $reason
        ]);

        $this->notificationModel->sendNotification($act['user_username'], 'escalation', 'Pengajuan Ditolak: ' . $act['title'], "Pengajuan modul '{$act['title']}' belum disetujui: {$reason}", 'Learning Ops Admin', $act['title'], $activityId);

        return $this->response->setJSON(['success' => true, 'message' => 'Kegiatan telah ditolak.']);
    }

    private function assignTraining(string $currentUser, string $userType)
    {
        if ($userType !== 'Admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya Admin yang dapat menugaskan pelatihan.']);
        }

        $targetUsername = trim($this->request->getPost('username') ?? '');
        $courseTitle    = trim($this->request->getPost('course_title') ?? '');
        $deadline       = trim($this->request->getPost('deadline') ?? '14 Hari');

        $targetUser = $this->userModel->find($targetUsername);
        if (!$targetUser) {
            return $this->response->setJSON(['success' => false, 'message' => 'Pegawai tidak ditemukan.']);
        }

        $actId = 'act_' . time() . '_' . rand(100, 999);
        $this->activityModel->insert([
            'id'                => $actId,
            'user_username'     => $targetUsername,
            'user_name'         => $targetUser['name'] ?? $targetUser['nama'],
            'title'             => $courseTitle,
            'type'              => 'Wajib K3',
            'category'          => 'Penugasan Resmi Biro',
            'duration'          => '3 Hari (24 Jam)',
            'level'             => 'Intermediate',
            'deadline'          => $deadline,
            'status'            => 'disetujui',
            'progress'          => 0,
            'current_module'    => 'Modul 1: Penugasan Resmi Biro',
            'total_modules'     => 4,
            'completed_modules' => 0,
            'notes'             => 'Ditugaskan langsung oleh Administrator Biro L&D Site Pomalaa.',
            'created_at'        => date('Y-m-d H:i:s'),
            'approved_at'       => date('Y-m-d H:i:s')
        ]);

        $this->notificationModel->sendNotification($targetUsername, 'request', 'Penugasan Pelatihan Wajib: ' . $courseTitle, "Anda ditugaskan mengikuti modul '{$courseTitle}'.", 'Biro L&D ANTAM', $courseTitle, $actId);

        return $this->response->setJSON(['success' => true, 'message' => "Pelatihan berhasil ditugaskan ke @{$targetUsername}!"]);
    }

    private function claimCertificate(string $username, string $userName)
    {
        $activityId = trim($this->request->getPost('activity_id') ?? $this->request->getPost('course_title') ?? '');
        $act = $this->activityModel->where('user_username', $username)
                                  ->groupStart()
                                      ->where('id', $activityId)
                                      ->orWhere('title', $activityId)
                                  ->groupEnd()
                                  ->first();

        if (!$act) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kegiatan pelatihan tidak ditemukan.']);
        }

        $this->activityModel->update($act['id'], [
            'progress'          => 100,
            'completed_modules' => $act['total_modules'] ?? 4,
            'current_module'    => 'Selesai — Bersertifikat Resmi',
            'grade'             => 100
        ]);

        $res = $this->certificateModel->issueCertificate($username, $userName, $act['title'], 100, $act['cert_template'] ?? null);

        $this->notificationModel->sendNotification($username, 'certificate', 'Sertifikat Resmi Terbit: ' . $act['title'], "Selamat {$userName}! Sertifikat resmi untuk pelatihan '{$act['title']}' telah terbit.", 'Biro HC Pomalaa', $act['title'], $act['id']);

        return $this->response->setJSON([
            'success'      => true,
            'message'      => $res['message'],
            'cert_id'      => $res['certificate']['id'] ?? '',
            'reg_no'       => $res['certificate']['reg_no'] ?? '',
            'course_title' => $act['title'],
            'score'        => 100,
            'issued_at'    => date('d M Y'),
            'valid_until'  => date('d M Y', strtotime('+3 years')),
            'holder_name'  => $userName
        ]);
    }

    private function submitQuiz(string $username, string $userName)
    {
        $courseTitle = trim($this->request->getPost('course_title') ?? '');
        $score       = max(0, min(100, (int)$this->request->getPost('score')));

        $act = $this->activityModel->where('user_username', $username)
                                  ->where('title', $courseTitle)
                                  ->first();

        if ($act) {
            $isPass = ($score >= 75);
            $this->activityModel->update($act['id'], [
                'grade'          => $score,
                'progress'       => $isPass ? 100 : 75,
                'current_module' => $isPass ? 'Selesai — Lulus Ujian K3' : 'Remedial Ujian K3'
            ]);

            if ($isPass) {
                $this->certificateModel->issueCertificate($username, $userName, $courseTitle, $score);
            }
        }

        return $this->response->setJSON(['success' => true, 'score' => $score, 'message' => 'Hasil evaluasi berhasil disimpan!']);
    }

    private function uploadExternalCertificate(string $username, string $userName)
    {
        $title      = trim($this->request->getPost('title') ?? '');
        $regNo      = trim($this->request->getPost('reg_no') ?? 'EXT/K3/' . date('Y') . '/' . rand(1000, 9999));
        $issuedBy   = trim($this->request->getPost('issued_by') ?? 'Lembaga Sertifikasi Eksternal');
        $validUntil = trim($this->request->getPost('valid_until') ?? date('Y-m-d', strtotime('+3 years')));
        $score      = (int)($this->request->getPost('score') ?? 100);

        if (empty($title)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nama/judul sertifikat wajib diisi.']);
        }

        $attachmentName = '';
        $file = $this->request->getFile('cert_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = 'cert_' . time() . '_' . $file->getRandomName();
            $file->move(FCPATH . 'uploads/', $newName);
            $attachmentName = $newName;
        }

        $certId = 'cert_ext_' . time() . '_' . rand(100, 999);
        $this->certificateModel->insert([
            'id'            => $certId,
            'user_username' => $username,
            'user_name'     => $userName,
            'course_title'  => $title,
            'reg_no'        => $regNo,
            'score'         => $score,
            'issued_at'     => date('Y-m-d H:i:s'),
            'valid_until'   => $validUntil,
            'issued_by'     => $issuedBy,
            'status'        => 'active',
            'attachment'    => $attachmentName
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Sertifikat eksternal berhasil dicatat dalam portofolio Anda!']);
    }

    private function batchGenerateCertificates(string $currentUser, string $userType)
    {
        $courseTitle = trim($this->request->getPost('course_title') ?? '');
        $issuer      = trim($this->request->getPost('issued_by') ?? 'PT ANTAM (PERSERO) TBK — Site Pomalaa');
        $raw         = $this->request->getPost('participants');

        $participants = is_array($raw) ? $raw : preg_split('/[\r\n,]+/', (string)$raw);
        $participants = array_filter(array_map('trim', (array)$participants));

        if (empty($courseTitle) || empty($participants)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Judul kegiatan dan minimal 1 nama peserta wajib diisi.']);
        }

        $templateFileName = null;
        $file = $this->request->getFile('template_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $templateFileName = 'tpl_' . time() . '_' . $file->getRandomName();
            $file->move(FCPATH . 'uploads/templates/', $templateFileName);
        }

        $generated = [];
        foreach ($participants as $p) {
            $name = is_array($p) ? ($p['name'] ?? '') : trim($p);
            if (empty($name)) continue;

            $matchedUser = $this->userModel->where('name', $name)->orWhere('username', $name)->first();
            $uname = $matchedUser ? $matchedUser['username'] : 'peserta_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
            $cleanName = $matchedUser ? $matchedUser['name'] : $name;

            $res = $this->certificateModel->issueCertificate($uname, $cleanName, $courseTitle, 100, $templateFileName);
            $generated[] = $res['certificate'] ?? [];
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => count($generated) . ' sertifikat resmi berhasil diterbitkan secara massal!',
            'count'     => count($generated),
            'generated' => $generated
        ]);
    }

    private function completeActivityWithAttendance(string $currentUser, string $userType, string $userName)
    {
        $activityId = trim($this->request->getPost('activity_id') ?? '');
        $act = $this->activityModel->find($activityId);
        if (!$act) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data kegiatan tidak ditemukan.']);
        }

        $attendanceFileName = null;
        $file = $this->request->getFile('attendance_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $attendanceFileName = 'absen_' . time() . '_' . $file->getRandomName();
            $file->move(FCPATH . 'uploads/attendance/', $attendanceFileName);
        }

        $this->activityModel->update($activityId, [
            'progress'          => 100,
            'completed_modules' => $act['total_modules'] ?? 4,
            'current_module'    => 'Selesai — Terverifikasi Absensi HC',
            'grade'             => 100,
            'attendance_file'   => $attendanceFileName
        ]);

        $this->certificateModel->issueCertificate($act['user_username'], $act['user_name'], $act['title'], 100, $act['cert_template'] ?? null);

        return $this->response->setJSON(['success' => true, 'message' => 'Kegiatan diselesaikan dan sertifikat resmi diterbitkan!']);
    }

    private function submitModuleReport(string $username, string $name)
    {
        $actId  = trim($this->request->getPost('activity_id') ?? '');
        $report = trim($this->request->getPost('report_text') ?? '');

        $act = $this->activityModel->find($actId);
        if (!$act) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
        }

        $attachName = null;
        $file = $this->request->getFile('report_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $attachName = 'lap_' . time() . '_' . $file->getRandomName();
            $file->move(FCPATH . 'uploads/reports/', $attachName);
        }

        $newProg = min(100, (int)$act['progress'] + 25);
        $this->activityModel->update($actId, [
            'progress'          => $newProg,
            'completed_modules' => min((int)$act['total_modules'], (int)$act['completed_modules'] + 1),
            'last_report'       => $report,
            'last_attachment'   => $attachName,
            'last_submitted_at' => date('Y-m-d H:i:s')
        ]);

        $this->notificationModel->sendNotification('admin', 'submission', 'Lembar Kerja Masuk: ' . $act['title'], "Pegawai @{$username} mengumpulkan lembar kerja modul.", $name, $act['title'], $actId);

        return $this->response->setJSON(['success' => true, 'message' => 'Lembar kerja modul berhasil dikumpulkan!']);
    }

    private function reviewModuleReport(string $currentUser, string $userType, string $adminName)
    {
        if ($userType !== 'Admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        $actId    = trim($this->request->getPost('activity_id') ?? '');
        $score    = max(0, min(100, (int)$this->request->getPost('score')));
        $feedback = trim($this->request->getPost('feedback') ?? '');

        $act = $this->activityModel->find($actId);
        if (!$act) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
        }

        $this->activityModel->update($actId, [
            'grade'          => $score,
            'admin_feedback' => $feedback,
            'graded_at'      => date('Y-m-d H:i:s'),
            'progress'       => $score
        ]);

        $this->notificationModel->sendNotification($act['user_username'], 'graded', 'Lembar Kerja Dinilai: ' . $act['title'], "Lembar kerja Anda dinilai dengan skor {$score}/100. Catatan: {$feedback}", $adminName, $act['title'], $actId);

        return $this->response->setJSON(['success' => true, 'message' => 'Penilaian lembar kerja berhasil disimpan!']);
    }

    private function checkNotifications(string $username, string $userType)
    {
        $isAdmin = ($userType === 'Admin');
        $target = $isAdmin ? 'admin' : strtolower($username);

        $pendingActivities = $isAdmin
            ? $this->activityModel->where('status', 'menunggu_konfirmasi')->orderBy('created_at', 'DESC')->findAll()
            : $this->activityModel->where('user_username', $username)->where('status', 'menunggu_konfirmasi')->orderBy('created_at', 'DESC')->findAll();

        $unreadNotifications = $this->notificationModel->where('user_target', $target)->where('status', 'unread')->orderBy('created_at', 'DESC')->limit(10)->findAll();

        $pendingCount = count($pendingActivities);
        $unreadCount  = count($unreadNotifications);
        $badgeCount   = $isAdmin ? (2 + $pendingCount) : (2 + $unreadCount + $pendingCount);

        return $this->response->setJSON([
            'success'              => true,
            'user_type'            => $userType,
            'badge_count'          => $badgeCount,
            'pending_count'        => $pendingCount,
            'unread_count'         => $unreadCount,
            'pending_activities'   => $pendingActivities,
            'unread_notifications' => $unreadNotifications,
            'latest_pending'       => !empty($pendingActivities) ? $pendingActivities[0] : null,
            'server_time'          => date('Y-m-d H:i:s')
        ]);
    }

    private function updateProfile(string $username)
    {
        $email = strtolower(trim($this->request->getPost('email') ?? ''));
        $phone = trim($this->request->getPost('phone') ?? '');
        $bio   = trim($this->request->getPost('bio') ?? '');
        $color = trim($this->request->getPost('avatar_color') ?? '#0f766e');

        $update = [
            'email'        => $email,
            'phone'        => $phone,
            'bio'          => $bio,
            'avatar_color' => $color
        ];

        $file = $this->request->getFile('avatar');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = 'avatar_' . $username . '_' . time() . '.' . $file->getExtension();
            $file->move(FCPATH . 'uploads/avatars/', $newName);
            $update['avatar_image'] = $newName;
            session()->set('avatar_image', $newName);
        }

        $this->userModel->update($username, $update);
        session()->set('avatar_color', $color);

        return $this->response->setJSON(['success' => true, 'message' => 'Data profil berhasil diperbarui!']);
    }

    private function updateAvatar(string $username)
    {
        $file = $this->request->getFile('avatar');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'File gambar tidak valid.']);
        }

        $newName = 'avatar_' . $username . '_' . time() . '.' . $file->getExtension();
        $file->move(FCPATH . 'uploads/avatars/', $newName);

        $this->userModel->update($username, ['avatar_image' => $newName]);
        session()->set('avatar_image', $newName);

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Foto profil berhasil diunggah!',
            'image_url' => base_url('uploads/avatars/' . $newName)
        ]);
    }

    private function updatePassword(string $username)
    {
        $oldPassword = (string)$this->request->getPost('old_password');
        $newPassword = (string)$this->request->getPost('new_password');

        if (strlen($newPassword) < 6) {
            return $this->response->setJSON(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
        }

        $user = $this->userModel->find($username);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User tidak ditemukan.']);
        }

        $isMatch = false;
        if (password_verify($oldPassword, $user['password'])) {
            $isMatch = true;
        } elseif (md5($oldPassword) === $user['password'] || $oldPassword === $user['password']) {
            $isMatch = true;
        }

        if (!$isMatch) {
            return $this->response->setJSON(['success' => false, 'message' => 'Password lama tidak sesuai.']);
        }

        $this->userModel->update($username, [
            'password' => password_hash($newPassword, PASSWORD_BCRYPT)
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Password berhasil diperbarui!']);
    }

    private function sendWhatsAppNudge()
    {
        $employeeName = trim($this->request->getPost('employee_name') ?? '');
        $courseName   = trim($this->request->getPost('course_name') ?? 'Pelatihan K3 Wajib');
        $phone        = trim($this->request->getPost('phone') ?? '');
        $savePhone    = !empty($this->request->getPost('save_phone'));

        if (empty($phone)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nomor WhatsApp penerima wajib diisi.']);
        }

        if ($savePhone && !empty($employeeName)) {
            $this->userModel->where('name', $employeeName)->orWhere('username', $employeeName)->set(['phone' => $phone])->update();
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strpos($cleanPhone, '0') === 0) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (strpos($cleanPhone, '8') === 0) {
            $cleanPhone = '62' . $cleanPhone;
        }

        $messageText = "*[PT ANTAM — SMART LEARNING ALARM]*\n"
                     . "Yth. Sdr. *" . $employeeName . "*,\n\n"
                     . "Modul wajib: _\"" . $courseName . "\"_ telah mencapai batas waktu. Harap segera mengakses portal Smart Learning sebelum akses site dibekukan.\n\n"
                     . "Tautan: " . base_url('login') . "\n"
                     . "_— Learning & Development_";

        $waUrl = "https://api.whatsapp.com/send?phone=" . $cleanPhone . "&text=" . rawurlencode($messageText);

        return $this->response->setJSON([
            'success' => true,
            'mode'    => 'direct',
            'phone'   => $cleanPhone,
            'wa_url'  => $waUrl,
            'message' => 'Pesan disiapkan untuk dikirim ke ' . $cleanPhone
        ]);
    }

    private function saveGatewayConfig(string $userType)
    {
        if ($userType !== 'Admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        $token    = trim($this->request->getPost('token') ?? '');
        $provider = trim($this->request->getPost('provider') ?? 'fonnte');
        $autoSend = !empty($this->request->getPost('auto_send'));

        $cfg = [
            'provider'   => $provider,
            'token'      => $token,
            'auto_send'  => $autoSend,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $dir = WRITEPATH . 'data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = WRITEPATH . 'gateway_config.json';
        $ok = @file_put_contents($file, json_encode($cfg, JSON_PRETTY_PRINT)) !== false;

        return $this->response->setJSON([
            'success' => $ok,
            'message' => $ok ? 'Konfigurasi WhatsApp Gateway berhasil disimpan.' : 'Gagal menyimpan konfigurasi.'
        ]);
    }

    private function addCatalogCourse(string $userType)
    {
        if ($userType !== 'Admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya Admin yang dapat menambahkan modul master.']);
        }

        $code     = trim($this->request->getPost('code') ?? 'TRC-NEW');
        $title    = trim($this->request->getPost('title') ?? '');
        $category = trim($this->request->getPost('category') ?? 'GENERAL');
        $type     = trim($this->request->getPost('type') ?? 'Inhouse');
        $duration = trim($this->request->getPost('duration') ?? '2 Hari (16 Jam)');

        if (empty($title)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Judul modul wajib diisi.']);
        }

        $this->lnaModel->insert([
            'code'        => $code,
            'title'       => $title,
            'category'    => $category,
            'type'        => $type,
            'duration'    => $duration,
            'description' => trim($this->request->getPost('description') ?? ''),
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Modul baru berhasil ditambahkan ke katalog master!']);
    }
}
