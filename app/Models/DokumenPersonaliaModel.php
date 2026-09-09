<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenPersonaliaModel extends Model
{
    protected $table = 'dokumen_personalia';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'id_guru',
        'id_pegawai',
        'jenis_dokumen',
        'nama_dokumen',
        'nomor_dokumen',
        'tanggal_dokumen',
        'file_path',
        'nama_file_asli',
        'mime_type',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
