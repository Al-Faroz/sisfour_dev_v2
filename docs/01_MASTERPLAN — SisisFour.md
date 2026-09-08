# 🏫 Masterplan SisisFour — MTsN 4 Jombang

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

---

# 1. Identitas Sistem

**SisisFour** adalah Sistem Informasi Manajemen Madrasah untuk MTsN 4 Jombang yang mengintegrasikan data master, presensi siswa, jurnal mengajar, laporan, BK, prestasi, kartu pelajar, dashboard, profile, settings, backup, log aktivitas, dan kebutuhan mobile.

Sistem dibangun sebagai aplikasi terpusat dengan satu database utama dan sistem authorization berbasis role + permission + scope.

---

# 2. Tujuan

SisisFour dirancang untuk:

1. menyediakan satu sumber data sekolah;
2. mengurangi input berulang;
3. menjaga histori siswa;
4. mendukung pengelolaan kelas per tahun ajaran;
5. mendukung Presensi Siswa dan Presensi Mengajar;
6. menyediakan laporan operasional;
7. menjaga otorisasi secara granular;
8. mendukung Web dan Mobile;
9. menjaga keamanan data;
10. dapat dipelihara oleh tim administrasi sekolah dengan struktur kode yang sederhana.

---

# 3. Lingkup Sistem

## 3.1 Dalam Lingkup

- Authentication Web;
- JWT Mobile;
- RBAC;
- Dashboard;
- Guru;
- Pegawai;
- Siswa;
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
- Backup;
- Log Activity;
- export Excel/PDF;
- Mobile Cordova.

## 3.2 Di Luar Lingkup

- integrasi Dapodik;
- integrasi EMIS;
- offline-first sync;
- push notification wajib;
- payroll;
- keuangan sekolah;
- LMS lengkap.

---

# 4. Stack Teknis

| Komponen | Teknologi |
|---|---|
| Framework | CodeIgniter 4 |
| PHP | 8.2+ |
| Database | MariaDB/MySQL |
| Local | XAMPP |
| UI | Sneat Free + Bootstrap 5 |
| JavaScript aplikasi | Vanilla JS |
| HTTP frontend | Fetch API |
| Table | DataTables |
| Dialog | SweetAlert2 |
| Dropdown lanjutan | Select2 bila dibutuhkan |
| Chart | ApexCharts/Chart library sesuai modul |
| Excel | PhpSpreadsheet |
| PDF | Dompdf |
| QR | endroid/qr-code |
| Session Web | Database (`ci_sessions`) |
| Mobile Auth | JWT |
| Timezone | Asia/Jakarta |

jQuery boleh tersedia sebagai dependency vendor/template, tetapi business JavaScript SisisFour menggunakan Vanilla JS + Fetch.

---

# 5. Arsitektur Aplikasi

```text
Browser / Mobile
       ↓
Routes
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

## 5.1 Controller

Controller bertanggung jawab untuk:
- membaca request;
- upload file;
- memanggil Service;
- menentukan HTML/JSON;
- menghasilkan download;
- mengembalikan status HTTP.

Controller tidak boleh menjadi lokasi business rule utama.

## 5.2 Service

Service adalah pusat:
- authorization data-level;
- transaction;
- validasi relasi;
- side effect antar tabel;
- import/export;
- status lifecycle;
- histori;
- duplicate prevention.

## 5.3 Model

Model menangani:
- nama tabel;
- primary key;
- allowed fields;
- timestamp;
- soft delete;
- validasi dasar;
- helper query sederhana.

---

# 6. Struktur Folder

```text
sisfour_dev_v2/
├── app/
│   ├── Config/
│   │   ├── App.php
│   │   ├── Database.php
│   │   ├── Filters.php
│   │   ├── Routes.php
│   │   ├── Security.php
│   │   └── Session.php
│   ├── Controllers/
│   ├── Filters/
│   ├── Models/
│   ├── Services/
│   └── Views/
│       ├── main.php
│       ├── _header.php
│       ├── _scripts.php
│       ├── _sidebar.php
│       └── master/
├── assets/
│   ├── css/
│   ├── js/
│   │   ├── csrf-fetch.js
│   │   └── master/
│   ├── img/
│   └── vendor/
├── uploads/
│   ├── foto_guru/
│   ├── foto_siswa/
│   ├── branding/
│   └── kartu_pelajar/
├── writable/
├── vendor/
├── docs/
├── .env
├── .htaccess
└── index.php
```

---

# 7. Arsitektur Pengolahan Data Besar

SisisFour memproyeksikan tabel `presensi` sebagai tabel operasional terbesar.

Dengan sekitar 1.600 siswa dan sekitar 200–220 hari efektif:

```text
Sesi Awal saja:
1.600 × 200–220
≈ 320.000–352.000 row / tahun

Sesi Awal + Sesi Akhir:
≈ 640.000–704.000 row / tahun
```

Dalam beberapa tahun, jumlah row dapat mencapai beberapa juta. Skala ini masih sesuai untuk MariaDB/MySQL selama index dan pola query benar.

Prinsip arsitektur:

```text
MariaDB/MySQL
    ↓
filter / search / join
group / count / sum
sort / limit
    ↓
dataset kecil
    ↓
PHP Service
    ↓
business rule / pivot ringan / format
```

Aturan:

1. filtering dataset besar dilakukan database;
2. searching dilakukan database;
3. sorting dilakukan database;
4. grouping/counting/agregasi dilakukan database;
5. PHP tidak melakukan agregasi utama dari seluruh tabel;
6. halaman histori memakai server-side pagination;
7. query laporan dibatasi periode/scope;
8. query penting harus memiliki index yang sesuai;
9. performa query besar diverifikasi dengan `EXPLAIN`;
10. N+1 query pada loop siswa/tanggal dilarang.

Belum diperlukan partitioning atau sharding untuk skala awal. Optimasi dilakukan bertahap berdasarkan hasil pengukuran nyata.

---

# 8. Authentication

## 7.1 Web

Web menggunakan session database.

Session minimum:

```php
[
    'user_id'      => ...,
    'role'         => ...,
    'username'     => ...,
    'id_guru'      => ...,
    'id_pegawai'   => ...,
    'id_siswa'     => ...,
    'auth_version' => ...,
    'logged_in'    => true,
]
```

## 7.2 Mobile

Mobile menggunakan:
- access token 1 jam;
- refresh token 30 hari;
- token tersimpan di `api_tokens`.

## 7.3 Single Active Session

`users.auth_version` menjadi versi autentikasi.

Login baru meningkatkan `auth_version`. Session/token lama dengan versi lebih rendah tidak lagi valid.

---

# 9. Role

Role resmi:

```text
admin
operator
pimpinan
bk
guru
siswa
```

Tidak ada role `wali_kelas`.

## 8.1 Wali Kelas

Wali Kelas adalah status dinamis.

Sumber:

```text
mapping_wali_kelas
WHERE deleted_at IS NULL
AND id_tahun = tahun aktif
```

Seorang Guru dapat memperoleh akses tambahan scope `KELAS_DIAMPU` tanpa mengubah role.

## 8.2 Multi-Role

Permission user adalah union dari:

```text
users.role
+
user_roles.role
```

`users.role` boleh NULL untuk akun Pegawai yang belum diberi role operasional.

---

# 10. Frontend dan CSRF

## 9.1 Prinsip

- View menghasilkan markup.
- JS menangani interaksi.
- Fetch menangani data.
- SweetAlert menangani feedback.
- Server tetap menjadi sumber validasi final.

## 9.2 CSRF

CSRF aktif untuk Web.

`_header.php` menyediakan metadata token.

`_scripts.php` memuat:

```text
csrf-fetch.js
```

sebelum JS modul.

Wrapper otomatis memasang header CSRF pada request mutasi same-origin.

Route API mobile tidak menggunakan mekanisme session-CSRF Web.

---

# 11. Master Data

Master Data terdiri dari:

1. Guru;
2. Pegawai;
3. Siswa;
4. Kelas;
5. Tahun Ajaran;
6. Mata Pelajaran;
7. Mapping Wali Kelas;
8. Jadwal Guru.

Business rule detail ada di `04_MASTER_DATA`.

---

# 12. Data Siswa dan Histori

Siswa memiliki:
- NIK 16 digit;
- NISN;
- status Aktif/Lulus/Pindah/Keluar.

Kelas per tahun disimpan pada `anggota_kelas`.

Histori perjalanan siswa disimpan di `riwayat_siswa`.

Tidak boleh mengandalkan hanya nilai kelas saat ini tanpa histori.

---

# 13. Tahun Ajaran

Tahun ajaran mencakup:
- nama tahun, contoh `2026/2027`;
- semester `Ganjil/Genap`;
- status aktif.

Hanya satu record boleh aktif pada satu waktu.

Semua modul operasional harus mengetahui `id_tahun` secara eksplisit.

---

# 14. Jadwal

Jadwal Guru adalah sumber:
- kelas terjadwal;
- jam mulai;
- jam selesai;
- sesi;
- kewajiban Presensi;
- validasi overlap.

Jadwal tidak diinput manual satu per satu.

Input resmi melalui import Excel atomic.

---

# 15. Presensi

## 14.1 Presensi Siswa

Status:

```text
Hadir
Sakit
Izin
Alpha
```

Sesi:

```text
Sesi Awal
Sesi Akhir
```

Sesi Awal adalah data resmi laporan.

Sesi Akhir adalah dokumentasi tambahan.

## 14.2 Presensi Mengajar

Status:

```text
Hadir
Izin
Sakit
```

Materi wajib.

Semua sesi Jadwal Guru termasuk `Non Sesi` tetap wajib Jurnal.

---

# 16. Geofencing

Geofencing:
- dihitung server-side;
- rumus Haversine;
- radius default 500 meter;
- koordinat sekolah disimpan di Settings;
- dapat dimatikan global.

Guru mapel wajib geofencing pada aksi tertentu. Admin/Operator/Wali memiliki pengecualian sesuai spesifikasi Presensi.

---

# 17. Soft Delete

Soft delete digunakan pada:

```text
guru
pegawai
siswa
kelas
tahun_ajaran
mapping_wali_kelas
```

Lifecycle umum:

```text
Aktif
  ↓ delete
Recycle Bin
  ↓ restore
Aktif

atau

Recycle Bin
  ↓ force delete
Hapus Permanen
```

Mata Pelajaran menggunakan hard delete.

---

# 18. Import/Export

Import:
- template resmi;
- validasi header;
- validasi semua row;
- stop-on-error;
- transaction;
- rollback total.

Export:
- mengikuti filter aktif;
- format data polos;
- tidak memasukkan data di luar scope.

---

# 19. Upload Image

Foto Guru/Siswa:
- PNG;
- maksimal 2 MB;
- crop 3:4;
- re-encode;
- metadata dibuang;
- lokasi upload terpisah.

---

# 20. Audit

Perubahan penting dicatat ke:

```text
log_activity
```

Kolom:

```text
id_user
aksi
modul
keterangan
waktu
```

Transaksi yang memiliki audit log harus mempertimbangkan konsistensi transaction.

---

# 21. Route dan Permission

Semua route privat:
- melewati AuthFilter;
- memiliki PermissionFilter yang sesuai.

Penyembunyian tombol/menu bukan pengganti authorization server-side.

---

# 22. Output HTML + JSON

Controller modul yang membutuhkan mobile atau AJAX dapat memberikan:

```text
HTML
JSON
```

Format JSON minimum:

```json
{
  "status": "success",
  "message": "Berhasil.",
  "data": {}
}
```

Error menggunakan status HTTP yang relevan.

---

# 23. Deployment

Target produksi minimum:
- PHP 8.2+;
- MariaDB/MySQL;
- HTTPS;
- GD;
- intl;
- mbstring;
- fileinfo;
- zip;
- mysqlnd.

Direkomendasikan:
- RAM ≥ 2 GB;
- `memory_limit` memadai untuk Excel/PDF;
- backup tidak menggunakan shell bila hosting menonaktifkan exec.

---

# 24. Dokumen Modul

| Dokumen | Fokus |
|---|---|
| 00 | Pola pengerjaan |
| 01 | Arsitektur dan masterplan |
| 02 | Database |
| 03 | Auth/RBAC/Menu |
| 04 | Master Data |
| 05 | Presensi |
| 06 | Laporan |
| 07 | BK/Prestasi/Kartu |
| 08 | Dashboard/Settings/Backup |
| 09 | Profile |
| 15 | Testing & Polish |

---

# 25. Kriteria Arsitektur Final

SisisFour dianggap konsisten bila:

1. business logic tidak tersebar di View;
2. permission tidak hardcoded per role di Controller;
3. Wali bukan role;
4. satu siswa satu kelas per tahun;
5. histori siswa tidak hilang;
6. jadwal tidak overlap;
7. Presensi selalu terkait tahun/kelas;
8. Web mutation terlindungi CSRF;
9. import atomic;
10. fresh install database dapat dibangun dari dokumen database;
11. seluruh role dan scope dapat diuji secara deterministik.
