<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AnggotaKelasModel
 *
 * Relasi siswa <-> kelas per tahun ajaran. UNIQUE (id_siswa, id_tahun) —
 * satu siswa hanya boleh ada di satu kelas per tahun ajaran.
 * Referensi: 02_DATABASE §1.7
 */
class AnggotaKelasModel extends Model
{
    protected $table            = 'anggota_kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;

    protected $allowedFields = ['id_siswa', 'id_kelas', 'id_tahun'];

    protected $validationRules = [
        'id_siswa' => 'required|integer',
        'id_kelas' => 'required|integer',
        'id_tahun' => 'required|integer',
    ];

    protected $skipValidation = false;

    public function getKelasSiswa(int $idSiswa, int $idTahun): ?array
    {
        return $this->where('id_siswa', $idSiswa)->where('id_tahun', $idTahun)->first();
    }

    /**
     * Pindahkan siswa ke kelas/tahun baru (dipakai KelasService::naikKelas).
     * Karena UNIQUE(id_siswa, id_tahun), gunakan upsert: hapus baris lama di
     * tahun sama (jika ada) lalu insert baris baru.
     */
    public function pindahkan(int $idSiswa, int $idKelasBaru, int $idTahunBaru): void
    {
        $existing = $this->getKelasSiswa($idSiswa, $idTahunBaru);
        if ($existing) {
            $this->update($existing['id'], ['id_kelas' => $idKelasBaru]);

            return;
        }

        $this->insert(['id_siswa' => $idSiswa, 'id_kelas' => $idKelasBaru, 'id_tahun' => $idTahunBaru]);
    }
}
