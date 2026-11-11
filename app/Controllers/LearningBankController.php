<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\LnaModel;
use App\Models\NotificationModel;

class LearningBankController extends BaseController
{
    protected UserModel $userModel;
    protected ActivityModel $activityModel;
    protected LnaModel $lnaModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityModel = new ActivityModel();
        $this->lnaModel = new LnaModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $session = session();
        $username = $session->get('username');
        $userType = $session->get('user_type') ?? 'User';

        $user = $this->userModel->find($username);
        $lnaList = $this->lnaModel->findAll();
        $notifications = $this->notificationModel->getForUser($username, $userType);
        $unreadCount = count(array_filter($notifications, fn($n) => ($n['status'] ?? '') === 'unread'));

        $data = [
            'title'         => 'Katalog Modul (Learning Bank) — PT ANTAM Tbk',
            'user'          => $user,
            'lnaList'       => $lnaList,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'userType'      => $userType,
            'username'      => $username,
            'activeNav'     => 'learning_bank'
        ];

        return view('learning_bank/index', $data);
    }
}

