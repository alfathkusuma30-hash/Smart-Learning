# PANDUAN ARSITEKTUR CODEIGNITER 4 (MVC)
## PT ANTAM Tbk — Smart Learning Portal

Dokumen ini memetakan seluruh struktur proyek setelah dimigrasi penuh ke framework **CodeIgniter 4 (v4.7.6)** dengan pola arsitektur **Model-View-Controller (MVC)**.

---

### 1. Peta Direktori Utama

```
Smart_learning/
│
├── app/                          <-- [INTI APLIKASI] Seluruh kode logika, database, & tampilan
│   ├── Config/                   <-- Konfigurasi sistem (Routes, Email, Database, Filters)
│   │   ├── Routes.php            <-- Pemetaan URL ke Controller
│   │   └── Email.php             <-- Pengaturan protokol SMTP
│   │
│   ├── Controllers/              <-- [LAPISAN 1: CONTROLLERS] Pengendali Logika Bisnis
│   │   ├── AuthController.php        <-- Login, 2FA OTP Email, Registrasi, Logout
│   │   ├── HomeController.php        <-- Halaman Home (Portal Overview)
│   │   ├── DashboardController.php   <-- Dashboard KPI & Desk Tent Calendar
│   │   ├── LearningBankController.php<-- Katalog Modul Pelatihan K3
│   │   ├── MyLearningController.php  <-- Track Pelatihan & Portofolio Sertifikat
│   │   ├── NotificationController.php<-- Pusat Alarm & Notifikasi K3
│   │   ├── AdminPanelController.php  <-- Konsol Verifikasi Usulan & Monitoring
│   │   ├── ReportsController.php     <-- Laporan Kepatuhan & Audit ESDM
│   │   ├── SettingsController.php    <-- Pengaturan Akun, Profil, & Keamanan
│   │   └── ApiController.php         <-- Endpoint Pusat untuk AJAX & Interaksi Form
│   │
│   ├── Models/                   <-- [LAPISAN 2: MODELS] Pengelola Database MySQL
│   │   ├── UserModel.php             <-- Tabel `users` (Akun, Auth, OTP, Hash Password, DNS Check)
│   │   ├── ActivityModel.php         <-- Tabel `activities` (Kegiatan, Status, Modul, Progress)
│   │   ├── CertificateModel.php      <-- Tabel `certificates` (Penerbitan & Verifikasi Sertifikat)
│   │   ├── LnaModel.php              <-- Tabel `lna_catalog` (Katalog Modul & Kebutuhan LNA)
│   │   └── NotificationModel.php     <-- Tabel `notifications` (Alarm, Notifikasi, Audit Trail)
│   │
│   ├── Views/                    <-- [LAPISAN 3: VIEWS] Antarmuka Tampilan Pengguna
│   │   ├── layout/
│   │   │   └── main.php              <-- Master Layout (Topbar, Dock Sidebar, Modal Global)
│   │   ├── auth/
│   │   │   └── index.php             <-- Tampilan Login, Verifikasi 2FA, & Registrasi
│   │   ├── home/index.php            <-- Tampilan Home Portal
│   │   ├── dashboard/index.php       <-- Tampilan Dashboard & Kalender Operasi
│   │   ├── learning_bank/index.php   <-- Tampilan Katalog Modul
│   │   ├── my_learning/index.php     <-- Tampilan Tracking & Sertifikat E-Learning
│   │   ├── notifications/index.php   <-- Tampilan Pusat Alarm & Notifikasi
│   │   ├── admin_panel/index.php     <-- Tampilan Konsol Admin & Verifikasi
│   │   ├── reports/index.php         <-- Tampilan Laporan Audit Kepatuhan
│   │   └── settings/index.php        <-- Tampilan Profil & Pengaturan
│   │
│   ├── Filters/
│   │   └── AuthFilter.php        <-- Penjaga Akses (Wajib login untuk membuka aplikasi)
│   ├── Helpers/
│   │   └── antam_helper.php      <-- Fungsi pembantu tanggal Indonesia, masking email, dll.
│   └── Libraries/
│       └── AntamMailer.php       <-- Mesin pengirim email HTML resmi via SMTP
│
├── public/                       <-- [ROOT PUBLIK WEB] Folder aset yang diakses browser
│   ├── index.php                 <-- Front controller utama CodeIgniter 4
│   ├── .htaccess                 <-- Rewrite URL & proteksi keamanan
│   ├── assets/
│   │   ├── css/style.css         <-- Styling korporat ANTAM (Deep Teal & Gold)
│   │   ├── js/script.js          <-- Logika interaktif JavaScript frontend
│   │   └── img/                  <-- Logo resmi ANTAM & gambar latar gedung Pomalaa
│   └── uploads/                  <-- Berkas unggahan pengguna (absensi, avatar, template)
│
├── includes/                     <-- Konfigurasi runtime tambahan (mail_config.php)
├── writable/                     <-- Berkas sementara server (logs, cache, session)
├── .env                          <-- Variabel lingkungan (Database & SMTP Email)
├── .htaccess                     <-- Forwarder root otomatis ke folder `public/`
├── index.php                     <-- Forwarder root Apache
└── start_public_beta.bat         <-- Launcher online Cloudflare Tunnel untuk demo ke atasan
```

---

### 2. Bagaimana Pola Kerja MVC di Proyek Ini?

```
User (Browser)
      │
      ▼ (1. Request URL, misal: /dashboard)
[ Routes.php ]
      │
      ▼ (2. Dicek keamanannya oleh AuthFilter)
[ AuthFilter ]
      │
      ▼ (3. Diteruskan ke Controller)
[ DashboardController ]
      │
      ├──► (4. Mengambil data dari MySQL) ──► [ ActivityModel / LnaModel ]
      │◄── (5. Data hasil query dikembalikan) ◄─────────┘
      │
      ▼ (6. Mengirim data ke View)
[ app/Views/dashboard/index.php ] ◄── Di-wrap oleh ── [ app/Views/layout/main.php ]
      │
      ▼ (7. Hasil HTML dirender sempurna ke layar user)
Browser Pengguna
```

---

### 3. Di Mana Harus Mengedit Jika Ingin Mengubah Sesuatu?

| Yang Ingin Diubah | Lokasi File |
|---|---|
| **Menambah kolom / query database MySQL** | [`app/Models/`](file:///c:/xampp/htdocs/Smart_learning/app/Models/) |
| **Mengubah alur logika, permission, atau data yang dikirim** | [`app/Controllers/`](file:///c:/xampp/htdocs/Smart_learning/app/Controllers/) |
| **Mengubah tampilan HTML / teks halaman** | [`app/Views/`](file:///c:/xampp/htdocs/Smart_learning/app/Views/) |
| **Mengubah warna, font, atau tata letak CSS** | [`public/assets/css/style.css`](file:///c:/xampp/htdocs/Smart_learning/public/assets/css/style.css) |
| **Mengubah fungsi tombol / interaksi JavaScript** | [`public/assets/js/script.js`](file:///c:/xampp/htdocs/Smart_learning/public/assets/js/script.js) |
| **Mengubah kredensial database atau email SMTP** | [`.env`](file:///c:/xampp/htdocs/Smart_learning/.env) |

