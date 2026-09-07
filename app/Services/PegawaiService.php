<?php

namespace App\Services;

use App\Models\PegawaiModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * PegawaiService
 *
 * Business logic Master Pegawai.
 *
 * Acuan:
 * - docs/04_MASTER_DATA §3.1, §6, §8
 *
 * Aturan utama:
 * - NIP tidak boleh sama dengan Guru.
 * - Saat Pegawai dibuat, akun users otomatis dibuat:
 *   username = NIP, password = hash(NIP), role = NULL.
 * - Admin menentukan role Pegawai kemudian melalui Manajemen User.
 * - Import bersifat atomic + stop-on-error.
 * - Pegawai menggunakan soft delete + recycle bin.
 */
class PegawaiService
{
    protected BaseConnection $db;
    protected PegawaiModel $pegawaiModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->db           = Database::connect();
        $this->pegawaiModel = new PegawaiModel();
        $this->userModel    = new UserModel();
    }

    public function getList(array $filter = [], bool $deletedOnly = false): array
    {
        $builder = $this->db
            ->table('pegawai p')
            ->select(
                'p.id, p.nip, p.nama, p.jenis_kelamin, p.tempat_lahir, ' .
                'p.tanggal_lahir, p.alamat, p.no_telepon, p.email, p.jabatan, ' .
                'p.deleted_at, p.created_at, p.updated_at, ' .
                'u.id AS id_user, u.role AS role_user, u.status_aktif AS status_user'
            )
            ->join('users u', 'u.id_pegawai = p.id', 'left');

        if ($deletedOnly) {
            $builder->where('p.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('p.deleted_at', null);
        }

        $nama = trim((string) ($filter['nama'] ?? ''));
        if ($nama !== '') {
            $builder->like('p.nama', $nama);
        }

        $nip = trim((string) ($filter['nip'] ?? ''));
        if ($nip !== '') {
            $builder->like('p.nip', $nip);
        }

        $jk = trim((string) ($filter['jenis_kelamin'] ?? ''));
        if (in_array($jk, ['L', 'P'], true)) {
            $builder->where('p.jenis_kelamin', $jk);
        }

        $jabatan = trim((string) ($filter['jabatan'] ?? ''));
        if ($jabatan !== '') {
            $builder->like('p.jabatan', $jabatan);
        }

        return $builder
            ->orderBy('p.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function find(int $id): ?array
    {
        return $this->pegawaiModel->find($id);
    }

    public function findWithDeleted(int $id): ?array
    {
        return $this->pegawaiModel
            ->withDeleted()
            ->find($id);
    }

    /**
     * @return array{success:bool,message:string,id?:int}
     */
    public function create(array $data): array
    {
        $payload = $this->normalizePayload($data);

        $precheck = $this->validateBusiness($payload);
        if ($precheck !== null) {
            return $precheck;
        }

        $this->db->transBegin();

        try {
            $idPegawai = $this->pegawaiModel->insert($payload, true);

            if ($idPegawai === false) {
                throw new \RuntimeException(
                    implode(' ', $this->pegawaiModel->errors())
                    ?: 'Data Pegawai gagal disimpan.'
                );
            }

            $idPegawai = (int) $idPegawai;

            $idUser = $this->userModel->insert([
                'username'     => $payload['nip'],
                'password'     => password_hash($payload['nip'], PASSWORD_DEFAULT),
                'role'         => null,
                'id_guru'      => null,
                'id_pegawai'   => $idPegawai,
                'id_siswa'     => null,
                'status_aktif' => 1,
                'auth_version' => 1,
            ], true);

            if ($idUser === false) {
                throw new \RuntimeException(
                    implode(' ', $this->userModel->errors())
                    ?: 'Akun Pegawai gagal dibuat.'
                );
            }

            /*
             * Tidak membuat user_roles di sini.
             * Dokumen menetapkan Admin harus menentukan role Pegawai
             * secara manual setelah akun dibuat.
             */

            $this->logActivity(
                'CREATE',
                'Master Pegawai',
                sprintf(
                    'Menambahkan Pegawai NIP %s - %s. Akun dibuat tanpa role.',
                    $payload['nip'],
                    $payload['nama']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Pegawai berhasil ditambahkan. Akun otomatis dibuat tanpa role; Admin dapat menentukan role melalui Manajemen User.',
                'id'      => $idPegawai,
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
     * @return array{success:bool,message:string}
     */
    public function update(int $id, array $data): array
    {
        $pegawai = $this->pegawaiModel->find($id);

        if ($pegawai === null) {
            return ['success' => false, 'message' => 'Data Pegawai tidak ditemukan.'];
        }

        $payload = $this->normalizePayload($data);

        $precheck = $this->validateBusiness($payload, $id);
        if ($precheck !== null) {
            return $precheck;
        }

        $this->db->transBegin();

        try {
            if (!$this->pegawaiModel->update($id, $payload)) {
                throw new \RuntimeException(
                    implode(' ', $this->pegawaiModel->errors())
                    ?: 'Data Pegawai gagal diperbarui.'
                );
            }

            if ($pegawai['nip'] !== $payload['nip']) {
                $linkedUser = $this->db
                    ->table('users')
                    ->where('id_pegawai', $id)
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
                'Master Pegawai',
                sprintf('Memperbarui Pegawai ID %d - %s.', $id, $payload['nama'])
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Pegawai berhasil diperbarui.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function delete(int $id): array
    {
        $pegawai = $this->pegawaiModel->find($id);

        if ($pegawai === null) {
            return ['success' => false, 'message' => 'Data Pegawai tidak ditemukan.'];
        }

        $this->db->transBegin();

        try {
            if (!$this->pegawaiModel->delete($id)) {
                throw new \RuntimeException(
                    'Data Pegawai gagal dipindahkan ke Recycle Bin.'
                );
            }

            $linkedUser = $this->db
                ->table('users')
                ->where('id_pegawai', $id)
                ->get()
                ->getRowArray();

            if ($linkedUser !== null) {
                $this->db
                    ->table('users')
                    ->where('id', (int) $linkedUser['id'])
                    ->update([
                        'status_aktif' => 0,
                        'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
            }

            $this->logActivity(
                'DELETE',
                'Master Pegawai',
                sprintf(
                    'Memindahkan Pegawai ID %d - %s ke Recycle Bin.',
                    $id,
                    $pegawai['nama']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Pegawai dipindahkan ke Recycle Bin.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restore(int $id): array
    {
        $pegawai = $this->findWithDeleted($id);

        if ($pegawai === null || empty($pegawai['deleted_at'])) {
            return [
                'success' => false,
                'message' => 'Data Pegawai pada Recycle Bin tidak ditemukan.',
            ];
        }

        $this->db->transBegin();

        try {
            $this->db
                ->table('pegawai')
                ->where('id', $id)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $linkedUser = $this->db
                ->table('users')
                ->where('id_pegawai', $id)
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
                if (
                    $this->db
                        ->table('users')
                        ->where('username', $pegawai['nip'])
                        ->countAllResults() > 0
                ) {
                    throw new \RuntimeException(
                        'Restore gagal: username NIP Pegawai sudah digunakan akun lain.'
                    );
                }

                $idUser = $this->userModel->insert([
                    'username'     => $pegawai['nip'],
                    'password'     => password_hash($pegawai['nip'], PASSWORD_DEFAULT),
                    'role'         => null,
                    'id_guru'      => null,
                    'id_pegawai'   => $id,
                    'id_siswa'     => null,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException(
                        'Akun Pegawai gagal dibuat kembali saat restore.'
                    );
                }
            }

            $this->logActivity(
                'RESTORE',
                'Master Pegawai',
                sprintf(
                    'Memulihkan Pegawai ID %d - %s dari Recycle Bin.',
                    $id,
                    $pegawai['nama']
                )
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Pegawai berhasil dipulihkan.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function forceDelete(int $id): array
    {
        $pegawai = $this->findWithDeleted($id);

        if ($pegawai === null || empty($pegawai['deleted_at'])) {
            return [
                'success' => false,
                'message' => 'Data Pegawai pada Recycle Bin tidak ditemukan.',
            ];
        }

        $this->db->transBegin();

        try {
            $this->db
                ->table('users')
                ->where('id_pegawai', $id)
                ->delete();

            $this->db
                ->table('pegawai')
                ->where('id', $id)
                ->delete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Pegawai tidak dapat dihapus permanen karena masih digunakan data lain.'
                );
            }

            $this->logActivity(
                'FORCE_DELETE',
                'Master Pegawai',
                sprintf(
                    'Menghapus permanen Pegawai ID %d - %s.',
                    $id,
                    $pegawai['nama']
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Pegawai berhasil dihapus permanen.',
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            return [
                'success' => false,
                'message' => 'Hapus permanen gagal. Data Pegawai kemungkinan masih direferensikan akun atau data terkait lainnya.',
            ];
        }
    }

    /**
     * Template:
     * NIP | NAMA LENGKAP | JENIS KELAMIN (L/P) | JABATAN
     */
    public function importExcel(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            return ['success' => false, 'message' => 'File import tidak valid.'];
        }

        $extension = strtolower((string) $file->getClientExtension());
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return [
                'success' => false,
                'message' => 'File import harus berformat XLSX atau XLS.',
            ];
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $rows = $spreadsheet
                ->getActiveSheet()
                ->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'File Excel tidak dapat dibaca.',
            ];
        }

        if (count($rows) < 2) {
            return [
                'success' => false,
                'message' => 'File import tidak memiliki data Pegawai.',
            ];
        }

        $header = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
            $rows[0]
        );

        $expected = [
            'NIP',
            'NAMA LENGKAP',
            'JENIS KELAMIN (L/P)',
            'JABATAN',
        ];

        if (array_slice($header, 0, 4) !== $expected) {
            return [
                'success' => false,
                'message' => 'Header template tidak sesuai. Gunakan template resmi Master Pegawai.',
            ];
        }

        $prepared = [];
        $seenNip = [];

        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $excelRow = $i + 1;

            $nip     = trim((string) ($rows[$i][0] ?? ''));
            $nama    = trim((string) ($rows[$i][1] ?? ''));
            $jk      = strtoupper(trim((string) ($rows[$i][2] ?? '')));
            $jabatan = trim((string) ($rows[$i][3] ?? ''));

            if ($nip === '' && $nama === '' && $jk === '' && $jabatan === '') {
                continue;
            }

            if (
                $nip === ''
                || $nama === ''
                || !in_array($jk, ['L', 'P'], true)
            ) {
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

            if (
                $this->db
                    ->table('users')
                    ->where('username', $nip)
                    ->countAllResults() > 0
            ) {
                return [
                    'success' => false,
                    'message' => "Import dihentikan pada baris {$excelRow}: username {$nip} sudah digunakan.",
                ];
            }

            $prepared[] = [
                'nip'           => $nip,
                'nama'          => $nama,
                'jenis_kelamin' => $jk,
                'tempat_lahir'  => null,
                'tanggal_lahir' => null,
                'alamat'        => null,
                'no_telepon'    => null,
                'email'         => null,
                'jabatan'       => $jabatan !== '' ? $jabatan : null,
            ];
        }

        if ($prepared === []) {
            return [
                'success' => false,
                'message' => 'Tidak ada baris data Pegawai yang dapat diimport.',
            ];
        }

        $this->db->transBegin();

        try {
            foreach ($prepared as $row) {
                $idPegawai = $this->pegawaiModel->insert($row, true);

                if ($idPegawai === false) {
                    throw new \RuntimeException(
                        implode(' ', $this->pegawaiModel->errors())
                        ?: 'Insert Pegawai gagal.'
                    );
                }

                $idUser = $this->userModel->insert([
                    'username'     => $row['nip'],
                    'password'     => password_hash($row['nip'], PASSWORD_DEFAULT),
                    'role'         => null,
                    'id_guru'      => null,
                    'id_pegawai'   => (int) $idPegawai,
                    'id_siswa'     => null,
                    'status_aktif' => 1,
                    'auth_version' => 1,
                ], true);

                if ($idUser === false) {
                    throw new \RuntimeException(
                        'Pembuatan akun Pegawai gagal.'
                    );
                }
            }

            $this->logActivity(
                'IMPORT',
                'Master Pegawai',
                sprintf('Import %d Pegawai dari Excel.', count($prepared))
            );

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf(
                    '%d data Pegawai berhasil diimport. Akun dibuat tanpa role.',
                    count($prepared)
                ),
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
            'nip'           => trim((string) ($data['nip'] ?? '')),
            'nama'          => trim((string) ($data['nama'] ?? '')),
            'jenis_kelamin' => strtoupper(
                trim((string) ($data['jenis_kelamin'] ?? ''))
            ),
            'tempat_lahir'  => $nullable($data['tempat_lahir'] ?? null),
            'tanggal_lahir' => $nullable($data['tanggal_lahir'] ?? null),
            'alamat'        => $nullable($data['alamat'] ?? null),
            'no_telepon'    => $nullable($data['no_telepon'] ?? null),
            'email'         => $nullable($data['email'] ?? null),
            'jabatan'       => $nullable($data['jabatan'] ?? null),
        ];
    }

    protected function validateBusiness(
        array $payload,
        ?int $exceptPegawaiId = null
    ): ?array {
        if ($payload['nip'] === '') {
            return ['success' => false, 'message' => 'NIP wajib diisi.'];
        }

        $pegawaiBuilder = $this->db
            ->table('pegawai')
            ->where('nip', $payload['nip']);

        if ($exceptPegawaiId !== null) {
            $pegawaiBuilder->where('id !=', $exceptPegawaiId);
        }

        if ($pegawaiBuilder->countAllResults() > 0) {
            return [
                'success' => false,
                'message' => 'NIP sudah digunakan pada data Pegawai.',
            ];
        }

        if (
            $this->db
                ->table('guru')
                ->where('nip', $payload['nip'])
                ->countAllResults() > 0
        ) {
            return [
                'success' => false,
                'message' => 'NIP sudah digunakan pada data Guru.',
            ];
        }

        $userBuilder = $this->db
            ->table('users')
            ->where('username', $payload['nip']);

        if ($exceptPegawaiId !== null) {
            $linked = $this->db
                ->table('users')
                ->select('id')
                ->where('id_pegawai', $exceptPegawaiId)
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
        return $this->db
                ->table('guru')
                ->where('nip', $nip)
                ->countAllResults() > 0
            || $this->db
                ->table('pegawai')
                ->where('nip', $nip)
                ->countAllResults() > 0;
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
                'id_user'    => $idUser ? (int) $idUser : null,
                'aksi'       => $aksi,
                'modul'      => $modul,
                'keterangan' => $keterangan,
                'waktu'      => date('Y-m-d H:i:s'),
            ]);
    }
}
