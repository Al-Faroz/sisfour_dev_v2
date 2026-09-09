<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatPendidikanModel extends Model
{
    protected $table = 'riwayat_pendidikan';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'id_guru',
        'id_pegawai',
        'tingkat_pendidikan',
        'nama_institusi',
        'program_studi',
        'tahun_lulus',
        'no_ijazah',
        'file_ijazah',
        'file_transkrip',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
