<?php

namespace App\Services;

/**
 * G3.9 action catalog untuk role operasional yang dapat muncul sebagai
 * secondary Dashboard surface.
 *
 * Pure service: permission resolver diberikan caller. Tidak melakukan query DB
 * dan tidak menjadi authorization boundary.
 */
final class DashboardActionCatalogService
{
    /**
     * @return array{
     *   role:string,
     *   title:string,
     *   subtitle:string,
     *   actions:list<array>
     * }|null
     */
    public static function secondarySection(
        string $role,
        callable $can
    ): ?array {
        $role = strtolower(trim($role));

        if (! in_array($role, ['kesehatan', 'ptsp'], true)) {
            return null;
        }

        $actions = self::allActions($role, $can);

        if ($actions === []) {
            return null;
        }

        return [
            'role' => $role,
            'title' => $role === 'kesehatan'
                ? 'Layanan UKS'
                : 'Layanan PTSP',
            'subtitle' => $role === 'kesehatan'
                ? 'Akses tambahan dari role Kesehatan'
                : 'Akses tambahan dari role PTSP',
            'actions' => $actions,
        ];
    }

    /**
     * @return list<array>
     */
    public static function secondarySections(
        array $secondaryRoles,
        callable $can
    ): array {
        $sections = [];

        foreach ($secondaryRoles as $role) {
            $section = self::secondarySection((string) $role, $can);

            if ($section !== null) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    public static function primaryAction(
        string $role,
        callable $can
    ): ?array {
        foreach (self::allActions($role, $can) as $action) {
            if (($action['surface'] ?? '') === 'primary') {
                return $action;
            }
        }

        return null;
    }

    /**
     * @return list<array>
     */
    public static function accessActions(
        string $role,
        callable $can
    ): array {
        return array_values(array_filter(
            self::allActions($role, $can),
            static fn (array $action): bool =>
                ($action['surface'] ?? '') !== 'primary'
        ));
    }

    /**
     * @return list<array>
     */
    public static function allActions(
        string $role,
        callable $can
    ): array {
        $actions = [];

        foreach (self::candidates($role) as $candidate) {
            $permission = (string) ($candidate['permission'] ?? '');

            if ($permission === '' || ! (bool) $can($permission)) {
                continue;
            }

            unset($candidate['permission']);
            $actions[] = $candidate;
        }

        return $actions;
    }

    /**
     * @return list<array>
     */
    private static function candidates(string $role): array
    {
        return match (strtolower(trim($role))) {
            'kesehatan' => [
                [
                    'permission' => 'uks_harian.manage',
                    'surface' => 'primary',
                    'tone' => 'teal',
                    'label' => 'Tambah Data Kunjungan',
                    'description' => 'Catat kunjungan siswa ke UKS',
                    'icon' => 'bx-plus-medical',
                    'url' => 'uks/harian#tambah',
                ],
                [
                    'permission' => 'uks_harian.view',
                    'surface' => 'access',
                    'tone' => 'teal',
                    'label' => 'Data UKS',
                    'description' => 'Buka Catatan Harian UKS',
                    'icon' => 'bx-plus-medical',
                    'url' => 'uks/harian',
                ],
                [
                    'permission' => 'uks_ckg.view',
                    'surface' => 'access',
                    'tone' => 'cyan',
                    'label' => 'Data CKG',
                    'description' => 'Buka data pemeriksaan CKG',
                    'icon' => 'bx-pulse',
                    'url' => 'uks/ckg',
                ],
                [
                    'permission' => 'uks_ckg.import',
                    'surface' => 'access',
                    'tone' => 'indigo',
                    'label' => 'Import CKG',
                    'description' => 'Import pemeriksaan CKG via XLSX',
                    'icon' => 'bx-import',
                    'url' => 'uks/ckg#import',
                ],
                [
                    'permission' => 'uks_master.manage',
                    'surface' => 'access',
                    'tone' => 'slate',
                    'label' => 'Master UKS',
                    'description' => 'Kelola keluhan, tindakan, dan hasil',
                    'icon' => 'bx-list-ul',
                    'url' => 'uks/master',
                ],
            ],
            'ptsp' => [
                [
                    'permission' => 'ptsp_layanan.view',
                    'surface' => 'primary',
                    'tone' => 'blue',
                    'label' => 'Layanan PTSP',
                    'description' => 'Kelola pengajuan layanan',
                    'icon' => 'bx-file',
                    'url' => 'ptsp/layanan',
                ],
                [
                    'permission' => 'ptsp_polling.view',
                    'surface' => 'access',
                    'tone' => 'green',
                    'label' => 'Polling Kepuasan',
                    'description' => 'Lihat hasil kepuasan',
                    'icon' => 'bx-happy',
                    'url' => 'ptsp/polling',
                ],
                [
                    'permission' => 'ptsp_pengaduan.view',
                    'surface' => 'access',
                    'tone' => 'rose',
                    'label' => 'Pengaduan',
                    'description' => 'Kelola laporan masuk',
                    'icon' => 'bx-message-square-error',
                    'url' => 'ptsp/pengaduan',
                ],
                [
                    'permission' => 'ptsp_layanan.view',
                    'surface' => 'access',
                    'tone' => 'cyan',
                    'label' => 'Buka Public PTSP',
                    'description' => 'Buka landing form publik',
                    'icon' => 'bx-globe',
                    'url' => 'ptsp',
                    'external' => true,
                ],
            ],
            default => [],
        };
    }
}
