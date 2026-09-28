-- ==========================================================
-- SMART LEARNING PORTAL — PT ANTAM TBK
-- Database Schema & Initial Data Seed
-- Compatibility: MySQL 5.7+ / MariaDB 10.3+ / XAMPP / InfinityFree
-- ==========================================================

-- Jika di localhost (XAMPP) belum ada database 'smart_learning', Anda bisa buat dulu atau aktifkan baris berikut:
-- CREATE DATABASE IF NOT EXISTS `smart_learning` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `smart_learning`;
-- (Di hosting seperti InfinityFree, biarkan baris di atas nonaktif karena database sudah dibuat via cPanel)


-- ----------------------------------------------------------
-- 1. TABEL: users (Data Pengguna & Profil Pegawai/Admin)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `user_id` VARCHAR(20) DEFAULT NULL,
  `nrp` VARCHAR(50) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(100) DEFAULT NULL,
  `type` ENUM('User','Admin') NOT NULL DEFAULT 'User',
  `dept` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `avatar_color` VARCHAR(20) DEFAULT '#0f766e',
  `bio` TEXT DEFAULT NULL,
  `reminder_freq` VARCHAR(50) DEFAULT '24 Jam',
  `wa_notif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`username`, `password`, `user_id`, `nrp`, `name`, `role`, `type`, `dept`, `email`, `phone`, `avatar_color`, `bio`, `reminder_freq`, `wa_notif`) VALUES
('egy', 'password123', 'EP', 'ANTAM-08912', 'Egy Pratama', 'Operasional Tambang', 'User', 'Mining Operations Underground', 'egy.pratama@antam.com', '0812-3456-7890', '#d4af37', 'Operator Tambang Bawah Tanah Site Pongkor. Fokus pada pemenuhan K3 dan standar operasional ESDM.', '24 Jam', 1),
('budi', 'password123', 'BS', 'ANTAM-07844', 'Budi Santoso', 'Processing Plant', 'User', 'Plant & Smelter', 'budi.santoso@antam.com', '0813-9876-5432', '#38bdf8', 'Teknisi Pengolahan Emas & Logam Mulia. Pengawas K3 Pabrik.', '12 Jam', 1),
('andi', 'password123', 'AW', 'ANTAM-06519', 'Andi Wijaya', 'Logistik & Gudang', 'User', 'Supply Chain & Bahan Peledak', 'andi.wijaya@antam.com', '0811-2233-4455', '#a855f7', 'Koordinator Pergudangan dan Distribusi Handak Site Pongkor.', '24 Jam', 0),
('admin', 'admin123', 'LO', 'ANTAM-HC001', 'Learning Ops Admin', 'Learning & Development', 'Admin', 'Human Capital & Corporate University', 'admin.lnd@antam.com', '0811-9988-7766', '#10b981', 'Super Administrator Sistem Pembelajaran & Evaluasi Kepatuhan K3 Terintegrasi PT ANTAM Tbk.', 'Realtime', 1);

-- ----------------------------------------------------------
-- 2. TABEL: activities (Kegiatan Pembelajaran & Tracking)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` VARCHAR(50) NOT NULL,
  `user_username` VARCHAR(50) NOT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'Pengembangan Mandiri',
  `category` VARCHAR(100) DEFAULT 'Operasional Tambang',
  `duration` VARCHAR(50) DEFAULT '2 Hari (16 Jam)',
  `level` VARCHAR(50) DEFAULT 'Basic',
  `deadline` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('menunggu_konfirmasi','disetujui','ditolak') NOT NULL DEFAULT 'menunggu_konfirmasi',
  `progress` INT NOT NULL DEFAULT 0,
  `current_module` VARCHAR(255) DEFAULT 'Menunggu Konfirmasi Admin',
  `total_modules` INT NOT NULL DEFAULT 4,
  `completed_modules` INT NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `last_report` TEXT DEFAULT NULL,
  `last_attachment` VARCHAR(255) DEFAULT NULL,
  `last_submitted_at` DATETIME DEFAULT NULL,
  `grade` INT DEFAULT NULL,
  `admin_feedback` TEXT DEFAULT NULL,
  `graded_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `approved_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_username` (`user_username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activities` (`id`, `user_username`, `user_name`, `title`, `type`, `category`, `duration`, `level`, `deadline`, `status`, `progress`, `current_module`, `total_modules`, `completed_modules`, `notes`, `admin_notes`, `last_report`, `last_attachment`, `last_submitted_at`, `created_at`, `approved_at`) VALUES
('act_1788767563_538', 'budi', 'Budi Santoso', 'pelatihan mengemudi bagi peserta magang', 'Pengembangan Mandiri', 'K3 & Keselamatan Tambang', '2 Hari (16 Jam)', 'Basic', '26 Agu 2026', 'disetujui', 0, 'Modul 1: Pengenalan & Dasar Kompetensi', 4, 0, 'Usulan kegiatan pembelajaran baru.', 'Disetujui oleh Learning Ops Administrator.', NULL, NULL, NULL, '2026-09-07 09:52:43', '2026-09-07 09:53:08'),
('act_1788753692_239', 'egy', 'Egy Pratama', 'pelatihan mengemudi bagi peserta magang', 'Pengembangan Mandiri', 'K3 & Keselamatan Tambang', '2 Hari (16 Jam)', 'Basic', '26 Agu 2026', 'disetujui', 50, 'Unit 3: Evaluasi & Praktik Lapangan', 4, 2, 'Usulan kegiatan pembelajaran baru.', 'Disetujui oleh Learning Ops Administrator.', '1. RINGKASAN PEMAHAMAN MATERI (SOP & K3)\r\nTuliskan rangkuman materi yang telah dipelajari pada unit ini. Contoh: Prosedur operasional tambang bawah tanah, standar ventilasi udara, dan kepatuhan APD wajib...\r\n\r\n2. IDENTIFIKASI RISIKO & PENERAPAN DI AREA KERJA\r\nJelaskan potensi bahaya nyata yang dijumpai di lapangan (misal: potensi gas berbahaya metana/CO, rem alat berat hauler, stabilitas batuan) dan tindakan pengendalian yang diambil...\r\n\r\n3. KESIMPULAN & KOMITMEN KESELAMATAN KERJA\r\nTuliskan komitmen Anda dalam mematuhi seluruh kaidah K3 ESDM saat bertugas di site tambang...', 'LOGBOOK- Annisa Sri Wulandari (1).docx', '2026-09-07 09:49:21', '2026-09-07 06:01:32', '2026-09-07 06:04:58'),
('act_1788752576_276', 'egy', 'Egy Pratama', 'Pengoperasian Dump Truck Bawah Tanah', 'Pengembangan Mandiri', 'K3 & Keselamatan Tambang', '2 Hari (16 Jam)', 'Basic', '26 Agu 2026', 'disetujui', 0, 'Modul 1: Pengenalan & Dasar Kompetensi', 4, 0, 'Usulan kegiatan pembelajaran baru.', 'Disetujui oleh Learning Ops Administrator.', NULL, NULL, NULL, '2026-09-07 05:42:56', '2026-09-07 06:05:24'),
('act_1', 'egy', 'Egy Pratama', 'Safety Induction Site Tambang Pongkor', 'Wajib K3', 'Keselamatan Kerja', '1 Hari (8 Jam)', 'Basic', '12 Agu 2026', 'disetujui', 60, 'Modul 4: Penanganan Bahaya Longsor & Rock Burst', 5, 3, 'Pelatihan wajib kepatuhan ESDM sebelum memasuki terowongan utama.', NULL, NULL, NULL, NULL, '2026-08-01 08:00:00', '2026-08-01 09:00:00'),
('act_2', 'egy', 'Egy Pratama', 'POP Level 1 — Pengawas Operasional Pertama', 'Wajib ESDM', 'Regulasi ESDM', '3 Hari (24 Jam)', 'Basic', '14 Agu 2026', 'disetujui', 30, 'Unit 3: Pelaksanaan Inspeksi & Identifikasi Bahaya', 6, 2, 'Sertifikasi pengawas operasional lapangan wajib perpanjangan.', NULL, NULL, NULL, NULL, '2026-08-05 10:00:00', '2026-08-05 11:30:00'),
('act_3', 'budi', 'Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', 'Sertifikasi', 'K3 Nasional', '5 Hari (40 Jam)', 'Advanced', '17 Agu 2026', 'disetujui', 94, 'Selesai - Menunggu Penerbitan Lisensi Resmi', 5, 5, 'Lulus ujian kuis online dengan nilai 94/100.', NULL, NULL, NULL, NULL, '2026-08-02 09:00:00', '2026-08-02 10:00:00');

-- ----------------------------------------------------------
-- 3. TABEL: certificates (Penerbitan Sertifikat Kelulusan)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `certificates`;
CREATE TABLE `certificates` (
  `id` VARCHAR(50) NOT NULL,
  `user_username` VARCHAR(50) NOT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `course_title` VARCHAR(255) NOT NULL,
  `reg_no` VARCHAR(100) NOT NULL,
  `score` INT NOT NULL DEFAULT 80,
  `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid_until` DATE NOT NULL,
  `issued_by` VARCHAR(100) NOT NULL DEFAULT 'Learning Ops Admin',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_cert_username` (`user_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `certificates` (`id`, `user_username`, `user_name`, `course_title`, `reg_no`, `score`, `issued_at`, `valid_until`, `issued_by`, `status`) VALUES
('cert_initial_budi', 'budi', 'Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', 'ANTAM/K3U/2026/8842', 94, '2026-08-11 16:45:00', '2029-08-11', 'Learning Ops Admin', 'active');

-- ----------------------------------------------------------
-- 4. TABEL: lna_catalog (Katalog Kebutuhan Belajar / LNA)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `lna_catalog`;
CREATE TABLE `lna_catalog` (
  `id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `level` VARCHAR(50) NOT NULL,
  `gap_score` VARCHAR(100) DEFAULT NULL,
  `target_role` VARCHAR(255) DEFAULT NULL,
  `competency_area` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `prerequisites` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lna_catalog` (`id`, `title`, `code`, `category`, `type`, `duration`, `level`, `gap_score`, `target_role`, `competency_area`, `description`, `prerequisites`) VALUES
('lna_1', 'POP Level 1 — Pengawas Operasional Pertama', 'ESDM-POP-01', 'Kepatuhan ESDM', 'Wajib', '3 Hari (24 Jam)', 'Basic', 'Gap Tinggi (Prioritas 1)', 'Semua Foreman & Pengawas Tambang', 'Regulasi ESDM No. 1827', 'Standar kompetensi pengawas operasional pertambangan mineral dan batubara. Membekali pengawas dalam inspeksi bahaya bawah tanah, K3, dan kepatuhan hukum.', 'Pengalaman minimal 1 tahun di site'),
('lna_2', 'Safety Induction Site Tambang Pongkor', 'ANTAM-SAF-02', 'K3 & Keselamatan', 'Wajib', '1 Hari (8 Jam)', 'Basic', 'Kritis (Izin Masuk Site)', 'Seluruh Pegawai Site', 'Protokol Underground K3', 'Pengenalan komprehensif bahaya bawah tanah, prosedur evakuasi darurat, deteksi gas metana/CO, penggunaan respirator SCSR, dan standar kelayakan APD.', 'Medical Check Up Fit to Work'),
('lna_3', 'Sertifikasi K3 Umum & Pertambangan', 'KEMNAKER-K3U-03', 'Sertifikasi Nasional', 'Sertifikasi', '5 Hari (40 Jam)', 'Advanced', 'Gap Sedang (Lisensi)', 'Officer, Supervisor & K3 Team', 'Manajemen Risiko K3 Korporat', 'Program sertifikasi lisensi resmi pemenuhan syarat regulasi Kemnaker & ESDM. Dilengkapi uji materi hukum K3, inspeksi praktis, dan investigasi insiden.', 'Pendidikan min. D3 / S1 dengan pengalaman'),
('lna_4', 'Technical Skill A — Heavy Hauling 777D', 'TECH-OPR-04', 'Teknik Operasional', 'Wajib', '4 Hari (32 Jam)', 'Advanced', 'Gap Spesialis (Hauling)', 'Operator Alat Berat & Hauler', 'Operasional Alat Tambang', 'Manuver tanjakan ekstrem, inspeksi rem retarder hidrolik, penanganan blindspot, dan keselamatan loading point di pit tambang.', 'SIM B2 Umum & SIO Alat Berat'),
('lna_5', 'Leadership & Supervisory Underground', 'LD-SUP-05', 'Leadership & Manajemen', 'Optional', '2 Hari (16 Jam)', 'Intermediate', 'Pengembangan Mandiri', 'Team Leader & Asisten Foreman', 'Manajemen Regu Kerja', 'Pembekalan kepemimpinan regu kerja di terowongan bawah tanah, komunikasi radio darurat, manajemen konflik shif, dan coaching keselamatan berkala.', 'Pengalaman memimpin minimal 6 bulan'),
('lna_6', 'Effective Radio Communication & Mine Dispatch', 'COMM-DSP-06', 'Komunikasi & Logistik', 'Optional', '2 Hari (12 Jam)', 'Basic', 'Standardisasi Komunikasi', 'Dispatch, Logistik, Operator', 'Protokol Komunikasi Radio', 'Kode fonetik standar keselamatan, alokasi channel radio darurat underground, koordinasi dispatch armada, dan etika komunikasi radio tambang.', 'Semua personel lapangan');

-- ----------------------------------------------------------
-- 5. TABEL: lna_catalog_tags (Tag Relasi Katalog LNA)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `lna_catalog_tags`;
CREATE TABLE `lna_catalog_tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `catalog_id` VARCHAR(50) NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  KEY `idx_cat_id` (`catalog_id`),
  CONSTRAINT `fk_catalog_tags` FOREIGN KEY (`catalog_id`) REFERENCES `lna_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lna_catalog_tags` (`catalog_id`, `tag`) VALUES
('lna_1', 'Wajib'), ('lna_1', 'ESDM'), ('lna_1', 'K3 Tambang'),
('lna_2', 'Wajib'), ('lna_2', 'Underground'), ('lna_2', 'K3 Dasar'),
('lna_3', 'Sertifikasi'), ('lna_3', 'Lisensi'), ('lna_3', 'Kemnaker'),
('lna_4', 'Teknik'), ('lna_4', 'Alat Berat'), ('lna_4', 'Operasional'),
('lna_5', 'Leadership'), ('lna_5', 'Manajemen'), ('lna_5', 'Soft Skill'),
('lna_6', 'Komunikasi'), ('lna_6', 'Dispatch'), ('lna_6', 'Protokol');

-- ----------------------------------------------------------
-- 6. TABEL: notifications (Pusat Notifikasi, Alarm & Approval)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` VARCHAR(50) NOT NULL,
  `user_target` VARCHAR(50) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `related_user` VARCHAR(100) DEFAULT NULL,
  `related_course` VARCHAR(255) DEFAULT NULL,
  `activity_id` VARCHAR(50) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'unread',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_target` (`user_target`),
  KEY `idx_notif_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`id`, `user_target`, `type`, `title`, `message`, `related_user`, `related_course`, `activity_id`, `status`, `created_at`) VALUES
('notif_1788767588_774', 'budi', 'approval', 'Kegiatan Dikonfirmasi: pelatihan mengemudi bagi peserta magang', 'Admin telah menyetujui kegiatan "pelatihan mengemudi bagi peserta magang". Modul kini aktif di My Learning dan Dashboard Anda.', 'Learning Ops Admin', 'pelatihan mengemudi bagi peserta magang', 'act_1788767563_538', 'unread', '2026-09-07 09:53:08'),
('notif_1788767563_715', 'admin', 'request', 'Pengajuan Kegiatan: Budi Santoso', 'Budi Santoso mengajukan kegiatan baru: "pelatihan mengemudi bagi peserta magang". Menunggu konfirmasi Anda.', 'Budi Santoso', 'pelatihan mengemudi bagi peserta magang', 'act_1788767563_538', 'unread', '2026-09-07 09:52:43'),
('notif_1788767361_229', 'admin', 'submission', 'Lembar Kerja Masuk: pelatihan mengemudi bagi peserta magang', 'Pegawai @egy telah mengumpulkan laporan lembar kerja modul: "pelatihan mengemudi bagi peserta magang".', 'egy', 'pelatihan mengemudi bagi peserta magang', 'pelatihan mengemudi bagi peserta magang', 'unread', '2026-09-07 09:49:21'),
('notif_1788753924_202', 'egy', 'approval', 'Kegiatan Dikonfirmasi: Pengoperasian Dump Truck Bawah Tanah', 'Admin telah menyetujui kegiatan "Pengoperasian Dump Truck Bawah Tanah". Modul kini aktif di My Learning dan Dashboard Anda.', 'Learning Ops Admin', 'Pengoperasian Dump Truck Bawah Tanah', 'act_1788752576_276', 'unread', '2026-09-07 06:05:24'),
('notif_1788753898_938', 'egy', 'approval', 'Kegiatan Dikonfirmasi: pelatihan mengemudi bagi peserta magang', 'Admin telah menyetujui kegiatan "pelatihan mengemudi bagi peserta magang". Modul kini aktif di My Learning dan Dashboard Anda.', 'Learning Ops Admin', 'pelatihan mengemudi bagi peserta magang', 'act_1788753692_239', 'unread', '2026-09-07 06:04:58'),
('notif_1788753692_212', 'admin', 'request', 'Pengajuan Kegiatan: Egy Pratama', 'Egy Pratama mengajukan kegiatan baru: "pelatihan mengemudi bagi peserta magang". Menunggu konfirmasi Anda.', 'Egy Pratama', 'pelatihan mengemudi bagi peserta magang', 'act_1788753692_239', 'unread', '2026-09-07 06:01:32'),
('notif_1788752576_554', 'admin', 'request', 'Pengajuan Kegiatan: Egy Pratama', 'Egy Pratama mengajukan kegiatan baru: "Pengoperasian Dump Truck Bawah Tanah". Menunggu konfirmasi Anda.', 'Egy Pratama', 'Pengoperasian Dump Truck Bawah Tanah', 'act_1788752576_276', 'unread', '2026-09-07 05:42:56'),
('notif_1', 'admin', 'escalation', 'Eskalasi Level 1: Egy Pratama', 'Safety Induction Overdue (+1 Hari). Izin masuk site underground terancam dibekukan.', 'Egy Pratama', 'Safety Induction Site Tambang Pongkor', NULL, 'unread', '2026-08-12 08:30:00'),
('notif_2', 'admin', 'verification', 'Verifikasi Ujian: Budi Santoso', 'Budi Santoso menyelesaikan evaluasi Sertifikasi K3 Umum dengan skor 94/100.', 'Budi Santoso', 'Sertifikasi K3 Umum & Pertambangan', NULL, 'unread', '2026-08-11 16:45:00'),
('notif_3', 'admin', 'request', 'Pengajuan Modul: Andi Wijaya', 'Andi Wijaya mengajukan penugasan modul Technical Skill A (Hauling Truck 777D).', 'Andi Wijaya', 'Technical Skill A — Heavy Hauling 777D', NULL, 'unread', '2026-08-10 11:20:00');
