<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatPangkatModel extends Model
{
    protected $table = 'riwayat_pangkat';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'id_guru',
        'id_pegawai',
        'golongan_ruang',
        'nama_pangkat',
        'tmt_pangkat',
        'no_sk_pangkat',
        'file_sk_pangkat',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
