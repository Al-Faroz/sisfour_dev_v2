<?php

namespace App\Services;

use App\Models\AnggotaKelasModel;
use App\Models\KelasModel;
use App\Models\RiwayatSiswaModel;
use App\Models\SiswaModel;
use Config\Database;
use Throwable;

class KelasService
{
    protected KelasModel $kelasModel;
    protected SiswaModel $siswaModel;
    protected AnggotaKelasModel $anggotaKelasModel;
    protected RiwayatSiswaModel $riwayatSiswaModel;
    protected $db;

    public function __construct()
    {
        $this->kelasModel = new KelasModel();
        $this->siswaModel = new SiswaModel();
        $this->anggotaKelasModel = new AnggotaKelasModel();
        $this->riwayatSiswaModel = new RiwayatSiswaModel();
        $this->db = Database::connect();
    }

    public function generateNamaKelas(string $tingkat, string $rombel): string
    {
        return $tingkat . '-' . strtoupper(trim($rombel));
    }

    public function naikKelas(
        int $idKelasAsal,
        int $idKelasTujuan,
        int $idTahunBaru,
        array $daftarSiswaTerpilih
    ): array {
        if ($daftarSiswaTerpilih === []) {
            return ['success' => false, 'message' => 'Tidak ada siswa yang dipilih untuk dipindahkan.'];
        }

        $kelasAsal = $this->kelasModel->find($idKelasAsal);
        $kelasTujuan = $this->kelasModel->find($idKelasTujuan);

        if ($kelasAsal === null || $kelasTujuan === null) {
            return ['success' => false, 'message' => 'Kelas asal atau kelas tujuan tidak ditemukan.'];
        }

        if ((int) $kelasTujuan['id_tahun'] !== $idTahunBaru) {
            return ['success' => false, 'message' => 'Kelas tujuan tidak berada pada tahun ajaran tujuan.'];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            foreach ($daftarSiswaTerpilih as $idSiswaRaw) {
                $idSiswa = (int) $idSiswaRaw;

                $anggotaAsal = $this->anggotaKelasModel
                    ->where('id_siswa', $idSiswa)
                    ->where('id_kelas', $idKelasAsal)
                    ->where('id_tahun', (int) $kelasAsal['id_tahun'])
                    ->first();

                if ($anggotaAsal === null) {
                    throw new \RuntimeException("Siswa ID {$idSiswa} bukan anggota kelas asal.");
                }

                $this->riwayatSiswaModel->tutupRiwayatAktif(
                    $idSiswa,
                    (int) $kelasAsal['id_tahun'],
                    $tanggal
                );

                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => $idTahunBaru,
                    'id_kelas' => $idKelasTujuan,
                    'status' => 'Aktif',
                    'tanggal_mulai' => $tanggal,
                    'keterangan' => 'Kenaikan kelas dari ' . $kelasAsal['nama_kelas'],
                ]) === false) {
                    throw new \RuntimeException('Histori kenaikan kelas gagal dicatat.');
                }

                if (!$this->anggotaKelasModel->pindahkan(
                    $idSiswa,
                    $idKelasTujuan,
                    $idTahunBaru
                )) {
                    throw new \RuntimeException('Keanggotaan kelas gagal diperbarui.');
                }

                if (!$this->siswaModel->update($idSiswa, [
                    'status_aktif' => 'Aktif',
                    'tanggal_mutasi' => null,
                    'keterangan_mutasi' => null,
                ])) {
                    throw new \RuntimeException('Status siswa gagal diperbarui.');
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi kenaikan kelas gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Kenaikan kelas berhasil.',
                'jumlah_dipindah' => count($daftarSiswaTerpilih),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function luluskan(
        int $idKelas,
        array $daftarSiswaTerpilih
    ): array {
        if ($daftarSiswaTerpilih === []) {
            return ['success' => false, 'message' => 'Tidak ada siswa yang dipilih untuk diluluskan.'];
        }

        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return ['success' => false, 'message' => 'Kelas tidak ditemukan.'];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            foreach ($daftarSiswaTerpilih as $idSiswaRaw) {
                $idSiswa = (int) $idSiswaRaw;

                $this->riwayatSiswaModel->tutupRiwayatAktif(
                    $idSiswa,
                    (int) $kelas['id_tahun'],
                    $tanggal
                );

                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => (int) $kelas['id_tahun'],
                    'id_kelas' => $idKelas,
                    'status' => 'Lulus',
                    'tanggal_mulai' => $tanggal,
                    'tanggal_selesai' => $tanggal,
                    'keterangan' => 'Kelulusan',
                ]) === false) {
                    throw new \RuntimeException('Histori kelulusan gagal dicatat.');
                }

                if (!$this->siswaModel->update($idSiswa, [
                    'status_aktif' => 'Lulus',
                    'tanggal_mutasi' => $tanggal,
                    'keterangan_mutasi' => 'Lulus',
                ])) {
                    throw new \RuntimeException('Status kelulusan siswa gagal diperbarui.');
                }

                $this->nonaktifkanKartu($idSiswa);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi kelulusan gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Kelulusan berhasil diproses.',
                'jumlah_diluluskan' => count($daftarSiswaTerpilih),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function mutasiSiswa(
        int $idSiswa,
        string $statusBaru,
        string $keterangan
    ): array {
        if (!in_array($statusBaru, ['Pindah', 'Keluar'], true)) {
            return ['success' => false, 'message' => 'Status mutasi hanya boleh Pindah atau Keluar.'];
        }

        $keterangan = trim($keterangan);

        if ($keterangan === '') {
            return ['success' => false, 'message' => 'Keterangan mutasi wajib diisi.'];
        }

        $siswa = $this->siswaModel->find($idSiswa);

        if ($siswa === null) {
            return ['success' => false, 'message' => 'Siswa tidak ditemukan.'];
        }

        if ($siswa['status_aktif'] !== 'Aktif') {
            return ['success' => false, 'message' => 'Mutasi hanya dapat diproses untuk siswa berstatus Aktif.'];
        }

        $anggota = $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_kelas, ak.id_tahun')
            ->join('tahun_ajaran ta', 'ta.id = ak.id_tahun')
            ->where('ak.id_siswa', $idSiswa)
            ->orderBy('ta.status_aktif', 'DESC')
            ->orderBy('ak.id_tahun', 'DESC')
            ->get()
            ->getRowArray();

        if ($anggota === null) {
            return [
                'success' => false,
                'message' => 'Mutasi tidak dapat diproses karena siswa belum memiliki riwayat kelas.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            $this->riwayatSiswaModel->tutupRiwayatAktif(
                $idSiswa,
                (int) $anggota['id_tahun'],
                $tanggal
            );

            if ($this->riwayatSiswaModel->insert([
                'id_siswa' => $idSiswa,
                'id_tahun' => (int) $anggota['id_tahun'],
                'id_kelas' => (int) $anggota['id_kelas'],
                'status' => $statusBaru,
                'tanggal_mulai' => $tanggal,
                'tanggal_selesai' => $tanggal,
                'keterangan' => $keterangan,
            ]) === false) {
                throw new \RuntimeException('Histori mutasi gagal dicatat.');
            }

            if (!$this->siswaModel->update($idSiswa, [
                'status_aktif' => $statusBaru,
                'tanggal_mutasi' => $tanggal,
                'keterangan_mutasi' => $keterangan,
            ])) {
                throw new \RuntimeException('Status mutasi siswa gagal diperbarui.');
            }

            $this->nonaktifkanKartu($idSiswa);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi mutasi gagal.');
            }

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Mutasi siswa berhasil dicatat.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function nonaktifkanKartu(int $idSiswa): void
    {
        $this->db
            ->table('kartu_pelajar')
            ->where('id_siswa', $idSiswa)
            ->where('status_aktif', 'Aktif')
            ->update(['status_aktif' => 'Nonaktif']);
    }
}
