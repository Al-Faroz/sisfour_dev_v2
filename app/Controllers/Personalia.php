<?php

namespace App\Controllers;

use App\Services\PersonaliaService;
use App\Services\PortfolioService;
use CodeIgniter\HTTP\ResponseInterface;

class Personalia extends BaseController
{
    protected PersonaliaService $service;
    protected PortfolioService $portfolioService;

    public function __construct()
    {
        $this->service = new PersonaliaService();
        $this->portfolioService = new PortfolioService();
    }

    public function selfGuru()
    {
        return $this->renderSelf('guru');
    }

    public function selfPegawai()
    {
        return $this->renderSelf('pegawai');
    }

    public function masterGuru($id)
    {
        return $this->renderMaster('guru', (int) $id);
    }

    public function masterPegawai($id)
    {
        return $this->renderMaster('pegawai', (int) $id);
    }

    public function saveSelfGuru($category)
    {
        return $this->saveSelf('guru', (string) $category);
    }

    public function saveSelfPegawai($category)
    {
        return $this->saveSelf('pegawai', (string) $category);
    }

    public function deleteSelfGuru($category, $recordId)
    {
        return $this->deleteSelf('guru', (string) $category, (int) $recordId);
    }

    public function deleteSelfPegawai($category, $recordId)
    {
        return $this->deleteSelf('pegawai', (string) $category, (int) $recordId);
    }

    public function saveMasterGuru($ownerId, $category)
    {
        return $this->respond(
            $this->service->saveRecord(
                $this->actorUserId(),
                'guru',
                (int) $ownerId,
                (string) $category,
                $this->request->getPost(),
                $this->request->getFiles()
            )
        );
    }

    public function saveMasterPegawai($ownerId, $category)
    {
        return $this->respond(
            $this->service->saveRecord(
                $this->actorUserId(),
                'pegawai',
                (int) $ownerId,
                (string) $category,
                $this->request->getPost(),
                $this->request->getFiles()
            )
        );
    }

    public function deleteMasterGuru($ownerId, $category, $recordId)
    {
        return $this->respond(
            $this->service->deleteRecord(
                $this->actorUserId(),
                'guru',
                (int) $ownerId,
                (string) $category,
                (int) $recordId
            )
        );
    }

    public function deleteMasterPegawai($ownerId, $category, $recordId)
    {
        return $this->respond(
            $this->service->deleteRecord(
                $this->actorUserId(),
                'pegawai',
                (int) $ownerId,
                (string) $category,
                (int) $recordId
            )
        );
    }

    public function file($category, $recordId, $field)
    {
        $result = $this->service->resolveFile(
            $this->actorUserId(),
            (string) $category,
            (int) $recordId,
            (string) $field
        );

        if (! $result['success']) {
            return $this->respond($result);
        }

        return $this->response
            ->download((string) $result['path'], null)
            ->setFileName((string) $result['filename']);
    }

    public function portfolioSelfGuru()
    {
        return $this->portfolioSelf('guru');
    }

    public function portfolioSelfPegawai()
    {
        return $this->portfolioSelf('pegawai');
    }

    public function portfolioMasterGuru($ownerId)
    {
        return $this->portfolio('guru', (int) $ownerId);
    }

    public function portfolioMasterPegawai($ownerId)
    {
        return $this->portfolio('pegawai', (int) $ownerId);
    }

    private function renderSelf(string $ownerType)
    {
        $resolved = $this->service->resolveSelfOwner($this->actorUserId(), $ownerType);
        if (! $resolved['success']) {
            return $this->renderError($resolved);
        }

        $ownerId = (int) $resolved['owner_id'];
        $context = $this->service->getPageContext($this->actorUserId(), $ownerType, $ownerId);
        if (! $context['success']) {
            return $this->renderError($context);
        }

        $context += [
            'title' => 'Riwayat Personalia ' . ucfirst($ownerType),
            'endpointBase' => base_url('profile/' . $ownerType . '/personalia'),
            'portfolioUrl' => base_url('profile/' . $ownerType . '/portofolio'),
            'backUrl' => base_url('profile/' . $ownerType),
            'backLabel' => 'Kembali ke Profile',
            'extraJs' => ['assets/js/personalia/detail.js'],
        ];

        return $this->response->setBody($this->renderWithLayout('personalia/detail', $context));
    }

    private function renderMaster(string $ownerType, int $ownerId)
    {
        $context = $this->service->getPageContext($this->actorUserId(), $ownerType, $ownerId);
        if (! $context['success']) {
            return $this->renderError($context);
        }

        $context += [
            'title' => 'Personalia ' . ucfirst($ownerType),
            'endpointBase' => base_url('master/' . $ownerType . '/personalia/' . $ownerId),
            'portfolioUrl' => base_url('master/' . $ownerType . '/portofolio/' . $ownerId),
            'backUrl' => base_url('master/' . $ownerType),
            'backLabel' => 'Kembali ke Master ' . ucfirst($ownerType),
            'extraJs' => ['assets/js/personalia/detail.js'],
        ];

        return $this->response->setBody($this->renderWithLayout('personalia/detail', $context));
    }

    private function saveSelf(string $ownerType, string $category)
    {
        $resolved = $this->service->resolveSelfOwner($this->actorUserId(), $ownerType);
        if (! $resolved['success']) {
            return $this->respond($resolved);
        }

        return $this->respond(
            $this->service->saveRecord(
                $this->actorUserId(),
                $ownerType,
                (int) $resolved['owner_id'],
                $category,
                $this->request->getPost(),
                $this->request->getFiles()
            )
        );
    }

    private function deleteSelf(string $ownerType, string $category, int $recordId)
    {
        $resolved = $this->service->resolveSelfOwner($this->actorUserId(), $ownerType);
        if (! $resolved['success']) {
            return $this->respond($resolved);
        }

        return $this->respond(
            $this->service->deleteRecord(
                $this->actorUserId(),
                $ownerType,
                (int) $resolved['owner_id'],
                $category,
                $recordId
            )
        );
    }

    private function portfolioSelf(string $ownerType)
    {
        $resolved = $this->service->resolveSelfOwner($this->actorUserId(), $ownerType);
        if (! $resolved['success']) {
            return $this->respond($resolved);
        }

        return $this->portfolio($ownerType, (int) $resolved['owner_id']);
    }

    private function portfolio(string $ownerType, int $ownerId)
    {
        $result = $this->portfolioService->generate($this->actorUserId(), $ownerType, $ownerId);
        if (! $result['success']) {
            return $this->respond($result);
        }

        $download = $this->request->getGet('download') === '1';
        $disposition = $download ? 'attachment' : 'inline';
        $filename = str_replace('"', '', (string) $result['filename']);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')
            ->setBody((string) $result['pdf']);
    }

    private function renderError(array $result)
    {
        $status = $this->errorStatus($result);
        return $this->response
            ->setStatusCode($status)
            ->setBody(
                $this->renderWithLayout('personalia/detail', [
                    'title' => 'Personalia',
                    'personaliaError' => $result['message'] ?? 'Data personalia tidak tersedia.',
                    'extraJs' => [],
                ])
            );
    }

    private function actorUserId(): int
    {
        return (int) session()->get('user_id');
    }

    private function respond(array $result)
    {
        $success = (bool) ($result['success'] ?? false);

        return $this->response
            ->setStatusCode($success ? 200 : $this->errorStatus($result))
            ->setJSON([
                'status' => $success ? 'success' : 'error',
                'success' => $success,
                'message' => $result['message'] ?? ($success ? 'Berhasil.' : 'Gagal.'),
                'code' => $result['code'] ?? null,
                'data' => $result,
            ]);
    }

    private function errorStatus(array $result): int
    {
        return match ($result['code'] ?? '') {
            'UNAUTHENTICATED' => ResponseInterface::HTTP_UNAUTHORIZED,
            'FORBIDDEN' => ResponseInterface::HTTP_FORBIDDEN,
            'NOT_FOUND' => ResponseInterface::HTTP_NOT_FOUND,
            default => ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
        };
    }
}
