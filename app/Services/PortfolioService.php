<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generator Portofolio Guru/Pegawai dari data terbaru.
 * Tidak membuat snapshot tabel portofolio.
 */
class PortfolioService
{
    protected PersonaliaService $personaliaService;

    public function __construct()
    {
        $this->personaliaService = new PersonaliaService();
    }

    public function generate(int $actorUserId, string $ownerType, int $ownerId): array
    {
        $context = $this->personaliaService->getPageContext($actorUserId, $ownerType, $ownerId);
        if (! $context['success']) {
            return $context;
        }

        $owner = $context['owner'];
        $latestAssignment = $context['penugasan'][0] ?? null;
        $title = trim((string) ($latestAssignment['jabatan_tugas'] ?? ''));

        if ($title === '' && $ownerType === 'pegawai') {
            $title = trim((string) ($owner['jabatan_legacy'] ?? ''));
        }
        if ($title === '') {
            $title = $ownerType === 'guru' ? 'Guru' : 'Pegawai';
        }

        $settingRows = db_connect()
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['nama_sekolah', 'alamat_sekolah'])
            ->get()
            ->getResultArray();

        $settings = [];
        foreach ($settingRows as $row) {
            $settings[(string) $row['setting_key']] = trim((string) $row['setting_value']);
        }

        $viewData = $context + [
            'nama_sekolah' => $settings['nama_sekolah'] ?? 'MTsN 4 Jombang',
            'alamat_sekolah' => $settings['alamat_sekolah'] ?? '',
            'jabatan_display' => $title,
            'photo_data_uri' => $this->photoDataUri($ownerType, $owner['foto'] ?? null),
            'printed_at' => $this->formatDateTime(date('Y-m-d H:i:s')),
        ];

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('personalia/portfolio', $viewData));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $identifier = trim((string) (($owner['nip'] ?? '') ?: ($owner['nik'] ?? '') ?: $ownerId));
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $identifier) ?: (string) $ownerId;

        return [
            'success' => true,
            'message' => 'Portofolio berhasil dibuat.',
            'pdf' => $dompdf->output(),
            'filename' => 'portofolio_' . $ownerType . '_' . $safe . '.pdf',
        ];
    }

    private function photoDataUri(string $ownerType, mixed $filename): ?string
    {
        $filename = trim((string) $filename);
        if ($filename === '' || str_contains($filename, '..') || str_contains($filename, "\0")) {
            return null;
        }

        $folder = $ownerType === 'guru' ? 'uploads/foto_guru/' : 'uploads/foto_pegawai/';
        $path = FCPATH . $folder . basename($filename);
        if (! is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function formatDateTime(string $value): string
    {
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        return date('d-m-Y H:i', $ts) . ' WIB';
    }
}
