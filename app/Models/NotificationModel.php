<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'user_target', 'type', 'title', 'message', 'related_user', 
        'related_course', 'activity_id', 'status', 'created_at'
    ];
    protected $useTimestamps    = false;

    /**
     * Ambil notifikasi untuk user (termasuk notifikasi admin jika tipe Admin)
     */
    public function getForUser(string $username, string $userType = 'User'): array
    {
        $builder = $this->builder();
        if ($userType === 'Admin') {
            $builder->where('user_target', 'admin')
                    ->orWhere('user_target', $username);
        } else {
            $builder->where('user_target', $username);
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Kirim notifikasi baru
     */
    public function sendNotification(string $recipient, string $type, string $title, string $message, ?string $sender = null, ?string $course = null, ?string $activityId = null): void
    {
        $data = [
            'id'             => 'notif_' . time() . '_' . rand(100, 999),
            'user_target'    => $recipient,
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'related_user'   => $sender,
            'related_course' => $course,
            'activity_id'    => $activityId,
            'status'         => 'unread',
            'created_at'     => date('Y-m-d H:i:s')
        ];

        $this->insert($data);
    }
}

