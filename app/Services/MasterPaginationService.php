<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Read-only pagination query service for large Master/Settings datasets.
 *
 * Step 02 goals:
 * - DB-side LIMIT/OFFSET;
 * - one response contract: rows, total, limit, offset, page, total_pages;
 * - preserve existing business services for create/update/delete/import/export;
 * - preserve RBAC/data scope for Master Siswa and Jadwal Guru.
 */
class MasterPaginationService
{
    private const LIMIT_DEFAULT = 25;
    private const LIMIT_MAX = 100;
    private const LIMIT_ALLOWED = [25, 50, 100];

    protected BaseConnection $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    public function normalizePaging(array $input): array
    {
        $limit = (int) ($input['limit'] ?? self::LIMIT_DEFAULT);

        if (! in_array($limit, self::LIMIT_ALLOWED, true)) {
            $limit = self::LIMIT_DEFAULT;
        }

        $offset = max(0, (int) ($input['offset'] ?? 0));

        return [
            'limit' => min(self::LIMIT_MAX, $limit),
            'offset' => $offset,
        ];
    }

    public function pageGuru(
        array $filter,
        int $limit,
        int $offset,
        bool $deletedOnly = false
    ): array {
        $builder = $this->db
            ->table('guru g')
            ->select(
                'g.id, g.nik, g.nip, g.nama, g.jenis_kelamin, g.tempat_lahir, ' .
                'g.tanggal_lahir, g.agama, g.alamat, g.no_telepon, g.email, ' .
                'g.status_kepegawaian, g.nuptk, g.foto, g.deleted_at, ' .
                'g.created_at, g.updated_at, u.id AS id_user, u.username, ' .
                'u.role AS role_user, u.status_aktif AS status_user'
            )
            ->join('users u', 'u.id_guru = g.id', 'left');

        if ($deletedOnly) {
            $builder->where('g.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('g.deleted_at', null);
        }

        $this->applyPersonaliaFilter($builder, $filter, 'g');

        $page = $this->executePage(
            $builder,
            $limit,
            $offset,
            static function ($query): void {
                $query->orderBy('g.nama', 'ASC')->orderBy('g.id', 'ASC');
            }
        );

        foreach ($page['rows'] as &$row) {
            $row['login_identifier'] = $this->loginIdentifier($row);
            $row['identity_complete'] = $this->validNik((string) ($row['nik'] ?? ''));
        }
        unset($row);

        return $page;
    }

    public function pagePegawai(
        array $filter,
        int $limit,
        int $offset,
        bool $deletedOnly = false
    ): array {
        $builder = $this->db
            ->table('pegawai p')
            ->select(
                'p.id, p.nik, p.nip, p.nama, p.jenis_kelamin, p.tempat_lahir, ' .
                'p.tanggal_lahir, p.agama, p.alamat, p.no_telepon, p.email, ' .
                'p.status_kepegawaian, p.nuptk, p.foto, p.jabatan AS jabatan_legacy, ' .
                'p.deleted_at, p.created_at, p.updated_at, u.id AS id_user, ' .
                'u.username, u.role AS role_user, u.status_aktif AS status_user'
            )
            ->join('users u', 'u.id_pegawai = p.id', 'left');

        if ($deletedOnly) {
            $builder->where('p.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('p.deleted_at', null);
        }

        $this->applyPersonaliaFilter($builder, $filter, 'p');

        $page = $this->executePage(
            $builder,
            $limit,
            $offset,
            static function ($query): void {
                $query->orderBy('p.nama', 'ASC')->orderBy('p.id', 'ASC');
            }
        );

        foreach ($page['rows'] as &$row) {
            $row['login_identifier'] = $this->loginIdentifier($row);
            $row['identity_complete'] = $this->validNik((string) ($row['nik'] ?? ''));
        }
        unset($row);

        return $page;
    }

    public function pageSiswa(
        array $filter,
        int $userId,
        int $limit,
        int $offset,
        bool $deletedOnly = false
    ): array {
        $idTahunAktif = $this->getIdTahunAktif();

        $builder = $this->db
            ->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, s.tempat_lahir, ' .
                's.tanggal_lahir, s.alamat, s.no_telepon, s.kebutuhan_khusus, ' .
                's.disabilitas, s.nomor_kip_pip, s.nama_ayah_kandung, ' .
                's.nama_ibu_kandung, s.nama_wali, s.foto, s.status_aktif, ' .
                's.tanggal_mutasi, s.keterangan_mutasi, s.deleted_at, ' .
                's.created_at, s.updated_at'
            );

        if ($idTahunAktif !== null) {
            $builder
                ->select(
                    'ak.id_kelas AS id_kelas_aktif, ' .
                    'k.nama_kelas AS nama_kelas_aktif'
                )
                ->join(
                    'anggota_kelas ak',
                    'ak.id_siswa = s.id AND ak.id_tahun = ' . (int) $idTahunAktif,
                    'left'
                )
                ->join(
                    'kelas k',
                    'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                    'left'
                );
        } else {
            $builder->select(
                'NULL AS id_kelas_aktif, NULL AS nama_kelas_aktif',
                false
            );
        }

        if ($deletedOnly) {
            $builder->where('s.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('s.deleted_at', null);

            if (! $this->applySiswaViewScope(
                $builder,
                $userId,
                $idTahunAktif
            )) {
                return $this->emptyPage($limit);
            }
        }

        $nama = trim((string) ($filter['nama'] ?? ''));
        $nik = trim((string) ($filter['nik'] ?? ''));
        $nisn = trim((string) ($filter['nisn'] ?? ''));
        $idKelas = (int) ($filter['id_kelas'] ?? 0);
        $status = trim((string) ($filter['status_aktif'] ?? ''));

        if ($nama !== '') {
            $builder->like('s.nama', $nama);
        }

        if ($nik !== '') {
            $builder->like('s.nik', $nik);
        }

        if ($nisn !== '') {
            $builder->like('s.nisn', $nisn);
        }

        if ($idKelas > 0 && $idTahunAktif !== null) {
            $builder->where('ak.id_kelas', $idKelas);
        }

        if (in_array($status, ['Aktif', 'Lulus', 'Pindah', 'Keluar'], true)) {
            $builder->where('s.status_aktif', $status);
        }

        return $this->executePage(
            $builder,
            $limit,
            $offset,
            static function ($query): void {
                $query->orderBy('s.nama', 'ASC')->orderBy('s.id', 'ASC');
            }
        );
    }

    public function pageJadwal(
        array $filter,
        int $userId,
        int $limit,
        int $offset
    ): array {
        $builder = $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.id, jg.id_guru, jg.id_kelas, jg.id_mapel, jg.id_tahun, ' .
                'jg.hari, jg.jam_mulai, jg.jam_selesai, jg.sesi, ' .
                'jg.status_jadwal, g.nip, g.nik, g.nama AS nama_guru, ' .
                'k.nama_kelas, k.tingkat, k.rombel, ' .
                'mp.nama_mapel, mp.kode_mapel, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif'
            )
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->join('tahun_ajaran ta', 'ta.id = jg.id_tahun');

        if (! $this->applyJadwalViewScope($builder, $userId)) {
            return $this->emptyPage($limit);
        }

        $idGuru = (int) ($filter['id_guru'] ?? 0);
        $idKelas = (int) ($filter['id_kelas'] ?? 0);
        $idTahun = (int) ($filter['id_tahun'] ?? 0);
        $hari = trim((string) ($filter['hari'] ?? ''));
        $status = trim((string) ($filter['status_jadwal'] ?? ''));

        if ($idGuru > 0) {
            $builder->where('jg.id_guru', $idGuru);
        }

        if ($idKelas > 0) {
            $builder->where('jg.id_kelas', $idKelas);
        }

        if ($idTahun > 0) {
            $builder->where('jg.id_tahun', $idTahun);
        }

        if (in_array(
            $hari,
            ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            true
        )) {
            $builder->where('jg.hari', $hari);
        }

        if (in_array($status, ['Aktif', 'Nonaktif'], true)) {
            $builder->where('jg.status_jadwal', $status);
        }

        return $this->executePage(
            $builder,
            $limit,
            $offset,
            static function ($query): void {
                $query
                    ->orderBy('ta.nama_tahun', 'DESC')
                    ->orderBy(
                        "FIELD(ta.semester,'Ganjil','Genap')",
                        '',
                        false
                    )
                    ->orderBy(
                        "FIELD(jg.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')",
                        '',
                        false
                    )
                    ->orderBy('jg.jam_mulai', 'ASC')
                    ->orderBy('k.tingkat', 'ASC')
                    ->orderBy('k.rombel', 'ASC')
                    ->orderBy('jg.id', 'ASC');
            }
        );
    }

    private function applyPersonaliaFilter(
        $builder,
        array $filter,
        string $alias
    ): void {
        $nama = trim((string) ($filter['nama'] ?? ''));
        $nik = trim((string) ($filter['nik'] ?? ''));
        $nip = trim((string) ($filter['nip'] ?? ''));
        $jk = strtoupper(trim((string) ($filter['jenis_kelamin'] ?? '')));
        $status = trim((string) ($filter['status_kepegawaian'] ?? ''));

        if ($nama !== '') {
            $builder->like($alias . '.nama', $nama);
        }

        if ($nik !== '') {
            $builder->like($alias . '.nik', $nik);
        }

        if ($nip !== '') {
            $builder->like($alias . '.nip', $nip);
        }

        if (in_array($jk, ['L', 'P'], true)) {
            $builder->where($alias . '.jenis_kelamin', $jk);
        }

        if ($status !== '') {
            $builder->where($alias . '.status_kepegawaian', $status);
        }
    }

    private function applySiswaViewScope(
        $builder,
        int $userId,
        ?int $idTahunAktif
    ): bool {
        $scope = $this->authService->resolveScope(
            'master_siswa.view',
            $userId
        );

        if ($scope === 'SEMUA') {
            return true;
        }

        if ($scope === 'KELAS_DIAMPU') {
            if ($idTahunAktif === null) {
                return false;
            }

            $idGuru = $this->getIdentityId($userId, 'id_guru');

            if ($idGuru <= 0) {
                return false;
            }

            $kelas = $this->authService->getKelasDiampu(
                $idGuru,
                $idTahunAktif
            );

            if ($kelas === []) {
                return false;
            }

            $builder->whereIn('ak.id_kelas', $kelas);
            return true;
        }

        if ($scope === 'DIRI_SENDIRI') {
            $idSiswa = $this->getIdentityId($userId, 'id_siswa');

            if ($idSiswa <= 0) {
                return false;
            }

            $builder->where('s.id', $idSiswa);
            return true;
        }

        return false;
    }

    private function applyJadwalViewScope(
        $builder,
        int $userId
    ): bool {
        if (
            $this->authService->resolveScope(
                'jadwal_guru.manage',
                $userId
            ) === 'SEMUA'
        ) {
            return true;
        }

        if (
            $this->authService->resolveScope(
                'jadwal_guru.view_all',
                $userId
            ) === 'SEMUA'
        ) {
            return true;
        }

        $scope = $this->authService->resolveScope(
            'jadwal_guru.view',
            $userId
        );

        $idGuru = $this->getIdentityId(
            $userId,
            'id_guru'
        );

        if ($scope === 'DIRI_SENDIRI') {
            if ($idGuru <= 0) {
                return false;
            }

            $builder->where('jg.id_guru', $idGuru);
            return true;
        }

        if ($scope === 'KELAS_DIAMPU') {
            $idTahun = $this->getIdTahunAktif();

            if ($idGuru <= 0 || $idTahun === null) {
                return false;
            }

            $kelas = $this->authService->getKelasDiampu(
                $idGuru,
                $idTahun
            );

            if ($kelas === []) {
                return false;
            }

            $builder
                ->where('jg.id_tahun', $idTahun)
                ->whereIn('jg.id_kelas', $kelas);

            return true;
        }

        return false;
    }

    private function executePage(
        $builder,
        int $limit,
        int $offset,
        callable $order
    ): array {
        $limit = $this->sanitizeLimit($limit);
        $offset = max(0, $offset);

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->countAllResults();

        if ($total > 0 && $offset >= $total) {
            $offset = (int) (floor(($total - 1) / $limit) * $limit);
        }

        $order($builder);

        $rows = $builder
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->pagePayload($rows, $total, $limit, $offset);
    }

    private function pagePayload(
        array $rows,
        int $total,
        int $limit,
        int $offset
    ): array {
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = (int) floor($offset / $limit) + 1;

        return [
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'total_pages' => $totalPages,
            'has_previous' => $offset > 0,
            'has_next' => $offset + $limit < $total,
        ];
    }

    private function emptyPage(int $limit): array
    {
        return $this->pagePayload(
            [],
            0,
            $this->sanitizeLimit($limit),
            0
        );
    }

    private function sanitizeLimit(int $limit): int
    {
        return in_array($limit, self::LIMIT_ALLOWED, true)
            ? $limit
            : self::LIMIT_DEFAULT;
    }

    private function getIdTahunAktif(): ?int
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row !== null ? (int) $row['id'] : null;
    }

    private function getIdentityId(int $userId, string $column): int
    {
        if (! in_array($column, ['id_guru', 'id_siswa'], true)) {
            return 0;
        }

        $row = $this->db
            ->table('users')
            ->select($column)
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        return isset($row[$column]) ? (int) $row[$column] : 0;
    }

    private function loginIdentifier(array $row): string
    {
        $nip = preg_replace('/\D+/', '', (string) ($row['nip'] ?? ''));

        if ($nip !== '') {
            return $nip;
        }

        return preg_replace('/\D+/', '', (string) ($row['nik'] ?? ''));
    }

    private function validNik(string $value): bool
    {
        return preg_match('/^\d{16}$/', $value) === 1;
    }
}
