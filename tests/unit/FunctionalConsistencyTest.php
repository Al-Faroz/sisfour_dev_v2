<?php

namespace Tests\Unit;

use App\Services\JadwalGuruService;
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
    public function testDashboardBkTakesPriorityOverManagedGuruRole(): void
    {
        $service = (new ReflectionClass(RoleAwareDashboardService::class))
            ->newInstanceWithoutConstructor();

        $this->assertSame(
            'bk',
            $service->resolveDashboardRole(['guru', 'bk'])
        );

        $this->assertSame(
            'pimpinan',
            $service->resolveDashboardRole(['guru', 'bk', 'pimpinan'])
        );

        $this->assertSame(
            'operator',
            $service->resolveDashboardRole(['guru', 'bk', 'operator'])
        );

        $this->assertSame(
            'admin',
            $service->resolveDashboardRole(['guru', 'bk', 'admin'])
        );
    }

    public function testDashboardGuruRemainsGuruWithoutHigherExperienceRole(): void
    {
        $service = (new ReflectionClass(RoleAwareDashboardService::class))
            ->newInstanceWithoutConstructor();

        $this->assertSame(
            'guru',
            $service->resolveDashboardRole(['guru'])
        );

        $this->assertSame(
            'siswa',
            $service->resolveDashboardRole(['siswa'])
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
