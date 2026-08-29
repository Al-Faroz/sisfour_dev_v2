<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * JadwalGuruModel
 *
 * Hanya diisi via import Excel (JadwalGuruService::importJadwal), tidak ada
 * form input manual. Referensi: 02_DATABASE §1.9, 04_MASTER_DATA §5
 */
class JadwalGuruModel extends Model
{
    protected $table            = 'jadwal_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'id_guru', 'id_kelas', 'id_mapel', 'id_tahun',
        'hari', 'jam_mulai', 'jam_selesai', 'sesi', 'status_jadwal',
    ];

    protected $validationRules = [
        'id_guru'     => 'required|integer',
        'id_kelas'    => 'required|integer',
        'id_mapel'    => 'required|integer',
        'id_tahun'    => 'required|integer',
        'hari'        => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu]',
        'jam_mulai'   => 'required',
        'jam_selesai' => 'required',
        'sesi'        => 'required|in_list[Sesi Awal,Sesi Akhir,Non Sesi]',
    ];

    protected $skipValidation = false;

    public function getAktifByGuruHari(int $idGuru, string $hari, int $idTahun): array
    {
        return $this->where('id_guru', $idGuru)
            ->where('hari', $hari)
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('jam_mulai', 'ASC')
            ->findAll();
    }

    public function getAktifByGuruDiriSendiri(int $idGuru, int $idTahun): array
    {
        return $this->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('hari', 'ASC')
            ->orderBy('jam_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Nonaktifkan seluruh jadwal aktif pada tahun ajaran tsb (dipanggil sebelum
     * import jadwal baru — 04_MASTER_DATA §5.2).
     */
    public function nonaktifkanByTahun(int $idTahun): void
    {
        $this->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->set(['status_jadwal' => 'Nonaktif'])
            ->update();
    }
}
