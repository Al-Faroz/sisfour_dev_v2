<?php

namespace App\Services;

/**
 * G3.9 pure Sidebar composition contract.
 *
 * Menerima tree menu yang sudah difilter per role lalu:
 * - menjaga Primary/Secondary provenance;
 * - melakukan incremental leaf dedupe;
 * - mempertahankan parent/container per section;
 * - memberi label role/context untuk renderer.
 *
 * Service ini tidak melakukan authorization dan tidak query database.
 */
final class SidebarMenuCompositionService
{
    private const ROLE_LABELS = [
        'admin' => 'Admin',
        'operator' => 'Operator',
        'pimpinan' => 'Pimpinan',
        'bk' => 'BK',
        'kesehatan' => 'Kesehatan',
        'ptsp' => 'PTSP',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
    ];

    /**
     * @return array{
     *   is_multi_role:bool,
     *   global_items:list<array>,
     *   role_sections:list<array{kind:string,role:string,label:string,items:list<array>}>,
     *   account_items:list<array>
     * }
     */
    public static function compose(
        array $dashboardComposition,
        array $roleTrees,
        array $globalItems,
        array $accountItems,
        bool $isWali
    ): array {
        $primaryRole = strtolower(trim((string) (
            $dashboardComposition['primary_role'] ?? ''
        )));
        $secondaryRoles = array_values(array_filter(array_map(
            static fn ($role): string => strtolower(trim((string) $role)),
            $dashboardComposition['secondary_roles'] ?? []
        )));

        $seenLeafKeys = [];
        $sections = [];

        if ($primaryRole !== '') {
            $primaryItems = self::dedupeTree(
                $roleTrees[$primaryRole] ?? [],
                $seenLeafKeys
            );

            if ($primaryItems !== []) {
                $sections[] = [
                    'kind' => 'primary',
                    'role' => $primaryRole,
                    'label' => self::roleLabel($primaryRole, $isWali),
                    'items' => $primaryItems,
                ];
            }
        }

        foreach ($secondaryRoles as $role) {
            if ($role === '' || $role === $primaryRole) {
                continue;
            }

            $items = self::dedupeTree(
                $roleTrees[$role] ?? [],
                $seenLeafKeys
            );

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'kind' => 'secondary',
                'role' => $role,
                'label' => self::roleLabel($role, false),
                'items' => $items,
            ];
        }

        return [
            'is_multi_role' => $secondaryRoles !== [],
            'global_items' => array_values($globalItems),
            'role_sections' => $sections,
            'account_items' => array_values($accountItems),
        ];
    }

    /**
     * Parent/container boleh berulang per role section.
     * Leaf yang sudah dimiliki section lebih awal tidak diulang.
     *
     * @param array<string,bool> $seenLeafKeys
     * @return list<array>
     */
    public static function dedupeTree(
        array $tree,
        array &$seenLeafKeys
    ): array {
        $result = [];

        foreach ($tree as $item) {
            $children = self::dedupeTree(
                $item['children'] ?? [],
                $seenLeafKeys
            );

            $link = trim((string) ($item['link'] ?? ''));
            $isContainer = $link === '' || $link === '#';

            if ($isContainer) {
                if ($children === []) {
                    continue;
                }

                $item['children'] = $children;
                $result[] = $item;

                continue;
            }

            $key = self::leafKey($item);

            if (isset($seenLeafKeys[$key])) {
                continue;
            }

            $seenLeafKeys[$key] = true;
            $item['children'] = $children;
            $result[] = $item;
        }

        return array_values($result);
    }

    public static function roleLabel(
        string $role,
        bool $isWali = false
    ): string {
        $role = strtolower(trim($role));

        if ($role === 'guru' && $isWali) {
            return 'Guru / Wali Kelas';
        }

        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }

    private static function leafKey(array $item): string
    {
        $link = trim((string) ($item['link'] ?? ''));

        if ($link !== '' && $link !== '#') {
            return 'link:' . trim($link, '/');
        }

        return 'id:' . (int) ($item['id'] ?? 0);
    }
}
