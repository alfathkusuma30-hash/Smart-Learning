<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\NotificationModel;

class ReportsController extends BaseController
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
        $activities = $this->activityModel->orderBy('created_at', 'DESC')->findAll();
        $stats = $this->activityModel->getStats();
        $notifications = $this->notificationModel->getForUser($username, $userType);
        $unreadCount = count(array_filter($notifications, fn($n) => ($n['status'] ?? '') === 'unread'));

        $data = [
            'title'         => 'Laporan & Audit Kepatuhan ESDM — PT ANTAM Tbk',
            'user'          => $user,
            'activities'    => $activities,
            'stats'         => $stats,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'userType'      => $userType,
            'username'      => $username,
            'activeNav'     => 'reports'
        ];

        return view('reports/index', $data);
    }
}

