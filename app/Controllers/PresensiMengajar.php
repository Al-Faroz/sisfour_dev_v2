<?php

namespace App\Controllers;

use App\Services\PresensiMengajarService;
use CodeIgniter\I18n\Time;

/**
 * PresensiMengajar
 *
 * Controller tipis untuk Presensi Mengajar / Jurnal.
 * Business rule dan data-level authorization berada di PresensiMengajarService.
 */
class PresensiMengajar extends BaseController
{
    private const TZ = 'Asia/Jakarta';

    protected PresensiMengajarService $service;

    public function __construct()
    {
        $this->service = new PresensiMengajarService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $tanggal = trim((string) $this->request->getGet('tanggal'));
        $idGuru = (int) $this->request->getGet('id_guru');

        if ($tanggal === '') {
            $tanggal = Time::now(self::TZ)->format('Y-m-d');
        }

        if ($this->wantsJson()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data pilihan Jurnal berhasil dimuat.',
                'data' => [
                    'tanggal' => $tanggal,
                    'guru' => $this->service->getGuruInputOptions($userId, $tanggal),
                    'jadwal' => $idGuru > 0
                        ? $this->service->getJadwalByGuruTanggal($userId, $idGuru, $tanggal)
                        : [],
                ],
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/mengajar', [
                'title' => 'Presensi Mengajar / Jurnal',
                'tanggal' => $tanggal,
                'selectedGuru' => $idGuru,
                'selectedJadwal' => (int) $this->request->getGet('id_jadwal'),
                'guruOptions' => $this->service->getGuruInputOptions($userId, $tanggal),
                'jadwalOptions' => $idGuru > 0
                    ? $this->service->getJadwalByGuruTanggal($userId, $idGuru, $tanggal)
                    : [],
                'tahunAktif' => $this->service->getTahunAktifInfo(),
                'extraJs' => ['assets/js/presensi/mengajar.js'],
            ])
        );
    }

    public function input($idJadwal)
    {
        $userId = (int) session()->get('user_id');
        $tanggal = trim((string) $this->request->getGet('tanggal'));

        if ($tanggal === '') {
            $tanggal = Time::now(self::TZ)->format('Y-m-d');
        }

        $result = $this->service->loadInput(
            $userId,
            (int) $idJadwal,
            $tanggal
        );

        if ($this->wantsJson()) {
            return $this->respondService($result);
        }

        $idGuru = (int) ($result['jadwal']['id_guru'] ?? 0);

        return $this->response->setBody(
            $this->renderWithLayout('presensi/mengajar', [
                'title' => 'Presensi Mengajar / Jurnal',
                'tanggal' => $tanggal,
                'selectedGuru' => $idGuru,
                'selectedJadwal' => (int) $idJadwal,
                'guruOptions' => $this->service->getGuruInputOptions(
                    $userId,
                    $tanggal
                ),
                'jadwalOptions' => $idGuru > 0
                    ? $this->service->getJadwalByGuruTanggal($userId, $idGuru, $tanggal)
                    : [],
                'tahunAktif' => $this->service->getTahunAktifInfo(),
                'initialResult' => $result,
                'extraJs' => ['assets/js/presensi/mengajar.js'],
            ])
        );
    }

    public function save()
    {
        return $this->respondService(
            $this->service->save(
                (int) session()->get('user_id'),
                $this->getPayload()
            ),
            201
        );
    }

    public function laporan()
    {
        $userId = (int) session()->get('user_id');
        $now = Time::now(self::TZ);

        $tanggalMulai = trim((string) $this->request->getGet('tanggal_mulai'));
        $tanggalSelesai = trim((string) $this->request->getGet('tanggal_selesai'));
        $statusRaw = trim((string) $this->request->getGet('status'));
        $limit = max(1, min(500, (int) ($this->request->getGet('limit') ?: 50)));
        $offset = max(0, (int) $this->request->getGet('offset'));

        if ($tanggalMulai === '') {
            $tanggalMulai = $now->format('Y-m-01');
        }

        if ($tanggalSelesai === '') {
            $tanggalSelesai = $now->format('Y-m-d');
        }

        $status = in_array($statusRaw, ['Hadir', 'Izin', 'Sakit'], true)
            ? $statusRaw
            : null;

        if ($this->wantsJson()) {
            return $this->respondService(
                $this->service->getHistori(
                    $userId,
                    $tanggalMulai,
                    $tanggalSelesai,
                    $limit,
                    $offset,
                    $status
                )
            );
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/mengajar_laporan', [
                'title' => 'Laporan Jurnal Mengajar',
                'tanggalMulai' => $tanggalMulai,
                'tanggalSelesai' => $tanggalSelesai,
                'tahunAktif' => $this->service->getTahunAktifInfo(),
                'extraJs' => ['assets/js/presensi/mengajar-laporan.js'],
            ])
        );
    }

    private function getPayload(): array
    {
        $json = $this->request->getJSON(true);

        if (is_array($json) && $json !== []) {
            return $json;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
    }

    private function wantsJson(): bool
    {
        return $this->request->getGet('format') === 'json'
            || $this->request->isAJAX();
    }

    private function respondService(array $result, int $successCode = 200)
    {
        $success = (bool) ($result['success'] ?? false);
        $code = (string) ($result['code'] ?? '');
        $httpCode = $success ? $successCode : $this->httpCodeFor($code);

        return $this->response
            ->setStatusCode($httpCode)
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function httpCodeFor(string $code): int
    {
        return match ($code) {
            'UNAUTHENTICATED' => 401,
            'FORBIDDEN',
            'FORBIDDEN_REVISE',
            'NO_GURU_IDENTITY',
            'OUTSIDE_SCHEDULE_DATE' => 403,
            'ALREADY_SUBMITTED' => 409,
            default => 422,
        };
    }
}
