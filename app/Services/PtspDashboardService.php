<?php

namespace App\Services;

class PtspDashboardService extends KesehatanDashboardService
{
    private const LIMIT = 5;

    protected function widgetsPtsp(int $userId): array
    {
        $user = $this->getUser($userId);
        $hasIdentity = (int) ($user['id_pegawai'] ?? 0) > 0;
        $tahun = $this->tahunAktif();
        $idTahun = (int) ($tahun['id'] ?? 0);
        $hasPeriod = $idTahun > 0;

        $canLayanan = $this->can($userId, 'ptsp_layanan.view');
        $canPolling = $this->can($userId, 'ptsp_polling.view');
        $canPengaduan = $this->can($userId, 'ptsp_pengaduan.view');

        if (! $hasIdentity) {
            $canLayanan = $canPolling = $canPengaduan = false;
        }

        return [
            'tahun_aktif' => $tahun,
            'period_available' => $hasPeriod,
            'identity_available' => $hasIdentity,
            'access' => [
                'layanan' => $canLayanan,
                'polling' => $canPolling,
                'pengaduan' => $canPengaduan,
            ],
            'layanan_baru' => $canLayanan && $hasPeriod ? $this->countLayanan($idTahun, 'Baru') : null,
            'layanan_diproses' => $canLayanan && $hasPeriod ? $this->countLayanan($idTahun, 'Diproses') : null,
            'pengaduan_masuk' => $canPengaduan && $hasPeriod ? $this->countPengaduan($idTahun, 'Masuk') : null,
            'rata_kepuasan' => $canPolling && $hasPeriod ? $this->averagePolling($idTahun) : null,
            'layanan_terbaru' => $canLayanan && $hasPeriod ? $this->latestLayanan($idTahun) : [],
            'pengaduan_terbaru' => $canPengaduan && $hasPeriod ? $this->latestPengaduan($idTahun) : [],
            'kepuasan_ringkas' => $canPolling && $hasPeriod ? $this->pollingSummary($idTahun) : [],
            'quick_actions' => $this->quickActions($userId),
        ];
    }

    private function countLayanan(int $idTahun, string $status): int
    {
        return $this->db->table('ptsp_layanan')->where('id_tahun', $idTahun)->where('status', $status)->countAllResults();
    }

    private function countPengaduan(int $idTahun, string $status): int
    {
        return $this->db->table('ptsp_pengaduan')->where('id_tahun', $idTahun)->where('status', $status)->countAllResults();
    }

    private function averagePolling(int $idTahun): ?float
    {
        $row = $this->db->table('ptsp_polling')
            ->select('AVG(score) AS avg_score', false)
            ->where('id_tahun', $idTahun)
            ->get()->getRowArray();

        return isset($row['avg_score']) && $row['avg_score'] !== null
            ? round((float) $row['avg_score'], 2)
            : null;
    }

    private function latestLayanan(int $idTahun): array
    {
        return $this->db->table('ptsp_layanan')
            ->select('id, nama_lengkap, kategori_pemohon, jenis_layanan, status, created_at')
            ->where('id_tahun', $idTahun)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')
            ->limit(self::LIMIT)->get()->getResultArray();
    }

    private function latestPengaduan(int $idTahun): array
    {
        return $this->db->table('ptsp_pengaduan')
            ->select('id, judul_laporan, status, tanggal_kejadian, created_at')
            ->where('id_tahun', $idTahun)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')
            ->limit(self::LIMIT)->get()->getResultArray();
    }

    private function pollingSummary(int $idTahun): array
    {
        return $this->db->table('ptsp_polling')
            ->select('tingkat_kepuasan AS label, COUNT(*) AS total', false)
            ->where('id_tahun', $idTahun)
            ->groupBy('tingkat_kepuasan')
            ->orderBy('score', 'DESC')
            ->get()->getResultArray();
    }

    private function quickActions(int $userId): array
    {
        $candidates = [
            ['permission' => 'ptsp_layanan.view', 'label' => 'Layanan PTSP', 'description' => 'Kelola pengajuan layanan', 'icon' => 'bx-file', 'url' => 'ptsp/layanan'],
            ['permission' => 'ptsp_polling.view', 'label' => 'Polling Kepuasan', 'description' => 'Lihat hasil kepuasan', 'icon' => 'bx-happy', 'url' => 'ptsp/polling'],
            ['permission' => 'ptsp_pengaduan.view', 'label' => 'Pengaduan', 'description' => 'Kelola laporan masuk', 'icon' => 'bx-message-square-error', 'url' => 'ptsp/pengaduan'],
            ['permission' => 'ptsp_layanan.view', 'label' => 'Buka Public PTSP', 'description' => 'Buka landing form publik', 'icon' => 'bx-globe', 'url' => 'ptsp'],
        ];

        $result = [];
        foreach ($candidates as $candidate) {
            if (! $this->can($userId, $candidate['permission'])) {
                continue;
            }
            unset($candidate['permission']);
            $result[] = $candidate;
        }

        return $result;
    }
}
