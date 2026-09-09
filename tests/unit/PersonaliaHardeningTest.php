<?php

namespace Tests\Unit;

use App\Services\PersonaliaService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Phase 3.1 - pure policy tests untuk hardening Personalia.
 *
 * Test tidak membutuhkan koneksi database karena hanya menguji policy
 * whitelist jenis dokumen dan boundary path storage non-public.
 *
 * @internal
 */
final class PersonaliaHardeningTest extends CIUnitTestCase
{
    public function testKnownDocumentTypesAreAccepted(): void
    {
        foreach (PersonaliaService::DOCUMENT_TYPES as $type) {
            $this->assertTrue(
                PersonaliaService::isAllowedDocumentType($type),
                'Jenis dokumen resmi harus diterima: ' . $type
            );
        }
    }

    public function testUnknownDocumentTypeIsRejected(): void
    {
        $this->assertFalse(PersonaliaService::isAllowedDocumentType('Dokumen Bebas'));
        $this->assertFalse(PersonaliaService::isAllowedDocumentType('KTP'));
        $this->assertFalse(PersonaliaService::isAllowedDocumentType(''));
    }

    public function testValidStoredPathIsNormalized(): void
    {
        $path = PersonaliaService::normalizeStoredPath(
            'uploads/personalia/guru/12/ijazah_20260909_abcd1234.pdf',
            'guru',
            12
        );

        $this->assertSame(
            'uploads/personalia/guru/12/ijazah_20260909_abcd1234.pdf',
            $path
        );
    }

    public function testWrongOwnerPathIsRejected(): void
    {
        $this->assertNull(
            PersonaliaService::normalizeStoredPath(
                'uploads/personalia/guru/13/ijazah_20260909_abcd1234.pdf',
                'guru',
                12
            )
        );

        $this->assertNull(
            PersonaliaService::normalizeStoredPath(
                'uploads/personalia/pegawai/12/sk_awal_20260909_abcd1234.pdf',
                'guru',
                12
            )
        );
    }

    public function testTraversalAndAbsolutePathsAreRejected(): void
    {
        $invalid = [
            '../uploads/personalia/guru/12/file.pdf',
            'uploads/personalia/guru/12/../13/file.pdf',
            '/uploads/personalia/guru/12/file.pdf',
            'C:/xampp/htdocs/file.pdf',
            'uploads//personalia/guru/12/file.pdf',
            'writable/logs/log.php',
            'uploads/personalia/guru/12/file.php',
        ];

        foreach ($invalid as $path) {
            $this->assertNull(
                PersonaliaService::normalizeStoredPath($path),
                'Path harus ditolak: ' . $path
            );
        }
    }

    public function testPegawaiImagePathIsAccepted(): void
    {
        $this->assertSame(
            'uploads/personalia/pegawai/7/dokumen_20260909_abcd1234.jpg',
            PersonaliaService::normalizeStoredPath(
                'uploads/personalia/pegawai/7/dokumen_20260909_abcd1234.jpg',
                'pegawai',
                7
            )
        );
    }
}
