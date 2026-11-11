<?php

namespace App\Libraries;

use Config\Services;

class AntamMailer
{
    /**
     * Kirim email 2FA resmi PT ANTAM Tbk
     * 
     * @return array ['success' => bool, 'message' => string, 'logs' => array]
     */
    public static function send2FAOtp(string $toEmail, string $name, string $otpCode, string $roleTitle): array
    {
        if (empty($toEmail)) {
            return ['success' => false, 'message' => 'Alamat email penerima kosong.'];
        }

        // Cek jika ada konfigurasi manual di includes/mail_config.php
        $legacyConfig = [];
        if (file_exists(ROOTPATH . 'includes/mail_config.php')) {
            $legacyConfig = include ROOTPATH . 'includes/mail_config.php';
        }

        $actualRecipient = $toEmail;
        if (!empty($legacyConfig['test_recipient']) && (strpos($toEmail, '@antam.com') !== false || empty($toEmail))) {
            $actualRecipient = $legacyConfig['test_recipient'];
        }

        $subject = "Kode OTP Keamanan (2FA): {$otpCode} — Smart Learning PT ANTAM Tbk";

        $message = "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
          <meta charset='utf-8'>
          <title>Kode OTP Smart Learning ANTAM</title>
          <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 24px; color: #1e293b; }
            .email-card { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .header { background: linear-gradient(135deg, #0f766e, #0d9488); padding: 32px 24px; text-align: center; color: #ffffff; }
            .header h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
            .header p { margin: 8px 0 0; font-size: 13px; opacity: 0.9; }
            .content { padding: 32px 28px; }
            .badge { display: inline-block; background: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 5px 14px; border-radius: 99px; margin-bottom: 16px; letter-spacing: 1px; }
            .greeting { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
            .desc { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
            .otp-box { background: #f8fafc; border: 2px dashed #0f766e; border-radius: 12px; padding: 22px; text-align: center; margin: 24px 0; }
            .otp-label { font-size: 12px; font-weight: 700; color: #64748b; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
            .otp-code { font-family: 'Consolas', 'Courier New', monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #0f766e; margin: 0; }
            .otp-timer { font-size: 12px; color: #64748b; margin-top: 10px; font-weight: 500; }
            .security-notice { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 14px 18px; font-size: 12px; color: #92400e; line-height: 1.6; margin-top: 24px; }
            .footer { background: #f8fafc; padding: 20px 24px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; }
          </style>
        </head>
        <body>
          <div class='email-card'>
            <div class='header'>
              <h1>PT ANTAM Tbk &bull; Smart Learning</h1>
              <p>Sistem Terintegrasi Pembelajaran & Kepatuhan K3 ESDM Site Pomalaa</p>
            </div>
            <div class='content'>
              <div class='badge'>Autentikasi Dua Faktor (2FA)</div>
              <div class='greeting'>Halo, " . htmlspecialchars($name) . " (" . htmlspecialchars($roleTitle) . ")</div>
              <div class='desc'>
                Permintaan autentikasi masuk ke akun Anda terdeteksi. Silakan gunakan 6-digit kode OTP (One-Time Password) di bawah ini untuk memverifikasi identitas Anda:
              </div>
              
              <div class='otp-box'>
                <div class='otp-label'>KODE VERIFIKASI RESMI</div>
                <div class='otp-code'>" . htmlspecialchars($otpCode) . "</div>
                <div class='otp-timer'>⏱ Masa berlaku kode: 10 Menit</div>
              </div>
              
              <div class='security-notice'>
                <strong>Peringatan Keamanan ANTAM:</strong> Jangan pernah memberikan kode OTP ini kepada siapa pun, termasuk administrator atau pihak yang mengaku dari Human Capital. Sistem ANTAM tidak pernah meminta kode OTP Anda melalui pesan telepon atau chat.
              </div>
            </div>
            <div class='footer'>
              &copy; " . date('Y') . " PT ANTAM Tbk &bull; Human Capital & Corporate University Pomalaa<br>
              Pesan otomatis ini di-generate oleh Sistem Smart Learning Kepatuhan K3 ESDM.
            </div>
          </div>
        </body>
        </html>";

        $smtpUser = !empty($legacyConfig['smtp_user']) ? $legacyConfig['smtp_user'] : env('email.SMTPUser', '');
        $smtpPass = !empty($legacyConfig['smtp_pass']) ? str_replace(' ', '', $legacyConfig['smtp_pass']) : env('email.SMTPPass', '');
        $smtpHost = !empty($legacyConfig['smtp_host']) ? $legacyConfig['smtp_host'] : env('email.SMTPHost', 'smtp.gmail.com');
        $smtpPort = !empty($legacyConfig['smtp_port']) ? (int)$legacyConfig['smtp_port'] : (int)env('email.SMTPPort', 465);
        $smtpCrypto = !empty($legacyConfig['smtp_secure']) ? $legacyConfig['smtp_secure'] : env('email.SMTPCrypto', 'ssl');
        $fromEmail = !empty($legacyConfig['from_email']) ? $legacyConfig['from_email'] : ($smtpUser ?: 'security@antam.com');
        $fromName = !empty($legacyConfig['from_name']) ? $legacyConfig['from_name'] : 'PT ANTAM Tbk — Smart Learning Security';

        // 1. Coba via CI4 Email Service dengan konfigurasi yang tepat
        try {
            $emailService = Services::email();
            $emailService->initialize([
                'protocol'   => 'smtp',
                'SMTPHost'   => $smtpHost,
                'SMTPUser'   => $smtpUser,
                'SMTPPass'   => $smtpPass,
                'SMTPPort'   => $smtpPort,
                'SMTPCrypto' => $smtpCrypto,
                'mailType'   => 'html',
                'charset'    => 'UTF-8',
                'newline'    => "\r\n",
                'CRLF'       => "\r\n",
                'fromEmail'  => $fromEmail,
                'fromName'   => $fromName
            ]);

            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setTo($actualRecipient);
            $emailService->setSubject($subject);
            $emailService->setMessage($message);

            if ($emailService->send(false)) {
                return ['success' => true, 'message' => "Email OTP berhasil dikirim ke {$actualRecipient} via CI4 Email."];
            } else {
                $err = strip_tags($emailService->printDebugger(['headers']));
                log_message('error', 'CI4 Email Error: ' . $err);
            }
        } catch (\Throwable $e) {
            log_message('error', 'CI4 Email Service Exception: ' . $e->getMessage());
        }

        // 2. Fallback via Socket Client untuk menangkap error detail (BadCredentials dll)
        $res = SmtpClient::send($actualRecipient, $subject, $message, [
            'smtp_enabled'   => true,
            'smtp_host'      => $smtpHost,
            'smtp_port'      => $smtpPort,
            'smtp_secure'    => $smtpCrypto,
            'smtp_user'      => $smtpUser,
            'smtp_pass'      => $smtpPass,
            'from_email'     => $fromEmail,
            'from_name'      => $fromName,
            'test_recipient' => $legacyConfig['test_recipient'] ?? ''
        ]);

        return $res;
    }
}
