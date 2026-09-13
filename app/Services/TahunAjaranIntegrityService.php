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
}
