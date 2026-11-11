<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    protected UserModel $userModel;
    protected ActivityModel $activityModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityModel = new ActivityModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $session = session();
        $username = $session->get('username');
        $userType = $session->get('user_type') ?? 'User';

        $user = $this->userModel->find($username);
        $notifications = $this->notificationModel->getForUser($username, $userType);
        $unreadCount = count(array_filter($notifications, fn($n) => ($n['status'] ?? '') === 'unread'));

        $pendingRequests = ($userType === 'Admin') 
            ? $this->activityModel->getByStatus('menunggu_konfirmasi')
            : [];
        $userPending = ($userType !== 'Admin')
            ? $this->activityModel->getByUser($username, 'menunggu_konfirmasi')
            : [];

        $data = [
            'title'           => 'Pusat Alarm & Notifikasi — PT ANTAM Tbk',
            'user'            => $user,
            'notifications'   => $notifications,
            'unreadCount'     => $unreadCount,
            'userType'        => $userType,
            'username'        => $username,
            'pendingRequests' => $pendingRequests,
            'userPending'     => $userPending,
            'activeNav'       => 'notifikasi'
        ];

        return view('notifications/index', $data);
    }
}

