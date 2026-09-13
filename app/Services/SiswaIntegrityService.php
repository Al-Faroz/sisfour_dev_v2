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
    public function forceDelete(int $id): array
    {
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
}
