<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MappingWaliKelasModel
 *
 * Aturan khusus A2 (04_MASTER_DATA §4, 02_DATABASE §1):
 * - deleted_at = NULL  -> baris AKTIF, dihitung UNIQUE(id_guru,id_tahun) & UNIQUE(id_kelas,id_tahun)
 * - deleted_at = TIMESTAMP -> nonaktif, TIDAK dihitung UNIQUE, tetap tersimpan sebagai histori
 *
 * PENTING: business logic assign/restore ada di MappingWaliService, BUKAN di
 * sini. Model hanya menyediakan query primitif.
 */
class MappingWaliKelasModel extends Model
{
    protected $table            = 'mapping_wali_kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // dikelola manual (butuh query WHERE deleted_at IS NULL eksplisit di beberapa tempat)
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = ['id_guru', 'id_kelas', 'id_tahun', 'deleted_at'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Cari baris aktif ATAU nonaktif milik guru di tahun tsb (untuk cek restore).
     */
    public function findByGuruTahun(int $idGuru, int $idTahun): ?array
    {
        return $this->where('id_guru', $idGuru)->where('id_tahun', $idTahun)->first();
    }

    public function isGuruWaliAktif(int $idGuru, int $idTahun): bool
    {
        return $this->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    public function isKelasSudahAdaWali(int $idKelas, int $idTahun): bool
    {
        return $this->where('id_kelas', $idKelas)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    public function getAktifByTahun(int $idTahun): array
    {
        return $this->where('id_tahun', $idTahun)->where('deleted_at', null)->findAll();
    }

    public function getIdKelasDiampu(int $idGuru, int $idTahun): ?int
    {
        $row = $this->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->first();

        return $row['id_kelas'] ?? null;
    }
}
