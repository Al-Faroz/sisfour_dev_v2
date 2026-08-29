<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ApiTokensModel
 *
 * Merepresentasikan tabel `api_tokens` — untuk autentikasi mobile (JWT).
 * Access token: 1 jam. Refresh token: 30 hari (03_AUTH_RBAC_MENU §1.1, 16_MOBILE_CORDOVA §4.1).
 * Refresh token disimpan dalam bentuk HASH (SHA-256) — 16_MOBILE_CORDOVA §10.
 */
class ApiTokensModel extends Model
{
    protected $table            = 'api_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'id_user',
        'token',
        'refresh_token',
        'device_name',
        'expires_at',
        'refresh_expires_at',
        'revoked_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    // Validation
    protected $validationRules = [
        'id_user'             => 'required|integer',
        'token'               => 'required|max_length[255]|is_unique[api_tokens.token,id,{id}]',
        'refresh_token'       => 'required|max_length[255]|is_unique[api_tokens.refresh_token,id,{id}]',
        'expires_at'          => 'required|valid_date',
        'refresh_expires_at'  => 'required|valid_date',
    ];

    protected $skipValidation = false;

    /**
     * Cari token aktif (belum revoked, belum expired) berdasarkan refresh_token hash.
     */
    public function findActiveByRefreshToken(string $refreshTokenHash): ?array
    {
        return $this->where('refresh_token', $refreshTokenHash)
            ->where('revoked_at', null)
            ->where('refresh_expires_at >=', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Revoke satu token (dipakai saat logout).
     */
    public function revoke(int $id): bool
    {
        return (bool) $this->update($id, ['revoked_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Revoke semua token milik satu user (dipakai saat single-session enforcement).
     */
    public function revokeAllByUser(int $idUser): void
    {
        $this->where('id_user', $idUser)
            ->where('revoked_at', null)
            ->set(['revoked_at' => date('Y-m-d H:i:s')])
            ->update();
    }
}
