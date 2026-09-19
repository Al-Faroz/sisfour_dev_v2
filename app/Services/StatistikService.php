<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;
use DateTimeZone;

class StatistikService
{
    private const TZ = 'Asia/Jakarta';
    private const EWS_DAYS = 14;
    private const EWS_LIMIT = 10;

    protected BaseConnection $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    public function page(int $userId, array $input): array
    {
        if (! $this->authService->hasPermission('statistik.view', $userId)) {
            return $this->fail('FORBIDDEN', 'Akses Statistik tidak diizinkan.');
        }

        $resolved = $this->resolveFilters($input);
        if (! $resolved['success']) {
            return $resolved;
        }

        $filters = $resolved['filters'];
        $data = $this->buildDataset($filters);

        return [
            'success' => true,
            'filters' => $this->publicFilters($filters),
            'options' => $this->filterOptions($filters),
            'can_export' => $this->authService->hasPermission(
                'statistik.export_pdf',
                $userId
            ),
            'data' => $data,
        ];
    }

    public function exportData(int $userId, array $input): array
    {
        if (! $this->authService->hasPermission('statistik.export_pdf', $userId)) {
            return $this->fail('FORBIDDEN', 'Export PDF Statistik tidak diizinkan.');
        }

        return $this->page($userId, $input);
    }

    private function resolveFilters(array $input): array
    {
        $years = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        if ($years === []) {
            return $this->fail('NO_PERIOD', 'Tahun Ajaran belum tersedia.');
        }

        $byId = [];
        $active = null;
        foreach ($years as $year) {
            $id = (int) ($year['id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $year;
            }
            if (! empty($year['status_aktif']) && $active === null) {
                $active = $year;
            }
        }

        $requestedYear = (int) ($input['id_tahun'] ?? 0);
        $year = $requestedYear > 0 && isset($byId[$requestedYear])
            ? $byId[$requestedYear]
            : ($active ?? $years[0]);
        $idTahun = (int) $year['id'];

        $tingkat = trim((string) ($input['tingkat'] ?? ''));
        if (! in_array($tingkat, ['', '7', '8', '9'], true)) {
            return $this->fail('VALIDATION', 'Filter tingkat tidak valid.');
        }

        $periode = trim((string) ($input['periode'] ?? 'all'));
        if (! in_array($periode, ['all', 'bulan_ini', '30_hari', 'custom'], true)) {
            return $this->fail('VALIDATION', 'Filter rentang waktu tidak valid.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone(self::TZ));
        $start = null;
        $end = null;

        if ($periode === 'bulan_ini') {
            $start = $now->modify('first day of this month')->format('Y-m-d');
            $end = $now->modify('last day of this month')->format('Y-m-d');
        } elseif ($periode === '30_hari') {
            $start = $now->modify('-29 days')->format('Y-m-d');
            $end = $now->format('Y-m-d');
        } elseif ($periode === 'custom') {
            $start = $this->validDate($input['tanggal_mulai'] ?? null);
            $end = $this->validDate($input['tanggal_selesai'] ?? null);
            if ($start === null || $end === null || $start > $end) {
                return $this->fail(
                    'VALIDATION',
                    'Rentang tanggal custom tidak valid.'
                );
            }
        }

        $kelasRows = $this->db
            ->table('kelas')
            ->select('id, tingkat, rombel, nama_kelas')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();

        $requestedClass = (int) ($input['id_kelas'] ?? 0);
        $classById = [];
        foreach ($kelasRows as $row) {
            $classById[(int) $row['id']] = $row;
        }

        if ($requestedClass > 0 && ! isset($classById[$requestedClass])) {
            return $this->fail(
                'VALIDATION',
                'Kelas tidak tersedia pada Tahun Ajaran terpilih.'
            );
        }
        if (
            $requestedClass > 0
            && $tingkat !== ''
            && (string) $classById[$requestedClass]['tingkat'] !== $tingkat
        ) {
            return $this->fail(
                'VALIDATION',
                'Kelas tidak sesuai dengan tingkat terpilih.'
            );
        }

        $kelasFilterActive = $requestedClass > 0 || $tingkat !== '';
        $kelasIds = [];
        foreach ($kelasRows as $row) {
            if ($requestedClass > 0 && (int) $row['id'] !== $requestedClass) {
                continue;
            }
            if ($tingkat !== '' && (string) $row['tingkat'] !== $tingkat) {
                continue;
            }
            $kelasIds[] = (int) $row['id'];
        }

        $studentBuilder = $this->db
            ->table('anggota_kelas')
            ->select('id_siswa')
            ->distinct()
            ->where('id_tahun', $idTahun);

        if ($kelasFilterActive) {
            if ($kelasIds === []) {
                $studentIds = [];
            } else {
                $studentIds = array_map(
                    'intval',
                    array_column(
                        $studentBuilder->whereIn('id_kelas', $kelasIds)
                            ->get()
                            ->getResultArray(),
                        'id_siswa'
                    )
                );
            }
        } else {
            $studentIds = array_map(
                'intval',
                array_column(
                    $studentBuilder->get()->getResultArray(),
                    'id_siswa'
                )
            );
        }

        return [
            'success' => true,
            'filters' => [
                'id_tahun' => $idTahun,
                'tahun' => $year,
                'periode' => $periode,
                'tanggal_mulai' => $start,
                'tanggal_selesai' => $end,
                'tingkat' => $tingkat,
                'id_kelas' => $requestedClass,
                'kelas_filter_active' => $kelasFilterActive,
                'kelas_ids' => array_values(array_unique($kelasIds)),
                'student_ids' => array_values(array_unique($studentIds)),
                'kelas_options' => $kelasRows,
                'tahun_options' => $years,
            ],
        ];
    }

    private function buildDataset(array $f): array
    {
        return [
            'meta' => $this->meta($f),
            'executive' => $this->executive($f),
            'composition' => $this->composition($f),
            'attendance' => $this->attendance($f),
            'ews' => $this->ews($f),
            'teaching' => $this->teaching($f),
            'discipline' => $this->discipline($f),
            'achievement' => $this->achievement($f),
            'uks' => $this->uks($f),
            'ptsp' => $this->ptsp($f),
            'mobility' => $this->mobility($f),
        ];
    }

    private function meta(array $f): array
    {
        $settings = $this->db
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['nama_sekolah'])
            ->get()
            ->getResultArray();
        $map = [];
        foreach ($settings as $row) {
            $map[(string) $row['setting_key']] = trim(
                (string) $row['setting_value']
            );
        }

        return [
            'nama_sekolah' => $map['nama_sekolah'] ?? 'MTsN 4 Jombang',
            'generated_at' => date('Y-m-d H:i:s'),
            'tahun_label' => trim(
                (string) ($f['tahun']['nama_tahun'] ?? '-')
                . ' - '
                . (string) ($f['tahun']['semester'] ?? '-')
            ),
            'periode_label' => $this->periodLabel($f),
            'kelas_label' => $this->classLabel($f),
        ];
    }

    private function executive(array $f): array
    {
        $studentBuilder = $this->db
            ->table('anggota_kelas ak')
            ->select('COUNT(DISTINCT ak.id_siswa) AS total', false)
            ->where('ak.id_tahun', $f['id_tahun']);
        $this->applyClassFilter($studentBuilder, 'ak.id_kelas', $f);
        $student = $studentBuilder->get()->getRowArray();

        $classBuilder = $this->db
            ->table('kelas')
            ->where('id_tahun', $f['id_tahun'])
            ->where('deleted_at', null);
        $this->applyClassFilter($classBuilder, 'id', $f);

        $cardBuilder = $this->db
            ->table('kartu_pelajar kp')
            ->select('COUNT(DISTINCT kp.id_siswa) AS total', false)
            ->where('kp.status_aktif', 'Aktif');
        $this->applyStudentFilter($cardBuilder, 'kp.id_siswa', $f, true);
        $card = $cardBuilder->get()->getRowArray();

        return [
            'siswa' => (int) ($student['total'] ?? 0),
            'guru' => $this->db->table('guru')
                ->where('deleted_at', null)
                ->countAllResults(),
            'pegawai' => $this->db->table('pegawai')
                ->where('deleted_at', null)
                ->countAllResults(),
            'kelas' => $classBuilder->countAllResults(),
            'mapel' => $this->db->table('mata_pelajaran')->countAllResults(),
            'kartu_aktif' => (int) ($card['total'] ?? 0),
        ];
    }

    private function composition(array $f): array
    {
        $levelBuilder = $this->db
            ->table('anggota_kelas ak')
            ->select('k.tingkat AS label, COUNT(DISTINCT ak.id_siswa) AS total', false)
            ->join('kelas k', 'k.id=ak.id_kelas AND k.deleted_at IS NULL')
            ->where('ak.id_tahun', $f['id_tahun']);
        $this->applyClassFilter($levelBuilder, 'ak.id_kelas', $f);
        $byLevel = $levelBuilder
            ->groupBy('k.tingkat')
            ->orderBy('k.tingkat', 'ASC')
            ->get()
            ->getResultArray();

        $genderBuilder = $this->db
            ->table('anggota_kelas ak')
            ->select(
                "CASE s.jenis_kelamin WHEN 'L' THEN 'Laki-laki' "
                . "WHEN 'P' THEN 'Perempuan' ELSE 'Lainnya' END AS label, "
                . 'COUNT(DISTINCT ak.id_siswa) AS total',
                false
            )
            ->join('siswa s', 's.id=ak.id_siswa AND s.deleted_at IS NULL')
            ->where('ak.id_tahun', $f['id_tahun']);
        $this->applyClassFilter($genderBuilder, 'ak.id_kelas', $f);
        $byGender = $genderBuilder
            ->groupBy('s.jenis_kelamin')
            ->orderBy('s.jenis_kelamin', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'by_level' => $this->normalizeLabelTotal($byLevel),
            'by_gender' => $this->normalizeLabelTotal($byGender),
        ];
    }

    private function attendance(array $f): array
    {
        $summaryBuilder = $this->db
            ->table('presensi p')
            ->select(
                "SUM(p.status='Hadir') AS hadir,"
                . "SUM(p.status='Sakit') AS sakit,"
                . "SUM(p.status='Izin') AS izin,"
                . "SUM(p.status='Alpha') AS alpha,"
                . 'COUNT(p.id) AS total',
                false
            )
            ->where('p.id_tahun', $f['id_tahun'])
            ->where('p.sesi', 'Sesi Awal');
        $this->applyDateFilter($summaryBuilder, 'p.tanggal', $f);
        $this->applyClassFilter($summaryBuilder, 'p.id_kelas', $f);
        $summary = $summaryBuilder->get()->getRowArray() ?: [];

        $trendBuilder = $this->db
            ->table('presensi p')
            ->select(
                "p.tanggal,"
                . "SUM(p.status='Hadir') AS hadir,"
                . "SUM(p.status='Sakit') AS sakit,"
                . "SUM(p.status='Izin') AS izin,"
                . "SUM(p.status='Alpha') AS alpha",
                false
            )
            ->where('p.id_tahun', $f['id_tahun'])
            ->where('p.sesi', 'Sesi Awal');
        $this->applyDateFilter($trendBuilder, 'p.tanggal', $f);
        $this->applyClassFilter($trendBuilder, 'p.id_kelas', $f);
        $trend = $trendBuilder
            ->groupBy('p.tanggal')
            ->orderBy('p.tanggal', 'ASC')
            ->get()
            ->getResultArray();

        $classBuilder = $this->db
            ->table('presensi p')
            ->select(
                'k.nama_kelas AS label, COUNT(p.id) AS total, '
                . "SUM(p.status='Hadir') AS hadir, "
                . "ROUND(100 * SUM(p.status='Hadir') / NULLIF(COUNT(p.id),0),1) AS persen_hadir",
                false
            )
            ->join('kelas k', 'k.id=p.id_kelas AND k.deleted_at IS NULL')
            ->where('p.id_tahun', $f['id_tahun'])
            ->where('p.sesi', 'Sesi Awal');
        $this->applyDateFilter($classBuilder, 'p.tanggal', $f);
        $this->applyClassFilter($classBuilder, 'p.id_kelas', $f);
        $byClass = $classBuilder
            ->groupBy('p.id_kelas, k.nama_kelas, k.tingkat, k.rombel')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'summary' => [
                'Hadir' => (int) ($summary['hadir'] ?? 0),
                'Sakit' => (int) ($summary['sakit'] ?? 0),
                'Izin' => (int) ($summary['izin'] ?? 0),
                'Alpha' => (int) ($summary['alpha'] ?? 0),
                'total' => (int) ($summary['total'] ?? 0),
            ],
            'trend' => array_map(
                static fn (array $row): array => [
                    'tanggal' => (string) ($row['tanggal'] ?? ''),
                    'Hadir' => (int) ($row['hadir'] ?? 0),
                    'Sakit' => (int) ($row['sakit'] ?? 0),
                    'Izin' => (int) ($row['izin'] ?? 0),
                    'Alpha' => (int) ($row['alpha'] ?? 0),
                ],
                $trend
            ),
            'by_class' => array_map(
                static fn (array $row): array => [
                    'label' => (string) ($row['label'] ?? '-'),
                    'total' => (int) ($row['total'] ?? 0),
                    'hadir' => (int) ($row['hadir'] ?? 0),
                    'persen_hadir' => (float) ($row['persen_hadir'] ?? 0),
                ],
                $byClass
            ),
        ];
    }

    private function ews(array $f): array
    {
        $anchorBuilder = $this->db
            ->table('presensi p')
            ->selectMax('p.tanggal', 'max_tanggal')
            ->where('p.id_tahun', $f['id_tahun'])
            ->where('p.sesi', 'Sesi Awal');
        $this->applyClassFilter($anchorBuilder, 'p.id_kelas', $f);
        if ($f['tanggal_selesai'] !== null) {
            $anchorBuilder->where('p.tanggal <=', $f['tanggal_selesai']);
        }
        $anchorRow = $anchorBuilder->get()->getRowArray();
        $anchor = $f['tanggal_selesai']
            ?? ($anchorRow['max_tanggal'] ?? null)
            ?? date('Y-m-d');

        $start = date(
            'Y-m-d',
            strtotime($anchor . ' -' . (self::EWS_DAYS - 1) . ' days')
        );

        $alphaBuilder = $this->db
            ->table('presensi p')
            ->select('p.id_siswa, COUNT(p.id) AS total_alpha', false)
            ->where('p.id_tahun', $f['id_tahun'])
            ->where('p.sesi', 'Sesi Awal')
            ->where('p.status', 'Alpha')
            ->where('p.tanggal >=', $start)
            ->where('p.tanggal <=', $anchor)
            ->where('p.id_siswa IS NOT NULL', null, false);
        $this->applyClassFilter($alphaBuilder, 'p.id_kelas', $f);
        $ewsCount = $alphaBuilder
            ->groupBy('p.id_siswa')
            ->having('COUNT(p.id) >=', 3)
            ->get()
            ->getNumRows();

        return [
            'window' => [
                'mulai' => $start,
                'selesai' => $anchor,
                'days' => self::EWS_DAYS,
            ],
            'ews_alpha_count' => $ewsCount,
            'top_sakit' => $this->topAttendanceStatus($f, $start, $anchor, 'Sakit'),
            'top_izin' => $this->topAttendanceStatus($f, $start, $anchor, 'Izin'),
            'top_alpha' => $this->topAttendanceStatus($f, $start, $anchor, 'Alpha'),
        ];
    }

    private function topAttendanceStatus(
        array $f,
        string $start,
        string $end,
        string $status
    ): array {
        $builder = $this->db
            ->table('presensi p')
            ->select(
                'p.id_siswa, MAX(p.nama_siswa_snapshot) AS nama_siswa, '
                . 'COALESCE(MAX(k.nama_kelas), \'-\') AS nama_kelas, '
                . 'COUNT(p.id) AS total',
                false
            )
            ->join('kelas k', 'k.id=p.id_kelas AND k.deleted_at IS NULL', 'left')
            ->where('p.id_tahun', $f['id_tahun'])
            ->where('p.sesi', 'Sesi Awal')
            ->where('p.status', $status)
            ->where('p.tanggal >=', $start)
            ->where('p.tanggal <=', $end)
            ->where('p.id_siswa IS NOT NULL', null, false);
        $this->applyClassFilter($builder, 'p.id_kelas', $f);

        return array_map(
            static fn (array $row): array => [
                'nama_siswa' => (string) ($row['nama_siswa'] ?? '-'),
                'nama_kelas' => (string) ($row['nama_kelas'] ?? '-'),
                'total' => (int) ($row['total'] ?? 0),
            ],
            $builder
                ->groupBy('p.id_siswa')
                ->orderBy('total', 'DESC')
                ->orderBy('nama_siswa', 'ASC')
                ->limit(self::EWS_LIMIT)
                ->get()
                ->getResultArray()
        );
    }

    private function teaching(array $f): array
    {
        $today = date('Y-m-d');
        $hari = $this->hariIndonesia();

        $scheduleBuilder = $this->db
            ->table('jadwal_guru jg')
            ->select('jg.id')
            ->where('jg.id_tahun', $f['id_tahun'])
            ->where('jg.hari', $hari)
            ->where('jg.status_jadwal', 'Aktif');
        $this->applyClassFilter($scheduleBuilder, 'jg.id_kelas', $f);
        $scheduleIds = array_map(
            'intval',
            array_column($scheduleBuilder->get()->getResultArray(), 'id')
        );

        $submitted = 0;
        if ($scheduleIds !== []) {
            $row = $this->db
                ->table('presensi_mengajar')
                ->select('COUNT(DISTINCT id_jadwal) AS total', false)
                ->where('id_tahun', $f['id_tahun'])
                ->where('tanggal', $today)
                ->whereIn('id_jadwal', $scheduleIds)
                ->get()
                ->getRowArray();
            $submitted = (int) ($row['total'] ?? 0);
        }

        $statusBuilder = $this->db
            ->table('presensi_mengajar pm')
            ->select('pm.status AS label, COUNT(pm.id) AS total', false)
            ->where('pm.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($statusBuilder, 'pm.tanggal', $f);
        $this->applyClassFilter($statusBuilder, 'pm.id_kelas', $f);
        $status = $statusBuilder
            ->groupBy('pm.status')
            ->orderBy('pm.status', 'ASC')
            ->get()
            ->getResultArray();

        $trendBuilder = $this->db
            ->table('presensi_mengajar pm')
            ->select('pm.tanggal, COUNT(pm.id) AS total', false)
            ->where('pm.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($trendBuilder, 'pm.tanggal', $f);
        $this->applyClassFilter($trendBuilder, 'pm.id_kelas', $f);
        $trend = $trendBuilder
            ->groupBy('pm.tanggal')
            ->orderBy('pm.tanggal', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'today' => [
                'tanggal' => $today,
                'wajib' => count($scheduleIds),
                'sudah' => $submitted,
                'belum' => max(0, count($scheduleIds) - $submitted),
            ],
            'status_distribution' => $this->normalizeLabelTotal($status),
            'trend' => array_map(
                static fn (array $row): array => [
                    'tanggal' => (string) ($row['tanggal'] ?? ''),
                    'total' => (int) ($row['total'] ?? 0),
                ],
                $trend
            ),
        ];
    }

    private function discipline(array $f): array
    {
        $base = $this->db
            ->table('catatan_kasus ck')
            ->where('ck.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($base, 'ck.tanggal', $f);
        $this->applyStudentFilter($base, 'ck.id_siswa', $f);
        $total = $base->countAllResults();

        $catBuilder = $this->db
            ->table('catatan_kasus ck')
            ->select('rp.kategori AS label, COUNT(ck.id) AS total', false)
            ->join('ref_pelanggaran rp', 'rp.id=ck.id_pelanggaran')
            ->where('ck.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($catBuilder, 'ck.tanggal', $f);
        $this->applyStudentFilter($catBuilder, 'ck.id_siswa', $f);
        $categories = $catBuilder
            ->groupBy('rp.kategori')
            ->orderBy('rp.kategori', 'ASC')
            ->get()
            ->getResultArray();

        $trendBuilder = $this->db
            ->table('catatan_kasus ck')
            ->select('ck.tanggal, COUNT(ck.id) AS total', false)
            ->where('ck.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($trendBuilder, 'ck.tanggal', $f);
        $this->applyStudentFilter($trendBuilder, 'ck.id_siswa', $f);
        $trend = $trendBuilder
            ->groupBy('ck.tanggal')
            ->orderBy('ck.tanggal', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'total' => $total,
            'categories' => $this->normalizeLabelTotal($categories),
            'trend' => $this->normalizeDateTotal($trend),
        ];
    }

    private function achievement(array $f): array
    {
        $base = $this->db
            ->table('catatan_prestasi cp')
            ->where('cp.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($base, 'cp.tanggal', $f);
        $this->applyStudentFilter($base, 'cp.id_siswa', $f);
        $total = $base->countAllResults();

        $levelBuilder = $this->db
            ->table('catatan_prestasi cp')
            ->select(
                "COALESCE(NULLIF(TRIM(cp.tingkat),''),'Tidak ditentukan') AS label, "
                . 'COUNT(cp.id) AS total',
                false
            )
            ->where('cp.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($levelBuilder, 'cp.tanggal', $f);
        $this->applyStudentFilter($levelBuilder, 'cp.id_siswa', $f);
        $levels = $levelBuilder
            ->groupBy('cp.tingkat')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $trendBuilder = $this->db
            ->table('catatan_prestasi cp')
            ->select('cp.tanggal, COUNT(cp.id) AS total', false)
            ->where('cp.id_tahun', $f['id_tahun']);
        $this->applyDateFilter($trendBuilder, 'cp.tanggal', $f);
        $this->applyStudentFilter($trendBuilder, 'cp.id_siswa', $f);
        $trend = $trendBuilder
            ->groupBy('cp.tanggal')
            ->orderBy('cp.tanggal', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'total' => $total,
            'levels' => $this->normalizeLabelTotal($levels),
            'trend' => $this->normalizeDateTotal($trend),
        ];
    }

    private function uks(array $f): array
    {
        $visitBuilder = $this->db
            ->table('uks_kunjungan u')
            ->where('u.id_tahun', $f['id_tahun'])
            ->where('u.deleted_at', null);
        $this->applyDateFilter($visitBuilder, 'u.tanggal', $f);
        $this->applyClassFilter($visitBuilder, 'u.id_kelas', $f);
        $visits = $visitBuilder->countAllResults();

        $ckgBuilder = $this->db
            ->table('uks_ckg c')
            ->where('c.id_tahun', $f['id_tahun'])
            ->where('c.deleted_at', null);
        $this->applyDateFilter($ckgBuilder, 'c.tanggal', $f);
        $this->applyClassFilter($ckgBuilder, 'c.id_kelas', $f);
        $ckg = $ckgBuilder->countAllResults();

        $refBuilder = $this->db
            ->table('uks_kunjungan u')
            ->join('uks_ref_hasil h', 'h.id=u.id_hasil')
            ->where('u.id_tahun', $f['id_tahun'])
            ->where('u.deleted_at', null)
            ->where('h.nama', 'Dirujuk ke klinik');
        $this->applyDateFilter($refBuilder, 'u.tanggal', $f);
        $this->applyClassFilter($refBuilder, 'u.id_kelas', $f);
        $referrals = $refBuilder->countAllResults();

        $resultBuilder = $this->db
            ->table('uks_kunjungan u')
            ->select('h.nama AS label, COUNT(u.id) AS total', false)
            ->join('uks_ref_hasil h', 'h.id=u.id_hasil')
            ->where('u.id_tahun', $f['id_tahun'])
            ->where('u.deleted_at', null);
        $this->applyDateFilter($resultBuilder, 'u.tanggal', $f);
        $this->applyClassFilter($resultBuilder, 'u.id_kelas', $f);
        $results = $resultBuilder
            ->groupBy('u.id_hasil, h.nama')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'kunjungan' => $visits,
            'ckg' => $ckg,
            'rujukan_klinik' => $referrals,
            'hasil' => $this->normalizeLabelTotal($results),
        ];
    }

    private function ptsp(array $f): array
    {
        $serviceBuilder = $this->db
            ->table('ptsp_layanan l')
            ->select('l.status AS label, COUNT(l.id) AS total', false)
            ->where('l.id_tahun', $f['id_tahun']);
        $this->applyDateTimeFilter($serviceBuilder, 'l.created_at', $f);
        $services = $serviceBuilder
            ->groupBy('l.status')
            ->orderBy('l.status', 'ASC')
            ->get()
            ->getResultArray();

        $complaintBuilder = $this->db
            ->table('ptsp_pengaduan p')
            ->select('p.status AS label, COUNT(p.id) AS total', false)
            ->where('p.id_tahun', $f['id_tahun']);
        $this->applyDateTimeFilter($complaintBuilder, 'p.created_at', $f);
        $complaints = $complaintBuilder
            ->groupBy('p.status')
            ->orderBy('p.status', 'ASC')
            ->get()
            ->getResultArray();

        $classBuilder = $this->db
            ->table('ptsp_pengaduan p')
            ->select('pk.klasifikasi AS label, COUNT(*) AS total', false)
            ->join(
                'ptsp_pengaduan_klasifikasi pk',
                'pk.id_pengaduan=p.id'
            )
            ->where('p.id_tahun', $f['id_tahun']);
        $this->applyDateTimeFilter($classBuilder, 'p.created_at', $f);
        $complaintClasses = $classBuilder
            ->groupBy('pk.klasifikasi')
            ->orderBy('pk.klasifikasi', 'ASC')
            ->get()
            ->getResultArray();

        $pollBuilder = $this->db
            ->table('ptsp_polling pp')
            ->select('AVG(pp.score) AS avg_score, COUNT(pp.id) AS total', false)
            ->where('pp.id_tahun', $f['id_tahun']);
        $this->applyDateTimeFilter($pollBuilder, 'pp.created_at', $f);
        $poll = $pollBuilder->get()->getRowArray() ?: [];

        $scoreBuilder = $this->db
            ->table('ptsp_polling pp')
            ->select('pp.score AS label, COUNT(pp.id) AS total', false)
            ->where('pp.id_tahun', $f['id_tahun']);
        $this->applyDateTimeFilter($scoreBuilder, 'pp.created_at', $f);
        $scores = $scoreBuilder
            ->groupBy('pp.score')
            ->orderBy('pp.score', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'layanan_status' => $this->normalizeLabelTotal($services),
            'pengaduan_status' => $this->normalizeLabelTotal($complaints),
            'pengaduan_klasifikasi' => $this->normalizeLabelTotal($complaintClasses),
            'polling' => [
                'total' => (int) ($poll['total'] ?? 0),
                'avg_score' => isset($poll['avg_score'])
                    && $poll['avg_score'] !== null
                    ? round((float) $poll['avg_score'], 2)
                    : null,
                'scores' => $this->normalizeLabelTotal($scores),
            ],
        ];
    }

    private function mobility(array $f): array
    {
        $builder = $this->db
            ->table('riwayat_siswa rs')
            ->select('rs.id, rs.id_siswa, rs.id_kelas, rs.status')
            ->where('rs.id_tahun', $f['id_tahun']);
        $this->applyClassFilter($builder, 'rs.id_kelas', $f);
        $rows = $builder
            ->orderBy('rs.id_siswa', 'ASC')
            ->orderBy('rs.id', 'DESC')
            ->get()
            ->getResultArray();

        $seen = [];
        $counts = [];
        foreach ($rows as $row) {
            $studentId = (int) ($row['id_siswa'] ?? 0);
            if ($studentId <= 0 || isset($seen[$studentId])) {
                continue;
            }
            $seen[$studentId] = true;
            $status = trim((string) ($row['status'] ?? 'Tidak diketahui'));
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        ksort($counts);
        $result = [];
        foreach ($counts as $label => $total) {
            $result[] = ['label' => $label, 'total' => (int) $total];
        }

        return ['status' => $result];
    }

    private function filterOptions(array $f): array
    {
        return [
            'tahun' => array_map(
                static fn (array $row): array => [
                    'id' => (int) ($row['id'] ?? 0),
                    'label' => trim(
                        (string) ($row['nama_tahun'] ?? '-')
                        . ' - '
                        . (string) ($row['semester'] ?? '-')
                    ),
                    'aktif' => ! empty($row['status_aktif']),
                ],
                $f['tahun_options']
            ),
            'kelas' => array_map(
                static fn (array $row): array => [
                    'id' => (int) ($row['id'] ?? 0),
                    'tingkat' => (string) ($row['tingkat'] ?? ''),
                    'nama' => (string) ($row['nama_kelas'] ?? '-'),
                ],
                $f['kelas_options']
            ),
        ];
    }

    private function publicFilters(array $f): array
    {
        return [
            'id_tahun' => $f['id_tahun'],
            'periode' => $f['periode'],
            'tanggal_mulai' => $f['tanggal_mulai'],
            'tanggal_selesai' => $f['tanggal_selesai'],
            'tingkat' => $f['tingkat'],
            'id_kelas' => $f['id_kelas'],
        ];
    }

    private function applyClassFilter($builder, string $field, array $f): void
    {
        if (! $f['kelas_filter_active']) {
            return;
        }

        if ($f['kelas_ids'] === []) {
            $builder->where('1=0', null, false);
            return;
        }

        $builder->whereIn($field, $f['kelas_ids']);
    }

    private function applyStudentFilter(
        $builder,
        string $field,
        array $f,
        bool $alwaysYearScope = false
    ): void {
        if (! $f['kelas_filter_active'] && ! $alwaysYearScope) {
            return;
        }

        if ($f['student_ids'] === []) {
            $builder->where('1=0', null, false);
            return;
        }

        $builder->whereIn($field, $f['student_ids']);
    }

    private function applyDateFilter($builder, string $field, array $f): void
    {
        if ($f['tanggal_mulai'] !== null) {
            $builder->where($field . ' >=', $f['tanggal_mulai']);
        }
        if ($f['tanggal_selesai'] !== null) {
            $builder->where($field . ' <=', $f['tanggal_selesai']);
        }
    }

    private function applyDateTimeFilter($builder, string $field, array $f): void
    {
        if ($f['tanggal_mulai'] !== null) {
            $builder->where($field . ' >=', $f['tanggal_mulai'] . ' 00:00:00');
        }
        if ($f['tanggal_selesai'] !== null) {
            $builder->where($field . ' <=', $f['tanggal_selesai'] . ' 23:59:59');
        }
    }

    private function normalizeLabelTotal(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'label' => (string) ($row['label'] ?? '-'),
                'total' => (int) ($row['total'] ?? 0),
            ],
            $rows
        );
    }

    private function normalizeDateTotal(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'tanggal' => (string) ($row['tanggal'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
            ],
            $rows
        );
    }

    private function validDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }

    private function periodLabel(array $f): string
    {
        return match ($f['periode']) {
            'bulan_ini' => 'Bulan Ini',
            '30_hari' => '30 Hari Terakhir',
            'custom' => (string) $f['tanggal_mulai']
                . ' s.d. '
                . (string) $f['tanggal_selesai'],
            default => 'Seluruh Periode Tahun Ajaran',
        };
    }

    private function classLabel(array $f): string
    {
        if ($f['id_kelas'] > 0) {
            foreach ($f['kelas_options'] as $row) {
                if ((int) ($row['id'] ?? 0) === $f['id_kelas']) {
                    return (string) ($row['nama_kelas'] ?? '-');
                }
            }
        }

        return $f['tingkat'] !== ''
            ? 'Tingkat ' . $f['tingkat']
            : 'Semua Kelas';
    }

    private function hariIndonesia(): string
    {
        return match ((int) date('N')) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
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
