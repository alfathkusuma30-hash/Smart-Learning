<?php

namespace App\Models;

use CodeIgniter\Model;

class CertificateModel extends Model
{
    protected $table            = 'certificates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'user_username', 'user_name', 'course_title', 'reg_no', 'score', 
        'issued_at', 'valid_until', 'issued_by', 'status', 'attachment', 'template_file'
    ];
    protected $useTimestamps    = false;

    /**
     * Ambil seluruh sertifikat milik user
     */
    public function getByUser(string $username): array
    {
        return $this->where('user_username', $username)
                    ->orderBy('issued_at', 'DESC')
                    ->findAll();
    }

    /**
     * Klaim dan terbitkan sertifikat resmi secara otomatis
     */
    public function issueCertificate(string $username, string $userName, string $courseTitle, int $score = 100, ?string $templateFile = null): array
    {
        // Cek jika sertifikat untuk judul ini sudah ada
        $existing = $this->where('user_username', $username)
                         ->where('LOWER(course_title)', strtolower($courseTitle))
                         ->first();

        if ($existing) {
            return [
                'success'      => true,
                'message'      => 'Sertifikat resmi sudah tersedia di portofolio Anda.',
                'certificate'  => $existing
            ];
        }

        $certId = 'cert_' . time() . '_' . rand(100, 999);
        $regNo  = 'ANTAM/K3U/' . date('Y') . '/' . rand(1000, 9999);
        $validUntil = date('Y-m-d', strtotime('+3 years'));

        $data = [
            'id'            => $certId,
            'user_username' => $username,
            'user_name'     => $userName,
            'course_title'  => $courseTitle,
            'reg_no'        => $regNo,
            'score'         => $score,
            'issued_at'     => date('Y-m-d H:i:s'),
            'valid_until'   => $validUntil,
            'issued_by'     => 'PT ANTAM (PERSERO) TBK — Site Pomalaa',
            'status'        => 'active',
            'template_file' => $templateFile,
            'attachment'    => $templateFile
        ];

        $this->insert($data);

        return [
            'success'     => true,
            'message'     => 'Selamat! Sertifikat resmi berhasil diterbitkan.',
            'certificate' => $data
        ];
    }
}

