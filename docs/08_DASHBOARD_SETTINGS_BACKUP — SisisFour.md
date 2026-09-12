# Dashboard, Settings, Maintenance, Backup & Log Activity — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`


> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog**.

## A. Dashboard

### 1. Experience Role

Dashboard memakai effective role dan priority:

```text
admin > operator > pimpinan > bk > guru > siswa
```

BK berada di atas Guru karena akun BK dapat tetap mempunyai identity/secondary role Guru.

Wali Kelas bukan role. Dashboard Wali = Dashboard Guru + contextual data/quick links berdasarkan mapping Wali aktif dan permission yang dimiliki.

### 2. Admin

Fokus ringkasan sistem, master, Presensi, Jurnal, EWS, BK/Prestasi, Kartu, status sistem dan log sesuai permission.

### 3. Operator

Fokus administrasi operasional, master, Presensi, laporan, BK/Kartu sesuai permission. Operator tidak otomatis memperoleh Settings/Backup.

### 4. Pimpinan

Fokus monitoring/supervisi readonly. Mutation hanya tersedia bila permission eksplisit memang diberikan.

### 5. BK

Fokus EWS, Kasus, Pelanggaran, dan Prestasi.

### 6. Guru

Dashboard Guru baseline menampilkan:

```text
Tahun Ajaran aktif
Jadwal hari ini
Task summary Presensi/Jurnal
Riwayat Jurnal terakhir
Akses Profile bila tersedia
```

Action Presensi/Jurnal pada jadwal mengikuti time-window dan status submission server-side.

### 7. Guru + Wali Kelas

Dashboard Wali adalah Dashboard Guru dengan tambahan contextual:

```text
Kelas wali
Jumlah siswa
Presensi Sesi Awal hari ini (H/S/I/A)
EWS kelas bila diizinkan
Ketidakhadiran terbaru
Quick links contextual
```

Quick links dapat mengarah ke Presensi Kelas, Rekap, Data Siswa, Matrix, EWS, Kasus, Prestasi, dan Kartu sesuai permission.

### 8. Siswa

Dashboard Siswa readonly menampilkan data diri:

```text
Tahun Ajaran aktif
Status kehadiran hari ini dari Sesi Awal
Rekap Presensi bulan berjalan
Presensi/ketidakhadiran terbaru
Prestasi
Pelanggaran/Kasus
Kartu
Profile
```

Tidak adanya row Sesi Awal bukan otomatis `Hadir`; UI membedakan data tersedia dan belum tersedia.

### 9. Redesign UI/UX

Fokus redesign aktif adalah Guru, Guru+Wali, dan Siswa. Mockup visual yang dibuat selama diskusi tidak menjadi runtime contract sebelum source diimplementasikan dan lolos regression.

Detail: `11_UI_UX_GURU_WALAS_SISWA — SisisFour.md`.

## B. Settings User

Permission:

```text
settings_user.manage
```

Fitur:

- create/update akun;
- primary role;
- secondary role;
- aktif/nonaktif;
- relasi Guru/Pegawai/Siswa;
- reset password;
- managed credential;
- `auth_version` invalidation ketika state keamanan/kredensial berubah.

Credential Guru/Pegawai yang dikelola Master mengikuti identifier NIP bila ada, selain itu NIK.

UI mutation create/update/reset/delete memiliki busy guard.

## C. Settings Menu

Permission:

```text
settings_menu.manage
```

`role_menus` mengatur visibilitas navigasi. Menu **bukan authorization boundary**; direct URL tetap diperiksa filter dan Service.

Sidebar harus:

- membuang parent kosong;
- hanya mempunyai satu active item paling spesifik;
- membuka ancestor dari active item;
- tidak menampilkan menu yang tidak berguna bagi effective role/context bila desain final sudah menetapkannya.

## D. Settings Sistem

Permission:

```text
settings_sistem.manage
```

Key utama:

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

Upload branding:

```text
uploads/settings/branding/
```

Upload background Kartu:

```text
uploads/settings/kartu/
```

File image divalidasi dan di-reencode dengan GD. Maksimum input 5 MB. Background Kartu dinormalisasi ke 1011×638.

## E. Maintenance

Maintenance adalah global filter.

Saat ON:

```text
Effective Admin -> tetap dapat login dan mengakses Web/AJAX/API yang sah
Non-Admin Web   -> HTTP 503 HTML
Non-Admin AJAX  -> HTTP 503 JSON
Non-Admin API   -> HTTP 503 JSON
```

Login page/logout mempunyai exception yang diperlukan agar recovery tetap mungkin.

## F. Backup

Permission:

```text
backup.manage
```

Lokasi:

```text
writable/backups/
```

Format:

```text
backup_YYYYMMDD_HHMMSS.sql
```

Prinsip:

- pure PHP, tidak mengandalkan shell database executable;
- dump dibuat konsisten;
- temporary `.part` lalu rename atomic;
- download/delete memakai whitelist dan path validation;
- folder harus writable di production.

## G. Log Activity

Tabel:

```text
log_activity
```

Permission:

```text
log_activity.view
```

Log tidak boleh menyimpan password/hash/token/cookie/session id/secret `.env`.
