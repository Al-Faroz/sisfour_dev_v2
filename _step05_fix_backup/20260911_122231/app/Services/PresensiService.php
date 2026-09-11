<?php

namespace App\Services;

use App\Models\PresensiModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

/**
 * PresensiService
 *
 * Business rule Presensi Siswa.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/03_AUTH_RBAC_MENU
 * - docs/05_PRESENSI
 */
class PresensiService
{
    private const TZ = 'Asia/Jakarta';
    private const STATUS_VALID = ['Hadir', 'Sakit', 'Izin', 'Alpha'];
    private const SESI_VALID = ['Sesi Awal', 'Sesi Akhir'];

    protected BaseConnection $db;
    protected PresensiModel $presensiModel;
    protected AuthService $authService;
    protected GeofencingService $geofencingService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->presensiModel = new PresensiModel();
        $this->authService = new AuthService();
        $this->geofencingService = new GeofencingService();
    }

    /**
     * Opsi kelas yang dapat dipakai untuk workflow input Presensi.
     *
     * Admin/Operator SEMUA melihat seluruh kelas tahun aktif.
     * Guru hanya kelas terjadwal pada hari target.
     * Wali mendapat tambahan kelas Wali aktif.
     */
    public function getKelasInputOptions(int $userId, string $tanggal): array
    {
        $tanggal = $this->normalizeTanggal($tanggal);

        if ($tanggal === '') {
            return [];
        }

        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [];
        }

        $scopes = $this->getPermissionScopes('presensi_siswa.input', $userId);

        if (in_array('SEMUA', $scopes, true)) {
            return $this->db
                ->table('kelas')
                ->select('id, nama_kelas, tingkat, rombel')
                ->where('id_tahun', (int) $tahun['id'])
                ->where('deleted_at', null)
                ->orderBy('tingkat', 'ASC')
                ->orderBy('rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);

        if ($idGuru <= 0) {
            return [];
        }

        $ids = [];

        if (in_array('KELAS_DIAMPU', $scopes, true)) {
            foreach ($this->authService->getKelasDiampu($idGuru, (int) $tahun['id']) as $idKelas) {
                $ids[(int) $idKelas] = (int) $idKelas;
            }
        }

        if (
            in_array('KELAS_TERJADWAL', $scopes, true)
            && $tanggal === $this->today()
        ) {
            $hari = $this->hariIndonesia($tanggal);

            $rows = $this->db
                ->table('jadwal_guru')
                ->select('id_kelas')
                ->where('id_guru', $idGuru)
                ->where('id_tahun', (int) $tahun['id'])
                ->where('hari', $hari)
                ->where('status_jadwal', 'Aktif')
                ->whereIn('sesi', self::SESI_VALID)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $idKelas = (int) ($row['id_kelas'] ?? 0);

                if ($idKelas > 0) {
                    $ids[$idKelas] = $idKelas;
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        return $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', (int) $tahun['id'])
            ->where('deleted_at', null)
            ->whereIn('id', array_values($ids))
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Opsi kelas untuk histori/rekap berdasarkan permission view.
     */
    public function getKelasViewOptions(int $userId): array
    {
        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [];
        }

        $scopes = $this->getPermissionScopes('presensi_siswa.view', $userId);
        $builder = $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', (int) $tahun['id'])
            ->where('deleted_at', null);

        if (in_array('SEMUA', $scopes, true)) {
            return $builder
                ->orderBy('tingkat', 'ASC')
                ->orderBy('rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        if (in_array('KELAS_DIAMPU', $scopes, true)) {
            $user = $this->getUser($userId);
            $idGuru = (int) ($user['id_guru'] ?? 0);
            $kelas = $this->authService->getKelasDiampu($idGuru, (int) $tahun['id']);

            if ($kelas === []) {
                return [];
            }

            return $builder
                ->whereIn('id', $kelas)
                ->orderBy('tingkat', 'ASC')
                ->orderBy('rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        return [];
    }

    /**
     * Informasi Tahun Ajaran aktif untuk header UI.
     */
    public function getTahunAktifInfo(): ?array
    {
        return $this->getTahunAktif();
    }

    /**
     * Menentukan apakah user adalah akun siswa dengan view DIRI_SENDIRI.
     */
    public function isSiswaSelfView(int $userId): bool
    {
        $scopes = $this->getPermissionScopes('presensi_siswa.view', $userId);
        $user = $this->getUser($userId);

        return in_array('DIRI_SENDIRI', $scopes, true)
            && (int) ($user['id_siswa'] ?? 0) > 0;
    }

    /**
     * Load form input/revisi Presensi Siswa.
     */
    public function loadInput(
        int $userId,
        int $idKelas,
        string $tanggal,
        string $sesi
    ): array {
        $tanggal = $this->normalizeTanggal($tanggal);
        $sesi = $this->normalizeSesi($sesi);

        if ($tanggal === '' || $sesi === '') {
            return [
                'success' => false,
                'code' => 'INVALID_REQUEST',
                'message' => 'Tanggal atau sesi Presensi tidak valid.',
            ];
        }

        $context = $this->resolveBaseContext($userId, $idKelas, $tanggal);

        if (! $context['success']) {
            return $context;
        }

        $capability = $this->resolveInputCapability(
            $userId,
            (int) $context['id_tahun'],
            $idKelas,
            $tanggal,
            $sesi
        );

        if (! $capability['success']) {
            return $capability;
        }

        $existing = $this->presensiModel->getByKelasTanggalSesi(
            $idKelas,
            $tanggal,
            $sesi
        );

        if ($existing !== [] && $capability['capability'] === 'GURU_TERJADWAL') {
            return [
                'success' => false,
                'code' => 'ALREADY_SUBMITTED',
                'message' => 'Presensi untuk kelas, tanggal, dan sesi ini sudah tersimpan.',
                'submitted' => true,
            ];
        }

        $canRevise = false;

        if ($existing !== []) {
            $canRevise = $this->canRevise(
                $userId,
                (int) $context['id_tahun'],
                $idKelas
            );

            if (! $canRevise) {
                return [
                    'success' => false,
                    'code' => 'FORBIDDEN_REVISE',
                    'message' => 'Anda tidak memiliki hak untuk membuka atau merevisi Presensi tersimpan.',
                ];
            }
        }

        $roster = $this->getRosterForDate(
            (int) $context['id_tahun'],
            $idKelas,
            $tanggal
        );

        if ($roster === []) {
            return [
                'success' => false,
                'code' => 'EMPTY_ROSTER',
                'message' => 'Tidak ada siswa yang valid pada kelas dan tanggal tersebut.',
            ];
        }

        $existingMap = [];

        foreach ($existing as $row) {
            $idSiswa = (int) ($row['id_siswa'] ?? 0);

            if ($idSiswa > 0) {
                $existingMap[$idSiswa] = $row;
            }
        }

        $items = [];

        foreach ($roster as $siswa) {
            $idSiswa = (int) $siswa['id'];
            $saved = $existingMap[$idSiswa] ?? null;

            $items[] = [
                'id_siswa' => $idSiswa,
                'nisn' => $siswa['nisn'],
                'nama' => $siswa['nama'],
                'status' => $saved['status'] ?? 'Hadir',
                'saved' => $saved !== null,
            ];
        }

        return [
            'success' => true,
            'message' => 'Data Presensi siap digunakan.',
            'id_tahun' => (int) $context['id_tahun'],
            'kelas' => $context['kelas'],
            'tanggal' => $tanggal,
            'sesi' => $sesi,
            'capability' => $capability['capability'],
            'geofence_required' => $capability['capability'] === 'GURU_TERJADWAL'
                && (bool) ($this->geofencingService->getConfig()['aktif'] ?? false),
            'submitted' => $existing !== [],
            'can_revise' => $canRevise,
            'items' => $items,
        ];
    }

    /**
     * Bulk save satu kelas secara atomic.
     */
    public function saveBulk(
        int $userId,
        array $data,
        bool $revisionRequested = false
    ): array {

        $idKelas = (int) ($data['id_kelas'] ?? 0);
        $tanggal = $this->normalizeTanggal((string) ($data['tanggal'] ?? ''));
        $sesi = $this->normalizeSesi((string) ($data['sesi'] ?? ''));

        if ($tanggal === '' || $sesi === '') {
            return [
                'success' => false,
                'code' => 'INVALID_REQUEST',
                'message' => 'Tanggal atau sesi Presensi tidak valid.',
            ];
        }

        if ($idKelas <= 0) {
            return [
                'success' => false,
                'code' => 'INVALID_CLASS',
                'message' => 'Kelas tidak valid.',
            ];
        }

        $context = $this->resolveBaseContext($userId, $idKelas, $tanggal);

        if (! $context['success']) {
            return $context;
        }

        $capability = $this->resolveInputCapability(
            $userId,
            (int) $context['id_tahun'],
            $idKelas,
            $tanggal,
            $sesi
        );

        if (! $capability['success']) {
            return $capability;
        }

        $roster = $this->getRosterForDate(
            (int) $context['id_tahun'],
            $idKelas,
            $tanggal
        );

        if ($roster === []) {
            return [
                'success' => false,
                'code' => 'EMPTY_ROSTER',
                'message' => 'Tidak ada siswa yang valid pada kelas dan tanggal tersebut.',
            ];
        }

        $submitted = $this->normalizeSubmittedStatuses($data['items'] ?? []);
        $rosterMap = [];

        foreach ($roster as $siswa) {
            $rosterMap[(int) $siswa['id']] = $siswa;
        }

        if (count($submitted) !== count($rosterMap)) {
            return [
                'success' => false,
                'code' => 'ROSTER_MISMATCH',
                'message' => 'Jumlah siswa yang dikirim tidak sesuai roster kelas pada tanggal tersebut.',
            ];
        }

        foreach ($submitted as $idSiswa => $status) {
            if (! isset($rosterMap[$idSiswa])) {
                return [
                    'success' => false,
                    'code' => 'ROSTER_MISMATCH',
                    'message' => 'Request mengandung siswa yang bukan anggota kelas pada tanggal tersebut.',
                ];
            }
        }

        $existing = $this->presensiModel->getByKelasTanggalSesi(
            $idKelas,
            $tanggal,
            $sesi
        );

        $isRevision = $existing !== [];

        if ($isRevision && ! $revisionRequested) {
            return [
                'success' => false,
                'code' => 'REVISION_ENDPOINT_REQUIRED',
                'message' => 'Presensi sudah tersimpan. Gunakan endpoint revisi untuk mengubah data.',
            ];
        }

        if (! $isRevision && $revisionRequested) {
            return [
                'success' => false,
                'code' => 'NO_EXISTING_PRESENSI',
                'message' => 'Data Presensi belum ada. Gunakan endpoint input baru.',
            ];
        }

        if ($isRevision) {
            if ($capability['capability'] === 'GURU_TERJADWAL') {
                return [
                    'success' => false,
                    'code' => 'ALREADY_SUBMITTED',
                    'message' => 'Presensi sudah tersimpan dan tidak dapat dibuka kembali oleh Guru biasa.',
                ];
            }

            if (! $this->canRevise($userId, (int) $context['id_tahun'], $idKelas)) {
                return [
                    'success' => false,
                    'code' => 'FORBIDDEN_REVISE',
                    'message' => 'Anda tidak memiliki hak revisi Presensi.',
                ];
            }
        }

        if ($capability['capability'] === 'GURU_TERJADWAL') {
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

        $actor = $this->getUser($userId);
        $actorGuru = $this->getGuruIdentity((int) ($actor['id_guru'] ?? 0));
        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');

        $existingMap = [];

        foreach ($existing as $row) {
            $idSiswa = (int) ($row['id_siswa'] ?? 0);

            if ($idSiswa > 0) {
                $existingMap[$idSiswa] = $row;
            }
        }

        $inserts = [];
        $updates = [];

        foreach ($rosterMap as $idSiswa => $siswa) {
            $status = $submitted[$idSiswa];
            $saved = $existingMap[$idSiswa] ?? null;

            if ($saved !== null) {
                $updates[] = [
                    'id' => (int) $saved['id'],
                    'status' => $status,
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ];
                continue;
            }

            $inserts[] = [
                'id_siswa' => $idSiswa,
                'nama_siswa_snapshot' => (string) $siswa['nama'],
                'id_kelas' => $idKelas,
                'id_tahun' => (int) $context['id_tahun'],
                'tanggal' => $tanggal,
                'sesi' => $sesi,
                'status' => $status,
                'id_guru_input' => $actorGuru['id'] ?? null,
                'nama_guru_input_snapshot' => $actorGuru['nama'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
                'updated_by' => $existing === [] ? null : $userId,
            ];
        }

        $this->db->transBegin();

        try {
            if ($inserts !== []) {
                $okInsert = $this->db
                    ->table('presensi')
                    ->insertBatch($inserts);

                if ($okInsert === false) {
                    throw new \RuntimeException('Insert batch Presensi gagal.');
                }
            }

            if ($updates !== []) {
                $okUpdate = $this->db
                    ->table('presensi')
                    ->updateBatch($updates, 'id');

                if ($okUpdate === false) {
                    throw new \RuntimeException('Update batch Presensi gagal.');
                }
            }

            $this->writeActivityLog(
                $userId,
                $isRevision ? 'REVISI' : 'INPUT',
                'Presensi Siswa',
                sprintf(
                    '%s Presensi kelas %s tanggal %s %s (%d siswa).',
                    $isRevision ? 'Revisi' : 'Input',
                    (string) $context['kelas']['nama_kelas'],
                    $tanggal,
                    $sesi,
                    count($rosterMap)
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction Presensi gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'code' => 'SAVE_FAILED',
                'message' => 'Presensi gagal disimpan. Tidak ada perubahan parsial yang dipertahankan.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }

        return [
            'success' => true,
            'message' => $isRevision
                ? 'Revisi Presensi berhasil disimpan.'
                : 'Presensi berhasil disimpan.',
            'revision' => $isRevision,
            'capability' => $capability['capability'],
            'total' => count($rosterMap),
        ];
    }

    /**
     * Histori kelas untuk actor yang mempunyai presensi_siswa.view.
     */
    public function getHistoriKelas(
        int $userId,
        int $idKelas,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit = 50,
        int $offset = 0,
        ?string $sesi = null,
        ?array $status = null
    ): array {
        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tidak ada Tahun Ajaran aktif.',
            ];
        }

        $kelas = $this->getKelasAktif($idKelas, (int) $tahun['id']);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak valid pada Tahun Ajaran aktif.',
            ];
        }

        if (! $this->canViewKelas($userId, (int) $tahun['id'], $idKelas)) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki hak melihat Presensi kelas tersebut.',
            ];
        }

        $rows = $this->presensiModel->getHistoriKelasPaged(
            (int) $tahun['id'],
            $idKelas,
            $tanggalMulai,
            $tanggalSelesai,
            $limit,
            $offset,
            $sesi,
            $status
        );

        $total = $this->presensiModel->countHistoriKelas(
            (int) $tahun['id'],
            $idKelas,
            $tanggalMulai,
            $tanggalSelesai,
            $sesi,
            $status
        );

        return [
            'success' => true,
            'kelas' => $kelas,
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Histori diri siswa.
     */
    public function getHistoriDiriSiswa(
        int $userId,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit = 50,
        int $offset = 0
    ): array {
        $scopes = $this->getPermissionScopes('presensi_siswa.view', $userId);

        if (! in_array('DIRI_SENDIRI', $scopes, true)) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki hak melihat Presensi diri.',
            ];
        }

        $user = $this->getUser($userId);
        $idSiswa = (int) ($user['id_siswa'] ?? 0);
        $tahun = $this->getTahunAktif();

        if ($idSiswa <= 0 || $tahun === null) {
            return [
                'success' => false,
                'message' => 'Identitas siswa atau Tahun Ajaran aktif tidak tersedia.',
            ];
        }

        $status = ['Sakit', 'Izin', 'Alpha'];

        $rows = $this->presensiModel->getHistoriSiswaPaged(
            $idSiswa,
            (int) $tahun['id'],
            $tanggalMulai,
            $tanggalSelesai,
            $limit,
            $offset,
            'Sesi Awal',
            $status
        );

        $total = $this->presensiModel->countHistoriSiswa(
            $idSiswa,
            (int) $tahun['id'],
            $tanggalMulai,
            $tanggalSelesai,
            'Sesi Awal',
            $status
        );

        return [
            'success' => true,
            'rows' => $rows,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Sumber EWS Alpha. Scope kelas tetap diterapkan server-side.
     */
    public function getEwsAlpha(
        int $userId,
        string $tanggalMulai,
        string $tanggalSelesai
    ): array {
        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [];
        }

        $scopes = $this->getPermissionScopes('ews_radar.view', $userId);

        if (in_array('SEMUA', $scopes, true)) {
            return $this->presensiModel->getEwsAlpha(
                (int) $tahun['id'],
                $tanggalMulai,
                $tanggalSelesai,
                null,
                3
            );
        }

        if (in_array('KELAS_DIAMPU', $scopes, true)) {
            $user = $this->getUser($userId);
            $idGuru = (int) ($user['id_guru'] ?? 0);
            $kelas = $this->authService->getKelasDiampu($idGuru, (int) $tahun['id']);

            return $this->presensiModel->getEwsAlpha(
                (int) $tahun['id'],
                $tanggalMulai,
                $tanggalSelesai,
                $kelas,
                3
            );
        }

        return [];
    }

    /**
     * Menentukan capability input terhadap target kelas/tanggal/sesi.
     *
     * Prioritas:
     * SEMUA → GURU_TERJADWAL valid → fallback WALI → tolak.
     */
    private function resolveInputCapability(
        int $userId,
        int $idTahun,
        int $idKelas,
        string $tanggal,
        string $sesi
    ): array {
        $scopes = $this->getPermissionScopes('presensi_siswa.input', $userId);

        if (in_array('SEMUA', $scopes, true)) {
            return [
                'success' => true,
                'capability' => 'SEMUA',
                'message' => 'Akses administratif.',
            ];
        }

        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);

        if ($idGuru <= 0) {
            return [
                'success' => false,
                'code' => 'NO_GURU_IDENTITY',
                'message' => 'User tidak memiliki identitas Guru untuk input Presensi.',
            ];
        }

        $isWaliTarget = in_array('KELAS_DIAMPU', $scopes, true)
            && $this->isWaliTarget($idGuru, $idTahun, $idKelas);

        if ($tanggal !== $this->today()) {
            if ($isWaliTarget) {
                return [
                    'success' => true,
                    'capability' => 'WALI',
                    'message' => 'Akses Wali Kelas.',
                ];
            }

            return [
                'success' => false,
                'code' => 'OUTSIDE_SCHEDULE_DATE',
                'message' => 'Guru biasa hanya dapat input melalui jadwal pada tanggal berjalan.',
            ];
        }

        if (in_array('KELAS_TERJADWAL', $scopes, true)) {
            $jadwal = $this->getMatchingSchedules(
                $idGuru,
                $idTahun,
                $idKelas,
                $tanggal,
                $sesi
            );

            if (count($jadwal) > 1) {
                return [
                    'success' => false,
                    'code' => 'AMBIGUOUS_SCHEDULE',
                    'message' => 'Terdapat lebih dari satu jadwal aktif untuk kelas/sesi yang sama. Periksa Master Jadwal Guru.',
                ];
            }

            if (count($jadwal) === 1) {
                $window = $this->evaluateTimeWindow($jadwal[0], $tanggal);

                if ($window['state'] === 'VALID') {
                    return [
                        'success' => true,
                        'capability' => 'GURU_TERJADWAL',
                        'id_jadwal' => (int) $jadwal[0]['id'],
                        'message' => 'Akses Guru Terjadwal.',
                    ];
                }

                if ($window['state'] === 'ENDED' && $isWaliTarget) {
                    return [
                        'success' => true,
                        'capability' => 'WALI',
                        'message' => 'Time-window jadwal telah berakhir; akses fallback Wali digunakan.',
                    ];
                }

                if ($window['state'] === 'NOT_STARTED') {
                    return [
                        'success' => false,
                        'code' => 'TIME_WINDOW_NOT_STARTED',
                        'message' => 'Waktu input Presensi untuk jadwal ini belum dimulai.',
                    ];
                }

                return [
                    'success' => false,
                    'code' => 'TIME_WINDOW_ENDED',
                    'message' => 'Waktu input Presensi untuk jadwal ini sudah berakhir.',
                ];
            }
        }

        if ($isWaliTarget) {
            return [
                'success' => true,
                'capability' => 'WALI',
                'message' => 'Akses Wali Kelas.',
            ];
        }

        return [
            'success' => false,
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak mempunyai jadwal atau hak Wali untuk kelas/sesi tersebut.',
        ];
    }

    private function canRevise(int $userId, int $idTahun, int $idKelas): bool
    {
        $scopes = $this->getPermissionScopes('presensi_siswa.revisi', $userId);

        if (in_array('SEMUA', $scopes, true)) {
            return true;
        }

        if (! in_array('KELAS_DIAMPU', $scopes, true)) {
            return false;
        }

        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);

        return $idGuru > 0 && $this->isWaliTarget($idGuru, $idTahun, $idKelas);
    }

    private function canViewKelas(int $userId, int $idTahun, int $idKelas): bool
    {
        $scopes = $this->getPermissionScopes('presensi_siswa.view', $userId);

        if (in_array('SEMUA', $scopes, true)) {
            return true;
        }

        if (! in_array('KELAS_DIAMPU', $scopes, true)) {
            return false;
        }

        $user = $this->getUser($userId);
        $idGuru = (int) ($user['id_guru'] ?? 0);

        return $idGuru > 0 && $this->isWaliTarget($idGuru, $idTahun, $idKelas);
    }

    private function resolveBaseContext(
        int $userId,
        int $idKelas,
        string $tanggal
    ): array {
        if ($userId <= 0) {
            return [
                'success' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Session user tidak valid.',
            ];
        }

        if ($idKelas <= 0) {
            return [
                'success' => false,
                'code' => 'INVALID_CLASS',
                'message' => 'Kelas tidak valid.',
            ];
        }

        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [
                'success' => false,
                'code' => 'NO_ACTIVE_YEAR',
                'message' => 'Tidak ada Tahun Ajaran aktif.',
            ];
        }

        $kelas = $this->getKelasAktif($idKelas, (int) $tahun['id']);

        if ($kelas === null) {
            return [
                'success' => false,
                'code' => 'INVALID_CLASS',
                'message' => 'Kelas tidak ditemukan pada Tahun Ajaran aktif.',
            ];
        }

        return [
            'success' => true,
            'id_tahun' => (int) $tahun['id'],
            'tahun' => $tahun,
            'kelas' => $kelas,
            'tanggal' => $tanggal,
        ];
    }

    private function getTahunAktif(): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function getKelasAktif(int $idKelas, int $idTahun): ?array
    {
        $row = $this->db
            ->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel, id_tahun')
            ->where('id', $idKelas)
            ->where('id_tahun', $idTahun)
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

    private function getGuruIdentity(int $idGuru): ?array
    {
        if ($idGuru <= 0) {
            return null;
        }

        $row = $this->db
            ->table('guru')
            ->select('id, nama')
            ->where('id', $idGuru)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Mengambil seluruh scope yang benar-benar diberikan role user untuk
     * satu permission. Ini sengaja tidak memakai scalar resolveScope()
     * karena Presensi membutuhkan dual-context per target.
     *
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

    private function isWaliTarget(
        int $idGuru,
        int $idTahun,
        int $idKelas
    ): bool {
        return $this->db
            ->table('mapping_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    private function getMatchingSchedules(
        int $idGuru,
        int $idTahun,
        int $idKelas,
        string $tanggal,
        string $sesi
    ): array {
        return $this->db
            ->table('jadwal_guru')
            ->select('id, id_guru, id_kelas, id_tahun, hari, jam_mulai, jam_selesai, sesi')
            ->where('id_guru', $idGuru)
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('hari', $this->hariIndonesia($tanggal))
            ->where('sesi', $sesi)
            ->where('status_jadwal', 'Aktif')
            ->orderBy('jam_mulai', 'ASC')
            ->get()
            ->getResultArray();
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

    /**
     * Roster untuk tanggal target.
     *
     * Hari berjalan memakai anggota_kelas + siswa aktif.
     * Tanggal historis memakai riwayat_siswa agar perpindahan kelas tidak
     * merusak membership masa lalu.
     */
    private function getRosterForDate(
        int $idTahun,
        int $idKelas,
        string $tanggal
    ): array {
        if ($tanggal === $this->today()) {
            return $this->db
                ->table('anggota_kelas ak')
                ->select('s.id, s.nisn, s.nama')
                ->join('siswa s', 's.id = ak.id_siswa')
                ->where('ak.id_tahun', $idTahun)
                ->where('ak.id_kelas', $idKelas)
                ->where('s.status_aktif', 'Aktif')
                ->where('s.deleted_at', null)
                ->orderBy('s.nama', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->db
            ->table('riwayat_siswa rs')
            ->select('s.id, s.nisn, s.nama')
            ->join('siswa s', 's.id = rs.id_siswa')
            ->where('rs.id_tahun', $idTahun)
            ->where('rs.id_kelas', $idKelas)
            ->where('rs.tanggal_mulai <=', $tanggal)
            ->groupStart()
                ->where('rs.tanggal_selesai', null)
                ->orWhere('rs.tanggal_selesai >=', $tanggal)
            ->groupEnd()
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<int, string> key = id_siswa
     */
    private function normalizeSubmittedStatuses(mixed $items): array
    {
        if (! is_array($items) || $items === []) {
            return [];
        }

        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $idSiswa = (int) ($item['id_siswa'] ?? 0);
            $status = trim((string) ($item['status'] ?? ''));

            if ($idSiswa <= 0 || ! in_array($status, self::STATUS_VALID, true)) {
                continue;
            }

            $result[$idSiswa] = $status;
        }

        return $result;
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

    private function normalizeSesi(string $sesi): string
    {
        $sesi = trim($sesi);

        return in_array($sesi, self::SESI_VALID, true) ? $sesi : '';
    }

    private function today(): string
    {
        return Time::now(self::TZ)->format('Y-m-d');
    }

    private function hariIndonesia(string $tanggal): string
    {
        $date = new \DateTimeImmutable($tanggal, new \DateTimeZone(self::TZ));

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
}
