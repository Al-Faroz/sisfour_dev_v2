<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MappingWaliKelasModel
 *
 * Mapping wali kelas per tahun ajaran.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §4 (A2)
 *
 * Aturan:
 * - 1 guru maksimal 1 kelas aktif per tahun.
 * - 1 kelas maksimal 1 wali aktif per tahun.
 * - Wali Kelas bukan role.
 * - Mapping lama tetap disimpan sebagai histori melalui soft delete.
 * - Reassign guru yang pernah menjadi wali pada tahun yang sama harus
 *   me-restore row lama, bukan INSERT row baru.
 */
class MappingWaliKelasModel extends Model
{
    protected $table            = 'mapping_wali_kelas';
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
        'id_guru',
        'id_kelas',
        'id_tahun',
        'deleted_at',
    ];

    protected $validationRules = [
        'id_guru'  => 'required|integer',
        'id_kelas' => 'required|integer',
        'id_tahun' => 'required|integer',
    ];

    protected $validationMessages = [
        'id_guru' => [
            'required' => 'Guru wajib dipilih.',
        ],
        'id_kelas' => [
            'required' => 'Kelas wajib dipilih.',
        ],
        'id_tahun' => [
            'required' => 'Tahun ajaran wajib dipilih.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Cari mapping guru pada tahun tertentu, termasuk histori soft-deleted.
     *
     * Digunakan oleh Service untuk menentukan INSERT baru atau RESTORE.
     */
    public function findByGuruTahun(int $idGuru, int $idTahun): ?array
    {
        return $this
            ->withDeleted()
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Cari wali aktif pada kelas/tahun tertentu.
     */
    public function findAktifByKelasTahun(int $idKelas, int $idTahun): ?array
    {
        return $this
            ->where('id_kelas', $idKelas)
            ->where('id_tahun', $idTahun)
            ->first();
    }

    /**
     * Apakah guru sedang menjadi wali aktif pada tahun tersebut.
     */
    public function isGuruWaliAktif(int $idGuru, int $idTahun): bool
    {
        return $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->countAllResults() > 0;
    }

    /**
     * Apakah kelas sudah mempunyai wali aktif pada tahun tersebut.
     */
    public function isKelasSudahAdaWali(int $idKelas, int $idTahun): bool
    {
        return $this
            ->where('id_kelas', $idKelas)
            ->where('id_tahun', $idTahun)
            ->countAllResults() > 0;
    }

    /**
     * Daftar mapping aktif pada tahun tertentu.
     */
    public function getAktifByTahun(int $idTahun): array
    {
        return $this
            ->where('id_tahun', $idTahun)
            ->orderBy('id_kelas', 'ASC')
            ->findAll();
    }

    /**
     * Ambil ID kelas yang sedang diampu seorang wali.
     */
    public function getIdKelasDiampu(int $idGuru, int $idTahun): ?int
    {
        $row = $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->first();

        return isset($row['id_kelas'])
            ? (int) $row['id_kelas']
            : null;
    }

    /**
     * Restore histori mapping dan pindahkan ke kelas baru.
     *
     * Caller/Service wajib memastikan kelas baru belum memiliki wali aktif
     * dan membungkus proses assign/reassign dalam transaction.
     */
    public function restoreMapping(int $id, int $idKelas): bool
    {
        return $this
            ->withDeleted()
            ->update($id, [
                'id_kelas'    => $idKelas,
                'deleted_at'  => null,
            ]);
    }
}
