<?php

namespace Tests\Unit;

use App\Services\DashboardActionCatalogService;
use App\Services\DashboardCompositionService;
use App\Services\JadwalGuruService;
use App\Services\RoleAssignmentPolicyService;
use App\Services\RoleAwareDashboardService;
use App\Services\SidebarMenuCompositionService;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * STEP 05 — pure consistency tests.
 *
 * Tidak membutuhkan koneksi database karena object dibuat tanpa constructor
 * dan hanya method pure yang diuji.
 *
 * @internal
 */
final class FunctionalConsistencyTest extends CIUnitTestCase
{
    public function testDashboardPrimaryRoleOwnsHome(): void
    {
        $service = (new ReflectionClass(RoleAwareDashboardService::class))
            ->newInstanceWithoutConstructor();

        $this->assertSame(
            'guru',
            $service->resolveDashboardRole(
                ['guru', 'operator'],
                'guru'
            )
        );

        $this->assertSame(
            'bk',
            $service->resolveDashboardRole(
                ['bk', 'operator', 'kesehatan'],
                'bk'
            )
        );
    }

    public function testDashboardCompositionSkipsNonActionSecondaryRole(): void
    {
        $composition = DashboardCompositionService::compose(
            'guru',
            ['guru', 'operator'],
            false
        );

        $this->assertSame('guru', $composition['primary_role']);
        $this->assertSame(['operator'], $composition['secondary_roles']);
        $this->assertSame(['guru'], $composition['action_roles']);
    }

    public function testDashboardCompositionBkTripleIsDeterministic(): void
    {
        $composition = DashboardCompositionService::compose(
            'bk',
            ['ptsp', 'bk', 'kesehatan', 'operator'],
            false
        );

        $this->assertSame('bk', $composition['primary_role']);
        $this->assertSame(
            ['operator', 'kesehatan'],
            $composition['secondary_roles']
        );
        $this->assertSame(
            ['bk', 'kesehatan'],
            $composition['action_roles']
        );
        $this->assertContains('ptsp', $composition['ignored_roles']);
    }

    public function testDashboardCompositionBkKeepsKesehatanAndPtsp(): void
    {
        $composition = DashboardCompositionService::compose(
            'bk',
            ['bk', 'kesehatan', 'ptsp'],
            false
        );

        $this->assertSame(
            ['kesehatan', 'ptsp'],
            $composition['secondary_roles']
        );
        $this->assertSame(
            ['bk', 'kesehatan', 'ptsp'],
            $composition['action_roles']
        );
        $this->assertSame([], $composition['ignored_roles']);
    }

    public function testDashboardCompositionWaliUsesRestrictedSecondarySet(): void
    {
        $composition = DashboardCompositionService::compose(
            'guru',
            ['guru', 'pimpinan', 'kesehatan'],
            true
        );

        $this->assertSame(['kesehatan'], $composition['secondary_roles']);
        $this->assertSame(
            ['guru', 'kesehatan'],
            $composition['action_roles']
        );
        $this->assertContains('pimpinan', $composition['ignored_roles']);
    }

    public function testDashboardPrimaryRoleHasLegacyFallbackOnlyWhenMissing(): void
    {
        $this->assertSame(
            'operator',
            DashboardCompositionService::resolvePrimaryRole(
                null,
                ['guru', 'operator']
            )
        );

        $this->assertSame(
            'siswa',
            DashboardCompositionService::resolvePrimaryRole(
                'invalid',
                ['siswa']
            )
        );
    }

    public function testSecondaryActionCatalogSkipsNonActionRoles(): void
    {
        $sections = DashboardActionCatalogService::secondarySections(
            ['operator', 'kesehatan', 'pimpinan', 'ptsp'],
            static fn (string $permission): bool => true
        );

        $this->assertSame(
            ['kesehatan', 'ptsp'],
            array_column($sections, 'role')
        );
        $this->assertSame(
            'Tambah Data Kunjungan',
            $sections[0]['actions'][0]['label']
        );
        $this->assertSame(
            'Layanan PTSP',
            $sections[1]['actions'][0]['label']
        );
    }

    public function testSecondaryActionCatalogFiltersByPermission(): void
    {
        $allowed = [
            'uks_harian.view',
            'ptsp_pengaduan.view',
        ];

        $sections = DashboardActionCatalogService::secondarySections(
            ['kesehatan', 'ptsp'],
            static fn (string $permission): bool =>
                in_array($permission, $allowed, true)
        );

        $this->assertCount(2, $sections);
        $this->assertSame(
            ['Data UKS'],
            array_column($sections[0]['actions'], 'label')
        );
        $this->assertSame(
            ['Pengaduan'],
            array_column($sections[1]['actions'], 'label')
        );
    }

    public function testRoleAssignmentAllowsGuruWithOperationalSecondary(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            'guru',
            ['ptsp'],
            10,
            null,
            null,
            false
        );

        $this->assertTrue($result['success']);
        $this->assertSame(['ptsp'], $result['secondary_roles']);
    }

    public function testRoleAssignmentAllowsBkOnGuruIdentityWithoutGuruRole(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            'bk',
            ['kesehatan', 'operator'],
            10,
            null,
            null,
            false
        );

        $this->assertTrue($result['success']);
        $this->assertSame(
            ['operator', 'kesehatan'],
            $result['secondary_roles']
        );
    }

    public function testRoleAssignmentRejectsRolelessWaliGuru(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            null,
            [],
            10,
            null,
            null,
            true
        );

        $this->assertFalse($result['success']);
        $this->assertSame('WALI_PRIMARY_ROLE', $result['code']);
    }

    public function testDashboardCompositionWaliAllowsPtspSecondary(): void
    {
        $composition = DashboardCompositionService::compose(
            'guru',
            ['guru', 'ptsp'],
            true
        );

        $this->assertSame(['ptsp'], $composition['secondary_roles']);
        $this->assertSame(
            ['guru', 'ptsp'],
            $composition['action_roles']
        );
    }

    public function testRoleAssignmentAllowsGuruWaliWithPtsp(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            'guru',
            ['ptsp'],
            10,
            null,
            null,
            true
        );

        $this->assertTrue($result['success']);
        $this->assertSame(['ptsp'], $result['secondary_roles']);
    }

    public function testRoleAssignmentRejectsInvalidGuruWaliSecondary(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            'guru',
            ['pimpinan'],
            10,
            null,
            null,
            true
        );

        $this->assertFalse($result['success']);
        $this->assertSame('ROLE_COMBINATION', $result['code']);
    }

    public function testRoleAssignmentRejectsOperationalStaffRoleWithoutStaffIdentity(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            'kesehatan',
            [],
            null,
            null,
            null,
            false
        );

        $this->assertFalse($result['success']);
        $this->assertSame('STAFF_IDENTITY_REQUIRED', $result['code']);
    }

    public function testRoleAssignmentRejectsStudentWithPersonnelRole(): void
    {
        $result = RoleAssignmentPolicyService::validate(
            'operator',
            [],
            null,
            null,
            99,
            false
        );

        $this->assertFalse($result['success']);
        $this->assertSame('STUDENT_IDENTITY_ROLE', $result['code']);
    }

    public function testSidebarCompositionKeepsRoleSectionsAndDedupesLeaves(): void
    {
        $composition = DashboardCompositionService::compose(
            'guru',
            ['guru', 'operator'],
            false
        );

        $roleTrees = [
            'guru' => [
                [
                    'id' => 3,
                    'nama_menu' => 'Master Data',
                    'link' => '#',
                    'children' => [
                        [
                            'id' => 33,
                            'nama_menu' => 'Data Siswa',
                            'link' => 'master/siswa',
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'operator' => [
                [
                    'id' => 3,
                    'nama_menu' => 'Master Data',
                    'link' => '#',
                    'children' => [
                        [
                            'id' => 33,
                            'nama_menu' => 'Data Siswa',
                            'link' => 'master/siswa',
                            'children' => [],
                        ],
                        [
                            'id' => 31,
                            'nama_menu' => 'Data Guru',
                            'link' => 'master/guru',
                            'children' => [],
                        ],
                    ],
                ],
            ],
        ];

        $layout = SidebarMenuCompositionService::compose(
            $composition,
            $roleTrees,
            [['id' => 1, 'link' => 'dashboard', 'children' => []]],
            [['id' => 9, 'link' => 'profile/guru', 'children' => []]],
            false
        );

        $this->assertTrue($layout['is_multi_role']);
        $this->assertSame(
            ['primary', 'secondary'],
            array_column($layout['role_sections'], 'kind')
        );
        $this->assertSame(
            ['Guru', 'Operator'],
            array_column($layout['role_sections'], 'label')
        );
        $this->assertSame(
            'Data Guru',
            $layout['role_sections'][1]['items'][0]['children'][0]['nama_menu']
        );
        $this->assertCount(
            1,
            $layout['role_sections'][1]['items'][0]['children']
        );
        $this->assertSame(
            'dashboard',
            $layout['global_items'][0]['link']
        );
        $this->assertSame(
            'profile/guru',
            $layout['account_items'][0]['link']
        );
    }

    public function testSidebarCompositionTreatsWaliAsGuruContextLabel(): void
    {
        $composition = DashboardCompositionService::compose(
            'guru',
            ['guru', 'ptsp'],
            true
        );

        $layout = SidebarMenuCompositionService::compose(
            $composition,
            [
                'guru' => [[
                    'id' => 2,
                    'nama_menu' => 'Presensi',
                    'link' => '#',
                    'children' => [[
                        'id' => 21,
                        'nama_menu' => 'Presensi Siswa',
                        'link' => 'presensi/siswa',
                        'children' => [],
                    ]],
                ]],
                'ptsp' => [[
                    'id' => 121,
                    'nama_menu' => 'PTSP',
                    'link' => '#',
                    'children' => [[
                        'id' => 122,
                        'nama_menu' => 'Layanan PTSP',
                        'link' => 'ptsp/layanan',
                        'children' => [],
                    ]],
                ]],
            ],
            [],
            [],
            true
        );

        $this->assertSame(
            'Guru / Wali Kelas',
            $layout['role_sections'][0]['label']
        );
        $this->assertSame(
            'PTSP',
            $layout['role_sections'][1]['label']
        );
        $this->assertNotContains(
            'Wali',
            array_column($layout['role_sections'], 'role')
        );
    }

    public function testSidebarCompositionBkTripleKeepsRoleOrder(): void
    {
        $composition = DashboardCompositionService::compose(
            'bk',
            ['ptsp', 'bk', 'kesehatan'],
            false
        );

        $layout = SidebarMenuCompositionService::compose(
            $composition,
            [
                'bk' => [[
                    'id' => 5,
                    'nama_menu' => 'BK & Prestasi',
                    'link' => '#',
                    'children' => [[
                        'id' => 51,
                        'nama_menu' => 'Catatan Pelanggaran',
                        'link' => 'bk/kasus',
                        'children' => [],
                    ]],
                ]],
                'kesehatan' => [[
                    'id' => 117,
                    'nama_menu' => 'UKS',
                    'link' => '#',
                    'children' => [[
                        'id' => 119,
                        'nama_menu' => 'Data UKS',
                        'link' => 'uks/harian',
                        'children' => [],
                    ]],
                ]],
                'ptsp' => [[
                    'id' => 121,
                    'nama_menu' => 'PTSP',
                    'link' => '#',
                    'children' => [[
                        'id' => 122,
                        'nama_menu' => 'Layanan PTSP',
                        'link' => 'ptsp/layanan',
                        'children' => [],
                    ]],
                ]],
            ],
            [],
            [],
            false
        );

        $this->assertSame(
            ['bk', 'kesehatan', 'ptsp'],
            array_column($layout['role_sections'], 'role')
        );
        $this->assertSame(
            ['primary', 'secondary', 'secondary'],
            array_column($layout['role_sections'], 'kind')
        );
    }

    public function testSidebarActiveStateUsesOneLongestMatch(): void
    {
        $service = (new ReflectionClass(\App\Services\MenuService::class))
            ->newInstanceWithoutConstructor();

        $layout = [
            'is_multi_role' => true,
            'global_items' => [[
                'id' => 1,
                'nama_menu' => 'Dashboard',
                'link' => 'dashboard',
                'children' => [],
            ]],
            'role_sections' => [
                [
                    'kind' => 'primary',
                    'role' => 'guru',
                    'label' => 'Guru',
                    'items' => [[
                        'id' => 23,
                        'nama_menu' => 'Rekap Presensi',
                        'link' => 'presensi/siswa',
                        'children' => [],
                    ]],
                ],
                [
                    'kind' => 'secondary',
                    'role' => 'operator',
                    'label' => 'Operator',
                    'items' => [[
                        'id' => 41,
                        'nama_menu' => 'Matrix Presensi',
                        'link' => 'presensi/siswa/rekap',
                        'children' => [],
                    ]],
                ],
            ],
            'account_items' => [],
        ];

        $resolved = $service->markMultiRoleLayoutActive(
            $layout,
            'presensi/siswa/rekap/detail'
        );

        $this->assertFalse(
            $resolved['role_sections'][0]['items'][0]['active']
        );
        $this->assertTrue(
            $resolved['role_sections'][1]['items'][0]['active']
        );
    }

    public function testScheduleOverlapUsesHalfOpenIntervals(): void
    {
        $service = (new ReflectionClass(JadwalGuruService::class))
            ->newInstanceWithoutConstructor();

        $this->assertTrue(
            $service->isOverlap(
                '07:30:00',
                '09:00:00',
                '08:30:00',
                '10:00:00'
            )
        );

        $this->assertFalse(
            $service->isOverlap(
                '07:30:00',
                '09:00:00',
                '09:00:00',
                '10:00:00'
            )
        );
    }

    public function testScheduleConflictDetectsGuruAndClassOverlap(): void
    {
        $service = (new ReflectionClass(JadwalGuruService::class))
            ->newInstanceWithoutConstructor();

        $rows = [
            [
                'id_guru' => 10,
                'id_kelas' => 20,
                'hari' => 'Senin',
                'jam_mulai' => '07:30:00',
                'jam_selesai' => '09:00:00',
            ],
            [
                'id_guru' => 10,
                'id_kelas' => 21,
                'hari' => 'Senin',
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:30:00',
            ],
            [
                'id_guru' => 11,
                'id_kelas' => 20,
                'hari' => 'Senin',
                'jam_mulai' => '08:15:00',
                'jam_selesai' => '09:15:00',
            ],
        ];

        $result = $service->validateBentrok($rows);

        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']);
        $this->assertStringContainsString(
            'Bentrok Guru',
            implode(' ', $result['errors'])
        );
        $this->assertStringContainsString(
            'Bentrok Kelas',
            implode(' ', $result['errors'])
        );
    }
}
