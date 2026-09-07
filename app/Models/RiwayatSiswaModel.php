<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatSiswaModel extends Model
{
    protected $table = 'riwayat_siswa';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $allowedFields = [
        'id_siswa','id_tahun','id_kelas','status',
        'tanggal_mulai','tanggal_selesai','keterangan',
    ];

    protected $validationRules = [
        'id_siswa' => 'required|integer',
        'id_tahun' => 'required|integer',
        'id_kelas' => 'required|integer',
        'status' => 'required|in_list[Aktif,Pindah,Keluar,Lulus]',
        'tanggal_mulai' => 'required|valid_date[Y-m-d]',
        'tanggal_selesai' => 'permit_empty|valid_date[Y-m-d]',
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function getRiwayatSiswa(int $idSiswa): array
    {
        return $this->where('id_siswa', $idSiswa)
            ->orderBy('tanggal_mulai', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function getRiwayatAktif(int $idSiswa, ?int $idTahun = null): ?array
    {
        $builder = $this->where('id_siswa', $idSiswa)
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null);

        if ($idTahun !== null) {
            $builder->where('id_tahun', $idTahun);
        }

        return $builder->orderBy('id', 'DESC')->first();
    }

    public function tutupRiwayatAktif(int $idSiswa, int $idTahun, string $tanggalSelesai): bool
    {
        return (bool) $this
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('status', 'Aktif')
            ->where('tanggal_selesai', null)
            ->set(['tanggal_selesai' => $tanggalSelesai])
            ->update();
    }
}
