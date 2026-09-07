<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * GuruModel
 *
 * Master data Guru.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §3.1
 *
 * Catatan bisnis:
 * - Menggunakan soft delete.
 * - NIP unik pada tabel guru.
 * - NIP juga tidak boleh dipakai pada tabel pegawai.
 *   Validasi lintas tabel tetap dieksekusi oleh Service.
 */
class GuruModel extends Model
{
    protected $table            = 'guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'nip',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telepon',
        'email',
        'status_kepegawaian',
        'foto',
    ];

    protected $validationRules = [
        'nip'                => 'required|max_length[30]|is_unique[guru.nip,id,{id}]',
        'nama'               => 'required|max_length[150]',
        'jenis_kelamin'      => 'required|in_list[L,P]',
        'tempat_lahir'       => 'permit_empty|max_length[100]',
        'tanggal_lahir'      => 'permit_empty|valid_date[Y-m-d]',
        'no_telepon'         => 'permit_empty|max_length[20]',
        'email'              => 'permit_empty|valid_email|max_length[100]',
        'status_kepegawaian' => 'permit_empty|in_list[PNS,PPPK,NON ASN,Yayasan,Outsourcing]',
        'foto'               => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'nip' => [
            'required'  => 'NIP wajib diisi.',
            'is_unique' => 'NIP sudah terdaftar pada data Guru.',
        ],
        'nama' => [
            'required' => 'Nama guru wajib diisi.',
        ],
        'jenis_kelamin' => [
            'required' => 'Jenis kelamin wajib diisi.',
            'in_list'  => 'Jenis kelamin harus L atau P.',
        ],
        'tanggal_lahir' => [
            'valid_date' => 'Tanggal lahir harus menggunakan format YYYY-MM-DD.',
        ],
        'email' => [
            'valid_email' => 'Format email tidak valid.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Cari guru berdasarkan NIP.
     *
     * @param bool $withDeleted true bila recycle-bin juga harus diperiksa.
     */
    public function findByNip(string $nip, bool $withDeleted = false): ?array
    {
        $model = $withDeleted ? $this->withDeleted() : $this;

        return $model
            ->where('nip', trim($nip))
            ->first();
    }

    /**
     * Validasi silang NIP terhadap tabel pegawai.
     *
     * Data soft-deleted tetap dihitung agar NIP tidak dipakai ulang dan
     * proses restore data lama tetap aman.
     */
    public function nipDipakaiPegawai(string $nip): bool
    {
        return $this->db
            ->table('pegawai')
            ->where('nip', trim($nip))
            ->countAllResults() > 0;
    }
}
