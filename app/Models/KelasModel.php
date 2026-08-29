<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * KelasModel
 *
 * nama_kelas di-generate dari tingkat + rombel (contoh: "7-A") — lihat
 * KelasService::generateNamaKelas(). Referensi: 02_DATABASE §1.4, 04_MASTER_DATA §3.3
 */
class KelasModel extends Model
{
    protected $table            = 'kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = ['tingkat', 'rombel', 'nama_kelas', 'id_tahun'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tingkat'  => 'required|in_list[7,8,9]',
        'rombel'   => 'required|max_length[10]',
        'id_tahun' => 'required|integer',
    ];

    protected $skipValidation = false;

    public function getByTahun(int $idTahun): array
    {
        return $this->where('id_tahun', $idTahun)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->findAll();
    }
}
