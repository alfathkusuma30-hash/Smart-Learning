<?php

namespace App\Libraries;

/**
 * Standalone SMTP Client — PT ANTAM Tbk Smart Learning Portal
 * Socket-level SMTP Client dengan TLS / SSL.
 */
class SmtpClient
{
    public static function send(string $toEmail, string $subject, string $htmlMessage, ?array $config = null): array
    {
        $logs = [];

        if ($config === null) {
            $configFile = ROOTPATH . 'includes/mail_config.php';
            $config = file_exists($configFile) ? include $configFile : [];
        }

        $smtpEnabled = !empty($config['smtp_enabled']) || !empty(env('email.protocol'));
        $host        = $config['smtp_host'] ?? env('email.SMTPHost', 'smtp.gmail.com');
        $port        = (int)($config['smtp_port'] ?? env('email.SMTPPort', 465));
        $secure      = strtolower($config['smtp_secure'] ?? env('email.SMTPCrypto', 'ssl'));
        $user        = trim($config['smtp_user'] ?? env('email.SMTPUser', ''));
        $pass        = trim($config['smtp_pass'] ?? env('email.SMTPPass', ''));
        $fromEmail   = !empty($config['from_email']) ? trim($config['from_email']) : ($user ?: env('email.fromEmail', 'security@antam.com'));
        $fromName    = $config['from_name'] ?? env('email.fromName', 'PT ANTAM Tbk — Smart Learning Security');
        $testTarget  = trim($config['test_recipient'] ?? '');

        // Redirect test recipient jika target dummy antam.com
        $actualRecipient = trim($toEmail);
        if (!empty($testTarget) && (strpos($actualRecipient, '@antam.com') !== false || empty($actualRecipient))) {
            $logs[] = "Info: Penerima dialihkan dari {$actualRecipient} ke {$testTarget}.";
            $actualRecipient = $testTarget;
        }

        if (empty($actualRecipient)) {
            return ['success' => false, 'message' => 'Alamat email penerima kosong.', 'logs' => $logs];
        }

        if (empty($user) || empty($pass)) {
            $logs[] = "Kredensial SMTP user/pass masih kosong.";
            return [
                'success' => false,
                'message' => 'Layanan SMTP belum dikonfigurasi. Sandi Aplikasi Gmail masih kosong.',
                'logs'    => $logs
            ];
        }

        $cleanPass = str_replace(' ', '', $pass);
        $timeout = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $prefix = ($secure === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';
        $connectHost = $prefix . $host;

        $logs[] = "Menghubungi server SMTP: {$connectHost}:{$port}...";
        $socket = @stream_socket_client("{$connectHost}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            $logs[] = "Koneksi gagal: {$errstr} ({$errno})";
            return [
                'success' => false,
                'message' => "Gagal terhubung ke server SMTP {$host}:{$port} ({$errstr})",
                'logs'    => $logs
            ];
        }

        stream_set_timeout($socket, $timeout);

        $readResponse = function() use ($socket, &$logs) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            $logs[] = "< " . trim($response);
            return $response;
        };

        $sendCommand = function(string $cmd, ?string $mask = null) use ($socket, &$logs) {
            $logCmd = $mask !== null ? $mask : $cmd;
            $logs[] = "> " . trim($logCmd);
            fwrite($socket, $cmd . "\r\n");
        };

        // 1. Respon 220
        $res = $readResponse();
        if (substr($res, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "Respon awal server tidak valid: {$res}", 'logs' => $logs];
        }

        // 2. EHLO
        $clientDomain = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $sendCommand("EHLO {$clientDomain}");
        $res = $readResponse();

        // 3. STARTTLS (Port 587)
        if ($secure === 'tls' || ($port === 587 && strpos($res, 'STARTTLS') !== false)) {
            $sendCommand("STARTTLS");
            $res = $readResponse();
            if (substr($res, 0, 3) !== '220') {
                fclose($socket);
                return ['success' => false, 'message' => "STARTTLS gagal: {$res}", 'logs' => $logs];
            }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                fclose($socket);
                return ['success' => false, 'message' => 'Enkripsi TLS gagal dinegosiasikan.', 'logs' => $logs];
            }

            $sendCommand("EHLO {$clientDomain}");
            $res = $readResponse();
        }

        // 4. AUTH LOGIN
        $sendCommand("AUTH LOGIN");
        $res = $readResponse();
        if (substr($res, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => "AUTH LOGIN tidak didukung server: {$res}", 'logs' => $logs];
        }

        // Username
        $sendCommand(base64_encode($user));
        $res = $readResponse();
        if (substr($res, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => "Username SMTP ditolak: {$res}", 'logs' => $logs];
        }

        // Password
        $sendCommand(base64_encode($cleanPass), '****** [PASSWORD DISAMARKAN]');
        $res = $readResponse();
        if (substr($res, 0, 3) !== '235') {
            fclose($socket);
            return [
                'success' => false,
                'message' => "Autentikasi gagal (Kata sandi atau Sandi Aplikasi Gmail salah): {$res}",
                'logs'    => $logs
            ];
        }

        // 5. MAIL FROM
        $sendCommand("MAIL FROM:<{$fromEmail}>");
        $res = $readResponse();
        if (substr($res, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "MAIL FROM ditolak: {$res}", 'logs' => $logs];
        }

        // 6. RCPT TO
        $sendCommand("RCPT TO:<{$actualRecipient}>");
        $res = $readResponse();
        if (substr($res, 0, 3) !== '250' && substr($res, 0, 3) !== '251') {
            fclose($socket);
            return ['success' => false, 'message' => "Alamat email tujuan ditolak oleh server ({$actualRecipient}): {$res}", 'logs' => $logs];
        }

        // 7. DATA
        $sendCommand("DATA");
        $res = $readResponse();
        if (substr($res, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'message' => "Perintah DATA ditolak: {$res}", 'logs' => $logs];
        }

        // MIME
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $headers  = "From: {$encodedFromName} <{$fromEmail}>\r\n";
        $headers .= "To: <{$actualRecipient}>\r\n";
        $headers .= "Subject: {$encodedSubject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "X-Mailer: PT-ANTAM-SmartLearning-Mailer/2.0\r\n";
        $headers .= "X-Priority: 1 (Highest)\r\n";

        $bodyBase64 = chunk_split(base64_encode($htmlMessage));
        $fullPayload = $headers . "\r\n" . $bodyBase64 . "\r\n.";

        fwrite($socket, $fullPayload . "\r\n");
        $logs[] = "> [MIME Payload dikirim ke server...]";

        $res = $readResponse();
        if (substr($res, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "Pengiriman pesan gagal: {$res}", 'logs' => $logs];
        }

        // 8. QUIT
        $sendCommand("QUIT");
        $readResponse();
        fclose($socket);

        $logs[] = "Email berhasil dikirim dan diterima oleh server tujuan untuk: {$actualRecipient}!";
        return [
            'success'   => true,
            'message'   => "Email berhasil dikirim ke {$actualRecipient}!",
            'recipient' => $actualRecipient,
            'logs'      => $logs
        ];
    }
}

