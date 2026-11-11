<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\CertificateModel;
use App\Models\NotificationModel;

class MyLearningController extends BaseController
{
    protected UserModel $userModel;
    protected ActivityModel $activityModel;
    protected CertificateModel $certificateModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityModel = new ActivityModel();
        $this->certificateModel = new CertificateModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $session = session();
        $username = $session->get('username');
        $userType = $session->get('user_type') ?? 'User';

        $user = $this->userModel->find($username);
        $activities = ($userType === 'Admin') 
            ? $this->activityModel->orderBy('created_at', 'DESC')->findAll()
            : $this->activityModel->getByUser($username);

        $certificates = $this->certificateModel->getByUser($username);
        $notifications = $this->notificationModel->getForUser($username, $userType);
        $unreadCount = count(array_filter($notifications, fn($n) => ($n['status'] ?? '') === 'unread'));

        $approvedActivities = array_filter($activities, fn($a) => ($a['status'] ?? '') === 'disetujui');
        $pendingActivities  = array_filter($activities, fn($a) => ($a['status'] ?? '') === 'menunggu_konfirmasi');

        $data = [
            'title'              => 'My Learning Track & Sertifikasi — PT ANTAM Tbk',
            'user'               => $user,
            'activities'         => $activities,
            'approvedActivities' => $approvedActivities,
            'pendingActivities'  => $pendingActivities,
            'certificates'       => $certificates,
            'notifications'      => $notifications,
            'unreadCount'        => $unreadCount,
            'userType'           => $userType,
            'username'           => $username,
            'activeNav'          => 'my_learning'
        ];

        return view('my_learning/index', $data);
    }
}

