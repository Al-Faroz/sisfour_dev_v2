<?php

namespace App\Controllers;

use App\Services\UksService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class UksMaster extends BaseController
{
    protected UksService $service;

    public function __construct()
    {
        $this->service = new UksService();
    }

    public function index()
    {
        $result = $this->service->masterPage($this->currentActorUserId());

        if ($this->requestWantsJson()) {
            return $this->respond($result);
        }

        return $this->response->setBody(
            $this->renderWithLayout('uks/master', [
                'title' => 'Master UKS',
                'initial' => $result,
                'extraJs' => ['assets/js/uks/master.js'],
            ])
        );
    }

    public function create($type)
    {
        return $this->respond(
            $this->service->saveMaster(
                $this->currentActorUserId(),
                (string) $type,
                null,
                $this->payload()
            )
        );
    }

    public function update($type, $id)
    {
        return $this->respond(
            $this->service->saveMaster(
                $this->currentActorUserId(),
                (string) $type,
                (int) $id,
                $this->payload()
            )
        );
    }

    public function delete($type, $id)
    {
        return $this->respond(
            $this->service->deleteMaster(
                $this->currentActorUserId(),
                (string) $type,
                (int) $id
            )
        );
    }

    private function payload(): array
    {
        $contentType = strtolower(trim($this->request->getHeaderLine('Content-Type')));

        if (str_contains($contentType, 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
            } catch (Throwable $e) {
                $json = null;
            }
            if (is_array($json)) {
                return $json;
            }
        }

        $post = $this->request->getPost();
        $raw = $this->request->getRawInput();

        return array_replace(
            is_array($raw) ? $raw : [],
            is_array($post) ? $post : []
        );
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : match ($result['code'] ?? '') {
                        'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
                        'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
                        default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
                    }
            )
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }
}
