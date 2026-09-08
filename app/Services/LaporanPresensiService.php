<?php

namespace App\Services;

use App\Models\LaporanPresensiModel;
use CodeIgniter\I18n\Time;

/**
 * LaporanPresensiService
 *
 * Scope Matrix/Export:
 * - SEMUA
 * - KELAS_DIAMPU
 *
 * KELAS_TERJADWAL tidak berlaku untuk laporan.
 */
class LaporanPresensiService
{
    private const TZ = 'Asia/Jakarta';

    protected LaporanPresensiModel $model;
    protected AuthService $authService;

    public function __construct()
    {
        $this->model = new LaporanPresensiModel();
        $this->authService = new AuthService();
    }

    public function getFilterOptions(int $userId, ?int $idTahun = null): array
    {
        $scope = $this->authService->resolveScope('laporan_matrix.view', $userId);

        if (!in_array($scope, ['SEMUA', 'KELAS_DIAMPU'], true)) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak melihat Matrix Presensi.',
            ];
        }

        $tahunAktif = $this->model->getTahunAktif();

        if ($tahunAktif === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        if ($scope === 'KELAS_DIAMPU') {
            // Hak Wali mengikuti mapping aktif saat ini.
            $idTahun = (int) $tahunAktif['id'];
            $idGuru = $this->getUserGuruId($userId);
            $kelasIds = $this->authService->getKelasDiampu($idGuru, $idTahun);

            return [
                'success' => true,
                'scope' => $scope,
                'tahun' => [$tahunAktif],
                'selected_tahun' => $idTahun,
                'kelas' => $this->model->getKelasOptions($idTahun, $kelasIds),
            ];
        }

        $selectedTahun = $idTahun ?: (int) $tahunAktif['id'];
        $tahun = $this->model->getTahun($selectedTahun);

        if ($tahun === null) {
            $selectedTahun = (int) $tahunAktif['id'];
        }

        return [
            'success' => true,
            'scope' => $scope,
            'tahun' => $this->model->getTahunOptions(),
            'selected_tahun' => $selectedTahun,
            'kelas' => $this->model->getKelasOptions($selectedTahun),
        ];
    }

    public function getMatrix(
        int $userId,
        int $idTahun,
        int $idKelas,
        string $bulan
    ): array {
        $scopeResult = $this->authorizeKelas(
            $userId,
            'laporan_matrix.view',
            $idTahun,
            $idKelas
        );

        if (!$scopeResult['success']) {
            return $scopeResult;
        }

        $period = $this->resolveMonthPeriod($bulan);

        if ($period === null) {
            return $this->fail('INVALID_MONTH', 'Format bulan harus YYYY-MM.');
        }

        $tahun = $this->model->getTahun($idTahun);
        $kelas = $this->model->getKelas($idKelas, $idTahun);

        if ($tahun === null || $kelas === null) {
            return $this->fail('INVALID_FILTER', 'Tahun Ajaran atau kelas tidak valid.');
        }

        $members = $this->model->getMembershipPeriod(
            $idTahun,
            $idKelas,
            $period['mulai'],
            $period['selesai']
        );

        $presensi = $this->model->getPresensiPeriod(
            $idTahun,
            $idKelas,
            $period['mulai'],
            $period['selesai'],
            'Sesi Awal'
        );

        $rows = $this->buildMonthlyMatrix($members, $presensi, $period);

        return [
            'success' => true,
            'scope' => $scopeResult['scope'],
            'tahun' => $tahun,
            'kelas' => $kelas,
            'bulan' => $bulan,
            'period' => $period,
            'rows' => $rows,
        ];
    }

    public function getMonthlyExportData(
        int $userId,
        int $idTahun,
        int $idKelas,
        string $bulan
    ): array {
        $scopeResult = $this->authorizeExport($userId, $idTahun, $idKelas);

        if (!$scopeResult['success']) {
            return $scopeResult;
        }

        $period = $this->resolveMonthPeriod($bulan);

        if ($period === null) {
            return $this->fail('INVALID_MONTH', 'Format bulan harus YYYY-MM.');
        }

        $tahun = $this->model->getTahun($idTahun);
        $kelas = $this->model->getKelas($idKelas, $idTahun);

        if ($tahun === null || $kelas === null) {
            return $this->fail('INVALID_FILTER', 'Tahun Ajaran atau kelas tidak valid.');
        }

        $members = $this->model->getMembershipPeriod(
            $idTahun,
            $idKelas,
            $period['mulai'],
            $period['selesai']
        );

        $allSessions = $this->model->getPresensiPeriod(
            $idTahun,
            $idKelas,
            $period['mulai'],
            $period['selesai'],
            null
        );

        return [
            'success' => true,
            'scope' => $scopeResult['scope'],
            'tahun' => $tahun,
            'kelas' => $kelas,
            'bulan' => $bulan,
            'period' => $period,
            'rows' => $this->buildMonthlyExport($members, $allSessions, $period),
        ];
    }

    public function getSemesterExportData(
        int $userId,
        int $idTahun,
        int $idKelas
    ): array {
        $scopeResult = $this->authorizeExport($userId, $idTahun, $idKelas);

        if (!$scopeResult['success']) {
            return $scopeResult;
        }

        $tahun = $this->model->getTahun($idTahun);
        $kelas = $this->model->getKelas($idKelas, $idTahun);

        if ($tahun === null || $kelas === null) {
            return $this->fail('INVALID_FILTER', 'Tahun Ajaran atau kelas tidak valid.');
        }

        $period = $this->semesterPeriod($tahun);

        $members = $this->model->getMembershipPeriod(
            $idTahun,
            $idKelas,
            $period['mulai'],
            $period['selesai']
        );

        $aggregate = $this->model->getSemesterAggregate(
            $idTahun,
            $idKelas,
            $period['mulai'],
            $period['selesai']
        );

        return [
            'success' => true,
            'scope' => $scopeResult['scope'],
            'tahun' => $tahun,
            'kelas' => $kelas,
            'period' => $period,
            'months' => $period['months'],
            'rows' => $this->buildSemesterRows($members, $aggregate, $period['months']),
        ];
    }

    public function defaultMonth(): string
    {
        return Time::now(self::TZ)->format('Y-m');
    }

    private function authorizeExport(int $userId, int $idTahun, int $idKelas): array
    {
        $exportScope = $this->authService->resolveScope(
            'laporan_export.generate',
            $userId
        );

        $viewScope = $this->authService->resolveScope(
            'laporan_matrix.view',
            $userId
        );

        if (!in_array($exportScope, ['SEMUA', 'KELAS_DIAMPU'], true)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak export Presensi.');
        }

        if (!in_array($viewScope, ['SEMUA', 'KELAS_DIAMPU'], true)) {
            return $this->fail(
                'FORBIDDEN_VIEW',
                'Export membutuhkan hak melihat Matrix Presensi.'
            );
        }

        return $this->authorizeKelas(
            $userId,
            'laporan_export.generate',
            $idTahun,
            $idKelas
        );
    }

    private function authorizeKelas(
        int $userId,
        string $permission,
        int $idTahun,
        int $idKelas
    ): array {
        $scope = $this->authService->resolveScope($permission, $userId);

        if ($scope === 'SEMUA') {
            return ['success' => true, 'scope' => 'SEMUA'];
        }

        if ($scope !== 'KELAS_DIAMPU') {
            return $this->fail('FORBIDDEN', 'Kelas berada di luar scope laporan Anda.');
        }

        $tahunAktif = $this->model->getTahunAktif();

        if ($tahunAktif === null || (int) $tahunAktif['id'] !== $idTahun) {
            return $this->fail(
                'OUTSIDE_WALI_YEAR',
                'Wali hanya dapat mengakses kelas Wali pada Tahun Ajaran aktif.'
            );
        }

        $idGuru = $this->getUserGuruId($userId);
        $kelasIds = $this->authService->getKelasDiampu($idGuru, $idTahun);

        if (!in_array($idKelas, $kelasIds, true)) {
            return $this->fail('FORBIDDEN', 'Kelas bukan kelas Wali aktif Anda.');
        }

        return ['success' => true, 'scope' => 'KELAS_DIAMPU'];
    }

    private function getUserGuruId(int $userId): ?int
    {
        $db = db_connect();
        $row = $db->table('users')
            ->select('id_guru')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        $id = (int) ($row['id_guru'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function resolveMonthPeriod(string $bulan): ?array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            return null;
        }

        $start = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $bulan . '-01',
            new \DateTimeZone(self::TZ)
        );

        if ($start === false) {
            return null;
        }

        $end = $start->modify('last day of this month');

        return [
            'mulai' => $start->format('Y-m-d'),
            'selesai' => $end->format('Y-m-d'),
            'days' => (int) $end->format('j'),
        ];
    }

    private function semesterPeriod(array $tahun): array
    {
        [$yearStart, $yearEnd] = array_map(
            'intval',
            explode('/', (string) $tahun['nama_tahun']) + [0, 0]
        );

        if ((string) $tahun['semester'] === 'Ganjil') {
            $months = [7, 8, 9, 10, 11, 12];

            return [
                'mulai' => sprintf('%04d-07-01', $yearStart),
                'selesai' => sprintf('%04d-12-31', $yearStart),
                'months' => $months,
            ];
        }

        $months = [1, 2, 3, 4, 5, 6];

        return [
            'mulai' => sprintf('%04d-01-01', $yearEnd),
            'selesai' => sprintf('%04d-06-30', $yearEnd),
            'months' => $months,
        ];
    }

    private function buildMonthlyMatrix(
        array $members,
        array $presensi,
        array $period
    ): array {
        $members = $this->mergeMemberships($members);
        $map = [];
        $snapshot = [];

        foreach ($presensi as $row) {
            $id = (int) ($row['id_siswa'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $tanggal = (string) $row['tanggal'];
            $day = (int) substr($tanggal, 8, 2);
            $map[$id][$day] = $this->statusCode((string) $row['status']);

            if (
                !isset($snapshot[$id])
                && trim((string) ($row['nama_siswa_snapshot'] ?? '')) !== ''
            ) {
                $snapshot[$id] = (string) $row['nama_siswa_snapshot'];
            }
        }

        $rows = [];
        $monthPrefix = substr((string) $period['mulai'], 0, 8);
        $days = (int) $period['days'];

        foreach ($members as $member) {
            $id = (int) $member['id_siswa'];
            $daysMap = [];
            $totals = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];

            for ($d = 1; $d <= $days; $d++) {
                $tanggal = $monthPrefix . str_pad((string) $d, 2, '0', STR_PAD_LEFT);

                // Membership adalah authoritative. Record stale/anomali di luar
                // periode keanggotaan tidak boleh ikut Matrix.
                if (!$this->isMembershipDate($member['intervals'], $tanggal)) {
                    $daysMap[$d] = '-';
                    continue;
                }

                $value = $map[$id][$d] ?? '-';
                $daysMap[$d] = $value;

                if (isset($totals[$value])) {
                    $totals[$value]++;
                }
            }

            $rows[] = [
                'id_siswa' => $id,
                'nisn' => (string) $member['nisn'],
                'nama' => $snapshot[$id] ?? (string) $member['nama'],
                'H' => $totals['H'],
                'S' => $totals['S'],
                'I' => $totals['I'],
                'A' => $totals['A'],
                'days' => $daysMap,
            ];
        }

        return $rows;
    }

    private function buildMonthlyExport(
        array $members,
        array $presensi,
        array $period
    ): array {
        $members = $this->mergeMemberships($members);
        $map = [];
        $snapshot = [];

        foreach ($presensi as $row) {
            $id = (int) ($row['id_siswa'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $day = (int) substr((string) $row['tanggal'], 8, 2);
            $sessionKey = (string) $row['sesi'] === 'Sesi Awal' ? 'AW' : 'AK';

            $map[$id][$day][$sessionKey] = $this->statusCode((string) $row['status']);

            if (
                !isset($snapshot[$id])
                && trim((string) ($row['nama_siswa_snapshot'] ?? '')) !== ''
            ) {
                $snapshot[$id] = (string) $row['nama_siswa_snapshot'];
            }
        }

        $rows = [];
        $monthPrefix = substr((string) $period['mulai'], 0, 8);
        $days = (int) $period['days'];

        foreach ($members as $member) {
            $id = (int) $member['id_siswa'];
            $totals = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
            $daysMap = [];

            for ($d = 1; $d <= $days; $d++) {
                $tanggal = $monthPrefix . str_pad((string) $d, 2, '0', STR_PAD_LEFT);

                if (!$this->isMembershipDate($member['intervals'], $tanggal)) {
                    $daysMap[$d] = ['AW' => '-', 'AK' => '-'];
                    continue;
                }

                $aw = $map[$id][$d]['AW'] ?? '-';
                $ak = $map[$id][$d]['AK'] ?? '-';

                if (isset($totals[$aw])) {
                    $totals[$aw]++;
                }

                $daysMap[$d] = [
                    'AW' => $aw,
                    'AK' => $ak,
                ];
            }

            $rows[] = [
                'id_siswa' => $id,
                'nisn' => (string) $member['nisn'],
                'nama' => $snapshot[$id] ?? (string) $member['nama'],
                'H' => $totals['H'],
                'S' => $totals['S'],
                'I' => $totals['I'],
                'A' => $totals['A'],
                'days' => $daysMap,
            ];
        }

        return $rows;
    }

    private function buildSemesterRows(array $members, array $aggregate, array $months): array
    {
        $members = $this->mergeMemberships($members);
        $agg = [];
        $snapshot = [];

        foreach ($aggregate as $row) {
            $id = (int) ($row['id_siswa'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $month = (int) $row['bulan'];
            $agg[$id][$month] = [
                'H' => (int) $row['hadir'],
                'S' => (int) $row['sakit'],
                'I' => (int) $row['izin'],
                'A' => (int) $row['alpha'],
            ];
            $snapshot[$id] = (string) $row['nama_siswa_snapshot'];
        }

        $rows = [];

        foreach ($members as $member) {
            $id = (int) $member['id_siswa'];
            $monthMap = [];
            $total = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];

            foreach ($months as $month) {
                $value = $agg[$id][$month] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
                $monthMap[$month] = $value;

                foreach ($total as $key => $_) {
                    $total[$key] += $value[$key];
                }
            }

            $rows[] = [
                'id_siswa' => $id,
                'nisn' => (string) $member['nisn'],
                'nama' => $snapshot[$id] ?? (string) $member['nama'],
                'H' => $total['H'],
                'S' => $total['S'],
                'I' => $total['I'],
                'A' => $total['A'],
                'months' => $monthMap,
            ];
        }

        return $rows;
    }

    /**
     * Gabungkan kemungkinan lebih dari satu interval membership siswa
     * agar Matrix/Export hanya menghasilkan satu baris per siswa.
     */
    private function mergeMemberships(array $members): array
    {
        $merged = [];

        foreach ($members as $member) {
            $id = (int) ($member['id_siswa'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            if (!isset($merged[$id])) {
                $merged[$id] = [
                    'id_siswa' => $id,
                    'nisn' => (string) ($member['nisn'] ?? ''),
                    'nama' => (string) ($member['nama'] ?? ''),
                    'intervals' => [],
                ];
            }

            $merged[$id]['intervals'][] = [
                'mulai' => (string) ($member['tanggal_mulai'] ?? ''),
                'selesai' => !empty($member['tanggal_selesai'])
                    ? (string) $member['tanggal_selesai']
                    : null,
            ];
        }

        $rows = array_values($merged);

        usort(
            $rows,
            static fn (array $a, array $b): int =>
                strcasecmp((string) $a['nama'], (string) $b['nama'])
        );

        return $rows;
    }

    private function isMembershipDate(array $intervals, string $tanggal): bool
    {
        foreach ($intervals as $interval) {
            $mulai = (string) ($interval['mulai'] ?? '');
            $selesai = $interval['selesai'] ?? null;

            if ($mulai === '' || $tanggal < $mulai) {
                continue;
            }

            if ($selesai === null || $selesai === '' || $tanggal <= $selesai) {
                return true;
            }
        }

        return false;
    }

    private function statusCode(string $status): string
    {
        return match ($status) {
            'Hadir' => 'H',
            'Sakit' => 'S',
            'Izin' => 'I',
            'Alpha' => 'A',
            default => '-',
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
