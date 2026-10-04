<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use Throwable;

class DokumenSiswaService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_EXPORT_ROWS = 50000;
    private const MAX_HARD_DELETE = 500;
    private const MANAGER_ROLES = ['admin', 'operator'];

    protected AuthService $authService;
    protected PeriodContextService $periodContext;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->periodContext = new PeriodContextService();
    }

    public function managerPage(int $userId, array $input): array
    {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.view_all'
        );
        if (! $auth['success']) {
            return $auth;
        }

        $filter = $this->normalizeFilter($input);
        $active = $this->periodContext->active();
        $activeId = (int) ($active['id'] ?? 0);
        $limit = max(
            1,
            min(100, (int) ($input['limit'] ?? 25))
        );
        $offset = max(0, (int) ($input['offset'] ?? 0));

        $builder = $this->managerBuilder(
            $filter,
            $activeId
        );
        $countBuilder = clone $builder;

        return [
            'success' => true,
            'can_manage' => $this->canManager(
                $userId,
                'dokumen_siswa.manage'
            ),
            'can_export' => $this->canManager(
                $userId,
                'dokumen_siswa.export'
            ),
            'can_hard_delete' => $this->canManager(
                $userId,
                'dokumen_siswa.hard_delete'
            ),
            'active_period' => $active,
            'classes' => $activeId > 0
                ? $this->classesForPeriod($activeId)
                : [],
            'batches' => $this->recentBatches(),
            'rows' => $builder
                ->orderBy('ds.created_at', 'DESC')
                ->orderBy('ds.id', 'DESC')
                ->limit($limit, $offset)
                ->get()
                ->getResultArray(),
            'total' => $countBuilder->countAllResults(),
            'limit' => $limit,
            'offset' => $offset,
            'filter' => $filter,
        ];
    }

    public function searchStudents(
        int $userId,
        string $query
    ): array {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.manage'
        );
        if (! $auth['success']) {
            return $auth;
        }

        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [
                'success' => true,
                'rows' => [],
                'message' => 'Ketik minimal 2 karakter.',
            ];
        }

        $active = $this->periodContext->active();
        $activeId = (int) ($active['id'] ?? 0);

        $builder = db_connect()
            ->table('siswa s')
            ->select([
                's.id',
                's.nisn',
                's.nama',
                's.status_aktif',
            ])
            ->where('s.deleted_at', null)
            ->groupStart()
                ->like('s.nama', $query)
                ->orLike('s.nisn', $query)
                ->orLike('s.nik', $query)
            ->groupEnd();

        if ($activeId > 0) {
            $builder
                ->select(
                    'k.nama_kelas, k.tingkat'
                )
                ->join(
                    'anggota_kelas ak',
                    'ak.id_siswa = s.id'
                    . ' AND ak.id_tahun = '
                    . $activeId,
                    'left',
                    false
                )
                ->join(
                    'kelas k',
                    'k.id = ak.id_kelas'
                    . ' AND k.deleted_at IS NULL',
                    'left',
                    false
                );
        } else {
            $builder
                ->select('NULL AS nama_kelas', false)
                ->select('NULL AS tingkat', false);
        }

        $rows = $builder
            ->orderBy('s.nama', 'ASC')
            ->limit(30)
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'rows' => array_map(
                static function (array $row): array {
                    $class = trim(
                        (string) ($row['nama_kelas'] ?? '')
                    );
                    $status = trim(
                        (string) ($row['status_aktif'] ?? '')
                    );

                    $suffix = [];
                    if ($class !== '') {
                        $suffix[] = $class;
                    }
                    if ($status !== '' && $status !== 'Aktif') {
                        $suffix[] = $status;
                    }

                    return [
                        'id' => (int) $row['id'],
                        'nisn' => (string) $row['nisn'],
                        'nama' => (string) $row['nama'],
                        'kelas' => $class,
                        'tingkat' => (string) (
                            $row['tingkat'] ?? ''
                        ),
                        'text' => trim(
                            (string) $row['nisn']
                        )
                            . ' — '
                            . trim((string) $row['nama'])
                            . ($suffix !== []
                                ? ' · ' . implode(' · ', $suffix)
                                : ''),
                    ];
                },
                $rows
            ),
        ];
    }

    public function create(
        int $userId,
        array $input
    ): array {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.manage'
        );
        if (! $auth['success']) {
            return $auth;
        }

        $validated = $this->validateDocument(
            $input,
            null
        );
        if (! $validated['success']) {
            return $validated;
        }

        $now = Time::now(self::TZ)
            ->format('Y-m-d H:i:s');
        $data = $validated['data'] + [
            'id_import_batch' => null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_by' => $userId,
            'updated_at' => $now,
        ];

        $db = db_connect();
        $db->table('dokumen_siswa')->insert($data);
        $id = (int) $db->insertID();

        $this->log(
            $userId,
            'CREATE',
            "Membuat Dokumen Siswa #{$id}"
        );

        return [
            'success' => true,
            'message' => 'Dokumen Siswa berhasil disimpan.',
            'id' => $id,
        ];
    }

    public function update(
        int $userId,
        int $id,
        array $input
    ): array {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.manage'
        );
        if (! $auth['success']) {
            return $auth;
        }

        $existing = db_connect()
            ->table('dokumen_siswa')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (! $existing) {
            return $this->fail(
                'NOT_FOUND',
                'Dokumen Siswa tidak ditemukan.'
            );
        }

        $validated = $this->validateDocument(
            $input,
            $existing
        );
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'] + [
            'updated_by' => $userId,
            'updated_at' => Time::now(self::TZ)
                ->format('Y-m-d H:i:s'),
        ];

        if (! db_connect()
            ->table('dokumen_siswa')
            ->where('id', $id)
            ->update($data)
        ) {
            return $this->fail(
                'UPDATE_FAILED',
                'Dokumen Siswa gagal diperbarui.'
            );
        }

        $this->log(
            $userId,
            'UPDATE',
            "Memperbarui Dokumen Siswa #{$id}"
        );

        return [
            'success' => true,
            'message' => 'Dokumen Siswa berhasil diperbarui.',
        ];
    }

    public function archive(
        int $userId,
        int $id
    ): array {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.manage'
        );
        if (! $auth['success']) {
            return $auth;
        }

        if (db_connect()
            ->table('dokumen_siswa')
            ->where('id', $id)
            ->countAllResults() < 1
        ) {
            return $this->fail(
                'NOT_FOUND',
                'Dokumen Siswa tidak ditemukan.'
            );
        }

        db_connect()
            ->table('dokumen_siswa')
            ->where('id', $id)
            ->update([
                'status' => 'ARCHIVED',
                'updated_by' => $userId,
                'updated_at' => Time::now(self::TZ)
                    ->format('Y-m-d H:i:s'),
            ]);

        $this->log(
            $userId,
            'ARCHIVE',
            "Mengarsipkan Dokumen Siswa #{$id}"
        );

        return [
            'success' => true,
            'message' => 'Dokumen Siswa diarsipkan.',
        ];
    }

    public function hardDelete(
        int $userId,
        array $ids
    ): array {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.hard_delete'
        );
        if (! $auth['success']) {
            return $auth;
        }

        $ids = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $ids),
                    static fn (int $id): bool => $id > 0
                )
            )
        );

        if ($ids === []) {
            return $this->fail(
                'VALIDATION',
                'Pilih minimal satu Dokumen untuk dihapus.'
            );
        }

        if (count($ids) > self::MAX_HARD_DELETE) {
            return $this->fail(
                'TOO_MANY_TARGETS',
                'Maksimal 500 Dokumen per hard delete.'
            );
        }

        $docs = db_connect()
            ->table('dokumen_siswa')
            ->whereIn('id', $ids)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (count($docs) !== count($ids)) {
            return $this->fail(
                'INVALID_TARGET',
                'Sebagian Dokumen sudah tidak tersedia. Muat ulang data lalu ulangi.'
            );
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $now = Time::now(self::TZ)
                ->format('Y-m-d H:i:s');
            $batchKey = bin2hex(random_bytes(16));

            foreach ($docs as $doc) {
                $db->table('dokumen_siswa_delete_log')
                    ->insert([
                        'delete_batch_key' => $batchKey,
                        'id_dokumen_asal' => (int) $doc['id'],
                        'target_type' => (string) $doc['target_type'],
                        'id_siswa' => $doc['id_siswa'] !== null
                            ? (int) $doc['id_siswa']
                            : null,
                        'tingkat' => $doc['tingkat'],
                        'judul' => (string) $doc['judul'],
                        'format_file' => (string) $doc['format_file'],
                        'link_gdrive' => (string) $doc['link_gdrive'],
                        'status_asal' => (string) $doc['status'],
                        'id_import_batch' => $doc['id_import_batch'] !== null
                            ? (int) $doc['id_import_batch']
                            : null,
                        'created_by_asal' => $doc['created_by'] !== null
                            ? (int) $doc['created_by']
                            : null,
                        'created_at_asal' => $doc['created_at'],
                        'deleted_by' => $userId,
                        'deleted_at' => $now,
                    ]);
            }

            $db->table('dokumen_siswa_access_log')
                ->whereIn('id_dokumen', $ids)
                ->delete();

            $db->table('dokumen_siswa')
                ->whereIn('id', $ids)
                ->delete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi hard delete gagal.'
                );
            }

            $db->transCommit();

            $this->log(
                $userId,
                'HARD_DELETE',
                'Hard delete '
                . count($ids)
                . " Dokumen Siswa; delete batch {$batchKey}. "
                . 'File Google Drive tidak diubah.'
            );

            return [
                'success' => true,
                'message' => count($ids)
                    . ' Dokumen dihapus permanen dari SisFour. '
                    . 'File Google Drive tidak dihapus.',
                'deleted_count' => count($ids),
                'delete_batch_key' => $batchKey,
            ];
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->fail(
                'DELETE_FAILED',
                'Hard delete Dokumen Siswa gagal.'
            );
        }
    }

    public function selfPage(int $userId): array
    {
        if ($this->authService->resolveScope(
            'dokumen_siswa.view_self',
            $userId
        ) !== 'DIRI_SENDIRI') {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki akses Dokumen Saya.'
            );
        }

        $idSiswa = $this->studentIdentity($userId);
        if ($idSiswa <= 0) {
            return $this->fail(
                'NO_STUDENT_IDENTITY',
                'User tidak memiliki identitas siswa.'
            );
        }

        $active = $this->periodContext->active();
        $tingkat = $active !== null
            ? $this->studentLevelForPeriod(
                $idSiswa,
                (int) $active['id']
            )
            : null;

        return [
            'success' => true,
            'active_period' => $active,
            'current_level' => $tingkat,
            'rows' => $this->selfRows(
                $idSiswa,
                $tingkat
            ),
        ];
    }

    public function openSelf(
        int $userId,
        int $idDokumen
    ): array {
        if ($this->authService->resolveScope(
            'dokumen_siswa.view_self',
            $userId
        ) !== 'DIRI_SENDIRI') {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki akses Dokumen Saya.'
            );
        }

        $idSiswa = $this->studentIdentity($userId);
        if ($idSiswa <= 0) {
            return $this->fail(
                'NO_STUDENT_IDENTITY',
                'User tidak memiliki identitas siswa.'
            );
        }

        $doc = db_connect()
            ->table('dokumen_siswa')
            ->where('id', $idDokumen)
            ->where('status', 'PUBLISHED')
            ->get()
            ->getRowArray();

        if (! $doc || ! $this->studentEligibleForDocument(
            $idSiswa,
            $doc
        )) {
            return $this->fail(
                'NOT_FOUND',
                'Dokumen tidak ditemukan atau tidak tersedia untuk Anda.'
            );
        }

        $link = StudentDocumentPolicyService
            ::normalizeGoogleDriveUrl(
                (string) ($doc['link_gdrive'] ?? '')
            );

        if ($link === null) {
            return $this->fail(
                'NOT_FOUND',
                'Link Dokumen tidak valid.'
            );
        }

        db_connect()
            ->table('dokumen_siswa_access_log')
            ->insert([
                'id_dokumen' => $idDokumen,
                'id_user' => $userId,
                'id_siswa' => $idSiswa,
                'aksi' => 'OPEN',
                'waktu' => Time::now(self::TZ)
                    ->format('Y-m-d H:i:s'),
            ]);

        return [
            'success' => true,
            'link' => $link,
            'judul' => (string) $doc['judul'],
        ];
    }

    public function exportData(
        int $userId,
        array $input
    ): array {
        $auth = $this->requireManager(
            $userId,
            'dokumen_siswa.export'
        );
        if (! $auth['success']) {
            return $auth;
        }

        $active = $this->periodContext->active();
        $activeId = (int) ($active['id'] ?? 0);
        $builder = $this->managerBuilder(
            $this->normalizeFilter($input),
            $activeId
        );

        $total = (clone $builder)
            ->countAllResults();

        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail(
                'EXPORT_TOO_LARGE',
                'Dokumen melebihi 50.000 baris. Persempit filter.'
            );
        }

        $rows = $builder
            ->orderBy('ds.id', 'ASC')
            ->get()
            ->getResultArray();

        $ids = array_values(
            array_filter(
                array_map(
                    static fn (array $row): int =>
                        (int) ($row['id'] ?? 0),
                    $rows
                )
            )
        );

        $accessLogs = [];

        if ($ids !== []) {
            $logBuilder = db_connect()
                ->table('dokumen_siswa_access_log al')
                ->whereIn('al.id_dokumen', $ids);

            if ((clone $logBuilder)
                ->countAllResults() > self::MAX_EXPORT_ROWS
            ) {
                return $this->fail(
                    'EXPORT_TOO_LARGE',
                    'Riwayat akses Dokumen melebihi 50.000 baris. '
                    . 'Persempit filter.'
                );
            }

            $accessLogs = $logBuilder
                ->select([
                    'al.id',
                    'al.id_dokumen',
                    'al.id_user',
                    'al.id_siswa',
                    'al.aksi',
                    'al.waktu',
                    'ds.judul',
                    's.nisn',
                    's.nama AS nama_siswa',
                    'u.username',
                ])
                ->join(
                    'dokumen_siswa ds',
                    'ds.id = al.id_dokumen'
                )
                ->join(
                    'siswa s',
                    's.id = al.id_siswa',
                    'left'
                )
                ->join(
                    'users u',
                    'u.id = al.id_user',
                    'left'
                )
                ->orderBy('al.waktu', 'ASC')
                ->orderBy('al.id', 'ASC')
                ->get()
                ->getResultArray();
        }

        return [
            'success' => true,
            'rows' => $rows,
            'access_logs' => $accessLogs,
            'active_period' => $active,
        ];
    }

    public function recordExport(
        int $userId,
        int $documentCount
    ): void {
        $this->log(
            $userId,
            'EXPORT',
            "Export metadata {$documentCount} Dokumen Siswa "
            . 'beserta riwayat akses.'
        );
    }

    public function classesForPeriod(
        int $idTahun
    ): array {
        if ($idTahun <= 0) {
            return [];
        }

        return db_connect()
            ->table('kelas')
            ->select(
                'id, nama_kelas, tingkat, rombel'
            )
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function validateDocument(
        array $input,
        ?array $existing
    ): array {
        $target = StudentDocumentPolicyService
            ::normalizeTarget(
                (string) ($input['target_type'] ?? '')
            );
        $format = StudentDocumentPolicyService
            ::normalizeFormat(
                (string) ($input['format_file'] ?? '')
            );
        $status = StudentDocumentPolicyService
            ::normalizeStatus(
                (string) ($input['status'] ?? 'PUBLISHED')
            );
        $judul = StudentDocumentPolicyService
            ::normalizeTitle(
                (string) ($input['judul'] ?? '')
            );
        $link = StudentDocumentPolicyService
            ::normalizeGoogleDriveUrl(
                (string) ($input['link_gdrive'] ?? '')
            );

        if ($target === null) {
            return $this->fail(
                'VALIDATION',
                'Target Dokumen tidak valid.'
            );
        }

        if ($format === null) {
            return $this->fail(
                'VALIDATION',
                'Format hanya PDF atau IMAGE.'
            );
        }

        if ($status === null) {
            return $this->fail(
                'VALIDATION',
                'Status Dokumen tidak valid.'
            );
        }

        if ($judul === '' || mb_strlen($judul) > 200) {
            return $this->fail(
                'VALIDATION',
                'Judul wajib diisi maksimal 200 karakter.'
            );
        }

        if ($link === null) {
            return $this->fail(
                'VALIDATION',
                'Link wajib HTTPS drive.google.com yang valid.'
            );
        }

        $idSiswa = null;
        $tingkat = null;

        if ($target === 'INDIVIDU') {
            $idSiswa = (int) (
                $input['id_siswa'] ?? 0
            );

            if ($idSiswa <= 0
                || ! $this->studentExists($idSiswa)
            ) {
                return $this->fail(
                    'INVALID_TARGET',
                    'Siswa tidak ditemukan.'
                );
            }
        } else {
            $tingkat = trim(
                (string) ($input['tingkat'] ?? '')
            );

            if (! StudentDocumentPolicyService
                ::validLevel($tingkat)
            ) {
                return $this->fail(
                    'INVALID_TARGET',
                    'Tingkat hanya 7, 8, atau 9.'
                );
            }
        }

        $excludeId = (int) (
            $existing['id'] ?? 0
        );

        if ($this->duplicateExists(
            $target,
            $idSiswa,
            $tingkat,
            $judul,
            $excludeId
        )) {
            return $this->fail(
                'DUPLICATE_DOCUMENT',
                'Dokumen Published dengan target dan judul '
                . 'yang sama sudah tersedia.'
            );
        }

        return [
            'success' => true,
            'data' => [
                'target_type' => $target,
                'id_siswa' => $idSiswa,
                'tingkat' => $tingkat,
                'judul' => $judul,
                'format_file' => $format,
                'link_gdrive' => $link,
                'status' => $status,
            ],
        ];
    }

    private function managerBuilder(
        array $filter,
        int $activePeriodId
    ) {
        $builder = db_connect()
            ->table('dokumen_siswa ds')
            ->select([
                'ds.*',
                's.nisn',
                's.nama AS nama_siswa',
                'u.username AS username_pencatat',
            ])
            ->select(
                'COALESCE(g.nama, p.nama, u.username) AS nama_pencatat',
                false
            )
            ->join(
                'siswa s',
                's.id = ds.id_siswa',
                'left'
            )
            ->join(
                'users u',
                'u.id = ds.created_by',
                'left'
            )
            ->join(
                'guru g',
                'g.id = u.id_guru',
                'left'
            )
            ->join(
                'pegawai p',
                'p.id = u.id_pegawai',
                'left'
            );

        if ($activePeriodId > 0) {
            $builder
                ->select(
                    'k.nama_kelas AS nama_kelas_current'
                )
                ->join(
                    'anggota_kelas ak',
                    'ak.id_siswa = ds.id_siswa'
                    . ' AND ak.id_tahun = '
                    . $activePeriodId,
                    'left',
                    false
                )
                ->join(
                    'kelas k',
                    'k.id = ak.id_kelas'
                    . ' AND k.deleted_at IS NULL',
                    'left',
                    false
                );
        } else {
            $builder->select(
                'NULL AS nama_kelas_current',
                false
            );
        }

        if ($filter['target_type'] !== '') {
            $builder->where(
                'ds.target_type',
                $filter['target_type']
            );
        }

        if ($filter['format_file'] !== '') {
            $builder->where(
                'ds.format_file',
                $filter['format_file']
            );
        }

        if ($filter['status'] !== '') {
            $builder->where(
                'ds.status',
                $filter['status']
            );
        }

        if ($filter['tingkat'] !== '') {
            $builder->where(
                'ds.tingkat',
                $filter['tingkat']
            );
        }

        if ($filter['search'] !== '') {
            $builder
                ->groupStart()
                    ->like(
                        'ds.judul',
                        $filter['search']
                    )
                    ->orLike(
                        's.nama',
                        $filter['search']
                    )
                    ->orLike(
                        's.nisn',
                        $filter['search']
                    )
                ->groupEnd();
        }

        return $builder;
    }

    private function selfRows(
        int $idSiswa,
        ?string $tingkat
    ): array {
        $builder = db_connect()
            ->table('dokumen_siswa ds')
            ->select([
                'ds.id',
                'ds.judul',
                'ds.target_type',
                'ds.format_file',
                'ds.tingkat',
            ])
            ->where(
                'ds.status',
                'PUBLISHED'
            )
            ->groupStart()
                ->groupStart()
                    ->where(
                        'ds.target_type',
                        'INDIVIDU'
                    )
                    ->where(
                        'ds.id_siswa',
                        $idSiswa
                    )
                ->groupEnd();

        if ($tingkat !== null
            && StudentDocumentPolicyService
                ::validLevel($tingkat)
        ) {
            $builder
                ->orGroupStart()
                    ->where(
                        'ds.target_type',
                        'TINGKAT'
                    )
                    ->where(
                        'ds.tingkat',
                        $tingkat
                    )
                ->groupEnd();
        }

        return $builder
            ->groupEnd()
            ->orderBy(
                'ds.target_type',
                'ASC'
            )
            ->orderBy(
                'ds.judul',
                'ASC'
            )
            ->get()
            ->getResultArray();
    }

    private function studentEligibleForDocument(
        int $idSiswa,
        array $doc
    ): bool {
        if ((string) $doc['target_type']
            === 'INDIVIDU'
        ) {
            return (int) (
                $doc['id_siswa'] ?? 0
            ) === $idSiswa;
        }

        if ((string) $doc['target_type']
            !== 'TINGKAT'
        ) {
            return false;
        }

        $active = $this->periodContext->active();
        if ($active === null) {
            return false;
        }

        $tingkat = $this->studentLevelForPeriod(
            $idSiswa,
            (int) $active['id']
        );

        return $tingkat !== null
            && $tingkat
                === (string) ($doc['tingkat'] ?? '');
    }

    private function normalizeFilter(
        array $input
    ): array {
        $target = strtoupper(
            trim((string) (
                $input['target_type'] ?? ''
            ))
        );
        $format = strtoupper(
            trim((string) (
                $input['format_file'] ?? ''
            ))
        );
        $status = strtoupper(
            trim((string) (
                $input['status'] ?? ''
            ))
        );
        $tingkat = trim(
            (string) ($input['tingkat'] ?? '')
        );

        return [
            'target_type' => in_array(
                $target,
                StudentDocumentPolicyService::TARGETS,
                true
            )
                ? $target
                : '',
            'format_file' => in_array(
                $format,
                StudentDocumentPolicyService::FORMATS,
                true
            )
                ? $format
                : '',
            'status' => in_array(
                $status,
                StudentDocumentPolicyService::STATUSES,
                true
            )
                ? $status
                : '',
            'tingkat' => StudentDocumentPolicyService
                ::validLevel($tingkat)
                ? $tingkat
                : '',
            'search' => trim(
                (string) ($input['search'] ?? '')
            ),
        ];
    }

    private function duplicateExists(
        string $target,
        ?int $idSiswa,
        ?string $tingkat,
        string $judul,
        int $excludeId = 0
    ): bool {
        $builder = db_connect()
            ->table('dokumen_siswa')
            ->where(
                'target_type',
                $target
            )
            ->where(
                'status',
                'PUBLISHED'
            )
            ->where(
                'LOWER(TRIM(judul)) = '
                . db_connect()->escape(
                    mb_strtolower($judul)
                ),
                null,
                false
            );

        if ($target === 'INDIVIDU') {
            $builder->where(
                'id_siswa',
                $idSiswa
            );
        } else {
            $builder->where(
                'tingkat',
                $tingkat
            );
        }

        if ($excludeId > 0) {
            $builder->where(
                'id !=',
                $excludeId
            );
        }

        return $builder->countAllResults() > 0;
    }

    private function studentExists(
        int $idSiswa
    ): bool {
        return $idSiswa > 0
            && db_connect()
                ->table('siswa')
                ->where('id', $idSiswa)
                ->where('deleted_at', null)
                ->countAllResults() > 0;
    }

    private function studentLevelForPeriod(
        int $idSiswa,
        int $idTahun
    ): ?string {
        if ($idSiswa <= 0 || $idTahun <= 0) {
            return null;
        }

        $row = db_connect()
            ->table('anggota_kelas ak')
            ->select('k.tingkat')
            ->join(
                'kelas k',
                'k.id = ak.id_kelas'
                . ' AND k.id_tahun = ak.id_tahun'
            )
            ->where(
                'ak.id_siswa',
                $idSiswa
            )
            ->where(
                'ak.id_tahun',
                $idTahun
            )
            ->where(
                'k.deleted_at',
                null
            )
            ->get()
            ->getRowArray();

        $value = trim(
            (string) ($row['tingkat'] ?? '')
        );

        return StudentDocumentPolicyService
            ::validLevel($value)
            ? $value
            : null;
    }

    private function studentIdentity(
        int $userId
    ): int {
        $row = db_connect()
            ->table('users')
            ->select('id_siswa')
            ->where('id', $userId)
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        return (int) (
            $row['id_siswa'] ?? 0
        );
    }

    private function recentBatches(): array
    {
        return db_connect()
            ->table(
                'dokumen_siswa_import_batch b'
            )
            ->select([
                'b.id',
                'b.judul',
                'b.format_file',
                'b.source_filename',
                'b.total_row',
                'b.total_valid',
                'b.total_error',
                'b.status',
                'b.created_at',
                'b.committed_at',
                'b.rolled_back_at',
                'u.username AS username_pencatat',
            ])
            ->select(
                'COALESCE(g.nama, p.nama, u.username) AS nama_pencatat',
                false
            )
            ->join(
                'users u',
                'u.id = b.created_by',
                'left'
            )
            ->join(
                'guru g',
                'g.id = u.id_guru',
                'left'
            )
            ->join(
                'pegawai p',
                'p.id = u.id_pegawai',
                'left'
            )
            ->orderBy('b.id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }

    private function requireManager(
        int $userId,
        string $permission
    ): array {
        return $this->canManager(
            $userId,
            $permission
        )
            ? ['success' => true]
            : $this->fail(
                'FORBIDDEN',
                'Dokumen Siswa hanya dapat dikelola '
                . 'Admin/Operator dengan permission terkait.'
            );
    }

    private function canManager(
        int $userId,
        string $permission
    ): bool {
        return array_intersect(
            self::MANAGER_ROLES,
            $this->authService->getUserRoles(
                $userId
            )
        ) !== []
            && $this->authService->resolveScope(
                $permission,
                $userId
            ) === 'SEMUA';
    }

    private function log(
        int $userId,
        string $action,
        string $description
    ): void {
        db_connect()
            ->table('log_activity')
            ->insert([
                'id_user' => $userId,
                'aksi' => $action,
                'modul' => 'Dokumen Siswa',
                'keterangan' => $description,
                'waktu' => Time::now(self::TZ)
                    ->format('Y-m-d H:i:s'),
            ]);
    }

    private function fail(
        string $code,
        string $message
    ): array {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
