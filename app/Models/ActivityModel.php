<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table            = 'activities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'user_username', 'user_name', 'title', 'type', 'category', 'duration', 
        'level', 'deadline', 'status', 'progress', 'current_module', 'total_modules', 
        'completed_modules', 'notes', 'admin_notes', 'last_report', 'last_attachment', 
        'last_submitted_at', 'grade', 'admin_feedback', 'graded_at', 'created_at', 
        'approved_at', 'cert_template', 'attendance_file'
    ];
    protected $useTimestamps    = false;

    /**
     * Ambil daftar kegiatan untuk user tertentu
     */
    public function getByUser(string $username, ?string $status = null): array
    {
        $this->where('user_username', $username);
        if ($status !== null) {
            $this->where('status', $status);
        }
        return $this->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Ambil daftar kegiatan berdasarkan status
     */
    public function getByStatus(string $status): array
    {
        return $this->where('status', $status)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Hitung statistik ringkasan untuk dashboard
     */
    public function getStats(?string $username = null): array
    {
        $builder = $this->builder();
        if ($username !== null) {
            $builder->where('user_username', $username);
        }

        $all = $builder->get()->getResultArray();
        $totalActivities = count($all);
        $completed = 0;
        $inProgress = 0;
        $pending = 0;
        $totalHours = 0;

        foreach ($all as $act) {
            $prog = (int)($act['progress'] ?? 0);
            $st   = $act['status'] ?? '';

            if ($prog >= 100 || $st === 'selesai') {
                $completed++;
            } elseif ($st === 'menunggu_konfirmasi') {
                $pending++;
            } else {
                $inProgress++;
            }

            // Estimasi jam belajar dari string duration
            if (preg_match('/(\d+)\s*Jam/i', $act['duration'] ?? '', $m)) {
                $totalHours += (int)$m[1];
            } elseif (preg_match('/(\d+)\s*Hari/i', $act['duration'] ?? '', $m)) {
                $totalHours += (int)$m[1] * 8;
            } else {
                $totalHours += 8;
            }
        }

        $complianceRate = ($totalActivities > 0) ? round(($completed / $totalActivities) * 100) : 0;

        return [
            'total'          => $totalActivities,
            'completed'      => $completed,
            'in_progress'    => $inProgress,
            'pending'        => $pending,
            'total_hours'    => $totalHours,
            'compliance'     => $complianceRate
        ];
    }
}
