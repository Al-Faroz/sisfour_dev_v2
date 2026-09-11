<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

/**
 * SignageService
 *
 * Sumber data read-only untuk Digital Signage public.
 *
 * Aturan STEP 06:
 * - sumber Presensi hanya Sesi Awal;
 * - ranking menggunakan jendela 14 hari yang sama dengan EWS internal;
 * - Top 20 Alpha, Izin, dan Sakit;
 * - daftar Sakit/Izin/Alpha hari ini;
 * - refresh client setiap 5 menit;
 * - hasil query dicache server-side agar beberapa display tidak membebani DB.
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
            return [
                'success' => true,
                'message' => 'Tahun Ajaran aktif belum tersedia.',
                'generated_at' => $now->format('Y-m-d H:i:s'),
                'tahun_ajaran' => null,
                'ranking_period' => null,
                'top_alpha' => [],
                'top_izin' => [],
                'top_sakit' => [],
                'tidak_masuk_hari_ini' => [],
                'today_summary' => [
                    'Sakit' => 0,
                    'Izin' => 0,
                    'Alpha' => 0,
                    'total' => 0,
                ],
            ];
        }

        $idTahun = (int) $tahun['id'];
        $today = $now->format('Y-m-d');
        $cacheKey = $this->cacheKey($idTahun, $today);
        $cached = $this->readCache($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $tanggalMulai = $now
            ->subDays(self::RANKING_DAYS - 1)
            ->format('Y-m-d');

        $tidakMasuk = $this->getTidakMasukHariIni(
            $idTahun,
            $today
        );

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
            'top_alpha' => $this->getTopStatus(
                $idTahun,
                $tanggalMulai,
                $today,
                'Alpha'
            ),
            'top_izin' => $this->getTopStatus(
                $idTahun,
                $tanggalMulai,
                $today,
                'Izin'
            ),
            'top_sakit' => $this->getTopStatus(
                $idTahun,
                $tanggalMulai,
                $today,
                'Sakit'
            ),
            'tidak_masuk_hari_ini' => $tidakMasuk,
            'today_summary' => $this->buildTodaySummary($tidakMasuk),
        ];

        $this->writeCache($cacheKey, $result);

        return $result;
    }

    /**
     * Top siswa per status dalam periode ranking.
     * Kelas yang ditampilkan adalah kelas aktif siswa pada Tahun Ajaran aktif.
     */
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
                'anggota_kelas ak',
                'ak.id_siswa = pr.id_siswa AND ak.id_tahun = ' . $idTahun,
                'left',
                false
            )
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.deleted_at IS NULL',
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

    /**
     * Daftar siswa S/I/A pada Sesi Awal hari berjalan.
     */
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
        return 'sisfour_signage_v2_'
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
            service('cache')->save(
                $key,
                $data,
                self::CACHE_TTL_SECONDS
            );
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Signage cache write gagal: {message}',
                ['message' => $e->getMessage()]
            );
        }
    }
}
