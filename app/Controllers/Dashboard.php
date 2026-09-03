<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use Psr\Log\LoggerInterface;

/**
 * Dashboard — halaman landing setelah login. Widget berbeda per role.
 * Acuan: 08_DASHBOARD_SETTINGS_BACKUP §1
 */
class Dashboard extends BaseController
{
    protected BaseConnection $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = Database::connect();
    }

    public function index()
    {
        $role = session()->get('role');
        $isWali = $this->layoutData['authUser']['is_wali'] ?? false;

        $widgets = match (true) {
            $role === 'admin' || $role === 'operator' => $this->widgetsAdminOperator(),
            $role === 'pimpinan' => $this->widgetsPimpinan(),
            $role === 'bk'       => $this->widgetsBk(),
            $role === 'guru' && $isWali => $this->widgetsGuru(true),
            $role === 'guru'     => $this->widgetsGuru(false),
            $role === 'siswa'    => $this->widgetsSiswa(),
            default => [],
        };

        $viewMap = [
            'admin'    => 'dashboard_admin',
            'operator' => 'dashboard_operator',
            'pimpinan' => 'dashboard_pimpinan',
            'bk'       => 'dashboard_bk',
            'guru'     => $isWali ? 'dashboard_wali' : 'dashboard_guru',
            'siswa'    => 'dashboard_siswa',
        ];

        $view = $viewMap[$role] ?? 'dashboard_guru';

        if ($this->request->getGet('format') === 'json') {
            return $this->response->setJSON(['status' => 'success', 'data' => $widgets]);
        }

        return $this->response->setBody($this->renderWithLayout($view, ['widgets' => $widgets]));
    }

    public function data()
    {
        // Endpoint khusus JSON (dual-output / mobile) — hindari duplikasi, delegasikan.
        return $this->index();
    }

    protected function tahunAktifId(): ?int
    {
        $row = $this->db->table('tahun_ajaran')->select('id')->where('status_aktif', 1)->get()->getRowArray();
        return $row['id'] ?? null;
    }

    protected function widgetsAdminOperator(): array
    {
        return [
            'total_siswa_aktif' => $this->db->table('siswa')->where('status_aktif', 'Aktif')->where('deleted_at', null)->countAllResults(),
            'total_guru'        => $this->db->table('guru')->where('deleted_at', null)->countAllResults(),
            'total_kelas'       => $this->db->table('kelas')->where('deleted_at', null)->countAllResults(),
            'aktivitas_terakhir' => $this->db->table('log_activity')->orderBy('waktu', 'DESC')->limit(10)->get()->getResultArray(),
        ];
    }

    protected function widgetsPimpinan(): array
    {
        $idTahun = $this->tahunAktifId();
        $today = date('Y-m-d');
        $hariIni = $this->hariIndonesia();

        // Kelas belum presensi hari ini (Sesi Awal)
        $totalKelas = $this->db->table('kelas')->where('id_tahun', $idTahun)->where('deleted_at', null)->countAllResults();
        $kelasSudahPresensi = $this->db->table('presensi')
            ->select('id_kelas')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $today)
            ->where('sesi', 'Sesi Awal')
            ->countAllResults();
        $kelasBelumPresensi = max(0, $totalKelas - $kelasSudahPresensi);

        // EWS Radar ringkasan: siswa dengan >=3 Alpha dalam 14 hari terakhir (Sesi Awal)
        $ewsCount = $this->hitungEwsRadar($idTahun);

        // Kasus BK bulan ini
        $kasusBulanIni = $this->db->table('catatan_kasus')
            ->where('MONTH(tanggal)', date('n'))
            ->where('YEAR(tanggal)', date('Y'))
            ->countAllResults();

        // Guru belum input jurnal hari ini (punya jadwal aktif hari ini tapi belum presensi_mengajar)
        $guruTerjadwalHariIni = $this->db->table('jadwal_guru')
            ->select('id_guru')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('hari', $hariIni)
            ->where('status_jadwal', 'Aktif')
            ->get()->getResultArray();
        $totalGuruTerjadwal = count($guruTerjadwalHariIni);

        $guruSudahJurnal = $this->db->table('presensi_mengajar')
            ->select('id_guru')
            ->distinct()
            ->where('id_tahun', $idTahun)
            ->where('tanggal', $today)
            ->countAllResults();

        $guruBelumJurnal = max(0, $totalGuruTerjadwal - $guruSudahJurnal);

        return [
            'kelas_belum_presensi' => $kelasBelumPresensi,
            'ews_radar_count'      => $ewsCount,
            'kasus_bk_bulan_ini'   => $kasusBulanIni,
            'tren_presensi'        => $this->trenPresensiMingguan($idTahun),
            'guru_belum_jurnal'    => $guruBelumJurnal,
            'top20_pelanggaran'    => $this->top20Pelanggaran(),
        ];
    }

    protected function widgetsBk(): array
    {
        $idTahun = $this->tahunAktifId();

        return [
            'kasus_bulan_ini' => $this->db->table('catatan_kasus')
                ->where('MONTH(tanggal)', date('n'))
                ->where('YEAR(tanggal)', date('Y'))
                ->countAllResults(),
            'top20_pelanggaran' => $this->top20Pelanggaran(),
            'ews_radar_count'   => $this->hitungEwsRadar($idTahun),
            'prestasi_terbaru'  => $this->db->table('catatan_prestasi')->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray(),
        ];
    }

    protected function widgetsGuru(bool $isWali): array
    {
        $idGuru = session()->get('id_guru');
        $idTahun = $this->tahunAktifId();
        $hariIni = $this->hariIndonesia();

        $jadwalHariIni = $this->db->table('jadwal_guru jg')
            ->select('jg.*, k.nama_kelas, mp.nama_mapel')
            ->join('kelas k', 'k.id = jg.id_kelas')
            ->join('mata_pelajaran mp', 'mp.id = jg.id_mapel')
            ->where('jg.id_guru', $idGuru)
            ->where('jg.id_tahun', $idTahun)
            ->where('jg.hari', $hariIni)
            ->where('jg.status_jadwal', 'Aktif')
            ->orderBy('jg.jam_mulai', 'ASC')
            ->get()->getResultArray();

        $data = [
            'jadwal_hari_ini'       => $jadwalHariIni,
            'riwayat_jurnal_terakhir' => $this->db->table('presensi_mengajar')
                ->where('id_guru', $idGuru)
                ->orderBy('tanggal', 'DESC')
                ->limit(5)
                ->get()->getResultArray(),
        ];

        if ($isWali) {
            $kelasDiampu = $this->authService->getKelasDiampu((int) $idGuru, $idTahun);
            $data['kelas_diampu'] = empty($kelasDiampu) ? [] :
                $this->db->table('siswa s')
                    ->select('s.id, s.nama, s.nisn, s.jenis_kelamin')
                    ->join('anggota_kelas ak', 'ak.id_siswa = s.id')
                    ->whereIn('ak.id_kelas', $kelasDiampu)
                    ->where('ak.id_tahun', $idTahun)
                    ->where('s.deleted_at', null)
                    ->get()->getResultArray();
        }

        return $data;
    }

    protected function widgetsSiswa(): array
    {
        $idSiswa = session()->get('id_siswa');

        $rekap = $this->db->table('presensi')
            ->select("
                SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) AS alpha
            ")
            ->where('id_siswa', $idSiswa)
            ->where('sesi', 'Sesi Awal')
            ->where('MONTH(tanggal)', date('n'))
            ->where('YEAR(tanggal)', date('Y'))
            ->get()->getRowArray();

        return [
            'rekap_presensi_bulan_ini' => $rekap,
            'riwayat_prestasi' => $this->db->table('catatan_prestasi')->where('id_siswa', $idSiswa)->orderBy('tanggal', 'DESC')->get()->getResultArray(),
            'riwayat_pelanggaran' => $this->db->table('catatan_kasus ck')
                ->select('ck.*, rp.nama_pelanggaran, rp.kategori, rp.poin')
                ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
                ->where('ck.id_siswa', $idSiswa)
                ->orderBy('ck.tanggal', 'DESC')
                ->get()->getResultArray(),
        ];
    }

    /** EWS Radar: siswa dengan >= 3 Alpha dalam 14 hari terakhir, Sesi Awal saja. */
    protected function hitungEwsRadar(?int $idTahun): int
    {
        $sejak = date('Y-m-d', strtotime('-14 days'));

        return $this->db->table('presensi')
            ->select('id_siswa')
            ->where('sesi', 'Sesi Awal')
            ->where('status', 'Alpha')
            ->where('tanggal >=', $sejak)
            ->where('id_tahun', $idTahun)
            ->groupBy('id_siswa')
            ->having('COUNT(id) >=', 3)
            ->get()->getNumRows();
    }

    /** Top 20 siswa dengan akumulasi poin pelanggaran tertinggi. */
    protected function top20Pelanggaran(): array
    {
        return $this->db->table('catatan_kasus ck')
            ->select('ck.id_siswa, s.nama, SUM(rp.poin) AS total_poin')
            ->join('ref_pelanggaran rp', 'rp.id = ck.id_pelanggaran')
            ->join('siswa s', 's.id = ck.id_siswa')
            ->groupBy('ck.id_siswa')
            ->orderBy('total_poin', 'DESC')
            ->limit(20)
            ->get()->getResultArray();
    }

    /** Tren presensi 7 hari terakhir (persentase Hadir dari Sesi Awal). */
    protected function trenPresensiMingguan(?int $idTahun): array
    {
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-{$i} days"));

            $total = $this->db->table('presensi')->where('tanggal', $tanggal)->where('sesi', 'Sesi Awal')->where('id_tahun', $idTahun)->countAllResults();
            $hadir = $this->db->table('presensi')->where('tanggal', $tanggal)->where('sesi', 'Sesi Awal')->where('id_tahun', $idTahun)->where('status', 'Hadir')->countAllResults();

            $result[] = [
                'tanggal' => $tanggal,
                'persen_hadir' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
            ];
        }
        return $result;
    }

    protected function hariIndonesia(): string
    {
        $map = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        return $map[date('l')] ?? 'Senin';
    }
}
