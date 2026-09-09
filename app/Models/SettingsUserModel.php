<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SettingsUserModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPaged(array $filter, int $limit, int $offset): array
    {
        return $this->baseBuilder($filter)
            ->orderBy('u.username', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countFiltered(array $filter): int
    {
        return $this->baseBuilder($filter)->countAllResults();
    }

    public function getById(int $id): ?array
    {
        $row = $this->db
            ->table('users u')
            ->select([
                'u.id', 'u.username', 'u.role', 'u.id_guru', 'u.id_pegawai',
                'u.id_siswa', 'u.status_aktif', 'u.auth_version', 'u.created_at',
                'u.updated_at', 'g.nama AS nama_guru', 'g.nik AS nik_guru',
                'g.nip AS nip_guru', 'p.nama AS nama_pegawai', 'p.nik AS nik_pegawai',
                'p.nip AS nip_pegawai', 's.nama AS nama_siswa', 's.nisn',
            ])
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->join('siswa s', 's.id = u.id_siswa', 'left')
            ->where('u.id', $id)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        $row['secondary_roles'] = $this->getSecondaryRoles($id);
        return $row;
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $builder = $this->db->table('users')->where('username', $username);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        return $builder->countAllResults() > 0;
    }

    public function identityInUse(string $column, int $identityId, ?int $exceptUserId = null): bool
    {
        if (! in_array($column, ['id_guru', 'id_pegawai', 'id_siswa'], true)) {
            return true;
        }

        $builder = $this->db->table('users')->where($column, $identityId);
        if ($exceptUserId !== null) {
            $builder->where('id !=', $exceptUserId);
        }
        return $builder->countAllResults() > 0;
    }

    public function insertUser(array $data): int
    {
        $this->db->table('users')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateUser(int $id, array $data): bool
    {
        return (bool) $this->db->table('users')->where('id', $id)->update($data);
    }

    public function deleteUser(int $id): bool
    {
        return (bool) $this->db->table('users')->where('id', $id)->delete();
    }

    public function replaceSecondaryRoles(int $userId, array $roles): void
    {
        $this->db->table('user_roles')->where('id_user', $userId)->delete();
        foreach ($roles as $role) {
            $this->db->table('user_roles')->insert([
                'id_user' => $userId,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getSecondaryRoles(int $userId): array
    {
        $rows = $this->db->table('user_roles')
            ->select('role')
            ->where('id_user', $userId)
            ->orderBy('role', 'ASC')
            ->get()->getResultArray();

        return array_values(array_unique(array_column($rows, 'role')));
    }

    public function getIdentityOptions(string $type, string $search, int $limit = 30): array
    {
        $search = trim($search);
        $limit = max(1, min(50, $limit));

        if ($type === 'guru') {
            $builder = $this->db->table('guru g')
                ->select("g.id, COALESCE(NULLIF(g.nip,''), g.nik) AS kode, g.nik, g.nip, g.nama, u.id AS id_user", false)
                ->join('users u', 'u.id_guru = g.id', 'left')
                ->where('g.deleted_at', null);
            if ($search !== '') {
                $builder->groupStart()->like('g.nama', $search)->orLike('g.nik', $search)->orLike('g.nip', $search)->groupEnd();
            }
            return $builder->orderBy('g.nama', 'ASC')->limit($limit)->get()->getResultArray();
        }

        if ($type === 'pegawai') {
            $builder = $this->db->table('pegawai p')
                ->select("p.id, COALESCE(NULLIF(p.nip,''), p.nik) AS kode, p.nik, p.nip, p.nama, u.id AS id_user", false)
                ->join('users u', 'u.id_pegawai = p.id', 'left')
                ->where('p.deleted_at', null);
            if ($search !== '') {
                $builder->groupStart()->like('p.nama', $search)->orLike('p.nik', $search)->orLike('p.nip', $search)->groupEnd();
            }
            return $builder->orderBy('p.nama', 'ASC')->limit($limit)->get()->getResultArray();
        }

        if ($type === 'siswa') {
            $builder = $this->db->table('siswa s')
                ->select("s.id, s.nisn AS kode, s.nama, u.id AS id_user", false)
                ->join('users u', 'u.id_siswa = s.id', 'left')
                ->where('s.deleted_at', null);
            if ($search !== '') {
                $builder->groupStart()->like('s.nama', $search)->orLike('s.nisn', $search)->groupEnd();
            }
            return $builder->orderBy('s.nama', 'ASC')->limit($limit)->get()->getResultArray();
        }

        return [];
    }

    public function countActiveEffectiveAdmins(?int $exceptUserId = null): int
    {
        $sql = "SELECT COUNT(DISTINCT u.id) AS total FROM users u LEFT JOIN user_roles ur ON ur.id_user = u.id WHERE u.status_aktif = 1 AND (u.role = 'admin' OR ur.role = 'admin')";
        $params = [];
        if ($exceptUserId !== null) {
            $sql .= ' AND u.id != ?';
            $params[] = $exceptUserId;
        }
        $row = $this->db->query($sql, $params)->getRowArray();
        return (int) ($row['total'] ?? 0);
    }

    private function baseBuilder(array $filter)
    {
        $builder = $this->db->table('users u')
            ->select([
                'u.id', 'u.username', 'u.role', 'u.id_guru', 'u.id_pegawai',
                'u.id_siswa', 'u.status_aktif', 'u.auth_version',
                'g.nama AS nama_guru', 'g.nik AS nik_guru', 'g.nip AS nip_guru',
                'p.nama AS nama_pegawai', 'p.nik AS nik_pegawai', 'p.nip AS nip_pegawai',
                's.nama AS nama_siswa', 's.nisn',
            ])
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->join('siswa s', 's.id = u.id_siswa', 'left');

        if (! empty($filter['status'])) {
            $builder->where('u.status_aktif', $filter['status'] === 'aktif' ? 1 : 0);
        }
        if (! empty($filter['role'])) {
            $role = (string) $filter['role'];
            $builder->groupStart()
                ->where('u.role', $role)
                ->orWhere("EXISTS (SELECT 1 FROM user_roles ur WHERE ur.id_user = u.id AND ur.role = " . $this->db->escape($role) . ')', null, false)
                ->groupEnd();
        }
        if (! empty($filter['search'])) {
            $search = trim((string) $filter['search']);
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('g.nama', $search)->orLike('g.nik', $search)->orLike('g.nip', $search)
                ->orLike('p.nama', $search)->orLike('p.nik', $search)->orLike('p.nip', $search)
                ->orLike('s.nama', $search)->orLike('s.nisn', $search)
                ->groupEnd();
        }
        return $builder;
    }
}
