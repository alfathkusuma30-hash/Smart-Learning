<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\NotificationModel;

class AdminPanelController extends BaseController
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

        if ($userType !== 'Admin') {
            return redirect()->to('home')->with('error', 'Halaman ini khusus untuk Learning Ops Administrator.');
        }

        $user = $this->userModel->find($username);
        $allActivities = $this->activityModel->orderBy('created_at', 'DESC')->findAll();
        $pendingRequests = array_filter($allActivities, fn($a) => ($a['status'] ?? '') === 'menunggu_konfirmasi');
        $approvedActivities = array_filter($allActivities, fn($a) => ($a['status'] ?? '') === 'disetujui');
        $users = $this->userModel->findAll();
        $notifications = $this->notificationModel->getForUser($username, $userType);
        $unreadCount = count(array_filter($notifications, fn($n) => ($n['status'] ?? '') === 'unread'));

        $data = [
            'title'              => 'Admin Console & Monitoring — PT ANTAM Tbk',
            'user'               => $user,
            'allActivities'      => $allActivities,
            'pendingRequests'    => $pendingRequests,
            'approvedActivities' => $approvedActivities,
            'users'              => $users,
            'notifications'      => $notifications,
            'unreadCount'        => $unreadCount,
            'userType'           => $userType,
            'username'           => $username,
            'activeNav'          => 'admin_panel'
        ];

        return view('admin_panel/index', $data);
    }
}

