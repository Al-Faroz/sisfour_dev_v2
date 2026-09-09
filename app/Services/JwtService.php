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
    private const ACCESS_TTL = 3600;
    private const REFRESH_TTL = 2592000;

    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

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

        $refreshToken = bin2hex(random_bytes(64));

        $inserted = $this->db
            ->table('api_tokens')
            ->insert([
                'id_user' => $userId,
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'device_name' => $deviceName,
                'expires_at' => date('Y-m-d H:i:s', $accessExp),
                'refresh_expires_at' => date('Y-m-d H:i:s', $refreshExp),
                'revoked_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$inserted) {
            throw new RuntimeException('Token API gagal disimpan.');
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TTL,
            'refresh_expires_in' => self::REFRESH_TTL,
        ];
    }

    public function validateAccessToken(
        string $accessToken
    ): array {
        $accessToken = trim($accessToken);

        if ($accessToken === '') {
            throw new RuntimeException('Access token kosong.');
        }

        try {
            $decoded = JWT::decode(
                $accessToken,
                new Key($this->getSecret(), self::ALGORITHM)
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Access token tidak valid atau sudah kedaluwarsa.'
            );
        }

        $userId = (int) ($decoded->uid ?? 0);
        $authVersion = (int) ($decoded->av ?? -1);

        if ($userId <= 0 || $authVersion < 0) {
            throw new RuntimeException('Claim access token tidak valid.');
        }

        $tokenRow = $this->db
            ->table('api_tokens')
            ->where('token', $accessToken)
            ->where('id_user', $userId)
            ->where('revoked_at IS NULL', null, false)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get()
            ->getRowArray();

        if (!$tokenRow) {
            throw new RuntimeException('Access token sudah tidak aktif.');
        }

        $user = $this->activeUser($userId);

        if (
            !$user
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

    public function refresh(
        string $refreshToken,
        ?string $deviceName = null
    ): array {
        $refreshToken = trim($refreshToken);

        if ($refreshToken === '') {
            throw new RuntimeException('Refresh token wajib diisi.');
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

        $userId = (int) ($row['id_user'] ?? 0);
        $user = $this->activeUser($userId);

        if (!$user) {
            throw new RuntimeException('User tidak aktif.');
        }

        $storedClaims = $this->verifiedStoredClaims(
            (string) ($row['token'] ?? '')
        );

        if (
            (int) ($storedClaims['uid'] ?? 0) !== $userId
            || (int) ($storedClaims['av'] ?? -1)
                !== (int) $user['auth_version']
        ) {
            $this->db
                ->table('api_tokens')
                ->where('id', (int) $row['id'])
                ->update([
                    'revoked_at' => date('Y-m-d H:i:s'),
                ]);

            throw new RuntimeException(
                'Refresh token sudah tidak berlaku. Silakan login kembali.'
            );
        }

        $now = time();

        $payload = [
            'iss' => base_url('/'),
            'aud' => 'sisfour-api',
            'iat' => $now,
            'exp' => $now + self::ACCESS_TTL,
            'sub' => (string) $user['id'],
            'uid' => (int) $user['id'],
            'av' => (int) $user['auth_version'],
        ];

        $accessToken = JWT::encode(
            $payload,
            $this->getSecret(),
            self::ALGORITHM
        );

        $this->db
            ->table('api_tokens')
            ->where('id', (int) $row['id'])
            ->update([
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);

        $newRefreshToken = bin2hex(random_bytes(64));

        $inserted = $this->db
            ->table('api_tokens')
            ->insert([
                'id_user' => (int) $user['id'],
                'token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'device_name' => $deviceName ?? $row['device_name'],
                'expires_at' => date(
                    'Y-m-d H:i:s',
                    $now + self::ACCESS_TTL
                ),
                'refresh_expires_at' => date(
                    'Y-m-d H:i:s',
                    $now + self::REFRESH_TTL
                ),
                'revoked_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$inserted) {
            throw new RuntimeException('Token API baru gagal disimpan.');
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => self::ACCESS_TTL,
            'refresh_expires_in' => self::REFRESH_TTL,
        ];
    }

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

    private function activeUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $user = $this->db
            ->table('users')
            ->select(
                'id, username, role, id_guru, id_siswa, id_pegawai, ' .
                'auth_version, status_aktif'
            )
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$user || (int) ($user['status_aktif'] ?? 0) !== 1) {
            return null;
        }

        return $user;
    }

    private function verifiedStoredClaims(string $accessToken): array
    {
        $parts = explode('.', trim($accessToken));

        if (count($parts) !== 3) {
            throw new RuntimeException(
                'Refresh token tidak terikat pada access token yang valid.'
            );
        }

        [$headerPart, $payloadPart, $signaturePart] = $parts;

        $header = json_decode(
            $this->base64UrlDecode($headerPart),
            true
        );

        $payload = json_decode(
            $this->base64UrlDecode($payloadPart),
            true
        );

        if (
            !is_array($header)
            || !is_array($payload)
            || ($header['alg'] ?? '') !== self::ALGORITHM
        ) {
            throw new RuntimeException(
                'Refresh token tidak terikat pada access token yang valid.'
            );
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $headerPart . '.' . $payloadPart,
            $this->getSecret(),
            true
        );

        $actualSignature = $this->base64UrlDecode($signaturePart);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            throw new RuntimeException(
                'Refresh token tidak terikat pada access token yang valid.'
            );
        }

        return $payload;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
            true
        );

        if ($decoded === false) {
            throw new RuntimeException('Token tidak valid.');
        }

        return $decoded;
    }
}
