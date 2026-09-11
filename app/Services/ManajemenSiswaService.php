<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class ManajemenSiswaService
{
    protected BaseConnection $db;
    protected AuthService $authService;
    protected SiswaKelasService $siswaKelasService;
    protected SiswaService $siswaService;
    protected KelasService $kelasService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
        $this->siswaKelasService = new SiswaKelasService();
        $this->siswaService = new SiswaService();
        $this->kelasService = new KelasService();
    }

    public function getPlacementList(int $actorUserId, array $filter = []): array
    {
        if (!$this->canManage($actorUserId)) {
            return [];
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return [];
        }

        $builder = $this->placementBuilder((int) $tahun['id'], $filter);

        return $builder->orderBy('s.nama', 'ASC')->get()->getResultArray();
    }

    public function getPlacementPage(
        int $actorUserId,
        array $filter = [],
        int $limit = 25,
        int $offset = 0
    ): array {
        if (!$this->canManage($actorUserId)) {
            return $this->emptyPage($limit);
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return $this->emptyPage($limit);
        }

        $limit = $this->normalizeLimit($limit);
        $offset = max(0, $offset);

        $builder = $this->placementBuilder((int) $tahun['id'], $filter);
        $total = (clone $builder)->countAllResults();

        $offset = $this->normalizeOffset($offset, $limit, $total);

        $rows = $builder
            ->orderBy('s.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function getMutasiList(int $actorUserId, array $filter = []): array
    {
        if (!$this->canManage($actorUserId)) {
            return [];
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return [];
        }

        $builder = $this->mutasiBuilder((int) $tahun['id'], $filter);

        return $builder->orderBy('s.nama', 'ASC')->get()->getResultArray();
    }

    public function getMutasiPage(
        int $actorUserId,
        array $filter = [],
        int $limit = 25,
        int $offset = 0
    ): array {
        if (!$this->canManage($actorUserId)) {
            return $this->emptyPage($limit);
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return $this->emptyPage($limit);
        }

        $limit = $this->normalizeLimit($limit);
        $offset = max(0, $offset);

        $builder = $this->mutasiBuilder((int) $tahun['id'], $filter);
        $total = (clone $builder)->countAllResults();

        $offset = $this->normalizeOffset($offset, $limit, $total);

        $rows = $builder
            ->orderBy('s.nama', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function getActiveClassOptions(int $actorUserId): array
    {
        if (!$this->canManage($actorUserId)) {
            return [];
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return [];
        }

        return $this->db->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', (int) $tahun['id'])
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKenaikanSourceClasses(int $actorUserId): array
    {
        return $this->getSourceClasses($actorUserId, ['7', '8']);
    }

    public function getKelulusanSourceClasses(int $actorUserId): array
    {
        return $this->getSourceClasses($actorUserId, ['9']);
    }

    public function setOrMoveClass(
        int $actorUserId,
        int $idSiswa,
        int $idKelas
    ): array {
        if (!$this->canManage($actorUserId)) {
            return $this->forbidden();
        }

        return $this->siswaKelasService->setOrMoveClass(
            $actorUserId,
            $idSiswa,
            $idKelas
        );
    }

    public function getProcessData(int $actorUserId, int $idKelas): array
    {
        if (!$this->canManage($actorUserId)) {
            return $this->forbidden();
        }

        return $this->kelasService->getProcessData($idKelas);
    }

    public function naikKelas(
        int $actorUserId,
        int $idKelasAsal,
        int $idKelasTujuan,
        int $idTahunBaru,
        array $selected
    ): array {
        if (!$this->canManage($actorUserId)) {
            return $this->forbidden();
        }

        return $this->kelasService->naikKelas(
            $idKelasAsal,
            $idKelasTujuan,
            $idTahunBaru,
            $selected
        );
    }

    public function mutasi(
        int $actorUserId,
        int $idSiswa,
        string $status,
        string $keterangan
    ): array {
        if (!$this->canManage($actorUserId)) {
            return $this->forbidden();
        }

        return $this->siswaService->mutasi($idSiswa, $status, $keterangan);
    }

    public function lulus(int $actorUserId, int $idKelas, array $selected): array
    {
        if (!$this->canManage($actorUserId)) {
            return $this->forbidden();
        }

        return $this->kelasService->luluskan($idKelas, $selected);
    }

    public function getActiveYearInfo(int $actorUserId): ?array
    {
        if (!$this->canManage($actorUserId)) {
            return null;
        }

        return $this->getActiveYear();
    }

    private function placementBuilder(int $idTahun, array $filter)
    {
        $builder = $this->db->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, s.status_aktif, ' .
                'ak.id_kelas, k.nama_kelas'
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = s.id AND ak.id_tahun = ' . $idTahun,
                'left'
            )
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                'left'
            )
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif');

        $q = trim((string) ($filter['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('s.nama', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nik', $q)
                ->groupEnd();
        }

        $kelas = trim((string) ($filter['kelas'] ?? ''));
        if ($kelas === 'tanpa') {
            $builder->where('ak.id IS NULL', null, false);
        } elseif (ctype_digit($kelas) && (int) $kelas > 0) {
            $builder->where('ak.id_kelas', (int) $kelas);
        }

        return $builder;
    }

    private function mutasiBuilder(int $idTahun, array $filter)
    {
        $builder = $this->db->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, ' .
                's.status_aktif, ak.id_kelas AS id_kelas_aktif, ' .
                'k.nama_kelas AS nama_kelas_aktif'
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = s.id AND ak.id_tahun = ' . $idTahun,
                'left'
            )
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                'left'
            )
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif');

        $q = trim((string) ($filter['q'] ?? ''));
        if ($q !== '') {
            $builder
                ->groupStart()
                ->like('s.nama', $q)
                ->orLike('s.nisn', $q)
                ->orLike('s.nik', $q)
                ->groupEnd();
        }

        $idKelas = (int) ($filter['id_kelas'] ?? 0);
        if ($idKelas > 0) {
            $builder->where('ak.id_kelas', $idKelas);
        }

        return $builder;
    }

    private function normalizeLimit(int $limit): int
    {
        return in_array($limit, [25, 50, 100], true) ? $limit : 25;
    }

    private function normalizeOffset(int $offset, int $limit, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        if ($offset < $total) {
            return max(0, $offset);
        }

        return (int) (floor(($total - 1) / $limit) * $limit);
    }

    private function emptyPage(int $limit): array
    {
        return [
            'rows' => [],
            'total' => 0,
            'limit' => $this->normalizeLimit($limit),
            'offset' => 0,
        ];
    }

    private function getSourceClasses(int $actorUserId, array $tingkat): array
    {
        if (!$this->canManage($actorUserId)) {
            return [];
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return [];
        }

        return $this->db->table('kelas k')
            ->select(
                'k.id, k.nama_kelas, k.tingkat, k.rombel, COUNT(ak.id) AS jumlah_siswa'
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_kelas = k.id AND ak.id_tahun = k.id_tahun',
                'left'
            )
            ->where('k.id_tahun', (int) $tahun['id'])
            ->where('k.deleted_at', null)
            ->whereIn('k.tingkat', $tingkat)
            ->groupBy(['k.id', 'k.nama_kelas', 'k.tingkat', 'k.rombel'])
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function canManage(int $actorUserId): bool
    {
        return $this->authService->resolveScope(
            'master_siswa.manage',
            $actorUserId
        ) === 'SEMUA';
    }

    private function getActiveYear(): ?array
    {
        $row = $this->db->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function forbidden(): array
    {
        return [
            'success' => false,
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak untuk mengelola siswa.',
        ];
    }
}
