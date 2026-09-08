<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;

/**
 * DashboardService
 *
 * Dashboard adalah agregasi data, bukan security boundary.
 * Semua widget dibentuk server-side dan hanya ditampilkan bila permission sumber valid.
 */
class DashboardService
{
    private const TZ = 'Asia/Jakarta';

    protected BaseConnection $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    /**
     * @return array{dashboard_role:string,is_wali:bool,roles:array,widgets:array}
     */
    public function build(int $userId): array
    {
        $roles = $this->authService->getUserRoles($userId);
        $effectiveRole = $this->resolveDashboardRole($roles);
        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);
        $isWali = $idGuru > 0 && $this->authService->isWaliKelas($idGuru);

        $widgets = match ($effectiveRole) {
            'admin' => $this->widgetsAdmin($userId),
            'operator' => $this->widgetsOperator($userId),
            'pimpinan' => $this->widgetsPimpinan($userId),
            'guru' => $this->widgetsGuru($userId, $isWali),
            'bk' => $this->widgetsBk($userId),
            'siswa' => $this->widgetsSiswa($userId),
            default => [],
        };

        return [
            'dashboard_role' => $effectiveRole,
            'is_wali' => $effectiveRole === 'guru' && $isWali,
            'roles' => $roles,
            'widgets' => $widgets,
        ];
    }

    /**
     * Priority business dashboard:
     * Admin > Operator > Pimpinan > Guru/Wali > BK > Siswa.
     */
    public function resolveDashboardRole(array $roles): string
    {
        foreach (['admin', 'operator', 'pimpinan', 'guru', 'bk', 'siswa'] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'guru';
    }

    protected function widgetsAdmin(int $userId): array
    {
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        return [
            'tahun_aktif' => $tahun,
            'master' => $this->masterSummary($idTahun),
            'presensi_hari_ini' => $this->presensiTodaySummary($idTahun),
            'jurnal_hari_ini' => $this->journalTodaySummary($idTahun),
            'ews_count' => $this->can($userId, 'ews_radar.view') ? $this->ewsCount($idTahun) : null,
            'bk_bulan_ini' => $this->can($userId, 'bk_kasus.view') ? $this->caseCountThisMonth() : null,
            'prestasi_bulan_ini' => $this->can($userId, 'prestasi.view') ? $this->prestasiCountThisMonth() : null,
            'kartu' => $this->can($userId, 'kartu_pelajar.view') ? $this->cardSummary() : null,
            'status_sistem' => $this->systemStatus(),
            'tren_presensi' => $this->trendAttendance($idTahun),
            'aktivitas_terakhir' => $this->can($userId, 'log_activity.view') ? $this->latestActivity() : [],
        ];
    }

    protected function widgetsOperator(int $userId): array
    {
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        return [
            'tahun_aktif' => $tahun,
            'master' => $this->masterSummary($idTahun),
            'presensi_hari_ini' => $this->presensiTodaySummary($idTahun),
            'jurnal_hari_ini' => $this->journalTodaySummary($idTahun),
            'ews_count' => $this->can($userId, 'ews_radar.view') ? $this->ewsCount($idTahun) : null,
            'bk_bulan_ini' => $this->can($userId, 'bk_kasus.view') ? $this->caseCountThisMonth() : null,
            'prestasi_bulan_ini' => $this->can($userId, 'prestasi.view') ? $this->prestasiCountThisMonth() : null,
            'kartu' => $this->can($userId, 'kartu_pelajar.view') ? $this->cardSummary() : null,
            'tren_presensi' => $this->trendAttendance($idTahun),
            'aktivitas_terakhir' => $this->can($userId, 'log_activity.view') ? $this->latestActivity() : [],
        ];
    }

    protected function widgetsPimpinan(int $userId): array
    {
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        return [
            'tahun_aktif' => $tahun,
            'master' => $this->masterSummary($idTahun),
            'presensi_hari_ini' => $this->presensiTodaySummary($idTahun),
            'jurnal_hari_ini' => $this->journalTodaySummary($idTahun),
            'ews_count' => $this->can($userId, 'ews_radar.view') ? $this->ewsCount($idTahun) : null,
            'ews_top' => $this->can($userId, 'ews_radar.view') ? $this->ewsTop($idTahun, null, 10) : [],
            'kasus_bulan_ini' => $this->can($userId, 'bk_kasus.view') ? $this->caseCountThisMonth() : null,
            'top20_pelanggaran' => $this->can($userId, 'bk_kasus.view') ? $this->topViolations(20) : [],
            'prestasi_terbaru' => $this->can($userId, 'prestasi.view') ? $this->latestPrestasi(5) : [],
            'kartu' => $this->can($userId, 'kartu_pelajar.view') ? $this->cardSummary() : null,
            'tren_presensi' => $this->trendAttendance($idTahun),
        ];
    }

    protected function widgetsBk(int $userId): array
    {
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        return [
            'tahun_aktif' => $tahun,
            'kasus_bulan_ini' => $this->can($userId, 'bk_kasus.view') ? $this->caseCountThisMonth() : null,
            'pelanggaran_berat_bulan_ini' => $this->can($userId, 'bk_kasus.view') ? $this->severeCaseCountThisMonth() : null,
            'ews_count' => $this->can($userId, 'ews_radar.view') ? $this->ewsCount($idTahun) : null,
            'prestasi_bulan_ini' => $this->can($userId, 'prestasi.view') ? $this->prestasiCountThisMonth() : null,
            'ews_top' => $this->can($userId, 'ews_radar.view') ? $this->ewsTop($idTahun, null, 10) : [],
            'top20_pelanggaran' => $this->can($userId, 'bk_kasus.view') ? $this->topViolations(20) : [],
            'kasus_terbaru' => $this->can($userId, 'bk_kasus.view') ? $this->latestCases(8) : [],
            'prestasi_terbaru' => $this->can($userId, 'prestasi.view') ? $this->latestPrestasi(8) : [],
        ];
    }

    protected function widgetsGuru(int $userId, bool $isWali): array
    {
        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);
        $jadwal = $idGuru > 0 ? $this->teacherScheduleToday($idGuru, $idTahun, $isWali) : [];

        $result = [
            'tahun_aktif' => $tahun,
            'jadwal_hari_ini' => $jadwal,
            'task_summary' => $this->teacherTaskSummary($jadwal),
            'riwayat_jurnal_terakhir' => $idGuru > 0 ? $this->latestTeacherJournals($idGuru, 5) : [],
            'profile_available' => $this->can($userId, 'profile_guru.view'),
        ];

        if ($isWali && $idGuru > 0 && $idTahun > 0) {
            $result['wali'] = $this->waliSummary($userId, $idGuru, $idTahun);
        }

        return $result;
    }

    protected function widgetsSiswa(int $userId): array
    {
        $user = $this->getUser($userId);
        $idSiswa = (int) ($user['id_siswa'] ?? 0);
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);

        if ($idSiswa <= 0) {
            return [
                'tahun_aktif' => $tahun,
                'rekap_presensi_bulan_ini' => [],
                'presensi_terbaru' => [],
                'riwayat_prestasi' => [],
                'riwayat_pelanggaran' => [],
                'kartu' => null,
                'profile_available' => false,
            ];
        }

        return [
            'tahun_aktif' => $tahun,
            'rekap_presensi_bulan_ini' => $this->can($userId, 'presensi_siswa.view')
                ? $this->studentAttendanceMonth($idSiswa, $idTahun)
                : [],
            'presensi_terbaru' => $this->can($userId, 'presensi_siswa.view')
                ? $this->studentRecentAbsence($idSiswa, $idTahun, 5)
                : [],
            'riwayat_prestasi' => $this->can($userId, 'prestasi.view')
                ? $this->studentPrestasi($idSiswa, 5)
                : [],
            'riwayat_pelanggaran' => $this->can($userId, 'bk_kasus.view')
                ? $this->studentCases($idSiswa, 5)
                : [],
            'kartu' => $this->can($userId, 'kartu_pelajar.view')
                ? $this->studentCard($idSiswa)
                : null,
            'profile_available' => $this->can($userId, 'profile_siswa.view'),
        ];
    }

    protected function getUser(int $userId): array
    {
        return $this->db
            ->table('users')
            ->select('id, username, role, id_guru, id_pegawai, id_siswa, status_aktif')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray() ?: [];
    }

    protected function can(int $userId, string $permission): bool
    {
        return $this->authService->hasPermission($permission, $userId);
    }

    protected function tahunAktif(): ?array
    {
        return $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray() ?: null;
    }

    protected function masterSummary(int $idTahun): array
    {
        return [
            'siswa' => $this->db->table('siswa')->where('status_aktif', 'Aktif')->where('deleted_at', null)->countAllResults(),
            'guru' => $this->db->table('guru')->where('deleted_at', null)->countAllResults(),
            'pegawai' => $this->db->table('pegawai')->where('deleted_at', null)->countAllResults(),
            'kelas' => $idTahun > 0
                ? $this->db->table('kelas')->where('id_tahun', $idTahun)->where('deleted_at', null)->countAllResults()
                : 0,
        ];
    }

    /**
     * Hanya kelas yang mempunyai kewajiban Sesi Awal pada Jadwal Aktif hari ini.
     */
    protected function presensiTodaySummary(int $idTahun): array
    {
        if ($idTahun <= 0) {
            return $this->emptyAttendanceSummary();
        }

        $today = $this->today();
        $hari = $this->hariIndonesia();

        $obligatedRows = $this->db
            ->table('jadwal_guru')
            ->select('id_kelas')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('hari', $hari)
            ->where('sesi', 'Sesi Awal')
            ->where('status_jadwal', 'Aktif')
            ->get()
            ->getResultArray();

        $obligatedIds = array_values(array_unique(array_map('intval', array_column($obligatedRows, 'id_kelas'))));
        $wajib = count($obligatedIds);
        $sudah = 0;

        if ($obligatedIds !== []) {
            $row = $this->db
                ->table('presensi')
                ->select('COUNT(DISTINCT id_kelas) AS total', false)
                ->where('id_tahun', $idTahun)
                ->where('tanggal', $today)
                ->where('sesi', 'Sesi Awal')
                ->whereIn('id_kelas', $obligatedIds)
                ->get()
                ->getRowArray();

            $sudah = (int) ($row['total'] ?? 0);
        }

        $status = $this->db
            ->table('presensi')
            ->select("SUM(status = 'Hadir') AS hadir, SUM(status = 'Sakit') AS sakit, SUM(status = 'Izin') AS izin, SUM(status = 'Alpha') AS alpha", false)
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $today)
            ->where('sesi', 'Sesi Awal')
            ->get()
            ->getRowArray() ?: [];

        return [
            'wajib_kelas' => $wajib,
            'sudah_kelas' => $sudah,
            'belum_kelas' => max(0, $wajib - $sudah),
            'hadir' => (int) ($status['hadir'] ?? 0),
            'sakit' => (int) ($status['sakit'] ?? 0),
            'izin' => (int) ($status['izin'] ?? 0),
            'alpha' => (int) ($status['alpha'] ?? 0),
        ];
    }

    protected function emptyAttendanceSummary(): array
    {
        return [
            'wajib_kelas' => 0,
            'sudah_kelas' => 0,
            'belum_kelas' => 0,
            'hadir' => 0,
            'sakit' => 0,
            'izin' => 0,
            'alpha' => 0,
        ];
    }

    /**
     * Semua Jadwal Aktif hari ini wajib Jurnal, termasuk Non Sesi.
     */
    protected function journalTodaySummary(int $idTahun): array
    {
        if ($idTahun <= 0) {
            return ['wajib' => 0, 'sudah' => 0, 'belum' => 0];
        }

        $today = $this->today();
        $hari = $this->hariIndonesia();

        $wajib = $this->db
            ->table('jadwal_guru')
            ->where('id_tahun', $idTahun)
            ->where('hari', $hari)
            ->where('status_jadwal', 'Aktif')
            ->countAllResults();

        $row = $this->db
            ->table('presensi_mengajar pm')
            ->select('COUNT(DISTINCT pm.id_jadwal) AS total', false)
            ->join('jadwal_guru jg', 'jg.id = pm.id_jadwal')
            ->where('pm.id_tahun', $idTahun)
            ->where('pm.tanggal', $today)
            ->where('jg.hari', $hari)
            ->where('jg.status_jadwal', 'Aktif')
            ->get()
            ->getRowArray();

        $sudah = (int) ($row['total'] ?? 0);

        return [
            'wajib' => $wajib,
            'sudah' => $sudah,
            'belum' => max(0, $wajib - $sudah),
        ];
    }

    /** EWS = >=3 Alpha, Sesi Awal, hari ini + 13 hari sebelumnya. */
    protected function ewsCount(int $idTahun, ?array $kelasIds = null): int
    {
        if ($idTahun <= 0) {
            return 0;
        }

        $builder = $this->db
            ->table('presensi')
            ->select('id_siswa')
            ->where('id_tahun', $idTahun)
            ->where('sesi', 'Sesi Awal')
            ->where('status', 'Alpha')
            ->where('tanggal >=', $this->dateMinusDays(13))
            ->where('tanggal <=', $this->today());

        if ($kelasIds !== null) {
            if ($kelasIds === []) {
                return 0;
            }
            $builder->whereIn('id_kelas', $kelasIds);
        }

        return $builder
            ->groupBy('id_siswa')
            ->having('COUNT(id) >=', 3)
            ->get()
            ->getNumRows();
    }

    protected function ewsTop(int $idTahun, ?array $kelasIds, int $limit): array
    {
        if ($idTahun <= 0) {
            return [];
        }

        $builder = $this->db
            ->table('presensi p')
            ->select('p.id_siswa, s.nama, COUNT(p.id) AS total_alpha')
            ->join('siswa s', 's.id = p.id_siswa')
            ->where('p.id_tahun', $idTahun)
            ->where('p.sesi', 'Sesi Awal')
            ->where('p.status', 'Alpha')
            ->where('p.tanggal >=', $this->dateMinusDays(13))
            ->where('p.tanggal <=', $this->today())
            ->where('s.deleted_at', null);

        if ($kelasIds !== null) {
            if ($kelasIds === []) {
                return [];
            }
            $builder->whereIn('p.id_kelas', $kelasIds);
        }

        return $builder
            ->groupBy('p.id_siswa, s.nama')
            ->having('COUNT(p.id) >=', 3)
            ->orderBy('total_alpha', 'DESC')
            ->orderBy('s.nama', 'ASC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function trendAttendance(int $idTahun): array
    {
        $start = $this->dateMinusDays(6);
        $end = $this->today();

        if ($idTahun <= 0) {
            return $this->fillTrendDays([], $start, $end);
        }

        $rows = $this->db
            ->table('presensi')
            ->select("tanggal, COUNT(id) AS total, SUM(status = 'Hadir') AS hadir", false)
            ->where('id_tahun', $idTahun)
            ->where('sesi', 'Sesi Awal')
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'ASC')
            ->get()
            ->getResultArray();

        return $this->fillTrendDays($rows, $start, $end);
    }

    protected function fillTrendDays(array $rows, string $start, string $end): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['tanggal']] = $row;
        }

        $result = [];
        $cursor = new \DateTimeImmutable($start, new \DateTimeZone(self::TZ));
        $last = new \DateTimeImmutable($end, new \DateTimeZone(self::TZ));

        while ($cursor <= $last) {
            $date = $cursor->format('Y-m-d');
            $row = $map[$date] ?? null;
            $total = (int) ($row['total'] ?? 0);
            $hadir = (int) ($row['hadir'] ?? 0);

            $result[] = [
                'tanggal' => $date,
                'persen_hadir' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0.0,
            ];

            $cursor = $cursor->modify('+1 day');
        }

        return $result;
    }

    protected function caseCountThisMonth(): int
    {
        [$start, $end] = $this->monthPeriod();
        return $this->db->table('catatan_kasus')->where('tanggal >=', $start)->where('tanggal <=', $end)->countAllResults();
    }

    protected function severeCaseCountThisMonth(): int
    {
        [$start, $end] = $this->monthPeriod();
        return $this->db
            ->table('catatan_kasus ck')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->where('ck.tanggal >=', $start)
            ->where('ck.tanggal <=', $end)
            ->where('rp.kategori', 'Berat')
            ->countAllResults();
    }

    protected function prestasiCountThisMonth(): int
    {
        [$start, $end] = $this->monthPeriod();
        return $this->db->table('catatan_prestasi')->where('tanggal >=', $start)->where('tanggal <=', $end)->countAllResults();
    }

    protected function topViolations(int $limit): array
    {
        return $this->db
            ->table('catatan_kasus ck')
            ->select('ck.id_siswa, s.nama, SUM(rp.poin) AS total_poin')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->join('siswa s', 's.id = ck.id_siswa')
            ->groupBy('ck.id_siswa, s.nama')
            ->orderBy('total_poin', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function latestCases(int $limit): array
    {
        return $this->db
            ->table('catatan_kasus ck')
            ->select('ck.id, ck.id_siswa, ck.tanggal, ck.keterangan, s.nama, rp.nama_pelanggaran, rp.kategori, rp.poin')
            ->join('siswa s', 's.id = ck.id_siswa')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->orderBy('ck.tanggal', 'DESC')
            ->orderBy('ck.id', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function latestPrestasi(int $limit): array
    {
        return $this->db
            ->table('catatan_prestasi cp')
            ->select('cp.*, s.nama')
            ->join('siswa s', 's.id = cp.id_siswa')
            ->orderBy('cp.tanggal', 'DESC')
            ->orderBy('cp.id', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function cardSummary(): array
    {
        $row = $this->db
            ->table('kartu_pelajar')
            ->select("SUM(status_aktif = 'Aktif') AS aktif, SUM(status_aktif = 'Nonaktif') AS nonaktif, COUNT(id) AS total", false)
            ->get()
            ->getRowArray() ?: [];

        return [
            'aktif' => (int) ($row['aktif'] ?? 0),
            'nonaktif' => (int) ($row['nonaktif'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    protected function systemStatus(): array
    {
        $rows = $this->db
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['geofencing_aktif', 'radius_geofencing', 'maintenance_mode'])
            ->get()
            ->getResultArray();

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return [
            'geofence_aktif' => ($settings['geofencing_aktif'] ?? '0') === '1',
            'radius_geofence' => (int) ($settings['radius_geofencing'] ?? 500),
            'maintenance_mode' => ($settings['maintenance_mode'] ?? '0') === '1',
        ];
    }

    protected function latestActivity(int $limit = 10): array
    {
        return $this->db
            ->table('log_activity')
            ->select('id, id_user, aksi, modul, keterangan, waktu')
            ->orderBy('waktu', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function teacherScheduleToday(int $idGuru, int $idTahun, bool $isWali): array
    {
        if ($idGuru <= 0 || $idTahun <= 0) {
            return [];
        }

        $today = $this->today();
        $rows = $this->db
            ->table('jadwal_guru jg')
            ->select('jg.id, jg.id_guru, jg.id_kelas, jg.id_mapel, jg.id_tahun, jg.hari, jg.jam_mulai, jg.jam_selesai, jg.sesi, k.nama_kelas, mp.nama_mapel, mp.kode_mapel')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id_guru', $idGuru)
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.hari', $this->hariIndonesia())
            ->where('jg.status_jadwal', 'Aktif')
            ->where('k.deleted_at', null)
            ->orderBy('jg.jam_mulai', 'ASC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [];
        }

        $kelasWali = $isWali ? $this->authService->getKelasDiampu($idGuru, $idTahun) : [];
        $idJadwal = array_map('intval', array_column($rows, 'id'));
        $idKelas = array_values(array_unique(array_map('intval', array_column($rows, 'id_kelas'))));

        $journalRows = $this->db
            ->table('presensi_mengajar')
            ->select('id_jadwal')
            ->where('tanggal', $today)
            ->whereIn('id_jadwal', $idJadwal)
            ->get()
            ->getResultArray();
        $journalSet = array_fill_keys(array_map('intval', array_column($journalRows, 'id_jadwal')), true);

        $attendanceRows = $this->db
            ->table('presensi')
            ->select('id_kelas, sesi')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $today)
            ->whereIn('id_kelas', $idKelas)
            ->get()
            ->getResultArray();
        $attendanceSet = [];
        foreach ($attendanceRows as $row) {
            $attendanceSet[(int) $row['id_kelas'] . '|' . (string) $row['sesi']] = true;
        }

        foreach ($rows as &$row) {
            $window = $this->windowState((string) $row['jam_mulai'], (string) $row['jam_selesai']);
            $row['window_state'] = $window;
            $row['presensi_url'] = null;
            $row['jurnal_url'] = null;

            if ((string) $row['sesi'] === 'Non Sesi') {
                $row['presensi_state'] = 'not_applicable';
            } elseif (isset($attendanceSet[(int) $row['id_kelas'] . '|' . (string) $row['sesi']])) {
                $row['presensi_state'] = 'submitted';
            } elseif ($window === 'NOT_STARTED') {
                $row['presensi_state'] = 'not_started';
            } elseif ($window === 'VALID') {
                $row['presensi_state'] = 'available';
                $row['presensi_url'] = 'presensi/siswa/input/' . (int) $row['id_kelas']
                    . '?tanggal=' . rawurlencode($today)
                    . '&sesi=' . rawurlencode((string) $row['sesi']);
            } elseif (in_array((int) $row['id_kelas'], $kelasWali, true)) {
                $row['presensi_state'] = 'wali_available';
                $row['presensi_url'] = 'presensi/siswa/input/' . (int) $row['id_kelas']
                    . '?tanggal=' . rawurlencode($today)
                    . '&sesi=' . rawurlencode((string) $row['sesi']);
            } else {
                $row['presensi_state'] = 'ended';
            }

            if (isset($journalSet[(int) $row['id']])) {
                $row['jurnal_state'] = 'submitted';
            } elseif ($window === 'NOT_STARTED') {
                $row['jurnal_state'] = 'not_started';
            } elseif ($window === 'VALID') {
                $row['jurnal_state'] = 'available';
                $row['jurnal_url'] = 'presensi/mengajar/input/' . (int) $row['id']
                    . '?tanggal=' . rawurlencode($today);
            } else {
                $row['jurnal_state'] = 'ended';
            }
        }
        unset($row);

        return $rows;
    }

    protected function teacherTaskSummary(array $jadwal): array
    {
        $summary = [
            'jadwal' => count($jadwal),
            'presensi_perlu' => 0,
            'jurnal_perlu' => 0,
            'jurnal_selesai' => 0,
        ];

        foreach ($jadwal as $row) {
            if (in_array($row['presensi_state'] ?? '', ['available', 'wali_available'], true)) {
                $summary['presensi_perlu']++;
            }
            if (($row['jurnal_state'] ?? '') === 'available') {
                $summary['jurnal_perlu']++;
            }
            if (($row['jurnal_state'] ?? '') === 'submitted') {
                $summary['jurnal_selesai']++;
            }
        }

        return $summary;
    }

    protected function latestTeacherJournals(int $idGuru, int $limit): array
    {
        return $this->db
            ->table('presensi_mengajar pm')
            ->select('pm.id, pm.tanggal, pm.status, pm.materi, k.nama_kelas, mp.nama_mapel')
            ->join('jadwal_guru jg', 'jg.id = pm.id_jadwal', 'left')
            ->join('kelas k', 'k.id = pm.id_kelas', 'left')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel', 'left')
            ->where('pm.id_guru', $idGuru)
            ->orderBy('pm.tanggal', 'DESC')
            ->orderBy('pm.id', 'DESC')
            ->limit(max(1, min(10, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function waliSummary(int $userId, int $idGuru, int $idTahun): array
    {
        $mapping = $this->db
            ->table('mapping_wali_kelas mw')
            ->select('mw.id_kelas, k.nama_kelas')
            ->join('kelas k', 'k.id = mw.id_kelas')
            ->where('mw.id_guru', $idGuru)
            ->where('mw.id_tahun', $idTahun)
            ->where('mw.deleted_at', null)
            ->where('k.deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $mapping) {
            return [];
        }

        $idKelas = (int) $mapping['id_kelas'];
        $jumlah = $this->db
            ->table('anggota_kelas ak')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_kelas', $idKelas)
            ->where('ak.id_tahun', $idTahun)
            ->where('s.status_aktif', 'Aktif')
            ->where('s.deleted_at', null)
            ->countAllResults();

        $presensi = $this->db
            ->table('presensi')
            ->select("SUM(status = 'Hadir') AS hadir, SUM(status = 'Sakit') AS sakit, SUM(status = 'Izin') AS izin, SUM(status = 'Alpha') AS alpha", false)
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('tanggal', $this->today())
            ->where('sesi', 'Sesi Awal')
            ->get()
            ->getRowArray() ?: [];

        $recent = $this->db
            ->table('presensi p')
            ->select('p.id_siswa, p.tanggal, p.status, s.nama, s.nisn')
            ->join('siswa s', 's.id = p.id_siswa')
            ->where('p.id_tahun', $idTahun)
            ->where('p.id_kelas', $idKelas)
            ->where('p.sesi', 'Sesi Awal')
            ->whereIn('p.status', ['Sakit', 'Izin', 'Alpha'])
            ->orderBy('p.tanggal', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        return [
            'id_kelas' => $idKelas,
            'nama_kelas' => (string) $mapping['nama_kelas'],
            'jumlah_siswa' => $jumlah,
            'presensi_hari_ini' => [
                'hadir' => (int) ($presensi['hadir'] ?? 0),
                'sakit' => (int) ($presensi['sakit'] ?? 0),
                'izin' => (int) ($presensi['izin'] ?? 0),
                'alpha' => (int) ($presensi['alpha'] ?? 0),
            ],
            'ews_count' => $this->can($userId, 'ews_radar.view') ? $this->ewsCount($idTahun, [$idKelas]) : null,
            'ews_top' => $this->can($userId, 'ews_radar.view') ? $this->ewsTop($idTahun, [$idKelas], 5) : [],
            'recent_absence' => $recent,
            'quick_links' => $this->waliQuickLinks($userId, $idKelas),
        ];
    }

    protected function waliQuickLinks(int $userId, int $idKelas): array
    {
        $links = [];

        // Hanya endpoint yang sudah memiliki Controller implementatif pada checkpoint ini.
        // Permission modul Laporan/BK/Prestasi/Kartu tetap disiapkan oleh RBAC,
        // tetapi shortcut dashboard ditambahkan saat modul tersebut selesai.
        $candidates = [
            ['permission' => 'presensi_siswa.input', 'label' => 'Presensi Kelas', 'url' => 'presensi/siswa?kelas=' . $idKelas],
            ['permission' => 'presensi_siswa.view', 'label' => 'Rekap Presensi', 'url' => 'presensi/siswa/rekap?id_kelas=' . $idKelas],
            ['permission' => 'master_siswa.view', 'label' => 'Data Siswa', 'url' => 'master/siswa?id_kelas=' . $idKelas],
        ];

        foreach ($candidates as $candidate) {
            if ($this->can($userId, $candidate['permission'])) {
                $links[] = $candidate;
            }
        }

        return $links;
    }

    protected function studentAttendanceMonth(int $idSiswa, int $idTahun): array
    {
        [$start, $end] = $this->monthPeriod();
        $row = $this->db
            ->table('presensi')
            ->select("SUM(status = 'Hadir') AS hadir, SUM(status = 'Sakit') AS sakit, SUM(status = 'Izin') AS izin, SUM(status = 'Alpha') AS alpha", false)
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('sesi', 'Sesi Awal')
            ->where('tanggal >=', $start)
            ->where('tanggal <=', $end)
            ->get()
            ->getRowArray() ?: [];

        return [
            'hadir' => (int) ($row['hadir'] ?? 0),
            'sakit' => (int) ($row['sakit'] ?? 0),
            'izin' => (int) ($row['izin'] ?? 0),
            'alpha' => (int) ($row['alpha'] ?? 0),
        ];
    }

    protected function studentRecentAbsence(int $idSiswa, int $idTahun, int $limit): array
    {
        return $this->db
            ->table('presensi')
            ->select('tanggal, status')
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('sesi', 'Sesi Awal')
            ->whereIn('status', ['Sakit', 'Izin', 'Alpha'])
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function studentPrestasi(int $idSiswa, int $limit): array
    {
        return $this->db
            ->table('catatan_prestasi')
            ->where('id_siswa', $idSiswa)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function studentCases(int $idSiswa, int $limit): array
    {
        return $this->db
            ->table('catatan_kasus ck')
            ->select('ck.id, ck.tanggal, ck.keterangan, rp.nama_pelanggaran, rp.kategori, rp.poin')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->where('ck.id_siswa', $idSiswa)
            ->orderBy('ck.tanggal', 'DESC')
            ->orderBy('ck.id', 'DESC')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->getResultArray();
    }

    protected function studentCard(int $idSiswa): ?array
    {
        return $this->db
            ->table('kartu_pelajar')
            ->where('id_siswa', $idSiswa)
            ->orderBy("FIELD(status_aktif, 'Aktif', 'Nonaktif')", '', false)
            ->orderBy('tanggal_terbit', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray() ?: null;
    }

    protected function windowState(string $jamMulai, string $jamSelesai): string
    {
        $tz = new \DateTimeZone(self::TZ);
        $today = $this->today();
        $now = new \DateTimeImmutable('now', $tz);
        $start = new \DateTimeImmutable($today . ' ' . $jamMulai, $tz);
        $end = (new \DateTimeImmutable($today . ' ' . $jamSelesai, $tz))->modify('+15 minutes');

        if ($now < $start) {
            return 'NOT_STARTED';
        }

        if ($now > $end) {
            return 'ENDED';
        }

        return 'VALID';
    }

    protected function today(): string
    {
        return Time::now(self::TZ)->format('Y-m-d');
    }

    protected function dateMinusDays(int $days): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::TZ));
        return $now->modify('-' . max(0, $days) . ' days')->format('Y-m-d');
    }

    protected function monthPeriod(): array
    {
        $now = Time::now(self::TZ);
        return [$now->format('Y-m-01'), $now->format('Y-m-t')];
    }

    protected function hariIndonesia(): string
    {
        return match ((int) Time::now(self::TZ)->format('N')) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
    }
}
