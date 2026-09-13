<?php

namespace App\Services;

use Throwable;

/**
 * F14 lifecycle hardening untuk Manajemen Siswa.
 *
 * Anggota kelas merepresentasikan membership operasional per tahun. Saat siswa
 * masuk status terminal (Lulus/Pindah/Keluar), membership pada tahun yang
 * ditutup harus ikut dihapus dalam transaksi yang sama. Riwayat tetap disimpan
 * di riwayat_siswa sehingga histori kelas tidak hilang.
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

        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
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
                    ->where('id_tahun', (int) $kelas['id_tahun'])
                    ->first();

                if ($anggota === null) {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} bukan anggota kelas ini."
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
                    (int) $kelas['id_tahun']
                );

                if ($riwayatAktif === null) {
                    throw new \RuntimeException(
                        "Histori aktif siswa ID {$idSiswa} pada tahun kelas tidak ditemukan."
                    );
                }

                if (! $this->riwayatSiswaModel->tutupRiwayatAktif(
                    $idSiswa,
                    (int) $kelas['id_tahun'],
                    $tanggal
                )) {
                    throw new \RuntimeException(
                        "Histori aktif siswa ID {$idSiswa} gagal ditutup."
                    );
                }

                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => (int) $kelas['id_tahun'],
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
                    'Meluluskan %d siswa dari %s, menutup membership operasional, dan mempertahankan histori.',
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
                'message' => 'Kelulusan berhasil diproses. Membership kelas aktif ditutup dan histori tetap tersimpan.',
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
            ->table('anggota_kelas ak')
            ->select('ak.id, ak.id_kelas, ak.id_tahun, ta.status_aktif')
            ->join('tahun_ajaran ta', 'ta.id = ak.id_tahun')
            ->where('ak.id_siswa', $idSiswa)
            ->orderBy('ta.status_aktif', 'DESC')
            ->orderBy('ak.id_tahun', 'DESC')
            ->orderBy('ak.id', 'DESC')
            ->get()
            ->getRowArray();

        if ($anggota === null) {
            return [
                'success' => false,
                'message' => 'Mutasi tidak dapat diproses karena siswa belum memiliki membership kelas.',
            ];
        }

        $riwayatAktif = $this->riwayatSiswaModel->getRiwayatAktif(
            $idSiswa,
            (int) $anggota['id_tahun']
        );

        if ($riwayatAktif === null) {
            return [
                'success' => false,
                'message' => 'Mutasi tidak dapat diproses karena histori aktif siswa pada membership terpilih tidak ditemukan.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            if (! $this->riwayatSiswaModel->tutupRiwayatAktif(
                $idSiswa,
                (int) $anggota['id_tahun'],
                $tanggal
            )) {
                throw new \RuntimeException('Histori aktif siswa gagal ditutup.');
            }

            if ($this->riwayatSiswaModel->insert([
                'id_siswa' => $idSiswa,
                'id_tahun' => (int) $anggota['id_tahun'],
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
                    'Mutasi siswa ID %d menjadi %s; membership tahun ID %d ditutup dan histori dipertahankan. %s',
                    $idSiswa,
                    $statusBaru,
                    (int) $anggota['id_tahun'],
                    $keterangan
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi mutasi gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Mutasi siswa berhasil dicatat. Membership kelas operasional ditutup dan histori tetap tersimpan.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
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
