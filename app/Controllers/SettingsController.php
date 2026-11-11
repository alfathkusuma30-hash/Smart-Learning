<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\NotificationModel;

class SettingsController extends BaseController
{
    protected UserModel $userModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
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

        $data = [
            'title'         => 'Pengaturan Akun & Keamanan — PT ANTAM Tbk',
            'user'          => $user,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'userType'      => $userType,
            'username'      => $username,
            'activeNav'     => 'settings'
        ];

        return view('settings/index', $data);
    }
}

