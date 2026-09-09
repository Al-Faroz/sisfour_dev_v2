<?php

namespace App\Services;

use App\Models\BKPrestasiModel;
use CodeIgniter\I18n\Time;

class PrestasiService
{
    private const TZ = 'Asia/Jakarta';
    private const TINGKAT = [
        'Madrasah',
        'Kecamatan',
        'Kabupaten',
        'Provinsi',
        'Nasional',
        'Internasional',
    ];
    private const MAX_EXPORT_ROWS = 50000;

    protected BKPrestasiModel $model;
    protected AuthService $authService;
    protected BkScopeService $scopeService;

    public function __construct()
    {
        $this->model = new BKPrestasiModel();
        $this->authService = new AuthService();
        $this->scopeService = new BkScopeService();
    }

    public function getPage(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds('prestasi.view', $userId);

        if (! $scope['success']) {
            return $scope;
        }

        $filter = $this->filter($input);

        if (! $filter['success']) {
            return $filter;
        }

        $limit = max(1, min(200, (int) ($input['limit'] ?? 50)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $ids = $scope['student_ids'];

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $this->authService->resolveScope('prestasi.manage', $userId) === 'SEMUA',
            'tingkat_options' => self::TINGKAT,
            'rows' => $this->model->getPaged($filter['filter'], $ids, $limit, $offset),
            'total' => $this->model->countFiltered($filter['filter'], $ids),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function create(int $userId, array $input): array
    {
        $auth = $this->requireManage($userId);

        if (! $auth['success']) {
            return $auth;
        }

        $validated = $this->validate($input, null);

        if (! $validated['success']) {
            return $validated;
        }

        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $data = $validated['data'];
        $data['id_guru_input'] = $this->scopeService->userGuruId($userId);
        $data['created_at'] = $now;

        $id = $this->model->insert($data);
        $this->log($userId, 'CREATE', "Membuat Prestasi #{$id} siswa #{$data['id_siswa']}");

        return [
            'success' => true,
            'message' => 'Prestasi berhasil disimpan.',
            'id' => $id,
        ];
    }

    public function update(int $userId, int $id, array $input): array
    {
        $auth = $this->requireManage($userId);

        if (! $auth['success']) {
            return $auth;
        }

        $existing = $this->model->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'Prestasi tidak ditemukan.');
        }

        $validated = $this->validate($input, $existing);

        if (! $validated['success']) {
            return $validated;
        }

        $this->model->update($id, $validated['data']);
        $this->log($userId, 'UPDATE', "Memperbarui Prestasi #{$id}");

        return [
            'success' => true,
            'message' => 'Prestasi berhasil diperbarui.',
        ];
    }

    public function delete(int $userId, int $id): array
    {
        $auth = $this->requireManage($userId);

        if (! $auth['success']) {
            return $auth;
        }

        if (! $this->model->getById($id)) {
            return $this->fail('NOT_FOUND', 'Prestasi tidak ditemukan.');
        }

        $this->model->delete($id);
        $this->log($userId, 'DELETE', "Menghapus Prestasi #{$id}");

        return [
            'success' => true,
            'message' => 'Prestasi berhasil dihapus.',
        ];
    }

    public function getExport(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds('prestasi.view', $userId);

        if (! $scope['success']) {
            return $scope;
        }

        $filter = $this->filter($input);

        if (! $filter['success']) {
            return $filter;
        }

        $total = $this->model->countFiltered($filter['filter'], $scope['student_ids']);

        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail('EXPORT_TOO_LARGE', 'Data melebihi 50.000 baris. Persempit filter.');
        }

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'filter' => $filter['filter'],
            'rows' => $this->model->getForExport(
                $filter['filter'],
                $scope['student_ids'],
                self::MAX_EXPORT_ROWS
            ),
        ];
    }

    private function validate(array $input, ?array $existing): array
    {
        $idSiswa = (int) ($input['id_siswa'] ?? 0);
        $nama = trim((string) ($input['nama_prestasi'] ?? ''));
        $tingkat = trim((string) ($input['tingkat'] ?? ''));
        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $penyelenggara = trim((string) ($input['penyelenggara'] ?? ''));
        $keterangan = trim((string) ($input['keterangan'] ?? ''));

        $siswa = db_connect()
            ->table('siswa')
            ->select('id, status_aktif, deleted_at')
            ->where('id', $idSiswa)
            ->get()
            ->getRowArray();

        if ($idSiswa <= 0 || ! $siswa || ! empty($siswa['deleted_at'])) {
            return $this->fail('VALIDATION', 'Siswa tidak valid.');
        }

        $sameHistoricalStudent = $existing !== null
            && (int) ($existing['id_siswa'] ?? 0) === $idSiswa;

        if (! $sameHistoricalStudent && (string) $siswa['status_aktif'] !== 'Aktif') {
            return $this->fail('VALIDATION', 'Prestasi baru hanya dapat diberikan kepada siswa aktif.');
        }

        if ($nama === '' || mb_strlen($nama) > 200) {
            return $this->fail('VALIDATION', 'Nama prestasi wajib diisi maksimal 200 karakter.');
        }

        if (! in_array($tingkat, self::TINGKAT, true)) {
            return $this->fail('VALIDATION', 'Tingkat prestasi tidak valid.');
        }

        if (! $this->validDate($tanggal)) {
            return $this->fail('VALIDATION', 'Tanggal prestasi tidak valid.');
        }

        return [
            'success' => true,
            'data' => [
                'id_siswa' => $idSiswa,
                'nama_prestasi' => $nama,
                'tingkat' => $tingkat,
                'tanggal' => $tanggal,
                'penyelenggara' => $penyelenggara !== '' ? $penyelenggara : null,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
            ],
        ];
    }

    private function filter(array $input): array
    {
        $tingkat = trim((string) ($input['tingkat'] ?? ''));
        $tanggalMulai = trim((string) ($input['tanggal_mulai'] ?? ''));
        $tanggalSelesai = trim((string) ($input['tanggal_selesai'] ?? ''));

        if ($tingkat !== '' && ! in_array($tingkat, self::TINGKAT, true)) {
            return $this->fail('VALIDATION', 'Tingkat prestasi tidak valid.');
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
                'tingkat' => $tingkat !== '' ? $tingkat : null,
                'tanggal_mulai' => $tanggalMulai !== '' ? $tanggalMulai : null,
                'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
                'search' => trim((string) ($input['search'] ?? '')) ?: null,
            ],
        ];
    }

    private function requireManage(int $userId): array
    {
        if ($this->authService->resolveScope('prestasi.manage', $userId) !== 'SEMUA') {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak mengelola Prestasi.');
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

    private function log(int $userId, string $aksi, string $keterangan): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $aksi,
            'modul' => 'Prestasi',
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
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
