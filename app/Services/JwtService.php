<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

class JwtService
{
    private const ALGORITHM = 'HS256';
    private const ACCESS_TTL = 3600;       // 1 jam
    private const REFRESH_TTL = 2592000;   // 30 hari

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Membuat access token + refresh token.
     *
     * @return array{
     *     access_token: string,
     *     refresh_token: string,
     *     expires_in: int,
     *     refresh_expires_in: int
     * }
     */
    public function issueTokens(
        array $user,
        ?string $deviceName = null
    ): array {
        $secret = $this->getSecret();

        $now = time();
        $accessExp = $now + self::ACCESS_TTL;
        $refreshExp = $now + self::REFRESH_TTL;

        $authVersion = (int) ($user['auth_version'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        if ($userId <= 0) {
            throw new RuntimeException('User ID tidak valid.');
        }

        /*
         * JWT access token.
         *
         * auth_version dimasukkan ke claim agar setiap request
         * dapat dibandingkan dengan users.auth_version.
         */
        $payload = [
            'iss' => base_url('/'),
            'aud' => 'sisfour-api',
            'iat' => $now,
            'exp' => $accessExp,
            'sub' => (string) $userId,
            'uid' => $userId,
            'av' => $authVersion,
        ];

        $accessToken = JWT::encode(
            $payload,
            $secret,
            self::ALGORITHM
        );

        /*
         * Refresh token menggunakan random opaque token.
         * Token tetap disimpan di database sehingga dapat direvoke.
         */
        $refreshToken = bin2hex(random_bytes(64));

        $inserted = $this->db
            ->table('api_tokens')
            ->insert([
                'id_user' => $userId,
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'device_name' => $deviceName,
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    $accessExp
                ),
                'refresh_expires_at' => date(
                    'Y-m-d H:i:s',
                    $refreshExp
                ),
                'revoked_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$inserted) {
            throw new RuntimeException(
                'Token API gagal disimpan.'
            );
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TTL,
            'refresh_expires_in' => self::REFRESH_TTL,
        ];
    }

    /**
     * Validasi access token JWT + database.
     *
     * @return array<string,mixed>
     */
    public function validateAccessToken(
        string $accessToken
    ): array {
        $accessToken = trim($accessToken);

        if ($accessToken === '') {
            throw new RuntimeException(
                'Access token kosong.'
            );
        }

        $secret = $this->getSecret();

        try {
            $decoded = JWT::decode(
                $accessToken,
                new Key($secret, self::ALGORITHM)
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Access token tidak valid atau sudah kedaluwarsa.'
            );
        }

        $userId = (int) ($decoded->uid ?? 0);
        $authVersion = (int) ($decoded->av ?? -1);

        if ($userId <= 0 || $authVersion < 0) {
            throw new RuntimeException(
                'Claim access token tidak valid.'
            );
        }

        /*
         * Token harus masih tercatat dan belum direvoke.
         */
        $tokenRow = $this->db
            ->table('api_tokens')
            ->where('token', $accessToken)
            ->where('id_user', $userId)
            ->where('revoked_at IS NULL', null, false)
            ->where(
                'expires_at >=',
                date('Y-m-d H:i:s')
            )
            ->get()
            ->getRowArray();

        if (!$tokenRow) {
            throw new RuntimeException(
                'Access token sudah tidak aktif.'
            );
        }

        /*
         * Single Active Session:
         * auth_version token harus sama dengan database.
         */
        $user = $this->db
            ->table('users')
            ->select(
                'id, username, role, id_guru, id_siswa, id_pegawai, auth_version, status_aktif'
            )
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (
            !$user
            || (int) $user['status_aktif'] !== 1
            || (int) $user['auth_version'] !== $authVersion
        ) {
            throw new RuntimeException(
                'Sesi API sudah tidak berlaku. Silakan login kembali.'
            );
        }

        return [
            'user' => $user,
            'token' => $tokenRow,
            'claims' => (array) $decoded,
        ];
    }

    /**
     * Revoke token API.
     */
    public function revokeAccessToken(
        string $accessToken
    ): bool {
        return $this->db
            ->table('api_tokens')
            ->where('token', trim($accessToken))
            ->where('revoked_at IS NULL', null, false)
            ->update([
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Refresh access token menggunakan refresh token.
     *
     * Refresh tidak menaikkan auth_version karena refresh bukan
     * login baru. Token baru tetap memakai auth_version aktif.
     */
    public function refresh(
        string $refreshToken,
        ?string $deviceName = null
    ): array {
        $refreshToken = trim($refreshToken);

        if ($refreshToken === '') {
            throw new RuntimeException(
                'Refresh token wajib diisi.'
            );
        }

        $row = $this->db
            ->table('api_tokens')
            ->where('refresh_token', $refreshToken)
            ->where('revoked_at IS NULL', null, false)
            ->where(
                'refresh_expires_at >=',
                date('Y-m-d H:i:s')
            )
            ->get()
            ->getRowArray();

        if (!$row) {
            throw new RuntimeException(
                'Refresh token tidak valid atau sudah kedaluwarsa.'
            );
        }

        $user = $this->db
            ->table('users')
            ->select(
                'id, username, role, id_guru, id_siswa, id_pegawai, auth_version, status_aktif'
            )
            ->where('id', (int) $row['id_user'])
            ->get()
            ->getRowArray();

        if (
            !$user
            || (int) $user['status_aktif'] !== 1
        ) {
            throw new RuntimeException(
                'User tidak aktif.'
            );
        }

        /*
         * Jika auth_version berubah karena login lain,
         * refresh token lama ikut tidak berlaku.
         */
        $payload = [
            'iss' => base_url('/'),
            'aud' => 'sisfour-api',
            'iat' => time(),
            'exp' => time() + self::ACCESS_TTL,
            'sub' => (string) $user['id'],
            'uid' => (int) $user['id'],
            'av' => (int) $user['auth_version'],
        ];

        $accessToken = JWT::encode(
            $payload,
            $this->getSecret(),
            self::ALGORITHM
        );

        /*
         * Revoke token lama agar satu refresh token hanya
         * menghasilkan satu access token aktif pada satu waktu.
         */
        $this->db
            ->table('api_tokens')
            ->where('id', (int) $row['id'])
            ->update([
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);

        $newRefreshToken = bin2hex(random_bytes(64));

        $this->db
            ->table('api_tokens')
            ->insert([
                'id_user' => (int) $user['id'],
                'token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'device_name' => $deviceName ?? $row['device_name'],
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + self::ACCESS_TTL
                ),
                'refresh_expires_at' => date(
                    'Y-m-d H:i:s',
                    time() + self::REFRESH_TTL
                ),
                'revoked_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => self::ACCESS_TTL,
            'refresh_expires_in' => self::REFRESH_TTL,
        ];
    }

    /**
     * Ambil secret JWT dari environment.
     */
    protected function getSecret(): string
    {
        $secret = trim((string) env('JWT_SECRET'));

        if ($secret === '') {
            throw new RuntimeException(
                'JWT_SECRET belum dikonfigurasi pada .env.'
            );
        }

        if (strlen($secret) < 32) {
            throw new RuntimeException(
                'JWT_SECRET minimal 32 karakter.'
            );
        }

        return $secret;
    }
}
