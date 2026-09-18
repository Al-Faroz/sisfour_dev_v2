<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class UksKunjunganModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, ?array $allowedIds, int $limit, int $offset): array
    {
        $rows = $this->baseBuilder($filter, $allowedIds)
            ->orderBy('u.tanggal', 'DESC')
            ->orderBy('u.jam_masuk', 'DESC')
            ->orderBy('u.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->attachActions($rows);
    }

    public function countFiltered(array $filter, ?array $allowedIds): int
    {
        return $this->baseBuilder($filter, $allowedIds)->countAllResults();
    }

    public function getForExport(array $filter, ?array $allowedIds, int $limit): array
    {
        $rows = $this->baseBuilder($filter, $allowedIds)
            ->orderBy('u.tanggal', 'ASC')
            ->orderBy('u.jam_masuk', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return $this->attachActions($rows);
    }

    public function getById(int $id, bool $withDeleted = false): ?array
    {
        $builder = $this->baseBuilder([], null)->where('u.id', $id);

        if ($withDeleted) {
            $builder = $this->rawBaseBuilder()->where('u.id', $id);
        }

        $row = $builder->get()->getRowArray();
        if (! $row) {
            return null;
        }

        return $this->attachActions([$row])[0] ?? null;
    }

    public function insert(array $data): int
    {
        $this->db->table('uks_kunjungan')->insert($data);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->db->table('uks_kunjungan')->where('id', $id)->update($data);
    }

    public function softDelete(int $id, int $userId): bool
    {
        return (bool) $this->db
            ->table('uks_kunjungan')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function replaceActions(int $idKunjungan, array $actionIds): void
    {
        $this->db->table('uks_kunjungan_tindakan')
            ->where('id_kunjungan', $idKunjungan)
            ->delete();

        foreach (array_values(array_unique(array_map('intval', $actionIds))) as $idTindakan) {
            if ($idTindakan <= 0) {
                continue;
            }
            $this->db->table('uks_kunjungan_tindakan')->insert([
                'id_kunjungan' => $idKunjungan,
                'id_tindakan' => $idTindakan,
            ]);
        }
    }

    private function rawBaseBuilder()
    {
        return $this->db
            ->table('uks_kunjungan u')
            ->select([
                'u.*',
                's.nisn',
                's.nama AS nama_siswa',
                'k.nama_kelas',
                'ta.nama_tahun',
                'ta.semester',
                'rk.nama AS keluhan',
                'rh.nama AS hasil',
                'COALESCE(p.nama, actor.username) AS petugas_nama',
            ], false)
            ->join('siswa s', 's.id = u.id_siswa')
            ->join('kelas k', 'k.id = u.id_kelas')
            ->join('tahun_ajaran ta', 'ta.id = u.id_tahun')
            ->join('uks_ref_keluhan rk', 'rk.id = u.id_keluhan')
            ->join('uks_ref_hasil rh', 'rh.id = u.id_hasil')
            ->join('users actor', 'actor.id = u.id_petugas_user', 'left')
            ->join('pegawai p', 'p.id = actor.id_pegawai', 'left');
    }

    private function baseBuilder(array $filter, ?array $allowedIds)
    {
        $builder = $this->rawBaseBuilder()->where('u.deleted_at', null);

        if (is_array($allowedIds)) {
            if ($allowedIds === []) {
                $builder->where('1 = 0', null, false);
            } else {
                $builder->whereIn('u.id_siswa', $allowedIds);
            }
        }

        if (! empty($filter['id_tahun'])) {
            $builder->where('u.id_tahun', (int) $filter['id_tahun']);
        }
        if (! empty($filter['id_kelas'])) {
            $builder->where('u.id_kelas', (int) $filter['id_kelas']);
        }
        if (! empty($filter['id_keluhan'])) {
            $builder->where('u.id_keluhan', (int) $filter['id_keluhan']);
        }
        if (! empty($filter['id_hasil'])) {
            $builder->where('u.id_hasil', (int) $filter['id_hasil']);
        }
        if (! empty($filter['tanggal_mulai'])) {
            $builder->where('u.tanggal >=', (string) $filter['tanggal_mulai']);
        }
        if (! empty($filter['tanggal_selesai'])) {
            $builder->where('u.tanggal <=', (string) $filter['tanggal_selesai']);
        }
        if (! empty($filter['search'])) {
            $q = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('s.nama', $q)
                ->orLike('s.nisn', $q)
                ->orLike('k.nama_kelas', $q)
                ->orLike('rk.nama', $q)
                ->orLike('rh.nama', $q)
                ->groupEnd();
        }

        return $builder;
    }

    private function attachActions(array $rows): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        )));

        if ($ids === []) {
            return $rows;
        }

        $actionRows = $this->db
            ->table('uks_kunjungan_tindakan kt')
            ->select('kt.id_kunjungan, rt.id, rt.nama')
            ->join('uks_ref_tindakan rt', 'rt.id = kt.id_tindakan')
            ->whereIn('kt.id_kunjungan', $ids)
            ->orderBy('rt.urutan', 'ASC')
            ->orderBy('rt.nama', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($actionRows as $action) {
            $map[(int) $action['id_kunjungan']][] = [
                'id' => (int) $action['id'],
                'nama' => (string) $action['nama'],
            ];
        }

        foreach ($rows as &$row) {
            $row['tindakan'] = $map[(int) $row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }
}
