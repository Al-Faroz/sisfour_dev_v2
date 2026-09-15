<?php

namespace App\Services;

use Throwable;

/**
 * G2 integrity hardening untuk Master Jadwal Guru.
 *
 * Aturan tambahan:
 * - setiap kelas/hari yang diimport wajib memiliki minimal dua jadwal;
 * - jadwal paling awal wajib Sesi Awal;
 * - jadwal paling akhir wajib Sesi Akhir;
 * - seluruh jadwal di antara keduanya wajib Non Sesi;
 * - jadwal yang sudah direferensikan Jurnal Mengajar tidak boleh dihapus;
 * - penghapusan jadwal aktif tidak boleh meninggalkan topology sesi invalid.
 *
 * Import tetap eksplisit: Service tidak menebak/mengubah label sesi file Excel.
 */
class JadwalGuruIntegrityService extends JadwalGuruService
{
    public function validateBentrok(
        array $rows,
        array $sourceRows = []
    ): array {
        $overlap = parent::validateBentrok($rows, $sourceRows);

        if (! $overlap['valid']) {
            return $overlap;
        }

        $errors = $this->validateSessionTopology($rows, $sourceRows);

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function delete(
        int $id,
        int $userId
    ): array {
        if (! $this->canManage($userId)) {
            return $this->failure(
                'FORBIDDEN',
                'Anda tidak memiliki hak untuk menghapus jadwal.'
            );
        }

        $jadwal = $this->db
            ->table('jadwal_guru jg')
            ->select(
                'jg.*, g.nama AS nama_guru, ' .
                'k.nama_kelas, mp.nama_mapel'
            )
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id', $id)
            ->get()
            ->getRowArray();

        if ($jadwal === null) {
            return $this->failure(
                'NOT_FOUND',
                'Jadwal tidak ditemukan.'
            );
        }

        $jumlahJurnal = $this->db
            ->table('presensi_mengajar')
            ->where('id_jadwal', $id)
            ->countAllResults();

        if ($jumlahJurnal > 0) {
            return $this->failure(
                'JADWAL_IN_USE',
                sprintf(
                    'Jadwal tidak dapat dihapus karena sudah digunakan pada %d Jurnal Mengajar. Nonaktifkan melalui replacement import bila jadwal tidak lagi berlaku.',
                    $jumlahJurnal
                )
            );
        }

        $isActive = (string) ($jadwal['status_jadwal'] ?? '') === 'Aktif';
        $remainingActive = [];

        if ($isActive) {
            $remainingActive = $this->db
                ->table('jadwal_guru')
                ->select('id, jam_mulai, jam_selesai, sesi')
                ->where('id_tahun', (int) $jadwal['id_tahun'])
                ->where('id_kelas', (int) $jadwal['id_kelas'])
                ->where('hari', (string) $jadwal['hari'])
                ->where('status_jadwal', 'Aktif')
                ->where('id !=', $id)
                ->orderBy('jam_mulai', 'ASC')
                ->orderBy('jam_selesai', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if (count($remainingActive) === 1) {
                return $this->failure(
                    'SESSION_TOPOLOGY',
                    'Jadwal tidak dapat dihapus karena akan menyisakan hanya satu jadwal aktif pada kelas/hari tersebut. Sesi Awal dan Sesi Akhir wajib tetap tersedia.'
                );
            }
        }

        $this->db->transBegin();

        try {
            $deleted = $this->db
                ->table('jadwal_guru')
                ->where('id', $id)
                ->delete();

            if (! $deleted) {
                throw new \RuntimeException('Jadwal gagal dihapus.');
            }

            if ($isActive && count($remainingActive) >= 2) {
                $this->normalizeActiveGroupSessions($remainingActive);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi penghapusan jadwal gagal.'
                );
            }

            $this->activityLog->write(
                $userId,
                'DELETE',
                'Master Jadwal Guru',
                sprintf(
                    'Menghapus jadwal %s - %s - %s dan menjaga topology sesi kelas/hari.',
                    $jadwal['nama_guru'],
                    $jadwal['nama_kelas'],
                    $jadwal['nama_mapel']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Jadwal berhasil dihapus. Penanda Sesi Awal/Akhir kelas-hari tetap konsisten.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->failure(
                'DELETE_FAILED',
                $e->getMessage()
            );
        }
    }

    /**
     * @return string[]
     */
    private function validateSessionTopology(
        array $rows,
        array $sourceRows
    ): array {
        $groups = [];

        foreach ($rows as $index => $row) {
            $key = (int) ($row['id_kelas'] ?? 0)
                . '|'
                . (string) ($row['hari'] ?? '');

            $groups[$key][] = [
                'index' => $index,
                'row' => $row,
            ];
        }

        $errors = [];

        foreach ($groups as $items) {
            usort(
                $items,
                static function (array $a, array $b): int {
                    $left = $a['row'];
                    $right = $b['row'];

                    return [
                        (string) ($left['jam_mulai'] ?? ''),
                        (string) ($left['jam_selesai'] ?? ''),
                        (int) $a['index'],
                    ] <=> [
                        (string) ($right['jam_mulai'] ?? ''),
                        (string) ($right['jam_selesai'] ?? ''),
                        (int) $b['index'],
                    ];
                }
            );

            $count = count($items);
            $first = $items[0];
            $firstIndex = (int) $first['index'];
            $firstSource = $sourceRows[$firstIndex] ?? [];
            $namaKelas = (string) (
                $firstSource['nama_kelas']
                ?? ('Kelas ID ' . (int) ($first['row']['id_kelas'] ?? 0))
            );
            $hari = (string) ($first['row']['hari'] ?? '');

            if ($count < 2) {
                $errors[] = sprintf(
                    'Topology sesi tidak valid: %s pada %s hanya memiliki %d jadwal. Minimal dua jadwal diperlukan agar Sesi Awal dan Sesi Akhir keduanya tersedia.',
                    $namaKelas,
                    $hari,
                    $count
                );
                continue;
            }

            $last = $items[$count - 1];

            if ((string) ($first['row']['sesi'] ?? '') !== 'Sesi Awal') {
                $errors[] = sprintf(
                    'Baris %d: jadwal pertama %s pada %s (%s-%s) wajib berlabel Sesi Awal.',
                    (int) ($firstSource['excel_row'] ?? ($firstIndex + 2)),
                    $namaKelas,
                    $hari,
                    substr((string) $first['row']['jam_mulai'], 0, 5),
                    substr((string) $first['row']['jam_selesai'], 0, 5)
                );
            }

            $lastIndex = (int) $last['index'];
            $lastSource = $sourceRows[$lastIndex] ?? [];

            if ((string) ($last['row']['sesi'] ?? '') !== 'Sesi Akhir') {
                $errors[] = sprintf(
                    'Baris %d: jadwal terakhir %s pada %s (%s-%s) wajib berlabel Sesi Akhir.',
                    (int) ($lastSource['excel_row'] ?? ($lastIndex + 2)),
                    $namaKelas,
                    $hari,
                    substr((string) $last['row']['jam_mulai'], 0, 5),
                    substr((string) $last['row']['jam_selesai'], 0, 5)
                );
            }

            for ($position = 1; $position < $count - 1; $position++) {
                $item = $items[$position];

                if ((string) ($item['row']['sesi'] ?? '') === 'Non Sesi') {
                    continue;
                }

                $index = (int) $item['index'];
                $source = $sourceRows[$index] ?? [];

                $errors[] = sprintf(
                    'Baris %d: jadwal tengah %s pada %s (%s-%s) wajib berlabel Non Sesi.',
                    (int) ($source['excel_row'] ?? ($index + 2)),
                    $namaKelas,
                    $hari,
                    substr((string) $item['row']['jam_mulai'], 0, 5),
                    substr((string) $item['row']['jam_selesai'], 0, 5)
                );
            }
        }

        return $errors;
    }

    private function normalizeActiveGroupSessions(array $rows): void
    {
        $ids = array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );

        $this->db
            ->table('jadwal_guru')
            ->whereIn('id', $ids)
            ->update(['sesi' => 'Non Sesi']);

        $first = $rows[0];
        $last = $rows[count($rows) - 1];

        $this->db
            ->table('jadwal_guru')
            ->where('id', (int) $first['id'])
            ->update(['sesi' => 'Sesi Awal']);

        $this->db
            ->table('jadwal_guru')
            ->where('id', (int) $last['id'])
            ->update(['sesi' => 'Sesi Akhir']);
    }

    private function failure(
        string $code,
        string $message
    ): array {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
