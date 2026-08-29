<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * GuruModel
 *
 * Referensi: 02_DATABASE §1.1, 04_MASTER_DATA §3.1
 */
class GuruModel extends Model
{
    protected $table            = 'guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'nip', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'alamat', 'no_telepon', 'email', 'status_kepegawaian', 'foto',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nip'           => 'required|max_length[30]|is_unique[guru.nip,id,{id}]',
        'nama'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'email'         => 'permit_empty|valid_email',
        'status_kepegawaian' => 'permit_empty|in_list[PNS,PPPK,NON ASN,Yayasan,Outsourcing]',
    ];

    protected $validationMessages = [
        'nip' => ['is_unique' => 'NIP sudah terdaftar di data Guru.'],
    ];

    protected $skipValidation = false;

    /**
     * Validasi tambahan (04_MASTER_DATA §3.1): NIP tidak boleh sama
     * antara tabel guru dan pegawai. Dipanggil dari Service sebelum insert/update.
     */
    public function nipDipakaiPegawai(string $nip, ?int $exceptPegawaiId = null): bool
    {
        $builder = $this->db->table('pegawai')->where('nip', $nip)->where('deleted_at', null);
        if ($exceptPegawaiId) {
            $builder->where('id !=', $exceptPegawaiId);
        }

        return $builder->countAllResults() > 0;
    }
}
