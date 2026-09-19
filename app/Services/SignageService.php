<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

/**
 * Public read-only Digital Signage.
 *
 * G3.6C:
 * - layout utama stabil mengikuti TemplateSIGNAGE;
 * - summary H/S/I/A Sesi Awal hari ini + coverage kelas;
 * - EWS panel merotasi Top Sakit/Izin/Alpha dalam 14 hari;
 * - Kelas Belum Presensi dan Jadwal Belum Jurnal auto-page di client;
 * - refresh data 5 menit, rotasi 15 detik.
 */
class SignageService
{
    private const TZ = 'Asia/Jakarta';
    private const REFRESH_MINUTES = 5;
    private const ROTATION_SECONDS = 15;
    private const RANKING_DAYS = 14;
    private const TOP_LIMIT = 20;
    private const CACHE_TTL_SECONDS = 240;

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getDisplayInfo(): array
    {
        $settings = $this->getSystemSettings();
        $tahun = $this->getTahunAktif();

        return [
            'nama_sekolah' => $settings['nama_sekolah'] ?? 'MTsN 4 Jombang',
            'logo_sekolah' => $settings['logo_sekolah'] ?? '',
            'tahun_ajaran' => $tahun !== null
                ? trim((string) $tahun['nama_tahun'] . ' - ' . (string) $tahun['semester'])
                : null,
            'refresh_minutes' => self::REFRESH_MINUTES,
            'rotation_seconds' => self::ROTATION_SECONDS,
            'ranking_days' => self::RANKING_DAYS,
        ];
    }

    public function getData(): array
    {
        $now = Time::now(self::TZ);
        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return $this->emptyPayload($now);
        }

        $idTahun = (int) $tahun['id'];
        $today = $now->format('Y-m-d');
        $cacheKey = $this->cacheKey($idTahun, $today);
        $cached = $this->readCache($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $tanggalMulai = date(
            'Y-m-d',
            strtotime($today . ' -' . (self::RANKING_DAYS - 1) . ' days')
        );
        $tidakMasuk = $this->getTidakMasukHariIni($idTahun, $today);
        $obligated = $this->getObligatedClasses($idTahun, $now);

        $result = [
            'success' => true,
            'message' => 'Data Digital Signage berhasil dimuat.',
            'generated_at' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
            'tahun_ajaran' => trim(
                (string) $tahun['nama_tahun']
                . ' - '
                . (string) $tahun['semester']
            ),
            'ranking_period' => [
                'mulai' => $tanggalMulai,
                'selesai' => $today,
                'days' => self::RANKING_DAYS,
            ],
            'top_sakit' => $this->getTopStatus(
                $idTahun,
                $tanggalMulai,
                $today,
                'Sakit'
            ),
            'top_izin' => $this->getTopStatus(
                $idTahun,
                $tanggalMulai,
                $today,
                'Izin'
            ),
            'top_alpha' => $this->getTopStatus(
                $idTahun,
                $tanggalMulai,
                $today,
                'Alpha'
            ),
            // Legacy-compatible keys retained for regression safety.
            'tidak_masuk_hari_ini' => $tidakMasuk,
            'today_summary' => $this->buildTodaySummary($tidakMasuk),
            // G3.6C presentation data.
            'attendance_summary' => $this->getAttendanceSummary(
                $idTahun,
                $today,
                $obligated
            ),
            'kelas_belum_presensi' => $this->getKelasBelumPresensi(
                $idTahun,
                $today,
                $obligated
            ),
            'jadwal_belum_jurnal' => $this->getJadwalBelumJurnal(
                $idTahun,
                $today,
                $now
            ),
        ];

        $this->writeCache($cacheKey, $result);

        return $result;
    }

    public function getTopStatus(
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai,
        string $status
    ): array {
        if (! in_array($status, ['Sakit', 'Izin', 'Alpha'], true)) {
            return [];
        }

        return $this->db
            ->table('presensi pr')
            ->select(
                'pr.id_siswa, '
                . 'MAX(pr.nama_siswa_snapshot) AS nama_siswa, '
                . 'COALESCE(MAX(k.nama_kelas), \'-\') AS nama_kelas, '
                . 'COUNT(pr.id) AS total',
                false
            )
            ->join(
                'kelas k',
                'k.id = pr.id_kelas AND k.deleted_at IS NULL',
                'left',
                false
            )
            ->where('pr.id_tahun', $idTahun)
            ->where('pr.sesi', 'Sesi Awal')
            ->where('pr.status', $status)
            ->where('pr.tanggal >=', $tanggalMulai)
            ->where('pr.tanggal <=', $tanggalSelesai)
            ->where('pr.id_siswa IS NOT NULL', null, false)
            ->groupBy('pr.id_siswa')
            ->orderBy('total', 'DESC')
            ->orderBy('nama_siswa', 'ASC')
            ->limit(self::TOP_LIMIT)
            ->get()
            ->getResultArray();
    }

    public function getTidakMasukHariIni(
        int $idTahun,
        string $tanggal
    ): array {
        return $this->db
            ->table('presensi pr')
            ->select([
                'pr.id_siswa',
                'pr.nama_siswa_snapshot AS nama_siswa',
                'k.nama_kelas',
                'pr.status',
            ])
            ->join(
                'kelas k',
                'k.id = pr.id_kelas AND k.deleted_at IS NULL',
                'left',
                false
            )
            ->where('pr.id_tahun', $idTahun)
            ->where('pr.tanggal', $tanggal)
            ->where('pr.sesi', 'Sesi Awal')
            ->whereIn('pr.status', ['Sakit', 'Izin', 'Alpha'])
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->orderBy('pr.nama_siswa_snapshot', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getAttendanceSummary(
        int $idTahun,
        string $tanggal,
        array $obligated
    ): array {
        $row = $this->db
            ->table('presensi')
            ->select(
                "SUM(status='Hadir') AS hadir,"
                . "SUM(status='Sakit') AS sakit,"
                . "SUM(status='Izin') AS izin,"
                . "SUM(status='Alpha') AS alpha,"
                . 'COUNT(id) AS total',
                false
            )
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $tanggal)
            ->where('sesi', 'Sesi Awal')
            ->get()
            ->getRowArray() ?: [];

        $counts = [
            'Hadir' => (int) ($row['hadir'] ?? 0),
            'Sakit' => (int) ($row['sakit'] ?? 0),
            'Izin' => (int) ($row['izin'] ?? 0),
            'Alpha' => (int) ($row['alpha'] ?? 0),
        ];
        $total = max(0, (int) ($row['total'] ?? array_sum($counts)));
        $percent = [];

        foreach ($counts as $status => $count) {
            $percent[$status] = $total > 0
                ? round(($count / $total) * 100, 1)
                : 0.0;
        }

        $obligatedIds = array_values(array_unique(array_map(
            static fn (array $item): int => (int) ($item['id_kelas'] ?? 0),
            $obligated
        )));
        $obligatedIds = array_values(array_filter($obligatedIds));
        $submitted = $this->submittedClassIds(
            $idTahun,
            $tanggal,
            $obligatedIds
        );

        return [
            'counts' => $counts,
            'percent' => $percent,
            'total_recorded' => $total,
            'coverage' => [
                'wajib_kelas' => count($obligatedIds),
                'sudah_kelas' => count($submitted),
                'belum_kelas' => max(0, count($obligatedIds) - count($submitted)),
            ],
        ];
    }

    private function getKelasBelumPresensi(
        int $idTahun,
        string $tanggal,
        array $obligated
    ): array {
        if ($obligated === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(
            static fn (array $item): int => (int) ($item['id_kelas'] ?? 0),
            $obligated
        )));
        $ids = array_values(array_filter($ids));
        $submitted = array_fill_keys(
            $this->submittedClassIds($idTahun, $tanggal, $ids),
            true
        );

        return array_values(array_filter(
            $obligated,
            static fn (array $row): bool =>
                ! isset($submitted[(int) ($row['id_kelas'] ?? 0)])
        ));
    }

    private function getObligatedClasses(int $idTahun, Time $now): array
    {
        return $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.id_kelas, k.nama_kelas, k.tingkat, k.rombel, '
                . 'COALESCE(g.nama, \'-\') AS wali_kelas',
                false
            )
            ->join(
                'kelas k',
                'k.id = jg.id_kelas AND k.deleted_at IS NULL',
                'inner',
                false
            )
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_kelas = jg.id_kelas '
                . 'AND mw.id_tahun = ' . $idTahun
                . ' AND mw.deleted_at IS NULL',
                'left',
                false
            )
            ->join(
                'guru g',
                'g.id = mw.id_guru AND g.deleted_at IS NULL',
                'left',
                false
            )
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.hari', $this->hariIndonesia($now))
            ->where('jg.sesi', 'Sesi Awal')
            ->where('jg.status_jadwal', 'Aktif')
            ->groupBy('jg.id_kelas, k.nama_kelas, k.tingkat, k.rombel, g.nama')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function submittedClassIds(
        int $idTahun,
        string $tanggal,
        array $obligatedIds
    ): array {
        if ($obligatedIds === []) {
            return [];
        }

        $rows = $this->db
            ->table('presensi')
            ->select('id_kelas')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $tanggal)
            ->where('sesi', 'Sesi Awal')
            ->whereIn('id_kelas', $obligatedIds)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map(
            'intval',
            array_column($rows, 'id_kelas')
        )));
    }

    private function getJadwalBelumJurnal(
        int $idTahun,
        string $tanggal,
        Time $now
    ): array {
        $rows = $this->db
            ->table('jadwal_guru jg')
            ->select([
                'jg.id',
                'jg.jam_mulai',
                'jg.jam_selesai',
                'g.nama AS nama_guru',
                'k.nama_kelas',
                'mp.nama_mapel',
            ])
            ->join('guru g', 'g.id = jg.id_guru AND g.deleted_at IS NULL')
            ->join('kelas k', 'k.id = jg.id_kelas AND k.deleted_at IS NULL')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.hari', $this->hariIndonesia($now))
            ->where('jg.status_jadwal', 'Aktif')
            ->orderBy('jg.jam_selesai', 'ASC')
            ->orderBy('g.nama', 'ASC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));
        $submittedRows = $this->db
            ->table('presensi_mengajar')
            ->select('id_jadwal')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $tanggal)
            ->whereIn('id_jadwal', $ids)
            ->get()
            ->getResultArray();
        $submitted = array_fill_keys(
            array_map('intval', array_column($submittedRows, 'id_jadwal')),
            true
        );

        $nowTs = strtotime($tanggal . ' ' . $now->format('H:i:s'));
        $result = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $deadline = strtotime(
                $tanggal . ' ' . (string) ($row['jam_selesai'] ?? '00:00:00')
                . ' +15 minutes'
            );

            if ($id <= 0 || isset($submitted[$id]) || $deadline === false || $nowTs <= $deadline) {
                continue;
            }

            $result[] = [
                'id_jadwal' => $id,
                'nama_guru' => (string) ($row['nama_guru'] ?? '-'),
                'nama_kelas' => (string) ($row['nama_kelas'] ?? '-'),
                'nama_mapel' => (string) ($row['nama_mapel'] ?? '-'),
                'jam_mulai' => substr((string) ($row['jam_mulai'] ?? ''), 0, 5),
                'jam_selesai' => substr((string) ($row['jam_selesai'] ?? ''), 0, 5),
            ];
        }

        return $result;
    }

    private function buildTodaySummary(array $rows): array
    {
        $summary = [
            'Sakit' => 0,
            'Izin' => 0,
            'Alpha' => 0,
            'total' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');

            if (array_key_exists($status, $summary) && $status !== 'total') {
                $summary[$status]++;
                $summary['total']++;
            }
        }

        return $summary;
    }

    private function emptyPayload(Time $now): array
    {
        return [
            'success' => true,
            'message' => 'Tahun Ajaran aktif belum tersedia.',
            'generated_at' => $now->format('Y-m-d H:i:s'),
            'tahun_ajaran' => null,
            'ranking_period' => null,
            'top_sakit' => [],
            'top_izin' => [],
            'top_alpha' => [],
            'tidak_masuk_hari_ini' => [],
            'today_summary' => [
                'Sakit' => 0,
                'Izin' => 0,
                'Alpha' => 0,
                'total' => 0,
            ],
            'attendance_summary' => [
                'counts' => [
                    'Hadir' => 0,
                    'Sakit' => 0,
                    'Izin' => 0,
                    'Alpha' => 0,
                ],
                'percent' => [
                    'Hadir' => 0.0,
                    'Sakit' => 0.0,
                    'Izin' => 0.0,
                    'Alpha' => 0.0,
                ],
                'total_recorded' => 0,
                'coverage' => [
                    'wajib_kelas' => 0,
                    'sudah_kelas' => 0,
                    'belum_kelas' => 0,
                ],
            ],
            'kelas_belum_presensi' => [],
            'jadwal_belum_jurnal' => [],
        ];
    }

    private function hariIndonesia(Time $time): string
    {
        return match ((int) $time->format('N')) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
    }

    private function getTahunAktif(): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function getSystemSettings(): array
    {
        $rows = $this->db
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['nama_sekolah', 'logo_sekolah'])
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row['setting_key']] = trim(
                (string) $row['setting_value']
            );
        }

        return $result;
    }

    private function cacheKey(int $idTahun, string $tanggal): string
    {
        return 'sisfour_signage_v3_'
            . $idTahun
            . '_'
            . str_replace('-', '', $tanggal);
    }

    private function readCache(string $key): ?array
    {
        try {
            $cached = service('cache')->get($key);

            return is_array($cached) ? $cached : null;
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Signage cache read gagal: {message}',
                ['message' => $e->getMessage()]
            );

            return null;
        }
    }

    private function writeCache(string $key, array $data): void
    {
        try {
            service('cache')->save($key, $data, self::CACHE_TTL_SECONDS);
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Signage cache write gagal: {message}',
                ['message' => $e->getMessage()]
            );
        }
    }
}
