<?php

namespace App\Services;

/**
 * Integrity guard untuk permanent delete Tahun Ajaran.
 *
 * Dependency dicek ulang saat force-delete karena sebagian tabel legacy yang
 * membawa id_tahun tidak memiliki foreign key database.
 */
class TahunAjaranIntegrityService extends TahunAjaranService
{
    public function forceDelete(int $id): array
    {
        if (! $this->canManageIntegrity()) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak mengelola Master Tahun Ajaran.',
            ];
        }

        $dependencies = $this->getDependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'code' => 'DATA_IN_USE',
                'message' => 'Hapus permanen ditolak karena Tahun Ajaran masih digunakan: '
                    . implode(', ', $dependencies)
                    . '. Pertahankan data di Recycle Bin agar histori akademik tidak menjadi yatim.',
            ];
        }

        return parent::forceDelete($id);
    }

    private function canManageIntegrity(): bool
    {
        $userId = (int) (session()->get('user_id') ?? 0);

        return $userId > 0
            && $this->authService->resolveScope(
                'master_tahun_ajaran.manage',
                $userId
            ) === 'SEMUA';
    }
}
