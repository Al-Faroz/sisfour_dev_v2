<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

class ActivityLogService
{
    private const TZ = 'Asia/Jakarta';

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function write(
        ?int $userId,
        string $aksi,
        string $modul,
        ?string $keterangan = null
    ): void {
        $aksi = $this->cleanText($aksi, 100);
        $modul = $this->cleanText($modul, 100);
        $keterangan = $this->cleanNullableText(
            $keterangan,
            2000
        );

        if ($aksi === '' || $modul === '') {
            return;
        }

        try {
            $this->db
                ->table('log_activity')
                ->insert([
                    'id_user' => $userId && $userId > 0
                        ? $userId
                        : null,
                    'aksi' => $aksi,
                    'modul' => $modul,
                    'keterangan' => $keterangan,
                    'waktu' => Time::now(self::TZ)
                        ->format('Y-m-d H:i:s'),
                ]);
        } catch (Throwable $e) {
            log_message(
                'warning',
                'Activity log gagal ditulis: {message}',
                ['message' => $e->getMessage()]
            );
        }
    }

    private function cleanText(
        string $value,
        int $maxLength
    ): string {
        $value = trim(
            preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
                '',
                $value
            ) ?? ''
        );

        return mb_substr(
            $value,
            0,
            $maxLength
        );
    }

    private function cleanNullableText(
        ?string $value,
        int $maxLength
    ): ?string {
        if ($value === null) {
            return null;
        }

        $clean = $this->cleanText(
            $value,
            $maxLength
        );

        return $clean !== ''
            ? $clean
            : null;
    }
}
