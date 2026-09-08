# Database — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Baseline

Jumlah tabel pada dump: **27**.

Database menggunakan InnoDB dan utf8mb4.

# 2. Daftar Tabel

| No | Tabel |
|---:|---|

| 1 | `anggota_kelas` |
| 2 | `api_tokens` |
| 3 | `catatan_kasus` |
| 4 | `catatan_prestasi` |
| 5 | `ci_sessions` |
| 6 | `guru` |
| 7 | `jadwal_guru` |
| 8 | `kartu_pelajar` |
| 9 | `kelas` |
| 10 | `login_attempts` |
| 11 | `log_activity` |
| 12 | `mapping_wali_kelas` |
| 13 | `mata_pelajaran` |
| 14 | `menus` |
| 15 | `pegawai` |
| 16 | `permissions` |
| 17 | `presensi` |
| 18 | `presensi_mengajar` |
| 19 | `ref_pelanggaran` |
| 20 | `riwayat_siswa` |
| 21 | `role_menus` |
| 22 | `role_permissions` |
| 23 | `setting_sistem` |
| 24 | `siswa` |
| 25 | `tahun_ajaran` |
| 26 | `users` |
| 27 | `user_roles` |

# 3. Struktur Tabel

Definisi kolom berikut diambil dari dump baseline.


## 3.1 `anggota_kelas`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_anggota_siswa_tahun (id_siswa,id_tahun), ADD KEY idx_anggota_kelas (id_kelas), ADD KEY idx_anggota_tahun (id_tahun)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_anggota_kelas FOREIGN KEY (id_kelas) REFERENCES kelas (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_anggota_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_anggota_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE`

## 3.2 `api_tokens`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_user` | `int(10) UNSIGNED NOT NULL` |
| `token` | `varchar(255) NOT NULL` |
| `refresh_token` | `varchar(255) NOT NULL` |
| `device_name` | `varchar(100) DEFAULT NULL` |
| `expires_at` | `datetime NOT NULL` |
| `refresh_expires_at` | `datetime NOT NULL` |
| `revoked_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY token (token), ADD UNIQUE KEY refresh_token (refresh_token), ADD KEY idx_api_tokens_user (id_user), ADD KEY idx_api_tokens_expiry (expires_at), ADD KEY idx_api_tokens_refresh_expiry (refresh_expires_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4`
- `ADD CONSTRAINT fk_api_tokens_user FOREIGN KEY (id_user) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE`

## 3.3 `catatan_kasus`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `id_pelanggaran` | `int(10) UNSIGNED NOT NULL` |
| `tanggal` | `date NOT NULL` |
| `keterangan` | `text DEFAULT NULL` |
| `id_guru_input` | `int(10) UNSIGNED DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |
| `updated_by` | `int(10) UNSIGNED DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_kasus_siswa_tanggal (id_siswa,tanggal), ADD KEY idx_kasus_pelanggaran (id_pelanggaran), ADD KEY idx_kasus_guru_input (id_guru_input), ADD KEY idx_kasus_updated_by (updated_by)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_kasus_guru_input FOREIGN KEY (id_guru_input) REFERENCES guru (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_kasus_pelanggaran FOREIGN KEY (id_pelanggaran) REFERENCES ref_pelanggaran (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_kasus_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_kasus_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE`

## 3.4 `catatan_prestasi`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `nama_prestasi` | `varchar(200) NOT NULL` |
| `tingkat` | `varchar(100) DEFAULT NULL` |
| `tanggal` | `date NOT NULL` |
| `penyelenggara` | `varchar(200) DEFAULT NULL` |
| `keterangan` | `text DEFAULT NULL` |
| `id_guru_input` | `int(10) UNSIGNED DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_prestasi_siswa_tanggal (id_siswa,tanggal), ADD KEY idx_prestasi_guru_input (id_guru_input)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_prestasi_guru_input FOREIGN KEY (id_guru_input) REFERENCES guru (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_prestasi_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON UPDATE CASCADE`

## 3.5 `ci_sessions`

| Kolom | Definisi |
|---|---|
| `id` | `varchar(128) NOT NULL` |
| `ip_address` | `varchar(45) NOT NULL` |
| `timestamp` | `int(10) UNSIGNED NOT NULL DEFAULT 0` |
| `data` | `blob NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY ci_sessions_timestamp (timestamp)`

## 3.6 `guru`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nip` | `varchar(30) NOT NULL` |
| `nama` | `varchar(150) NOT NULL` |
| `jenis_kelamin` | `enum('L','P') NOT NULL` |
| `tempat_lahir` | `varchar(100) DEFAULT NULL` |
| `tanggal_lahir` | `date DEFAULT NULL` |
| `alamat` | `text DEFAULT NULL` |
| `no_telepon` | `varchar(20) DEFAULT NULL` |
| `email` | `varchar(100) DEFAULT NULL` |
| `status_kepegawaian` | `enum('PNS','PPPK','NON ASN','Yayasan','Outsourcing') DEFAULT NULL` |
| `foto` | `varchar(255) DEFAULT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY nip (nip), ADD KEY idx_guru_nama (nama), ADD KEY idx_guru_status_kepegawaian (status_kepegawaian), ADD KEY idx_guru_deleted_at (deleted_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`

## 3.7 `jadwal_guru`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `id_mapel` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |
| `hari` | `enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL` |
| `jam_mulai` | `time NOT NULL` |
| `jam_selesai` | `time NOT NULL` |
| `sesi` | `enum('Sesi Awal','Sesi Akhir','Non Sesi') NOT NULL` |
| `status_jadwal` | `enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif'` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_jadwal_guru_hari (id_guru,id_tahun,hari,status_jadwal), ADD KEY idx_jadwal_kelas_hari (id_kelas,id_tahun,hari,status_jadwal), ADD KEY idx_jadwal_mapel (id_mapel), ADD KEY idx_jadwal_waktu (jam_mulai,jam_selesai), ADD KEY fk_jadwal_tahun (id_tahun)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_jadwal_guru FOREIGN KEY (id_guru) REFERENCES guru (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_jadwal_kelas FOREIGN KEY (id_kelas) REFERENCES kelas (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_jadwal_mapel FOREIGN KEY (id_mapel) REFERENCES mata_pelajaran (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_jadwal_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE`

## 3.8 `kartu_pelajar`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `nomor_kartu` | `varchar(50) NOT NULL` |
| `kode_verifikasi` | `varchar(100) NOT NULL` |
| `tanggal_terbit` | `date NOT NULL` |
| `status_aktif` | `enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif'` |
| `id_siswa_aktif` | `int(10) UNSIGNED GENERATED ALWAYS AS (case when `status_aktif` = 'Aktif' then `id_siswa` else NULL end) STORED` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY nomor_kartu (nomor_kartu), ADD UNIQUE KEY kode_verifikasi (kode_verifikasi), ADD UNIQUE KEY uq_kartu_siswa_aktif (id_siswa_aktif), ADD KEY idx_kartu_siswa (id_siswa), ADD KEY idx_kartu_status (status_aktif)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_kartu_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON UPDATE CASCADE`

## 3.9 `kelas`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `tingkat` | `enum('7','8','9') NOT NULL` |
| `rombel` | `varchar(10) NOT NULL` |
| `nama_kelas` | `varchar(20) NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_kelas_tahun_nama (id_tahun,nama_kelas), ADD KEY idx_kelas_tahun (id_tahun), ADD KEY idx_kelas_tingkat (tingkat), ADD KEY idx_kelas_deleted_at (deleted_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_kelas_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE`

## 3.10 `login_attempts`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `username` | `varchar(50) NOT NULL` |
| `ip_address` | `varchar(45) NOT NULL` |
| `waktu` | `datetime NOT NULL` |
| `berhasil` | `tinyint(1) NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_login_attempts_lookup (username,ip_address,waktu), ADD KEY idx_login_attempts_waktu (waktu)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34`

## 3.11 `log_activity`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_user` | `int(10) UNSIGNED DEFAULT NULL` |
| `aksi` | `varchar(100) NOT NULL` |
| `modul` | `varchar(100) NOT NULL` |
| `keterangan` | `text DEFAULT NULL` |
| `waktu` | `datetime NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_log_waktu (waktu), ADD KEY idx_log_user_waktu (id_user,waktu), ADD KEY idx_log_modul_waktu (modul,waktu)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19`
- `ADD CONSTRAINT fk_log_user FOREIGN KEY (id_user) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE`

## 3.12 `mapping_wali_kelas`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |
| `uk_guru_aktif` | `int(10) UNSIGNED GENERATED ALWAYS AS (case when `deleted_at` is null then `id_guru` else NULL end) STORED` |
| `uk_kelas_aktif` | `int(10) UNSIGNED GENERATED ALWAYS AS (case when `deleted_at` is null then `id_kelas` else NULL end) STORED` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_wali_guru_aktif (id_tahun,uk_guru_aktif), ADD UNIQUE KEY uk_wali_kelas_aktif (id_tahun,uk_kelas_aktif), ADD KEY idx_wali_guru (id_guru,id_tahun), ADD KEY idx_wali_kelas (id_kelas,id_tahun), ADD KEY idx_wali_deleted_at (deleted_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_wali_guru FOREIGN KEY (id_guru) REFERENCES guru (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_wali_kelas FOREIGN KEY (id_kelas) REFERENCES kelas (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_wali_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE`

## 3.13 `mata_pelajaran`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_mapel` | `varchar(100) NOT NULL` |
| `kode_mapel` | `varchar(10) NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY kode_mapel (kode_mapel), ADD KEY idx_mapel_nama (nama_mapel)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18`

## 3.14 `menus`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_menu` | `varchar(100) NOT NULL` |
| `parent_id` | `int(10) UNSIGNED DEFAULT NULL` |
| `urutan` | `int(11) NOT NULL DEFAULT 0` |
| `icon` | `varchar(100) DEFAULT NULL` |
| `link` | `varchar(100) DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_menus_parent (parent_id), ADD KEY idx_menus_urutan (urutan)`
- `ADD CONSTRAINT fk_menus_parent FOREIGN KEY (parent_id) REFERENCES menus (id) ON DELETE CASCADE ON UPDATE CASCADE`

## 3.15 `pegawai`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nip` | `varchar(30) NOT NULL` |
| `nama` | `varchar(150) NOT NULL` |
| `jenis_kelamin` | `enum('L','P') NOT NULL` |
| `tempat_lahir` | `varchar(100) DEFAULT NULL` |
| `tanggal_lahir` | `date DEFAULT NULL` |
| `alamat` | `text DEFAULT NULL` |
| `no_telepon` | `varchar(20) DEFAULT NULL` |
| `email` | `varchar(100) DEFAULT NULL` |
| `jabatan` | `varchar(100) DEFAULT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY nip (nip), ADD KEY idx_pegawai_nama (nama), ADD KEY idx_pegawai_jabatan (jabatan), ADD KEY idx_pegawai_deleted_at (deleted_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`

## 3.16 `permissions`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `permission_key` | `varchar(100) NOT NULL` |
| `nama` | `varchar(100) NOT NULL` |
| `modul` | `varchar(50) NOT NULL` |
| `scope_didukung` | `varchar(100) NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY permission_key (permission_key), ADD KEY idx_permissions_modul (modul)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44`

## 3.17 `presensi`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED DEFAULT NULL` |
| `nama_siswa_snapshot` | `varchar(150) NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |
| `tanggal` | `date NOT NULL` |
| `sesi` | `enum('Sesi Awal','Sesi Akhir') NOT NULL` |
| `status` | `enum('Hadir','Sakit','Izin','Alpha') NOT NULL` |
| `id_guru_input` | `int(10) UNSIGNED DEFAULT NULL` |
| `nama_guru_input_snapshot` | `varchar(150) DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |
| `updated_by` | `int(10) UNSIGNED DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_presensi (id_kelas,tanggal,sesi,id_siswa), ADD KEY idx_presensi_siswa_tahun_tanggal (id_siswa,id_tahun,tanggal), ADD KEY idx_presensi_kelas_tanggal (id_kelas,tanggal), ADD KEY idx_presensi_laporan_awal (id_tahun,sesi,tanggal,status), ADD KEY idx_presensi_guru_input (id_guru_input), ADD KEY idx_presensi_updated_by (updated_by), ADD KEY idx_presensi_kelas_periode (id_tahun,id_kelas,sesi,tanggal), ADD KEY idx_presensi_siswa_periode (id_siswa,id_tahun,sesi,tanggal)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_presensi_guru_input FOREIGN KEY (id_guru_input) REFERENCES guru (id) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT fk_presensi_kelas FOREIGN KEY (id_kelas) REFERENCES kelas (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_presensi_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT fk_presensi_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_presensi_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE`

## 3.18 `presensi_mengajar`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED DEFAULT NULL` |
| `nama_guru_snapshot` | `varchar(150) NOT NULL` |
| `id_jadwal` | `int(10) UNSIGNED NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |
| `tanggal` | `date NOT NULL` |
| `status` | `enum('Hadir','Izin','Sakit') NOT NULL` |
| `materi` | `text NOT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |
| `updated_by` | `int(10) UNSIGNED DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_presensi_mengajar (id_jadwal,tanggal), ADD KEY idx_jurnal_guru_tanggal (id_guru,tanggal), ADD KEY idx_jurnal_kelas_tanggal (id_kelas,tanggal), ADD KEY idx_jurnal_tahun (id_tahun), ADD KEY idx_jurnal_updated_by (updated_by)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_jurnal_guru FOREIGN KEY (id_guru) REFERENCES guru (id) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT fk_jurnal_jadwal FOREIGN KEY (id_jadwal) REFERENCES jadwal_guru (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_jurnal_kelas FOREIGN KEY (id_kelas) REFERENCES kelas (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_jurnal_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_jurnal_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE`

## 3.19 `ref_pelanggaran`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_pelanggaran` | `varchar(150) NOT NULL` |
| `kategori` | `enum('Ringan','Sedang','Berat') NOT NULL` |
| `poin` | `int(11) NOT NULL DEFAULT 0` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_ref_pelanggaran_nama (nama_pelanggaran), ADD KEY idx_ref_pelanggaran_kategori (kategori)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13`

## 3.20 `riwayat_siswa`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `status` | `enum('Aktif','Pindah','Keluar','Lulus') NOT NULL` |
| `tanggal_mulai` | `date NOT NULL` |
| `tanggal_selesai` | `date DEFAULT NULL` |
| `keterangan` | `text DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD KEY idx_riwayat_siswa_tahun (id_siswa,id_tahun), ADD KEY idx_riwayat_kelas (id_kelas), ADD KEY fk_riwayat_tahun (id_tahun)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`
- `ADD CONSTRAINT fk_riwayat_kelas FOREIGN KEY (id_kelas) REFERENCES kelas (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_riwayat_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_riwayat_tahun FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran (id) ON UPDATE CASCADE`

## 3.21 `role_menus`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL` |
| `id_menu` | `int(10) UNSIGNED NOT NULL` |
| `tampil` | `tinyint(1) NOT NULL DEFAULT 1` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_role_menu (role,id_menu), ADD KEY idx_role_menus_menu (id_menu)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126`
- `ADD CONSTRAINT fk_rm_menu FOREIGN KEY (id_menu) REFERENCES menus (id) ON DELETE CASCADE ON UPDATE CASCADE`

## 3.22 `role_permissions`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL` |
| `id_permission` | `int(10) UNSIGNED NOT NULL` |
| `scope` | `varchar(50) NOT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_role_permission_scope (role,id_permission,scope), ADD KEY idx_role_permissions_permission (id_permission), ADD KEY idx_role_permissions_role (role)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139`
- `ADD CONSTRAINT fk_rp_permission FOREIGN KEY (id_permission) REFERENCES permissions (id) ON DELETE CASCADE ON UPDATE CASCADE`

## 3.23 `setting_sistem`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `setting_key` | `varchar(100) NOT NULL` |
| `setting_value` | `text NOT NULL` |
| `type` | `varchar(20) NOT NULL DEFAULT 'string'` |
| `updated_at` | `datetime DEFAULT NULL` |
| `updated_by` | `int(10) UNSIGNED DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY setting_key (setting_key), ADD KEY idx_setting_type (type), ADD KEY idx_setting_updated_by (updated_by)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13`
- `ADD CONSTRAINT fk_setting_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE`

## 3.24 `siswa`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nik` | `varchar(16) NOT NULL` |
| `nisn` | `varchar(20) NOT NULL` |
| `nama` | `varchar(150) NOT NULL` |
| `jenis_kelamin` | `enum('L','P') NOT NULL` |
| `tempat_lahir` | `varchar(100) DEFAULT NULL` |
| `tanggal_lahir` | `date DEFAULT NULL` |
| `alamat` | `text DEFAULT NULL` |
| `no_telepon` | `varchar(20) DEFAULT NULL` |
| `kebutuhan_khusus` | `varchar(100) DEFAULT NULL` |
| `disabilitas` | `varchar(100) DEFAULT NULL` |
| `nomor_kip_pip` | `varchar(50) DEFAULT NULL` |
| `nama_ayah_kandung` | `varchar(150) DEFAULT NULL` |
| `nama_ibu_kandung` | `varchar(150) DEFAULT NULL` |
| `nama_wali` | `varchar(150) DEFAULT NULL` |
| `foto` | `varchar(255) DEFAULT NULL` |
| `status_aktif` | `enum('Aktif','Lulus','Pindah','Keluar') NOT NULL DEFAULT 'Aktif'` |
| `tanggal_mutasi` | `date DEFAULT NULL` |
| `keterangan_mutasi` | `text DEFAULT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY nik (nik), ADD UNIQUE KEY nisn (nisn), ADD KEY idx_siswa_nama (nama), ADD KEY idx_siswa_status (status_aktif), ADD KEY idx_siswa_deleted_at (deleted_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT`

## 3.25 `tahun_ajaran`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_tahun` | `varchar(20) NOT NULL` |
| `semester` | `enum('Ganjil','Genap') NOT NULL` |
| `status_aktif` | `tinyint(1) NOT NULL DEFAULT 0` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_tahun_semester (nama_tahun,semester), ADD KEY idx_tahun_aktif (status_aktif), ADD KEY idx_tahun_deleted_at (deleted_at)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2`

## 3.26 `users`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `username` | `varchar(50) NOT NULL` |
| `password` | `varchar(255) NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') DEFAULT NULL` |
| `id_guru` | `int(10) UNSIGNED DEFAULT NULL` |
| `id_pegawai` | `int(10) UNSIGNED DEFAULT NULL` |
| `id_siswa` | `int(10) UNSIGNED DEFAULT NULL` |
| `status_aktif` | `tinyint(1) NOT NULL DEFAULT 1` |
| `auth_version` | `int(10) UNSIGNED NOT NULL DEFAULT 1` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY username (username), ADD KEY idx_users_role (role), ADD KEY idx_users_guru (id_guru), ADD KEY idx_users_pegawai (id_pegawai), ADD KEY idx_users_siswa (id_siswa), ADD KEY idx_users_status (status_aktif)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2`
- `ADD CONSTRAINT fk_user_guru FOREIGN KEY (id_guru) REFERENCES guru (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_user_pegawai FOREIGN KEY (id_pegawai) REFERENCES pegawai (id) ON UPDATE CASCADE, ADD CONSTRAINT fk_user_siswa FOREIGN KEY (id_siswa) REFERENCES siswa (id) ON UPDATE CASCADE`

## 3.27 `user_roles`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_user` | `int(10) UNSIGNED NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL` |
| `created_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint baseline:**
- `ADD PRIMARY KEY (id), ADD UNIQUE KEY uk_user_role (id_user,role), ADD KEY idx_user_roles_role (role)`
- `MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2`
- `ADD CONSTRAINT fk_user_roles_user FOREIGN KEY (id_user) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE`

# 4. Permission

Jumlah permission: **43**.

| ID | Permission | Modul | Scope Didukung |
|---:|---|---|---|

| 1 | `dashboard.view` | Dashboard | `Otomatis` |
| 2 | `presensi_siswa.input` | Presensi Siswa | `SEMUA,KELAS_DIAMPU,KELAS_TERJADWAL` |
| 3 | `presensi_siswa.revisi` | Presensi Siswa | `SEMUA,KELAS_DIAMPU` |
| 4 | `presensi_siswa.view` | Presensi Siswa | `SEMUA,KELAS_DIAMPU,KELAS_TERJADWAL,DIRI_SENDIRI` |
| 5 | `presensi_mengajar.input` | Presensi Mengajar | `SEMUA,KELAS_TERJADWAL,DIRI_SENDIRI` |
| 6 | `presensi_mengajar.view` | Presensi Mengajar | `SEMUA,DIRI_SENDIRI` |
| 7 | `master_guru.manage` | Master Data | `SEMUA` |
| 8 | `master_guru.view` | Master Data | `SEMUA` |
| 9 | `master_pegawai.manage` | Master Data | `SEMUA` |
| 10 | `master_pegawai.view` | Master Data | `SEMUA` |
| 11 | `master_siswa.view` | Master Data | `SEMUA,KELAS_DIAMPU,DIRI_SENDIRI` |
| 12 | `master_siswa.edit_biodata` | Master Data | `SEMUA,KELAS_DIAMPU` |
| 13 | `master_siswa.manage` | Master Data | `SEMUA` |
| 14 | `master_siswa.import_export` | Master Data | `SEMUA` |
| 15 | `master_kelas.manage` | Master Data | `SEMUA` |
| 16 | `master_tahun_ajaran.manage` | Master Data | `SEMUA` |
| 17 | `master_mapel.manage` | Master Data | `SEMUA` |
| 18 | `mapping_wali.manage` | Master Data | `SEMUA` |
| 19 | `mapping_wali.view` | Master Data | `DIRI_SENDIRI` |
| 20 | `mapping_wali.view_all` | Master Data | `SEMUA` |
| 21 | `jadwal_guru.manage` | Master Data | `SEMUA` |
| 22 | `jadwal_guru.view` | Master Data | `DIRI_SENDIRI` |
| 23 | `jadwal_guru.view_all` | Master Data | `SEMUA` |
| 24 | `laporan_matrix.view` | Laporan | `SEMUA,KELAS_DIAMPU` |
| 25 | `laporan_export.generate` | Laporan | `SEMUA,KELAS_DIAMPU` |
| 26 | `laporan_jurnal.view` | Laporan | `SEMUA,DIRI_SENDIRI` |
| 27 | `laporan_jurnal.export` | Laporan | `SEMUA,DIRI_SENDIRI` |
| 28 | `ews_radar.view` | Presensi | `SEMUA,KELAS_DIAMPU` |
| 29 | `bk_kasus.manage` | BK | `SEMUA` |
| 30 | `bk_kasus.view` | BK | `SEMUA,KELAS_DIAMPU,DIRI_SENDIRI` |
| 31 | `bk_pelanggaran_master.manage` | BK | `SEMUA` |
| 32 | `prestasi.manage` | BK | `SEMUA` |
| 33 | `prestasi.view` | BK | `SEMUA,KELAS_DIAMPU,DIRI_SENDIRI` |
| 34 | `kartu_pelajar.manage` | Kartu Pelajar | `SEMUA,KELAS_DIAMPU` |
| 35 | `kartu_pelajar.view` | Kartu Pelajar | `SEMUA,KELAS_DIAMPU,DIRI_SENDIRI` |
| 36 | `settings_user.manage` | Settings | `SEMUA` |
| 37 | `settings_menu.manage` | Settings | `SEMUA` |
| 38 | `settings_sistem.manage` | Settings | `SEMUA` |
| 39 | `backup.manage` | Backup | `SEMUA` |
| 40 | `log_activity.view` | Backup | `SEMUA` |
| 41 | `profile_guru.view` | Profile | `DIRI_SENDIRI` |
| 42 | `profile_guru.edit` | Profile | `DIRI_SENDIRI` |
| 43 | `profile_siswa.view` | Profile | `DIRI_SENDIRI` |

# 5. Role Permission

Permission efektif user adalah union primary role + secondary roles.


## 5.1 `admin`

| Permission | Scope |
|---|---|
| `dashboard.view` | `SEMUA` |
| `presensi_siswa.input` | `SEMUA` |
| `presensi_siswa.revisi` | `SEMUA` |
| `presensi_siswa.view` | `SEMUA` |
| `presensi_mengajar.input` | `SEMUA` |
| `presensi_mengajar.view` | `SEMUA` |
| `master_guru.manage` | `SEMUA` |
| `master_guru.view` | `SEMUA` |
| `master_pegawai.manage` | `SEMUA` |
| `master_pegawai.view` | `SEMUA` |
| `master_siswa.view` | `SEMUA` |
| `master_siswa.edit_biodata` | `SEMUA` |
| `master_siswa.manage` | `SEMUA` |
| `master_siswa.import_export` | `SEMUA` |
| `master_kelas.manage` | `SEMUA` |
| `master_tahun_ajaran.manage` | `SEMUA` |
| `master_mapel.manage` | `SEMUA` |
| `mapping_wali.manage` | `SEMUA` |
| `mapping_wali.view` | `DIRI_SENDIRI` |
| `mapping_wali.view_all` | `SEMUA` |
| `jadwal_guru.manage` | `SEMUA` |
| `jadwal_guru.view` | `DIRI_SENDIRI` |
| `jadwal_guru.view_all` | `SEMUA` |
| `laporan_matrix.view` | `SEMUA` |
| `laporan_export.generate` | `SEMUA` |
| `laporan_jurnal.view` | `SEMUA` |
| `laporan_jurnal.export` | `SEMUA` |
| `ews_radar.view` | `SEMUA` |
| `bk_kasus.manage` | `SEMUA` |
| `bk_kasus.view` | `SEMUA` |
| `bk_pelanggaran_master.manage` | `SEMUA` |
| `prestasi.manage` | `SEMUA` |
| `prestasi.view` | `SEMUA` |
| `kartu_pelajar.manage` | `SEMUA` |
| `kartu_pelajar.view` | `SEMUA` |
| `settings_user.manage` | `SEMUA` |
| `settings_menu.manage` | `SEMUA` |
| `settings_sistem.manage` | `SEMUA` |
| `backup.manage` | `SEMUA` |
| `log_activity.view` | `SEMUA` |
| `profile_guru.view` | `DIRI_SENDIRI` |
| `profile_guru.edit` | `DIRI_SENDIRI` |
| `profile_siswa.view` | `DIRI_SENDIRI` |

## 5.2 `operator`

| Permission | Scope |
|---|---|
| `dashboard.view` | `SEMUA` |
| `presensi_siswa.input` | `SEMUA` |
| `presensi_siswa.revisi` | `SEMUA` |
| `presensi_siswa.view` | `SEMUA` |
| `presensi_mengajar.input` | `SEMUA` |
| `presensi_mengajar.view` | `SEMUA` |
| `master_guru.manage` | `SEMUA` |
| `master_guru.view` | `SEMUA` |
| `master_pegawai.manage` | `SEMUA` |
| `master_pegawai.view` | `SEMUA` |
| `master_siswa.view` | `SEMUA` |
| `master_siswa.edit_biodata` | `SEMUA` |
| `master_siswa.manage` | `SEMUA` |
| `master_siswa.import_export` | `SEMUA` |
| `master_kelas.manage` | `SEMUA` |
| `master_tahun_ajaran.manage` | `SEMUA` |
| `master_mapel.manage` | `SEMUA` |
| `mapping_wali.manage` | `SEMUA` |
| `mapping_wali.view` | `SEMUA` |
| `mapping_wali.view_all` | `SEMUA` |
| `jadwal_guru.manage` | `SEMUA` |
| `jadwal_guru.view` | `SEMUA` |
| `jadwal_guru.view_all` | `SEMUA` |
| `laporan_matrix.view` | `SEMUA` |
| `laporan_export.generate` | `SEMUA` |
| `laporan_jurnal.view` | `SEMUA` |
| `laporan_jurnal.export` | `SEMUA` |
| `ews_radar.view` | `SEMUA` |
| `bk_kasus.manage` | `SEMUA` |
| `bk_kasus.view` | `SEMUA` |
| `bk_pelanggaran_master.manage` | `SEMUA` |
| `prestasi.manage` | `SEMUA` |
| `prestasi.view` | `SEMUA` |
| `kartu_pelajar.manage` | `SEMUA` |
| `kartu_pelajar.view` | `SEMUA` |
| `log_activity.view` | `SEMUA` |
| `profile_guru.view` | `DIRI_SENDIRI` |
| `profile_guru.edit` | `DIRI_SENDIRI` |

## 5.3 `pimpinan`

| Permission | Scope |
|---|---|
| `dashboard.view` | `SEMUA` |
| `presensi_siswa.view` | `SEMUA` |
| `presensi_mengajar.input` | `DIRI_SENDIRI` |
| `presensi_mengajar.view` | `SEMUA` |
| `master_guru.view` | `SEMUA` |
| `master_pegawai.view` | `SEMUA` |
| `master_siswa.view` | `SEMUA` |
| `mapping_wali.view` | `DIRI_SENDIRI` |
| `mapping_wali.view_all` | `SEMUA` |
| `jadwal_guru.view` | `DIRI_SENDIRI` |
| `jadwal_guru.view_all` | `SEMUA` |
| `laporan_matrix.view` | `SEMUA` |
| `laporan_export.generate` | `SEMUA` |
| `laporan_jurnal.view` | `SEMUA` |
| `laporan_jurnal.export` | `SEMUA` |
| `ews_radar.view` | `SEMUA` |
| `bk_kasus.view` | `SEMUA` |
| `prestasi.view` | `SEMUA` |
| `kartu_pelajar.view` | `SEMUA` |
| `profile_guru.view` | `DIRI_SENDIRI` |
| `profile_guru.edit` | `DIRI_SENDIRI` |

## 5.4 `bk`

| Permission | Scope |
|---|---|
| `dashboard.view` | `SEMUA` |
| `ews_radar.view` | `SEMUA` |
| `bk_kasus.manage` | `SEMUA` |
| `bk_kasus.view` | `SEMUA` |
| `bk_pelanggaran_master.manage` | `SEMUA` |
| `prestasi.manage` | `SEMUA` |
| `prestasi.view` | `SEMUA` |
| `profile_guru.view` | `DIRI_SENDIRI` |
| `profile_guru.edit` | `DIRI_SENDIRI` |

## 5.5 `guru`

| Permission | Scope |
|---|---|
| `dashboard.view` | `DIRI_SENDIRI` |
| `presensi_siswa.input` | `KELAS_DIAMPU` |
| `presensi_siswa.input` | `KELAS_TERJADWAL` |
| `presensi_siswa.revisi` | `KELAS_DIAMPU` |
| `presensi_siswa.view` | `KELAS_DIAMPU` |
| `presensi_mengajar.input` | `DIRI_SENDIRI` |
| `presensi_mengajar.view` | `DIRI_SENDIRI` |
| `master_siswa.view` | `KELAS_DIAMPU` |
| `master_siswa.edit_biodata` | `KELAS_DIAMPU` |
| `mapping_wali.view` | `DIRI_SENDIRI` |
| `jadwal_guru.view` | `DIRI_SENDIRI` |
| `laporan_matrix.view` | `KELAS_DIAMPU` |
| `laporan_export.generate` | `KELAS_DIAMPU` |
| `laporan_jurnal.view` | `DIRI_SENDIRI` |
| `laporan_jurnal.export` | `DIRI_SENDIRI` |
| `ews_radar.view` | `KELAS_DIAMPU` |
| `bk_kasus.view` | `KELAS_DIAMPU` |
| `prestasi.view` | `KELAS_DIAMPU` |
| `kartu_pelajar.view` | `KELAS_DIAMPU` |
| `profile_guru.view` | `DIRI_SENDIRI` |
| `profile_guru.edit` | `DIRI_SENDIRI` |

## 5.6 `siswa`

| Permission | Scope |
|---|---|
| `dashboard.view` | `DIRI_SENDIRI` |
| `presensi_siswa.view` | `DIRI_SENDIRI` |
| `bk_kasus.view` | `DIRI_SENDIRI` |
| `prestasi.view` | `DIRI_SENDIRI` |
| `kartu_pelajar.view` | `DIRI_SENDIRI` |
| `profile_siswa.view` | `DIRI_SENDIRI` |

# 6. Menu Baseline

`menus` adalah katalog menu. `role_menus` menentukan menu statis. Permission tetap authorization boundary.


## 6.1 `admin`

| Menu | Link |
|---|---|
| Dashboard | `dashboard` |
| Presensi | `#` |
| Presensi Siswa | `presensi/siswa` |
| Presensi Mengajar | `presensi/mengajar` |
| Master Data | `#` |
| Data Guru | `master/guru` |
| Data Pegawai | `master/pegawai` |
| Data Siswa | `master/siswa` |
| Data Kelas | `master/kelas` |
| Tahun Ajaran | `master/tahun` |
| Mata Pelajaran | `master/mapel` |
| Mapping Wali Kelas | `master/wali-kelas` |
| Jadwal Guru | `master/jadwal` |
| Laporan | `#` |
| Matrix Presensi | `laporan/presensi/matrix` |
| Export Presensi | `laporan/presensi/export` |
| Laporan Jurnal | `laporan/jurnal` |
| BK & Prestasi | `#` |
| Catatan Kasus | `bk/kasus` |
| Master Pelanggaran | `bk/pelanggaran` |
| Prestasi Siswa | `bk/prestasi` |
| Kartu Pelajar | `#` |
| Daftar Kartu | `kartu/daftar` |
| Terbitkan Kartu | `kartu/daftar` |
| Settings | `#` |
| Manajemen User | `settings/user` |
| Menu & Role | `settings/menu` |
| Setting Sistem | `settings/sistem` |
| Backup & Log | `#` |
| Backup | `backup` |
| Log Activity | `log/activity` |
| Rekap Presensi | `presensi/siswa/rekap` |
| EWS Radar | `presensi/siswa/ews` |

## 6.2 `operator`

| Menu | Link |
|---|---|
| Dashboard | `dashboard` |
| Presensi | `#` |
| Presensi Siswa | `presensi/siswa` |
| Presensi Mengajar | `presensi/mengajar` |
| Master Data | `#` |
| Data Guru | `master/guru` |
| Data Pegawai | `master/pegawai` |
| Data Siswa | `master/siswa` |
| Data Kelas | `master/kelas` |
| Tahun Ajaran | `master/tahun` |
| Mata Pelajaran | `master/mapel` |
| Mapping Wali Kelas | `master/wali-kelas` |
| Jadwal Guru | `master/jadwal` |
| Laporan | `#` |
| Matrix Presensi | `laporan/presensi/matrix` |
| Export Presensi | `laporan/presensi/export` |
| Laporan Jurnal | `laporan/jurnal` |
| BK & Prestasi | `#` |
| Catatan Kasus | `bk/kasus` |
| Master Pelanggaran | `bk/pelanggaran` |
| Prestasi Siswa | `bk/prestasi` |
| Kartu Pelajar | `#` |
| Daftar Kartu | `kartu/daftar` |
| Terbitkan Kartu | `kartu/daftar` |
| Rekap Presensi | `presensi/siswa/rekap` |
| EWS Radar | `presensi/siswa/ews` |

## 6.3 `pimpinan`

| Menu | Link |
|---|---|
| Dashboard | `dashboard` |
| Presensi | `#` |
| Presensi Mengajar | `presensi/mengajar` |
| Master Data | `#` |
| Data Guru | `master/guru` |
| Data Pegawai | `master/pegawai` |
| Data Siswa | `master/siswa` |
| Mapping Wali Kelas | `master/wali-kelas` |
| Jadwal Guru | `master/jadwal` |
| Laporan | `#` |
| Matrix Presensi | `laporan/presensi/matrix` |
| Laporan Jurnal | `laporan/jurnal` |
| BK & Prestasi | `#` |
| Catatan Kasus | `bk/kasus` |
| Prestasi Siswa | `bk/prestasi` |
| Kartu Pelajar | `#` |
| Daftar Kartu | `kartu/daftar` |
| Profile Guru | `profile/guru` |
| Rekap Presensi | `presensi/siswa/rekap` |
| EWS Radar | `presensi/siswa/ews` |
| Export Presensi | `laporan/presensi/export` |

## 6.4 `bk`

| Menu | Link |
|---|---|
| Dashboard | `dashboard` |
| BK & Prestasi | `#` |
| Catatan Kasus | `bk/kasus` |
| Master Pelanggaran | `bk/pelanggaran` |
| Prestasi Siswa | `bk/prestasi` |
| Profile Guru | `profile/guru` |
| Presensi | `#` |
| EWS Radar | `presensi/siswa/ews` |

## 6.5 `guru`

| Menu | Link |
|---|---|
| Dashboard | `dashboard` |
| Presensi | `#` |
| Presensi Siswa | `presensi/siswa` |
| Presensi Mengajar | `presensi/mengajar` |
| Mapping Wali Kelas | `master/wali-kelas` |
| Jadwal Guru | `master/jadwal` |
| Profile Guru | `profile/guru` |
| Master Data | `#` |
| Data Siswa | `master/siswa` |
| Rekap Presensi | `presensi/siswa/rekap` |
| EWS Radar | `presensi/siswa/ews` |
| Matrix Presensi | `laporan/presensi/matrix` |
| Export Presensi | `laporan/presensi/export` |
| Laporan Jurnal | `laporan/jurnal` |
| Laporan | `#` |
| Catatan Kasus | `bk/kasus` |
| Daftar Kartu | `kartu/daftar` |
| Prestasi Siswa | `bk/prestasi` |
| BK & Prestasi | `#` |
| Kartu Pelajar | `#` |

## 6.6 `siswa`

| Menu | Link |
|---|---|
| Dashboard | `dashboard` |
| Kartu Pelajar | `#` |
| Daftar Kartu | `kartu/daftar` |
| Profile Siswa | `profile/siswa` |
| Presensi | `#` |
| Rekap Presensi | `presensi/siswa/rekap` |
| Catatan Kasus | `bk/kasus` |
| Prestasi Siswa | `bk/prestasi` |
| BK & Prestasi | `#` |

**Baseline penting:** Operator mempunyai `log_activity.view`, tetapi `role_menus` tidak memberikan menu `Backup & Log`/`Log Activity`. Route `/log/activity` tetap authorized, namun link tidak muncul di sidebar Operator.

# 7. Setting Sistem

| Key | Type | Nilai Baseline |
|---|---|---|

| `geofencing_aktif` | `boolean` | `1` |
| `latitude_sekolah` | `decimal` | `-7.533383` |
| `longitude_sekolah` | `decimal` | `112.217607` |
| `radius_geofencing` | `decimal` | `500` |
| `maintenance_mode` | `boolean` | `0` |
| `maintenance_message` | `string` | `Sistem sedang dalam pemeliharaan...` |
| `logo_sekolah` | `string` | `uploads/settings/branding/logo_20260908_181247_ce029c58.png` |
| `icon_sekolah` | `string` | `uploads/settings/branding/icon_20260908_181304_16c7d215.png` |
| `background_kta_depan` | `string` | `` |
| `background_kta_belakang` | `string` | `` |
| `nama_sekolah` | `string` | `MTsN 4 Jombang` |
| `alamat_sekolah` | `string` | `Jl. KH. Bisri Syansuri No.77, Denanyar, Kec. Jombang, Jombang` |

# 8. Constraint Bisnis Kritis

```text
anggota_kelas
UNIQUE(id_siswa,id_tahun)

presensi
UNIQUE(id_kelas,tanggal,sesi,id_siswa)

presensi_mengajar
UNIQUE(id_jadwal,tanggal)

user_roles
UNIQUE(id_user,role)

role_permissions
UNIQUE(role,id_permission,scope)

role_menus
UNIQUE(role,id_menu)

tahun_ajaran
UNIQUE(nama_tahun,semester)
```

Mapping Wali memakai generated column untuk menjamin:

```text
1 Guru maksimal 1 Wali aktif per tahun
1 Kelas maksimal 1 Wali aktif per tahun
```

Kartu Pelajar memakai generated `id_siswa_aktif` untuk menjamin:

```text
maksimal 1 kartu Aktif per siswa
```

# 9. Identifier

```text
users.username      unique
guru.nip            unique pada guru
pegawai.nip         unique pada pegawai
siswa.nik           unique
siswa.nisn          unique
mata_pelajaran.kode_mapel unique
```

NIP lintas Guru/Pegawai dijaga Service.

# 10. Histori dan FK

- `log_activity.id_user` dapat menjadi NULL agar audit tidak hilang;
- setting updater dapat menjadi NULL;
- snapshot Presensi/Jurnal mempertahankan nama historis;
- FK dapat menolak force delete bila entity masih direferensikan;
- token user dihapus sesuai lifecycle FK.

# 11. Fresh Database

Fresh database dianggap sinkron bila menghasilkan:

```text
27 tabel
43 permission
users.role nullable
generated key Mapping Wali
generated id_siswa_aktif Kartu
index/unique/FK sesuai dump
```

# 12. Data Sensitif

Dump dapat berisi password hash, token, refresh token, session payload, IP login, dan data runtime lain. Nilai-nilai tersebut bukan kontrak dokumentasi dan tidak boleh disalin ke dokumen acuan.
