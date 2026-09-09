<?php

namespace App\Services;

use App\Models\KartuPelajarModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

class KartuPelajarService
{
    private const TZ = 'Asia/Jakarta';
    private const MAX_BATCH = 200;
    private const MAX_PRINT = 200;

    protected KartuPelajarModel $model;
    protected AuthService $authService;
    protected BkScopeService $scopeService;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->model = new KartuPelajarModel();
        $this->authService = new AuthService();
        $this->scopeService = new BkScopeService();
        $this->db = Database::connect();
    }

    public function getPage(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds(
            'kartu_pelajar.view',
            $userId
        );

        if (!$scope['success']) {
            return $scope;
        }

        $manageScope = $this->authService->resolveScope(
            'kartu_pelajar.manage',
            $userId
        );

        $canManage = in_array(
            $manageScope,
            ['SEMUA', 'KELAS_DIAMPU'],
            true
        );

        $limit = max(
            1,
            min(200, (int) ($input['limit'] ?? 50))
        );

        $offset = max(0, (int) ($input['offset'] ?? 0));

        $filter = [
            'status' => in_array(
                ($input['status'] ?? ''),
                ['Aktif', 'Nonaktif'],
                true
            )
                ? (string) $input['status']
                : null,
            'search' => trim(
                (string) ($input['search'] ?? '')
            ) ?: null,
            'id_kelas' => max(
                0,
                (int) ($input['id_kelas'] ?? 0)
            ),
        ];

        $manageIds = null;

        if ($canManage) {
            $manageResult =
                $this->scopeService->resolveStudentIds(
                    'kartu_pelajar.manage',
                    $userId
                );

            $manageIds = $manageResult['success']
                ? $manageResult['student_ids']
                : [];
        }

        $eligibleStudents = $canManage
            ? $this->model->getEligibleStudents($manageIds)
            : [];

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'can_manage' => $canManage,
            'rows' => $this->model->getPaged(
                $filter,
                $scope['student_ids'],
                $limit,
                $offset
            ),
            'total' => $this->model->countFiltered(
                $filter,
                $scope['student_ids']
            ),
            'limit' => $limit,
            'offset' => $offset,
            'eligible_students' => $eligibleStudents,
            'eligible_total' => count($eligibleStudents),
            'class_options' => $this->model->getActiveClassOptions(
                $scope['student_ids']
            ),
            'max_generate_batch' => self::MAX_BATCH,
            'max_print' => self::MAX_PRINT,
        ];
    }

    public function generate(int $userId, array $input): array
    {
        $ids = $input['id_siswa'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];

        return $this->generateIds($userId, $ids);
    }

    public function generateBulk(int $userId, array $input): array
    {
        $scope = $this->scopeService->resolveStudentIds(
            'kartu_pelajar.manage',
            $userId
        );

        if (!$scope['success']) {
            return $scope;
        }

        if (!in_array(
            $scope['scope'],
            ['SEMUA', 'KELAS_DIAMPU'],
            true
        )) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak menerbitkan Kartu Pelajar.'
            );
        }

        $idKelas = max(
            0,
            (int) ($input['id_kelas'] ?? 0)
        );

        /*
         * PENTING:
         * Satu request hanya memproses maksimal MAX_BATCH siswa.
         * Browser akan memanggil endpoint ini berulang sampai remaining = 0.
         *
         * Jangan meng-loop seluruh 1.000+ siswa dalam satu request PHP,
         * karena berisiko timeout meskipun transaksi DB sudah di-chunk.
         */
        $allEligibleIds = $this->model->getEligibleStudentIds(
            $scope['student_ids'],
            $idKelas > 0 ? $idKelas : null
        );

        $remainingBefore = count($allEligibleIds);

        if ($remainingBefore === 0) {
            return [
                'success' => true,
                'message' => 'Tidak ada kartu baru yang perlu digenerate.',
                'generated_count' => 0,
                'existing_count' => 0,
                'processed_count' => 0,
                'remaining_count' => 0,
                'done' => true,
            ];
        }

        $chunk = array_slice(
            $allEligibleIds,
            0,
            self::MAX_BATCH
        );

        $result = $this->generateIds(
            $userId,
            $chunk,
            false
        );

        if (!$result['success']) {
            return $result;
        }

        $generatedCount = count(
            $result['generated_ids'] ?? []
        );

        $existingCount = count(
            $result['existing_ids'] ?? []
        );

        /*
         * Hitung ulang dari database setelah commit.
         * Ini lebih andal daripada sekadar remainingBefore - processed,
         * karena endpoint bersifat idempotent dan mungkin ada perubahan
         * paralel dari user/request lain.
         */
        $remainingAfter = count(
            $this->model->getEligibleStudentIds(
                $scope['student_ids'],
                $idKelas > 0 ? $idKelas : null
            )
        );

        $processedCount =
            $remainingBefore - $remainingAfter;

        $this->log(
            $userId,
            'GENERATE_BULK_BATCH',
            'Kartu Pelajar',
            'Batch bulk generate. Diproses: '
            . $processedCount
            . ', baru: '
            . $generatedCount
            . ', existing: '
            . $existingCount
            . ', tersisa: '
            . $remainingAfter
        );

        return [
            'success' => true,
            'message' =>
                $remainingAfter > 0
                    ? 'Batch selesai. Masih ada '
                        . $remainingAfter
                        . ' siswa.'
                    : 'Bulk generate seluruh kartu selesai.',
            'generated_count' => $generatedCount,
            'existing_count' => $existingCount,
            'processed_count' => $processedCount,
            'remaining_count' => $remainingAfter,
            'done' => $remainingAfter === 0,
        ];
    }

    public function getCard(
        int $userId,
        int $idCard
    ): array {
        $scope = $this->scopeService->resolveStudentIds(
            'kartu_pelajar.view',
            $userId
        );

        if (!$scope['success']) {
            return $scope;
        }

        $card = $this->model->getById($idCard);

        if (!$card) {
            return $this->fail(
                'NOT_FOUND',
                'Kartu Pelajar tidak ditemukan.'
            );
        }

        if (!$this->scopeService->studentAllowed(
            $scope['student_ids'],
            (int) $card['id_siswa']
        )) {
            return $this->fail(
                'FORBIDDEN',
                'Kartu berada di luar scope Anda.'
            );
        }

        $card['kelas'] = $this->model->getCurrentClass(
            (int) $card['id_siswa']
        );

        return [
            'success' => true,
            'scope' => $scope['scope'],
            'card' => $card,
        ];
    }

    public function getCardsForPrint(
        int $userId,
        array $input
    ): array {
        $scope = $this->scopeService->resolveStudentIds(
            'kartu_pelajar.manage',
            $userId
        );

        if (!$scope['success']) {
            return $scope;
        }

        if (!in_array(
            $scope['scope'],
            ['SEMUA', 'KELAS_DIAMPU'],
            true
        )) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak mencetak kartu secara massal.'
            );
        }

        $mode = strtolower(
            trim((string) ($input['mode'] ?? 'selected'))
        );

        if ($mode === 'kelas') {
            $idKelas = (int) ($input['id_kelas'] ?? 0);

            if ($idKelas <= 0) {
                return $this->fail(
                    'VALIDATION',
                    'Kelas wajib dipilih.'
                );
            }

            $cards = $this->model->getActiveCardsForClass(
                $idKelas,
                $scope['student_ids']
            );
        } else {
            $ids = $input['id_kartu'] ?? [];
            $ids = is_array($ids) ? $ids : [$ids];
            $ids = array_values(array_unique(array_filter(
                array_map('intval', $ids)
            )));

            if ($ids === []) {
                return $this->fail(
                    'VALIDATION',
                    'Pilih minimal satu kartu.'
                );
            }

            $cards = $this->model->getByIds($ids);

            foreach ($cards as $card) {
                if (!$this->scopeService->studentAllowed(
                    $scope['student_ids'],
                    (int) $card['id_siswa']
                )) {
                    return $this->fail(
                        'FORBIDDEN',
                        'Terdapat kartu di luar scope Anda.'
                    );
                }
            }
        }

        $cards = array_values(array_filter(
            $cards,
            static fn(array $card): bool =>
                ($card['status_aktif'] ?? '') === 'Aktif'
        ));

        if ($cards === []) {
            return $this->fail(
                'NOT_FOUND',
                'Tidak ada kartu aktif yang dapat dicetak.'
            );
        }

        if (count($cards) > self::MAX_PRINT) {
            return $this->fail(
                'VALIDATION',
                'Maksimum '
                . self::MAX_PRINT
                . ' kartu per file PDF. Cetak per kelas atau per pilihan.'
            );
        }

        foreach ($cards as &$card) {
            $card['kelas'] = $this->model->getCurrentClass(
                (int) $card['id_siswa']
            );
        }
        unset($card);

        return [
            'success' => true,
            'cards' => $cards,
            'count' => count($cards),
        ];
    }

    public function reissue(
        int $userId,
        int $idCard
    ): array {
        $scope = $this->scopeService->resolveStudentIds(
            'kartu_pelajar.manage',
            $userId
        );

        if (!$scope['success']) {
            return $scope;
        }

        $card = $this->model->getById($idCard);

        if (!$card) {
            return $this->fail(
                'NOT_FOUND',
                'Kartu Pelajar tidak ditemukan.'
            );
        }

        if (!$this->scopeService->studentAllowed(
            $scope['student_ids'],
            (int) $card['id_siswa']
        )) {
            return $this->fail(
                'FORBIDDEN',
                'Kartu berada di luar scope Anda.'
            );
        }

        $this->log(
            $userId,
            'REISSUE',
            'Kartu Pelajar',
            'Reissue visual kartu #'
            . $idCard
            . ' nomor '
            . $card['nomor_kartu']
        );

        return [
            'success' => true,
            'message' =>
                'Kartu siap dicetak ulang tanpa mengubah nomor maupun kode verifikasi.',
            'id' => $idCard,
        ];
    }

    public function verifyPublic(string $code): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->fail(
                'NOT_FOUND',
                'Kode verifikasi tidak valid.'
            );
        }

        $card = $this->model->getByVerificationCode(
            $code
        );

        if (!$card) {
            return $this->fail(
                'NOT_FOUND',
                'Kartu tidak ditemukan.'
            );
        }

        $kelas = $this->model->getCurrentClass(
            (int) $card['id_siswa']
        );

        $row = $this->db->table('setting_sistem')
            ->select('setting_value')
            ->where('setting_key', 'nama_sekolah')
            ->get()
            ->getRowArray();

        return [
            'success' => true,
            'card' => [
                'nama' => $card['nama'],
                'kelas' => $kelas['nama_kelas'] ?? '-',
                'status_kartu' => $card['status_aktif'],
                'status_siswa' => $card['status_siswa'],
                'nomor_kartu' => $card['nomor_kartu'],
                'nik_masked' => $this->maskNik(
                    (string) $card['nik']
                ),
                'nama_sekolah' =>
                    $row['setting_value'] ?? 'SisisFour',
            ],
        ];
    }

    private function generateIds(
        int $userId,
        array $ids,
        bool $writeSummaryLog = true
    ): array {
        $scope = $this->scopeService->resolveStudentIds(
            'kartu_pelajar.manage',
            $userId
        );

        if (!$scope['success']) {
            return $scope;
        }

        if (!in_array(
            $scope['scope'],
            ['SEMUA', 'KELAS_DIAMPU'],
            true
        )) {
            return $this->fail(
                'FORBIDDEN',
                'Anda tidak memiliki hak menerbitkan Kartu Pelajar.'
            );
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids)
        )));

        if ($ids === []) {
            return $this->fail(
                'VALIDATION',
                'Pilih minimal satu siswa.'
            );
        }

        if (count($ids) > self::MAX_BATCH) {
            return $this->fail(
                'VALIDATION',
                'Maksimum '
                . self::MAX_BATCH
                . ' siswa per transaksi generate.'
            );
        }

        $students = [];

        foreach ($ids as $idSiswa) {
            if (!$this->scopeService->studentAllowed(
                $scope['student_ids'],
                $idSiswa
            )) {
                return $this->fail(
                    'FORBIDDEN',
                    'Terdapat siswa di luar scope penerbitan kartu Anda.'
                );
            }

            $siswa = $this->model->getStudent($idSiswa);

            if (
                !$siswa
                || ($siswa['status_aktif'] ?? '') !== 'Aktif'
            ) {
                return $this->fail(
                    'INVALID_TARGET',
                    'Kartu hanya dapat diterbitkan untuk siswa aktif.'
                );
            }

            $students[$idSiswa] = $siswa;
        }

        $generated = [];
        $existing = [];

        $this->db->transBegin();

        try {
            foreach (
                array_keys($students)
                as $idSiswa
            ) {
                $active =
                    $this->model->getActiveByStudent(
                        $idSiswa
                    );

                if ($active) {
                    $existing[] = (int) $active['id'];
                    continue;
                }

                $id = $this->model->insert([
                    'id_siswa' => $idSiswa,
                    'nomor_kartu' =>
                        $this->generateCardNumber(),
                    'kode_verifikasi' =>
                        bin2hex(random_bytes(32)),
                    'tanggal_terbit' =>
                        Time::now(self::TZ)
                            ->format('Y-m-d'),
                    'status_aktif' => 'Aktif',
                ]);

                if ($id <= 0) {
                    throw new \RuntimeException(
                        'Kartu gagal disimpan.'
                    );
                }

                $generated[] = $id;
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi generate kartu gagal.'
                );
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            return $this->fail(
                'GENERATE_FAILED',
                'Generate kartu dibatalkan untuk batch ini. Data tidak disimpan sebagian.'
            );
        }

        if ($writeSummaryLog) {
            $this->log(
                $userId,
                'GENERATE',
                'Kartu Pelajar',
                'Generate kartu atomic. Baru: '
                . count($generated)
                . ', existing: '
                . count($existing)
            );
        }

        return [
            'success' => true,
            'message' => 'Generate kartu selesai.',
            'generated_ids' => $generated,
            'existing_ids' => $existing,
        ];
    }

    private function generateCardNumber(): string
    {
        do {
            $candidate =
                'M4J-'
                . date('Y')
                . '-'
                . strtoupper(
                    bin2hex(random_bytes(4))
                );

            $exists = $this->db->table(
                'kartu_pelajar'
            )
                ->where('nomor_kartu', $candidate)
                ->countAllResults() > 0;
        } while ($exists);

        return $candidate;
    }

    private function maskNik(string $nik): string
    {
        $last = substr($nik, -4);

        return str_repeat(
            '*',
            max(0, strlen($nik) - 4)
        ) . $last;
    }

    private function log(
        int $userId,
        string $aksi,
        string $modul,
        string $keterangan
    ): void {
        $this->db->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $aksi,
            'modul' => $modul,
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)
                ->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(
        string $code,
        string $message
    ): array {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
