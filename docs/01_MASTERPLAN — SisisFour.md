# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`


> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.

## 1. Identitas Sistem

**SisisFour** adalah Sistem Informasi Manajemen Madrasah MTsN 4 Jombang yang mengintegrasikan administrasi akademik, presensi, monitoring, BK, kartu pelajar, personalia, dan pelaporan.

Target production:

```text
https://sisfour.mtsn4jombang.sch.id/
```

## 2. Skala Operasional

Kapasitas desain:

```text
Siswa       1.600+
Guru/BK     140+
Rombel      50+
Presensi    2 sesi per siswa per hari
```

Snapshot audit release:

```text
Siswa aktif        1.493
Kelas                 53
Guru                  115
Jadwal              1.253
Kartu aktif         1.493
Akun user           1.610
Permission             43
```

Angka snapshot bukan constraint bisnis; sistem tetap dirancang untuk skala di atasnya.

## 3. Modul Utama

- Auth Web dan API/JWT.
- RBAC dan menu dinamis.
- Dashboard per experience role.
- Master Guru/Pegawai/Siswa/Kelas/Tahun/Mapel.
- Mapping Wali Kelas dan Jadwal Guru.
- Manajemen Siswa: penempatan, kenaikan, mutasi, kelulusan.
- Presensi Siswa dan Presensi Mengajar/Jurnal.
- Matrix dan export laporan.
- EWS internal dan Digital Signage.
- BK: pelanggaran, kasus, tindak lanjut.
- Prestasi.
- Kartu Pelajar dan public verify.
- Profile Guru/Pegawai/Siswa.
- Personalia/Portofolio Guru/Pegawai.
- Settings User/Menu/Sistem.
- Maintenance, Backup, Log Activity.
- API core untuk mobile.

## 4. Arsitektur

```text
Client
  -> Route
  -> Global/Route Filters
  -> Controller
  -> Service
  -> Model / Query
  -> Database
```

- Controller menangani request/response.
- Service menentukan authorization data-level dan business rule.
- Model/Query menangani persistence.
- View/JS bukan security boundary.
- PermissionFilter tidak menggantikan scope validation di Service.

## 5. Role dan Experience

Role resmi:

```text
admin
operator
pimpinan
bk
guru
siswa
```

Wali Kelas adalah konteks Guru, bukan role.

Priority experience role UI/dashboard:

```text
admin > operator > pimpinan > bk > guru > siswa
```

BK diprioritaskan sebelum Guru karena akun BK dapat memiliki `id_guru` dan secondary role Guru.

## 6. Fokus UI/UX Aktif

Fokus redesign saat ini dibatasi pada tiga experience:

```text
Guru biasa
Guru + Wali Kelas
Siswa
```

Prinsip:

- UI redesign tidak boleh mengubah authorization/business rule secara diam-diam;
- Wali tetap bukan role baru;
- Dashboard Wali = Dashboard Guru + contextual kelas wali;
- Siswa tetap readonly untuk data diri;
- mockup/render visual adalah artefak desain, **bukan bukti implementasi runtime**;
- perubahan baru dianggap canonical runtime setelah source diubah, diuji, dan diputuskan final.

Kontrak detail ada di `11_UI_UX_GURU_WALAS_SISWA — SisisFour.md`.

## 7. Kontrak Identitas Login

### Guru/Pegawai

```text
identifier = NIP bila tersedia/valid
             jika tidak -> NIK
```

NIK wajib secara business rule untuk create/edit/import baru, tetapi schema tetap nullable untuk kompatibilitas legacy.

Bila akun awalnya login dengan NIK lalu memperoleh NIP:

```text
username -> NIP
password managed/reset -> NIP
auth_version -> increment
```

### Siswa

```text
username default = NISN
password default = NISN
```

## 8. Presensi

Status:

```text
Hadir
Sakit
Izin
Alpha
```

Sesi:

```text
Sesi Awal  -> resmi
Sesi Akhir -> dokumentasi
```

Statistik ketidakhadiran menggunakan S/I/A.

Guru biasa mengisi sesuai jadwal. Wali mengisi kelas wali sesuai contextual authorization. Revisi setelah simpan hanya Wali kelas target, Operator, atau Admin.

## 9. Digital Signage

Route public:

```text
/signage
/signage/data
```

Kontrak:

- sumber hanya Sesi Awal;
- ranking 14 hari;
- Top 20 Alpha, Izin, Sakit;
- Tidak Masuk Hari Ini (S/I/A);
- nama siswa ditampilkan;
- refresh client 5 menit;
- rotasi panel 15 detik;
- cache server 240 detik;
- penempatan internal ruang Guru/TU;
- maksimal sekitar 4 display.

## 10. Kartu Pelajar

- satu kartu Aktif maksimum per siswa;
- canvas 1011×638;
- QR `SISFOUR|V1|...`;
- public verify readonly;
- bulk generate maksimum 200 per request;
- cetak massal maksimum 200;
- A4 2×5 = 10 kartu per halaman;
- background shared/cached per request;
- lifecycle Lulus/Pindah/Keluar menonaktifkan kartu Aktif.

## 11. Personalia

```text
riwayat_pendidikan
riwayat_penugasan
riwayat_pangkat
dokumen_personalia
```

Dokumen mentah non-public. Self-service hanya identity sendiri; Admin/Operator manage dapat membantu target sesuai permission; readonly actor tidak mendapat raw document.

## 12. Deployment

Production:

```text
Hostinger hPanel
manual ZIP upload
project root -> public_html
HTTPS
PHP 8.2+ (direkomendasikan 8.3)
MariaDB/MySQL
.env production terpisah
```

## 13. Roadmap di Luar Baseline Wajib

- penyempurnaan UI/UX Guru–Walas–Siswa sesuai hasil review visual;
- Global Search lintas modul yang lebih luas;
- Notifikasi internal end-to-end;
- Dashboard Alumni penuh;
- APK Cordova final/distribusi;
- Integrasi EMIS/Dapodik.
