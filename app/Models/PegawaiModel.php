<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PegawaiModel
 *
 * Master data Pegawai/Tenaga Kependidikan.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/04_MASTER_DATA §3.1
 *
 * Catatan:
 * - Menggunakan soft delete.
 * - NIP unik pada tabel pegawai.
 * - NIP tidak boleh sama dengan NIP Guru; validasi lintas tabel
 *   dilakukan kembali oleh Service.
 */
class PegawaiModel extends Model
{
    protected $table            = 'pegawai';
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
        'jabatan',
    ];

    protected $validationRules = [
        'nip'           => 'required|max_length[30]|is_unique[pegawai.nip,id,{id}]',
        'nama'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'tempat_lahir'  => 'permit_empty|max_length[100]',
        'tanggal_lahir' => 'permit_empty|valid_date[Y-m-d]',
        'no_telepon'    => 'permit_empty|max_length[20]',
        'email'         => 'permit_empty|valid_email|max_length[100]',
        'jabatan'       => 'permit_empty|max_length[100]',
    ];

    protected $validationMessages = [
        'nip' => [
            'required'  => 'NIP wajib diisi.',
            'is_unique' => 'NIP sudah terdaftar pada data Pegawai.',
        ],
        'nama' => [
            'required' => 'Nama pegawai wajib diisi.',
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

    public function findByNip(string $nip, bool $withDeleted = false): ?array
    {
        $model = $withDeleted ? $this->withDeleted() : $this;

        return $model
            ->where('nip', trim($nip))
            ->first();
    }

    /**
     * Data soft-deleted ikut dihitung agar NIP tidak dipakai ulang.
     */
    public function nipDipakaiGuru(string $nip): bool
    {
        return $this->db
            ->table('guru')
            ->where('nip', trim($nip))
            ->countAllResults() > 0;
    }
}
