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
 * - scope DIRI_SENDIRI dan KELAS_DIAMPU digabung sebagai UNION data.
 * - scope KELAS_DIAMPU melihat jadwal kelas Wali aktif.
 * - identitas import Guru menerima NIP (18 digit) atau NIK (16 digit).
 * - header lama NIP_GURU tetap diterima untuk kompatibilitas.
 * - replacement import hanya menonaktifkan jadwal pada tahun yang diimport.
 * - prepared Semester Genap Nonaktif boleh menerima import sebelum aktivasi.
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

    public function getOptions(
        int $userId,
        ?int $filterGuru = null
    ): array {
        $scopes = $this->resolveViewScopes($userId);

        if ($scopes === []) {
            return $this->emptyOptions('');
        }

        if (in_array('SEMUA', $scopes, true)) {
            $guru = $this->db
                ->table('guru')
                ->select('id, nip, nik, nama')
                ->where('deleted_at', null)
                ->orderBy('nama', 'ASC')
                ->get()
                ->getResultArray();

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

            if ($filterGuru !== null && $filterGuru > 0) {
                $kelasBuilder
                    ->join(
                        'jadwal_guru jgk',
                        'jgk.id_kelas = k.id AND jgk.id_tahun = k.id_tahun'
                    )
                    ->where('jgk.id_guru', $filterGuru);
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
                'scope' => 'SEMUA',
            ];
        }

        $idGuruUser = $this->getIdGuruUser($userId);

        if ($idGuruUser <= 0) {
            return $this->emptyOptions(implode('+', $scopes));
        }

        $idTahunAktif = $this->getIdTahunAktif();
        $allowSelf = in_array('DIRI_SENDIRI', $scopes, true);
        $allowWali = in_array('KELAS_DIAMPU', $scopes, true);
        $kelasDiampu = [];

        if ($allowWali && $idTahunAktif > 0) {
            $kelasDiampu = $this->authService->getKelasDiampu(
                $idGuruUser,
                $idTahunAktif
            );
        }

        $scheduleBuilder = $this->db
            ->table('jadwal_guru jgo')
            ->distinct()
            ->select('jgo.id_guru, jgo.id_kelas, jgo.id_tahun');

        if (! $this->applyViewScope($scheduleBuilder, $userId, 'jgo')) {
            return $this->emptyOptions(implode('+', $scopes));
        }

        $scheduleRows = $scheduleBuilder
            ->get()
            ->getResultArray();

        $guruIds = [];
        $kelasIds = [];
        $tahunIds = [];

        if ($allowSelf) {
            $guruIds[$idGuruUser] = $idGuruUser;
        }

        foreach ($scheduleRows as $row) {
            $rowGuru = (int) ($row['id_guru'] ?? 0);
            $rowKelas = (int) ($row['id_kelas'] ?? 0);
            $rowTahun = (int) ($row['id_tahun'] ?? 0);

            if ($rowGuru > 0) {
                $guruIds[$rowGuru] = $rowGuru;
            }

            if (
                $rowKelas > 0
                && (
                    $filterGuru === null
                    || $filterGuru <= 0
                    || $rowGuru === $filterGuru
                )
            ) {
                $kelasIds[$rowKelas] = $rowKelas;
            }

            if ($rowTahun > 0) {
                $tahunIds[$rowTahun] = $rowTahun;
            }
        }

        if (
            $allowWali
            && $idTahunAktif > 0
            && ($filterGuru === null || $filterGuru <= 0)
        ) {
            foreach ($kelasDiampu as $idKelas) {
                $kelasIds[(int) $idKelas] = (int) $idKelas;
            }
        }

        if ($allowWali && $idTahunAktif > 0 && $kelasDiampu !== []) {
            $tahunIds[$idTahunAktif] = $idTahunAktif;
        }

        $guru = [];

        if ($guruIds !== []) {
            $guru = $this->db
                ->table('guru')
                ->select('id, nip, nik, nama')
                ->where('deleted_at', null)
                ->whereIn('id', array_values($guruIds))
                ->orderBy('nama', 'ASC')
                ->get()
                ->getResultArray();
        }

        $kelas = [];

        if ($kelasIds !== []) {
            $kelas = $this->db
                ->table('kelas k')
                ->select(
                    'k.id, k.nama_kelas, k.tingkat, k.rombel, k.id_tahun, ' .
                    'ta.nama_tahun, ta.semester'
                )
                ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
                ->where('k.deleted_at', null)
                ->where('ta.deleted_at', null)
                ->whereIn('k.id', array_values($kelasIds))
                ->orderBy('ta.nama_tahun', 'DESC')
                ->orderBy('k.tingkat', 'ASC')
                ->orderBy('k.rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        $tahun = [];

        if ($tahunIds !== []) {
            $tahun = $this->db
                ->table('tahun_ajaran')
                ->select('id, nama_tahun, semester, status_aktif')
                ->where('deleted_at', null)
                ->whereIn('id', array_values($tahunIds))
                ->orderBy('nama_tahun', 'DESC')
                ->orderBy(
                    "FIELD(semester,'Ganjil','Genap')",
                    '',
                    false
                )
                ->get()
                ->getResultArray();
        }

        return [
            'success' => true,
            'message' => 'Opsi jadwal berhasil dimuat.',
            'guru' => $guru,
            'kelas' => $kelas,
            'tahun' => $tahun,
            'scope' => implode('+', $scopes),
        ];
    }

    public function canManage(int $userId): bool
    {
        return $this->authService->resolveScope(
            'jadwal_guru.manage',
            $userId
        ) === 'SEMUA';
    }

    public function importJadwal(
        UploadedFile $file,
        int $idTahun,
        int $userId
    ): array {
        $actorUserId = $userId;

        if ($actorUserId <= 0 || ! $this->canManage($actorUserId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk mengimport jadwal.');
        }

        if (! $file->isValid()) {
            return $this->fail('INVALID_FILE', 'File import tidak valid.');
        }

        $extension = strtolower((string) $file->getClientExtension());

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->fail('INVALID_FILE', 'File import harus berformat XLSX atau XLS.');
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where('id', $idTahun)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($tahun === null) {
            return $this->fail('NOT_FOUND', 'Tahun ajaran tidak ditemukan.');
        }

        if (
            (int) $tahun['status_aktif'] !== 1
            && ! $this->isPreparedInactiveSemesterTarget($tahun)
        ) {
            return $this->fail(
                'INACTIVE_YEAR',
                'Import jadwal hanya dapat dilakukan ke periode Aktif atau Semester Genap Nonaktif yang sudah disiapkan dari Semester Ganjil aktif pada tahun pelajaran yang sama.'
            );
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return $this->fail('INVALID_FILE', 'File Excel tidak dapat dibaca.');
        }

        if (count($rows) < 2) {
            return $this->fail('EMPTY_IMPORT', 'File import tidak memiliki data jadwal.');
        }

        $header = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
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

        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $excelRow = $i + 1;
            $rawIdentitasGuru = $rows[$i][0] ?? '';

            if (is_int($rawIdentitasGuru) || is_float($rawIdentitasGuru)) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: kolom IDENTITAS_GURU wajib berformat Text agar NIK/NIP tidak kehilangan digit."
                );
            }

            $identitasGuru = $this->normalizeGuruIdentifier($rawIdentitasGuru);
            $namaKelas = strtoupper(trim((string) ($rows[$i][1] ?? '')));
            $kodeMapel = strtoupper(trim((string) ($rows[$i][2] ?? '')));
            $hari = $this->normalizeHari((string) ($rows[$i][3] ?? ''));
            $jamMulai = $this->normalizeJam($rows[$i][4] ?? null);
            $jamSelesai = $this->normalizeJam($rows[$i][5] ?? null);
            $sesi = $this->normalizeSesi((string) ($rows[$i][6] ?? ''));

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
                return $this->fail('VALIDATION', "Import dihentikan pada baris {$excelRow}: hari '{$hari}' tidak valid.");
            }

            if (! in_array($sesi, self::SESI_VALID, true)) {
                return $this->fail('VALIDATION', "Import dihentikan pada baris {$excelRow}: sesi tidak valid.");
            }

            if (strtotime($jamMulai) >= strtotime($jamSelesai)) {
                return $this->fail(
                    'VALIDATION',
                    "Import dihentikan pada baris {$excelRow}: jam mulai harus lebih kecil dari jam selesai."
                );
            }

            $guru = $this->findGuruByIdentifier($identitasGuru);

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
            return $this->fail('EMPTY_IMPORT', 'Tidak ada baris jadwal yang dapat diimport.');
        }

        $bentrok = $this->validateBentrok($prepared, $sourceRows);

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
            $this->db
                ->table('jadwal_guru')
                ->where('id_tahun', $idTahun)
                ->where('status_jadwal', 'Aktif')
                ->update(['status_jadwal' => 'Nonaktif']);

            foreach ($prepared as $row) {
                if ($this->jadwalModel->insert($row) === false) {
                    throw new \RuntimeException(
                        implode(' ', $this->jadwalModel->errors())
                            ?: 'Salah satu baris jadwal gagal disimpan.'
                    );
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi import jadwal gagal.');
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

                if (! $this->isOverlap(
                    $a['jam_mulai'],
                    $a['jam_selesai'],
                    $b['jam_mulai'],
                    $b['jam_selesai']
                )) {
                    continue;
                }

                $rowA = $sourceRows[$i]['excel_row'] ?? ($i + 2);
                $rowB = $sourceRows[$j]['excel_row'] ?? ($j + 2);

                if ((int) $a['id_guru'] === (int) $b['id_guru']) {
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

                if ((int) $a['id_kelas'] === (int) $b['id_kelas']) {
                    $errors[] = sprintf(
                        'Bentrok Kelas pada baris %d dan %d: %s pada hari %s memiliki jadwal overlap (tidak ada team teaching).',
                        $rowA,
                        $rowB,
                        $sourceRows[$i]['nama_kelas'] ?? 'kelas yang sama',
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

    public function delete(int $id, int $userId): array
    {
        if (! $this->canManage($userId)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk menghapus jadwal.');
        }

        $jadwal = $this->db
            ->table('jadwal_guru jg')
            ->select('jg.*, g.nama AS nama_guru, k.nama_kelas, mp.nama_mapel')
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id', $id)
            ->get()
            ->getRowArray();

        if ($jadwal === null) {
            return $this->fail('NOT_FOUND', 'Jadwal tidak ditemukan.');
        }

        $deleted = $this->db->table('jadwal_guru')->where('id', $id)->delete();

        if (! $deleted) {
            return $this->fail('DELETE_FAILED', 'Jadwal gagal dihapus.');
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

        return ['success' => true, 'message' => 'Jadwal berhasil dihapus.'];
    }

    protected function applyViewScope(
        BaseBuilder $builder,
        int $userId,
        string $alias = 'jg'
    ): bool {
        $scopes = $this->resolveViewScopes($userId);

        if (in_array('SEMUA', $scopes, true)) {
            return true;
        }

        $idGuru = $this->getIdGuruUser($userId);

        if ($idGuru <= 0) {
            return false;
        }

        $allowSelf = in_array('DIRI_SENDIRI', $scopes, true);
        $allowWali = in_array('KELAS_DIAMPU', $scopes, true);
        $idTahun = $allowWali ? $this->getIdTahunAktif() : 0;
        $kelas = ($allowWali && $idTahun > 0)
            ? $this->authService->getKelasDiampu($idGuru, $idTahun)
            : [];

        if (! $allowSelf && $kelas === []) {
            return false;
        }

        if ($allowSelf && $kelas !== []) {
            $builder
                ->groupStart()
                    ->where($alias . '.id_guru', $idGuru)
                    ->orGroupStart()
                        ->where($alias . '.id_tahun', $idTahun)
                        ->whereIn($alias . '.id_kelas', $kelas)
                    ->groupEnd()
                ->groupEnd();
            return true;
        }

        if ($allowSelf) {
            $builder->where($alias . '.id_guru', $idGuru);
            return true;
        }

        $builder
            ->where($alias . '.id_tahun', $idTahun)
            ->whereIn($alias . '.id_kelas', $kelas);

        return true;
    }

    protected function resolveViewScopes(int $userId): array
    {
        if ($this->authService->resolveScope('jadwal_guru.manage', $userId) === 'SEMUA') {
            return ['SEMUA'];
        }

        if ($this->authService->resolveScope('jadwal_guru.view_all', $userId) === 'SEMUA') {
            return ['SEMUA'];
        }

        $raw = $this->authService->getPermissionScopes('jadwal_guru.view', $userId);
        $scopes = [];

        foreach ($raw as $scope) {
            if (in_array($scope, ['DIRI_SENDIRI', 'KELAS_DIAMPU'], true)) {
                $scopes[$scope] = $scope;
            }
        }

        return array_values($scopes);
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

        return isset($row['id_guru']) ? (int) $row['id_guru'] : 0;
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

    private function applyFilters(BaseBuilder $builder, array $filter): void
    {
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
    }

    private function applyOrder(BaseBuilder $builder): BaseBuilder
    {
        return $builder
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy("FIELD(ta.semester,'Ganjil','Genap')", '', false)
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

    private function isPreparedInactiveSemesterTarget(array $target): bool
    {
        if (
            (string) ($target['semester'] ?? '') !== 'Genap'
            || (int) ($target['status_aktif'] ?? 0) !== 0
        ) {
            return false;
        }

        $source = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (
            $source === null
            || (string) ($source['semester'] ?? '') !== 'Ganjil'
            || (string) ($source['nama_tahun'] ?? '') !== (string) ($target['nama_tahun'] ?? '')
        ) {
            return false;
        }

        $sourceId = (int) $source['id'];
        $targetId = (int) $target['id'];

        $sourceClassCount = $this->db
            ->table('kelas')
            ->where('id_tahun', $sourceId)
            ->where('deleted_at', null)
            ->countAllResults();
        $targetClassCount = $this->db
            ->table('kelas')
            ->where('id_tahun', $targetId)
            ->where('deleted_at', null)
            ->countAllResults();

        $sourceMemberCount = $this->db
            ->table('anggota_kelas ak')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $sourceId)
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif')
            ->countAllResults();
        $targetMemberCount = $this->db
            ->table('anggota_kelas ak')
            ->join('siswa s', 's.id = ak.id_siswa')
            ->where('ak.id_tahun', $targetId)
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif')
            ->countAllResults();

        if (
            $sourceClassCount <= 0
            || $sourceClassCount !== $targetClassCount
            || $sourceMemberCount <= 0
            || $sourceMemberCount !== $targetMemberCount
        ) {
            return false;
        }

        if (
            $this->db
                ->table('riwayat_siswa')
                ->where('id_tahun', $targetId)
                ->where('status', 'Aktif')
                ->where('tanggal_selesai', null)
                ->countAllResults() > 0
        ) {
            return false;
        }

        foreach (['presensi', 'presensi_mengajar'] as $table) {
            if ($this->db
                ->table($table)
                ->where('id_tahun', $targetId)
                ->countAllResults() > 0
            ) {
                return false;
            }
        }

        return true;
    }

    private function emptyOptions(string $scope): array
    {
        return [
            'success' => true,
            'message' => 'Opsi jadwal berhasil dimuat.',
            'guru' => [],
            'kelas' => [],
            'tahun' => [],
            'scope' => $scope,
        ];
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

        return $builder->get()->getRowArray() ?: null;
    }

    protected function normalizeHari(string $value): string
    {
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

    protected function normalizeSesi(string $value): string
    {
        $value = strtolower(
            preg_replace('/\s+/', ' ', trim($value)) ?? ''
        );

        return match ($value) {
            'sesi awal' => 'Sesi Awal',
            'sesi akhir' => 'Sesi Akhir',
            'non sesi' => 'Non Sesi',
            default => trim($value),
        };
    }

    protected function normalizeJam($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i:s');
            } catch (Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (['H:i:s', 'H:i', 'G:i'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            if ($date === false) {
                continue;
            }

            $errors = \DateTime::getLastErrors();

            if (
                $errors === false
                || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)
            ) {
                return $date->format('H:i:s');
            }
        }

        return null;
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
