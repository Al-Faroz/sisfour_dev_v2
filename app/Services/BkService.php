<?php

namespace App\Services;

use App\Models\BKKasusModel;
use App\Models\BKPelanggaranModel;
use App\Models\BKTindakLanjutModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;

class BkService
{
    private const TZ = 'Asia/Jakarta';
    private const KATEGORI = ['Ringan', 'Sedang', 'Berat'];
    private const MAX_EXPORT_ROWS = 50000;
    private const TINDAK_LANJUT = [
        'Konseling Individu',
        'Pembinaan',
        'Koordinasi Wali Kelas',
        'Pemanggilan Orang Tua',
        'Surat Perjanjian',
        'SP 1',
        'SP 2',
        'SP 3',
        'Home Visit',
        'Konferensi Kasus',
        'Monitoring',
        'Lainnya',
    ];

    protected BKKasusModel $kasusModel;
    protected BKPelanggaranModel $pelanggaranModel;
    protected BKTindakLanjutModel $tindakLanjutModel;
    protected AuthService $authService;
    protected BkScopeService $scopeService;
    protected PeriodContextService $periodContext;

    public function __construct()
    {
        $this->kasusModel = new BKKasusModel();
        $this->pelanggaranModel = new BKPelanggaranModel();
        $this->tindakLanjutModel = new BKTindakLanjutModel();
        $this->authService = new AuthService();
        $this->scopeService = new BkScopeService();
        $this->periodContext = new PeriodContextService();
    }

    public function getKasusPage(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds('bk_kasus.view', $userId);
        if (! $scope['success']) {
            return $scope;
        }

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) {
            return $period;
        }

        $input['id_tahun'] = (int) $period['selected']['id'];
        $filter = $this->caseFilter($input);
        if (! $filter['success']) {
            return $filter;
        }

        $limit = max(1, min(200, (int) ($input['limit'] ?? 50)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $ids = $scope['student_ids'];

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $this->canManage($userId),
            'tahun_aktif' => $period['active'],
            'tahun_dipilih' => $period['selected'],
            'tahun_options' => $period['options'],
            'rows' => $this->kasusModel->getPaged($filter['filter'], $ids, $limit, $offset),
            'total' => $this->kasusModel->countFiltered($filter['filter'], $ids),
            'limit' => $limit,
            'offset' => $offset,
            'pelanggaran' => $this->kasusModel->getPelanggaranOptions(),
        ];
    }

    public function getKasusDetail(int $userId, int $id): array
    {
        $kasus = $this->kasusModel->getDetailById($id);
        if ($kasus === null) {
            return $this->fail('NOT_FOUND', 'Catatan Pelanggaran tidak ditemukan.');
        }

        $scope = $this->scopeService->resolveStudentIds('bk_kasus.view', $userId);
        if (! $scope['success']) {
            return $scope;
        }

        if (! $this->scopeService->studentAllowed($scope['student_ids'], (int) $kasus['id_siswa'])) {
            return $this->fail('FORBIDDEN', 'Catatan Pelanggaran berada di luar scope Anda.');
        }

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $this->canManage($userId),
            'kasus' => $kasus,
            'tindak_lanjut' => $this->tindakLanjutModel->getByKasus($id),
            'tindak_lanjut_options' => self::TINDAK_LANJUT,
        ];
    }

    public function createKasus(int $userId, array $input): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak membuat Catatan Pelanggaran.');
        }

        $active = $this->periodContext->active();
        if ($active === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $validated = $this->validateKasusPayload($input, null);
        if (! $validated['success']) {
            return $validated;
        }

        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $data = $validated['data'];
        $data['id_tahun'] = (int) $active['id'];
        $data['id_guru_input'] = $this->scopeService->userGuruId($userId);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['updated_by'] = $userId;

        $id = $this->kasusModel->insert($data);
        $this->log($userId, 'CREATE', 'BK Kasus', "Membuat Catatan Pelanggaran #{$id} siswa #{$data['id_siswa']}");

        return [
            'success' => true,
            'message' => 'Catatan Pelanggaran berhasil disimpan. Silakan lengkapi tindak lanjut bila diperlukan.',
            'id' => $id,
        ];
    }

    public function updateKasus(int $userId, int $id, array $input): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak memperbarui Catatan Pelanggaran.');
        }

        $existing = $this->kasusModel->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'Catatan Pelanggaran tidak ditemukan.');
        }

        $validated = $this->validateKasusPayload($input, $existing);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['updated_at'] = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $data['updated_by'] = $userId;

        if (! $this->kasusModel->update($id, $data)) {
            return $this->fail('UPDATE_FAILED', 'Catatan Pelanggaran gagal diperbarui.');
        }

        $this->log($userId, 'UPDATE', 'BK Kasus', "Memperbarui Catatan Pelanggaran #{$id}");

        return ['success' => true, 'message' => 'Catatan Pelanggaran berhasil diperbarui.'];
    }

    public function deleteKasus(int $userId, int $id): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak menghapus Catatan Pelanggaran.');
        }

        $existing = $this->kasusModel->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'Catatan Pelanggaran tidak ditemukan.');
        }

        if (! $this->kasusModel->delete($id)) {
            return $this->fail('DELETE_FAILED', 'Catatan Pelanggaran gagal dihapus.');
        }

        $this->log($userId, 'DELETE', 'BK Kasus', "Menghapus Catatan Pelanggaran #{$id} beserta tindak lanjutnya");

        return ['success' => true, 'message' => 'Catatan Pelanggaran dan riwayat tindak lanjutnya berhasil dihapus.'];
    }

    public function createTindakLanjut(int $userId, int $idKasus, array $input): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak menambah tindak lanjut.');
        }

        if ($this->kasusModel->getById($idKasus) === null) {
            return $this->fail('NOT_FOUND', 'Catatan Pelanggaran tidak ditemukan.');
        }

        $validated = $this->validateTindakLanjut($input);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['id_kasus'] = $idKasus;
        $data['id_user_input'] = $userId;
        $id = $this->tindakLanjutModel->insert($data, true);

        if ($id === false) {
            return $this->fail('SAVE_FAILED', implode(' ', $this->tindakLanjutModel->errors()) ?: 'Tindak lanjut gagal disimpan.');
        }

        $this->log($userId, 'CREATE', 'BK Tindak Lanjut', "Menambahkan tindak lanjut #{$id} pada pelanggaran #{$idKasus}");

        return ['success' => true, 'message' => 'Tindak lanjut berhasil disimpan.', 'id' => (int) $id];
    }

    public function updateTindakLanjut(int $userId, int $id, array $input): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak memperbarui tindak lanjut.');
        }

        $existing = $this->tindakLanjutModel->find($id);
        if ($existing === null) {
            return $this->fail('NOT_FOUND', 'Tindak lanjut tidak ditemukan.');
        }

        $validated = $this->validateTindakLanjut($input);
        if (! $validated['success']) {
            return $validated;
        }

        if (! $this->tindakLanjutModel->update($id, $validated['data'])) {
            return $this->fail('UPDATE_FAILED', implode(' ', $this->tindakLanjutModel->errors()) ?: 'Tindak lanjut gagal diperbarui.');
        }

        $this->log($userId, 'UPDATE', 'BK Tindak Lanjut', "Memperbarui tindak lanjut #{$id}");

        return ['success' => true, 'message' => 'Tindak lanjut berhasil diperbarui.'];
    }

    public function getTop20(int $userId): array
    {
        $scope = $this->scopeService->resolveStudentIds('bk_kasus.view', $userId);
        if (! $scope['success']) {
            return $scope;
        }

        if ($scope['scope'] === 'DIRI_SENDIRI') {
            return $this->fail('FORBIDDEN', 'Top 20 tidak tersedia untuk Siswa.');
        }

        return ['success' => true, 'scope' => $scope['scope'], 'rows' => $this->kasusModel->getTop20($scope['student_ids'])];
    }

    public function getKasusExport(int $userId, array $input): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Export Catatan Pelanggaran hanya untuk pengelola BK.');
        }

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) {
            return $period;
        }
        $input['id_tahun'] = (int) $period['selected']['id'];

        $filter = $this->caseFilter($input);
        if (! $filter['success']) {
            return $filter;
        }

        $total = $this->kasusModel->countFiltered($filter['filter'], null);
        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail('EXPORT_TOO_LARGE', 'Data melebihi 50.000 baris. Persempit filter.');
        }

        return [
            'success' => true,
            'rows' => $this->kasusModel->getForExport($filter['filter'], null, self::MAX_EXPORT_ROWS),
            'filter' => $filter['filter'],
            'tahun_dipilih' => $period['selected'],
        ];
    }

    public function listPelanggaran(): array
    {
        return $this->pelanggaranModel->orderBy('kategori', 'ASC')->orderBy('nama_pelanggaran', 'ASC')->findAll();
    }

    public function createPelanggaran(int $userId, array $input): array
    {
        $auth = $this->requireMasterManage($userId);
        if (! $auth['success']) {
            return $auth;
        }

        $data = $this->validatePelanggaran($input);
        if (! $data['success']) {
            return $data;
        }

        try {
            $id = (int) $this->pelanggaranModel->insert($data['data'], true);
        } catch (\Throwable $e) {
            return $this->fail('DUPLICATE', 'Nama pelanggaran sudah digunakan.');
        }

        $this->log($userId, 'CREATE', 'BK Pelanggaran', "Membuat Master Pelanggaran #{$id}");
        return ['success' => true, 'message' => 'Master Pelanggaran berhasil dibuat.', 'id' => $id];
    }

    public function updatePelanggaran(int $userId, int $id, array $input): array
    {
        $auth = $this->requireMasterManage($userId);
        if (! $auth['success']) {
            return $auth;
        }
        if (! $this->pelanggaranModel->find($id)) {
            return $this->fail('NOT_FOUND', 'Master Pelanggaran tidak ditemukan.');
        }

        $data = $this->validatePelanggaran($input);
        if (! $data['success']) {
            return $data;
        }

        try {
            $this->pelanggaranModel->update($id, $data['data']);
        } catch (\Throwable $e) {
            return $this->fail('DUPLICATE', 'Nama pelanggaran sudah digunakan.');
        }

        $this->log($userId, 'UPDATE', 'BK Pelanggaran', "Memperbarui Master Pelanggaran #{$id}");
        return ['success' => true, 'message' => 'Master Pelanggaran berhasil diperbarui.'];
    }

    public function deletePelanggaran(int $userId, int $id): array
    {
        $auth = $this->requireMasterManage($userId);
        if (! $auth['success']) {
            return $auth;
        }
        if (! $this->pelanggaranModel->find($id)) {
            return $this->fail('NOT_FOUND', 'Master Pelanggaran tidak ditemukan.');
        }

        try {
            $deleted = $this->pelanggaranModel->delete($id);
            if ($deleted === false) {
                return $this->fail('IN_USE', 'Pelanggaran sudah digunakan pada Catatan Pelanggaran dan tidak dapat dihapus.');
            }
        } catch (DatabaseException $e) {
            return $this->fail('IN_USE', 'Pelanggaran sudah digunakan pada Catatan Pelanggaran dan tidak dapat dihapus.');
        }

        $this->log($userId, 'DELETE', 'BK Pelanggaran', "Menghapus Master Pelanggaran #{$id}");
        return ['success' => true, 'message' => 'Master Pelanggaran berhasil dihapus.'];
    }

    private function validateKasusPayload(array $input, ?array $existing): array
    {
        $idSiswa = (int) ($input['id_siswa'] ?? 0);
        $idPelanggaran = (int) ($input['id_pelanggaran'] ?? 0);
        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $keterangan = trim((string) ($input['keterangan'] ?? ''));

        if ($idSiswa <= 0 || $idPelanggaran <= 0 || ! $this->validDate($tanggal)) {
            return $this->fail('VALIDATION', 'Siswa, pelanggaran, dan tanggal wajib valid.');
        }

        $siswa = db_connect()->table('siswa')->select('id, status_aktif, deleted_at')->where('id', $idSiswa)->get()->getRowArray();
        if (! $siswa || ! empty($siswa['deleted_at'])) {
            return $this->fail('INVALID_TARGET', 'Siswa tidak ditemukan atau sudah dihapus.');
        }

        $sameHistoricalStudent = $existing !== null && (int) $existing['id_siswa'] === $idSiswa;
        if (! $sameHistoricalStudent && (string) $siswa['status_aktif'] !== 'Aktif') {
            return $this->fail('INVALID_TARGET', 'Catatan Pelanggaran baru hanya dapat diberikan kepada siswa aktif.');
        }

        if (db_connect()->table('ref_pelanggaran')->where('id', $idPelanggaran)->countAllResults() < 1) {
            return $this->fail('INVALID_TARGET', 'Pelanggaran tidak ditemukan.');
        }

        return [
            'success' => true,
            'data' => [
                'id_siswa' => $idSiswa,
                'id_pelanggaran' => $idPelanggaran,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
            ],
        ];
    }

    private function validateTindakLanjut(array $input): array
    {
        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $tindakLanjut = trim((string) ($input['tindak_lanjut'] ?? ''));
        $keterangan = trim((string) ($input['keterangan'] ?? ''));

        if (! $this->validDate($tanggal)) {
            return $this->fail('VALIDATION', 'Tanggal tindak lanjut tidak valid.');
        }
        if (! in_array($tindakLanjut, self::TINDAK_LANJUT, true)) {
            return $this->fail('VALIDATION', 'Jenis tindak lanjut tidak valid.');
        }

        return [
            'success' => true,
            'data' => [
                'tanggal' => $tanggal,
                'tindak_lanjut' => $tindakLanjut,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
            ],
        ];
    }

    private function caseFilter(array $input): array
    {
        $kategori = trim((string) ($input['kategori'] ?? ''));
        $tanggalMulai = trim((string) ($input['tanggal_mulai'] ?? ''));
        $tanggalSelesai = trim((string) ($input['tanggal_selesai'] ?? ''));
        $idTahun = (int) ($input['id_tahun'] ?? 0);

        if ($idTahun <= 0) {
            return $this->fail('INVALID_PERIOD', 'Tahun Ajaran wajib dipilih.');
        }
        if ($kategori !== '' && ! in_array($kategori, self::KATEGORI, true)) {
            return $this->fail('VALIDATION', 'Kategori tidak valid.');
        }
        if ($tanggalMulai !== '' && ! $this->validDate($tanggalMulai)) {
            return $this->fail('VALIDATION', 'Tanggal awal tidak valid.');
        }
        if ($tanggalSelesai !== '' && ! $this->validDate($tanggalSelesai)) {
            return $this->fail('VALIDATION', 'Tanggal akhir tidak valid.');
        }
        if ($tanggalMulai !== '' && $tanggalSelesai !== '' && $tanggalMulai > $tanggalSelesai) {
            return $this->fail('VALIDATION', 'Tanggal awal tidak boleh melewati tanggal akhir.');
        }

        return [
            'success' => true,
            'filter' => [
                'id_tahun' => $idTahun,
                'id_pelanggaran' => (int) ($input['id_pelanggaran'] ?? 0) ?: null,
                'kategori' => $kategori !== '' ? $kategori : null,
                'tanggal_mulai' => $tanggalMulai !== '' ? $tanggalMulai : null,
                'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
                'search' => trim((string) ($input['search'] ?? '')) ?: null,
            ],
        ];
    }

    private function validatePelanggaran(array $input): array
    {
        $nama = trim((string) ($input['nama_pelanggaran'] ?? ''));
        $kategori = trim((string) ($input['kategori'] ?? ''));

        if ($nama === '' || mb_strlen($nama) > 150) {
            return $this->fail('VALIDATION', 'Nama pelanggaran wajib diisi maksimal 150 karakter.');
        }
        if (! in_array($kategori, self::KATEGORI, true)) {
            return $this->fail('VALIDATION', 'Kategori pelanggaran tidak valid.');
        }

        return [
            'success' => true,
            'data' => [
                'nama_pelanggaran' => $nama,
                'kategori' => $kategori,
                'poin' => 0,
            ],
        ];
    }

    private function canManage(int $userId): bool
    {
        return $this->authService->resolveScope('bk_kasus.manage', $userId) === 'SEMUA';
    }

    private function requireMasterManage(int $userId): array
    {
        if ($this->authService->resolveScope('bk_pelanggaran_master.manage', $userId) !== 'SEMUA') {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak mengelola Master Pelanggaran.');
        }
        return ['success' => true];
    }

    private function validDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();
        return $d !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $d->format('Y-m-d') === $date;
    }

    private function log(int $userId, string $aksi, string $modul, string $keterangan): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $aksi,
            'modul' => $modul,
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
