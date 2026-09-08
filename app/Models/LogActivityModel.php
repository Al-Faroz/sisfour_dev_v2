<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class LogActivityModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(
        array $filter,
        int $limit,
        int $offset
    ): array {
        return $this->baseBuilder($filter)
            ->orderBy('l.waktu', 'DESC')
            ->orderBy('l.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter): int
    {
        return $this->baseBuilder($filter)
            ->countAllResults();
    }

    public function getForExport(
        array $filter,
        int $limit = 10000
    ): array {
        return $this->baseBuilder($filter)
            ->orderBy('l.waktu', 'DESC')
            ->orderBy('l.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getModules(): array
    {
        $rows = $this->db
            ->table('log_activity')
            ->select('modul')
            ->distinct()
            ->where('modul !=', '')
            ->orderBy('modul', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(
            array_filter(
                array_map(
                    static fn (array $row): string =>
                        trim((string) ($row['modul'] ?? '')),
                    $rows
                )
            )
        );
    }

    public function getActions(): array
    {
        $rows = $this->db
            ->table('log_activity')
            ->select('aksi')
            ->distinct()
            ->where('aksi !=', '')
            ->orderBy('aksi', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(
            array_filter(
                array_map(
                    static fn (array $row): string =>
                        trim((string) ($row['aksi'] ?? '')),
                    $rows
                )
            )
        );
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->db
            ->table('log_activity l')
            ->select([
                'l.id',
                'l.id_user',
                'u.username',
                'l.aksi',
                'l.modul',
                'l.keterangan',
                'l.waktu',
            ])
            ->join(
                'users u',
                'u.id = l.id_user',
                'left'
            );

        if (!empty($filter['search'])) {
            $search = trim(
                (string) $filter['search']
            );

            $builder
                ->groupStart()
                ->like('u.username', $search)
                ->orLike('l.aksi', $search)
                ->orLike('l.modul', $search)
                ->orLike('l.keterangan', $search)
                ->groupEnd();
        }

        if (!empty($filter['modul'])) {
            $builder->where(
                'l.modul',
                (string) $filter['modul']
            );
        }

        if (!empty($filter['aksi'])) {
            $builder->where(
                'l.aksi',
                (string) $filter['aksi']
            );
        }

        if (!empty($filter['tanggal_mulai'])) {
            $builder->where(
                'l.waktu >=',
                (string) $filter['tanggal_mulai']
                    . ' 00:00:00'
            );
        }

        if (!empty($filter['tanggal_selesai'])) {
            $builder->where(
                'l.waktu <=',
                (string) $filter['tanggal_selesai']
                    . ' 23:59:59'
            );
        }

        return $builder;
    }
}
