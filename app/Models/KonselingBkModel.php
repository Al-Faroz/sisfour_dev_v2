<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class KonselingBkModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, int $limit, int $offset): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('kb.tanggal', 'DESC')
            ->orderBy('kb.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter): int
    {
        return $this->baseBuilder($filter)->countAllResults();
    }

    public function getForExport(array $filter, int $maxRows): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('kb.tanggal', 'ASC')
            ->orderBy('k.nama_kelas', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->limit($maxRows)
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        $row = $this->db
            ->table('konseling_bk')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getDetailById(int $id): ?array
    {
        $builder = $this->db
            ->table('konseling_bk kb')
            ->select([
                'kb.*',
                's.nisn',
                's.nama AS nama_siswa',
                'k.nama_kelas',
                'ta.nama_tahun',
                'ta.semester',
                'g.nama AS nama_guru_bk',
                'u_creator.username AS username_pencatat',
            ])
            ->select(
                'COALESCE(g_creator.nama, p_creator.nama, u_creator.username) AS nama_pencatat',
                false
            )
            ->join('siswa s', 's.id = kb.id_siswa')
            ->join('kelas k', 'k.id = kb.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = kb.id_tahun')
            ->join('guru g', 'g.id = kb.id_guru_bk', 'left')
            ->join(
                'users u_creator',
                'u_creator.id = COALESCE(kb.created_by, kb.updated_by)',
                'left',
                false
            )
            ->join('guru g_creator', 'g_creator.id = u_creator.id_guru', 'left')
            ->join('pegawai p_creator', 'p_creator.id = u_creator.id_pegawai', 'left')
            ->where('kb.id', $id);

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('konseling_bk')->insert($data);

        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db
            ->table('konseling_bk')
            ->where('id', $id)
            ->update($data);
    }

    public function activeClasses(int $idTahun): array
    {
        if ($idTahun <= 0) {
            return [];
        }

        return $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('nama_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function studentsByClass(int $idTahun, int $idKelas): array
    {
        if ($idTahun <= 0 || $idKelas <= 0) {
            return [];
        }

        return $this->db
            ->table('anggota_kelas ak')
            ->select('s.id, s.nisn, s.nama')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $idTahun)
            ->where('ak.id_kelas', $idKelas)
            ->where('s.status_aktif', 'Aktif')
            ->where('s.deleted_at', null)
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->db
            ->table('konseling_bk kb')
            ->select([
                'kb.id',
                'kb.id_tahun',
                'kb.id_kelas',
                'kb.id_siswa',
                'kb.tanggal',
                'kb.pertemuan_ke',
                'kb.bentuk_layanan',
                'kb.cara_hadir',
                'kb.bidang',
                'kb.topik',
                'kb.uraian_masalah',
                'kb.hasil_kesepakatan',
                'kb.rencana_berikutnya',
                'kb.tanggal_berikutnya',
                'kb.status',
                'kb.id_guru_bk',
                'kb.created_by',
                'kb.created_at',
                'kb.updated_at',
                's.nisn',
                's.nama AS nama_siswa',
                'k.nama_kelas',
                'g.nama AS nama_guru_bk',
                'u_creator.username AS username_pencatat',
            ])
            ->select(
                'COALESCE(g_creator.nama, p_creator.nama, u_creator.username) AS nama_pencatat',
                false
            )
            ->join('siswa s', 's.id = kb.id_siswa')
            ->join('kelas k', 'k.id = kb.id_kelas')
            ->join('guru g', 'g.id = kb.id_guru_bk', 'left')
            ->join(
                'users u_creator',
                'u_creator.id = COALESCE(kb.created_by, kb.updated_by)',
                'left',
                false
            )
            ->join('guru g_creator', 'g_creator.id = u_creator.id_guru', 'left')
            ->join('pegawai p_creator', 'p_creator.id = u_creator.id_pegawai', 'left');

        if (! empty($filter['id_kelas'])) {
            $builder->where('kb.id_kelas', (int) $filter['id_kelas']);
        }

        if (! empty($filter['status'])) {
            $builder->where('kb.status', (string) $filter['status']);
        }

        if (! empty($filter['bidang'])) {
            $builder->where('kb.bidang', (string) $filter['bidang']);
        }

        if (! empty($filter['tanggal_mulai'])) {
            $builder->where('kb.tanggal >=', (string) $filter['tanggal_mulai']);
        }

        if (! empty($filter['tanggal_selesai'])) {
            $builder->where('kb.tanggal <=', (string) $filter['tanggal_selesai']);
        }

        if (! empty($filter['search'])) {
            $search = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('s.nama', $search)
                ->orLike('s.nisn', $search)
                ->orLike('kb.topik', $search)
                ->orLike('kb.bentuk_layanan', $search)
                ->groupEnd();
        }

        return $builder;
    }
}
