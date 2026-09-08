<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * PresensiModel
 *
 * Data Presensi Siswa.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/03_AUTH_RBAC_MENU
 * - docs/05_PRESENSI
 *
 * Prinsip:
 * - tidak menggunakan soft delete;
 * - seluruh query dataset Presensi harus bounded;
 * - filtering, sorting, pagination, dan agregasi utama dilakukan database-side;
 * - authorization, dual-context Guru/Wali, time-window, geofence, snapshot actor,
 *   transaction bulk, dan keputusan revisi berada pada PresensiService.
 */
class PresensiModel extends Model
{
    protected $table            = 'presensi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'id_siswa',
        'nama_siswa_snapshot',
        'id_kelas',
        'id_tahun',
        'tanggal',
        'sesi',
        'status',
        'id_guru_input',
        'nama_guru_input_snapshot',
        'updated_by',
    ];

    protected $validationRules = [
        'id_siswa'                 => 'permit_empty|integer',
        'nama_siswa_snapshot'      => 'required|max_length[150]',
        'id_kelas'                 => 'required|integer',
        'id_tahun'                 => 'required|integer',
        'tanggal'                  => 'required|valid_date[Y-m-d]',
        'sesi'                     => 'required|in_list[Sesi Awal,Sesi Akhir]',
        'status'                   => 'required|in_list[Hadir,Sakit,Izin,Alpha]',
        'id_guru_input'            => 'permit_empty|integer',
        'nama_guru_input_snapshot' => 'permit_empty|max_length[150]',
        'updated_by'               => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'nama_siswa_snapshot' => [
            'required'   => 'Snapshot nama siswa wajib diisi.',
            'max_length' => 'Snapshot nama siswa maksimal 150 karakter.',
        ],
        'id_kelas' => [
            'required' => 'Kelas wajib diisi.',
            'integer'  => 'ID kelas tidak valid.',
        ],
        'id_tahun' => [
            'required' => 'Tahun ajaran wajib diisi.',
            'integer'  => 'ID tahun ajaran tidak valid.',
        ],
        'tanggal' => [
            'required'   => 'Tanggal Presensi wajib diisi.',
            'valid_date' => 'Format tanggal Presensi harus Y-m-d.',
        ],
        'sesi' => [
            'required' => 'Sesi Presensi wajib diisi.',
            'in_list'  => 'Sesi Presensi hanya boleh Sesi Awal atau Sesi Akhir.',
        ],
        'status' => [
            'required' => 'Status Presensi wajib diisi.',
            'in_list'  => 'Status Presensi hanya boleh Hadir, Sakit, Izin, atau Alpha.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Mengambil seluruh record Presensi untuk satu kelas, tanggal, dan sesi.
     *
     * Query ini bounded oleh kelas/tanggal/sesi.
     */
    public function getByKelasTanggalSesi(
        int $idKelas,
        string $tanggal,
        string $sesi
    ): array {
        $this->assertId($idKelas, 'ID kelas');
        $this->assertTanggal($tanggal);
        $this->assertSesi($sesi);

        return $this
            ->where('id_kelas', $idKelas)
            ->where('tanggal', $tanggal)
            ->where('sesi', $sesi)
            ->orderBy('nama_siswa_snapshot', 'ASC')
            ->findAll();
    }

    /**
     * Guard ringan untuk mengetahui apakah Presensi kelas/tanggal/sesi
     * sudah pernah tersimpan.
     *
     * PresensiService dapat menggunakan method ini untuk Guru biasa agar
     * keberadaan data diketahui tanpa mengirim isi Presensi tersimpan.
     */
    public function hasKelasTanggalSesi(
        int $idKelas,
        string $tanggal,
        string $sesi
    ): bool {
        $this->assertId($idKelas, 'ID kelas');
        $this->assertTanggal($tanggal);
        $this->assertSesi($sesi);

        return $this->builder()
            ->where('id_kelas', $idKelas)
            ->where('tanggal', $tanggal)
            ->where('sesi', $sesi)
            ->countAllResults() > 0;
    }

    /**
     * Mengambil satu record Presensi siswa pada business key canonical.
     */
    public function findByBusinessKey(
        int $idSiswa,
        int $idKelas,
        string $tanggal,
        string $sesi
    ): ?array {
        $this->assertId($idSiswa, 'ID siswa');
        $this->assertId($idKelas, 'ID kelas');
        $this->assertTanggal($tanggal);
        $this->assertSesi($sesi);

        return $this
            ->where('id_siswa', $idSiswa)
            ->where('id_kelas', $idKelas)
            ->where('tanggal', $tanggal)
            ->where('sesi', $sesi)
            ->first();
    }

    /**
     * Data satu kelas dalam satu periode.
     *
     * Digunakan sebagai sumber Matrix/rekap kelas. Dataset sudah dibatasi
     * tahun, kelas, sesi, dan rentang tanggal sebelum dikembalikan ke PHP.
     */
    public function getPeriodeKelas(
        int $idTahun,
        int $idKelas,
        string $tanggalMulai,
        string $tanggalSelesai,
        string $sesi = 'Sesi Awal'
    ): array {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertId($idKelas, 'ID kelas');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);
        $this->assertSesi($sesi);

        return $this
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('sesi', $sesi)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai)
            ->orderBy('tanggal', 'ASC')
            ->orderBy('nama_siswa_snapshot', 'ASC')
            ->findAll();
    }

    /**
     * Histori Presensi seorang siswa dengan pagination database-side.
     *
     * @param array<int, string>|null $status Null = semua status.
     */
    public function getHistoriSiswaPaged(
        int $idSiswa,
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit,
        int $offset = 0,
        ?string $sesi = null,
        ?array $status = null
    ): array {
        $this->assertId($idSiswa, 'ID siswa');
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);
        $this->assertPagination($limit, $offset);

        if ($sesi !== null) {
            $this->assertSesi($sesi);
        }

        $status = $this->normalizeStatusFilter($status);

        $builder = $this
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai);

        if ($sesi !== null) {
            $builder->where('sesi', $sesi);
        }

        if ($status !== null) {
            $builder->whereIn('status', $status);
        }

        return $builder
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit, $offset);
    }

    /**
     * Total row untuk pagination histori siswa dengan filter yang sama.
     *
     * @param array<int, string>|null $status
     */
    public function countHistoriSiswa(
        int $idSiswa,
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai,
        ?string $sesi = null,
        ?array $status = null
    ): int {
        $this->assertId($idSiswa, 'ID siswa');
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);

        if ($sesi !== null) {
            $this->assertSesi($sesi);
        }

        $status = $this->normalizeStatusFilter($status);

        $builder = $this->builder()
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai);

        if ($sesi !== null) {
            $builder->where('sesi', $sesi);
        }

        if ($status !== null) {
            $builder->whereIn('status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Histori Presensi kelas dengan server-side pagination.
     *
     * Query ini ditujukan untuk Admin/Operator/Wali/Pimpinan setelah scope
     * ditentukan oleh Service.
     *
     * @param array<int, string>|null $status
     */
    public function getHistoriKelasPaged(
        int $idTahun,
        int $idKelas,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit,
        int $offset = 0,
        ?string $sesi = null,
        ?array $status = null
    ): array {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertId($idKelas, 'ID kelas');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);
        $this->assertPagination($limit, $offset);

        if ($sesi !== null) {
            $this->assertSesi($sesi);
        }

        $status = $this->normalizeStatusFilter($status);

        $builder = $this
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai);

        if ($sesi !== null) {
            $builder->where('sesi', $sesi);
        }

        if ($status !== null) {
            $builder->whereIn('status', $status);
        }

        return $builder
            ->orderBy('tanggal', 'DESC')
            ->orderBy('nama_siswa_snapshot', 'ASC')
            ->findAll($limit, $offset);
    }

    /**
     * Total row untuk pagination histori kelas.
     *
     * @param array<int, string>|null $status
     */
    public function countHistoriKelas(
        int $idTahun,
        int $idKelas,
        string $tanggalMulai,
        string $tanggalSelesai,
        ?string $sesi = null,
        ?array $status = null
    ): int {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertId($idKelas, 'ID kelas');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);

        if ($sesi !== null) {
            $this->assertSesi($sesi);
        }

        $status = $this->normalizeStatusFilter($status);

        $builder = $this->builder()
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai);

        if ($sesi !== null) {
            $builder->where('sesi', $sesi);
        }

        if ($status !== null) {
            $builder->whereIn('status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Rekap status satu kelas/periode dihitung langsung oleh database.
     */
    public function getRekapStatusKelas(
        int $idTahun,
        int $idKelas,
        string $tanggalMulai,
        string $tanggalSelesai,
        string $sesi = 'Sesi Awal'
    ): array {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertId($idKelas, 'ID kelas');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);
        $this->assertSesi($sesi);

        return $this->builder()
            ->select('status, COUNT(*) AS total')
            ->where('id_tahun', $idTahun)
            ->where('id_kelas', $idKelas)
            ->where('sesi', $sesi)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai)
            ->groupBy('status')
            ->orderBy(
                "FIELD(status,'Hadir','Sakit','Izin','Alpha')",
                '',
                false
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Query sumber EWS Alpha.
     *
     * Sesi selalu Sesi Awal sesuai dokumen canonical.
     * $idKelas dapat null untuk scope SEMUA. Bila array diberikan, array
     * tersebut harus sudah merupakan kelas yang diizinkan oleh Service.
     *
     * @param array<int, int|string>|null $idKelas
     */
    public function getEwsAlpha(
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai,
        ?array $idKelas = null,
        int $minimumAlpha = 3
    ): array {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);

        if ($minimumAlpha < 1) {
            throw new InvalidArgumentException('Minimum Alpha harus minimal 1.');
        }

        if ($idKelas !== null) {
            $idKelas = $this->normalizeIdList($idKelas);

            if ($idKelas === []) {
                return [];
            }
        }

        $builder = $this->builder()
            ->select(
                'id_siswa, MAX(nama_siswa_snapshot) AS nama_siswa_snapshot, COUNT(*) AS total_alpha'
            )
            ->where('id_tahun', $idTahun)
            ->where('sesi', 'Sesi Awal')
            ->where('status', 'Alpha')
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai)
            ->where('id_siswa IS NOT NULL', null, false);

        if ($idKelas !== null) {
            $builder->whereIn('id_kelas', $idKelas);
        }

        return $builder
            ->groupBy('id_siswa')
            ->having('COUNT(*) >=', $minimumAlpha, false)
            ->orderBy('total_alpha', 'DESC')
            ->orderBy('nama_siswa_snapshot', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function assertId(int $id, string $label): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
    }

    /**
     * Validasi format tanggal tanpa bergantung pada waktu client.
     */
    private function assertTanggal(string $tanggal): void
    {
        $tanggal = trim($tanggal);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $tanggal
        ) {
            throw new InvalidArgumentException(
                'Tanggal harus menggunakan format Y-m-d yang valid.'
            );
        }
    }

    private function assertPeriode(
        string $tanggalMulai,
        string $tanggalSelesai
    ): void {
        $this->assertTanggal($tanggalMulai);
        $this->assertTanggal($tanggalSelesai);

        if ($tanggalMulai > $tanggalSelesai) {
            throw new InvalidArgumentException(
                'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.'
            );
        }
    }

    private function assertSesi(string $sesi): void
    {
        if (! in_array($sesi, ['Sesi Awal', 'Sesi Akhir'], true)) {
            throw new InvalidArgumentException(
                'Sesi Presensi hanya boleh Sesi Awal atau Sesi Akhir.'
            );
        }
    }

    private function assertPagination(int $limit, int $offset): void
    {
        if ($limit < 1 || $limit > 500) {
            throw new InvalidArgumentException(
                'Limit pagination harus berada antara 1 sampai 500.'
            );
        }

        if ($offset < 0) {
            throw new InvalidArgumentException(
                'Offset pagination tidak boleh negatif.'
            );
        }
    }

    /**
     * @param array<int, string>|null $status
     * @return array<int, string>|null
     */
    private function normalizeStatusFilter(?array $status): ?array
    {
        if ($status === null) {
            return null;
        }

        $allowed = ['Hadir', 'Sakit', 'Izin', 'Alpha'];
        $normalized = [];

        foreach ($status as $value) {
            $value = trim((string) $value);

            if (! in_array($value, $allowed, true)) {
                throw new InvalidArgumentException(
                    'Filter status Presensi mengandung nilai yang tidak valid.'
                );
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private function normalizeIdList(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            if (! is_numeric($id)) {
                throw new InvalidArgumentException('Daftar ID kelas tidak valid.');
            }

            $id = (int) $id;

            if ($id < 1) {
                throw new InvalidArgumentException('Daftar ID kelas tidak valid.');
            }

            $normalized[$id] = $id;
        }

        return array_values($normalized);
    }
}
