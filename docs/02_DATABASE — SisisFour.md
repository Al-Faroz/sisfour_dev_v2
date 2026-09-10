# Database — SisisFour

**Versi Acuan:** v0.9 PHASE 3.2 FINAL POLISH  
**Tanggal Acuan:** 09 September 2026  
**Baseline Aplikasi:** `main` @ `c05466738012ea2da852fa3e878b6bbb897d6607` + paket Phase 3.2  
**Baseline Database:** `sisfour_dev_v2 (29).sql`

Dokumen ini adalah kontrak database SisisFour setelah Phase 3.1 hardening dan sinkronisasi Phase 3.2. Struktur kolom/index/FK di bawah dihasilkan dari dump `(29)`; kontrak CHECK Personalia dijelaskan terpisah karena representasi CHECK tidak terlihat pada dump `(29)` sehingga status live harus diverifikasi melalui `SHOW CREATE TABLE`.

---

# 1. Baseline

- Jumlah tabel: **32**.
- DBMS dump: **MariaDB 10.4.32**.
- phpMyAdmin: **5.2.1**.
- `ci_sessions.timestamp`: **DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP**.
- `users.role` tetap nullable dan enum operasional: `admin`, `operator`, `pimpinan`, `bk`, `guru`, `siswa`.
- Tidak ada role `pegawai`; identitas Pegawai berasal dari `users.id_pegawai`.
- `users.id_guru`, `users.id_pegawai`, dan `users.id_siswa` masing-masing mempunyai unique key.
- Empat tabel Personalia: `riwayat_pendidikan`, `riwayat_penugasan`, `riwayat_pangkat`, `dokumen_personalia`.
- BK tindak lanjut menggunakan `tindak_lanjut_kasus` 1:N terhadap `catatan_kasus`.

# 2. Kontrak Integritas Penting

## 2.1 Guru/Pegawai

- `guru.nik` dan `pegawai.nik` masih nullable pada schema untuk kompatibilitas legacy; Service mewajibkan NIK 16 digit pada create/edit/import baru.
- `nip` nullable. Login identifier memakai NIP bila tersedia, selain itu NIK.
- Status kepegawaian: `PNS`, `PPPK`, `GTT`, `PTT`, `GTY`, `PTY`, `Honorer`, `Outsourcing`.
- `pegawai.jabatan` dipertahankan sebagai field legacy compatibility sampai riwayat penugasan yang benar diisi.

## 2.2 Personalia

Setiap record Personalia hanya boleh mempunyai **satu** owner: Guru XOR Pegawai. Kontrak CHECK Phase 3.1:

```text
chk_rp_owner       -> riwayat_pendidikan: id_guru XOR id_pegawai
chk_rpen_owner     -> riwayat_penugasan: id_guru XOR id_pegawai
chk_rpen_periode   -> tanggal_selesai NULL atau >= tanggal_mulai
chk_rpk_owner      -> riwayat_pangkat: id_guru XOR id_pegawai
chk_dp_owner       -> dokumen_personalia: id_guru XOR id_pegawai
```

Pada dump `(29)` representasi CHECK tidak terlihat. Karena itu keberadaan CHECK pada live DB diverifikasi dengan `SHOW CREATE TABLE`; gunakan script `database/20260909_PHASE3_2_VERIFY_PERSONALIA_CHECKS.sql`.

## 2.3 Session

`ci_sessions` mengikuti CodeIgniter 4 DatabaseHandler dengan kolom timestamp temporal:

```text
id          VARCHAR(128)
ip_address  VARCHAR(45)
timestamp   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
data        BLOB
```

Nilai overflow legacy `4294967295` tidak lagi digunakan.

## 2.4 Unique/Generated Key penting

- `anggota_kelas`: unique `(id_siswa, id_tahun)`.
- `mapping_wali_kelas`: generated key menjaga satu mapping Guru aktif dan satu Kelas aktif.
- `kartu_pelajar`: generated `id_siswa_aktif` menjaga satu kartu Aktif per siswa.
- `users`: unique username dan unique identity link Guru/Pegawai/Siswa.

# 3. Daftar Tabel

| No | Tabel |
|---:|---|
| 1 | `anggota_kelas` |
| 2 | `api_tokens` |
| 3 | `catatan_kasus` |
| 4 | `catatan_prestasi` |
| 5 | `ci_sessions` |
| 6 | `dokumen_personalia` |
| 7 | `guru` |
| 8 | `jadwal_guru` |
| 9 | `kartu_pelajar` |
| 10 | `kelas` |
| 11 | `login_attempts` |
| 12 | `log_activity` |
| 13 | `mapping_wali_kelas` |
| 14 | `mata_pelajaran` |
| 15 | `menus` |
| 16 | `pegawai` |
| 17 | `permissions` |
| 18 | `presensi` |
| 19 | `presensi_mengajar` |
| 20 | `ref_pelanggaran` |
| 21 | `riwayat_pangkat` |
| 22 | `riwayat_pendidikan` |
| 23 | `riwayat_penugasan` |
| 24 | `riwayat_siswa` |
| 25 | `role_menus` |
| 26 | `role_permissions` |
| 27 | `setting_sistem` |
| 28 | `siswa` |
| 29 | `tahun_ajaran` |
| 30 | `tindak_lanjut_kasus` |
| 31 | `users` |
| 32 | `user_roles` |

# 4. Struktur Tabel

Definisi berikut mengikuti dump `sisfour_dev_v2 (29).sql`. Nilai `AUTO_INCREMENT` yang bersifat data-runtime tidak dianggap kontrak bisnis.

## 4.1 `anggota_kelas`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `id_kelas` | `int(10) UNSIGNED NOT NULL` |
| `id_tahun` | `int(10) UNSIGNED NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `anggota_kelas`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_anggota_siswa_tahun` (`id_siswa`,`id_tahun`),
  ADD KEY `idx_anggota_kelas` (`id_kelas`),
  ADD KEY `idx_anggota_tahun` (`id_tahun`)
;

ALTER TABLE `anggota_kelas`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1494
;

ALTER TABLE `anggota_kelas`
ADD CONSTRAINT `fk_anggota_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_anggota_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_anggota_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE
;
```

## 4.2 `api_tokens`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `api_tokens`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `refresh_token` (`refresh_token`),
  ADD KEY `idx_api_tokens_user` (`id_user`),
  ADD KEY `idx_api_tokens_expiry` (`expires_at`),
  ADD KEY `idx_api_tokens_refresh_expiry` (`refresh_expires_at`)
;

ALTER TABLE `api_tokens`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4
;

ALTER TABLE `api_tokens`
ADD CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.3 `catatan_kasus`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `catatan_kasus`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kasus_siswa_tanggal` (`id_siswa`,`tanggal`),
  ADD KEY `idx_kasus_pelanggaran` (`id_pelanggaran`),
  ADD KEY `idx_kasus_guru_input` (`id_guru_input`),
  ADD KEY `idx_kasus_updated_by` (`updated_by`)
;

ALTER TABLE `catatan_kasus`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `catatan_kasus`
ADD CONSTRAINT `fk_kasus_guru_input` FOREIGN KEY (`id_guru_input`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kasus_pelanggaran` FOREIGN KEY (`id_pelanggaran`) REFERENCES `ref_pelanggaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kasus_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kasus_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
;
```

## 4.4 `catatan_prestasi`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `catatan_prestasi`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prestasi_siswa_tanggal` (`id_siswa`,`tanggal`),
  ADD KEY `idx_prestasi_guru_input` (`id_guru_input`)
;

ALTER TABLE `catatan_prestasi`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `catatan_prestasi`
ADD CONSTRAINT `fk_prestasi_guru_input` FOREIGN KEY (`id_guru_input`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestasi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE
;
```

## 4.5 `ci_sessions`

| Kolom | Definisi |
|---|---|
| `id` | `varchar(128) NOT NULL` |
| `ip_address` | `varchar(45) NOT NULL` |
| `timestamp` | `datetime NOT NULL DEFAULT current_timestamp()` |
| `data` | `blob NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `ci_sessions`
ADD PRIMARY KEY (`id`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`)
;
```

## 4.6 `dokumen_personalia`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED DEFAULT NULL` |
| `id_pegawai` | `int(10) UNSIGNED DEFAULT NULL` |
| `jenis_dokumen` | `varchar(60) NOT NULL` |
| `nama_dokumen` | `varchar(150) NOT NULL` |
| `nomor_dokumen` | `varchar(100) DEFAULT NULL` |
| `tanggal_dokumen` | `date DEFAULT NULL` |
| `file_path` | `varchar(255) NOT NULL` |
| `nama_file_asli` | `varchar(255) DEFAULT NULL` |
| `mime_type` | `varchar(100) DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `dokumen_personalia`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dp_guru` (`id_guru`),
  ADD KEY `idx_dp_pegawai` (`id_pegawai`),
  ADD KEY `idx_dp_jenis` (`jenis_dokumen`)
;

ALTER TABLE `dokumen_personalia`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `dokumen_personalia`
ADD CONSTRAINT `fk_dp_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dp_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.7 `guru`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nik` | `varchar(16) DEFAULT NULL` |
| `nip` | `varchar(18) DEFAULT NULL` |
| `nama` | `varchar(150) NOT NULL` |
| `jenis_kelamin` | `enum('L','P') NOT NULL` |
| `tempat_lahir` | `varchar(100) DEFAULT NULL` |
| `tanggal_lahir` | `date DEFAULT NULL` |
| `agama` | `varchar(30) DEFAULT NULL` |
| `alamat` | `text DEFAULT NULL` |
| `no_telepon` | `varchar(20) DEFAULT NULL` |
| `email` | `varchar(100) DEFAULT NULL` |
| `status_kepegawaian` | `enum('PNS','PPPK','GTT','PTT','GTY','PTY','Honorer','Outsourcing') DEFAULT NULL` |
| `nuptk` | `varchar(16) DEFAULT NULL` |
| `foto` | `varchar(255) DEFAULT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `guru`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `uk_guru_nik` (`nik`),
  ADD KEY `idx_guru_nama` (`nama`),
  ADD KEY `idx_guru_status_kepegawaian` (`status_kepegawaian`),
  ADD KEY `idx_guru_deleted_at` (`deleted_at`)
;

ALTER TABLE `guru`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112
;
```

## 4.8 `jadwal_guru`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `jadwal_guru`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jadwal_guru_hari` (`id_guru`,`id_tahun`,`hari`,`status_jadwal`),
  ADD KEY `idx_jadwal_kelas_hari` (`id_kelas`,`id_tahun`,`hari`,`status_jadwal`),
  ADD KEY `idx_jadwal_mapel` (`id_mapel`),
  ADD KEY `idx_jadwal_waktu` (`jam_mulai`,`jam_selesai`),
  ADD KEY `fk_jadwal_tahun` (`id_tahun`)
;

ALTER TABLE `jadwal_guru`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `jadwal_guru`
ADD CONSTRAINT `fk_jadwal_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_mapel` FOREIGN KEY (`id_mapel`) REFERENCES `mata_pelajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE
;
```

## 4.9 `kartu_pelajar`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_siswa` | `int(10) UNSIGNED NOT NULL` |
| `nomor_kartu` | `varchar(50) NOT NULL` |
| `kode_verifikasi` | `varchar(100) NOT NULL` |
| `tanggal_terbit` | `date NOT NULL` |
| `status_aktif` | `enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif'` |
| `id_siswa_aktif` | `int(10) UNSIGNED GENERATED ALWAYS AS (case when `status_aktif` = 'Aktif' then `id_siswa` else NULL end) STORED` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `kartu_pelajar`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_kartu` (`nomor_kartu`),
  ADD UNIQUE KEY `kode_verifikasi` (`kode_verifikasi`),
  ADD UNIQUE KEY `uq_kartu_siswa_aktif` (`id_siswa_aktif`),
  ADD KEY `idx_kartu_siswa` (`id_siswa`),
  ADD KEY `idx_kartu_status` (`status_aktif`)
;

ALTER TABLE `kartu_pelajar`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1494
;

ALTER TABLE `kartu_pelajar`
ADD CONSTRAINT `fk_kartu_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE
;
```

## 4.10 `kelas`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `kelas`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_kelas_tahun_nama` (`id_tahun`,`nama_kelas`),
  ADD KEY `idx_kelas_tahun` (`id_tahun`),
  ADD KEY `idx_kelas_tingkat` (`tingkat`),
  ADD KEY `idx_kelas_deleted_at` (`deleted_at`)
;

ALTER TABLE `kelas`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54
;

ALTER TABLE `kelas`
ADD CONSTRAINT `fk_kelas_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE
;
```

## 4.11 `login_attempts`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `username` | `varchar(50) NOT NULL` |
| `ip_address` | `varchar(45) NOT NULL` |
| `waktu` | `datetime NOT NULL` |
| `berhasil` | `tinyint(1) NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `login_attempts`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_lookup` (`username`,`ip_address`,`waktu`),
  ADD KEY `idx_login_attempts_waktu` (`waktu`)
;

ALTER TABLE `login_attempts`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59
;
```

## 4.12 `log_activity`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_user` | `int(10) UNSIGNED DEFAULT NULL` |
| `aksi` | `varchar(100) NOT NULL` |
| `modul` | `varchar(100) NOT NULL` |
| `keterangan` | `text DEFAULT NULL` |
| `waktu` | `datetime NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `log_activity`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_waktu` (`waktu`),
  ADD KEY `idx_log_user_waktu` (`id_user`,`waktu`),
  ADD KEY `idx_log_modul_waktu` (`modul`,`waktu`)
;

ALTER TABLE `log_activity`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97
;

ALTER TABLE `log_activity`
ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
;
```

## 4.13 `mapping_wali_kelas`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `mapping_wali_kelas`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wali_guru_aktif` (`id_tahun`,`uk_guru_aktif`),
  ADD UNIQUE KEY `uk_wali_kelas_aktif` (`id_tahun`,`uk_kelas_aktif`),
  ADD KEY `idx_wali_guru` (`id_guru`,`id_tahun`),
  ADD KEY `idx_wali_kelas` (`id_kelas`,`id_tahun`),
  ADD KEY `idx_wali_deleted_at` (`deleted_at`)
;

ALTER TABLE `mapping_wali_kelas`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65
;

ALTER TABLE `mapping_wali_kelas`
ADD CONSTRAINT `fk_wali_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wali_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wali_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE
;
```

## 4.14 `mata_pelajaran`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_mapel` | `varchar(100) NOT NULL` |
| `kode_mapel` | `varchar(10) NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `mata_pelajaran`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_mapel` (`kode_mapel`),
  ADD KEY `idx_mapel_nama` (`nama_mapel`)
;

ALTER TABLE `mata_pelajaran`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24
;
```

## 4.15 `menus`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `menus`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menus_parent` (`parent_id`),
  ADD KEY `idx_menus_urutan` (`urutan`)
;

ALTER TABLE `menus`
ADD CONSTRAINT `fk_menus_parent` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.16 `pegawai`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nik` | `varchar(16) DEFAULT NULL` |
| `nip` | `varchar(18) DEFAULT NULL` |
| `nama` | `varchar(150) NOT NULL` |
| `jenis_kelamin` | `enum('L','P') NOT NULL` |
| `tempat_lahir` | `varchar(100) DEFAULT NULL` |
| `tanggal_lahir` | `date DEFAULT NULL` |
| `agama` | `varchar(30) DEFAULT NULL` |
| `alamat` | `text DEFAULT NULL` |
| `no_telepon` | `varchar(20) DEFAULT NULL` |
| `email` | `varchar(100) DEFAULT NULL` |
| `status_kepegawaian` | `enum('PNS','PPPK','GTT','PTT','GTY','PTY','Honorer','Outsourcing') DEFAULT NULL` |
| `nuptk` | `varchar(16) DEFAULT NULL` |
| `foto` | `varchar(255) DEFAULT NULL` |
| `jabatan` | `varchar(100) DEFAULT NULL` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `pegawai`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `uk_pegawai_nik` (`nik`),
  ADD KEY `idx_pegawai_nama` (`nama`),
  ADD KEY `idx_pegawai_jabatan` (`jabatan`),
  ADD KEY `idx_pegawai_deleted_at` (`deleted_at`),
  ADD KEY `idx_pegawai_status_kepegawaian` (`status_kepegawaian`)
;

ALTER TABLE `pegawai`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2
;
```

## 4.17 `permissions`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `permission_key` | `varchar(100) NOT NULL` |
| `nama` | `varchar(100) NOT NULL` |
| `modul` | `varchar(50) NOT NULL` |
| `scope_didukung` | `varchar(100) NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `permissions`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`),
  ADD KEY `idx_permissions_modul` (`modul`)
;

ALTER TABLE `permissions`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44
;
```

## 4.18 `presensi`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `presensi`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_presensi` (`id_kelas`,`tanggal`,`sesi`,`id_siswa`),
  ADD KEY `idx_presensi_siswa_tahun_tanggal` (`id_siswa`,`id_tahun`,`tanggal`),
  ADD KEY `idx_presensi_kelas_tanggal` (`id_kelas`,`tanggal`),
  ADD KEY `idx_presensi_laporan_awal` (`id_tahun`,`sesi`,`tanggal`,`status`),
  ADD KEY `idx_presensi_guru_input` (`id_guru_input`),
  ADD KEY `idx_presensi_updated_by` (`updated_by`),
  ADD KEY `idx_presensi_kelas_periode` (`id_tahun`,`id_kelas`,`sesi`,`tanggal`),
  ADD KEY `idx_presensi_siswa_periode` (`id_siswa`,`id_tahun`,`sesi`,`tanggal`)
;

ALTER TABLE `presensi`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31
;

ALTER TABLE `presensi`
ADD CONSTRAINT `fk_presensi_guru_input` FOREIGN KEY (`id_guru_input`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
;
```

## 4.19 `presensi_mengajar`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `presensi_mengajar`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_presensi_mengajar` (`id_jadwal`,`tanggal`),
  ADD KEY `idx_jurnal_guru_tanggal` (`id_guru`,`tanggal`),
  ADD KEY `idx_jurnal_kelas_tanggal` (`id_kelas`,`tanggal`),
  ADD KEY `idx_jurnal_tahun` (`id_tahun`),
  ADD KEY `idx_jurnal_updated_by` (`updated_by`)
;

ALTER TABLE `presensi_mengajar`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `presensi_mengajar`
ADD CONSTRAINT `fk_jurnal_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
;
```

## 4.20 `ref_pelanggaran`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_pelanggaran` | `varchar(150) NOT NULL` |
| `kategori` | `enum('Ringan','Sedang','Berat') NOT NULL` |
| `poin` | `int(11) NOT NULL DEFAULT 0` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `ref_pelanggaran`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ref_pelanggaran_nama` (`nama_pelanggaran`),
  ADD KEY `idx_ref_pelanggaran_kategori` (`kategori`)
;

ALTER TABLE `ref_pelanggaran`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13
;
```

## 4.21 `riwayat_pangkat`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED DEFAULT NULL` |
| `id_pegawai` | `int(10) UNSIGNED DEFAULT NULL` |
| `golongan_ruang` | `varchar(30) NOT NULL` |
| `nama_pangkat` | `varchar(100) DEFAULT NULL` |
| `tmt_pangkat` | `date NOT NULL` |
| `no_sk_pangkat` | `varchar(100) DEFAULT NULL` |
| `file_sk_pangkat` | `varchar(255) DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `riwayat_pangkat`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpk_guru` (`id_guru`),
  ADD KEY `idx_rpk_pegawai` (`id_pegawai`),
  ADD KEY `idx_rpk_tmt` (`tmt_pangkat`)
;

ALTER TABLE `riwayat_pangkat`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `riwayat_pangkat`
ADD CONSTRAINT `fk_rpk_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpk_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.22 `riwayat_pendidikan`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED DEFAULT NULL` |
| `id_pegawai` | `int(10) UNSIGNED DEFAULT NULL` |
| `tingkat_pendidikan` | `varchar(20) NOT NULL` |
| `nama_institusi` | `varchar(150) NOT NULL` |
| `program_studi` | `varchar(150) DEFAULT NULL` |
| `tahun_lulus` | `year(4) NOT NULL` |
| `no_ijazah` | `varchar(100) DEFAULT NULL` |
| `file_ijazah` | `varchar(255) DEFAULT NULL` |
| `file_transkrip` | `varchar(255) DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `riwayat_pendidikan`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rp_guru` (`id_guru`),
  ADD KEY `idx_rp_pegawai` (`id_pegawai`),
  ADD KEY `idx_rp_tahun_lulus` (`tahun_lulus`)
;

ALTER TABLE `riwayat_pendidikan`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `riwayat_pendidikan`
ADD CONSTRAINT `fk_rp_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.23 `riwayat_penugasan`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_guru` | `int(10) UNSIGNED DEFAULT NULL` |
| `id_pegawai` | `int(10) UNSIGNED DEFAULT NULL` |
| `instansi_penugasan` | `varchar(150) NOT NULL` |
| `jabatan_tugas` | `varchar(120) NOT NULL` |
| `mata_pelajaran` | `varchar(120) DEFAULT NULL` |
| `no_sk_penugasan` | `varchar(100) DEFAULT NULL` |
| `tanggal_mulai` | `date NOT NULL` |
| `tanggal_selesai` | `date DEFAULT NULL` |
| `file_sk_penugasan` | `varchar(255) DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `riwayat_penugasan`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpen_guru` (`id_guru`),
  ADD KEY `idx_rpen_pegawai` (`id_pegawai`),
  ADD KEY `idx_rpen_periode` (`tanggal_mulai`,`tanggal_selesai`)
;

ALTER TABLE `riwayat_penugasan`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `riwayat_penugasan`
ADD CONSTRAINT `fk_rpen_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpen_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.24 `riwayat_siswa`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `riwayat_siswa`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_riwayat_siswa_tahun` (`id_siswa`,`id_tahun`),
  ADD KEY `idx_riwayat_kelas` (`id_kelas`),
  ADD KEY `fk_riwayat_tahun` (`id_tahun`)
;

ALTER TABLE `riwayat_siswa`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1494
;

ALTER TABLE `riwayat_siswa`
ADD CONSTRAINT `fk_riwayat_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE
;
```

## 4.25 `role_menus`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL` |
| `id_menu` | `int(10) UNSIGNED NOT NULL` |
| `tampil` | `tinyint(1) NOT NULL DEFAULT 1` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `role_menus`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_menu` (`role`,`id_menu`),
  ADD KEY `idx_role_menus_menu` (`id_menu`)
;

ALTER TABLE `role_menus`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267
;

ALTER TABLE `role_menus`
ADD CONSTRAINT `fk_rm_menu` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.26 `role_permissions`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL` |
| `id_permission` | `int(10) UNSIGNED NOT NULL` |
| `scope` | `varchar(50) NOT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `role_permissions`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_permission_scope` (`role`,`id_permission`,`scope`),
  ADD KEY `idx_role_permissions_permission` (`id_permission`),
  ADD KEY `idx_role_permissions_role` (`role`)
;

ALTER TABLE `role_permissions`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139
;

ALTER TABLE `role_permissions`
ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

## 4.27 `setting_sistem`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `setting_key` | `varchar(100) NOT NULL` |
| `setting_value` | `text NOT NULL` |
| `type` | `varchar(20) NOT NULL DEFAULT 'string'` |
| `updated_at` | `datetime DEFAULT NULL` |
| `updated_by` | `int(10) UNSIGNED DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `setting_sistem`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_type` (`type`),
  ADD KEY `idx_setting_updated_by` (`updated_by`)
;

ALTER TABLE `setting_sistem`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13
;

ALTER TABLE `setting_sistem`
ADD CONSTRAINT `fk_setting_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
;
```

## 4.28 `siswa`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `siswa`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_siswa_nama` (`nama`),
  ADD KEY `idx_siswa_status` (`status_aktif`),
  ADD KEY `idx_siswa_deleted_at` (`deleted_at`)
;

ALTER TABLE `siswa`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1494
;
```

## 4.29 `tahun_ajaran`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `nama_tahun` | `varchar(20) NOT NULL` |
| `semester` | `enum('Ganjil','Genap') NOT NULL` |
| `status_aktif` | `tinyint(1) NOT NULL DEFAULT 0` |
| `deleted_at` | `datetime DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `tahun_ajaran`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tahun_semester` (`nama_tahun`,`semester`),
  ADD KEY `idx_tahun_aktif` (`status_aktif`),
  ADD KEY `idx_tahun_deleted_at` (`deleted_at`)
;

ALTER TABLE `tahun_ajaran`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2
;
```

## 4.30 `tindak_lanjut_kasus`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_kasus` | `int(10) UNSIGNED NOT NULL` |
| `tanggal` | `date NOT NULL` |
| `tindak_lanjut` | `varchar(100) NOT NULL` |
| `keterangan` | `text DEFAULT NULL` |
| `id_user_input` | `int(10) UNSIGNED DEFAULT NULL` |
| `created_at` | `datetime DEFAULT NULL` |
| `updated_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `tindak_lanjut_kasus`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tindak_kasus_tanggal` (`id_kasus`,`tanggal`,`id`),
  ADD KEY `idx_tindak_user_input` (`id_user_input`)
;

ALTER TABLE `tindak_lanjut_kasus`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT
;

ALTER TABLE `tindak_lanjut_kasus`
ADD CONSTRAINT `fk_tindak_kasus` FOREIGN KEY (`id_kasus`) REFERENCES `catatan_kasus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tindak_user_input` FOREIGN KEY (`id_user_input`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
;
```

## 4.31 `users`

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

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
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
  ADD KEY `idx_users_status` (`status_aktif`)
;

ALTER TABLE `users`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1607
;

ALTER TABLE `users`
ADD CONSTRAINT `fk_user_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE
;
```

## 4.32 `user_roles`

| Kolom | Definisi |
|---|---|
| `id` | `int(10) UNSIGNED NOT NULL` |
| `id_user` | `int(10) UNSIGNED NOT NULL` |
| `role` | `enum('admin','operator','pimpinan','bk','guru','siswa') NOT NULL` |
| `created_at` | `datetime DEFAULT NULL` |

**ALTER/Index/Constraint pada dump `(29)`:**

```sql
ALTER TABLE `user_roles`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_role` (`id_user`,`role`),
  ADD KEY `idx_user_roles_role` (`role`)
;

ALTER TABLE `user_roles`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1607
;

ALTER TABLE `user_roles`
ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
;
```

# 5. Storage File

Foto public runtime:

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
```

Dokumen Personalia non-public:

```text
WRITEPATH/uploads/personalia/{guru|pegawai}/{id}/...
```

Path file Personalia disimpan relatif di database dan hanya dilayani melalui endpoint berotorisasi. File runtime tidak boleh di-commit ke Git.

# 6. Verification SQL Phase 3.2

Jalankan hanya untuk verifikasi schema live:

```sql
SHOW CREATE TABLE ci_sessions;
SHOW CREATE TABLE riwayat_pendidikan;
SHOW CREATE TABLE riwayat_penugasan;
SHOW CREATE TABLE riwayat_pangkat;
SHOW CREATE TABLE dokumen_personalia;
```

Expected minimum:

- `ci_sessions.timestamp` bertipe `datetime`;
- lima CHECK bernama pada §2.2 tampil pada live DB;
- FK Personalia ke `guru`/`pegawai` tetap `ON DELETE CASCADE ON UPDATE CASCADE`.

# 7. Aturan Perubahan Schema

- Jangan menebak NIK/NIP legacy.
- Jangan membuat role `pegawai` hanya untuk identity Profile.
- Perubahan schema besar harus diawali precheck data, transaction/rollback strategy yang sesuai, dan postcheck.
- Service tetap menjadi authorization/data boundary; constraint DB adalah lapisan integritas tambahan.
- Setelah perubahan schema, buat dump baru dan sinkronkan dokumen ini dari kondisi live, bukan dari asumsi.
