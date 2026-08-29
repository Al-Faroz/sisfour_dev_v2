<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel
 *
 * Merepresentasikan tabel `users`.
 * Business logic (login, hashing, cek auth_version, dll) TIDAK di sini —
 * ada di AuthService (lihat 01_MASTERPLAN §7).
 *
 * Referensi: 02_DATABASE §3.1, 03_AUTH_RBAC_MENU §1.3
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // tabel users TIDAK pakai soft delete (02_DATABASE §1)

    protected $allowedFields = [
        'username',
        'password',
        'role',
        'id_guru',
        'id_pegawai',
        'id_siswa',
        'status_aktif',
        'auth_version',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = '';

    // Validation
    protected $validationRules = [
        'username'     => 'required|max_length[50]|is_unique[users.username,id,{id}]',
        'password'     => 'required|max_length[255]',
        'role'         => 'required|in_list[admin,operator,pimpinan,bk,guru,siswa]',
        'status_aktif' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'username' => [
            'is_unique' => 'Username sudah digunakan.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Cari user berdasarkan username (dipakai saat login).
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Ambil user beserta role tambahan dari user_roles (multi-role, 03_AUTH_RBAC_MENU §2.1).
     */
    public function findWithRoles(int $id): ?array
    {
        $user = $this->find($id);
        if (!$user) {
            return null;
        }

        $roleModel        = new UserRolesModel();
        $user['all_roles'] = $roleModel->where('id_user', $id)->findColumn('role') ?? [];

        return $user;
    }
}
