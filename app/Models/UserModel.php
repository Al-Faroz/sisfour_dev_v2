<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

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

    protected $dateFormat = 'datetime';

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';

    protected $deletedField = '';

    protected $validationRules = [
        'username' => 'required|max_length[50]|is_unique[users.username,id,{id}]',

        'password' => 'required|max_length[255]',

        'role' => 'required|in_list[
            admin,
            operator,
            pimpinan,
            bk,
            guru,
            siswa
        ]',

        'status_aktif' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'username' => [
            'is_unique' => 'Username sudah digunakan.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Cari user berdasarkan username.
     */
    public function findByUsername(string $username): ?array
    {
        $username = trim($username);

        if ($username === '') {
            return null;
        }

        return $this
            ->where('username', $username)
            ->first();
    }

    /**
     * Cari user sekaligus seluruh role-nya.
     */
    public function findWithRoles(int $id): ?array
    {
        $user = $this->find($id);

        if (!$user) {
            return null;
        }

        $roleModel = new UserRolesModel();

        $user['all_roles'] = $roleModel
            ->where('id_user', $id)
            ->findColumn('role') ?? [];

        /*
         * Pastikan role utama juga masuk.
         */
        if (!empty($user['role'])) {
            $user['all_roles'][] = $user['role'];
        }

        $user['all_roles'] = array_values(
            array_unique($user['all_roles'])
        );

        return $user;
    }
}
