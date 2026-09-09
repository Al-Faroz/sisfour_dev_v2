<?php

namespace App\Services;

use App\Models\SettingsUserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

class SettingsUserService
{
    private const TZ = 'Asia/Jakarta';
    private const ROLES = ['admin', 'operator', 'pimpinan', 'bk', 'guru', 'siswa'];

    protected SettingsUserModel $model;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->model = new SettingsUserModel();
        $this->db = Database::connect();
    }

    public function page(array $input): array
    {
        $limit = max(1, min(100, (int) ($input['limit'] ?? 30)));
        $offset = max(0, (int) ($input['offset'] ?? 0));
        $role = trim((string) ($input['role'] ?? ''));
        $status = trim((string) ($input['status'] ?? ''));

        if ($role !== '' && ! in_array($role, self::ROLES, true)) {
            return $this->fail('VALIDATION', 'Role filter tidak valid.');
        }
        if ($status !== '' && ! in_array($status, ['aktif', 'nonaktif'], true)) {
            return $this->fail('VALIDATION', 'Status filter tidak valid.');
        }

        $filter = [
            'search' => trim((string) ($input['search'] ?? '')) ?: null,
            'role' => $role ?: null,
            'status' => $status ?: null,
        ];

        $rows = $this->model->getPaged($filter, $limit, $offset);
        foreach ($rows as &$row) {
            $row['secondary_roles'] = $this->model->getSecondaryRoles((int) $row['id']);
            $row['identity_label'] = $this->identityLabel($row);
            $row['credential_managed'] = $this->isManagedPersonalia($row);
        }
        unset($row);

        return [
            'success' => true,
            'rows' => $rows,
            'total' => $this->model->countFiltered($filter),
            'limit' => $limit,
            'offset' => $offset,
            'roles' => self::ROLES,
        ];
    }

    public function get(int $id): array
    {
        $row = $this->model->getById($id);
        if (! $row) {
            return $this->fail('NOT_FOUND', 'User tidak ditemukan.');
        }

        $row['identity_label'] = $this->identityLabel($row);
        $row['credential_managed'] = $this->isManagedPersonalia($row);
        $row['credential_identifier'] = $this->managedIdentifier($row);

        return ['success' => true, 'user' => $row, 'roles' => self::ROLES];
    }

    public function identityOptions(array $input): array
    {
        $type = trim((string) ($input['type'] ?? ''));
        $search = trim((string) ($input['search'] ?? ''));
        if (! in_array($type, ['guru', 'pegawai', 'siswa'], true)) {
            return $this->fail('VALIDATION', 'Jenis identitas tidak valid.');
        }
        return ['success' => true, 'rows' => $this->model->getIdentityOptions($type, $search)];
    }

    public function create(int $actorUserId, array $input): array
    {
        $validated = $this->validatePayload($input, null, null);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $secondary = $validated['secondary_roles'];
        $managed = $validated['credential_managed'];
        $password = $managed
            ? (string) $validated['credential_identifier']
            : (string) ($input['password'] ?? '');

        if (strlen($password) < 8) {
            return $this->fail('VALIDATION', 'Password minimal 8 karakter.');
        }

        $now = Time::now(self::TZ)->format('Y-m-d H:i:s');
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        $data['auth_version'] = 1;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $this->db->transBegin();
        try {
            $id = $this->model->insertUser($data);
            if ($id <= 0) {
                throw new \RuntimeException('User gagal dibuat.');
            }
            $this->model->replaceSecondaryRoles($id, $secondary);
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }
            $this->log($actorUserId, 'CREATE', "Membuat user #{$id} ({$data['username']})");
            $this->db->transCommit();
            return ['success' => true, 'message' => $managed
                ? 'User berhasil dibuat. Username dan password mengikuti NIP/NIK identitas.'
                : 'User berhasil dibuat.', 'id' => $id];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('CREATE_FAILED', 'User gagal dibuat.');
        }
    }

    public function update(int $actorUserId, int $id, array $input): array
    {
        $existing = $this->model->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'User tidak ditemukan.');
        }

        $validated = $this->validatePayload($input, $id, $existing);
        if (! $validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        $secondary = $validated['secondary_roles'];

        if ($id === $actorUserId && (int) $data['status_aktif'] !== 1) {
            return $this->fail('SELF_LOCKOUT', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        $willBeAdmin = ($data['role'] ?? null) === 'admin' || in_array('admin', $secondary, true);
        $currentlyAdmin = ($existing['role'] ?? null) === 'admin'
            || in_array('admin', $existing['secondary_roles'] ?? [], true);

        if ($currentlyAdmin && (! $willBeAdmin || (int) $data['status_aktif'] !== 1)
            && $this->model->countActiveEffectiveAdmins($id) < 1) {
            return $this->fail('LAST_ADMIN', 'Minimal satu Admin aktif harus dipertahankan.');
        }

        $data['auth_version'] = ((int) $existing['auth_version']) + 1;
        $data['updated_at'] = Time::now(self::TZ)->format('Y-m-d H:i:s');

        $this->db->transBegin();
        try {
            if (! $this->model->updateUser($id, $data)) {
                throw new \RuntimeException('Update user gagal.');
            }
            $this->model->replaceSecondaryRoles($id, $secondary);
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }
            $this->log($actorUserId, 'UPDATE', "Memperbarui user #{$id}");
            $this->db->transCommit();
            return ['success' => true, 'message' => 'User berhasil diperbarui.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('UPDATE_FAILED', 'User gagal diperbarui.');
        }
    }

    public function resetPassword(int $actorUserId, int $id, array $input): array
    {
        $existing = $this->model->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'User tidak ditemukan.');
        }

        $managed = $this->isManagedPersonalia($existing);

        if ($managed) {
            $password = $this->managedIdentifier($existing);
            if ($password === null) {
                return $this->fail('IDENTITY_INCOMPLETE', 'NIP/NIK identitas belum cukup untuk reset kredensial. Lengkapi Master Guru/Pegawai terlebih dahulu.');
            }
            if ($this->model->usernameExists($password, $id)) {
                return $this->fail('DUPLICATE', 'Identitas login NIP/NIK sudah digunakan akun lain.');
            }
        } else {
            $password = (string) ($input['password'] ?? '');
            $confirmation = (string) ($input['password_confirmation'] ?? '');
            if (strlen($password) < 8) {
                return $this->fail('VALIDATION', 'Password baru minimal 8 karakter.');
            }
            if ($password !== $confirmation) {
                return $this->fail('VALIDATION', 'Konfirmasi password tidak sama.');
            }
        }

        $update = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'auth_version' => ((int) $existing['auth_version']) + 1,
            'updated_at' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ];

        if ($managed) {
            $update['username'] = $password;
        }

        $ok = $this->model->updateUser($id, $update);

        if (! $ok) {
            return $this->fail('UPDATE_FAILED', 'Password gagal direset.');
        }

        $this->log($actorUserId, 'RESET_PASSWORD', "Reset password user #{$id}");
        return ['success' => true, 'message' => $managed
            ? 'Username dan password berhasil disinkronkan ke identitas login NIP/NIK.'
            : 'Password berhasil direset.'];
    }

    public function delete(int $actorUserId, int $id): array
    {
        if ($id === $actorUserId) {
            return $this->fail('SELF_DELETE', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        $existing = $this->model->getById($id);
        if (! $existing) {
            return $this->fail('NOT_FOUND', 'User tidak ditemukan.');
        }

        if ($this->isManagedPersonalia($existing)) {
            return $this->fail(
                'MANAGED_ACCOUNT',
                'Akun Guru/Pegawai dikelola otomatis dari Master. Nonaktifkan akun bila perlu; jangan menghapusnya dari Manajemen User.'
            );
        }

        $isAdmin = ($existing['role'] ?? null) === 'admin'
            || in_array('admin', $existing['secondary_roles'] ?? [], true);
        if ($isAdmin && $this->model->countActiveEffectiveAdmins($id) < 1) {
            return $this->fail('LAST_ADMIN', 'Minimal satu Admin aktif harus dipertahankan.');
        }

        $this->db->transBegin();
        try {
            $this->db->table('user_roles')->where('id_user', $id)->delete();
            if (! $this->model->deleteUser($id)) {
                throw new \RuntimeException('Delete user gagal.');
            }
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }
            $this->log($actorUserId, 'DELETE', "Menghapus user #{$id}");
            $this->db->transCommit();
            return ['success' => true, 'message' => 'User berhasil dihapus.'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            return $this->fail('DELETE_FAILED', 'User tidak dapat dihapus karena masih memiliki relasi data. Nonaktifkan akun bila perlu.');
        }
    }

    private function validatePayload(array $input, ?int $exceptUserId, ?array $existing): array
    {
        $primary = trim((string) ($input['primary_role'] ?? ''));
        $status = filter_var($input['status_aktif'] ?? 1, FILTER_VALIDATE_INT);
        $secondary = $input['secondary_roles'] ?? [];

        if ($primary !== '' && ! in_array($primary, self::ROLES, true)) {
            return $this->fail('VALIDATION', 'Primary role tidak valid.');
        }
        if (! in_array($status, [0, 1], true)) {
            return $this->fail('VALIDATION', 'Status user tidak valid.');
        }

        if (! is_array($secondary)) {
            $secondary = [$secondary];
        }
        $secondary = array_values(array_unique(array_filter(array_map(
            static fn ($role) => trim((string) $role), $secondary
        ))));
        foreach ($secondary as $role) {
            if (! in_array($role, self::ROLES, true)) {
                return $this->fail('VALIDATION', 'Secondary role tidak valid.');
            }
        }
        if ($primary !== '') {
            $secondary = array_values(array_diff($secondary, [$primary]));
        }

        $idGuru = (int) ($input['id_guru'] ?? 0) ?: null;
        $idPegawai = (int) ($input['id_pegawai'] ?? 0) ?: null;
        $idSiswa = (int) ($input['id_siswa'] ?? 0) ?: null;

        // Identitas Guru selalu mempertahankan effective role Guru. Admin tetap
        // boleh menjadikan role operasional lain sebagai primary; role Guru
        // otomatis dipertahankan sebagai secondary bila diperlukan.
        if ($idGuru && $primary !== 'guru' && ! in_array('guru', $secondary, true)) {
            $secondary[] = 'guru';
        }

        if ($existing !== null && $this->isManagedPersonalia($existing)) {
            $existingGuru = (int) ($existing['id_guru'] ?? 0) ?: null;
            $existingPegawai = (int) ($existing['id_pegawai'] ?? 0) ?: null;
            if ($idGuru !== $existingGuru || $idPegawai !== $existingPegawai || $idSiswa !== null) {
                return $this->fail('MANAGED_ACCOUNT', 'Relasi identitas akun Guru/Pegawai dikelola dari Master dan tidak dapat dipindah melalui Manajemen User.');
            }
        }

        $identityCount = count(array_filter([$idGuru, $idPegawai, $idSiswa]));
        if ($identityCount > 1) {
            return $this->fail('VALIDATION', 'Satu user hanya boleh terhubung ke satu identitas Guru, Pegawai, atau Siswa.');
        }

        if (($primary === 'guru' || in_array('guru', $secondary, true)) && ! $idGuru) {
            return $this->fail('VALIDATION', 'Role Guru memerlukan relasi Guru.');
        }
        if (($primary === 'siswa' || in_array('siswa', $secondary, true)) && ! $idSiswa) {
            return $this->fail('VALIDATION', 'Role Siswa memerlukan relasi Siswa.');
        }

        foreach (['id_guru' => $idGuru, 'id_pegawai' => $idPegawai, 'id_siswa' => $idSiswa] as $column => $identityId) {
            if ($identityId && $this->model->identityInUse($column, $identityId, $exceptUserId)) {
                return $this->fail('IDENTITY_IN_USE', 'Identitas tersebut sudah terhubung ke user lain.');
            }
        }

        $managedRow = [
            'id_guru' => $idGuru,
            'id_pegawai' => $idPegawai,
        ];
        $credentialManaged = $this->isManagedPersonalia($managedRow);
        $credentialIdentifier = $credentialManaged ? $this->identifierForIds($idGuru, $idPegawai) : null;

        if ($credentialManaged) {
            if ($credentialIdentifier === null) {
                return $this->fail('IDENTITY_INCOMPLETE', 'Identitas Guru/Pegawai belum memiliki NIP/NIK login yang valid. Lengkapi Master terlebih dahulu.');
            }
            $username = $credentialIdentifier;
        } else {
            $username = trim((string) ($input['username'] ?? ''));
            if (! preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
                return $this->fail('VALIDATION', 'Username 3–50 karakter dan hanya boleh huruf, angka, titik, garis bawah, atau tanda minus.');
            }
        }

        if ($this->model->usernameExists($username, $exceptUserId)) {
            return $this->fail('DUPLICATE', 'Username sudah digunakan.');
        }

        return [
            'success' => true,
            'data' => [
                'username' => $username,
                'role' => $primary !== '' ? $primary : null,
                'id_guru' => $idGuru,
                'id_pegawai' => $idPegawai,
                'id_siswa' => $idSiswa,
                'status_aktif' => $status,
            ],
            'secondary_roles' => $secondary,
            'credential_managed' => $credentialManaged,
            'credential_identifier' => $credentialIdentifier,
        ];
    }

    private function identifierForIds(?int $idGuru, ?int $idPegawai): ?string
    {
        if ($idGuru) {
            $row = $this->db->table('guru')->select('nik, nip')->where('id', $idGuru)->where('deleted_at', null)->get()->getRowArray();
            return $row ? $this->personaliaIdentifier($row) : null;
        }
        if ($idPegawai) {
            $row = $this->db->table('pegawai')->select('nik, nip')->where('id', $idPegawai)->where('deleted_at', null)->get()->getRowArray();
            return $row ? $this->personaliaIdentifier($row) : null;
        }
        return null;
    }

    private function managedIdentifier(array $row): ?string
    {
        if (! empty($row['id_guru'])) {
            return $this->personaliaIdentifier([
                'nik' => $row['nik_guru'] ?? null,
                'nip' => $row['nip_guru'] ?? null,
            ]);
        }
        if (! empty($row['id_pegawai'])) {
            return $this->personaliaIdentifier([
                'nik' => $row['nik_pegawai'] ?? null,
                'nip' => $row['nip_pegawai'] ?? null,
            ]);
        }
        return null;
    }

    private function personaliaIdentifier(array $row): ?string
    {
        $nip = trim((string) ($row['nip'] ?? ''));
        if (preg_match('/^[0-9]{18}$/', $nip)) {
            return $nip;
        }
        $nik = trim((string) ($row['nik'] ?? ''));
        if (preg_match('/^[0-9]{16}$/', $nik)) {
            return $nik;
        }
        return null;
    }

    private function isManagedPersonalia(array $row): bool
    {
        return ! empty($row['id_guru']) || ! empty($row['id_pegawai']);
    }

    private function identityLabel(array $row): string
    {
        if (! empty($row['nama_guru'])) {
            $kode = trim((string) (($row['nip_guru'] ?? '') ?: ($row['nik_guru'] ?? '')));
            return 'Guru — ' . ($kode !== '' ? $kode . ' — ' : '') . $row['nama_guru'];
        }
        if (! empty($row['nama_pegawai'])) {
            $kode = trim((string) (($row['nip_pegawai'] ?? '') ?: ($row['nik_pegawai'] ?? '')));
            return 'Pegawai — ' . ($kode !== '' ? $kode . ' — ' : '') . $row['nama_pegawai'];
        }
        if (! empty($row['nama_siswa'])) {
            return 'Siswa — ' . ($row['nisn'] ? $row['nisn'] . ' — ' : '') . $row['nama_siswa'];
        }
        return 'Tanpa relasi';
    }

    private function log(int $actorUserId, string $aksi, string $keterangan): void
    {
        $this->db->table('log_activity')->insert([
            'id_user' => $actorUserId,
            'aksi' => $aksi,
            'modul' => 'Settings User',
            'keterangan' => $keterangan,
            'waktu' => Time::now(self::TZ)->format('Y-m-d H:i:s'),
        ]);
    }

    private function fail(string $code, string $message): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message];
    }
}
