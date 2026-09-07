<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MappingWaliKelasModel
 *
 * Mapping wali kelas per tahun ajaran.
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

    public function findByGuruTahun(
        int $idGuru,
        int $idTahun
    ): ?array {
        return $this
            ->withDeleted()
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function findAktifByGuruTahun(
        int $idGuru,
        int $idTahun
    ): ?array {
        return $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->first();
    }

    public function findAktifByKelasTahun(
        int $idKelas,
        int $idTahun
    ): ?array {
        return $this
            ->where('id_kelas', $idKelas)
            ->where('id_tahun', $idTahun)
            ->first();
    }

    public function isGuruWaliAktif(
        int $idGuru,
        int $idTahun
    ): bool {
        return $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->countAllResults() > 0;
    }

    public function isKelasSudahAdaWali(
        int $idKelas,
        int $idTahun
    ): bool {
        return $this
            ->where('id_kelas', $idKelas)
            ->where('id_tahun', $idTahun)
            ->countAllResults() > 0;
    }

    public function getAktifByTahun(int $idTahun): array
    {
        return $this
            ->where('id_tahun', $idTahun)
            ->orderBy('id_kelas', 'ASC')
            ->findAll();
    }

    public function getIdKelasDiampu(
        int $idGuru,
        int $idTahun
    ): ?int {
        $row = $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->first();

        return isset($row['id_kelas'])
            ? (int) $row['id_kelas']
            : null;
    }

    /**
     * Restore histori mapping dan arahkan ke kelas yang dipilih.
     *
     * Caller wajib memastikan constraint guru/kelas aktif tidak bentrok
     * dan membungkus operasi dalam transaction.
     */
    public function restoreMapping(
        int $id,
        int $idKelas
    ): bool {
        return (bool) $this->db
            ->table($this->table)
            ->where('id', $id)
            ->update([
                'id_kelas'   => $idKelas,
                'deleted_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
