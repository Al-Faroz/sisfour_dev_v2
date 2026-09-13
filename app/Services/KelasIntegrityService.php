<?php

namespace App\Services;

/**
 * Integrity guard untuk permanent delete Master Kelas.
 *
 * Permanent delete ditolak bila kelas pernah dipakai oleh membership, mapping,
 * jadwal, histori siswa, atau presensi; status aktif/nonaktif tidak menghapus
 * kebutuhan mempertahankan referensi historis tersebut.
 */
class KelasIntegrityService extends KelasService
{
    public function forceDelete(int $id): array
    {
        $dependencies = $this->dependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'code' => 'DATA_IN_USE',
                'message' => 'Hapus permanen ditolak karena Kelas masih memiliki histori/data terkait: '
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
            ['anggota_kelas', 'id_kelas', 'keanggotaan kelas'],
            ['mapping_wali_kelas', 'id_kelas', 'mapping wali kelas'],
            ['jadwal_guru', 'id_kelas', 'jadwal guru'],
            ['riwayat_siswa', 'id_kelas', 'riwayat siswa'],
            ['presensi', 'id_kelas', 'presensi siswa'],
            ['presensi_mengajar', 'id_kelas', 'jurnal/presensi mengajar'],
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
