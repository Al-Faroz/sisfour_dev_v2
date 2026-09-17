<?php

namespace App\Services;

use App\Models\KonselingBkFollowUpModel;
use App\Models\KonselingBkModel;
use CodeIgniter\I18n\Time;
use Throwable;

class KonselingBkService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_EXPORT_ROWS = 50000;
    private const ALLOWED_ROLES = ['admin', 'operator', 'bk'];

    protected KonselingBkModel $model;
    protected KonselingBkFollowUpModel $followUpModel;
    protected AuthService $authService;
    protected BkScopeService $scopeService;
    protected BkKonselingFormSettingsService $formSettings;
    protected PeriodContextService $periodContext;

    public function __construct()
    {
        $this->model = new KonselingBkModel();
        $this->followUpModel = new KonselingBkFollowUpModel();
        $this->authService = new AuthService();
        $this->scopeService = new BkScopeService();
        $this->formSettings = new BkKonselingFormSettingsService();
        $this->periodContext = new PeriodContextService();
    }

    public function getPage(int $userId, array $input): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.view');
        if (! $auth['success']) return $auth;

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) return $period;

        $input['id_tahun'] = (int) $period['selected']['id'];
        $filter = $this->filter($input);
        if (! $filter['success']) return $filter;

        $limit = max(1, min(200, (int) ($input['limit'] ?? 25)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $selectedYearId = (int) $period['selected']['id'];

        return [
            'success' => true,
            'can_manage' => $this->can($userId, 'bk_konseling.manage'),
            'can_export' => $this->can($userId, 'bk_konseling.export'),
            'tahun_aktif' => $period['active'],
            'tahun_dipilih' => $period['selected'],
            'tahun_options' => $period['options'],
            'kelas' => $this->model->activeClasses($selectedYearId),
            'options' => $this->options(),
            'rows' => $this->model->getPaged($filter['filter'], $limit, $offset),
            'total' => $this->model->countFiltered($filter['filter']),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function studentsByClass(int $userId, int $idKelas): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.manage');
        if (! $auth['success']) return $auth;

        $tahun = $this->periodContext->active();
        if ($tahun === null) return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');

        if (! $this->classBelongsToYear($idKelas, (int) $tahun['id'])) {
            return $this->fail('INVALID_TARGET', 'Kelas tidak valid pada Tahun Ajaran aktif.');
        }

        $rows = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'nisn' => (string) $row['nisn'],
                'nama' => (string) $row['nama'],
                'text' => trim((string) $row['nama']) . ' — ' . trim((string) $row['nisn']),
            ],
            $this->model->studentsByClass((int) $tahun['id'], $idKelas)
        );

        return ['success' => true, 'rows' => $rows];
    }

    public function getDetail(int $userId, int $id): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.view');
        if (! $auth['success']) return $auth;

        $row = $this->model->getDetailById($id);
        if ($row === null) return $this->fail('NOT_FOUND', 'Catatan Konseling BK tidak ditemukan.');

        return [
            'success' => true,
            'can_manage' => $this->can($userId, 'bk_konseling.manage'),
            'row' => $row,
            'tindak_lanjut' => $this->followUpModel->getByKonseling($id),
            'options' => $this->options(),
        ];
    }

    /** Tahap 1: identitas siswa/waktu dan jenis layanan. */
    public function create(int $userId, array $input): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.manage');
        if (! $auth['success']) return $auth;

        $tahun = $this->periodContext->active();
        if ($tahun === null) return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');

        $validated = $this->validateStageOne($input, (int) $tahun['id']);
        if (! $validated['success']) return $validated;

        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $data = $validated['data'];
        $data['id_tahun'] = (int) $tahun['id'];
        $data['status'] = 'Proses';
        $data['id_guru_bk'] = $this->scopeService->userGuruId($userId);
        $data['created_by'] = $userId;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['updated_by'] = $userId;

        $id = $this->model->insert($data);
        $this->log($userId, 'CREATE', 'BK Konseling', "Membuat Konseling BK #{$id} siswa #{$data['id_siswa']} (Tahap 1)");

        return ['success' => true, 'message' => 'Tahap 1 Konseling BK berhasil disimpan.', 'id' => $id];
    }

    /** Tahap 2: hasil pertemuan awal pada parent Konseling. */
    public function update(int $userId, int $id, array $input): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.manage');
        if (! $auth['success']) return $auth;

        $existing = $this->model->getById($id);
        if ($existing === null) return $this->fail('NOT_FOUND', 'Catatan Konseling BK tidak ditemukan.');

        $validated = $this->validateStageTwo($input, $existing);
        if (! $validated['success']) return $validated;

        $data = $validated['data'];
        $latest = $this->followUpModel->getLatestByKonseling($id);
        if ($latest !== null) {
            // Setelah ada tindak lanjut, status parent merefleksikan entry terbaru.
            $data['status'] = (string) $latest['status'];
        }
        $data['updated_at'] = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $data['updated_by'] = $userId;

        if (! $this->model->update($id, $data)) {
            return $this->fail('UPDATE_FAILED', 'Catatan Konseling BK gagal diperbarui.');
        }

        $this->log($userId, 'UPDATE', 'BK Konseling', "Memperbarui hasil pertemuan awal Konseling BK #{$id}");
        return ['success' => true, 'message' => 'Hasil pertemuan awal Konseling BK berhasil diperbarui.'];
    }

    public function createFollowUp(int $userId, int $idKonseling, array $input): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.manage');
        if (! $auth['success']) return $auth;

        $parent = $this->model->getById($idKonseling);
        if ($parent === null) return $this->fail('NOT_FOUND', 'Catatan Konseling BK tidak ditemukan.');

        $validated = $this->validateFollowUp($input, $parent, null);
        if (! $validated['success']) return $validated;

        $db = db_connect();
        $db->transBegin();
        try {
            $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
            $data = $validated['data'];
            $data['id_konseling'] = $idKonseling;
            $data['created_by'] = $userId;
            $data['created_at'] = $now;
            $data['updated_by'] = $userId;
            $data['updated_at'] = $now;

            $id = $this->followUpModel->insert($data);
            $this->syncParentStatus($idKonseling, $userId, $now);

            if ($db->transStatus() === false) throw new \RuntimeException('Transaksi tindak lanjut gagal.');
            $db->transCommit();

            $this->log($userId, 'CREATE', 'BK Konseling', "Menambahkan tindak lanjut Konseling #{$id} pada Konseling #{$idKonseling}");
            return ['success' => true, 'message' => 'Tindak lanjut Konseling berhasil disimpan.', 'id' => $id];
        } catch (Throwable $e) {
            $db->transRollback();
            return $this->fail('SAVE_FAILED', 'Tindak lanjut Konseling gagal disimpan.');
        }
    }

    public function updateFollowUp(int $userId, int $id, array $input): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.manage');
        if (! $auth['success']) return $auth;

        $existing = $this->followUpModel->getById($id);
        if ($existing === null) return $this->fail('NOT_FOUND', 'Tindak lanjut Konseling tidak ditemukan.');

        $idKonseling = (int) $existing['id_konseling'];
        $parent = $this->model->getById($idKonseling);
        if ($parent === null) return $this->fail('NOT_FOUND', 'Catatan Konseling BK induk tidak ditemukan.');

        $validated = $this->validateFollowUp($input, $parent, $existing);
        if (! $validated['success']) return $validated;

        $db = db_connect();
        $db->transBegin();
        try {
            $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
            $data = $validated['data'];
            $data['updated_by'] = $userId;
            $data['updated_at'] = $now;

            if (! $this->followUpModel->update($id, $data)) {
                throw new \RuntimeException('Update tindak lanjut gagal.');
            }
            $this->syncParentStatus($idKonseling, $userId, $now);

            if ($db->transStatus() === false) throw new \RuntimeException('Transaksi tindak lanjut gagal.');
            $db->transCommit();

            $this->log($userId, 'UPDATE', 'BK Konseling', "Memperbarui tindak lanjut Konseling #{$id}");
            return ['success' => true, 'message' => 'Tindak lanjut Konseling berhasil diperbarui.'];
        } catch (Throwable $e) {
            $db->transRollback();
            return $this->fail('UPDATE_FAILED', 'Tindak lanjut Konseling gagal diperbarui.');
        }
    }

    public function getExport(int $userId, array $input): array
    {
        $auth = $this->requirePermission($userId, 'bk_konseling.export');
        if (! $auth['success']) return $auth;

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) return $period;

        $input['id_tahun'] = (int) $period['selected']['id'];
        $filter = $this->filter($input);
        if (! $filter['success']) return $filter;

        $total = $this->model->countFiltered($filter['filter']);
        if ($total > self::MAX_EXPORT_ROWS) return $this->fail('EXPORT_TOO_LARGE', 'Data melebihi 50.000 baris. Persempit filter.');

        $rows = $this->model->getForExport($filter['filter'], self::MAX_EXPORT_ROWS);
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $followUps = $this->followUpModel->getForKonselingIds($ids, self::MAX_EXPORT_ROWS);

        return [
            'success' => true,
            'rows' => $rows,
            'tindak_lanjut' => $followUps,
            'filter' => $filter['filter'],
            'tahun_dipilih' => $period['selected'],
        ];
    }

    public function options(): array
    {
        return $this->formSettings->options();
    }

    private function validateStageOne(array $input, int $idTahun): array
    {
        $options = $this->options();
        $idKelas = (int) ($input['id_kelas'] ?? 0);
        $idSiswa = (int) ($input['id_siswa'] ?? 0);
        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $pertemuan = filter_var($input['pertemuan_ke'] ?? null, FILTER_VALIDATE_INT);
        $bentuk = trim((string) ($input['bentuk_layanan'] ?? ''));
        $caraHadir = trim((string) ($input['cara_hadir'] ?? ''));
        $bidang = trim((string) ($input['bidang'] ?? ''));
        $topik = trim((string) ($input['topik'] ?? ''));

        if ($idKelas <= 0 || $idSiswa <= 0) return $this->fail('VALIDATION', 'Kelas dan siswa wajib dipilih.');
        if (! $this->classBelongsToYear($idKelas, $idTahun)) return $this->fail('INVALID_TARGET', 'Kelas tidak valid pada Tahun Ajaran aktif.');
        if (! $this->studentBelongsToClass($idSiswa, $idKelas, $idTahun)) return $this->fail('INVALID_TARGET', 'Siswa tidak terdaftar pada kelas yang dipilih.');
        if (! $this->validDate($tanggal)) return $this->fail('VALIDATION', 'Tanggal konseling tidak valid.');
        if ($pertemuan === false || $pertemuan < 1 || $pertemuan > 99) return $this->fail('VALIDATION', 'Pertemuan ke- harus berupa angka 1–99.');
        if (! in_array($bentuk, $options['bentuk_layanan'] ?? [], true)) return $this->fail('VALIDATION', 'Bentuk layanan tidak valid.');
        if (! in_array($caraHadir, $options['cara_hadir'] ?? [], true)) return $this->fail('VALIDATION', 'Cara siswa hadir tidak valid.');
        if (! in_array($bidang, $options['bidang'] ?? [], true)) return $this->fail('VALIDATION', 'Bidang layanan tidak valid.');
        if (! in_array($topik, $options['topik'][$bidang] ?? [], true)) return $this->fail('VALIDATION', 'Topik tidak sesuai dengan bidang yang dipilih.');

        return ['success' => true, 'data' => [
            'id_kelas' => $idKelas,
            'id_siswa' => $idSiswa,
            'tanggal' => $tanggal,
            'pertemuan_ke' => (int) $pertemuan,
            'bentuk_layanan' => $bentuk,
            'cara_hadir' => $caraHadir,
            'bidang' => $bidang,
            'topik' => $topik,
        ]];
    }

    private function validateStageTwo(array $input, array $existing): array
    {
        $options = $this->options();
        $uraian = trim((string) ($input['uraian_masalah'] ?? ''));
        $hasil = trim((string) ($input['hasil_kesepakatan'] ?? ''));
        $rencana = trim((string) ($input['rencana_berikutnya'] ?? ''));
        $tanggalBerikutnya = trim((string) ($input['tanggal_berikutnya'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'Proses'));

        if (! in_array($status, $options['status'] ?? [], true)) return $this->fail('VALIDATION', 'Status Konseling BK tidak valid.');
        if (! $this->validRencana($rencana, (string) ($existing['rencana_berikutnya'] ?? ''), $options)) {
            return $this->fail('VALIDATION', 'Rencana berikutnya tidak valid.');
        }
        if ($tanggalBerikutnya !== '' && ! $this->validDate($tanggalBerikutnya)) return $this->fail('VALIDATION', 'Tanggal pertemuan berikutnya tidak valid.');
        if ($tanggalBerikutnya !== '' && ! empty($existing['tanggal']) && $tanggalBerikutnya < (string) $existing['tanggal']) {
            return $this->fail('VALIDATION', 'Tanggal pertemuan berikutnya tidak boleh sebelum tanggal konseling.');
        }
        if ($status === 'Selesai' && ($uraian === '' || $hasil === '')) {
            return $this->fail('VALIDATION', 'Untuk menandai Selesai, Uraian Masalah serta Hasil Pembahasan dan Kesepakatan wajib diisi.');
        }

        return ['success' => true, 'data' => [
            'uraian_masalah' => $uraian !== '' ? $uraian : null,
            'hasil_kesepakatan' => $hasil !== '' ? $hasil : null,
            'rencana_berikutnya' => $rencana !== '' ? $rencana : null,
            'tanggal_berikutnya' => $tanggalBerikutnya !== '' ? $tanggalBerikutnya : null,
            'status' => $status,
        ]];
    }

    private function validateFollowUp(array $input, array $parent, ?array $existing): array
    {
        $options = $this->options();
        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $perkembangan = trim((string) ($input['perkembangan'] ?? ''));
        $hasil = trim((string) ($input['hasil_kesepakatan'] ?? ''));
        $rencana = trim((string) ($input['rencana_berikutnya'] ?? ''));
        $tanggalBerikutnya = trim((string) ($input['tanggal_berikutnya'] ?? ''));
        $status = trim((string) ($input['status'] ?? 'Proses'));

        if (! $this->validDate($tanggal)) return $this->fail('VALIDATION', 'Tanggal tindak lanjut tidak valid.');
        if ($tanggal < (string) ($parent['tanggal'] ?? '')) return $this->fail('VALIDATION', 'Tanggal tindak lanjut tidak boleh sebelum tanggal Konseling.');
        if ($perkembangan === '') return $this->fail('VALIDATION', 'Perkembangan tindak lanjut wajib diisi.');
        if (! in_array($status, $options['status'] ?? [], true)) return $this->fail('VALIDATION', 'Status tindak lanjut tidak valid.');
        if (! $this->validRencana($rencana, (string) ($existing['rencana_berikutnya'] ?? ''), $options)) {
            return $this->fail('VALIDATION', 'Rencana berikutnya tidak valid.');
        }
        if ($tanggalBerikutnya !== '' && ! $this->validDate($tanggalBerikutnya)) return $this->fail('VALIDATION', 'Tanggal pertemuan berikutnya tidak valid.');
        if ($tanggalBerikutnya !== '' && $tanggalBerikutnya < $tanggal) return $this->fail('VALIDATION', 'Tanggal berikutnya tidak boleh sebelum tanggal tindak lanjut.');
        if ($status === 'Selesai' && $hasil === '') return $this->fail('VALIDATION', 'Hasil/Kesepakatan wajib diisi untuk menandai Konseling Selesai.');

        return ['success' => true, 'data' => [
            'tanggal' => $tanggal,
            'perkembangan' => $perkembangan,
            'hasil_kesepakatan' => $hasil !== '' ? $hasil : null,
            'rencana_berikutnya' => $rencana !== '' ? $rencana : null,
            'tanggal_berikutnya' => $tanggalBerikutnya !== '' ? $tanggalBerikutnya : null,
            'status' => $status,
        ]];
    }

    private function validRencana(string $value, string $existingValue, array $options): bool
    {
        if ($value === '') return true;
        $allowed = $options['rencana'] ?? [];
        $existingValue = trim($existingValue);
        if ($existingValue !== '' && ! in_array($existingValue, $allowed, true)) $allowed[] = $existingValue;
        return in_array($value, $allowed, true);
    }

    private function syncParentStatus(int $idKonseling, int $userId, string $now): void
    {
        $latest = $this->followUpModel->getLatestByKonseling($idKonseling);
        if ($latest === null) return;

        if (! $this->model->update($idKonseling, [
            'status' => (string) $latest['status'],
            'updated_at' => $now,
            'updated_by' => $userId,
        ])) {
            throw new \RuntimeException('Status Konseling induk gagal disinkronkan.');
        }
    }

    private function filter(array $input): array
    {
        $options = $this->options();
        $idTahun = (int) ($input['id_tahun'] ?? 0);
        $status = trim((string) ($input['status'] ?? ''));
        $bidang = trim((string) ($input['bidang'] ?? ''));
        $tanggalMulai = trim((string) ($input['tanggal_mulai'] ?? ''));
        $tanggalSelesai = trim((string) ($input['tanggal_selesai'] ?? ''));

        if ($idTahun <= 0) return $this->fail('INVALID_PERIOD', 'Tahun Ajaran wajib dipilih.');
        if ($status !== '' && ! in_array($status, $options['status'] ?? [], true)) return $this->fail('VALIDATION', 'Status filter tidak valid.');
        if ($bidang !== '' && ! in_array($bidang, $options['bidang'] ?? [], true)) return $this->fail('VALIDATION', 'Bidang filter tidak valid.');
        if ($tanggalMulai !== '' && ! $this->validDate($tanggalMulai)) return $this->fail('VALIDATION', 'Tanggal awal tidak valid.');
        if ($tanggalSelesai !== '' && ! $this->validDate($tanggalSelesai)) return $this->fail('VALIDATION', 'Tanggal akhir tidak valid.');
        if ($tanggalMulai !== '' && $tanggalSelesai !== '' && $tanggalMulai > $tanggalSelesai) return $this->fail('VALIDATION', 'Tanggal awal tidak boleh melewati tanggal akhir.');

        return ['success' => true, 'filter' => [
            'id_tahun' => $idTahun,
            'id_kelas' => (int) ($input['id_kelas'] ?? 0) ?: null,
            'status' => $status !== '' ? $status : null,
            'bidang' => $bidang !== '' ? $bidang : null,
            'tanggal_mulai' => $tanggalMulai !== '' ? $tanggalMulai : null,
            'tanggal_selesai' => $tanggalSelesai !== '' ? $tanggalSelesai : null,
            'search' => trim((string) ($input['search'] ?? '')) ?: null,
        ]];
    }

    private function classBelongsToYear(int $idKelas, int $idTahun): bool
    {
        return $idKelas > 0 && db_connect()->table('kelas')->where('id', $idKelas)->where('id_tahun', $idTahun)->where('deleted_at', null)->countAllResults() > 0;
    }

    private function studentBelongsToClass(int $idSiswa, int $idKelas, int $idTahun): bool
    {
        return $idSiswa > 0 && db_connect()
            ->table('anggota_kelas ak')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_kelas', $idKelas)
            ->where('ak.id_tahun', $idTahun)
            ->where('s.status_aktif', 'Aktif')
            ->where('s.deleted_at', null)
            ->countAllResults() > 0;
    }

    private function requirePermission(int $userId, string $permission): array
    {
        $roles = $this->authService->getUserRoles($userId);
        if (array_intersect(self::ALLOWED_ROLES, $roles) === []) {
            return $this->fail('FORBIDDEN', 'Konseling BK hanya dapat diakses oleh Admin, Operator, atau BK yang memiliki permission terkait.');
        }
        if ($this->authService->resolveScope($permission, $userId) !== 'SEMUA') {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak mengakses Konseling BK.');
        }
        return ['success' => true];
    }

    private function can(int $userId, string $permission): bool
    {
        $roles = $this->authService->getUserRoles($userId);
        return array_intersect(self::ALLOWED_ROLES, $roles) !== []
            && $this->authService->resolveScope($permission, $userId) === 'SEMUA';
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
