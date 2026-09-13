<?php

namespace App\Services;

/**
 * F14 integration layer untuk lifecycle Manajemen Siswa.
 *
 * Alur placement/kenaikan tetap memakai kontrak ManajemenSiswaService.
 * Kelulusan dan mutasi memakai KelasLifecycleService agar membership terminal,
 * histori, status siswa, dan kartu diproses atomically.
 */
class ManajemenSiswaIntegrityService extends ManajemenSiswaService
{
    public function __construct()
    {
        parent::__construct();
        $this->kelasService = new KelasLifecycleService();
    }

    public function mutasi(
        int $actorUserId,
        int $idSiswa,
        string $status,
        string $keterangan
    ): array {
        // Authorization tetap diputuskan di Service lifecycle.
        // actorUserId dipertahankan pada signature agar kontrak controller stabil.
        if ($actorUserId <= 0) {
            return [
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Anda tidak memiliki hak untuk mengelola siswa.',
            ];
        }

        return $this->kelasService->mutasiSiswa(
            $idSiswa,
            $status,
            $keterangan
        );
    }
}
