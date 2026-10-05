<?php

namespace App\Controllers;

use App\Services\KonselingBkExportService;
use App\Services\KonselingBkService;
use App\Services\KonselingKelompokService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class BKKonseling extends BaseController
{
    protected KonselingBkService $service;
    protected KonselingBkExportService $exportService;
    protected KonselingKelompokService $groupService;

    public function __construct()
    {
        $this->service = new KonselingBkService();
        $this->exportService = new KonselingBkExportService();
        $this->groupService = new KonselingKelompokService();
    }

    public function index()
    {
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond($this->service->getPage($userId, $this->request->getGet()));
        }

        return $this->response->setBody(
            $this->renderWithLayout('bk/konseling', [
                'title' => 'Konseling BK',
                'initial' => $this->service->getPage($userId, []),
                'extraJs' => ['assets/js/bk/konseling.js'],
            ])
        );
    }

    public function students($idKelas)
    {
        return $this->respond($this->service->studentsByClass($this->currentActorUserId(), (int) $idKelas));
    }

    public function detail($id)
    {
        return $this->respond($this->service->getDetail($this->currentActorUserId(), (int) $id));
    }

    public function create()
    {
        return $this->respond($this->service->create($this->currentActorUserId(), $this->getPayload()));
    }

    public function update($id)
    {
        return $this->respond($this->service->update($this->currentActorUserId(), (int) $id, $this->getPayload()));
    }

    public function createFollowUp($idKonseling)
    {
        return $this->respond(
            $this->service->createFollowUp(
                $this->currentActorUserId(),
                (int) $idKonseling,
                $this->getPayload()
            )
        );
    }

    public function updateFollowUp($id)
    {
        return $this->respond(
            $this->service->updateFollowUp(
                $this->currentActorUserId(),
                (int) $id,
                $this->getPayload()
            )
        );
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $input = $this->request->getGet();
        $data = $this->service->getExport($userId, $input);
        if (! ($data['success'] ?? false)) return $this->respond($data);

        $groups = $this->groupService->exportData($userId, $input);
        if (! ($groups['success'] ?? false)) return $this->respond($groups);

        $mode = strtolower(trim((string) $this->request->getGet('export_mode')));
        $scope = strtolower(trim((string) $this->request->getGet('export_scope')));
        $mode = in_array($mode, ['ringkas', 'lengkap'], true) ? $mode : 'ringkas';
        $scope = in_array($scope, ['filtered', 'year'], true) ? $scope : 'filtered';

        $file = $this->exportService->export(
            $data['rows'] ?? [],
            $data['tindak_lanjut'] ?? [],
            $groups['rows'] ?? [],
            $groups['members'] ?? [],
            $groups['follow_ups'] ?? [],
            [
                'mode' => $mode,
                'scope' => $scope,
                'tahun_dipilih' => $data['tahun_dipilih'] ?? [],
                'filter' => $data['filter'] ?? [],
            ]
        );
        if (! ($file['success'] ?? false)) return $this->respond($file);

        $path = (string) $file['path'];
        register_shutdown_function(static function () use ($path): void {
            if (is_file($path)) @unlink($path);
        });

        return $this->response->download($path, null)->setFileName((string) $file['filename']);
    }

    private function getPayload(): array
    {
        $contentType = strtolower(trim($this->request->getHeaderLine('Content-Type')));

        if (str_contains($contentType, 'application/json')) {
            try {
                $json = $this->request->getJSON(true);
            } catch (Throwable $e) {
                $json = null;
            }
            if (is_array($json)) return $json;
        }

        $post = $this->request->getPost();
        $raw = $this->request->getRawInput();
        return array_replace(is_array($raw) ? $raw : [], is_array($post) ? $post : []);
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? ResponseInterface::HTTP_OK : $this->httpCode((string) ($result['code'] ?? '')))
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'data' => $result,
            ]);
    }

    private function httpCode(string $code): int
    {
        return match ($code) {
            'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
            'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
            default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
        };
    }
}
