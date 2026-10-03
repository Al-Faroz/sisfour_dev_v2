<?php

namespace Tests\Unit;

use App\Services\DashboardCompositionService;
use App\Services\JadwalGuruService;
use App\Services\RoleAssignmentPolicyService;
use App\Services\RoleAwareDashboardService;
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
