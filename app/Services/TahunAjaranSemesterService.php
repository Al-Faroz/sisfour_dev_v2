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

        $transition = (new SemesterTransitionService())
            ->activateIfSemesterTransition($id);

        if ($transition !== null) {
            return $transition;
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
