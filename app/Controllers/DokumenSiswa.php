<?php

namespace App\Controllers;

use App\Services\DokumenSiswaExportService;
use App\Services\DokumenSiswaImportService;
use App\Services\DokumenSiswaService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class DokumenSiswa extends BaseController
{
    protected DokumenSiswaService $service;
    protected DokumenSiswaImportService $importService;
    protected DokumenSiswaExportService $exportService;

    public function __construct()
    {
        $this->service = new DokumenSiswaService();
        $this->importService = new DokumenSiswaImportService();
        $this->exportService = new DokumenSiswaExportService();
    }

    public function index()
    {
        return $this->renderManager(false);
    }

    public function importPage()
    {
        return $this->renderManager(true);
    }

    public function create()
    {
        return $this->respond(
            $this->service->create(
                $this->currentActorUserId(),
                $this->payload()
            )
        );
    }

    public function update($id)
    {
        return $this->respond(
            $this->service->update(
                $this->currentActorUserId(),
                (int) $id,
                $this->payload()
            )
        );
    }

    public function archive($id)
    {
        return $this->respond(
            $this->service->archive(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function template()
    {
        $userId = $this->currentActorUserId();
        $idTahun = (int) ($this->request->getGet('id_tahun') ?? 0);
        $result = $this->importService->templateRows(
            $userId,
            $idTahun,
            (string) ($this->request->getGet('tingkat') ?? ''),
            (int) ($this->request->getGet('id_kelas') ?? 0)
        );

        if (! ($result['success'] ?? false)) {
            return $this->respond($result);
        }

        $period = db_connect()->table('tahun_ajaran')
            ->select('nama_tahun, semester')
            ->where('id', $idTahun)
            ->get()
            ->getRowArray() ?? [];

        $file = $this->exportService->template(
            $result['rows'] ?? [],
            $period
        );

        if (! ($file['success'] ?? false)) {
            return $this->respond($file);
        }

        return $this->downloadAndCleanup(
            (string) $file['path'],
            (string) $file['filename']
        );
    }

    public function previewImport()
    {
        $file = $this->request->getFile('file');
        if ($file === null) {
            return $this->respond([
                'success' => false,
                'code' => 'VALIDATION',
                'message' => 'File XLSX wajib dipilih.',
            ]);
        }

        return $this->respond(
            $this->importService->preview(
                $this->currentActorUserId(),
                $file,
                $this->request->getPost()
            )
        );
    }

    public function commitImport()
    {
        return $this->respond(
            $this->importService->commit(
                $this->currentActorUserId(),
                trim((string) ($this->payload()['token'] ?? ''))
            )
        );
    }

    public function rollbackImport($id)
    {
        return $this->respond(
            $this->importService->rollback(
                $this->currentActorUserId(),
                (int) $id
            )
        );
    }

    public function export()
    {
        $userId = $this->currentActorUserId();
        $data = $this->service->exportData(
            $userId,
            $this->request->getGet()
        );

        if (! ($data['success'] ?? false)) {
            return $this->respond($data);
        }

        $file = $this->exportService->metadata($data['rows'] ?? []);
        if (! ($file['success'] ?? false)) {
            return $this->respond($file);
        }

        return $this->downloadAndCleanup(
            (string) $file['path'],
            (string) $file['filename']
        );
    }

    private function renderManager(bool $focusImport)
    {
        $userId = $this->currentActorUserId();

        if ($this->requestWantsJson()) {
            return $this->respond(
                $this->service->managerPage(
                    $userId,
                    $this->request->getGet()
                )
            );
        }

        return $this->response->setBody(
            $this->renderWithLayout('dokumen_siswa/index', [
                'title' => 'Dokumen Siswa',
                'initial' => $this->service->managerPage(
                    $userId,
                    $this->request->getGet()
                ),
                'focusImport' => $focusImport,
                'extraJs' => ['assets/js/dokumen-siswa.js'],
            ])
        );
    }

    private function payload(): array
    {
        $contentType = strtolower(
            trim($this->request->getHeaderLine('Content-Type'))
        );

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

    private function downloadAndCleanup(string $path, string $filename)
    {
        register_shutdown_function(static function () use ($path): void {
            if (is_file($path)) {
                @unlink($path);
            }
        });

        return $this->response
            ->download($path, null)
            ->setFileName($filename);
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode(
                $success
                    ? ResponseInterface::HTTP_OK
                    : match ($result['code'] ?? '') {
                        'FORBIDDEN',
                        'NO_STUDENT_IDENTITY'
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
