<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AnggotaKelasModel
 *
 * Relasi siswa <-> kelas per tahun ajaran.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §3.3
 *
 * Database menegakkan UNIQUE (id_siswa, id_tahun), sehingga satu siswa
 * hanya dapat menjadi anggota satu kelas dalam satu tahun ajaran.
 */
class AnggotaKelasModel extends Model
{
    protected $table            = 'anggota_kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'id_siswa',
        'id_kelas',
        'id_tahun',
    ];

    protected $validationRules = [
        'id_siswa' => 'required|integer',
        'id_kelas' => 'required|integer',
        'id_tahun' => 'required|integer',
    ];

    protected $validationMessages = [
        'id_siswa' => [
            'required' => 'Siswa wajib dipilih.',
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
     * Ambil keanggotaan kelas seorang siswa pada tahun tertentu.
     */
    public function getKelasSiswa(int $idSiswa, int $idTahun): ?array
    {
        return $this
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->first();
    }

    /**
     * Ambil semua anggota satu kelas/tahun.
     */
    public function getByKelasTahun(int $idKelas, int $idTahun): array
    {
        return $this
            ->select('anggota_kelas.*, siswa.nama, siswa.nik, siswa.nisn, siswa.status_aktif')
            ->join('siswa', 'siswa.id = anggota_kelas.id_siswa')
            ->where('anggota_kelas.id_kelas', $idKelas)
            ->where('anggota_kelas.id_tahun', $idTahun)
            ->where('siswa.deleted_at', null)
            ->orderBy('siswa.nama', 'ASC')
            ->findAll();
    }

    /**
     * Tempatkan/pindahkan siswa pada kelas di tahun tujuan.
     *
     * Jika siswa sudah mempunyai row pada tahun yang sama, row tersebut
     * diperbarui agar tidak melanggar UNIQUE (id_siswa, id_tahun).
     */
    public function pindahkan(
        int $idSiswa,
        int $idKelasBaru,
        int $idTahunBaru
    ): bool {
        $existing = $this->getKelasSiswa($idSiswa, $idTahunBaru);

        if ($existing !== null) {
            return $this->update($existing['id'], [
                'id_kelas' => $idKelasBaru,
            ]);
        }

        return $this->insert([
            'id_siswa' => $idSiswa,
            'id_kelas' => $idKelasBaru,
            'id_tahun' => $idTahunBaru,
        ]) !== false;
    }
}
