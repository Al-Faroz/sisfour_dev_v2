<?php

namespace App\Controllers;

use App\Services\KartuPelajarService;
use App\Services\KartuPrintService;
use App\Services\KartuRenderService;
use CodeIgniter\HTTP\ResponseInterface;

class KartuPelajar extends BaseController
{
    protected KartuPelajarService $service;
    protected KartuRenderService $renderService;
    protected KartuPrintService $printService;

    public function __construct()
    {
        $this->service = new KartuPelajarService();
        $this->renderService = new KartuRenderService();
        $this->printService = new KartuPrintService();
    }

    public function daftar()
    {
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond(
                $this->service->getPage(
                    $userId,
                    $this->request->getGet()
                )
            );
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'kartu/daftar',
                [
                    'title' => 'Kartu Pelajar',
                    'initial' => $this->service->getPage(
                        $userId,
                        []
                    ),
                    'extraJs' => [
                        'assets/js/kartu/daftar.js',
                    ],
                ]
            )
        );
    }

    public function generate()
    {
        return $this->respond(
            $this->service->generate(
                $this->currentActorUserId(),
                $this->getPayload()
            )
        );
    }

    public function generateBulk()
    {
        return $this->respond(
            $this->service->generateBulk(
                $this->currentActorUserId(),
                $this->getPayload()
            )
        );
    }

    public function preview($id)
    {
        $result = $this->service->getCard(
            $this->currentActorUserId(),
            (int) $id
        );

        if (!$result['success']) {
            return $this->respond($result);
        }

        $data = $this->renderService->viewData(
            $result['card']
        );

        if ($this->requestWantsJson()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'card' => $result['card'],
                    'qr_payload' => $data['qr_payload'],
                    'verify_url' => $data['verify_url'],
                ],
            ]);
        }

        return $this->response->setBody(
            $this->renderWithLayout(
                'kartu/preview',
                [
                    'title' => 'Preview Kartu Pelajar',
                    ...$data,
                ]
            )
        );
    }

    public function cetak($id)
    {
        return $this->download($id);
    }

    public function download($id)
    {
        $result = $this->service->getCard(
            $this->currentActorUserId(),
            (int) $id
        );

        if (!$result['success']) {
            return $this->respond($result);
        }

        $pdf = $this->renderService->pdf(
            $this->renderService->viewData(
                $result['card']
            )
        );

        $filename =
            'kartu_'
            . preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                $result['card']['nomor_kartu']
            )
            . '.pdf';

        return $this->response
            ->setHeader(
                'Content-Type',
                'application/pdf'
            )
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="'
                . $filename
                . '"'
            )
            ->setBody($pdf);
    }

    public function cetakMassal()
    {
        $payload = $this->getPayload();

        $result = $this->service->getCardsForPrint(
            $this->currentActorUserId(),
            $payload
        );

        if (!$result['success']) {
            return $this->respond($result);
        }

        $side = strtolower(
            trim((string) ($payload['side'] ?? ''))
        );

        $side = in_array(
            $side,
            ['front', 'back'],
            true
        )
            ? $side
            : 'front';

        $pdf = $this->printService->pdf(
            $result['cards'],
            $side
        );

        $label = $side === 'front'
            ? 'DEPAN'
            : 'BELAKANG';

        $filename =
            'kartu_pelajar_A4_'
            . $label
            . '_'
            . date('Ymd_His')
            . '.pdf';

        return $this->response
            ->setHeader(
                'Content-Type',
                'application/pdf'
            )
            ->setHeader(
                'Content-Disposition',
                'attachment; filename="'
                . $filename
                . '"'
            )
            ->setBody($pdf);
    }

    public function reissue($id)
    {
        return $this->respond(
            $this->service->reissue(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function verify($code)
    {
        return view(
            'kartu/verify',
            [
                'title' => 'Verifikasi Kartu Pelajar',
                'result' => $this->service->verifyPublic(
                    (string) $code
                ),
            ]
        );
    }

    private function getPayload(): array
    {
        // FormData / application/x-www-form-urlencoded harus dibaca sebagai
        // POST biasa. Jangan memanggil getJSON() untuk multipart/form-data,
        // karena body tersebut bukan JSON dan akan memicu JSON syntax error.
        $post = $this->request->getPost();

        if (is_array($post) && $post !== []) {
            return $post;
        }

        $contentType = strtolower(
            $this->request->getHeaderLine('Content-Type')
        );

        if (
            str_contains($contentType, 'application/json')
            || str_contains($contentType, '+json')
        ) {
            $json = $this->request->getJSON(true);

            return is_array($json) ? $json : [];
        }

        $raw = $this->request->getRawInput();

        return is_array($raw) ? $raw : [];
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success
                    ? 200
                    : match ($result['code'] ?? '') {
                        'FORBIDDEN',
                        'NO_STUDENT_IDENTITY',
                        'NO_GURU_IDENTITY'
                            => ResponseInterface::HTTP_FORBIDDEN,
                        'NOT_FOUND'
                            => ResponseInterface::HTTP_NOT_FOUND,
                        default
                            => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
                    }
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message']
                    ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
