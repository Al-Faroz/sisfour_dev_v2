<?php

namespace App\Services;

use CodeIgniter\I18n\Time;

class DokumenSiswaService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_EXPORT_ROWS = 50000;
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
        $auth = $this->requireManager($userId, 'dokumen_siswa.view_all');
        if (! $auth['success']) return $auth;

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) return $period;

        $idTahun = (int) $period['selected']['id'];
        $filter = $this->normalizeFilter($input);
        $limit = max(1, min(100, (int) ($input['limit'] ?? 25)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $builder = $this->managerBuilder($idTahun, $filter);
        $countBuilder = clone $builder;

        return [
            'success' => true,
            'can_manage' => $this->canManager($userId, 'dokumen_siswa.manage'),
            'can_export' => $this->canManager($userId, 'dokumen_siswa.export'),
            'tahun_aktif' => $period['active'],
            'tahun_dipilih' => $period['selected'],
            'tahun_options' => $period['options'],
            'classes' => $this->classesForPeriod($idTahun),
            'classes_by_period' => $this->classesByPeriod($period['options']),
            'batches' => $this->recentBatches($idTahun),
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

    public function searchStudentsForPeriod(
        int $userId,
        int $idTahun,
        string $query
    ): array {
        $auth = $this->requireManager($userId, 'dokumen_siswa.manage');
        if (! $auth['success']) return $auth;

        $query = trim($query);
        if (! $this->periodExists($idTahun)) {
            return $this->fail('INVALID_PERIOD', 'Tahun Ajaran/Semester tidak valid.');
        }
        if (mb_strlen($query) < 2) {
            return [
                'success' => true,
                'rows' => [],
                'message' => 'Ketik minimal 2 karakter.',
            ];
        }

        $rows = db_connect()->table('anggota_kelas ak')
            ->select(
                's.id, s.nisn, s.nama, k.nama_kelas, k.tingkat'
            )
            ->join('siswa s', 's.id = ak.id_siswa')
            ->join(
                'kelas k',
                'k.id = ak.id_kelas AND k.id_tahun = ak.id_tahun'
            )
            ->where('ak.id_tahun', $idTahun)
            ->where('s.deleted_at', null)
            ->where('k.deleted_at', null)
            ->groupStart()
                ->like('s.nama', $query)
                ->orLike('s.nisn', $query)
                ->orLike('s.nik', $query)
            ->groupEnd()
            ->orderBy('s.nama', 'ASC')
            ->limit(30)
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'rows' => array_map(
                static fn (array $row): array => [
                    'id' => (int) $row['id'],
                    'nisn' => (string) $row['nisn'],
                    'nama' => (string) $row['nama'],
                    'kelas' => (string) $row['nama_kelas'],
                    'tingkat' => (string) $row['tingkat'],
                    'text' => trim((string) $row['nisn'])
                        . ' — ' . trim((string) $row['nama'])
                        . ' · ' . trim((string) $row['nama_kelas']),
                ],
                $rows
            ),
        ];
    }

    public function create(int $userId, array $input): array
    {
        $auth = $this->requireManager($userId, 'dokumen_siswa.manage');
        if (! $auth['success']) return $auth;

        $validated = $this->validateDocument($input, null);
        if (! $validated['success']) return $validated;

        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
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
        $this->log($userId, 'CREATE', "Membuat Dokumen Siswa #{$id}");

        return ['success' => true, 'message' => 'Dokumen Siswa berhasil disimpan.', 'id' => $id];
    }

    public function update(int $userId, int $id, array $input): array
    {
        $auth = $this->requireManager($userId, 'dokumen_siswa.manage');
        if (! $auth['success']) return $auth;

        $existing = db_connect()->table('dokumen_siswa')->where('id', $id)->get()->getRowArray();
        if (! $existing) return $this->fail('NOT_FOUND', 'Dokumen Siswa tidak ditemukan.');

        $validated = $this->validateDocument($input, $existing);
        if (! $validated['success']) return $validated;

        $data = $validated['data'] + [
            'updated_by' => $userId,
            'updated_at' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ];

        if (! db_connect()->table('dokumen_siswa')->where('id', $id)->update($data)) {
            return $this->fail('UPDATE_FAILED', 'Dokumen Siswa gagal diperbarui.');
        }

        $this->log($userId, 'UPDATE', "Memperbarui Dokumen Siswa #{$id}");
        return ['success' => true, 'message' => 'Dokumen Siswa berhasil diperbarui.'];
    }

    public function archive(int $userId, int $id): array
    {
        $auth = $this->requireManager($userId, 'dokumen_siswa.manage');
        if (! $auth['success']) return $auth;

        if (db_connect()->table('dokumen_siswa')->where('id', $id)->countAllResults() < 1) {
            return $this->fail('NOT_FOUND', 'Dokumen Siswa tidak ditemukan.');
        }

        db_connect()->table('dokumen_siswa')->where('id', $id)->update([
            'status' => 'ARCHIVED',
            'updated_by' => $userId,
            'updated_at' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
        $this->log($userId, 'ARCHIVE', "Mengarsipkan Dokumen Siswa #{$id}");

        return ['success' => true, 'message' => 'Dokumen Siswa diarsipkan.'];
    }

    public function selfPage(int $userId, array $input): array
    {
        if ($this->authService->resolveScope('dokumen_siswa.view_self', $userId) !== 'DIRI_SENDIRI') {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki akses Dokumen Saya.');
        }

        $idSiswa = $this->studentIdentity($userId);
        if ($idSiswa <= 0) return $this->fail('NO_STUDENT_IDENTITY', 'User tidak memiliki identitas siswa.');

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) return $period;
        $idTahun = (int) $period['selected']['id'];

        return [
            'success' => true,
            'tahun_aktif' => $period['active'],
            'tahun_dipilih' => $period['selected'],
            'tahun_options' => $period['options'],
            'rows' => $this->selfRows($idSiswa, $idTahun),
        ];
    }

    public function openSelf(int $userId, int $idDokumen): array
    {
        if ($this->authService->resolveScope('dokumen_siswa.view_self', $userId) !== 'DIRI_SENDIRI') {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki akses Dokumen Saya.');
        }

        $idSiswa = $this->studentIdentity($userId);
        if ($idSiswa <= 0) return $this->fail('NO_STUDENT_IDENTITY', 'User tidak memiliki identitas siswa.');

        $doc = db_connect()->table('dokumen_siswa')
            ->where('id', $idDokumen)
            ->where('status', 'PUBLISHED')
            ->get()
            ->getRowArray();

        if (! $doc || ! $this->studentEligibleForDocument($idSiswa, $doc)) {
            return $this->fail('NOT_FOUND', 'Dokumen tidak ditemukan atau tidak tersedia untuk Anda.');
        }

        $link = StudentDocumentPolicyService::normalizeGoogleDriveUrl(
            (string) ($doc['link_gdrive'] ?? '')
        );
        if ($link === null) {
            return $this->fail(
                'NOT_FOUND',
                'Link Dokumen tidak valid.'
            );
        }

        db_connect()->table('dokumen_siswa_access_log')->insert([
            'id_dokumen' => $idDokumen,
            'id_user' => $userId,
            'id_siswa' => $idSiswa,
            'aksi' => 'OPEN',
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'link' => $link,
            'judul' => (string) $doc['judul'],
        ];
    }

    public function exportData(int $userId, array $input): array
    {
        $auth = $this->requireManager($userId, 'dokumen_siswa.export');
        if (! $auth['success']) return $auth;

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) return $period;

        $builder = $this->managerBuilder(
            (int) $period['selected']['id'],
            $this->normalizeFilter($input)
        );
        $total = (clone $builder)->countAllResults();
        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail('EXPORT_TOO_LARGE', 'Dokumen melebihi 50.000 baris. Persempit filter.');
        }

        return [
            'success' => true,
            'rows' => $builder->orderBy('ds.id', 'ASC')->get()->getResultArray(),
            'tahun_dipilih' => $period['selected'],
        ];
    }

    public function classesForPeriod(int $idTahun): array
    {
        if ($idTahun <= 0) return [];
        return db_connect()->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()->getResultArray();
    }

    private function classesByPeriod(array $periods): array
    {
        $result = [];
        foreach ($periods as $period) {
            $id = (int) ($period['id'] ?? 0);
            if ($id > 0) {
                $result[(string) $id] = $this->classesForPeriod($id);
            }
        }
        return $result;
    }

    private function recentBatches(int $idTahun): array
    {
        return db_connect()
            ->table('dokumen_siswa_import_batch b')
            ->select([
                'b.id',
                'b.judul',
                'b.id_tahun',
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
            ->join('users u', 'u.id = b.created_by', 'left')
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->where('b.id_tahun', $idTahun)
            ->orderBy('b.id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }

    private function validateDocument(array $input, ?array $existing): array
    {
        $idTahun = (int) ($input['id_tahun'] ?? 0);
        $target = StudentDocumentPolicyService::normalizeTarget((string) ($input['target_type'] ?? ''));
        $format = StudentDocumentPolicyService::normalizeFormat((string) ($input['format_file'] ?? ''));
        $status = StudentDocumentPolicyService::normalizeStatus((string) ($input['status'] ?? 'PUBLISHED'));
        $judul = StudentDocumentPolicyService::normalizeTitle((string) ($input['judul'] ?? ''));
        $link = StudentDocumentPolicyService::normalizeGoogleDriveUrl((string) ($input['link_gdrive'] ?? ''));

        if ($idTahun <= 0 || ! $this->periodExists($idTahun)) {
            return $this->fail('INVALID_PERIOD', 'Tahun Ajaran/Semester tidak valid.');
        }
        if ($target === null) return $this->fail('VALIDATION', 'Target Dokumen tidak valid.');
        if ($format === null) return $this->fail('VALIDATION', 'Format hanya PDF atau IMAGE.');
        if ($status === null) return $this->fail('VALIDATION', 'Status Dokumen tidak valid.');
        if ($judul === '' || mb_strlen($judul) > 200) return $this->fail('VALIDATION', 'Judul wajib diisi maksimal 200 karakter.');
        if ($link === null) return $this->fail('VALIDATION', 'Link wajib HTTPS Google Drive/Google Docs yang valid.');

        $idSiswa = null;
        $tingkat = null;

        if ($target === 'INDIVIDU') {
            $idSiswa = (int) ($input['id_siswa'] ?? 0);
            if ($idSiswa <= 0 || ! $this->studentHasMembership($idSiswa, $idTahun)) {
                return $this->fail('INVALID_TARGET', 'Siswa tidak memiliki membership pada Tahun Ajaran/Semester terpilih.');
            }
        } else {
            $tingkat = trim((string) ($input['tingkat'] ?? ''));
            if (! StudentDocumentPolicyService::validLevel($tingkat) || ! $this->levelExists($tingkat, $idTahun)) {
                return $this->fail('INVALID_TARGET', 'Tingkat tidak valid pada Tahun Ajaran/Semester terpilih.');
            }
        }

        $excludeId = (int) ($existing['id'] ?? 0);
        if ($this->duplicateExists($idTahun, $target, $idSiswa, $tingkat, $judul, $excludeId)) {
            return $this->fail('DUPLICATE_DOCUMENT', 'Dokumen Published dengan target, periode, dan judul yang sama sudah tersedia.');
        }

        return ['success' => true, 'data' => [
            'id_tahun' => $idTahun,
            'target_type' => $target,
            'id_siswa' => $idSiswa,
            'tingkat' => $tingkat,
            'judul' => $judul,
            'format_file' => $format,
            'link_gdrive' => $link,
            'status' => $status,
        ]];
    }

    private function managerBuilder(int $idTahun, array $filter)
    {
        $builder = db_connect()->table('dokumen_siswa ds')
            ->select([
                'ds.*', 'ta.nama_tahun', 'ta.semester',
                's.nisn', 's.nama AS nama_siswa',
                'k.nama_kelas',
                'u.username AS username_pencatat',
            ])
            ->select('COALESCE(g.nama, p.nama, u.username) AS nama_pencatat', false)
            ->join('tahun_ajaran ta', 'ta.id = ds.id_tahun')
            ->join('siswa s', 's.id = ds.id_siswa', 'left')
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = ds.id_siswa AND ak.id_tahun = ds.id_tahun',
                'left',
                false
            )
            ->join('kelas k', 'k.id = ak.id_kelas', 'left')
            ->join('users u', 'u.id = ds.created_by', 'left')
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->where('ds.id_tahun', $idTahun);

        if ($filter['target_type'] !== '') $builder->where('ds.target_type', $filter['target_type']);
        if ($filter['format_file'] !== '') $builder->where('ds.format_file', $filter['format_file']);
        if ($filter['status'] !== '') $builder->where('ds.status', $filter['status']);
        if ($filter['tingkat'] !== '') $builder->where('ds.tingkat', $filter['tingkat']);
        if ($filter['search'] !== '') {
            $builder->groupStart()
                ->like('ds.judul', $filter['search'])
                ->orLike('s.nama', $filter['search'])
                ->orLike('s.nisn', $filter['search'])
                ->groupEnd();
        }

        return $builder;
    }

    private function selfRows(int $idSiswa, int $idTahun): array
    {
        $membership = db_connect()->table('anggota_kelas ak')
            ->select('k.tingkat')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', $idTahun)
            ->get()->getRowArray();

        $tingkat = trim((string) ($membership['tingkat'] ?? ''));

        $builder = db_connect()->table('dokumen_siswa ds')
            ->select('ds.id, ds.judul, ds.target_type, ds.format_file, ds.tingkat, ds.id_tahun, ta.nama_tahun, ta.semester')
            ->join('tahun_ajaran ta', 'ta.id = ds.id_tahun')
            ->where('ds.id_tahun', $idTahun)
            ->where('ds.status', 'PUBLISHED')
            ->groupStart()
                ->groupStart()->where('ds.target_type', 'INDIVIDU')->where('ds.id_siswa', $idSiswa)->groupEnd();

        if ($tingkat !== '') {
            $builder->orGroupStart()
                ->where('ds.target_type', 'TINGKAT')
                ->where('ds.tingkat', $tingkat)
                ->groupEnd();
        }

        return $builder->groupEnd()
            ->orderBy('ds.judul', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function studentEligibleForDocument(int $idSiswa, array $doc): bool
    {
        if ((string) $doc['target_type'] === 'INDIVIDU') {
            return (int) ($doc['id_siswa'] ?? 0) === $idSiswa;
        }

        if ((string) $doc['target_type'] !== 'TINGKAT') return false;

        return db_connect()->table('anggota_kelas ak')
            ->join('kelas k', 'k.id = ak.id_kelas')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', (int) $doc['id_tahun'])
            ->where('k.tingkat', (string) $doc['tingkat'])
            ->countAllResults() > 0;
    }

    private function normalizeFilter(array $input): array
    {
        $target = strtoupper(trim((string) ($input['target_type'] ?? '')));
        $format = strtoupper(trim((string) ($input['format_file'] ?? '')));
        $status = strtoupper(trim((string) ($input['status'] ?? '')));
        $tingkat = trim((string) ($input['tingkat'] ?? ''));

        return [
            'target_type' => in_array($target, StudentDocumentPolicyService::TARGETS, true) ? $target : '',
            'format_file' => in_array($format, StudentDocumentPolicyService::FORMATS, true) ? $format : '',
            'status' => in_array($status, StudentDocumentPolicyService::STATUSES, true) ? $status : '',
            'tingkat' => StudentDocumentPolicyService::validLevel($tingkat) ? $tingkat : '',
            'search' => trim((string) ($input['search'] ?? '')),
        ];
    }

    private function duplicateExists(int $idTahun, string $target, ?int $idSiswa, ?string $tingkat, string $judul, int $excludeId = 0): bool
    {
        $builder = db_connect()->table('dokumen_siswa')
            ->where('id_tahun', $idTahun)
            ->where('target_type', $target)
            ->where('status', 'PUBLISHED')
            ->where(
                'LOWER(TRIM(judul)) = ' .
                db_connect()->escape(mb_strtolower($judul)),
                null,
                false
            );

        if ($target === 'INDIVIDU') $builder->where('id_siswa', $idSiswa);
        else $builder->where('tingkat', $tingkat);
        if ($excludeId > 0) $builder->where('id !=', $excludeId);

        return $builder->countAllResults() > 0;
    }

    private function periodExists(int $idTahun): bool
    {
        return db_connect()->table('tahun_ajaran')
            ->where('id', $idTahun)->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    private function studentHasMembership(int $idSiswa, int $idTahun): bool
    {
        return db_connect()->table('anggota_kelas ak')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->join('kelas k', 'k.id = ak.id_kelas AND k.id_tahun = ak.id_tahun')
            ->where('ak.id_siswa', $idSiswa)
            ->where('ak.id_tahun', $idTahun)
            ->where('s.deleted_at', null)
            ->where('k.deleted_at', null)
            ->countAllResults() > 0;
    }

    private function levelExists(string $tingkat, int $idTahun): bool
    {
        return db_connect()->table('kelas')
            ->where('id_tahun', $idTahun)
            ->where('tingkat', $tingkat)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    private function studentIdentity(int $userId): int
    {
        $row = db_connect()->table('users')->select('id_siswa')
            ->where('id', $userId)->where('status_aktif', 1)
            ->get()->getRowArray();
        return (int) ($row['id_siswa'] ?? 0);
    }

    private function requireManager(int $userId, string $permission): array
    {
        return $this->canManager($userId, $permission)
            ? ['success' => true]
            : $this->fail('FORBIDDEN', 'Dokumen Siswa hanya dapat dikelola Admin/Operator dengan permission terkait.');
    }

    private function canManager(int $userId, string $permission): bool
    {
        return array_intersect(self::MANAGER_ROLES, $this->authService->getUserRoles($userId)) !== []
            && $this->authService->resolveScope($permission, $userId) === 'SEMUA';
    }

    private function log(int $userId, string $action, string $description): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $action,
            'modul' => 'Dokumen Siswa',
            'keterangan' => $description,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
