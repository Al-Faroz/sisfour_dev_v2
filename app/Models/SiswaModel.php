<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SiswaModel
 *
 * NIK (16 digit, UNIQUE) = identifier utama. NISN = basis akun login.
 * Referensi: 02_DATABASE §1.3, 04_MASTER_DATA §3.2
 */
class SiswaModel extends Model
{
    protected $table            = 'siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'nik', 'nisn', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'alamat', 'no_telepon', 'kebutuhan_khusus', 'disabilitas', 'nomor_kip_pip',
        'nama_ayah_kandung', 'nama_ibu_kandung', 'nama_wali', 'foto',
        'status_aktif', 'tanggal_mutasi', 'keterangan_mutasi',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nik'           => 'required|exact_length[16]|numeric|is_unique[siswa.nik,id,{id}]',
        'nisn'          => 'required|max_length[20]|is_unique[siswa.nisn,id,{id}]',
        'nama'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'status_aktif'  => 'permit_empty|in_list[Aktif,Lulus,Pindah,Keluar]',
    ];

    protected $validationMessages = [
        'nik'  => [
            'exact_length' => 'NIK wajib 16 digit.',
            'is_unique'    => 'NIK sudah terdaftar.',
        ],
        'nisn' => ['is_unique' => 'NISN sudah terdaftar.'],
    ];

    protected $skipValidation = false;

    /**
     * Siswa aktif di kelas tertentu (via anggota_kelas) pada tahun ajaran tertentu.
     */
    public function getByKelasTahun(int $idKelas, int $idTahun): array
    {
        return $this->select('siswa.*')
            ->join('anggota_kelas', 'anggota_kelas.id_siswa = siswa.id')
            ->where('anggota_kelas.id_kelas', $idKelas)
            ->where('anggota_kelas.id_tahun', $idTahun)
            ->where('siswa.deleted_at', null)
            ->orderBy('siswa.nama', 'ASC')
            ->findAll();
    }

    public function countAktif(): int
    {
        return $this->where('status_aktif', 'Aktif')->countAllResults();
    }
}
