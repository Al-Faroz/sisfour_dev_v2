<?php

namespace App\Services;

use App\Models\TahunAjaranModel;
use Config\Database;
use Throwable;

/**
 * TahunAjaranService
 *
 * Business logic Master Tahun Ajaran.
 *
 * Acuan docs/04_MASTER_DATA §3.3:
 * - semester wajib Ganjil/Genap;
 * - hanya satu tahun ajaran aktif;
 * - mengaktifkan satu otomatis menonaktifkan yang sebelumnya;
 * - tabel memakai soft delete + recycle bin.
 */
class TahunAjaranService
{
    protected TahunAjaranModel $tahunModel;
    protected $db;

    public function __construct()
    {
        $this->tahunModel = new TahunAjaranModel();
        $this->db = Database::connect();
    }

    public function getList(bool $deletedOnly = false): array
    {
        $builder = $this->db
            ->table('tahun_ajaran ta')
            ->select(
                'ta.id, ta.nama_tahun, ta.semester, ta.status_aktif, ' .
                'ta.deleted_at, ta.created_at, ta.updated_at'
            )
            ->select(
                '(SELECT COUNT(*) FROM kelas k ' .
                'WHERE k.id_tahun = ta.id AND k.deleted_at IS NULL) AS jumlah_kelas',
                false
            )
            ->select(
                '(SELECT COUNT(*) FROM anggota_kelas ak ' .
                'WHERE ak.id_tahun = ta.id) AS jumlah_anggota',
                false
            )
            ->select(
                '(SELECT COUNT(*) FROM jadwal_guru jg ' .
                'WHERE jg.id_tahun = ta.id) AS jumlah_jadwal',
                false
            );

        if ($deletedOnly) {
            $builder->where('ta.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('ta.deleted_at', null);
        }

        return $builder
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy(
                "FIELD(ta.semester, 'Ganjil', 'Genap')",
                '',
                false
            )
            ->get()
            ->getResultArray();
    }

    public function create(array $data): array
    {
        $payload = $this->normalizePayload($data);

        $error = $this->validatePayload($payload);
        if ($error !== null) {
            return $error;
        }

        if (
            $this->tahunModel->tahunSemesterDipakai(
                $payload['nama_tahun'],
                $payload['semester']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kombinasi tahun ajaran dan semester sudah pernah dibuat. Jika berada di Recycle Bin, pulihkan data lama.',
            ];
        }

        $payload['status_aktif'] = 0;

        $id = $this->tahunModel->insert($payload, true);

        if ($id === false) {
            return [
                'success' => false,
                'message' => implode(' ', $this->tahunModel->errors())
                    ?: 'Tahun ajaran gagal disimpan.',
            ];
        }

        $this->logActivity(
            'CREATE',
            'Master Tahun Ajaran',
            sprintf(
                'Menambahkan tahun ajaran %s - %s.',
                $payload['nama_tahun'],
                $payload['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Tahun ajaran berhasil ditambahkan. Status awal Nonaktif.',
            'id' => (int) $id,
        ];
    }

    public function update(int $id, array $data): array
    {
        $tahun = $this->tahunModel->find($id);

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak ditemukan.',
            ];
        }

        $payload = $this->normalizePayload($data);

        $error = $this->validatePayload($payload);
        if ($error !== null) {
            return $error;
        }

        if (
            $this->tahunModel->tahunSemesterDipakai(
                $payload['nama_tahun'],
                $payload['semester'],
                $id
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kombinasi tahun ajaran dan semester sudah digunakan.',
            ];
        }

        /*
         * status_aktif tidak diedit dari form.
         * Aktivasi hanya melalui endpoint aktifkan agar invariant satu-aktif
         * selalu terjaga.
         */
        if (!$this->tahunModel->update($id, [
            'nama_tahun' => $payload['nama_tahun'],
            'semester' => $payload['semester'],
        ])) {
            return [
                'success' => false,
                'message' => implode(' ', $this->tahunModel->errors())
                    ?: 'Tahun ajaran gagal diperbarui.',
            ];
        }

        $this->logActivity(
            'UPDATE',
            'Master Tahun Ajaran',
            sprintf(
                'Memperbarui tahun ajaran ID %d menjadi %s - %s.',
                $id,
                $payload['nama_tahun'],
                $payload['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Tahun ajaran berhasil diperbarui.',
        ];
    }

    public function aktifkan(int $id): array
    {
        $tahun = $this->tahunModel->find($id);

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak ditemukan.',
            ];
        }

        if ((int) $tahun['status_aktif'] === 1) {
            return [
                'success' => true,
                'message' => 'Tahun ajaran tersebut sudah aktif.',
            ];
        }

        $this->db->transBegin();

        try {
            /*
             * Query Builder langsung digunakan agar seluruh baris aktif,
             * termasuk jika pernah terjadi data abnormal >1 aktif,
             * dinonaktifkan dalam transaksi yang sama.
             */
            $this->db
                ->table('tahun_ajaran')
                ->where('status_aktif', 1)
                ->where('id !=', $id)
                ->update([
                    'status_aktif' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $updated = $this->db
                ->table('tahun_ajaran')
                ->where('id', $id)
                ->where('deleted_at', null)
                ->update([
                    'status_aktif' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (!$updated || $this->db->affectedRows() < 1) {
                throw new \RuntimeException(
                    'Tahun ajaran gagal diaktifkan.'
                );
            }

            $this->logActivity(
                'AKTIFKAN',
                'Master Tahun Ajaran',
                sprintf(
                    'Mengaktifkan tahun ajaran %s - %s.',
                    $tahun['nama_tahun'],
                    $tahun['semester']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi aktivasi tahun ajaran gagal.'
                );
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Tahun ajaran berhasil diaktifkan. Tahun ajaran aktif sebelumnya otomatis dinonaktifkan.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function delete(int $id): array
    {
        $tahun = $this->tahunModel->find($id);

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak ditemukan.',
            ];
        }

        if ((int) $tahun['status_aktif'] === 1) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran yang sedang aktif tidak dapat dihapus. Aktifkan tahun ajaran lain terlebih dahulu.',
            ];
        }

        $dependencies = $this->getDependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran belum dapat dihapus karena masih digunakan: '
                    . implode(', ', $dependencies) . '.',
            ];
        }

        if (!$this->tahunModel->delete($id)) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran gagal dipindahkan ke Recycle Bin.',
            ];
        }

        $this->logActivity(
            'DELETE',
            'Master Tahun Ajaran',
            sprintf(
                'Memindahkan tahun ajaran %s - %s ke Recycle Bin.',
                $tahun['nama_tahun'],
                $tahun['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Tahun ajaran dipindahkan ke Recycle Bin.',
        ];
    }

    public function restore(int $id): array
    {
        $tahun = $this->tahunModel
            ->withDeleted()
            ->find($id);

        if ($tahun === null || empty($tahun['deleted_at'])) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran pada Recycle Bin tidak ditemukan.',
            ];
        }

        if (
            $this->tahunModel->tahunSemesterDipakai(
                $tahun['nama_tahun'],
                $tahun['semester'],
                $id
            )
        ) {
            return [
                'success' => false,
                'message' => 'Restore gagal karena kombinasi tahun ajaran dan semester sudah digunakan data lain.',
            ];
        }

        $updated = $this->db
            ->table('tahun_ajaran')
            ->where('id', $id)
            ->update([
                'deleted_at' => null,
                'status_aktif' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran gagal dipulihkan.',
            ];
        }

        $this->logActivity(
            'RESTORE',
            'Master Tahun Ajaran',
            sprintf(
                'Memulihkan tahun ajaran %s - %s.',
                $tahun['nama_tahun'],
                $tahun['semester']
            )
        );

        return [
            'success' => true,
            'message' => 'Tahun ajaran berhasil dipulihkan dalam status Nonaktif.',
        ];
    }

    public function forceDelete(int $id): array
    {
        $tahun = $this->tahunModel
            ->withDeleted()
            ->find($id);

        if ($tahun === null || empty($tahun['deleted_at'])) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran pada Recycle Bin tidak ditemukan.',
            ];
        }

        $this->db->transBegin();

        try {
            $this->db
                ->table('tahun_ajaran')
                ->where('id', $id)
                ->delete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Tahun ajaran masih direferensikan data lain.'
                );
            }

            $this->logActivity(
                'FORCE_DELETE',
                'Master Tahun Ajaran',
                sprintf(
                    'Menghapus permanen tahun ajaran %s - %s.',
                    $tahun['nama_tahun'],
                    $tahun['semester']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Tahun ajaran berhasil dihapus permanen.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Hapus permanen gagal. Tahun ajaran masih direferensikan kelas, anggota kelas, wali kelas, jadwal, presensi, histori siswa, atau data lain.',
            ];
        }
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'nama_tahun' => trim((string) ($data['nama_tahun'] ?? '')),
            'semester' => trim((string) ($data['semester'] ?? '')),
        ];
    }

    protected function validatePayload(array $payload): ?array
    {
        if ($payload['nama_tahun'] === '') {
            return [
                'success' => false,
                'message' => 'Tahun ajaran wajib diisi.',
            ];
        }

        if (mb_strlen($payload['nama_tahun']) > 20) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran maksimal 20 karakter.',
            ];
        }

        if (
            !preg_match(
                '/^\d{4}\/\d{4}$/',
                $payload['nama_tahun']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Format tahun ajaran harus YYYY/YYYY, contoh 2026/2027.',
            ];
        }

        [$awal, $akhir] = array_map(
            'intval',
            explode('/', $payload['nama_tahun'])
        );

        if ($akhir !== $awal + 1) {
            return [
                'success' => false,
                'message' => 'Tahun kedua harus satu tahun setelah tahun pertama, contoh 2026/2027.',
            ];
        }

        if (
            !in_array(
                $payload['semester'],
                ['Ganjil', 'Genap'],
                true
            )
        ) {
            return [
                'success' => false,
                'message' => 'Semester harus Ganjil atau Genap.',
            ];
        }

        return null;
    }

    /**
     * Soft delete tahun ajaran yang masih dipakai akan membuat child
     * tetap hidup tetapi parent hilang dari dropdown. Karena itu
     * penghapusan dibatasi saat dependency masih ada.
     */
    protected function getDependencies(int $idTahun): array
    {
        $map = [
            'kelas' => 'kelas',
            'anggota_kelas' => 'anggota kelas',
            'mapping_wali_kelas' => 'mapping wali kelas',
            'jadwal_guru' => 'jadwal guru',
            'riwayat_siswa' => 'riwayat siswa',
            'presensi' => 'presensi siswa',
            'presensi_mengajar' => 'presensi mengajar',
        ];

        $dependencies = [];

        foreach ($map as $table => $label) {
            if (
                $this->db
                    ->table($table)
                    ->where('id_tahun', $idTahun)
                    ->countAllResults() > 0
            ) {
                $dependencies[] = $label;
            }
        }

        return $dependencies;
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
                'id_user' => $idUser ? (int) $idUser : null,
                'aksi' => $aksi,
                'modul' => $modul,
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
            ]);
    }
}
