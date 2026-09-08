<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SettingSistemModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function allAssoc(): array
    {
        $rows = $this->db
            ->table('setting_sistem')
            ->select('id, setting_key, setting_value, type, updated_at, updated_by')
            ->orderBy('setting_key', 'ASC')
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row;
        }

        return $result;
    }

    public function set(string $key, string $value, string $type, int $userId): void
    {
        $exists = $this->db
            ->table('setting_sistem')
            ->where('setting_key', $key)
            ->countAllResults() > 0;

        $data = [
            'setting_value' => $value,
            'type' => $type,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ];

        if ($exists) {
            $this->db->table('setting_sistem')
                ->where('setting_key', $key)
                ->update($data);
            return;
        }

        $data['setting_key'] = $key;
        $this->db->table('setting_sistem')->insert($data);
    }
}
