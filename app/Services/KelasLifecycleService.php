<?php

namespace App\Services;

use Throwable;

/**
 * F14 lifecycle hardening untuk Manajemen Siswa.
 *
 * Anggota kelas merepresentasikan membership operasional per tahun. Saat siswa
 * masuk status terminal (Lulus/Pindah/Keluar), membership pada tahun aktif yang
 * ditutup harus ikut dihapus dalam transaksi yang sama. Membership tahun lama
 * tetap dipertahankan, sedangkan histori lifecycle disimpan di riwayat_siswa.
 */
class KelasLifecycleService extends KelasService
{
    public function luluskan(
        int $idKelas,
        array $daftarSiswaTerpilih
    ): array {
        if (! $this->canManageLifecycle()) {
            return $this->forbiddenLifecycle();
        }

        if ($daftarSiswaTerpilih === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada siswa yang dipilih untuk diluluskan.',
            ];
        }

        $idTahunAktif = $this->getActiveYearId();

        if ($idTahunAktif <= 0) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran aktif belum tersedia.',
            ];
        }

        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ];
        }

        if ((int) $kelas['id_tahun'] !== $idTahunAktif) {
            return [
                'success' => false,
                'message' => 'Kelulusan hanya dapat diproses dari kelas pada tahun ajaran yang sedang aktif.',
            ];
        }

        if ((string) $kelas['tingkat'] !== '9') {
            return [
                'success' => false,
                'message' => 'Proses kelulusan hanya tersedia untuk kelas tingkat 9.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            foreach ($daftarSiswaTerpilih as $idSiswaRaw) {
                $idSiswa = (int) $idSiswaRaw;

                if ($idSiswa <= 0) {
                    throw new \RuntimeException('ID siswa tidak valid.');
                }

                $anggota = $this->anggotaKelasModel
                    ->where('id_siswa', $idSiswa)
                    ->where('id_kelas', $idKelas)
                    ->where('id_tahun', $idTahunAktif)
                    ->first();

                if ($anggota === null) {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} bukan anggota aktif kelas ini."
                    );
                }

                $siswa = $this->siswaModel->find($idSiswa);

                if ($siswa === null || ($siswa['status_aktif'] ?? '') !== 'Aktif') {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} tidak berstatus Aktif."
                    );
                }

                $riwayatAktif = $this->riwayatSiswaModel->getRiwayatAktif(
                    $idSiswa,
                    $idTahunAktif
                );

                if ($riwayatAktif === null) {
                    throw new \RuntimeException(
                        "Histori aktif siswa ID {$idSiswa} pada tahun aktif tidak ditemukan."
                    );
                }

                if (! $this->riwayatSiswaModel->tutupRiwayatAktif(
                    $idSiswa,
                    $idTahunAktif,
                    $tanggal
                )) {
                    throw new \RuntimeException(
                        "Histori aktif siswa ID {$idSiswa} gagal ditutup."
                    );
                }

                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => $idTahunAktif,
                    'id_kelas' => $idKelas,
                    'status' => 'Lulus',
                    'tanggal_mulai' => $tanggal,
                    'tanggal_selesai' => $tanggal,
                    'keterangan' => 'Lulus dari ' . $kelas['nama_kelas'],
                ]) === false) {
                    throw new \RuntimeException(
                        'Histori kelulusan gagal dicatat.'
                    );
                }

                if (! $this->siswaModel->update($idSiswa, [
                    'status_aktif' => 'Lulus',
                    'tanggal_mutasi' => $tanggal,
                    'keterangan_mutasi' => 'Lulus',
                ])) {
                    throw new \RuntimeException(
                        'Status kelulusan siswa gagal diperbarui.'
                    );
                }

                if (! $this->anggotaKelasModel->delete((int) $anggota['id'])) {
                    throw new \RuntimeException(
                        'Membership kelas siswa lulus gagal ditutup.'
                    );
                }

                $this->nonaktifkanKartu($idSiswa);
            }

            $this->logActivity(
                'LULUSKAN',
                'Manajemen Siswa',
                sprintf(
                    'Meluluskan %d siswa dari %s pada tahun aktif; membership operasional ditutup dan histori dipertahankan.',
                    count($daftarSiswaTerpilih),
                    $kelas['nama_kelas']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi kelulusan gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Kelulusan berhasil diproses. Membership kelas tahun aktif ditutup dan histori tetap tersimpan.',
                'jumlah_diluluskan' => count($daftarSiswaTerpilih),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function mutasiSiswa(
        int $idSiswa,
        string $statusBaru,
        string $keterangan
    ): array {
        if (! $this->canManageLifecycle()) {
            return $this->forbiddenLifecycle();
        }

        if (! in_array($statusBaru, ['Pindah', 'Keluar'], true)) {
            return [
                'success' => false,
                'message' => 'Status mutasi hanya boleh Pindah atau Keluar.',
            ];
        }

        $keterangan = trim($keterangan);

        if ($keterangan === '') {
            return [
                'success' => false,
                'message' => 'Keterangan mutasi wajib diisi.',
            ];
        }

        $idTahunAktif = $this->getActiveYearId();

        if ($idTahunAktif <= 0) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran aktif belum tersedia.',
            ];
        }

        $siswa = $this->siswaModel->find($idSiswa);

        if ($siswa === null) {
            return [
                'success' => false,
                'message' => 'Siswa tidak ditemukan.',
            ];
        }

        if (($siswa['status_aktif'] ?? '') !== 'Aktif') {
            return [
                'success' => false,
                'message' => 'Mutasi hanya dapat diproses untuk siswa berstatus Aktif.',
            ];
        }

        $anggota = $this->db
            ->table('anggota_kelas')
            ->select('id, id_kelas, id_tahun')
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahunAktif)
            ->get()
            ->getRowArray();

        if ($anggota === null) {
            return [
                'success' => false,
                'message' => 'Mutasi tidak dapat diproses karena siswa belum memiliki membership kelas pada tahun ajaran aktif.',
            ];
        }

        $riwayatAktif = $this->riwayatSiswaModel->getRiwayatAktif(
            $idSiswa,
            $idTahunAktif
        );

        if ($riwayatAktif === null) {
            return [
                'success' => false,
                'message' => 'Mutasi tidak dapat diproses karena histori aktif siswa pada tahun ajaran aktif tidak ditemukan.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            if (! $this->riwayatSiswaModel->tutupRiwayatAktif(
                $idSiswa,
                $idTahunAktif,
                $tanggal
            )) {
                throw new \RuntimeException('Histori aktif siswa gagal ditutup.');
            }

            if ($this->riwayatSiswaModel->insert([
                'id_siswa' => $idSiswa,
                'id_tahun' => $idTahunAktif,
                'id_kelas' => (int) $anggota['id_kelas'],
                'status' => $statusBaru,
                'tanggal_mulai' => $tanggal,
                'tanggal_selesai' => $tanggal,
                'keterangan' => $keterangan,
            ]) === false) {
                throw new \RuntimeException(
                    'Histori mutasi gagal dicatat.'
                );
            }

            if (! $this->siswaModel->update($idSiswa, [
                'status_aktif' => $statusBaru,
                'tanggal_mutasi' => $tanggal,
                'keterangan_mutasi' => $keterangan,
            ])) {
                throw new \RuntimeException(
                    'Status mutasi siswa gagal diperbarui.'
                );
            }

            if (! $this->anggotaKelasModel->delete((int) $anggota['id'])) {
                throw new \RuntimeException(
                    'Membership kelas siswa mutasi gagal ditutup.'
                );
            }

            $this->nonaktifkanKartu($idSiswa);

            $this->logActivity(
                'MUTASI',
                'Manajemen Siswa',
                sprintf(
                    'Mutasi siswa ID %d menjadi %s pada tahun aktif ID %d; membership operasional ditutup dan histori dipertahankan. %s',
                    $idSiswa,
                    $statusBaru,
                    $idTahunAktif,
                    $keterangan
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi mutasi gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Mutasi siswa berhasil dicatat. Membership kelas tahun aktif ditutup dan histori tetap tersimpan.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getActiveYearId(): int
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return isset($row['id']) ? (int) $row['id'] : 0;
    }

    private function canManageLifecycle(): bool
    {
        $userId = (int) (session()->get('user_id') ?? 0);

        return $userId > 0
            && $this->authService->resolveScope(
                'master_siswa.manage',
                $userId
            ) === 'SEMUA';
    }

    private function forbiddenLifecycle(): array
    {
        return [
            'success' => false,
            'code' => 'FORBIDDEN',
            'message' => 'Anda tidak memiliki hak menjalankan lifecycle Manajemen Siswa.',
        ];
    }
}
