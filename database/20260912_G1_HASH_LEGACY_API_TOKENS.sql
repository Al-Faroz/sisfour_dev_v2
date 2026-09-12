-- SISFOUR DEV V2
-- G1 / F02 API token hardening
-- Tanggal: 2026-09-12
--
-- Tujuan:
-- 1. Menghilangkan access token / refresh token legacy yang tersimpan raw.
-- 2. Merevoke token legacy agar seluruh client API login ulang.
-- 3. Tidak mengubah schema tabel api_tokens.
--
-- Runtime baru menyimpan SHA-256 (64 hex chars) untuk kolom token dan
-- refresh_token. Refresh token baru yang diberikan ke client berbentuk
-- v{auth_version}.{random-128-hex}, tetapi database hanya menyimpan hash-nya.
--
-- Script ini aman dijalankan sebelum atau sesudah deploy source baru:
-- row yang sudah memakai storage hash 64 karakter tidak disentuh.

START TRANSACTION;

UPDATE api_tokens
SET
    token = SHA2(token, 256),
    refresh_token = SHA2(refresh_token, 256),
    revoked_at = COALESCE(revoked_at, NOW())
WHERE
    CHAR_LENGTH(token) <> 64
    OR CHAR_LENGTH(refresh_token) <> 64;

COMMIT;

-- Verifikasi setelah eksekusi:
-- SELECT
--     COUNT(*) AS total_token,
--     SUM(CHAR_LENGTH(token) = 64) AS access_hash_64,
--     SUM(CHAR_LENGTH(refresh_token) = 64) AS refresh_hash_64,
--     SUM(revoked_at IS NULL) AS token_masih_aktif
-- FROM api_tokens;
