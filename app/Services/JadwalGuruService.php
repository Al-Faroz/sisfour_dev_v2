<?php

namespace App\Services;

use App\Models\GuruModel;
use App\Models\JadwalGuruModel;
use App\Models\KelasModel;
use App\Models\MataPelajaranModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * JadwalGuruService
 *
 * Import Excel WAJIB (tidak ada form input manual), validasi bentrok jam
 * (guru & kelas), atomic transaction, Query Builder (dilarang raw SQL
 * concat). Referensi: 04_MASTER_DATA §5.2, §7.2; 15_TESTING_POLISH §3.1.
 *
 * validateBentrok() ditulis sebagai method murni (menerima array data, tidak
 * menyentuh DB langsung untuk perbandingan overlap) supaya mudah dites via
 * PHPUnit tanpa perlu database (lihat 15_TESTING_POLISH §3.1 Test Case 1-5).
 */
class JadwalGuruService
{
    protected JadwalGuruModel $jadwalGuruModel;
    protected GuruModel $guruModel;
    protected KelasModel $kelasModel;
    protected MataPelajaranModel $mapelModel;
    protected $db;

    public function __construct()
    {
        $this->jadwalGuruModel = new JadwalGuruModel();
        $this->guruModel       = new GuruModel();
        $this->kelasModel      = new KelasModel();
        $this->mapelModel      = new MataPelajaranModel();
        $this->db              = Database::connect();
    }

    /**
     * Cek apakah dua rentang jam (HH:MM atau HH:MM:SS) saling overlap.
     * Overlap jika: mulaiA < selesaiB DAN mulaiB < selesaiA.
     */
    public function isOverlap(string $mulaiA, string $selesaiA, string $mulaiB, string $selesaiB): bool
    {
        return strtotime($mulaiA) < strtotime($selesaiB) && strtotime($mulaiB) < strtotime($selesaiA);
    }

    /**
     * Validasi bentrok untuk SATU set baris jadwal (baru, hasil parsing Excel
     * atau data manual) terhadap satu sama lain DAN (opsional) terhadap
     * jadwal existing di database.
     *
     * @param array $daftarBaris  list of ['id_guru','id_kelas','hari','jam_mulai','jam_selesai', ...]
     * @param array $existingRows list jadwal aktif existing di DB (id_tahun sama) untuk dicek silang. Kosongkan jika hanya mengecek antar-baris baru.
     *
     * @return array{valid: bool, errors: list<array{index:int,pesan:string}>}
     */
    public function validateBentrok(array $daftarBaris, array $existingRows = []): array
    {
        $errors = [];

        // 1) Cek antar baris baru (guru sama & kelas sama, hari sama, overlap jam)
        foreach ($daftarBaris as $i => $a) {
            foreach ($daftarBaris as $j => $b) {
                if ($i >= $j || $a['hari'] !== $b['hari']) {
                    continue;
                }

                if (!$this->isOverlap($a['jam_mulai'], $a['jam_selesai'], $b['jam_mulai'], $b['jam_selesai'])) {
                    continue;
                }

                if ((int) $a['id_guru'] === (int) $b['id_guru']) {
                    $errors[] = ['index' => $j, 'pesan' => "Baris {$j}: Guru bentrok jam dengan baris {$i} pada hari {$b['hari']}."];
                }

                // Tidak ada team teaching (04_MASTER_DATA §5.2)
                if ((int) $a['id_kelas'] === (int) $b['id_kelas']) {
                    $errors[] = ['index' => $j, 'pesan' => "Baris {$j}: Kelas bentrok jam dengan baris {$i} pada hari {$b['hari']} (tidak ada team teaching)."];
                }
            }
        }

        // 2) Cek tiap baris baru terhadap jadwal existing di DB
        foreach ($daftarBaris as $i => $a) {
            foreach ($existingRows as $e) {
                if ($a['hari'] !== $e['hari']) {
                    continue;
                }
                if (!$this->isOverlap($a['jam_mulai'], $a['jam_selesai'], $e['jam_mulai'], $e['jam_selesai'])) {
                    continue;
                }

                if ((int) $a['id_guru'] === (int) $e['id_guru']) {
                    $errors[] = ['index' => $i, 'pesan' => "Baris {$i}: Guru bentrok dengan jadwal aktif yang sudah ada (id_jadwal {$e['id']})."];
                }
                if ((int) $a['id_kelas'] === (int) $e['id_kelas']) {
                    $errors[] = ['index' => $i, 'pesan' => "Baris {$i}: Kelas bentrok dengan jadwal aktif yang sudah ada (id_jadwal {$e['id']})."];
                }
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Import jadwal dari file Excel. Kolom wajib: nip_guru, nama_kelas,
     * kode_mapel, hari, jam_mulai, jam_selesai, sesi (04_MASTER_DATA §5.2).
     *
     * Semester/Tahun Ajaran baru: jadwal LAMA di-set Nonaktif (tidak dihapus),
     * jadwal baru masuk berstatus Aktif.
     *
     * @return array{success: bool, message: string, errors?: array, jumlah_import?: int}
     */
    public function importJadwal(string $filePath, int $idTahun): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'File Excel tidak dapat dibaca: ' . $e->getMessage()];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, false);
        array_shift($rows); // buang header

        $parsed = [];
        $rowErrors = [];

        foreach ($rows as $i => $row) {
            [$nipGuru, $namaKelas, $kodeMapel, $hari, $jamMulai, $jamSelesai, $sesi] = array_pad($row, 7, null);

            if ($nipGuru === null || trim((string) $nipGuru) === '') {
                continue; // baris kosong, lewati
            }

            $guru = $this->guruModel->where('nip', trim((string) $nipGuru))->first();
            if (!$guru) {
                $rowErrors[] = "Baris " . ($i + 2) . ": NIP guru '{$nipGuru}' tidak ditemukan.";

                continue;
            }

            $kelas = $this->kelasModel->where('nama_kelas', trim((string) $namaKelas))->where('id_tahun', $idTahun)->first();
            if (!$kelas) {
                $rowErrors[] = "Baris " . ($i + 2) . ": Kelas '{$namaKelas}' tidak ditemukan di tahun ajaran ini.";

                continue;
            }

            $mapel = $this->mapelModel->where('kode_mapel', trim((string) $kodeMapel))->first();
            if (!$mapel) {
                $rowErrors[] = "Baris " . ($i + 2) . ": Kode mapel '{$kodeMapel}' tidak ditemukan.";

                continue;
            }

            if (!in_array($sesi, ['Sesi Awal', 'Sesi Akhir', 'Non Sesi'], true)) {
                $rowErrors[] = "Baris " . ($i + 2) . ": Sesi '{$sesi}' tidak valid (harus Sesi Awal/Sesi Akhir/Non Sesi).";

                continue;
            }

            $parsed[] = [
                'id_guru'     => $guru['id'],
                'id_kelas'    => $kelas['id'],
                'id_mapel'    => $mapel['id'],
                'id_tahun'    => $idTahun,
                'hari'        => trim((string) $hari),
                'jam_mulai'   => $this->normalizeJam($jamMulai),
                'jam_selesai' => $this->normalizeJam($jamSelesai),
                'sesi'        => $sesi,
                'status_jadwal' => 'Aktif',
            ];
        }

        if (!empty($rowErrors)) {
            return ['success' => false, 'message' => 'Import dibatalkan, ditemukan baris bermasalah.', 'errors' => $rowErrors];
        }

        if (empty($parsed)) {
            return ['success' => false, 'message' => 'Tidak ada baris valid untuk diimport.'];
        }

        // Validasi bentrok antar baris baru DAN terhadap jadwal aktif existing
        $existing = $this->jadwalGuruModel->where('id_tahun', $idTahun)->where('status_jadwal', 'Aktif')->findAll();
        $cek      = $this->validateBentrok($parsed, $existing);

        if (!$cek['valid']) {
            return [
                'success' => false,
                'message' => 'Import dibatalkan karena ditemukan bentrok jadwal.',
                'errors'  => array_column($cek['errors'], 'pesan'),
            ];
        }

        // Atomic transaction — jika satu baris gagal, seluruh transaksi rollback
        $this->db->transStart();

        $this->jadwalGuruModel->nonaktifkanByTahun($idTahun);

        foreach ($parsed as $baris) {
            $this->jadwalGuruModel->insert($baris);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Import gagal, transaksi dibatalkan (rollback).'];
        }

        return ['success' => true, 'message' => 'Import jadwal berhasil.', 'jumlah_import' => count($parsed)];
    }

    /**
     * Nonaktifkan jadwal lama saat tahun ajaran baru dimulai (dipanggil terpisah
     * jika diperlukan di luar alur import, misal saat aktivasi tahun ajaran baru).
     */
    public function setStatusJadwal(int $idTahun): void
    {
        $this->jadwalGuruModel->nonaktifkanByTahun($idTahun);
    }

    /**
     * Normalisasi nilai jam dari Excel (bisa berupa string "07:30" atau
     * pecahan desimal waktu Excel) menjadi format "HH:MM:SS".
     */
    protected function normalizeJam($value): string
    {
        if (is_numeric($value)) {
            // Nilai desimal waktu Excel (fraksi dari 1 hari)
            $seconds = (int) round(((float) $value) * 86400);
            $h = intdiv($seconds, 3600);
            $m = intdiv($seconds % 3600, 60);

            return sprintf('%02d:%02d:00', $h, $m);
        }

        $value = trim((string) $value);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
        }

        return $value;
    }
}
