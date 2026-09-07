<?php

namespace App\Services;

use App\Models\GuruModel;
use App\Models\UserModel;
use App\Models\UserRolesModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * GuruService
 *
 * Business logic Master Guru.
 *
 * Acuan:
 * - docs/04_MASTER_DATA §3.1, §6, §8
 *
 * Tanggung jawab:
 * - CRUD Guru.
 * - Validasi NIP lintas guru/pegawai.
 * - Auto-create akun user Guru.
 * - Sinkron username ketika NIP berubah.
 * - Upload foto PNG 2 MB, crop 3:4, re-encode via UploadService.
 * - Import Excel atomic stop-on-error.
 * - Soft delete, recycle bin, restore, force delete.
 * - Activity log.
 */
class GuruService
{
    protected BaseConnection $db;
    protected GuruModel $guruModel;
    protected UserModel $userModel;
    protected UserRolesModel $userRolesModel;
    protected UploadService $uploadService;

    public function __construct()
    {
        $this->db             = Database::connect();
        $this->guruModel      = new GuruModel();
        $this->userModel      = new UserModel();
        $this->userRolesModel = new UserRolesModel();
        $this->uploadService  = new UploadService();
    }

    /**
     * Ambil daftar Guru dengan filter aktif.
     */
    public function getList(array $filter = [], bool $deletedOnly = false): array
    {
        $builder = $this->db->table('guru g')
            ->select(
                'g.id, g.nip, g.nama, g.jenis_kelamin, g.tempat_lahir, ' .
                'g.tanggal_lahir, g.alamat, g.no_telepon, g.email, ' .
                'g.status_kepegawaian, g.foto, g.deleted_at, g.created_at, g.updated_at'
            );

        if ($deletedOnly) {
            $builder->where('g.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('g.deleted_at', null);
        }

        $nama = trim((string) ($filter['nama'] ?? ''));
        if ($nama !== '') {
            $builder->like('g.nama', $nama);
        }

        $nip = trim((string) ($filter['nip'] ?? ''));
        if ($nip !== '') {
            $builder->like('g.nip', $nip);
        }

        $jk = trim((string) ($filter['jenis_kelamin'] ?? ''));
        if (in_array($jk, ['L', 'P'], true)) {
            $builder->where('g.jenis_kelamin', $jk);
        }

        $status = trim((string) ($filter['status_kepegawaian'] ?? ''));
        if (in_array($status, ['PNS', 'PPPK', 'NON ASN', 'Yayasan', 'Outsourcing'], true)) {
            $builder->where('g.status_kepegawaian', $status);
        }

        return $builder
            ->orderBy('g.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Detail Guru aktif.
     */
    public function find(int $id): ?array
    {
        return $this->guruModel->find($id);
    }

    /**
     * Detail Guru termasuk recycle-bin.
     */
    public function findWithDeleted(int $id): ?array
    {
        return $this->guruModel->withDeleted()->find($id);
    }

    /**
     * Create Guru + User + user_roles dalam satu transaction.
     *
     * @return array{success:bool,message:string,id?:int,errors?:array}
     */
    public function create(array $data, ?UploadedFile $foto = null): array
    {
        $payload = $this->normalizePayload($data);

        $precheck = $this->validateBusiness($payload);
        if ($precheck !== null) {
            return $precheck;
        }

        $newFoto = null;

        $this->db->transBegin();

        try {
            if ($foto !== null && $foto->getError() !== UPLOAD_ERR_NO_FILE) {
                $newFoto = $this->processFoto($foto, $payload['nip']);
                $payload['foto'] = $newFoto;
            }

            $idGuru = $this->guruModel->insert($payload, true);

            if ($idGuru === false) {
                throw new \RuntimeException(
                    implode(' ', $this->guruModel->errors()) ?: 'Data Guru gagal disimpan.'
                );
            }

            $idGuru = (int) $idGuru;

            $idUser = $this->userModel->insert([
                'username'     => $payload['nip'],
                'password'     => password_hash($payload['nip'], PASSWORD_DEFAULT),
                'role'         => 'guru',
                'id_guru'      => $idGuru,
                'id_pegawai'   => null,
                'id_siswa'     => null,
                'status_aktif' => 1,
                'auth_version' => 1,
            ], true);

            if ($idUser === false) {
                throw new \RuntimeException(
                    implode(' ', $this->userModel->errors()) ?: 'Akun Guru gagal dibuat.'
                );
            }

            if ($this->userRolesModel->insert([
                'id_user' => (int) $idUser,
                'role'    => 'guru',
            ]) === false) {
                throw new \RuntimeException('Role akun Guru gagal dibuat.');
            }

            $this->logActivity(
                'CREATE',
                'Master Guru',
                sprintf('Menambahkan Guru NIP %s - %s.', $payload['nip'], $payload['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Guru berhasil ditambahkan dan akun Guru otomatis dibuat.',
                'id'      => $idGuru,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            if ($newFoto !== null) {
                $this->deleteFotoFile($newFoto);
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update biodata Guru.
     *
     * Foto tidak diproses di PUT; foto memiliki endpoint POST terpisah.
     *
     * @return array{success:bool,message:string,errors?:array}
     */
    public function update(int $id, array $data): array
    {
        $guru = $this->guruModel->find($id);

        if ($guru === null) {
            return ['success' => false, 'message' => 'Data Guru tidak ditemukan.'];
        }

        $payload = $this->normalizePayload($data);

        $precheck = $this->validateBusiness($payload, $id);
        if ($precheck !== null) {
            return $precheck;
        }

        $this->db->transBegin();

        try {
            if (!$this->guruModel->update($id, $payload)) {
                throw new \RuntimeException(
                    implode(' ', $this->guruModel->errors()) ?: 'Data Guru gagal diperbarui.'
                );
            }

            if ($guru['nip'] !== $payload['nip']) {
                $linkedUser = $this->db
                    ->table('users')
                    ->where('id_guru', $id)
                    ->get()
                    ->getRowArray();

                if ($linkedUser !== null) {
                    $usernameDipakai = $this->db
                        ->table('users')
                        ->where('username', $payload['nip'])
                        ->where('id !=', (int) $linkedUser['id'])
                        ->countAllResults() > 0;

                    if ($usernameDipakai) {
                        throw new \RuntimeException(
                            'NIP baru tidak dapat digunakan karena username yang sama sudah dipakai akun lain.'
                        );
                    }

                    $this->db
                        ->table('users')
                        ->where('id', (int) $linkedUser['id'])
                        ->update([
                            'username'     => $payload['nip'],
                            'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                            'updated_at'   => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            $this->logActivity(
                'UPDATE',
                'Master Guru',
                sprintf('Memperbarui Guru ID %d - %s.', $id, $payload['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Guru berhasil diperbarui.',
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
     * Upload/ganti foto Guru.
     *
     * @return array{success:bool,message:string,foto?:string}
     */
    public function uploadFoto(int $id, UploadedFile $foto): array
    {
        $guru = $this->guruModel->find($id);

        if ($guru === null) {
            return ['success' => false, 'message' => 'Data Guru tidak ditemukan.'];
        }

        $newFoto = null;

        try {
            $newFoto = $this->processFoto($foto, $guru['nip']);

            if (!$this->guruModel->update($id, ['foto' => $newFoto])) {
                throw new \RuntimeException(
                    implode(' ', $this->guruModel->errors()) ?: 'Foto Guru gagal disimpan.'
                );
            }

            if (!empty($guru['foto'])) {
                $this->deleteFotoFile((string) $guru['foto']);
            }

            $this->logActivity(
                'UPDATE_FOTO',
                'Master Guru',
                sprintf('Mengganti foto Guru ID %d - %s.', $id, $guru['nama'])
            );

            return [
                'success' => true,
                'message' => 'Foto Guru berhasil diperbarui.',
                'foto'    => $newFoto,
            ];
        } catch (Throwable $e) {
            if ($newFoto !== null) {
                $this->deleteFotoFile($newFoto);
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Soft delete Guru dan nonaktifkan akun terkait.
     */
    public function delete(int $id): array
    {
        $guru = $this->guruModel->find($id);

        if ($guru === null) {
            return ['success' => false, 'message' => 'Data Guru tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            if (!$this->guruModel->delete($id)) {
                throw new \RuntimeException('Data Guru gagal dipindahkan ke Recycle Bin.');
            }

            $this->db
                ->table('users')
                ->where('id_guru', $id)
                ->update([
                    'status_aktif' => 0,
                    'auth_version' => $this->db->raw('auth_version + 1'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            $this->logActivity(
                'DELETE',
                'Master Guru',
                sprintf('Memindahkan Guru ID %d - %s ke Recycle Bin.', $id, $guru['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Guru dipindahkan ke Recycle Bin.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restore Guru dari Recycle Bin dan aktifkan kembali akun terkait.
     */
    public function restore(int $id): array
    {
        $guru = $this->findWithDeleted($id);

        if ($guru === null || empty($guru['deleted_at'])) {
            return ['success' => false, 'message' => 'Data Guru pada Recycle Bin tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            $this->db
                ->table('guru')
                ->where('id', $id)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $linkedUser = $this->db
                ->table('users')
                ->where('id_guru', $id)
                ->get()
                ->getRowArray();

            if ($linkedUser !== null) {
                $this->db
                    ->table('users')
                    ->where('id', (int) $linkedUser['id'])
                    ->update([
                        'status_aktif' => 1,
                        'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
            } else {
                $usernameDipakai = $this->db
                    ->table('users')
                    ->where('username', $guru['nip'])
                    ->countAllResults() > 0;

                if ($usernameDipakai) {
                    throw new \RuntimeException(
                        'Restore gagal: username NIP Guru sudah digunakan akun lain.'
                    );
                }

                $idUser = $this->userModel->insert([
                    'username'     => $guru['nip'],
                    'password'     => password_hash($guru['nip'], PASSWORD_DEFAULT),
                    'role'         => 'guru',
                    'id_guru'      => $id,
                    'id_pegawai'   => null,
                    'id_siswa'     => null,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException('Akun Guru gagal dibuat kembali saat restore.');
                }

                if ($this->userRolesModel->insert([
                    'id_user' => (int) $idUser,
                    'role'    => 'guru',
                ]) === false) {
                    throw new \RuntimeException('Role akun Guru gagal dibuat kembali.');
                }
            }

            $this->logActivity(
                'RESTORE',
                'Master Guru',
                sprintf('Memulihkan Guru ID %d - %s dari Recycle Bin.', $id, $guru['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Data Guru berhasil dipulihkan.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Hapus permanen Guru.
     *
     * Foreign key database menjadi perlindungan terakhir. Jika Guru sudah
     * direferensikan jadwal/presensi/BK/dll., transaksi akan gagal dan data
     * tetap utuh.
     */
    public function forceDelete(int $id): array
    {
        $guru = $this->findWithDeleted($id);

        if ($guru === null || empty($guru['deleted_at'])) {
            return ['success' => false, 'message' => 'Data Guru pada Recycle Bin tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            $this->db
                ->table('users')
                ->where('id_guru', $id)
                ->delete();

            $this->db
                ->table('guru')
                ->where('id', $id)
                ->delete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Guru tidak dapat dihapus permanen karena masih digunakan oleh data lain.'
                );
            }

            $this->logActivity(
                'FORCE_DELETE',
                'Master Guru',
                sprintf('Menghapus permanen Guru ID %d - %s.', $id, $guru['nama'])
            );

            $this->db->transCommit();

            if (!empty($guru['foto'])) {
                $this->deleteFotoFile((string) $guru['foto']);
            }

            return ['success' => true, 'message' => 'Data Guru berhasil dihapus permanen.'];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Hapus permanen gagal. Data Guru kemungkinan masih dipakai oleh jadwal, presensi, BK, wali kelas, atau data terkait lainnya.',
            ];
        }
    }

    /**
     * Import Excel Guru — atomic + stop-on-error.
     *
     * Header wajib:
     * NIP | NAMA LENGKAP & GELAR | JENIS KELAMIN (L/P)
     */
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
            return ['success' => false, 'message' => 'File import tidak memiliki data Guru.'];
        }

        $header = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
            $rows[0]
        );

        $expected = [
            'NIP',
            'NAMA LENGKAP & GELAR',
            'JENIS KELAMIN (L/P)',
        ];

        if (array_slice($header, 0, 3) !== $expected) {
            return [
                'success' => false,
                'message' => 'Header template tidak sesuai. Gunakan template resmi Master Guru.',
            ];
        }

        $prepared = [];
        $seenNip = [];

        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $excelRow = $i + 1;
            $nip      = trim((string) ($rows[$i][0] ?? ''));
            $nama     = trim((string) ($rows[$i][1] ?? ''));
            $jk       = strtoupper(trim((string) ($rows[$i][2] ?? '')));

            if ($nip === '' && $nama === '' && $jk === '') {
                continue;
            }

            if ($nip === '' || $nama === '' || !in_array($jk, ['L', 'P'], true)) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIP, nama, dan jenis kelamin L/P wajib valid.",
                ];
            }

            if (isset($seenNip[$nip])) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIP {$nip} duplikat di dalam file.",
                ];
            }
            $seenNip[$nip] = true;

            if ($this->nipExistsAnywhere($nip)) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: NIP {$nip} sudah dipakai pada Guru/Pegawai.",
                ];
            }

            if ($this->db->table('users')->where('username', $nip)->countAllResults() > 0) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: username {$nip} sudah digunakan.",
                ];
            }

            $prepared[] = [
                'nip'                  => $nip,
                'nama'                 => $nama,
                'jenis_kelamin'        => $jk,
                'tempat_lahir'         => null,
                'tanggal_lahir'        => null,
                'alamat'               => null,
                'no_telepon'           => null,
                'email'                => null,
                'status_kepegawaian'   => null,
                'foto'                 => null,
            ];
        }

        if ($prepared === []) {
            return ['success' => false, 'message' => 'Tidak ada baris data Guru yang dapat diimport.'];
        }

        $this->db->transBegin();

        try {
            foreach ($prepared as $row) {
                $idGuru = $this->guruModel->insert($row, true);

                if ($idGuru === false) {
                    throw new \RuntimeException(
                        implode(' ', $this->guruModel->errors()) ?: 'Insert Guru gagal.'
                    );
                }

                $idUser = $this->userModel->insert([
                    'username'     => $row['nip'],
                    'password'     => password_hash($row['nip'], PASSWORD_DEFAULT),
                    'role'         => 'guru',
                    'id_guru'      => (int) $idGuru,
                    'id_pegawai'   => null,
                    'id_siswa'     => null,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException('Pembuatan akun Guru gagal.');
                }

                if ($this->userRolesModel->insert([
                    'id_user' => (int) $idUser,
                    'role'    => 'guru',
                ]) === false) {
                    throw new \RuntimeException('Pembuatan role Guru gagal.');
                }
            }

            $this->logActivity(
                'IMPORT',
                'Master Guru',
                sprintf('Import %d Guru dari Excel.', count($prepared))
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf('%d data Guru berhasil diimport.', count($prepared)),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Import dibatalkan seluruhnya: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalisasi payload form Guru.
     */
    protected function normalizePayload(array $data): array
    {
        $nullable = static function ($value): ?string {
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        };

        return [
            'nip'                  => trim((string) ($data['nip'] ?? '')),
            'nama'                 => trim((string) ($data['nama'] ?? '')),
            'jenis_kelamin'        => strtoupper(trim((string) ($data['jenis_kelamin'] ?? ''))),
            'tempat_lahir'         => $nullable($data['tempat_lahir'] ?? null),
            'tanggal_lahir'        => $nullable($data['tanggal_lahir'] ?? null),
            'alamat'               => $nullable($data['alamat'] ?? null),
            'no_telepon'           => $nullable($data['no_telepon'] ?? null),
            'email'                => $nullable($data['email'] ?? null),
            'status_kepegawaian'   => $nullable($data['status_kepegawaian'] ?? null),
        ];
    }

    /**
     * Validasi lintas tabel dan username.
     */
    protected function validateBusiness(array $payload, ?int $exceptGuruId = null): ?array
    {
        if ($payload['nip'] === '') {
            return ['success' => false, 'message' => 'NIP wajib diisi.'];
        }

        $guruBuilder = $this->db
            ->table('guru')
            ->where('nip', $payload['nip']);

        if ($exceptGuruId !== null) {
            $guruBuilder->where('id !=', $exceptGuruId);
        }

        if ($guruBuilder->countAllResults() > 0) {
            return ['success' => false, 'message' => 'NIP sudah digunakan pada data Guru.'];
        }

        if ($this->db->table('pegawai')->where('nip', $payload['nip'])->countAllResults() > 0) {
            return ['success' => false, 'message' => 'NIP sudah digunakan pada data Pegawai.'];
        }

        $userBuilder = $this->db
            ->table('users')
            ->where('username', $payload['nip']);

        if ($exceptGuruId !== null) {
            $linked = $this->db
                ->table('users')
                ->select('id')
                ->where('id_guru', $exceptGuruId)
                ->get()
                ->getRowArray();

            if ($linked !== null) {
                $userBuilder->where('id !=', (int) $linked['id']);
            }
        }

        if ($userBuilder->countAllResults() > 0) {
            return [
                'success' => false,
                'message' => 'NIP tidak dapat digunakan karena username yang sama sudah dipakai akun lain.',
            ];
        }

        return null;
    }

    protected function nipExistsAnywhere(string $nip): bool
    {
        return $this->db->table('guru')->where('nip', $nip)->countAllResults() > 0
            || $this->db->table('pegawai')->where('nip', $nip)->countAllResults() > 0;
    }

    protected function processFoto(UploadedFile $foto, string $nip): string
    {
        $safeNip = preg_replace('/[^0-9A-Za-z_-]/', '', $nip) ?: 'guru';

        return $this->uploadService->processFotoPortrait(
            $foto,
            ROOTPATH . 'uploads/foto_guru',
            'guru_' . $safeNip
        );
    }

    protected function deleteFotoFile(string $filename): void
    {
        $filename = basename($filename);
        $path = ROOTPATH . 'uploads/foto_guru/' . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    protected function logActivity(string $aksi, string $modul, string $keterangan): void
    {
        $idUser = session()->get('user_id');

        $this->db->table('log_activity')->insert([
            'id_user'    => $idUser ? (int) $idUser : null,
            'aksi'       => $aksi,
            'modul'      => $modul,
            'keterangan' => $keterangan,
            'waktu'      => date('Y-m-d H:i:s'),
        ]);
    }
}
