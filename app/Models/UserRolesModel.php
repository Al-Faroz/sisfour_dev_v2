<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserRolesModel
 *
 * Merepresentasikan tabel `user_roles` — mendukung Multi-Role
 * (contoh: Guru merangkap Operator). Lihat 03_AUTH_RBAC_MENU §2.1.
 *
 * PermissionFilter WAJIB mengecek tabel ini selain users.role
 * saat resolusi permission (03_AUTH_RBAC_MENU §3.1).
 */
class UserRolesModel extends Model
{
    protected $table            = 'user_roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'id_user',
        'role',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    // Validation
    protected $validationRules = [
        'id_user' => 'required|integer',
        'role'    => 'required|in_list[admin,operator,pimpinan,bk,guru,siswa]',
    ];

    protected $skipValidation = false;

    /**
     * Ambil semua role tambahan milik satu user.
     *
     * @return list<string>
     */
    public function getRolesByUser(int $idUser): array
    {
        return $this->where('id_user', $idUser)->findColumn('role') ?? [];
    }
}
