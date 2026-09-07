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
 * Validasi overlap guru/kelas dan atomic transaction berada pada Service.
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

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

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

    public function getAktifByGuruDiriSendiri(
        int $idGuru,
        int $idTahun
    ): array {
        return $this
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('status_jadwal', 'Aktif')
            ->orderBy(
                "FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')",
                '',
                false
            )
            ->orderBy('jam_mulai', 'ASC')
            ->findAll();
    }

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
}
