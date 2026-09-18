<?php

namespace App\Services;

use App\Models\UksCkgModel;
use App\Models\UksKunjunganModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class UksService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_EXPORT_ROWS = 50000;

    private const STATUS_GIZI = [
        'Normal', 'Kurus', 'Sangat kurus', 'Gemuk', 'Obesitas',
    ];
    private const STATUS_TINGGI = [
        'Normal', 'Pendek', 'Sangat pendek',
    ];
    private const KONDISI_GIGI = [
        'Normal', 'Karies', 'Gusi bermasalah', 'Kebersihan kurang',
    ];
    private const PENDENGARAN = [
        'Normal', 'Terganggu', 'Serumen',
    ];
    private const TALASEMIA = [
        'Tidak dilakukan', 'Negatif', 'Perlu pemeriksaan lanjut',
    ];
    private const TUBERKULOSIS = [
        'Negatif', 'Terduga, perlu rujukan',
    ];

    protected BaseConnection $db;
    protected AuthService $authService;
    protected PeriodContextService $periodContext;
    protected UksScopeService $scopeService;
    protected UksCkgModel $ckgModel;
    protected UksKunjunganModel $kunjunganModel;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
        $this->periodContext = new PeriodContextService();
        $this->scopeService = new UksScopeService();
        $this->ckgModel = new UksCkgModel();
        $this->kunjunganModel = new UksKunjunganModel();
        $this->activityLog = new ActivityLogService();
    }

    public function ckgPage(int $userId, array $input): array
    {
        $period = $this->periodContext->resolve($input);
        if (! $period['success']) {
            return $period;
        }

        $idTahun = (int) $period['selected']['id'];
        $scope = $this->scopeService->resolveStudentIds(
            'uks_ckg.view',
            $userId,
            $idTahun
        );
        if (! $scope['success']) {
            return $scope;
        }

        $filter = $this->buildFilter($input + ['id_tahun' => $idTahun]);
        if (! $filter['success']) {
            return $filter;
        }

        $limit = max(1, min(200, (int) ($input['limit'] ?? 50)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $ids = $scope['student_ids'];

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $this->isAll('uks_ckg.manage', $userId),
            'can_import' => $this->isAll('uks_ckg.import', $userId),
            'can_export' => $this->isAll('uks_ckg.export', $userId),
            'tahun_aktif' => $period['active'],
            'tahun_dipilih' => $period['selected'],
            'tahun_options' => $period['options'],
            'kelas_options' => $this->classOptions($idTahun, $ids),
            'fixed_options' => $this->fixedOptions(),
            'rows' => $this->ckgModel->getPaged(
                $filter['filter'],
                $ids,
                $limit,
                $offset
            ),
            'total' => $this->ckgModel->countFiltered(
                $filter['filter'],
                $ids
            ),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function createCkg(int $userId, array $input): array
    {
        if (! $this->isAll('uks_ckg.manage', $userId)) {
            return $this->forbidden('mengelola Data CKG');
        }

        $active = $this->periodContext->active();
        if ($active === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $idTahun = (int) $active['id'];
        $validated = $this->validateCkg($input, null, $idTahun);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['id_tahun'] = $idTahun;
        $data['id_kelas'] = (int) $validated['membership']['id_kelas'];
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['created_at'] = $this->now();
        $data['updated_at'] = $this->now();

        $id = $this->ckgModel->insert($data);
        $this->activityLog->write(
            $userId,
            'CREATE',
            'UKS CKG',
            sprintf('Membuat CKG #%d siswa #%d.', $id, $data['id_siswa'])
        );

        return [
            'success' => true,
            'message' => 'Data CKG berhasil disimpan.',
            'id' => $id,
        ];
    }

    public function updateCkg(int $userId, int $id, array $input): array
    {
        if (! $this->isAll('uks_ckg.manage', $userId)) {
            return $this->forbidden('mengelola Data CKG');
        }

        $existing = $this->ckgModel->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'Data CKG tidak ditemukan.');
        }

        $idTahun = (int) $existing['id_tahun'];
        $validated = $this->validateCkg($input, $existing, $idTahun);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['id_kelas'] = (int) $validated['membership']['id_kelas'];
        $data['updated_by'] = $userId;
        $data['updated_at'] = $this->now();

        $this->ckgModel->update($id, $data);
        $this->activityLog->write(
            $userId,
            'UPDATE',
            'UKS CKG',
            sprintf('Memperbarui CKG #%d.', $id)
        );

        return ['success' => true, 'message' => 'Data CKG berhasil diperbarui.'];
    }

    public function deleteCkg(int $userId, int $id): array
    {
        if (! $this->isAll('uks_ckg.manage', $userId)) {
            return $this->forbidden('menghapus Data CKG');
        }

        if (! $this->ckgModel->getById($id)) {
            return $this->fail('NOT_FOUND', 'Data CKG tidak ditemukan.');
        }

        $this->ckgModel->softDelete($id, $userId);
        $this->activityLog->write(
            $userId,
            'DELETE',
            'UKS CKG',
            sprintf('Soft delete CKG #%d.', $id)
        );

        return ['success' => true, 'message' => 'Data CKG dipindahkan dari listing aktif.'];
    }

    public function ckgExportData(int $userId, array $input): array
    {
        if (! $this->isAll('uks_ckg.export', $userId)) {
            return $this->forbidden('export Data CKG');
        }

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) {
            return $period;
        }

        $idTahun = (int) $period['selected']['id'];
        $scope = $this->scopeService->resolveStudentIds(
            'uks_ckg.view',
            $userId,
            $idTahun
        );
        if (! $scope['success']) {
            return $scope;
        }

        $filter = $this->buildFilter($input + ['id_tahun' => $idTahun]);
        if (! $filter['success']) {
            return $filter;
        }

        $total = $this->ckgModel->countFiltered(
            $filter['filter'],
            $scope['student_ids']
        );
        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail(
                'EXPORT_TOO_LARGE',
                'Data melebihi 50.000 baris. Persempit filter.'
            );
        }

        return [
            'success' => true,
            'tahun_dipilih' => $period['selected'],
            'rows' => $this->ckgModel->getForExport(
                $filter['filter'],
                $scope['student_ids'],
                self::MAX_EXPORT_ROWS
            ),
        ];
    }

    public function importCkg(int $userId, UploadedFile $file): array
    {
        if (! $this->isAll('uks_ckg.import', $userId)) {
            return $this->forbidden('import Data CKG');
        }
        if (! $file->isValid()) {
            return $this->fail('VALIDATION', 'File import tidak valid.');
        }

        $ext = strtolower((string) $file->getClientExtension());
        if (! in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->fail('VALIDATION', 'File import harus XLSX atau XLS.');
        }

        $active = $this->periodContext->active();
        if ($active === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }
        $idTahun = (int) $active['id'];

        try {
            $sheet = IOFactory::load($file->getTempName())->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (Throwable $e) {
            return $this->fail(
                'IMPORT_INVALID',
                'File Excel tidak dapat dibaca: ' . $e->getMessage()
            );
        }

        if ($rows === []) {
            return $this->fail('IMPORT_EMPTY', 'File import kosong.');
        }

        $headerRow = $rows[array_key_first($rows)];
        $columns = [];
        foreach ($headerRow as $column => $value) {
            $columns[$this->normalizeHeader((string) $value)] = $column;
        }

        foreach (['nisn', 'tanggal'] as $required) {
            if (! isset($columns[$required])) {
                return $this->fail(
                    'IMPORT_HEADER',
                    'Header import wajib memuat NISN dan Tanggal.'
                );
            }
        }

        $prepared = [];
        $seen = [];
        $lineNo = 1;

        foreach (array_slice($rows, 1, null, true) as $row) {
            $lineNo++;
            $values = array_map(
                static fn ($value): string => trim((string) $value),
                $row
            );
            if (implode('', $values) === '') {
                continue;
            }

            $nisn = trim((string) ($row[$columns['nisn']] ?? ''));
            if ($nisn === '') {
                return $this->fail(
                    'IMPORT_VALIDATION',
                    "Baris {$lineNo}: NISN wajib diisi."
                );
            }

            $siswa = $this->db
                ->table('siswa')
                ->select('id, nisn, nama, deleted_at')
                ->where('nisn', $nisn)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $siswa) {
                return $this->fail(
                    'IMPORT_VALIDATION',
                    "Baris {$lineNo}: NISN {$nisn} tidak ditemukan."
                );
            }

            $tanggal = $this->excelDate(
                $row[$columns['tanggal']] ?? null
            );
            if ($tanggal === null) {
                return $this->fail(
                    'IMPORT_VALIDATION',
                    "Baris {$lineNo}: Tanggal tidak valid."
                );
            }

            $key = $nisn . '|' . $tanggal;
            if (isset($seen[$key])) {
                return $this->fail(
                    'IMPORT_DUPLICATE',
                    "Baris {$lineNo}: NISN + tanggal duplikat di dalam file."
                );
            }
            $seen[$key] = true;

            $membership = $this->scopeService->membershipForYear(
                (int) $siswa['id'],
                $idTahun
            );
            if ($membership === null) {
                return $this->fail(
                    'IMPORT_VALIDATION',
                    "Baris {$lineNo}: siswa tidak mempunyai membership pada Tahun Ajaran aktif."
                );
            }

            $payload = [
                'id_siswa' => (int) $siswa['id'],
                'tanggal' => $tanggal,
            ];

            foreach ($this->importFieldMap() as $keyName => $aliases) {
                foreach ($aliases as $alias) {
                    if (isset($columns[$alias])) {
                        $payload[$keyName] = $row[$columns[$alias]] ?? null;
                        break;
                    }
                }
            }

            $validated = $this->validateCkg(
                $payload,
                null,
                $idTahun,
                true
            );
            if (! $validated['success']) {
                return $this->fail(
                    'IMPORT_VALIDATION',
                    "Baris {$lineNo}: " . $validated['message']
                );
            }

            $prepared[] = [
                'data' => $validated['data'],
                'membership' => $membership,
            ];
        }

        if ($prepared === []) {
            return $this->fail('IMPORT_EMPTY', 'Tidak ada baris CKG untuk diproses.');
        }

        $inserted = 0;
        $updated = 0;
        $this->db->transBegin();

        try {
            foreach ($prepared as $item) {
                $data = $item['data'];
                $data['id_tahun'] = $idTahun;
                $data['id_kelas'] = (int) $item['membership']['id_kelas'];
                $existing = $this->ckgModel->findActiveDuplicate(
                    (int) $data['id_siswa'],
                    (string) $data['tanggal']
                );

                if ($existing) {
                    $data['updated_by'] = $userId;
                    $data['updated_at'] = $this->now();
                    $this->ckgModel->update((int) $existing['id'], $data);
                    $updated++;
                } else {
                    // Tombstone tidak dicari/di-restore: import membentuk record aktif baru.
                    $data['created_by'] = $userId;
                    $data['updated_by'] = $userId;
                    $data['created_at'] = $this->now();
                    $data['updated_at'] = $this->now();
                    $this->ckgModel->insert($data);
                    $inserted++;
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi import gagal.');
            }

            $this->activityLog->write(
                $userId,
                'IMPORT',
                'UKS CKG',
                sprintf(
                    'Import CKG: %d insert, %d update.',
                    $inserted,
                    $updated
                )
            );
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('IMPORT_FAILED', 'Import dibatalkan: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => sprintf(
                'Import selesai: %d data baru, %d data diperbarui.',
                $inserted,
                $updated
            ),
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    public function harianPage(int $userId, array $input): array
    {
        $period = $this->periodContext->resolve($input);
        if (! $period['success']) {
            return $period;
        }

        $idTahun = (int) $period['selected']['id'];
        $scope = $this->scopeService->resolveStudentIds(
            'uks_harian.view',
            $userId,
            $idTahun
        );
        if (! $scope['success']) {
            return $scope;
        }

        $filter = $this->buildFilter($input + ['id_tahun' => $idTahun], true);
        if (! $filter['success']) {
            return $filter;
        }

        $limit = max(1, min(200, (int) ($input['limit'] ?? 50)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $ids = $scope['student_ids'];

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $this->isAll('uks_harian.manage', $userId),
            'can_export' => $this->isAll('uks_harian.export', $userId),
            'tahun_aktif' => $period['active'],
            'tahun_dipilih' => $period['selected'],
            'tahun_options' => $period['options'],
            'kelas_options' => $this->classOptions($idTahun, $ids),
            'refs' => $this->references(true),
            'rows' => $this->kunjunganModel->getPaged(
                $filter['filter'],
                $ids,
                $limit,
                $offset
            ),
            'total' => $this->kunjunganModel->countFiltered(
                $filter['filter'],
                $ids
            ),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function createHarian(int $userId, array $input): array
    {
        if (! $this->isAll('uks_harian.manage', $userId)) {
            return $this->forbidden('mengelola Catatan Harian UKS');
        }

        $active = $this->periodContext->active();
        if ($active === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $idTahun = (int) $active['id'];
        $validated = $this->validateHarian($input, null, $idTahun);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['id_tahun'] = $idTahun;
        $data['id_kelas'] = (int) $validated['membership']['id_kelas'];
        $data['id_petugas_user'] = $userId;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['created_at'] = $this->now();
        $data['updated_at'] = $this->now();

        $this->db->transBegin();
        try {
            $id = $this->kunjunganModel->insert($data);
            $this->kunjunganModel->replaceActions(
                $id,
                $validated['action_ids']
            );
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }
            $this->activityLog->write(
                $userId,
                'CREATE',
                'UKS Harian',
                sprintf('Membuat kunjungan UKS #%d siswa #%d.', $id, $data['id_siswa'])
            );
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('CREATE_FAILED', 'Catatan UKS gagal disimpan.');
        }

        return [
            'success' => true,
            'message' => 'Catatan Harian UKS berhasil disimpan.',
            'id' => $id,
        ];
    }

    public function updateHarian(int $userId, int $id, array $input): array
    {
        if (! $this->isAll('uks_harian.manage', $userId)) {
            return $this->forbidden('mengelola Catatan Harian UKS');
        }

        $existing = $this->kunjunganModel->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'Catatan Harian UKS tidak ditemukan.');
        }

        $validated = $this->validateHarian(
            $input,
            $existing,
            (int) $existing['id_tahun']
        );
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $data['id_kelas'] = (int) $validated['membership']['id_kelas'];
        $data['updated_by'] = $userId;
        $data['updated_at'] = $this->now();

        $this->db->transBegin();
        try {
            $this->kunjunganModel->update($id, $data);
            $this->kunjunganModel->replaceActions(
                $id,
                $validated['action_ids']
            );
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }
            $this->activityLog->write(
                $userId,
                'UPDATE',
                'UKS Harian',
                sprintf('Memperbarui kunjungan UKS #%d.', $id)
            );
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('UPDATE_FAILED', 'Catatan UKS gagal diperbarui.');
        }

        return ['success' => true, 'message' => 'Catatan Harian UKS berhasil diperbarui.'];
    }

    public function deleteHarian(int $userId, int $id): array
    {
        if (! $this->isAll('uks_harian.manage', $userId)) {
            return $this->forbidden('menghapus Catatan Harian UKS');
        }

        if (! $this->kunjunganModel->getById($id)) {
            return $this->fail('NOT_FOUND', 'Catatan Harian UKS tidak ditemukan.');
        }

        $this->kunjunganModel->softDelete($id, $userId);
        $this->activityLog->write(
            $userId,
            'DELETE',
            'UKS Harian',
            sprintf('Soft delete kunjungan UKS #%d.', $id)
        );

        return ['success' => true, 'message' => 'Catatan UKS dipindahkan dari listing aktif.'];
    }

    public function harianExportData(int $userId, array $input): array
    {
        if (! $this->isAll('uks_harian.export', $userId)) {
            return $this->forbidden('export Catatan Harian UKS');
        }

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) {
            return $period;
        }

        $idTahun = (int) $period['selected']['id'];
        $scope = $this->scopeService->resolveStudentIds(
            'uks_harian.view',
            $userId,
            $idTahun
        );
        if (! $scope['success']) {
            return $scope;
        }

        $filter = $this->buildFilter($input + ['id_tahun' => $idTahun], true);
        if (! $filter['success']) {
            return $filter;
        }

        $total = $this->kunjunganModel->countFiltered(
            $filter['filter'],
            $scope['student_ids']
        );
        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail('EXPORT_TOO_LARGE', 'Data melebihi 50.000 baris. Persempit filter.');
        }

        return [
            'success' => true,
            'tahun_dipilih' => $period['selected'],
            'rows' => $this->kunjunganModel->getForExport(
                $filter['filter'],
                $scope['student_ids'],
                self::MAX_EXPORT_ROWS
            ),
        ];
    }

    public function masterPage(int $userId): array
    {
        if (! $this->isAll('uks_master.manage', $userId)) {
            return $this->forbidden('mengelola Master UKS');
        }

        return [
            'success' => true,
            'refs' => $this->references(false),
        ];
    }

    public function saveMaster(
        int $userId,
        string $type,
        ?int $id,
        array $input
    ): array {
        if (! $this->isAll('uks_master.manage', $userId)) {
            return $this->forbidden('mengelola Master UKS');
        }

        $table = $this->masterTable($type);
        if ($table === null) {
            return $this->fail('VALIDATION', 'Jenis master UKS tidak valid.');
        }

        $nama = trim((string) ($input['nama'] ?? ''));
        $urutan = max(0, (int) ($input['urutan'] ?? 0));
        $status = (int) ($input['status_aktif'] ?? 1);

        if ($nama === '' || mb_strlen($nama) > 120) {
            return $this->fail('VALIDATION', 'Nama wajib diisi maksimal 120 karakter.');
        }
        if (! in_array($status, [0, 1], true)) {
            return $this->fail('VALIDATION', 'Status master tidak valid.');
        }

        $dup = $this->db->table($table)
            ->where('nama', $nama)
            ->where('deleted_at', null);
        if ($id !== null) {
            $dup->where('id !=', $id);
        }
        if ($dup->countAllResults() > 0) {
            return $this->fail('DUPLICATE', 'Nama master UKS sudah digunakan.');
        }

        $data = [
            'nama' => $nama,
            'urutan' => $urutan,
            'status_aktif' => $status,
            'updated_at' => $this->now(),
        ];

        if ($id === null) {
            $data['created_at'] = $this->now();
            $this->db->table($table)->insert($data);
            $id = (int) $this->db->insertID();
            $action = 'CREATE';
        } else {
            $exists = $this->db->table($table)
                ->where('id', $id)
                ->where('deleted_at', null)
                ->countAllResults() > 0;
            if (! $exists) {
                return $this->fail('NOT_FOUND', 'Master UKS tidak ditemukan.');
            }
            $this->db->table($table)->where('id', $id)->update($data);
            $action = 'UPDATE';
        }

        $this->activityLog->write(
            $userId,
            $action,
            'Master UKS',
            sprintf('%s master %s #%d.', $action, $type, $id)
        );

        return ['success' => true, 'message' => 'Master UKS berhasil disimpan.', 'id' => $id];
    }

    public function deleteMaster(int $userId, string $type, int $id): array
    {
        if (! $this->isAll('uks_master.manage', $userId)) {
            return $this->forbidden('mengelola Master UKS');
        }

        $table = $this->masterTable($type);
        if ($table === null) {
            return $this->fail('VALIDATION', 'Jenis master UKS tidak valid.');
        }

        $exists = $this->db->table($table)
            ->where('id', $id)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
        if (! $exists) {
            return $this->fail('NOT_FOUND', 'Master UKS tidak ditemukan.');
        }

        // Master/reference tidak ditombstone: opsi dinonaktifkan agar histori
        // tetap stabil dan opsi lama dapat diaktifkan kembali lewat Edit.
        $this->db->table($table)->where('id', $id)->update([
            'status_aktif' => 0,
            'updated_at' => $this->now(),
        ]);

        $this->activityLog->write(
            $userId,
            'UPDATE',
            'Master UKS',
            sprintf('Menonaktifkan master %s #%d.', $type, $id)
        );

        return ['success' => true, 'message' => 'Master UKS dinonaktifkan.'];
    }

    public function fixedOptions(): array
    {
        return [
            'status_gizi' => self::STATUS_GIZI,
            'status_tinggi' => self::STATUS_TINGGI,
            'kondisi_gigi_mulut' => self::KONDISI_GIGI,
            'buta_warna' => ['Ya', 'Tidak'],
            'hasil_pendengaran' => self::PENDENGARAN,
            'skrining_talasemia' => self::TALASEMIA,
            'skrining_tuberkulosis' => self::TUBERKULOSIS,
        ];
    }

    private function validateCkg(
        array $input,
        ?array $existing,
        int $idTahun,
        bool $fromImport = false
    ): array {
        $idSiswa = (int) ($input['id_siswa'] ?? 0);
        $tanggal = $fromImport
            ? (string) ($input['tanggal'] ?? '')
            : trim((string) ($input['tanggal'] ?? ''));

        if ($idSiswa <= 0) {
            return $this->fail('VALIDATION', 'Siswa wajib dipilih.');
        }
        if (! $this->validDate($tanggal)) {
            return $this->fail('VALIDATION', 'Tanggal pemeriksaan tidak valid.');
        }

        $membership = $this->scopeService->membershipForYear(
            $idSiswa,
            $idTahun
        );
        if ($membership === null) {
            return $this->fail(
                'VALIDATION',
                'Siswa tidak mempunyai membership pada Tahun Ajaran record.'
            );
        }

        if (! $fromImport) {
            $duplicate = $this->ckgModel->findActiveDuplicate(
                $idSiswa,
                $tanggal,
                $existing !== null ? (int) $existing['id'] : null
            );
            if ($duplicate) {
                return $this->fail(
                    'DUPLICATE',
                    'CKG siswa pada tanggal tersebut sudah ada.'
                );
            }
        }

        $data = [
            'id_siswa' => $idSiswa,
            'tanggal' => $tanggal,
        ];

        foreach ([
            'berat_badan',
            'tinggi_badan',
            'lingkar_perut',
            'gula_darah',
        ] as $field) {
            $value = $this->nullableNumber($input[$field] ?? null);
            if ($value === false) {
                return $this->fail('VALIDATION', "Field {$field} harus berupa angka non-negatif.");
            }
            $data[$field] = $value;
        }

        foreach (['tekanan_sistol', 'tekanan_diastol'] as $field) {
            $value = $this->nullableInteger($input[$field] ?? null);
            if ($value === false) {
                return $this->fail('VALIDATION', "Field {$field} harus berupa bilangan bulat non-negatif.");
            }
            $data[$field] = $value;
        }

        $optionMap = [
            'status_gizi' => self::STATUS_GIZI,
            'status_tinggi' => self::STATUS_TINGGI,
            'kondisi_gigi_mulut' => self::KONDISI_GIGI,
            'buta_warna' => ['Ya', 'Tidak'],
            'hasil_pendengaran' => self::PENDENGARAN,
            'skrining_talasemia' => self::TALASEMIA,
            'skrining_tuberkulosis' => self::TUBERKULOSIS,
        ];

        foreach ($optionMap as $field => $allowed) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && ! in_array($value, $allowed, true)) {
                return $this->fail('VALIDATION', "Pilihan {$field} tidak valid.");
            }
            $data[$field] = $value !== '' ? $value : null;
        }

        foreach (['visus_kanan', 'visus_kiri'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if (mb_strlen($value) > 30) {
                return $this->fail('VALIDATION', "Field {$field} maksimal 30 karakter.");
            }
            $data[$field] = $value !== '' ? $value : null;
        }

        return [
            'success' => true,
            'data' => $data,
            'membership' => $membership,
        ];
    }

    private function validateHarian(
        array $input,
        ?array $existing,
        int $idTahun
    ): array {
        $idSiswa = (int) ($input['id_siswa'] ?? 0);
        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        $jamMasuk = $this->normalizeTime($input['jam_masuk'] ?? null);
        $jamKeluar = $this->normalizeTime($input['jam_keluar'] ?? null, true);
        $idKeluhan = (int) ($input['id_keluhan'] ?? 0);
        $idHasil = (int) ($input['id_hasil'] ?? 0);
        $orangTua = trim((string) ($input['orang_tua_dihubungi'] ?? 'Tidak'));
        $actionIds = $input['tindakan'] ?? $input['tindakan_ids'] ?? [];

        if (! is_array($actionIds)) {
            $actionIds = [$actionIds];
        }
        $actionIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $actionIds
        ))));

        if ($idSiswa <= 0) {
            return $this->fail('VALIDATION', 'Siswa wajib dipilih.');
        }
        if (! $this->validDate($tanggal)) {
            return $this->fail('VALIDATION', 'Tanggal kunjungan tidak valid.');
        }
        if ($jamMasuk === null) {
            return $this->fail('VALIDATION', 'Jam masuk wajib menggunakan format waktu valid.');
        }
        if (($input['jam_keluar'] ?? '') !== '' && $jamKeluar === null) {
            return $this->fail('VALIDATION', 'Jam keluar tidak valid.');
        }
        if (! in_array($orangTua, ['Ya', 'Tidak'], true)) {
            return $this->fail('VALIDATION', 'Pilihan Orang tua dihubungi tidak valid.');
        }

        $membership = $this->scopeService->membershipForYear(
            $idSiswa,
            $idTahun
        );
        if ($membership === null) {
            return $this->fail(
                'VALIDATION',
                'Siswa tidak mempunyai membership pada Tahun Ajaran record.'
            );
        }

        $existingKeluhan = $existing !== null ? (int) ($existing['id_keluhan'] ?? 0) : 0;
        $existingHasil = $existing !== null ? (int) ($existing['id_hasil'] ?? 0) : 0;

        if (! $this->referenceAllowed('uks_ref_keluhan', $idKeluhan, $existingKeluhan)) {
            return $this->fail('VALIDATION', 'Keluhan tidak valid.');
        }
        if (! $this->referenceAllowed('uks_ref_hasil', $idHasil, $existingHasil)) {
            return $this->fail('VALIDATION', 'Hasil kunjungan tidak valid.');
        }

        $existingActionIds = [];
        if ($existing !== null) {
            foreach (($existing['tindakan'] ?? []) as $row) {
                $existingActionIds[] = (int) ($row['id'] ?? 0);
            }
        }

        foreach ($actionIds as $idAction) {
            if (! $this->referenceAllowed(
                'uks_ref_tindakan',
                $idAction,
                in_array($idAction, $existingActionIds, true) ? $idAction : 0
            )) {
                return $this->fail('VALIDATION', 'Tindakan UKS tidak valid.');
            }
        }

        $suhu = $this->nullableNumber($input['suhu_tubuh'] ?? null);
        if ($suhu === false) {
            return $this->fail('VALIDATION', 'Suhu tubuh harus berupa angka non-negatif.');
        }

        $catatan = trim((string) ($input['catatan_keluhan'] ?? ''));
        $tekanan = trim((string) ($input['tekanan_darah'] ?? ''));
        $obat = trim((string) ($input['obat_diberikan'] ?? ''));

        if (mb_strlen($tekanan) > 20) {
            return $this->fail('VALIDATION', 'Tekanan darah maksimal 20 karakter.');
        }
        if (mb_strlen($obat) > 255) {
            return $this->fail('VALIDATION', 'Obat yang diberikan maksimal 255 karakter.');
        }

        return [
            'success' => true,
            'data' => [
                'id_siswa' => $idSiswa,
                'tanggal' => $tanggal,
                'jam_masuk' => $jamMasuk,
                'id_keluhan' => $idKeluhan,
                'catatan_keluhan' => $catatan !== '' ? $catatan : null,
                'suhu_tubuh' => $suhu,
                'tekanan_darah' => $tekanan !== '' ? $tekanan : null,
                'obat_diberikan' => $obat !== '' ? $obat : null,
                'jam_keluar' => $jamKeluar,
                'id_hasil' => $idHasil,
                'orang_tua_dihubungi' => $orangTua,
            ],
            'action_ids' => $actionIds,
            'membership' => $membership,
        ];
    }

    private function buildFilter(array $input, bool $harian = false): array
    {
        $idTahun = (int) ($input['id_tahun'] ?? 0);
        $idKelas = (int) ($input['id_kelas'] ?? 0);
        $mulai = trim((string) ($input['tanggal_mulai'] ?? ''));
        $selesai = trim((string) ($input['tanggal_selesai'] ?? ''));

        if ($idTahun <= 0) {
            return $this->fail('INVALID_PERIOD', 'Tahun Ajaran wajib dipilih.');
        }
        if ($mulai !== '' && ! $this->validDate($mulai)) {
            return $this->fail('VALIDATION', 'Tanggal awal tidak valid.');
        }
        if ($selesai !== '' && ! $this->validDate($selesai)) {
            return $this->fail('VALIDATION', 'Tanggal akhir tidak valid.');
        }
        if ($mulai !== '' && $selesai !== '' && $mulai > $selesai) {
            return $this->fail('VALIDATION', 'Tanggal awal tidak boleh melewati tanggal akhir.');
        }

        $filter = [
            'id_tahun' => $idTahun,
            'id_kelas' => $idKelas > 0 ? $idKelas : null,
            'tanggal_mulai' => $mulai !== '' ? $mulai : null,
            'tanggal_selesai' => $selesai !== '' ? $selesai : null,
            'search' => trim((string) ($input['search'] ?? '')) ?: null,
        ];

        if ($harian) {
            $filter['id_keluhan'] = (int) ($input['id_keluhan'] ?? 0) ?: null;
            $filter['id_hasil'] = (int) ($input['id_hasil'] ?? 0) ?: null;
        }

        return ['success' => true, 'filter' => $filter];
    }

    private function classOptions(int $idTahun, ?array $allowedIds): array
    {
        $builder = $this->db
            ->table('anggota_kelas ak')
            ->distinct()
            ->select('k.id, k.nama_kelas')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->where('ak.id_tahun', $idTahun)
            ->where('k.deleted_at', null);

        if (is_array($allowedIds)) {
            if ($allowedIds === []) {
                return [];
            }
            $builder->whereIn('ak.id_siswa', $allowedIds);
        }

        return $builder
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function references(bool $activeOnly): array
    {
        return [
            'keluhan' => $this->referenceRows('uks_ref_keluhan', $activeOnly),
            'tindakan' => $this->referenceRows('uks_ref_tindakan', $activeOnly),
            'hasil' => $this->referenceRows('uks_ref_hasil', $activeOnly),
        ];
    }

    private function referenceRows(string $table, bool $activeOnly): array
    {
        $builder = $this->db
            ->table($table)
            ->select('id, nama, urutan, status_aktif, deleted_at');

        if ($activeOnly) {
            $builder->where('status_aktif', 1)->where('deleted_at', null);
        } else {
            $builder->where('deleted_at', null);
        }

        return $builder
            ->orderBy('urutan', 'ASC')
            ->orderBy('nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function referenceAllowed(string $table, int $id, int $existingId = 0): bool
    {
        if ($id <= 0) {
            return false;
        }
        if ($id === $existingId && $existingId > 0) {
            return $this->db->table($table)->where('id', $id)->countAllResults() > 0;
        }

        return $this->db
            ->table($table)
            ->where('id', $id)
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    private function masterTable(string $type): ?string
    {
        return match ($type) {
            'keluhan' => 'uks_ref_keluhan',
            'tindakan' => 'uks_ref_tindakan',
            'hasil' => 'uks_ref_hasil',
            default => null,
        };
    }

    private function importFieldMap(): array
    {
        return [
            'berat_badan' => ['berat_badan_kg', 'berat_badan'],
            'tinggi_badan' => ['tinggi_badan_cm', 'tinggi_badan'],
            'status_gizi' => ['status_gizi_bb_u', 'status_gizi'],
            'status_tinggi' => ['status_tinggi_tb_u', 'status_tinggi'],
            'lingkar_perut' => ['lingkar_perut_cm', 'lingkar_perut'],
            'tekanan_sistol' => ['tekanan_darah_sistol', 'tekanan_sistol'],
            'tekanan_diastol' => ['tekanan_darah_diastol', 'tekanan_diastol'],
            'gula_darah' => ['gula_darah_mg_dl', 'gula_darah'],
            'kondisi_gigi_mulut' => ['kondisi_gigi_dan_mulut', 'kondisi_gigi_mulut'],
            'visus_kanan' => ['visus_mata_kanan', 'visus_kanan'],
            'visus_kiri' => ['visus_mata_kiri', 'visus_kiri'],
            'buta_warna' => ['buta_warna'],
            'hasil_pendengaran' => ['hasil_tes_pendengaran', 'hasil_pendengaran'],
            'skrining_talasemia' => ['skrining_talasemia_kelas_vii', 'skrining_talasemia'],
            'skrining_tuberkulosis' => ['skrining_tuberkulosis'],
        ];
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(
            ['(', ')', '/', '°', '.', '-', '–', '—'],
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
            $value
        );
        $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? '';
        return trim($value, '_');
    }

    private function excelDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (Throwable $e) {
                return null;
            }
        }

        $raw = trim((string) $value);
        if ($this->validDate($raw)) {
            return $raw;
        }

        foreach (['d/m/Y', 'd-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $raw);
            $errors = \DateTimeImmutable::getLastErrors();
            if (
                $date !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            ) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function normalizeTime(mixed $value, bool $nullable = false): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return $nullable ? null : null;
        }

        foreach (['H:i', 'H:i:s'] as $format) {
            $time = \DateTimeImmutable::createFromFormat('!' . $format, $raw);
            $errors = \DateTimeImmutable::getLastErrors();
            if (
                $time !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            ) {
                return $time->format('H:i:s');
            }
        }

        return null;
    }

    private function nullableNumber(mixed $value): float|int|null|false
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if (! is_numeric($value) || (float) $value < 0) {
            return false;
        }
        return (float) $value;
    }

    private function nullableInteger(mixed $value): int|null|false
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            return false;
        }
        return (int) $value;
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }

    private function isAll(string $permission, int $userId): bool
    {
        return $this->authService->resolveScope($permission, $userId) === 'SEMUA';
    }

    private function now(): string
    {
        return Time::now(self::TZ)->format('Y-m-d H:i:s');
    }

    private function forbidden(string $action): array
    {
        return $this->fail(
            'FORBIDDEN',
            'Anda tidak memiliki hak ' . $action . '.'
        );
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
