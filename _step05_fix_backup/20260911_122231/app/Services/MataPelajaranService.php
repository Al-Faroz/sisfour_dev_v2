<?php

namespace App\Services;

use App\Models\MataPelajaranModel;
use Config\Database;
use Throwable;

/**
 * MataPelajaranService
 *
 * Business logic Master Mata Pelajaran.
 *
 * Aturan utama:
 * - nama_mapel wajib;
 * - kode_mapel wajib, unik, maksimal 10 karakter;
 * - kode dinormalisasi uppercase;
 * - hard delete;
 * - delete ditolak bila mapel sudah digunakan pada jadwal_guru.
 */
class MataPelajaranService
{
    protected MataPelajaranModel $mapelModel;
    protected $db;

    public function __construct()
    {
        $this->mapelModel = new MataPelajaranModel();
        $this->db = Database::connect();
    }

    public function getList(array $filter = []): array
    {
        $builder = $this->db
            ->table('mata_pelajaran mp')
            ->select(
                'mp.id, mp.nama_mapel, mp.kode_mapel'
            )
            ->select(
                '(SELECT COUNT(*) FROM jadwal_guru jg ' .
                'WHERE jg.id_mapel = mp.id) AS jumlah_jadwal',
                false
            );

        $nama = trim((string) ($filter['nama_mapel'] ?? ''));
        if ($nama !== '') {
            $builder->like('mp.nama_mapel', $nama);
        }

        $kode = strtoupper(
            trim((string) ($filter['kode_mapel'] ?? ''))
        );

        if ($kode !== '') {
            $builder->like('mp.kode_mapel', $kode);
        }

        return $builder
            ->orderBy('mp.nama_mapel', 'ASC')
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
            $this->mapelModel->kodeDipakai(
                $payload['kode_mapel']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kode mata pelajaran sudah digunakan.',
            ];
        }

        $id = $this->mapelModel->insert(
            $payload,
            true
        );

        if ($id === false) {
            return [
                'success' => false,
                'message' => implode(
                    ' ',
                    $this->mapelModel->errors()
                ) ?: 'Mata pelajaran gagal disimpan.',
            ];
        }

        $this->logActivity(
            'CREATE',
            'Master Mata Pelajaran',
            sprintf(
                'Menambahkan mata pelajaran %s (%s).',
                $payload['nama_mapel'],
                $payload['kode_mapel']
            )
        );

        return [
            'success' => true,
            'message' => 'Mata pelajaran berhasil ditambahkan.',
            'id' => (int) $id,
        ];
    }

    public function update(int $id, array $data): array
    {
        $mapel = $this->mapelModel->find($id);

        if ($mapel === null) {
            return [
                'success' => false,
                'message' => 'Mata pelajaran tidak ditemukan.',
            ];
        }

        $payload = $this->normalizePayload($data);

        $error = $this->validatePayload($payload);
        if ($error !== null) {
            return $error;
        }

        if (
            $this->mapelModel->kodeDipakai(
                $payload['kode_mapel'],
                $id
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kode mata pelajaran sudah digunakan.',
            ];
        }

        if (
            !$this->mapelModel->update(
                $id,
                $payload
            )
        ) {
            return [
                'success' => false,
                'message' => implode(
                    ' ',
                    $this->mapelModel->errors()
                ) ?: 'Mata pelajaran gagal diperbarui.',
            ];
        }

        $this->logActivity(
            'UPDATE',
            'Master Mata Pelajaran',
            sprintf(
                'Memperbarui mata pelajaran ID %d menjadi %s (%s).',
                $id,
                $payload['nama_mapel'],
                $payload['kode_mapel']
            )
        );

        return [
            'success' => true,
            'message' => 'Mata pelajaran berhasil diperbarui.',
        ];
    }

    public function delete(int $id): array
    {
        $mapel = $this->mapelModel->find($id);

        if ($mapel === null) {
            return [
                'success' => false,
                'message' => 'Mata pelajaran tidak ditemukan.',
            ];
        }

        $jumlahJadwal = $this->db
            ->table('jadwal_guru')
            ->where('id_mapel', $id)
            ->countAllResults();

        if ($jumlahJadwal > 0) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Mata pelajaran tidak dapat dihapus karena sudah digunakan pada %d data jadwal guru.',
                    $jumlahJadwal
                ),
            ];
        }

        $this->db->transBegin();

        try {
            $deleted = $this->db
                ->table('mata_pelajaran')
                ->where('id', $id)
                ->delete();

            if (
                !$deleted
                || $this->db->transStatus() === false
            ) {
                throw new \RuntimeException(
                    'Mata pelajaran gagal dihapus.'
                );
            }

            $this->logActivity(
                'DELETE',
                'Master Mata Pelajaran',
                sprintf(
                    'Menghapus mata pelajaran %s (%s).',
                    $mapel['nama_mapel'],
                    $mapel['kode_mapel']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Mata pelajaran berhasil dihapus.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Mata pelajaran gagal dihapus. Pastikan data tidak sedang digunakan oleh jadwal guru.',
            ];
        }
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'nama_mapel' => trim(
                (string) ($data['nama_mapel'] ?? '')
            ),
            'kode_mapel' => strtoupper(
                trim(
                    (string) ($data['kode_mapel'] ?? '')
                )
            ),
        ];
    }

    protected function validatePayload(array $payload): ?array
    {
        if ($payload['nama_mapel'] === '') {
            return [
                'success' => false,
                'message' => 'Nama mata pelajaran wajib diisi.',
            ];
        }

        if (mb_strlen($payload['nama_mapel']) > 100) {
            return [
                'success' => false,
                'message' => 'Nama mata pelajaran maksimal 100 karakter.',
            ];
        }

        if ($payload['kode_mapel'] === '') {
            return [
                'success' => false,
                'message' => 'Kode mata pelajaran wajib diisi.',
            ];
        }

        if (mb_strlen($payload['kode_mapel']) > 10) {
            return [
                'success' => false,
                'message' => 'Kode mata pelajaran maksimal 10 karakter.',
            ];
        }

        if (
            !preg_match(
                '/^[A-Z0-9_-]+$/',
                $payload['kode_mapel']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kode mata pelajaran hanya boleh berisi huruf, angka, underscore, atau tanda minus.',
            ];
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
