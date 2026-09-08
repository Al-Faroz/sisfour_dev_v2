# 🔐 Authentication, RBAC & Menu — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026  
**Status:** FINAL RBAC + Presensi + Dashboard

Dokumen ini adalah sumber aturan Authentication, Authorization, role, permission, multi-scope, menu, contextual Wali Kelas, dan route gate SisisFour.

---

# 1. Prinsip Authentication

Web menggunakan session database. Mobile menggunakan JWT pada modul API/Mobile.

Session web minimum:

```text
user_id
role              = primary role, boleh NULL untuk Pegawai tanpa role operasional
username
id_guru
id_pegawai
id_siswa
auth_version
logged_in
```

Authorization **tidak boleh** hanya memakai `session('role')`.

---

# 2. Role Resmi

Role database hanya:

```text
admin
operator
pimpinan
bk
guru
siswa
```

`wali_kelas` **bukan role**.

Dilarang membuat:

```text
users.role = wali_kelas
user_roles.role = wali_kelas
```

---

# 3. Multi-Role

Effective roles:

```text
users.role
UNION
user_roles.role
```

Sumber canonical:

```php
AuthService::getUserRoles($userId)
```

Permission user adalah union seluruh effective role.

Contoh:

```text
Primary : guru
Secondary: operator

Untuk aksi yang Operator mempunyai SEMUA:
→ capability Operator menang
→ tidak terkena pembatas Guru seperti geofence/time-window pada aksi tersebut.
```

---

## 3.1 Contoh Guru + Pimpinan

Kombinasi `guru + pimpinan` valid melalui union `users.role + user_roles.role`. Permission adalah union keduanya. Dashboard priority memilih `Pimpinan`, tetapi identitas `users.id_guru` dan capability Guru yang memang diberikan tetap tersedia. Pimpinan tidak memperoleh mutation yang role Pimpinan sendiri tidak punya; mutation Guru hanya berlaku bila permission/scope Guru valid.

# 4. Prioritas Kewenangan Bisnis

Jika beberapa role/konteks valid bertemu:

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

Urutan ini bukan pengganti permission table.

Pimpinan tetap readonly pada modul yang hanya memberinya permission view walaupun berada di atas Guru/Wali pada hierarchy.

---

# 5. Wali Kelas

Wali adalah konteks dinamis bagi user Guru.

Validasi:

```text
users.id_guru
→ mapping_wali_kelas
→ id_tahun = tahun aktif
→ deleted_at IS NULL
```

Jika mapping dinonaktifkan, seluruh hak `KELAS_DIAMPU` hilang segera.

Hak histori Presensi mengikuti **Wali aktif saat ini**, bukan actor input awal.

Wali baru dapat merevisi histori Presensi kelas pada tahun aktif bila permission revisi valid. Wali lama kehilangan akses setelah mapping nonaktif.

---

# 6. Multi-Scope role_permissions

Satu role dapat mempunyai lebih dari satu scope pada permission yang sama.

Unique canonical:

```text
UNIQUE(role, id_permission, scope)
```

Bukan:

```text
UNIQUE(role, id_permission)
```

Contoh wajib:

```text
guru | presensi_siswa.input | KELAS_TERJADWAL
guru | presensi_siswa.input | KELAS_DIAMPU
```

`AuthService::resolveScope()` digunakan sebagai scalar gate untuk route/menu dan harus:

1. pilih `SEMUA` bila tersedia;
2. pilih `KELAS_DIAMPU` hanya bila user benar-benar Wali aktif;
3. bila bukan Wali tetapi ada `KELAS_TERJADWAL`, pilih `KELAS_TERJADWAL`;
4. berikutnya `DIRI_SENDIRI`;
5. selain itu `TIDAK_ADA`.

Service Presensi yang membutuhkan dual-context harus membaca seluruh scope, bukan hanya scalar scope.

---

# 7. Scope Canonical

## 7.1 SEMUA

Akses seluruh data modul sesuai permission.

## 7.2 KELAS_DIAMPU

Hanya kelas Wali aktif.

Sumber:

```text
mapping_wali_kelas
```

## 7.3 KELAS_TERJADWAL

Hanya kelas dari `jadwal_guru` aktif milik Guru. Service dapat mempersempit dengan hari, sesi, tanggal dan time-window.

## 7.4 DIRI_SENDIRI

Hanya identitas user sendiri (`id_guru` atau `id_siswa` sesuai modul).

## 7.5 TIDAK_ADA

Tidak ada akses. Service tidak boleh meneruskan query tanpa scope.

---

# 8. Permission Canonical

| ID | Permission | Scope didukung |
|---:|---|---|
| 1 | `dashboard.view` | Otomatis |
| 2 | `presensi_siswa.input` | SEMUA,KELAS_DIAMPU,KELAS_TERJADWAL |
| 3 | `presensi_siswa.revisi` | SEMUA,KELAS_DIAMPU |
| 4 | `presensi_siswa.view` | SEMUA,KELAS_DIAMPU,KELAS_TERJADWAL,DIRI_SENDIRI |
| 5 | `presensi_mengajar.input` | SEMUA,KELAS_TERJADWAL,DIRI_SENDIRI |
| 6 | `presensi_mengajar.view` | SEMUA,DIRI_SENDIRI |
| 7 | `master_guru.manage` | SEMUA |
| 8 | `master_guru.view` | SEMUA |
| 9 | `master_pegawai.manage` | SEMUA |
| 10 | `master_pegawai.view` | SEMUA |
| 11 | `master_siswa.view` | SEMUA,KELAS_DIAMPU,DIRI_SENDIRI |
| 12 | `master_siswa.edit_biodata` | SEMUA,KELAS_DIAMPU |
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
| 24 | `laporan_matrix.view` | SEMUA,KELAS_DIAMPU |
| 25 | `laporan_export.generate` | SEMUA,KELAS_DIAMPU |
| 26 | `laporan_jurnal.view` | SEMUA,DIRI_SENDIRI |
| 27 | `laporan_jurnal.export` | SEMUA,DIRI_SENDIRI |
| 28 | `ews_radar.view` | SEMUA,KELAS_DIAMPU |
| 29 | `bk_kasus.manage` | SEMUA |
| 30 | `bk_kasus.view` | SEMUA,KELAS_DIAMPU,DIRI_SENDIRI |
| 31 | `bk_pelanggaran_master.manage` | SEMUA |
| 32 | `prestasi.manage` | SEMUA |
| 33 | `prestasi.view` | SEMUA,KELAS_DIAMPU,DIRI_SENDIRI |
| 34 | `kartu_pelajar.manage` | SEMUA,KELAS_DIAMPU |
| 35 | `kartu_pelajar.view` | SEMUA,KELAS_DIAMPU,DIRI_SENDIRI |
| 36 | `settings_user.manage` | SEMUA |
| 37 | `settings_menu.manage` | SEMUA |
| 38 | `settings_sistem.manage` | SEMUA |
| 39 | `backup.manage` | SEMUA |
| 40 | `log_activity.view` | SEMUA |
| 41 | `profile_guru.view` | DIRI_SENDIRI |
| 42 | `profile_guru.edit` | DIRI_SENDIRI |
| 43 | `profile_siswa.view` | DIRI_SENDIRI |

---

# 9. Hak Presensi per Context

## Admin

```text
Presensi Siswa input/view/revisi = SEMUA
Jurnal input/view/revisi = SEMUA
Geofence/time-window tidak membatasi aksi administratif.
```

## Operator

Sama seperti Admin untuk modul Presensi yang diberikan permission.

## Pimpinan

```text
Presensi Siswa view = SEMUA readonly
EWS/Matrix/Laporan = SEMUA readonly
Jurnal view = SEMUA readonly
Jurnal input = DIRI_SENDIRI hanya bila mempunyai identitas Guru + jadwal valid
```

Pimpinan tidak mendapat input/revisi Presensi Siswa.

## BK

```text
EWS = SEMUA
Presensi Siswa mutation = tidak
```

## Guru biasa

```text
presensi_siswa.input = KELAS_TERJADWAL
presensi_mengajar.input/view = DIRI_SENDIRI
```

## Wali

Tambahan contextual:

```text
presensi_siswa.input   = KELAS_DIAMPU
presensi_siswa.view    = KELAS_DIAMPU
presensi_siswa.revisi  = KELAS_DIAMPU
laporan_matrix.view    = KELAS_DIAMPU
laporan_export.generate= KELAS_DIAMPU
ews_radar.view        = KELAS_DIAMPU
master_siswa.view      = KELAS_DIAMPU
master_siswa.edit_biodata = KELAS_DIAMPU
bk_kasus.view          = KELAS_DIAMPU
prestasi.view          = KELAS_DIAMPU
kartu_pelajar.view     = KELAS_DIAMPU
```

## Siswa

```text
presensi_siswa.view = DIRI_SENDIRI
bk_kasus.view       = DIRI_SENDIRI
prestasi.view       = DIRI_SENDIRI
kartu_pelajar.view  = DIRI_SENDIRI
profile_siswa.view  = DIRI_SENDIRI
```

Siswa tidak mempunyai permission mutation untuk modul tersebut.

---

# 10. Dual-Context Guru + Wali pada Presensi Siswa

Untuk target kelas yang dia wali sekaligus dia ajar:

1. bila Jadwal aktif target sesi ada dan waktu masih valid → `GURU_TERJADWAL`, time-window/geofence berlaku;
2. bila waktu Jadwal telah selesai → boleh fallback `WALI` tanpa time-window/geofence;
3. sebelum Jadwal mulai → tidak boleh memakai Wali untuk bypass kewajiban terjadwal;
4. kelas lain yang hanya dia ajar → tidak mendapat fallback Wali;
5. tanggal lampau → Wali aktif dapat memakai konteks Wali pada tahun aktif.

Service Presensi adalah authoritative data-level authorization.

---

# 11. Menu

Menu berasal dari:

```text
menus
+
role_menus
+
permission
+
contextual Wali
```

Menu tidak boleh menjadi security boundary.

`MenuService` wajib memfilter child menu berdasarkan permission route tujuan. Parent group kosong harus dipangkas.

Menu Presensi dipisahkan:

```text
Presensi Siswa      → input, permission presensi_siswa.input
Presensi Mengajar   → input Jurnal, permission presensi_mengajar.input
Rekap Presensi      → readonly/view, permission presensi_siswa.view
EWS Radar           → readonly/view, permission ews_radar.view
```

Dengan pemisahan ini Pimpinan/BK/Siswa tidak diarahkan ke endpoint mutation hanya untuk melihat data.

---

# 12. Menu Contextual Wali

Role `guru` boleh diberi mapping menu statis untuk:

```text
Data Siswa
Rekap Presensi
EWS
Matrix
Export
Catatan Kasus
Prestasi
Kartu Pelajar
```

Tetapi menu tersebut hanya muncul bila `AuthService::resolveScope()` menghasilkan akses valid. Guru biasa tanpa mapping Wali tidak boleh melihat menu contextual tersebut.

---

# 13. Route Gate

Route protected memakai:

```text
auth
permission:<permission_key>
```

PermissionFilter hanya route gate.

Data-level authorization tetap wajib di Service.

Dilarang:

```text
if role == admin then bypass all
```

Gunakan permission/scope.

---

# 14. Dashboard Multi-Role

Dashboard tidak boleh menentukan konteks hanya dari `session('role')`.

Gunakan effective roles dengan priority:

```text
admin > operator > pimpinan > guru > bk > siswa
```

Jika effective dashboard role `guru`, status Wali dihitung dinamis. Dashboard Wali = Dashboard Guru + contextual widget.

Setiap widget tambahan tetap tunduk pada permission sumber.

---

# 15. Security

- server tidak percaya role/scope/id_guru/id_siswa dari browser;
- seluruh mutation CSRF protected;
- user identity berasal dari session/token;
- data target divalidasi ulang di Service;
- query scoped di database;
- menu tersembunyi bukan pengganti authorization;
- user multi-role tidak boleh kehilangan permission dari role lain;
- role NULL untuk Pegawai valid.

---

# 16. Checkpoint Final

Wajib diuji:

```text
Admin
Operator
Pimpinan
BK
Guru biasa
Guru + Wali
Siswa
Guru + Operator (multi-role)
Guru + Pimpinan (multi-role)
```

Checklist:

- effective roles benar;
- ordinary Guru tetap lolos `KELAS_TERJADWAL` walau role Guru juga memiliki `KELAS_DIAMPU`;
- Wali hanya memperoleh `KELAS_DIAMPU` saat mapping aktif;
- Pimpinan tidak mendapat mutation Presensi Siswa;
- Siswa hanya melihat data diri;
- Siswa dapat melihat Catatan Kasus diri;
- menu yang tampil mempunyai route/permission usable;
- direct URL tetap ditolak bila scope tidak valid.
