<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TahunAjaranModel
 *
 * Master Tahun Ajaran/Semester.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §3.3
 *
 * Hanya satu baris boleh aktif pada satu waktu.
 * Operasi pengaktifan wajib dibungkus transaction oleh Service/Controller.
 */
class TahunAjaranModel extends Model
{
    protected $table            = 'tahun_ajaran';
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
        'nama_tahun',
        'semester',
        'status_aktif',
    ];

    protected $validationRules = [
        'nama_tahun'   => 'required|max_length[20]',
        'semester'     => 'required|in_list[Ganjil,Genap]',
        'status_aktif' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'nama_tahun' => [
            'required' => 'Tahun ajaran wajib diisi.',
        ],
        'semester' => [
            'required' => 'Semester wajib diisi.',
            'in_list'  => 'Semester harus Ganjil atau Genap.',
        ],
        'status_aktif' => [
            'in_list' => 'Status aktif hanya boleh bernilai 0 atau 1.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Ambil tahun ajaran/semester yang sedang aktif.
     */
    public function getAktif(): ?array
    {
        return $this
            ->where('status_aktif', 1)
            ->first();
    }

    /**
     * Cari kombinasi nama tahun + semester.
     */
    public function findByTahunSemester(
        string $namaTahun,
        string $semester,
        bool $withDeleted = false
    ): ?array {
        $model = $withDeleted ? $this->withDeleted() : $this;

        return $model
            ->where('nama_tahun', trim($namaTahun))
            ->where('semester', $semester)
            ->first();
    }

    /**
     * Cek composite unique nama_tahun + semester.
     *
     * Data recycle-bin ikut dihitung karena unique key database bersifat fisik.
     */
    public function tahunSemesterDipakai(
        string $namaTahun,
        string $semester,
        ?int $exceptId = null
    ): bool {
        $builder = $this->db
            ->table('tahun_ajaran')
            ->where('nama_tahun', trim($namaTahun))
            ->where('semester', $semester);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Nonaktifkan tahun lain dan aktifkan satu baris.
     *
     * Caller wajib membungkus method ini dalam database transaction.
     */
    public function aktifkanTunggal(int $id): bool
    {
        $this->where('status_aktif', 1)
            ->where('id !=', $id)
            ->set(['status_aktif' => 0])
            ->update();

        return $this->update($id, ['status_aktif' => 1]);
    }
}
