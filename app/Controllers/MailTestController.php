<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\SmtpClient;

class MailTestController extends BaseController
{
    protected string $configFile;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->configFile = ROOTPATH . 'includes/mail_config.php';
        $this->userModel  = new UserModel();
    }

    public function index()
    {
        $currentConfig = file_exists($this->configFile) ? include $this->configFile : [
            'smtp_enabled'   => false,
            'smtp_host'      => 'smtp.gmail.com',
            'smtp_port'      => 465,
            'smtp_secure'    => 'ssl',
            'smtp_user'      => '',
            'smtp_pass'      => '',
            'from_email'     => '',
            'from_name'      => 'PT ANTAM Tbk — Smart Learning Security',
            'test_recipient' => '',
        ];

        $message = '';
        $messageType = '';
        $testLogs = [];

        if ($this->request->getMethod() === 'POST') {
            $action = $this->request->getPost('action') ?? '';

            if ($action === 'save' || $action === 'test_send') {
                $newConfig = [
                    'smtp_enabled'   => $this->request->getPost('smtp_enabled') === '1',
                    'smtp_host'      => trim($this->request->getPost('smtp_host') ?? 'smtp.gmail.com'),
                    'smtp_port'      => (int)($this->request->getPost('smtp_port') ?? 465),
                    'smtp_secure'    => trim($this->request->getPost('smtp_secure') ?? 'ssl'),
                    'smtp_user'      => trim($this->request->getPost('smtp_user') ?? ''),
                    'smtp_pass'      => trim($this->request->getPost('smtp_pass') ?? ''),
                    'from_email'     => trim($this->request->getPost('from_email') ?? ''),
                    'from_name'      => trim($this->request->getPost('from_name') ?? 'PT ANTAM Tbk — Smart Learning Security'),
                    'test_recipient' => trim($this->request->getPost('test_recipient') ?? ''),
                ];

                if (empty($newConfig['from_email'])) {
                    $newConfig['from_email'] = $newConfig['smtp_user'];
                }

                // Simpan ke mail_config.php
                $exportContent = "<?php\n/**\n * Konfigurasi Pengiriman Email OTP Resmi — PT ANTAM Tbk\n * Smart Learning Portal\n */\n\nreturn " . var_export($newConfig, true) . ";\n";
                @file_put_contents($this->configFile, $exportContent);
                $currentConfig = $newConfig;

                // Juga sinkronkan ke .env jika memungkinkan
                $this->updateEnvFile([
                    'email.SMTPUser' => $newConfig['smtp_user'],
                    'email.SMTPPass' => $newConfig['smtp_pass'],
                    'email.fromEmail' => $newConfig['from_email'],
                    'email.SMTPHost' => $newConfig['smtp_host'],
                    'email.SMTPPort' => (string)$newConfig['smtp_port'],
                    'email.SMTPCrypto' => $newConfig['smtp_secure'],
                ]);

                if ($action === 'save') {
                    $message = 'Konfigurasi SMTP berhasil disimpan dan disinkronkan!';
                    $messageType = 'success';
                } elseif ($action === 'test_send') {
                    $targetEmail = trim($this->request->getPost('target_email') ?? '');
                    if (empty($targetEmail)) {
                        $targetEmail = $newConfig['test_recipient'] ?: $newConfig['smtp_user'];
                    }

                    if (empty($targetEmail)) {
                        $message = 'Tentukan alamat email penerima uji coba pada kolom formulir.';
                        $messageType = 'danger';
                    } else {
                        $randomOtp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                        $subject = "UJI COBA: Kode OTP Keamanan 2FA ({$randomOtp}) — PT ANTAM Tbk";

                        $htmlMsg = "
                        <!DOCTYPE html>
                        <html lang='id'>
                        <head>
                          <meta charset='utf-8'>
                          <title>Uji Coba Kode OTP Smart Learning ANTAM</title>
                          <style>
                            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 24px; color: #1e293b; }
                            .email-card { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
                            .header { background: linear-gradient(135deg, #0f766e, #0d9488); padding: 32px 24px; text-align: center; color: #ffffff; }
                            .header h1 { margin: 0; font-size: 22px; font-weight: 800; }
                            .content { padding: 32px 28px; }
                            .otp-box { background: #f8fafc; border: 2px dashed #0f766e; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0; }
                            .otp-code { font-family: 'Consolas', monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #0f766e; margin: 0; }
                          </style>
                        </head>
                        <body>
                          <div class='email-card'>
                            <div class='header'>
                              <h1>PT ANTAM Tbk &bull; Smart Learning</h1>
                              <p>Uji Coba Pengiriman Email Nyata (2FA OTP)</p>
                            </div>
                            <div class='content'>
                              <p>Halo Penguji,</p>
                              <p>Ini adalah pesan konfirmasi bahwa integrasi SMTP pengiriman email kode OTP pada <strong>Smart Learning PT ANTAM Tbk</strong> telah aktif dan berhasil terhubung secara nyata ke kotak masuk Anda!</p>
                              <div class='otp-box'>
                                <div style='font-size:12px; font-weight:700; color:#64748b;'>KODE OTP UJI COBA ANDA:</div>
                                <div class='otp-code'>{$randomOtp}</div>
                                <div style='font-size:12px; color:#64748b; margin-top:8px;'>⏱ Masa berlaku kode: 10 Menit</div>
                              </div>
                              <p style='font-size:13px; color:#64748b;'>Waktu pengiriman: " . date('d M Y, H:i:s T') . "</p>
                            </div>
                          </div>
                        </body>
                        </html>";

                        $tempConfig = $newConfig;
                        $tempConfig['smtp_enabled'] = true;
                        $res = SmtpClient::send($targetEmail, $subject, $htmlMsg, $tempConfig);
                        $testLogs = $res['logs'] ?? [];

                        if (!empty($res['success'])) {
                            $message = "BERHASIL! Email berisi kode OTP telah terkirim ke {$targetEmail}. Silakan periksa Inbox atau folder Spam Anda!";
                            $messageType = 'success';
                        } else {
                            $message = "PENGIRIMAN GAGAL: " . ($res['message'] ?? 'Periksa kredensial SMTP.');
                            $messageType = 'danger';
                        }
                    }
                }
            } elseif ($action === 'update_account_email') {
                $targetUser = strtolower(trim($this->request->getPost('target_user') ?? ''));
                $newEmail   = strtolower(trim($this->request->getPost('new_email') ?? ''));

                if (empty($targetUser) || empty($newEmail)) {
                    $message = 'Pilih akun dan masukkan alamat email yang valid.';
                    $messageType = 'danger';
                } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Format alamat email tidak valid.';
                    $messageType = 'danger';
                } else {
                    $this->userModel->update($targetUser, ['email' => $newEmail]);
                    $message = "Alamat email untuk akun '{$targetUser}' berhasil diperbarui menjadi {$newEmail}!";
                    $messageType = 'success';
                }
            }
        }

        $allUsers = $this->userModel->select('username, name, email, role, type')->findAll();

        return view('mail_test/index', [
            'title'         => 'Konfigurasi & Uji Coba SMTP Email — PT ANTAM Tbk',
            'currentConfig' => $currentConfig,
            'message'       => $message,
            'messageType'   => $messageType,
            'testLogs'      => $testLogs,
            'allUsers'      => $allUsers
        ]);
    }

    private function updateEnvFile(array $updates): void
    {
        $envPath = ROOTPATH . '.env';
        if (!file_exists($envPath)) return;

        $content = file_get_contents($envPath);
        foreach ($updates as $key => $val) {
            $escapedKey = preg_quote($key, '/');
            if (preg_match("/^{$escapedKey}\s*=/m", $content)) {
                $content = preg_replace("/^{$escapedKey}\s*=.*$/m", "{$key} = '{$val}'", $content);
            }
        }
        @file_put_contents($envPath, $content);
    }
}

