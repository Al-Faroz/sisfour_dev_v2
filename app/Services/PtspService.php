<?php

namespace App\Services;

use App\Models\PtspLayananModel;
use App\Models\PtspPengaduanModel;
use App\Models\PtspPollingModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use Throwable;

class PtspService
{
    private const MAX_EXPORT_ROWS = 50000;
    private const STORAGE_ROOT = 'uploads/ptsp/pengaduan/';

    public const KATEGORI = [
        'Siswa/Siswi',
        'Alumni',
        'Wali Murid',
        'Guru/Staff',
        'Umum/Instansi Lain',
    ];

    public const JENIS_LAYANAN = [
        'Legalisir Ijazah/Raport',
        'Rekomendasi Mutasi Siswa',
        'Surat Keterangan Lulus (SKL)',
        'Surat Keterangan Aktif Belajar',
        'Surat Keterangan Lainnya',
        'Layanan Kepegawaian',
        'Pendaftaran Siswa Baru',
        'Lainnya',
    ];

    public const STATUS_LAYANAN = ['Baru', 'Diproses', 'Selesai'];

    public const KEPUASAN = [
        'Sangat Memuaskan' => 5,
        'Memuaskan' => 4,
        'Cukup Memadai' => 3,
        'Kurang Memuaskan' => 2,
        'Sangat Mengecewakan' => 1,
    ];

    public const KLASIFIKASI = [
        'Pengaduan',
        'Aspirasi',
        'Permintaan Informasi',
    ];

    public const STATUS_PENGADUAN = [
        'Masuk',
        'Diverifikasi',
        'Diproses',
        'Selesai',
    ];

    protected BaseConnection $db;
    protected AuthService $authService;
    protected ActivityLogService $activityLog;
    protected UploadService $uploadService;
    protected PtspLayananModel $layananModel;
    protected PtspPollingModel $pollingModel;
    protected PtspPengaduanModel $pengaduanModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
        $this->activityLog = new ActivityLogService();
        $this->uploadService = new UploadService();
        $this->layananModel = new PtspLayananModel();
        $this->pollingModel = new PtspPollingModel();
        $this->pengaduanModel = new PtspPengaduanModel();
    }

    public function publicOptions(): array
    {
        return [
            'kategori' => self::KATEGORI,
            'jenis_layanan' => self::JENIS_LAYANAN,
            'kepuasan' => array_keys(self::KEPUASAN),
            'klasifikasi' => self::KLASIFIKASI,
        ];
    }

    public function layananPage(int $userId, array $input): array
    {
        if (! $this->isAll('ptsp_layanan.view', $userId)) {
            return $this->forbidden('melihat Layanan PTSP');
        }

        $context = $this->periodContext($input);
        if (! $context['success']) {
            return $context;
        }

        $limit = max(1, min(100, (int) ($input['limit'] ?? 25)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $filter = [
            'id_tahun' => (int) $context['tahun_dipilih']['id'],
            'status' => $this->option($input['status'] ?? null, self::STATUS_LAYANAN),
            'kategori' => $this->option($input['kategori'] ?? null, self::KATEGORI),
            'jenis_layanan' => $this->option($input['jenis_layanan'] ?? null, self::JENIS_LAYANAN),
            'search' => $this->nullableText($input['search'] ?? null, 120),
        ];

        return [
            'success' => true,
            'rows' => $this->layananModel->getPaged($filter, $limit, $offset),
            'total' => $this->layananModel->countFiltered($filter),
            'limit' => $limit,
            'offset' => $offset,
            'filter' => $filter,
            'tahun_options' => $context['tahun_options'],
            'tahun_dipilih' => $context['tahun_dipilih'],
            'can_manage' => $this->isAll('ptsp_layanan.manage', $userId),
            'can_export' => $this->isAll('ptsp_layanan.export', $userId),
            'can_delete' => $this->isAll('ptsp_layanan.delete', $userId),
            'options' => [
                'kategori' => self::KATEGORI,
                'jenis_layanan' => self::JENIS_LAYANAN,
                'status' => self::STATUS_LAYANAN,
            ],
        ];
    }

    public function pollingPage(int $userId, array $input): array
    {
        if (! $this->isAll('ptsp_polling.view', $userId)) {
            return $this->forbidden('melihat Polling Kepuasan PTSP');
        }

        $context = $this->periodContext($input);
        if (! $context['success']) {
            return $context;
        }

        $limit = max(1, min(100, (int) ($input['limit'] ?? 25)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $filter = [
            'id_tahun' => (int) $context['tahun_dipilih']['id'],
            'kategori' => $this->option($input['kategori'] ?? null, self::KATEGORI),
            'tingkat_kepuasan' => $this->option($input['tingkat_kepuasan'] ?? null, array_keys(self::KEPUASAN)),
            'search' => $this->nullableText($input['search'] ?? null, 120),
        ];

        return [
            'success' => true,
            'rows' => $this->pollingModel->getPaged($filter, $limit, $offset),
            'total' => $this->pollingModel->countFiltered($filter),
            'limit' => $limit,
            'offset' => $offset,
            'filter' => $filter,
            'tahun_options' => $context['tahun_options'],
            'tahun_dipilih' => $context['tahun_dipilih'],
            'can_export' => $this->isAll('ptsp_polling.export', $userId),
            'can_delete' => $this->isAll('ptsp_polling.delete', $userId),
            'options' => [
                'kategori' => self::KATEGORI,
                'kepuasan' => array_keys(self::KEPUASAN),
            ],
        ];
    }

    public function pengaduanPage(int $userId, array $input): array
    {
        if (! $this->isAll('ptsp_pengaduan.view', $userId)) {
            return $this->forbidden('melihat Pengaduan PTSP');
        }

        $context = $this->periodContext($input);
        if (! $context['success']) {
            return $context;
        }

        $limit = max(1, min(100, (int) ($input['limit'] ?? 25)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $filter = [
            'id_tahun' => (int) $context['tahun_dipilih']['id'],
            'status' => $this->option($input['status'] ?? null, self::STATUS_PENGADUAN),
            'klasifikasi' => $this->option($input['klasifikasi'] ?? null, self::KLASIFIKASI),
            'tanggal_mulai' => $this->date($input['tanggal_mulai'] ?? null, false),
            'tanggal_selesai' => $this->date($input['tanggal_selesai'] ?? null, false),
            'search' => $this->nullableText($input['search'] ?? null, 120),
        ];

        return [
            'success' => true,
            'rows' => $this->pengaduanModel->getPaged($filter, $limit, $offset),
            'total' => $this->pengaduanModel->countFiltered($filter),
            'limit' => $limit,
            'offset' => $offset,
            'filter' => $filter,
            'tahun_options' => $context['tahun_options'],
            'tahun_dipilih' => $context['tahun_dipilih'],
            'can_manage' => $this->isAll('ptsp_pengaduan.manage', $userId),
            'can_export' => $this->isAll('ptsp_pengaduan.export', $userId),
            'can_delete' => $this->isAll('ptsp_pengaduan.delete', $userId),
            'options' => [
                'klasifikasi' => self::KLASIFIKASI,
                'status' => self::STATUS_PENGADUAN,
            ],
        ];
    }

    public function submitLayanan(array $input, ?int $actorUserId = null): array
    {
        if ($actorUserId !== null && $actorUserId > 0 && ! $this->isAll('ptsp_layanan.manage', $actorUserId)) {
            return $this->forbidden('membuat Layanan PTSP');
        }

        $tahun = $this->activeYear();
        if (! $tahun) {
            return $this->fail('NO_ACTIVE_PERIOD', 'Tidak ada Tahun Ajaran aktif. Pengajuan belum dapat disimpan.');
        }

        $nama = $this->text($input['nama_lengkap'] ?? null, 150);
        $kategori = $this->option($input['kategori_pemohon'] ?? null, self::KATEGORI);
        $whatsapp = $this->whatsapp($input['nomor_whatsapp'] ?? null, true);
        $jenis = $this->option($input['jenis_layanan'] ?? null, self::JENIS_LAYANAN);
        $tujuan = $this->text($input['tujuan_keterangan'] ?? null, 2000);

        if ($nama === '' || $kategori === null || $whatsapp === null || $jenis === null || $tujuan === '') {
            return $this->fail('VALIDATION', 'Nama, kategori pemohon, WhatsApp, jenis layanan, dan tujuan/keterangan wajib diisi dengan benar.');
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->layananModel->insert([
            'id_tahun' => (int) $tahun['id'],
            'nama_lengkap' => $nama,
            'kategori_pemohon' => $kategori,
            'nomor_whatsapp' => $whatsapp,
            'jenis_layanan' => $jenis,
            'tujuan_keterangan' => $tujuan,
            'status' => 'Baru',
            'created_by' => $actorUserId && $actorUserId > 0 ? $actorUserId : null,
            'updated_by' => $actorUserId && $actorUserId > 0 ? $actorUserId : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($id <= 0) {
            return $this->fail('SAVE_FAILED', 'Pengajuan layanan gagal disimpan.');
        }

        if ($actorUserId && $actorUserId > 0) {
            $this->activityLog->write($actorUserId, 'CREATE', 'PTSP Layanan', "Membuat Layanan PTSP #{$id}.");
        }

        return [
            'success' => true,
            'message' => 'Pengajuan layanan berhasil disimpan.',
            'receipt' => [
                'submitted_at' => $now,
                'nama_lengkap' => $nama,
                'kategori_pemohon' => $kategori,
                'jenis_layanan' => $jenis,
                'tujuan_keterangan' => $tujuan,
            ],
        ];
    }

    public function submitPolling(array $input): array
    {
        $tahun = $this->activeYear();
        if (! $tahun) {
            return $this->fail('NO_ACTIVE_PERIOD', 'Tidak ada Tahun Ajaran aktif. Polling belum dapat disimpan.');
        }

        $nama = $this->nullableText($input['nama_lengkap'] ?? null, 150);
        $kategori = $this->option($input['kategori_responden'] ?? null, self::KATEGORI);
        $whatsappRaw = trim((string) ($input['nomor_whatsapp'] ?? ''));
        $whatsapp = $whatsappRaw !== '' ? $this->whatsapp($whatsappRaw, false) : null;
        $kepuasan = $this->option($input['tingkat_kepuasan'] ?? null, array_keys(self::KEPUASAN));
        $masukan = $this->nullableText($input['masukan_saran'] ?? null, 3000);

        if ($whatsappRaw !== '' && $whatsapp === null) {
            return $this->fail('VALIDATION', 'Nomor WhatsApp tidak valid.');
        }
        if ($kepuasan === null) {
            return $this->fail('VALIDATION', 'Tingkat kepuasan wajib dipilih.');
        }

        $id = $this->pollingModel->insert([
            'id_tahun' => (int) $tahun['id'],
            'nama_lengkap' => $nama,
            'kategori_responden' => $kategori,
            'nomor_whatsapp' => $whatsapp,
            'tingkat_kepuasan' => $kepuasan,
            'score' => self::KEPUASAN[$kepuasan],
            'masukan_saran' => $masukan,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $id > 0
            ? ['success' => true, 'message' => 'Terima kasih. Polling kepuasan berhasil dikirim.']
            : $this->fail('SAVE_FAILED', 'Polling kepuasan gagal disimpan.');
    }

    public function submitPengaduan(array $input, ?UploadedFile $lampiran): array
    {
        $tahun = $this->activeYear();
        if (! $tahun) {
            return $this->fail('NO_ACTIVE_PERIOD', 'Tidak ada Tahun Ajaran aktif. Laporan belum dapat disimpan.');
        }

        $klasifikasi = $input['klasifikasi'] ?? [];
        if (! is_array($klasifikasi)) {
            $klasifikasi = [$klasifikasi];
        }
        $klasifikasi = array_values(array_unique(array_filter(array_map(
            fn ($value): ?string => $this->option($value, self::KLASIFIKASI),
            $klasifikasi
        ))));

        $judul = $this->text($input['judul_laporan'] ?? null, 200);
        $isi = $this->text($input['isi_laporan'] ?? null, 5000);
        $tanggal = $this->date($input['tanggal_kejadian'] ?? null, false);

        if ($klasifikasi === [] || $judul === '' || $isi === '') {
            return $this->fail('VALIDATION', 'Minimal satu klasifikasi, judul laporan, dan isi laporan wajib diisi.');
        }

        $stored = null;
        $relativePath = null;

        try {
            if ($lampiran instanceof UploadedFile && $lampiran->getError() !== UPLOAD_ERR_NO_FILE) {
                $dest = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, self::STORAGE_ROOT);
                $stored = $this->uploadService->processPersonaliaDocument($lampiran, $dest, 'pengaduan');
                $relativePath = self::STORAGE_ROOT . $stored['filename'];
            }

            $this->db->transBegin();

            $id = $this->pengaduanModel->insert([
                'id_tahun' => (int) $tahun['id'],
                'judul_laporan' => $judul,
                'isi_laporan' => $isi,
                'tanggal_kejadian' => $tanggal,
                'status' => 'Masuk',
                'lampiran_path' => $relativePath,
                'lampiran_nama_asli' => $stored['original_name'] ?? null,
                'lampiran_mime' => $stored['mime_type'] ?? null,
                'updated_by' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($id <= 0) {
                throw new \RuntimeException('Insert pengaduan gagal.');
            }

            $this->pengaduanModel->replaceClassifications($id, $klasifikasi);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi pengaduan gagal.');
            }

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Laporan berhasil dikirim.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            if ($relativePath !== null) {
                $this->deleteStoredFile($relativePath);
            }

            return $this->fail(
                'SAVE_FAILED',
                ENVIRONMENT === 'development' ? $e->getMessage() : 'Laporan gagal disimpan.'
            );
        }
    }

    public function updateLayananStatus(int $userId, int $id, array $input): array
    {
        if (! $this->isAll('ptsp_layanan.manage', $userId)) {
            return $this->forbidden('mengubah status Layanan PTSP');
        }

        $row = $this->layananModel->getById($id);
        if (! $row) {
            return $this->fail('NOT_FOUND', 'Layanan PTSP tidak ditemukan.');
        }

        $target = $this->option($input['status'] ?? null, self::STATUS_LAYANAN);
        if ($target === null || ! $this->canTransitionLayanan((string) $row['status'], $target)) {
            return $this->fail('VALIDATION', 'Perubahan status Layanan PTSP tidak valid.');
        }

        if ($target === (string) $row['status']) {
            return ['success' => true, 'message' => 'Status tidak berubah.'];
        }

        $this->layananModel->update($id, [
            'status' => $target,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->activityLog->write($userId, 'UPDATE', 'PTSP Layanan', "Status Layanan PTSP #{$id}: {$row['status']} -> {$target}.");

        return ['success' => true, 'message' => 'Status layanan berhasil diperbarui.'];
    }

    public function updatePengaduanStatus(int $userId, int $id, array $input): array
    {
        if (! $this->isAll('ptsp_pengaduan.manage', $userId)) {
            return $this->forbidden('mengubah status Pengaduan');
        }

        $row = $this->pengaduanModel->getById($id);
        if (! $row) {
            return $this->fail('NOT_FOUND', 'Pengaduan tidak ditemukan.');
        }

        $target = $this->option($input['status'] ?? null, self::STATUS_PENGADUAN);
        if ($target === null || ! $this->canTransitionPengaduan((string) $row['status'], $target)) {
            return $this->fail('VALIDATION', 'Perubahan status Pengaduan tidak valid.');
        }

        if ($target === (string) $row['status']) {
            return ['success' => true, 'message' => 'Status tidak berubah.'];
        }

        $this->pengaduanModel->update($id, [
            'status' => $target,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->activityLog->write($userId, 'UPDATE', 'PTSP Pengaduan', "Status Pengaduan #{$id}: {$row['status']} -> {$target}.");

        return ['success' => true, 'message' => 'Status pengaduan berhasil diperbarui.'];
    }

    public function deleteLayanan(int $userId, int $id): array
    {
        if (! $this->isAll('ptsp_layanan.delete', $userId)) {
            return $this->forbidden('menghapus Layanan PTSP');
        }
        if (! $this->layananModel->getById($id)) {
            return $this->fail('NOT_FOUND', 'Layanan PTSP tidak ditemukan.');
        }

        $this->layananModel->hardDelete($id);
        $this->activityLog->write($userId, 'DELETE', 'PTSP Layanan', "Hard delete Layanan PTSP #{$id}.");

        return ['success' => true, 'message' => 'Layanan PTSP berhasil dihapus permanen.'];
    }

    public function deletePolling(int $userId, int $id): array
    {
        if (! $this->isAll('ptsp_polling.delete', $userId)) {
            return $this->forbidden('menghapus Polling Kepuasan');
        }
        if (! $this->pollingModel->getById($id)) {
            return $this->fail('NOT_FOUND', 'Polling Kepuasan tidak ditemukan.');
        }

        $this->pollingModel->hardDelete($id);
        $this->activityLog->write($userId, 'DELETE', 'PTSP Polling', "Hard delete Polling PTSP #{$id}.");

        return ['success' => true, 'message' => 'Polling Kepuasan berhasil dihapus permanen.'];
    }

    public function deletePengaduan(int $userId, int $id): array
    {
        if (! $this->isAll('ptsp_pengaduan.delete', $userId)) {
            return $this->forbidden('menghapus Pengaduan');
        }

        $row = $this->pengaduanModel->getById($id);
        if (! $row) {
            return $this->fail('NOT_FOUND', 'Pengaduan tidak ditemukan.');
        }

        $this->db->transBegin();
        try {
            $this->pengaduanModel->hardDelete($id);
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Delete pengaduan gagal.');
            }
            $this->db->transCommit();

            $this->deleteStoredFile($row['lampiran_path'] ?? null);
            $this->activityLog->write($userId, 'DELETE', 'PTSP Pengaduan', "Hard delete Pengaduan #{$id}.");

            return ['success' => true, 'message' => 'Pengaduan berhasil dihapus permanen.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('DELETE_FAILED', 'Pengaduan gagal dihapus.');
        }
    }

    public function resolveAttachment(int $userId, int $id): array
    {
        if (! $this->isAll('ptsp_pengaduan.view', $userId)) {
            return $this->forbidden('membuka lampiran Pengaduan');
        }

        $row = $this->pengaduanModel->getById($id);
        if (! $row || empty($row['lampiran_path'])) {
            return $this->fail('NOT_FOUND', 'Lampiran Pengaduan tidak ditemukan.');
        }

        $relative = str_replace('\\', '/', (string) $row['lampiran_path']);
        if (! preg_match('#^uploads/ptsp/pengaduan/[A-Za-z0-9_.-]+$#', $relative)) {
            return $this->fail('NOT_FOUND', 'Path lampiran tidak valid.');
        }

        $path = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_file($path)) {
            return $this->fail('NOT_FOUND', 'File lampiran tidak ditemukan.');
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return [
            'success' => true,
            'path' => $path,
            'filename' => basename((string) ($row['lampiran_nama_asli'] ?? ('lampiran.' . $ext))),
            'mime_type' => $mime,
        ];
    }

    public function layananExportData(int $userId, array $input): array
    {
        return $this->exportData(
            'ptsp_layanan.export',
            $userId,
            $input,
            fn (array $filter): int => $this->layananModel->countFiltered($filter),
            fn (array $filter): array => $this->layananModel->getForExport($filter, self::MAX_EXPORT_ROWS),
            [
                'status' => self::STATUS_LAYANAN,
                'kategori' => self::KATEGORI,
                'jenis_layanan' => self::JENIS_LAYANAN,
            ]
        );
    }

    public function pollingExportData(int $userId, array $input): array
    {
        return $this->exportData(
            'ptsp_polling.export',
            $userId,
            $input,
            fn (array $filter): int => $this->pollingModel->countFiltered($filter),
            fn (array $filter): array => $this->pollingModel->getForExport($filter, self::MAX_EXPORT_ROWS),
            [
                'kategori' => self::KATEGORI,
                'tingkat_kepuasan' => array_keys(self::KEPUASAN),
            ]
        );
    }

    public function pengaduanExportData(int $userId, array $input): array
    {
        return $this->exportData(
            'ptsp_pengaduan.export',
            $userId,
            $input,
            fn (array $filter): int => $this->pengaduanModel->countFiltered($filter),
            fn (array $filter): array => $this->pengaduanModel->getForExport($filter, self::MAX_EXPORT_ROWS),
            [
                'status' => self::STATUS_PENGADUAN,
                'klasifikasi' => self::KLASIFIKASI,
            ],
            true
        );
    }

    public function publicStatistics(string $type, array $input): array
    {
        $context = $this->periodContext($input);
        if (! $context['success']) {
            return $context;
        }

        $idTahun = (int) $context['tahun_dipilih']['id'];
        $period = [
            'id_tahun' => $idTahun,
            'nama_tahun' => (string) $context['tahun_dipilih']['nama_tahun'],
            'semester' => (string) $context['tahun_dipilih']['semester'],
        ];

        return match ($type) {
            'layanan' => [
                'success' => true,
                'period' => $period,
                'total' => $this->countTable('ptsp_layanan', $idTahun),
                'per_status' => $this->groupCounts('ptsp_layanan', 'status', $idTahun),
                'per_kategori' => $this->groupCounts('ptsp_layanan', 'kategori_pemohon', $idTahun),
                'per_jenis_layanan' => $this->groupCounts('ptsp_layanan', 'jenis_layanan', $idTahun),
            ],
            'polling' => $this->pollingStatistics($idTahun, $period),
            'pengaduan' => [
                'success' => true,
                'period' => $period,
                'total' => $this->countTable('ptsp_pengaduan', $idTahun),
                'per_status' => $this->groupCounts('ptsp_pengaduan', 'status', $idTahun),
                'per_klasifikasi' => $this->complaintClassificationCounts($idTahun),
            ],
            default => $this->fail('NOT_FOUND', 'Jenis statistik PTSP tidak ditemukan.'),
        };
    }

    private function exportData(
        string $permission,
        int $userId,
        array $input,
        callable $counter,
        callable $reader,
        array $optionMap,
        bool $includeDates = false
    ): array {
        if (! $this->isAll($permission, $userId)) {
            return $this->forbidden('export data PTSP');
        }

        $context = $this->periodContext($input);
        if (! $context['success']) {
            return $context;
        }

        $filter = [
            'id_tahun' => (int) $context['tahun_dipilih']['id'],
            'search' => $this->nullableText($input['search'] ?? null, 120),
        ];
        foreach ($optionMap as $key => $allowed) {
            $filter[$key] = $this->option($input[$key] ?? null, $allowed);
        }
        if ($includeDates) {
            $filter['tanggal_mulai'] = $this->date($input['tanggal_mulai'] ?? null, false);
            $filter['tanggal_selesai'] = $this->date($input['tanggal_selesai'] ?? null, false);
        }

        $total = (int) $counter($filter);
        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail('EXPORT_TOO_LARGE', 'Data melebihi 50.000 baris. Persempit filter.');
        }

        return [
            'success' => true,
            'rows' => $reader($filter),
            'tahun_dipilih' => $context['tahun_dipilih'],
            'filter' => $filter,
        ];
    }

    private function periodContext(array $input): array
    {
        $options = $this->tahunOptions();
        if ($options === []) {
            return $this->fail('NO_PERIOD', 'Tahun Ajaran tidak tersedia.');
        }

        $requested = (int) ($input['id_tahun'] ?? 0);
        $selected = null;

        if ($requested > 0) {
            foreach ($options as $row) {
                if ((int) $row['id'] === $requested) {
                    $selected = $row;
                    break;
                }
            }
            if ($selected === null) {
                return $this->fail('VALIDATION', 'Tahun Ajaran tidak valid.');
            }
        } else {
            foreach ($options as $row) {
                if ((int) ($row['status_aktif'] ?? 0) === 1) {
                    $selected = $row;
                    break;
                }
            }
            $selected ??= $options[0];
        }

        return [
            'success' => true,
            'tahun_options' => $options,
            'tahun_dipilih' => $selected,
        ];
    }

    private function tahunOptions(): array
    {
        return $this->db->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null)
            ->orderBy('status_aktif', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
    }

    private function activeYear(): ?array
    {
        return $this->db->table('tahun_ajaran')
            ->select('id, nama_tahun, semester')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray() ?: null;
    }

    private function pollingStatistics(int $idTahun, array $period): array
    {
        $aggregate = $this->db->table('ptsp_polling')
            ->select('COUNT(*) AS total, AVG(score) AS average_score', false)
            ->where('id_tahun', $idTahun)
            ->get()
            ->getRowArray() ?? [];

        return [
            'success' => true,
            'period' => $period,
            'total' => (int) ($aggregate['total'] ?? 0),
            'average_score' => $aggregate['average_score'] !== null
                ? round((float) $aggregate['average_score'], 2)
                : null,
            'per_kategori' => $this->groupCounts('ptsp_polling', 'kategori_responden', $idTahun, true),
            'per_label' => $this->groupCounts('ptsp_polling', 'tingkat_kepuasan', $idTahun),
            'per_score' => $this->groupCounts('ptsp_polling', 'score', $idTahun),
        ];
    }

    private function countTable(string $table, int $idTahun): int
    {
        return $this->db->table($table)
            ->where('id_tahun', $idTahun)
            ->countAllResults();
    }

    private function groupCounts(string $table, string $column, int $idTahun, bool $nullable = false): array
    {
        $rows = $this->db->table($table)
            ->select($column . ' AS label, COUNT(*) AS total', false)
            ->where('id_tahun', $idTahun)
            ->groupBy($column)
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(
            static fn (array $row): array => [
                'label' => ($nullable && ($row['label'] === null || $row['label'] === ''))
                    ? 'Tidak Diisi'
                    : (string) ($row['label'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
            ],
            $rows
        );
    }

    private function complaintClassificationCounts(int $idTahun): array
    {
        $rows = $this->db->table('ptsp_pengaduan_klasifikasi pk')
            ->select('pk.klasifikasi AS label, COUNT(*) AS total', false)
            ->join('ptsp_pengaduan p', 'p.id = pk.id_pengaduan')
            ->where('p.id_tahun', $idTahun)
            ->groupBy('pk.klasifikasi')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(
            static fn (array $row): array => [
                'label' => (string) ($row['label'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
            ],
            $rows
        );
    }

    private function canTransitionLayanan(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            'Baru' => $to === 'Diproses',
            'Diproses' => $to === 'Selesai',
            default => false,
        };
    }

    private function canTransitionPengaduan(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            'Masuk' => $to === 'Diverifikasi',
            'Diverifikasi' => in_array($to, ['Diproses', 'Selesai'], true),
            'Diproses' => $to === 'Selesai',
            default => false,
        };
    }

    private function isAll(string $permission, int $userId): bool
    {
        return $userId > 0
            && $this->authService->resolveScope($permission, $userId) === 'SEMUA';
    }

    private function option(mixed $value, array $allowed): ?string
    {
        $value = trim((string) $value);
        return $value !== '' && in_array($value, $allowed, true) ? $value : null;
    }

    private function text(mixed $value, int $max): string
    {
        return mb_substr(trim((string) $value), 0, $max);
    }

    private function nullableText(mixed $value, int $max): ?string
    {
        $value = $this->text($value, $max);
        return $value !== '' ? $value : null;
    }

    private function whatsapp(mixed $value, bool $required): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return $required ? null : null;
        }

        if (! preg_match('/^[0-9+().\-\s]{8,25}$/', $raw)) {
            return null;
        }

        return $raw;
    }

    private function date(mixed $value, bool $required): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $required ? null : null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function deleteStoredFile(mixed $relativePath): void
    {
        $relative = str_replace('\\', '/', trim((string) $relativePath));
        if (! preg_match('#^uploads/ptsp/pengaduan/[A-Za-z0-9_.-]+$#', $relative)) {
            return;
        }

        $path = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function forbidden(string $action): array
    {
        return $this->fail('FORBIDDEN', 'Anda tidak memiliki hak untuk ' . $action . '.');
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
