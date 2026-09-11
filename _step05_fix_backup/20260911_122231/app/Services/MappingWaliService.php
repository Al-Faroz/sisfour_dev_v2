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
 * Acuan docs/04_MASTER_DATA §4 dan §7.3.
 *
 * Aturan utama:
 * - Wali Kelas bukan role.
 * - 1 guru maksimal 1 kelas aktif per tahun.
 * - 1 kelas maksimal 1 wali aktif per tahun.
 * - assign guru yang pernah menjadi wali pada tahun yang sama harus
 *   RESTORE row lama, bukan INSERT baru.
 * - dropdown guru/kelas hanya menampilkan yang belum dipakai aktif.
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
     * Daftar mapping aktif sesuai scope.
     *
     * Admin/Operator/Pimpinan: semua sesuai permission.
     * Guru: hanya mapping dirinya sendiri.
     */
    public function getList(
        array $filter,
        int $userId,
        bool $deletedOnly = false
    ): array {
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

            if (!$this->applyViewScope($builder, $userId)) {
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

    public function getTahunOptions(): array
    {
        return $this->db
            ->table('tahun_ajaran')
            ->select(
                'id, nama_tahun, semester, status_aktif'
            )
            ->where('deleted_at', null)
            ->orderBy('nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(semester, 'Ganjil', 'Genap')",
                '',
                false
            )
            ->get()
            ->getResultArray();
    }

    public function getKelasFilterOptions(): array
    {
        return $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.nama_kelas, k.id_tahun, ' .
                'ta.nama_tahun, ta.semester'
            )
            ->join(
                'tahun_ajaran ta',
                'ta.id = k.id_tahun'
            )
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null)
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Dropdown assign.
     *
     * Guru: hanya guru yang belum menjadi wali aktif pada tahun tersebut.
     * Kelas: hanya kelas yang belum memiliki wali aktif pada tahun tersebut.
     */
    public function getAssignOptions(int $idTahun): array
    {
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
                'g.id, g.nip, g.nama'
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

    /**
     * Assign / reassign Wali Kelas.
     *
     * Jika guru mempunyai row histori soft-deleted di tahun yang sama:
     * UPDATE row tersebut (restore + ganti id_kelas).
     * Tidak membuat INSERT kedua untuk guru/tahun yang sama.
     */
    public function assign(
        int $idGuru,
        int $idKelas,
        int $idTahun
    ): array {
        $validation = $this->validateReferences(
            $idGuru,
            $idKelas,
            $idTahun
        );

        if ($validation !== null) {
            return $validation;
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
                && !empty($historiGuru['deleted_at'])
            ) {
                if (
                    !$this->mappingModel->restoreMapping(
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

    /**
     * Nonaktifkan mapping melalui soft delete.
     */
    public function delete(int $id): array
    {
        $mapping = $this->getOneActive($id);

        if ($mapping === null) {
            return [
                'success' => false,
                'message' => 'Mapping wali kelas aktif tidak ditemukan.',
            ];
        }

        if (!$this->mappingModel->delete($id)) {
            return [
                'success' => false,
                'message' => 'Mapping wali kelas gagal dinonaktifkan.',
            ];
        }

        $this->logActivity(
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

    /**
     * Restore dari Recycle Bin ke kelas historisnya.
     *
     * Untuk reassign ke kelas berbeda, gunakan assign(); assign akan
     * otomatis me-restore row lama milik guru/tahun tersebut.
     */
    public function restore(int $id): array
    {
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

        if (
            !$this->mappingModel->restoreMapping(
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

    public function forceDelete(int $id): array
    {
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

        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Histori mapping wali kelas gagal dihapus permanen.',
            ];
        }

        $this->logActivity(
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
        return $this->authService->resolveScope(
            'mapping_wali.manage',
            $userId
        ) === 'SEMUA';
    }

    protected function applyViewScope(
        $builder,
        int $userId
    ): bool {
        if (
            $this->authService->resolveScope(
                'mapping_wali.manage',
                $userId
            ) === 'SEMUA'
        ) {
            return true;
        }

        if (
            $this->authService->resolveScope(
                'mapping_wali.view_all',
                $userId
            ) === 'SEMUA'
        ) {
            return true;
        }

        $scope = $this->authService->resolveScope(
            'mapping_wali.view',
            $userId
        );

        if ($scope === 'SEMUA') {
            return true;
        }

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
            ->get()
            ->getRowArray();

        return isset($row['id_guru'])
            ? (int) $row['id_guru']
            : 0;
    }

    protected function logActivity(
        string $aksi,
        string $modul,
        string $keterangan
    ): void {
        $idUser = session()->get('user_id');

        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $idUser
                    ? (int) $idUser
                    : null,
                'aksi' => $aksi,
                'modul' => $modul,
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
            ]);
    }
}
