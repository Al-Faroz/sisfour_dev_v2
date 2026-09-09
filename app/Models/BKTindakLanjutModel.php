<?php

namespace App\Models;

use CodeIgniter\Model;

class BKTindakLanjutModel extends Model
{
    protected $table = 'tindak_lanjut_kasus';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'id_kasus',
        'tanggal',
        'tindak_lanjut',
        'keterangan',
        'id_user_input',
    ];

    protected $validationRules = [
        'id_kasus' => 'required|integer',
        'tanggal' => 'required|valid_date[Y-m-d]',
        'tindak_lanjut' => 'required|max_length[100]',
        'keterangan' => 'permit_empty',
        'id_user_input' => 'permit_empty|integer',
    ];

    public function getByKasus(int $idKasus): array
    {
        return $this->db
            ->table('tindak_lanjut_kasus tl')
            ->select([
                'tl.id',
                'tl.id_kasus',
                'tl.tanggal',
                'tl.tindak_lanjut',
                'tl.keterangan',
                'tl.id_user_input',
                'tl.created_at',
                'tl.updated_at',
                'u.username AS username_input',
                'COALESCE(g.nama, p.nama, s.nama, u.username) AS nama_input',
            ])
            ->join('users u', 'u.id = tl.id_user_input', 'left')
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->join('siswa s', 's.id = u.id_siswa', 'left')
            ->where('tl.id_kasus', $idKasus)
            ->orderBy('tl.tanggal', 'ASC')
            ->orderBy('tl.id', 'ASC')
            ->get()
            ->getResultArray();
    }
}
