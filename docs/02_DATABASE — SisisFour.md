# 🗄️ Database Schema — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan struktur database canonical SisisFour. Database menggunakan MariaDB/MySQL dan dibangun dengan SQL murni. Struktur ini menjadi acuan untuk fresh install dan harus konsisten dengan Model/Service aplikasi.

---

# 1. Konvensi

- Database engine: InnoDB.
- Charset: `utf8mb4`.
- Collation: `utf8mb4_general_ci` atau collation utf8mb4 setara.
- Timezone aplikasi: `Asia/Jakarta`.
- Primary key: `id INT UNSIGNED AUTO_INCREMENT`.
- Foreign key aktif.
- Nama tabel/kolom: `snake_case`.
- Timestamp aplikasi menggunakan `DATETIME`.
- Soft delete menggunakan `deleted_at DATETIME NULL`.
- Business identifier seperti NIP/NIK/NISN menggunakan VARCHAR untuk mempertahankan leading zero.

Soft delete:

```text
guru
pegawai
siswa
kelas
tahun_ajaran
mapping_wali_kelas
```

Hard delete:

```text
mata_pelajaran
jadwal_guru
```

---

# 2. SQL Dasar

```sql
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
```

---

# 3. Tabel Master

## 3.1 `guru`

```sql
CREATE TABLE guru (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tempat_lahir VARCHAR(100) NULL,
    tanggal_lahir DATE NULL,
    alamat TEXT NULL,
    no_telepon VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    status_kepegawaian ENUM(
        'PNS',
        'PPPK',
        'NON ASN',
        'Yayasan',
        'Outsourcing'
    ) NULL,
    foto VARCHAR(255) NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

NIP juga harus divalidasi lintas tabel terhadap `pegawai.nip` di Service.

---

## 3.2 `pegawai`

```sql
CREATE TABLE pegawai (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tempat_lahir VARCHAR(100) NULL,
    tanggal_lahir DATE NULL,
    alamat TEXT NULL,
    no_telepon VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    jabatan VARCHAR(100) NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3.3 `siswa`

```sql
CREATE TABLE siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL UNIQUE,
    nisn VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tempat_lahir VARCHAR(100) NULL,
    tanggal_lahir DATE NULL,
    alamat TEXT NULL,
    no_telepon VARCHAR(20) NULL,
    kebutuhan_khusus VARCHAR(100) NULL,
    disabilitas VARCHAR(100) NULL,
    nomor_kip_pip VARCHAR(50) NULL,
    nama_ayah_kandung VARCHAR(150) NULL,
    nama_ibu_kandung VARCHAR(150) NULL,
    nama_wali VARCHAR(150) NULL,
    foto VARCHAR(255) NULL,
    status_aktif ENUM(
        'Aktif',
        'Lulus',
        'Pindah',
        'Keluar'
    ) NOT NULL DEFAULT 'Aktif',
    tanggal_mutasi DATE NULL,
    keterangan_mutasi TEXT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Aturan NIK 16 digit divalidasi di aplikasi.

---

## 3.4 `tahun_ajaran`

```sql
CREATE TABLE tahun_ajaran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_tahun VARCHAR(20) NOT NULL,
    semester ENUM('Ganjil','Genap') NOT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_tahun_semester (nama_tahun, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Invariant satu tahun ajaran aktif dijaga Service dalam transaction.

---

## 3.5 `kelas`

```sql
CREATE TABLE kelas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tingkat ENUM('7','8','9') NOT NULL,
    rombel VARCHAR(10) NOT NULL,
    nama_kelas VARCHAR(20) NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_kelas_tahun (id_tahun, nama_kelas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3.6 `mata_pelajaran`

```sql
CREATE TABLE mata_pelajaran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_mapel VARCHAR(100) NOT NULL,
    kode_mapel VARCHAR(10) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3.7 `anggota_kelas`

```sql
CREATE TABLE anggota_kelas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT UNSIGNED NOT NULL,
    id_kelas INT UNSIGNED NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,
    UNIQUE KEY uk_siswa_tahun (id_siswa, id_tahun),
    KEY idx_anggota_kelas (id_kelas, id_tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Satu siswa hanya boleh menjadi anggota satu kelas pada tahun yang sama.

---

## 3.8 `mapping_wali_kelas`

Mapping Wali membutuhkan histori soft delete sekaligus UNIQUE hanya untuk record aktif.

Struktur canonical:

```sql
CREATE TABLE mapping_wali_kelas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_guru INT UNSIGNED NOT NULL,
    id_kelas INT UNSIGNED NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,

    deleted_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,

    uk_guru_aktif INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN deleted_at IS NULL THEN id_guru
                ELSE NULL
            END
        ) STORED,

    uk_kelas_aktif INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN deleted_at IS NULL THEN id_kelas
                ELSE NULL
            END
        ) STORED,

    UNIQUE KEY uk_mapping_guru_aktif (
        id_tahun,
        uk_guru_aktif
    ),

    UNIQUE KEY uk_mapping_kelas_aktif (
        id_tahun,
        uk_kelas_aktif
    ),

    KEY idx_mapping_wali_tahun (
        id_tahun,
        deleted_at
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Konsekuensi:
- row aktif: generated column berisi ID dan terkena UNIQUE;
- row nonaktif: generated column NULL sehingga histori dapat tetap disimpan;
- restore/reassign row lama dimungkinkan.

---

## 3.9 `jadwal_guru`

```sql
CREATE TABLE jadwal_guru (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_guru INT UNSIGNED NOT NULL,
    id_kelas INT UNSIGNED NOT NULL,
    id_mapel INT UNSIGNED NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,
    hari ENUM(
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu'
    ) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    sesi ENUM(
        'Sesi Awal',
        'Sesi Akhir',
        'Non Sesi'
    ) NOT NULL,
    status_jadwal ENUM(
        'Aktif',
        'Nonaktif'
    ) NOT NULL DEFAULT 'Aktif',

    KEY idx_jadwal_guru (
        id_guru,
        id_tahun,
        hari,
        status_jadwal
    ),

    KEY idx_jadwal_kelas (
        id_kelas,
        id_tahun,
        hari,
        status_jadwal
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Overlap tidak dapat dijaga hanya dengan unique index karena memakai interval waktu; validasi overlap dilakukan Service.

---

# 4. Histori Siswa

```sql
CREATE TABLE riwayat_siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT UNSIGNED NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,
    id_kelas INT UNSIGNED NOT NULL,
    status ENUM(
        'Aktif',
        'Pindah',
        'Keluar',
        'Lulus'
    ) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NULL,
    keterangan TEXT NULL,
    created_at DATETIME NULL,
    KEY idx_riwayat_siswa (id_siswa, id_tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

# 5. Authentication dan RBAC

## 5.1 `users`

`role` boleh NULL.

```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    role ENUM(
        'admin',
        'operator',
        'pimpinan',
        'bk',
        'guru',
        'siswa'
    ) NULL DEFAULT NULL,

    id_guru INT UNSIGNED NULL,
    id_pegawai INT UNSIGNED NULL,
    id_siswa INT UNSIGNED NULL,

    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    auth_version INT UNSIGNED NOT NULL DEFAULT 1,

    created_at DATETIME NULL,
    updated_at DATETIME NULL,

    KEY idx_users_role (role),
    KEY idx_users_guru (id_guru),
    KEY idx_users_pegawai (id_pegawai),
    KEY idx_users_siswa (id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Akun Pegawai dapat dibuat dengan `role = NULL` sampai role diberikan Admin.

---

## 5.2 `user_roles`

```sql
CREATE TABLE user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    role ENUM(
        'admin',
        'operator',
        'pimpinan',
        'bk',
        'guru',
        'siswa'
    ) NOT NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uk_user_role (id_user, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5.3 `login_attempts`

```sql
CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    waktu DATETIME NOT NULL,
    berhasil TINYINT(1) NOT NULL,
    KEY idx_login_username_waktu (username, waktu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5.4 `ci_sessions`

```sql
CREATE TABLE ci_sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    timestamp INT UNSIGNED NOT NULL DEFAULT 0,
    data BLOB NOT NULL,
    KEY ci_sessions_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5.5 `api_tokens`

```sql
CREATE TABLE api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    refresh_token VARCHAR(255) NOT NULL UNIQUE,
    device_name VARCHAR(100) NULL,
    expires_at DATETIME NOT NULL,
    refresh_expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5.6 `menus`

```sql
CREATE TABLE menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_menu VARCHAR(100) NOT NULL,
    parent_id INT UNSIGNED NULL,
    urutan INT NOT NULL DEFAULT 0,
    icon VARCHAR(100) NULL,
    link VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_menu_parent (parent_id, urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5.7 `permissions`

```sql
CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    modul VARCHAR(50) NOT NULL,
    scope_didukung VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Permission canonical:

```sql
INSERT INTO permissions
(id, permission_key, nama, modul, scope_didukung)
VALUES
(1,  'dashboard.view', 'Lihat Dashboard', 'Dashboard', 'OTOMATIS'),
(2,  'presensi_siswa.input', 'Input Presensi Siswa', 'Presensi Siswa', 'SEMUA,KELAS_DIAMPU,KELAS_TERJADWAL'),
(3,  'presensi_siswa.revisi', 'Revisi Presensi Siswa', 'Presensi Siswa', 'SEMUA,KELAS_DIAMPU'),
(4,  'presensi_siswa.view', 'Lihat Presensi Siswa', 'Presensi Siswa', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'),
(5,  'presensi_mengajar.input', 'Input Presensi Mengajar', 'Presensi Mengajar', 'SEMUA,KELAS_TERJADWAL,DIRI_SENDIRI'),
(6,  'presensi_mengajar.view', 'Lihat Presensi Mengajar', 'Presensi Mengajar', 'SEMUA,DIRI_SENDIRI'),
(7,  'master_guru.manage', 'Kelola Guru', 'Master Data', 'SEMUA'),
(8,  'master_guru.view', 'Lihat Guru', 'Master Data', 'SEMUA'),
(9,  'master_pegawai.manage', 'Kelola Pegawai', 'Master Data', 'SEMUA'),
(10, 'master_pegawai.view', 'Lihat Pegawai', 'Master Data', 'SEMUA'),
(11, 'master_siswa.view', 'Lihat Siswa', 'Master Data', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'),
(12, 'master_siswa.edit_biodata', 'Edit Biodata Siswa', 'Master Data', 'SEMUA,KELAS_DIAMPU'),
(13, 'master_siswa.manage', 'Kelola Siswa', 'Master Data', 'SEMUA'),
(14, 'master_siswa.import_export', 'Import Export Siswa', 'Master Data', 'SEMUA'),
(15, 'master_kelas.manage', 'Kelola Kelas', 'Master Data', 'SEMUA'),
(16, 'master_tahun_ajaran.manage', 'Kelola Tahun Ajaran', 'Master Data', 'SEMUA'),
(17, 'master_mapel.manage', 'Kelola Mata Pelajaran', 'Master Data', 'SEMUA'),
(18, 'mapping_wali.manage', 'Kelola Mapping Wali', 'Master Data', 'SEMUA'),
(19, 'mapping_wali.view', 'Lihat Mapping Wali Diri', 'Master Data', 'DIRI_SENDIRI'),
(20, 'mapping_wali.view_all', 'Lihat Semua Mapping Wali', 'Master Data', 'SEMUA'),
(21, 'jadwal_guru.manage', 'Kelola Jadwal Guru', 'Master Data', 'SEMUA'),
(22, 'jadwal_guru.view', 'Lihat Jadwal Diri', 'Master Data', 'DIRI_SENDIRI'),
(23, 'jadwal_guru.view_all', 'Lihat Semua Jadwal', 'Master Data', 'SEMUA'),
(24, 'laporan_matrix.view', 'Lihat Matrix Presensi', 'Laporan', 'SEMUA,KELAS_DIAMPU'),
(25, 'laporan_export.generate', 'Generate Export Presensi', 'Laporan', 'SEMUA,KELAS_DIAMPU'),
(26, 'laporan_jurnal.view', 'Lihat Laporan Jurnal', 'Laporan', 'SEMUA,DIRI_SENDIRI'),
(27, 'laporan_jurnal.export', 'Export Laporan Jurnal', 'Laporan', 'SEMUA,DIRI_SENDIRI'),
(28, 'ews_radar.view', 'Lihat EWS Radar', 'Presensi', 'SEMUA,KELAS_DIAMPU'),
(29, 'bk_kasus.manage', 'Kelola Kasus BK', 'BK', 'SEMUA'),
(30, 'bk_kasus.view', 'Lihat Kasus BK', 'BK', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'),
(31, 'bk_pelanggaran_master.manage', 'Kelola Master Pelanggaran', 'BK', 'SEMUA'),
(32, 'prestasi.manage', 'Kelola Prestasi', 'BK', 'SEMUA'),
(33, 'prestasi.view', 'Lihat Prestasi', 'BK', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'),
(34, 'kartu_pelajar.manage', 'Kelola Kartu Pelajar', 'Kartu Pelajar', 'SEMUA,KELAS_DIAMPU'),
(35, 'kartu_pelajar.view', 'Lihat Kartu Pelajar', 'Kartu Pelajar', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'),
(36, 'settings_user.manage', 'Kelola User', 'Settings', 'SEMUA'),
(37, 'settings_menu.manage', 'Kelola Menu Role', 'Settings', 'SEMUA'),
(38, 'settings_sistem.manage', 'Kelola Setting Sistem', 'Settings', 'SEMUA'),
(39, 'backup.manage', 'Kelola Backup', 'Backup', 'SEMUA'),
(40, 'log_activity.view', 'Lihat Log Activity', 'Backup', 'SEMUA'),
(41, 'profile_guru.view', 'Lihat Profile Guru', 'Profile', 'DIRI_SENDIRI'),
(42, 'profile_guru.edit', 'Edit Profile Guru', 'Profile', 'DIRI_SENDIRI'),
(43, 'profile_siswa.view', 'Lihat Profile Siswa', 'Profile', 'DIRI_SENDIRI');
```

---

## 5.8 `role_permissions`

```sql
CREATE TABLE role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM(
        'admin',
        'operator',
        'pimpinan',
        'bk',
        'guru',
        'siswa'
    ) NOT NULL,
    id_permission INT UNSIGNED NOT NULL,
    scope VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_role_permission (role, id_permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Mapping canonical dijelaskan rinci pada `03_AUTH_RBAC_MENU`.

---

## 5.9 `role_menus`

```sql
CREATE TABLE role_menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM(
        'admin',
        'operator',
        'pimpinan',
        'bk',
        'guru',
        'siswa'
    ) NOT NULL,
    id_menu INT UNSIGNED NOT NULL,
    tampil TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_role_menu (role, id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Menu contextual Wali tetap ditentukan application layer.

---

# 6. Presensi

## 6.1 `presensi`

```sql
CREATE TABLE presensi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT UNSIGNED NULL,
    nama_siswa_snapshot VARCHAR(150) NOT NULL,
    id_kelas INT UNSIGNED NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    sesi ENUM('Sesi Awal','Sesi Akhir') NOT NULL,
    status ENUM('Hadir','Sakit','Izin','Alpha') NOT NULL,
    id_guru_input INT UNSIGNED NULL,
    nama_guru_input_snapshot VARCHAR(150) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    updated_by INT UNSIGNED NULL,

    UNIQUE KEY uk_presensi_siswa (
        id_kelas,
        tanggal,
        sesi,
        id_siswa
    ),

    KEY idx_presensi_laporan (
        id_tahun,
        tanggal,
        sesi,
        status
    ),

    KEY idx_presensi_siswa (
        id_siswa,
        tanggal
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Snapshot mempertahankan identitas historis bila data master berubah.

---

## 6.2 `presensi_mengajar`

```sql
CREATE TABLE presensi_mengajar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_guru INT UNSIGNED NULL,
    nama_guru_snapshot VARCHAR(150) NOT NULL,
    id_jadwal INT UNSIGNED NOT NULL,
    id_kelas INT UNSIGNED NOT NULL,
    id_tahun INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('Hadir','Izin','Sakit') NOT NULL,
    materi TEXT NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    updated_by INT UNSIGNED NULL,

    UNIQUE KEY uk_jurnal_jadwal_tanggal (
        id_jadwal,
        tanggal
    ),

    KEY idx_jurnal_guru_tanggal (
        id_guru,
        tanggal
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

# 7. BK dan Prestasi

## 7.1 `ref_pelanggaran`

```sql
CREATE TABLE ref_pelanggaran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggaran VARCHAR(150) NOT NULL,
    kategori ENUM('Ringan','Sedang','Berat') NOT NULL,
    poin INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 7.2 `catatan_kasus`

```sql
CREATE TABLE catatan_kasus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT UNSIGNED NOT NULL,
    id_pelanggaran INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT NULL,
    id_guru_input INT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    updated_by INT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 7.3 `catatan_prestasi`

```sql
CREATE TABLE catatan_prestasi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT UNSIGNED NOT NULL,
    nama_prestasi VARCHAR(200) NOT NULL,
    tingkat VARCHAR(100) NULL,
    tanggal DATE NOT NULL,
    penyelenggara VARCHAR(200) NULL,
    keterangan TEXT NULL,
    id_guru_input INT UNSIGNED NOT NULL,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

# 8. Kartu Pelajar

```sql
CREATE TABLE kartu_pelajar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT UNSIGNED NOT NULL,
    nomor_kartu VARCHAR(50) NOT NULL UNIQUE,
    kode_verifikasi VARCHAR(100) NOT NULL UNIQUE,
    tanggal_terbit DATE NOT NULL,
    status_aktif ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
    KEY idx_kartu_siswa (id_siswa, status_aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

# 9. Settings dan Log

## 9.1 `setting_sistem`

```sql
CREATE TABLE setting_sistem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'string',
    updated_at DATETIME NULL,
    updated_by INT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 9.2 `log_activity`

```sql
CREATE TABLE log_activity (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NULL,
    aksi VARCHAR(100) NOT NULL,
    modul VARCHAR(100) NOT NULL,
    keterangan TEXT NULL,
    waktu DATETIME NOT NULL,
    KEY idx_log_waktu (waktu),
    KEY idx_log_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

# 10. Foreign Key Canonical

```sql
ALTER TABLE kelas
ADD CONSTRAINT fk_kelas_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE anggota_kelas
ADD CONSTRAINT fk_anggota_siswa
FOREIGN KEY (id_siswa) REFERENCES siswa(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE anggota_kelas
ADD CONSTRAINT fk_anggota_kelas
FOREIGN KEY (id_kelas) REFERENCES kelas(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE anggota_kelas
ADD CONSTRAINT fk_anggota_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE mapping_wali_kelas
ADD CONSTRAINT fk_mapping_guru
FOREIGN KEY (id_guru) REFERENCES guru(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE mapping_wali_kelas
ADD CONSTRAINT fk_mapping_kelas
FOREIGN KEY (id_kelas) REFERENCES kelas(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE mapping_wali_kelas
ADD CONSTRAINT fk_mapping_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE jadwal_guru
ADD CONSTRAINT fk_jadwal_guru
FOREIGN KEY (id_guru) REFERENCES guru(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE jadwal_guru
ADD CONSTRAINT fk_jadwal_kelas
FOREIGN KEY (id_kelas) REFERENCES kelas(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE jadwal_guru
ADD CONSTRAINT fk_jadwal_mapel
FOREIGN KEY (id_mapel) REFERENCES mata_pelajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE jadwal_guru
ADD CONSTRAINT fk_jadwal_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE riwayat_siswa
ADD CONSTRAINT fk_riwayat_siswa
FOREIGN KEY (id_siswa) REFERENCES siswa(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE riwayat_siswa
ADD CONSTRAINT fk_riwayat_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE riwayat_siswa
ADD CONSTRAINT fk_riwayat_kelas
FOREIGN KEY (id_kelas) REFERENCES kelas(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE users
ADD CONSTRAINT fk_users_guru
FOREIGN KEY (id_guru) REFERENCES guru(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE users
ADD CONSTRAINT fk_users_pegawai
FOREIGN KEY (id_pegawai) REFERENCES pegawai(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE users
ADD CONSTRAINT fk_users_siswa
FOREIGN KEY (id_siswa) REFERENCES siswa(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE user_roles
ADD CONSTRAINT fk_user_roles_user
FOREIGN KEY (id_user) REFERENCES users(id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE api_tokens
ADD CONSTRAINT fk_api_tokens_user
FOREIGN KEY (id_user) REFERENCES users(id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE role_permissions
ADD CONSTRAINT fk_role_permission_permission
FOREIGN KEY (id_permission) REFERENCES permissions(id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE role_menus
ADD CONSTRAINT fk_role_menu_menu
FOREIGN KEY (id_menu) REFERENCES menus(id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE menus
ADD CONSTRAINT fk_menu_parent
FOREIGN KEY (parent_id) REFERENCES menus(id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE presensi
ADD CONSTRAINT fk_presensi_siswa
FOREIGN KEY (id_siswa) REFERENCES siswa(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE presensi
ADD CONSTRAINT fk_presensi_kelas
FOREIGN KEY (id_kelas) REFERENCES kelas(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE presensi
ADD CONSTRAINT fk_presensi_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE presensi
ADD CONSTRAINT fk_presensi_guru_input
FOREIGN KEY (id_guru_input) REFERENCES guru(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE presensi_mengajar
ADD CONSTRAINT fk_jurnal_guru
FOREIGN KEY (id_guru) REFERENCES guru(id)
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE presensi_mengajar
ADD CONSTRAINT fk_jurnal_jadwal
FOREIGN KEY (id_jadwal) REFERENCES jadwal_guru(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE presensi_mengajar
ADD CONSTRAINT fk_jurnal_kelas
FOREIGN KEY (id_kelas) REFERENCES kelas(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE presensi_mengajar
ADD CONSTRAINT fk_jurnal_tahun
FOREIGN KEY (id_tahun) REFERENCES tahun_ajaran(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE kartu_pelajar
ADD CONSTRAINT fk_kartu_siswa
FOREIGN KEY (id_siswa) REFERENCES siswa(id)
ON DELETE RESTRICT ON UPDATE CASCADE;
```

FK BK/Prestasi disesuaikan dengan tabel siswa/guru terkait menggunakan RESTRICT, kecuali kebutuhan histori mengharuskan snapshot/SET NULL.

---

# 11. Seeder Mata Pelajaran

Minimal seed mapel harus mempunyai `kode_mapel` unik.

Kategori mapel canonical sekolah:

```text
Al-Qur'an Hadis
Akidah Akhlak
Fikih
SKI
Bahasa Arab
PPKn
Bahasa Indonesia
Matematika
IPA
IPS
Bahasa Inggris
PJOK
Informatika
Seni Budaya
Bahasa Daerah
Prakarya
Bimbingan/Kegiatan sesuai kebijakan madrasah
```

Kode final harus konsisten dengan template Jadwal Guru.

---

# 12. Seeder Admin Awal

Fresh install memerlukan satu akun bootstrap Admin.

Password wajib di-hash menggunakan PHP `password_hash()` dan tidak boleh disimpan plaintext di SQL produksi.

Konsep data:

```text
username      = admin
role          = admin
status_aktif  = 1
auth_version  = 1
id_guru       = NULL
id_pegawai    = NULL
id_siswa      = NULL
```

Admin bootstrap adalah pengecualian terhadap aturan identitas normal.

---

# 13. Invariant Database + Service

Invariant yang wajib selalu benar:

1. NIK siswa unik dan 16 digit.
2. NISN unik.
3. NIP Guru unik di Guru.
4. NIP Pegawai unik di Pegawai.
5. NIP lintas Guru/Pegawai tidak boleh sama.
6. satu siswa hanya satu kelas per tahun.
7. satu mapping Wali aktif per Guru/tahun.
8. satu mapping Wali aktif per Kelas/tahun.
9. satu Tahun Ajaran aktif.
10. satu Presensi siswa per kelas/tanggal/sesi/siswa.
11. satu jurnal per jadwal/tanggal.
12. jadwal Guru tidak overlap.
13. jadwal Kelas tidak overlap.
14. role Pegawai boleh NULL.
15. Wali bukan role.
16. data historis tidak dihapus hanya karena data master berubah.

---

# 14. Fresh Install

Urutan fresh install:

```text
1. Buat database
2. CREATE tabel
3. Buat index/generated column
4. Tambah FK
5. Insert permissions
6. Insert menus
7. Insert role_permissions
8. Insert role_menus
9. Insert mapel
10. Insert settings default
11. Insert admin bootstrap
12. SET FOREIGN_KEY_CHECKS = 1
```

Seeder menu dan matriks role/permission harus mengikuti `03_AUTH_RBAC_MENU`.

---

# 15. Pemeriksaan Fresh Install

Setelah import:

```sql
SHOW TABLES;

SELECT COUNT(*) FROM permissions;
SELECT COUNT(*) FROM mata_pelajaran;
SELECT role, COUNT(*) FROM role_permissions GROUP BY role;
SELECT role, COUNT(*) FROM role_menus GROUP BY role;

SELECT
    id,
    nama_tahun,
    semester,
    status_aktif
FROM tahun_ajaran;
```

Expected:
- permission = 43;
- tidak ada FK error;
- `mapping_wali_kelas` memiliki generated column;
- `users.role` nullable;
- tidak ada role `wali_kelas`;
- seluruh tabel memakai engine InnoDB.
