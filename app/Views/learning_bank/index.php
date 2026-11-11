<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$user_type = $_SESSION['user_type'] ?? 'User';

$courses = [
    [
        'code' => 'TRC4-4-3',
        'title' => 'Applied Negotiation Techniques',
        'category' => 'SOFT SKILL AND OTHERS',
        'type' => 'Inhouse',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Intermediate',
        'desc' => 'Strategi negosiasi praktis, resolusi konflik kepentingan operasional, dan teknik mencapai kesepakatan win-win di lingkungan industri pertambangan.'
    ],
    [
        'code' => 'TRC5-46',
        'title' => 'CS Excellence and Handling Customer Complaint',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Advanced',
        'desc' => 'Standar pelayanan prima korporat, penanganan keluhan pelanggan eksternal dan stakeholder tambang dengan protokol komunikasi profesional bersertifikasi.'
    ],
    [
        'code' => 'TRC4-5-26',
        'title' => 'Internal Auditor ISO 50001 Sistem Manajemen Energi',
        'category' => 'TECH-CORP SERVICES',
        'type' => 'Inhouse',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '4 Hari (32 Jam)',
        'level' => 'Advanced',
        'desc' => 'Audit internal efisiensi energi operasional pabrik pengolahan emas dan instalasi kelistrikan bawah tanah Site Pomalaa.'
    ],
    [
        'code' => 'TRC5-70',
        'title' => 'Sertifikasi Instruktur BNSP KKNI Level 4',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '5 Hari (40 Jam)',
        'level' => 'Advanced',
        'desc' => 'Sertifikasi lisensi instruktur pelatihan kerja nasional berstandar BNSP untuk instruktur internal ANTAM Learning Center.'
    ],
    [
        'code' => 'TRC1-9',
        'title' => '17th MGEI Annual Convention',
        'category' => 'FOUNDATION',
        'type' => 'Public',
        'total' => 'IDR 0',
        'plan' => '0 Person',
        'required_new' => 'No',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Intermediate',
        'desc' => 'Konvensi tahunan Masyarakat Geologi Ekonomi Indonesia, perkembangan eksplorasi mineral dan teknologi ekstraksi bijih emas terkini.'
    ],
    [
        'code' => 'K3-ESDM-01',
        'title' => 'POP Level 1 â€” Pengawas Operasional Pertama',
        'category' => 'REGULASI K3 ESDM',
        'type' => 'Wajib',
        'total' => 'IDR 0',
        'plan' => '24 Person',
        'required_new' => 'Yes',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Basic',
        'desc' => 'Standar kompetensi pengawas operasional pertambangan mineral dan batubara. Wajib bagi seluruh asisten foreman & supervisor lapangan Site Pomalaa.'
    ],
    [
        'code' => 'K3-ESDM-02',
        'title' => 'Safety Induction Site Pomalaa',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'total' => 'IDR 0',
        'plan' => '50 Person',
        'required_new' => 'Yes',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'desc' => 'Pengenalan bahaya bawah tanah, pengoperasian SCSR, evakuasi gas beracun, dan etika keselamatan PT ANTAM Tbk Pomalaa.'
    ]
];

// Merge custom/newly added courses from DB catalog if present
$existingCourseTitles = array_map(function($item) { return strtolower(trim($item['title'] ?? '')); }, $courses);
$dbCatalog = get_lna_catalog();
foreach ($dbCatalog as $lna) {
    $cleanT = strtolower(trim($lna['title'] ?? ''));
    if (!empty($cleanT) && !in_array($cleanT, $existingCourseTitles)) {
        $courses[] = [
            'code' => $lna['code'] ?? 'TRC-GEN',
            'title' => $lna['title'],
            'category' => $lna['category'] ?? 'GENERAL',
            'type' => $lna['type'] ?? 'Inhouse',
            'total' => 'IDR 0',
            'plan' => '0 Person',
            'required_new' => (strtolower($lna['type'] ?? '') === 'wajib') ? 'Yes' : 'No',
            'duration' => $lna['duration'] ?? '2 Hari (16 Jam)',
            'level' => $lna['level'] ?? 'Basic',
            'desc' => $lna['description'] ?? ($lna['desc'] ?? '')
        ];
        $existingCourseTitles[] = $cleanT;
    }
}

// -------------------------------------------------------------
// HELPER: Indonesian Date Parsing to ISO YYYY-MM-DD
// -------------------------------------------------------------
if (!function_exists('_parse_indonesian_date_to_iso')) {
function _parse_indonesian_date_to_iso($str) {
    if (empty($str)) return null;
    $str = trim($str);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $str, $m)) {
        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }
    $months = [
        'jan' => '01', 'januari' => '01', 'january' => '01',
        'feb' => '02', 'februari' => '02', 'february' => '02',
        'mar' => '03', 'maret' => '03', 'march' => '03',
        'apr' => '04', 'april' => '04',
        'mei' => '05', 'may' => '05',
        'jun' => '06', 'juni' => '06', 'june' => '06',
        'jul' => '07', 'juli' => '07', 'july' => '07',
        'agu' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08',
        'sep' => '09', 'september' => '09',
        'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10',
        'nov' => '11', 'november' => '11',
        'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
    ];
    if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/', $str, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mLower = strtolower($m[2]);
        $mon = $months[$mLower] ?? null;
        if ($mon) return "{$m[3]}-{$mon}-{$day}";
    }
    $ts = strtotime($str);
    if ($ts !== false && $ts > 0) return date('Y-m-d', $ts);
    return null;
}
}

// -------------------------------------------------------------
// BUILD CORPORATE CALENDAR EVENTS LIST
// (Memadukan kegiatan historis terlaksana & jadwal mendatang)
// -------------------------------------------------------------
$calendarEvents = [
    // --- AGUSTUS 2026 (Historical Terlaksana & Active) ---
    [
        'id' => 'evt_aug_04',
        'title' => 'Pengenalan Sistem Manajemen K3 Tambang',
        'code' => 'K3-ESDM-00',
        'date' => '2026-08-04',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Batch Personel Baru Site Pomalaa (35 Pegawai)',
        'target' => 'Karyawan Baru & Kontraktor',
        'location' => 'Auditorium Diklat ANTAM Pomalaa',
        'instructor' => 'Divisi Keselamatan Pertambangan (K3) ANTAM',
        'description' => 'Sosialisasi prinsip Golden Rules keselamatan tambang, hierarki pengendalian risiko bahaya, dan regulasi dasar ESDM No. 1827/2018.',
        'progress' => 100
    ],
    [
        'id' => 'evt_aug_12',
        'title' => 'Safety Induction Site Pomalaa',
        'code' => 'K3-ESDM-02',
        'date' => '2026-08-12',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Egy Pratama (Operasional Tambang)',
        'target' => 'Operator Tambang Bawah Tanah',
        'location' => 'Simulasi Portal Utama Site Pomalaa',
        'instructor' => 'Instruktur Senior K3 Tambang Bawah Tanah',
        'description' => 'Pengenalan bahaya bawah tanah, pengoperasian SCSR, evakuasi gas beracun (CO, NO2, CH4), dan etika keselamatan PT ANTAM Tbk Pomalaa.',
        'progress' => 100
    ],
    [
        'id' => 'evt_aug_14',
        'title' => 'POP Level 1 â€” Pengawas Operasional Pertama',
        'code' => 'K3-ESDM-01',
        'date' => '2026-08-14',
        'category' => 'REGULASI K3 ESDM',
        'type' => 'Wajib',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Basic',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Egy Pratama & Batch Foreman (12 Peserta)',
        'target' => 'Pengawas Lapangan & Asisten Foreman',
        'location' => 'Training Room ANTAM Pomalaa',
        'instructor' => 'Lembaga Sertifikasi Profesi (LSP) ESDM',
        'description' => 'Standar kompetensi pengawas operasional pertambangan mineral dan batubara. Wajib bagi seluruh asisten foreman & supervisor lapangan Site Pomalaa.',
        'progress' => 100
    ],
    [
        'id' => 'evt_aug_17',
        'title' => 'Sertifikasi K3 Umum & Pertambangan',
        'code' => 'KEMNAKER-K3U-03',
        'date' => '2026-08-17',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'duration' => '5 Hari (40 Jam)',
        'level' => 'Advanced',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Budi Santoso (Processing Plant)',
        'target' => 'Officer & Teknisi K3 Pabrik',
        'location' => 'Pusdiklat Sertifikasi Kemnaker RI',
        'instructor' => 'Pengawas K3 Spesialis Kemnaker & Praktisi ESDM',
        'description' => 'Program sertifikasi lisensi resmi pemenuhan syarat regulasi Kemnaker & ESDM. Dilengkapi uji materi hukum K3, inspeksi praktis, dan investigasi insiden.',
        'progress' => 100
    ],
    [
        'id' => 'evt_aug_18',
        'title' => 'Technical Skill A â€” Heavy Hauling 777D',
        'code' => 'TECH-OPR-04',
        'date' => '2026-08-18',
        'category' => 'TEKNIK OPERASIONAL',
        'type' => 'Inhouse',
        'duration' => '4 Hari (32 Jam)',
        'level' => 'Advanced',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Operator Dump Truck & Alat Berat',
        'target' => 'Operator Pit & Underground Hauler',
        'location' => 'Hauling Track & Tambang Pomalaa',
        'instructor' => 'Trainer Alat Berat Caterpillar / Trakindo',
        'description' => 'Manuver tanjakan ekstrem, inspeksi rem retarder hidrolik, penanganan blindspot, dan keselamatan loading point di pit tambang.',
        'progress' => 100
    ],
    [
        'id' => 'evt_aug_21',
        'title' => 'Effective Radio Communication & Mine Dispatch',
        'code' => 'COMM-DSP-06',
        'date' => '2026-08-21',
        'category' => 'KOMUNIKASI & LOGISTIK',
        'type' => 'Inhouse',
        'duration' => '2 Hari (12 Jam)',
        'level' => 'Basic',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Andi Wijaya & Dispatch Team',
        'target' => 'Operator Radio, Logistik, Dispatcher',
        'location' => 'Control Room & Dispatch Center',
        'instructor' => 'Supervisor Dispatch & Telekomunikasi ANTAM',
        'description' => 'Kode fonetik standar keselamatan, alokasi channel radio darurat underground, koordinasi dispatch armada, dan etika komunikasi radio tambang.',
        'progress' => 100
    ],
    [
        'id' => 'evt_aug_26',
        'title' => 'Pengoperasian Dump Truck Bawah Tanah',
        'code' => 'TRC-DUMP-26',
        'date' => '2026-08-26',
        'category' => 'TEKNIK TAMBANG',
        'type' => 'Inhouse',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Basic',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Egy Pratama & Operator Muda',
        'target' => 'Driver & Operator Bawah Tanah',
        'location' => 'Ramp Way Underground Site Pomalaa',
        'instructor' => 'Senior Underground Hauling Specialist',
        'description' => 'Prosedur keselamatan mengemudi di lorong sempit, jarak aman kendaraan konvoi, dan penanganan mesin mati di terowongan miring.',
        'progress' => 100
    ],

    // --- SEPTEMBER 2026 (Historical Terlaksana) ---
    [
        'id' => 'evt_sep_03',
        'title' => 'Leadership & Supervisory Underground',
        'code' => 'LD-SUP-05',
        'date' => '2026-09-03',
        'category' => 'LEADERSHIP & MANAJEMEN',
        'type' => 'Inhouse',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Intermediate',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Foreman & Asisten Supervisor Shif',
        'target' => 'Team Leader Lapangan',
        'location' => 'Meeting Room ANTAM Pomalaa',
        'instructor' => 'Corporate Human Capital PT ANTAM Tbk',
        'description' => 'Pembekalan kepemimpinan regu kerja di terowongan bawah tanah, komunikasi radio darurat, manajemen konflik shif, dan coaching keselamatan berkala.',
        'progress' => 100
    ],
    [
        'id' => 'evt_sep_07',
        'title' => 'Pelatihan Mengemudi Bagi Peserta Magang',
        'code' => 'TRC-DRIVE-01',
        'date' => '2026-09-07',
        'category' => 'K3 & KESELAMATAN TAMBANG',
        'type' => 'Inhouse',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Basic',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Peserta Magang & Pegawai Baru',
        'target' => 'Mahasiswa Magang & Operator Baru',
        'location' => 'Site Pomalaa Driving Circuit',
        'instructor' => 'K3 Defensive Driving ANTAM',
        'description' => 'Praktik keselamatan mengemudikan kendaraan ringan (LV 4x4) di medan tambang berlumpur, tanjakan terjal, dan kondisi hujan ekstrem.',
        'progress' => 100
    ],
    [
        'id' => 'evt_sep_15',
        'title' => 'Applied Negotiation Techniques (Batch 1)',
        'code' => 'TRC4-4-3',
        'date' => '2026-09-15',
        'category' => 'SOFT SKILL AND OTHERS',
        'type' => 'Inhouse',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Intermediate',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Supervisor Supply Chain & Procurement',
        'target' => 'Pengadaan, Logistik & Hubungan Stakeholder',
        'location' => 'Training Room Corporate ANTAM',
        'instructor' => 'Konsultan Negosiasi Bisnis & Kontrak',
        'description' => 'Strategi negosiasi praktis, resolusi konflik kepentingan operasional, dan teknik mencapai kesepakatan win-win di lingkungan industri pertambangan.',
        'progress' => 100
    ],
    [
        'id' => 'evt_sep_22',
        'title' => 'Inspeksi Geoteknik & K3 Site Pomalaa',
        'code' => 'GEO-PONG-09',
        'date' => '2026-09-22',
        'category' => 'TEKNIK OPERASIONAL',
        'type' => 'Inhouse',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Advanced',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Geoteknik & Mine Survey Team',
        'target' => 'Inspektur Geoteknik & Shif Incharge',
        'location' => 'Terowongan Penambangan Level 500',
        'instructor' => 'Chief Geologist & Geotechnical Specialist',
        'description' => 'Pemasangan instrumen konvergensi terowongan, monitoring pergeseran batuan stope, dan evaluasi kekuatan penyanggaan rockbolt/shotcrete.',
        'progress' => 100
    ],
    [
        'id' => 'evt_sep_28',
        'title' => 'Internal Auditor ISO 50001 Sistem Manajemen Energi',
        'code' => 'TRC4-5-26',
        'date' => '2026-09-28',
        'category' => 'TECH-CORP SERVICES',
        'type' => 'Inhouse',
        'duration' => '4 Hari (32 Jam)',
        'level' => 'Advanced',
        'status' => 'completed',
        'status_label' => 'Telah Dilaksanakan',
        'user_name' => 'Tim Efisiensi Energi & Pabrik Pengolahan',
        'target' => 'Auditor Internal ISO & Engineering Team',
        'location' => 'Processing Plant & Smelter Pomalaa',
        'instructor' => 'Lead Auditor ISO 50001 Bersertifikasi Internasional',
        'description' => 'Audit internal efisiensi energi operasional pabrik pengolahan emas dan instalasi kelistrikan bawah tanah Site Pomalaa.',
        'progress' => 100
    ],

    // --- OKTOBER 2026 (Upcoming / Kegiatan Yang Akan Terjadi) ---
    [
        'id' => 'evt_oct_06',
        'title' => 'Evaluasi Bulanan Kepatuhan Keselamatan Tambang',
        'code' => 'K3-EVAL-10',
        'date' => '2026-10-06',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Seluruh Kepala Regu & Pengawas Site',
        'target' => 'Pengawas Lapangan & Panitia K3 (P2K3)',
        'location' => 'Aula Utama ANTAM Pomalaa',
        'instructor' => 'Kepala Teknik Tambang (KTT) Site Pomalaa',
        'description' => 'Kajian kepatuhan K3 triwulan III, analisis near-miss incident bawah tanah, dan review pemenuhan lisensi berkala operator.',
        'progress' => 0
    ],
    [
        'id' => 'evt_oct_14',
        'title' => 'Safety Induction Site Pomalaa (Batch 4)',
        'code' => 'K3-ESDM-02',
        'date' => '2026-10-14',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Batch Pegawai Kuartal IV (Target: 30 Peserta)',
        'target' => 'Pegawai Baru, Magang, Kontraktor Vendor',
        'location' => 'Auditorium K3 Site Pomalaa',
        'instructor' => 'Tim Instruktur K3 ANTAM Pomalaa',
        'description' => 'Pembekalan protokol keselamatan esensial underground, pemakaian respirator filter kimia, dan jalur penyelamatan darurat lorong tambang.',
        'progress' => 0
    ],
    [
        'id' => 'evt_oct_18',
        'title' => 'CS Excellence and Handling Customer Complaint',
        'code' => 'TRC5-46',
        'date' => '2026-10-18',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Pendaftaran Terbuka (Slot: 15 Peserta)',
        'target' => 'Customer Relations, Logistik Emas, Humas',
        'location' => 'Public Training Center Jakarta / Virtual',
        'instructor' => 'Lembaga Sertifikasi Pelayanan Korporat Nasional',
        'description' => 'Standar pelayanan prima korporat, penanganan keluhan pelanggan eksternal dan stakeholder tambang dengan protokol komunikasi profesional bersertifikasi.',
        'progress' => 0
    ],
    [
        'id' => 'evt_oct_22',
        'title' => 'POP Level 1 â€” Pengawas Operasional Pertama (Batch Mandiri)',
        'code' => 'K3-ESDM-01',
        'date' => '2026-10-22',
        'category' => 'REGULASI K3 ESDM',
        'type' => 'Wajib',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Basic',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Batch Mandiri Calon Pengawas Tambang',
        'target' => 'Asisten Foreman & Junior Supervisor',
        'location' => 'Ruang Pelatihan LSP ESDM Site Pomalaa',
        'instructor' => 'Asesor Kompetensi K3 ESDM RI',
        'description' => 'Uji kompetensi 8 unit standar POP: inspeksi, investigasi insiden, IBPR, JSA, pertemuan K3 terencana, dan pemenuhan regulasi hukum K3 pertambangan.',
        'progress' => 0
    ],
    [
        'id' => 'evt_oct_26',
        'title' => '17th MGEI Annual Convention',
        'code' => 'TRC1-9',
        'date' => '2026-10-26',
        'category' => 'FOUNDATION',
        'type' => 'Public',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Intermediate',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Delegasi Geologist ANTAM PT ANTAM Tbk Pomalaa',
        'target' => 'Exploration & Mine Geology Specialist',
        'location' => 'Convention Hall Yogyakarta & Virtual Hybrid',
        'instructor' => 'Masyarakat Geologi Ekonomi Indonesia (MGEI)',
        'description' => 'Konvensi tahunan Masyarakat Geologi Ekonomi Indonesia, perkembangan eksplorasi mineral dan teknologi ekstraksi bijih emas terkini.',
        'progress' => 0
    ],
    [
        'id' => 'evt_oct_30',
        'title' => 'Simulasi Tanggap Darurat Gas Bawah Tanah & SCSR',
        'code' => 'K3-EMERG-05',
        'date' => '2026-10-30',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Intermediate',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Regu Penyelamat Tambang (Emergency Response Team)',
        'target' => 'ERT Pomalaa & Seluruh Shift Siaga',
        'location' => 'Rescue Chamber Portal Ciguha Site Pomalaa',
        'instructor' => 'Commander ERT ANTAM Pomalaa & Basarnas',
        'description' => 'Drill tahunan uji kesiapsiagaan aktivasi tabung oksigen SCSR 60 menit, deteksi dini semburan gas metana, dan evakuasi tandu di terowongan vertikal.',
        'progress' => 0
    ],

    // --- NOVEMBER 2026 (Upcoming / Kegiatan Yang Akan Terjadi) ---
    [
        'id' => 'evt_nov_05',
        'title' => 'Sertifikasi Instruktur BNSP KKNI Level 4',
        'code' => 'TRC5-70',
        'date' => '2026-11-05',
        'category' => 'SERTIFIKASI',
        'type' => 'Public',
        'duration' => '5 Hari (40 Jam)',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Calon Instruktur Internal ANTAM (Slot: 8 Orang)',
        'target' => 'Trainer Internal & Senior Foreman',
        'location' => 'Pusat Diklat BNSP Jakarta',
        'instructor' => 'Master Asesor Badan Nasional Sertifikasi Profesi',
        'description' => 'Sertifikasi lisensi instruktur pelatihan kerja nasional berstandar BNSP untuk instruktur internal ANTAM Learning Center.',
        'progress' => 0
    ],
    [
        'id' => 'evt_nov_08',
        'title' => 'Technical Skill A â€” Heavy Hauling 777D (Batch 2)',
        'code' => 'TECH-OPR-04',
        'date' => '2026-11-08',
        'category' => 'TEKNIK OPERASIONAL',
        'type' => 'Inhouse',
        'duration' => '4 Hari (32 Jam)',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Operator Dump Truck Tambang Permukaan & Pit',
        'target' => 'Operator Baru & Refresher',
        'location' => 'Hauling Track Site Pomalaa',
        'instructor' => 'Senior Trainer Alat Berat',
        'description' => 'Pemeliharaan harian pra-operasi (P2H), teknik pengereman emergency retarder hidrolik, dan manuver keselamatan dumping pit.',
        'progress' => 0
    ],
    [
        'id' => 'evt_nov_12',
        'title' => 'Applied Negotiation Techniques (Batch 2)',
        'code' => 'TRC4-4-3',
        'date' => '2026-11-12',
        'category' => 'SOFT SKILL AND OTHERS',
        'type' => 'Inhouse',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Intermediate',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Tim Hubungan Industrial & CSR',
        'target' => 'Officer CSR, External Relations, Legal',
        'location' => 'Training Room Corporate ANTAM',
        'instructor' => 'Pakar Komunikasi & Negosiasi Korporasi',
        'description' => 'Pengembangan kemampuan persuasif, manajemen stakeholder lingkar tambang, dan mediasi kesepakatan kemitraan masyarakat.',
        'progress' => 0
    ],
    [
        'id' => 'evt_nov_19',
        'title' => 'Manajemen Ventilasi Tambang Bawah Tanah',
        'code' => 'K3-VENT-03',
        'date' => '2026-11-19',
        'category' => 'REGULASI K3 ESDM',
        'type' => 'Wajib',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Teknisi Ventilasi & Mine Engineering',
        'target' => 'Ventilation Engineer & Pengawas Udara',
        'location' => 'Main Fan Station Level 600 Site Pomalaa',
        'instructor' => 'Spesialis Ventilasi Tambang Bawah Tanah ITB / ESDM',
        'description' => 'Pengukuran debit aliran udara, pengaturan regulator door, instalasi booster fan auxilliary, dan mitigasi heat stress penambang.',
        'progress' => 0
    ],
    [
        'id' => 'evt_nov_24',
        'title' => 'Leadership & Supervisory Underground (Batch 2)',
        'code' => 'LD-SUP-05',
        'date' => '2026-11-24',
        'category' => 'LEADERSHIP & MANAJEMEN',
        'type' => 'Inhouse',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Intermediate',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Calon Foreman Shif Malam & Weekend',
        'target' => 'Supervisor & Team Leader Produksi',
        'location' => 'Ruang Diklat ANTAM Pomalaa',
        'instructor' => 'Senior L&D Manager PT ANTAM Tbk',
        'description' => 'Kepemimpinan berorientasi Zero Accident, coaching shif harian, briefing toolbox keselamatan, dan penanganan krisis lapangan.',
        'progress' => 0
    ],
    [
        'id' => 'evt_nov_28',
        'title' => 'Audit Kesiapsiagaan Alat Berat Tambang',
        'code' => 'TECH-AUD-07',
        'date' => '2026-11-28',
        'category' => 'TEKNIK OPERASIONAL',
        'type' => 'Wajib',
        'duration' => '2 Hari (16 Jam)',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Mekanik & Quality Control Workshop',
        'target' => 'Maintenance Planner & K3 Mekanikal',
        'location' => 'Central Workshop Site Pomalaa',
        'instructor' => 'Inspektur Kelaikan Peralatan ESDM',
        'description' => 'Uji statis dan dinamis rem darurat loader LHD, fire suppression automatic system, dan uji emisi gas buang armada underground.',
        'progress' => 0
    ],

    // --- JANUARI 2027 (SESUAI FOTO REFERENSI KALENDER MEJA PT ANTAM TBK 2027) ---
    [
        'id' => 'evt_2027_jan_01',
        'title' => "New Year's Day (Public Hol.)",
        'code' => 'HOLIDAY-2027-01',
        'date' => '2027-01-01',
        'category' => 'HARI LIBUR NASIONAL',
        'type' => 'Libur Nasional',
        'duration' => '1 Hari',
        'level' => 'Umum',
        'status' => 'upcoming',
        'status_label' => 'Hari Libur Nasional',
        'user_name' => 'Seluruh Insan ANTAM Site Pomalaa',
        'target' => 'Seluruh Pegawai & Kontraktor',
        'location' => 'PT ANTAM Tbk Pomalaa',
        'instructor' => 'Pemerintah Republik Indonesia',
        'description' => "Tahun Baru Masehi 2027. Libur resmi nasional operasional PT ANTAM Tbk Pomalaa.",
        'progress' => 0
    ],
    [
        'id' => 'evt_2027_jan_12',
        'title' => 'Safety Induction Site Pomalaa (Batch 1 2027)',
        'code' => 'K3-ESDM-02',
        'date' => '2027-01-12',
        'category' => 'KESELAMATAN PERTAMBANGAN',
        'type' => 'Wajib',
        'duration' => '1 Hari (8 Jam)',
        'level' => 'Basic',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Batch Pegawai & Rekanan Baru 2027',
        'target' => 'Operator Tambang Bawah Tanah',
        'location' => 'Auditorium K3 ANTAM Pomalaa',
        'instructor' => 'Tim Instruktur K3 Underground',
        'description' => 'Induksi keselamatan perdana tahun 2027, simulasi alat pelindung pernafasan SCSR, etika terowongan dan zona bahaya.',
        'progress' => 0
    ],
    [
        'id' => 'evt_2027_jan_15',
        'title' => 'Quarterly Report Due',
        'code' => 'CORP-Q4-RPT',
        'date' => '2027-01-15',
        'category' => 'MANAJEMEN KORPORAT',
        'type' => 'Wajib',
        'duration' => 'Deadline Pelaporan',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Seluruh Kepala Divisi & K3 Site',
        'target' => 'Management Site & Lead Auditor',
        'location' => 'Kantor Manajemen Site Pomalaa',
        'instructor' => 'Divisi Kinerja Operasional Corporate ANTAM',
        'description' => 'Batas akhir penyusunan dan penyerahan Laporan Triwulan IV & Kinerja Tahunan K3 ESDM Site Pomalaa kepada Kantor Pusat.',
        'progress' => 0
    ],
    [
        'id' => 'evt_2027_jan_19',
        'title' => 'POP Level 1 â€” Pengawas Operasional Pertama (ESDM)',
        'code' => 'K3-ESDM-01',
        'date' => '2027-01-19',
        'category' => 'REGULASI K3 ESDM',
        'type' => 'Wajib',
        'duration' => '3 Hari (24 Jam)',
        'level' => 'Basic',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Calon Pengawas & Foreman Angkatan 2027',
        'target' => 'Pengawas Lapangan & Asisten Foreman',
        'location' => 'Training Room LSP ESDM Pomalaa',
        'instructor' => 'LSP Pertambangan ESDM RI',
        'description' => 'Sertifikasi kompetensi pengawas operasional pertama tambang mineral bawah tanah berstandar K3 ESDM No. 1827/2018.',
        'progress' => 0
    ],
    [
        'id' => 'evt_2027_jan_21',
        'title' => 'Board Meeting',
        'code' => 'EXEC-BM-2027',
        'date' => '2027-01-21',
        'category' => 'LEADERSHIP & MANAJEMEN',
        'type' => 'Direksi',
        'duration' => '1 Hari',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Dewan Direksi & General Manager UBPN',
        'target' => 'Executive Committee PT ANTAM Tbk',
        'location' => 'Executive Boardroom PT ANTAM Tbk Pomalaa / Hybrid',
        'instructor' => 'Board of Directors PT ANTAM Tbk',
        'description' => 'Rapat Dewan Direksi dan General Manager PT ANTAM Tbk Pomalaa terkait Rencana Kerja dan Anggaran Perusahaan (RKAP) 2027 dan target Zero Accident.',
        'progress' => 0
    ],
    [
        'id' => 'evt_2027_jan_28',
        'title' => 'Project X Review',
        'code' => 'TECH-PRJX-01',
        'date' => '2027-01-28',
        'category' => 'TEKNIK OPERASIONAL',
        'type' => 'Inhouse',
        'duration' => '1 Hari',
        'level' => 'Advanced',
        'status' => 'upcoming',
        'status_label' => 'Akan Terjadi',
        'user_name' => 'Tim Engineering & Underground Taskforce',
        'target' => 'Project Managers & Mine Planners',
        'location' => 'Engineering Center Site Pomalaa',
        'instructor' => 'Project Lead Mining Automation ANTAM',
        'description' => 'Review milestone dan uji coba tahap akhir Project X: Modernisasi Otomasi Ventilasi & Pemantauan Seismik Mikro Operasional Site Pomalaa.',
        'progress' => 0
    ]
];

// -------------------------------------------------------------
// SINKRONISASI KEGIATAN AKTIF DARI DATABASE NYATA (MYSQL)
// -------------------------------------------------------------
$allActivities = get_activities();
$existingEventTitles = array_map(function($e) { return strtolower(trim($e['title'])); }, $calendarEvents);

foreach ($allActivities as $act) {
    $actTitle = trim($act['title'] ?? '');
    if (empty($actTitle)) continue;

    $actDate = _parse_indonesian_date_to_iso($act['deadline'] ?? '') 
               ?: _parse_indonesian_date_to_iso(substr($act['created_at'] ?? '', 0, 10))
               ?: date('Y-m-d');

    // Evaluasi status: telah dilaksanakan vs akan terjadi
    $todayIso = date('Y-m-d');
    $isComplete = ($act['progress'] >= 100) 
               || ($act['completed_modules'] >= ($act['total_modules'] ?? 4))
               || ($act['grade'] !== null)
               || ($actDate < $todayIso && ($act['status'] ?? '') === 'disetujui');

    $statusKey = $isComplete ? 'completed' : 'upcoming';
    $statusLbl = $isComplete ? 'Telah Dilaksanakan' : 'Akan Terjadi';

    $tLower = strtolower($actTitle);
    if (!in_array($tLower, $existingEventTitles)) {
        $calendarEvents[] = [
            'id' => $act['id'],
            'title' => $act['title'],
            'code' => 'ACT-' . strtoupper(substr(md5($act['id']), 0, 6)),
            'date' => $actDate,
            'category' => $act['category'] ?? 'Operasional Tambang',
            'type' => $act['type'] ?? 'Wajib',
            'duration' => $act['duration'] ?? '2 Hari (16 Jam)',
            'level' => $act['level'] ?? 'Intermediate',
            'status' => $statusKey,
            'status_label' => $statusLbl,
            'user_username' => $act['user_username'] ?? '',
            'user_name' => $act['user_name'] ?? 'Pegawai Site Pomalaa',
            'target' => !empty($act['user_name']) ? 'Pegawai: ' . $act['user_name'] : 'Penugasan Khusus Pegawai',
            'location' => 'PT ANTAM Tbk Pomalaa',
            'instructor' => 'Learning Ops Administrator ANTAM',
            'description' => $act['notes'] ?? 'Kegiatan pembelajaran terdaftar resmi di sistem Smart Learning ANTAM.',
            'progress' => (int)($act['progress'] ?? 0)
        ];
        $existingEventTitles[] = $tLower;
    }
}

// Kumpulkan list kategori unik untuk dropdown filter
$categoryList = [];
foreach ($calendarEvents as $ev) {
    $cat = trim($ev['category'] ?? '');
    if ($cat && !in_array($cat, $categoryList)) {
        $categoryList[] = $cat;
    }
}
sort($categoryList);

// Real-time Waktu Kalender Sistem (Bulan Ini & Hari Ini)
$realtimeDateObj = new DateTime();
$realMonthIndex = (int)$realtimeDateObj->format('n'); // 1 - 12
$realYear = (int)$realtimeDateObj->format('Y');
$indoMonthsMap = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$realMonthNameIndo = $indoMonthsMap[$realMonthIndex] ?? 'Oktober';
$realMonthNameEng = strtoupper($realtimeDateObj->format('F'));
$realTodayIso = $realtimeDateObj->format('Y-m-d');
?>

<div class="learning-bank-container">
    
    <!-- ============================================================== -->
    <!-- TAMPILAN UTAMA & EKSKLUSIF: MODERN TRAINING & EVENT CALENDAR    -->
    <!-- Menampilkan Penanda Kegiatan Telah Dilaksanakan & Akan Terjadi -->
    <!-- ============================================================== -->
    <div id="catalog-calendar-view" class="catalog-calendar-view">
        
        <!-- Unified Executive Control Dashboard: Header, Quick Tools, Month Navigator & Status Filter -->
        <div class="cal-unified-card mb-4">
            <!-- Baris 1: Brand Title, Search, Filter & Menu Training Action -->
            <div class="cal-card-top-row">
                <div class="cal-title-box">
                    <div class="cal-title-icon" style="background: linear-gradient(135deg, #0f766e, #0d9488); color: white;">
                        <i class="ph-duotone ph-calendar-check"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="cal-main-heading">Kalender Pelatihan</h2>
                            <span class="tag tag-teal font-mono text-xs">Site Pomalaa</span>
                        </div>
                        <p class="cal-sub-heading">Agenda pelatihan K3 dan pengembangan kompetensi Site Pomalaa</p>
                    </div>
                </div>

                <div class="cal-tools-right">
                    <!-- Search Box Kalender Real-time -->
                    <div class="cal-search-box">
                        <i class="ph-bold ph-magnifying-glass"></i>
                        <input type="text" id="cal-search-input" class="cal-search-input" placeholder="Cari kegiatan, modul, instruktur..." oninput="handleCalSearchInput(this.value)" onkeydown="handleCalSearchKeydown(event)" onfocus="handleCalSearchFocus()" autocomplete="off">
                        <button type="button" class="cal-search-clear" id="cal-search-clear" onclick="clearCalSearch()" title="Hapus pencarian">
                            <i class="ph-bold ph-x"></i>
                        </button>
                        <div id="cal-search-dropdown" class="cal-search-dropdown"></div>
                    </div>

                    <!-- Dropdown Kategori -->
                    <select id="cal-category-filter" class="cal-select-input" onchange="filterCalendar()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categoryList as $catName): ?>
                        <option value="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($user_type === 'Admin'): ?>
                    <button type="button" class="btn-dark" onclick="openAssignTrainingModal()" style="font-size: 0.82rem; padding: 0.48rem 0.95rem;">
                        <i class="ph-bold ph-calendar-plus"></i> Tugaskan Pelatihan
                    </button>
                    <?php else: ?>
                    <a href="<?= base_url('my-learning') ?>" class="btn-dark" style="font-size: 0.82rem; padding: 0.48rem 0.95rem; text-decoration: none;" title="Buka Menu Training & Kelola Kegiatan">
                        <i class="ph-bold ph-graduation-cap"></i> Menu Training
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Garis Pemisah Halus -->
            <div class="cal-card-divider"></div>

            <!-- Baris 2: Navigasi Bulan, Filter Status & Pilihan Format -->
            <div class="cal-card-nav-row">
                <!-- Navigasi Bulan & Tahun -->
                <div class="cal-nav-group">
                    <button type="button" class="cal-nav-btn" onclick="calChangeMonth(-1)" title="Bulan Sebelumnya">
                        <i class="ph-bold ph-caret-left"></i>
                    </button>
                    <h3 class="cal-month-title" id="cal-month-display"><?php echo htmlspecialchars("{$realMonthNameIndo} {$realYear}"); ?></h3>
                    <button type="button" class="cal-nav-btn" onclick="calChangeMonth(1)" title="Bulan Berikutnya">
                        <i class="ph-bold ph-caret-right"></i>
                    </button>
                    <button type="button" class="cal-today-btn" onclick="calGoToToday()">
                        <i class="ph-bold ph-calendar-blank"></i> Bulan Ini
                    </button>
                </div>

                <!-- Penanda Status Kegiatan (Pills Filter) -->
                <div class="cal-filter-pills">
                    <button type="button" class="cal-filter-pill active" id="pill-status-all" onclick="filterCalStatus('all')">
                        <i class="ph-bold ph-stack"></i> Semua (<span id="count-pill-all">0</span>)
                    </button>
                    <button type="button" class="cal-filter-pill pill-completed" id="pill-status-completed" onclick="filterCalStatus('completed')" title="Tampilkan kegiatan yang sudah selesai dilaksanakan">
                        <span class="status-dot dot-green"></span> Selesai (<span id="count-pill-completed">0</span>)
                    </button>
                    <button type="button" class="cal-filter-pill pill-upcoming" id="pill-status-upcoming" onclick="filterCalStatus('upcoming')" title="Tampilkan agenda kegiatan yang akan datang">
                        <span class="status-dot dot-blue"></span> Mendatang (<span id="count-pill-upcoming">0</span>)
                    </button>
                </div>

                <!-- Quick Actions Kanan: Shortcut Referensi Jan 2027, Cetak, dan Format Hari -->
                <div class="cal-actions-right">
                    <button type="button" class="cal-quick-ref-btn" onclick="calJumpToReference2027()" title="Buka Tampilan Referensi Kalender Meja ANTAM Januari 2027">
                        <i class="ph-fill ph-sparkle"></i> Jan 2027
                    </button>

                    <button type="button" class="btn-print-desk" onclick="printDeskCalendar()" title="Cetak / Simpan Kalender Meja ke PDF">
                        <i class="ph-bold ph-printer"></i> Cetak
                    </button>

                    <!-- Hidden helper select elements for programmatic month/year sync -->
                    <select id="cal-select-month" style="display: none;" onchange="calJumpMonth(this.value)">
                        <?php foreach ($indoMonthsMap as $idx => $mName): ?>
                        <option value="<?php echo $idx - 1; ?>" <?php echo ($idx === $realMonthIndex) ? 'selected' : ''; ?>><?php echo $mName; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="cal-select-year" style="display: none;" onchange="calJumpYear(this.value)">
                        <option value="<?php echo $realYear - 1; ?>"><?php echo $realYear - 1; ?></option>
                        <option value="<?php echo $realYear; ?>" selected><?php echo $realYear; ?></option>
                        <option value="<?php echo $realYear + 1; ?>"><?php echo $realYear + 1; ?></option>
                    </select>

                    <!-- Switcher Format Hari Pertama (Senin / Minggu) -->
                    <div class="cal-week-start-toggle" title="Format Hari Pertama Kalender">
                        <button type="button" class="cal-toggle-btn active" id="toggle-start-mon" onclick="setWeekStart('mon')" title="Mulai Senin (Sesuai Referensi, Kolom Minggu Merah di Kanan)">Sen</button>
                        <button type="button" class="cal-toggle-btn" id="toggle-start-sun" onclick="setWeekStart('sun')" title="Mulai Minggu">Mgg</button>
                    </div>
                </div>
            </div>

            <!-- Container Banner Hasil Pencarian Real-time -->
            <div id="cal-search-banner-container" style="display: none; padding: 0.25rem 0.5rem 0 0.5rem;"></div>
        </div>

        <!-- ============================================================== -->
        <!-- REALISTIC CORPORATE DESK TENT CALENDAR (SESUAI FOTO REFERENSI) -->
        <!-- ============================================================== -->
        <div class="desk-calendar-scene" id="desk-calendar-scene">
            
            <!-- Spiral Wire Ring Binding with Punched Holes (16 Twin Loops) -->
            <div class="desk-spiral-binding" id="desk-spiral-binding">
                <?php for ($i = 0; $i < 16; $i++): ?>
                <div class="desk-spiral-loop">
                    <div class="desk-paper-hole"></div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- The White Calendar Cardstock Sheet -->
            <div class="desk-calendar-card" id="desk-calendar-card">
                
                <!-- Top Header: Logo ANTAM + Red Slash + Excavator Graphic + Month Year Title -->
                <div class="desk-cal-header-row">
                    <div class="desk-cal-brand-area">
                        <div class="desk-cal-brand">
                            <div class="desk-antam-emblem">
                                <!-- PT ANTAM Corporate Emblem SVG -->
                                <svg width="42" height="36" viewBox="0 0 100 85" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 70 L34 25 L46 70 Z" fill="#b91c1c"/>
                                    <path d="M34 25 L46 70 L28 70 Z" fill="#991b1b"/>
                                    <path d="M32 70 L50 8 L68 70 Z" fill="#dc2626"/>
                                    <path d="M50 8 L68 70 L50 70 Z" fill="#b91c1c"/>
                                    <path d="M54 70 L66 25 L88 70 Z" fill="#b91c1c"/>
                                    <path d="M66 25 L88 70 L72 70 Z" fill="#991b1b"/>
                                    <path d="M22 60 L34 32 L40 60 Z" fill="rgba(255,255,255,0.35)"/>
                                    <path d="M42 50 L50 18 L58 50 Z" fill="rgba(255,255,255,0.4)"/>
                                </svg>
                            </div>
                            <div class="desk-cal-company-text">
                                <span class="desk-cal-company-name">PT ANTAM Tbk</span>
                                <span class="desk-cal-site-name">PT ANTAM TBK â€¢ SITE POMALAA</span>
                            </div>
                        </div>

                        <!-- Red Slanted Accent Slash (-20deg) -->
                        <div class="desk-cal-red-slash"></div>

                        <!-- Mining Excavator Graphic Silhouette matching reference photo -->
                        <div class="desk-cal-mining-art">
                            <svg width="150" height="46" viewBox="0 0 200 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M 0 60 L 15 54 L 35 48 L 52 42 L 72 45 L 90 58 L 105 60 Z" fill="#64748b" opacity="0.65"/>
                                <path d="M 12 60 L 32 50 L 50 44 L 68 47 L 85 59 Z" fill="#475569" opacity="0.45"/>
                                <line x1="0" y1="60" x2="200" y2="60" stroke="#94a3b8" stroke-width="2"/>
                                <rect x="120" y="52" width="60" height="9" rx="4.5" fill="#334155"/>
                                <circle cx="126" cy="56.5" r="3" fill="#64748b"/>
                                <circle cx="138" cy="56.5" r="2.5" fill="#64748b"/>
                                <circle cx="150" cy="56.5" r="2.5" fill="#64748b"/>
                                <circle cx="162" cy="56.5" r="2.5" fill="#64748b"/>
                                <circle cx="174" cy="56.5" r="3" fill="#64748b"/>
                                <rect x="132" y="49" width="36" height="4" fill="#1e293b"/>
                                <path d="M 125 49 L 125 38 L 142 38 L 150 43 L 175 43 L 178 49 Z" fill="#475569"/>
                                <path d="M 127 46 L 127 40 L 138 40 L 138 46 Z" fill="#94a3b8"/>
                                <rect x="168" y="42" width="10" height="7" rx="1" fill="#334155"/>
                                <path d="M 136 43 L 115 22 L 111 25 L 133 46 Z" fill="#475569"/>
                                <line x1="138" y1="45" x2="124" y2="30" stroke="#334155" stroke-width="2"/>
                                <path d="M 113 23 L 82 29 L 81 33 L 111 26 Z" fill="#64748b"/>
                                <line x1="118" y1="20" x2="95" y2="28" stroke="#334155" stroke-width="1.5"/>
                                <path d="M 82 30 L 70 38 L 74 46 L 82 45 L 83 37 Z" fill="#334155"/>
                                <polygon points="70,38 66,41 71,42" fill="#1e293b"/>
                                <polygon points="72,42 68,45 73,46" fill="#1e293b"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Right Month & Year Title: e.g. "JANUARY 2027" -->
                    <div class="desk-cal-title-area">
                        <h2 class="desk-cal-month-year-title" id="desk-cal-month-title"><?php echo htmlspecialchars("{$realMonthNameEng} {$realYear}"); ?></h2>
                        <div class="desk-cal-tagline">
                            <i class="ph-bold ph-shield-check"></i> SISTEM PEMBELAJARAN & K3 RESMI
                        </div>
                    </div>
                </div>

                <!-- Body Layout: Left 7-Col Grid + Right NOTES Notepad -->
                <div class="desk-cal-body-layout">
                    
                    <!-- Main Grid Section -->
                    <div class="desk-cal-grid-section">
                        <!-- Weekdays Header Strip (Sun, Mon, Tue, Wed, Thu, Fri, Sat with RED Sunday) -->
                        <div class="desk-cal-weekdays" id="desk-cal-weekdays-header">
                            <!-- Populated dynamically by renderWeekdaysHeader() -->
                        </div>

                        <!-- 35/42 Days Grid Table -->
                        <div class="desk-cal-days-table" id="desk-cal-days-body">
                            <!-- Populated dynamically by renderCalendar() -->
                        </div>
                    </div>

                    <!-- Right Ruled Notepad ("NOTES / Plan...") matching reference photo -->
                    <div class="desk-cal-sidebar-notes">
                        <div class="desk-sidebar-notes-header">
                            <div class="flex items-center justify-between">
                                <span class="desk-notes-tag">NOTES</span>
                                <button type="button" class="desk-btn-mini-add" onclick="addSidebarCustomNote()" title="Tambah Catatan Plan Baru">
                                    <i class="ph-bold ph-plus"></i> Tambah
                                </button>
                            </div>
                            <span class="desk-notes-subtag">Plan...</span>
                        </div>

                        <!-- Ruled Notebook Lines -->
                        <div class="desk-sidebar-notes-body" id="desk-sidebar-notes-body">
                            <!-- Ruled notebook lines with interactive typing -->
                        </div>

                        <div class="desk-notes-action-row">
                            <button type="button" class="desk-btn-mini-reset" onclick="resetSidebarNotes()" title="Reset ke Catatan Default Bulan Ini">
                                <i class="ph-bold ph-arrow-counter-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Bottom Ruled Notes Section ("NOTES / Plan... / Follow-up...") matching reference photo -->
                <div class="desk-cal-bottom-notes">
                    <div class="desk-bottom-notes-grid">
                        <!-- Kolom Kiri: NOTES Plan... -->
                        <div class="desk-bottom-notes-col">
                            <div class="desk-bottom-notes-header">
                                <span class="desk-notes-tag">NOTES</span>
                                <span class="desk-notes-subtag">Plan...</span>
                            </div>
                            <div class="desk-bottom-notes-body" id="desk-bottom-plan-body">
                                <!-- Populated with editable lines -->
                            </div>
                        </div>

                        <!-- Kolom Kanan: NOTES Plan... Follow-up... -->
                        <div class="desk-bottom-notes-col">
                            <div class="desk-bottom-notes-header">
                                <span class="desk-notes-tag">NOTES</span>
                                <span class="desk-notes-subtag">Plan... Follow-up...</span>
                            </div>
                            <div class="desk-bottom-notes-body" id="desk-bottom-followup-body">
                                <!-- Populated with editable lines -->
                            </div>
                        </div>
                    </div>

                    <div class="desk-bottom-action-bar">
                        <span class="text-xs text-muted">
                            <i class="ph-bold ph-note-pencil text-accent"></i> Tip: Ketik langsung di baris bergaris untuk mencatat rencana & follow-up bulanan. Catatan tersimpan otomatis.
                        </span>
                    </div>
                </div>

            </div>

            <!-- Realistic Triangular Easel Stand Base Underneath -->
            <div class="desk-calendar-stand-base">
                <div class="desk-stand-fold-left"></div>
                <div class="desk-stand-fold-right"></div>
            </div>

            <!-- Realistic Executive Metallic Ballpoint Pen lying on the desk -->
            <div class="desk-pen-wrapper">
                <div class="desk-pen-assembly" id="desk-pen-widget" onclick="togglePenMode()" title="Klik pena untuk mengaktifkan / menonaktifkan Mode Catat (Pen Mode)">
                    <div class="desk-pen-shadow"></div>
                    <div class="desk-pen-mockup">
                        <div class="desk-pen-clip"></div>
                        <div class="desk-pen-cap"></div>
                        <div class="desk-pen-ring"></div>
                        <div class="desk-pen-barrel">
                            <span class="desk-pen-brand">ANTAM POMALAA</span>
                        </div>
                        <div class="desk-pen-grip"></div>
                        <div class="desk-pen-cone"></div>
                        <div class="desk-pen-point"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- KPI Mini Ribbon: Ringkasan Kegiatan Bulan Terpilih -->
        <div class="cal-kpi-grid">
            <div class="cal-kpi-card">
                <div class="cal-kpi-icon kpi-icon-teal">
                    <i class="ph-bold ph-calendar-check"></i>
                </div>
                <div class="cal-kpi-content">
                    <span class="cal-kpi-num" id="kpi-total-month">0</span>
                    <span class="cal-kpi-lbl">Total Agenda Bulan Ini</span>
                </div>
            </div>

            <div class="cal-kpi-card">
                <div class="cal-kpi-icon kpi-icon-green">
                    <i class="ph-fill ph-check-circle"></i>
                </div>
                <div class="cal-kpi-content">
                    <span class="cal-kpi-num text-success" id="kpi-completed-month" style="color: #059669;">0</span>
                    <span class="cal-kpi-lbl">Telah Dilaksanakan</span>
                </div>
            </div>

            <div class="cal-kpi-card">
                <div class="cal-kpi-icon kpi-icon-blue">
                    <i class="ph-fill ph-clock-countdown"></i>
                </div>
                <div class="cal-kpi-content">
                    <span class="cal-kpi-num text-sky" id="kpi-upcoming-month" style="color: #0284c7;">0</span>
                    <span class="cal-kpi-lbl">Akan Terjadi (Mendatang)</span>
                </div>
            </div>

            <div class="cal-kpi-card">
                <div class="cal-kpi-icon kpi-icon-amber">
                    <i class="ph-fill ph-shield-check"></i>
                </div>
                <div class="cal-kpi-content">
                    <span class="cal-kpi-num" id="kpi-compliance-rate">100%</span>
                    <span class="cal-kpi-lbl">Kepatuhan Standar ESDM</span>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- DAFTAR AGENDA BULAN INI: DUA KOLOM RIWAYAT & JADWAL MENDATANG  -->
        <!-- ============================================================== -->
        <div class="cal-agenda-section">
            <div class="cal-agenda-header">
                <h4 class="cal-agenda-title">
                    <i class="ph-bold ph-list-checks text-accent"></i>
                    Detail Agenda Kegiatan: <span id="agenda-month-subtitle" style="font-weight: 800; color: #0f766e;"><?php echo htmlspecialchars("{$realMonthNameIndo} {$realYear}"); ?></span>
                </h4>
                <div class="flex items-center gap-3">
                    <button type="button" id="btn-clear-day-filter" class="sf-btn-outline" style="display: none; padding: 0.35rem 0.75rem; font-size: 0.78rem;" onclick="selectCalendarDay(null)">
                        <i class="ph-bold ph-x"></i> Tampilkan Semua Hari
                    </button>
                    <div class="text-xs text-muted">
                        Klik kartu modul untuk membuka informasi kurikulum, peserta, atau pendaftaran.
                    </div>
                </div>
            </div>

            <div class="cal-agenda-grid">
                <!-- Kolom Kiri: Kegiatan Yang Telah Dilaksanakan -->
                <div class="cal-agenda-col">
                    <div class="cal-agenda-col-header">
                        <div class="cal-agenda-col-title col-title-completed">
                            <i class="ph-fill ph-check-circle" style="font-size: 1.2rem; color: #10b981;"></i>
                            <span>Telah Dilaksanakan</span>
                        </div>
                        <span class="cal-agenda-badge-count badge-count-completed" id="badge-count-completed">0 Selesai</span>
                    </div>

                    <div class="cal-agenda-list" id="agenda-list-completed">
                        <!-- Kartu kegiatan selesai di-render di sini -->
                    </div>
                </div>

                <!-- Kolom Kanan: Kegiatan Yang Akan Terjadi -->
                <div class="cal-agenda-col">
                    <div class="cal-agenda-col-header">
                        <div class="cal-agenda-col-title col-title-upcoming">
                            <i class="ph-fill ph-clock-afternoon" style="font-size: 1.2rem; color: #0284c7;"></i>
                            <span>Akan Terjadi (Jadwal Mendatang)</span>
                        </div>
                        <span class="cal-agenda-badge-count badge-count-upcoming" id="badge-count-upcoming">0 Terjadwal</span>
                    </div>

                    <div class="cal-agenda-list" id="agenda-list-upcoming">
                        <!-- Kartu kegiatan mendatang di-render di sini -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fallback escapeHtml jika script.js belum selesai dimuat
if (typeof escapeHtml !== 'function') {
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// ==============================================================
// DATA KALENDER LENGKAP DARI BACKEND
// ==============================================================
window.ALL_CALENDAR_EVENTS = <?php echo json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
window.CURRENT_USER_ROLE = <?php echo json_encode($user_type); ?>;
window.CURRENT_USER_NAME = <?php echo json_encode($_SESSION['user_name'] ?? ($activeUser['name'] ?? 'Egy Pratama')); ?>;
window.CURRENT_USERNAME = <?php echo json_encode($_SESSION['username'] ?? ($activeUser['username'] ?? 'egy')); ?>;
var ALL_CALENDAR_EVENTS = window.ALL_CALENDAR_EVENTS;
var CURRENT_USER_ROLE = window.CURRENT_USER_ROLE;
var CURRENT_USER_NAME = window.CURRENT_USER_NAME;
var CURRENT_USERNAME = window.CURRENT_USERNAME;

// Muat otomatis kegiatan kustom yang tersimpan di browser
(function loadCustomCalendarEvents() {
    try {
        const stored = localStorage.getItem('antam_custom_calendar_events');
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed)) {
                parsed.forEach(pe => {
                    if (!ALL_CALENDAR_EVENTS.some(e => e.id === pe.id)) {
                        ALL_CALENDAR_EVENTS.push(pe);
                    }
                });
            }
        }
    } catch(e) {}
})();

// State Kalender (Bulan & Tahun Otomatis Sesuai Waktu Real-Time)
const _nowReal = new Date();
window.calState = window.calState || {
    year: _nowReal.getFullYear(),
    month: _nowReal.getMonth(), // 0 = Jan, 9 = Okt (otomatis real-time)
    selectedDay: null,
    filterStatus: 'all', // 'all', 'completed', 'upcoming'
    weekStart: localStorage.getItem('cal_week_start') || 'mon' // 'mon' (Senin) atau 'sun' (Minggu)
};
var calState = window.calState;

const INDO_MONTHS = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const ENG_MONTHS = [
    'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
    'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
];

// Helper: dapatkan tanggal hari ini (Real-Time Sistem)
function getSystemSimulatedDate() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

// -------------------------------------------------------------
// RENDER HEADER HARI (SENIN / MINGGU SESUAI FOTO REFERENSI)
// -------------------------------------------------------------
function renderWeekdaysHeader() {
    const container = document.getElementById('desk-cal-weekdays-header');
    if (!container) return;

    if (calState.weekStart === 'mon') {
        // Format Mulai Senin (Kolom ke-7 MINGGU / RED SUNDAY persis di sebelah kolom NOTES sesuai foto referensi)
        container.innerHTML = `
            <div class="desk-cal-weekday-col">Mon</div>
            <div class="desk-cal-weekday-col">Tue</div>
            <div class="desk-cal-weekday-col">Wed</div>
            <div class="desk-cal-weekday-col">Thu</div>
            <div class="desk-cal-weekday-col">Fri</div>
            <div class="desk-cal-weekday-col">Sat</div>
            <div class="desk-cal-weekday-col is-sun">Sun</div>
        `;
    } else {
        // Format Mulai Minggu
        container.innerHTML = `
            <div class="desk-cal-weekday-col is-sun">Sun</div>
            <div class="desk-cal-weekday-col">Mon</div>
            <div class="desk-cal-weekday-col">Tue</div>
            <div class="desk-cal-weekday-col">Wed</div>
            <div class="desk-cal-weekday-col">Thu</div>
            <div class="desk-cal-weekday-col">Fri</div>
            <div class="desk-cal-weekday-col">Sat</div>
        `;
    }

    // Update tombol switcher active
    const btnSun = document.getElementById('toggle-start-sun');
    const btnMon = document.getElementById('toggle-start-mon');
    if (btnSun && btnMon) {
        if (calState.weekStart === 'sun') {
            btnSun.classList.add('active');
            btnMon.classList.remove('active');
        } else {
            btnSun.classList.remove('active');
            btnMon.classList.add('active');
        }
    }
}

function setWeekStart(startDay) {
    calState.weekStart = startDay;
    localStorage.setItem('cal_week_start', startDay);
    renderWeekdaysHeader();
    renderCalendar();
}

// -------------------------------------------------------------
// RENDER KALENDER MEJA REALISTIS DENGAN PENEMPATAN PERSISI
// -------------------------------------------------------------
function renderCalendar(animate = false) {
    renderWeekdaysHeader();

    const gridBody = document.getElementById('desk-cal-days-body');
    const monthDisplay = document.getElementById('cal-month-display');
    const deskMonthTitle = document.getElementById('desk-cal-month-title');
    const subtitleMonth = document.getElementById('agenda-month-subtitle');
    const selMonth = document.getElementById('cal-select-month');
    const selYear = document.getElementById('cal-select-year');
    const btnClearFilter = document.getElementById('btn-clear-day-filter');

    if (!gridBody) return;

    // Trigger animasi flip kartu kalender HANYA jika animasi diminta secara eksplisit saat navigasi bulan
    const calCard = document.getElementById('desk-calendar-card');
    if (calCard && animate) {
        calCard.classList.remove('flipping');
        void calCard.offsetWidth; // trigger reflow
        calCard.classList.add('flipping');
        setTimeout(() => calCard.classList.remove('flipping'), 380);
    }

    // Update Judul Bulan di Header Meja & Toolbar
    const monthNameIndo = INDO_MONTHS[calState.month];
    const monthNameEng = ENG_MONTHS[calState.month];

    if (monthDisplay) monthDisplay.textContent = `${monthNameIndo} ${calState.year}`;
    if (deskMonthTitle) {
        // Tampilkan format huruf kapital rapi seperti referensi: e.g. "JANUARY 2027"
        deskMonthTitle.textContent = `${monthNameEng} ${calState.year}`;
    }

    if (subtitleMonth) {
        if (calState.selectedDay) {
            subtitleMonth.textContent = formatIndoDate(calState.selectedDay);
            if (btnClearFilter) btnClearFilter.style.display = 'inline-flex';
        } else {
            subtitleMonth.textContent = `${monthNameIndo} ${calState.year}`;
            if (btnClearFilter) btnClearFilter.style.display = 'none';
        }
    }
    if (selMonth) selMonth.value = calState.month;
    if (selYear) selYear.value = calState.year;

    // Filter Query dari Search Input & Dropdown Kategori
    const searchQuery = (document.getElementById('cal-search-input')?.value || '').toLowerCase().trim();
    const categoryQuery = (document.getElementById('cal-category-filter')?.value || '').toLowerCase().trim();

    // 1. Kumpulkan semua event yang cocok di seluruh kalender
    const allMatchedEvents = ALL_CALENDAR_EVENTS.filter(evt => eventMatchesQuery(evt, searchQuery, categoryQuery));

    // 2. Kumpulkan event yang cocok di bulan berjalan ini
    const monthPrefix = `${calState.year}-${String(calState.month + 1).padStart(2, '0')}`;
    let monthEvents = allMatchedEvents.filter(evt => evt.date && evt.date.startsWith(monthPrefix));

    // Update Banner Hasil Pencarian
    updateCalSearchBanner(searchQuery, monthEvents, allMatchedEvents, monthNameIndo);

    // ---------------------------------------------------------
    // RUMUS PENEMPATAN HARI PERSISI
    // JS getDay(): 0 = Sun, 1 = Mon, 2 = Tue, 3 = Wed, 4 = Thu, 5 = Fri, 6 = Sat
    // ---------------------------------------------------------
    const firstDateObj = new Date(calState.year, calState.month, 1);
    const firstDayIndex = firstDateObj.getDay(); 

    let startOffset = 0;
    if (calState.weekStart === 'sun') {
        startOffset = firstDayIndex;
    } else {
        // Mulai Senin: jika Minggu (0) offset 6, selain itu firstDayIndex - 1
        startOffset = (firstDayIndex === 0) ? 6 : (firstDayIndex - 1);
    }

    const daysInMonth = new Date(calState.year, calState.month + 1, 0).getDate();
    const daysInPrevMonth = new Date(calState.year, calState.month, 0).getDate();
    const simTodayStr = getSystemSimulatedDate();

    // Hitung KPI & Badge (Bulan ini jika ada, atau seluruh hasil pencarian)
    const kpiSourceEvents = (searchQuery && monthEvents.length === 0 && allMatchedEvents.length > 0) ? allMatchedEvents : monthEvents;
    let totalCompleted = 0;
    let totalUpcoming = 0;
    kpiSourceEvents.forEach(evt => {
        if (evt.status === 'completed') totalCompleted++;
        else totalUpcoming++;
    });

    // Update KPI Ribbon
    const elTotMonth = document.getElementById('kpi-total-month');
    const elCompMonth = document.getElementById('kpi-completed-month');
    const elUpcMonth = document.getElementById('kpi-upcoming-month');
    const elCompRate = document.getElementById('kpi-compliance-rate');

    if (elTotMonth) elTotMonth.textContent = kpiSourceEvents.length;
    if (elCompMonth) elCompMonth.textContent = totalCompleted;
    if (elUpcMonth) elUpcMonth.textContent = totalUpcoming;
    if (elCompRate) {
        const rate = kpiSourceEvents.length > 0 ? Math.round((totalCompleted / kpiSourceEvents.length) * 100) : 100;
        elCompRate.textContent = `${rate}%`;
    }

    // Update Pills Count
    const pillAll = document.getElementById('count-pill-all');
    const pillComp = document.getElementById('count-pill-completed');
    const pillUpc = document.getElementById('count-pill-upcoming');
    if (pillAll) pillAll.textContent = kpiSourceEvents.length;
    if (pillComp) pillComp.textContent = totalCompleted;
    if (pillUpc) pillUpc.textContent = totalUpcoming;

    // Bersihkan Grid Meja
    gridBody.innerHTML = '';

    // 1. Kotak hari bulan sebelumnya (subdued gray)
    for (let i = startOffset - 1; i >= 0; i--) {
        const prevDayNum = daysInPrevMonth - i;
        const cell = document.createElement('div');
        cell.className = 'desk-cal-day-box is-other-month';
        cell.innerHTML = `
            <div class="desk-cal-day-header">
                <span class="desk-cal-day-num">${prevDayNum}</span>
            </div>
        `;
        gridBody.appendChild(cell);
    }

    // 2. Kotak hari bulan berjalan (1 s/d 31)
    for (let day = 1; day <= daysInMonth; day++) {
        const dayStr = String(day).padStart(2, '0');
        const fullDateStr = `${monthPrefix}-${dayStr}`;
        const isToday = (fullDateStr === simTodayStr);
        const isSelected = (calState.selectedDay === fullDateStr);

        const cellDateObj = new Date(calState.year, calState.month, day);
        const cellDayOfWeek = cellDateObj.getDay(); // 0 = Sun, 6 = Sat
        const isSunday = (cellDayOfWeek === 0);

        // Cari event pada hari ini
        const dayEvents = monthEvents.filter(e => e.date === fullDateStr);

        // Filter status event sesuai pill penanda aktif
        const visibleDayEvents = dayEvents.filter(e => {
            if (calState.filterStatus === 'completed') return e.status === 'completed';
            if (calState.filterStatus === 'upcoming') return e.status === 'upcoming';
            return true;
        });

        const isSearchMatched = Boolean(searchQuery && (visibleDayEvents.length > 0));

        const cell = document.createElement('div');
        cell.className = `desk-cal-day-box ${isSunday ? 'is-sun' : ''} ${isToday ? 'is-today' : ''} ${isSelected ? 'is-selected' : ''} ${isSearchMatched ? 'is-search-matched' : ''}`;
        cell.title = `Klik tanggal ${day} ${monthNameIndo} untuk melihat atau menambahkan kegiatan`;
        cell.onclick = () => onCalendarDateClick(fullDateStr);

        const hasEvents = (visibleDayEvents.length > 0);
        let eventsHtml = '';
        if (hasEvents) {
            const firstEvt = visibleDayEvents[0];
            const extraCount = visibleDayEvents.length - 1;
            const isCompleted = (firstEvt.status === 'completed');
            eventsHtml = `
                <div class="cal-date-event-pill ${isCompleted ? 'event-done' : 'event-plan'} ${isSearchMatched ? 'matched-pill' : ''}" title="${visibleDayEvents.length} Kegiatan Terdaftar: ${escapeHtml(visibleDayEvents.map(e => e.title).join(', '))}">
                    <span class="cal-event-pill-text">${escapeHtml(firstEvt.title)}</span>
                    ${extraCount > 0 ? `<span class="cal-event-pill-count">+${extraCount}</span>` : ''}
                </div>
            `;
        }

        cell.innerHTML = `
            <div class="desk-cal-day-header">
                <span class="desk-cal-day-num">${day}</span>
                <div class="flex items-center gap-1">
                    ${isToday ? '<span class="cal-day-badge-today" style="font-size:0.62rem; background:#0f766e; color:white; padding:1px 5px; border-radius:4px; font-weight:700;">HARI INI</span>' : ''}
                    <button type="button" class="desk-cal-add-btn" title="Kelola kegiatan pada tanggal ${day}" onclick="event.stopPropagation(); onCalendarDateClick('${fullDateStr}')">
                        <i class="ph-bold ph-plus"></i>
                    </button>
                </div>
            </div>
            <div class="desk-cal-notes-wrap">
                ${eventsHtml}
            </div>
        `;

        gridBody.appendChild(cell);
    }

    // 3. Kotak hari bulan berikutnya agar genap kelipatan 7 (total 35 atau 42 kotak rapi)
    const totalCellsSoFar = startOffset + daysInMonth;
    const remainingCells = (totalCellsSoFar <= 35) ? (35 - totalCellsSoFar) : ((7 - (totalCellsSoFar % 7)) % 7);
    
    for (let day = 1; day <= remainingCells; day++) {
        const cell = document.createElement('div');
        cell.className = 'desk-cal-day-box is-other-month';
        cell.innerHTML = `
            <div class="desk-cal-day-header">
                <span class="desk-cal-day-num">${day}</span>
            </div>
        `;
        gridBody.appendChild(cell);
    }

    // Render Ruled Notes Sections (Sidebar Kanan & Bawah Meja)
    renderNotesSection();

    // Render Dua Kolom Agenda di bawah kalender
    renderAgendaList(monthEvents, allMatchedEvents);
}

// -------------------------------------------------------------
// RENDER & KELOLA CATATAN KERTAS BERGARIS (NOTES / Plan...)
// Sesuai Tampilan Kalender Meja di Foto Referensi
// -------------------------------------------------------------
function renderNotesSection() {
    const sbBody = document.getElementById('desk-sidebar-notes-body');
    const botPlanBody = document.getElementById('desk-bottom-plan-body');
    const botFupBody = document.getElementById('desk-bottom-followup-body');

    // Default template catatan berdasarkan bulan aktif
    const isJan2027 = (calState.year === 2027 && calState.month === 0);
    const isAug2026 = (calState.year === 2026 && calState.month === 7);
    const isOct2026 = (calState.year === 2026 && calState.month === 9);

    // 1. Sidebar Notes (Kanan)
    if (sbBody) {
        const storageKeySb = `antam_notes_sb_${calState.year}_${calState.month}`;
        let sbNotes = [];
        try {
            const raw = localStorage.getItem(storageKeySb);
            if (raw) sbNotes = JSON.parse(raw);
        } catch(e) {}

        if (!sbNotes || sbNotes.length === 0) {
            if (isJan2027) {
                sbNotes = [
                    'Kick-off RKAP 2027 Site Pomalaa',
                    'Induksi K3 100% personel baru',
                    'Penyerahan Laporan Triwulan IV',
                    'Review Project X Otomasi'
                ];
            } else if (isOct2026) {
                sbNotes = [
                    'Evaluasi Kepatuhan K3 Kuartal IV',
                    'Safety Induction Batch 4 Pomalaa',
                    'Uji POP Mandiri Asesor ESDM',
                    'Drill Emergency Response Underground'
                ];
            } else if (isAug2026) {
                sbNotes = [
                    'Zero Accident Campaign Pomalaa',
                    'Sertifikasi POP Level 1 ESDM',
                    'Defensive Driving Tambang 4x4',
                    'Inspeksi gas beracun terowongan'
                ];
            } else {
                sbNotes = [
                    'Rencana target kompetensi bulan ini',
                    'Kajian kepatuhan Golden Rules K3',
                    'Penyelesaian modul Smart Learning'
                ];
            }
        }

        let sbHtml = '';
        const totalLines = Math.max(12, sbNotes.length + 3);
        for (let i = 0; i < totalLines; i++) {
            const val = sbNotes[i] || '';
            sbHtml += `
                <div class="desk-note-item-wrap">
                    <input type="text" class="desk-note-input" value="${escapeHtml(val)}" placeholder="${i === 0 ? 'Plan / Catatan...' : ''}" onchange="saveSidebarNotes()">
                    ${val ? `<button type="button" class="desk-note-del-btn" onclick="clearSingleNote(this, 'sb')" title="Hapus"><i class="ph-bold ph-x"></i></button>` : ''}
                </div>
            `;
        }
        sbBody.innerHTML = sbHtml;
    }

    // 2. Bottom Notes: Plan...
    if (botPlanBody) {
        const storageKeyPlan = `antam_notes_bot_plan_${calState.year}_${calState.month}`;
        let planNotes = [];
        try {
            const raw = localStorage.getItem(storageKeyPlan);
            if (raw) planNotes = JSON.parse(raw);
        } catch(e) {}

        if (!planNotes || planNotes.length === 0) {
            if (isJan2027) {
                planNotes = ['Persiapan dokumen audit kelaikan ESDM', 'Sinkronisasi buku tambang dengan logbook digital'];
            } else if (isOct2026) {
                planNotes = ['Evaluasi bulanan kepatuhan keselamatan tambang', 'Uji kesiapsiagaan SCSR di Rescue Chamber Portal Ciguha'];
            } else {
                planNotes = ['Simulasi tanggap darurat SCSR underground', 'Refresher pengoperasian dump truck 777D'];
            }
        }

        let planHtml = '';
        for (let i = 0; i < 3; i++) {
            const val = planNotes[i] || '';
            planHtml += `
                <div class="desk-bottom-line">
                    <i class="ph-bold ph-caret-right"></i>
                    <input type="text" class="desk-note-input" value="${escapeHtml(val)}" placeholder="${i === 0 ? 'Plan... (ketik di sini)' : ''}" onchange="saveBottomNotes()">
                </div>
            `;
        }
        botPlanBody.innerHTML = planHtml;
    }

    // 3. Bottom Notes: Plan... Follow-up...
    if (botFupBody) {
        const storageKeyFup = `antam_notes_bot_fup_${calState.year}_${calState.month}`;
        let fupNotes = [];
        try {
            const raw = localStorage.getItem(storageKeyFup);
            if (raw) fupNotes = JSON.parse(raw);
        } catch(e) {}

        if (!fupNotes || fupNotes.length === 0) {
            if (isJan2027) {
                fupNotes = ['Follow-up evaluasi Project X bersama konsultan', 'Konfirmasi jadwal asesmen LSP ESDM batch 1'];
            } else if (isOct2026) {
                fupNotes = ['Follow-up jadwal asesmen POP LSP ESDM batch mandiri', 'Penyusunan rekapitulasi jam pelatihan kuartal III'];
            } else {
                fupNotes = ['Follow-up penerbitan lisensi K3 Kemnaker', 'Laporan mingguan kepatuhan Golden Rules K3'];
            }
        }

        let fupHtml = '';
        for (let i = 0; i < 3; i++) {
            const val = fupNotes[i] || '';
            fupHtml += `
                <div class="desk-bottom-line">
                    <i class="ph-bold ph-check-square"></i>
                    <input type="text" class="desk-note-input" value="${escapeHtml(val)}" placeholder="${i === 0 ? 'Follow-up... (ketik di sini)' : ''}" onchange="saveBottomNotes()">
                </div>
            `;
        }
        botFupBody.innerHTML = fupHtml;
    }
}

function saveSidebarNotes() {
    const inputs = document.querySelectorAll('#desk-sidebar-notes-body .desk-note-input');
    const vals = [];
    inputs.forEach(inp => {
        if (inp.value.trim()) vals.push(inp.value.trim());
    });
    localStorage.setItem(`antam_notes_sb_${calState.year}_${calState.month}`, JSON.stringify(vals));
}

function saveBottomNotes() {
    const planInputs = document.querySelectorAll('#desk-bottom-plan-body .desk-note-input');
    const fupInputs = document.querySelectorAll('#desk-bottom-followup-body .desk-note-input');
    
    const planVals = [];
    planInputs.forEach(inp => { if (inp.value.trim()) planVals.push(inp.value.trim()); });
    localStorage.setItem(`antam_notes_bot_plan_${calState.year}_${calState.month}`, JSON.stringify(planVals));

    const fupVals = [];
    fupInputs.forEach(inp => { if (inp.value.trim()) fupVals.push(inp.value.trim()); });
    localStorage.setItem(`antam_notes_bot_fup_${calState.year}_${calState.month}`, JSON.stringify(fupVals));
}

function resetSidebarNotes() {
    localStorage.removeItem(`antam_notes_sb_${calState.year}_${calState.month}`);
    localStorage.removeItem(`antam_notes_bot_plan_${calState.year}_${calState.month}`);
    localStorage.removeItem(`antam_notes_bot_fup_${calState.year}_${calState.month}`);
    renderNotesSection();
    if (typeof showToast === 'function') {
        showToast('Catatan bulan ini telah di-reset ke default.', 'info');
    }
}

function addSidebarCustomNote() {
    const inputs = document.querySelectorAll('#desk-sidebar-notes-body .desk-note-input');
    for (let inp of inputs) {
        if (!inp.value.trim()) {
            inp.focus();
            inp.placeholder = 'Ketik rencana baru...';
            return;
        }
    }
}

function clearSingleNote(btn, type) {
    const wrap = btn.closest('.desk-note-item-wrap');
    if (wrap) {
        const inp = wrap.querySelector('.desk-note-input');
        if (inp) {
            inp.value = '';
            saveSidebarNotes();
            btn.remove();
        }
    }
}

// -------------------------------------------------------------
// PINTASAN KHUSUS: LIHAT REFERENSI KALENDER ANTAM JANUARI 2027
// -------------------------------------------------------------
function calJumpToReference2027() {
    calState.year = 2027;
    calState.month = 0; // Januari
    calState.selectedDay = null;
    calState.weekStart = 'mon'; // Format Senin agar Minggu Merah di Kanan persis seperti foto referensi
    localStorage.setItem('cal_week_start', 'mon');
    
    renderCalendar(true);

    const scene = document.getElementById('desk-calendar-scene');
    if (scene) {
        scene.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    if (typeof showToast === 'function') {
        showToast('ðŸ—“ï¸ Menampilkan Referensi Kalender Meja PT ANTAM Tbk (Januari 2027)', 'info');
    }
}

// -------------------------------------------------------------
// MODE PENA REALISTIS (PEN / WRITE MODE)
// -------------------------------------------------------------
function togglePenMode() {
    const isActive = document.body.classList.toggle('pen-mode-active');
    const penWidget = document.getElementById('desk-pen-widget');
    if (penWidget) penWidget.classList.toggle('is-active', isActive);

    if (typeof showToast === 'function') {
        if (isActive) {
            showToast('âœ’ï¸ Mode Pena Aktif: Anda dapat mengetik langsung pada baris catatan bergaris kalender meja!', 'success');
        } else {
            showToast('Mode Pena dinonaktifkan.', 'info');
        }
    }
}

// -------------------------------------------------------------
// CETAK KALENDER MEJA (PRINT / PDF)
// -------------------------------------------------------------
function printDeskCalendar() {
    window.print();
}

// -------------------------------------------------------------
// RENDER AGENDA TIMELINE: DUA KOLOM (TELAH DILAKSANAKAN & AKAN TERJADI)
// -------------------------------------------------------------
function renderAgendaList(monthEvents, allMatchedEvents = []) {
    const listCompleted = document.getElementById('agenda-list-completed');
    const listUpcoming = document.getElementById('agenda-list-upcoming');
    const badgeCompleted = document.getElementById('badge-count-completed');
    const badgeUpcoming = document.getElementById('badge-count-upcoming');
    const subtitleMonth = document.getElementById('agenda-month-subtitle');
    const btnClearFilter = document.getElementById('btn-clear-day-filter');

    if (!listCompleted || !listUpcoming) return;

    const searchQuery = (document.getElementById('cal-search-input')?.value || '').trim();

    let targetEvents = [];
    if (calState.selectedDay) {
        // Jika sedang memilih tanggal tertentu
        const sourceEvents = (searchQuery && allMatchedEvents.length > 0) ? allMatchedEvents : monthEvents;
        targetEvents = sourceEvents.filter(e => e.date === calState.selectedDay);
        if (subtitleMonth) {
            subtitleMonth.textContent = `${formatIndoDate(calState.selectedDay)}${searchQuery ? ` â€¢ Pencarian: "${searchQuery}"` : ''}`;
        }
        if (btnClearFilter) btnClearFilter.style.display = 'inline-flex';
    } else if (searchQuery) {
        // Jika sedang mode pencarian aktif:
        if (monthEvents.length === 0 && allMatchedEvents.length > 0) {
            // Jika di bulan ini 0 kegiatan namun di bulan lain ada, tampilkan semua kegiatan yang cocok!
            targetEvents = [...allMatchedEvents];
            if (subtitleMonth) {
                subtitleMonth.textContent = `Pencarian Seluruh Bulan: "${searchQuery}" (${allMatchedEvents.length} Hasil Ditemukan)`;
            }
        } else {
            targetEvents = [...monthEvents];
            if (subtitleMonth) {
                subtitleMonth.textContent = `${INDO_MONTHS[calState.month]} ${calState.year} â€¢ Pencarian: "${searchQuery}" (${monthEvents.length} Hasil)`;
            }
        }
        if (btnClearFilter) btnClearFilter.style.display = 'none';
    } else {
        targetEvents = [...monthEvents];
        if (subtitleMonth) {
            subtitleMonth.textContent = `${INDO_MONTHS[calState.month]} ${calState.year}`;
        }
        if (btnClearFilter) btnClearFilter.style.display = 'none';
    }

    const completedEvents = targetEvents.filter(e => e.status === 'completed');
    const upcomingEvents = targetEvents.filter(e => e.status === 'upcoming');

    if (badgeCompleted) badgeCompleted.textContent = `${completedEvents.length} Selesai`;
    if (badgeUpcoming) badgeUpcoming.textContent = `${upcomingEvents.length} Terjadwal`;

    // 1. Kolom Telah Dilaksanakan
    if (completedEvents.length === 0) {
        listCompleted.innerHTML = `
            <div class="cal-empty-state">
                <i class="ph-bold ph-check-circle" style="color: #cbd5e1;"></i>
                <p style="font-size: 0.85rem; font-weight: 600; color: #64748b;">
                    ${searchQuery 
                        ? `Tidak ada riwayat kegiatan selesai yang cocok dengan "${escapeHtml(searchQuery)}".` 
                        : `Belum ada riwayat kegiatan selesai ${calState.selectedDay ? 'pada tanggal ini' : 'di bulan ini'}.`}
                </p>
            </div>
        `;
    } else {
        listCompleted.innerHTML = completedEvents.map(e => {
            const formattedDate = formatIndoDate(e.date);
            return `
                <div class="cal-agenda-card card-completed">
                    <div class="cal-agenda-card-top">
                        <span class="cal-agenda-date-badge">
                            <i class="ph-bold ph-calendar"></i> ${formattedDate}
                        </span>
                        <span class="cal-agenda-status-pill pill-status-done">
                            <i class="ph-bold ph-check-circle"></i> Telah Dilaksanakan
                        </span>
                    </div>
                    
                    <h5 class="cal-agenda-card-title">${searchQuery ? highlightMatch(e.title, searchQuery) : escapeHtml(e.title)}</h5>
                    
                    <div class="cal-agenda-card-meta">
                        <span><i class="ph-bold ph-tag"></i> ${escapeHtml(e.category)}</span>
                        <span><i class="ph-bold ph-clock"></i> ${escapeHtml(e.duration)}</span>
                        <span><i class="ph-bold ph-user"></i> ${escapeHtml(e.user_name || 'Pegawai Site Pomalaa')}</span>
                    </div>

                    <div class="cal-agenda-card-bottom">
                        <span class="text-xs" style="color: #059669; font-weight: 700;">
                            <i class="ph-fill ph-seal-check"></i> Selesai & Terverifikasi (100%)
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" class="sf-btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;" onclick="calJumpToEvent('${e.id}', '${e.date}')" title="Buka jadwal tanggal ini di Kalender Meja">
                                <i class="ph-bold ph-calendar"></i> Kalender
                            </button>
                            <button type="button" class="sf-btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.78rem;" onclick="openCalendarEventDetailModal('${e.id}')">
                                Detail
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // 2. Kolom Akan Terjadi
    if (upcomingEvents.length === 0) {
        listUpcoming.innerHTML = `
            <div class="cal-empty-state">
                <i class="ph-bold ph-calendar-plus" style="color: #cbd5e1;"></i>
                <p style="font-size: 0.85rem; font-weight: 600; color: #64748b;">
                    ${searchQuery 
                        ? `Tidak ada agenda terjadwal yang cocok dengan "${escapeHtml(searchQuery)}".` 
                        : `Tidak ada jadwal pelatihan mendatang ${calState.selectedDay ? 'pada tanggal ini' : 'di bulan ini'}.`}
                </p>
            </div>
        `;
    } else {
        listUpcoming.innerHTML = upcomingEvents.map(e => {
            const formattedDate = formatIndoDate(e.date);
            const countdownBadge = getCountdownBadge(e.date);

            const isUserAdmin = (CURRENT_USER_ROLE === 'Admin');
            const currentUname = (window.CURRENT_USERNAME || '').toLowerCase();
            const eventUname = (e.user_username || '').toLowerCase();
            const isOwner = (eventUname && eventUname === currentUname);
            const isAnotherUserActivity = (eventUname && !isOwner && !isUserAdmin);

            const safeTitle = (e.title || '').replace(/'/g, "\\'");
            const safeType = (e.type || 'Wajib').replace(/'/g, "\\'");
            const safeCat = (e.category || 'Operasional Tambang').replace(/'/g, "\\'");
            const safeDur = (e.duration || '2 Hari (16 Jam)').replace(/'/g, "\\'");
            const safeLev = (e.level || 'Basic').replace(/'/g, "\\'");

            let actionBtn = '';
            if (isAnotherUserActivity) {
                actionBtn = `<span class="tag tag-gray" style="font-size: 0.72rem; padding: 3px 8px;" title="Kegiatan milik rekan pegawai lain: hanya dapat dilihat di kalender"><i class="ph-bold ph-eye"></i> Jadwal Rekan</span>`;
            } else if (isOwner) {
                actionBtn = `<a href="<?= base_url('my-learning') ?>" class="sf-btn-teal" style="padding: 0.35rem 0.75rem; font-size: 0.78rem; text-decoration: none;"><i class="ph-bold ph-graduation-cap"></i> Kegiatan Saya</a>`;
            } else if (isUserAdmin) {
                actionBtn = `<button type="button" class="sf-btn-teal" style="padding: 0.35rem 0.75rem; font-size: 0.78rem;" onclick="openAssignTrainingModal('${safeTitle}')"><i class="ph-bold ph-user-plus"></i> Tugaskan</button>`;
            } else {
                actionBtn = `<button type="button" class="sf-btn-teal" style="padding: 0.35rem 0.75rem; font-size: 0.78rem;" onclick="openNewActivityModalWithPreset('${safeTitle}', '${safeType}', '${safeCat}', '${safeDur}', '${safeLev}')"><i class="ph-bold ph-check"></i> Ikuti Jadwal</button>`;
            }

            const targetLabel = e.user_name ? ('Pegawai: ' + e.user_name) : (e.target || 'Seluruh Pegawai Site');

            return `
                <div class="cal-agenda-card card-upcoming">
                    <div class="cal-agenda-card-top">
                        <span class="cal-agenda-date-badge">
                            <i class="ph-bold ph-calendar"></i> ${formattedDate}
                        </span>
                        <span class="cal-agenda-status-pill pill-status-upcoming">
                            <i class="ph-bold ph-clock"></i> Akan Terjadi
                        </span>
                    </div>
                    
                    <h5 class="cal-agenda-card-title">${searchQuery ? highlightMatch(e.title, searchQuery) : escapeHtml(e.title)}</h5>
                    
                    <div class="cal-agenda-card-meta">
                        <span><i class="ph-bold ph-tag"></i> ${escapeHtml(e.category)}</span>
                        <span><i class="ph-bold ph-clock"></i> ${escapeHtml(e.duration)}</span>
                        <span title="${escapeHtml(targetLabel)}"><i class="ph-bold ph-user"></i> ${escapeHtml(targetLabel)}</span>
                    </div>

                    <div class="cal-agenda-card-bottom">
                        ${countdownBadge}
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button type="button" class="sf-btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;" onclick="calJumpToEvent('${e.id}', '${e.date}')" title="Buka jadwal tanggal ini di Kalender Meja">
                                <i class="ph-bold ph-calendar"></i> Kalender
                            </button>
                            <button type="button" class="sf-btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.78rem;" onclick="openCalendarEventDetailModal('${e.id}')">
                                Info
                            </button>
                            ${actionBtn}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
}

// -------------------------------------------------------------
// HELPER FORMAT & COUNTDOWN
// -------------------------------------------------------------
function formatIndoDate(isoDateStr) {
    if (!isoDateStr) return '';
    const parts = isoDateStr.split('-');
    if (parts.length === 3) {
        const d = parseInt(parts[2], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parts[0];
        return `${d} ${INDO_MONTHS[m]} ${y}`;
    }
    return isoDateStr;
}

function getCountdownBadge(isoDateStr) {
    const simToday = getSystemSimulatedDate();
    const tToday = new Date(simToday).getTime();
    const tEvent = new Date(isoDateStr).getTime();
    const diffDays = Math.round((tEvent - tToday) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return `<span style="font-size: 0.75rem; font-weight: 700; color: #d97706;"><i class="ph-fill ph-lightning"></i> Pelaksanaan Hari Ini</span>`;
    } else if (diffDays > 0) {
        return `<span style="font-size: 0.75rem; font-weight: 700; color: #0284c7;"><i class="ph-bold ph-hourglass"></i> ${diffDays} Hari Lagi</span>`;
    } else {
        return `<span style="font-size: 0.75rem; font-weight: 700; color: #059669;"><i class="ph-bold ph-check"></i> Selesai Dilaksanakan</span>`;
    }
}

// -------------------------------------------------------------
// NAVIGASI & KONTROL KALENDER
// -------------------------------------------------------------
function calChangeMonth(delta) {
    calState.month += delta;
    if (calState.month < 0) {
        calState.month = 11;
        calState.year -= 1;
    } else if (calState.month > 11) {
        calState.month = 0;
        calState.year += 1;
    }
    calState.selectedDay = null;
    renderCalendar(true);
}

function calGoToToday() {
    const now = new Date();
    calState.year = now.getFullYear();
    calState.month = now.getMonth(); // Real-time bulan saat ini
    calState.selectedDay = null;
    renderCalendar(true);
    if (typeof showToast === 'function') {
        showToast(`Kalender kembali ke bulan real-time: ${INDO_MONTHS[calState.month]} ${calState.year}`, 'info');
    }
}

function calJumpMonth(val) {
    calState.month = parseInt(val, 10);
    calState.selectedDay = null;
    renderCalendar();
}

function calJumpYear(val) {
    calState.year = parseInt(val, 10);
    calState.selectedDay = null;
    renderCalendar();
}

function filterCalStatus(status) {
    calState.filterStatus = status;

    document.querySelectorAll('.cal-filter-pill').forEach(btn => btn.classList.remove('active'));
    const targetBtn = document.getElementById(`pill-status-${status}`);
    if (targetBtn) targetBtn.classList.add('active');

    renderCalendar();
}

function selectCalendarDay(dateStr) {
    if (calState.selectedDay === dateStr) {
        calState.selectedDay = null;
    } else {
        calState.selectedDay = dateStr;
    }
    renderCalendar();

    // Scroll halus ke daftar agenda jika memilih tanggal
    if (calState.selectedDay) {
        const agendaSection = document.querySelector('.cal-agenda-section');
        if (agendaSection) {
            agendaSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

// Handler saat tanggal pada kalender meja diklik
function onCalendarDateClick(dateStr) {
    calState.selectedDay = dateStr;
    renderCalendar();
    openDateActionModal(dateStr);
}

// =============================================================
// MODAL NOTIFIKASI & KONFIRMASI TAMBAH KEGIATAN TANGGAL
// Menampilkan info agenda & konfirmasi apakah ingin menambah kegiatan ke Menu Training
// =============================================================
function openDateActionModal(dateStr) {
    if (!dateStr) return;

    // Parse tanggal untuk header
    const parts = dateStr.split('-');
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    const dateObj = new Date(y, m, d);
    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const dayNameIndo = dayNames[dateObj.getDay()] || 'Hari';
    const formattedIndo = `${d} ${INDO_MONTHS[m]} ${y}`;
    const fullHeaderLabel = `${dayNameIndo}, ${formattedIndo}`;

    // Cari semua event pada tanggal ini
    const dayEvents = ALL_CALENDAR_EVENTS.filter(e => e.date === dateStr);

    let eventsHtml = '';
    if (dayEvents.length > 0) {
        eventsHtml = `
            <div class="cal-notice-events-box">
                <div class="cal-notice-events-header flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase font-mono text-dark flex items-center gap-1.5">
                        <i class="ph-bold ph-calendar-check text-accent"></i> Kegiatan Terdaftar (${dayEvents.length}):
                    </span>
                    <span class="text-xs text-muted font-mono">${formattedIndo}</span>
                </div>
                <div class="cal-notice-events-list">
                    ${dayEvents.map(evt => {
                        const isDone = (evt.status === 'completed');
                        const isHoliday = (evt.type === 'Libur Nasional' || evt.category === 'HARI LIBUR NASIONAL');
                        let badgeHtml = '';
                        if (isHoliday) {
                            badgeHtml = '<span class="tag tag-red text-xs"><i class="ph-fill ph-star"></i> Libur Nasional</span>';
                        } else if (isDone) {
                            badgeHtml = '<span class="tag tag-green text-xs"><i class="ph-fill ph-check-circle"></i> Selesai</span>';
                        } else {
                            badgeHtml = '<span class="tag tag-sky text-xs"><i class="ph-fill ph-clock"></i> Terjadwal</span>';
                        }

                        const isCustom = (evt.id && String(evt.id).startsWith('evt_custom_'));

                        return `
                            <div class="cal-notice-event-item">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="sf-course-code" style="font-size: 0.72rem; padding: 1px 5px;">${escapeHtml(evt.code || 'EVENT')}</span>
                                        ${badgeHtml}
                                        <span class="tag tag-gray text-xs">${escapeHtml(evt.type || 'Pelatihan')}</span>
                                    </div>
                                    <strong class="text-sm text-dark block truncate" title="${escapeHtml(evt.title)}">${escapeHtml(evt.title)}</strong>
                                    <div class="text-xs text-muted mt-0.5 flex items-center gap-3 flex-wrap">
                                        <span><i class="ph-bold ph-tag"></i> ${escapeHtml(evt.category)}</span>
                                        <span><i class="ph-bold ph-clock"></i> ${escapeHtml(evt.duration || '1 Hari')}</span>
                                        <span><i class="ph-bold ph-user"></i> ${escapeHtml(evt.user_name || 'Seluruh Pegawai')}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button type="button" class="sf-btn-outline text-xs" onclick="closeModal(); openCalendarEventDetailModal('${evt.id}')" title="Buka Detail">
                                        <i class="ph-bold ph-info"></i> Info
                                    </button>
                                    ${isCustom ? `
                                    <button type="button" class="sf-btn-outline text-xs" style="color: #dc2626; border-color: #fca5a5;" onclick="deleteCustomCalendarEvent('${evt.id}', '${dateStr}')" title="Hapus Kegiatan Kustom">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    } else {
        eventsHtml = `
            <div class="cal-notice-empty-state" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.6rem;">
                <i class="ph-bold ph-calendar-blank text-accent" style="font-size: 1.25rem;"></i>
                <span class="text-xs text-muted font-medium">Belum ada agenda kegiatan pelatihan atau operasional K3 pada tanggal ini.</span>
            </div>
        `;
    }

    const modalBody = `
        <div class="cal-notice-modal">
            <!-- Header Kartu Tanggal -->
            <div class="cal-notice-header-card">
                <div class="cal-notice-icon-circle">
                    <i class="ph-duotone ph-calendar-blank"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <span class="cal-notice-badge-label">Kalender Meja ANTAM â€¢ Site Pomalaa</span>
                    <h4 class="cal-notice-title">${fullHeaderLabel}</h4>
                    <div class="cal-notice-site">
                        <i class="ph-fill ph-map-pin"></i> PT ANTAM Tbk â€¢ Site Pomalaa
                    </div>
                </div>
                <div class="cal-date-badge-pill" style="background: rgba(255,255,255,0.22); color: #fff; font-weight: 700; border: 1px solid rgba(255,255,255,0.3); padding: 5px 12px; border-radius: 99px; font-size: 0.76rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                    ${dayEvents.length > 0 ? `<i class="ph-fill ph-check-circle"></i> ${dayEvents.length} Kegiatan` : '<i class="ph-bold ph-calendar-blank"></i> 0 Agenda'}
                </div>
            </div>

            <!-- List Kegiatan Terdaftar Jika Ada -->
            ${eventsHtml}

            <!-- Kotak Pemberitahuan & Pertanyaan Konfirmasi -->
            <div class="cal-notice-prompt-box">
                <div class="cal-notice-prompt-icon">
                    <i class="ph-duotone ph-bell-ringing"></i>
                </div>
                <div class="cal-notice-prompt-text" style="flex: 1;">
                    <h5 style="color: #0f766e; font-size: 0.95rem; font-weight: 800; margin-bottom: 0.25rem;">Pemberitahuan Penambahan Kegiatan</h5>
                    <p style="font-size: 0.84rem; color: #334155; margin-bottom: 0.35rem; line-height: 1.45;">
                        Apakah Anda ingin menambahkan kegiatan pembelajaran / pelatihan baru pada tanggal <strong>${fullHeaderLabel}</strong>?
                    </p>
                    <p style="font-size: 0.78rem; color: #64748b; margin: 0; line-height: 1.4;">
                        <i class="ph-bold ph-info text-accent"></i> Jika <strong>Iya</strong>, sistem akan mengarahkan Anda ke <strong>Menu Training</strong> untuk melengkapi formulir penugasan modul.
                    </p>
                </div>
            </div>

            <!-- Tombol Aksi Konfirmasi (Batal vs Ya Menuju Menu Training) -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t mt-1">
                <button type="button" class="btn-light" onclick="closeModal()" style="font-size: 0.82rem; padding: 0.5rem 1rem;">
                    <i class="ph-bold ph-x"></i> Batal / Tutup
                </button>
                <button type="button" class="btn-dark" onclick="goToTrainingMenuToAdd('${dateStr}')" style="font-size: 0.82rem; padding: 0.52rem 1.15rem; background: #0f766e; border-color: #0f766e;">
                    <i class="ph-bold ph-graduation-cap"></i> Ya, Menuju Menu Training
                </button>
            </div>
        </div>
    `;

    openModal(`Pemberitahuan Agenda: ${formattedIndo}`, modalBody, '', 'ph-duotone ph-bell-ringing', true);
}

// Redirect ke Menu Training dengan parameter tanggal terpilih
function goToTrainingMenuToAdd(dateStr) {
    closeModal();
    const formattedIndo = formatIndoDate(dateStr);
    if (typeof showToast === 'function') {
        showToast(`ðŸ—“ï¸ Mengarahkan ke menu Training untuk tanggal ${formattedIndo}...`, 'info');
    }
    setTimeout(() => {
        window.location.href = `<?= base_url('my-learning') ?>&add_date=${encodeURIComponent(dateStr)}`;
    }, 350);
}

function deleteCustomCalendarEvent(eventId, dateStr) {
    if (!confirm('Apakah Anda yakin ingin menghapus kegiatan ini dari kalender?')) return;

    ALL_CALENDAR_EVENTS = ALL_CALENDAR_EVENTS.filter(e => e.id !== eventId);
    window.ALL_CALENDAR_EVENTS = ALL_CALENDAR_EVENTS;

    try {
        let stored = [];
        const raw = localStorage.getItem('antam_custom_calendar_events');
        if (raw) stored = JSON.parse(raw);
        if (Array.isArray(stored)) {
            stored = stored.filter(e => e.id !== eventId);
            localStorage.setItem('antam_custom_calendar_events', JSON.stringify(stored));
        }
    } catch(err) {}

    showToast('Kegiatan berhasil dihapus dari kalender.', 'info');
    renderCalendar();
    if (dateStr) {
        openDateActionModal(dateStr);
    }
}

// -------------------------------------------------------------
// SISTEM PENCARIAN & FILTER KEGIATAN KALENDER REAL-TIME
// -------------------------------------------------------------
function eventMatchesQuery(evt, searchQuery, categoryQuery) {
    if (categoryQuery) {
        const evtCat = (evt.category || '').toLowerCase();
        if (evtCat !== categoryQuery) return false;
    }
    if (!searchQuery) return true;

    const q = searchQuery.toLowerCase().trim();
    return (evt.title && evt.title.toLowerCase().includes(q))
        || (evt.code && evt.code.toLowerCase().includes(q))
        || (evt.user_name && evt.user_name.toLowerCase().includes(q))
        || (evt.user_username && evt.user_username.toLowerCase().includes(q))
        || (evt.category && evt.category.toLowerCase().includes(q))
        || (evt.description && evt.description.toLowerCase().includes(q))
        || (evt.location && evt.location.toLowerCase().includes(q))
        || (evt.instructor && evt.instructor.toLowerCase().includes(q))
        || (evt.target && evt.target.toLowerCase().includes(q))
        || (evt.type && evt.type.toLowerCase().includes(q))
        || (evt.date && evt.date.includes(q));
}

function highlightMatch(text, query) {
    if (!text) return '';
    if (!query) return escapeHtml(text);
    const escapedText = escapeHtml(text);
    const escapedQuery = escapeHtml(query);
    const regex = new RegExp(`(${escapedQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return escapedText.replace(regex, '<span class="cal-search-highlight">$1</span>');
}

function updateCalSearchBanner(searchQuery, monthEvents, allMatchedEvents, monthNameIndo) {
    const bannerContainer = document.getElementById('cal-search-banner-container');
    if (!bannerContainer) return;

    if (!searchQuery) {
        bannerContainer.innerHTML = '';
        bannerContainer.style.display = 'none';
        return;
    }

    const monthPrefix = `${calState.year}-${String(calState.month + 1).padStart(2, '0')}`;
    const otherMonthMap = {};
    allMatchedEvents.forEach(e => {
        if (!e.date) return;
        const p = e.date.split('-');
        if (p.length >= 2) {
            const key = `${p[0]}-${p[1]}`;
            if (key !== monthPrefix) {
                const y = parseInt(p[0], 10);
                const m = parseInt(p[1], 10) - 1;
                if (!otherMonthMap[key]) {
                    otherMonthMap[key] = { year: y, month: m, label: `${INDO_MONTHS[m]} ${y}`, count: 0 };
                }
                otherMonthMap[key].count++;
            }
        }
    });

    const otherMonthKeys = Object.keys(otherMonthMap);
    let jumpBtnsHtml = '';
    if (otherMonthKeys.length > 0) {
        jumpBtnsHtml = `
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs text-muted font-bold">Buka bulan lain:</span>
                ${otherMonthKeys.map(k => {
                    const om = otherMonthMap[k];
                    return `<button type="button" class="btn-jump-month-pill" onclick="calJumpToYearMonth(${om.year}, ${om.month})">
                        <i class="ph-bold ph-arrow-right"></i> ${om.label} <span class="badge-count">${om.count}</span>
                    </button>`;
                }).join('')}
            </div>
        `;
    }

    let statusText = '';
    if (allMatchedEvents.length === 0) {
        statusText = `<span style="color:#dc2626; font-weight:700;"><i class="ph-bold ph-warning-circle"></i> Tidak ditemukan kegiatan dengan kata kunci "<strong>${escapeHtml(searchQuery)}</strong>"</span>`;
    } else if (monthEvents.length === 0) {
        statusText = `<span style="color:#b45309; font-weight:700;"><i class="ph-bold ph-info"></i> Tidak ada kegiatan di bulan <strong>${monthNameIndo} ${calState.year}</strong>. Ditemukan <strong>${allMatchedEvents.length} kegiatan</strong> di bulan lain:</span>`;
    } else {
        statusText = `<span style="color:#0f766e; font-weight:700;"><i class="ph-bold ph-check-circle"></i> Ditemukan <strong>${allMatchedEvents.length} kegiatan</strong> (${monthEvents.length} di bulan ${monthNameIndo} ${calState.year})</span>`;
    }

    bannerContainer.innerHTML = `
        <div class="cal-search-banner">
            <div class="flex items-center gap-2 flex-wrap">
                ${statusText}
                ${jumpBtnsHtml}
            </div>
            <button type="button" class="btn-clear-search-pill" onclick="clearCalSearch()" title="Hapus Filter Pencarian">
                <i class="ph-bold ph-x"></i> Hapus Filter
            </button>
        </div>
    `;
    bannerContainer.style.display = 'block';
}

function handleCalSearchInput(val) {
    const clearBtn = document.getElementById('cal-search-clear');
    if (clearBtn) {
        clearBtn.style.display = (val && val.trim().length > 0) ? 'inline-flex' : 'none';
    }

    renderLiveSearchDropdown(val);
    filterCalendar();
}

function handleCalSearchFocus() {
    const input = document.getElementById('cal-search-input');
    if (input && input.value.trim().length > 0) {
        renderLiveSearchDropdown(input.value);
    }
}

function handleCalSearchKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const input = document.getElementById('cal-search-input');
        const q = (input?.value || '').toLowerCase().trim();
        const cat = (document.getElementById('cal-category-filter')?.value || '').toLowerCase().trim();
        if (q) {
            const matches = ALL_CALENDAR_EVENTS.filter(evt => eventMatchesQuery(evt, q, cat));
            if (matches.length > 0) {
                calJumpToEvent(matches[0].id, matches[0].date);
            }
        }
        const dropdown = document.getElementById('cal-search-dropdown');
        if (dropdown) dropdown.style.display = 'none';
    } else if (e.key === 'Escape') {
        const dropdown = document.getElementById('cal-search-dropdown');
        if (dropdown) dropdown.style.display = 'none';
    }
}

function renderLiveSearchDropdown(query) {
    const dropdown = document.getElementById('cal-search-dropdown');
    if (!dropdown) return;

    const q = (query || '').trim();
    const cat = (document.getElementById('cal-category-filter')?.value || '').toLowerCase().trim();

    if (!q) {
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
        return;
    }

    const matches = ALL_CALENDAR_EVENTS.filter(evt => eventMatchesQuery(evt, q, cat));

    if (matches.length === 0) {
        dropdown.innerHTML = `
            <div class="cal-dropdown-empty">
                <i class="ph-bold ph-magnifying-glass" style="font-size: 1.5rem; color: #cbd5e1; display: block; margin-bottom: 0.35rem;"></i>
                Tidak ada kegiatan yang sesuai dengan "<strong>${escapeHtml(q)}</strong>"
            </div>
        `;
        dropdown.style.display = 'block';
        return;
    }

    const topMatches = matches.slice(0, 8);
    let html = `
        <div class="cal-dropdown-header">
            <span>Ditemukan ${matches.length} kegiatan</span>
            <span class="text-xs text-muted">Klik untuk menuju tanggal</span>
        </div>
    `;

    topMatches.forEach(e => {
        const isDone = (e.status === 'completed');
        const statusBadge = isDone 
            ? `<span class="tag tag-green text-xs"><i class="ph-fill ph-check-circle"></i> Selesai</span>` 
            : `<span class="tag tag-sky text-xs"><i class="ph-fill ph-clock"></i> Terjadwal</span>`;

        html += `
            <div class="cal-dropdown-item" onclick="calJumpToEvent('${e.id}', '${e.date}')">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="sf-course-code" style="font-size: 0.7rem; padding: 1px 5px;">${escapeHtml(e.code || 'EVENT')}</span>
                    <span class="cal-dropdown-date"><i class="ph-bold ph-calendar"></i> ${formatIndoDate(e.date)}</span>
                </div>
                <div class="cal-dropdown-title">${highlightMatch(e.title, q)}</div>
                <div class="cal-dropdown-meta">
                    ${statusBadge}
                    <span class="text-xs text-muted"><i class="ph-bold ph-tag"></i> ${escapeHtml(e.category)}</span>
                </div>
            </div>
        `;
    });

    if (matches.length > 8) {
        html += `
            <div class="cal-dropdown-footer">
                Dan ${matches.length - 8} kegiatan lainnya (lihat detail di tabel agenda bawah)
            </div>
        `;
    }

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
}

function clearCalSearch() {
    const input = document.getElementById('cal-search-input');
    if (input) input.value = '';
    const clearBtn = document.getElementById('cal-search-clear');
    if (clearBtn) clearBtn.style.display = 'none';
    const dropdown = document.getElementById('cal-search-dropdown');
    if (dropdown) dropdown.style.display = 'none';
    calState.selectedDay = null;
    filterCalendar();
}

function calJumpToYearMonth(year, month, selectedDay = null) {
    calState.year = parseInt(year, 10);
    calState.month = parseInt(month, 10);
    calState.selectedDay = selectedDay;
    renderCalendar(true);
    if (selectedDay) {
        setTimeout(() => {
            const dayEl = document.querySelector(`.desk-cal-day-box.is-selected`);
            if (dayEl) {
                dayEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }, 180);
    }
}

function calJumpToEvent(eventId, dateStr) {
    if (!dateStr) return;
    const parts = dateStr.split('-');
    if (parts.length >= 3) {
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        calJumpToYearMonth(y, m, dateStr);
    }

    const dropdown = document.getElementById('cal-search-dropdown');
    if (dropdown) dropdown.style.display = 'none';

    if (typeof showToast === 'function') {
        showToast(`Membuka jadwal tanggal ${formatIndoDate(dateStr)}`, 'info');
    }

    setTimeout(() => {
        openCalendarEventDetailModal(eventId);
    }, 250);
}

function filterCalendar() {
    renderCalendar();
}

// -------------------------------------------------------------
// DETAIL MODAL KEGIATAN KALENDER
// -------------------------------------------------------------
function openCalendarEventDetailModal(eventId) {
    const evt = ALL_CALENDAR_EVENTS.find(e => e.id === eventId);
    if (!evt) return;

    const isDone = (evt.status === 'completed');
    const statusPill = isDone
        ? `<span class="tag tag-green"><i class="ph-fill ph-check-circle"></i> Telah Dilaksanakan</span>`
        : `<span class="tag tag-sky"><i class="ph-fill ph-clock"></i> Akan Terjadi (Terjadwal)</span>`;

    const formattedDate = formatIndoDate(evt.date);
    const isAdmin = (CURRENT_USER_ROLE === 'Admin');
    const currentUname = (window.CURRENT_USERNAME || '').toLowerCase();
    const eventUname = (evt.user_username || '').toLowerCase();
    const isOwner = (eventUname && eventUname === currentUname);
    const isAnotherUserActivity = (eventUname && !isOwner && !isAdmin);

    const peerNoticeHtml = isAnotherUserActivity ? `
        <div class="glass-panel p-3 mb-3" style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; color:#0369a1; font-size:0.8rem; line-height:1.45;">
            <div class="flex items-center gap-2 font-bold mb-1" style="color:#0284c7;">
                <i class="ph-bold ph-shield-check" style="font-size:1.15rem;"></i>
                <span>Hak Akses Kalender: Mode Lihat Jadwal (View-Only)</span>
            </div>
            Kegiatan ini terdaftar resmi atas nama pegawai <strong>${escapeHtml(evt.user_name || evt.user_username)}</strong>. Rekan kerja lainnya dapat melihat informasi jadwal pada kalender, namun hak konfirmasi dan upload berkas absensi hanya dimiliki oleh pegawai yang bersangkutan &amp; Tim HC/Admin.
        </div>
    ` : '';

    const bodyHtml = `
        <div class="calendar-detail-modal">
            ${peerNoticeHtml}
            <div class="flex items-center gap-2 mb-3">
                <span class="sf-course-code">${escapeHtml(evt.code)}</span>
                ${statusPill}
                <span class="tag ${evt.type === 'Wajib' ? 'tag-red' : (evt.type === 'Sertifikasi' ? 'tag-teal' : 'tag-gray')}">${escapeHtml(evt.type)}</span>
            </div>

            <h3 class="text-xl font-bold mb-2 text-dark">${escapeHtml(evt.title)}</h3>
            <p class="text-sm text-muted mb-4" style="line-height: 1.6;">${escapeHtml(evt.description || 'Pelatihan peningkatan standar kompetensi kerja PT ANTAM Tbk.')}</p>

            <div class="glass-panel p-3 mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; font-size: 0.85rem;">
                    <div>
                        <span class="text-xs text-muted uppercase font-mono block">Jadwal Tanggal:</span>
                        <strong class="text-dark"><i class="ph-bold ph-calendar text-accent"></i> ${formattedDate}</strong>
                    </div>
                    <div>
                        <span class="text-xs text-muted uppercase font-mono block">Durasi Pembelajaran:</span>
                        <strong class="text-dark"><i class="ph-bold ph-clock text-accent"></i> ${escapeHtml(evt.duration)}</strong>
                    </div>
                    <div>
                        <span class="text-xs text-muted uppercase font-mono block">Lokasi & Fasilitas:</span>
                        <strong class="text-dark"><i class="ph-bold ph-map-pin text-accent"></i> ${escapeHtml(evt.location || 'Site Pomalaa')}</strong>
                    </div>
                    <div>
                        <span class="text-xs text-muted uppercase font-mono block">Level Kompetensi:</span>
                        <strong class="text-dark"><i class="ph-bold ph-chart-bar text-accent"></i> ${escapeHtml(evt.level)}</strong>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <span class="text-xs text-muted uppercase font-mono block">Pemilik / Sasaran Peserta:</span>
                        <strong class="text-dark"><i class="ph-bold ph-user text-accent"></i> ${escapeHtml(evt.user_name ? (evt.user_name + (evt.user_username ? ' (@' + evt.user_username + ')' : '')) : (evt.target || 'Seluruh Pegawai Site'))}</strong>
                    </div>
                </div>
            </div>

            <div class="syllabus-box glass-panel p-3 mb-2" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
                <h5 class="text-xs font-semibold uppercase text-muted mb-2 font-mono">Penyelenggara & Standar Lisensi:</h5>
                <p class="text-sm" style="color: #334155;">Pengampu: <strong>${escapeHtml(evt.instructor || 'Tim L&D Corporate ANTAM')}</strong></p>
                <p class="text-xs text-muted mt-1">Sertifikasi & logbook terintegrasi ke buku tambang dan database kepatuhan K3 ESDM.</p>
            </div>
        </div>
    `;

    const safeTitle = (evt.title || '').replace(/'/g, "\\'");
    const safeType = (evt.type || 'Wajib').replace(/'/g, "\\'");
    const safeCat = (evt.category || 'Operasional Tambang').replace(/'/g, "\\'");
    const safeDur = (evt.duration || '2 Hari').replace(/'/g, "\\'");
    const safeLev = (evt.level || 'Basic').replace(/'/g, "\\'");

    let actionBtn = '';
    if (isAnotherUserActivity) {
        // Rekan pegawai lain: hanya melihat jadwal di kalender
        actionBtn = '';
    } else if (isOwner) {
        actionBtn = `<button type="button" class="btn-dark" onclick="closeModal(); window.location.href='<?= base_url('my-learning') ?>'"><i class="ph-bold ph-graduation-cap"></i> Buka di My Learning Saya</button>`;
    } else if (isAdmin) {
        actionBtn = `<button type="button" class="btn-dark" onclick="closeModal(); openAssignTrainingModal('${safeTitle}')"><i class="ph-bold ph-user-plus"></i> Tugaskan ke Pegawai</button>`;
    } else if (!isDone) {
        actionBtn = `<button type="button" class="btn-dark" onclick="closeModal(); openNewActivityModalWithPreset('${safeTitle}', '${safeType}', '${safeCat}', '${safeDur}', '${safeLev}')"><i class="ph-bold ph-check"></i> Ikuti Jadwal Ini</button>`;
    } else {
        actionBtn = `<button type="button" class="btn-dark" onclick="closeModal(); window.location.href='<?= base_url('my-learning') ?>'"><i class="ph-bold ph-graduation-cap"></i> Buka Logbook & Riwayat</button>`;
    }

    const footerHtml = `
        <button type="button" class="btn-light" onclick="closeModal()">Tutup</button>
        ${actionBtn}
    `;

    openModal('Detail Agenda Kegiatan', bodyHtml, footerHtml, 'ph-fill ph-calendar-check');
}

// -------------------------------------------------------------
// MODAL KHUSUS ADMIN: TAMBAH MASTER PELATIHAN BARU
// -------------------------------------------------------------
function openAddNewCourseModal() {
    const bodyHtml = `
        <form id="modal-form-add-training" onsubmit="handleAddNewTrainingFromModal(event)" class="admin-form">
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="sf-filter-field">
                    <label class="sf-filter-label">KODE TRAINING *</label>
                    <input type="text" id="modal-new-code" placeholder="cth. TRC5-99" class="sf-filter-input" required>
                </div>

                <div class="sf-filter-field">
                    <label class="sf-filter-label">NAMA TRAINING / MODUL *</label>
                    <input type="text" id="modal-new-title" placeholder="cth. Manajemen K3 Tambang Pomalaa" class="sf-filter-input" required>
                </div>
                
                <div class="sf-filter-field">
                    <label class="sf-filter-label">KATEGORI TRAINING</label>
                    <input type="text" id="modal-new-category" placeholder="cth. TEKNIK TAMBANG" class="sf-filter-input" required>
                </div>

                <div class="sf-filter-field">
                    <label class="sf-filter-label">TIPE KURIKULUM *</label>
                    <select id="modal-new-type" class="sf-filter-select" required>
                        <option value="Inhouse">Inhouse</option>
                        <option value="Public">Public</option>
                        <option value="Wajib">Wajib (K3 ESDM)</option>
                    </select>
                </div>
                
                <div class="sf-filter-field">
                    <label class="sf-filter-label">DURASI PEMBELAJARAN *</label>
                    <input type="text" id="modal-new-duration" placeholder="cth. 3 Hari (24 Jam)" class="sf-filter-input" required>
                </div>
                
                <div class="sf-filter-field">
                    <label class="sf-filter-label">LEVEL KOMPETENSI *</label>
                    <select id="modal-new-level" class="sf-filter-select" required>
                        <option value="Basic">Basic</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
                
                <div class="sf-filter-field" style="grid-column: 1 / -1;">
                    <label class="sf-filter-label">DESKRIPSI & SILABUS MATERI</label>
                    <textarea id="modal-new-desc" placeholder="Tuliskan gambaran umum materi, target peserta, dan kompetensi yang diuji..." class="sf-filter-input" rows="3"></textarea>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-3 mt-4 border-t pt-3">
                <button type="button" class="btn-light" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-dark" id="btn-submit-modal-course">
                    <i class="ph-bold ph-floppy-disk"></i>
                    <span>Simpan ke Katalog Master</span>
                </button>
            </div>
        </form>
    `;

    openModal('Tambah Master Pelatihan Baru', bodyHtml, '', 'ph-fill ph-plus-circle');
}

async function handleAddNewTrainingFromModal(e) {
    e.preventDefault();
    const code = document.getElementById('modal-new-code').value.trim() || 'TRC-NEW';
    const title = document.getElementById('modal-new-title').value.trim();
    const category = document.getElementById('modal-new-category').value.trim() || 'GENERAL';
    const type = document.getElementById('modal-new-type').value;
    const duration = document.getElementById('modal-new-duration').value.trim() || '2 Hari (16 Jam)';
    const level = document.getElementById('modal-new-level').value;
    const desc = document.getElementById('modal-new-desc').value.trim() || 'Pelatihan baru standar operasional.';

    if (!title) {
        showToast('Nama modul pelatihan wajib diisi!', 'warning');
        return;
    }

    const submitBtn = document.getElementById('btn-submit-modal-course');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Menyimpan...';
    }

    const fd = new FormData();
    fd.append('action', 'add_catalog_course');
    fd.append('code', code);
    fd.append('title', title);
    fd.append('category', category);
    fd.append('type', type);
    fd.append('duration', duration);
    fd.append('level', level);
    fd.append('desc', desc);

    try {
        const resp = await fetch('api.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if (res.success) {
            closeModal();
            showToast(`âœ… Modul "${title}" berhasil disimpan ke katalog master!`, 'success');
            setTimeout(() => { window.location.reload(); }, 900);
        } else {
            showToast(res.message || 'Gagal menyimpan ke database.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph-bold ph-floppy-disk"></i> <span>Simpan ke Katalog Master</span>';
            }
        }
    } catch (err) {
        closeModal();
        showToast(`Modul "${title}" disimpan untuk sesi ini.`, 'info');
    }
}

// Inisialisasi Kalender saat Halaman Siap
document.addEventListener('DOMContentLoaded', () => {
    // Tangani parameter URL pencarian (?q=... atau ?search=...)
    const urlParams = new URLSearchParams(window.location.search);
    const qParam = urlParams.get('q') || urlParams.get('search');
    if (qParam) {
        const sInput = document.getElementById('cal-search-input');
        if (sInput) {
            sInput.value = qParam;
            const clearBtn = document.getElementById('cal-search-clear');
            if (clearBtn) clearBtn.style.display = 'inline-flex';
        }
    }
    renderCalendar();
});

// Listener klik di luar search box untuk menutup live dropdown
document.addEventListener('click', (e) => {
    const searchBox = document.querySelector('.cal-search-box');
    const dropdown = document.getElementById('cal-search-dropdown');
    if (searchBox && dropdown && !searchBox.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

<?= $this->endSection() ?>

