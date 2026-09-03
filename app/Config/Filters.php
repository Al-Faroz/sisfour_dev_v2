<?php

namespace Config;

use App\Filters\AuthFilter;
use App\Filters\MaintenanceFilter;
use App\Filters\PermissionFilter;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
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

        // Filter kustom SisisFour — acuan 03_AUTH_RBAC_MENU §3.1
        'auth'        => AuthFilter::class,
        'permission'  => PermissionFilter::class,
        'maintenance' => MaintenanceFilter::class,
    ];

    /**
     * Filter global yang berlaku di semua route.
     * 'maintenance' dipasang global karena harus mengecualikan Admin secara
     * dinamis (dicek di dalam filter itu sendiri), bukan per-route.
     */
    public array $globals = [
        'before' => [
            'maintenance',
            // 'csrf', // aktifkan setelah semua form pakai csrf_field()
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public array $methods = [];

    /**
     * Filter per-grup route. 'auth' & 'permission' dipasang bersamaan di setiap
     * grup terproteksi (lihat Routes.php) — 'auth' mengecek session valid,
     * 'permission' mengecek permission_key + scope untuk route tersebut.
     */
    public array $filters = [];
}
