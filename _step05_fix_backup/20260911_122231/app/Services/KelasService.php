<?php

namespace App\Services;

use App\Models\AnggotaKelasModel;
use App\Models\KelasModel;
use App\Models\RiwayatSiswaModel;
use App\Models\SiswaModel;
use Config\Database;
use Throwable;

/**
 * KelasService
 *
 * Business logic Master Kelas.
 *
 * Acuan:
 * - docs/04_MASTER_DATA §2, §3.3, §7.1
 *
 * Menangani:
 * - CRUD kelas + auto-generate nama_kelas;
 * - filter kelas;
 * - anggota kelas;
 * - kenaikan kelas checklist;
 * - kelulusan;
 * - soft delete/recycle bin/restore/force delete;
 * - activity log.
 */
class KelasService
{
    protected KelasModel $kelasModel;
    protected SiswaModel $siswaModel;
    protected AnggotaKelasModel $anggotaKelasModel;
    protected RiwayatSiswaModel $riwayatSiswaModel;
    protected $db;

    public function __construct()
    {
        $this->kelasModel = new KelasModel();
        $this->siswaModel = new SiswaModel();
        $this->anggotaKelasModel = new AnggotaKelasModel();
        $this->riwayatSiswaModel = new RiwayatSiswaModel();
        $this->db = Database::connect();
    }

    public function generateNamaKelas(string $tingkat, string $rombel): string
    {
        return trim($tingkat) . '-' . strtoupper(trim($rombel));
    }

    public function getList(array $filter = [], bool $deletedOnly = false): array
    {
        $builder = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.tingkat, k.rombel, k.nama_kelas, k.id_tahun, ' .
                'k.deleted_at, k.created_at, k.updated_at, ' .
                'ta.nama_tahun, ta.semester, ta.status_aktif AS tahun_aktif'
            )
            ->select(
                '(SELECT COUNT(*) FROM anggota_kelas ak ' .
                'WHERE ak.id_kelas = k.id AND ak.id_tahun = k.id_tahun) AS jumlah_siswa',
                false
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun');

        if ($deletedOnly) {
            $builder->where('k.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('k.deleted_at', null);
        }

        $tingkat = trim((string) ($filter['tingkat'] ?? ''));
        if (in_array($tingkat, ['7', '8', '9'], true)) {
            $builder->where('k.tingkat', $tingkat);
        }

        $idTahun = (int) ($filter['id_tahun'] ?? 0);
        if ($idTahun > 0) {
            $builder->where('k.id_tahun', $idTahun);
        }

        return $builder
            ->orderBy('ta.nama_tahun', 'DESC')
            ->orderBy('ta.semester', 'ASC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTahunOptions(): array
    {
        return $this->db
            ->table('tahun_ajaran')
            ->select('id, nama_tahun, semester, status_aktif')
            ->where('deleted_at', null)
            ->orderBy('nama_tahun', 'DESC')
            ->orderBy('semester', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function create(array $data): array
    {
        $payload = $this->normalizeKelasPayload($data);

        $precheck = $this->validateKelasPayload($payload);
        if ($precheck !== null) {
            return $precheck;
        }

        if (
            $this->kelasModel->namaKelasDipakai(
                $payload['nama_kelas'],
                $payload['id_tahun']
            )
        ) {
            return [
                'success' => false,
                'message' => 'Kelas dengan nama yang sama sudah pernah dibuat pada tahun ajaran tersebut. Jika berada di Recycle Bin, pulihkan data lama.',
            ];
        }

        $id = $this->kelasModel->insert($payload, true);

        if ($id === false) {
            return [
                'success' => false,
                'message' => implode(' ', $this->kelasModel->errors())
                    ?: 'Data Kelas gagal disimpan.',
            ];
        }

        $this->logActivity(
            'CREATE',
            'Master Kelas',
            sprintf(
                'Menambahkan kelas %s pada tahun ajaran ID %d.',
                $payload['nama_kelas'],
                $payload['id_tahun']
            )
        );

        return [
            'success' => true,
            'message' => 'Data Kelas berhasil ditambahkan.',
            'id' => (int) $id,
        ];
    }

    public function update(int $id, array $data): array
    {
        $kelas = $this->kelasModel->find($id);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Data Kelas tidak ditemukan.',
            ];
        }

        $payload = $this->normalizeKelasPayload($data);

        $precheck = $this->validateKelasPayload($payload);
        if ($precheck !== null) {
            return $precheck;
        }

        if (
            $this->kelasModel->namaKelasDipakai(
                $payload['nama_kelas'],
                $payload['id_tahun'],
                $id
            )
        ) {
            return [
                'success' => false,
                'message' => 'Nama kelas sudah digunakan pada tahun ajaran tersebut.',
            ];
        }

        /*
         * Perubahan tahun ajaran pada kelas yang sudah mempunyai anggota
         * berisiko memutus konsistensi anggota_kelas/riwayat_siswa.
         */
        if (
            (int) $kelas['id_tahun'] !== $payload['id_tahun']
            && $this->anggotaKelasModel
                ->where('id_kelas', $id)
                ->countAllResults() > 0
        ) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak dapat diubah karena kelas sudah memiliki anggota. Buat kelas baru pada tahun ajaran tujuan.',
            ];
        }

        if (!$this->kelasModel->update($id, $payload)) {
            return [
                'success' => false,
                'message' => implode(' ', $this->kelasModel->errors())
                    ?: 'Data Kelas gagal diperbarui.',
            ];
        }

        $this->logActivity(
            'UPDATE',
            'Master Kelas',
            sprintf(
                'Memperbarui kelas ID %d menjadi %s.',
                $id,
                $payload['nama_kelas']
            )
        );

        return [
            'success' => true,
            'message' => 'Data Kelas berhasil diperbarui.',
        ];
    }

    public function delete(int $id): array
    {
        $kelas = $this->kelasModel->find($id);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Data Kelas tidak ditemukan.',
            ];
        }

        $dependencies = $this->getActiveDependencies($id);

        if ($dependencies !== []) {
            return [
                'success' => false,
                'message' => 'Kelas belum dapat dihapus karena masih digunakan: '
                    . implode(', ', $dependencies) . '.',
            ];
        }

        if (!$this->kelasModel->delete($id)) {
            return [
                'success' => false,
                'message' => 'Data Kelas gagal dipindahkan ke Recycle Bin.',
            ];
        }

        $this->logActivity(
            'DELETE',
            'Master Kelas',
            sprintf(
                'Memindahkan kelas ID %d - %s ke Recycle Bin.',
                $id,
                $kelas['nama_kelas']
            )
        );

        return [
            'success' => true,
            'message' => 'Data Kelas dipindahkan ke Recycle Bin.',
        ];
    }

    public function restore(int $id): array
    {
        $kelas = $this->kelasModel->withDeleted()->find($id);

        if ($kelas === null || empty($kelas['deleted_at'])) {
            return [
                'success' => false,
                'message' => 'Data Kelas pada Recycle Bin tidak ditemukan.',
            ];
        }

        $updated = $this->db
            ->table('kelas')
            ->where('id', $id)
            ->update([
                'deleted_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Data Kelas gagal dipulihkan.',
            ];
        }

        $this->logActivity(
            'RESTORE',
            'Master Kelas',
            sprintf(
                'Memulihkan kelas ID %d - %s.',
                $id,
                $kelas['nama_kelas']
            )
        );

        return [
            'success' => true,
            'message' => 'Data Kelas berhasil dipulihkan.',
        ];
    }

    public function forceDelete(int $id): array
    {
        $kelas = $this->kelasModel->withDeleted()->find($id);

        if ($kelas === null || empty($kelas['deleted_at'])) {
            return [
                'success' => false,
                'message' => 'Data Kelas pada Recycle Bin tidak ditemukan.',
            ];
        }

        $this->db->transBegin();

        try {
            $this->db
                ->table('kelas')
                ->where('id', $id)
                ->delete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Kelas masih direferensikan data lain.'
                );
            }

            $this->logActivity(
                'FORCE_DELETE',
                'Master Kelas',
                sprintf(
                    'Menghapus permanen kelas ID %d - %s.',
                    $id,
                    $kelas['nama_kelas']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Kelas berhasil dihapus permanen.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Hapus permanen gagal. Kelas masih direferensikan anggota kelas, histori siswa, wali kelas, jadwal, presensi, atau data lain.',
            ];
        }
    }

    /**
     * Data untuk modal Kelola Anggota.
     *
     * Daftar kandidat hanya siswa Aktif yang:
     * - belum memiliki kelas pada tahun yang sama; atau
     * - memang sudah menjadi anggota kelas ini.
     */
    public function getAnggotaData(int $idKelas): array
    {
        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ];
        }

        $rows = $this->db
            ->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, ' .
                'ak.id AS id_anggota, ak.id_kelas, ' .
                'k.nama_kelas AS kelas_saat_ini'
            )
            ->join(
                'anggota_kelas ak',
                'ak.id_siswa = s.id AND ak.id_tahun = ' . (int) $kelas['id_tahun'],
                'left'
            )
            ->join('kelas k', 'k.id = ak.id_kelas', 'left')
            ->where('s.deleted_at', null)
            ->where('s.status_aktif', 'Aktif')
            ->groupStart()
                ->where('ak.id IS NULL', null, false)
                ->orWhere('ak.id_kelas', $idKelas)
            ->groupEnd()
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'message' => 'Data anggota berhasil dimuat.',
            'kelas' => $kelas,
            'data' => $rows,
        ];
    }

    public function addAnggota(int $idKelas, int $idSiswa): array
    {
        $kelas = $this->kelasModel->find($idKelas);
        $siswa = $this->siswaModel->find($idSiswa);

        if ($kelas === null || $siswa === null) {
            return [
                'success' => false,
                'message' => 'Kelas atau Siswa tidak ditemukan.',
            ];
        }

        if ($siswa['status_aktif'] !== 'Aktif') {
            return [
                'success' => false,
                'message' => 'Hanya siswa berstatus Aktif yang dapat dimasukkan ke kelas.',
            ];
        }

        $existing = $this->anggotaKelasModel->getKelasSiswa(
            $idSiswa,
            (int) $kelas['id_tahun']
        );

        if ($existing !== null) {
            if ((int) $existing['id_kelas'] === $idKelas) {
                return [
                    'success' => true,
                    'message' => 'Siswa sudah menjadi anggota kelas ini.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Siswa sudah menjadi anggota kelas lain pada tahun ajaran yang sama.',
            ];
        }

        $this->db->transBegin();

        try {
            $idAnggota = $this->anggotaKelasModel->insert([
                'id_siswa' => $idSiswa,
                'id_kelas' => $idKelas,
                'id_tahun' => (int) $kelas['id_tahun'],
            ], true);

            if ($idAnggota === false) {
                throw new \RuntimeException(
                    'Keanggotaan kelas gagal disimpan.'
                );
            }

            $riwayat = $this->riwayatSiswaModel->getRiwayatAktif(
                $idSiswa,
                (int) $kelas['id_tahun']
            );

            if ($riwayat === null) {
                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => (int) $kelas['id_tahun'],
                    'id_kelas' => $idKelas,
                    'status' => 'Aktif',
                    'tanggal_mulai' => date('Y-m-d'),
                    'tanggal_selesai' => null,
                    'keterangan' => 'Penempatan anggota kelas',
                ]) === false) {
                    throw new \RuntimeException(
                        'Histori siswa gagal dicatat.'
                    );
                }
            }

            $this->logActivity(
                'ADD_ANGGOTA',
                'Master Kelas',
                sprintf(
                    'Menambahkan siswa ID %d ke kelas %s.',
                    $idSiswa,
                    $kelas['nama_kelas']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Siswa berhasil dimasukkan ke kelas.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mengeluarkan siswa dari keanggotaan kelas untuk koreksi administratif.
     * Status siswa tetap Aktif, tetapi riwayat Aktif pada tahun tersebut ditutup.
     */
    public function removeAnggota(int $idKelas, int $idSiswa): array
    {
        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ];
        }

        $anggota = $this->anggotaKelasModel
            ->where('id_siswa', $idSiswa)
            ->where('id_kelas', $idKelas)
            ->where('id_tahun', (int) $kelas['id_tahun'])
            ->first();

        if ($anggota === null) {
            return [
                'success' => false,
                'message' => 'Siswa bukan anggota kelas ini.',
            ];
        }

        $this->db->transBegin();

        try {
            if (!$this->anggotaKelasModel->delete((int) $anggota['id'])) {
                throw new \RuntimeException(
                    'Keanggotaan kelas gagal dihapus.'
                );
            }

            $this->riwayatSiswaModel->tutupRiwayatAktif(
                $idSiswa,
                (int) $kelas['id_tahun'],
                date('Y-m-d')
            );

            $this->logActivity(
                'REMOVE_ANGGOTA',
                'Master Kelas',
                sprintf(
                    'Mengeluarkan siswa ID %d dari kelas %s.',
                    $idSiswa,
                    $kelas['nama_kelas']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Siswa berhasil dikeluarkan dari kelas.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Data checklist untuk kenaikan/kelulusan.
     */
    public function getProcessData(int $idKelas): array
    {
        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ];
        }

        $siswa = $this->anggotaKelasModel
            ->getByKelasTahun(
                $idKelas,
                (int) $kelas['id_tahun']
            );

        $siswa = array_values(
            array_filter(
                $siswa,
                static fn (array $row): bool =>
                    ($row['status_aktif'] ?? '') === 'Aktif'
            )
        );

        $targetKelas = $this->db
            ->table('kelas k')
            ->select(
                'k.id, k.nama_kelas, k.tingkat, k.rombel, k.id_tahun, ' .
                'ta.nama_tahun, ta.semester'
            )
            ->join('tahun_ajaran ta', 'ta.id = k.id_tahun')
            ->where('k.deleted_at', null)
            ->where('ta.deleted_at', null)
            ->where('k.id_tahun !=', (int) $kelas['id_tahun'])
            ->orderBy('ta.nama_tahun', 'ASC')
            ->orderBy('ta.semester', 'ASC')
            ->orderBy('k.tingkat', 'ASC')
            ->orderBy('k.rombel', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'message' => 'Data proses kelas berhasil dimuat.',
            'kelas' => $kelas,
            'siswa' => $siswa,
            'target_kelas' => $targetKelas,
        ];
    }

    public function naikKelas(
        int $idKelasAsal,
        int $idKelasTujuan,
        int $idTahunBaru,
        array $daftarSiswaTerpilih
    ): array {
        if ($daftarSiswaTerpilih === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada siswa yang dipilih untuk dipindahkan.',
            ];
        }

        $kelasAsal = $this->kelasModel->find($idKelasAsal);
        $kelasTujuan = $this->kelasModel->find($idKelasTujuan);

        if ($kelasAsal === null || $kelasTujuan === null) {
            return [
                'success' => false,
                'message' => 'Kelas asal atau kelas tujuan tidak ditemukan.',
            ];
        }

        if ((int) $kelasTujuan['id_tahun'] !== $idTahunBaru) {
            return [
                'success' => false,
                'message' => 'Kelas tujuan tidak berada pada tahun ajaran tujuan.',
            ];
        }

        if ((int) $kelasAsal['id_tahun'] === $idTahunBaru) {
            return [
                'success' => false,
                'message' => 'Kenaikan kelas harus menuju tahun ajaran yang berbeda.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            foreach ($daftarSiswaTerpilih as $idSiswaRaw) {
                $idSiswa = (int) $idSiswaRaw;

                $anggotaAsal = $this->anggotaKelasModel
                    ->where('id_siswa', $idSiswa)
                    ->where('id_kelas', $idKelasAsal)
                    ->where('id_tahun', (int) $kelasAsal['id_tahun'])
                    ->first();

                if ($anggotaAsal === null) {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} bukan anggota kelas asal."
                    );
                }

                $siswa = $this->siswaModel->find($idSiswa);

                if ($siswa === null || $siswa['status_aktif'] !== 'Aktif') {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} tidak berstatus Aktif."
                    );
                }

                $this->riwayatSiswaModel->tutupRiwayatAktif(
                    $idSiswa,
                    (int) $kelasAsal['id_tahun'],
                    $tanggal
                );

                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => $idTahunBaru,
                    'id_kelas' => $idKelasTujuan,
                    'status' => 'Aktif',
                    'tanggal_mulai' => $tanggal,
                    'tanggal_selesai' => null,
                    'keterangan' => 'Kenaikan kelas dari ' . $kelasAsal['nama_kelas'],
                ]) === false) {
                    throw new \RuntimeException(
                        'Histori kenaikan kelas gagal dicatat.'
                    );
                }

                if (!$this->anggotaKelasModel->pindahkan(
                    $idSiswa,
                    $idKelasTujuan,
                    $idTahunBaru
                )) {
                    throw new \RuntimeException(
                        'Keanggotaan kelas gagal diperbarui.'
                    );
                }

                if (!$this->siswaModel->update($idSiswa, [
                    'status_aktif' => 'Aktif',
                    'tanggal_mutasi' => null,
                    'keterangan_mutasi' => null,
                ])) {
                    throw new \RuntimeException(
                        'Status siswa gagal diperbarui.'
                    );
                }
            }

            $this->logActivity(
                'NAIK_KELAS',
                'Master Kelas',
                sprintf(
                    'Memindahkan %d siswa dari %s ke %s.',
                    count($daftarSiswaTerpilih),
                    $kelasAsal['nama_kelas'],
                    $kelasTujuan['nama_kelas']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Transaksi kenaikan kelas gagal.'
                );
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Kenaikan kelas berhasil.',
                'jumlah_dipindah' => count($daftarSiswaTerpilih),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function luluskan(
        int $idKelas,
        array $daftarSiswaTerpilih
    ): array {
        if ($daftarSiswaTerpilih === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada siswa yang dipilih untuk diluluskan.',
            ];
        }

        $kelas = $this->kelasModel->find($idKelas);

        if ($kelas === null) {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ];
        }

        if ((string) $kelas['tingkat'] !== '9') {
            return [
                'success' => false,
                'message' => 'Proses kelulusan hanya tersedia untuk kelas tingkat 9.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            foreach ($daftarSiswaTerpilih as $idSiswaRaw) {
                $idSiswa = (int) $idSiswaRaw;

                $anggota = $this->anggotaKelasModel
                    ->where('id_siswa', $idSiswa)
                    ->where('id_kelas', $idKelas)
                    ->where('id_tahun', (int) $kelas['id_tahun'])
                    ->first();

                if ($anggota === null) {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} bukan anggota kelas ini."
                    );
                }

                $siswa = $this->siswaModel->find($idSiswa);

                if ($siswa === null || $siswa['status_aktif'] !== 'Aktif') {
                    throw new \RuntimeException(
                        "Siswa ID {$idSiswa} tidak berstatus Aktif."
                    );
                }

                $this->riwayatSiswaModel->tutupRiwayatAktif(
                    $idSiswa,
                    (int) $kelas['id_tahun'],
                    $tanggal
                );

                if ($this->riwayatSiswaModel->insert([
                    'id_siswa' => $idSiswa,
                    'id_tahun' => (int) $kelas['id_tahun'],
                    'id_kelas' => $idKelas,
                    'status' => 'Lulus',
                    'tanggal_mulai' => $tanggal,
                    'tanggal_selesai' => $tanggal,
                    'keterangan' => 'Lulus dari ' . $kelas['nama_kelas'],
                ]) === false) {
                    throw new \RuntimeException(
                        'Histori kelulusan gagal dicatat.'
                    );
                }

                if (!$this->siswaModel->update($idSiswa, [
                    'status_aktif' => 'Lulus',
                    'tanggal_mutasi' => $tanggal,
                    'keterangan_mutasi' => 'Lulus',
                ])) {
                    throw new \RuntimeException(
                        'Status kelulusan siswa gagal diperbarui.'
                    );
                }

                $this->nonaktifkanKartu($idSiswa);
            }

            $this->logActivity(
                'LULUSKAN',
                'Master Kelas',
                sprintf(
                    'Meluluskan %d siswa dari %s.',
                    count($daftarSiswaTerpilih),
                    $kelas['nama_kelas']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi kelulusan gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Kelulusan berhasil diproses.',
                'jumlah_diluluskan' => count($daftarSiswaTerpilih),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Tetap dipakai oleh Master Siswa untuk Pindah/Keluar.
     */
    public function mutasiSiswa(
        int $idSiswa,
        string $statusBaru,
        string $keterangan
    ): array {
        if (!in_array($statusBaru, ['Pindah', 'Keluar'], true)) {
            return [
                'success' => false,
                'message' => 'Status mutasi hanya boleh Pindah atau Keluar.',
            ];
        }

        $keterangan = trim($keterangan);

        if ($keterangan === '') {
            return [
                'success' => false,
                'message' => 'Keterangan mutasi wajib diisi.',
            ];
        }

        $siswa = $this->siswaModel->find($idSiswa);

        if ($siswa === null) {
            return [
                'success' => false,
                'message' => 'Siswa tidak ditemukan.',
            ];
        }

        if ($siswa['status_aktif'] !== 'Aktif') {
            return [
                'success' => false,
                'message' => 'Mutasi hanya dapat diproses untuk siswa berstatus Aktif.',
            ];
        }

        $anggota = $this->db
            ->table('anggota_kelas ak')
            ->select('ak.id_kelas, ak.id_tahun')
            ->join('tahun_ajaran ta', 'ta.id = ak.id_tahun')
            ->where('ak.id_siswa', $idSiswa)
            ->orderBy('ta.status_aktif', 'DESC')
            ->orderBy('ak.id_tahun', 'DESC')
            ->get()
            ->getRowArray();

        if ($anggota === null) {
            return [
                'success' => false,
                'message' => 'Mutasi tidak dapat diproses karena siswa belum memiliki riwayat kelas.',
            ];
        }

        $tanggal = date('Y-m-d');
        $this->db->transBegin();

        try {
            $this->riwayatSiswaModel->tutupRiwayatAktif(
                $idSiswa,
                (int) $anggota['id_tahun'],
                $tanggal
            );

            if ($this->riwayatSiswaModel->insert([
                'id_siswa' => $idSiswa,
                'id_tahun' => (int) $anggota['id_tahun'],
                'id_kelas' => (int) $anggota['id_kelas'],
                'status' => $statusBaru,
                'tanggal_mulai' => $tanggal,
                'tanggal_selesai' => $tanggal,
                'keterangan' => $keterangan,
            ]) === false) {
                throw new \RuntimeException(
                    'Histori mutasi gagal dicatat.'
                );
            }

            if (!$this->siswaModel->update($idSiswa, [
                'status_aktif' => $statusBaru,
                'tanggal_mutasi' => $tanggal,
                'keterangan_mutasi' => $keterangan,
            ])) {
                throw new \RuntimeException(
                    'Status mutasi siswa gagal diperbarui.'
                );
            }

            $this->nonaktifkanKartu($idSiswa);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi mutasi gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Mutasi siswa berhasil dicatat.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function normalizeKelasPayload(array $data): array
    {
        $tingkat = trim((string) ($data['tingkat'] ?? ''));
        $rombel = strtoupper(trim((string) ($data['rombel'] ?? '')));
        $idTahun = (int) ($data['id_tahun'] ?? 0);

        return [
            'tingkat' => $tingkat,
            'rombel' => $rombel,
            'nama_kelas' => $this->generateNamaKelas($tingkat, $rombel),
            'id_tahun' => $idTahun,
        ];
    }

    protected function validateKelasPayload(array $payload): ?array
    {
        if (!in_array($payload['tingkat'], ['7', '8', '9'], true)) {
            return [
                'success' => false,
                'message' => 'Tingkat hanya boleh 7, 8, atau 9.',
            ];
        }

        if (
            $payload['rombel'] === ''
            || !preg_match('/^[A-Z0-9-]{1,10}$/', $payload['rombel'])
        ) {
            return [
                'success' => false,
                'message' => 'Rombel wajib diisi dan hanya boleh berisi huruf, angka, atau tanda minus.',
            ];
        }

        $tahun = $this->db
            ->table('tahun_ajaran')
            ->where('id', $payload['id_tahun'])
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if ($tahun === null) {
            return [
                'success' => false,
                'message' => 'Tahun ajaran tidak valid.',
            ];
        }

        return null;
    }

    /**
     * Dependency aktif yang membuat soft delete berbahaya.
     */
    protected function getActiveDependencies(int $idKelas): array
    {
        $dependencies = [];

        if (
            $this->db
                ->table('anggota_kelas')
                ->where('id_kelas', $idKelas)
                ->countAllResults() > 0
        ) {
            $dependencies[] = 'anggota kelas';
        }

        if (
            $this->db
                ->table('mapping_wali_kelas')
                ->where('id_kelas', $idKelas)
                ->where('deleted_at', null)
                ->countAllResults() > 0
        ) {
            $dependencies[] = 'wali kelas aktif';
        }

        if (
            $this->db
                ->table('jadwal_guru')
                ->where('id_kelas', $idKelas)
                ->where('status_jadwal', 'Aktif')
                ->countAllResults() > 0
        ) {
            $dependencies[] = 'jadwal guru aktif';
        }

        return $dependencies;
    }

    protected function nonaktifkanKartu(int $idSiswa): void
    {
        $this->db
            ->table('kartu_pelajar')
            ->where('id_siswa', $idSiswa)
            ->where('status_aktif', 'Aktif')
            ->update([
                'status_aktif' => 'Nonaktif',
            ]);
    }

    protected function logActivity(
        string $aksi,
        string $modul,
        string $keterangan
    ): void {
        $idUser = session()->get('user_id');

        $this->db
            ->table('log_activity')
            ->insert([
                'id_user' => $idUser ? (int) $idUser : null,
                'aksi' => $aksi,
                'modul' => $modul,
                'keterangan' => $keterangan,
                'waktu' => date('Y-m-d H:i:s'),
            ]);
    }
}
