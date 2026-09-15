<?php

namespace App\Services;

/**
 * Integrity guard untuk permanent delete Master Guru.
 *
 * Hosting legacy tidak memiliki FK pada seluruh tabel histori. Permanent delete
 * hanya diizinkan untuk data Guru yang benar-benar belum pernah dipakai.
 */
class GuruIntegrityService extends GuruService
{
    public function forceDelete(int $id): array
    {
        if (! $this->canManageIntegrity()) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak mengelola Master Guru.',
            ];
        }

        $dependencies = $this->dependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'code' => 'DATA_IN_USE',
                'message' => 'Hapus permanen ditolak karena Guru masih memiliki histori/data terkait: '
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
            ['jadwal_guru', 'id_guru', 'jadwal guru'],
            ['mapping_wali_kelas', 'id_guru', 'mapping wali kelas'],
            ['presensi', 'id_guru_input', 'presensi siswa'],
            ['presensi_mengajar', 'id_guru', 'jurnal/presensi mengajar'],
            ['catatan_kasus', 'id_guru_input', 'catatan kasus BK'],
            ['catatan_prestasi', 'id_guru_input', 'catatan prestasi'],
            ['riwayat_pendidikan', 'id_guru', 'riwayat pendidikan'],
            ['riwayat_penugasan', 'id_guru', 'riwayat penugasan'],
            ['riwayat_pangkat', 'id_guru', 'riwayat pangkat'],
            ['dokumen_personalia', 'id_guru', 'dokumen personalia'],
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
                'master_guru.manage',
                $userId
            ) === 'SEMUA';
    }
}
