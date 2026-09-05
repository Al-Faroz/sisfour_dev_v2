<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Konvensi filter:
 * - 'auth'                => Session-based (web)
 * - 'auth:api'            => JWT-based (mobile)
 * - 'permission:key'      => Cek 1 permission_key
 * - 'permission:key1,key2' => OR
 *
 * Dual-Output:
 * Semua controller dapat mendukung ?format=json
 * atau endpoint /json sesuai kebutuhan controller.
 */

// ================================================================
// 1. AUTENTIKASI (WEB)
// ================================================================

/*
 * Halaman utama -> Login
 */
$routes->get(
    '/',
    'Auth::login'
);

/*
 * Login page
 *
 * GET  -> tampilkan form
 * POST -> proses login
 */
$routes->get(
    'auth/login',
    'Auth::login'
);

$routes->post(
    'auth/login',
    'Auth::login'
);

/*
 * Logout
 *
 * WAJIB POST.
 *
 * Auth filter memastikan request berasal dari
 * session yang masih valid.
 *
 * CSRF global akan melindungi POST ini.
 */
$routes->post(
    'auth/logout',
    'Auth::logout',
    [
        'filter' => 'auth',
    ]
);

// ================================================================
// 2. AUTENTIKASI API (MOBILE)
// ================================================================

$routes->group('api', function ($routes) {

    $routes->post(
        'auth/login',
        'Auth::apiLogin'
    );

    $routes->post(
        'auth/logout',
        'Auth::apiLogout',
        [
            'filter' => 'auth:api',
        ]
    );

    $routes->get(
        'auth/me',
        'Auth::apiMe',
        [
            'filter' => 'auth:api',
        ]
    );

    $routes->post(
        'auth/refresh',
        'Auth::apiRefresh'
    );

    $routes->get(
        'version',
        'Api::version'
    );
});

// ================================================================
// 3. PUBLIC
// ================================================================

$routes->get(
    'kartu/verify/(:segment)',
    'KartuPelajar::verify/$1'
);

// ================================================================
// 4. PROTECTED WEB
// ================================================================

$routes->group(
    '',
    [
        'filter' => 'auth',
    ],
    static function ($routes) {

        // ========================================================
        // SEMUA ROUTE LAMA MULAI DARI DASHBOARD
        // TETAP DI SINI
        // ========================================================

        $routes->get(
            'dashboard',
            'Dashboard::index',
            [
                'filter' => 'permission:dashboard.view',
            ]
        );

        $routes->get(
            'dashboard/data',
            'Dashboard::data',
            [
                'filter' => 'permission:dashboard.view',
            ]
        );
        // ------------------------------------------------------------
        // 4.1 Dashboard
        // ------------------------------------------------------------
        $routes->get('dashboard', 'Dashboard::index', ['filter' => 'permission:dashboard.view']);
        $routes->get('dashboard/data', 'Dashboard::data', ['filter' => 'permission:dashboard.view']);

        // ------------------------------------------------------------
        // 4.2 Master Data — Prefix: master/
        // ------------------------------------------------------------
        $routes->group('master', static function ($routes) {

            // 4.2.1 Guru
            $routes->get('guru', 'MasterGuru::index', ['filter' => 'permission:master_guru.manage,master_guru.view']);
            $routes->get('guru/json', 'MasterGuru::index', ['filter' => 'permission:master_guru.manage,master_guru.view']);
            $routes->get('guru/template', 'MasterGuru::downloadTemplate', ['filter' => 'permission:master_guru.manage']);
            $routes->post('guru/create', 'MasterGuru::create', ['filter' => 'permission:master_guru.manage']);
            $routes->post('guru/import', 'MasterGuru::import', ['filter' => 'permission:master_guru.manage']);
            $routes->get('guru/export', 'MasterGuru::export', ['filter' => 'permission:master_guru.manage']);
            $routes->put('guru/update/(:segment)', 'MasterGuru::update/$1', ['filter' => 'permission:master_guru.manage']);
            $routes->delete('guru/delete/(:segment)', 'MasterGuru::delete/$1', ['filter' => 'permission:master_guru.manage']);

            // 4.2.2 Pegawai
            $routes->get('pegawai', 'MasterPegawai::index', ['filter' => 'permission:master_pegawai.manage,master_pegawai.view']);
            $routes->get('pegawai/json', 'MasterPegawai::index', ['filter' => 'permission:master_pegawai.manage,master_pegawai.view']);
            $routes->get('pegawai/template', 'MasterPegawai::downloadTemplate', ['filter' => 'permission:master_pegawai.manage']);
            $routes->post('pegawai/create', 'MasterPegawai::create', ['filter' => 'permission:master_pegawai.manage']);
            $routes->post('pegawai/import', 'MasterPegawai::import', ['filter' => 'permission:master_pegawai.manage']);
            $routes->get('pegawai/export', 'MasterPegawai::export', ['filter' => 'permission:master_pegawai.manage']);
            $routes->put('pegawai/update/(:segment)', 'MasterPegawai::update/$1', ['filter' => 'permission:master_pegawai.manage']);
            $routes->delete('pegawai/delete/(:segment)', 'MasterPegawai::delete/$1', ['filter' => 'permission:master_pegawai.manage']);

            // 4.2.3 Siswa
            $routes->get('siswa', 'MasterSiswa::index', ['filter' => 'permission:master_siswa.view']);
            $routes->get('siswa/json', 'MasterSiswa::index', ['filter' => 'permission:master_siswa.view']);
            $routes->get('siswa/template', 'MasterSiswa::downloadTemplate', ['filter' => 'permission:master_siswa.import_export']);
            $routes->post('siswa/create', 'MasterSiswa::create', ['filter' => 'permission:master_siswa.manage']);
            $routes->post('siswa/import', 'MasterSiswa::import', ['filter' => 'permission:master_siswa.import_export']);
            $routes->get('siswa/export', 'MasterSiswa::export', ['filter' => 'permission:master_siswa.import_export']);
            $routes->put('siswa/update/(:segment)', 'MasterSiswa::update/$1', ['filter' => 'permission:master_siswa.edit_biodata']);
            $routes->delete('siswa/delete/(:segment)', 'MasterSiswa::delete/$1', ['filter' => 'permission:master_siswa.manage']);
            $routes->post('siswa/mutasi', 'MasterSiswa::mutasi', ['filter' => 'permission:master_siswa.manage']);
            $routes->post('siswa/upload-foto/(:segment)', 'MasterSiswa::uploadFoto/$1', ['filter' => 'permission:master_siswa.edit_biodata']);

            // 4.2.4 Kelas
            $routes->get('kelas', 'MasterKelas::index', ['filter' => 'permission:master_kelas.manage']);
            $routes->get('kelas/json', 'MasterKelas::index', ['filter' => 'permission:master_kelas.manage']);
            $routes->post('kelas/create', 'MasterKelas::create', ['filter' => 'permission:master_kelas.manage']);
            $routes->put('kelas/update/(:segment)', 'MasterKelas::update/$1', ['filter' => 'permission:master_kelas.manage']);
            $routes->delete('kelas/delete/(:segment)', 'MasterKelas::delete/$1', ['filter' => 'permission:master_kelas.manage']);
            $routes->post('kelas/naik/(:segment)', 'MasterKelas::naik/$1', ['filter' => 'permission:master_kelas.manage']);
            $routes->post('kelas/lulus/(:segment)', 'MasterKelas::lulus/$1', ['filter' => 'permission:master_kelas.manage']);

            // 4.2.5 Tahun Ajaran
            $routes->get('tahun', 'MasterTahunAjaran::index', ['filter' => 'permission:master_tahun_ajaran.manage']);
            $routes->get('tahun/json', 'MasterTahunAjaran::index', ['filter' => 'permission:master_tahun_ajaran.manage']);
            $routes->post('tahun/create', 'MasterTahunAjaran::create', ['filter' => 'permission:master_tahun_ajaran.manage']);
            $routes->post('tahun/aktifkan/(:segment)', 'MasterTahunAjaran::aktifkan/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);
            $routes->delete('tahun/delete/(:segment)', 'MasterTahunAjaran::delete/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);

            // 4.2.6 Mata Pelajaran
            $routes->get('mapel', 'MasterMapel::index', ['filter' => 'permission:master_mapel.manage']);
            $routes->get('mapel/json', 'MasterMapel::index', ['filter' => 'permission:master_mapel.manage']);
            $routes->post('mapel/create', 'MasterMapel::create', ['filter' => 'permission:master_mapel.manage']);
            $routes->put('mapel/update/(:segment)', 'MasterMapel::update/$1', ['filter' => 'permission:master_mapel.manage']);
            $routes->delete('mapel/delete/(:segment)', 'MasterMapel::delete/$1', ['filter' => 'permission:master_mapel.manage']);

            // 4.2.7 Mapping Wali Kelas
            $routes->get('wali-kelas', 'MappingWaliKelas::index', ['filter' => 'permission:mapping_wali.view,mapping_wali.manage,mapping_wali.view_all']);
            $routes->get('wali-kelas/json', 'MappingWaliKelas::index', ['filter' => 'permission:mapping_wali.view,mapping_wali.manage,mapping_wali.view_all']);
            $routes->post('wali-kelas/assign', 'MappingWaliKelas::assign', ['filter' => 'permission:mapping_wali.manage']);
            $routes->delete('wali-kelas/delete/(:segment)', 'MappingWaliKelas::delete/$1', ['filter' => 'permission:mapping_wali.manage']);

            // 4.2.8 Jadwal Guru
            $routes->get('jadwal', 'JadwalGuru::index', ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']);
            $routes->get('jadwal/json', 'JadwalGuru::index', ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']);
            $routes->get('jadwal/template', 'JadwalGuru::downloadTemplate', ['filter' => 'permission:jadwal_guru.manage']);
            $routes->post('jadwal/import', 'JadwalGuru::import', ['filter' => 'permission:jadwal_guru.manage']);
            $routes->delete('jadwal/delete/(:segment)', 'JadwalGuru::delete/$1', ['filter' => 'permission:jadwal_guru.manage']);
        });

        // ------------------------------------------------------------
        // 4.3 Presensi — Prefix: presensi/
        // ------------------------------------------------------------
        $routes->group('presensi', static function ($routes) {

            // 4.3.1 Presensi Siswa
            $routes->get('siswa', 'PresensiSiswa::index', ['filter' => 'permission:presensi_siswa.input']);
            $routes->get('siswa/input/(:segment)', 'PresensiSiswa::input/$1', ['filter' => 'permission:presensi_siswa.input']);
            $routes->get('siswa/input/(:segment)/json', 'PresensiSiswa::input/$1', ['filter' => 'permission:presensi_siswa.input']);
            $routes->post('siswa/save', 'PresensiSiswa::save', ['filter' => 'permission:presensi_siswa.input']);
            $routes->put('siswa/revisi/(:segment)', 'PresensiSiswa::revisi/$1', ['filter' => 'permission:presensi_siswa.revisi']);
            $routes->get('siswa/ews', 'PresensiSiswa::ews', ['filter' => 'permission:ews_radar.view']);
            $routes->get('siswa/ews/json', 'PresensiSiswa::ews', ['filter' => 'permission:ews_radar.view']);
            $routes->get('siswa/rekap/(:segment)', 'PresensiSiswa::rekap/$1', ['filter' => 'permission:presensi_siswa.view']);
            $routes->get('siswa/rekap/(:segment)/json', 'PresensiSiswa::rekap/$1', ['filter' => 'permission:presensi_siswa.view']);

            // 4.3.2 Presensi Mengajar
            $routes->get('mengajar', 'PresensiMengajar::index', ['filter' => 'permission:presensi_mengajar.input']);
            $routes->get('mengajar/input/(:segment)', 'PresensiMengajar::input/$1', ['filter' => 'permission:presensi_mengajar.input']);
            $routes->get('mengajar/input/(:segment)/json', 'PresensiMengajar::input/$1', ['filter' => 'permission:presensi_mengajar.input']);
            $routes->post('mengajar/save', 'PresensiMengajar::save', ['filter' => 'permission:presensi_mengajar.input']);
            $routes->get('mengajar/laporan', 'PresensiMengajar::laporan', ['filter' => 'permission:presensi_mengajar.view']);
            $routes->get('mengajar/laporan/json', 'PresensiMengajar::laporan', ['filter' => 'permission:presensi_mengajar.view']);
        });

        // ------------------------------------------------------------
        // 4.4 Laporan & Export — Prefix: laporan/
        // ------------------------------------------------------------
        $routes->group('laporan', static function ($routes) {
            $routes->get('presensi/matrix', 'LaporanPresensi::matrix', ['filter' => 'permission:laporan_matrix.view']);
            $routes->get('presensi/matrix/json', 'LaporanPresensi::matrix', ['filter' => 'permission:laporan_matrix.view']);
            $routes->get('presensi/export', 'LaporanPresensi::export', ['filter' => 'permission:laporan_export.generate']);
            $routes->get('presensi/export/json', 'LaporanPresensi::export', ['filter' => 'permission:laporan_export.generate']);
            $routes->get('jurnal', 'LaporanJurnal::index', ['filter' => 'permission:laporan_jurnal.view']);
            $routes->get('jurnal/json', 'LaporanJurnal::index', ['filter' => 'permission:laporan_jurnal.view']);
            $routes->get('jurnal/export', 'LaporanJurnal::export', ['filter' => 'permission:laporan_jurnal.export']);
        });

        // ------------------------------------------------------------
        // 4.5 BK & Prestasi — Prefix: bk/
        // ------------------------------------------------------------
        $routes->group('bk', static function ($routes) {
            $routes->get('kasus', 'BKKasus::index', ['filter' => 'permission:bk_kasus.view']);
            $routes->get('kasus/json', 'BKKasus::index', ['filter' => 'permission:bk_kasus.view']);
            $routes->post('kasus/create', 'BKKasus::create', ['filter' => 'permission:bk_kasus.manage']);
            $routes->get('kasus/top', 'BKKasus::top', ['filter' => 'permission:bk_kasus.view']);
            $routes->get('kasus/top/json', 'BKKasus::top', ['filter' => 'permission:bk_kasus.view']);
            $routes->get('kasus/export', 'BKKasus::export', ['filter' => 'permission:bk_kasus.manage']);

            $routes->get('pelanggaran', 'BKPelanggaran::index', ['filter' => 'permission:bk_pelanggaran_master.manage']);
            $routes->get('pelanggaran/json', 'BKPelanggaran::index', ['filter' => 'permission:bk_pelanggaran_master.manage']);
            $routes->post('pelanggaran/create', 'BKPelanggaran::create', ['filter' => 'permission:bk_pelanggaran_master.manage']);
            $routes->put('pelanggaran/update/(:segment)', 'BKPelanggaran::update/$1', ['filter' => 'permission:bk_pelanggaran_master.manage']);
            $routes->delete('pelanggaran/delete/(:segment)', 'BKPelanggaran::delete/$1', ['filter' => 'permission:bk_pelanggaran_master.manage']);

            $routes->get('prestasi', 'BKPrestasi::index', ['filter' => 'permission:prestasi.view']);
            $routes->get('prestasi/json', 'BKPrestasi::index', ['filter' => 'permission:prestasi.view']);
            $routes->post('prestasi/create', 'BKPrestasi::create', ['filter' => 'permission:prestasi.manage']);
            $routes->get('prestasi/export', 'BKPrestasi::export', ['filter' => 'permission:prestasi.manage']);
        });

        // ------------------------------------------------------------
        // 4.6 Kartu Pelajar — Prefix: kartu/
        // ------------------------------------------------------------
        $routes->group('kartu', static function ($routes) {
            $routes->get('daftar', 'KartuPelajar::daftar', ['filter' => 'permission:kartu_pelajar.view']);
            $routes->get('daftar/json', 'KartuPelajar::daftar', ['filter' => 'permission:kartu_pelajar.view']);
            $routes->post('generate', 'KartuPelajar::generate', ['filter' => 'permission:kartu_pelajar.manage']);
            $routes->get('cetak/(:segment)', 'KartuPelajar::cetak/$1', ['filter' => 'permission:kartu_pelajar.manage']);
            $routes->get('preview/(:segment)', 'KartuPelajar::preview/$1', ['filter' => 'permission:kartu_pelajar.view']);
            $routes->get('preview/(:segment)/json', 'KartuPelajar::preview/$1', ['filter' => 'permission:kartu_pelajar.view']);
            $routes->get('download/(:segment)', 'KartuPelajar::download/$1', ['filter' => 'permission:kartu_pelajar.view']);
            $routes->post('reissue/(:segment)', 'KartuPelajar::reissue/$1', ['filter' => 'permission:kartu_pelajar.manage']);
        });

        // ------------------------------------------------------------
        // 4.7 Profile — Prefix: profile/
        // ------------------------------------------------------------
        $routes->group('profile', static function ($routes) {
            $routes->get('guru', 'ProfileGuru::index', ['filter' => 'permission:profile_guru.view']);
            $routes->get('guru/json', 'ProfileGuru::index', ['filter' => 'permission:profile_guru.view']);
            $routes->put('guru/update', 'ProfileGuru::update', ['filter' => 'permission:profile_guru.edit']);
            $routes->post('guru/upload-foto', 'ProfileGuru::uploadFoto', ['filter' => 'permission:profile_guru.edit']);
            $routes->get('siswa', 'ProfileSiswa::index', ['filter' => 'permission:profile_siswa.view']);
            $routes->get('siswa/json', 'ProfileSiswa::index', ['filter' => 'permission:profile_siswa.view']);
        });

        // ------------------------------------------------------------
        // 4.8 Settings — Prefix: settings/ — Hanya Admin
        // ------------------------------------------------------------
        $routes->group('settings', static function ($routes) {
            $routes->get('user', 'SettingsUser::index', ['filter' => 'permission:settings_user.manage']);
            $routes->get('user/json', 'SettingsUser::index', ['filter' => 'permission:settings_user.manage']);
            $routes->post('user/create', 'SettingsUser::create', ['filter' => 'permission:settings_user.manage']);
            $routes->put('user/update/(:segment)', 'SettingsUser::update/$1', ['filter' => 'permission:settings_user.manage']);
            $routes->post('user/reset/(:segment)', 'SettingsUser::reset/$1', ['filter' => 'permission:settings_user.manage']);
            $routes->delete('user/delete/(:segment)', 'SettingsUser::delete/$1', ['filter' => 'permission:settings_user.manage']);

            $routes->get('menu', 'SettingsMenu::index', ['filter' => 'permission:settings_menu.manage']);
            $routes->get('menu/json', 'SettingsMenu::index', ['filter' => 'permission:settings_menu.manage']);
            $routes->put('menu/update', 'SettingsMenu::update', ['filter' => 'permission:settings_menu.manage']);

            $routes->get('sistem', 'SettingsSistem::index', ['filter' => 'permission:settings_sistem.manage']);
            $routes->get('sistem/json', 'SettingsSistem::index', ['filter' => 'permission:settings_sistem.manage']);
            $routes->put('sistem/update', 'SettingsSistem::update', ['filter' => 'permission:settings_sistem.manage']);
            $routes->post('sistem/maintenance', 'SettingsSistem::maintenance', ['filter' => 'permission:settings_sistem.manage']);
            $routes->post('sistem/upload-branding', 'SettingsSistem::uploadBranding', ['filter' => 'permission:settings_sistem.manage']);
            $routes->post('sistem/upload-background-kta', 'SettingsSistem::uploadBackgroundKta', ['filter' => 'permission:settings_sistem.manage']);
        });

        // ------------------------------------------------------------
        // 4.9 Backup & Log Activity — Hanya Admin
        // ------------------------------------------------------------
        $routes->get('backup', 'Backup::index', ['filter' => 'permission:backup.manage']);
        $routes->post('backup/generate', 'Backup::generate', ['filter' => 'permission:backup.manage']);
        $routes->get('backup/download/(:segment)', 'Backup::download/$1', ['filter' => 'permission:backup.manage']);
        $routes->delete('backup/delete/(:segment)', 'Backup::delete/$1', ['filter' => 'permission:backup.manage']);

        $routes->get('log/activity', 'LogActivity::index', ['filter' => 'permission:log_activity.view']);
        $routes->get('log/activity/json', 'LogActivity::index', ['filter' => 'permission:log_activity.view']);
        $routes->get('log/activity/export', 'LogActivity::export', ['filter' => 'permission:log_activity.view']);
    }
);

// ================================================================
// 5. API MOBILE (auth:api) — Prefix: api/
// ================================================================
$routes->group('api', ['filter' => 'auth:api'], static function ($routes) {

    $routes->get('dashboard/data', 'Dashboard::data', ['filter' => 'permission:dashboard.view']);

    $routes->get('presensi/siswa/input/(:segment)', 'PresensiSiswa::input/$1', ['filter' => 'permission:presensi_siswa.input']);
    $routes->post('presensi/siswa/save', 'PresensiSiswa::save', ['filter' => 'permission:presensi_siswa.input']);
    $routes->get('presensi/siswa/ews', 'PresensiSiswa::ews', ['filter' => 'permission:ews_radar.view']);
    $routes->get('presensi/siswa/rekap/(:segment)', 'PresensiSiswa::rekap/$1', ['filter' => 'permission:presensi_siswa.view']);

    $routes->get('presensi/mengajar/input/(:segment)', 'PresensiMengajar::input/$1', ['filter' => 'permission:presensi_mengajar.input']);
    $routes->post('presensi/mengajar/save', 'PresensiMengajar::save', ['filter' => 'permission:presensi_mengajar.input']);
    $routes->get('presensi/mengajar/laporan', 'PresensiMengajar::laporan', ['filter' => 'permission:presensi_mengajar.view']);

    $routes->get('laporan/presensi/matrix', 'LaporanPresensi::matrix', ['filter' => 'permission:laporan_matrix.view']);
    $routes->get('laporan/jurnal', 'LaporanJurnal::index', ['filter' => 'permission:laporan_jurnal.view']);

    $routes->get('bk/kasus', 'BKKasus::index', ['filter' => 'permission:bk_kasus.view']);
    $routes->post('bk/kasus/create', 'BKKasus::create', ['filter' => 'permission:bk_kasus.manage']);
    $routes->get('bk/kasus/top', 'BKKasus::top', ['filter' => 'permission:bk_kasus.view']);

    $routes->get('bk/prestasi', 'BKPrestasi::index', ['filter' => 'permission:prestasi.view']);
    $routes->post('bk/prestasi/create', 'BKPrestasi::create', ['filter' => 'permission:prestasi.manage']);

    $routes->get('kartu/preview/(:segment)', 'KartuPelajar::preview/$1', ['filter' => 'permission:kartu_pelajar.view']);
    $routes->get('kartu/download/(:segment)', 'KartuPelajar::download/$1', ['filter' => 'permission:kartu_pelajar.view']);

    $routes->get('profile/guru', 'ProfileGuru::index', ['filter' => 'permission:profile_guru.view']);
    $routes->put('profile/guru', 'ProfileGuru::update', ['filter' => 'permission:profile_guru.edit']);
    $routes->post('profile/guru/foto', 'ProfileGuru::uploadFoto', ['filter' => 'permission:profile_guru.edit']);

    $routes->get('profile/siswa', 'ProfileSiswa::index', ['filter' => 'permission:profile_siswa.view']);
});

// ================================================================
// 6. 404 CATCH-ALL
// ================================================================
// Ditangani otomatis oleh CI4 melalui Config\Routes::set404Override()
// atau default handler bawaan framework — tidak perlu didefinisikan manual di sini.
