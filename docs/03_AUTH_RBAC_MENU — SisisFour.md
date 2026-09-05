# 🔐 Auth, RBAC &amp; Menu — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini mengatur seluruh mekanisme **Autentikasi**, **Otorisasi (RBAC)**, dan **Navigasi Menu** untuk seluruh pengguna SisisFour. Fondasi keamanan ini menjadi filter utama sebelum pengguna mengakses fitur apa pun.

* * *

## 1. Autentikasi (Authentication)

### 1.1 Metode Autentikasi

- **Web (Browser):** Menggunakan **Session** berbasis database (`ci_sessions`). Session timeout: **2 jam** idle.
- **Mobile (APK Cordova):** Menggunakan **JWT (JSON Web Token)** dengan mekanisme Access Token + Refresh Token:
  
  - **Access Token:** Berlaku **1 jam**.
  - **Refresh Token:** Berlaku **30 hari**.
  - Disimpan di tabel `api_tokens`.

### 1.2 Single Active Session

- Setiap user hanya boleh memiliki **satu sesi aktif** di satu waktu (termasuk web &amp; mobile).
- **Mekanisme:** Menggunakan kolom `auth_version` di tabel `users`.
- **Alur:** Setiap login sukses (web maupun API), sistem **meng-increment** `auth_version`. Sistem mengecek versi di setiap request. Jika versi di session/token **lebih rendah** dari versi di database, sesi/token dianggap **invalid** dan user harus login ulang.

### 1.3 Struktur Session (WAJIB)

Setelah login berhasil, sistem **wajib** menyimpan data berikut di session (web). Untuk JWT, data ini dikemas dalam token dan diverifikasi ulang di setiap request.

```
session()->set([
    'user_id'     => $user['id'],
    'role'        => $user['role'],
    'username'    => $user['username'],
    'id_guru'     => $user['id_guru'],   // NULL jika siswa/admin
    'id_siswa'    => $user['id_siswa'],  // NULL jika guru
    'id_pegawai'  => $user['id_pegawai'],
    'auth_version'=> $user['auth_version'],
    'logged_in'   => true,
]);
```

### 1.4 Rate Limiting Login

- **5 kali percobaan login gagal** berturut-turut untuk username yang sama → **lock 5 menit**.
- Data dicatat di tabel `login_attempts`.
- **Catatan:** Rate limiting berbasis **username** (bukan IP) untuk menghindari lock massal di lingkungan sekolah (satu IP digunakan banyak user).

### 1.5 Reset Password

- Hanya **Admin** yang dapat mereset password.
- Default password di-reset ke:
  
  - **Siswa:** NISN
  - **Guru / Pegawai / Operator/Pimpinan berbasis Guru atau Pegawai:** NIP
  - **Admin:** username
- Password disimpan dalam bentuk **hash bcrypt**.

* * *

## 2. RBAC — Role, Scope &amp; Permission

### 2.1 Role (6) + Status Wali Kelas Dinamis

| Role           | Basis Identitas             | Catatan                                                                             |
|----------------|-----------------------------|-------------------------------------------------------------------------------------|
| **Admin**      | Berdiri sendiri             | Tidak memiliki data guru/pegawai/siswa terhubung.                                   |
| **Operator**   | `id_guru` atau `id_pegawai` | Wajib terkait data pegawai/guru; kewenangan administratif penuh. Admin awal adalah pengecualian. |
| **Pimpinan**   | `id_guru` atau `id_pegawai` | Akses read-only untuk supervisi — **tidak** memiliki permission \`.manage\` apapun. |
| **BK**         | Selalu `id_guru`            | Tidak memiliki jadwal mengajar (tidak ada kewajiban presensi mengajar).             |
| **Guru**       | `id_guru`                   | Dapat berstatus sebagai Wali Kelas (dinamis).                                       |
| **Wali Kelas** | `id_guru`                   | BUKAN role terpisah. Status dicek dari `mapping_wali_kelas`.                        |
| **Siswa**      | `id_siswa`                  | Akses terbatas pada data diri sendiri dan kartu pelajar.                            |

**Multi-Role:** Permission user adalah **union** dari `users.role` dan seluruh `user_roles.role`. Tidak ada hardcoded bypass Admin di aplikasi; Admin memperoleh akses melalui mapping permission `SEMUA`.

**Status Wali Kelas:** `Wali Kelas` tidak boleh menjadi nilai `users.role` maupun `user_roles.role`. Status ditentukan dinamis dari `mapping_wali_kelas` untuk tahun ajaran aktif. Seorang Guru yang menjadi Wali memperoleh **tambahan permission kontekstual Wali** hanya untuk kelas walinya. Jika mengajar kelas lain, ia kembali diperlakukan sebagai Guru Biasa.

### 2.2 Scope (5)

| Scope | Deskripsi |
| --- | --- |
| `SEMUA` | Seluruh data tanpa pembatasan kelas. |
| `KELAS_DIAMPU` | Hanya kelas yang **sedang menjadi kelas wali** user pada tahun ajaran aktif. Untuk modul tertentu, ini adalah scope khusus Wali Kelas. |
| `KELAS_TERJADWAL` | Hanya kelas yang aktif dijadwalkan kepada Guru pada hari berjalan. Dipakai untuk input Presensi Siswa Guru Biasa. |
| `DIRI_SENDIRI` | Hanya record yang secara langsung terkait user. |
| `TIDAK_ADA` | Tidak memiliki akses. |

**Penting:** `KELAS_DIAMPU` tidak berarti semua kelas yang pernah/sempat diajar. Untuk aturan Wali Kelas, daftar kelas berasal dari mapping wali aktif. Untuk Guru Biasa, akses input presensi memakai `KELAS_TERJADWAL`.

### 2.3 Daftar Permission (43)

Setiap permission memiliki `permission_key`, modul, dan daftar scope yang didukung (`scope_didukung`).

| \# | Permission Key                 | Modul             | Scope Didukung                                        |
|----|--------------------------------|-------------------|-------------------------------------------------------|
| 1  | `dashboard.view`               | Dashboard         | Otomatis                                              |
| 2  | `presensi_siswa.input`         | Presensi Siswa    | SEMUA, KELAS\_DIAMPU, KELAS\_TERJADWAL                |
| 3  | `presensi_siswa.revisi`        | Presensi Siswa    | SEMUA, KELAS\_DIAMPU                                  |
| 4  | `presensi_siswa.view`          | Presensi Siswa    | SEMUA, KELAS\_DIAMPU, DIRI\_SENDIRI |
| 5  | `presensi_mengajar.input`      | Presensi Mengajar | SEMUA, KELAS\_TERJADWAL, DIRI\_SENDIRI                |
| 6  | `presensi_mengajar.view`       | Presensi Mengajar | SEMUA, DIRI\_SENDIRI                                  |
| 7  | `master_guru.manage`           | Master Data       | SEMUA                                                 |
| 8  | `master_guru.view`             | Master Data       | SEMUA                                                 |
| 9  | `master_pegawai.manage`        | Master Data       | SEMUA                                                 |
| 10 | `master_pegawai.view`          | Master Data       | SEMUA                                                 |
| 11 | `master_siswa.view`            | Master Data       | SEMUA, KELAS\_DIAMPU, DIRI\_SENDIRI                   |
| 12 | `master_siswa.edit_biodata`    | Master Data       | SEMUA, KELAS\_DIAMPU                                  |
| 13 | `master_siswa.manage`          | Master Data       | SEMUA                                                 |
| 14 | `master_siswa.import_export`   | Master Data       | SEMUA                                                 |
| 15 | `master_kelas.manage`          | Master Data       | SEMUA                                                 |
| 16 | `master_tahun_ajaran.manage`   | Master Data       | SEMUA                                                 |
| 17 | `master_mapel.manage`          | Master Data       | SEMUA                                                 |
| 18 | `mapping_wali.manage`          | Master Data       | SEMUA                                                 |
| 19 | `mapping_wali.view_all`        | Master Data       | SEMUA                                                 |
| 20 | `mapping_wali.view`            | Master Data       | DIRI\_SENDIRI                                         |
| 21 | `jadwal_guru.manage`           | Master Data       | SEMUA                                                 |
| 22 | `jadwal_guru.view`             | Master Data       | DIRI\_SENDIRI                                         |
| 23 | `jadwal_guru.view_all`         | Master Data       | SEMUA                                                 |
| 24 | `laporan_matrix.view`          | Laporan           | SEMUA, KELAS\_DIAMPU                                  |
| 25 | `laporan_export.generate`      | Laporan           | SEMUA, KELAS\_DIAMPU                                  |
| 26 | `laporan_jurnal.view`          | Laporan           | SEMUA, DIRI\_SENDIRI                                  |
| 27 | `laporan_jurnal.export`        | Laporan           | SEMUA, DIRI\_SENDIRI                                  |
| 28 | `ews_radar.view`               | Presensi          | SEMUA, KELAS\_DIAMPU                                  |
| 29 | `bk_kasus.manage`              | BK                | SEMUA                                                 |
| 30 | `bk_kasus.view`                | BK                | SEMUA, KELAS\_DIAMPU, DIRI\_SENDIRI                   |
| 31 | `bk_pelanggaran_master.manage` | BK                | SEMUA                                                 |
| 32 | `prestasi.manage`              | BK                | SEMUA                                                 |
| 33 | `prestasi.view`                | BK                | SEMUA, KELAS\_DIAMPU, DIRI\_SENDIRI                   |
| 34 | `kartu_pelajar.manage`         | Kartu Pelajar     | SEMUA, KELAS\_DIAMPU                                  |
| 35 | `kartu_pelajar.view`           | Kartu Pelajar     | SEMUA, KELAS\_DIAMPU, DIRI\_SENDIRI                   |
| 36 | `settings_user.manage`         | Settings          | SEMUA                                                 |
| 37 | `settings_menu.manage`         | Settings          | SEMUA                                                 |
| 38 | `settings_sistem.manage`       | Settings          | SEMUA                                                 |
| 39 | `backup.manage`                | Backup            | SEMUA                                                 |
| 40 | `log_activity.view`            | Backup            | SEMUA                                                 |
| 41 | `profile_guru.view`            | Profile           | DIRI\_SENDIRI                                         |
| 42 | `profile_guru.edit`            | Profile           | DIRI\_SENDIRI                                         |
| 43 | `profile_siswa.view`           | Profile           | DIRI\_SENDIRI                                         |

**Keterangan Tambahan (Final):**

- `master_siswa.view` dan `master_siswa.edit_biodata` dengan scope `KELAS_DIAMPU` adalah **khusus konteks Wali Kelas**. Guru Biasa tidak memperoleh akses tersebut.
- `presensi_siswa.view`, `presensi_siswa.revisi`, Matrix, Export, EWS, BK detail, Prestasi view, dan Kartu view dengan scope `KELAS_DIAMPU` tidak boleh diperlakukan sebagai akses umum seluruh Guru; semuanya harus divalidasi bahwa user memang Wali aktif.
- `laporan_export.generate` wajib didahului akses `laporan_export`/view yang sesuai; **export tanpa view dilarang**.
- Guru Biasa tidak mempunyai fitur Matrix/Export/Laporan Presensi.
- Pimpinan tidak mempunyai permission `.manage` operasional, **kecuali `kartu_pelajar.manage` yang secara eksplisit merupakan kewenangan final Pimpinan**.
- Log Activity hanya untuk Admin dan Operator.
- Siswa hanya melihat rincian presensi dirinya pada status **Sakit/Izin/Alpha**; status Hadir tidak perlu ditampilkan pada rincian dashboard siswa.

* * *

## 2.4 Matriks Hak Akses Bisnis Final

| Fitur | Admin | Operator | Pimpinan | BK | Guru Biasa | Wali Kelas | Siswa |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Dashboard | Semua | Semua | Semua | Semua | Diri | Diri | Diri |
| Presensi Siswa — input | Semua | Semua | — | — | Kelas Terjadwal | Kelas Wali + dapat kapan saja | — |
| Presensi Siswa — lihat hasil tersimpan | Semua | Semua | Semua readonly | — | — | Kelas Wali | Diri: hanya S/I/A |
| Presensi Siswa — revisi | Semua | Semua | — | — | — | Kelas Wali | — |
| Presensi Mengajar/Jurnal | Semua | Semua | Diri | — | Diri | Diri | — |
| Master Siswa | Full | Full | Semua readonly | — | — | Kelas Wali | Diri readonly |
| Edit biodata siswa | Full | Full | — | — | — | Kelas Wali, **NISN immutable** | — |
| Matrix Presensi | Full | Full | Semua readonly | — | — | Kelas Wali | — |
| Export Presensi | Full | Full | Semua readonly | — | — | Kelas Wali | — |
| Laporan Jurnal | Full | Full | Semua readonly | — | — | Diri sendiri | — |
| BK Kasus | Full | Full | Semua readonly | Full | — | Detail kelas wali readonly | — |
| Prestasi — manage | Full | Full | — | Full | — | — | — |
| Prestasi — view | Full | Full | Semua | Full | — | Kelas Wali | Diri sendiri |
| Kartu — view/cetak | Full | Full | Full | — | — | Kelas Wali, individual & massal | Diri sendiri |
| Kartu — manage/terbitkan | Full | Full | Full | — | — | — | — |
| Log Activity | Full | Full | — | — | — | — | — |

**Aturan menu:** menu Wali Kelas bersifat **contextual**. Karena Wali bukan role, `MenuService` wajib menggabungkan role menu dengan hasil `AuthService::isWaliKelas()` dan permission kontekstual. Guru Biasa tidak boleh melihat menu Wali-only.

## 3. Authorization — PermissionFilter &amp; Scope Resolver

### 3.1 Arsitektur

- Semua cek akses dilakukan oleh **PermissionFilter** yang dipasang di **setiap grup route** (`master/`, `presensi/`, `laporan/`, `bk/`, `kartu/`, `settings/`, `backup/`).
- Controller **tidak boleh** melakukan cek permission secara manual — semua di level filter.

**Alur PermissionFilter:**

1. Filter menangkap request dan membaca `permission_key` yang dibutuhkan oleh route (didefinisikan di `Routes.php`).
2. Memanggil `AuthService::resolveScope($permission_key, $userId)`.
3. `resolveScope` mencari di tabel `role_permissions` berdasarkan role user (dari `users.role` + `user_roles`).
4. Jika user tidak memiliki permission → tolak akses (HTTP 403).
5. Jika memiliki, tambahkan **filter query otomatis** ke Model berdasarkan scope yang dikembalikan (lihat §3.3).

### 3.2 Implementasi `resolveScope()`

```
public function resolveScope($permission_key, $userId)
{
    $roles = $this->getUserRoles($userId);

    $result = $this->db->table('role_permissions')
        ->select('scope')
        ->whereIn('role', $roles)
        ->join('permissions', 'permissions.id = role_permissions.id_permission')
        ->where('permissions.permission_key', $permission_key)
        ->get()
        ->getRow();

    if (!$result) {
        return 'TIDAK_ADA';
    }

    return $result->scope;
}
```

### 3.3 Implementasi Scope di Model

- **SEMUA:** Tidak ada filter tambahan.
- **DIRI\_SENDIRI:** Tambahkan `->where('id_guru', session('id_guru'))` atau `->where('id_siswa', session('id_siswa'))`.
- **KELAS\_DIAMPU:** Ambil daftar `id_kelas` dari `mapping_wali_kelas` untuk `id_guru` yang login + `id_tahun` aktif. Jika daftar kosong, langsung return collection kosong (hindari `IN ()` invalid).
- **KELAS\_TERJADWAL:** Ambil daftar `id_kelas` dari `jadwal_guru` untuk `id_guru` yang login + `id_tahun` aktif + `hari = hari_ini`. Jika daftar kosong, langsung return collection kosong.

### 3.4 Status Wali Kelas — Dinamis

- **Larangan:** Jangan pernah menyimpan status `is_wali` di session.
- Status Wali Kelas dicek langsung ke database (`mapping_wali_kelas`) setiap kali dibutuhkan, karena bisa berubah kapan saja (soft delete/restore).
- Method baku: `AuthService::isWaliKelas($id_guru, $id_tahun = null)`.

### 3.5 Menu EWS Radar &amp; Top 20

- Fitur **EWS Radar** (`/presensi/siswa/ews`) dan **Top 20 Poin Pelanggaran** (`/bk/kasus/top`) **tidak** memiliki entri menu di sidebar.
- Keduanya diakses **hanya melalui widget dashboard** masing-masing role (Pimpinan, BK, Wali Kelas).
- Permission untuk akses tetap ada (`ews_radar.view` dan `bk_kasus.view`), tetapi tidak ditampilkan sebagai menu.

* * *

## 4. Menu &amp; Navigasi

### 4.1 Prinsip

- Menu dibangun dari tabel `menus` dan `role_menus`.
- Menu yang tampil di sidebar ditentukan oleh `tampil = 1` di `role_menus`.
- Meskipun menu tampil, akses ke halaman tetap diatur oleh **PermissionFilter**. Jika user tidak punya permission untuk link tersebut, akan ditolak di level filter.
- Menu **Data Guru (31)** DIsembunyikan untuk role `guru` (non-wali dan wali) dan `siswa` — karena digantikan oleh Profile Guru.

### 4.2 Struktur Menu Final

| ID | Nama Menu          | Parent | Urutan | Icon               | Link                    |
|----|--------------------|--------|--------|--------------------|-------------------------|
| 1  | Dashboard          | NULL   | 1      | bx bx-home-circle  | dashboard               |
| 2  | Presensi           | NULL   | 2      | bx bx-check-shield | \#                      |
| 21 | Presensi Siswa     | 2      | 1      |                    | presensi/siswa          |
| 22 | Presensi Mengajar  | 2      | 2      |                    | presensi/mengajar       |
| 3  | Master Data        | NULL   | 3      | bx bx-data         | \#                      |
| 31 | Data Guru          | 3      | 1      |                    | master/guru             |
| 32 | Data Pegawai       | 3      | 2      |                    | master/pegawai          |
| 33 | Data Siswa         | 3      | 3      |                    | master/siswa            |
| 34 | Data Kelas         | 3      | 4      |                    | master/kelas            |
| 35 | Tahun Ajaran       | 3      | 5      |                    | master/tahun            |
| 36 | Mata Pelajaran     | 3      | 6      |                    | master/mapel            |
| 37 | Mapping Wali Kelas | 3      | 7      |                    | master/wali-kelas       |
| 38 | Jadwal Guru        | 3      | 8      |                    | master/jadwal           |
| 4  | Laporan            | NULL   | 4      | bx bx-file         | \#                      |
| 41 | Matrix Presensi    | 4      | 1      |                    | laporan/presensi/matrix |
| 42 | Export Presensi    | 4      | 2      |                    | laporan/presensi/export |
| 43 | Laporan Jurnal     | 4      | 3      |                    | laporan/jurnal          |
| 5  | BK &amp; Prestasi  | NULL   | 5      | bx bx-user-voice   | \#                      |
| 51 | Catatan Kasus      | 5      | 1      |                    | bk/kasus                |
| 52 | Master Pelanggaran | 5      | 2      |                    | bk/pelanggaran          |
| 53 | Prestasi Siswa     | 5      | 3      |                    | bk/prestasi             |
| 6  | Kartu Pelajar      | NULL   | 6      | bx bx-id-card      | \#                      |
| 61 | Daftar Kartu       | 6      | 1      |                    | kartu/daftar            |
| 62 | Terbitkan Kartu    | 6      | 2      |                    | kartu/generate          |
| 9  | Profile Guru       | NULL   | 7      | bx bx-user         | profile/guru            |
| 10 | Profile Siswa      | NULL   | 7      | bx bx-user         | profile/siswa           |
| 7  | Settings           | NULL   | 8      | bx bx-cog          | \#                      |
| 71 | Manajemen User     | 7      | 1      |                    | settings/user           |
| 72 | Menu &amp; Role    | 7      | 2      |                    | settings/menu           |
| 73 | Setting Sistem     | 7      | 3      |                    | settings/sistem         |
| 8  | Backup &amp; Log   | NULL   | 9      | bx bx-server       | \#                      |
| 81 | Backup             | 8      | 1      |                    | backup                  |
| 82 | Log Activity       | 8      | 2      |                    | log/activity            |

### 4.3 Matrix Akses Menu per Role

✓ = Tampil di sidebar. ✗ = Tidak tampil.

| Menu (ID)               | Admin | Operator | Pimpinan | BK | Guru (non-wali) | Wali Kelas | Siswa |
|-------------------------|-------|----------|----------|----|-----------------|------------|-------|
| Dashboard (1)           | ✓     | ✓        | ✓        | ✓  | ✓               | ✓          | ✓     |
| Presensi (2)            | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Presensi Siswa (21)     | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Presensi Mengajar (22)  | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Master Data (3)         | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Data Guru (31)          | ✓     | ✓        | ✓        | ✗  | ✗               | ✗          | ✗     |
| Data Pegawai (32)       | ✓     | ✓        | ✓        | ✗  | ✗               | ✗          | ✗     |
| Data Siswa (33)         | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Data Kelas (34)         | ✓     | ✓        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Tahun Ajaran (35)       | ✓     | ✓        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Mata Pelajaran (36)     | ✓     | ✓        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Mapping Wali (37)       | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Jadwal Guru (38)        | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Laporan (4)             | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Matrix Presensi (41)    | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| Export Presensi (42)    | ✓     | ✓        | ✓        | ✗  | ✗               | ✓          | ✗     |
| Laporan Jurnal (43)     | ✓     | ✓        | ✓        | ✗  | ✓               | ✓          | ✗     |
| BK &amp; Prestasi (5)   | ✓     | ✓        | ✓        | ✓  | ✓               | ✓          | ✗     |
| Catatan Kasus (51)      | ✓     | ✓        | ✓        | ✓  | ✓               | ✓          | ✗     |
| Master Pelanggaran (52) | ✓     | ✓        | ✗        | ✓  | ✗               | ✗          | ✗     |
| Prestasi Siswa (53)     | ✓     | ✓        | ✓        | ✓  | ✓               | ✓          | ✗     |
| Kartu Pelajar (6)       | ✓     | ✓        | ✓        | ✗  | ✗               | ✓          | ✓     |
| Daftar Kartu (61)       | ✓     | ✓        | ✓        | ✗  | ✗               | ✓          | ✓     |
| Terbitkan Kartu (62)    | ✓     | ✓        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Profile Guru (9)        | ✗     | ✗        | ✓        | ✓  | ✓               | ✓          | ✗     |
| Profile Siswa (10)      | ✗     | ✗        | ✗        | ✗  | ✗               | ✗          | ✓     |
| Settings (7)            | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Manajemen User (71)     | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Menu &amp; Role (72)    | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Setting Sistem (73)     | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Backup &amp; Log (8)    | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Backup (81)             | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |
| Log Activity (82)       | ✓     | ✗        | ✗        | ✗  | ✗               | ✗          | ✗     |

**Catatan Khusus:**

- Menu **Data Guru (31)** disembunyikan untuk `guru` dan `siswa` — digantikan oleh Profile Guru.
- **Wali Kelas** mendapatkan menu **Data Siswa (33)** untuk mengedit biodata siswa di kelasnya (scope KELAS\_DIAMPU).
- **Export Presensi (42)** tersedia untuk **Wali Kelas** (scope KELAS\_DIAMPU), tetapi tidak untuk Guru non-wali.
- **Settings &amp; Backup** hanya untuk Admin.
- Pimpinan hanya memiliki akses **readonly** — tidak ada akses edit/manage.

* * *

## 5. Catatan Penting untuk Developer

- **PermissionFilter:** Dipasang di semua grup route. Controller tidak boleh melakukan cek permission manual.
- **Wali Kelas:** Jangan disimpan di session. Cek dinamis menggunakan `AuthService::isWaliKelas()`.
- **Multi-Role:** PermissionFilter wajib mengecek `user_roles` selain `users.role`.
- **Single Active Session:** Cek `auth_version` di setiap request (web dan API).
- **EWS &amp; Top 20:** Tidak punya menu, hanya diakses lewat widget dashboard.
- **Session Structure:** Wajib menyimpan `id_guru`, `id_siswa`, dan `auth_version` seperti yang sudah ditentukan.
- **Pimpinan:** Pastikan Pimpinan hanya memiliki permission \`.view\` dan \`.view\_all\`, **tidak** memiliki \`.manage\` apapun.
- **Guard for empty array:** Pada implementasi scope `KELAS_DIAMPU` dan `KELAS_TERJADWAL`, jika `$daftarKelas` kosong, langsung return collection kosong — jangan jalankan query builder.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Auth, RBAC &amp; Menu Final
