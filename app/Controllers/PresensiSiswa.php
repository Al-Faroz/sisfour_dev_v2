<?php

namespace App\Controllers;

use App\Services\PresensiService;
use CodeIgniter\I18n\Time;

/**
 * PresensiSiswa
 *
 * Controller tipis untuk Presensi Siswa.
 * Business rule dan data-level authorization berada di PresensiService.
 */
class PresensiSiswa extends BaseController
{
    private const TZ = 'Asia/Jakarta';

    protected PresensiService $presensiService;

    public function __construct()
    {
        $this->presensiService = new PresensiService();
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $tanggal = trim((string) $this->request->getGet('tanggal'));

        if ($tanggal === '') {
            $tanggal = Time::now(self::TZ)->format('Y-m-d');
        }

        $kelasOptions = $this->presensiService->getKelasInputOptions(
            $userId,
            $tanggal
        );

        if ($this->wantsJson()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Opsi kelas berhasil dimuat.',
                'data' => [
                    'tanggal' => $tanggal,
                    'kelas' => $kelasOptions,
                ],
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/siswa', [
                'title' => 'Presensi Siswa',
                'tanggal' => $tanggal,
                'selectedKelas' => (int) $this->request->getGet('id_kelas'),
                'selectedSesi' => trim((string) $this->request->getGet('sesi')) ?: 'Sesi Awal',
                'kelasOptions' => $kelasOptions,
                'tahunAktif' => $this->presensiService->getTahunAktifInfo(),
                'extraJs' => ['assets/js/presensi/siswa.js'],
            ])
        );
    }

    public function input($idKelas)
    {
        $userId = (int) session()->get('user_id');
        $tanggal = trim((string) $this->request->getGet('tanggal'));
        $sesi = trim((string) $this->request->getGet('sesi'));

        if ($tanggal === '') {
            $tanggal = Time::now(self::TZ)->format('Y-m-d');
        }

        if ($sesi === '') {
            $sesi = 'Sesi Awal';
        }

        $result = $this->presensiService->loadInput(
            $userId,
            (int) $idKelas,
            $tanggal,
            $sesi
        );

        if ($this->wantsJson()) {
            return $this->respondService($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/siswa', [
                'title' => 'Presensi Siswa',
                'tanggal' => $tanggal,
                'selectedKelas' => (int) $idKelas,
                'selectedSesi' => $sesi,
                'kelasOptions' => $this->presensiService->getKelasInputOptions(
                    $userId,
                    $tanggal
                ),
                'tahunAktif' => $this->presensiService->getTahunAktifInfo(),
                'initialResult' => $result,
                'extraJs' => ['assets/js/presensi/siswa.js'],
            ])
        );
    }

    public function save()
    {
        $payload = $this->getPayload();

        return $this->respondService(
            $this->presensiService->saveBulk(
                (int) session()->get('user_id'),
                $payload,
                false
            ),
            201
        );
    }

    public function revisi($idKelas)
    {
        $userId = (int) session()->get('user_id');
        $tanggal = trim((string) $this->request->getGet('tanggal'));
        $sesi = trim((string) $this->request->getGet('sesi'));

        if ($tanggal === '') {
            $tanggal = Time::now(self::TZ)->format('Y-m-d');
        }

        if ($sesi === '') {
            $sesi = 'Sesi Awal';
        }

        $result = $this->presensiService->loadInput(
            $userId,
            (int) $idKelas,
            $tanggal,
            $sesi
        );

        if ($this->wantsJson()) {
            return $this->respondService($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/siswa', [
                'title' => 'Revisi Presensi Siswa',
                'tanggal' => $tanggal,
                'selectedKelas' => (int) $idKelas,
                'selectedSesi' => $sesi,
                'kelasOptions' => $this->presensiService->getKelasInputOptions(
                    $userId,
                    $tanggal
                ),
                'tahunAktif' => $this->presensiService->getTahunAktifInfo(),
                'initialResult' => $result,
                'forceRevisionMode' => true,
                'extraJs' => ['assets/js/presensi/siswa.js'],
            ])
        );
    }

    public function saveRevisi()
    {
        return $this->respondService(
            $this->presensiService->saveBulk(
                (int) session()->get('user_id'),
                $this->getPayload(),
                true
            )
        );
    }

    public function rekap()
    {
        $userId = (int) session()->get('user_id');
        $now = Time::now(self::TZ);
        $tanggalMulai = trim((string) $this->request->getGet('tanggal_mulai'));
        $tanggalSelesai = trim((string) $this->request->getGet('tanggal_selesai'));
        $idKelas = (int) $this->request->getGet('id_kelas');
        $sesi = trim((string) $this->request->getGet('sesi'));
        $statusRaw = trim((string) $this->request->getGet('status'));
        $limit = max(1, min(500, (int) ($this->request->getGet('limit') ?: 50)));
        $offset = max(0, (int) $this->request->getGet('offset'));

        if ($tanggalMulai === '') {
            $tanggalMulai = $now->format('Y-m-01');
        }

        if ($tanggalSelesai === '') {
            $tanggalSelesai = $now->format('Y-m-d');
        }

        $sesiFilter = in_array($sesi, ['Sesi Awal', 'Sesi Akhir'], true)
            ? $sesi
            : null;

        $statusFilter = in_array($statusRaw, ['Hadir', 'Sakit', 'Izin', 'Alpha'], true)
            ? [$statusRaw]
            : null;

        if ($this->wantsJson()) {
            if ($this->presensiService->isSiswaSelfView($userId)) {
                return $this->respondService(
                    $this->presensiService->getHistoriDiriSiswa(
                        $userId,
                        $tanggalMulai,
                        $tanggalSelesai,
                        $limit,
                        $offset
                    )
                );
            }

            if ($idKelas <= 0) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Pilih kelas untuk menampilkan histori.',
                    'data' => [
                        'rows' => [],
                        'total' => 0,
                        'limit' => $limit,
                        'offset' => $offset,
                    ],
                ]);
            }

            return $this->respondService(
                $this->presensiService->getHistoriKelas(
                    $userId,
                    $idKelas,
                    $tanggalMulai,
                    $tanggalSelesai,
                    $limit,
                    $offset,
                    $sesiFilter,
                    $statusFilter
                )
            );
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/siswa_rekap', [
                'title' => 'Rekap Presensi Siswa',
                'tanggalMulai' => $tanggalMulai,
                'tanggalSelesai' => $tanggalSelesai,
                'kelasOptions' => $this->presensiService->getKelasViewOptions($userId),
                'isSiswaSelfView' => $this->presensiService->isSiswaSelfView($userId),
                'tahunAktif' => $this->presensiService->getTahunAktifInfo(),
                'extraJs' => ['assets/js/presensi/siswa-rekap.js'],
            ])
        );
    }

    public function ews()
    {
        $now = Time::now(self::TZ);
        $tanggalSelesai = trim((string) $this->request->getGet('tanggal_selesai'));
        $tanggalMulai = trim((string) $this->request->getGet('tanggal_mulai'));

        if ($tanggalSelesai === '') {
            $tanggalSelesai = $now->format('Y-m-d');
        }

        if ($tanggalMulai === '') {
            $tanggalMulai = $now->subDays(13)->format('Y-m-d');
        }

        $data = $this->presensiService->getEwsAlpha(
            (int) session()->get('user_id'),
            $tanggalMulai,
            $tanggalSelesai
        );

        if ($this->wantsJson()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data EWS berhasil dimuat.',
                'data' => $data,
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout('presensi/siswa_ews', [
                'title' => 'EWS Presensi Siswa',
                'tanggalMulai' => $tanggalMulai,
                'tanggalSelesai' => $tanggalSelesai,
                'tahunAktif' => $this->presensiService->getTahunAktifInfo(),
                'extraJs' => ['assets/js/presensi/siswa-ews.js'],
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
        $path = trim($this->request->getUri()->getPath(), '/');

        return str_ends_with($path, '/json')
            || $this->request->getGet('format') === 'json'
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
            'ALREADY_SUBMITTED',
            'AMBIGUOUS_SCHEDULE',
            'REVISION_ENDPOINT_REQUIRED',
            'NO_EXISTING_PRESENSI' => 409,
            default => 422,
        };
    }
}
