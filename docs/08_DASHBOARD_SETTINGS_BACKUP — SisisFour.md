# 📊 Dashboard, Settings, Backup & Log Activity — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan Dashboard per konteks user, Settings, Maintenance Mode, Backup, dan Log Activity.

---

# BAGIAN A — DASHBOARD

## 1. Prinsip

Dashboard adalah agregasi data, bukan security boundary.

Setiap widget wajib:

- mempunyai permission sumber;
- menerapkan scope server-side;
- tidak mengirim dataset global untuk difilter frontend;
- tidak menyediakan bypass ke route yang tidak boleh diakses user;
- memiliki fallback bila data kosong.

Dashboard dapat berbeda berdasarkan effective roles + contextual Wali.

---

# 2. Permission Dashboard

Permission dasar:

```text
dashboard.view
```

Permission widget tambahan tetap mengikuti modul sumber.

Contoh:

```text
EWS → ews_radar.view
BK → bk_kasus.view
Prestasi → prestasi.view
Kartu → kartu_pelajar.view
```

Memiliki `dashboard.view` tidak otomatis memberi akses seluruh widget.

---

# 3. Dashboard Admin

Widget utama:

```text
Total Siswa Aktif
Total Guru
Total Pegawai
Total Kelas
Tahun Ajaran Aktif
Presensi Hari Ini
Kelas Belum Presensi
Guru Belum Jurnal
EWS Radar
Kasus BK
Prestasi
Status Kartu
Aktivitas Terakhir
Status Sistem
```

Scope:

```text
SEMUA
```

Admin bootstrap tetap dapat menggunakan dashboard walaupun tidak terkait Guru/Pegawai.

---

# 4. Dashboard Operator

Dashboard operasional:

- total siswa;
- total Guru/Pegawai;
- total kelas;
- Presensi hari ini;
- kelas belum Presensi;
- Guru belum Jurnal;
- EWS;
- BK;
- Prestasi;
- status Kartu;
- aktivitas terbaru sesuai akses.

Operator tidak otomatis memperoleh Settings/Backup bila permission tidak diberikan.

---

# 5. Dashboard Pimpinan

Readonly.

Widget:

1. kelas belum Presensi;
2. EWS Radar;
3. kasus BK bulan ini;
4. tren Presensi;
5. Guru belum input Jurnal;
6. Top 20 poin pelanggaran;
7. Prestasi terbaru;
8. ringkasan Kartu bila permission tersedia;
9. ringkasan Master Data.

Tidak boleh ada create/update/delete dari Dashboard Pimpinan kecuali fitur khusus yang permission-nya memang mengizinkan.

---

# 6. Dashboard BK

Widget:

```text
Kasus bulan ini
Top 20 poin pelanggaran
EWS Radar
Prestasi terbaru
```

BK hanya dapat membuka detail sesuai permission BK/Prestasi.

BK tidak memperoleh Master Siswa global.

---

# 7. Dashboard Guru Biasa

Widget:

```text
Jadwal hari ini
Presensi Siswa yang dapat diinput
Jurnal yang harus diisi
Status Jurnal diri
Profile
```

Tidak mendapatkan shortcut ke:

```text
Matrix Presensi
Export Presensi
Master Siswa
BK global
Prestasi global
Master Data admin
```

---

# 8. Dashboard Wali

Wali = Dashboard Guru + contextual widget:

```text
Kelas Wali
Jumlah Siswa
Ringkasan H/S/I/A
EWS kelas
Data Siswa kelas
Matrix kelas
Export kelas
Kartu kelas
Prestasi kelas
BK detail kelas readonly
```

Semua tambahan hanya jika permission + scope tersedia.

Jika mapping Wali dinonaktifkan, widget tersebut hilang.

---

# 9. Dashboard Siswa

Sederhana.

## 9.1 Presensi Diri

Ringkasan:

```text
Hadir
Sakit
Izin
Alpha
```

Detail menampilkan:

```text
Sakit
Izin
Alpha
```

Query:

```text
id_siswa = session('id_siswa')
```

Tidak menerima target siswa bebas dari query string.

## 9.2 Prestasi

Hanya milik sendiri.

## 9.3 Kartu

Hanya milik sendiri.

---

# 10. Kelas Belum Presensi

Untuk tanggal hari ini, widget memeriksa kelas yang seharusnya memiliki kewajiban Presensi Sesi Awal berdasarkan Jadwal aktif.

Jangan sekadar membandingkan seluruh kelas master dengan tabel Presensi.

Kelas yang tidak mempunyai kewajiban Sesi Awal pada hari itu tidak boleh otomatis dianggap belum Presensi.

---

# 11. Guru Belum Jurnal

Sumber:

```text
jadwal_guru Aktif pada hari ini
LEFT JOIN presensi_mengajar tanggal hari ini
```

Termasuk:

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

Karena semua Jadwal aktif wajib Jurnal.

---

# 12. Tren Presensi

Gunakan:

```text
Sesi Awal
```

Sesi Akhir tidak masuk tren resmi.

---

# BAGIAN B — SETTINGS

# 13. Hak Akses

Settings utama hanya:

```text
Admin
```

Permission:

```text
settings_user.manage
settings_menu.manage
settings_sistem.manage
```

Role lain tidak memperoleh menu Settings kecuali kebijakan permission diubah secara eksplisit.

---

# 14. Manajemen User

Fungsi:

```text
Create User administratif
Update User
Aktif/Nonaktif
Reset Password
Set Primary Role
Tambah/Hapus Secondary Role
Relasi Guru/Pegawai/Siswa
Naikkan auth_version bila diperlukan
```

---

# 15. Identitas User

User normal:

- Guru → `id_guru`;
- Siswa → `id_siswa`;
- Pegawai administratif → `id_pegawai` atau identitas valid;
- multi-role tetap mempertahankan satu account.

Admin bootstrap boleh tanpa identity relation.

Jangan membuat duplicate account hanya untuk menambah role.

---

# 16. Reset Password

Reset password:

- hanya melalui permission Settings;
- menggunakan password hash;
- tidak menampilkan hash;
- dapat menaikkan `auth_version`;
- sebaiknya memaksa session lama invalid.

Default reset mengikuti kebijakan identitas:

```text
Guru/Pegawai → NIP
Siswa        → NISN
```

atau password manual yang aman bila UI mendukung.

---

# 17. Role dan Multi-Role

Admin dapat mengelola:

```text
users.role
user_roles
```

Aturan:

- role resmi hanya 6;
- Wali tidak boleh dibuat sebagai role;
- role NULL valid untuk Pegawai;
- duplicate role user ditolak oleh unique index.

---

# 18. Menu & Role

Admin mengelola:

```text
role_menus
```

Tetapi menu tidak boleh menjadi authorization boundary.

Menu harus usable:

```text
role_menus
+
permission
+
scope
+
contextual Wali
```

Jika menu diberi ke role tetapi role tidak memiliki permission yang membuatnya usable, konfigurasi dianggap tidak konsisten.

---

# 19. Setting Sistem

Tabel:

```text
setting_sistem
```

Key minimum:

| Key | Tipe | Fungsi |
|---|---|---|
| `latitude_sekolah` | float/string | Latitude |
| `longitude_sekolah` | float/string | Longitude |
| `radius_geofencing` | integer | Radius meter |
| `geofencing_aktif` | boolean | ON/OFF |
| `nama_sekolah` | string | Nama |
| `alamat_sekolah` | string/text | Alamat |
| `logo_sekolah` | string | File logo |
| `icon_sekolah` | string | Icon |
| `background_kta_depan` | string | Kartu depan |
| `background_kta_belakang` | string | Kartu belakang |
| `maintenance_mode` | boolean | Maintenance |
| `maintenance_message` | text | Pesan |

Gunakan nama key ini secara konsisten lintas docs/Service. Jangan memiliki dua key berbeda untuk koordinat yang sama.

---

# 20. Validasi Setting

### Latitude

```text
-90..90
```

### Longitude

```text
-180..180
```

### Radius

```text
> 0
```

### Asset branding

- image valid;
- re-encode bila di-upload;
- path aman;
- tidak executable.

---

# 21. Caching Setting

Setting yang sering dibaca boleh di-cache.

Perubahan Settings harus:
- update DB;
- clear cache relevan;
- log activity.

Cache tidak boleh membuat Maintenance/Geofence tetap memakai nilai lama terlalu lama.

---

# BAGIAN C — MAINTENANCE

# 22. Behavior

Saat:

```text
maintenance_mode = ON
```

Admin:

```text
tetap dapat login dan masuk
```

Role lain:

```text
halaman maintenance
```

API Mobile dapat mengembalikan status:

```text
503 Service Unavailable
```

dengan pesan aman.

---

# 23. MaintenanceFilter

Filter memeriksa setting sebelum Controller bisnis.

Pengecualian:
- login Admin;
- logout;
- asset statis;
- endpoint yang memang perlu agar Admin mematikan maintenance.

Jangan membuat pengecualian wildcard terlalu luas.

---

# BAGIAN D — BACKUP

# 24. Hak Akses

Backup hanya:

```text
Admin
```

Permission:

```text
backup.manage
```

Operator tidak memperoleh Backup secara default.

---

# 25. Metode

Backup menggunakan PHP murni.

Tidak bergantung:

```text
exec()
shell_exec()
system()
passthru()
```

karena hosting dapat menonaktifkannya.

---

# 26. Isi Backup

Backup SQL harus mencakup:

- CREATE TABLE;
- data tabel;
- index;
- foreign key;
- generated column;
- AUTO_INCREMENT;
- charset/collation seperlunya.

Untuk restore penuh, urutan FK harus aman.

---

# 27. Nama File

```text
backup_YYYYMMDD_HHMMSS.sql
```

Lokasi:

```text
writable/backups/
```

Tidak public.

---

# 28. Download Backup

Download harus melalui Controller yang:

- Auth;
- `backup.manage`;
- validasi filename whitelist;
- tidak menerima arbitrary path.

Dilarang:

```text
/download?file=../../.env
```

---

# 29. Retention

Dapat diterapkan:

```text
maksimum jumlah file
atau
maksimum umur
```

Jika retention aktif, penghapusan file lama harus dicatat log.

---

# 30. Restore

Restore otomatis dari UI adalah aksi berisiko tinggi.

Jika disediakan:

- Admin only;
- konfirmasi eksplisit;
- backup sebelum restore disarankan;
- validasi file;
- transaction bila engine/statement memungkinkan;
- maintenance mode selama restore disarankan.

Jika belum diimplementasikan, modul Backup v0.5 cukup menjamin create/list/download/delete file backup secara aman.

---

# BAGIAN E — LOG ACTIVITY

# 31. Schema

Gunakan schema canonical:

```text
id
id_user
aksi
modul
keterangan
waktu
```

Jangan menggunakan schema alternatif:

```text
role
ip_address
activity
details
created_at
```

kecuali database resmi diperbarui lebih dulu.

---

# 32. Hak Akses Log

Permission:

```text
log_activity.view
```

Kebijakan:

```text
Admin    → Ya
Operator → Ya
Pimpinan → Tidak
BK       → Tidak
Guru     → Tidak
Wali     → Tidak
Siswa    → Tidak
```

---

# 33. Event yang Dicatat

Minimal:

```text
Login/Logout
Perubahan User
Reset Password
Perubahan Role
Perubahan Menu
Perubahan Master Data
Mutasi/Kenaikan/Kelulusan
Perubahan Presensi
Revisi Jurnal
Export
BK/Prestasi
Generate/Reissue/Cetak Kartu
Perubahan Settings
Backup
Maintenance
```

---

# 34. Isi Keterangan

Tidak boleh memasukkan:

- password;
- hash;
- token;
- cookie;
- koordinat pribadi pengguna bila tidak diperlukan.

Contoh:

```text
aksi       = UPDATE
modul      = Master Tahun Ajaran
keterangan = Mengaktifkan 2026/2027 - Ganjil
```

---

# 35. Retention Log

Retention dapat ditentukan kemudian berdasarkan kapasitas database.

Jangan hard delete log penting tanpa kebijakan.

---

# BAGIAN F — SERVICE

# 36. Service yang Disarankan

```text
DashboardService
SettingsService
UserManagementService
BackupService
LogActivityService
```

`DashboardService` hanya agregasi; permission/scope tetap berasal dari AuthService.

---

# BAGIAN G — PERFORMANCE

# 37. Dashboard

Widget berat dapat:
- cache pendek;
- query agregat;
- batasi Top 20;
- hindari N+1.

Data near-real-time seperti Presensi hari ini dapat refresh berkala.

Jangan polling lebih cepat dari kebutuhan.

---

# BAGIAN H — SECURITY

# 38. Settings

- CSRF mutation;
- Admin only;
- validate key;
- jangan menerima arbitrary setting key bila Service memakai whitelist.

## 39. Backup

- path traversal protection;
- file extension `.sql`;
- directory di luar public;
- no shell;
- permission check setiap download/delete.

## 40. Log

- readonly;
- filter server-side;
- escape keterangan;
- jangan render HTML dari log.

---

# BAGIAN I — CHECKPOINT

# 41. Dashboard

- Admin;
- Operator;
- Pimpinan;
- BK;
- Guru;
- Wali;
- Siswa;
- Wali contextual berubah otomatis;
- widget permission;
- no leak.

# 42. Settings

- user;
- role;
- multi-role;
- role NULL;
- menu;
- geofence;
- branding;
- maintenance;
- cache clear;
- log.

# 43. Backup

- create;
- file valid;
- download protected;
- delete protected;
- no public access;
- no shell dependency.

# 44. Log

- schema benar;
- Admin/Operator only;
- event penting tercatat;
- filter bekerja;
- no sensitive secrets.

---

# 45. Kriteria Selesai

Modul selesai bila:

1. Dashboard setiap actor hanya menerima widget sesuai scope;
2. Settings hanya Admin;
3. menu tidak menggantikan permission;
4. Maintenance tetap memungkinkan Admin memulihkan sistem;
5. Backup aman dan tidak public;
6. Log memakai schema canonical;
7. tidak ada path traversal;
8. perubahan penting tercatat;
9. seluruh mutation memakai CSRF.
