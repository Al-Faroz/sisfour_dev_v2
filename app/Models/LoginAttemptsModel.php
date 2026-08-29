<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * LoginAttemptsModel
 *
 * Merepresentasikan tabel `login_attempts`.
 * Rate limiting berbasis USERNAME (bukan IP) — lihat 03_AUTH_RBAC_MENU §1.4:
 * "menghindari lock massal di lingkungan sekolah (satu IP dipakai banyak user)".
 */
class LoginAttemptsModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'username',
        'ip_address',
        'waktu',
        'berhasil',
    ];

    // Dates — tabel ini pakai kolom `waktu` manual, bukan timestamps otomatis CI4
    protected $useTimestamps = false;

    // Validation
    protected $validationRules = [
        'username'   => 'required|max_length[50]',
        'ip_address' => 'required|max_length[45]',
        'waktu'      => 'required|valid_date',
        'berhasil'   => 'required|in_list[0,1]',
    ];

    protected $skipValidation = false;

    /**
     * Hitung jumlah percobaan gagal berturut-turut dalam N menit terakhir
     * untuk satu username (dipakai AuthService untuk cek lock).
     */
    public function countRecentFailedAttempts(string $username, int $minutes = 5): int
    {
        $batasWaktu = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return $this->where('username', $username)
            ->where('berhasil', 0)
            ->where('waktu >=', $batasWaktu)
            ->countAllResults();
    }

    /**
     * Catat satu percobaan login (berhasil atau gagal).
     */
    public function catat(string $username, string $ipAddress, bool $berhasil): void
    {
        $this->insert([
            'username'   => $username,
            'ip_address' => $ipAddress,
            'waktu'      => date('Y-m-d H:i:s'),
            'berhasil'   => $berhasil ? 1 : 0,
        ]);
    }
}
