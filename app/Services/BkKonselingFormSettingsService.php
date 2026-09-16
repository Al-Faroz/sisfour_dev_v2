<?php

namespace App\Services;

use App\Models\SettingSistemModel;
use CodeIgniter\I18n\Time;
use Throwable;

class BkKonselingFormSettingsService
{
    private const TZ = 'Asia/Jakarta';
    private const SETTING_KEY = 'bk_konseling_form_options';
    private const MAX_ITEMS_PER_GROUP = 40;
    private const MAX_ITEM_LENGTH = 150;

    private const FIXED_BIDANG = [
        'Pribadi',
        'Sosial',
        'Belajar',
        'Karier',
    ];

    private const FIXED_STATUS = ['Proses', 'Selesai'];

    private const DEFAULT_EDITABLE = [
        'bentuk_layanan' => [
            'Konseling individu',
            'Konseling kelompok',
            'Konsultasi',
        ],
        'cara_hadir' => [
            'Datang sendiri',
            'Dipanggil guru BK',
            'Rujukan wali kelas',
            'Rujukan guru mata pelajaran',
            'Permintaan orang tua',
            'Rujukan UKS',
        ],
        'topik' => [
            'Pribadi' => [
                'Percaya diri',
                'Mengendalikan emosi',
                'Cemas atau takut berlebihan',
                'Sedih berkepanjangan',
                'Masalah kesehatan',
                'Perubahan masa pubertas',
                'Lainnya',
            ],
            'Sosial' => [
                'Konflik dengan teman',
                'Menjadi korban perundungan',
                'Melakukan perundungan',
                'Menarik diri dari pergaulan',
                'Masalah keluarga',
                'Pergaulan dengan lawan jenis',
                'Kecanduan gawai atau game',
                'Lainnya',
            ],
            'Belajar' => [
                'Motivasi belajar rendah',
                'Sulit berkonsentrasi',
                'Nilai menurun',
                'Kesulitan pada mata pelajaran tertentu',
                'Sering terlambat atau tidak masuk',
                'Mengatur waktu belajar',
                'Lainnya',
            ],
            'Karier' => [
                'Memilih sekolah lanjutan',
                'Mengenali bakat dan minat',
                'Tekanan pilihan dari orang tua',
                'Lainnya',
            ],
        ],
        'rencana' => [
            'Selesai',
            'Konseling lanjutan',
            'Memanggil orang tua',
            'Koordinasi dengan wali kelas',
            'Kunjungan rumah',
            'Rujuk ke UKS',
            'Rujuk ke psikolog atau Puskesmas',
        ],
    ];

    protected SettingSistemModel $model;
    protected AuthService $authService;

    public function __construct()
    {
        $this->model = new SettingSistemModel();
        $this->authService = new AuthService();
    }

    public function options(): array
    {
        $editable = $this->loadEditable();

        return [
            'bentuk_layanan' => $editable['bentuk_layanan'],
            'cara_hadir' => $editable['cara_hadir'],
            'bidang' => self::FIXED_BIDANG,
            'topik' => $editable['topik'],
            'rencana' => $editable['rencana'],
            'status' => self::FIXED_STATUS,
        ];
    }

    public function page(int $userId): array
    {
        $guard = $this->authorize($userId);
        if ($guard !== null) {
            return $guard;
        }

        return [
            'success' => true,
            'options' => $this->options(),
            'setting_key' => self::SETTING_KEY,
        ];
    }

    public function update(int $userId, array $input): array
    {
        $guard = $this->authorize($userId);
        if ($guard !== null) {
            return $guard;
        }

        $editable = [
            'bentuk_layanan' => $this->parseLines($input['bentuk_layanan'] ?? ''),
            'cara_hadir' => $this->parseLines($input['cara_hadir'] ?? ''),
            'topik' => [
                'Pribadi' => $this->parseLines($input['topik_pribadi'] ?? ''),
                'Sosial' => $this->parseLines($input['topik_sosial'] ?? ''),
                'Belajar' => $this->parseLines($input['topik_belajar'] ?? ''),
                'Karier' => $this->parseLines($input['topik_karier'] ?? ''),
            ],
            'rencana' => $this->parseLines($input['rencana'] ?? ''),
        ];

        $validation = $this->validateEditable($editable);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $json = json_encode(
                $editable,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            // Kolom type existing memakai tipe string; isi JSON disimpan di setting_value.
            $this->model->set(self::SETTING_KEY, $json, 'string', $userId);
        } catch (Throwable $e) {
            return $this->fail(
                'SAVE_FAILED',
                'Pengaturan Form Konseling gagal disimpan.'
            );
        }

        $this->log($userId);

        return [
            'success' => true,
            'message' => 'Pengaturan Form Konseling berhasil disimpan.',
            'options' => $this->options(),
        ];
    }

    public function reset(int $userId): array
    {
        $guard = $this->authorize($userId);
        if ($guard !== null) {
            return $guard;
        }

        try {
            $json = json_encode(
                self::DEFAULT_EDITABLE,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );

            $this->model->set(self::SETTING_KEY, $json, 'string', $userId);
        } catch (Throwable $e) {
            return $this->fail('SAVE_FAILED', 'Default Form Konseling gagal dipulihkan.');
        }

        $this->log($userId, 'RESET');

        return [
            'success' => true,
            'message' => 'Pengaturan Form Konseling berhasil dikembalikan ke default.',
            'options' => $this->options(),
        ];
    }

    private function loadEditable(): array
    {
        $settings = $this->model->allAssoc();
        $raw = (string) ($settings[self::SETTING_KEY]['setting_value'] ?? '');

        if ($raw === '') {
            return self::DEFAULT_EDITABLE;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return self::DEFAULT_EDITABLE;
        }

        if (! is_array($decoded)) {
            return self::DEFAULT_EDITABLE;
        }

        $candidate = [
            'bentuk_layanan' => $this->normalizeArray($decoded['bentuk_layanan'] ?? []),
            'cara_hadir' => $this->normalizeArray($decoded['cara_hadir'] ?? []),
            'topik' => [],
            'rencana' => $this->normalizeArray($decoded['rencana'] ?? []),
        ];

        foreach (self::FIXED_BIDANG as $bidang) {
            $candidate['topik'][$bidang] = $this->normalizeArray($decoded['topik'][$bidang] ?? []);
        }

        return $this->validateEditable($candidate) === null
            ? $candidate
            : self::DEFAULT_EDITABLE;
    }

    private function parseLines(mixed $value): array
    {
        $lines = preg_split('/\R/u', (string) $value) ?: [];
        return $this->normalizeArray($lines);
    }

    private function normalizeArray(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $result = [];
        $seen = [];

        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $value;
        }

        return $result;
    }

    private function validateEditable(array $editable): ?array
    {
        $groups = [
            'Bentuk Layanan' => $editable['bentuk_layanan'] ?? [],
            'Cara Siswa Hadir' => $editable['cara_hadir'] ?? [],
            'Rencana Berikutnya' => $editable['rencana'] ?? [],
        ];

        foreach (self::FIXED_BIDANG as $bidang) {
            $groups['Topik ' . $bidang] = $editable['topik'][$bidang] ?? [];
        }

        foreach ($groups as $label => $items) {
            if (! is_array($items) || $items === []) {
                return $this->fail('VALIDATION', $label . ' minimal memiliki satu pilihan.');
            }

            if (count($items) > self::MAX_ITEMS_PER_GROUP) {
                return $this->fail(
                    'VALIDATION',
                    $label . ' maksimal ' . self::MAX_ITEMS_PER_GROUP . ' pilihan.'
                );
            }

            foreach ($items as $item) {
                if (mb_strlen((string) $item) > self::MAX_ITEM_LENGTH) {
                    return $this->fail(
                        'VALIDATION',
                        $label . ' memiliki pilihan yang lebih dari '
                            . self::MAX_ITEM_LENGTH . ' karakter.'
                    );
                }
            }
        }

        return null;
    }

    private function authorize(int $userId): ?array
    {
        if (
            $userId > 0
            && $this->authService->resolveScope('bk_konseling.settings', $userId) === 'SEMUA'
        ) {
            return null;
        }

        return $this->fail(
            'FORBIDDEN',
            'Anda tidak memiliki izin mengubah Pengaturan Form Konseling.'
        );
    }

    private function log(int $userId, string $aksi = 'UPDATE'): void
    {
        db_connect()->table('log_activity')->insert([
            'id_user' => $userId,
            'aksi' => $aksi,
            'modul' => 'BK Konseling',
            'keterangan' => $aksi === 'RESET'
                ? 'Memulihkan Pengaturan Form Konseling ke default.'
                : 'Memperbarui Pengaturan Form Konseling.',
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
