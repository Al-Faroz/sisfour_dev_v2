<?php

namespace App\Services;

/**
 * F10 semester-aware integration layer.
 *
 * Siapkan Genap adalah satu transaksi atomic: membuat Genap, menyalin baseline
 * operasional, memindahkan histori aktif, lalu mengaktifkan Genap. Aktivasi
 * manual Genap untuk tahun yang sama ditolak agar workflow ini tidak dilewati.
 */
class TahunAjaranSemesterService extends TahunAjaranIntegrityService
{
    public function create(array $data): array
    {
        if (($data['mode'] ?? '') === 'prepare_next_semester') {
            if (! $this->canManageSemester()) {
                return $this->forbiddenSemester();
            }

            return (new SemesterTransitionService())
                ->transitionFromActive();
        }

        return parent::create($data);
    }

    public function aktifkan(int $id): array
    {
        if (! $this->canManageSemester()) {
            return $this->forbiddenSemester();
        }

        $source = $this->db
            ->table('tahun_ajaran')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $target = $this->db
            ->table('tahun_ajaran')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (
            $source !== null
            && $target !== null
            && (string) $source['semester'] === 'Ganjil'
            && (string) $target['semester'] === 'Genap'
            && (string) $source['nama_tahun'] === (string) $target['nama_tahun']
        ) {
            return [
                'success' => false,
                'code' => 'USE_PREPARE_GENAP',
                'message' => 'Semester Genap pada tahun pelajaran yang sama tidak boleh diaktifkan manual. Gunakan tombol Siapkan Genap agar Kelas, Anggota, Mapping Wali, Jadwal, dan histori diproses secara atomic.',
            ];
        }

        return parent::aktifkan($id);
    }

    private function canManageSemester(): bool
    {
        $userId = (int) (session()->get('user_id') ?? 0);

        return $userId > 0
            && $this->authService->resolveScope(
                'master_tahun_ajaran.manage',
                $userId
            ) === 'SEMUA';
    }

    private function forbiddenSemester(): array
    {
        return [
            'success' => false,
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak mengelola Master Tahun Ajaran.',
        ];
    }
}
