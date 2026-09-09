<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * SearchableEntityService
 *
 * Endpoint pencarian entity untuk selector remote.
 * Scope selalu ditentukan server-side dari context yang di-whitelist.
 */
class SearchableEntityService
{
    protected BaseConnection $db;
    protected BkScopeService $scopeService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->scopeService = new BkScopeService();
    }

    public function searchStudents(
        int $userId,
        string $query,
        string $context,
        int $limit = 25
    ): array {
        $query = trim($query);
        $context = trim($context);
        $limit = max(1, min(30, $limit));

        if (mb_strlen($query) < 2) {
            return [
                'success' => true,
                'message' => 'Ketik minimal 2 karakter.',
                'rows' => [],
            ];
        }

        $permission = $this->studentPermissionForContext($context);

        if ($permission === null) {
            return $this->fail('INVALID_CONTEXT', 'Context pencarian siswa tidak valid.');
        }

        $scope = $this->scopeService->resolveStudentIds($permission, $userId);

        if (! $scope['success']) {
            return $scope;
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $idTahun = (int) ($tahun['id'] ?? 0);

        $builder = $this->db
            ->table('siswa s')
            ->select([
                's.id',
                's.nisn',
                's.nama',
                'k.nama_kelas',
            ])
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = s.id' . ($idTahun > 0 ? ' AND ak.id_tahun = ' . $idTahun : ' AND 1 = 0'),
                'left',
                false
            )
            ->join('kelas k', 'k.id = ak.id_kelas AND k.deleted_at IS NULL', 'left', false)
            ->where('s.status_aktif', 'Aktif')
            ->where('s.deleted_at', null)
            ->groupStart()
                ->like('s.nama', $query)
                ->orLike('s.nisn', $query)
                ->orLike('s.nik', $query)
            ->groupEnd();

        $allowedIds = $scope['student_ids'];

        if (is_array($allowedIds)) {
            if ($allowedIds === []) {
                return [
                    'success' => true,
                    'message' => 'Tidak ada siswa dalam scope.',
                    'rows' => [],
                ];
            }

            $builder->whereIn('s.id', $allowedIds);
        }

        $rows = $builder
            ->orderBy('s.nama', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $kelas = trim((string) ($row['nama_kelas'] ?? ''));
            $label = trim((string) $row['nisn']) . ' — ' . trim((string) $row['nama']);

            if ($kelas !== '') {
                $label .= ' · ' . $kelas;
            }

            $result[] = [
                'id' => (int) $row['id'],
                'text' => $label,
                'nisn' => (string) $row['nisn'],
                'nama' => (string) $row['nama'],
                'kelas' => $kelas !== '' ? $kelas : null,
            ];
        }

        return [
            'success' => true,
            'message' => 'Hasil pencarian siswa berhasil dimuat.',
            'rows' => $result,
        ];
    }

    private function studentPermissionForContext(string $context): ?string
    {
        return match ($context) {
            'bk_kasus' => 'bk_kasus.manage',
            'prestasi' => 'prestasi.manage',
            'kartu' => 'kartu_pelajar.manage',
            'master_siswa' => 'master_siswa.view',
            default => null,
        };
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
