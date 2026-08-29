<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PegawaiModel
 *
 * Referensi: 02_DATABASE §1.2, 04_MASTER_DATA §3.1
 */
class PegawaiModel extends Model
{
    protected $table            = 'pegawai';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'nip', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'alamat', 'no_telepon', 'email', 'jabatan',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nip'           => 'required|max_length[30]|is_unique[pegawai.nip,id,{id}]',
        'nama'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'email'         => 'permit_empty|valid_email',
    ];

    protected $validationMessages = [
        'nip' => ['is_unique' => 'NIP sudah terdaftar di data Pegawai.'],
    ];

    protected $skipValidation = false;

    /**
     * Validasi silang (04_MASTER_DATA §3.1): NIP tidak boleh sama
     * dengan yang ada di tabel guru.
     */
    public function nipDipakaiGuru(string $nip, ?int $exceptGuruId = null): bool
    {
        $builder = $this->db->table('guru')->where('nip', $nip)->where('deleted_at', null);
        if ($exceptGuruId) {
            $builder->where('id !=', $exceptGuruId);
        }

        return $builder->countAllResults() > 0;
    }
}
