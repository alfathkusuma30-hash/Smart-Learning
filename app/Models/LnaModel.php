<?php

namespace App\Models;

use CodeIgniter\Model;

class LnaModel extends Model
{
    protected $table            = 'lna_catalog';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'title', 'code', 'category', 'type', 'duration', 'level', 
        'gap_score', 'target_role', 'competency_area', 'description', 'prerequisites'
    ];
    protected $useTimestamps    = false;
}

