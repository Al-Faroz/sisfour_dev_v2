<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;

/**
 * SignageService
 *
 * Sumber data read-only untuk Digital Signage public.
 * Tidak menggunakan user context dan tidak mengembalikan identifier sensitif.
 */
class SignageService
{
    private const TZ = 'Asia/Jakarta';
    private const EWS_MIN_ALPHA = 3;
    private const REFRESH_MINUTES = 20;

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getDisplayInfo(): array
    {
        $settings = $this->getSystemSettings();
        $tahun = $this->getTahunAktif();

        return [
            'nama_sekolah' => $settings['nama_sekolah'] ?? 'MTsN 4 Jombang',
            'logo_sekolah' => $settings['logo_sekolah'] ?? '',
            'tahun_ajaran' => $tahun !== null
                ? trim((string) $tahun['nama_tahun'] . ' - ' . (string) $tahun['semester'])
                : null,
            'refresh_minutes' => self::REFRESH_MINUTES,
        ];
    }

    public function getData(): array
    {
        $now = Time::now(self::TZ);
        $tahun = $this->getTahunAktif();

        if ($tahun === null) {
            return [
                'success' => true,
                'message' => 'Tahun Ajaran aktif belum tersedia.',
                'generated_at' => $now->format('Y-m-d H:i:s'),
                'tahun_ajaran' => null,
                'ews_siswa' => [],
                'kelas_belum_presensi' => [],
                'guru_belum_presensi' => [],
            ];
        }

        $idTahun = (int) $tahun['id'];
        $today = $now->format('Y-m-d');
        $tanggalMulai = $now->subDays(13)->format('Y-m-d');
        $hari = $this->hariIndonesia($today);

        return [
            'success' => true,
            'message' => 'Data Digital Signage berhasil dimuat.',
            'generated_at' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
            'tahun_ajaran' => trim((string) $tahun['nama_tahun'] . ' - ' . (string) $tahun['semester']),
            'ews_period' => [
                'mulai' => $tanggalMulai,
                'selesai' => $today,
            ],
            'ews_siswa' => $this->getEwsPresensiSiswa($idTahun, $tanggalMulai, $today),
            'kelas_belum_presensi' => $this->getKelasBelumPresensiAwal($idTahun, $today),
            'guru_belum_presensi' => $this->getGuruBelumPresensiMengajar(
                $idTahun,
                $today,
                $hari,
                Time::now(self::TZ)->format('H:i:s')
            ),
        ];
    }

    public function getEwsPresensiSiswa(
        int $idTahun,
        string $tanggalMulai,
        string $tanggalSelesai
    ): array {
        return $this->db
            ->table('presensi pr')
            ->select(
                'MAX(pr.nama_siswa_snapshot) AS nama_siswa, ' .
                'COALESCE(MAX(k.nama_kelas), \'-\') AS nama_kelas, ' .
                'COUNT(pr.id) AS total_alpha',
                false
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = pr.id_siswa AND ak.id_tahun = ' . $idTahun,
                'left',
                false
            )
            ->join('kelas k', 'k.id = ak.id_kelas AND k.deleted_at IS NULL', 'left', false)
            ->where('pr.id_tahun', $idTahun)
            ->where('pr.sesi', 'Sesi Awal')
            ->where('pr.status', 'Alpha')
            ->where('pr.tanggal >=', $tanggalMulai)
            ->where('pr.tanggal <=', $tanggalSelesai)
            ->where('pr.id_siswa IS NOT NULL', null, false)
            ->groupBy('pr.id_siswa')
            ->having('COUNT(pr.id) >=', self::EWS_MIN_ALPHA, false)
            ->orderBy('total_alpha', 'DESC')
            ->orderBy('nama_siswa', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKelasBelumPresensiAwal(int $idTahun, string $tanggal): array
    {
        return $this->db
            ->table('kelas k')
            ->select([
                'k.nama_kelas',
                'g.nama AS nama_wali',
            ])
            ->join(
                'mapping_wali_kelas mw',
                'mw.id_kelas = k.id AND mw.id_tahun = ' . $idTahun . ' AND mw.deleted_at IS NULL',
                'left',
                false
            )
            ->join('guru g', 'g.id = mw.id_guru AND g.deleted_at IS NULL', 'left', false)
            ->join(
                'presensi pr',
                'pr.id_kelas = k.id AND pr.tanggal = ' . $this->db->escape($tanggal)
                    . " AND pr.sesi = 'Sesi Awal'",
                'left',
                false
            )
            ->where('k.id_tahun', $idTahun)
            ->where('k.deleted_at', null)
            ->where('pr.id IS NULL', null, false)
            ->groupBy('k.id, k.nama_kelas, g.nama')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getGuruBelumPresensiMengajar(
        int $idTahun,
        string $tanggal,
        string $hari,
        string $jamSekarang
    ): array {
        return $this->db
            ->table('jadwal_guru jg')
            ->select([
                'g.nama AS nama_guru',
                'k.nama_kelas',
                'mp.nama_mapel',
                'jg.jam_mulai',
                'jg.jam_selesai',
            ])
            ->join('guru g', 'g.id = jg.id_guru')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->join(
                'presensi_mengajar pm',
                'pm.id_jadwal = jg.id AND pm.tanggal = ' . $this->db->escape($tanggal),
                'left',
                false
            )
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.hari', $hari)
            ->where('jg.status_jadwal', 'Aktif')
            ->where('g.deleted_at', null)
            ->where('k.deleted_at', null)
            ->where('pm.id IS NULL', null, false)
            ->where(
                "ADDTIME(jg.jam_selesai, '00:15:00') < " . $this->db->escape($jamSekarang),
                null,
                false
            )
            ->orderBy('jg.jam_mulai', 'ASC')
            ->orderBy('g.nama', 'ASC')
            ->orderBy('k.nama_kelas', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getTahunAktif(): ?array
    {
        $row = $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function getSystemSettings(): array
    {
        $rows = $this->db
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['nama_sekolah', 'logo_sekolah'])
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row['setting_key']] = trim((string) $row['setting_value']);
        }

        return $result;
    }

    private function hariIndonesia(string $tanggal): string
    {
        $english = (new \DateTimeImmutable($tanggal))->format('l');

        return match ($english) {
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
            default => 'Senin',
        };
    }
}
