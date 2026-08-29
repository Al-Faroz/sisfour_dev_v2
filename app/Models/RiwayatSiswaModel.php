<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RiwayatSiswaModel (A4)
 *
 * Histori status/kelas siswa (kenaikan kelas, mutasi, kelulusan).
 * Referensi: 02_DATABASE §2, 04_MASTER_DATA §3.2, §3.3
 */
class RiwayatSiswaModel extends Model
{
    protected $table            = 'riwayat_siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'id_siswa', 'id_tahun', 'id_kelas', 'status',
        'tanggal_mulai', 'tanggal_selesai', 'keterangan',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // tabel histori tidak punya updated_at

    protected $validationRules = [
        'id_siswa'      => 'required|integer',
        'id_tahun'      => 'required|integer',
        'id_kelas'      => 'required|integer',
        'status'        => 'required|in_list[Aktif,Pindah,Keluar,Lulus]',
        'tanggal_mulai' => 'required|valid_date',
    ];

    protected $skipValidation = false;

    public function getRiwayatSiswa(int $idSiswa): array
    {
        return $this->where('id_siswa', $idSiswa)->orderBy('tanggal_mulai', 'DESC')->findAll();
    }

    /**
     * Tutup riwayat "Aktif" yang sedang berjalan (isi tanggal_selesai) —
     * dipanggil sebelum mencatat status baru (mutasi/kelulusan/kenaikan kelas).
     */
    public function tutupRiwayatAktif(int $idSiswa, int $idTahun, string $tanggalSelesai): void
    {
        $this->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null)
            ->set(['tanggal_selesai' => $tanggalSelesai])
            ->update();
    }
}
