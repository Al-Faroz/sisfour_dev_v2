<?php

namespace App\Services;

/**
 * F12 integrity guard untuk Mapping Wali Kelas.
 *
 * Mapping yang dinonaktifkan adalah histori akademik. Histori boleh di-restore,
 * tetapi tidak boleh dihapus permanen dari Recycle Bin.
 */
class MappingWaliIntegrityService extends MappingWaliService
{
    public function forceDelete(int $id, int $userId): array
    {
        if (! $this->canManage($userId)) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak mengelola Mapping Wali Kelas.',
            ];
        }

        $mapping = $this->db
            ->table('mapping_wali_kelas')
            ->select('id')
            ->where('id', $id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->get()
            ->getRowArray();

        if ($mapping === null) {
            return [
                'success' => false,
                'code' => 'NOT_FOUND',
                'message' => 'Histori mapping wali kelas tidak ditemukan.',
            ];
        }

        return [
            'success' => false,
            'code' => 'HISTORY_PROTECTED',
            'message' => 'Histori Mapping Wali Kelas dilindungi dan tidak dapat dihapus permanen. Gunakan Restore bila mapping perlu diaktifkan kembali.',
        ];
    }
}
