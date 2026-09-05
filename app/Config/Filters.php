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
     * Filter aliases.
     */
    public array $aliases = [
        'csrf' => CSRF::class,
        'toolbar' => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'invalidchars' => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors' => Cors::class,
        'forcehttps' => ForceHTTPS::class,
        'pagecache' => PageCache::class,
        'performance' => PerformanceMetrics::class,

        // Custom SisisFour filters.
        'auth' => AuthFilter::class,
        'permission' => PermissionFilter::class,
        'maintenance' => MaintenanceFilter::class,
    ];

    /**
     * Required / global filters.
     */
    public array $required = [
        'before' => [
            'forcehttps',
        ],
        'after' => [
            'forcehttps',
        ],
    ];

    /**
     * Global filters.
     *
     * CSRF:
     * - aktif untuk Web
     * - dikecualikan untuk API
     *
     * API nanti menggunakan mekanisme autentikasi JWT
     * dan bukan CSRF session-based.
     */
    public array $globals = [
        'before' => [
            'maintenance',

            'csrf' => [
                'except' => [
                    'api/*',
                ],
            ],
        ],

        'after' => [
            'toolbar',
        ],
    ];

    /**
     * Method-specific filters.
     */
    public array $methods = [];

    /**
     * Route-specific filter exceptions.
     */
    public array $filters = [];
}
