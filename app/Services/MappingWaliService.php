<?php

namespace App\Services;

use App\Models\MappingWaliKelasModel;
use Config\Database;
use Throwable;

/**
 * MappingWaliService
 *
 * Business logic Mapping Wali Kelas.
 *
 * Aturan utama:
 * - Wali Kelas bukan role.
 * - 1 guru maksimal 1 kelas aktif per tahun.
 * - 1 kelas maksimal 1 wali aktif per tahun.
 * - assign guru yang pernah menjadi wali pada tahun yang sama harus
 *   RESTORE row lama, bukan INSERT baru.
 * - dropdown guru/kelas hanya menampilkan yang belum dipakai aktif.
 * - Service adalah authoritative authorization boundary.
 */
class MappingWaliService
{
    protected MappingWaliKelasModel $mappingModel;
    protected AuthService $authService;
    protected $db;

    public function __construct()
    {
        $this->mappingModel = new MappingWaliKelasModel();
        $this->authService = new AuthService();
        $this->db = Database::connect();
    }

    /**
     * Daftar mapping sesuai scope.
     *
     * Recycle Bin bersifat administratif dan hanya boleh dibaca actor yang
     * mempunyai mapping_wali.manage = SEMUA.
     */
    public function getList(
        array $filter,
        int $userId,
        bool $deletedOnly = false
    ): array {
        if ($userId <= 0) {
            return [];
        }

        if ($deletedOnly && ! $this->canManage($userId)) {
            return [];
        }

        $builder = $this->db
            ->table('mapping_wali_kelas mw')
            ->select(
                'mw.id, mw.id_guru, mw.id_kelas, mw.id_tahun, ' .
                'mw.deleted_at, mw.created_at, mw.updated_at, ' .
                'g.nip, g.nama AS nama_guru, g.jenis_kelamin, ' .
                'k.nama_kelas, k.tingkat, k.rombel, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif'
            )
            ->join(
                'guru g',
                'g.id = mw.id_guru'
            )
            ->join(
                'kelas k',
                'k.id = mw.id_kelas'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = mw.id_tahun'
            );

        if ($deletedOnly) {
            $builder->where(
                'mw.deleted_at IS NOT NULL',
                null,
                false
            );
        } else {
            $builder->where('mw.deleted_at', null);

            if (! $this->applyViewScope($builder, $userId)) {
                return [];
            }
        }

        $idTahun = (int) ($filter['id_tahun'] ?? 0);

        if ($idTahun > 0) {
            $builder->where('mw.id_tahun', $idTahun);
        }

        $guru = trim((string) ($filter['guru'] ?? ''));

        if ($guru !== '') {
            $builder
                ->groupStart()
                ->like('g.nama', $guru)
                ->orLike('g.nip', $guru)
                ->orLike('g.nik', $guru)
                ->groupEnd();
        }

        $idKelas = (int) ($filter['id_kelas'] ?? 0);

        if ($idKelas > 0) {
            $builder->where('mw.id_kelas', $idKelas);
        }

        return $builder
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(ta.semester, 'Ganjil', 'Genap')",
                '',
                false
            )
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTahunOptions(int $userId): array
    {
        $builder = $this->db
            ->table('tahun_ajaran ta')
            ->distinct()
            ->select(
                'ta.id, ta.nama_tahun, ta.semester, ta.status_aktif'
            )
            ->where('ta.deleted_at', null);

        if ($this->canViewAll($userId)) {
            return $builder
                ->orderBy('ta.nama_tahun', 'DESC')
                ->orderBy(
                    "FIELD(ta.semester, 'Ganjil', 'Genap')",
                    '',
                    false
                )
                ->get()
                ->getResultArray();
        }

        $idGuru = $this->getIdGuruUser($userId);

        if (
            $idGuru <= 0
            || $this->authService->resolveScope(
                'mapping_wali.view',
                $userId
            ) !== 'DIRI_SENDIRI'
        ) {
            return [];
        }

        return $builder
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_tahun = ta.id'
            )
            ->where('mw.id_guru', $idGuru)
            ->where('mw.deleted_at', null)
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(ta.semester, 'Ganjil', 'Genap')",
                '',
                false
            )
            ->get()
            ->getResultArray();
    }

    public function getKelasFilterOptions(int $userId): array
    {
        $builder = $this->db
            ->table('kelas k')
            ->distinct()
            ->select(
                'k.id, k.nama_kelas, k.id_tahun, ' .
                'ta.nama_tahun, ta.semester'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = k.id_tahun'
            )
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null);

        if ($this->canViewAll($userId)) {
            return $builder
                ->orderBy('ta.nama_tahun', 'DESC')
                ->orderBy('k.tingkat', 'ASC')
                ->orderBy('k.rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        $idGuru = $this->getIdGuruUser($userId);

        if (
            $idGuru <= 0
            || $this->authService->resolveScope(
                'mapping_wali.view',
                $userId
            ) !== 'DIRI_SENDIRI'
        ) {
            return [];
        }

        return $builder
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_kelas = k.id AND mw.id_tahun = k.id_tahun'
            )
            ->where('mw.id_guru', $idGuru)
            ->where('mw.deleted_at', null)
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Dropdown assign.
     *
     * Hanya actor dengan mapping_wali.manage = SEMUA yang boleh meminta opsi.
     */
    public function getAssignOptions(
        int $idTahun,
        int $userId
    ): array {
        if (! $this->canManage($userId)) {
            return $this->forbidden();
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak ditemukan.',
                'guru' => [],
                'kelas' => [],
            ];
        }

        $guru = $this->db
            ->table('guru g')
            ->select(
                'g.id, g.nip, g.nik, g.nama'
            )
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_guru = g.id ' .
                'AND mw.id_tahun = ' . (int) $idTahun . ' ' .
                'AND mw.deleted_at IS NULL',
                'left'
            )
            ->where('g.deleted_at', null)
            ->where('mw.id', null)
            ->orderBy('g.nama', 'ASC')
            ->get()
            ->getResultArray();

        $kelas = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.nama_kelas, k.tingkat, k.rombel'
            )
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_kelas = k.id ' .
                'AND mw.id_tahun = ' . (int) $idTahun . ' ' .
                'AND mw.deleted_at IS NULL',
                'left'
            )
            ->where('k.id_tahun', $idTahun)
            ->where('k.deleted_at', null)
            ->where('mw.id', null)
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'message' => 'Pilihan mapping berhasil dimuat.',
            'tahun' => $tahun,
            'guru' => $guru,
            'kelas' => $kelas,
        ];
    }

    public function assign(
        int $idGuru,
        int $idKelas,
        int $idTahun,
        int $userId
    ): array {
        if (! $this->canManage($userId)) {
            return $this->forbidden();
        }

        $validation = $this->validateReferences(
            $idGuru,
            $idKelas,
            $idTahun
        );

        if ($validation !== null) {
            return $validation;
        }

        $roleValidation = $this->validateWaliRoleContract($idGuru);

        if ($roleValidation !== null) {
            return $roleValidation;
        }

        $guruAktif = $this->mappingModel
            ->findAktifByGuruTahun(
                $idGuru,
                $idTahun
            );

        if ($guruAktif !== null) {
            if (
                (int) $guruAktif['id_kelas']
                === $idKelas
            ) {
                return [
                    'success' => true,
                    'message' => 'Guru tersebut sudah menjadi wali kelas yang dipilih.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Guru sudah menjadi wali kelas lain pada tahun ajaran yang sama. Nonaktifkan mapping aktif terlebih dahulu.',
            ];
        }

        $kelasAktif = $this->mappingModel
            ->findAktifByKelasTahun(
                $idKelas,
                $idTahun
            );

        if ($kelasAktif !== null) {
            return [
                'success' => false,
                'message' => 'Kelas yang dipilih sudah memiliki wali aktif.',
            ];
        }

        $historiGuru = $this->mappingModel
            ->findByGuruTahun(
                $idGuru,
                $idTahun
            );

        $this->db->transBegin();

        try {
            if (
                $historiGuru !== null
                && ! empty($historiGuru['deleted_at'])
            ) {
                if (
                    ! $this->mappingModel->restoreMapping(
                        (int) $historiGuru['id'],
                        $idKelas
                    )
                ) {
                    throw new \RuntimeException(
                        'Histori mapping wali gagal dipulihkan.'
                    );
                }

                $action = 'RESTORE_REASSIGN';
                $message = 'Wali kelas berhasil di-restore dan di-assign ke kelas baru.';
            } else {
                $idMapping = $this->mappingModel->insert([
                    'id_guru' => $idGuru,
                    'id_kelas' => $idKelas,
                    'id_tahun' => $idTahun,
                ], true);

                if ($idMapping === false) {
                    throw new \RuntimeException(
                        implode(
                            ' ',
                            $this->mappingModel->errors()
                        ) ?: 'Mapping wali kelas gagal disimpan.'
                    );
                }

                $action = 'ASSIGN';
                $message = 'Wali kelas berhasil di-assign.';
            }

            $detail = $this->getDetailNames(
                $idGuru,
                $idKelas,
                $idTahun
            );

            $this->logActivity(
                $userId,
                $action,
                'Mapping Wali Kelas',
                sprintf(
                    '%s menjadi wali %s pada %s - %s.',
                    $detail['nama_guru'],
                    $detail['nama_kelas'],
                    $detail['nama_tahun'],
                    $detail['semester']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi mapping wali kelas gagal.'
                );
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function delete(int $id, int $userId): array
    {
        if (! $this->canManage($userId)) {
            return $this->forbidden();
        }

        $mapping = $this->getOneActive($id);

        if ($mapping === null) {
            return [
                'success' => false,
                'message' => 'Mapping wali kelas aktif tidak ditemukan.',
            ];
        }

        if (! $this->mappingModel->delete($id)) {
            return [
                'success' => false,
                'message' => 'Mapping wali kelas gagal dinonaktifkan.',
            ];
        }

        $this->logActivity(
            $userId,
            'NONAKTIFKAN',
            'Mapping Wali Kelas',
            sprintf(
                'Menonaktifkan %s sebagai wali %s pada %s - %s.',
                $mapping['nama_guru'],
                $mapping['nama_kelas'],
                $mapping['nama_tahun'],
                $mapping['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Mapping wali kelas berhasil dinonaktifkan dan disimpan sebagai histori.',
        ];
    }

    public function restore(int $id, int $userId): array
    {
        if (! $this->canManage($userId)) {
            return $this->forbidden();
        }

        $mapping = $this->getOneDeleted($id);

        if ($mapping === null) {
            return [
                'success' => false,
                'message' => 'Histori mapping wali kelas tidak ditemukan.',
            ];
        }

        if (
            $this->mappingModel->isGuruWaliAktif(
                (int) $mapping['id_guru'],
                (int) $mapping['id_tahun']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Guru sudah menjadi wali aktif pada tahun ajaran tersebut.',
            ];
        }

        if (
            $this->mappingModel->isKelasSudahAdaWali(
                (int) $mapping['id_kelas'],
                (int) $mapping['id_tahun']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kelas historis sudah memiliki wali aktif. Gunakan form Assign untuk reassign guru ke kelas lain yang masih kosong.',
            ];
        }

        $guru = $this->db
            ->table('guru')
            ->where(
                'id',
                (int) $mapping['id_guru']
            )
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $kelas = $this->db
            ->table('kelas')
            ->where(
                'id',
                (int) $mapping['id_kelas']
            )
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where(
                'id',
                (int) $mapping['id_tahun']
            )
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (
            $guru === null
            || $kelas === null
            || $tahun === null
        ) {
            return [
                'success' => false,
                'message' => 'Restore tidak dapat dilakukan karena Guru, Kelas, atau Tahun Ajaran terkait sudah tidak aktif.',
            ];
        }

        $roleValidation = $this->validateWaliRoleContract(
            (int) $mapping['id_guru']
        );

        if ($roleValidation !== null) {
            return $roleValidation;
        }

        if (
            ! $this->mappingModel->restoreMapping(
                $id,
                (int) $mapping['id_kelas']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Histori mapping wali kelas gagal dipulihkan.',
            ];
        }

        $this->logActivity(
            $userId,
            'RESTORE',
            'Mapping Wali Kelas',
            sprintf(
                'Memulihkan %s sebagai wali %s pada %s - %s.',
                $mapping['nama_guru'],
                $mapping['nama_kelas'],
                $mapping['nama_tahun'],
                $mapping['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Mapping wali kelas berhasil dipulihkan.',
        ];
    }

    public function forceDelete(int $id, int $userId): array
    {
        if (! $this->canManage($userId)) {
            return $this->forbidden();
        }

        $mapping = $this->getOneDeleted($id);

        if ($mapping === null) {
            return [
                'success' => false,
                'message' => 'Histori mapping wali kelas tidak ditemukan.',
            ];
        }

        $deleted = $this->db
            ->table('mapping_wali_kelas')
            ->where('id', $id)
            ->where(
                'deleted_at IS NOT NULL',
                null,
                false
            )
            ->delete();

        if (! $deleted) {
            return [
                'success' => false,
                'message' => 'Histori mapping wali kelas gagal dihapus permanen.',
            ];
        }

        $this->logActivity(
            $userId,
            'FORCE_DELETE',
            'Mapping Wali Kelas',
            sprintf(
                'Menghapus permanen histori %s sebagai wali %s pada %s - %s.',
                $mapping['nama_guru'],
                $mapping['nama_kelas'],
                $mapping['nama_tahun'],
                $mapping['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Histori mapping wali kelas berhasil dihapus permanen.',
        ];
    }

    public function isWaliAktif(
        int $idGuru,
        int $idTahun
    ): bool {
        return $this->mappingModel
            ->isGuruWaliAktif(
                $idGuru,
                $idTahun
            );
    }

    public function getKelasDiampu(
        int $idGuru,
        int $idTahun
    ): array {
        $idKelas = $this->mappingModel
            ->getIdKelasDiampu(
                $idGuru,
                $idTahun
            );

        return $idKelas !== null
            ? [$idKelas]
            : [];
    }

    public function canManage(int $userId): bool
    {
        return $userId > 0
            && $this->authService->resolveScope(
                'mapping_wali.manage',
                $userId
            ) === 'SEMUA';
    }

    protected function applyViewScope(
        $builder,
        int $userId
    ): bool {
        if ($this->canViewAll($userId)) {
            return true;
        }

        $scope = $this->authService->resolveScope(
            'mapping_wali.view',
            $userId
        );

        if ($scope === 'DIRI_SENDIRI') {
            $idGuru = $this->getIdGuruUser(
                $userId
            );

            if ($idGuru <= 0) {
                return false;
            }

            $builder->where(
                'mw.id_guru',
                $idGuru
            );

            return true;
        }

        return false;
    }

    private function canViewAll(int $userId): bool
    {
        if ($this->canManage($userId)) {
            return true;
        }

        return $userId > 0
            && $this->authService->resolveScope(
                'mapping_wali.view_all',
                $userId
            ) === 'SEMUA';
    }

    protected function validateReferences(
        int $idGuru,
        int $idKelas,
        int $idTahun
    ): ?array {
        if (
            $idGuru <= 0
            || $idKelas <= 0
            || $idTahun <= 0
        ) {
            return [
                'success' => false,
                'message' => 'Guru, Kelas, dan Tahun Ajaran wajib dipilih.',
            ];
        }

        $guru = $this->db
            ->table('guru')
            ->where('id', $idGuru)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($guru === null) {
            return [
                'success' => false,
                'message' => 'Guru tidak ditemukan atau sudah tidak aktif.',
            ];
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak ditemukan.',
            ];
        }

        $kelas = $this->db
            ->table('kelas')
            ->where('id', $idKelas)
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan pada tahun ajaran yang dipilih.',
            ];
        }

        return null;
    }

    private function validateWaliRoleContract(int $idGuru): ?array
    {
        $user = $this->db
            ->table('users')
            ->select('id, role, id_guru, id_pegawai, id_siswa')
            ->where('id_guru', $idGuru)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        // Preserve legacy ability to keep mapping data even when a Guru account
        // has not been provisioned. The contract becomes enforceable as soon as
        // an active user is linked to the Guru identity.
        if ($user === null) {
            return null;
        }

        $userId = (int) ($user['id'] ?? 0);
        $primary = trim((string) ($user['role'] ?? ''));
        $effectiveRoles = $this->authService->getUserRoles($userId);
        $secondary = array_values(array_diff(
            $effectiveRoles,
            $primary !== '' ? [$primary] : []
        ));

        $policy = RoleAssignmentPolicyService::validate(
            $primary !== '' ? $primary : null,
            $secondary,
            (int) ($user['id_guru'] ?? 0) ?: null,
            (int) ($user['id_pegawai'] ?? 0) ?: null,
            (int) ($user['id_siswa'] ?? 0) ?: null,
            true
        );

        if ($policy['success']) {
            return null;
        }

        return [
            'success' => false,
            'code' => 'ROLE_COMBINATION',
            'message' => 'Guru tidak dapat dijadikan Wali sebelum konfigurasi role diperbaiki. '
                . (string) $policy['message'],
        ];
    }

    protected function getOneActive(int $id): ?array
    {
        return $this->db
            ->table('mapping_wali_kelas mw')
            ->select(
                'mw.*, g.nama AS nama_guru, ' .
                'k.nama_kelas, ta.nama_tahun, ta.semester'
            )
            ->join(
                'guru g',
                'g.id = mw.id_guru'
            )
            ->join(
                'kelas k',
                'k.id = mw.id_kelas'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = mw.id_tahun'
            )
            ->where('mw.id', $id)
            ->where('mw.deleted_at', null)
            ->get()
            ->getRowArray();
    }

    protected function getOneDeleted(int $id): ?array
    {
        return $this->db
            ->table('mapping_wali_kelas mw')
            ->select(
                'mw.*, g.nama AS nama_guru, ' .
                'k.nama_kelas, ta.nama_tahun, ta.semester'
            )
            ->join(
                'guru g',
                'g.id = mw.id_guru'
            )
            ->join(
                'kelas k',
                'k.id = mw.id_kelas'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = mw.id_tahun'
            )
            ->where('mw.id', $id)
            ->where(
                'mw.deleted_at IS NOT NULL',
                null,
                false
            )
            ->get()
            ->getRowArray();
    }

    protected function getDetailNames(
        int $idGuru,
        int $idKelas,
        int $idTahun
    ): array {
        $row = $this->db
            ->table('guru g')
            ->select(
                'g.nama AS nama_guru, ' .
                'k.nama_kelas, ta.nama_tahun, ta.semester'
            )
            ->join(
                'kelas k',
                'k.id = ' . (int) $idKelas
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = ' . (int) $idTahun
            )
            ->where('g.id', $idGuru)
            ->get()
            ->getRowArray();

        return $row ?? [
            'nama_guru' => 'Guru',
            'nama_kelas' => 'Kelas',
            'nama_tahun' => 'Tahun Ajaran',
            'semester' => '',
        ];
    }

    protected function getIdGuruUser(int $userId): int
    {
        $row = $this->db
            ->table('users')
            ->select('id_guru')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        return isset($row['id_guru'])
            ? (int) $row['id_guru']
            : 0;
    }

    private function forbidden(): array
    {
        return [
            'success' => false,
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak mengelola Mapping Wali Kelas.',
        ];
    }

    protected function logActivity(
        int $idUser,
        string $aksi,
        string $modul,
        string $keterangan
    ): void {
        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $idUser > 0
                    ? $idUser
                    : null,
                'aksi' => $aksi,
                'modul' => $modul,
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
            ]);
    }
}
