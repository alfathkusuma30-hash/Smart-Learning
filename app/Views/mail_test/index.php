<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 2rem 1rem; }
        .test-container { max-width: 820px; margin: 0 auto; background: #ffffff; color: #1e293b; border-radius: 20px; padding: 2.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .test-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 1.5rem; margin-bottom: 2rem; }
        .test-title { font-size: 1.4rem; font-weight: 800; color: #0f766e; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .form-full { grid-column: 1 / -1; }
        .field-label { display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; letter-spacing: 0.5px; }
        .field-input { width: 100%; box-sizing: border-box; padding: 0.75rem 1rem; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.9rem; outline: none; transition: 0.2s; background: #f8fafc; color: #0f172a; }
        .field-input:focus { border-color: #0f766e; background: #fff; box-shadow: 0 0 0 3px rgba(15,118,110,0.15); }
        .btn-act { padding: 0.85rem 1.5rem; border-radius: 10px; font-weight: 700; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: 0.2s; }
        .btn-save { background: #0f766e; color: #ffffff; }
        .btn-save:hover { background: #0d9488; }
        .btn-test { background: #f59e0b; color: #ffffff; }
        .btn-test:hover { background: #d97706; }
        .alert-box { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 0.75rem; line-height: 1.5; }
        .alert-success { background: #f0fdfa; border: 1px solid #99f6e4; color: #0f766e; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .logs-box { background: #090d16; color: #38bdf8; font-family: 'Consolas', monospace; font-size: 0.8rem; padding: 1.25rem; border-radius: 12px; max-height: 260px; overflow-y: auto; margin-top: 0.5rem; line-height: 1.6; border: 1px solid #1e293b; }
        .guide-box { background: #f8fafc; border: 1.5px dashed #0f766e; padding: 1.5rem; border-radius: 14px; margin-top: 2rem; font-size: 0.85rem; color: #334155; }
        .guide-box a { color: #0f766e; font-weight: 700; text-decoration: underline; }
        @media(max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } .test-container { padding: 1.5rem; } }
    </style>
</head>
<body>

<div class="test-container">
    <div class="test-header">
        <div>
            <h1 class="test-title">
                <i class="ph-fill ph-envelope-simple-check"></i>
                Konfigurasi &amp; Uji Coba Email SMTP ANTAM
            </h1>
            <p style="margin: 0.35rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                Uji langsung pengiriman email OTP 2FA ke akun Gmail asli secara real-time
            </p>
        </div>
        <a href="<?= base_url('/') ?>" class="btn-act" style="background: #f1f5f9; color: #475569; padding: 0.6rem 1rem; font-size: 0.8rem; text-decoration: none;">
            <i class="ph-bold ph-arrow-left"></i> Kembali ke Login
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert-box <?= $messageType === 'success' ? 'alert-success' : 'alert-danger' ?>">
            <i class="ph-bold <?= $messageType === 'success' ? 'ph-check-circle' : 'ph-warning-circle' ?>" style="font-size: 1.25rem; flex-shrink: 0; margin-top: 2px;"></i>
            <div><?= esc($message) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('test-mail') ?>">
        <div class="form-grid-2">
            
            <div class="form-full" style="background: #f0fdfa; padding: 1.25rem; border-radius: 12px; border: 1px solid #99f6e4; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="color: #0f766e; display: block; font-size: 1rem;">Aktifkan Layanan Pengiriman Email (SMTP)</strong>
                    <span style="font-size: 0.8rem; color: #475569;">Jika diaktifkan, kode OTP login akan dikirimkan otomatis ke inbox email pengguna</span>
                </div>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 700; color: #0f766e; font-size: 0.95rem;">
                    <input type="checkbox" name="smtp_enabled" value="1" <?= !empty($currentConfig['smtp_enabled']) ? 'checked' : '' ?> style="width: 22px; height: 22px; accent-color: #0f766e;">
                    <span>Aktif</span>
                </label>
            </div>

            <div>
                <label class="field-label">Server SMTP Host</label>
                <input type="text" name="smtp_host" class="field-input" value="<?= esc($currentConfig['smtp_host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <label class="field-label">Port</label>
                    <input type="number" name="smtp_port" class="field-input" value="<?= esc($currentConfig['smtp_port'] ?? 465) ?>" placeholder="465" required>
                </div>
                <div>
                    <label class="field-label">Protokol Keamanan</label>
                    <select name="smtp_secure" class="field-input">
                        <option value="ssl" <?= ($currentConfig['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                        <option value="tls" <?= ($currentConfig['smtp_secure'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS / STARTTLS (Port 587)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="field-label">Email Pengirim (Akun Gmail Anda)</label>
                <input type="email" name="smtp_user" class="field-input" value="<?= esc($currentConfig['smtp_user'] ?? '') ?>" placeholder="contoh: akunanda@gmail.com" required>
            </div>

            <div>
                <label class="field-label">Sandi Aplikasi Google (16 Karakter App Password)</label>
                <input type="text" name="smtp_pass" class="field-input" value="<?= esc($currentConfig['smtp_pass'] ?? '') ?>" placeholder="contoh: abcd efgh ijkl mnop" required autocomplete="off">
            </div>

            <div>
                <label class="field-label">Nama Pengirim Email</label>
                <input type="text" name="from_name" class="field-input" value="<?= esc($currentConfig['from_name'] ?? 'PT ANTAM Tbk — Smart Learning Security') ?>" placeholder="PT ANTAM Tbk — Smart Learning Security">
            </div>

            <div>
                <label class="field-label">Target Email Penerima Uji Coba</label>
                <input type="email" name="test_recipient" class="field-input" value="<?= esc($currentConfig['test_recipient'] ?? '') ?>" placeholder="Masukkan email pribadi Anda (cth: emailanda@gmail.com)">
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" name="action" value="save" class="btn-act btn-save">
                    <i class="ph-bold ph-floppy-disk"></i> Simpan Konfigurasi
                </button>
                <button type="submit" name="action" value="test_send" class="btn-act btn-test">
                    <i class="ph-bold ph-paper-plane-tilt"></i> Kirim Uji Coba Sekarang
                </button>
            </div>
        </div>
    </form>

    <?php if (!empty($testLogs)): ?>
        <div style="margin-top: 2rem;">
            <strong style="font-size: 0.85rem; color: #0f172a; display: flex; align-items: center; gap: 0.4rem;">
                <i class="ph-fill ph-terminal-window" style="color: #0f766e;"></i> Log Komunikasi Server SMTP (Live Handshake):
            </strong>
            <div class="logs-box">
                <?php foreach ($testLogs as $logLine): ?>
                    <div><?= esc($logLine) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="guide-box">
        <strong style="display: flex; align-items: center; gap: 0.4rem; font-size: 1rem; color: #0f766e; margin-bottom: 0.75rem;">
            <i class="ph-fill ph-lightbulb"></i> Cara Mendapatkan "Sandi Aplikasi" (App Password) dari Google Mail:
        </strong>
        <p style="margin: 0 0 0.75rem 0; color: #475569;">
            Google tidak mengizinkan kata sandi akun biasa untuk SMTP pihak ketiga sejak Mei 2022. Ikuti 4 langkah cepat ini:
        </p>
        <ol style="margin: 0; padding-left: 1.25rem; line-height: 1.7;">
            <li>Buka halaman keamanan akun Google: <a href="https://myaccount.google.com/security" target="_blank">myaccount.google.com/security</a>.</li>
            <li>Pastikan fitur <strong>Verifikasi 2 Langkah (2-Step Verification)</strong> sudah <strong>Aktif</strong>.</li>
            <li>Ketik di kolom pencarian Akun Google: <strong>"Sandi Aplikasi"</strong> atau buka langsung tautan: <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a>.</li>
            <li>Beri nama aplikasi, misal <strong>Smart Learning ANTAM</strong>, lalu klik <strong>Buat</strong>.</li>
            <li>Salin <strong>16 karakter</strong> huruf yang muncul ke kolom <em>Sandi Aplikasi Google</em> di atas, lalu klik <strong>Simpan &amp; Kirim Uji Coba</strong>.</li>
        </ol>
    </div>

    <!-- Update Alamat Email Akun -->
    <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <i class="ph-fill ph-user-gear" style="font-size: 1.25rem; color: #0f766e;"></i>
            <strong style="color: #0f766e; font-size: 1rem;">Hubungkan Email Nyata ke Akun Personel yang Ada</strong>
        </div>
        <p style="font-size: 0.8rem; color: #64748b; margin: 0 0 1rem 0;">
            Ganti email akun dummy agar saat Anda login menggunakan username tersebut, kode OTP 2FA langsung dikirimkan ke email pribadi Anda.
        </p>

        <form method="POST" action="<?= base_url('test-mail') ?>">
            <input type="hidden" name="action" value="update_account_email">
            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.75rem; align-items: flex-end;">
                <div>
                    <label class="field-label">Pilih Akun</label>
                    <select name="target_user" class="field-input">
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= esc($u['username']) ?>">
                                <?= esc($u['username']) ?> (<?= esc($u['name']) ?> - <?= esc($u['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label">Email Nyata Baru</label>
                    <input type="email" name="new_email" class="field-input" placeholder="contoh: emailanda@gmail.com" required>
                </div>
                <div>
                    <button type="submit" class="btn-act btn-save" style="padding: 0.75rem 1.25rem;">
                        <i class="ph-bold ph-check"></i> Simpan Email Akun
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

</body>
</html>

