<?php

namespace App\Services;

use App\Models\LaporanJurnalModel;
use CodeIgniter\I18n\Time;

/**
 * LaporanJurnalService
 *
 * Scope:
 * - SEMUA
 * - DIRI_SENDIRI
 *
 * Wali tetap DIRI_SENDIRI.
 */
class LaporanJurnalService
{
    private const TZ = 'Asia/Jakarta';
    private const HARI = [
        'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu',
    ];
    private const STATUS = ['Hadir', 'Izin', 'Sakit'];

    protected LaporanJurnalModel $model;
    protected AuthService $authService;

    public function __construct()
    {
        $this->model = new LaporanJurnalModel();
        $this->authService = new AuthService();
    }

    public function getOptions(int $userId, ?int $idTahun = null): array
    {
        $scope = $this->authService->resolveScope('laporan_jurnal.view', $userId);

        if (!in_array($scope, ['SEMUA', 'DIRI_SENDIRI'], true)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak melihat Laporan Jurnal.');
        }

        $tahunAktif = $this->model->getTahunAktif();

        if ($tahunAktif === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $selectedTahun = $idTahun ?: (int) $tahunAktif['id'];
        $idGuru = null;

        if ($scope === 'DIRI_SENDIRI') {
            $idGuru = $this->getUserGuruId($userId);

            if ($idGuru === null) {
                return $this->fail(
                    'NO_GURU_IDENTITY',
                    'User tidak memiliki identitas Guru untuk laporan Jurnal diri.'
                );
            }
        }

        return [
            'success' => true,
            'scope' => $scope,
            'id_guru_fixed' => $idGuru,
            'tahun' => $this->model->getTahunOptions(),
            'selected_tahun' => $selectedTahun,
            'guru' => $scope === 'SEMUA'
                ? $this->model->getGuruOptions($selectedTahun)
                : [],
            'kelas' => $this->model->getKelasOptions($selectedTahun, $idGuru),
            'hari' => self::HARI,
            'status' => self::STATUS,
        ];
    }

    public function getPaged(int $userId, array $input): array
    {
        $resolved = $this->resolveFilter($userId, $input, 'laporan_jurnal.view');

        if (!$resolved['success']) {
            return $resolved;
        }

        $limit = max(1, min(200, (int) ($input['limit'] ?? 50)));
        $offset = max(0, (int) ($input['offset'] ?? 0));

        $rows = $this->model->getPaged($resolved['filter'], $limit, $offset);
        $total = $this->model->countFiltered($resolved['filter']);

        return [
            'success' => true,
            'scope' => $resolved['scope'],
            'filter' => $resolved['filter'],
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function getExportData(int $userId, array $input): array
    {
        $exportScope = $this->authService->resolveScope(
            'laporan_jurnal.export',
            $userId
        );

        $viewScope = $this->authService->resolveScope(
            'laporan_jurnal.view',
            $userId
        );

        if (!in_array($exportScope, ['SEMUA', 'DIRI_SENDIRI'], true)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak export Jurnal.');
        }

        if (!in_array($viewScope, ['SEMUA', 'DIRI_SENDIRI'], true)) {
            return $this->fail(
                'FORBIDDEN_VIEW',
                'Export Jurnal membutuhkan hak melihat Laporan Jurnal.'
            );
        }

        $resolved = $this->resolveFilter($userId, $input, 'laporan_jurnal.export');

        if (!$resolved['success']) {
            return $resolved;
        }

        return [
            'success' => true,
            'scope' => $resolved['scope'],
            'filter' => $resolved['filter'],
            'rows' => $this->model->getForExport($resolved['filter']),
        ];
    }

    public function defaultDates(): array
    {
        $now = Time::now(self::TZ);

        return [
            'tanggal_mulai' => $now->format('Y-m-01'),
            'tanggal_selesai' => $now->format('Y-m-d'),
        ];
    }

    private function resolveFilter(
        int $userId,
        array $input,
        string $permission
    ): array {
        $scope = $this->authService->resolveScope($permission, $userId);

        if (!in_array($scope, ['SEMUA', 'DIRI_SENDIRI'], true)) {
            return $this->fail('FORBIDDEN', 'Scope Laporan Jurnal tidak valid.');
        }

        $idTahun = max(0, (int) ($input['id_tahun'] ?? 0));
        $idKelas = max(0, (int) ($input['id_kelas'] ?? 0));
        $idGuru = max(0, (int) ($input['id_guru'] ?? 0));
        $hari = trim((string) ($input['hari'] ?? ''));
        $status = trim((string) ($input['status'] ?? ''));
        $tanggalMulai = trim((string) ($input['tanggal_mulai'] ?? ''));
        $tanggalSelesai = trim((string) ($input['tanggal_selesai'] ?? ''));

        if ($idTahun <= 0) {
            $aktif = $this->model->getTahunAktif();
            $idTahun = (int) ($aktif['id'] ?? 0);
        }

        if ($idTahun <= 0) {
            return $this->fail('INVALID_YEAR', 'Tahun Ajaran tidak valid.');
        }

        if ($hari !== '' && !in_array($hari, self::HARI, true)) {
            return $this->fail('INVALID_DAY', 'Filter hari tidak valid.');
        }

        if ($status !== '' && !in_array($status, self::STATUS, true)) {
            return $this->fail('INVALID_STATUS', 'Filter status tidak valid.');
        }

        if ($tanggalMulai !== '' && !$this->validDate($tanggalMulai)) {
            return $this->fail('INVALID_DATE', 'Tanggal awal tidak valid.');
        }

        if ($tanggalSelesai !== '' && !$this->validDate($tanggalSelesai)) {
            return $this->fail('INVALID_DATE', 'Tanggal akhir tidak valid.');
        }

        if (
            $tanggalMulai !== ''
            && $tanggalSelesai !== ''
            && $tanggalMulai > $tanggalSelesai
        ) {
            return $this->fail(
                'INVALID_PERIOD',
                'Tanggal awal tidak boleh lebih besar dari tanggal akhir.'
            );
        }

        if ($scope === 'DIRI_SENDIRI') {
            $selfGuru = $this->getUserGuruId($userId);

            if ($selfGuru === null) {
                return $this->fail(
                    'NO_GURU_IDENTITY',
                    'User tidak mempunyai identitas Guru.'
                );
            }

            if ($idGuru > 0 && $idGuru !== $selfGuru) {
                return $this->fail(
                    'FORBIDDEN',
                    'Guru hanya dapat melihat Jurnal dirinya sendiri.'
                );
            }

            $idGuru = $selfGuru;
        }

        return [
            'success' => true,
            'scope' => $scope,
            'filter' => [
                'id_tahun' => $idTahun,
                'id_guru' => $idGuru > 0 ? $idGuru : null,
                'id_kelas' => $idKelas > 0 ? $idKelas : null,
                'hari' => $hari !== '' ? $hari : null,
                'status' => $status !== '' ? $status : null,
                'tanggal_mulai' => $tanggalMulai !== '' ? $tanggalMulai : null,
                'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
            ],
        ];
    }

    private function getUserGuruId(int $userId): ?int
    {
        $row = db_connect()
            ->table('users')
            ->select('id_guru')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        $id = (int) ($row['id_guru'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function validDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();

        return $d !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $d->format('Y-m-d') === $date;
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
