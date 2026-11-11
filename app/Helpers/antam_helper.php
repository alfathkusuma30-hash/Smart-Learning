<?php

use App\Models\UserModel;
use App\Models\ActivityModel;
use App\Models\CertificateModel;
use App\Models\LnaModel;
use App\Models\NotificationModel;

if (!function_exists('get_realtime_indonesian_date')) {
    function get_realtime_indonesian_date(string $format = 'day_short', ?int $timestamp = null): string {
        if ($timestamp === null) $timestamp = time();
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['','Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $monthsLong = ['','Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $w = (int)date('w', $timestamp);
        $d = date('d', $timestamp);
        $m = (int)date('m', $timestamp);
        $y = date('Y', $timestamp);
        $time = date('H:i:s', $timestamp);

        if ($format === 'short') {
            return "{$d} {$months[$m]} {$y}";
        } elseif ($format === 'time') {
            return "{$d} {$months[$m]} {$y} · {$time}";
        } elseif ($format === 'full_time') {
            return "{$days[$w]}, {$d} {$months[$m]} {$y} · {$time} WITA";
        } elseif ($format === 'long') {
            return "{$days[$w]}, {$d} {$monthsLong[$m]} {$y}";
        } else {
            return "{$days[$w]}, {$d} {$months[$m]} {$y}";
        }
    }
}

if (!function_exists('mask_email')) {
    function mask_email(string $email): string {
        $email = trim($email);
        if (empty($email) || strpos($email, '@') === false) {
            return 'email terdaftar Anda';
        }
        list($user, $domain) = explode('@', $email, 2);
        $len = strlen($user);
        if ($len <= 2) {
            $maskedUser = substr($user, 0, 1) . '***';
        } else {
            $maskedUser = substr($user, 0, 2) . str_repeat('*', max(3, $len - 4)) . substr($user, -2);
        }
        return $maskedUser . '@' . $domain;
    }
}

if (!function_exists('get_user_by_username')) {
    function get_user_by_username(string $username): ?array {
        $model = new UserModel();
        return $model->find($username);
    }
}

if (!function_exists('get_all_users')) {
    function get_all_users(): array {
        $model = new UserModel();
        $users = $model->findAll();
        $res = [];
        foreach ($users as $u) {
            $res[$u['username']] = $u;
        }
        return $res;
    }
}

if (!function_exists('get_activities')) {
    function get_activities(?string $username = null, ?string $status = null): array {
        $model = new ActivityModel();
        if ($username !== null) {
            $model->where('user_username', $username);
        }
        if ($status !== null) {
            $model->where('status', $status);
        }
        return $model->orderBy('created_at', 'DESC')->findAll();
    }
}

if (!function_exists('get_activities_by_id')) {
    function get_activities_by_id(string $actId): ?array {
        $model = new ActivityModel();
        return $model->find($actId);
    }
}

if (!function_exists('get_user_certificates')) {
    function get_user_certificates(string $username): array {
        $model = new CertificateModel();
        return $model->getByUser($username);
    }
}

if (!function_exists('get_lna_catalog')) {
    function get_lna_catalog(): array {
        $model = new LnaModel();
        return $model->findAll();
    }
}

if (!function_exists('get_notifications_for_user')) {
    function get_notifications_for_user(string $username, string $userType = 'User'): array {
        $model = new NotificationModel();
        return $model->getForUser($username, $userType);
    }
}

if (!function_exists('get_gateway_config')) {
    function get_gateway_config(): array {
        $file = WRITEPATH . 'gateway_config.json';
        if (!file_exists($file)) {
            $legacyFile = ROOTPATH . 'data/gateway_config.json';
            if (file_exists($legacyFile)) {
                $file = $legacyFile;
            }
        }
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return array_merge([
                    'provider' => 'fonnte',
                    'token' => '',
                    'auto_send' => false
                ], $data);
            }
        }
        return [
            'provider' => 'fonnte',
            'token' => '',
            'auto_send' => false
        ];
    }
}

