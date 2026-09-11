<?php

namespace App\Services;

use App\Models\JadwalGuruModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * JadwalGuruService
 *
 * STEP 05 consistency:
 * - Service tetap authorization boundary.
 * - Wali Kelas adalah context dinamis, bukan role.
 * - scope KELAS_DIAMPU melihat jadwal kelas Wali aktif.
 * - identitas import Guru menerima NIP (18 digit) atau NIK (16 digit).
 * - header lama NIP_GURU tetap diterima untuk kompatibilitas.
 * - replacement import hanya menonaktifkan jadwal pada tahun yang diimport.
 */
class JadwalGuruService
{
    protected JadwalGuruModel $jadwalModel;
    protected AuthService $authService;
    protected ActivityLogService $activityLog;
    protected BaseConnection $db;

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

    private const HEADER_IDENTITAS = [
        'IDENTITAS_GURU',
        'NIP_GURU',
        'NIP_NIK_GURU',
    ];

    public function __construct()
    {
        $this->jadwalModel = new JadwalGuruModel();
        $this->authService = new AuthService();
        $this->activityLog = new ActivityLogService();
        $this->db = Database::connect();
    }

    public function getList(
        array $filter,
        int $userId
    ): array {
        $builder = $this->baseListBuilder();

        if (! $this->applyViewScope($builder, $userId)) {
            return [];
        }

        $this->applyFilters($builder, $filter);

        return $this->applyOrder($builder)
            ->get()
            ->getResultArray();
    }

    /**
     * Opsi filter sesuai data-level scope.
     *
     * SEMUA:
     * - seluruh Guru/Kelas/Tahun.
     *
     * DIRI_SENDIRI:
     * - hanya identitas Guru actor dan kelas yang mempunyai jadwal Guru tersebut.
     *
     * KELAS_DIAMPU:
     * - hanya kelas Wali aktif dan Guru yang muncul pada jadwal kelas tersebut.
     */
    public function getOptions(
        int $userId,
        ?int $filterGuru = null
    ): array {
        $scope = $this->resolveViewScope($userId);
        $idGuruUser = $this->getIdGuruUser($userId);
        $idTahunAktif = $this->getIdTahunAktif();
        $kelasDiampu = [];

        if ($scope === 'KELAS_DIAMPU') {
            if ($idGuruUser <= 0 || $idTahunAktif <= 0) {
                return [
                    'success' => true,
                    'message' => 'Opsi jadwal berhasil dimuat.',
                    'guru' => [],
                    'kelas' => [],
                    'tahun' => [],
                    'scope' => $scope,
                ];
            }

            $kelasDiampu = $this->authService->getKelasDiampu(
                $idGuruUser,
                $idTahunAktif
            );

            if ($kelasDiampu === []) {
                return [
                    'success' => true,
                    'message' => 'Opsi jadwal berhasil dimuat.',
                    'guru' => [],
                    'kelas' => [],
                    'tahun' => [],
                    'scope' => $scope,
                ];
            }
        }

        $guruBuilder = $this->db
            ->table('guru g')
            ->distinct()
            ->select('g.id, g.nip, g.nik, g.nama')
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
        } elseif ($scope === 'KELAS_DIAMPU') {
            $guru = $guruBuilder
                ->join(
                    'jadwal_guru jgo',
                    'jgo.id_guru = g.id',
                    'inner'
                )
                ->whereIn('jgo.id_kelas', $kelasDiampu)
                ->where('jgo.id_tahun', $idTahunAktif)
                ->where('jgo.status_jadwal', 'Aktif')
                ->orderBy('g.nama', 'ASC')
                ->get()
                ->getResultArray();
        } elseif ($scope === 'SEMUA') {
            $guru = $guruBuilder
                ->orderBy('g.nama', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            $guru = [];
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
        } elseif ($scope === 'KELAS_DIAMPU') {
            $kelasBuilder
                ->where('k.id_tahun', $idTahunAktif)
                ->whereIn('k.id', $kelasDiampu);

            if ($idGuruKelas !== null) {
                $kelasBuilder
                    ->join(
                        'jadwal_guru jgk',
                        'jgk.id_kelas = k.id AND jgk.id_tahun = k.id_tahun'
                    )
                    ->where('jgk.id_guru', $idGuruKelas)
                    ->where('jgk.status_jadwal', 'Aktif');
            }
        }

        if (
            $scope !== 'KELAS_DIAMPU'
            && $idGuruKelas !== null
        ) {
            $kelasBuilder
                ->join(
                    'jadwal_guru jgk',
                    'jgk.id_kelas = k.id AND jgk.id_tahun = k.id_tahun'
                )
                ->where('jgk.id_guru', $idGuruKelas);
        }

        $kelas = in_array(
            $scope,
            ['SEMUA', 'DIRI_SENDIRI', 'KELAS_DIAMPU'],
            true
        )
            ? $kelasBuilder
                ->orderBy('ta.nama_tahun', 'DESC')
                ->orderBy('k.tingkat', 'ASC')
                ->orderBy('k.rombel', 'ASC')
                ->get()
                ->getResultArray()
            : [];

        $tahunBuilder = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null);

        if ($scope === 'KELAS_DIAMPU') {
            $tahunBuilder->where('id', $idTahunAktif);
        }

        $tahun = in_array(
            $scope,
            ['SEMUA', 'DIRI_SENDIRI', 'KELAS_DIAMPU'],
            true
        )
            ? $tahunBuilder
                ->orderBy('nama_tahun', 'DESC')
                ->orderBy(
                    "FIELD(semester,'Ganjil','Genap')",
                    '',
                    false
                )
                ->get()
                ->getResultArray()
            : [];

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
     * Import Excel replacement semantics untuk satu Tahun Ajaran.
     *
     * Actor wajib eksplisit agar Service tetap menjadi authorization boundary.
     */
    public function importJadwal(
        UploadedFile $file,
        int $idTahun,
        int $userId
    ): array {
        $actorUserId = $userId;

        if ($actorUserId <= 0 || ! $this->canManage($actorUserId)) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak untuk mengimport jadwal.'
            );
        }

        if (! $file->isValid()) {
            return $this->fail(
                'INVALID_FILE',
                'File import tidak valid.'
            );
        }

        $extension = strtolower(
            (string) $file->getClientExtension()
        );

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->fail(
                'INVALID_FILE',
                'File import harus berformat XLSX atau XLS.'
            );
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($tahun === null) {
            return $this->fail(
                'NOT_FOUND',
                'Tahun ajaran tidak ditemukan.'
            );
        }

        if ((int) $tahun['status_aktif'] !== 1) {
            return $this->fail(
                'INACTIVE_YEAR',
                'Import jadwal hanya dapat dilakukan ke tahun ajaran/semester yang sedang Aktif.'
            );
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
            return $this->fail(
                'INVALID_FILE',
                'File Excel tidak dapat dibaca.'
            );
        }

        if (count($rows) < 2) {
            return $this->fail(
                'EMPTY_IMPORT',
                'File import tidak memiliki data jadwal.'
            );
        }

        $header = array_map(
            static fn ($value): string =>
                strtoupper(trim((string) $value)),
            array_slice($rows[0], 0, 7)
        );

        if (! $this->validImportHeader($header)) {
            return $this->fail(
                'INVALID_HEADER',
                'Header template tidak sesuai. Gunakan: IDENTITAS_GURU, NAMA_KELAS, KODE_MAPEL, HARI, JAM_MULAI, JAM_SELESAI, SESI.'
            );
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

            $rawIdentitasGuru = $rows[$i][0] ?? '';

            if (is_int($rawIdentitasGuru) || is_float($rawIdentitasGuru)) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: kolom IDENTITAS_GURU wajib berformat Text agar NIK/NIP tidak kehilangan digit."
                );
            }

            $identitasGuru = $this->normalizeGuruIdentifier(
                $rawIdentitasGuru
            );
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
                $identitasGuru === ''
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
                $identitasGuru === ''
                || $namaKelas === ''
                || $kodeMapel === ''
                || $hari === ''
                || $jamMulai === null
                || $jamSelesai === null
                || $sesi === ''
            ) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: seluruh kolom wajib diisi dengan format valid."
                );
            }

            if (! preg_match('/^(?:\d{16}|\d{18})$/', $identitasGuru)) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: IDENTITAS_GURU harus NIK 16 digit atau NIP 18 digit. Pastikan kolom Excel berformat Text."
                );
            }

            if (! in_array($hari, self::HARI_VALID, true)) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: hari '{$hari}' tidak valid."
                );
            }

            if (! in_array($sesi, self::SESI_VALID, true)) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: sesi tidak valid."
                );
            }

            if (
                strtotime($jamMulai)
                >= strtotime($jamSelesai)
            ) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: jam mulai harus lebih kecil dari jam selesai."
                );
            }

            $guru = $this->findGuruByIdentifier(
                $identitasGuru
            );

            if ($guru === null) {
                return $this->fail(
                    'NOT_FOUND',
                    "Import dihentikan pada baris {$excelRow}: NIP/NIK Guru {$identitasGuru} tidak ditemukan."
                );
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
                return $this->fail(
                    'NOT_FOUND',
                    "Import dihentikan pada baris {$excelRow}: kelas {$namaKelas} tidak ditemukan pada tahun ajaran yang dipilih."
                );
            }

            $mapel = $this->db
                ->table('mata_pelajaran')
                ->select('id, nama_mapel')
                ->where('kode_mapel', $kodeMapel)
                ->get()
                ->getRowArray();

            if ($mapel === null) {
                return $this->fail(
                    'NOT_FOUND',
                    "Import dihentikan pada baris {$excelRow}: kode mapel {$kodeMapel} tidak ditemukan."
                );
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
            return $this->fail(
                'EMPTY_IMPORT',
                'Tidak ada baris jadwal yang dapat diimport.'
            );
        }

        $bentrok = $this->validateBentrok(
            $prepared,
            $sourceRows
        );

        if (! $bentrok['valid']) {
            return [
                'success' => false,
                'code' => 'SCHEDULE_CONFLICT',
                'message' => $bentrok['errors'][0]
                    ?? 'Import dibatalkan karena ditemukan bentrok jadwal.',
                'errors' => $bentrok['errors'],
            ];
        }

        $this->db->transBegin();

        try {
            // Replacement hanya berlaku pada Tahun Ajaran yang diimport.
            $this->db
                ->table('jadwal_guru')
                ->where('id_tahun', $idTahun)
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

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi import jadwal gagal.'
                );
            }

            $this->activityLog->write(
                $actorUserId,
                'IMPORT',
                'Master Jadwal Guru',
                sprintf(
                    'Import %d jadwal aktif untuk %s - %s.',
                    count($prepared),
                    $tahun['nama_tahun'],
                    $tahun['semester']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf(
                    '%d jadwal berhasil diimport. Jadwal aktif sebelumnya pada tahun ajaran yang sama telah dibuat Nonaktif.',
                    count($prepared)
                ),
                'jumlah_import' => count($prepared),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->fail(
                'IMPORT_FAILED',
                'Import dibatalkan seluruhnya: ' . $e->getMessage()
            );
        }
    }

    /**
     * Pure overlap validation untuk baris hasil import.
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
                    ! $this->isOverlap(
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
        if (! $this->canManage($userId)) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak untuk menghapus jadwal.'
            );
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
            return $this->fail(
                'NOT_FOUND',
                'Jadwal tidak ditemukan.'
            );
        }

        $deleted = $this->db
            ->table('jadwal_guru')
            ->where('id', $id)
            ->delete();

        if (! $deleted) {
            return $this->fail(
                'DELETE_FAILED',
                'Jadwal gagal dihapus.'
            );
        }

        $this->activityLog->write(
            $userId,
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
        BaseBuilder $builder,
        int $userId
    ): bool {
        $scope = $this->resolveViewScope($userId);

        if ($scope === 'SEMUA') {
            return true;
        }

        $idGuru = $this->getIdGuruUser($userId);

        if ($scope === 'DIRI_SENDIRI') {
            if ($idGuru <= 0) {
                return false;
            }

            $builder->where('jg.id_guru', $idGuru);

            return true;
        }

        if ($scope === 'KELAS_DIAMPU') {
            $idTahun = $this->getIdTahunAktif();

            if ($idGuru <= 0 || $idTahun <= 0) {
                return false;
            }

            $kelas = $this->authService->getKelasDiampu(
                $idGuru,
                $idTahun
            );

            if ($kelas === []) {
                return false;
            }

            $builder
                ->where('jg.id_tahun', $idTahun)
                ->whereIn('jg.id_kelas', $kelas);

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
            ->where('status_aktif', 1)
            ->get()
            ->getRowArray();

        return isset($row['id_guru'])
            ? (int) $row['id_guru']
            : 0;
    }

    private function baseListBuilder(): BaseBuilder
    {
        return $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.id, jg.id_guru, jg.id_kelas, jg.id_mapel, jg.id_tahun, ' .
                'jg.hari, jg.jam_mulai, jg.jam_selesai, jg.sesi, ' .
                'jg.status_jadwal, g.nip, g.nik, g.nama AS nama_guru, ' .
                'k.nama_kelas, k.tingkat, k.rombel, ' .
                'mp.nama_mapel, mp.kode_mapel, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif'
            )
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->join('tahun_ajaran ta', 'ta.id = jg.id_tahun');
    }

    private function applyFilters(
        BaseBuilder $builder,
        array $filter
    ): void {
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

        $status = trim(
            (string) ($filter['status_jadwal'] ?? '')
        );
        if (in_array($status, ['Aktif', 'Nonaktif'], true)) {
            $builder->where('jg.status_jadwal', $status);
        }
    }

    private function applyOrder(BaseBuilder $builder): BaseBuilder
    {
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
            ->orderBy('jg.id', 'ASC');
    }

    private function getIdTahunAktif(): int
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return (int) ($row['id'] ?? 0);
    }

    private function validImportHeader(array $header): bool
    {
        if (count($header) !== 7) {
            return false;
        }

        if (! in_array($header[0] ?? '', self::HEADER_IDENTITAS, true)) {
            return false;
        }

        return array_slice($header, 1) === [
            'NAMA_KELAS',
            'KODE_MAPEL',
            'HARI',
            'JAM_MULAI',
            'JAM_SELESAI',
            'SESI',
        ];
    }

    private function normalizeGuruIdentifier($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value)) {
            $value = sprintf('%.0f', $value);
        }

        $text = trim((string) $value);
        $text = preg_replace('/\s+/', '', $text) ?? '';

        return $text;
    }

    private function findGuruByIdentifier(string $identifier): ?array
    {
        $builder = $this->db
            ->table('guru')
            ->select('id, nik, nip, nama')
            ->where('deleted_at', null);

        if (preg_match('/^\d{18}$/', $identifier) === 1) {
            $builder->where('nip', $identifier);
        } elseif (preg_match('/^\d{16}$/', $identifier) === 1) {
            $builder->where('nik', $identifier);
        } else {
            return null;
        }

        return $builder
            ->get()
            ->getRowArray() ?: null;
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
            ) ?? ''
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
        if ($value === null || $value === '') {
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

            if ($date === false) {
                continue;
            }

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

        return null;
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
