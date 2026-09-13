<?php

namespace App\Services;

/**
 * F10 semester-aware integration layer.
 *
 * Menjaga seluruh hardening TahunAjaranIntegrityService, lalu menambahkan
 * workflow Ganjil -> Genap tanpa mengubah kontrak route/controller.
 */
class TahunAjaranSemesterService extends TahunAjaranIntegrityService
{
    public function create(array $data): array
    {
        if (($data['mode'] ?? '') === 'prepare_next_semester') {
            if (! $this->canManageSemester()) {
                return $this->forbiddenSemester();
            }

            $copyWali = (string) ($data['copy_wali'] ?? '1') !== '0';

            return (new SemesterTransitionService())
                ->prepareFromActive($copyWali);
        }

        return parent::create($data);
    }

    public function aktifkan(int $id): array
    {
        if (! $this->canManageSemester()) {
            return $this->forbiddenSemester();
        }

        $coverage = $this->semesterScheduleCoverageCheck($id);

        if ($coverage !== null && ! $coverage['ready']) {
            return [
                'success' => false,
                'code' => 'SEMESTER_SCHEDULE_INCOMPLETE',
                'message' => $coverage['message'],
                'coverage' => $coverage,
            ];
        }

        $transition = (new SemesterTransitionService())
            ->activateIfSemesterTransition($id);

        if ($transition !== null) {
            return $transition;
        }

        return parent::aktifkan($id);
    }

    /**
     * Return null untuk aktivasi biasa yang bukan Ganjil -> Genap tahun sama.
     */
    private function semesterScheduleCoverageCheck(int $targetId): ?array
    {
        $source = $this->db
            ->table('tahun_ajaran')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $target = $this->db
            ->table('tahun_ajaran')
            ->where('id', $targetId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (
            $source === null
            || $target === null
            || (string) $source['semester'] !== 'Ganjil'
            || (string) $target['semester'] !== 'Genap'
            || (string) $source['nama_tahun'] !== (string) $target['nama_tahun']
        ) {
            return null;
        }

        $sourceClasses = $this->scheduledClassNames((int) $source['id']);
        $targetClasses = $this->scheduledClassNames((int) $target['id']);

        if ($sourceClasses === []) {
            return [
                'ready' => true,
                'message' => 'Semester sumber tidak memiliki Jadwal aktif.',
                'source_classes' => [],
                'target_classes' => $targetClasses,
            ];
        }

        if ($sourceClasses !== $targetClasses) {
            $missing = array_values(
                array_diff($sourceClasses, $targetClasses)
            );

            return [
                'ready' => false,
                'message' => 'Semester Genap belum siap diaktifkan karena cakupan kelas pada Jadwal Guru belum lengkap.'
                    . ($missing !== []
                        ? ' Kelas tanpa Jadwal Genap: ' . implode(', ', $missing) . '.'
                        : ''),
                'source_classes' => $sourceClasses,
                'target_classes' => $targetClasses,
                'missing_classes' => $missing,
            ];
        }

        return [
            'ready' => true,
            'message' => 'Cakupan kelas Jadwal Semester Genap lengkap.',
            'source_classes' => $sourceClasses,
            'target_classes' => $targetClasses,
        ];
    }

    private function scheduledClassNames(int $idTahun): array
    {
        $rows = $this->db
            ->table('jadwal_guru jg')
            ->distinct()
            ->select('k.nama_kelas')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.status_jadwal', 'Aktif')
            ->where('k.deleted_at', null)
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_map(
            static fn (array $row): string => (string) $row['nama_kelas'],
            $rows
        ));
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
