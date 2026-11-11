<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\NotificationModel;

class HomeController extends BaseController
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
        $activities = $this->activityModel->getByUser($username);
        $stats = $this->activityModel->getStats($userType === 'Admin' ? null : $username);
        $notifications = $this->notificationModel->getForUser($username, $userType);
        $unreadCount = count(array_filter($notifications, fn($n) => ($n['status'] ?? '') === 'unread'));

        $data = [
            'title'         => 'Home — Smart Learning Portal PT ANTAM Tbk',
            'user'          => $user,
            'activities'    => $activities,
            'stats'         => $stats,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'userType'      => $userType,
            'username'      => $username,
            'activeNav'     => 'home'
        ];

        return view('home/index', $data);
    }
}

