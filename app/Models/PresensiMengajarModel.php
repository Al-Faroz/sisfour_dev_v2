<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * PresensiMengajarModel
 *
 * Data Presensi Mengajar / Jurnal Guru.
 *
 * Acuan:
 * - docs/02_DATABASE
 * - docs/03_AUTH_RBAC_MENU
 * - docs/05_PRESENSI
 *
 * Prinsip:
 * - tidak menggunakan soft delete;
 * - unique business key: (id_jadwal, tanggal);
 * - query histori selalu bounded;
 * - authorization, ownership, time-window, geofence, dan revisi berada di Service.
 */
class PresensiMengajarModel extends Model
{
    protected $table            = 'presensi_mengajar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'id_guru',
        'nama_guru_snapshot',
        'id_jadwal',
        'id_kelas',
        'id_tahun',
        'tanggal',
        'status',
        'materi',
        'updated_by',
    ];

    protected $validationRules = [
        'id_guru'             => 'permit_empty|integer',
        'nama_guru_snapshot'  => 'required|max_length[150]',
        'id_jadwal'           => 'required|integer',
        'id_kelas'            => 'required|integer',
        'id_tahun'            => 'required|integer',
        'tanggal'             => 'required|valid_date[Y-m-d]',
        'status'              => 'required|in_list[Hadir,Izin,Sakit]',
        'materi'              => 'required',
        'updated_by'          => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'nama_guru_snapshot' => [
            'required'   => 'Snapshot nama Guru wajib diisi.',
            'max_length' => 'Snapshot nama Guru maksimal 150 karakter.',
        ],
        'id_jadwal' => [
            'required' => 'Jadwal wajib dipilih.',
            'integer'  => 'ID Jadwal tidak valid.',
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
            'required'   => 'Tanggal Jurnal wajib diisi.',
            'valid_date' => 'Format tanggal Jurnal harus Y-m-d.',
        ],
        'status' => [
            'required' => 'Status Jurnal wajib diisi.',
            'in_list'  => 'Status Jurnal hanya boleh Hadir, Izin, atau Sakit.',
        ],
        'materi' => [
            'required' => 'Materi/keterangan Jurnal wajib diisi.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Mengambil satu Jurnal berdasarkan unique business key.
     */
    public function findByJadwalTanggal(int $idJadwal, string $tanggal): ?array
    {
        $this->assertId($idJadwal, 'ID Jadwal');
        $this->assertTanggal($tanggal);

        return $this
            ->where('id_jadwal', $idJadwal)
            ->where('tanggal', $tanggal)
            ->first();
    }

    /**
     * Histori Jurnal bounded dan server-side pagination.
     *
     * $idGuru null berarti seluruh Guru setelah Service memastikan scope SEMUA.
     */
    public function getHistoriPaged(
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai,
        int $limit,
        int $offset = 0,
        ?int $idGuru = null,
        ?string $status = null
    ): array {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);
        $this->assertPagination($limit, $offset);

        if ($idGuru !== null) {
            $this->assertId($idGuru, 'ID Guru');
        }

        if ($status !== null) {
            $this->assertStatus($status);
        }

        $builder = $this->db
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
                'pm.created_at',
                'pm.updated_at',
                'pm.updated_by',
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
            ->where('pm.id_tahun', $idTahun)
            ->where('pm.tanggal >=', $tanggalMulai)
            ->where('pm.tanggal <=', $tanggalSelesai);

        if ($idGuru !== null) {
            $builder->where('pm.id_guru', $idGuru);
        }

        if ($status !== null) {
            $builder->where('pm.status', $status);
        }

        return $builder
            ->orderBy('pm.tanggal', 'DESC')
            ->orderBy('jg.jam_mulai', 'ASC')
            ->orderBy('pm.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Total row untuk pagination histori dengan filter identik.
     */
    public function countHistori(
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai,
        ?int $idGuru = null,
        ?string $status = null
    ): int {
        $this->assertId($idTahun, 'ID tahun ajaran');
        $this->assertPeriode($tanggalMulai, $tanggalSelesai);

        if ($idGuru !== null) {
            $this->assertId($idGuru, 'ID Guru');
        }

        if ($status !== null) {
            $this->assertStatus($status);
        }

        $builder = $this->db
            ->table('presensi_mengajar')
            ->where('id_tahun', $idTahun)
            ->where('tanggal >=', $tanggalMulai)
            ->where('tanggal <=', $tanggalSelesai);

        if ($idGuru !== null) {
            $builder->where('id_guru', $idGuru);
        }

        if ($status !== null) {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    private function assertId(int $id, string $label): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
    }

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
            throw new InvalidArgumentException('Tanggal harus menggunakan format Y-m-d yang valid.');
        }
    }

    private function assertPeriode(string $tanggalMulai, string $tanggalSelesai): void
    {
        $this->assertTanggal($tanggalMulai);
        $this->assertTanggal($tanggalSelesai);

        if ($tanggalMulai > $tanggalSelesai) {
            throw new InvalidArgumentException(
                'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.'
            );
        }
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['Hadir', 'Izin', 'Sakit'], true)) {
            throw new InvalidArgumentException('Status Jurnal tidak valid.');
        }
    }

    private function assertPagination(int $limit, int $offset): void
    {
        if ($limit < 1 || $limit > 500) {
            throw new InvalidArgumentException('Limit harus berada antara 1 sampai 500.');
        }

        if ($offset < 0) {
            throw new InvalidArgumentException('Offset tidak boleh negatif.');
        }
    }
}
