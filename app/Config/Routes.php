<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * ROUTES FINAL — SISISFOUR
 *
 * Prinsip:
 * - AutoRoute tidak digunakan.
 * - Semua route ditulis eksplisit.
 * - Web protected memakai filter auth.
 * - API protected memakai filter auth:api.
 * - Permission ditetapkan di route.
 * - Scope data tetap ditegakkan oleh Service/Model.
 * - Wali Kelas bukan role; status wali ditentukan dinamis.
 * - Endpoint JSON memakai permission yang sama dengan endpoint HTML.
 * - Tidak boleh ada deklarasi route yang sama dua kali.
 */

// ============================================================================
// 1. AUTENTIKASI WEB
// ============================================================================

$routes->get('/', 'Auth::login');
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/login', 'Auth::login');

$routes->post(
    'auth/logout',
    'Auth::logout',
    ['filter' => 'auth']
);

// ============================================================================
// 2. AUTENTIKASI API / MOBILE
// ============================================================================

$routes->group('api', static function ($routes) {
    $routes->post('auth/login', 'Auth::apiLogin');

    $routes->post(
        'auth/logout',
        'Auth::apiLogout',
        ['filter' => 'auth:api']
    );

    $routes->get(
        'auth/me',
        'Auth::apiMe',
        ['filter' => 'auth:api']
    );

    $routes->post('auth/refresh', 'Auth::apiRefresh');
    $routes->get('version', 'Api::version');
});

// ============================================================================
// 3. PUBLIC
// ============================================================================

$routes->get(
    'kartu/verify/(:segment)',
    'KartuPelajar::verify/$1'
);

// ============================================================================
// 4. PROTECTED WEB
// ============================================================================

$routes->group(
    '',
    ['filter' => 'auth'],
    static function ($routes) {

        // ====================================================================
        // 4.1 DASHBOARD
        // ====================================================================

        $routes->get(
            'dashboard',
            'Dashboard::index',
            ['filter' => 'permission:dashboard.view']
        );

        $routes->get(
            'dashboard/data',
            'Dashboard::data',
            ['filter' => 'permission:dashboard.view']
        );

        // ====================================================================
        // 4.2 MASTER DATA
        // Prefix: master/
        // ====================================================================

        $routes->group('master', static function ($routes) {

            // ----------------------------------------------------------------
            // 4.2.1 MASTER GURU
            // ----------------------------------------------------------------

            $routes->get(
                'guru',
                'MasterGuru::index',
                ['filter' => 'permission:master_guru.manage,master_guru.view']
            );

            $routes->get(
                'guru/json',
                'MasterGuru::index',
                ['filter' => 'permission:master_guru.manage,master_guru.view']
            );

            $routes->get(
                'guru/template',
                'MasterGuru::downloadTemplate',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->post(
                'guru/create',
                'MasterGuru::create',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->post(
                'guru/import',
                'MasterGuru::import',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->get(
                'guru/export',
                'MasterGuru::export',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->put(
                'guru/update/(:segment)',
                'MasterGuru::update/$1',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->delete(
                'guru/delete/(:segment)',
                'MasterGuru::delete/$1',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->post(
                'guru/upload-foto/(:segment)',
                'MasterGuru::uploadFoto/$1',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->get(
                'guru/recycle',
                'MasterGuru::recycle',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->get(
                'guru/recycle/json',
                'MasterGuru::recycle',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->post(
                'guru/restore/(:segment)',
                'MasterGuru::restore/$1',
                ['filter' => 'permission:master_guru.manage']
            );

            $routes->delete(
                'guru/force-delete/(:segment)',
                'MasterGuru::forceDelete/$1',
                ['filter' => 'permission:master_guru.manage']
            );

            // ----------------------------------------------------------------
            // 4.2.2 MASTER PEGAWAI
            // ----------------------------------------------------------------

            $routes->get(
                'pegawai',
                'MasterPegawai::index',
                ['filter' => 'permission:master_pegawai.manage,master_pegawai.view']
            );

            $routes->get(
                'pegawai/json',
                'MasterPegawai::index',
                ['filter' => 'permission:master_pegawai.manage,master_pegawai.view']
            );

            $routes->get(
                'pegawai/template',
                'MasterPegawai::downloadTemplate',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->post(
                'pegawai/create',
                'MasterPegawai::create',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->post(
                'pegawai/import',
                'MasterPegawai::import',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->get(
                'pegawai/export',
                'MasterPegawai::export',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->put(
                'pegawai/update/(:segment)',
                'MasterPegawai::update/$1',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->delete(
                'pegawai/delete/(:segment)',
                'MasterPegawai::delete/$1',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->get(
                'pegawai/recycle',
                'MasterPegawai::recycle',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->get(
                'pegawai/recycle/json',
                'MasterPegawai::recycle',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->post(
                'pegawai/restore/(:segment)',
                'MasterPegawai::restore/$1',
                ['filter' => 'permission:master_pegawai.manage']
            );

            $routes->delete(
                'pegawai/force-delete/(:segment)',
                'MasterPegawai::forceDelete/$1',
                ['filter' => 'permission:master_pegawai.manage']
            );

            // ----------------------------------------------------------------
            // 4.2.3 MASTER SISWA
            // ----------------------------------------------------------------

            $routes->get(
                'siswa',
                'MasterSiswa::index',
                ['filter' => 'permission:master_siswa.view']
            );

            $routes->get(
                'siswa/json',
                'MasterSiswa::index',
                ['filter' => 'permission:master_siswa.view']
            );

            $routes->get(
                'siswa/template',
                'MasterSiswa::downloadTemplate',
                ['filter' => 'permission:master_siswa.import_export']
            );

            $routes->post(
                'siswa/create',
                'MasterSiswa::create',
                ['filter' => 'permission:master_siswa.manage']
            );

            $routes->post(
                'siswa/import',
                'MasterSiswa::import',
                ['filter' => 'permission:master_siswa.import_export']
            );

            $routes->get(
                'siswa/export',
                'MasterSiswa::export',
                ['filter' => 'permission:master_siswa.import_export']
            );

            $routes->put(
                'siswa/update/(:segment)',
                'MasterSiswa::update/$1',
                ['filter' => 'permission:master_siswa.edit_biodata']
            );

            $routes->delete(
                'siswa/delete/(:segment)',
                'MasterSiswa::delete/$1',
                ['filter' => 'permission:master_siswa.manage']
            );

            $routes->get(
                'siswa/recycle',
                'MasterSiswa::recycle',
                ['filter' => 'permission:master_siswa.manage']
            );

            $routes->get(
                'siswa/recycle/json',
                'MasterSiswa::recycle',
                ['filter' => 'permission:master_siswa.manage']
            );

            $routes->post(
                'siswa/restore/(:segment)',
                'MasterSiswa::restore/$1',
                ['filter' => 'permission:master_siswa.manage']
            );

            $routes->delete(
                'siswa/force-delete/(:segment)',
                'MasterSiswa::forceDelete/$1',
                ['filter' => 'permission:master_siswa.manage']
            );

            $routes->post(
                'siswa/mutasi/(:segment)',
                'MasterSiswa::mutasi/$1',
                ['filter' => 'permission:master_siswa.manage']
            );

            // Kebutuhan biodata/foto siswa pada dokumen Master Data.
            $routes->post(
                'siswa/upload-foto/(:segment)',
                'MasterSiswa::uploadFoto/$1',
                ['filter' => 'permission:master_siswa.edit_biodata']
            );

            // ----------------------------------------------------------------
            // 4.2.4 MASTER KELAS
            // ----------------------------------------------------------------

            $routes->get(
                'kelas',
                'MasterKelas::index',
                ['filter' => 'permission:master_kelas.manage']
            );

            $routes->get(
                'kelas/json',
                'MasterKelas::index',
                ['filter' => 'permission:master_kelas.manage']
            );

            $routes->post(
                'kelas/create',
                'MasterKelas::create',
                ['filter' => 'permission:master_kelas.manage']
            );

            $routes->put(
                'kelas/update/(:segment)',
                'MasterKelas::update/$1',
                ['filter' => 'permission:master_kelas.manage']
            );

            $routes->delete(
                'kelas/delete/(:segment)',
                'MasterKelas::delete/$1',
                ['filter' => 'permission:master_kelas.manage']
            );

            // Proses administratif siswa per kelas.
            $routes->post(
                'kelas/naik/(:segment)',
                'MasterKelas::naik/$1',
                ['filter' => 'permission:master_kelas.manage']
            );

            $routes->post(
                'kelas/lulus/(:segment)',
                'MasterKelas::lulus/$1',
                ['filter' => 'permission:master_kelas.manage']
            );

            // ----------------------------------------------------------------
            // 4.2.5 TAHUN AJARAN
            // ----------------------------------------------------------------

            $routes->get(
                'tahun',
                'MasterTahunAjaran::index',
                ['filter' => 'permission:master_tahun_ajaran.manage']
            );

            $routes->get(
                'tahun/json',
                'MasterTahunAjaran::index',
                ['filter' => 'permission:master_tahun_ajaran.manage']
            );

            $routes->post(
                'tahun/create',
                'MasterTahunAjaran::create',
                ['filter' => 'permission:master_tahun_ajaran.manage']
            );

            $routes->put(
                'tahun/update/(:segment)',
                'MasterTahunAjaran::update/$1',
                ['filter' => 'permission:master_tahun_ajaran.manage']
            );

            // Endpoint eksplisit untuk menjadikan satu tahun/semester aktif.
            $routes->post(
                'tahun/aktifkan/(:segment)',
                'MasterTahunAjaran::aktifkan/$1',
                ['filter' => 'permission:master_tahun_ajaran.manage']
            );

            $routes->delete(
                'tahun/delete/(:segment)',
                'MasterTahunAjaran::delete/$1',
                ['filter' => 'permission:master_tahun_ajaran.manage']
            );

            // ----------------------------------------------------------------
            // 4.2.6 MATA PELAJARAN
            // ----------------------------------------------------------------

            $routes->get(
                'mapel',
                'MasterMapel::index',
                ['filter' => 'permission:master_mapel.manage']
            );

            $routes->get(
                'mapel/json',
                'MasterMapel::index',
                ['filter' => 'permission:master_mapel.manage']
            );

            $routes->post(
                'mapel/create',
                'MasterMapel::create',
                ['filter' => 'permission:master_mapel.manage']
            );

            $routes->put(
                'mapel/update/(:segment)',
                'MasterMapel::update/$1',
                ['filter' => 'permission:master_mapel.manage']
            );

            $routes->delete(
                'mapel/delete/(:segment)',
                'MasterMapel::delete/$1',
                ['filter' => 'permission:master_mapel.manage']
            );

            // ----------------------------------------------------------------
            // 4.2.7 MAPPING WALI KELAS
            // ----------------------------------------------------------------

            $routes->get(
                'wali-kelas',
                'MappingWaliKelas::index',
                ['filter' => 'permission:mapping_wali.view,mapping_wali.manage,mapping_wali.view_all']
            );

            $routes->get(
                'wali-kelas/json',
                'MappingWaliKelas::index',
                ['filter' => 'permission:mapping_wali.view,mapping_wali.manage,mapping_wali.view_all']
            );

            $routes->post(
                'wali-kelas/assign',
                'MappingWaliKelas::assign',
                ['filter' => 'permission:mapping_wali.manage']
            );

            $routes->delete(
                'wali-kelas/delete/(:segment)',
                'MappingWaliKelas::delete/$1',
                ['filter' => 'permission:mapping_wali.manage']
            );

            // ----------------------------------------------------------------
            // 4.2.8 JADWAL GURU
            // ----------------------------------------------------------------

            $routes->get(
                'jadwal',
                'JadwalGuru::index',
                ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']
            );

            $routes->get(
                'jadwal/json',
                'JadwalGuru::index',
                ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']
            );

            $routes->get(
                'jadwal/template',
                'JadwalGuru::downloadTemplate',
                ['filter' => 'permission:jadwal_guru.manage']
            );

            $routes->post(
                'jadwal/import',
                'JadwalGuru::import',
                ['filter' => 'permission:jadwal_guru.manage']
            );

            $routes->delete(
                'jadwal/delete/(:segment)',
                'JadwalGuru::delete/$1',
                ['filter' => 'permission:jadwal_guru.manage']
            );
        });

        // ====================================================================
        // 4.3 PRESENSI
        // Prefix: presensi/
        // ====================================================================

        $routes->group('presensi', static function ($routes) {

            // ----------------------------------------------------------------
            // 4.3.1 PRESENSI SISWA
            // ----------------------------------------------------------------

            $routes->get(
                'siswa',
                'PresensiSiswa::index',
                ['filter' => 'permission:presensi_siswa.input']
            );

            $routes->get(
                'siswa/input/(:segment)',
                'PresensiSiswa::input/$1',
                ['filter' => 'permission:presensi_siswa.input']
            );

            $routes->get(
                'siswa/input/(:segment)/json',
                'PresensiSiswa::input/$1',
                ['filter' => 'permission:presensi_siswa.input']
            );

            $routes->post(
                'siswa/save',
                'PresensiSiswa::save',
                ['filter' => 'permission:presensi_siswa.input']
            );

            $routes->get(
                'siswa/revisi/(:segment)',
                'PresensiSiswa::revisi/$1',
                ['filter' => 'permission:presensi_siswa.revisi']
            );

            $routes->post(
                'siswa/revisi/save',
                'PresensiSiswa::saveRevisi',
                ['filter' => 'permission:presensi_siswa.revisi']
            );

            $routes->get(
                'siswa/rekap',
                'PresensiSiswa::rekap',
                ['filter' => 'permission:presensi_siswa.view']
            );

            $routes->get(
                'siswa/rekap/json',
                'PresensiSiswa::rekap',
                ['filter' => 'permission:presensi_siswa.view']
            );

            $routes->get(
                'siswa/ews',
                'PresensiSiswa::ews',
                ['filter' => 'permission:ews_radar.view']
            );

            $routes->get(
                'siswa/ews/json',
                'PresensiSiswa::ews',
                ['filter' => 'permission:ews_radar.view']
            );

            // ----------------------------------------------------------------
            // 4.3.2 PRESENSI MENGAJAR / JURNAL
            // ----------------------------------------------------------------

            $routes->get(
                'mengajar',
                'PresensiMengajar::index',
                ['filter' => 'permission:presensi_mengajar.input']
            );

            $routes->get(
                'mengajar/input/(:segment)',
                'PresensiMengajar::input/$1',
                ['filter' => 'permission:presensi_mengajar.input']
            );

            $routes->get(
                'mengajar/input/(:segment)/json',
                'PresensiMengajar::input/$1',
                ['filter' => 'permission:presensi_mengajar.input']
            );

            $routes->post(
                'mengajar/save',
                'PresensiMengajar::save',
                ['filter' => 'permission:presensi_mengajar.input']
            );

            $routes->get(
                'mengajar/laporan',
                'PresensiMengajar::laporan',
                ['filter' => 'permission:presensi_mengajar.view']
            );

            $routes->get(
                'mengajar/laporan/json',
                'PresensiMengajar::laporan',
                ['filter' => 'permission:presensi_mengajar.view']
            );
        });

        // ====================================================================
        // 4.4 LAPORAN
        // Prefix: laporan/
        // ====================================================================

        $routes->group('laporan', static function ($routes) {

            // Matrix Presensi
            $routes->get(
                'presensi/matrix',
                'LaporanPresensi::matrix',
                ['filter' => 'permission:laporan_matrix.view']
            );

            $routes->get(
                'presensi/matrix/json',
                'LaporanPresensi::matrix',
                ['filter' => 'permission:laporan_matrix.view']
            );

            // Export Presensi
            $routes->get(
                'presensi/export',
                'LaporanPresensi::export',
                ['filter' => 'permission:laporan_export.generate']
            );

            $routes->get(
                'presensi/export/bulan',
                'LaporanPresensi::exportBulan',
                ['filter' => 'permission:laporan_export.generate']
            );

            $routes->get(
                'presensi/export/semester',
                'LaporanPresensi::exportSemester',
                ['filter' => 'permission:laporan_export.generate']
            );

            // Laporan Jurnal
            $routes->get(
                'jurnal',
                'LaporanJurnal::index',
                ['filter' => 'permission:laporan_jurnal.view']
            );

            $routes->get(
                'jurnal/json',
                'LaporanJurnal::index',
                ['filter' => 'permission:laporan_jurnal.view']
            );

            $routes->get(
                'jurnal/export',
                'LaporanJurnal::export',
                ['filter' => 'permission:laporan_jurnal.export']
            );
        });

        // ====================================================================
        // 4.5 BK & PRESTASI
        // Prefix: bk/
        // ====================================================================

        $routes->group('bk', static function ($routes) {

            // ----------------------------------------------------------------
            // 4.5.1 CATATAN KASUS
            // ----------------------------------------------------------------

            $routes->get(
                'kasus',
                'BKKasus::index',
                ['filter' => 'permission:bk_kasus.view']
            );

            $routes->get(
                'kasus/json',
                'BKKasus::index',
                ['filter' => 'permission:bk_kasus.view']
            );

            $routes->get(
                'kasus/top',
                'BKKasus::top',
                ['filter' => 'permission:bk_kasus.view']
            );

            $routes->get(
                'kasus/top/json',
                'BKKasus::top',
                ['filter' => 'permission:bk_kasus.view']
            );

            $routes->post(
                'kasus/create',
                'BKKasus::create',
                ['filter' => 'permission:bk_kasus.manage']
            );

            $routes->get(
                'kasus/export',
                'BKKasus::export',
                ['filter' => 'permission:bk_kasus.manage']
            );

            // ----------------------------------------------------------------
            // 4.5.2 MASTER PELANGGARAN
            // ----------------------------------------------------------------

            $routes->get(
                'pelanggaran',
                'BKPelanggaran::index',
                ['filter' => 'permission:bk_pelanggaran_master.manage']
            );

            $routes->get(
                'pelanggaran/json',
                'BKPelanggaran::index',
                ['filter' => 'permission:bk_pelanggaran_master.manage']
            );

            $routes->post(
                'pelanggaran/create',
                'BKPelanggaran::create',
                ['filter' => 'permission:bk_pelanggaran_master.manage']
            );

            $routes->put(
                'pelanggaran/update/(:segment)',
                'BKPelanggaran::update/$1',
                ['filter' => 'permission:bk_pelanggaran_master.manage']
            );

            $routes->delete(
                'pelanggaran/delete/(:segment)',
                'BKPelanggaran::delete/$1',
                ['filter' => 'permission:bk_pelanggaran_master.manage']
            );

            // ----------------------------------------------------------------
            // 4.5.3 PRESTASI SISWA
            // ----------------------------------------------------------------

            $routes->get(
                'prestasi',
                'BKPrestasi::index',
                ['filter' => 'permission:prestasi.view']
            );

            $routes->get(
                'prestasi/json',
                'BKPrestasi::index',
                ['filter' => 'permission:prestasi.view']
            );

            $routes->post(
                'prestasi/create',
                'BKPrestasi::create',
                ['filter' => 'permission:prestasi.manage']
            );

            $routes->put(
                'prestasi/update/(:segment)',
                'BKPrestasi::update/$1',
                ['filter' => 'permission:prestasi.manage']
            );

            $routes->delete(
                'prestasi/delete/(:segment)',
                'BKPrestasi::delete/$1',
                ['filter' => 'permission:prestasi.manage']
            );

            $routes->get(
                'prestasi/export',
                'BKPrestasi::export',
                ['filter' => 'permission:prestasi.view,prestasi.manage']
            );
        });

        // ====================================================================
        // 4.6 KARTU PELAJAR
        // Prefix: kartu/
        // ====================================================================

        $routes->group('kartu', static function ($routes) {

            $routes->get(
                'daftar',
                'KartuPelajar::daftar',
                ['filter' => 'permission:kartu_pelajar.view']
            );

            $routes->get(
                'daftar/json',
                'KartuPelajar::daftar',
                ['filter' => 'permission:kartu_pelajar.view']
            );

            $routes->post(
                'generate',
                'KartuPelajar::generate',
                ['filter' => 'permission:kartu_pelajar.manage']
            );

            $routes->get(
                'cetak/(:segment)',
                'KartuPelajar::cetak/$1',
                ['filter' => 'permission:kartu_pelajar.manage,kartu_pelajar.view']
            );

            $routes->get(
                'preview/(:segment)',
                'KartuPelajar::preview/$1',
                ['filter' => 'permission:kartu_pelajar.view']
            );

            $routes->get(
                'preview/(:segment)/json',
                'KartuPelajar::preview/$1',
                ['filter' => 'permission:kartu_pelajar.view']
            );

            $routes->get(
                'download/(:segment)',
                'KartuPelajar::download/$1',
                ['filter' => 'permission:kartu_pelajar.view']
            );

            $routes->post(
                'reissue/(:segment)',
                'KartuPelajar::reissue/$1',
                ['filter' => 'permission:kartu_pelajar.manage']
            );
        });

        // ====================================================================
        // 4.7 PROFILE
        // Prefix: profile/
        // ====================================================================

        $routes->group('profile', static function ($routes) {

            // Profile Guru
            $routes->get(
                'guru',
                'ProfileGuru::index',
                ['filter' => 'permission:profile_guru.view']
            );

            $routes->get(
                'guru/json',
                'ProfileGuru::index',
                ['filter' => 'permission:profile_guru.view']
            );

            $routes->put(
                'guru/update',
                'ProfileGuru::update',
                ['filter' => 'permission:profile_guru.edit']
            );

            $routes->post(
                'guru/upload-foto',
                'ProfileGuru::uploadFoto',
                ['filter' => 'permission:profile_guru.edit']
            );

            // Profile Siswa — readonly
            $routes->get(
                'siswa',
                'ProfileSiswa::index',
                ['filter' => 'permission:profile_siswa.view']
            );

            $routes->get(
                'siswa/json',
                'ProfileSiswa::index',
                ['filter' => 'permission:profile_siswa.view']
            );
        });

        // ====================================================================
        // 4.8 SETTINGS
        // Prefix: settings/
        // ====================================================================

        $routes->group('settings', static function ($routes) {

            // ----------------------------------------------------------------
            // 4.8.1 USER
            // ----------------------------------------------------------------

            $routes->get(
                'user',
                'SettingsUser::index',
                ['filter' => 'permission:settings_user.manage']
            );

            $routes->get(
                'user/json',
                'SettingsUser::index',
                ['filter' => 'permission:settings_user.manage']
            );

            $routes->post(
                'user/create',
                'SettingsUser::create',
                ['filter' => 'permission:settings_user.manage']
            );

            $routes->put(
                'user/update/(:segment)',
                'SettingsUser::update/$1',
                ['filter' => 'permission:settings_user.manage']
            );

            $routes->post(
                'user/reset/(:segment)',
                'SettingsUser::reset/$1',
                ['filter' => 'permission:settings_user.manage']
            );

            $routes->delete(
                'user/delete/(:segment)',
                'SettingsUser::delete/$1',
                ['filter' => 'permission:settings_user.manage']
            );

            // ----------------------------------------------------------------
            // 4.8.2 MENU & ROLE
            // ----------------------------------------------------------------

            $routes->get(
                'menu',
                'SettingsMenu::index',
                ['filter' => 'permission:settings_menu.manage']
            );

            $routes->get(
                'menu/json',
                'SettingsMenu::index',
                ['filter' => 'permission:settings_menu.manage']
            );

            $routes->put(
                'menu/update/(:segment)',
                'SettingsMenu::update/$1',
                ['filter' => 'permission:settings_menu.manage']
            );

            // ----------------------------------------------------------------
            // 4.8.3 SETTING SISTEM
            // ----------------------------------------------------------------

            $routes->get(
                'sistem',
                'SettingsSistem::index',
                ['filter' => 'permission:settings_sistem.manage']
            );

            $routes->get(
                'sistem/json',
                'SettingsSistem::index',
                ['filter' => 'permission:settings_sistem.manage']
            );

            $routes->put(
                'sistem/update',
                'SettingsSistem::update',
                ['filter' => 'permission:settings_sistem.manage']
            );

            // Endpoint operasional yang sudah menjadi kebutuhan Settings.
            $routes->post(
                'sistem/maintenance',
                'SettingsSistem::maintenance',
                ['filter' => 'permission:settings_sistem.manage']
            );

            $routes->post(
                'sistem/upload-branding',
                'SettingsSistem::uploadBranding',
                ['filter' => 'permission:settings_sistem.manage']
            );

            $routes->post(
                'sistem/upload-background-kta',
                'SettingsSistem::uploadBackgroundKta',
                ['filter' => 'permission:settings_sistem.manage']
            );
        });

        // ====================================================================
        // 4.9 BACKUP
        // ====================================================================

        $routes->get(
            'backup',
            'Backup::index',
            ['filter' => 'permission:backup.manage']
        );

        $routes->post(
            'backup/create',
            'Backup::create',
            ['filter' => 'permission:backup.manage']
        );

        $routes->get(
            'backup/download/(:segment)',
            'Backup::download/$1',
            ['filter' => 'permission:backup.manage']
        );

        // Disediakan untuk manajemen file backup dari UI administratif.
        $routes->delete(
            'backup/delete/(:segment)',
            'Backup::delete/$1',
            ['filter' => 'permission:backup.manage']
        );

        // ====================================================================
        // 4.10 LOG ACTIVITY
        // ====================================================================

        $routes->get(
            'log/activity',
            'LogActivity::index',
            ['filter' => 'permission:log_activity.view']
        );

        $routes->get(
            'log/activity/json',
            'LogActivity::index',
            ['filter' => 'permission:log_activity.view']
        );

        $routes->get(
            'log/activity/export',
            'LogActivity::export',
            ['filter' => 'permission:log_activity.view']
        );
    }
);

// ============================================================================
// 5. API MOBILE — PROTECTED
// Prefix: api/
// ============================================================================

$routes->group(
    'api',
    ['filter' => 'auth:api'],
    static function ($routes) {

        // Dashboard
        $routes->get(
            'dashboard',
            'Dashboard::index',
            ['filter' => 'permission:dashboard.view']
        );

        $routes->get(
            'dashboard/data',
            'Dashboard::data',
            ['filter' => 'permission:dashboard.view']
        );

        // Presensi Siswa
        $routes->get(
            'presensi/siswa',
            'PresensiSiswa::index',
            ['filter' => 'permission:presensi_siswa.input']
        );

        $routes->get(
            'presensi/siswa/input/(:segment)',
            'PresensiSiswa::input/$1',
            ['filter' => 'permission:presensi_siswa.input']
        );

        $routes->post(
            'presensi/siswa/save',
            'PresensiSiswa::save',
            ['filter' => 'permission:presensi_siswa.input']
        );

        $routes->get(
            'presensi/siswa/revisi/(:segment)',
            'PresensiSiswa::revisi/$1',
            ['filter' => 'permission:presensi_siswa.revisi']
        );

        $routes->post(
            'presensi/siswa/revisi/save',
            'PresensiSiswa::saveRevisi',
            ['filter' => 'permission:presensi_siswa.revisi']
        );

        $routes->get(
            'presensi/siswa/rekap',
            'PresensiSiswa::rekap',
            ['filter' => 'permission:presensi_siswa.view']
        );

        $routes->get(
            'presensi/siswa/ews',
            'PresensiSiswa::ews',
            ['filter' => 'permission:ews_radar.view']
        );

        // Presensi Mengajar
        $routes->get(
            'presensi/mengajar',
            'PresensiMengajar::index',
            ['filter' => 'permission:presensi_mengajar.input']
        );

        $routes->get(
            'presensi/mengajar/input/(:segment)',
            'PresensiMengajar::input/$1',
            ['filter' => 'permission:presensi_mengajar.input']
        );

        $routes->post(
            'presensi/mengajar/save',
            'PresensiMengajar::save',
            ['filter' => 'permission:presensi_mengajar.input']
        );

        $routes->get(
            'presensi/mengajar/laporan',
            'PresensiMengajar::laporan',
            ['filter' => 'permission:presensi_mengajar.view']
        );

        // Laporan
        $routes->get(
            'laporan/presensi/matrix',
            'LaporanPresensi::matrix',
            ['filter' => 'permission:laporan_matrix.view']
        );

        $routes->get(
            'laporan/jurnal',
            'LaporanJurnal::index',
            ['filter' => 'permission:laporan_jurnal.view']
        );

        // BK
        $routes->get(
            'bk/kasus',
            'BKKasus::index',
            ['filter' => 'permission:bk_kasus.view']
        );

        $routes->get(
            'bk/kasus/top',
            'BKKasus::top',
            ['filter' => 'permission:bk_kasus.view']
        );

        $routes->post(
            'bk/kasus/create',
            'BKKasus::create',
            ['filter' => 'permission:bk_kasus.manage']
        );

        $routes->get(
            'bk/prestasi',
            'BKPrestasi::index',
            ['filter' => 'permission:prestasi.view']
        );

        $routes->post(
            'bk/prestasi/create',
            'BKPrestasi::create',
            ['filter' => 'permission:prestasi.manage']
        );

        // Kartu Pelajar
        $routes->get(
            'kartu/preview/(:segment)',
            'KartuPelajar::preview/$1',
            ['filter' => 'permission:kartu_pelajar.view']
        );

        $routes->get(
            'kartu/download/(:segment)',
            'KartuPelajar::download/$1',
            ['filter' => 'permission:kartu_pelajar.view']
        );

        // Profile Guru
        $routes->get(
            'profile/guru',
            'ProfileGuru::index',
            ['filter' => 'permission:profile_guru.view']
        );

        $routes->put(
            'profile/guru',
            'ProfileGuru::update',
            ['filter' => 'permission:profile_guru.edit']
        );

        $routes->post(
            'profile/guru/foto',
            'ProfileGuru::uploadFoto',
            ['filter' => 'permission:profile_guru.edit']
        );

        // Profile Siswa
        $routes->get(
            'profile/siswa',
            'ProfileSiswa::index',
            ['filter' => 'permission:profile_siswa.view']
        );
    }
);

// ============================================================================
// 6. 404
// ============================================================================
//
// Ditangani oleh konfigurasi CI4 / default handler.
// Tidak perlu membuat catch-all manual di file ini.
