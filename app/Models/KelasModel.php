<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * KelasModel
 *
 * Master data kelas.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §3.3
 *
 * nama_kelas selalu dihasilkan dari tingkat + rombel oleh KelasService.
 */
class KelasModel extends Model
{
    protected $table            = 'kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'tingkat',
        'rombel',
        'nama_kelas',
        'id_tahun',
    ];

    protected $validationRules = [
        'tingkat'    => 'required|in_list[7,8,9]',
        'rombel'     => 'required|max_length[10]',
        'nama_kelas' => 'required|max_length[20]',
        'id_tahun'   => 'required|integer',
    ];

    protected $validationMessages = [
        'tingkat' => [
            'required' => 'Tingkat wajib diisi.',
            'in_list'  => 'Tingkat hanya boleh 7, 8, atau 9.',
        ],
        'rombel' => [
            'required' => 'Rombel wajib diisi.',
        ],
        'nama_kelas' => [
            'required' => 'Nama kelas wajib diisi.',
        ],
        'id_tahun' => [
            'required' => 'Tahun ajaran wajib dipilih.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function getByTahun(int $idTahun): array
    {
        return $this
            ->where('id_tahun', $idTahun)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->findAll();
    }

    public function findByNamaTahun(
        string $namaKelas,
        int $idTahun,
        bool $withDeleted = false
    ): ?array {
        $model = $withDeleted ? $this->withDeleted() : $this;

        return $model
            ->where('nama_kelas', trim($namaKelas))
            ->where('id_tahun', $idTahun)
            ->first();
    }

    /**
     * Database memakai UNIQUE fisik (id_tahun, nama_kelas), sehingga
     * data di recycle-bin juga tetap dihitung.
     */
    public function namaKelasDipakai(
        string $namaKelas,
        int $idTahun,
        ?int $exceptId = null
    ): bool {
        $builder = $this->db
            ->table('kelas')
            ->where('nama_kelas', trim($namaKelas))
            ->where('id_tahun', $idTahun);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }
}
