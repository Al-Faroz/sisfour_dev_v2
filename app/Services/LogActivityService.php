<?php

namespace App\Services;

use App\Models\LogActivityModel;
use DateTimeImmutable;

class LogActivityService
{
    private const MAX_PAGE_SIZE = 100;
    private const EXPORT_LIMIT = 10000;

    protected LogActivityModel $model;

    public function __construct()
    {
        $this->model = new LogActivityModel();
    }

    public function page(array $input): array
    {
        $validated = $this->validateFilter($input);

        if (!$validated['success']) {
            return $validated;
        }

        $limit = max(
            1,
            min(
                self::MAX_PAGE_SIZE,
                (int) ($input['limit'] ?? 50)
            )
        );

        $offset = max(
            0,
            (int) ($input['offset'] ?? 0)
        );

        $filter = $validated['filter'];

        return [
            'success' => true,
            'rows' => $this->model->getPaged(
                $filter,
                $limit,
                $offset
            ),
            'total' => $this->model
                ->countFiltered($filter),
            'limit' => $limit,
            'offset' => $offset,
            'modules' => $this->model
                ->getModules(),
            'actions' => $this->model
                ->getActions(),
        ];
    }

    public function export(array $input): array
    {
        $validated = $this->validateFilter($input);

        if (!$validated['success']) {
            return $validated;
        }

        $rows = $this->model->getForExport(
            $validated['filter'],
            self::EXPORT_LIMIT
        );

        $stream = fopen(
            'php://temp',
            'w+b'
        );

        if ($stream === false) {
            return $this->fail(
                'EXPORT_FAILED',
                'Export Log Activity gagal dibuat.'
            );
        }

        // UTF-8 BOM agar Excel Windows membaca karakter Indonesia dengan baik.
        fwrite(
            $stream,
            "\xEF\xBB\xBF"
        );

        fputcsv(
            $stream,
            [
                'ID',
                'Waktu',
                'User',
                'User ID',
                'Aksi',
                'Modul',
                'Keterangan',
            ]
        );

        foreach ($rows as $row) {
            fputcsv(
                $stream,
                [
                    (int) $row['id'],
                    (string) $row['waktu'],
                    (string) (
                        $row['username']
                        ?? 'User terhapus/tidak tersedia'
                    ),
                    $row['id_user'] !== null
                        ? (int) $row['id_user']
                        : '',
                    (string) $row['aksi'],
                    (string) $row['modul'],
                    (string) (
                        $row['keterangan']
                        ?? ''
                    ),
                ]
            );
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            return $this->fail(
                'EXPORT_FAILED',
                'Export Log Activity gagal dibaca.'
            );
        }

        return [
            'success' => true,
            'content' => $csv,
            'filename' => 'log_activity_'
                . date('Ymd_His')
                . '.csv',
            'count' => count($rows),
            'truncated' =>
                count($rows) >= self::EXPORT_LIMIT,
        ];
    }

    private function validateFilter(
        array $input
    ): array {
        $tanggalMulai = trim(
            (string) ($input['tanggal_mulai'] ?? '')
        );
        $tanggalSelesai = trim(
            (string) ($input['tanggal_selesai'] ?? '')
        );

        if (
            $tanggalMulai !== ''
            && !$this->validDate($tanggalMulai)
        ) {
            return $this->fail(
                'VALIDATION',
                'Tanggal mulai tidak valid.'
            );
        }

        if (
            $tanggalSelesai !== ''
            && !$this->validDate($tanggalSelesai)
        ) {
            return $this->fail(
                'VALIDATION',
                'Tanggal selesai tidak valid.'
            );
        }

        if (
            $tanggalMulai !== ''
            && $tanggalSelesai !== ''
            && $tanggalMulai > $tanggalSelesai
        ) {
            return $this->fail(
                'VALIDATION',
                'Tanggal mulai tidak boleh setelah tanggal selesai.'
            );
        }

        $search = trim(
            (string) ($input['search'] ?? '')
        );
        $modul = trim(
            (string) ($input['modul'] ?? '')
        );
        $aksi = trim(
            (string) ($input['aksi'] ?? '')
        );

        if (mb_strlen($search) > 100) {
            return $this->fail(
                'VALIDATION',
                'Pencarian maksimal 100 karakter.'
            );
        }

        if (
            mb_strlen($modul) > 100
            || mb_strlen($aksi) > 100
        ) {
            return $this->fail(
                'VALIDATION',
                'Filter modul/aksi tidak valid.'
            );
        }

        return [
            'success' => true,
            'filter' => [
                'search' => $search !== ''
                    ? $search
                    : null,
                'modul' => $modul !== ''
                    ? $modul
                    : null,
                'aksi' => $aksi !== ''
                    ? $aksi
                    : null,
                'tanggal_mulai' =>
                    $tanggalMulai !== ''
                        ? $tanggalMulai
                        : null,
                'tanggal_selesai' =>
                    $tanggalSelesai !== ''
                        ? $tanggalSelesai
                        : null,
            ],
        ];
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        return $date !== false
            && $date->format('Y-m-d') === $value;
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
