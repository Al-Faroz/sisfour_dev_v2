<?php

namespace App\Services;

use App\Models\KartuPelajarModel;
use CodeIgniter\I18n\Time;

class KartuPelajarService
{
    private const TZ = 'Asia/Jakarta';

    protected KartuPelajarModel $model;
    protected AuthService $authService;
    protected BkScopeService $scopeService;

    public function __construct()
    {
        $this->model = new KartuPelajarModel();
        $this->authService = new AuthService();
        $this->scopeService = new BkScopeService();
    }

    public function getPage(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds('kartu_pelajar.view', $userId);

        if (!$scope['success']) {
            return $scope;
        }

        $manageScope = $this->authService->resolveScope('kartu_pelajar.manage', $userId);
        $canManage = in_array($manageScope, ['SEMUA', 'KELAS_DIAMPU'], true);
        $limit = max(1, min(200, (int) ($input['limit'] ?? 50)));
        $offset = max(0, (int) ($input['offset'] ?? 0));

        $filter = [
            'status' => in_array(($input['status'] ?? ''), ['Aktif', 'Nonaktif'], true)
                ? (string) $input['status']
                : null,
            'search' => trim((string) ($input['search'] ?? '')) ?: null,
        ];

        $manageIds = null;

        if ($canManage) {
            $manageResolved = $this->scopeService->resolveStudentIds('kartu_pelajar.manage', $userId);
            $manageIds = $manageResolved['success'] ? $manageResolved['student_ids'] : [];
        }

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $canManage,
            'rows' => $this->model->getPaged($filter, $scope['student_ids'], $limit, $offset),
            'total' => $this->model->countFiltered($filter, $scope['student_ids']),
            'limit' => $limit,
            'offset' => $offset,
            'eligible_students' => $canManage
                ? $this->model->getEligibleStudents($manageIds)
                : [],
        ];
    }

    public function generate(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds('kartu_pelajar.manage', $userId);

        if (!$scope['success']) {
            return $scope;
        }

        if (!in_array($scope['scope'], ['SEMUA', 'KELAS_DIAMPU'], true)) {
            return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak menerbitkan Kartu Pelajar.');
        }

        $ids = $input['id_siswa'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return $this->fail('VALIDATION', 'Pilih minimal satu siswa.');
        }

        if (count($ids) > 200) {
            return $this->fail('VALIDATION', 'Maksimum 200 siswa per proses generate.');
        }

        $generated = [];
        $existing = [];

        foreach ($ids as $idSiswa) {
            if (!$this->scopeService->studentAllowed($scope['student_ids'], $idSiswa)) {
                return $this->fail('FORBIDDEN', 'Terdapat siswa di luar scope penerbitan kartu Anda.');
            }

            $siswa = $this->model->getStudent($idSiswa);

            if (!$siswa || ($siswa['status_aktif'] ?? '') !== 'Aktif') {
                return $this->fail('INVALID_TARGET', 'Kartu hanya dapat diterbitkan untuk siswa aktif.');
            }

            $active = $this->model->getActiveByStudent($idSiswa);

            if ($active) {
                $existing[] = (int) $active['id'];
                continue;
            }

            $id = $this->model->insert([
                'id_siswa' => $idSiswa,
                'nomor_kartu' => $this->generateCardNumber(),
                'kode_verifikasi' => bin2hex(random_bytes(32)),
                'tanggal_terbit' => Time::now(self::TZ)->format('Y-m-d'),
                'status_aktif' => 'Aktif',
            ]);

            $generated[] = $id;
        }

        $this->log(
            $userId,
            'GENERATE',
            'Kartu Pelajar',
            'Generate kartu. Baru: ' . count($generated) . ', existing: ' . count($existing)
        );

        return [
            'success' => true,
            'message' => 'Generate kartu selesai.',
            'generated_ids' => $generated,
            'existing_ids' => $existing,
        ];
    }

    public function getCard(int $userId, int $idCard): array
    {
        $scope = $this->scopeService->resolveStudentIds('kartu_pelajar.view', $userId);

        if (!$scope['success']) {
            return $scope;
        }

        $card = $this->model->getById($idCard);

        if (!$card) {
            return $this->fail('NOT_FOUND', 'Kartu Pelajar tidak ditemukan.');
        }

        if (!$this->scopeService->studentAllowed($scope['student_ids'], (int) $card['id_siswa'])) {
            return $this->fail('FORBIDDEN', 'Kartu berada di luar scope Anda.');
        }

        $card['kelas'] = $this->model->getCurrentClass((int) $card['id_siswa']);

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'card' => $card,
        ];
    }

    public function reissue(int $userId, int $idCard): array
    {
        $scope = $this->scopeService->resolveStudentIds('kartu_pelajar.manage', $userId);

        if (!$scope['success']) {
            return $scope;
        }

        $card = $this->model->getById($idCard);

        if (!$card) {
            return $this->fail('NOT_FOUND', 'Kartu Pelajar tidak ditemukan.');
        }

        if (!$this->scopeService->studentAllowed($scope['student_ids'], (int) $card['id_siswa'])) {
            return $this->fail('FORBIDDEN', 'Kartu berada di luar scope Anda.');
        }

        // Reissue visual: identitas kartu tidak berubah.
        $this->log(
            $userId,
            'REISSUE',
            'Kartu Pelajar',
            "Reissue visual kartu #{$idCard} nomor {$card['nomor_kartu']}"
        );

        return [
            'success' => true,
            'message' => 'Kartu siap dicetak ulang tanpa mengubah identitas kartu.',
            'id' => $idCard,
        ];
    }

    public function verifyPublic(string $code): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->fail('NOT_FOUND', 'Kode verifikasi tidak valid.');
        }

        $card = $this->model->getByVerificationCode($code);

        if (!$card) {
            return $this->fail('NOT_FOUND', 'Kartu tidak ditemukan.');
        }

        $kelas = $this->model->getCurrentClass((int) $card['id_siswa']);
        $settingRows = db_connect()
            ->table('setting_sistem')
            ->select('setting_key, setting_value')
            ->whereIn('setting_key', ['nama_sekolah'])
            ->get()
            ->getResultArray();

        $setting = [];

        foreach ($settingRows as $row) {
            $setting[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'success' => true,
            'card' => [
                'nama' => $card['nama'],
                'kelas' => $kelas['nama_kelas'] ?? '-',
                'status_kartu' => $card['status_aktif'],
                'status_siswa' => $card['status_siswa'],
                'nomor_kartu' => $card['nomor_kartu'],
                'nik_masked' => $this->maskNik((string) $card['nik']),
                'foto' => $card['foto'],
                'nama_sekolah' => $setting['nama_sekolah'] ?? 'SisisFour',
            ],
        ];
    }

    private function generateCardNumber(): string
    {
        do {
            $candidate = 'M4J-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $exists = db_connect()
                ->table('kartu_pelajar')
                ->where('nomor_kartu', $candidate)
                ->countAllResults() > 0;
        } while ($exists);

        return $candidate;
    }

    private function maskNik(string $nik): string
    {
        $last = substr($nik, -4);

        return str_repeat('*', max(0, strlen($nik) - 4)) . $last;
    }

    private function log(int $userId, string $aksi, string $modul, string $keterangan): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $aksi,
            'modul' => $modul,
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
