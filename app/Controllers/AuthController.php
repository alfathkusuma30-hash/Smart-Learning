<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\AntamMailer;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Tampilan Utama Halaman Login / 2FA / Registrasi
     */
    public function index()
    {
        $session = session();
        if ($session->get('is_logged_in') && !empty($session->get('username'))) {
            return redirect()->to(base_url('home'));
        }

        $pending2FA = $session->get('pending_2fa');
        $isWaiting2FA = !empty($pending2FA['username']);

        $data = [
            'title'        => 'Smart Learning — Portal Pembelajaran PT ANTAM Tbk',
            'isWaiting2FA' => $isWaiting2FA,
            'pendingUser'  => $pending2FA,
            'maskedEmail'  => $isWaiting2FA ? $this->maskEmail($pending2FA['email'] ?? '') : '',
            'errorMessage' => $session->getFlashdata('error') ?? '',
            'successMessage' => $session->getFlashdata('success') ?? '',
            'authMode'     => $this->request->getGet('mode') ?? 'login',
            'prefillRole'  => $this->request->getGet('role') ?? 'pegawai',
            'prefillUser'  => $session->getFlashdata('prefill_username') ?? ''
        ];

        return view('auth/index', $data);
    }

    /**
     * Submit Kredensial Login (Langkah 1 -> Picu OTP 2FA)
     */
    public function doLogin()
    {
        $session = session();
        $username = trim($this->request->getPost('username') ?? '');
        $password = trim($this->request->getPost('password') ?? '');
        $portal   = trim($this->request->getPost('portal') ?? 'pegawai');

        if (empty($username) || empty($password)) {
            return redirect()->back()->with('error', 'Silakan masukkan username/email dan kata sandi Anda.')->withInput();
        }

        $auth = $this->userModel->authenticate($username, $password);
        if (!$auth['success']) {
            return redirect()->back()->with('error', $auth['message'])->withInput();
        }

        $user = $auth['user'];

        // Sinkronisasi peran akun (Admin Biro vs Pegawai HC)
        $actualPortal = (($user['type'] ?? '') === 'Admin') ? 'admin' : 'pegawai';

        // Buat 6-digit kode OTP
        $otpCode = $this->userModel->generate2FAOtp($user['username']);

        // Kirim email OTP
        $email = !empty($user['email']) ? $user['email'] : ($user['username'] . '@antam.com');
        $roleTitle = ($user['type'] === 'Admin') ? 'Biro Human Capital Admin' : 'Pegawai HC Operasional';
        $mailResult = AntamMailer::send2FAOtp($email, $user['name'] ?? $user['username'], $otpCode, $roleTitle);
        $mailSent = !empty($mailResult['success']);

        // Simpan state 2FA sementara di session
        $session->set('pending_2fa', [
            'username'     => $user['username'],
            'email'        => $email,
            'name'         => $user['name'] ?? $user['username'],
            'type'         => $user['type'] ?? 'User',
            'role'         => $user['role'] ?? '',
            'dept'         => $user['dept'] ?? '',
            'user_id'      => $user['user_id'] ?? '',
            'avatar_color' => $user['avatar_color'] ?? '#0f766e',
            'avatar_image' => $user['avatar_image'] ?? null,
            'mail_sent'    => $mailSent,
            'mail_message' => $mailResult['message'] ?? ''
        ]);

        if ($mailSent) {
            return redirect()->to(base_url('/'))->with('success', 'Kredensial valid. Kode OTP verifikasi 2FA telah dikirimkan ke email terdaftar Anda (' . $this->maskEmail($email) . ').');
        } else {
            return redirect()->to(base_url('/'))->with('error', 'Kredensial valid. Namun pengiriman email ke Google terkendala: ' . ($mailResult['message'] ?? 'Gmail menolak otentikasi.'));
        }
    }

    /**
     * Verifikasi 6-Digit Kode OTP (Langkah 2 -> Masuk Dashboard)
     */
    public function verify2FA()
    {
        $session = session();
        $pending = $session->get('pending_2fa');

        if (empty($pending['username'])) {
            return redirect()->to(base_url('/'))->with('error', 'Sesi verifikasi telah kedaluwarsa. Silakan masukkan kredensial kembali.');
        }

        $otpCode = trim($this->request->getPost('otp_code') ?? '');
        if (empty($otpCode)) {
            $digits = '';
            for ($i = 1; $i <= 6; $i++) {
                $digits .= trim($this->request->getPost("digit_{$i}") ?? '');
            }
            $otpCode = $digits;
        }

        if (empty($otpCode)) {
            return redirect()->to(base_url('/'))->with('error', 'Silakan masukkan 6-digit kode OTP verifikasi.');
        }

        $verify = $this->userModel->verify2FAOtp($pending['username'], $otpCode);
        if (!$verify['success']) {
            return redirect()->to(base_url('/'))->with('error', $verify['message']);
        }

        $user = $verify['user'];

        // Inisialisasi Sesi Login Resmi ANTAM
        $session->set([
            'is_logged_in' => true,
            'username'     => $user['username'],
            'user_id'      => $user['user_id'] ?? 'HC',
            'user_name'    => $user['name'] ?? $user['nama'] ?? $user['username'],
            'user_role'    => $user['role'] ?? 'Operasional Tambang',
            'user_type'    => $user['type'] ?? 'User',
            'user_dept'    => $user['dept'] ?? $user['unit_kerja'] ?? 'PT ANTAM Tbk',
            'avatar_color' => $user['avatar_color'] ?? '#0f766e',
            'avatar_image' => $user['avatar_image'] ?? null
        ]);

        $session->remove('pending_2fa');

        return redirect()->to(base_url('home'));
    }

    /**
     * Kirim Ulang Kode OTP
     */
    public function resendOtp()
    {
        $session = session();
        $pending = $session->get('pending_2fa');

        if (empty($pending['username'])) {
            return redirect()->to(base_url('/'))->with('error', 'Sesi telah kedaluwarsa.');
        }

        $user = $this->userModel->find($pending['username']);
        if (!$user) {
            return redirect()->to(base_url('/'))->with('error', 'Pengguna tidak ditemukan.');
        }

        $otpCode = $this->userModel->generate2FAOtp($user['username']);
        $email = $pending['email'];
        $roleTitle = ($user['type'] === 'Admin') ? 'Biro Human Capital Admin' : 'Pegawai HC Operasional';
        $mailResult = AntamMailer::send2FAOtp($email, $user['name'] ?? $user['username'], $otpCode, $roleTitle);

        $pending['mail_sent'] = !empty($mailResult['success']);
        $pending['mail_message'] = $mailResult['message'] ?? '';
        $session->set('pending_2fa', $pending);

        if (!empty($mailResult['success'])) {
            return redirect()->to(base_url('/'))->with('success', 'Kode OTP baru telah berhasil dikirimkan ke email terdaftar Anda.');
        } else {
            return redirect()->to(base_url('/'))->with('error', 'Gagal mengirim email OTP via SMTP Google: ' . ($mailResult['message'] ?? 'Periksa Sandi Aplikasi Gmail.'));
        }
    }

    /**
     * Batal & Ganti Akun
     */
    public function cancel2FA()
    {
        session()->remove('pending_2fa');
        return redirect()->to(base_url('/'));
    }

    /**
     * Registrasi Akun Baru
     */
    public function register()
    {
        $res = $this->userModel->registerUser($this->request->getPost());
        if (!$res['success']) {
            return redirect()->to(base_url('/?mode=register'))->with('error', $res['message'])->withInput();
        }

        return redirect()->to(base_url('/?mode=login'))
                         ->with('success', $res['message'])
                         ->with('prefill_username', $res['username']);
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('/'));
    }

    /**
     * Masking alamat email untuk tampilan keamanan (cth: eg*******ma@antam.com)
     */
    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if (empty($email) || strpos($email, '@') === false) {
            return 'email terdaftar Anda';
        }
        list($user, $domain) = explode('@', $email, 2);
        $len = strlen($user);
        if ($len <= 2) {
            $masked = substr($user, 0, 1) . '***';
        } else {
            $masked = substr($user, 0, 2) . str_repeat('*', max(3, $len - 4)) . substr($user, -2);
        }
        return $masked . '@' . $domain;
    }
}

