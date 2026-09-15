<?php

namespace App\Services;

/**
 * Integrity guard untuk permanent delete Master Siswa.
 *
 * Riwayat siswa, kartu, dan presensi legacy tidak seluruhnya memiliki FK.
 * Permanent delete hanya untuk data salah/unused yang belum mempunyai histori.
 */
class SiswaIntegrityService extends SiswaService
{
    /**
     * Admin/Operator dengan hak manage SEMUA harus selalu memperoleh seluruh
     * kelas pada tahun ajaran aktif. Jangan menggantungkan dropdown operasional
     * pada scope master_siswa.view karena permission manage adalah otoritas yang
     * lebih kuat untuk workflow Master Siswa.
     */
    public function getKelasOptions(int $userId): array
    {
        if (
            $userId > 0
            && $this->authService->resolveScope(
                'master_siswa.manage',
                $userId
            ) === 'SEMUA'
        ) {
            $tahunAktif = $this->db
                ->table('tahun_ajaran')
                ->select('id')
                ->where('status_aktif', 1)
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($tahunAktif === null) {
                return [];
            }

            return $this->db
                ->table('kelas')
                ->select('id, nama_kelas, tingkat, rombel')
                ->where('id_tahun', (int) $tahunAktif['id'])
                ->where('deleted_at', null)
                ->orderBy('tingkat', 'ASC')
                ->orderBy('rombel', 'ASC')
                ->get()
                ->getResultArray();
        }

        return parent::getKelasOptions($userId);
    }

    public function forceDelete(int $id): array
    {
        if (! $this->canManageIntegrity()) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak mengelola Master Siswa.',
            ];
        }

        $dependencies = $this->dependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'code' => 'DATA_IN_USE',
                'message' => 'Hapus permanen ditolak karena Siswa masih memiliki histori/data terkait: '
                    . implode(', ', $dependencies)
                    . '. Pertahankan data di Recycle Bin agar histori akademik tidak menjadi yatim.',
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
            ['anggota_kelas', 'id_siswa', 'keanggotaan kelas'],
            ['riwayat_siswa', 'id_siswa', 'riwayat siswa'],
            ['presensi', 'id_siswa', 'presensi siswa'],
            ['kartu_pelajar', 'id_siswa', 'kartu pelajar'],
            ['catatan_kasus', 'id_siswa', 'catatan kasus BK'],
            ['catatan_prestasi', 'id_siswa', 'catatan prestasi'],
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
                'master_siswa.manage',
                $userId
            ) === 'SEMUA';
    }
}
