<?php

namespace Tests\Unit;

use App\Services\JadwalGuruService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Referensi: 15_TESTING_POLISH §3.1 (5 test case validasi bentrok jadwal).
 * Test ini murni logika (tidak butuh koneksi database) karena
 * JadwalGuruService::validateBentrok() menerima array, bukan query DB.
 *
 * @internal
 */
final class JadwalGuruServiceTest extends CIUnitTestCase
{
    private JadwalGuruService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JadwalGuruService();
    }

    private function baris(int $idGuru, int $idKelas, string $hari, string $mulai, string $selesai): array
    {
        return [
            'id_guru'     => $idGuru,
            'id_kelas'    => $idKelas,
            'hari'        => $hari,
            'jam_mulai'   => $mulai,
            'jam_selesai' => $selesai,
        ];
    }

    public function testGuruSamaHariSamaJamOverlapDitolak(): void
    {
        $rows = [
            $this->baris(1, 10, 'Senin', '07:00', '08:30'),
            $this->baris(1, 20, 'Senin', '08:00', '09:00'), // overlap dgn baris 0
        ];

        $result = $this->service->validateBentrok($rows);

        $this->assertFalse($result['valid']);
    }

    public function testGuruSamaHariSamaJamTidakOverlapDiterima(): void
    {
        $rows = [
            $this->baris(1, 10, 'Senin', '07:00', '08:00'),
            $this->baris(1, 20, 'Senin', '08:00', '09:00'), // bersambung, tidak overlap
        ];

        $result = $this->service->validateBentrok($rows);

        $this->assertTrue($result['valid']);
    }

    public function testKelasSamaHariSamaJamOverlapDitolak(): void
    {
        $rows = [
            $this->baris(1, 10, 'Selasa', '07:00', '08:30'),
            $this->baris(2, 10, 'Selasa', '08:00', '09:00'), // kelas 10 dobel jam -> team teaching dilarang
        ];

        $result = $this->service->validateBentrok($rows);

        $this->assertFalse($result['valid']);
    }

    public function testKelasSamaHariSamaJamTidakOverlapDiterima(): void
    {
        $rows = [
            $this->baris(1, 10, 'Rabu', '07:00', '08:00'),
            $this->baris(2, 10, 'Rabu', '08:00', '09:00'),
        ];

        $result = $this->service->validateBentrok($rows);

        $this->assertTrue($result['valid']);
    }

    public function testGuruBerbedaKelasBerbedaJamOverlapDiterima(): void
    {
        $rows = [
            $this->baris(1, 10, 'Kamis', '07:00', '08:30'),
            $this->baris(2, 20, 'Kamis', '07:30', '09:00'),
        ];

        $result = $this->service->validateBentrok($rows);

        $this->assertTrue($result['valid']);
    }
}
