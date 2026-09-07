<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * JadwalGuruModel
 *
 * Jadwal mengajar Guru.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §5
 *
 * Jadwal hanya diinput melalui import Excel.
 * Validasi overlap guru/kelas dan atomic transaction berada pada
 * JadwalGuruService.
 */
class JadwalGuruModel extends Model
{
    protected $table            = 'jadwal_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'id_guru',
        'id_kelas',
        'id_mapel',
        'id_tahun',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'sesi',
        'status_jadwal',
    ];

    protected $validationRules = [
        'id_guru'       => 'required|integer',
        'id_kelas'      => 'required|integer',
        'id_mapel'      => 'required|integer',
        'id_tahun'      => 'required|integer',
        'hari'          => 'required|in_list[Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu]',
        'jam_mulai'     => 'required',
        'jam_selesai'   => 'required',
        'sesi'          => 'required|in_list[Sesi Awal,Sesi Akhir,Non Sesi]',
        'status_jadwal' => 'permit_empty|in_list[Aktif,Nonaktif]',
    ];

    protected $validationMessages = [
        'hari' => [
            'in_list' => 'Hari jadwal tidak valid.',
        ],
        'sesi' => [
            'in_list' => 'Sesi jadwal tidak valid.',
        ],
        'status_jadwal' => [
            'in_list' => 'Status jadwal tidak valid.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Jadwal aktif guru pada hari tertentu.
     */
    public function getAktifByGuruHari(
        int $idGuru,
        string $hari,
        int $idTahun
    ): array {
        return $this
            ->where('id_guru', $idGuru)
            ->where('hari', $hari)
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('jam_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Seluruh jadwal aktif milik guru pada tahun tertentu.
     */
    public function getAktifByGuruDiriSendiri(int $idGuru, int $idTahun): array
    {
        return $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('hari', 'ASC')
            ->orderBy('jam_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Jadwal aktif pada kelas dan hari tertentu.
     *
     * Digunakan Service saat validasi overlap kelas.
     */
    public function getAktifByKelasHari(
        int $idKelas,
        string $hari,
        int $idTahun
    ): array {
        return $this
            ->where('id_kelas', $idKelas)
            ->where('hari', $hari)
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('jam_mulai', 'ASC')
            ->findAll();
    }

    /**
     * Nonaktifkan semua jadwal aktif pada tahun ajaran tertentu.
     *
     * Dipanggil sebagai bagian transaction import jadwal baru.
     */
    public function nonaktifkanByTahun(int $idTahun): bool
    {
        return (bool) $this
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->set(['status_jadwal' => 'Nonaktif'])
            ->update();
    }
}
