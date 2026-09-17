<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class KonselingBkFollowUpModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getByKonseling(int $idKonseling): array
    {
        return $this->baseBuilder()
            ->where('tl.id_konseling', $idKonseling)
            ->orderBy('tl.tanggal', 'ASC')
            ->orderBy('tl.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getLatestByKonseling(int $idKonseling): ?array
    {
        $row = $this->baseBuilder()
            ->where('tl.id_konseling', $idKonseling)
            ->orderBy('tl.tanggal', 'DESC')
            ->orderBy('tl.id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getById(int $id): ?array
    {
        $row = $this->db
            ->table('tindak_lanjut_konseling_bk')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('tindak_lanjut_konseling_bk')->insert($data);

        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db
            ->table('tindak_lanjut_konseling_bk')
            ->where('id', $id)
            ->update($data);
    }

    public function getForKonselingIds(array $ids, int $limit = 50000): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        return $this->baseBuilder()
            ->select([
                'kb.tanggal AS tanggal_konseling',
                's.nisn',
                's.nama AS nama_siswa',
                'k.nama_kelas',
                'ta.nama_tahun',
                'ta.semester',
            ])
            ->join('konseling_bk kb', 'kb.id = tl.id_konseling')
            ->join('siswa s', 's.id = kb.id_siswa')
            ->join('kelas k', 'k.id = kb.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = kb.id_tahun')
            ->whereIn('tl.id_konseling', $ids)
            ->orderBy('kb.tanggal', 'ASC')
            ->orderBy('tl.tanggal', 'ASC')
            ->orderBy('tl.id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    private function baseBuilder()
    {
        return $this->db
            ->table('tindak_lanjut_konseling_bk tl')
            ->select([
                'tl.id',
                'tl.id_konseling',
                'tl.tanggal',
                'tl.perkembangan',
                'tl.hasil_kesepakatan',
                'tl.rencana_berikutnya',
                'tl.tanggal_berikutnya',
                'tl.status',
                'tl.created_by',
                'tl.created_at',
                'tl.updated_by',
                'tl.updated_at',
                'u.username AS username_pencatat',
            ])
            ->select('COALESCE(g.nama, p.nama, u.username) AS nama_pencatat', false)
            ->join('users u', 'u.id = tl.created_by', 'left')
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left');
    }
}
