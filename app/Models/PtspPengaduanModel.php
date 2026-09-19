<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class PtspPengaduanModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, int $limit, int $offset): array
    {
        $rows = $this->baseBuilder($filter)
            ->orderBy('p.created_at', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->attachClassifications($rows);
    }

    public function countFiltered(array $filter): int
    {
        return $this->baseBuilder($filter)->countAllResults();
    }

    public function getForExport(array $filter, int $limit): array
    {
        $rows = $this->baseBuilder($filter)
            ->orderBy('p.created_at', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return $this->attachClassifications($rows);
    }

    public function getById(int $id): ?array
    {
        $row = $this->rawBuilder()
            ->where('p.id', $id)
            ->get()
            ->getRowArray();

        return $row ? ($this->attachClassifications([$row])[0] ?? null) : null;
    }

    public function insert(array $data): int
    {
        $this->db->table('ptsp_pengaduan')->insert($data);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db->table('ptsp_pengaduan')
            ->where('id', $id)
            ->update($data);
    }

    public function replaceClassifications(int $idPengaduan, array $values): void
    {
        $this->db->table('ptsp_pengaduan_klasifikasi')
            ->where('id_pengaduan', $idPengaduan)
            ->delete();

        foreach (array_values(array_unique($values)) as $value) {
            $this->db->table('ptsp_pengaduan_klasifikasi')->insert([
                'id_pengaduan' => $idPengaduan,
                'klasifikasi' => $value,
            ]);
        }
    }

    public function hardDelete(int $id): bool
    {
        return (bool) $this->db->table('ptsp_pengaduan')
            ->where('id', $id)
            ->delete();
    }

    private function rawBuilder()
    {
        return $this->db
            ->table('ptsp_pengaduan p')
            ->select([
                'p.*',
                'ta.nama_tahun',
                'ta.semester',
                'COALESCE(pg.nama, actor.username) AS petugas_nama',
            ], false)
            ->join('tahun_ajaran ta', 'ta.id = p.id_tahun')
            ->join('users actor', 'actor.id = p.updated_by', 'left')
            ->join('pegawai pg', 'pg.id = actor.id_pegawai', 'left');
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->rawBuilder();

        if (! empty($filter['id_tahun'])) {
            $builder->where('p.id_tahun', (int) $filter['id_tahun']);
        }
        if (! empty($filter['status'])) {
            $builder->where('p.status', (string) $filter['status']);
        }
        if (! empty($filter['tanggal_mulai'])) {
            $builder->where('p.tanggal_kejadian >=', (string) $filter['tanggal_mulai']);
        }
        if (! empty($filter['tanggal_selesai'])) {
            $builder->where('p.tanggal_kejadian <=', (string) $filter['tanggal_selesai']);
        }
        if (! empty($filter['klasifikasi'])) {
            $builder->where(
                'EXISTS (SELECT 1 FROM ptsp_pengaduan_klasifikasi pk WHERE pk.id_pengaduan = p.id AND pk.klasifikasi = ' .
                $this->db->escape((string) $filter['klasifikasi']) . ')',
                null,
                false
            );
        }
        if (! empty($filter['search'])) {
            $q = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('p.judul_laporan', $q)
                ->orLike('p.isi_laporan', $q)
                ->groupEnd();
        }

        return $builder;
    }

    private function attachClassifications(array $rows): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        )));

        if ($ids === []) {
            return $rows;
        }

        $items = $this->db->table('ptsp_pengaduan_klasifikasi')
            ->select('id_pengaduan, klasifikasi')
            ->whereIn('id_pengaduan', $ids)
            ->orderBy('klasifikasi', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($items as $item) {
            $map[(int) $item['id_pengaduan']][] = (string) $item['klasifikasi'];
        }

        foreach ($rows as &$row) {
            $row['klasifikasi'] = $map[(int) $row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }
}
