# Masterplan — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Identitas

**SisisFour** adalah Sistem Informasi Manajemen Madrasah untuk MTsN 4 Jombang.

Nama produk pada halaman login:

```text
SisFour Dev
```

Branding programmer pada footer:

```text
By : LemahTeles
```

# 2. Tujuan

SisisFour mengintegrasikan:

- Authentication Web;
- JWT/API;
- RBAC;
- Dashboard;
- Master Guru;
- Master Pegawai;
- Master Siswa;
- Kelas;
- Tahun Ajaran;
- Mata Pelajaran;
- Mapping Wali;
- Jadwal Guru;
- Presensi Siswa;
- Presensi Mengajar/Jurnal;
- Laporan;
- EWS;
- BK;
- Prestasi;
- Kartu Pelajar;
- Profile;
- Settings;
- Maintenance;
- Backup;
- Log Activity.

# 3. Arsitektur

```text
Browser / Mobile
       ↓
Routes
       ↓
MaintenanceFilter
       ↓
AuthFilter
       ↓
PermissionFilter
       ↓
Controller
       ↓
Service
       ↓
Model / Query Builder
       ↓
MariaDB/MySQL
```

`PermissionFilter` hanya route gate. Service tetap authoritative untuk target data.

# 4. Web

Web menggunakan:

- session database;
- layout `app/Views/main.php`;
- CSRF;
- Fetch API;
- HTML/JSON sesuai endpoint.

# 5. API

API menggunakan:

- access token;
- refresh token;
- `api_tokens`;
- auth_version;
- endpoint prefix `/api`.

# 6. Role

```text
admin
operator
pimpinan
bk
guru
siswa
```

Wali adalah konteks dinamis dari Mapping Wali Kelas.

# 7. Modul dan Status Baseline

| Modul | Status |
|---|---|
| Auth Web | Tersedia |
| API/JWT | Tersedia |
| RBAC/Menu | Tersedia |
| Dashboard | Tersedia |
| Guru | Tersedia |
| Pegawai | Tersedia |
| Siswa | Tersedia |
| Kelas | Tersedia |
| Tahun Ajaran | Tersedia |
| Mata Pelajaran | Tersedia |
| Mapping Wali | Tersedia |
| Jadwal Guru | Tersedia |
| Presensi Siswa | Tersedia |
| Presensi Mengajar/Jurnal | Tersedia |
| Laporan | Tersedia |
| EWS | Tersedia |
| BK/Pelanggaran | Tersedia |
| Prestasi | Tersedia |
| Kartu Pelajar | Tersedia |
| Settings | Tersedia |
| Maintenance | Tersedia |
| Backup | Tersedia |
| Log Activity | Tersedia |
| Profile | **Route/permission tersedia; Controller runtime tidak ditemukan** |

# 8. Login

Halaman login canonical:

- logo dari `setting_sistem.logo_sekolah`;
- favicon dari `setting_sistem.icon_sekolah`;
- nama sekolah dari `setting_sistem.nama_sekolah`;
- judul `SisFour Dev`;
- responsive desktop/laptop/mobile;
- fallback ikon gedung bila logo belum tersedia;
- pesan:

```text
Aktifkan lokasi di perangkat saat menggunakan aplikasi
```

# 9. Footer

Setelah login:

```text
© {tahun} SisisFour · {nama_sekolah}
By : LemahTeles
```

Nama sekolah berasal dari Setting Sistem.

# 10. Master Data

Sumber utama:

```text
guru
pegawai
siswa
kelas
tahun_ajaran
mata_pelajaran
anggota_kelas
riwayat_siswa
mapping_wali_kelas
jadwal_guru
```

# 11. Presensi

Presensi Siswa:

```text
Sesi Awal = resmi
Sesi Akhir = dokumentasi
```

Jurnal mendukung:

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

# 12. EWS

Canonical:

```text
Alpha
Sesi Awal
>= 3
14 tanggal inklusif
```

# 13. Kartu Pelajar

- canvas 1011×638;
- depan dinamis;
- belakang statis;
- satu kartu Aktif maksimum per siswa;
- QR format `SISFOUR|V1|...`;
- verification public minimal;
- kartu otomatis Nonaktif saat Lulus/Pindah/Keluar.

# 14. Settings

Key runtime:

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

# 15. Backup

```text
writable/backups/
backup_YYYYMMDD_HHMMSS.sql
```

Pure PHP, tanpa shell executable.

# 16. Log Activity

Audit store:

```text
log_activity
```

Viewer mendukung filter, pagination, search, dan export CSV.

# 17. Skala Data

Presensi diperlakukan sebagai dataset besar.

Prinsip:

- database-first;
- query bounded;
- index-aware;
- server-side pagination;
- tidak ada N+1.

# 18. Di Luar Lingkup v0.5

- keuangan;
- payroll;
- LMS lengkap;
- integrasi langsung EMIS/Dapodik;
- offline-first sync;
- push notification wajib;
- partitioning/sharding awal.

# 19. Kriteria Rilis

Rilis hanya dapat dilakukan setelah:

1. seluruh blocker Testing & Polish selesai;
2. Profile route dan runtime diselaraskan;
3. RBAC seluruh role lulus;
4. CSRF lulus;
5. backup lulus;
6. maintenance recovery Admin lulus;
7. security test kritis lulus;
8. tidak ada route menuju Controller yang hilang.
