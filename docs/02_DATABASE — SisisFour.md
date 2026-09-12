# Database — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.


## 1. Prinsip

Database adalah sumber integritas persistence. Business rule tambahan tetap dijaga Service.

Baseline schema terdiri dari **32 tabel**:

```text
anggota_kelas
api_tokens
catatan_kasus
catatan_prestasi
ci_sessions
dokumen_personalia
guru
jadwal_guru
kartu_pelajar
kelas
login_attempts
log_activity
mapping_wali_kelas
mata_pelajaran
menus
pegawai
permissions
presensi
presensi_mengajar
ref_pelanggaran
riwayat_pangkat
riwayat_pendidikan
riwayat_penugasan
riwayat_siswa
role_menus
role_permissions
setting_sistem
siswa
tahun_ajaran
tindak_lanjut_kasus
users
user_roles
```

## 2. Runtime Tables

### `ci_sessions`

Dipakai CodeIgniter DatabaseHandler.

```text
id          VARCHAR(128) PK
ip_address  VARCHAR(45)
timestamp   DATETIME
data        BLOB
```

### `api_tokens`

Access/refresh token, expiry, device, revoke state.

### `login_attempts`

Rate-limit/lockout login.

Pada deploy environment baru:

```sql
TRUNCATE TABLE ci_sessions;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE login_attempts;
```

## 3. User dan Identity

### `users`

- `username` unique.
- `role` nullable.
- role resmi: `admin`, `operator`, `pimpinan`, `bk`, `guru`, `siswa`.
- `id_guru`, `id_pegawai`, `id_siswa` masing-masing unique.
- `auth_version` = invalidation token.
- login normal tidak menaikkan `auth_version`.

### `user_roles`

Secondary role. Effective role = primary union secondary.

### Guru/Pegawai

`nik` dan `nip` tetap nullable pada schema untuk kompatibilitas legacy.

Business rule:

```text
NIK wajib 16 digit pada create/edit/import baru
NIP optional
login identifier = NIP bila ada, selain itu NIK
```

### Siswa

NISN unique dan menjadi default credential identifier.

## 4. Tahun/Kelas/Membership

### `tahun_ajaran`
Hanya satu operasional aktif secara Service.

### `kelas`
Terkait tahun ajaran.

### `anggota_kelas`

```text
UNIQUE(id_siswa, id_tahun)
```

### `riwayat_siswa`
Histori membership/lifecycle.

## 5. Mapping Wali

`mapping_wali_kelas` adalah sumber context Wali.

```text
1 Guru max 1 kelas aktif per tahun
1 Kelas max 1 Wali aktif per tahun
```

Soft delete mempertahankan histori.

## 6. Jadwal Guru

Mengikat Guru, Kelas, Mapel, Tahun, hari, waktu, sesi.

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

Import harus menolak overlap Guru/Kelas dan bersifat atomic.

## 7. Presensi

### `presensi`

```text
status: Hadir, Sakit, Izin, Alpha
sesi:   Sesi Awal, Sesi Akhir
```

Unique business record menjaga satu siswa per kelas/tanggal/sesi.

Snapshot nama siswa/guru input dipertahankan.

### `presensi_mengajar`

Satu record per jadwal/tanggal dan menyimpan status/materi/jurnal.

## 8. BK dan Prestasi

```text
ref_pelanggaran
catatan_kasus
tindak_lanjut_kasus
catatan_prestasi
```

`tindak_lanjut_kasus` = 1:N terhadap `catatan_kasus`.

## 9. Kartu Pelajar

Field utama:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Generated/unique contract menjaga maksimum satu kartu Aktif per siswa.

## 10. Personalia

```text
riwayat_pendidikan
riwayat_penugasan
riwayat_pangkat
dokumen_personalia
```

Owner:

```text
id_guru XOR id_pegawai
```

Periode penugasan:

```text
tanggal_selesai IS NULL
atau tanggal_selesai >= tanggal_mulai
```

## 11. Menu dan Permission

Tabel:

```text
menus
role_menus
permissions
role_permissions
```

Baseline memiliki **43 permission key**:

```text
dashboard.view
presensi_siswa.input
presensi_siswa.revisi
presensi_siswa.view
presensi_mengajar.input
presensi_mengajar.view
master_guru.manage
master_guru.view
master_pegawai.manage
master_pegawai.view
master_siswa.view
master_siswa.edit_biodata
master_siswa.manage
master_siswa.import_export
master_kelas.manage
master_tahun_ajaran.manage
master_mapel.manage
mapping_wali.manage
mapping_wali.view
mapping_wali.view_all
jadwal_guru.manage
jadwal_guru.view
jadwal_guru.view_all
laporan_matrix.view
laporan_export.generate
laporan_jurnal.view
laporan_jurnal.export
ews_radar.view
bk_kasus.manage
bk_kasus.view
bk_pelanggaran_master.manage
prestasi.manage
prestasi.view
kartu_pelajar.manage
kartu_pelajar.view
settings_user.manage
settings_menu.manage
settings_sistem.manage
backup.manage
log_activity.view
profile_guru.view
profile_guru.edit
profile_siswa.view
```

Profile Pegawai memakai self identity, bukan `profile_pegawai.*`.

## 12. Setting Sistem

```text
nama_sekolah
alamat_sekolah
logo_sekolah
icon_sekolah
latitude_sekolah
longitude_sekolah
radius_geofencing
geofencing_aktif
maintenance_mode
maintenance_message
background_kta_depan
background_kta_belakang
```

## 13. Snapshot Data Release

```text
Siswa aktif          1.493
Anggota kelas        1.493
Riwayat siswa aktif  1.493
Kartu aktif          1.493
Users                1.610
Guru                   115
Kelas                   53
Mapping wali aktif      53
Jadwal                1.253
```

Audit release tidak menemukan mismatch utama siswa vs membership/riwayat/account/kartu.

## 14. Aturan Perubahan Schema

- Jangan menambah kolom berdasarkan asumsi UI.
- Perubahan schema harus punya alasan business/integrity.
- Baseline deploy dapat berasal dari dump SQL resmi.
- Setiap perubahan schema wajib memperbarui dokumen ini dan dump terbaru.
- Nilai `AUTO_INCREMENT` bukan kontrak bisnis.

## 15. DDL Reference Baseline

Bagian ini menyalin struktur DDL dari dump `(33)` tanpa data row. Nilai counter `AUTO_INCREMENT` runtime dihilangkan karena bukan kontrak bisnis. CHECK constraint yang harus ada tetap diverifikasi pada database live bila dump tidak merepresentasikannya secara lengkap.

### 15.1 `anggota_kelas`

```sql
CREATE TABLE `anggota_kelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `anggota_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_anggota_siswa_tahun` (`id_siswa`,`id_tahun`),
  ADD KEY `idx_anggota_kelas` (`id_kelas`),
  ADD KEY `idx_anggota_tahun` (`id_tahun`);

ALTER TABLE `anggota_kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `anggota_kelas`
  ADD CONSTRAINT `fk_anggota_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_anggota_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_anggota_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE;
```

### 15.2 `api_tokens`

```sql
CREATE TABLE `api_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `refresh_token` varchar(255) NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `refresh_expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `refresh_token` (`refresh_token`),
  ADD KEY `idx_api_tokens_user` (`id_user`),
  ADD KEY `idx_api_tokens_expiry` (`expires_at`),
  ADD KEY `idx_api_tokens_refresh_expiry` (`refresh_expires_at`);

ALTER TABLE `api_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `api_tokens`
  ADD CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.3 `catatan_kasus`

```sql
CREATE TABLE `catatan_kasus` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED NOT NULL,
  `id_pelanggaran` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_guru_input` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `catatan_kasus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kasus_siswa_tanggal` (`id_siswa`,`tanggal`),
  ADD KEY `idx_kasus_pelanggaran` (`id_pelanggaran`),
  ADD KEY `idx_kasus_guru_input` (`id_guru_input`),
  ADD KEY `idx_kasus_updated_by` (`updated_by`);

ALTER TABLE `catatan_kasus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catatan_kasus`
  ADD CONSTRAINT `fk_kasus_guru_input` FOREIGN KEY (`id_guru_input`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kasus_pelanggaran` FOREIGN KEY (`id_pelanggaran`) REFERENCES `ref_pelanggaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kasus_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kasus_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 15.4 `catatan_prestasi`

```sql
CREATE TABLE `catatan_prestasi` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED NOT NULL,
  `nama_prestasi` varchar(200) NOT NULL,
  `tingkat` varchar(100) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `penyelenggara` varchar(200) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `id_guru_input` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `catatan_prestasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prestasi_siswa_tanggal` (`id_siswa`,`tanggal`),
  ADD KEY `idx_prestasi_guru_input` (`id_guru_input`);

ALTER TABLE `catatan_prestasi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catatan_prestasi`
  ADD CONSTRAINT `fk_prestasi_guru_input` FOREIGN KEY (`id_guru_input`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestasi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE;
```

### 15.5 `ci_sessions`

```sql
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`);
```

### 15.6 `dokumen_personalia`

```sql
CREATE TABLE `dokumen_personalia` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED DEFAULT NULL,
  `id_pegawai` int(10) UNSIGNED DEFAULT NULL,
  `jenis_dokumen` varchar(60) NOT NULL,
  `nama_dokumen` varchar(150) NOT NULL,
  `nomor_dokumen` varchar(100) DEFAULT NULL,
  `tanggal_dokumen` date DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `nama_file_asli` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ;

ALTER TABLE `dokumen_personalia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dp_guru` (`id_guru`),
  ADD KEY `idx_dp_pegawai` (`id_pegawai`),
  ADD KEY `idx_dp_jenis` (`jenis_dokumen`);

ALTER TABLE `dokumen_personalia`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `dokumen_personalia`
  ADD CONSTRAINT `fk_dp_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dp_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.7 `guru`

```sql
CREATE TABLE `guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `nip` varchar(18) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `agama` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status_kepegawaian` enum('PNS','PPPK','GTT','PTT','GTY','PTY','Honorer','Outsourcing') DEFAULT NULL,
  `nuptk` varchar(16) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `uk_guru_nik` (`nik`),
  ADD KEY `idx_guru_nama` (`nama`),
  ADD KEY `idx_guru_status_kepegawaian` (`status_kepegawaian`),
  ADD KEY `idx_guru_deleted_at` (`deleted_at`);

ALTER TABLE `guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.8 `jadwal_guru`

```sql
CREATE TABLE `jadwal_guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_mapel` int(10) UNSIGNED NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `sesi` enum('Sesi Awal','Sesi Akhir','Non Sesi') NOT NULL,
  `status_jadwal` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `jadwal_guru`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jadwal_guru_hari` (`id_guru`,`id_tahun`,`hari`,`status_jadwal`),
  ADD KEY `idx_jadwal_kelas_hari` (`id_kelas`,`id_tahun`,`hari`,`status_jadwal`),
  ADD KEY `idx_jadwal_mapel` (`id_mapel`),
  ADD KEY `idx_jadwal_waktu` (`jam_mulai`,`jam_selesai`),
  ADD KEY `fk_jadwal_tahun` (`id_tahun`);

ALTER TABLE `jadwal_guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `jadwal_guru`
  ADD CONSTRAINT `fk_jadwal_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_mapel` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE;
```

### 15.9 `kartu_pelajar`

```sql
CREATE TABLE `kartu_pelajar` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED NOT NULL,
  `nomor_kartu` varchar(50) NOT NULL,
  `kode_verifikasi` varchar(100) NOT NULL,
  `tanggal_terbit` date NOT NULL,
  `status_aktif` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `id_siswa_aktif` int(10) UNSIGNED GENERATED ALWAYS AS (case when `status_aktif` = 'Aktif' then `id_siswa` else NULL end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `kartu_pelajar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_kartu` (`nomor_kartu`),
  ADD UNIQUE KEY `kode_verifikasi` (`kode_verifikasi`),
  ADD UNIQUE KEY `uq_kartu_siswa_aktif` (`id_siswa_aktif`),
  ADD KEY `idx_kartu_siswa` (`id_siswa`),
  ADD KEY `idx_kartu_status` (`status_aktif`);

ALTER TABLE `kartu_pelajar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `kartu_pelajar`
  ADD CONSTRAINT `fk_kartu_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE;
```

### 15.10 `kelas`

```sql
CREATE TABLE `kelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `tingkat` enum('7','8','9') NOT NULL,
  `rombel` varchar(10) NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_kelas_tahun_nama` (`id_tahun`,`nama_kelas`),
  ADD KEY `idx_kelas_tahun` (`id_tahun`),
  ADD KEY `idx_kelas_tingkat` (`tingkat`),
  ADD KEY `idx_kelas_deleted_at` (`deleted_at`);

ALTER TABLE `kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE;
```

### 15.11 `login_attempts`

```sql
CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `waktu` datetime NOT NULL,
  `berhasil` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_lookup` (`username`,`ip_address`,`waktu`),
  ADD KEY `idx_login_attempts_waktu` (`waktu`);

ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.12 `log_activity`

```sql
CREATE TABLE `log_activity` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `aksi` varchar(100) NOT NULL,
  `modul` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `waktu` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `log_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_waktu` (`waktu`),
  ADD KEY `idx_log_user_waktu` (`id_user`,`waktu`),
  ADD KEY `idx_log_modul_waktu` (`modul`,`waktu`);

ALTER TABLE `log_activity`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `log_activity`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 15.13 `mapping_wali_kelas`

```sql
CREATE TABLE `mapping_wali_kelas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `uk_guru_aktif` int(10) UNSIGNED GENERATED ALWAYS AS (case when `deleted_at` is null then `id_guru` else NULL end) STORED,
  `uk_kelas_aktif` int(10) UNSIGNED GENERATED ALWAYS AS (case when `deleted_at` is null then `id_kelas` else NULL end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `mapping_wali_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wali_guru_aktif` (`id_tahun`,`uk_guru_aktif`),
  ADD UNIQUE KEY `uk_wali_kelas_aktif` (`id_tahun`,`uk_kelas_aktif`),
  ADD KEY `idx_wali_guru` (`id_guru`,`id_tahun`),
  ADD KEY `idx_wali_kelas` (`id_kelas`,`id_tahun`),
  ADD KEY `idx_wali_deleted_at` (`deleted_at`);

ALTER TABLE `mapping_wali_kelas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mapping_wali_kelas`
  ADD CONSTRAINT `fk_wali_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wali_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wali_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE;
```

### 15.14 `mata_pelajaran`

```sql
CREATE TABLE `mata_pelajaran` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `kode_mapel` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `mata_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_mapel` (`kode_mapel`),
  ADD KEY `idx_mapel_nama` (`nama_mapel`);

ALTER TABLE `mata_pelajaran`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.15 `menus`

```sql
CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `icon` varchar(100) DEFAULT NULL,
  `link` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menus_parent` (`parent_id`),
  ADD KEY `idx_menus_urutan` (`urutan`);

ALTER TABLE `menus`
  ADD CONSTRAINT `fk_menus_parent` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.16 `pegawai`

```sql
CREATE TABLE `pegawai` (
  `id` int(10) UNSIGNED NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `nip` varchar(18) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `agama` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status_kepegawaian` enum('PNS','PPPK','GTT','PTT','GTY','PTY','Honorer','Outsourcing') DEFAULT NULL,
  `nuptk` varchar(16) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `uk_pegawai_nik` (`nik`),
  ADD KEY `idx_pegawai_nama` (`nama`),
  ADD KEY `idx_pegawai_jabatan` (`jabatan`),
  ADD KEY `idx_pegawai_deleted_at` (`deleted_at`),
  ADD KEY `idx_pegawai_status_kepegawaian` (`status_kepegawaian`);

ALTER TABLE `pegawai`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.17 `permissions`

```sql
CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `modul` varchar(50) NOT NULL,
  `scope_didukung` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`),
  ADD KEY `idx_permissions_modul` (`modul`);

ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.18 `presensi`

```sql
CREATE TABLE `presensi` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED DEFAULT NULL,
  `nama_siswa_snapshot` varchar(150) NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `sesi` enum('Sesi Awal','Sesi Akhir') NOT NULL,
  `status` enum('Hadir','Sakit','Izin','Alpha') NOT NULL,
  `id_guru_input` int(10) UNSIGNED DEFAULT NULL,
  `nama_guru_input_snapshot` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_presensi` (`id_kelas`,`tanggal`,`sesi`,`id_siswa`),
  ADD KEY `idx_presensi_siswa_tahun_tanggal` (`id_siswa`,`id_tahun`,`tanggal`),
  ADD KEY `idx_presensi_kelas_tanggal` (`id_kelas`,`tanggal`),
  ADD KEY `idx_presensi_laporan_awal` (`id_tahun`,`sesi`,`tanggal`,`status`),
  ADD KEY `idx_presensi_guru_input` (`id_guru_input`),
  ADD KEY `idx_presensi_updated_by` (`updated_by`),
  ADD KEY `idx_presensi_kelas_periode` (`id_tahun`,`id_kelas`,`sesi`,`tanggal`),
  ADD KEY `idx_presensi_siswa_periode` (`id_siswa`,`id_tahun`,`sesi`,`tanggal`);

ALTER TABLE `presensi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `presensi`
  ADD CONSTRAINT `fk_presensi_guru_input` FOREIGN KEY (`id_guru_input`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 15.19 `presensi_mengajar`

```sql
CREATE TABLE `presensi_mengajar` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED DEFAULT NULL,
  `nama_guru_snapshot` varchar(150) NOT NULL,
  `id_jadwal` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Izin','Sakit') NOT NULL,
  `materi` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `presensi_mengajar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_presensi_mengajar` (`id_jadwal`,`tanggal`),
  ADD KEY `idx_jurnal_guru_tanggal` (`id_guru`,`tanggal`),
  ADD KEY `idx_jurnal_kelas_tanggal` (`id_kelas`,`tanggal`),
  ADD KEY `idx_jurnal_tahun` (`id_tahun`),
  ADD KEY `idx_jurnal_updated_by` (`updated_by`);

ALTER TABLE `presensi_mengajar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `presensi_mengajar`
  ADD CONSTRAINT `fk_jurnal_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 15.20 `ref_pelanggaran`

```sql
CREATE TABLE `ref_pelanggaran` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_pelanggaran` varchar(150) NOT NULL,
  `kategori` enum('Ringan','Sedang','Berat') NOT NULL,
  `poin` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `ref_pelanggaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ref_pelanggaran_nama` (`nama_pelanggaran`),
  ADD KEY `idx_ref_pelanggaran_kategori` (`kategori`);

ALTER TABLE `ref_pelanggaran`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.21 `riwayat_pangkat`

```sql
CREATE TABLE `riwayat_pangkat` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED DEFAULT NULL,
  `id_pegawai` int(10) UNSIGNED DEFAULT NULL,
  `golongan_ruang` varchar(30) NOT NULL,
  `nama_pangkat` varchar(100) DEFAULT NULL,
  `tmt_pangkat` date NOT NULL,
  `no_sk_pangkat` varchar(100) DEFAULT NULL,
  `file_sk_pangkat` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ;

ALTER TABLE `riwayat_pangkat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpk_guru` (`id_guru`),
  ADD KEY `idx_rpk_pegawai` (`id_pegawai`),
  ADD KEY `idx_rpk_tmt` (`tmt_pangkat`);

ALTER TABLE `riwayat_pangkat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `riwayat_pangkat`
  ADD CONSTRAINT `fk_rpk_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpk_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.22 `riwayat_pendidikan`

```sql
CREATE TABLE `riwayat_pendidikan` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED DEFAULT NULL,
  `id_pegawai` int(10) UNSIGNED DEFAULT NULL,
  `tingkat_pendidikan` varchar(20) NOT NULL,
  `nama_institusi` varchar(150) NOT NULL,
  `program_studi` varchar(150) DEFAULT NULL,
  `tahun_lulus` year(4) NOT NULL,
  `no_ijazah` varchar(100) DEFAULT NULL,
  `file_ijazah` varchar(255) DEFAULT NULL,
  `file_transkrip` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ;

ALTER TABLE `riwayat_pendidikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rp_guru` (`id_guru`),
  ADD KEY `idx_rp_pegawai` (`id_pegawai`),
  ADD KEY `idx_rp_tahun_lulus` (`tahun_lulus`);

ALTER TABLE `riwayat_pendidikan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `riwayat_pendidikan`
  ADD CONSTRAINT `fk_rp_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.23 `riwayat_penugasan`

```sql
CREATE TABLE `riwayat_penugasan` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_guru` int(10) UNSIGNED DEFAULT NULL,
  `id_pegawai` int(10) UNSIGNED DEFAULT NULL,
  `instansi_penugasan` varchar(150) NOT NULL,
  `jabatan_tugas` varchar(120) NOT NULL,
  `mata_pelajaran` varchar(120) DEFAULT NULL,
  `no_sk_penugasan` varchar(100) DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `file_sk_penugasan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ;

ALTER TABLE `riwayat_penugasan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpen_guru` (`id_guru`),
  ADD KEY `idx_rpen_pegawai` (`id_pegawai`),
  ADD KEY `idx_rpen_periode` (`tanggal_mulai`,`tanggal_selesai`);

ALTER TABLE `riwayat_penugasan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `riwayat_penugasan`
  ADD CONSTRAINT `fk_rpen_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpen_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.24 `riwayat_siswa`

```sql
CREATE TABLE `riwayat_siswa` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED NOT NULL,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `status` enum('Aktif','Pindah','Keluar','Lulus') NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `riwayat_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_riwayat_siswa_tahun` (`id_siswa`,`id_tahun`),
  ADD KEY `idx_riwayat_kelas` (`id_kelas`),
  ADD KEY `fk_riwayat_tahun` (`id_tahun`);

ALTER TABLE `riwayat_siswa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `riwayat_siswa`
  ADD CONSTRAINT `fk_riwayat_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE;
```

### 15.25 `role_menus`

```sql
CREATE TABLE `role_menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL,
  `id_menu` int(10) UNSIGNED NOT NULL,
  `tampil` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `role_menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_menu` (`role`,`id_menu`),
  ADD KEY `idx_role_menus_menu` (`id_menu`);

ALTER TABLE `role_menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `role_menus`
  ADD CONSTRAINT `fk_rm_menu` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.26 `role_permissions`

```sql
CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL,
  `id_permission` int(10) UNSIGNED NOT NULL,
  `scope` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_permission_scope` (`role`,`id_permission`,`scope`),
  ADD KEY `idx_role_permissions_permission` (`id_permission`),
  ADD KEY `idx_role_permissions_role` (`role`);

ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

### 15.27 `setting_sistem`

```sql
CREATE TABLE `setting_sistem` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `setting_sistem`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_type` (`type`),
  ADD KEY `idx_setting_updated_by` (`updated_by`);

ALTER TABLE `setting_sistem`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `setting_sistem`
  ADD CONSTRAINT `fk_setting_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 15.28 `siswa`

```sql
CREATE TABLE `siswa` (
  `id` int(10) UNSIGNED NOT NULL,
  `nik` varchar(16) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `kebutuhan_khusus` varchar(100) DEFAULT NULL,
  `disabilitas` varchar(100) DEFAULT NULL,
  `nomor_kip_pip` varchar(50) DEFAULT NULL,
  `nama_ayah_kandung` varchar(150) DEFAULT NULL,
  `nama_ibu_kandung` varchar(150) DEFAULT NULL,
  `nama_wali` varchar(150) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status_aktif` enum('Aktif','Lulus','Pindah','Keluar') NOT NULL DEFAULT 'Aktif',
  `tanggal_mutasi` date DEFAULT NULL,
  `keterangan_mutasi` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_siswa_nama` (`nama`),
  ADD KEY `idx_siswa_status` (`status_aktif`),
  ADD KEY `idx_siswa_deleted_at` (`deleted_at`);

ALTER TABLE `siswa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.29 `tahun_ajaran`

```sql
CREATE TABLE `tahun_ajaran` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_tahun` varchar(20) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tahun_semester` (`nama_tahun`,`semester`),
  ADD KEY `idx_tahun_aktif` (`status_aktif`),
  ADD KEY `idx_tahun_deleted_at` (`deleted_at`);

ALTER TABLE `tahun_ajaran`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 15.30 `tindak_lanjut_kasus`

```sql
CREATE TABLE `tindak_lanjut_kasus` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_kasus` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `tindak_lanjut` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_user_input` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tindak_lanjut_kasus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tindak_kasus_tanggal` (`id_kasus`,`tanggal`,`id`),
  ADD KEY `idx_tindak_user_input` (`id_user_input`);

ALTER TABLE `tindak_lanjut_kasus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `tindak_lanjut_kasus`
  ADD CONSTRAINT `fk_tindak_kasus` FOREIGN KEY (`id_kasus`) REFERENCES `catatan_kasus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tindak_user_input` FOREIGN KEY (`id_user_input`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 15.31 `users`

```sql
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','pimpinan','bk','guru','siswa') DEFAULT NULL,
  `id_guru` int(10) UNSIGNED DEFAULT NULL,
  `id_pegawai` int(10) UNSIGNED DEFAULT NULL,
  `id_siswa` int(10) UNSIGNED DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `auth_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uk_users_id_guru` (`id_guru`),
  ADD UNIQUE KEY `uk_users_id_pegawai` (`id_pegawai`),
  ADD UNIQUE KEY `uk_users_id_siswa` (`id_siswa`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_guru` (`id_guru`),
  ADD KEY `idx_users_pegawai` (`id_pegawai`),
  ADD KEY `idx_users_siswa` (`id_siswa`),
  ADD KEY `idx_users_status` (`status_aktif`);

ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE;
```

### 15.32 `user_roles`

```sql
CREATE TABLE `user_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_role` (`id_user`,`role`),
  ADD KEY `idx_user_roles_role` (`role`);

ALTER TABLE `user_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```
