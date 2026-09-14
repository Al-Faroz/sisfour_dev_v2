<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Master Guru - Phase 2 Personalia.
 *
 * NIK adalah identitas wajib untuk record baru/perubahan administratif.
 * NIP adalah identitas kepegawaian resmi dan boleh kosong.
 * Record legacy yang belum mempunyai NIK tetap dapat dibaca sampai dilengkapi.
 *
 * Uniqueness NIK/NIP dijaga GuruService dengan awareness terhadap record yang
 * sedang diedit, lalu tetap diproteksi UNIQUE KEY database. Model fokus pada
 * validasi bentuk agar update tidak salah mendeteksi record sendiri sebagai
 * duplikat.
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
        'nik',
        'nip',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'no_telepon',
        'email',
        'status_kepegawaian',
        'nuptk',
        'foto',
    ];

    protected $validationRules = [
        'nik'                 => 'required|exact_length[16]|numeric',
        'nip'                 => 'permit_empty|exact_length[18]|numeric',
        'nama'                => 'required|max_length[150]',
        'jenis_kelamin'       => 'required|in_list[L,P]',
        'tempat_lahir'        => 'permit_empty|max_length[100]',
        'tanggal_lahir'       => 'permit_empty|valid_date[Y-m-d]',
        'agama'               => 'permit_empty|max_length[30]',
        'no_telepon'          => 'permit_empty|max_length[20]',
        'email'               => 'permit_empty|valid_email|max_length[100]',
        'status_kepegawaian'  => 'required|in_list[PNS,PPPK,GTT,PTT,GTY,PTY,Honorer,Outsourcing]',
        'nuptk'               => 'permit_empty|exact_length[16]|numeric',
        'foto'                => 'permit_empty|max_length[255]',
    ];

    protected $validationMessages = [
        'nik' => [
            'required'     => 'NIK wajib diisi.',
            'exact_length' => 'NIK harus 16 digit.',
            'numeric'      => 'NIK hanya boleh berisi angka.',
        ],
        'nip' => [
            'exact_length' => 'NIP harus 18 digit.',
            'numeric'      => 'NIP hanya boleh berisi angka.',
        ],
        'nama' => [
            'required' => 'Nama Guru wajib diisi.',
        ],
        'jenis_kelamin' => [
            'required' => 'Jenis kelamin wajib diisi.',
            'in_list'  => 'Jenis kelamin harus L atau P.',
        ],
        'status_kepegawaian' => [
            'required' => 'Status kepegawaian wajib dipilih.',
            'in_list'  => 'Status kepegawaian tidak valid.',
        ],
        'tanggal_lahir' => [
            'valid_date' => 'Tanggal lahir harus menggunakan format YYYY-MM-DD.',
        ],
        'email' => [
            'valid_email' => 'Format email tidak valid.',
        ],
        'nuptk' => [
            'exact_length' => 'NUPTK harus 16 digit.',
            'numeric'      => 'NUPTK hanya boleh berisi angka.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function findByNik(string $nik, bool $withDeleted = false): ?array
    {
        $model = $withDeleted ? $this->withDeleted() : $this;

        return $model->where('nik', trim($nik))->first();
    }

    public function findByNip(string $nip, bool $withDeleted = false): ?array
    {
        $model = $withDeleted ? $this->withDeleted() : $this;

        return $model->where('nip', trim($nip))->first();
    }

    public function nikDipakaiPegawai(string $nik): bool
    {
        $nik = trim($nik);

        return $nik !== ''
            && $this->db->table('pegawai')->where('nik', $nik)->countAllResults() > 0;
    }

    public function nipDipakaiPegawai(string $nip): bool
    {
        $nip = trim($nip);

        return $nip !== ''
            && $this->db->table('pegawai')->where('nip', $nip)->countAllResults() > 0;
    }
}
