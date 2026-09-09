<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatPenugasanModel extends Model
{
    protected $table = 'riwayat_penugasan';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'id_guru',
        'id_pegawai',
        'instansi_penugasan',
        'jabatan_tugas',
        'mata_pelajaran',
        'no_sk_penugasan',
        'tanggal_mulai',
        'tanggal_selesai',
        'file_sk_penugasan',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
