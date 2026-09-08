<?php

namespace App\Services;

use App\Models\PresensiMengajarModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

/**
 * PresensiMengajarService
 *
 * Business rule Presensi Mengajar / Jurnal.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/03_AUTH_RBAC_MENU
 * - docs/05_PRESENSI
 *
 * Rule utama:
 * - semua Jadwal Aktif (Sesi Awal/Akhir/Non Sesi) membutuhkan Jurnal;
 * - Guru/Pimpinan input diri berdasarkan Jadwal milik sendiri;
 * - Admin/Operator scope SEMUA dapat input/revisi atas nama Guru;
 * - selector awal Admin/Operator adalah Nama Guru;
 * - id_jadwal tetap menjadi identitas operasional Jurnal;
 * - Guru biasa tidak dapat revisi;
 * - status Hadir Guru wajib geofence bila setting aktif;
 * - Izin/Sakit tidak wajib geofence;
 * - seluruh input non-SEMUA terikat time-window;
 * - Wali tidak mendapat bypass khusus karena Jurnal melekat pada Jadwal Guru.
 */
class PresensiMengajarService
{
    private const TZ = 'Asia/Jakarta';
    private const STATUS_VALID = ['Hadir', 'Izin', 'Sakit'];

    protected BaseConnection $db;
    protected PresensiMengajarModel $model;
    protected AuthService $authService;
    protected GeofencingService $geofencingService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->model = new PresensiMengajarModel();
        $this->authService = new AuthService();
        $this->geofencingService = new GeofencingService();
    }

    public function getTahunAktifInfo(): ?array
    {
        return $this->getTahunAktif();
    }

    /**
     * Daftar Guru yang dapat dipilih pada workflow input.
     *
     * Scope SEMUA:
     * - seluruh Guru yang memiliki Jadwal Aktif pada hari tanggal target.
     *
     * Scope diri:
     * - hanya identitas Guru user sendiri.
     */
    public function getGuruInputOptions(int $userId, string $tanggal): array
    {
        $tanggal = $this->normalizeTanggal($tanggal);

        if ($tanggal === '') {
            return [];
        }

        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [];
        }

        $scopes = $this->getPermissionScopes('presensi_mengajar.input', $userId);

        if ($scopes === []) {
            return [];
        }

        $hari = $this->hariIndonesia($tanggal);

        if (in_array('SEMUA', $scopes, true)) {
            return $this->db
                ->table('jadwal_guru jg')
                ->select('g.id, g.nama, g.nip')
                ->join('guru g', 'g.id = jg.id_guru')
                ->where('jg.id_tahun', (int) $tahun['id'])
                ->where('jg.hari', $hari)
                ->where('jg.status_jadwal', 'Aktif')
                ->where('g.deleted_at', null)
                ->groupBy('g.id, g.nama, g.nip')
                ->orderBy('g.nama', 'ASC')
                ->get()
                ->getResultArray();
        }

        if (! $this->hasSelfInputScope($scopes)) {
            return [];
        }

        if ($tanggal !== $this->today()) {
            return [];
        }

        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);

        if ($idGuru <= 0) {
            return [];
        }

        $row = $this->db
            ->table('jadwal_guru jg')
            ->select('g.id, g.nama, g.nip')
            ->join('guru g', 'g.id = jg.id_guru')
            ->where('jg.id_guru', $idGuru)
            ->where('jg.id_tahun', (int) $tahun['id'])
            ->where('jg.hari', $hari)
            ->where('jg.status_jadwal', 'Aktif')
            ->where('g.deleted_at', null)
            ->groupBy('g.id, g.nama, g.nip')
            ->get()
            ->getRowArray();

        return $row ? [$row] : [];
    }

    /**
     * Jadwal Aktif milik satu Guru pada tanggal target.
     *
     * Service tetap memastikan actor berhak melihat Guru tersebut.
     */
    public function getJadwalByGuruTanggal(
        int $userId,
        int $idGuru,
        string $tanggal
    ): array {
        $tanggal = $this->normalizeTanggal($tanggal);

        if ($tanggal === '' || $idGuru <= 0) {
            return [];
        }

        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [];
        }

        $scopes = $this->getPermissionScopes('presensi_mengajar.input', $userId);

        if ($scopes === []) {
            return [];
        }

        if (! in_array('SEMUA', $scopes, true)) {
            if (! $this->hasSelfInputScope($scopes) || $tanggal !== $this->today()) {
                return [];
            }

            $user = $this->getUser($userId);

            if ((int) ($user['id_guru'] ?? 0) !== $idGuru) {
                return [];
            }
        }

        return $this->db
            ->table('jadwal_guru jg')
            ->select([
                'jg.id',
                'jg.id_guru',
                'jg.id_kelas',
                'jg.id_mapel',
                'jg.id_tahun',
                'jg.hari',
                'jg.jam_mulai',
                'jg.jam_selesai',
                'jg.sesi',
                'g.nama AS nama_guru',
                'g.nip',
                'k.nama_kelas',
                'mp.nama_mapel',
                'mp.kode_mapel',
            ])
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id_guru', $idGuru)
            ->where('jg.id_tahun', (int) $tahun['id'])
            ->where('jg.hari', $this->hariIndonesia($tanggal))
            ->where('jg.status_jadwal', 'Aktif')
            ->where('g.deleted_at', null)
            ->where('k.deleted_at', null)
            ->orderBy('jg.jam_mulai', 'ASC')
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function loadInput(int $userId, int $idJadwal, string $tanggal): array
    {
        $tanggal = $this->normalizeTanggal($tanggal);

        if ($tanggal === '' || $idJadwal <= 0) {
            return $this->fail('INVALID_REQUEST', 'Jadwal atau tanggal tidak valid.');
        }

        $context = $this->resolveScheduleContext($userId, $idJadwal, $tanggal);

        if (! $context['success']) {
            return $context;
        }

        $existing = $this->model->findByJadwalTanggal($idJadwal, $tanggal);
        $canRevise = $context['capability'] === 'SEMUA';

        if ($existing !== null && ! $canRevise) {
            return [
                'success' => false,
                'code' => 'ALREADY_SUBMITTED',
                'message' => 'Jurnal untuk Jadwal dan tanggal ini sudah tersimpan. Guru tidak dapat merevisi Jurnal.',
                'submitted' => true,
            ];
        }

        return [
            'success' => true,
            'message' => 'Form Jurnal siap digunakan.',
            'tanggal' => $tanggal,
            'capability' => $context['capability'],
            'can_revise' => $canRevise,
            'submitted' => $existing !== null,
            'jadwal' => $context['jadwal'],
            'existing' => $existing,
        ];
    }

    public function save(int $userId, array $data): array
    {
        $idJadwal = (int) ($data['id_jadwal'] ?? 0);
        $tanggal = $this->normalizeTanggal((string) ($data['tanggal'] ?? ''));
        $status = trim((string) ($data['status'] ?? ''));
        $materi = trim((string) ($data['materi'] ?? ''));

        if ($idJadwal <= 0 || $tanggal === '') {
            return $this->fail('INVALID_REQUEST', 'Jadwal atau tanggal tidak valid.');
        }

        if (! in_array($status, self::STATUS_VALID, true)) {
            return $this->fail('INVALID_STATUS', 'Status hanya boleh Hadir, Izin, atau Sakit.');
        }

        if ($materi === '') {
            return $this->fail('EMPTY_MATERI', 'Materi/keterangan Jurnal wajib diisi.');
        }

        $context = $this->resolveScheduleContext($userId, $idJadwal, $tanggal);

        if (! $context['success']) {
            return $context;
        }

        $existing = $this->model->findByJadwalTanggal($idJadwal, $tanggal);
        $isRevision = $existing !== null;

        if ($isRevision && $context['capability'] !== 'SEMUA') {
            return $this->fail(
                'FORBIDDEN_REVISE',
                'Jurnal yang sudah tersimpan hanya dapat direvisi Admin/Operator.'
            );
        }

        if ($context['capability'] !== 'SEMUA' && $status === 'Hadir') {
            $geo = $this->geofencingService->validateRequired(
                $data['latitude'] ?? null,
                $data['longitude'] ?? null
            );

            if (! $geo['success']) {
                return [
                    'success' => false,
                    'code' => 'GEOFENCE_FAILED',
                    'message' => $geo['message'],
                    'geofence' => $geo,
                ];
            }
        }

        $jadwal = $context['jadwal'];
        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');

        $payload = [
            'id_guru' => (int) $jadwal['id_guru'],
            'nama_guru_snapshot' => (string) $jadwal['nama_guru'],
            'id_jadwal' => $idJadwal,
            'id_kelas' => (int) $jadwal['id_kelas'],
            'id_tahun' => (int) $jadwal['id_tahun'],
            'tanggal' => $tanggal,
            'status' => $status,
            'materi' => $materi,
        ];

        $this->db->transBegin();

        try {
            if ($isRevision) {
                $payload['updated_at'] = $now;
                $payload['updated_by'] = $userId;

                $ok = $this->db
                    ->table('presensi_mengajar')
                    ->where('id', (int) $existing['id'])
                    ->update($payload);
            } else {
                $payload['created_at'] = $now;
                $payload['updated_at'] = $now;
                $payload['updated_by'] = null;

                $ok = $this->db
                    ->table('presensi_mengajar')
                    ->insert($payload);
            }

            if ($ok === false) {
                throw new \RuntimeException('Penyimpanan Jurnal gagal.');
            }

            $this->writeActivityLog(
                $userId,
                $isRevision ? 'REVISI' : 'INPUT',
                'Presensi Mengajar',
                sprintf(
                    '%s Jurnal %s - %s tanggal %s (%s).',
                    $isRevision ? 'Revisi' : 'Input',
                    (string) $jadwal['nama_guru'],
                    (string) $jadwal['nama_kelas'],
                    $tanggal,
                    $status
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction Jurnal gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'code' => 'SAVE_FAILED',
                'message' => 'Jurnal gagal disimpan.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }

        return [
            'success' => true,
            'message' => $isRevision
                ? 'Revisi Jurnal berhasil disimpan.'
                : 'Jurnal berhasil disimpan.',
            'revision' => $isRevision,
            'capability' => $context['capability'],
        ];
    }

    public function getHistori(
        int $userId,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit = 50,
        int $offset = 0,
        ?string $status = null
    ): array {
        $tanggalMulai = $this->normalizeTanggal($tanggalMulai);
        $tanggalSelesai = $this->normalizeTanggal($tanggalSelesai);

        if ($tanggalMulai === '' || $tanggalSelesai === '' || $tanggalMulai > $tanggalSelesai) {
            return $this->fail('INVALID_PERIOD', 'Periode Jurnal tidak valid.');
        }

        if ($status !== null && ! in_array($status, self::STATUS_VALID, true)) {
            return $this->fail('INVALID_STATUS', 'Filter status Jurnal tidak valid.');
        }

        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $scopes = $this->getPermissionScopes('presensi_mengajar.view', $userId);

        if ($scopes === []) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak melihat Jurnal.');
        }

        $idGuru = null;

        if (! in_array('SEMUA', $scopes, true)) {
            if (! in_array('DIRI_SENDIRI', $scopes, true)) {
                return $this->fail('FORBIDDEN', 'Anda tidak memiliki scope melihat Jurnal.');
            }

            $user = $this->getUser($userId);
            $idGuru = (int) ($user['id_guru'] ?? 0);

            if ($idGuru <= 0) {
                return $this->fail(
                    'NO_GURU_IDENTITY',
                    'User tidak mempunyai identitas Guru untuk melihat Jurnal diri.'
                );
            }
        }

        $rows = $this->model->getHistoriPaged(
            (int) $tahun['id'],
            $tanggalMulai,
            $tanggalSelesai,
            max(1, min(500, $limit)),
            max(0, $offset),
            $idGuru,
            $status
        );

        $total = $this->model->countHistori(
            (int) $tahun['id'],
            $tanggalMulai,
            $tanggalSelesai,
            $idGuru,
            $status
        );

        return [
            'success' => true,
            'message' => 'Histori Jurnal berhasil dimuat.',
            'rows' => $rows,
            'total' => $total,
            'limit' => max(1, min(500, $limit)),
            'offset' => max(0, $offset),
        ];
    }

    private function resolveScheduleContext(
        int $userId,
        int $idJadwal,
        string $tanggal
    ): array {
        if ($userId <= 0) {
            return $this->fail('UNAUTHENTICATED', 'Session user tidak valid.');
        }

        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $jadwal = $this->getJadwalAktif($idJadwal, (int) $tahun['id']);

        if ($jadwal === null) {
            return $this->fail(
                'INVALID_SCHEDULE',
                'Jadwal tidak ditemukan atau sudah tidak aktif.'
            );
        }

        if ((string) $jadwal['hari'] !== $this->hariIndonesia($tanggal)) {
            return $this->fail(
                'SCHEDULE_DAY_MISMATCH',
                'Tanggal tidak sesuai dengan hari pada Jadwal Guru.'
            );
        }

        $scopes = $this->getPermissionScopes('presensi_mengajar.input', $userId);

        if (in_array('SEMUA', $scopes, true)) {
            return [
                'success' => true,
                'capability' => 'SEMUA',
                'jadwal' => $jadwal,
            ];
        }

        if (! $this->hasSelfInputScope($scopes)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak input Jurnal.');
        }

        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);

        if ($idGuru <= 0) {
            return $this->fail(
                'NO_GURU_IDENTITY',
                'User tidak mempunyai identitas Guru.'
            );
        }

        if ($idGuru !== (int) $jadwal['id_guru']) {
            return $this->fail(
                'FORBIDDEN',
                'Guru hanya dapat mengisi Jurnal untuk Jadwal miliknya sendiri.'
            );
        }

        if ($tanggal !== $this->today()) {
            return $this->fail(
                'OUTSIDE_SCHEDULE_DATE',
                'Guru hanya dapat mengisi Jurnal melalui Jadwal tanggal berjalan.'
            );
        }

        $window = $this->evaluateTimeWindow($jadwal, $tanggal);

        if ($window['state'] === 'NOT_STARTED') {
            return $this->fail(
                'TIME_WINDOW_NOT_STARTED',
                'Waktu input Jurnal belum dimulai.'
            );
        }

        if ($window['state'] === 'ENDED') {
            return $this->fail(
                'TIME_WINDOW_ENDED',
                'Waktu input Jurnal sudah berakhir.'
            );
        }

        return [
            'success' => true,
            'capability' => 'DIRI_SENDIRI',
            'jadwal' => $jadwal,
            'window' => $window,
        ];
    }

    private function getJadwalAktif(int $idJadwal, int $idTahun): ?array
    {
        $row = $this->db
            ->table('jadwal_guru jg')
            ->select([
                'jg.id',
                'jg.id_guru',
                'jg.id_kelas',
                'jg.id_mapel',
                'jg.id_tahun',
                'jg.hari',
                'jg.jam_mulai',
                'jg.jam_selesai',
                'jg.sesi',
                'jg.status_jadwal',
                'g.nama AS nama_guru',
                'g.nip',
                'k.nama_kelas',
                'mp.nama_mapel',
                'mp.kode_mapel',
            ])
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id', $idJadwal)
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.status_jadwal', 'Aktif')
            ->where('g.deleted_at', null)
            ->where('k.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
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

    private function getUser(int $userId): array
    {
        $row = $this->db
            ->table('users')
            ->select('id, username, role, id_guru, id_pegawai, id_siswa, status_aktif')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        return $row ?: [];
    }

    /**
     * @return string[]
     */
    private function getPermissionScopes(string $permissionKey, int $userId): array
    {
        $roles = $this->authService->getUserRoles($userId);

        if ($roles === []) {
            return [];
        }

        $rows = $this->db
            ->table('role_permissions rp')
            ->select('rp.scope')
            ->join('permissions p', 'p.id = rp.id_permission')
            ->whereIn('rp.role', $roles)
            ->where('p.permission_key', $permissionKey)
            ->get()
            ->getResultArray();

        $scopes = [];

        foreach ($rows as $row) {
            $scope = trim((string) ($row['scope'] ?? ''));

            if ($scope !== '' && $scope !== 'TIDAK_ADA') {
                $scopes[$scope] = $scope;
            }
        }

        return array_values($scopes);
    }

    private function hasSelfInputScope(array $scopes): bool
    {
        return in_array('DIRI_SENDIRI', $scopes, true)
            || in_array('KELAS_TERJADWAL', $scopes, true);
    }

    /**
     * @return array{state:string,start:string,end:string}
     */
    private function evaluateTimeWindow(array $jadwal, string $tanggal): array
    {
        $tz = new \DateTimeZone(self::TZ);
        $now = new \DateTimeImmutable('now', $tz);
        $start = new \DateTimeImmutable(
            $tanggal . ' ' . (string) $jadwal['jam_mulai'],
            $tz
        );
        $end = new \DateTimeImmutable(
            $tanggal . ' ' . (string) $jadwal['jam_selesai'],
            $tz
        );
        $endPlus = $end->modify('+15 minutes');

        if ($now < $start) {
            $state = 'NOT_STARTED';
        } elseif ($now > $endPlus) {
            $state = 'ENDED';
        } else {
            $state = 'VALID';
        }

        return [
            'state' => $state,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $endPlus->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizeTanggal(string $tanggal): string
    {
        $tanggal = trim($tanggal);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $tanggal
        ) {
            return '';
        }

        return $tanggal;
    }

    private function today(): string
    {
        return Time::now(self::TZ)->format('Y-m-d');
    }

    private function hariIndonesia(string $tanggal): string
    {
        $date = new \DateTimeImmutable(
            $tanggal,
            new \DateTimeZone(self::TZ)
        );

        return match ((int) $date->format('N')) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
    }

    private function writeActivityLog(
        int $userId,
        string $aksi,
        string $modul,
        string $keterangan
    ): void {
        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $userId,
                'aksi' => $aksi,
                'modul' => $modul,
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
