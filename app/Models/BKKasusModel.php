<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class BKKasusModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(
        array $filter,
        ?array $allowedStudentIds,
        int $limit,
        int $offset
    ): array {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->orderBy('ck.tanggal', 'DESC')
            ->orderBy('ck.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter, ?array $allowedStudentIds): int
    {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->countAllResults();
    }

    public function getForExport(
        array $filter,
        ?array $allowedStudentIds,
        int $maxRows
    ): array {
        return $this->baseBuilder($filter, $allowedStudentIds)
            ->orderBy('ck.tanggal', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->limit($maxRows)
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        $row = $this->db
            ->table('catatan_kasus')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getTop20(?array $allowedStudentIds): array
    {
        $builder = $this->db
            ->table('catatan_kasus ck')
            ->select("
                ck.id_siswa,
                s.nisn,
                s.nama,
                SUM(rp.poin) AS total_poin,
                COUNT(ck.id) AS total_kasus
            ", false)
            ->join('siswa s', 's.id = ck.id_siswa')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran');

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                return [];
            }

            $builder->whereIn('ck.id_siswa', $allowedStudentIds);
        }

        return $builder
            ->groupBy('ck.id_siswa, s.nisn, s.nama')
            ->orderBy('total_poin', 'DESC')
            ->orderBy('total_kasus', 'DESC')
            ->orderBy('s.nama', 'ASC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }

    public function insert(array $data): int
    {
        $this->db->table('catatan_kasus')->insert($data);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db
            ->table('catatan_kasus')
            ->where('id', $id)
            ->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->db
            ->table('catatan_kasus')
            ->where('id', $id)
            ->delete();
    }

    public function getPelanggaranOptions(): array
    {
        return $this->db
            ->table('ref_pelanggaran')
            ->select('id, nama_pelanggaran, kategori, poin')
            ->orderBy('kategori', 'ASC')
            ->orderBy('nama_pelanggaran', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function baseBuilder(array $filter, ?array $allowedStudentIds)
    {
        $builder = $this->db
            ->table('catatan_kasus ck')
            ->select([
                'ck.id',
                'ck.id_siswa',
                'ck.id_pelanggaran',
                'ck.tanggal',
                'ck.keterangan',
                'ck.created_at',
                'ck.updated_at',
                's.nisn',
                's.nama AS nama_siswa',
                'rp.nama_pelanggaran',
                'rp.kategori',
                'rp.poin',
            ])
            ->join('siswa s', 's.id = ck.id_siswa')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran');

        if (is_array($allowedStudentIds)) {
            if ($allowedStudentIds === []) {
                $builder->where('1 = 0', null, false);
            } else {
                $builder->whereIn('ck.id_siswa', $allowedStudentIds);
            }
        }

        if (!empty($filter['id_pelanggaran'])) {
            $builder->where('ck.id_pelanggaran', (int) $filter['id_pelanggaran']);
        }
        if (!empty($filter['kategori'])) {
            $builder->where('rp.kategori', (string) $filter['kategori']);
        }
        if (!empty($filter['tanggal_mulai'])) {
            $builder->where('ck.tanggal >=', (string) $filter['tanggal_mulai']);
        }
        if (!empty($filter['tanggal_selesai'])) {
            $builder->where('ck.tanggal <=', (string) $filter['tanggal_selesai']);
        }
        if (!empty($filter['search'])) {
            $search = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('s.nama', $search)
                ->orLike('s.nisn', $search)
                ->orLike('rp.nama_pelanggaran', $search)
                ->groupEnd();
        }

        return $builder;
    }
}
