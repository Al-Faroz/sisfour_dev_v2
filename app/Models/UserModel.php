<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel
 *
 * Merepresentasikan tabel `users`.
 *
 * Business logic autentikasi tetap berada di AuthService.
 *
 * Catatan Master Pegawai:
 * - Guru dibuat dengan role = 'guru'.
 * - Siswa dibuat dengan role = 'siswa'.
 * - Pegawai dibuat dengan role = NULL sampai Admin menentukan role.
 *
 * Karena itu `role` harus nullable pada schema dan permit_empty pada Model.
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useSoftDeletes = false;

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

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = '';

    protected $validationRules = [
        'username'     => 'required|max_length[50]|is_unique[users.username,id,{id}]',
        'password'     => 'required|max_length[255]',
        'role'         => 'permit_empty|in_list[admin,operator,pimpinan,bk,guru,siswa]',
        'status_aktif' => 'permit_empty|in_list[0,1]',
        'auth_version' => 'permit_empty|integer',
    ];

    protected $validationMessages = [
        'username' => [
            'required'  => 'Username wajib diisi.',
            'is_unique' => 'Username sudah digunakan.',
        ],
        'role' => [
            'in_list' => 'Role user tidak valid.',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public function findByUsername(string $username): ?array
    {
        return $this
            ->where('username', trim($username))
            ->first();
    }

    /**
     * Ambil user beserta role utama dan role tambahan.
     *
     * Role utama boleh NULL untuk akun Pegawai yang belum ditetapkan
     * kewenangannya oleh Admin.
     */
    public function findWithRoles(int $id): ?array
    {
        $user = $this->find($id);

        if ($user === null) {
            return null;
        }

        $roleModel = new UserRolesModel();
        $extraRoles = $roleModel
            ->where('id_user', $id)
            ->findColumn('role') ?? [];

        $roles = [];

        if (!empty($user['role'])) {
            $roles[] = (string) $user['role'];
        }

        foreach ($extraRoles as $role) {
            if (!empty($role)) {
                $roles[] = (string) $role;
            }
        }

        $user['all_roles'] = array_values(array_unique($roles));

        return $user;
    }
}
