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
        // Built-in CodeIgniter filters.
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
        //
        // auth:api pada Routes.php berarti:
        // alias = auth
        // argument = api
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
     * Maintenance berlaku secara global.
     *
     * CSRF:
     * - Aktif untuk Web.
     * - Dikecualikan untuk seluruh endpoint API.
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
