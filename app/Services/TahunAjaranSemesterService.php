<?php

namespace App\Services;

/**
 * F10 semester-aware integration layer.
 *
 * Siapkan Genap adalah satu transaksi atomic: membuat Genap, menyalin baseline
 * operasional, memindahkan histori aktif, lalu mengaktifkan Genap. Jalur create,
 * update, dan aktivasi manual Genap tahun yang sama ditutup agar workflow ini
 * tidak dapat dilewati.
 */
class TahunAjaranSemesterService extends TahunAjaranIntegrityService
{
    public function create(array $data): array
    {
        if (! $this->canManageSemester()) {
            return $this->forbiddenSemester();
        }

        if (($data['mode'] ?? '') === 'prepare_next_semester') {
            return (new SemesterTransitionService())
                ->transitionFromActive();
        }

        if ($this->isManualSameYearGenap($data)) {
            return $this->usePrepareGenap();
        }

        return parent::create($data);
    }

    public function update(int $id, array $data): array
    {
        if (! $this->canManageSemester()) {
            return $this->forbiddenSemester();
        }

        if ($this->isManualSameYearGenap($data)) {
            return $this->usePrepareGenap();
        }

        return parent::update($id, $data);
    }

    public function aktifkan(int $id): array
    {
        if (! $this->canManageSemester()) {
            return $this->forbiddenSemester();
        }

        $source = $this->activeYear();
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
            return $this->usePrepareGenap();
        }

        return parent::aktifkan($id);
    }

    private function isManualSameYearGenap(array $data): bool
    {
        $namaTahun = trim((string) ($data['nama_tahun'] ?? ''));
        $semester = trim((string) ($data['semester'] ?? ''));

        if ($namaTahun === '' || $semester !== 'Genap') {
            return false;
        }

        $source = $this->activeYear();

        return $source !== null
            && (string) $source['semester'] === 'Ganjil'
            && (string) $source['nama_tahun'] === $namaTahun;
    }

    private function activeYear(): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
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

    private function usePrepareGenap(): array
    {
        return [
            'success' => false,
            'code' => 'USE_PREPARE_GENAP',
            'message' => 'Semester Genap untuk tahun pelajaran yang sedang aktif tidak dibuat atau diaktifkan manual. Gunakan tombol Siapkan Genap agar Kelas, Anggota, Mapping Wali, Jadwal, histori, dan status semester diproses secara atomic.',
        ];
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
