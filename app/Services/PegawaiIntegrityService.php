<?php

namespace App\Services;

/**
 * Integrity guard untuk permanent delete Master Pegawai.
 *
 * Riwayat personalia legacy tidak seluruhnya memiliki FK. Permanent delete
 * hanya diizinkan bila Pegawai belum memiliki histori/personalia terkait.
 */
class PegawaiIntegrityService extends PegawaiService
{
    public function forceDelete(int $id): array
    {
        if (! $this->canManageIntegrity()) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak mengelola Master Pegawai.',
            ];
        }

        $dependencies = $this->dependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'code' => 'DATA_IN_USE',
                'message' => 'Hapus permanen ditolak karena Pegawai masih memiliki histori/data terkait: '
                    . implode(', ', $dependencies)
                    . '. Pertahankan data di Recycle Bin agar histori tidak menjadi yatim.',
            ];
        }

        return parent::forceDelete($id);
    }

    /**
     * @return string[]
     */
    private function dependencies(int $id): array
    {
        $checks = [
            ['riwayat_pendidikan', 'id_pegawai', 'riwayat pendidikan'],
            ['riwayat_penugasan', 'id_pegawai', 'riwayat penugasan'],
            ['riwayat_pangkat', 'id_pegawai', 'riwayat pangkat'],
            ['dokumen_personalia', 'id_pegawai', 'dokumen personalia'],
        ];

        $found = [];

        foreach ($checks as [$table, $column, $label]) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            if (
                $this->db
                    ->table($table)
                    ->where($column, $id)
                    ->countAllResults() > 0
            ) {
                $found[] = $label;
            }
        }

        return $found;
    }

    private function canManageIntegrity(): bool
    {
        $userId = (int) (session()->get('user_id') ?? 0);

        return $userId > 0
            && $this->authService->resolveScope(
                'master_pegawai.manage',
                $userId
            ) === 'SEMUA';
    }
}
