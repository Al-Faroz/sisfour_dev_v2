<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MataPelajaranModel
 *
 * Hard delete (tanpa deleted_at) — 04_MASTER_DATA §3.4.
 * FK dari jadwal_guru bersifat RESTRICT, jadi hapus akan gagal jika mapel
 * masih dipakai; tangkap DatabaseException di Controller/Service.
 */
class MataPelajaranModel extends Model
{
    protected $table            = 'mata_pelajaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['nama_mapel', 'kode_mapel'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'nama_mapel' => 'required|max_length[100]',
        'kode_mapel' => 'required|max_length[10]|is_unique[mata_pelajaran.kode_mapel,id,{id}]',
    ];

    protected $validationMessages = [
        'kode_mapel' => ['is_unique' => 'Kode mapel sudah dipakai.'],
    ];

    protected $skipValidation = false;
}
