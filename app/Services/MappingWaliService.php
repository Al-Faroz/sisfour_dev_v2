<?php

namespace App\Services;

use App\Models\MappingWaliKelasModel;
use Config\Database;

/**
 * MappingWaliService
 *
 * Aturan A2 (04_MASTER_DATA §4, 15_TESTING_POLISH checklist):
 * - Assign baru -> INSERT baris (deleted_at = NULL).
 * - Guru yang PERNAH jadi wali di tahun sama (baris ter-soft-delete) -> WAJIB
 *   RESTORE (UPDATE deleted_at = NULL + id_kelas baru), JANGAN INSERT baru.
 * - Hapus/nonaktifkan -> UPDATE deleted_at = NOW().
 *
 * Referensi: 02_DATABASE §1 (Aturan Khusus A2), 04_MASTER_DATA §4, §7.3
 */
class MappingWaliService
{
    protected MappingWaliKelasModel $model;
    protected $db;

    public function __construct()
    {
        $this->model = new MappingWaliKelasModel();
        $this->db    = Database::connect();
    }

    /**
     * Assign guru sebagai wali kelas. Menangani mekanisme restore otomatis.
     *
     * @return array{success: bool, message: string}
     */
    public function assign(int $idGuru, int $idKelas, int $idTahun): array
    {
        // Validasi: guru sudah wali aktif di tahun ini? (di kelas lain)
        if ($this->model->isGuruWaliAktif($idGuru, $idTahun)) {
            return ['success' => false, 'message' => 'Guru ini sudah menjadi wali kelas aktif di tahun ajaran ini.'];
        }

        // Validasi: kelas sudah punya wali aktif?
        if ($this->model->isKelasSudahAdaWali($idKelas, $idTahun)) {
            return ['success' => false, 'message' => 'Kelas ini sudah memiliki wali kelas aktif di tahun ajaran ini.'];
        }

        $this->db->transStart();

        $existing = $this->model->findByGuruTahun($idGuru, $idTahun);

        if ($existing) {
            // RESTORE — WAJIB update, JANGAN insert baru (A2)
            $this->model->update($existing['id'], [
                'id_kelas'   => $idKelas,
                'deleted_at' => null,
            ]);
        } else {
            $this->model->insert([
                'id_guru'  => $idGuru,
                'id_kelas' => $idKelas,
                'id_tahun' => $idTahun,
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Assign wali kelas gagal.'];
        }

        return ['success' => true, 'message' => 'Wali kelas berhasil di-assign.'];
    }

    /**
     * Nonaktifkan mapping (soft delete timestamp — bukan hard delete).
     */
    public function nonaktifkan(int $id): array
    {
        $row = $this->model->find($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Data mapping tidak ditemukan.'];
        }

        $this->model->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);

        return ['success' => true, 'message' => 'Wali kelas berhasil dinonaktifkan.'];
    }

    public function isWaliAktif(int $idGuru, int $idTahun): bool
    {
        return $this->model->isGuruWaliAktif($idGuru, $idTahun);
    }

    public function getKelasDiampu(int $idGuru, int $idTahun): ?int
    {
        return $this->model->getIdKelasDiampu($idGuru, $idTahun);
    }

    /**
     * Dropdown Guru: hanya guru yang BELUM jadi wali aktif di tahun ini.
     *
     * @return int[] id_guru yang sudah terpakai (untuk WHERE NOT IN di query dropdown)
     */
    public function getIdGuruSudahJadiWali(int $idTahun): array
    {
        $rows = $this->model->where('id_tahun', $idTahun)->where('deleted_at', null)->findColumn('id_guru');

        return $rows ?: [];
    }

    /**
     * Dropdown Kelas: hanya kelas yang BELUM punya wali aktif di tahun ini.
     *
     * @return int[] id_kelas yang sudah terpakai
     */
    public function getIdKelasSudahAdaWali(int $idTahun): array
    {
        $rows = $this->model->where('id_tahun', $idTahun)->where('deleted_at', null)->findColumn('id_kelas');

        return $rows ?: [];
    }
}
