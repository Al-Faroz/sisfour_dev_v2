# 🔐 Authentication, RBAC & Menu — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan Authentication, Authorization, role, permission, scope, menu, contextual Wali Kelas, dan perlindungan route SisisFour.

---

# 1. Authentication Web

Web menggunakan session database.

Login sukses menyimpan:

```php
session()->set([
    'user_id'      => $user['id'],
    'role'         => $user['role'],
    'username'     => $user['username'],
    'id_guru'      => $user['id_guru'],
    'id_pegawai'   => $user['id_pegawai'],
    'id_siswa'     => $user['id_siswa'],
    'auth_version' => $user['auth_version'],
    'logged_in'    => true,
]);
```

`role` session adalah primary role dan boleh NULL untuk akun Pegawai yang belum mempunyai role operasional.

Authorization tidak boleh hanya menggunakan `session('role')`.

---

# 2. Authentication Mobile

Mobile menggunakan JWT.

Komponen:

```text
Access Token
Refresh Token
api_tokens
auth_version
```

Ketentuan:

- Access Token berlaku 1 jam.
- Refresh Token berlaku 30 hari.
- Token yang revoked tidak valid.
- Token dengan `auth_version` lebih lama dari database tidak valid.

Authentication Mobile tidak menggunakan session Web sebagai sumber identitas utama.

---

# 3. Multi-Role

Role user:

```text
users.role
UNION
user_roles.role
```

`AuthService::getUserRoles($userId)` menjadi sumber role efektif.

Contoh:

```text
users.role = guru
user_roles = operator

effective roles:
guru + operator
```

Permission efektif adalah union semua permission dari role tersebut.

Primary role tidak boleh menghapus permission yang berasal dari secondary role.

## 3.1 Prioritas Kewenangan Efektif

Jika seorang user mempunyai lebih dari satu role atau konteks bisnis sekaligus, kewenangan efektif mengikuti tingkat berikut dari tertinggi ke terendah:

```text
Admin
>
Operator
>
Pimpinan
>
Wali Kelas
>
Guru biasa
>
BK
>
Siswa
```

Catatan penting:

- `Wali Kelas` bukan role database, tetapi konteks tambahan untuk user Guru;
- urutan ini bukan pengganti permission table, melainkan aturan saat beberapa permission/konteks yang sah bertemu pada aksi yang sama;
- Admin/Operator dengan scope `SEMUA` menang atas scope Guru/Wali yang lebih sempit;
- Pimpinan tetap readonly walaupun berada di atas Wali/Guru dalam hierarki bisnis;
- Service tidak boleh hardcode berdasarkan nama role saja bila keputusan sebenarnya berasal dari permission + scope.

Contoh:

```text
Guru + Operator
→ permission efektif union
→ untuk Presensi, hak Operator/SEMUA menang
→ tidak terkena pembatas time-window/geofence Guru
```

---

# 4. Role Resmi

```text
admin
operator
pimpinan
bk
guru
siswa
```

`wali_kelas` tidak valid sebagai role.

Tidak boleh ada:

```text
users.role = wali_kelas
user_roles.role = wali_kelas
```

---

# 5. Wali Kelas

Wali adalah status kontekstual.

Validasi Wali:

```text
user.id_guru
    ↓
mapping_wali_kelas
    ↓
deleted_at IS NULL
    ↓
id_tahun = tahun ajaran aktif
```

Wali memperoleh scope tambahan `KELAS_DIAMPU` hanya untuk kelas aktif yang diwalikan.

Jika mapping dinonaktifkan, akses Wali hilang tanpa mengubah role.

Status Wali tidak boleh di-cache sebagai role permanen.

## 5.1 Pergantian Wali

Hak Wali selalu mengikuti mapping aktif saat ini.

Jika Wali lama tidak lagi menjadi Wali:

```text
mapping_wali_kelas.deleted_at IS NOT NULL
```

maka hak `KELAS_DIAMPU` atas kelas tersebut langsung hilang.

Wali baru yang menjadi mapping aktif memperoleh hak Wali terhadap kelas tersebut, termasuk hak melihat dan merevisi histori Presensi kelas pada tahun ajaran aktif sesuai permission.

Hak histori tidak melekat permanen pada Wali lama.

---

# 6. Scope

## 6.1 `SEMUA`

Akses seluruh record dalam modul.

Contoh:

```text
Admin melihat semua siswa
Operator mengelola semua kelas
Pimpinan melihat semua jadwal
```

---

## 6.2 `KELAS_DIAMPU`

Hanya kelas Wali aktif.

Harus menghasilkan `TIDAK_ADA` bila user bukan Wali aktif.

Sumber kelas:

```text
mapping_wali_kelas
WHERE id_guru = user.id_guru
AND id_tahun = tahun aktif
AND deleted_at IS NULL
```

---

## 6.3 `KELAS_TERJADWAL`

Kelas yang terjadwal kepada Guru berdasarkan Jadwal Guru aktif dan konteks hari/waktu yang dibutuhkan modul.

Sumber:

```text
jadwal_guru
WHERE id_guru = user.id_guru
AND id_tahun = tahun aktif
AND status_jadwal = Aktif
```

Untuk Presensi, Service dapat menambah pembatas hari/sesi/time-window.

---

## 6.4 `DIRI_SENDIRI`

Hanya record milik identitas user.

Contoh:

```text
Guru melihat jadwal sendiri
Guru melihat profile sendiri
Siswa melihat profile sendiri
```

---

## 6.5 `TIDAK_ADA`

Tidak memiliki akses.

Service harus mengembalikan collection kosong atau menolak aksi, bukan menjalankan query tanpa pembatas.

---

# 7. Prioritas Scope

Prioritas scope efektif:

```text
SEMUA
>
KELAS_DIAMPU / KELAS_TERJADWAL
>
DIRI_SENDIRI
>
TIDAK_ADA
```

Untuk permission contextual:

```text
role permission mengatakan KELAS_DIAMPU
        ↓
AuthService cek apakah user Wali aktif
        ↓
YA    → KELAS_DIAMPU
TIDAK → TIDAK_ADA
```

Dengan pola ini Admin yang memiliki `SEMUA` tidak ikut terkena pemeriksaan Wali.

## 7.1 Scope Presensi Harus Diselesaikan Per Target

Khusus Presensi, satu nilai scope global tidak cukup untuk menggambarkan user Guru yang juga Wali.

Presensi Service harus melakukan resolusi berdasarkan:

```text
permission
+ user
+ tahun aktif
+ kelas target
+ tanggal
+ sesi
+ jadwal aktif
+ mapping Wali aktif
+ aksi yang diminta
```

Contoh:

```text
User = Guru + Wali 7-A

Target 7-A
→ dapat memiliki KELAS_DIAMPU

Target 8-B yang dia ajar
→ KELAS_TERJADWAL

Target 9-C tanpa mapping/jadwal
→ TIDAK_ADA
```

Service tidak boleh hanya mengambil satu scalar scope lalu menganggapnya berlaku sama untuk semua kelas.

---

# 8. Permission Canonical

| ID | Permission | Scope |
|---:|---|---|
| 1 | `dashboard.view` | Otomatis |
| 2 | `presensi_siswa.input` | SEMUA, KELAS_DIAMPU, KELAS_TERJADWAL |
| 3 | `presensi_siswa.revisi` | SEMUA, KELAS_DIAMPU |
| 4 | `presensi_siswa.view` | SEMUA, KELAS_DIAMPU, DIRI_SENDIRI |
| 5 | `presensi_mengajar.input` | SEMUA, KELAS_TERJADWAL, DIRI_SENDIRI |
| 6 | `presensi_mengajar.view` | SEMUA, DIRI_SENDIRI |
| 7 | `master_guru.manage` | SEMUA |
| 8 | `master_guru.view` | SEMUA |
| 9 | `master_pegawai.manage` | SEMUA |
| 10 | `master_pegawai.view` | SEMUA |
| 11 | `master_siswa.view` | SEMUA, KELAS_DIAMPU, DIRI_SENDIRI |
| 12 | `master_siswa.edit_biodata` | SEMUA, KELAS_DIAMPU |
| 13 | `master_siswa.manage` | SEMUA |
| 14 | `master_siswa.import_export` | SEMUA |
| 15 | `master_kelas.manage` | SEMUA |
| 16 | `master_tahun_ajaran.manage` | SEMUA |
| 17 | `master_mapel.manage` | SEMUA |
| 18 | `mapping_wali.manage` | SEMUA |
| 19 | `mapping_wali.view` | DIRI_SENDIRI |
| 20 | `mapping_wali.view_all` | SEMUA |
| 21 | `jadwal_guru.manage` | SEMUA |
| 22 | `jadwal_guru.view` | DIRI_SENDIRI |
| 23 | `jadwal_guru.view_all` | SEMUA |
| 24 | `laporan_matrix.view` | SEMUA, KELAS_DIAMPU |
| 25 | `laporan_export.generate` | SEMUA, KELAS_DIAMPU |
| 26 | `laporan_jurnal.view` | SEMUA, DIRI_SENDIRI |
| 27 | `laporan_jurnal.export` | SEMUA, DIRI_SENDIRI |
| 28 | `ews_radar.view` | SEMUA, KELAS_DIAMPU |
| 29 | `bk_kasus.manage` | SEMUA |
| 30 | `bk_kasus.view` | SEMUA, KELAS_DIAMPU, DIRI_SENDIRI |
| 31 | `bk_pelanggaran_master.manage` | SEMUA |
| 32 | `prestasi.manage` | SEMUA |
| 33 | `prestasi.view` | SEMUA, KELAS_DIAMPU, DIRI_SENDIRI |
| 34 | `kartu_pelajar.manage` | SEMUA, KELAS_DIAMPU |
| 35 | `kartu_pelajar.view` | SEMUA, KELAS_DIAMPU, DIRI_SENDIRI |
| 36 | `settings_user.manage` | SEMUA |
| 37 | `settings_menu.manage` | SEMUA |
| 38 | `settings_sistem.manage` | SEMUA |
| 39 | `backup.manage` | SEMUA |
| 40 | `log_activity.view` | SEMUA |
| 41 | `profile_guru.view` | DIRI_SENDIRI |
| 42 | `profile_guru.edit` | DIRI_SENDIRI |
| 43 | `profile_siswa.view` | DIRI_SENDIRI |

---

# 9. Matriks Hak Bisnis

| Fitur | Admin | Operator | Pimpinan | BK | Guru | Wali | Siswa |
|---|---|---|---|---|---|---|---|
| Dashboard | Semua | Semua | Semua | Semua | Diri | Diri | Diri |
| Presensi Siswa Input | Semua | Semua | — | — | Kelas Terjadwal | Kelas Wali | — |
| Presensi Siswa View | Semua | Semua | Semua readonly | — | — | Kelas Wali | Diri |
| Presensi Siswa Revisi | Semua | Semua | — | — | — | Kelas Wali | — |
| Jurnal Input | Semua | Semua | Diri bila punya jadwal | — | Diri | Diri | — |
| Jurnal View | Semua | Semua | Semua readonly | — | Diri | Diri | — |
| Guru | Full | Full | Readonly | — | — | — | — |
| Pegawai | Full | Full | Readonly | — | — | — | — |
| Siswa | Full | Full | Readonly semua | — | — | Kelas Wali | Diri |
| Kelas | Full | Full | — | — | — | — | — |
| Tahun Ajaran | Full | Full | — | — | — | — | — |
| Mapel | Full | Full | — | — | — | — | — |
| Mapping Wali | Full | Full | Semua readonly | — | Diri | Diri | — |
| Jadwal Guru | Full | Full | Semua readonly | — | Diri | Diri | — |
| Matrix Presensi | Full | Full | Semua readonly | — | — | Kelas Wali | — |
| Export Presensi | Full | Full | Semua readonly sesuai permission | — | — | Kelas Wali | — |
| BK Kasus | Full | Full | Readonly | Full | — | Detail Wali readonly | — |
| Prestasi Manage | Full | Full | — | Full | — | — | — |
| Prestasi View | Full | Full | Semua | Semua | — | Kelas Wali | Diri |
| Kartu View | Full | Full | Full | — | — | Kelas Wali | Diri |
| Kartu Manage | Full | Full | sesuai permission final | — | — | — | — |
| Log Activity | Full | Full | — | — | — | — | — |

---

# 10. Admin

Admin mendapat akses penuh melalui permission, bukan bypass hardcoded.

Scope utama:

```text
SEMUA
```

Admin bootstrap boleh tidak mempunyai:

```text
id_guru
id_pegawai
id_siswa
```

Karena itu service/menu tidak boleh mengharuskan Admin menjadi Guru atau Wali.

---

# 11. Operator

Operator mendapat kewenangan administratif terhadap:

- Presensi;
- Master Data;
- Laporan;
- BK sesuai permission;
- Prestasi;
- Kartu;
- Settings;
- Backup;
- Log.

Identity normal Operator harus terkait Guru/Pegawai.

Jika user juga memiliki role Guru/Wali, kewenangan Operator yang memiliki scope `SEMUA` tetap menang untuk aksi yang permission-nya diberikan kepada Operator.

---

# 12. Pimpinan

Pimpinan berorientasi supervisi.

Prinsip:

- readonly data operasional;
- tidak memperoleh `.manage` umum;
- dapat `mapping_wali.view_all`;
- dapat `jadwal_guru.view_all`;
- dapat melihat Presensi/Laporan sesuai permission;
- tidak boleh memperoleh mutation hanya karena menu terlihat.

Button mutasi tidak boleh tampil bila user tidak mempunyai manage permission.

Direct mutation route juga harus menolak.

---

# 13. BK

BK:

- Dashboard;
- EWS sesuai permission;
- BK;
- Pelanggaran;
- Prestasi;
- Profile Guru.

BK tidak otomatis memperoleh akses Master Siswa umum.

---

# 14. Guru

Guru:

- Dashboard;
- Presensi Siswa input sesuai Jadwal;
- Presensi Mengajar diri;
- Mapping Wali diri;
- Jadwal diri;
- Profile diri.

Guru biasa tidak memiliki Master Siswa.

---

# 15. Wali

Wali tetap role `guru`.

Tambahan contextual:

- Master Siswa view kelas Wali;
- edit biodata/foto siswa kelas Wali;
- Presensi Siswa kelas Wali;
- revisi Presensi kelas Wali;
- Matrix;
- Export;
- EWS;
- BK detail readonly;
- Prestasi view;
- Kartu view/cetak.

Semua dibatasi `KELAS_DIAMPU`.

Wali tidak boleh:

- mengubah NISN;
- mutasi siswa;
- kenaikan;
- kelulusan;
- import/export Master Siswa;
- mengelola Tahun/Kelas/Mapel hanya karena menjadi Wali.

## 15.1 Dual-Context Guru + Wali pada Presensi Siswa

Seorang Guru dapat sekaligus menjadi Wali kelas.

Untuk kelas Wali sendiri:

1. bila pada tanggal/sesi tersebut terdapat jadwal aktif miliknya yang cocok, input normal pertama diproses sebagai **Guru Terjadwal**;
2. pada jalur Guru Terjadwal berlaku time-window dan geofence;
3. jika jalur jadwal tidak valid atau time-window sudah lewat, hak Wali dapat menjadi fallback untuk kelas Wali;
4. fallback Wali bebas time-window dan geofence;
5. Wali tetap dapat melihat dan merevisi data tersimpan pada kelas Wali;
6. hak Wali tidak berlaku pada kelas lain yang hanya dia ajar.

Contoh:

```text
Wali 7-A
+ jadwal Sesi Awal 7-A 07:00–08:00

07:30
→ Guru Terjadwal
→ time-window berlaku
→ geofence berlaku

10:00 dan Presensi belum diinput
→ jalur jadwal sudah lewat
→ fallback KELAS_DIAMPU
→ Wali masih boleh input kelas 7-A

Target 8-B yang dia ajar
→ Guru biasa
→ tidak ada fallback Wali
```

Aturan detail Presensi tetap mengacu ke `05_PRESENSI`.

---

# 16. Siswa

Siswa:

- Dashboard diri;
- profile diri;
- kartu diri;
- prestasi diri;
- rincian Presensi diri.

Tidak ada menu administrasi Master Data.

---

# 17. PermissionFilter

PermissionFilter bertanggung jawab untuk:

1. memastikan login;
2. membaca permission yang diperlukan route;
3. memanggil `resolveScope`;
4. menolak bila semua hasil `TIDAK_ADA`;
5. mengizinkan Controller bila minimal satu permission valid.

Filter **tidak boleh dianggap otomatis memfilter setiap query**.

Service tetap bertanggung jawab menerapkan pembatasan dataset sesuai scope.

Khusus Presensi, PermissionFilter hanya menjadi gate permission awal. Presensi Service wajib melakukan resolusi kontekstual per target kelas/tanggal/sesi/aksi sebagaimana bagian 7.1 dan 15.1.

---

# 18. Data-Level Authorization

Contoh Master Siswa:

```text
PermissionFilter
    ↓
master_siswa.view valid
    ↓
SiswaService
    ↓
resolveScope(master_siswa.view)
    ↓
SEMUA / KELAS_DIAMPU / DIRI_SENDIRI
    ↓
query dibatasi
```

Jangan hanya mengandalkan UI.

Untuk Presensi, data-level authorization harus lebih spesifik:

```text
PermissionFilter
    ↓
permission valid
    ↓
PresensiService
    ↓
resolve target kelas/tanggal/sesi/aksi
    ↓
SEMUA / GURU_TERJADWAL / WALI / DIRI_SENDIRI / TIDAK_ADA
    ↓
validasi time-window/geofence bila diperlukan
    ↓
query/mutasi dibatasi
```

---

# 19. MenuService

Menu dasar:

```text
role_menus
UNION untuk semua effective role
```

Setelah itu contextual menu diperiksa dengan `AuthService::resolveScope()`.

Prinsip:

- Admin/Operator/Pimpinan tidak boleh hilang menu hanya karena bukan Wali;
- Guru biasa tidak boleh melihat Wali-only;
- Wali melihat menu contextual;
- empty parent group di-prune;
- menu bukan sistem authorization utama.

---

# 20. Menu Data Siswa

Harus mengikuti:

```text
Admin      → tampil
Operator   → tampil
Pimpinan   → tampil readonly
Guru biasa → tidak tampil
Wali       → tampil contextual
Siswa      → bukan menu admin
```

Permission relevan:

```text
master_siswa.view
master_siswa.manage
master_siswa.edit_biodata
```

---

# 21. Role Menus

`role_menus` menentukan kandidat menu per role.

Karena user multi-role:

```text
menu efektif = union seluruh role_menus role user
```

Menu parent seperti `Master Data` harus ikut tersedia bila child membutuhkan parent.

Wali-only child dapat diberikan sebagai kandidat Guru lalu dipangkas secara contextual oleh MenuService.

---

# 22. CSRF Web

CSRF global aktif untuk Web.

Request mutasi menggunakan header:

```text
X-CSRF-TOKEN
```

Token diletakkan di meta header layout dan disuntikkan oleh wrapper Fetch global.

Konfigurasi CSRF Web harus cocok dengan pola AJAX aplikasi.

API mobile menggunakan JWT dan tidak bergantung pada CSRF Web.

---

# 23. Rate Limiting

Login gagal:

```text
5 kali berturut-turut
→ lock 5 menit
```

Berbasis username, bukan hanya IP.

`login_attempts` menyimpan:

```text
username
ip_address
waktu
berhasil
```

---

# 24. Password

Default:

```text
Guru     → NIP
Pegawai  → NIP
Siswa    → NISN
Admin bootstrap → username
```

Semua disimpan menggunakan password hash.

Reset password hanya user dengan permission administratif yang ditentukan Settings.

---

# 25. Single Active Session

Setiap login meningkatkan:

```text
users.auth_version
```

Request berikutnya membandingkan version session/token dengan database.

Mismatch:

```text
logout / 401 / login ulang
```

---

# 26. Status User

`users.status_aktif = 0` harus mencegah login.

Soft delete Guru/Pegawai/Siswa harus sinkron dengan account terkait.

Restore harus mengaktifkan kembali atau membuat account bila dibutuhkan.

---

# 27. Route Contract

Semua route privat berada dalam group Auth.

Contoh:

```php
$routes->get(
    'master/siswa',
    'MasterSiswa::index',
    ['filter' => 'permission:master_siswa.view,master_siswa.manage,master_siswa.edit_biodata']
);
```

Mutation route wajib memakai manage permission yang tepat.

Tidak boleh ada route mutasi yang hanya terlindungi `auth`.

---

# 28. OR Permission pada Route

Bila route menerima beberapa permission:

```text
permission:a,b,c
```

artinya user boleh masuk bila minimal satu permission resolve ke selain `TIDAK_ADA`.

Service tetap menentukan apa yang boleh dilakukan setelah masuk.

---

# 29. HTTP Status

Recommended:

```text
200 OK
201 Created
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
500 Internal Server Error
```

AJAX menerima JSON error yang konsisten.

---

# 30. JSON Authorization

Response JSON hanya boleh mengandung data sesuai scope.

Jangan mengirim semua data ke frontend lalu menyembunyikan sebagian dengan JavaScript.

---

# 31. Security Rule

Tidak boleh mempercayai:

```text
id_user dari form
id_guru dari form untuk Guru biasa
role dari client
kelas dari client tanpa validasi
scope dari client
status Wali dari client
```

Sumber identitas adalah session/token + database.

---

# 32. Audit Checklist Auth

- role hanya 6;
- Wali bukan role;
- role NULL didukung;
- multi-role union;
- prioritas kewenangan efektif terdokumentasi;
- Admin/Operator dengan SEMUA menang atas scope lebih sempit;
- Admin tidak hardcoded bypass;
- scope Wali tervalidasi;
- hak Wali hilang saat mapping tidak aktif;
- Wali baru memperoleh hak histori kelas pada tahun aktif;
- dual-context Guru/Wali Presensi diselesaikan per target kelas/tanggal/sesi;
- Guru non-Wali tidak mendapat kelas Wali;
- menu contextual benar;
- direct route 403;
- CSRF aktif;
- user nonaktif tidak login;
- auth_version bekerja;
- password di-hash;
- JSON tidak bocor scope;
- Service menerapkan data-level authorization.
