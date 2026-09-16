<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Extension G3.2 untuk Jurnal Mengajar.
 *
 * Menambah:
 * - catatan optional pada parent Jurnal;
 * - exception siswa Sakit/Izin/Alpha per Jurnal;
 * - roster-aware validation;
 * - parent + child atomic save/revision;
 * - aggregate exception untuk laporan;
 * - detail laporan on-demand.
 *
 * Tabel presensi siswa resmi tidak pernah ditulis oleh service ini.
 */
class PresensiMengajarJurnalService extends PresensiMengajarService
{
    private const TZ = 'Asia/Jakarta';
    private const GURU_STATUS = ['Hadir', 'Izin', 'Sakit'];
    private const SISWA_STATUS = ['Sakit', 'Izin', 'Alpha'];

    public function loadInput(int $userId, int $idJadwal, string $tanggal): array
    {
        if (! $this->schemaReady()) {
            return $this->failEnhanced(
                'SCHEMA_NOT_READY',
                'Schema Jurnal siswa belum tersedia. Jalankan SQL schema G3.2 terlebih dahulu.'
            );
        }

        $result = parent::loadInput($userId, $idJadwal, $tanggal);

        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $jadwal = $result['jadwal'] ?? [];
        $idTahun = (int) ($jadwal['id_tahun'] ?? 0);
        $idKelas = (int) ($jadwal['id_kelas'] ?? 0);
        $tanggal = $this->normalizeTanggalEnhanced($tanggal);

        $result['siswa_options'] = $this->getRosterForDate(
            $idTahun,
            $idKelas,
            $tanggal
        );

        $idJurnal = (int) ($result['existing']['id'] ?? 0);
        $result['siswa_exceptions'] = $idJurnal > 0
            ? $this->getStudentExceptions($idJurnal)
            : [];
        $result['siswa_exception_summary'] = $this->summarizeExceptions(
            $result['siswa_exceptions']
        );

        return $result;
    }

    public function save(int $userId, array $data): array
    {
        if (! $this->schemaReady()) {
            return $this->failEnhanced(
                'SCHEMA_NOT_READY',
                'Schema Jurnal siswa belum tersedia. Jalankan SQL schema G3.2 terlebih dahulu.'
            );
        }

        $idJadwal = (int) ($data['id_jadwal'] ?? 0);
        $tanggal = $this->normalizeTanggalEnhanced((string) ($data['tanggal'] ?? ''));
        $status = trim((string) ($data['status'] ?? ''));
        $materi = trim((string) ($data['materi'] ?? ''));
        $catatan = trim((string) ($data['catatan'] ?? ''));

        if ($idJadwal <= 0 || $tanggal === '') {
            return $this->failEnhanced('INVALID_REQUEST', 'Jadwal atau tanggal tidak valid.');
        }

        if (! in_array($status, self::GURU_STATUS, true)) {
            return $this->failEnhanced(
                'INVALID_STATUS',
                'Status Guru hanya boleh Hadir, Izin, atau Sakit.'
            );
        }

        if ($materi === '') {
            return $this->failEnhanced(
                'EMPTY_MATERI',
                'Materi/keterangan Jurnal wajib diisi.'
            );
        }

        // Parent loadInput tetap menjadi authorization/time-window boundary.
        $context = parent::loadInput($userId, $idJadwal, $tanggal);

        if (! ($context['success'] ?? false)) {
            return $context;
        }

        $jadwal = $context['jadwal'] ?? [];
        $existing = $context['existing'] ?? null;
        $isRevision = is_array($existing) && (int) ($existing['id'] ?? 0) > 0;

        $roster = $this->getRosterForDate(
            (int) ($jadwal['id_tahun'] ?? 0),
            (int) ($jadwal['id_kelas'] ?? 0),
            $tanggal
        );

        $normalizedSiswa = $this->normalizeStudentExceptions(
            $data['siswa'] ?? [],
            $roster
        );

        if (! ($normalizedSiswa['success'] ?? false)) {
            return $normalizedSiswa;
        }

        $studentRows = $normalizedSiswa['rows'] ?? [];

        if ($status !== 'Hadir' && $studentRows !== []) {
            return $this->failEnhanced(
                'STUDENT_EXCEPTIONS_REQUIRE_HADIR',
                'Daftar siswa S/I/A hanya dapat dicatat ketika status Guru Hadir.'
            );
        }

        if (! $isRevision && ($context['capability'] ?? '') !== 'SEMUA' && $status === 'Hadir') {
            $geo = $this->geofencingService->validateRequired(
                $data['latitude'] ?? null,
                $data['longitude'] ?? null
            );

            if (! ($geo['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => 'GEOFENCE_FAILED',
                    'message' => $geo['message'] ?? 'Validasi lokasi gagal.',
                    'geofence' => $geo,
                ];
            }
        }

        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $this->db->transBegin();

        try {
            if ($isRevision) {
                $idJurnal = (int) $existing['id'];
                $ok = $this->db
                    ->table('presensi_mengajar')
                    ->where('id', $idJurnal)
                    ->update([
                        'status' => $status,
                        'materi' => $materi,
                        'catatan' => $catatan !== '' ? $catatan : null,
                        'updated_at' => $now,
                        'updated_by' => $userId,
                    ]);

                if ($ok === false) {
                    throw new \RuntimeException('Revisi Jurnal gagal.');
                }

                $deleted = $this->db
                    ->table('presensi_mengajar_siswa')
                    ->where('id_presensi_mengajar', $idJurnal)
                    ->delete();

                if ($deleted === false) {
                    throw new \RuntimeException('Reset detail siswa Jurnal gagal.');
                }
            } else {
                $ok = $this->db
                    ->table('presensi_mengajar')
                    ->insert([
                        'id_guru' => (int) ($jadwal['id_guru'] ?? 0),
                        'nama_guru_snapshot' => (string) ($jadwal['nama_guru'] ?? ''),
                        'id_jadwal' => $idJadwal,
                        'id_kelas' => (int) ($jadwal['id_kelas'] ?? 0),
                        'id_tahun' => (int) ($jadwal['id_tahun'] ?? 0),
                        'tanggal' => $tanggal,
                        'status' => $status,
                        'materi' => $materi,
                        'catatan' => $catatan !== '' ? $catatan : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'updated_by' => null,
                    ]);

                if ($ok === false) {
                    throw new \RuntimeException('Penyimpanan Jurnal gagal.');
                }

                $idJurnal = (int) $this->db->insertID();
            }

            if ($status === 'Hadir' && $studentRows !== []) {
                $batch = [];

                foreach ($studentRows as $row) {
                    $batch[] = [
                        'id_presensi_mengajar' => $idJurnal,
                        'id_siswa' => (int) $row['id_siswa'],
                        'nama_siswa_snapshot' => (string) $row['nama'],
                        'nisn_snapshot' => ($row['nisn'] ?? '') !== ''
                            ? (string) $row['nisn']
                            : null,
                        'status' => (string) $row['status'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $okChild = $this->db
                    ->table('presensi_mengajar_siswa')
                    ->insertBatch($batch);

                if ($okChild === false) {
                    throw new \RuntimeException('Penyimpanan siswa Jurnal gagal.');
                }
            }

            $this->writeActivityLogEnhanced(
                $userId,
                $isRevision ? 'REVISI' : 'INPUT',
                sprintf(
                    '%s Jurnal %s - %s tanggal %s (%s, %d siswa S/I/A).',
                    $isRevision ? 'Revisi' : 'Input',
                    (string) ($jadwal['nama_guru'] ?? '-'),
                    (string) ($jadwal['nama_kelas'] ?? '-'),
                    $tanggal,
                    $status,
                    count($studentRows)
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction Jurnal gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'code' => 'SAVE_FAILED',
                'message' => $isRevision
                    ? 'Revisi Jurnal gagal disimpan. Tidak ada perubahan parsial yang dipertahankan.'
                    : 'Jurnal gagal disimpan. Tidak ada perubahan parsial yang dipertahankan.',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ];
        }

        return [
            'success' => true,
            'message' => $isRevision
                ? 'Revisi Jurnal berhasil disimpan.'
                : 'Jurnal berhasil disimpan.',
            'revision' => $isRevision,
            'capability' => $context['capability'] ?? '',
            'id_jurnal' => $idJurnal,
            'siswa_exception_total' => count($studentRows),
        ];
    }

    public function getHistori(
        int $userId,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit = 50,
        int $offset = 0,
        ?string $status = null
    ): array {
        $result = parent::getHistori(
            $userId,
            $tanggalMulai,
            $tanggalSelesai,
            $limit,
            $offset,
            $status
        );

        if (! ($result['success'] ?? false) || ! $this->schemaReady()) {
            return $result;
        }

        $result['rows'] = $this->attachExceptionSummary(
            is_array($result['rows'] ?? null) ? $result['rows'] : []
        );

        return $result;
    }

    public function getHistoriDetail(int $userId, int $idJurnal): array
    {
        if (! $this->schemaReady()) {
            return $this->failEnhanced(
                'SCHEMA_NOT_READY',
                'Schema Jurnal siswa belum tersedia. Jalankan SQL schema G3.2 terlebih dahulu.'
            );
        }

        if ($userId <= 0 || $idJurnal <= 0) {
            return $this->failEnhanced('INVALID_REQUEST', 'Jurnal tidak valid.');
        }

        $tahun = $this->getTahunAktifInfo();

        if ($tahun === null) {
            return $this->failEnhanced('NO_ACTIVE_YEAR', 'Tidak ada Tahun Ajaran aktif.');
        }

        $scopes = $this->authService->getPermissionScopes(
            'presensi_mengajar.view',
            $userId
        );

        if ($scopes === []) {
            return $this->failEnhanced('FORBIDDEN', 'Anda tidak memiliki hak melihat Jurnal.');
        }

        $row = $this->db
            ->table('presensi_mengajar pm')
            ->select([
                'pm.id',
                'pm.id_guru',
                'pm.nama_guru_snapshot',
                'pm.id_jadwal',
                'pm.id_kelas',
                'pm.id_tahun',
                'pm.tanggal',
                'pm.status',
                'pm.materi',
                'pm.catatan',
                'pm.created_at',
                'pm.updated_at',
                'k.nama_kelas',
                'mp.nama_mapel',
                'mp.kode_mapel',
                'jg.hari',
                'jg.jam_mulai',
                'jg.jam_selesai',
                'jg.sesi',
            ])
            ->join('jadwal_guru jg', 'jg.id = pm.id_jadwal', 'left')
            ->join('kelas k', 'k.id = pm.id_kelas', 'left')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel', 'left')
            ->where('pm.id', $idJurnal)
            ->where('pm.id_tahun', (int) $tahun['id'])
            ->get()
            ->getRowArray();

        if ($row === null) {
            return $this->failEnhanced('NOT_FOUND', 'Jurnal tidak ditemukan.');
        }

        if (! in_array('SEMUA', $scopes, true)) {
            if (! in_array('DIRI_SENDIRI', $scopes, true)) {
                return $this->failEnhanced('FORBIDDEN', 'Anda tidak memiliki scope melihat Jurnal.');
            }

            $user = $this->db
                ->table('users')
                ->select('id_guru')
                ->where('id', $userId)
                ->where('status_aktif', 1)
                ->get()
                ->getRowArray();

            if ((int) ($user['id_guru'] ?? 0) !== (int) $row['id_guru']) {
                return $this->failEnhanced('FORBIDDEN', 'Anda hanya dapat melihat Jurnal diri sendiri.');
            }
        }

        $siswa = $this->getStudentExceptions($idJurnal);

        return [
            'success' => true,
            'message' => 'Detail Jurnal berhasil dimuat.',
            'jurnal' => $row,
            'siswa' => $siswa,
            'siswa_exception_summary' => $this->summarizeExceptions($siswa),
        ];
    }

    private function schemaReady(): bool
    {
        return $this->db->fieldExists('catatan', 'presensi_mengajar')
            && $this->db->tableExists('presensi_mengajar_siswa');
    }

    private function getRosterForDate(
        int $idTahun,
        int $idKelas,
        string $tanggal
    ): array {
        if ($idTahun <= 0 || $idKelas <= 0 || $tanggal === '') {
            return [];
        }

        if ($tanggal === Time::now(self::TZ)->format('Y-m-d')) {
            return $this->db
                ->table('anggota_kelas ak')
                ->select('s.id, s.nisn, s.nama')
                ->join('siswa s', 's.id = ak.id_siswa')
                ->where('ak.id_tahun', $idTahun)
                ->where('ak.id_kelas', $idKelas)
                ->where('s.status_aktif', 'Aktif')
                ->where('s.deleted_at', null)
                ->orderBy('s.nama', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->db
            ->table('riwayat_siswa rs')
            ->select('s.id, s.nisn, s.nama')
            ->join('siswa s', 's.id = rs.id_siswa')
            ->where('rs.id_tahun', $idTahun)
            ->where('rs.id_kelas', $idKelas)
            ->where('rs.tanggal_mulai <=', $tanggal)
            ->groupStart()
                ->where('rs.tanggal_selesai', null)
                ->orWhere('rs.tanggal_selesai >=', $tanggal)
            ->groupEnd()
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getStudentExceptions(int $idJurnal): array
    {
        if ($idJurnal <= 0 || ! $this->db->tableExists('presensi_mengajar_siswa')) {
            return [];
        }

        return $this->db
            ->table('presensi_mengajar_siswa')
            ->select('id, id_siswa, nama_siswa_snapshot, nisn_snapshot, status')
            ->where('id_presensi_mengajar', $idJurnal)
            ->orderBy('nama_siswa_snapshot', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function normalizeStudentExceptions(mixed $items, array $roster): array
    {
        if ($items === null || $items === '') {
            $items = [];
        }

        if (! is_array($items)) {
            return $this->failEnhanced(
                'INVALID_STUDENT_LIST',
                'Format daftar siswa Jurnal tidak valid.'
            );
        }

        $rosterMap = [];
        foreach ($roster as $student) {
            $id = (int) ($student['id'] ?? 0);
            if ($id > 0) {
                $rosterMap[$id] = $student;
            }
        }

        $rows = [];
        $seen = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                return $this->failEnhanced(
                    'INVALID_STUDENT_LIST',
                    'Format salah satu siswa Jurnal tidak valid.'
                );
            }

            $idSiswa = (int) ($item['id_siswa'] ?? 0);
            $status = trim((string) ($item['status'] ?? ''));

            if ($idSiswa <= 0 || ! in_array($status, self::SISWA_STATUS, true)) {
                return $this->failEnhanced(
                    'INVALID_STUDENT_STATUS',
                    'Status siswa pada Jurnal hanya boleh Sakit, Izin, atau Alpha.'
                );
            }

            if (isset($seen[$idSiswa])) {
                return $this->failEnhanced(
                    'DUPLICATE_STUDENT',
                    'Siswa yang sama tidak boleh dicatat dua kali pada satu Jurnal.'
                );
            }

            if (! isset($rosterMap[$idSiswa])) {
                return $this->failEnhanced(
                    'STUDENT_OUTSIDE_CLASS',
                    'Salah satu siswa bukan anggota kelas Jurnal pada tanggal tersebut.'
                );
            }

            $seen[$idSiswa] = true;
            $rows[] = [
                'id_siswa' => $idSiswa,
                'nama' => (string) ($rosterMap[$idSiswa]['nama'] ?? ''),
                'nisn' => (string) ($rosterMap[$idSiswa]['nisn'] ?? ''),
                'status' => $status,
            ];
        }

        return [
            'success' => true,
            'rows' => $rows,
        ];
    }

    private function attachExceptionSummary(array $rows): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        )));

        if ($ids === []) {
            return $rows;
        }

        $counts = $this->db
            ->table('presensi_mengajar_siswa')
            ->select('id_presensi_mengajar, status, COUNT(*) AS jumlah')
            ->whereIn('id_presensi_mengajar', $ids)
            ->groupBy('id_presensi_mengajar, status')
            ->get()
            ->getResultArray();

        $summaryMap = [];
        foreach ($counts as $count) {
            $id = (int) ($count['id_presensi_mengajar'] ?? 0);
            $status = (string) ($count['status'] ?? '');
            $jumlah = (int) ($count['jumlah'] ?? 0);

            if (! isset($summaryMap[$id])) {
                $summaryMap[$id] = [
                    'Sakit' => 0,
                    'Izin' => 0,
                    'Alpha' => 0,
                    'total' => 0,
                ];
            }

            if (isset($summaryMap[$id][$status])) {
                $summaryMap[$id][$status] = $jumlah;
                $summaryMap[$id]['total'] += $jumlah;
            }
        }

        foreach ($rows as &$row) {
            $id = (int) ($row['id'] ?? 0);
            $summary = $summaryMap[$id] ?? [
                'Sakit' => 0,
                'Izin' => 0,
                'Alpha' => 0,
                'total' => 0,
            ];
            $row['siswa_exception_summary'] = $summary;
            $row['siswa_exception_count'] = (int) $summary['total'];
        }
        unset($row);

        return $rows;
    }

    private function summarizeExceptions(array $rows): array
    {
        $summary = [
            'Sakit' => 0,
            'Izin' => 0,
            'Alpha' => 0,
            'total' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (isset($summary[$status])) {
                $summary[$status]++;
                $summary['total']++;
            }
        }

        return $summary;
    }

    private function normalizeTanggalEnhanced(string $tanggal): string
    {
        $tanggal = trim($tanggal);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $tanggal
        ) {
            return '';
        }

        return $tanggal;
    }

    private function writeActivityLogEnhanced(
        int $userId,
        string $aksi,
        string $keterangan
    ): void {
        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $userId,
                'aksi' => $aksi,
                'modul' => 'Presensi Mengajar',
                'keterangan' => $keterangan,
                'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
            ]);
    }

    private function failEnhanced(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
