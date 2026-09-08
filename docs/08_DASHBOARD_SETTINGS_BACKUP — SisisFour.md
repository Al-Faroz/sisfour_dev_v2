# Dashboard, Settings, Maintenance, Backup & Log Activity — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# A. Dashboard

## 1. Prinsip

Dashboard bukan security boundary.

Effective dashboard priority:

```text
admin > operator > pimpinan > guru > bk > siswa
```

Wali = Dashboard Guru + contextual widget.

## 2. Admin

Ringkasan utama:

- Master Data;
- Presensi hari ini;
- Jurnal;
- EWS;
- BK/Prestasi;
- Kartu;
- geofence;
- maintenance;
- tren;
- aktivitas terakhir.

## 3. Operator

Fokus operasional. Tidak mempunyai Settings/Backup pada baseline permission.

Mempunyai `log_activity.view`.

## 4. Pimpinan

Supervisi readonly. Input Jurnal diri hanya jika identity/jadwal/permission valid.

## 5. Guru/Wali

Guru berorientasi tugas Jadwal hari ini. Wali mendapat tambahan data kelas Wali sesuai scope.

## 6. BK

Fokus EWS, Kasus, Pelanggaran, Prestasi.

## 7. Siswa

Data diri: Presensi, Kasus, Prestasi, Kartu, dan Profile bila implementasi tersedia.

## 8. Query

Database-first. Top list bounded. EWS 14 hari.

# B. Settings

## 9. Permission

```text
settings_user.manage
settings_menu.manage
settings_sistem.manage
```

Baseline: Admin.

## 10. User Management

Mendukung:

- create/update user;
- aktif/nonaktif;
- reset password;
- primary role;
- secondary roles;
- relasi Guru/Pegawai/Siswa;
- auth_version.

Role NULL valid bagi Pegawai.

## 11. Menu & Role

Admin mengelola `role_menus`.

Menu bukan authorization boundary.

## 12. Setting Sistem

Key baseline:

```text
geofencing_aktif
latitude_sekolah
longitude_sekolah
radius_geofencing
maintenance_mode
maintenance_message
logo_sekolah
icon_sekolah
background_kta_depan
background_kta_belakang
nama_sekolah
alamat_sekolah
```

Branding upload:

```text
uploads/settings/branding/
```

KTA upload:

```text
uploads/settings/kartu/
```

Logo login/sidebar = `logo_sekolah`.

Favicon = `icon_sekolah`.

# C. Login & Footer

## 13. Login

```text
SisFour Dev
{nama_sekolah}
```

Logo berasal dari hasil upload Setting Sistem.

Pesan:

```text
Aktifkan lokasi di perangkat saat menggunakan aplikasi
```

Responsive desktop/mobile.

## 14. Footer

```text
© {tahun} SisisFour · {nama_sekolah}
By : LemahTeles
```

# D. Maintenance

## 15. Behavior

Maintenance ON:

```text
Admin effective  → tetap dapat login/akses
non-Admin Web    → HTML 503
non-Admin API    → JSON 503
```

Login page dan logout mempunyai exception exact route.

POST login hanya dilewatkan bila username adalah effective Admin aktif; password tetap diverifikasi AuthService.

Header:

```text
Retry-After: 300
Cache-Control: no-store, no-cache, must-revalidate
```

# E. Backup

## 16. Permission

```text
backup.manage
```

Baseline: Admin.

## 17. Implementasi

Pure PHP:

- tanpa exec/shell/system/passthru;
- `SHOW CREATE TABLE`;
- INSERT data;
- consistent snapshot;
- `.part`;
- atomic rename.

Lokasi:

```text
writable/backups/
```

Filename:

```text
backup_YYYYMMDD_HHMMSS.sql
```

Download/delete memakai whitelist + realpath validation.

# F. Log Activity

## 18. Schema

```text
id
id_user
aksi
modul
keterangan
waktu
```

Permission:

```text
log_activity.view
```

Baseline: Admin + Operator.

## 19. Viewer

```text
/log/activity
/log/activity/json
/log/activity/export
```

Fitur:

- DB-side filter/search;
- pagination;
- filter modul/aksi/tanggal;
- CSV UTF-8 BOM;
- max 10.000 row export.

## 20. Producer

`ActivityLogService` menyediakan writer umum.

Auth mencatat:

```text
LOGIN Web
LOGOUT Web
LOGIN API
LOGOUT API
```

Backup mencatat create/download/delete.

Log tidak menyimpan password/hash/token/cookie/session id.

## 21. Menu Operator

Operator authorized untuk `/log/activity` tetapi sidebar baseline tidak menampilkan menu tersebut karena `role_menus` tidak memasukkannya. Ini adalah item Polish, bukan kegagalan permission.

# G. Checkpoint

- dashboard seluruh role;
- dynamic branding;
- responsive login/footer;
- maintenance;
- backup;
- Log Activity;
- menu Operator diputuskan.
