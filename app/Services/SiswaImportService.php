<?php

namespace App\Services;

use App\Models\SiswaModel;
use App\Models\UserModel;
use App\Models\UserRolesModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class SiswaImportService
{
    protected BaseConnection $db;
    protected SiswaModel $siswaModel;
    protected UserModel $userModel;
    protected UserRolesModel $userRolesModel;
    protected AuthService $authService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->siswaModel = new SiswaModel();
        $this->userModel = new UserModel();
        $this->userRolesModel = new UserRolesModel();
        $this->authService = new AuthService();
        $this->activityLog = new ActivityLogService();
    }

    public function import(UploadedFile $file, int $actorUserId): array
    {
        if ($this->authService->resolveScope('master_siswa.import_export', $actorUserId) !== 'SEMUA') {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk mengimpor data siswa.');
        }

        if (!$file->isValid()) {
            return $this->fail('INVALID_FILE', 'File import tidak valid.');
        }

        if (!in_array(strtolower((string) $file->getClientExtension()), ['xlsx', 'xls'], true)) {
            return $this->fail('INVALID_FILE', 'File import harus berformat XLSX atau XLS.');
        }

        $tahun = $this->getActiveYear();
        if ($tahun === null) {
            return $this->fail('NO_ACTIVE_YEAR', 'Tahun ajaran aktif belum tersedia.');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getSheetByName('DATA_SISWA') ?? $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return $this->fail('INVALID_FILE', 'File Excel tidak dapat dibaca.');
        }

        if (count($rows) < 2) {
            return $this->fail('EMPTY_FILE', 'File import tidak memiliki data siswa.');
        }

        $headerMap = $this->buildHeaderMap($rows[0]);
        foreach (['NIK', 'NISN', 'NAMA LENGKAP', 'JENIS KELAMIN', 'NAMA KELAS'] as $header) {
            if (!array_key_exists($header, $headerMap)) {
                return $this->fail(
                    'INVALID_TEMPLATE',
                    sprintf('Kolom "%s" tidak ditemukan. Gunakan template terbaru.', $header)
                );
            }
        }

        $kelasMap = $this->getActiveClassMap((int) $tahun['id']);
        if ($kelasMap === []) {
            return $this->fail('NO_CLASS', 'Belum ada kelas pada tahun ajaran aktif.');
        }

        $prepared = [];
        $errors = [];
        $seenNik = [];
        $seenNisn = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $excelRow = $offset + 2;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $record = $this->normalizeRow($row, $headerMap);
            $rowErrors = $this->validateRow($record, $kelasMap, $seenNik, $seenNisn);

            if ($rowErrors !== []) {
                foreach ($rowErrors as $error) {
                    $errors[] = sprintf('Baris %d: %s', $excelRow, $error);
                }
                continue;
            }

            $seenNik[$record['nik']] = true;
            $seenNisn[$record['nisn']] = true;
            $classKey = $this->classKey($record['nama_kelas']);
            $record['id_kelas'] = (int) $kelasMap[$classKey]['id'];
            $record['nama_kelas_resmi'] = $kelasMap[$classKey]['nama_kelas'];
            $prepared[] = $record;
        }

        if ($prepared === [] && $errors === []) {
            return $this->fail('EMPTY_FILE', 'Tidak ada baris siswa yang dapat diproses.');
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'code' => 'VALIDATION',
                'message' => 'Import dibatalkan. Perbaiki data pada file Excel terlebih dahulu.',
                'errors' => $errors,
                'total_error' => count($errors),
            ];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $this->db->transBegin();

        try {
            foreach ($prepared as $record) {
                $idSiswa = $this->siswaModel->insert([
                    'nik' => $record['nik'],
                    'nisn' => $record['nisn'],
                    'nama' => $record['nama'],
                    'jenis_kelamin' => $record['jenis_kelamin'],
                    'tempat_lahir' => $record['tempat_lahir'],
                    'tanggal_lahir' => $record['tanggal_lahir'],
                    'alamat' => $record['alamat'],
                    'status_aktif' => 'Aktif',
                    'tanggal_mutasi' => null,
                    'keterangan_mutasi' => null,
                ], true);

                if ($idSiswa === false) {
                    throw new \RuntimeException(
                        implode(' ', $this->siswaModel->errors())
                        ?: sprintf('Siswa %s gagal disimpan.', $record['nama'])
                    );
                }

                $idSiswa = (int) $idSiswa;
                $idUser = $this->userModel->insert([
                    'username' => $record['nisn'],
                    'password' => password_hash($record['nisn'], PASSWORD_DEFAULT),
                    'role' => 'siswa',
                    'id_guru' => null,
                    'id_pegawai' => null,
                    'id_siswa' => $idSiswa,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException(
                        implode(' ', $this->userModel->errors())
                        ?: sprintf('Akun siswa %s gagal dibuat.', $record['nama'])
                    );
                }

                if ($this->userRolesModel->insert([
                    'id_user' => (int) $idUser,
                    'role' => 'siswa',
                ]) === false) {
                    throw new \RuntimeException(sprintf('Role akun siswa %s gagal dibuat.', $record['nama']));
                }

                if (!$this->db->table('anggota_kelas')->insert([
                    'id_siswa' => $idSiswa,
                    'id_kelas' => $record['id_kelas'],
                    'id_tahun' => (int) $tahun['id'],
                ])) {
                    throw new \RuntimeException(sprintf('Penempatan kelas siswa %s gagal.', $record['nama']));
                }

                if (!$this->db->table('riwayat_siswa')->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => (int) $tahun['id'],
                    'id_kelas' => $record['id_kelas'],
                    'status' => 'Aktif',
                    'tanggal_mulai' => $today,
                    'tanggal_selesai' => null,
                    'keterangan' => sprintf(
                        'Penempatan awal melalui Import Siswa ke kelas %s.',
                        $record['nama_kelas_resmi']
                    ),
                    'created_at' => $now,
                ])) {
                    throw new \RuntimeException(sprintf('Riwayat kelas siswa %s gagal dibuat.', $record['nama']));
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi import siswa gagal.');
            }

            $this->activityLog->write(
                $actorUserId,
                'IMPORT',
                'Master Siswa',
                sprintf(
                    'Mengimpor %d siswa dan langsung menempatkan ke kelas pada tahun ajaran %s - %s.',
                    count($prepared),
                    $tahun['nama_tahun'],
                    $tahun['semester']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf('%d siswa berhasil diimpor dan ditempatkan ke kelas.', count($prepared)),
                'total_import' => count($prepared),
                'id_tahun' => (int) $tahun['id'],
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('IMPORT_FAILED', $e->getMessage());
        }
    }

    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $value) {
            $header = strtoupper(trim((string) $value));
            if ($header !== '') {
                $map[$header] = (int) $index;
            }
        }
        return $map;
    }

    private function normalizeRow(array $row, array $headerMap): array
    {
        $value = static function (string $header) use ($row, $headerMap): string {
            if (!isset($headerMap[$header])) {
                return '';
            }
            return trim((string) ($row[$headerMap[$header]] ?? ''));
        };

        return [
            'nik' => preg_replace('/\D+/', '', $value('NIK')) ?? '',
            'nisn' => trim($value('NISN')),
            'nama' => trim($value('NAMA LENGKAP')),
            'jenis_kelamin' => strtoupper(trim($value('JENIS KELAMIN'))),
            'tempat_lahir' => trim($value('TEMPAT LAHIR')),
            'tanggal_lahir' => $this->normalizeDate($value('TANGGAL LAHIR')),
            'alamat' => trim($value('ALAMAT')),
            'nama_kelas' => trim($value('NAMA KELAS')),
        ];
    }

    private function validateRow(
        array $record,
        array $kelasMap,
        array $seenNik,
        array $seenNisn
    ): array {
        $errors = [];

        if (!preg_match('/^\d{16}$/', $record['nik'])) {
            $errors[] = 'NIK wajib tepat 16 digit.';
        }
        if ($record['nisn'] === '' || mb_strlen($record['nisn']) > 20) {
            $errors[] = 'NISN wajib diisi dan maksimal 20 karakter.';
        }
        if ($record['nama'] === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        }
        if (!in_array($record['jenis_kelamin'], ['L', 'P'], true)) {
            $errors[] = 'Jenis kelamin wajib L atau P.';
        }
        if ($record['nama_kelas'] === '') {
            $errors[] = 'Nama kelas wajib diisi.';
        } elseif (!isset($kelasMap[$this->classKey($record['nama_kelas'])])) {
            $errors[] = sprintf(
                'Kelas "%s" tidak ditemukan pada tahun ajaran aktif.',
                $record['nama_kelas']
            );
        }
        if (isset($seenNik[$record['nik']])) {
            $errors[] = 'NIK duplikat di dalam file import.';
        }
        if (isset($seenNisn[$record['nisn']])) {
            $errors[] = 'NISN duplikat di dalam file import.';
        }
        if (
            $record['nik'] !== ''
            && $this->db->table('siswa')->where('nik', $record['nik'])->countAllResults() > 0
        ) {
            $errors[] = 'NIK sudah terdaftar di database.';
        }
        if (
            $record['nisn'] !== ''
            && $this->db->table('siswa')->where('nisn', $record['nisn'])->countAllResults() > 0
        ) {
            $errors[] = 'NISN sudah terdaftar di database.';
        }
        if (
            $record['nisn'] !== ''
            && $this->db->table('users')->where('username', $record['nisn'])->countAllResults() > 0
        ) {
            $errors[] = 'NISN sudah digunakan sebagai username.';
        }
        if ($record['tanggal_lahir'] === false) {
            $errors[] = 'Tanggal lahir tidak valid. Gunakan format YYYY-MM-DD.';
        }

        return $errors;
    }

    private function getActiveYear(): ?array
    {
        $row = $this->db->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function getActiveClassMap(int $idTahun): array
    {
        $rows = $this->db->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null)
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$this->classKey($row['nama_kelas'])] = $row;
        }

        return $map;
    }

    private function classKey(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }

    private function normalizeDate(string $value): string|false|null
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return false;
        }

        return $value;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
