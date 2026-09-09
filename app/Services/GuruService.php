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
 * Business logic Master Guru - Phase 2 Personalia.
 *
 * Aturan login:
 * - bila NIP tersedia, username/password identifier = NIP;
 * - bila NIP kosong, username/password identifier = NIK;
 * - perubahan identifier login mereset password dan menaikkan auth_version.
 */
class GuruService
{
    public const STATUS_KEPEGAWAIAN = [
        'PNS',
        'PPPK',
        'GTT',
        'PTT',
        'GTY',
        'PTY',
        'Honorer',
        'Outsourcing',
    ];

    protected BaseConnection $db;
    protected GuruModel $guruModel;
    protected UserModel $userModel;
    protected UserRolesModel $userRolesModel;
    protected UploadService $uploadService;
    protected ActivityLogService $activityLog;

    public function __construct()
    {
        $this->db             = Database::connect();
        $this->guruModel      = new GuruModel();
        $this->userModel      = new UserModel();
        $this->userRolesModel = new UserRolesModel();
        $this->uploadService  = new UploadService();
        $this->activityLog    = new ActivityLogService();
    }

    public function getList(array $filter = [], bool $deletedOnly = false): array
    {
        $builder = $this->db
            ->table('guru g')
            ->select(
                'g.id, g.nik, g.nip, g.nama, g.jenis_kelamin, g.tempat_lahir, ' .
                'g.tanggal_lahir, g.agama, g.alamat, g.no_telepon, g.email, ' .
                'g.status_kepegawaian, g.nuptk, g.foto, g.deleted_at, ' .
                'g.created_at, g.updated_at, u.id AS id_user, u.username, ' .
                'u.role AS role_user, u.status_aktif AS status_user'
            )
            ->join('users u', 'u.id_guru = g.id', 'left');

        if ($deletedOnly) {
            $builder->where('g.deleted_at IS NOT NULL', null, false);
        } else {
            $builder->where('g.deleted_at', null);
        }

        $nama = trim((string) ($filter['nama'] ?? ''));
        if ($nama !== '') {
            $builder->like('g.nama', $nama);
        }

        $nik = trim((string) ($filter['nik'] ?? ''));
        if ($nik !== '') {
            $builder->like('g.nik', $nik);
        }

        $nip = trim((string) ($filter['nip'] ?? ''));
        if ($nip !== '') {
            $builder->like('g.nip', $nip);
        }

        $jk = strtoupper(trim((string) ($filter['jenis_kelamin'] ?? '')));
        if (in_array($jk, ['L', 'P'], true)) {
            $builder->where('g.jenis_kelamin', $jk);
        }

        $status = $this->canonicalStatus($filter['status_kepegawaian'] ?? null);
        if ($status !== null) {
            $builder->where('g.status_kepegawaian', $status);
        }

        $rows = $builder
            ->orderBy('g.nama', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['login_identifier'] = $this->loginIdentifier($row);
            $row['identity_complete'] = $this->validNik((string) ($row['nik'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    public function find(int $id): ?array
    {
        return $this->guruModel->find($id);
    }

    public function findWithDeleted(int $id): ?array
    {
        return $this->guruModel->withDeleted()->find($id);
    }

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
                $newFoto = $this->processFoto($foto, $this->loginIdentifier($payload));
                $payload['foto'] = $newFoto;
            }

            $idGuru = $this->insertGuruAndUser($payload);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'CREATE',
                'Master Guru',
                sprintf('Menambahkan Guru ID %d - %s.', $idGuru, $payload['nama'])
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => 'Data Guru berhasil ditambahkan dan akun Guru otomatis dibuat.',
                'id' => $idGuru,
                'login_identifier' => $this->loginIdentifier($payload),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();

            if ($newFoto !== null) {
                $this->deleteFotoFile($newFoto);
            }

            return $this->fail($e->getMessage());
        }
    }

    public function update(int $id, array $data): array
    {
        $guru = $this->guruModel->find($id);

        if ($guru === null) {
            return $this->fail('Data Guru tidak ditemukan.');
        }

        $linkedUser = $this->db
            ->table('users')
            ->where('id_guru', $id)
            ->get()
            ->getRowArray();

        $payload = $this->normalizePayload($data);
        $precheck = $this->validateBusiness(
            $payload,
            $id,
            $linkedUser !== null ? (int) $linkedUser['id'] : null
        );

        if ($precheck !== null) {
            return $precheck;
        }

        $oldLogin = $this->loginIdentifier($guru);
        $newLogin = $this->loginIdentifier($payload);
        $credentialReset = false;

        $this->db->transBegin();

        try {
            if (! $this->guruModel->update($id, $payload)) {
                throw new \RuntimeException(
                    implode(' ', $this->guruModel->errors()) ?: 'Data Guru gagal diperbarui.'
                );
            }

            if ($linkedUser === null) {
                $this->createGuruUser($id, $newLogin);
                $credentialReset = true;
            } else {
                $credentialReset = $oldLogin !== $newLogin
                    || (string) $linkedUser['username'] !== $newLogin;

                $userUpdate = [
                    'username' => $newLogin,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($credentialReset) {
                    $userUpdate['password'] = password_hash($newLogin, PASSWORD_DEFAULT);
                    $userUpdate['auth_version'] = ((int) $linkedUser['auth_version']) + 1;
                }

                if (! $this->db->table('users')->where('id', (int) $linkedUser['id'])->update($userUpdate)) {
                    throw new \RuntimeException('Akun Guru gagal disinkronkan.');
                }

                $this->ensureGuruRole((int) $linkedUser['id']);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'UPDATE',
                'Master Guru',
                sprintf(
                    'Memperbarui Guru ID %d - %s%s',
                    $id,
                    $payload['nama'],
                    $credentialReset ? ' dan mereset kredensial login.' : '.'
                )
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => $credentialReset
                    ? 'Data Guru berhasil diperbarui. Username dan password disinkronkan ke identitas login baru.'
                    : 'Data Guru berhasil diperbarui.',
                'credential_reset' => $credentialReset,
                'login_identifier' => $newLogin,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail($e->getMessage());
        }
    }

    public function uploadFoto(int $id, UploadedFile $foto): array
    {
        $guru = $this->guruModel->find($id);

        if ($guru === null) {
            return $this->fail('Data Guru tidak ditemukan.');
        }

        $newFoto = null;

        try {
            $newFoto = $this->processFoto($foto, $this->loginIdentifier($guru) ?: 'guru_' . $id);

            if (! $this->guruModel->update($id, ['foto' => $newFoto])) {
                throw new \RuntimeException(
                    implode(' ', $this->guruModel->errors()) ?: 'Foto Guru gagal disimpan.'
                );
            }

            if (! empty($guru['foto'])) {
                $this->deleteFotoFile((string) $guru['foto']);
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'UPDATE_FOTO',
                'Master Guru',
                sprintf('Mengganti foto Guru ID %d - %s.', $id, $guru['nama'])
            );

            return [
                'success' => true,
                'message' => 'Foto Guru berhasil diperbarui.',
                'foto' => $newFoto,
            ];
        } catch (Throwable $e) {
            if ($newFoto !== null) {
                $this->deleteFotoFile($newFoto);
            }

            return $this->fail($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $guru = $this->guruModel->find($id);

        if ($guru === null) {
            return $this->fail('Data Guru tidak ditemukan.');
        }

        $this->db->transBegin();

        try {
            if (! $this->guruModel->delete($id)) {
                throw new \RuntimeException('Data Guru gagal dipindahkan ke Recycle Bin.');
            }

            $linkedUser = $this->db->table('users')->where('id_guru', $id)->get()->getRowArray();

            if ($linkedUser !== null) {
                $this->db
                    ->table('users')
                    ->where('id', (int) $linkedUser['id'])
                    ->update([
                        'status_aktif' => 0,
                        'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'DELETE',
                'Master Guru',
                sprintf('Memindahkan Guru ID %d - %s ke Recycle Bin.', $id, $guru['nama'])
            );

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Data Guru dipindahkan ke Recycle Bin.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail($e->getMessage());
        }
    }

    public function restore(int $id): array
    {
        $guru = $this->findWithDeleted($id);

        if ($guru === null || empty($guru['deleted_at'])) {
            return $this->fail('Data Guru pada Recycle Bin tidak ditemukan.');
        }

        $login = $this->loginIdentifier($guru);
        if ($login === '') {
            return $this->fail('Restore gagal: identitas login Guru belum tersedia.');
        }

        $linkedUser = $this->db->table('users')->where('id_guru', $id)->get()->getRowArray();
        $available = $this->usernameAvailable(
            $login,
            $linkedUser !== null ? (int) $linkedUser['id'] : null
        );

        if (! $available) {
            return $this->fail('Restore gagal: identitas login sudah digunakan akun lain.');
        }

        $this->db->transBegin();

        try {
            if (! $this->db->table('guru')->where('id', $id)->update([
                'deleted_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ])) {
                throw new \RuntimeException('Data Guru gagal dipulihkan.');
            }

            if ($linkedUser === null) {
                $this->createGuruUser($id, $login);
            } else {
                $userUpdate = [
                    'username' => $login,
                    'status_aktif' => 1,
                    'auth_version' => ((int) $linkedUser['auth_version']) + 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ((string) $linkedUser['username'] !== $login) {
                    $userUpdate['password'] = password_hash($login, PASSWORD_DEFAULT);
                }

                $this->db->table('users')->where('id', (int) $linkedUser['id'])->update($userUpdate);
                $this->ensureGuruRole((int) $linkedUser['id']);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi database gagal.');
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'RESTORE',
                'Master Guru',
                sprintf('Memulihkan Guru ID %d - %s.', $id, $guru['nama'])
            );

            $this->db->transCommit();

            return ['success' => true, 'message' => 'Data Guru berhasil dipulihkan.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail($e->getMessage());
        }
    }

    public function forceDelete(int $id): array
    {
        $guru = $this->findWithDeleted($id);

        if ($guru === null || empty($guru['deleted_at'])) {
            return $this->fail('Data Guru pada Recycle Bin tidak ditemukan.');
        }

        $personaliaFiles = $this->collectPersonaliaFiles('id_guru', $id);

        $this->db->transBegin();

        try {
            $this->db->table('users')->where('id_guru', $id)->delete();
            $this->db->table('guru')->where('id', $id)->delete();

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(
                    'Guru tidak dapat dihapus permanen karena masih direferensikan data lain.'
                );
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'FORCE_DELETE',
                'Master Guru',
                sprintf('Menghapus permanen Guru ID %d - %s.', $id, $guru['nama'])
            );

            $this->db->transCommit();

            if (! empty($guru['foto'])) {
                $this->deleteFotoFile((string) $guru['foto']);
            }
            $this->deletePersonaliaFiles($personaliaFiles);

            return ['success' => true, 'message' => 'Data Guru berhasil dihapus permanen.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail(
                'Hapus permanen gagal. Data Guru masih digunakan oleh jadwal, wali kelas, presensi, BK, atau data terkait lainnya.'
            );
        }
    }

    public function importExcel(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return $this->fail('File import tidak valid.');
        }

        $extension = strtolower((string) $file->getClientExtension());
        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->fail('File import harus berformat XLSX atau XLS.');
        }

        try {
            $sheet = IOFactory::load($file->getTempName())->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (Throwable $e) {
            return $this->fail('File Excel tidak dapat dibaca: ' . $e->getMessage());
        }

        if ($rows === []) {
            return $this->fail('File import kosong.');
        }

        $header = array_map(
            static fn ($value): string => strtoupper(trim((string) $value)),
            $rows[array_key_first($rows)]
        );

        $columns = $this->resolveImportColumns($header);
        if ($columns === null) {
            return $this->fail(
                'Header import harus memuat: NIK, NIP, NAMA LENGKAP & GELAR, JENIS KELAMIN (L/P), STATUS KEPEGAWAIAN.'
            );
        }

        $prepared = [];
        $seenNik = [];
        $seenNip = [];
        $lineNo = 1;

        foreach (array_slice($rows, 1, null, true) as $row) {
            $lineNo++;

            $rawValues = array_map(
                static fn ($value): string => trim((string) $value),
                $row
            );

            if (implode('', $rawValues) === '') {
                continue;
            }

            $payload = $this->normalizePayload([
                'nik' => $row[$columns['nik']] ?? '',
                'nip' => $row[$columns['nip']] ?? '',
                'nama' => $row[$columns['nama']] ?? '',
                'jenis_kelamin' => $row[$columns['jk']] ?? '',
                'status_kepegawaian' => $row[$columns['status']] ?? '',
            ]);

            $validation = $this->validateBusiness($payload);
            if ($validation !== null) {
                return $this->fail('Baris ' . $lineNo . ': ' . $validation['message']);
            }

            if (isset($seenNik[$payload['nik']])) {
                return $this->fail('Baris ' . $lineNo . ': NIK duplikat di dalam file import.');
            }
            $seenNik[$payload['nik']] = true;

            if ($payload['nip'] !== null) {
                if (isset($seenNip[$payload['nip']])) {
                    return $this->fail('Baris ' . $lineNo . ': NIP duplikat di dalam file import.');
                }
                $seenNip[$payload['nip']] = true;
            }

            $prepared[] = $payload;
        }

        if ($prepared === []) {
            return $this->fail('Tidak ada baris data Guru yang dapat diimport.');
        }

        $this->db->transBegin();

        try {
            foreach ($prepared as $payload) {
                $this->insertGuruAndUser($payload);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi import gagal.');
            }

            $this->activityLog->write(
                $this->actorUserId(),
                'IMPORT',
                'Master Guru',
                sprintf('Import %d data Guru berhasil.', count($prepared))
            );

            $this->db->transCommit();

            return [
                'success' => true,
                'message' => sprintf('%d data Guru berhasil diimport.', count($prepared)),
                'total' => count($prepared),
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('Import dibatalkan: ' . $e->getMessage());
        }
    }

    private function insertGuruAndUser(array $payload): int
    {
        $idGuru = $this->guruModel->insert($payload, true);

        if ($idGuru === false) {
            throw new \RuntimeException(
                implode(' ', $this->guruModel->errors()) ?: 'Data Guru gagal disimpan.'
            );
        }

        $idGuru = (int) $idGuru;
        $this->createGuruUser($idGuru, $this->loginIdentifier($payload));

        return $idGuru;
    }

    private function createGuruUser(int $idGuru, string $login): int
    {
        if ($login === '') {
            throw new \RuntimeException('Identitas login Guru tidak tersedia.');
        }

        if (! $this->usernameAvailable($login)) {
            throw new \RuntimeException('Identitas login sudah digunakan akun lain.');
        }

        $idUser = $this->userModel->insert([
            'username' => $login,
            'password' => password_hash($login, PASSWORD_DEFAULT),
            'role' => 'guru',
            'id_guru' => $idGuru,
            'id_pegawai' => null,
            'id_siswa' => null,
            'status_aktif' => 1,
            'auth_version' => 1,
        ], true);

        if ($idUser === false) {
            throw new \RuntimeException(
                implode(' ', $this->userModel->errors()) ?: 'Akun Guru gagal dibuat.'
            );
        }

        $this->ensureGuruRole((int) $idUser);

        return (int) $idUser;
    }

    private function ensureGuruRole(int $idUser): void
    {
        $exists = $this->db
            ->table('user_roles')
            ->where('id_user', $idUser)
            ->where('role', 'guru')
            ->countAllResults() > 0;

        if ($exists) {
            return;
        }

        if ($this->userRolesModel->insert([
            'id_user' => $idUser,
            'role' => 'guru',
        ]) === false) {
            throw new \RuntimeException('Role akun Guru gagal dibuat.');
        }
    }

    private function normalizePayload(array $data): array
    {
        return [
            'nik' => $this->identifier($data['nik'] ?? null),
            'nip' => $this->nullableIdentifier($data['nip'] ?? null),
            'nama' => trim((string) ($data['nama'] ?? '')),
            'jenis_kelamin' => strtoupper(trim((string) ($data['jenis_kelamin'] ?? ''))),
            'tempat_lahir' => $this->nullableText($data['tempat_lahir'] ?? null, 100),
            'tanggal_lahir' => $this->nullableText($data['tanggal_lahir'] ?? null, 10),
            'agama' => $this->nullableText($data['agama'] ?? null, 30),
            'alamat' => $this->nullableText($data['alamat'] ?? null, 5000),
            'no_telepon' => $this->nullableText($data['no_telepon'] ?? null, 20),
            'email' => $this->nullableText($data['email'] ?? null, 100),
            'status_kepegawaian' => $this->canonicalStatus($data['status_kepegawaian'] ?? null),
            'nuptk' => $this->nullableIdentifier($data['nuptk'] ?? null),
        ];
    }

    private function validateBusiness(
        array $payload,
        ?int $currentId = null,
        ?int $linkedUserId = null
    ): ?array {
        if (! $this->validNik((string) $payload['nik'])) {
            return $this->fail('NIK wajib berupa tepat 16 digit angka.');
        }

        if ($payload['nip'] !== null && ! preg_match('/^[0-9]{18}$/', $payload['nip'])) {
            return $this->fail('NIP harus kosong atau berupa tepat 18 digit angka.');
        }

        if ($payload['nama'] === '' || mb_strlen($payload['nama']) > 150) {
            return $this->fail('Nama lengkap wajib diisi maksimal 150 karakter.');
        }

        if (! in_array($payload['jenis_kelamin'], ['L', 'P'], true)) {
            return $this->fail('Jenis kelamin harus L atau P.');
        }

        if ($payload['status_kepegawaian'] === null) {
            return $this->fail('Status kepegawaian wajib dipilih.');
        }

        if ($payload['nuptk'] !== null && ! preg_match('/^[0-9]{16}$/', $payload['nuptk'])) {
            return $this->fail('NUPTK harus kosong atau berupa tepat 16 digit angka.');
        }

        if ($payload['email'] !== null && ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Format email tidak valid.');
        }

        if ($payload['tanggal_lahir'] !== null && ! $this->validDate($payload['tanggal_lahir'])) {
            return $this->fail('Tanggal lahir harus menggunakan format YYYY-MM-DD.');
        }

        if ($this->identifierExists('guru', 'nik', $payload['nik'], $currentId)) {
            return $this->fail('NIK sudah terdaftar pada data Guru.');
        }

        if ($this->guruModel->nikDipakaiPegawai($payload['nik'])) {
            return $this->fail('NIK sudah digunakan pada data Pegawai.');
        }

        if ($payload['nip'] !== null) {
            if ($this->identifierExists('guru', 'nip', $payload['nip'], $currentId)) {
                return $this->fail('NIP sudah terdaftar pada data Guru.');
            }

            if ($this->guruModel->nipDipakaiPegawai($payload['nip'])) {
                return $this->fail('NIP sudah digunakan pada data Pegawai.');
            }
        }

        $login = $this->loginIdentifier($payload);
        if (! $this->usernameAvailable($login, $linkedUserId)) {
            return $this->fail('Identitas login tidak dapat digunakan karena username yang sama sudah dipakai akun lain.');
        }

        return null;
    }

    private function identifierExists(
        string $table,
        string $field,
        string $value,
        ?int $currentId
    ): bool {
        $builder = $this->db->table($table)->where($field, $value);

        if ($currentId !== null) {
            $builder->where('id !=', $currentId);
        }

        return $builder->countAllResults() > 0;
    }

    private function usernameAvailable(string $username, ?int $ignoreUserId = null): bool
    {
        if ($username === '') {
            return false;
        }

        $builder = $this->db->table('users')->where('username', $username);

        if ($ignoreUserId !== null) {
            $builder->where('id !=', $ignoreUserId);
        }

        return $builder->countAllResults() === 0;
    }

    private function loginIdentifier(array $data): string
    {
        $nip = trim((string) ($data['nip'] ?? ''));
        if ($nip !== '') {
            return $nip;
        }

        return trim((string) ($data['nik'] ?? ''));
    }

    private function validNik(string $nik): bool
    {
        return (bool) preg_match('/^[0-9]{16}$/', trim($nik));
    }

    private function canonicalStatus(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (self::STATUS_KEPEGAWAIAN as $status) {
            if (strcasecmp($status, $value) === 0) {
                return $status;
            }
        }

        return null;
    }

    private function identifier(mixed $value): string
    {
        return preg_replace('/\s+/u', '', trim((string) $value)) ?? '';
    }

    private function nullableIdentifier(mixed $value): ?string
    {
        $value = $this->identifier($value);
        return $value !== '' ? $value : null;
    }

    private function nullableText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? mb_substr($value, 0, $maxLength) : null;
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date;
    }

    private function processFoto(UploadedFile $foto, string $identity): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $identity) ?: 'guru';

        return $this->uploadService->processFotoPortrait(
            $foto,
            rtrim(FCPATH . 'uploads/foto_guru', DIRECTORY_SEPARATOR),
            'guru_' . $safe
        );
    }

    private function deleteFotoFile(string $filename): void
    {
        $filename = basename(trim($filename));
        if ($filename === '') {
            return;
        }

        $path = rtrim(FCPATH . 'uploads/foto_guru', DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function resolveImportColumns(array $header): ?array
    {
        $aliases = [
            'nik' => ['NIK'],
            'nip' => ['NIP'],
            'nama' => ['NAMA LENGKAP & GELAR', 'NAMA LENGKAP', 'NAMA'],
            'jk' => ['JENIS KELAMIN (L/P)', 'JENIS KELAMIN', 'JK'],
            'status' => ['STATUS KEPEGAWAIAN', 'STATUS PEGAWAI', 'STATUS'],
        ];

        $resolved = [];

        foreach ($aliases as $key => $names) {
            foreach ($header as $column => $title) {
                if (in_array($title, $names, true)) {
                    $resolved[$key] = $column;
                    break;
                }
            }
        }

        return count($resolved) === count($aliases) ? $resolved : null;
    }

    private function actorUserId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    /**
     * Ambil path file Phase 3 sebelum owner dihapus permanen. DB rows akan
     * terhapus lewat ON DELETE CASCADE; file fisik baru dihapus setelah commit.
     */
    private function collectPersonaliaFiles(string $ownerColumn, int $ownerId): array
    {
        if ($ownerId <= 0 || ! in_array($ownerColumn, ['id_guru', 'id_pegawai'], true)) {
            return [];
        }

        $paths = [];
        $sources = [
            'riwayat_pendidikan' => ['file_ijazah', 'file_transkrip'],
            'riwayat_penugasan' => ['file_sk_penugasan'],
            'riwayat_pangkat' => ['file_sk_pangkat'],
            'dokumen_personalia' => ['file_path'],
        ];

        foreach ($sources as $table => $fields) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            $rows = $this->db
                ->table($table)
                ->select(implode(', ', $fields))
                ->where($ownerColumn, $ownerId)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                foreach ($fields as $field) {
                    if (! empty($row[$field])) {
                        $paths[] = (string) $row[$field];
                    }
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function deletePersonaliaFiles(array $relativePaths): void
    {
        foreach ($relativePaths as $relative) {
            $relative = str_replace('\\', '/', trim((string) $relative));
            if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
                continue;
            }

            $path = WRITEPATH . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relative, '/'));
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
