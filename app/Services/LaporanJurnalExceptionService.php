<?php

namespace App\Services;

/**
 * Extension G3.2 untuk Laporan Jurnal canonical.
 *
 * Menjaga satu row = satu Jurnal, menambahkan aggregate S/I/A secara batch,
 * dan detail child on-demand tanpa N+1 query.
 */
class LaporanJurnalExceptionService extends LaporanJurnalService
{
    private const SUMMARY_CHUNK = 1000;

    public function getPaged(int $userId, array $input): array
    {
        if (! $this->schemaReady()) {
            return $this->failEnhanced(
                'SCHEMA_NOT_READY',
                'Schema Jurnal siswa belum tersedia. Jalankan migration terlebih dahulu.'
            );
        }

        $result = parent::getPaged($userId, $input);

        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $result['rows'] = $this->attachSummary(
            is_array($result['rows'] ?? null) ? $result['rows'] : []
        );

        return $result;
    }

    public function getExportData(int $userId, array $input): array
    {
        if (! $this->schemaReady()) {
            return $this->failEnhanced(
                'SCHEMA_NOT_READY',
                'Schema Jurnal siswa belum tersedia. Jalankan migration terlebih dahulu.'
            );
        }

        $result = parent::getExportData($userId, $input);

        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $result['rows'] = $this->attachSummary(
            is_array($result['rows'] ?? null) ? $result['rows'] : []
        );

        return $result;
    }

    public function getDetail(int $userId, int $idJurnal): array
    {
        if (! $this->schemaReady()) {
            return $this->failEnhanced(
                'SCHEMA_NOT_READY',
                'Schema Jurnal siswa belum tersedia. Jalankan migration terlebih dahulu.'
            );
        }

        if ($userId <= 0 || $idJurnal <= 0) {
            return $this->failEnhanced('INVALID_REQUEST', 'Jurnal tidak valid.');
        }

        $scope = $this->authService->resolveScope('laporan_jurnal.view', $userId);

        if (! in_array($scope, ['SEMUA', 'DIRI_SENDIRI'], true)) {
            return $this->failEnhanced(
                'FORBIDDEN',
                'Anda tidak memiliki hak melihat Detail Jurnal.'
            );
        }

        $jurnal = $this->model->getById($idJurnal);

        if ($jurnal === null) {
            return $this->failEnhanced('NOT_FOUND', 'Jurnal tidak ditemukan.');
        }

        if ($scope === 'DIRI_SENDIRI') {
            $user = db_connect()
                ->table('users')
                ->select('id_guru')
                ->where('id', $userId)
                ->get()
                ->getRowArray();

            if ((int) ($user['id_guru'] ?? 0) !== (int) ($jurnal['id_guru'] ?? 0)) {
                return $this->failEnhanced(
                    'FORBIDDEN',
                    'Guru hanya dapat melihat Detail Jurnal dirinya sendiri.'
                );
            }
        }

        $siswa = $this->model->getStudentExceptions($idJurnal);
        $summaryMap = $this->model->getStudentExceptionSummary([$idJurnal]);

        return [
            'success' => true,
            'message' => 'Detail Jurnal berhasil dimuat.',
            'scope' => $scope,
            'jurnal' => $jurnal,
            'siswa' => $siswa,
            'siswa_exception_summary' => $summaryMap[$idJurnal]
                ?? $this->emptySummary(),
        ];
    }

    private function attachSummary(array $rows): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $rows
        )));

        if ($ids === []) {
            return $rows;
        }

        $summaryMap = [];

        foreach (array_chunk($ids, self::SUMMARY_CHUNK) as $chunk) {
            $summaryMap += $this->model->getStudentExceptionSummary($chunk);
        }

        foreach ($rows as &$row) {
            $id = (int) ($row['id'] ?? 0);
            $summary = $summaryMap[$id] ?? $this->emptySummary();
            $row['siswa_exception_summary'] = $summary;
            $row['siswa_exception_count'] = (int) ($summary['total'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    private function emptySummary(): array
    {
        return [
            'Sakit' => 0,
            'Izin' => 0,
            'Alpha' => 0,
            'total' => 0,
        ];
    }

    private function schemaReady(): bool
    {
        $db = db_connect();

        return $db->fieldExists('catatan', 'presensi_mengajar')
            && $db->tableExists('presensi_mengajar_siswa');
    }

    private function failEnhanced(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
