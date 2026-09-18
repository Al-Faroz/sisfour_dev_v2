<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class PtspPollingModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, int $limit, int $offset): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('p.created_at', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter): int
    {
        return $this->baseBuilder($filter)->countAllResults();
    }

    public function getForExport(array $filter, int $limit): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('p.created_at', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        return $this->rawBuilder()
            ->where('p.id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('ptsp_polling')->insert($data);
        return (int) $this->db->insertID();
    }

    public function hardDelete(int $id): bool
    {
        return (bool) $this->db->table('ptsp_polling')
            ->where('id', $id)
            ->delete();
    }

    private function rawBuilder()
    {
        return $this->db
            ->table('ptsp_polling p')
            ->select('p.*, ta.nama_tahun, ta.semester')
            ->join('tahun_ajaran ta', 'ta.id = p.id_tahun');
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->rawBuilder();

        if (! empty($filter['id_tahun'])) {
            $builder->where('p.id_tahun', (int) $filter['id_tahun']);
        }
        if (! empty($filter['kategori'])) {
            $builder->where('p.kategori_responden', (string) $filter['kategori']);
        }
        if (! empty($filter['tingkat_kepuasan'])) {
            $builder->where('p.tingkat_kepuasan', (string) $filter['tingkat_kepuasan']);
        }
        if (! empty($filter['search'])) {
            $q = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('p.nama_lengkap', $q)
                ->orLike('p.nomor_whatsapp', $q)
                ->orLike('p.masukan_saran', $q)
                ->groupEnd();
        }

        return $builder;
    }
}
