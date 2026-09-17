<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->group('bk', static function ($routes) {
        $routes->get('konseling', 'BKKonseling::index', ['filter' => 'permission:bk_konseling.view']);
        $routes->get('konseling/json', 'BKKonseling::index', ['filter' => 'permission:bk_konseling.view']);
        $routes->get('konseling/settings', 'BKKonselingSettings::index', ['filter' => 'permission:bk_konseling.settings']);
        $routes->post('konseling/settings', 'BKKonselingSettings::update', ['filter' => 'permission:bk_konseling.settings']);
        $routes->post('konseling/settings/reset', 'BKKonselingSettings::reset', ['filter' => 'permission:bk_konseling.settings']);
        $routes->get('konseling/siswa-kelas/(:num)', 'BKKonseling::students/$1', ['filter' => 'permission:bk_konseling.manage']);
        $routes->get('konseling/detail/(:num)', 'BKKonseling::detail/$1', ['filter' => 'permission:bk_konseling.view']);
        $routes->post('konseling/create', 'BKKonseling::create', ['filter' => 'permission:bk_konseling.manage']);
        $routes->put('konseling/update/(:num)', 'BKKonseling::update/$1', ['filter' => 'permission:bk_konseling.manage']);
        $routes->post('konseling/(:num)/tindak-lanjut', 'BKKonseling::createFollowUp/$1', ['filter' => 'permission:bk_konseling.manage']);
        $routes->put('konseling/tindak-lanjut/(:num)', 'BKKonseling::updateFollowUp/$1', ['filter' => 'permission:bk_konseling.manage']);
        $routes->get('konseling/export', 'BKKonseling::export', ['filter' => 'permission:bk_konseling.export']);
    });
});
