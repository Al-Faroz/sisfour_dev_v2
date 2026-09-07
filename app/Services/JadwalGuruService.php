<?php

namespace App\Services;

use App\Models\JadwalGuruModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * JadwalGuruService
 *
 * Business logic Master Jadwal Guru.
 *
 * Acuan docs/04_MASTER_DATA:
 * - input jadwal hanya melalui import Excel;
 * - template wajib:
 *   nip_guru, nama_kelas, kode_mapel, hari,
 *   jam_mulai, jam_selesai, sesi;
 * - guru tidak boleh overlap pada hari yang sama;
 * - kelas tidak boleh overlap (tidak ada team teaching);
 * - import stop-on-error + atomic transaction;
 * - jadwal lama tidak dihapus, tetapi dibuat Nonaktif;
 * - jadwal baru menjadi Aktif;
 * - Admin/Operator dapat import/export;
 * - Guru hanya melihat jadwal dirinya sesuai scope.
 */
class JadwalGuruService
{
    protected JadwalGuruModel $jadwalModel;
    protected AuthService $authService;
    protected $db;

    private const HARI_VALID = [
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu',
    ];

    private const SESI_VALID = [
        'Sesi Awal',
        'Sesi Akhir',
        'Non Sesi',
    ];

    public function __construct()
    {
        $this->jadwalModel = new JadwalGuruModel();
        $this->authService = new AuthService();
        $this->db = Database::connect();
    }

    public function getList(
        array $filter,
        int $userId
    ): array {
        $builder = $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.id, jg.id_guru, jg.id_kelas, jg.id_mapel, jg.id_tahun, ' .
                'jg.hari, jg.jam_mulai, jg.jam_selesai, jg.sesi, jg.status_jadwal, ' .
                'g.nip, g.nama AS nama_guru, ' .
                'k.nama_kelas, k.tingkat, k.rombel, ' .
                'mp.nama_mapel, mp.kode_mapel, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif'
            )
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->join('tahun_ajaran ta', 'ta.id = jg.id_tahun');

        if (!$this->applyViewScope($builder, $userId)) {
            return [];
        }

        $idGuru = (int) ($filter['id_guru'] ?? 0);
        if ($idGuru > 0) {
            $builder->where('jg.id_guru', $idGuru);
        }

        $idKelas = (int) ($filter['id_kelas'] ?? 0);
        if ($idKelas > 0) {
            $builder->where('jg.id_kelas', $idKelas);
        }

        $idTahun = (int) ($filter['id_tahun'] ?? 0);
        if ($idTahun > 0) {
            $builder->where('jg.id_tahun', $idTahun);
        }

        $hari = trim((string) ($filter['hari'] ?? ''));
        if (in_array($hari, self::HARI_VALID, true)) {
            $builder->where('jg.hari', $hari);
        }

        $status = trim((string) ($filter['status_jadwal'] ?? ''));
        if (in_array($status, ['Aktif', 'Nonaktif'], true)) {
            $builder->where('jg.status_jadwal', $status);
        }

        return $builder
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(ta.semester,'Ganjil','Genap')",
                '',
                false
            )
            ->orderBy(
                "FIELD(jg.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')",
                '',
                false
            )
            ->orderBy('jg.jam_mulai', 'ASC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Opsi filter. Kelas dapat dipersempit berdasarkan guru terpilih.
     */
    public function getOptions(
        int $userId,
        ?int $filterGuru = null
    ): array {
        $scope = $this->resolveViewScope($userId);
        $idGuruUser = $this->getIdGuruUser($userId);

        $guruBuilder = $this->db
            ->table('guru g')
            ->select('g.id, g.nip, g.nama')
            ->where('g.deleted_at', null);

        if ($scope === 'DIRI_SENDIRI') {
            if ($idGuruUser <= 0) {
                $guru = [];
            } else {
                $guru = $guruBuilder
                    ->where('g.id', $idGuruUser)
                    ->orderBy('g.nama', 'ASC')
                    ->get()
                    ->getResultArray();
            }
        } else {
            $guru = $guruBuilder
                ->orderBy('g.nama', 'ASC')
                ->get()
                ->getResultArray();
        }

        $kelasBuilder = $this->db
            ->table('kelas k')
            ->distinct()
            ->select(
                'k.id, k.nama_kelas, k.tingkat, k.rombel, k.id_tahun, ' .
                'ta.nama_tahun, ta.semester'
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null);

        $idGuruKelas = $filterGuru ?: null;

        if ($scope === 'DIRI_SENDIRI') {
            $idGuruKelas = $idGuruUser > 0
                ? $idGuruUser
                : -1;
        }

        if ($idGuruKelas !== null) {
            $kelasBuilder
                ->join(
                    'jadwal_guru jg',
                    'jg.id_kelas = k.id AND jg.id_tahun = k.id_tahun'
                )
                ->where('jg.id_guru', $idGuruKelas);
        }

        $kelas = $kelasBuilder
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null)
            ->orderBy('nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(semester,'Ganjil','Genap')",
                '',
                false
            )
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'message' => 'Opsi jadwal berhasil dimuat.',
            'guru' => $guru,
            'kelas' => $kelas,
            'tahun' => $tahun,
            'scope' => $scope,
        ];
    }

    public function canManage(int $userId): bool
    {
        return $this->authService->resolveScope(
            'jadwal_guru.manage',
            $userId
        ) === 'SEMUA';
    }

    /**
     * Import Excel dengan replacement semantics:
     * - hanya tahun ajaran yang sedang aktif boleh diimport;
     * - seluruh jadwal Aktif lama dinonaktifkan dalam transaksi;
     * - data import baru menjadi satu-satunya set jadwal Aktif.
     */
    public function importJadwal(
        UploadedFile $file,
        int $idTahun
    ): array {
        if (!$file->isValid()) {
            return [
                'success' => false,
                'message' => 'File import tidak valid.',
            ];
        }

        $extension = strtolower(
            (string) $file->getClientExtension()
        );

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return [
                'success' => false,
                'message' => 'File import harus berformat XLSX atau XLS.',
            ];
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak ditemukan.',
            ];
        }

        if ((int) $tahun['status_aktif'] !== 1) {
            return [
                'success' => false,
                'message' => 'Import jadwal hanya dapat dilakukan ke tahun ajaran/semester yang sedang Aktif.',
            ];
        }

        try {
            $spreadsheet = IOFactory::load(
                $file->getTempName()
            );

            $rows = $spreadsheet
                ->getActiveSheet()
                ->toArray(
                    null,
                    true,
                    true,
                    false
                );
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'File Excel tidak dapat dibaca.',
            ];
        }

        if (count($rows) < 2) {
            return [
                'success' => false,
                'message' => 'File import tidak memiliki data jadwal.',
            ];
        }

        $expected = [
            'NIP_GURU',
            'NAMA_KELAS',
            'KODE_MAPEL',
            'HARI',
            'JAM_MULAI',
            'JAM_SELESAI',
            'SESI',
        ];

        $header = array_map(
            static fn ($value): string =>
                strtoupper(trim((string) $value)),
            array_slice($rows[0], 0, 7)
        );

        if ($header !== $expected) {
            return [
                'success' => false,
                'message' => 'Header template tidak sesuai. Gunakan template resmi Master Jadwal Guru.',
            ];
        }

        $prepared = [];
        $sourceRows = [];

        for (
            $i = 1,
            $count = count($rows);
            $i < $count;
            $i++
        ) {
            $excelRow = $i + 1;

            $nip = trim((string) ($rows[$i][0] ?? ''));
            $namaKelas = strtoupper(
                trim((string) ($rows[$i][1] ?? ''))
            );
            $kodeMapel = strtoupper(
                trim((string) ($rows[$i][2] ?? ''))
            );
            $hari = $this->normalizeHari(
                (string) ($rows[$i][3] ?? '')
            );
            $jamMulai = $this->normalizeJam(
                $rows[$i][4] ?? null
            );
            $jamSelesai = $this->normalizeJam(
                $rows[$i][5] ?? null
            );
            $sesi = $this->normalizeSesi(
                (string) ($rows[$i][6] ?? '')
            );

            if (
                $nip === ''
                && $namaKelas === ''
                && $kodeMapel === ''
                && trim((string) ($rows[$i][3] ?? '')) === ''
                && ($rows[$i][4] ?? '') === ''
                && ($rows[$i][5] ?? '') === ''
                && trim((string) ($rows[$i][6] ?? '')) === ''
            ) {
                continue;
            }

            if (
                $nip === ''
                || $namaKelas === ''
                || $kodeMapel === ''
                || $hari === ''
                || $jamMulai === null
                || $jamSelesai === null
                || $sesi === ''
            ) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: seluruh kolom wajib diisi dengan format valid.",
                ];
            }

            if (!in_array($hari, self::HARI_VALID, true)) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: hari '{$hari}' tidak valid.",
                ];
            }

            if (!in_array($sesi, self::SESI_VALID, true)) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: sesi tidak valid.",
                ];
            }

            if (
                strtotime($jamMulai)
                >= strtotime($jamSelesai)
            ) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: jam mulai harus lebih kecil dari jam selesai.",
                ];
            }

            $guru = $this->db
                ->table('guru')
                ->select('id, nama')
                ->where('nip', $nip)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($guru === null) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIP Guru {$nip} tidak ditemukan.",
                ];
            }

            $kelas = $this->db
                ->table('kelas')
                ->select('id, nama_kelas')
                ->where('nama_kelas', $namaKelas)
                ->where('id_tahun', $idTahun)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($kelas === null) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: kelas {$namaKelas} tidak ditemukan pada tahun ajaran yang dipilih.",
                ];
            }

            $mapel = $this->db
                ->table('mata_pelajaran')
                ->select('id, nama_mapel')
                ->where('kode_mapel', $kodeMapel)
                ->get()
                ->getRowArray();

            if ($mapel === null) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: kode mapel {$kodeMapel} tidak ditemukan.",
                ];
            }

            $prepared[] = [
                'id_guru' => (int) $guru['id'],
                'id_kelas' => (int) $kelas['id'],
                'id_mapel' => (int) $mapel['id'],
                'id_tahun' => $idTahun,
                'hari' => $hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'sesi' => $sesi,
                'status_jadwal' => 'Aktif',
            ];

            $sourceRows[] = [
                'excel_row' => $excelRow,
                'nama_guru' => $guru['nama'],
                'nama_kelas' => $kelas['nama_kelas'],
                'kode_mapel' => $kodeMapel,
            ];
        }

        if ($prepared === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada baris jadwal yang dapat diimport.',
            ];
        }

        $bentrok = $this->validateBentrok(
            $prepared,
            $sourceRows
        );

        if (!$bentrok['valid']) {
            return [
                'success' => false,
                'message' => $bentrok['errors'][0]
                    ?? 'Import dibatalkan karena ditemukan bentrok jadwal.',
                'errors' => $bentrok['errors'],
            ];
        }

        $this->db->transBegin();

        try {
            /*
             * Jadwal lama tidak dihapus. Semua jadwal aktif lama
             * dijadikan Nonaktif, kemudian hasil import menjadi set Aktif.
             */
            $this->db
                ->table('jadwal_guru')
                ->where('status_jadwal', 'Aktif')
                ->update([
                    'status_jadwal' => 'Nonaktif',
                ]);

            foreach ($prepared as $row) {
                if ($this->jadwalModel->insert($row) === false) {
                    throw new \RuntimeException(
                        implode(
                            ' ',
                            $this->jadwalModel->errors()
                        ) ?: 'Salah satu baris jadwal gagal disimpan.'
                    );
                }
            }

            $this->logActivity(
                'IMPORT',
                'Master Jadwal Guru',
                sprintf(
                    'Import %d jadwal aktif untuk %s - %s.',
                    count($prepared),
                    $tahun['nama_tahun'],
                    $tahun['semester']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi import jadwal gagal.'
                );
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf(
                    '%d jadwal berhasil diimport. Jadwal aktif sebelumnya telah dibuat Nonaktif.',
                    count($prepared)
                ),
                'jumlah_import' => count($prepared),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Import dibatalkan seluruhnya: '
                    . $e->getMessage(),
            ];
        }
    }

    /**
     * Pure overlap validation untuk baris hasil import.
     *
     * Tidak ada team teaching:
     * - guru sama + hari sama + overlap => error;
     * - kelas sama + hari sama + overlap => error.
     */
    public function validateBentrok(
        array $rows,
        array $sourceRows = []
    ): array {
        $errors = [];

        for ($i = 0, $count = count($rows); $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $rows[$i];
                $b = $rows[$j];

                if ($a['hari'] !== $b['hari']) {
                    continue;
                }

                if (
                    !$this->isOverlap(
                        $a['jam_mulai'],
                        $a['jam_selesai'],
                        $b['jam_mulai'],
                        $b['jam_selesai']
                    )
                ) {
                    continue;
                }

                $rowA = $sourceRows[$i]['excel_row']
                    ?? ($i + 2);
                $rowB = $sourceRows[$j]['excel_row']
                    ?? ($j + 2);

                if (
                    (int) $a['id_guru']
                    === (int) $b['id_guru']
                ) {
                    $errors[] = sprintf(
                        'Bentrok Guru pada baris %d dan %d: hari %s, %s-%s bertabrakan dengan %s-%s.',
                        $rowA,
                        $rowB,
                        $a['hari'],
                        substr($a['jam_mulai'], 0, 5),
                        substr($a['jam_selesai'], 0, 5),
                        substr($b['jam_mulai'], 0, 5),
                        substr($b['jam_selesai'], 0, 5)
                    );
                }

                if (
                    (int) $a['id_kelas']
                    === (int) $b['id_kelas']
                ) {
                    $errors[] = sprintf(
                        'Bentrok Kelas pada baris %d dan %d: %s pada hari %s memiliki jadwal overlap (tidak ada team teaching).',
                        $rowA,
                        $rowB,
                        $sourceRows[$i]['nama_kelas']
                            ?? 'kelas yang sama',
                        $a['hari']
                    );
                }
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function isOverlap(
        string $mulaiA,
        string $selesaiA,
        string $mulaiB,
        string $selesaiB
    ): bool {
        return strtotime($mulaiA) < strtotime($selesaiB)
            && strtotime($mulaiB) < strtotime($selesaiA);
    }

    public function delete(
        int $id,
        int $userId
    ): array {
        if (!$this->canManage($userId)) {
            return [
                'success' => false,
                'message' => 'Anda tidak memiliki hak untuk menghapus jadwal.',
            ];
        }

        $jadwal = $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.*, g.nama AS nama_guru, ' .
                'k.nama_kelas, mp.nama_mapel'
            )
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join(
                'mata_pelajaran mp',
                'mp.id = jg.id_mapel'
            )
            ->where('jg.id', $id)
            ->get()
            ->getRowArray();

        if ($jadwal === null) {
            return [
                'success' => false,
                'message' => 'Jadwal tidak ditemukan.',
            ];
        }

        $deleted = $this->db
            ->table('jadwal_guru')
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Jadwal gagal dihapus.',
            ];
        }

        $this->logActivity(
            'DELETE',
            'Master Jadwal Guru',
            sprintf(
                'Menghapus jadwal %s - %s - %s.',
                $jadwal['nama_guru'],
                $jadwal['nama_kelas'],
                $jadwal['nama_mapel']
            )
        );

        return [
            'success' => true,
            'message' => 'Jadwal berhasil dihapus.',
        ];
    }

    protected function applyViewScope(
        $builder,
        int $userId
    ): bool {
        $scope = $this->resolveViewScope($userId);

        if ($scope === 'SEMUA') {
            return true;
        }

        if ($scope === 'DIRI_SENDIRI') {
            $idGuru = $this->getIdGuruUser($userId);

            if ($idGuru <= 0) {
                return false;
            }

            $builder->where('jg.id_guru', $idGuru);

            return true;
        }

        return false;
    }

    protected function resolveViewScope(int $userId): string
    {
        if (
            $this->authService->resolveScope(
                'jadwal_guru.manage',
                $userId
            ) === 'SEMUA'
        ) {
            return 'SEMUA';
        }

        if (
            $this->authService->resolveScope(
                'jadwal_guru.view_all',
                $userId
            ) === 'SEMUA'
        ) {
            return 'SEMUA';
        }

        return $this->authService->resolveScope(
            'jadwal_guru.view',
            $userId
        );
    }

    protected function getIdGuruUser(int $userId): int
    {
        $row = $this->db
            ->table('users')
            ->select('id_guru')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        return isset($row['id_guru'])
            ? (int) $row['id_guru']
            : 0;
    }

    protected function normalizeHari(
        string $value
    ): string {
        $value = strtolower(trim($value));

        $map = [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            "jum'at" => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
        ];

        return $map[$value] ?? trim($value);
    }

    protected function normalizeSesi(
        string $value
    ): string {
        $value = strtolower(
            preg_replace(
                '/\s+/',
                ' ',
                trim($value)
            )
        );

        return match ($value) {
            'sesi awal' => 'Sesi Awal',
            'sesi akhir' => 'Sesi Akhir',
            'non sesi' => 'Non Sesi',
            default => trim($value),
        };
    }

    protected function normalizeJam(
        $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject(
                    (float) $value
                )->format('H:i:s');
            } catch (Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (['H:i:s', 'H:i', 'G:i'] as $format) {
            $date = \DateTime::createFromFormat(
                $format,
                $value
            );

            if ($date !== false) {
                $errors = \DateTime::getLastErrors();

                if (
                    $errors === false
                    || (
                        $errors['warning_count'] === 0
                        && $errors['error_count'] === 0
                    )
                ) {
                    return $date->format('H:i:s');
                }
            }
        }

        return null;
    }

    protected function logActivity(
        string $aksi,
        string $modul,
        string $keterangan
    ): void {
        $idUser = session()->get('user_id');

        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $idUser
                    ? (int) $idUser
                    : null,
                'aksi' => $aksi,
                'modul' => $modul,
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
            ]);
    }
}
