<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class PtspLayananModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, int $limit, int $offset): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('l.created_at', 'DESC')
            ->orderBy('l.id', 'DESC')
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
            ->orderBy('l.created_at', 'ASC')
            ->orderBy('l.id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        return $this->rawBuilder()
            ->where('l.id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->table('ptsp_layanan')->insert($data);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db->table('ptsp_layanan')
            ->where('id', $id)
            ->update($data);
    }

    public function hardDelete(int $id): bool
    {
        return (bool) $this->db->table('ptsp_layanan')
            ->where('id', $id)
            ->delete();
    }

    private function rawBuilder()
    {
        return $this->db
            ->table('ptsp_layanan l')
            ->select([
                'l.*',
                'ta.nama_tahun',
                'ta.semester',
                'COALESCE(p.nama, actor.username) AS petugas_nama',
            ], false)
            ->join('tahun_ajaran ta', 'ta.id = l.id_tahun')
            ->join('users actor', 'actor.id = l.updated_by', 'left')
            ->join('pegawai p', 'p.id = actor.id_pegawai', 'left');
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->rawBuilder();

        if (! empty($filter['id_tahun'])) {
            $builder->where('l.id_tahun', (int) $filter['id_tahun']);
        }
        if (! empty($filter['status'])) {
            $builder->where('l.status', (string) $filter['status']);
        }
        if (! empty($filter['kategori'])) {
            $builder->where('l.kategori_pemohon', (string) $filter['kategori']);
        }
        if (! empty($filter['jenis_layanan'])) {
            $builder->where('l.jenis_layanan', (string) $filter['jenis_layanan']);
        }
        if (! empty($filter['search'])) {
            $q = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('l.nama_lengkap', $q)
                ->orLike('l.nomor_whatsapp', $q)
                ->orLike('l.tujuan_keterangan', $q)
                ->groupEnd();
        }

        return $builder;
    }
}
