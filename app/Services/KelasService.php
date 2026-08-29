<?php

namespace App\Services;

use App\Models\AnggotaKelasModel;
use App\Models\KelasModel;
use App\Models\RiwayatSiswaModel;
use App\Models\SiswaModel;
use Config\Database;

/**
 * KelasService
 *
 * Business logic: kenaikan kelas (checklist), kelulusan, mutasi siswa,
 * pencatatan histori (riwayat_siswa — A4).
 *
 * Referensi: 04_MASTER_DATA §3.3, §7.1
 */
class KelasService
{
    protected KelasModel $kelasModel;
    protected SiswaModel $siswaModel;
    protected AnggotaKelasModel $anggotaKelasModel;
    protected RiwayatSiswaModel $riwayatSiswaModel;
    protected $db;

    public function __construct()
    {
        $this->kelasModel        = new KelasModel();
        $this->siswaModel        = new SiswaModel();
        $this->anggotaKelasModel = new AnggotaKelasModel();
        $this->riwayatSiswaModel = new RiwayatSiswaModel();
        $this->db                = Database::connect();
    }

    /**
     * Generate nama_kelas dari tingkat + rombel (contoh: "7-A").
     */
    public function generateNamaKelas(string $tingkat, string $rombel): string
    {
        return $tingkat . '-' . strtoupper($rombel);
    }

    /**
     * Kenaikan kelas dengan checklist (A6, 04_MASTER_DATA §3.3).
     *
     * @param int   $idKelasAsal
     * @param int   $idKelasTujuan
     * @param int   $idTahunBaru
     * @param int[] $daftarSiswaTerpilih  id_siswa yang DICENTANG (default semua tercentang di UI, tapi array ini WAJIB eksplisit, jangan hardcode "semua siswa")
     *
     * @return array{success: bool, message: string, jumlah_dipindah?: int}
     */
    public function naikKelas(int $idKelasAsal, int $idKelasTujuan, int $idTahunBaru, array $daftarSiswaTerpilih): array
    {
        if (empty($daftarSiswaTerpilih)) {
            return ['success' => false, 'message' => 'Tidak ada siswa yang dipilih untuk dipindahkan.'];
        }

        $kelasTujuan = $this->kelasModel->find($idKelasTujuan);
        if (!$kelasTujuan) {
            return ['success' => false, 'message' => 'Kelas tujuan tidak ditemukan.'];
        }

        $this->db->transStart();

        $tanggalHariIni = date('Y-m-d');

        foreach ($daftarSiswaTerpilih as $idSiswa) {
            $idSiswa = (int) $idSiswa;

            // Tutup riwayat aktif di kelas asal, catat riwayat baru di kelas tujuan (A4)
            $this->riwayatSiswaModel->insert([
                'id_siswa'        => $idSiswa,
                'id_tahun'        => $idTahunBaru,
                'id_kelas'        => $idKelasTujuan,
                'status'          => 'Aktif',
                'tanggal_mulai'   => $tanggalHariIni,
                'keterangan'      => 'Kenaikan kelas dari kelas asal ID ' . $idKelasAsal,
            ]);

            // Pindahkan keanggotaan kelas (anggota_kelas) ke tahun ajaran baru
            $this->anggotaKelasModel->pindahkan($idSiswa, $idKelasTujuan, $idTahunBaru);

            // Catatan: Wali Kelas & Jadwal Guru TIDAK ikut pindah (04_MASTER_DATA §3.3)
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Proses kenaikan kelas gagal, transaksi dibatalkan.'];
        }

        return [
            'success'         => true,
            'message'         => 'Kenaikan kelas berhasil.',
            'jumlah_dipindah' => count($daftarSiswaTerpilih),
        ];
    }

    /**
     * Kelulusan siswa kelas 9.
     *
     * @param int   $idKelas
     * @param int[] $daftarSiswaTerpilih
     */
    public function luluskan(int $idKelas, array $daftarSiswaTerpilih): array
    {
        if (empty($daftarSiswaTerpilih)) {
            return ['success' => false, 'message' => 'Tidak ada siswa yang dipilih untuk diluluskan.'];
        }

        $kelas = $this->kelasModel->find($idKelas);
        if (!$kelas) {
            return ['success' => false, 'message' => 'Kelas tidak ditemukan.'];
        }

        $this->db->transStart();

        $tanggalHariIni = date('Y-m-d');

        foreach ($daftarSiswaTerpilih as $idSiswa) {
            $idSiswa = (int) $idSiswa;

            $this->riwayatSiswaModel->tutupRiwayatAktif($idSiswa, $kelas['id_tahun'], $tanggalHariIni);

            $this->riwayatSiswaModel->insert([
                'id_siswa'        => $idSiswa,
                'id_tahun'        => $kelas['id_tahun'],
                'id_kelas'        => $idKelas,
                'status'          => 'Lulus',
                'tanggal_mulai'   => $tanggalHariIni,
                'tanggal_selesai' => $tanggalHariIni,
                'keterangan'      => 'Kelulusan',
            ]);

            $this->siswaModel->update($idSiswa, [
                'status_aktif'      => 'Lulus',
                'tanggal_mutasi'    => $tanggalHariIni,
                'keterangan_mutasi' => 'Lulus',
            ]);

            // Efek: kartu pelajar otomatis Nonaktif — lihat KartuPelajarService (event listener saat Tahap 10)
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Proses kelulusan gagal, transaksi dibatalkan.'];
        }

        return [
            'success'          => true,
            'message'          => 'Kelulusan berhasil diproses.',
            'jumlah_diluluskan' => count($daftarSiswaTerpilih),
        ];
    }

    /**
     * Mutasi siswa (Pindah/Keluar) — 04_MASTER_DATA §3.2.
     *
     * @param string $statusBaru  'Pindah' atau 'Keluar'
     */
    public function mutasiSiswa(int $idSiswa, string $statusBaru, string $keterangan): array
    {
        if (!in_array($statusBaru, ['Pindah', 'Keluar'], true)) {
            return ['success' => false, 'message' => 'Status mutasi tidak valid.'];
        }

        $siswa = $this->siswaModel->find($idSiswa);
        if (!$siswa) {
            return ['success' => false, 'message' => 'Siswa tidak ditemukan.'];
        }

        $this->db->transStart();

        $tanggalHariIni = date('Y-m-d');

        $anggota = $this->db->table('anggota_kelas')
            ->select('id_kelas, id_tahun')
            ->where('id_siswa', $idSiswa)
            ->orderBy('id_tahun', 'DESC')
            ->get()
            ->getRowArray();

        if ($anggota) {
            $this->riwayatSiswaModel->tutupRiwayatAktif($idSiswa, (int) $anggota['id_tahun'], $tanggalHariIni);

            $this->riwayatSiswaModel->insert([
                'id_siswa'        => $idSiswa,
                'id_tahun'        => $anggota['id_tahun'],
                'id_kelas'        => $anggota['id_kelas'],
                'status'          => $statusBaru,
                'tanggal_mulai'   => $tanggalHariIni,
                'tanggal_selesai' => $tanggalHariIni,
                'keterangan'      => $keterangan,
            ]);
        }

        $this->siswaModel->update($idSiswa, [
            'status_aktif'      => $statusBaru,
            'tanggal_mutasi'    => $tanggalHariIni,
            'keterangan_mutasi' => $keterangan,
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Proses mutasi gagal, transaksi dibatalkan.'];
        }

        return ['success' => true, 'message' => 'Mutasi siswa berhasil dicatat.'];
    }
}
