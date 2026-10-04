<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class DokumenSiswaImportService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_ROWS = 10000;
    private const PREVIEW_TTL = 7200;
    private const MANAGER_ROLES = ['admin', 'operator'];

    protected AuthService $authService;
    protected PeriodContextService $periodContext;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->periodContext = new PeriodContextService();
    }

    public function templateRows(
        int $userId,
        string $tingkat = '',
        int $idKelas = 0
    ): array {
        $auth = $this->requireManage($userId);
        if (! $auth['success']) {
            return $auth;
        }

        $active = $this->periodContext->active();
        $activeId = (int) ($active['id'] ?? 0);
        $tingkat = trim($tingkat);

        if (($tingkat !== '' || $idKelas > 0)
            && $activeId <= 0
        ) {
            return $this->fail(
                'NO_ACTIVE_YEAR',
                'Filter Tingkat/Kelas membutuhkan Tahun Ajaran aktif.'
            );
        }

        if ($tingkat !== ''
            && ! StudentDocumentPolicyService
                ::validLevel($tingkat)
        ) {
            return $this->fail(
                'INVALID_TARGET',
                'Tingkat template hanya 7, 8, atau 9.'
            );
        }

        if ($idKelas > 0) {
            $validClass = db_connect()
                ->table('kelas')
                ->where('id', $idKelas)
                ->where('id_tahun', $activeId)
                ->where('deleted_at', null)
                ->countAllResults() > 0;

            if (! $validClass) {
                return $this->fail(
                    'INVALID_TARGET',
                    'Kelas template tidak valid pada periode aktif.'
                );
            }
        }

        $builder = db_connect()
            ->table('siswa s')
            ->select([
                's.nisn',
                's.nama AS nama_siswa',
                's.status_aktif',
            ])
            ->where('s.deleted_at', null);

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

            if ($tingkat !== '') {
                $builder->where(
                    'k.tingkat',
                    $tingkat
                );
            }

            if ($idKelas > 0) {
                $builder->where(
                    'k.id',
                    $idKelas
                );
            }
        } else {
            $builder
                ->select(
                    'NULL AS nama_kelas',
                    false
                )
                ->select(
                    'NULL AS tingkat',
                    false
                );
        }

        return [
            'success' => true,
            'rows' => $builder
                ->orderBy('s.nama', 'ASC')
                ->get()
                ->getResultArray(),
            'roster_period' => $active,
        ];
    }

    public function preview(
        int $userId,
        UploadedFile $file,
        array $context
    ): array {
        $auth = $this->requireManage($userId);
        if (! $auth['success']) {
            return $auth;
        }

        $judul = StudentDocumentPolicyService
            ::normalizeTitle(
                (string) ($context['judul'] ?? '')
            );
        $format = StudentDocumentPolicyService
            ::normalizeFormat(
                (string) ($context['format_file'] ?? '')
            );

        if ($judul === ''
            || mb_strlen($judul) > 200
        ) {
            return $this->fail(
                'VALIDATION',
                'Judul Dokumen wajib diisi maksimal 200 karakter.'
            );
        }

        if ($format === null) {
            return $this->fail(
                'VALIDATION',
                'Format hanya PDF atau IMAGE.'
            );
        }

        if (! $file->isValid()
            || strtolower(
                (string) $file->getClientExtension()
            ) !== 'xlsx'
        ) {
            return $this->fail(
                'INVALID_FILE',
                'Bulk import wajib menggunakan file XLSX.'
            );
        }

        try {
            $spreadsheet = IOFactory::load(
                $file->getTempName()
            );
            $sheet = $spreadsheet
                ->getSheetByName('DATA_DOKUMEN')
                ?? $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(
                null,
                true,
                true,
                false
            );
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $e) {
            return $this->fail(
                'INVALID_FILE',
                'File XLSX tidak dapat dibaca.'
            );
        }

        if (count($rows) < 2) {
            return $this->fail(
                'EMPTY_FILE',
                'File tidak memiliki data Dokumen.'
            );
        }

        if ((count($rows) - 1)
            > self::MAX_ROWS
        ) {
            return $this->fail(
                'IMPORT_TOO_LARGE',
                'Maksimal 10.000 baris per bulk import.'
            );
        }

        $headers = $this->headerMap(
            $rows[0]
        );

        foreach (
            ['NISN', 'LINK GOOGLE DRIVE']
            as $required
        ) {
            if (! array_key_exists(
                $required,
                $headers
            )) {
                return $this->fail(
                    'INVALID_TEMPLATE',
                    "Kolom {$required} tidak ditemukan. "
                    . 'Gunakan template terbaru.'
                );
            }
        }

        $active = $this->periodContext->active();
        $activeId = (int) ($active['id'] ?? 0);
        $prepared = [];
        $errors = [];
        $warnings = [];
        $seen = [];
        $processedRows = 0;
        $errorRows = 0;

        foreach (
            array_slice($rows, 1)
            as $offset => $row
        ) {
            $excelRow = $offset + 2;
            $nisn = trim(
                (string) (
                    $row[$headers['NISN']] ?? ''
                )
            );
            $rawLink = trim(
                (string) (
                    $row[
                        $headers['LINK GOOGLE DRIVE']
                    ] ?? ''
                )
            );
            $link = StudentDocumentPolicyService
                ::normalizeGoogleDriveUrl(
                    $rawLink
                );
            $nameInput = isset(
                $headers['NAMA SISWA']
            )
                ? trim(
                    (string) (
                        $row[
                            $headers['NAMA SISWA']
                        ] ?? ''
                    )
                )
                : '';
            $classInput = isset(
                $headers['KELAS']
            )
                ? trim(
                    (string) (
                        $row[
                            $headers['KELAS']
                        ] ?? ''
                    )
                )
                : '';

            if ($nisn === ''
                && $rawLink === ''
                && $nameInput === ''
                && $classInput === ''
            ) {
                continue;
            }

            $processedRows++;
            $rowErrors = [];

            if ($nisn === '') {
                $rowErrors[] = 'NISN wajib diisi.';
            } elseif (isset($seen[$nisn])) {
                $rowErrors[] =
                    'NISN duplikat di file import.';
            } else {
                $seen[$nisn] = true;
            }

            if ($link === null) {
                $rowErrors[] =
                    'Link Google Drive tidak valid.';
            }

            $resolved = $nisn !== ''
                ? $this->resolveStudent(
                    $nisn,
                    $activeId
                )
                : null;

            if ($nisn !== ''
                && $resolved === null
            ) {
                $rowErrors[] =
                    'NISN tidak ditemukan.';
            }

            if ($resolved !== null
                && $this->duplicatePublished(
                    (int) $resolved['id_siswa'],
                    $judul
                )
            ) {
                $rowErrors[] =
                    'Dokumen Published dengan judul yang sama '
                    . 'sudah tersedia untuk siswa.';
            }

            if ($resolved !== null
                && $nameInput !== ''
                && mb_strtolower($nameInput)
                    !== mb_strtolower(
                        trim(
                            (string) $resolved['nama_siswa']
                        )
                    )
            ) {
                $warnings[] =
                    "Baris {$excelRow}: Nama pada template "
                    . 'berbeda dengan database; database tetap authoritative.';
            }

            if ($resolved !== null
                && $classInput !== ''
                && mb_strtolower($classInput)
                    !== mb_strtolower(
                        trim(
                            (string) (
                                $resolved['nama_kelas'] ?? ''
                            )
                        )
                    )
            ) {
                $warnings[] =
                    "Baris {$excelRow}: Kelas pada template "
                    . 'berbeda dengan kelas current; '
                    . 'database tetap authoritative.';
            }

            if ($rowErrors !== []) {
                $errorRows++;

                foreach (
                    $rowErrors as $message
                ) {
                    $errors[] =
                        "Baris {$excelRow}: {$message}";
                }

                continue;
            }

            $prepared[] = [
                'excel_row' => $excelRow,
                'id_siswa' => (int) $resolved['id_siswa'],
                'nisn' => (string) $resolved['nisn'],
                'nama_siswa' => (string) $resolved['nama_siswa'],
                'nama_kelas' => (string) (
                    $resolved['nama_kelas'] ?? ''
                ),
                'link_gdrive' => $link,
            ];
        }

        if ($processedRows === 0) {
            return $this->fail(
                'EMPTY_FILE',
                'Tidak ada baris yang dapat diproses.'
            );
        }

        $token = null;

        if ($errors === []) {
            $token = bin2hex(
                random_bytes(32)
            );

            $this->writePreview(
                $token,
                [
                    'user_id' => $userId,
                    'created_at' => time(),
                    'judul' => $judul,
                    'format_file' => $format,
                    'source_filename' => basename(
                        (string) $file
                            ->getClientName()
                    ),
                    'rows' => $prepared,
                ]
            );
        }

        return [
            'success' => true,
            'message' => $errors === []
                ? 'Preview valid. Siap commit.'
                : 'Preview menemukan error. '
                    . 'Perbaiki XLSX lalu upload ulang.',
            'can_commit' => $errors === [],
            'token' => $token,
            'total_row' => $processedRows,
            'total_valid' => count($prepared),
            'total_error' => $errorRows,
            'rows' => $prepared,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function commit(
        int $userId,
        string $token
    ): array {
        $auth = $this->requireManage($userId);
        if (! $auth['success']) {
            return $auth;
        }

        $payload = $this->readPreview(
            $token
        );

        if ($payload === null
            || (int) ($payload['user_id'] ?? 0)
                !== $userId
        ) {
            return $this->fail(
                'INVALID_PREVIEW',
                'Preview import tidak valid atau sudah kedaluwarsa.'
            );
        }

        $judul = (string) $payload['judul'];
        $format = (string) $payload['format_file'];
        $rows = is_array(
            $payload['rows'] ?? null
        )
            ? $payload['rows']
            : [];

        if ($rows === []) {
            return $this->fail(
                'INVALID_PREVIEW',
                'Context preview tidak lagi valid.'
            );
        }

        $active = $this->periodContext->active();
        $activeId = (int) ($active['id'] ?? 0);

        foreach ($rows as $row) {
            $resolved = $this->resolveStudent(
                (string) $row['nisn'],
                $activeId
            );
            $link = StudentDocumentPolicyService
                ::normalizeGoogleDriveUrl(
                    (string) $row['link_gdrive']
                );

            if ($resolved === null
                || $link === null
                || (int) $resolved['id_siswa']
                    !== (int) $row['id_siswa']
                || $this->duplicatePublished(
                    (int) $row['id_siswa'],
                    $judul
                )
            ) {
                return $this->fail(
                    'IMPORT_CHANGED',
                    'Data siswa/dokumen berubah sejak preview. '
                    . 'Jalankan Preview ulang.'
                );
            }
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $now = Time::now(self::TZ)
                ->format('Y-m-d H:i:s');

            $db->table(
                'dokumen_siswa_import_batch'
            )->insert([
                'judul' => $judul,
                'format_file' => $format,
                'source_filename' => (string) (
                    $payload['source_filename'] ?? ''
                ),
                'total_row' => count($rows),
                'total_valid' => count($rows),
                'total_error' => 0,
                'status' => 'COMMITTED',
                'created_by' => $userId,
                'created_at' => $now,
                'committed_at' => $now,
            ]);

            $batchId = (int) $db->insertID();

            foreach ($rows as $row) {
                $db->table('dokumen_siswa')
                    ->insert([
                        'target_type' => 'INDIVIDU',
                        'id_siswa' => (int) $row['id_siswa'],
                        'tingkat' => null,
                        'judul' => $judul,
                        'format_file' => $format,
                        'link_gdrive' => (string) $row['link_gdrive'],
                        'status' => 'PUBLISHED',
                        'id_import_batch' => $batchId,
                        'created_by' => $userId,
                        'created_at' => $now,
                        'updated_by' => $userId,
                        'updated_at' => $now,
                    ]);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi bulk import gagal.'
                );
            }

            $db->transCommit();
            $this->deletePreview($token);

            $this->log(
                $userId,
                'IMPORT',
                "Bulk import Dokumen Siswa batch #{$batchId}: "
                . count($rows)
                . ' siswa'
            );

            return [
                'success' => true,
                'message' => count($rows)
                    . ' Dokumen Individu berhasil diimport.',
                'id_import_batch' => $batchId,
                'total_import' => count($rows),
            ];
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->fail(
                'IMPORT_FAILED',
                'Bulk import Dokumen Siswa gagal disimpan.'
            );
        }
    }

    public function rollback(
        int $userId,
        int $batchId
    ): array {
        $auth = $this->requireManage($userId);
        if (! $auth['success']) {
            return $auth;
        }

        $batch = db_connect()
            ->table(
                'dokumen_siswa_import_batch'
            )
            ->where('id', $batchId)
            ->where('status', 'COMMITTED')
            ->get()
            ->getRowArray();

        if (! $batch) {
            return $this->fail(
                'NOT_FOUND',
                'Batch import aktif tidak ditemukan.'
            );
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $now = Time::now(self::TZ)
                ->format('Y-m-d H:i:s');

            $db->table('dokumen_siswa')
                ->where(
                    'id_import_batch',
                    $batchId
                )
                ->update([
                    'status' => 'ARCHIVED',
                    'updated_by' => $userId,
                    'updated_at' => $now,
                ]);

            $db->table(
                'dokumen_siswa_import_batch'
            )
                ->where('id', $batchId)
                ->update([
                    'status' => 'ROLLED_BACK',
                    'rolled_back_at' => $now,
                ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException(
                    'Rollback gagal.'
                );
            }

            $db->transCommit();

            $this->log(
                $userId,
                'ROLLBACK',
                "Rollback metadata Dokumen Siswa batch #{$batchId}"
            );

            return [
                'success' => true,
                'message' =>
                    'Batch di-rollback. File Google Drive '
                    . 'tidak diubah/dihapus.',
            ];
        } catch (Throwable $e) {
            $db->transRollback();

            return $this->fail(
                'ROLLBACK_FAILED',
                'Rollback batch gagal.'
            );
        }
    }

    private function resolveStudent(
        string $nisn,
        int $activePeriodId
    ): ?array {
        $builder = db_connect()
            ->table('siswa s')
            ->select([
                's.id AS id_siswa',
                's.nisn',
                's.nama AS nama_siswa',
            ])
            ->where('s.nisn', trim($nisn))
            ->where('s.deleted_at', null);

        if ($activePeriodId > 0) {
            $builder
                ->select(
                    'k.nama_kelas'
                )
                ->join(
                    'anggota_kelas ak',
                    'ak.id_siswa = s.id'
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
                'NULL AS nama_kelas',
                false
            );
        }

        $row = $builder
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function duplicatePublished(
        int $idSiswa,
        string $judul
    ): bool {
        return db_connect()
            ->table('dokumen_siswa')
            ->where(
                'id_siswa',
                $idSiswa
            )
            ->where(
                'target_type',
                'INDIVIDU'
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
            )
            ->countAllResults() > 0;
    }

    private function headerMap(
        array $row
    ): array {
        $map = [];

        foreach ($row as $i => $value) {
            $key = strtoupper(
                trim((string) $value)
            );

            if ($key !== '') {
                $map[$key] = (int) $i;
            }
        }

        return $map;
    }

    private function previewDir(): string
    {
        return WRITEPATH
            . 'cache/dokumen_siswa_import';
    }

    private function writePreview(
        string $token,
        array $payload
    ): void {
        $dir = $this->previewDir();

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        foreach (
            glob(
                $dir
                . DIRECTORY_SEPARATOR
                . '*.json'
            ) ?: []
            as $path
        ) {
            if (is_file($path)
                && filemtime($path)
                    < time() - self::PREVIEW_TTL
            ) {
                @unlink($path);
            }
        }

        file_put_contents(
            $dir
                . DIRECTORY_SEPARATOR
                . $token
                . '.json',
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            ),
            LOCK_EX
        );
    }

    private function readPreview(
        string $token
    ): ?array {
        if (! preg_match(
            '/^[a-f0-9]{64}$/',
            $token
        )) {
            return null;
        }

        $path = $this->previewDir()
            . DIRECTORY_SEPARATOR
            . $token
            . '.json';

        if (! is_file($path)
            || filemtime($path)
                < time() - self::PREVIEW_TTL
        ) {
            if (is_file($path)) {
                @unlink($path);
            }

            return null;
        }

        $decoded = json_decode(
            (string) file_get_contents($path),
            true
        );

        return is_array($decoded)
            ? $decoded
            : null;
    }

    private function deletePreview(
        string $token
    ): void {
        if (! preg_match(
            '/^[a-f0-9]{64}$/',
            $token
        )) {
            return;
        }

        $path = $this->previewDir()
            . DIRECTORY_SEPARATOR
            . $token
            . '.json';

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function requireManage(
        int $userId
    ): array {
        $roles = $this->authService
            ->getUserRoles($userId);

        return array_intersect(
            self::MANAGER_ROLES,
            $roles
        ) !== []
            && $this->authService
                ->resolveScope(
                    'dokumen_siswa.manage',
                    $userId
                ) === 'SEMUA'
            ? ['success' => true]
            : $this->fail(
                'FORBIDDEN',
                'Bulk Dokumen Siswa hanya untuk Admin/Operator.'
            );
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
