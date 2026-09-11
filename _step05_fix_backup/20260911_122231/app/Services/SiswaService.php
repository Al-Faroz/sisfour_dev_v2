<?php

namespace App\Services;

use App\Models\SiswaModel;
use App\Models\UserModel;
use App\Models\UserRolesModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class SiswaService
{
    protected BaseConnection $db;
    protected SiswaModel $siswaModel;
    protected UserModel $userModel;
    protected UserRolesModel $userRolesModel;
    protected UploadService $uploadService;
    protected AuthService $authService;
    protected KelasService $kelasService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->siswaModel = new SiswaModel();
        $this->userModel = new UserModel();
        $this->userRolesModel = new UserRolesModel();
        $this->uploadService = new UploadService();
        $this->authService = new AuthService();
        $this->kelasService = new KelasService();
    }

    public function getList(array $filter, int $userId, bool $deletedOnly = false): array
    {
        $idTahunAktif = $this->getIdTahunAktif();

        $builder = $this->db->table('siswa s')
            ->select(
                's.id, s.nik, s.nisn, s.nama, s.jenis_kelamin, s.tempat_lahir, ' .
                's.tanggal_lahir, s.alamat, s.no_telepon, s.kebutuhan_khusus, ' .
                's.disabilitas, s.nomor_kip_pip, s.nama_ayah_kandung, ' .
                's.nama_ibu_kandung, s.nama_wali, s.foto, s.status_aktif, ' .
                's.tanggal_mutasi, s.keterangan_mutasi, s.deleted_at, ' .
                's.created_at, s.updated_at'
            );

        if ($idTahunAktif !== null) {
            $builder
                ->select('ak.id_kelas AS id_kelas_aktif, k.nama_kelas AS nama_kelas_aktif')
                ->join(
                    'anggota_kelas ak',
                    'ak.id_siswa = s.id AND ak.id_tahun = ' . (int) $idTahunAktif,
                    'left'
                )
                ->join(
                    'kelas k',
                    'k.id = ak.id_kelas AND k.deleted_at IS NULL',
                    'left'
                );
        } else {
            $builder->select('NULL AS id_kelas_aktif, NULL AS nama_kelas_aktif', false);
        }

        if ($deletedOnly) {
            $builder->where('s.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('s.deleted_at', null);

            if (!$this->applyViewScope($builder, $userId, $idTahunAktif)) {
                return [];
            }
        }

        $nama = trim((string) ($filter['nama'] ?? ''));
        $nik = trim((string) ($filter['nik'] ?? ''));
        $nisn = trim((string) ($filter['nisn'] ?? ''));
        $idKelas = (int) ($filter['id_kelas'] ?? 0);
        $status = trim((string) ($filter['status_aktif'] ?? ''));

        if ($nama !== '') {
            $builder->like('s.nama', $nama);
        }
        if ($nik !== '') {
            $builder->like('s.nik', $nik);
        }
        if ($nisn !== '') {
            $builder->like('s.nisn', $nisn);
        }
        if ($idKelas > 0 && $idTahunAktif !== null) {
            $builder->where('ak.id_kelas', $idKelas);
        }
        if (in_array($status, ['Aktif', 'Lulus', 'Pindah', 'Keluar'], true)) {
            $builder->where('s.status_aktif', $status);
        }

        return $builder->orderBy('s.nama', 'ASC')->get()->getResultArray();
    }

    public function getKelasOptions(int $userId): array
    {
        $idTahun = $this->getIdTahunAktif();

        if ($idTahun === null) {
            return [];
        }

        $scope = $this->authService->resolveScope('master_siswa.view', $userId);

        $builder = $this->db->table('kelas')
            ->select('id, nama_kelas, tingkat, rombel')
            ->where('id_tahun', $idTahun)
            ->where('deleted_at', null);

        if ($scope === 'KELAS_DIAMPU') {
            $kelas = $this->authService->getKelasDiampu(
                $this->getIdGuruUser($userId),
                $idTahun
            );

            if ($kelas === []) {
                return [];
            }

            $builder->whereIn('id', $kelas);
        } elseif ($scope === 'DIRI_SENDIRI') {
            $idSiswa = $this->getIdSiswaUser($userId);

            if ($idSiswa <= 0) {
                return [];
            }

            $anggota = $this->db->table('anggota_kelas')
                ->select('id_kelas')
                ->where('id_siswa', $idSiswa)
                ->where('id_tahun', $idTahun)
                ->get()
                ->getRowArray();

            if ($anggota === null) {
                return [];
            }

            $builder->where('id', (int) $anggota['id_kelas']);
        } elseif ($scope !== 'SEMUA') {
            return [];
        }

        return $builder
            ->orderBy('tingkat', 'ASC')
            ->orderBy('rombel', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getEditScope(int $userId): string
    {
        return $this->authService->resolveScope('master_siswa.edit_biodata', $userId);
    }

    public function getManageScope(int $userId): string
    {
        return $this->authService->resolveScope('master_siswa.manage', $userId);
    }

    public function getImportExportScope(int $userId): string
    {
        return $this->authService->resolveScope('master_siswa.import_export', $userId);
    }

    public function create(array $data, ?UploadedFile $foto = null): array
    {
        $payload = $this->normalizePayload($data);
        $payload['status_aktif'] = 'Aktif';
        $payload['tanggal_mutasi'] = null;
        $payload['keterangan_mutasi'] = null;

        $precheck = $this->validateBusiness($payload);

        if ($precheck !== null) {
            return $precheck;
        }

        $newFoto = null;
        $this->db->transBegin();

        try {
            if ($foto !== null && $foto->getError() !== UPLOAD_ERR_NO_FILE) {
                $newFoto = $this->processFoto($foto, $payload['nisn']);
                $payload['foto'] = $newFoto;
            }

            $idSiswa = $this->siswaModel->insert($payload, true);

            if ($idSiswa === false) {
                throw new \RuntimeException(
                    implode(' ', $this->siswaModel->errors()) ?: 'Data Siswa gagal disimpan.'
                );
            }

            $idSiswa = (int) $idSiswa;

            $idUser = $this->userModel->insert([
                'username' => $payload['nisn'],
                'password' => password_hash($payload['nisn'], PASSWORD_DEFAULT),
                'role' => 'siswa',
                'id_guru' => null,
                'id_pegawai' => null,
                'id_siswa' => $idSiswa,
                'status_aktif' => 1,
                'auth_version' => 1,
            ], true);

            if ($idUser === false) {
                throw new \RuntimeException(
                    implode(' ', $this->userModel->errors()) ?: 'Akun Siswa gagal dibuat.'
                );
            }

            if ($this->userRolesModel->insert([
                'id_user' => (int) $idUser,
                'role' => 'siswa',
            ]) === false) {
                throw new \RuntimeException('Role akun Siswa gagal dibuat.');
            }

            $this->logActivity(
                'CREATE',
                'Master Siswa',
                sprintf('Menambahkan Siswa NISN %s - %s.', $payload['nisn'], $payload['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Siswa berhasil ditambahkan dan akun Siswa otomatis dibuat.',
                'id' => $idSiswa,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            if ($newFoto !== null) {
                $this->deleteFotoFile($newFoto);
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update(int $id, array $data, int $userId): array
    {
        $siswa = $this->siswaModel->find($id);

        if ($siswa === null) {
            return ['success' => false, 'message' => 'Data Siswa tidak ditemukan.'];
        }

        $scope = $this->getEditScope($userId);

        if (!$this->canAccessStudentByScope($id, $userId, $scope)) {
            return ['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengedit siswa ini.'];
        }

        $payload = $this->normalizePayload($data);

        if ($scope !== 'SEMUA') {
            $payload['nisn'] = $siswa['nisn'];
        }

        $precheck = $this->validateBusiness($payload, $id);

        if ($precheck !== null) {
            return $precheck;
        }

        $this->db->transBegin();

        try {
            if (!$this->siswaModel->update($id, $payload)) {
                throw new \RuntimeException(
                    implode(' ', $this->siswaModel->errors()) ?: 'Data Siswa gagal diperbarui.'
                );
            }

            if ($siswa['nisn'] !== $payload['nisn']) {
                $linkedUser = $this->db->table('users')
                    ->where('id_siswa', $id)
                    ->get()
                    ->getRowArray();

                if ($linkedUser !== null) {
                    $usernameDipakai = $this->db->table('users')
                        ->where('username', $payload['nisn'])
                        ->where('id !=', (int) $linkedUser['id'])
                        ->countAllResults() > 0;

                    if ($usernameDipakai) {
                        throw new \RuntimeException(
                            'NISN baru tidak dapat digunakan karena username yang sama sudah dipakai akun lain.'
                        );
                    }

                    $this->db->table('users')
                        ->where('id', (int) $linkedUser['id'])
                        ->update([
                            'username' => $payload['nisn'],
                            'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            $this->logActivity(
                'UPDATE',
                'Master Siswa',
                sprintf('Memperbarui Siswa ID %d - %s.', $id, $payload['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Biodata Siswa berhasil diperbarui.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function uploadFoto(int $id, UploadedFile $foto, int $userId): array
    {
        $siswa = $this->siswaModel->find($id);

        if ($siswa === null) {
            return ['success' => false, 'message' => 'Data Siswa tidak ditemukan.'];
        }

        $scope = $this->getEditScope($userId);

        if (!$this->canAccessStudentByScope($id, $userId, $scope)) {
            return ['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengganti foto siswa ini.'];
        }

        $newFoto = null;

        try {
            $newFoto = $this->processFoto($foto, $siswa['nisn']);

            if (!$this->siswaModel->update($id, ['foto' => $newFoto])) {
                throw new \RuntimeException('Foto Siswa gagal disimpan.');
            }

            if (!empty($siswa['foto'])) {
                $this->deleteFotoFile((string) $siswa['foto']);
            }

            $this->logActivity(
                'UPDATE_FOTO',
                'Master Siswa',
                sprintf('Mengganti foto Siswa ID %d - %s.', $id, $siswa['nama'])
            );

            return [
                'success' => true,
                'message' => 'Foto Siswa berhasil diperbarui.',
                'foto' => $newFoto,
            ];
        } catch (Throwable $e) {
            if ($newFoto !== null) {
                $this->deleteFotoFile($newFoto);
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function mutasi(int $id, string $status, string $keterangan): array
    {
        $result = $this->kelasService->mutasiSiswa($id, $status, $keterangan);

        if (!empty($result['success'])) {
            $this->logActivity(
                'MUTASI',
                'Master Siswa',
                sprintf('Mutasi Siswa ID %d menjadi %s. %s', $id, $status, $keterangan)
            );
        }

        return $result;
    }

    public function delete(int $id): array
    {
        $siswa = $this->siswaModel->find($id);

        if ($siswa === null) {
            return ['success' => false, 'message' => 'Data Siswa tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            if (!$this->siswaModel->delete($id)) {
                throw new \RuntimeException('Data Siswa gagal dipindahkan ke Recycle Bin.');
            }

            $linkedUser = $this->db->table('users')
                ->where('id_siswa', $id)
                ->get()
                ->getRowArray();

            if ($linkedUser !== null) {
                $this->db->table('users')
                    ->where('id', (int) $linkedUser['id'])
                    ->update([
                        'status_aktif' => 0,
                        'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            $this->logActivity(
                'DELETE',
                'Master Siswa',
                sprintf('Memindahkan Siswa ID %d - %s ke Recycle Bin.', $id, $siswa['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Data Siswa dipindahkan ke Recycle Bin.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restore(int $id): array
    {
        $siswa = $this->siswaModel->withDeleted()->find($id);

        if ($siswa === null || empty($siswa['deleted_at'])) {
            return ['success' => false, 'message' => 'Data Siswa pada Recycle Bin tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            $this->db->table('siswa')
                ->where('id', $id)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $linkedUser = $this->db->table('users')
                ->where('id_siswa', $id)
                ->get()
                ->getRowArray();

            if ($linkedUser !== null) {
                $this->db->table('users')
                    ->where('id', (int) $linkedUser['id'])
                    ->update([
                        'status_aktif' => 1,
                        'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } else {
                if ($this->db->table('users')
                    ->where('username', $siswa['nisn'])
                    ->countAllResults() > 0) {
                    throw new \RuntimeException(
                        'Restore gagal: username NISN sudah digunakan akun lain.'
                    );
                }

                $idUser = $this->userModel->insert([
                    'username' => $siswa['nisn'],
                    'password' => password_hash($siswa['nisn'], PASSWORD_DEFAULT),
                    'role' => 'siswa',
                    'id_guru' => null,
                    'id_pegawai' => null,
                    'id_siswa' => $id,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException('Akun Siswa gagal dibuat kembali saat restore.');
                }

                if ($this->userRolesModel->insert([
                    'id_user' => (int) $idUser,
                    'role' => 'siswa',
                ]) === false) {
                    throw new \RuntimeException('Role akun Siswa gagal dibuat kembali.');
                }
            }

            $this->logActivity(
                'RESTORE',
                'Master Siswa',
                sprintf('Memulihkan Siswa ID %d - %s dari Recycle Bin.', $id, $siswa['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Data Siswa berhasil dipulihkan.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function forceDelete(int $id): array
    {
        $siswa = $this->siswaModel->withDeleted()->find($id);

        if ($siswa === null || empty($siswa['deleted_at'])) {
            return ['success' => false, 'message' => 'Data Siswa pada Recycle Bin tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            $this->db->table('users')->where('id_siswa', $id)->delete();
            $this->db->table('siswa')->where('id', $id)->delete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Siswa masih direferensikan data lain.');
            }

            $this->logActivity(
                'FORCE_DELETE',
                'Master Siswa',
                sprintf('Menghapus permanen Siswa ID %d - %s.', $id, $siswa['nama'])
            );

            $this->db->transCommit();

            if (!empty($siswa['foto'])) {
                $this->deleteFotoFile((string) $siswa['foto']);
            }

            return ['success' => true, 'message' => 'Data Siswa berhasil dihapus permanen.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Hapus permanen gagal. Data Siswa masih digunakan oleh kelas, presensi, kartu pelajar, BK, prestasi, atau histori.',
            ];
        }
    }

    public function importExcel(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            return ['success' => false, 'message' => 'File import tidak valid.'];
        }

        $extension = strtolower((string) $file->getClientExtension());

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return ['success' => false, 'message' => 'File import harus berformat XLSX atau XLS.'];
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'File Excel tidak dapat dibaca.'];
        }

        if (count($rows) < 2) {
            return ['success' => false, 'message' => 'File import tidak memiliki data Siswa.'];
        }

        $header = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
            $rows[0]
        );

        $expected = [
            'NIK',
            'NISN',
            'NAMA LENGKAP',
            'JENIS KELAMIN',
            'TEMPAT LAHIR',
            'TANGGAL LAHIR',
            'ALAMAT',
        ];

        if (array_slice($header, 0, 7) !== $expected) {
            return [
                'success' => false,
                'message' => 'Header template tidak sesuai. Gunakan template resmi Master Siswa.',
            ];
        }

        $prepared = [];
        $seenNik = [];
        $seenNisn = [];

        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $excelRow = $i + 1;
            $nik = trim((string) ($rows[$i][0] ?? ''));
            $nisn = trim((string) ($rows[$i][1] ?? ''));
            $nama = trim((string) ($rows[$i][2] ?? ''));
            $jk = strtoupper(trim((string) ($rows[$i][3] ?? '')));
            $tempat = trim((string) ($rows[$i][4] ?? ''));
            $tanggal = $this->normalizeExcelDate($rows[$i][5] ?? null);
            $alamat = trim((string) ($rows[$i][6] ?? ''));

            if (
                $nik === '' && $nisn === '' && $nama === '' && $jk === ''
                && $tempat === '' && $tanggal === null && $alamat === ''
            ) {
                continue;
            }

            if (!preg_match('/^\\d{16}$/', $nik)) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIK wajib tepat 16 digit.",
                ];
            }

            if ($nisn === '' || $nama === '' || !in_array($jk, ['L', 'P'], true)) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NISN, nama, dan jenis kelamin L/P wajib valid.",
                ];
            }

            if (isset($seenNik[$nik])) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIK {$nik} duplikat di dalam file.",
                ];
            }

            if (isset($seenNisn[$nisn])) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NISN {$nisn} duplikat di dalam file.",
                ];
            }

            $seenNik[$nik] = true;
            $seenNisn[$nisn] = true;

            if ($this->db->table('siswa')->where('nik', $nik)->countAllResults() > 0) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIK {$nik} sudah terdaftar.",
                ];
            }

            if ($this->db->table('siswa')->where('nisn', $nisn)->countAllResults() > 0) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NISN {$nisn} sudah terdaftar.",
                ];
            }

            if ($this->db->table('users')->where('username', $nisn)->countAllResults() > 0) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: username {$nisn} sudah digunakan.",
                ];
            }

            $prepared[] = [
                'nik' => $nik,
                'nisn' => $nisn,
                'nama' => $nama,
                'jenis_kelamin' => $jk,
                'tempat_lahir' => $tempat !== '' ? $tempat : null,
                'tanggal_lahir' => $tanggal,
                'alamat' => $alamat !== '' ? $alamat : null,
                'no_telepon' => null,
                'kebutuhan_khusus' => null,
                'disabilitas' => null,
                'nomor_kip_pip' => null,
                'nama_ayah_kandung' => null,
                'nama_ibu_kandung' => null,
                'nama_wali' => null,
                'foto' => null,
                'status_aktif' => 'Aktif',
                'tanggal_mutasi' => null,
                'keterangan_mutasi' => null,
            ];
        }

        if ($prepared === []) {
            return ['success' => false, 'message' => 'Tidak ada baris data Siswa yang dapat diimport.'];
        }

        $this->db->transBegin();

        try {
            foreach ($prepared as $row) {
                $idSiswa = $this->siswaModel->insert($row, true);

                if ($idSiswa === false) {
                    throw new \RuntimeException(
                        implode(' ', $this->siswaModel->errors()) ?: 'Insert Siswa gagal.'
                    );
                }

                $idUser = $this->userModel->insert([
                    'username' => $row['nisn'],
                    'password' => password_hash($row['nisn'], PASSWORD_DEFAULT),
                    'role' => 'siswa',
                    'id_guru' => null,
                    'id_pegawai' => null,
                    'id_siswa' => (int) $idSiswa,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException('Pembuatan akun Siswa gagal.');
                }

                if ($this->userRolesModel->insert([
                    'id_user' => (int) $idUser,
                    'role' => 'siswa',
                ]) === false) {
                    throw new \RuntimeException('Pembuatan role Siswa gagal.');
                }
            }

            $this->logActivity(
                'IMPORT',
                'Master Siswa',
                sprintf('Import %d Siswa dari Excel.', count($prepared))
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf('%d data Siswa berhasil diimport.', count($prepared)),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Import dibatalkan seluruhnya: ' . $e->getMessage(),
            ];
        }
    }

    protected function normalizePayload(array $data): array
    {
        $nullable = static function ($value): ?string {
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        };

        return [
            'nik' => trim((string) ($data['nik'] ?? '')),
            'nisn' => trim((string) ($data['nisn'] ?? '')),
            'nama' => trim((string) ($data['nama'] ?? '')),
            'jenis_kelamin' => strtoupper(trim((string) ($data['jenis_kelamin'] ?? ''))),
            'tempat_lahir' => $nullable($data['tempat_lahir'] ?? null),
            'tanggal_lahir' => $nullable($data['tanggal_lahir'] ?? null),
            'alamat' => $nullable($data['alamat'] ?? null),
            'no_telepon' => $nullable($data['no_telepon'] ?? null),
            'kebutuhan_khusus' => $nullable($data['kebutuhan_khusus'] ?? null),
            'disabilitas' => $nullable($data['disabilitas'] ?? null),
            'nomor_kip_pip' => $nullable($data['nomor_kip_pip'] ?? null),
            'nama_ayah_kandung' => $nullable($data['nama_ayah_kandung'] ?? null),
            'nama_ibu_kandung' => $nullable($data['nama_ibu_kandung'] ?? null),
            'nama_wali' => $nullable($data['nama_wali'] ?? null),
        ];
    }

    protected function validateBusiness(array $payload, ?int $exceptId = null): ?array
    {
        if (!preg_match('/^\\d{16}$/', $payload['nik'])) {
            return ['success' => false, 'message' => 'NIK wajib tepat 16 digit angka.'];
        }

        if ($payload['nisn'] === '') {
            return ['success' => false, 'message' => 'NISN wajib diisi.'];
        }

        $nikBuilder = $this->db->table('siswa')->where('nik', $payload['nik']);
        $nisnBuilder = $this->db->table('siswa')->where('nisn', $payload['nisn']);

        if ($exceptId !== null) {
            $nikBuilder->where('id !=', $exceptId);
            $nisnBuilder->where('id !=', $exceptId);
        }

        if ($nikBuilder->countAllResults() > 0) {
            return ['success' => false, 'message' => 'NIK sudah terdaftar.'];
        }

        if ($nisnBuilder->countAllResults() > 0) {
            return ['success' => false, 'message' => 'NISN sudah terdaftar.'];
        }

        $userBuilder = $this->db->table('users')->where('username', $payload['nisn']);

        if ($exceptId !== null) {
            $linked = $this->db->table('users')
                ->select('id')
                ->where('id_siswa', $exceptId)
                ->get()
                ->getRowArray();

            if ($linked !== null) {
                $userBuilder->where('id !=', (int) $linked['id']);
            }
        }

        if ($userBuilder->countAllResults() > 0) {
            return [
                'success' => false,
                'message' => 'NISN tidak dapat digunakan karena username yang sama sudah dipakai akun lain.',
            ];
        }

        return null;
    }

    protected function applyViewScope($builder, int $userId, ?int $idTahun): bool
    {
        $scope = $this->authService->resolveScope('master_siswa.view', $userId);

        if ($scope === 'SEMUA') {
            return true;
        }

        if ($scope === 'KELAS_DIAMPU') {
            if ($idTahun === null) {
                return false;
            }

            $kelas = $this->authService->getKelasDiampu(
                $this->getIdGuruUser($userId),
                $idTahun
            );

            if ($kelas === []) {
                return false;
            }

            $builder->whereIn('ak.id_kelas', $kelas);
            return true;
        }

        if ($scope === 'DIRI_SENDIRI') {
            $idSiswa = $this->getIdSiswaUser($userId);

            if ($idSiswa <= 0) {
                return false;
            }

            $builder->where('s.id', $idSiswa);
            return true;
        }

        return false;
    }

    protected function canAccessStudentByScope(int $idSiswa, int $userId, string $scope): bool
    {
        if ($scope === 'SEMUA') {
            return true;
        }

        if ($scope !== 'KELAS_DIAMPU') {
            return false;
        }

        $idTahun = $this->getIdTahunAktif();

        if ($idTahun === null) {
            return false;
        }

        $kelas = $this->authService->getKelasDiampu(
            $this->getIdGuruUser($userId),
            $idTahun
        );

        if ($kelas === []) {
            return false;
        }

        return $this->db->table('anggota_kelas')
            ->where('id_siswa', $idSiswa)
            ->where('id_tahun', $idTahun)
            ->whereIn('id_kelas', $kelas)
            ->countAllResults() > 0;
    }

    protected function getIdTahunAktif(): ?int
    {
        $row = $this->db->table('tahun_ajaran')
            ->select('id')
            ->where('status_aktif', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return isset($row['id']) ? (int) $row['id'] : null;
    }

    protected function getIdGuruUser(int $userId): int
    {
        $row = $this->db->table('users')
            ->select('id_guru')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        return isset($row['id_guru']) ? (int) $row['id_guru'] : 0;
    }

    protected function getIdSiswaUser(int $userId): int
    {
        $row = $this->db->table('users')
            ->select('id_siswa')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        return isset($row['id_siswa']) ? (int) $row['id_siswa'] : 0;
    }

    protected function processFoto(UploadedFile $foto, string $nisn): string
    {
        $safeNisn = preg_replace('/[^0-9A-Za-z_-]/', '', $nisn) ?: 'siswa';

        return $this->uploadService->processFotoPortrait(
            $foto,
            ROOTPATH . 'uploads/foto_siswa',
            'siswa_' . $safeNisn
        );
    }

    protected function deleteFotoFile(string $filename): void
    {
        $path = ROOTPATH . 'uploads/foto_siswa/' . basename($filename);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    protected function normalizeExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(
                    (float) $value
                )->format('Y-m-d');
            } catch (Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    protected function logActivity(string $aksi, string $modul, string $keterangan): void
    {
        $idUser = session()->get('user_id');

        $this->db->table('log_activity')->insert([
            'id_user' => $idUser ? (int) $idUser : null,
            'aksi' => $aksi,
            'modul' => $modul,
            'keterangan' => $keterangan,
            'waktu' => date('Y-m-d H:i:s'),
        ]);
    }
}
