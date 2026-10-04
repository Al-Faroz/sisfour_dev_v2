<?php

namespace App\Services;

final class StudentDocumentPolicyService
{
    public const FORMATS = ['PDF', 'IMAGE'];
    public const TARGETS = ['INDIVIDU', 'TINGKAT'];
    public const STATUSES = ['PUBLISHED', 'ARCHIVED'];
    public const LEVELS = ['7', '8', '9'];

    public static function normalizeTitle(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    public static function normalizeFormat(string $value): ?string
    {
        $value = strtoupper(trim($value));
        return in_array($value, self::FORMATS, true) ? $value : null;
    }

    public static function normalizeTarget(string $value): ?string
    {
        $value = strtoupper(trim($value));
        return in_array($value, self::TARGETS, true) ? $value : null;
    }

    public static function normalizeStatus(string $value): ?string
    {
        $value = strtoupper(trim($value));
        return in_array($value, self::STATUSES, true) ? $value : null;
    }

    public static function validLevel(string $value): bool
    {
        return in_array(trim($value), self::LEVELS, true);
    }

    public static function normalizeGoogleDriveUrl(string $value): ?string
    {
        $value = trim($value);
        if (
            $value === ''
            || mb_strlen($value) > 1000
            || filter_var($value, FILTER_VALIDATE_URL) === false
        ) {
            return null;
        }

        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

        if ($scheme !== 'https') {
            return null;
        }

        if (! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
            return null;
        }

        if (! empty($parts['user']) || ! empty($parts['pass'])) {
            return null;
        }

        return $value;
    }
}
