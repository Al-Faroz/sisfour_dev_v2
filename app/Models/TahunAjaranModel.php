<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * TahunAjaranModel
 *
 * Hanya satu tahun ajaran boleh status_aktif = 1 pada satu waktu (dijaga di
 * Service, bukan di sini — lihat KelasService/MasterTahunAjaran::aktifkan()).
 * Referensi: 02_DATABASE §1.5, 04_MASTER_DATA §3.3
 */
class TahunAjaranModel extends Model
{
    protected $table            = 'tahun_ajaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = ['nama_tahun', 'semester', 'status_aktif'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nama_tahun' => 'required|max_length[20]',
        'semester'   => 'required|in_list[Ganjil,Genap]',
    ];

    protected $skipValidation = false;

    public function getAktif(): ?array
    {
        return $this->where('status_aktif', 1)->first();
    }

    /**
     * Nonaktifkan semua baris lain, aktifkan $id. Dibungkus transaction oleh caller (Service).
     */
    public function aktifkanTunggal(int $id): void
    {
        $this->where('id !=', $id)->set(['status_aktif' => 0])->update();
        $this->update($id, ['status_aktif' => 1]);
    }
}
