<?php

namespace Config;

use App\Filters\AuthFilter;
use App\Filters\MaintenanceFilter;
use App\Filters\PermissionFilter;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Alias filter SisisFour + bawaan CI4.
     *
     * PENTING: 'forcehttps', 'pagecache', 'performance' WAJIB tetap terdaftar
     * di sini walau tidak aktif dipakai, karena property $required (bawaan
     * BaseFilters, lihat di bawah) mereferensikan alias-alias ini secara
     * hardcode di system/Config/Filters.php. Jika hilang dari $aliases,
     * CI4 melempar FilterException::forNoAlias('forcehttps') — inilah
     * penyebab error yang tadi muncul.
     *
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // Filter kustom SisisFour
        'auth'          => AuthFilter::class,
        'permission'    => PermissionFilter::class,
        'maintenance'   => MaintenanceFilter::class,
    ];

    /**
     * Filter wajib bawaan CI4 (dari BaseFilters), di-override KOSONG di sini
     * karena kita jalan di localhost/XAMPP (non-HTTPS) untuk development.
     *
     * Di produksi (hosting WAJIB SSL — 01_MASTERPLAN §3), aktifkan kembali
     * 'forcehttps' pada array 'before' supaya semua request dipaksa HTTPS.
     */
    public array $required = [
        'before' => [
            // 'forcehttps', // aktifkan saat deploy produksi
        ],
        'after' => [],
    ];

    /**
     * Filter yang berjalan di SETIAP request (before/after) — di luar $required.
     * MaintenanceFilter WAJIB di sini agar mengecek semua route; ia sendiri
     * sudah mengecualikan Admin & route whitelist (login, api auth, dsb).
     */
    public array $globals = [
        'before' => [
            'maintenance',
            // 'csrf' => ['except' => ['api/*', 'kartu/verify/*']], // aktifkan jika CSRF form web diperlukan
        ],
        'after' => [
            'toolbar',
        ],
    ];

    /**
     * Filter berdasarkan method HTTP — dikosongkan, kita atur granular per
     * route/group langsung di app/Config/Routes.php.
     */
    public array $methods = [];

    /**
     * Filter yang bisa dipasang per-group/per-route via ['filter' => '...'].
     * 'auth' dan 'permission' TIDAK perlu didaftarkan ulang di sini — cukup
     * ada di $aliases; pemasangannya sudah eksplisit per route di Routes.php.
     */
    public array $filters = [];
}
