<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
    protected $table = 'siswa';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'nik','nisn','nama','jenis_kelamin','tempat_lahir','tanggal_lahir',
        'alamat','no_telepon','kebutuhan_khusus','disabilitas','nomor_kip_pip',
        'nama_ayah_kandung','nama_ibu_kandung','nama_wali','foto','status_aktif',
        'tanggal_mutasi','keterangan_mutasi',
    ];

    protected $validationRules = [
        'nik' => 'required|exact_length[16]|numeric|is_unique[siswa.nik,id,{id}]',
        'nisn' => 'required|max_length[20]|is_unique[siswa.nisn,id,{id}]',
        'nama' => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'tempat_lahir' => 'permit_empty|max_length[100]',
        'tanggal_lahir' => 'permit_empty|valid_date[Y-m-d]',
        'no_telepon' => 'permit_empty|max_length[20]',
        'kebutuhan_khusus' => 'permit_empty|max_length[100]',
        'disabilitas' => 'permit_empty|max_length[100]',
        'nomor_kip_pip' => 'permit_empty|max_length[50]',
        'nama_ayah_kandung' => 'permit_empty|max_length[150]',
        'nama_ibu_kandung' => 'permit_empty|max_length[150]',
        'nama_wali' => 'permit_empty|max_length[150]',
        'foto' => 'permit_empty|max_length[255]',
        'status_aktif' => 'permit_empty|in_list[Aktif,Lulus,Pindah,Keluar]',
        'tanggal_mutasi' => 'permit_empty|valid_date[Y-m-d]',
    ];

    protected $validationMessages = [
        'nik' => [
            'required' => 'NIK wajib diisi.',
            'exact_length' => 'NIK wajib 16 digit.',
            'numeric' => 'NIK hanya boleh berisi angka.',
            'is_unique' => 'NIK sudah terdaftar.',
        ],
        'nisn' => [
            'required' => 'NISN wajib diisi.',
            'is_unique' => 'NISN sudah terdaftar.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function findByNik(string $nik, bool $withDeleted = false): ?array
    {
        $model = $withDeleted ? $this->withDeleted() : $this;
        return $model->where('nik', trim($nik))->first();
    }

    public function findByNisn(string $nisn, bool $withDeleted = false): ?array
    {
        $model = $withDeleted ? $this->withDeleted() : $this;
        return $model->where('nisn', trim($nisn))->first();
    }

    public function getByKelasTahun(int $idKelas, int $idTahun): array
    {
        return $this
            ->select('siswa.*')
            ->join('anggota_kelas', 'anggota_kelas.id_siswa = siswa.id')
            ->where('anggota_kelas.id_kelas', $idKelas)
            ->where('anggota_kelas.id_tahun', $idTahun)
            ->where('siswa.status_aktif', 'Aktif')
            ->orderBy('siswa.nama', 'ASC')
            ->findAll();
    }

    public function countAktif(): int
    {
        return $this->where('status_aktif', 'Aktif')->countAllResults();
    }
}
