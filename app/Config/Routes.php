<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Auth::login');
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/login', 'Auth::login');
$routes->post('auth/logout', 'Auth::logout', ['filter' => 'auth']);

$routes->group('api', static function ($routes) {
    $routes->post('auth/login', 'Auth::apiLogin');
    $routes->post('auth/logout', 'Auth::apiLogout', ['filter' => 'auth:api']);
    $routes->get('auth/me', 'Auth::apiMe', ['filter' => 'auth:api']);
    $routes->post('auth/refresh', 'Auth::apiRefresh');
    $routes->get('version', 'Api::version');
});

$routes->get('kartu/verify/(:segment)', 'KartuPelajar::verify/$1');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index', ['filter' => 'permission:dashboard.view']);
    $routes->get('dashboard/data', 'Dashboard::data', ['filter' => 'permission:dashboard.view']);

    $routes->group('master', static function ($routes) {
        $routes->get('guru', 'MasterGuru::index', ['filter' => 'permission:master_guru.manage,master_guru.view']);
        $routes->get('guru/json', 'MasterGuru::index', ['filter' => 'permission:master_guru.manage,master_guru.view']);
        $routes->get('guru/template', 'MasterGuru::downloadTemplate', ['filter' => 'permission:master_guru.manage']);
        $routes->post('guru/create', 'MasterGuru::create', ['filter' => 'permission:master_guru.manage']);
        $routes->post('guru/import', 'MasterGuru::import', ['filter' => 'permission:master_guru.manage']);
        $routes->get('guru/export', 'MasterGuru::export', ['filter' => 'permission:master_guru.manage']);
        $routes->put('guru/update/(:segment)', 'MasterGuru::update/$1', ['filter' => 'permission:master_guru.manage']);
        $routes->delete('guru/delete/(:segment)', 'MasterGuru::delete/$1', ['filter' => 'permission:master_guru.manage']);
        $routes->post('guru/upload-foto/(:segment)', 'MasterGuru::uploadFoto/$1', ['filter' => 'permission:master_guru.manage']);
        $routes->get('guru/recycle', 'MasterGuru::recycle', ['filter' => 'permission:master_guru.manage']);
        $routes->get('guru/recycle/json', 'MasterGuru::recycle', ['filter' => 'permission:master_guru.manage']);
        $routes->post('guru/restore/(:segment)', 'MasterGuru::restore/$1', ['filter' => 'permission:master_guru.manage']);
        $routes->delete('guru/force-delete/(:segment)', 'MasterGuru::forceDelete/$1', ['filter' => 'permission:master_guru.manage']);

        $routes->get('pegawai', 'MasterPegawai::index', ['filter' => 'permission:master_pegawai.manage,master_pegawai.view']);
        $routes->get('pegawai/json', 'MasterPegawai::index', ['filter' => 'permission:master_pegawai.manage,master_pegawai.view']);
        $routes->get('pegawai/template', 'MasterPegawai::downloadTemplate', ['filter' => 'permission:master_pegawai.manage']);
        $routes->post('pegawai/create', 'MasterPegawai::create', ['filter' => 'permission:master_pegawai.manage']);
        $routes->post('pegawai/import', 'MasterPegawai::import', ['filter' => 'permission:master_pegawai.manage']);
        $routes->get('pegawai/export', 'MasterPegawai::export', ['filter' => 'permission:master_pegawai.manage']);
        $routes->put('pegawai/update/(:segment)', 'MasterPegawai::update/$1', ['filter' => 'permission:master_pegawai.manage']);
        $routes->delete('pegawai/delete/(:segment)', 'MasterPegawai::delete/$1', ['filter' => 'permission:master_pegawai.manage']);
        $routes->get('pegawai/recycle', 'MasterPegawai::recycle', ['filter' => 'permission:master_pegawai.manage']);
        $routes->get('pegawai/recycle/json', 'MasterPegawai::recycle', ['filter' => 'permission:master_pegawai.manage']);
        $routes->post('pegawai/restore/(:segment)', 'MasterPegawai::restore/$1', ['filter' => 'permission:master_pegawai.manage']);
        $routes->delete('pegawai/force-delete/(:segment)', 'MasterPegawai::forceDelete/$1', ['filter' => 'permission:master_pegawai.manage']);

        $routes->get('siswa', 'MasterSiswa::index', ['filter' => 'permission:master_siswa.view']);
        $routes->get('siswa/json', 'MasterSiswa::index', ['filter' => 'permission:master_siswa.view']);
        $routes->get('siswa/template', 'MasterSiswa::downloadTemplate', ['filter' => 'permission:master_siswa.import_export']);
        $routes->post('siswa/create', 'MasterSiswa::create', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('siswa/import', 'MasterSiswa::import', ['filter' => 'permission:master_siswa.import_export']);
        $routes->get('siswa/export', 'MasterSiswa::export', ['filter' => 'permission:master_siswa.import_export']);
        $routes->put('siswa/update/(:segment)', 'MasterSiswa::update/$1', ['filter' => 'permission:master_siswa.edit_biodata']);
        $routes->delete('siswa/delete/(:segment)', 'MasterSiswa::delete/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('siswa/recycle', 'MasterSiswa::recycle', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('siswa/recycle/json', 'MasterSiswa::recycle', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('siswa/restore/(:segment)', 'MasterSiswa::restore/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->delete('siswa/force-delete/(:segment)', 'MasterSiswa::forceDelete/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('siswa/upload-foto/(:segment)', 'MasterSiswa::uploadFoto/$1', ['filter' => 'permission:master_siswa.edit_biodata']);

        $routes->get('kelas', 'MasterKelas::index', ['filter' => 'permission:master_kelas.manage']);
        $routes->get('kelas/json', 'MasterKelas::index', ['filter' => 'permission:master_kelas.manage']);
        $routes->post('kelas/create', 'MasterKelas::create', ['filter' => 'permission:master_kelas.manage']);
        $routes->put('kelas/update/(:segment)', 'MasterKelas::update/$1', ['filter' => 'permission:master_kelas.manage']);
        $routes->delete('kelas/delete/(:segment)', 'MasterKelas::delete/$1', ['filter' => 'permission:master_kelas.manage']);
        $routes->get('kelas/recycle', 'MasterKelas::recycle', ['filter' => 'permission:master_kelas.manage']);
        $routes->get('kelas/recycle/json', 'MasterKelas::recycle', ['filter' => 'permission:master_kelas.manage']);
        $routes->post('kelas/restore/(:segment)', 'MasterKelas::restore/$1', ['filter' => 'permission:master_kelas.manage']);
        $routes->delete('kelas/force-delete/(:segment)', 'MasterKelas::forceDelete/$1', ['filter' => 'permission:master_kelas.manage']);

        $routes->get('tahun', 'MasterTahunAjaran::index', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->get('tahun/json', 'MasterTahunAjaran::index', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->post('tahun/create', 'MasterTahunAjaran::create', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->put('tahun/update/(:segment)', 'MasterTahunAjaran::update/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->post('tahun/aktifkan/(:segment)', 'MasterTahunAjaran::aktifkan/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->delete('tahun/delete/(:segment)', 'MasterTahunAjaran::delete/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->get('tahun/recycle', 'MasterTahunAjaran::recycle', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->get('tahun/recycle/json', 'MasterTahunAjaran::recycle', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->post('tahun/restore/(:segment)', 'MasterTahunAjaran::restore/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);
        $routes->delete('tahun/force-delete/(:segment)', 'MasterTahunAjaran::forceDelete/$1', ['filter' => 'permission:master_tahun_ajaran.manage']);

        $routes->get('mapel', 'MasterMapel::index', ['filter' => 'permission:master_mapel.manage']);
        $routes->get('mapel/json', 'MasterMapel::index', ['filter' => 'permission:master_mapel.manage']);
        $routes->post('mapel/create', 'MasterMapel::create', ['filter' => 'permission:master_mapel.manage']);
        $routes->put('mapel/update/(:segment)', 'MasterMapel::update/$1', ['filter' => 'permission:master_mapel.manage']);
        $routes->delete('mapel/delete/(:segment)', 'MasterMapel::delete/$1', ['filter' => 'permission:master_mapel.manage']);

        $routes->get('wali-kelas', 'MappingWaliKelas::index', ['filter' => 'permission:mapping_wali.view,mapping_wali.manage,mapping_wali.view_all']);
        $routes->get('wali-kelas/json', 'MappingWaliKelas::index', ['filter' => 'permission:mapping_wali.view,mapping_wali.manage,mapping_wali.view_all']);
        $routes->post('wali-kelas/assign', 'MappingWaliKelas::assign', ['filter' => 'permission:mapping_wali.manage']);
        $routes->delete('wali-kelas/delete/(:segment)', 'MappingWaliKelas::delete/$1', ['filter' => 'permission:mapping_wali.manage']);
        $routes->get('wali-kelas/options', 'MappingWaliKelas::options', ['filter' => 'permission:mapping_wali.manage']);
        $routes->get('wali-kelas/recycle', 'MappingWaliKelas::recycle', ['filter' => 'permission:mapping_wali.manage']);
        $routes->get('wali-kelas/recycle/json', 'MappingWaliKelas::recycle', ['filter' => 'permission:mapping_wali.manage']);
        $routes->post('wali-kelas/restore/(:segment)', 'MappingWaliKelas::restore/$1', ['filter' => 'permission:mapping_wali.manage']);
        $routes->delete('wali-kelas/force-delete/(:segment)', 'MappingWaliKelas::forceDelete/$1', ['filter' => 'permission:mapping_wali.manage']);

        $routes->get('jadwal', 'JadwalGuru::index', ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']);
        $routes->get('jadwal/json', 'JadwalGuru::index', ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']);
        $routes->get('jadwal/options', 'JadwalGuru::options', ['filter' => 'permission:jadwal_guru.view,jadwal_guru.view_all,jadwal_guru.manage']);
        $routes->get('jadwal/template', 'JadwalGuru::downloadTemplate', ['filter' => 'permission:jadwal_guru.manage']);
        $routes->post('jadwal/import', 'JadwalGuru::import', ['filter' => 'permission:jadwal_guru.manage']);
        $routes->get('jadwal/export', 'JadwalGuru::export', ['filter' => 'permission:jadwal_guru.manage']);
        $routes->delete('jadwal/delete/(:segment)', 'JadwalGuru::delete/$1', ['filter' => 'permission:jadwal_guru.manage']);
    });

    $routes->group('manajemen-siswa', static function ($routes) {
        $routes->get('kelas', 'ManajemenSiswa::kelas', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('kelas/json', 'ManajemenSiswa::kelas', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('kelas/set/(:segment)', 'ManajemenSiswa::setKelas/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('kenaikan', 'ManajemenSiswa::kenaikan', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('process-data/(:segment)', 'ManajemenSiswa::processData/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('kenaikan/proses/(:segment)', 'ManajemenSiswa::naik/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('mutasi', 'ManajemenSiswa::mutasi', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('mutasi/json', 'ManajemenSiswa::mutasi', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('mutasi/proses/(:segment)', 'ManajemenSiswa::prosesMutasi/$1', ['filter' => 'permission:master_siswa.manage']);
        $routes->get('kelulusan', 'ManajemenSiswa::kelulusan', ['filter' => 'permission:master_siswa.manage']);
        $routes->post('kelulusan/proses/(:segment)', 'ManajemenSiswa::lulus/$1', ['filter' => 'permission:master_siswa.manage']);
    });

    $routes->group('presensi', static function ($routes) {
        $routes->get('siswa', 'PresensiSiswa::index', ['filter' => 'permission:presensi_siswa.input']);
        $routes->get('siswa/input/(:segment)', 'PresensiSiswa::input/$1', ['filter' => 'permission:presensi_siswa.input']);
        $routes->get('siswa/input/(:segment)/json', 'PresensiSiswa::input/$1', ['filter' => 'permission:presensi_siswa.input']);
        $routes->post('siswa/save', 'PresensiSiswa::save', ['filter' => 'permission:presensi_siswa.input']);
        $routes->get('siswa/revisi/(:segment)', 'PresensiSiswa::revisi/$1', ['filter' => 'permission:presensi_siswa.revisi']);
        $routes->post('siswa/revisi/save', 'PresensiSiswa::saveRevisi', ['filter' => 'permission:presensi_siswa.revisi']);
        $routes->get('siswa/rekap', 'PresensiSiswa::rekap', ['filter' => 'permission:presensi_siswa.view']);
        $routes->get('siswa/rekap/json', 'PresensiSiswa::rekap', ['filter' => 'permission:presensi_siswa.view']);
        $routes->get('siswa/ews', 'PresensiSiswa::ews', ['filter' => 'permission:ews_radar.view']);
        $routes->get('siswa/ews/json', 'PresensiSiswa::ews', ['filter' => 'permission:ews_radar.view']);
        $routes->get('mengajar', 'PresensiMengajar::index', ['filter' => 'permission:presensi_mengajar.input']);
        $routes->get('mengajar/input/(:segment)', 'PresensiMengajar::input/$1', ['filter' => 'permission:presensi_mengajar.input']);
        $routes->get('mengajar/input/(:segment)/json', 'PresensiMengajar::input/$1', ['filter' => 'permission:presensi_mengajar.input']);
        $routes->post('mengajar/save', 'PresensiMengajar::save', ['filter' => 'permission:presensi_mengajar.input']);
        $routes->get('mengajar/laporan', 'PresensiMengajar::laporan', ['filter' => 'permission:presensi_mengajar.view']);
        $routes->get('mengajar/laporan/json', 'PresensiMengajar::laporan', ['filter' => 'permission:presensi_mengajar.view']);
    });

    $routes->group('laporan', static function ($routes) {
        $routes->get('presensi/matrix', 'LaporanPresensi::matrix', ['filter' => 'permission:laporan_matrix.view']);
        $routes->get('presensi/matrix/json', 'LaporanPresensi::matrix', ['filter' => 'permission:laporan_matrix.view']);
        $routes->get('presensi/export', 'LaporanPresensi::export', ['filter' => 'permission:laporan_export.generate']);
        $routes->get('presensi/export/bulan', 'LaporanPresensi::exportBulan', ['filter' => 'permission:laporan_export.generate']);
        $routes->get('presensi/export/semester', 'LaporanPresensi::exportSemester', ['filter' => 'permission:laporan_export.generate']);
        $routes->get('jurnal', 'LaporanJurnal::index', ['filter' => 'permission:laporan_jurnal.view']);
        $routes->get('jurnal/json', 'LaporanJurnal::index', ['filter' => 'permission:laporan_jurnal.view']);
        $routes->get('jurnal/export', 'LaporanJurnal::export', ['filter' => 'permission:laporan_jurnal.export']);
    });

    $routes->group('bk', static function ($routes) {
        $routes->get('kasus', 'BKKasus::index', ['filter' => 'permission:bk_kasus.view']);
        $routes->get('kasus/json', 'BKKasus::index', ['filter' => 'permission:bk_kasus.view']);
        $routes->get('kasus/top', 'BKKasus::top', ['filter' => 'permission:bk_kasus.view']);
        $routes->get('kasus/top/json', 'BKKasus::top', ['filter' => 'permission:bk_kasus.view']);
        $routes->post('kasus/create', 'BKKasus::create', ['filter' => 'permission:bk_kasus.manage']);
        $routes->put('kasus/update/(:segment)', 'BKKasus::update/$1', ['filter' => 'permission:bk_kasus.manage']);
        $routes->delete('kasus/delete/(:segment)', 'BKKasus::delete/$1', ['filter' => 'permission:bk_kasus.manage']);
        $routes->get('kasus/export', 'BKKasus::export', ['filter' => 'permission:bk_kasus.manage']);
        $routes->get('pelanggaran', 'BKPelanggaran::index', ['filter' => 'permission:bk_pelanggaran_master.manage']);
        $routes->get('pelanggaran/json', 'BKPelanggaran::index', ['filter' => 'permission:bk_pelanggaran_master.manage']);
        $routes->post('pelanggaran/create', 'BKPelanggaran::create', ['filter' => 'permission:bk_pelanggaran_master.manage']);
        $routes->put('pelanggaran/update/(:segment)', 'BKPelanggaran::update/$1', ['filter' => 'permission:bk_pelanggaran_master.manage']);
        $routes->delete('pelanggaran/delete/(:segment)', 'BKPelanggaran::delete/$1', ['filter' => 'permission:bk_pelanggaran_master.manage']);
        $routes->get('prestasi', 'BKPrestasi::index', ['filter' => 'permission:prestasi.view']);
        $routes->get('prestasi/json', 'BKPrestasi::index', ['filter' => 'permission:prestasi.view']);
        $routes->post('prestasi/create', 'BKPrestasi::create', ['filter' => 'permission:prestasi.manage']);
        $routes->put('prestasi/update/(:segment)', 'BKPrestasi::update/$1', ['filter' => 'permission:prestasi.manage']);
        $routes->delete('prestasi/delete/(:segment)', 'BKPrestasi::delete/$1', ['filter' => 'permission:prestasi.manage']);
        $routes->get('prestasi/export', 'BKPrestasi::export', ['filter' => 'permission:prestasi.view,prestasi.manage']);
    });

    $routes->group('kartu', static function ($routes) {
        $routes->get('daftar', 'KartuPelajar::daftar', ['filter' => 'permission:kartu_pelajar.view']);
        $routes->get('daftar/json', 'KartuPelajar::daftar', ['filter' => 'permission:kartu_pelajar.view']);
        $routes->post('generate', 'KartuPelajar::generate', ['filter' => 'permission:kartu_pelajar.manage']);
        $routes->get('cetak/(:segment)', 'KartuPelajar::cetak/$1', ['filter' => 'permission:kartu_pelajar.manage,kartu_pelajar.view']);
        $routes->get('preview/(:segment)', 'KartuPelajar::preview/$1', ['filter' => 'permission:kartu_pelajar.view']);
        $routes->get('preview/(:segment)/json', 'KartuPelajar::preview/$1', ['filter' => 'permission:kartu_pelajar.view']);
        $routes->get('download/(:segment)', 'KartuPelajar::download/$1', ['filter' => 'permission:kartu_pelajar.view']);
        $routes->post('reissue/(:segment)', 'KartuPelajar::reissue/$1', ['filter' => 'permission:kartu_pelajar.manage']);
    });

    $routes->group('profile', static function ($routes) {
        $routes->get('guru', 'ProfileGuru::index', ['filter' => 'permission:profile_guru.view']);
        $routes->get('guru/json', 'ProfileGuru::index', ['filter' => 'permission:profile_guru.view']);
        $routes->put('guru/update', 'ProfileGuru::update', ['filter' => 'permission:profile_guru.edit']);
        $routes->post('guru/upload-foto', 'ProfileGuru::uploadFoto', ['filter' => 'permission:profile_guru.edit']);
        $routes->get('siswa', 'ProfileSiswa::index', ['filter' => 'permission:profile_siswa.view']);
        $routes->get('siswa/json', 'ProfileSiswa::index', ['filter' => 'permission:profile_siswa.view']);
    });

    $routes->group('settings', static function ($routes) {
        $routes->get('user', 'SettingsUser::index', ['filter' => 'permission:settings_user.manage']);
        $routes->get('user/json', 'SettingsUser::index', ['filter' => 'permission:settings_user.manage']);
        $routes->post('user/create', 'SettingsUser::create', ['filter' => 'permission:settings_user.manage']);
        $routes->put('user/update/(:segment)', 'SettingsUser::update/$1', ['filter' => 'permission:settings_user.manage']);
        $routes->post('user/reset/(:segment)', 'SettingsUser::reset/$1', ['filter' => 'permission:settings_user.manage']);
        $routes->delete('user/delete/(:segment)', 'SettingsUser::delete/$1', ['filter' => 'permission:settings_user.manage']);
        $routes->get('menu', 'SettingsMenu::index', ['filter' => 'permission:settings_menu.manage']);
        $routes->get('menu/json', 'SettingsMenu::index', ['filter' => 'permission:settings_menu.manage']);
        $routes->put('menu/update/(:segment)', 'SettingsMenu::update/$1', ['filter' => 'permission:settings_menu.manage']);
        $routes->get('sistem', 'SettingsSistem::index', ['filter' => 'permission:settings_sistem.manage']);
        $routes->get('sistem/json', 'SettingsSistem::index', ['filter' => 'permission:settings_sistem.manage']);
        $routes->put('sistem/update', 'SettingsSistem::update', ['filter' => 'permission:settings_sistem.manage']);
        $routes->post('sistem/maintenance', 'SettingsSistem::maintenance', ['filter' => 'permission:settings_sistem.manage']);
        $routes->post('sistem/upload-branding', 'SettingsSistem::uploadBranding', ['filter' => 'permission:settings_sistem.manage']);
        $routes->post('sistem/upload-background-kta', 'SettingsSistem::uploadBackgroundKta', ['filter' => 'permission:settings_sistem.manage']);
    });

    $routes->get('backup', 'Backup::index', ['filter' => 'permission:backup.manage']);
    $routes->post('backup/create', 'Backup::create', ['filter' => 'permission:backup.manage']);
    $routes->get('backup/download/(:segment)', 'Backup::download/$1', ['filter' => 'permission:backup.manage']);
    $routes->delete('backup/delete/(:segment)', 'Backup::delete/$1', ['filter' => 'permission:backup.manage']);
    $routes->get('log/activity', 'LogActivity::index', ['filter' => 'permission:log_activity.view']);
    $routes->get('log/activity/json', 'LogActivity::index', ['filter' => 'permission:log_activity.view']);
    $routes->get('log/activity/export', 'LogActivity::export', ['filter' => 'permission:log_activity.view']);
});

$routes->group('api', ['filter' => 'auth:api'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index', ['filter' => 'permission:dashboard.view']);
    $routes->get('dashboard/data', 'Dashboard::data', ['filter' => 'permission:dashboard.view']);
    $routes->get('presensi/siswa', 'PresensiSiswa::index', ['filter' => 'permission:presensi_siswa.input']);
    $routes->get('presensi/siswa/input/(:segment)', 'PresensiSiswa::input/$1', ['filter' => 'permission:presensi_siswa.input']);
    $routes->post('presensi/siswa/save', 'PresensiSiswa::save', ['filter' => 'permission:presensi_siswa.input']);
    $routes->get('presensi/siswa/revisi/(:segment)', 'PresensiSiswa::revisi/$1', ['filter' => 'permission:presensi_siswa.revisi']);
    $routes->post('presensi/siswa/revisi/save', 'PresensiSiswa::saveRevisi', ['filter' => 'permission:presensi_siswa.revisi']);
    $routes->get('presensi/siswa/rekap', 'PresensiSiswa::rekap', ['filter' => 'permission:presensi_siswa.view']);
    $routes->get('presensi/siswa/ews', 'PresensiSiswa::ews', ['filter' => 'permission:ews_radar.view']);
    $routes->get('presensi/mengajar', 'PresensiMengajar::index', ['filter' => 'permission:presensi_mengajar.input']);
    $routes->get('presensi/mengajar/input/(:segment)', 'PresensiMengajar::input/$1', ['filter' => 'permission:presensi_mengajar.input']);
    $routes->post('presensi/mengajar/save', 'PresensiMengajar::save', ['filter' => 'permission:presensi_mengajar.input']);
    $routes->get('presensi/mengajar/laporan', 'PresensiMengajar::laporan', ['filter' => 'permission:presensi_mengajar.view']);
    $routes->get('laporan/presensi/matrix', 'LaporanPresensi::matrix', ['filter' => 'permission:laporan_matrix.view']);
    $routes->get('laporan/jurnal', 'LaporanJurnal::index', ['filter' => 'permission:laporan_jurnal.view']);
    $routes->get('bk/kasus', 'BKKasus::index', ['filter' => 'permission:bk_kasus.view']);
    $routes->get('bk/kasus/top', 'BKKasus::top', ['filter' => 'permission:bk_kasus.view']);
    $routes->post('bk/kasus/create', 'BKKasus::create', ['filter' => 'permission:bk_kasus.manage']);
    $routes->put('bk/kasus/update/(:segment)', 'BKKasus::update/$1', ['filter' => 'permission:bk_kasus.manage']);
    $routes->delete('bk/kasus/delete/(:segment)', 'BKKasus::delete/$1', ['filter' => 'permission:bk_kasus.manage']);
    $routes->get('bk/prestasi', 'BKPrestasi::index', ['filter' => 'permission:prestasi.view']);
    $routes->post('bk/prestasi/create', 'BKPrestasi::create', ['filter' => 'permission:prestasi.manage']);
    $routes->get('kartu/preview/(:segment)', 'KartuPelajar::preview/$1', ['filter' => 'permission:kartu_pelajar.view']);
    $routes->get('kartu/download/(:segment)', 'KartuPelajar::download/$1', ['filter' => 'permission:kartu_pelajar.view']);
    $routes->get('profile/guru', 'ProfileGuru::index', ['filter' => 'permission:profile_guru.view']);
    $routes->put('profile/guru', 'ProfileGuru::update', ['filter' => 'permission:profile_guru.edit']);
    $routes->post('profile/guru/foto', 'ProfileGuru::uploadFoto', ['filter' => 'permission:profile_guru.edit']);
    $routes->get('profile/siswa', 'ProfileSiswa::index', ['filter' => 'permission:profile_siswa.view']);
});
