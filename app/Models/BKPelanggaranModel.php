<?php

namespace App\Models;

use CodeIgniter\Model;

class BKPelanggaranModel extends Model
{
    protected $table = 'ref_pelanggaran';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nama_pelanggaran',
        'kategori',
        'poin',
    ];
}
