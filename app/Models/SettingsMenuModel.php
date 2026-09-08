<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SettingsMenuModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getMenus(): array
    {
        return $this->db
            ->table('menus')
            ->select('id, nama_menu, parent_id, urutan, icon, link')
            ->orderBy('parent_id', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getMenu(int $id): ?array
    {
        return $this->db
            ->table('menus')
            ->where('id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function getRoleMenus(): array
    {
        return $this->db
            ->table('role_menus')
            ->select('role, id_menu, tampil')
            ->where('tampil', 1)
            ->get()
            ->getResultArray();
    }

    public function replaceRoles(int $idMenu, array $roles): void
    {
        $this->db->table('role_menus')
            ->where('id_menu', $idMenu)
            ->delete();

        foreach ($roles as $role) {
            $this->db->table('role_menus')->insert([
                'role' => $role,
                'id_menu' => $idMenu,
                'tampil' => 1,
            ]);
        }
    }

    public function roleHasAnyPermission(string $role, array $permissionKeys): bool
    {
        if ($permissionKeys === []) {
            return true;
        }

        return $this->db
            ->table('role_permissions rp')
            ->join('permissions p', 'p.id = rp.id_permission')
            ->where('rp.role', $role)
            ->whereIn('p.permission_key', $permissionKeys)
            ->countAllResults() > 0;
    }

    public function permissionSummary(): array
    {
        return $this->db
            ->table('role_permissions rp')
            ->select('rp.role, p.permission_key, rp.scope')
            ->join('permissions p', 'p.id = rp.id_permission')
            ->orderBy('rp.role', 'ASC')
            ->orderBy('p.permission_key', 'ASC')
            ->get()
            ->getResultArray();
    }
}
