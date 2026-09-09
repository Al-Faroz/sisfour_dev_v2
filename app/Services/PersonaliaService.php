<?php

namespace App\Services;

use App\Models\DokumenPersonaliaModel;
use App\Models\RiwayatPangkatModel;
use App\Models\RiwayatPendidikanModel;
use App\Models\RiwayatPenugasanModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Model;
use Config\Database;
use Throwable;

/**
 * Business rule Riwayat Personalia Guru/Pegawai.
 *
 * Security boundary data:
 * - self hanya identity yang terhubung ke users;
 * - Master Guru/Pegawai memakai permission existing;
 * - Pimpinan/readonly dapat melihat tetapi tidak memutasi;
 * - dokumen disimpan di WRITEPATH, bukan public/uploads.
 */
class PersonaliaService
{
    public const CATEGORY_PENDIDIKAN = 'pendidikan';
    public const CATEGORY_PENUGASAN = 'penugasan';
    public const CATEGORY_PANGKAT = 'pangkat';
    public const CATEGORY_DOKUMEN = 'dokumen';

    public const STORAGE_ROOT = 'uploads/personalia/';

    public const DOCUMENT_TYPES = [
        'KTP / KK',
        'SK Pengangkatan Awal',
        'Kartu / Bukti NUPTK',
        'Sertifikat Pendidik',
        'Kartu Pegawai',
        'Lainnya',
    ];

    protected BaseConnection $db;
    protected AuthService $authService;
    protected UploadService $uploadService;
    protected ActivityLogService $activityLog;
    protected RiwayatPendidikanModel $pendidikanModel;
    protected RiwayatPenugasanModel $penugasanModel;
    protected RiwayatPangkatModel $pangkatModel;
    protected DokumenPersonaliaModel $dokumenModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
        $this->uploadService = new UploadService();
        $this->activityLog = new ActivityLogService();
        $this->pendidikanModel = new RiwayatPendidikanModel();
        $this->penugasanModel = new RiwayatPenugasanModel();
        $this->pangkatModel = new RiwayatPangkatModel();
        $this->dokumenModel = new DokumenPersonaliaModel();
    }

    /**
     * Resolve identity self dari users.
     *
     * @return array{success:bool,owner_id?:int,message?:string,code?:string}
     */
    public function resolveSelfOwner(int $actorUserId, string $ownerType): array
    {
        if (! in_array($ownerType, ['guru', 'pegawai'], true)) {
            return $this->fail('VALIDATION', 'Jenis personalia tidak valid.');
        }

        $field = $ownerType === 'guru' ? 'id_guru' : 'id_pegawai';
        $user = $this->db
            ->table('users')
            ->select('id, ' . $field . ', status_aktif')
            ->where('id', $actorUserId)
            ->get()
            ->getRowArray();

        if (! $user || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return $this->fail('UNAUTHENTICATED', 'Akun tidak aktif atau tidak ditemukan.');
        }

        $ownerId = (int) ($user[$field] ?? 0);
        if ($ownerId <= 0) {
            return $this->fail(
                'IDENTITY_NOT_LINKED',
                'Akun ini tidak terhubung dengan identitas ' . ucfirst($ownerType) . '.'
            );
        }

        return ['success' => true, 'owner_id' => $ownerId];
    }

    public function getPageContext(int $actorUserId, string $ownerType, int $ownerId): array
    {
        $access = $this->access($actorUserId, $ownerType, $ownerId);
        if (! $access['success']) {
            return $access;
        }

        $owner = $this->owner($ownerType, $ownerId);
        if ($owner === null) {
            return $this->fail('NOT_FOUND', 'Data personalia tidak ditemukan.');
        }

        return [
            'success' => true,
            'message' => 'Data personalia berhasil dimuat.',
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'owner' => $owner,
            'is_self' => $access['is_self'],
            'can_edit' => $access['can_edit'],
            'can_delete' => $access['can_delete'],
            'can_view_documents' => $access['can_view_documents'],
            'pendidikan' => $this->listOwner($this->pendidikanModel, $ownerType, $ownerId, 'tahun_lulus DESC, id DESC'),
            'penugasan' => $this->listOwner($this->penugasanModel, $ownerType, $ownerId, 'tanggal_mulai DESC, id DESC'),
            'pangkat' => $this->listOwner($this->pangkatModel, $ownerType, $ownerId, 'tmt_pangkat DESC, id DESC'),
            'dokumen' => $access['can_view_documents']
                ? $this->listOwner($this->dokumenModel, $ownerType, $ownerId, 'tanggal_dokumen DESC, id DESC')
                : [],
            'document_types' => self::DOCUMENT_TYPES,
        ];
    }

    /**
     * @param array<string, UploadedFile|mixed> $files
     */
    public function saveRecord(
        int $actorUserId,
        string $ownerType,
        int $ownerId,
        string $category,
        array $input,
        array $files = []
    ): array {
        $access = $this->access($actorUserId, $ownerType, $ownerId);
        if (! $access['success']) {
            return $access;
        }
        if (! $access['can_edit']) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk mengubah data personalia ini.');
        }

        $owner = $this->owner($ownerType, $ownerId);
        if ($owner === null) {
            return $this->fail('NOT_FOUND', 'Data personalia tidak ditemukan.');
        }

        $category = strtolower(trim($category));
        if (! in_array($category, $this->categories(), true)) {
            return $this->fail('VALIDATION', 'Kategori riwayat tidak valid.');
        }

        return match ($category) {
            self::CATEGORY_PENDIDIKAN => $this->savePendidikan($actorUserId, $ownerType, $ownerId, $input, $files),
            self::CATEGORY_PENUGASAN => $this->savePenugasan($actorUserId, $ownerType, $ownerId, $input, $files),
            self::CATEGORY_PANGKAT => $this->savePangkat($actorUserId, $ownerType, $ownerId, $input, $files),
            self::CATEGORY_DOKUMEN => $this->saveDokumen($actorUserId, $ownerType, $ownerId, $input, $files),
        };
    }

    public function deleteRecord(
        int $actorUserId,
        string $ownerType,
        int $ownerId,
        string $category,
        int $recordId
    ): array {
        $access = $this->access($actorUserId, $ownerType, $ownerId);
        if (! $access['success']) {
            return $access;
        }
        if (! $access['can_delete']) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk menghapus data personalia ini.');
        }

        $category = strtolower(trim($category));
        $model = $this->modelFor($category);
        if ($model === null) {
            return $this->fail('VALIDATION', 'Kategori riwayat tidak valid.');
        }

        $record = $this->ownedRecord($model, $ownerType, $ownerId, $recordId);
        if ($record === null) {
            return $this->fail('NOT_FOUND', 'Record riwayat tidak ditemukan.');
        }

        $paths = $this->recordFilePaths($category, $record);

        $this->db->transBegin();
        try {
            if (! $model->delete($recordId)) {
                throw new \RuntimeException('Record gagal dihapus.');
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $actorUserId,
                'DELETE',
                'Personalia ' . ucfirst($category),
                sprintf('Menghapus %s ID %d milik %s ID %d.', $category, $recordId, $ownerType, $ownerId)
            );

            $this->db->transCommit();
            $this->deleteFiles($paths);

            return ['success' => true, 'message' => 'Data ' . $this->categoryLabel($category) . ' berhasil dihapus.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('DELETE_FAILED', $e->getMessage());
        }
    }

    /**
     * Resolve file non-public setelah actor lolos authorization.
     *
     * @return array{success:bool,path?:string,filename?:string,mime_type?:string,code?:string,message?:string}
     */
    public function resolveFile(
        int $actorUserId,
        string $category,
        int $recordId,
        string $field
    ): array {
        $category = strtolower(trim($category));
        $model = $this->modelFor($category);
        if ($model === null) {
            return $this->fail('VALIDATION', 'Kategori dokumen tidak valid.');
        }

        $allowedFields = match ($category) {
            self::CATEGORY_PENDIDIKAN => ['file_ijazah', 'file_transkrip'],
            self::CATEGORY_PENUGASAN => ['file_sk_penugasan'],
            self::CATEGORY_PANGKAT => ['file_sk_pangkat'],
            self::CATEGORY_DOKUMEN => ['file_path'],
            default => [],
        };

        if (! in_array($field, $allowedFields, true)) {
            return $this->fail('VALIDATION', 'Field dokumen tidak valid.');
        }

        $record = $model->find($recordId);
        if (! is_array($record)) {
            return $this->fail('NOT_FOUND', 'Dokumen tidak ditemukan.');
        }

        $owner = $this->recordOwner($record);
        if ($owner === null) {
            return $this->fail('NOT_FOUND', 'Pemilik dokumen tidak valid.');
        }

        $access = $this->access($actorUserId, $owner['type'], $owner['id']);
        if (! $access['success']) {
            return $access;
        }
        if (! $access['can_view_documents']) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk membuka dokumen personalia mentah.');
        }

        $relative = self::normalizeStoredPath(
            $record[$field] ?? null,
            $owner['type'],
            $owner['id']
        );
        if ($relative === null) {
            return $this->fail('NOT_FOUND', 'File dokumen belum tersedia atau path penyimpanan tidak valid.');
        }

        $path = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_file($path)) {
            return $this->fail('NOT_FOUND', 'File dokumen tidak ditemukan pada penyimpanan.');
        }

        // Jangan mempercayai mime_type yang tersimpan di database untuk
        // header response. MIME selalu diturunkan dari ekstensi storage
        // yang sudah dibatasi oleh normalizeStoredPath().
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        $filename = $category === self::CATEGORY_DOKUMEN && ! empty($record['nama_file_asli'])
            ? basename((string) $record['nama_file_asli'])
            : $this->downloadFilename($category, $field, $recordId, $extension);
        $filename = preg_replace('/[\r\n"]+/', '_', $filename) ?: $this->downloadFilename(
            $category,
            $field,
            $recordId,
            $extension
        );

        return [
            'success' => true,
            'path' => $path,
            'filename' => $filename,
            'mime_type' => $mime,
        ];
    }

    private function savePendidikan(
        int $actorUserId,
        string $ownerType,
        int $ownerId,
        array $input,
        array $files
    ): array {
        $id = max(0, (int) ($input['id'] ?? 0));
        $existing = $id > 0
            ? $this->ownedRecord($this->pendidikanModel, $ownerType, $ownerId, $id)
            : null;

        if ($id > 0 && $existing === null) {
            return $this->fail('NOT_FOUND', 'Riwayat pendidikan tidak ditemukan.');
        }

        $tahun = (int) ($input['tahun_lulus'] ?? 0);
        $currentYear = (int) date('Y');
        $payload = $this->ownerPayload($ownerType, $ownerId) + [
            'tingkat_pendidikan' => $this->text($input['tingkat_pendidikan'] ?? null, 20),
            'nama_institusi' => $this->text($input['nama_institusi'] ?? null, 150),
            'program_studi' => $this->nullableText($input['program_studi'] ?? null, 150),
            'tahun_lulus' => $tahun,
            'no_ijazah' => $this->nullableText($input['no_ijazah'] ?? null, 100),
            'file_ijazah' => $existing['file_ijazah'] ?? null,
            'file_transkrip' => $existing['file_transkrip'] ?? null,
        ];

        if ($payload['tingkat_pendidikan'] === '' || $payload['nama_institusi'] === '') {
            return $this->fail('VALIDATION', 'Tingkat pendidikan dan nama institusi wajib diisi.');
        }
        if ($tahun < 1950 || $tahun > $currentYear + 1) {
            return $this->fail('VALIDATION', 'Tahun lulus tidak valid.');
        }

        return $this->persistWithFiles(
            $actorUserId,
            self::CATEGORY_PENDIDIKAN,
            $ownerType,
            $ownerId,
            $this->pendidikanModel,
            $id,
            $payload,
            $existing,
            $files,
            [
                'file_ijazah' => 'ijazah',
                'file_transkrip' => 'transkrip',
            ]
        );
    }

    private function savePenugasan(
        int $actorUserId,
        string $ownerType,
        int $ownerId,
        array $input,
        array $files
    ): array {
        $id = max(0, (int) ($input['id'] ?? 0));
        $existing = $id > 0
            ? $this->ownedRecord($this->penugasanModel, $ownerType, $ownerId, $id)
            : null;

        if ($id > 0 && $existing === null) {
            return $this->fail('NOT_FOUND', 'Riwayat penugasan tidak ditemukan.');
        }

        $mulai = $this->date($input['tanggal_mulai'] ?? null);
        $selesai = $this->nullableDate($input['tanggal_selesai'] ?? null);
        if ($mulai === null) {
            return $this->fail('VALIDATION', 'Tanggal mulai penugasan wajib diisi dengan format YYYY-MM-DD.');
        }
        if ($selesai === false) {
            return $this->fail('VALIDATION', 'Tanggal selesai penugasan tidak valid.');
        }
        if (is_string($selesai) && $selesai < $mulai) {
            return $this->fail('VALIDATION', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
        }

        $payload = $this->ownerPayload($ownerType, $ownerId) + [
            'instansi_penugasan' => $this->text($input['instansi_penugasan'] ?? null, 150),
            'jabatan_tugas' => $this->text($input['jabatan_tugas'] ?? null, 120),
            'mata_pelajaran' => $this->nullableText($input['mata_pelajaran'] ?? null, 120),
            'no_sk_penugasan' => $this->nullableText($input['no_sk_penugasan'] ?? null, 100),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai ?: null,
            'file_sk_penugasan' => $existing['file_sk_penugasan'] ?? null,
        ];

        if ($payload['instansi_penugasan'] === '' || $payload['jabatan_tugas'] === '') {
            return $this->fail('VALIDATION', 'Instansi dan Jabatan/Tugas wajib diisi.');
        }

        return $this->persistWithFiles(
            $actorUserId,
            self::CATEGORY_PENUGASAN,
            $ownerType,
            $ownerId,
            $this->penugasanModel,
            $id,
            $payload,
            $existing,
            $files,
            ['file_sk_penugasan' => 'sk_penugasan']
        );
    }

    private function savePangkat(
        int $actorUserId,
        string $ownerType,
        int $ownerId,
        array $input,
        array $files
    ): array {
        $id = max(0, (int) ($input['id'] ?? 0));
        $existing = $id > 0
            ? $this->ownedRecord($this->pangkatModel, $ownerType, $ownerId, $id)
            : null;

        if ($id > 0 && $existing === null) {
            return $this->fail('NOT_FOUND', 'Riwayat kepangkatan tidak ditemukan.');
        }

        $tmt = $this->date($input['tmt_pangkat'] ?? null);
        if ($tmt === null) {
            return $this->fail('VALIDATION', 'TMT pangkat wajib diisi dengan format YYYY-MM-DD.');
        }

        $payload = $this->ownerPayload($ownerType, $ownerId) + [
            'golongan_ruang' => $this->text($input['golongan_ruang'] ?? null, 30),
            'nama_pangkat' => $this->nullableText($input['nama_pangkat'] ?? null, 100),
            'tmt_pangkat' => $tmt,
            'no_sk_pangkat' => $this->nullableText($input['no_sk_pangkat'] ?? null, 100),
            'file_sk_pangkat' => $existing['file_sk_pangkat'] ?? null,
        ];

        if ($payload['golongan_ruang'] === '') {
            return $this->fail('VALIDATION', 'Golongan/Ruang wajib diisi.');
        }

        return $this->persistWithFiles(
            $actorUserId,
            self::CATEGORY_PANGKAT,
            $ownerType,
            $ownerId,
            $this->pangkatModel,
            $id,
            $payload,
            $existing,
            $files,
            ['file_sk_pangkat' => 'sk_pangkat']
        );
    }

    private function saveDokumen(
        int $actorUserId,
        string $ownerType,
        int $ownerId,
        array $input,
        array $files
    ): array {
        $id = max(0, (int) ($input['id'] ?? 0));
        $existing = $id > 0
            ? $this->ownedRecord($this->dokumenModel, $ownerType, $ownerId, $id)
            : null;

        if ($id > 0 && $existing === null) {
            return $this->fail('NOT_FOUND', 'Dokumen personalia tidak ditemukan.');
        }

        $tanggal = $this->nullableDate($input['tanggal_dokumen'] ?? null);
        if ($tanggal === false) {
            return $this->fail('VALIDATION', 'Tanggal dokumen tidak valid.');
        }

        $jenis = $this->text($input['jenis_dokumen'] ?? null, 60);
        $nama = $this->text($input['nama_dokumen'] ?? null, 150);
        if ($jenis === '' || $nama === '') {
            return $this->fail('VALIDATION', 'Jenis dan nama dokumen wajib diisi.');
        }
        if (! self::isAllowedDocumentType($jenis)) {
            return $this->fail('VALIDATION', 'Jenis dokumen tidak termasuk daftar yang diizinkan.');
        }

        $payload = $this->ownerPayload($ownerType, $ownerId) + [
            'jenis_dokumen' => $jenis,
            'nama_dokumen' => $nama,
            'nomor_dokumen' => $this->nullableText($input['nomor_dokumen'] ?? null, 100),
            'tanggal_dokumen' => $tanggal ?: null,
            'file_path' => $existing['file_path'] ?? null,
            'nama_file_asli' => $existing['nama_file_asli'] ?? null,
            'mime_type' => $existing['mime_type'] ?? null,
        ];

        $file = $files['file_dokumen'] ?? null;
        if ($id <= 0 && ! $this->hasUpload($file)) {
            return $this->fail('VALIDATION', 'File dokumen wajib dipilih untuk dokumen baru.');
        }

        $newPath = null;
        $oldPath = null;
        $transactionStarted = false;
        try {
            if ($this->hasUpload($file)) {
                /** @var UploadedFile $file */
                $stored = $this->storeDocument($file, $ownerType, $ownerId, 'dokumen');
                $newPath = $stored['relative_path'];
                $oldPath = $existing['file_path'] ?? null;
                $payload['file_path'] = $newPath;
                $payload['nama_file_asli'] = $stored['original_name'];
                $payload['mime_type'] = $stored['mime_type'];
            }

            $this->db->transBegin();
            $transactionStarted = true;
            $savedId = $id;
            if ($id > 0) {
                if (! $this->dokumenModel->update($id, $payload)) {
                    throw new \RuntimeException('Dokumen gagal diperbarui.');
                }
            } else {
                $savedId = (int) $this->dokumenModel->insert($payload, true);
                if ($savedId <= 0) {
                    throw new \RuntimeException('Dokumen gagal disimpan.');
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $actorUserId,
                $id > 0 ? 'UPDATE' : 'CREATE',
                'Personalia Dokumen',
                sprintf('%s dokumen ID %d milik %s ID %d.', $id > 0 ? 'Memperbarui' : 'Menambahkan', $savedId, $ownerType, $ownerId)
            );

            $this->db->transCommit();
            if ($oldPath && $oldPath !== $newPath) {
                $this->deleteFiles([$oldPath]);
            }

            return [
                'success' => true,
                'message' => $id > 0 ? 'Dokumen personalia berhasil diperbarui.' : 'Dokumen personalia berhasil ditambahkan.',
                'id' => $savedId,
            ];
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->db->transRollback();
            }
            if ($newPath !== null) {
                $this->deleteFiles([$newPath]);
            }
            return $this->fail('SAVE_FAILED', $e->getMessage());
        }
    }

    /**
     * @param array<string, UploadedFile|mixed> $files
     * @param array<string,string> $fileFields field_db => prefix
     */
    private function persistWithFiles(
        int $actorUserId,
        string $category,
        string $ownerType,
        int $ownerId,
        Model $model,
        int $id,
        array $payload,
        ?array $existing,
        array $files,
        array $fileFields
    ): array {
        $newPaths = [];
        $oldPaths = [];
        $transactionStarted = false;

        try {
            foreach ($fileFields as $field => $prefix) {
                $file = $files[$field] ?? null;
                if (! $this->hasUpload($file)) {
                    continue;
                }

                /** @var UploadedFile $file */
                $stored = $this->storeDocument($file, $ownerType, $ownerId, $prefix);
                $newPaths[] = $stored['relative_path'];
                if (! empty($existing[$field])) {
                    $oldPaths[] = (string) $existing[$field];
                }
                $payload[$field] = $stored['relative_path'];
            }

            $this->db->transBegin();
            $transactionStarted = true;
            $savedId = $id;

            if ($id > 0) {
                if (! $model->update($id, $payload)) {
                    throw new \RuntimeException('Data ' . $this->categoryLabel($category) . ' gagal diperbarui.');
                }
            } else {
                $savedId = (int) $model->insert($payload, true);
                if ($savedId <= 0) {
                    throw new \RuntimeException('Data ' . $this->categoryLabel($category) . ' gagal disimpan.');
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $actorUserId,
                $id > 0 ? 'UPDATE' : 'CREATE',
                'Personalia ' . ucfirst($category),
                sprintf('%s %s ID %d milik %s ID %d.', $id > 0 ? 'Memperbarui' : 'Menambahkan', $category, $savedId, $ownerType, $ownerId)
            );

            $this->db->transCommit();
            $this->deleteFiles($oldPaths);

            return [
                'success' => true,
                'message' => 'Data ' . $this->categoryLabel($category) . ($id > 0 ? ' berhasil diperbarui.' : ' berhasil ditambahkan.'),
                'id' => $savedId,
            ];
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->db->transRollback();
            }
            $this->deleteFiles($newPaths);
            return $this->fail('SAVE_FAILED', $e->getMessage());
        }
    }

    /**
     * @return array{relative_path:string,original_name:string,mime_type:string}
     */
    private function storeDocument(
        UploadedFile $file,
        string $ownerType,
        int $ownerId,
        string $prefix
    ): array {
        $relativeDir = self::STORAGE_ROOT . $ownerType . '/' . $ownerId;
        $destDir = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        $stored = $this->uploadService->processPersonaliaDocument($file, $destDir, $prefix);

        return [
            'relative_path' => $relativeDir . '/' . $stored['filename'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
        ];
    }

    private function access(int $actorUserId, string $ownerType, int $ownerId): array
    {
        if ($actorUserId <= 0 || $ownerId <= 0 || ! in_array($ownerType, ['guru', 'pegawai'], true)) {
            return $this->fail('VALIDATION', 'Konteks personalia tidak valid.');
        }

        $user = $this->db
            ->table('users')
            ->select('id, id_guru, id_pegawai, status_aktif')
            ->where('id', $actorUserId)
            ->get()
            ->getRowArray();

        if (! $user || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return $this->fail('UNAUTHENTICATED', 'Akun tidak aktif atau tidak ditemukan.');
        }

        $selfField = $ownerType === 'guru' ? 'id_guru' : 'id_pegawai';
        $isSelf = (int) ($user[$selfField] ?? 0) === $ownerId;
        $viewPermission = $ownerType === 'guru' ? 'master_guru.view' : 'master_pegawai.view';
        $managePermission = $ownerType === 'guru' ? 'master_guru.manage' : 'master_pegawai.manage';

        $canManage = $this->authService->hasPermission($managePermission, $actorUserId);
        $canViewMaster = $canManage || $this->authService->hasPermission($viewPermission, $actorUserId);
        $canViewSelf = $isSelf && (
            $ownerType === 'pegawai'
            || $this->authService->hasPermission('profile_guru.view', $actorUserId)
        );

        if (! $canViewMaster && ! $canViewSelf) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak melihat data personalia ini.');
        }

        $canEditSelf = $isSelf && (
            $ownerType === 'pegawai'
            || $this->authService->hasPermission('profile_guru.edit', $actorUserId)
        );

        $canEdit = $canManage || $canEditSelf;

        return [
            'success' => true,
            'is_self' => $isSelf,
            'can_edit' => $canEdit,
            // Kebijakan Phase 3.1 dikunci: self-service yang boleh edit juga
            // boleh menghapus record miliknya sendiri. Admin/Operator manage
            // tetap boleh menghapus. Tidak ada workflow approval.
            'can_delete' => $canEdit,
            // Dokumen mentah (KTP/KK, ijazah, SK, dst.) lebih sensitif dari
            // ringkasan Portofolio. Akses dibatasi ke pemilik sendiri dan
            // actor dengan permission manage Master.
            'can_view_documents' => $isSelf || $canManage,
        ];
    }

    private function owner(string $ownerType, int $ownerId): ?array
    {
        $table = $ownerType === 'guru' ? 'guru' : 'pegawai';
        $row = $this->db
            ->table($table)
            ->select('id, nik, nip, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, alamat, no_telepon, email, status_kepegawaian, nuptk, foto' . ($ownerType === 'pegawai' ? ', jabatan' : ''))
            ->where('id', $ownerId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        $row['jabatan_legacy'] = $ownerType === 'pegawai' ? ($row['jabatan'] ?? null) : null;
        unset($row['jabatan']);

        return $row;
    }

    private function listOwner(Model $model, string $ownerType, int $ownerId, string $order): array
    {
        $column = $ownerType === 'guru' ? 'id_guru' : 'id_pegawai';
        $builder = $model->where($column, $ownerId);

        foreach (explode(',', $order) as $item) {
            $parts = preg_split('/\s+/', trim($item));
            if (! empty($parts[0])) {
                $builder->orderBy($parts[0], strtoupper($parts[1] ?? 'ASC'));
            }
        }

        return $builder->findAll();
    }

    private function ownedRecord(Model $model, string $ownerType, int $ownerId, int $recordId): ?array
    {
        if ($recordId <= 0) {
            return null;
        }

        $column = $ownerType === 'guru' ? 'id_guru' : 'id_pegawai';
        $row = $model->where('id', $recordId)->where($column, $ownerId)->first();

        return is_array($row) ? $row : null;
    }

    private function recordOwner(array $record): ?array
    {
        $idGuru = (int) ($record['id_guru'] ?? 0);
        $idPegawai = (int) ($record['id_pegawai'] ?? 0);

        if ($idGuru > 0 && $idPegawai === 0) {
            return ['type' => 'guru', 'id' => $idGuru];
        }
        if ($idPegawai > 0 && $idGuru === 0) {
            return ['type' => 'pegawai', 'id' => $idPegawai];
        }

        return null;
    }

    private function ownerPayload(string $ownerType, int $ownerId): array
    {
        return [
            'id_guru' => $ownerType === 'guru' ? $ownerId : null,
            'id_pegawai' => $ownerType === 'pegawai' ? $ownerId : null,
        ];
    }

    private function modelFor(string $category): ?Model
    {
        return match ($category) {
            self::CATEGORY_PENDIDIKAN => $this->pendidikanModel,
            self::CATEGORY_PENUGASAN => $this->penugasanModel,
            self::CATEGORY_PANGKAT => $this->pangkatModel,
            self::CATEGORY_DOKUMEN => $this->dokumenModel,
            default => null,
        };
    }

    private function categories(): array
    {
        return [
            self::CATEGORY_PENDIDIKAN,
            self::CATEGORY_PENUGASAN,
            self::CATEGORY_PANGKAT,
            self::CATEGORY_DOKUMEN,
        ];
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_PENDIDIKAN => 'Riwayat Pendidikan',
            self::CATEGORY_PENUGASAN => 'Riwayat Penugasan',
            self::CATEGORY_PANGKAT => 'Riwayat Kepangkatan',
            self::CATEGORY_DOKUMEN => 'Dokumen Personalia',
            default => 'Personalia',
        };
    }

    private function recordFilePaths(string $category, array $record): array
    {
        $fields = match ($category) {
            self::CATEGORY_PENDIDIKAN => ['file_ijazah', 'file_transkrip'],
            self::CATEGORY_PENUGASAN => ['file_sk_penugasan'],
            self::CATEGORY_PANGKAT => ['file_sk_pangkat'],
            self::CATEGORY_DOKUMEN => ['file_path'],
            default => [],
        };

        $paths = [];
        foreach ($fields as $field) {
            if (! empty($record[$field])) {
                $paths[] = (string) $record[$field];
            }
        }

        return $paths;
    }

    private function deleteFiles(array $relativePaths): void
    {
        foreach (array_unique(array_filter($relativePaths)) as $relative) {
            $relative = self::normalizeStoredPath($relative);
            if ($relative === null) {
                continue;
            }

            $path = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public static function isAllowedDocumentType(string $value): bool
    {
        return in_array(trim($value), self::DOCUMENT_TYPES, true);
    }

    /**
     * Validasi path storage Personalia non-public.
     *
     * Bentuk yang diterima hanya:
     * uploads/personalia/{guru|pegawai}/{id}/{nama_file}.{pdf|png|jpg|jpeg}
     *
     * Bila expected owner diberikan, path juga wajib berada pada folder
     * identity yang sama. Method dibuat public-static agar policy keamanan
     * dapat diuji tanpa koneksi database.
     */
    public static function normalizeStoredPath(
        mixed $value,
        ?string $expectedOwnerType = null,
        ?int $expectedOwnerId = null
    ): ?string {
        $relative = str_replace('\\', '/', trim((string) $value));
        if ($relative === '' || str_contains($relative, "\0")) {
            return null;
        }

        if (str_starts_with($relative, '/') || preg_match('/^[A-Za-z]:\//', $relative)) {
            return null;
        }

        if (str_contains($relative, '..') || str_contains($relative, '//')) {
            return null;
        }

        $pattern = '#^uploads/personalia/(guru|pegawai)/([1-9][0-9]*)/'
            . '([A-Za-z0-9][A-Za-z0-9_.-]{0,254}\.(?:pdf|png|jpe?g))$#i';

        if (! preg_match($pattern, $relative, $matches)) {
            return null;
        }

        $ownerType = strtolower($matches[1]);
        $ownerId = (int) $matches[2];

        if ($expectedOwnerType !== null && strtolower($expectedOwnerType) !== $ownerType) {
            return null;
        }
        if ($expectedOwnerId !== null && $expectedOwnerId > 0 && $expectedOwnerId !== $ownerId) {
            return null;
        }

        return self::STORAGE_ROOT . $ownerType . '/' . $ownerId . '/' . $matches[3];
    }

    private function hasUpload(mixed $file): bool
    {
        return $file instanceof UploadedFile
            && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }

    private function text(mixed $value, int $max): string
    {
        return mb_substr(trim((string) $value), 0, $max);
    }

    private function nullableText(mixed $value, int $max): ?string
    {
        $text = $this->text($value, $max);
        return $text !== '' ? $text : null;
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year) ? $value : null;
    }

    private function nullableDate(mixed $value): string|false|null
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $this->date($value) ?? false;
    }

    private function downloadFilename(string $category, string $field, int $recordId, string $extension): string
    {
        $label = str_replace('file_', '', $field);
        $extension = preg_replace('/[^A-Za-z0-9]/', '', $extension) ?: 'bin';
        return sprintf('%s_%s_%d.%s', $category, $label, $recordId, $extension);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
