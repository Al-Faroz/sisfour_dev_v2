<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use RuntimeException;
use Throwable;

class BackupService
{
    private const TZ = 'Asia/Jakarta';
    private const FILE_PATTERN = '/^backup_\d{8}_\d{6}\.sql$/';
    private const INSERT_BATCH = 100;

    protected BaseConnection $db;
    protected string $backupDir;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->backupDir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'backups';
    }

    public function page(): array
    {
        $directory = $this->ensureBackupDirectory();

        if (!$directory['success']) {
            return $directory;
        }

        $rows = [];

        foreach (scandir($this->backupDir) ?: [] as $filename) {
            if (!$this->isAllowedFilename($filename)) {
                continue;
            }

            $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

            if (!is_file($path)) {
                continue;
            }

            $mtime = filemtime($path);
            $size = filesize($path);

            $rows[] = [
                'filename' => $filename,
                'size' => $size !== false ? (int) $size : 0,
                'created_at' => $mtime !== false
                    ? date('Y-m-d H:i:s', $mtime)
                    : null,
            ];
        }

        usort(
            $rows,
            static fn (array $a, array $b): int =>
                strcmp($b['filename'], $a['filename'])
        );

        return [
            'success' => true,
            'rows' => $rows,
            'directory' => $this->backupDir,
        ];
    }

    public function create(int $actorUserId): array
    {
        $directory = $this->ensureBackupDirectory();

        if (!$directory['success']) {
            return $directory;
        }

        $handle = null;
        $tempPath = null;
        $finalPath = null;
        $filename = null;
        $snapshotStarted = false;

        try {
            $filename = $this->newFilename();
            $finalPath = $this->backupDir
                . DIRECTORY_SEPARATOR
                . $filename;
            $tempPath = $finalPath . '.part';

            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            $handle = @fopen($tempPath, 'wb');

            if ($handle === false) {
                throw new RuntimeException(
                    'File backup sementara tidak dapat dibuat.'
                );
            }

            $this->beginConsistentSnapshot();
            $snapshotStarted = true;

            $this->writeHeader($handle);

            foreach ($this->baseTables() as $table) {
                $this->writeTable($handle, $table);
            }

            $this->writeFooter($handle);

            if (!fflush($handle)) {
                throw new RuntimeException(
                    'Gagal flush file backup.'
                );
            }

            fclose($handle);
            $handle = null;

            $this->endConsistentSnapshot();
            $snapshotStarted = false;

            clearstatcache(true, $tempPath);

            if (
                !is_file($tempPath)
                || !is_readable($tempPath)
                || filesize($tempPath) <= 0
            ) {
                throw new RuntimeException(
                    'File backup hasil proses tidak valid.'
                );
            }

            if (!@rename($tempPath, $finalPath)) {
                throw new RuntimeException(
                    'File backup gagal difinalisasi.'
                );
            }

            $this->log(
                $actorUserId,
                'CREATE',
                'Membuat backup database ' . $filename
            );

            return [
                'success' => true,
                'message' => 'Backup database berhasil dibuat.',
                'filename' => $filename,
                'size' => (int) (filesize($finalPath) ?: 0),
            ];
        } catch (Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            if ($snapshotStarted) {
                $this->cancelConsistentSnapshot();
            }

            if (is_string($tempPath) && $tempPath !== '') {
                @unlink($tempPath);
            }

            log_message(
                'error',
                'Backup database gagal: {message}',
                ['message' => $e->getMessage()]
            );

            return $this->fail(
                'BACKUP_FAILED',
                'Backup database gagal dibuat. Detail teknis dicatat pada log aplikasi.'
            );
        }
    }

    public function resolveDownload(
        int $actorUserId,
        string $filename
    ): array {
        $resolved = $this->resolveFile($filename);

        if (!$resolved['success']) {
            return $resolved;
        }

        $this->log(
            $actorUserId,
            'DOWNLOAD',
            'Mengunduh backup database ' . $filename
        );

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $resolved['path'],
        ];
    }

    public function delete(int $actorUserId, string $filename): array
    {
        $resolved = $this->resolveFile($filename);

        if (!$resolved['success']) {
            return $resolved;
        }

        if (!@unlink($resolved['path'])) {
            return $this->fail(
                'DELETE_FAILED',
                'File backup gagal dihapus.'
            );
        }

        $this->log(
            $actorUserId,
            'DELETE',
            'Menghapus backup database ' . $filename
        );

        return [
            'success' => true,
            'message' => 'File backup berhasil dihapus.',
        ];
    }

    private function ensureBackupDirectory(): array
    {
        if (
            !is_dir($this->backupDir)
            && !@mkdir($this->backupDir, 0775, true)
            && !is_dir($this->backupDir)
        ) {
            return $this->fail(
                'DIRECTORY_FAILED',
                'Folder backup tidak dapat dibuat.'
            );
        }

        if (!is_writable($this->backupDir)) {
            return $this->fail(
                'DIRECTORY_FAILED',
                'Folder backup tidak dapat ditulis.'
            );
        }

        return ['success' => true];
    }

    private function newFilename(): string
    {
        for ($attempt = 0; $attempt < 12; $attempt++) {
            $filename = 'backup_'
                . Time::now(self::TZ)->format('Ymd_His')
                . '.sql';

            $path = $this->backupDir
                . DIRECTORY_SEPARATOR
                . $filename;

            if (!is_file($path) && !is_file($path . '.part')) {
                return $filename;
            }

            usleep(200000);
        }

        throw new RuntimeException(
            'Nama backup unik tidak dapat dibuat.'
        );
    }

    private function beginConsistentSnapshot(): void
    {
        $this->db->query(
            'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'
        );
        $this->db->query(
            'START TRANSACTION WITH CONSISTENT SNAPSHOT'
        );
    }

    private function endConsistentSnapshot(): void
    {
        $this->db->query('COMMIT');
    }

    private function cancelConsistentSnapshot(): void
    {
        try {
            $this->db->query('ROLLBACK');
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Rollback snapshot backup gagal: {message}',
                ['message' => $e->getMessage()]
            );
        }
    }

    /**
     * @return string[]
     */
    private function baseTables(): array
    {
        $rows = $this->db
            ->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
            ->getResultArray();

        $tables = [];

        foreach ($rows as $row) {
            $values = array_values($row);

            if (isset($values[0]) && $values[0] !== '') {
                $tables[] = (string) $values[0];
            }
        }

        sort($tables, SORT_STRING);

        return $tables;
    }

    private function writeHeader($handle): void
    {
        $database = $this->db->getDatabase();

        $header = [
            '-- SisisFour Database Backup v0.5',
            '-- Generated: '
                . Time::now(self::TZ)->format('Y-m-d H:i:s T'),
            '-- Database: ' . $database,
            '',
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET UNIQUE_CHECKS=0;',
            'SET NAMES utf8mb4;',
            '',
        ];

        $this->write(
            $handle,
            implode("\n", $header) . "\n"
        );
    }

    private function writeFooter($handle): void
    {
        $footer = [
            '',
            'SET UNIQUE_CHECKS=1;',
            'SET FOREIGN_KEY_CHECKS=1;',
            '',
            '-- End of backup',
            '',
        ];

        $this->write(
            $handle,
            implode("\n", $footer)
        );
    }

    private function writeTable($handle, string $table): void
    {
        $quotedTable = $this->quoteIdentifier($table);

        $createRow = $this->db
            ->query('SHOW CREATE TABLE ' . $quotedTable)
            ->getRowArray();

        if (!$createRow) {
            throw new RuntimeException(
                'SHOW CREATE TABLE gagal untuk ' . $table
            );
        }

        $createValues = array_values($createRow);
        $createSql = $createValues[1] ?? null;

        if (!is_string($createSql) || trim($createSql) === '') {
            throw new RuntimeException(
                'DDL tabel tidak tersedia untuk ' . $table
            );
        }

        $this->write(
            $handle,
            "\n-- --------------------------------------------------------\n"
            . '-- Table: ' . $table . "\n"
            . "-- --------------------------------------------------------\n\n"
            . 'DROP TABLE IF EXISTS ' . $quotedTable . ";\n"
            . $createSql . ";\n\n"
        );

        $fields = $this->db->getFieldNames($table);

        if ($fields === []) {
            return;
        }

        $quotedFields = array_map(
            fn (string $field): string =>
                $this->quoteIdentifier($field),
            $fields
        );

        $offset = 0;

        while (true) {
            $rows = $this->db
                ->table($table)
                ->select('*')
                ->limit(self::INSERT_BATCH, $offset)
                ->get()
                ->getResultArray();

            if ($rows === []) {
                break;
            }

            $valuesSql = [];

            foreach ($rows as $row) {
                $values = [];

                foreach ($fields as $field) {
                    $values[] = $this->sqlValue(
                        $row[$field] ?? null
                    );
                }

                $valuesSql[] = '('
                    . implode(', ', $values)
                    . ')';
            }

            $insert = 'INSERT INTO '
                . $quotedTable
                . ' ('
                . implode(', ', $quotedFields)
                . ") VALUES\n"
                . implode(",\n", $valuesSql)
                . ";\n";

            $this->write($handle, $insert);

            $count = count($rows);
            $offset += $count;

            if ($count < self::INSERT_BATCH) {
                break;
            }
        }
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $this->db->escape((string) $value);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'
            . str_replace('`', '``', $identifier)
            . '`';
    }

    private function write($handle, string $content): void
    {
        $length = strlen($content);
        $offset = 0;

        while ($offset < $length) {
            $written = fwrite(
                $handle,
                substr($content, $offset)
            );

            if ($written === false || $written === 0) {
                throw new RuntimeException(
                    'Gagal menulis isi backup ke file.'
                );
            }

            $offset += $written;
        }
    }

    private function resolveFile(string $filename): array
    {
        if (!$this->isAllowedFilename($filename)) {
            return $this->fail(
                'INVALID_FILENAME',
                'Nama file backup tidak valid.'
            );
        }

        $directory = $this->ensureBackupDirectory();

        if (!$directory['success']) {
            return $directory;
        }

        $path = $this->backupDir
            . DIRECTORY_SEPARATOR
            . $filename;

        if (!is_file($path) || !is_readable($path)) {
            return $this->fail(
                'NOT_FOUND',
                'File backup tidak ditemukan.'
            );
        }

        $realDir = realpath($this->backupDir);
        $realPath = realpath($path);

        if (
            $realDir === false
            || $realPath === false
            || dirname($realPath) !== $realDir
        ) {
            return $this->fail(
                'INVALID_PATH',
                'Path file backup tidak valid.'
            );
        }

        return [
            'success' => true,
            'path' => $realPath,
        ];
    }

    private function isAllowedFilename(string $filename): bool
    {
        return preg_match(
            self::FILE_PATTERN,
            $filename
        ) === 1;
    }

    private function log(
        int $actorUserId,
        string $aksi,
        string $keterangan
    ): void {
        try {
            $this->db
                ->table('log_activity')
                ->insert([
                    'id_user' => $actorUserId,
                    'aksi' => $aksi,
                    'modul' => 'Backup',
                    'keterangan' => $keterangan,
                    'waktu' => Time::now(self::TZ)
                        ->format('Y-m-d H:i:s'),
                ]);
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Log activity Backup gagal ditulis: {message}',
                ['message' => $e->getMessage()]
            );
        }
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
