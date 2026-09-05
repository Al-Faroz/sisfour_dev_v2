# ✅ Presensi Siswa &amp; Presensi Mengajar (Jurnal) — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini mengatur dua jenis presensi: **Presensi Siswa** (kehadiran siswa di kelas) dan **Presensi Mengajar** (jurnal mengajar guru). Keduanya memiliki aturan, hak akses, dan mekanisme geofencing yang berbeda. Seluruh modul presensi sudah terintegrasi dengan RBAC dan aturan time-window yang granular.

* * *

## Bagian 1: Presensi Siswa

### 1.1 Status Kehadiran

- Hanya terdapat **4 status**: `Hadir`, `Sakit`, `Izin`, `Alpha`.
- Status **"Terlambat"** dari versi sebelumnya DIHAPUS.

### 1.2 Sesi Awal (AW) vs Sesi Akhir (AK)

- **Sesi Awal (AW):** Merupakan presensi **resmi**. Seluruh laporan (Matrix, Export, EWS) hanya menghitung data dari sesi ini.
- **Sesi Akhir (AK):** Bersifat **dokumentasi/arsip tambahan**. Data ini disimpan, tetapi **tidak pernah** dihitung dalam laporan resmi, matrix, atau EWS.
- **Unique Key:** Tabel `presensi` memiliki UNIQUE `(id_kelas, tanggal, sesi, id_siswa)` — memungkinkan 1 siswa memiliki 2 catatan per hari (AW dan AK).

### 1.3 Hak Akses Presensi Siswa

| Role | Input | Lihat hasil tersimpan | Revisi | Scope | Catatan |
| --- | --- | --- | --- | --- | --- |
| **Admin** | ✅ | ✅ | ✅ | SEMUA | Full |
| **Operator** | ✅ | ✅ | ✅ | SEMUA | Full |
| **Pimpinan** | ❌ | ✅ | ❌ | SEMUA | Read-only |
| **BK** | ❌ | ❌ | ❌ | — | Tidak mengelola presensi siswa |
| **Guru Biasa** | ✅ | ❌ | ❌ | KELAS_TERJADWAL | Hanya input sesuai jadwal aktif; setelah simpan tidak dapat membuka hasil tersimpan |
| **Wali Kelas** | ✅ | ✅ | ✅ | KELAS_DIAMPU | Kelas wali saja; input dan revisi boleh kapan saja; AW dan AK |
| **Siswa** | ❌ | Diri sendiri | ❌ | DIRI_SENDIRI | Dashboard/rincian hanya menampilkan Sakit, Izin, Alpha |

**Aturan final Guru Biasa:**

1. Guru Biasa dapat membuka workflow input hanya untuk kelas yang benar-benar terjadwal pada hari tersebut.
2. Guru hanya memasukkan status siswa; hasil yang sudah tersimpan **tidak dapat dilihat kembali oleh Guru Biasa**.
3. Guru Biasa tidak dapat membuka mode revisi dan tidak dapat mengubah presensi yang sudah tersimpan.
4. Jika terjadi kesalahan, revisi hanya dapat dilakukan oleh **Wali Kelas, Operator, atau Admin**.

**Aturan final Wali Kelas:**

1. Wali dapat input Presensi Siswa untuk kelas walinya **kapan saja**, tidak bergantung time-window.
2. Wali dapat input Sesi Awal dan Sesi Akhir.
3. Wali dapat merevisi presensi yang sudah tersimpan untuk kelas walinya.
4. Saat Wali mengajar kelas lain yang bukan kelas walinya, ia bertindak sebagai **Guru Biasa** dan tunduk pada aturan jadwal Guru Biasa.

### 1.4 Tampilan Input — Tombol Status Solid (Default Hadir)

- Halaman menampilkan **semua siswa** dalam satu kelas (tanpa pagination).
- Status **default** untuk semua siswa adalah **Hadir** (tombol menyala/terpilih).
- Guru tinggal mengklik tombol status lain (**Sakit, Izin, Alpha**) jika ada perubahan.
- Tombol yang aktif berwarna solid/berbeda (UX: tombol radio/button grup yang jelas).

┌─────────────────────────────────────────────────────────────────────────────┐ │ 📋 Input Presensi Siswa – Kelas 7-A │ │ Tanggal: 27-08-2026 | Sesi: Sesi Awal │ ├─────────────────────────────────────────────────────────────────────────────┤ │ No │ NISN │ Nama Siswa │ JK │ Status │ ├────┼──────────┼─────────────┼──────┼─────────────────────────────────────┤ │ 1 │ 12345 │ Ahmad │ L │ \[✅ Hadir] \[Sakit] \[Izin] \[Alpha] │ │ 2 │ 12346 │ Budi │ L │ \[✅ Hadir] \[Sakit] \[Izin] \[Alpha] │ │ 3 │ 12347 │ Cinta │ P │ \[✅ Hadir] \[Sakit] \[Izin] \[Alpha] │ └────┴──────────┴─────────────┴──────┴─────────────────────────────────────┘ 📌 Semua siswa default Hadir. Klik tombol lain untuk mengubah status.

### 1.5 Tampilan Revisi — Tombol Status Sesuai Tersimpan

- Pada mode revisi, tombol status aktif **sesuai dengan status yang tersimpan** di database.
- User (Wali Kelas/Admin/Operator) dapat mengklik tombol lain untuk mengubah status.

┌─────────────────────────────────────────────────────────────────────────────┐ │ 📋 Revisi Presensi Siswa – Kelas 7-A │ │ Tanggal: 26-08-2026 | Sesi: Sesi Awal │ ├─────────────────────────────────────────────────────────────────────────────┤ │ No │ NISN │ Nama Siswa │ JK │ Status │ ├────┼──────────┼─────────────┼──────┼─────────────────────────────────────┤ │ 1 │ 12345 │ Ahmad │ L │ \[✅ Hadir] \[Sakit] \[Izin] \[Alpha] │ │ 2 │ 12346 │ Budi │ L │ \[Hadir] \[✅ Sakit] \[Izin] \[Alpha] │ │ 3 │ 12347 │ Cinta │ P │ \[Hadir] \[Sakit] \[✅ Izin] \[Alpha] │ └────┴──────────┴─────────────┴──────┴─────────────────────────────────────┘ 📌 Tombol aktif sesuai status tersimpan di database.

### 1.6 EWS Radar

- **Kriteria:** Siswa dengan **≥ 3 status Alpha** dalam **14 hari terakhir**.
- **Sumber data:** Hanya dari **Sesi Awal** (AW).
- **Hak Akses:** Admin, Operator, Pimpinan, BK, dan Wali Kelas (KELAS\_DIAMPU).
- **Akses:** Hanya melalui widget dashboard (tidak ada menu sidebar).

* * *

## Bagian 2: Presensi Mengajar (Jurnal)

### 2.1 Konsep Dasar

- Fitur ini **terpisah total** dari Presensi Siswa.
- **Berlaku untuk SEMUA sesi:** Sesi Awal, Sesi Akhir, maupun **Non Sesi**. Tidak ada pengecualian.
- **Status:** Hanya 3 pilihan: `Hadir`, `Izin`, `Sakit`.
- **Materi (TEXT):** WAJIB diisi untuk semua status, termasuk Izin dan Sakit (contoh: "Memberikan tugas melalui grup WA").
- **Unique Key:** `(id_jadwal, tanggal)` — satu jadwal hanya bisa diisi sekali per hari.

### 2.2 Hak Akses Presensi Mengajar

| Role | Lihat Jadwal | Input Jurnal | Revisi | Laporan Jurnal | Scope |
| --- | --- | --- | --- | --- | --- |
| **Admin** | Semua | Semua/atas nama | ✅ | Semua | SEMUA |
| **Operator** | Semua | Semua/atas nama | ✅ | Semua | SEMUA |
| **Pimpinan** | Semua | Diri sendiri bila memiliki jadwal | ❌ | Semua readonly | SEMUA untuk laporan |
| **BK** | ❌ | ❌ | ❌ | ❌ | — |
| **Guru Biasa** | Jadwal hari ini | Diri sendiri | ❌ | Diri sendiri | DIRI_SENDIRI |
| **Wali Kelas** | Jadwal hari ini | Diri sendiri | ❌ | Diri sendiri | DIRI_SENDIRI |
| **Siswa** | ❌ | ❌ | ❌ | ❌ | — |

**Catatan:** Status Wali Kelas tidak menambah hak revisi Jurnal. Revisi Presensi Mengajar tetap hanya Admin/Operator.

### 2.3 Alur Input

1. Guru membuka halaman "Presensi Mengajar" → melihat daftar jadwal hari ini (semua sesi, termasuk Non Sesi).
2. Klik tombol "Presensi" pada baris jadwal.
3. Muncul 3 pilihan status: **Hadir**, **Izin**, **Sakit**.
4. Setelah memilih status, form `materi` (TEXT) muncul dan **wajib diisi**.
5. Klik Simpan → data langsung tersimpan (final).

* * *

## Bagian 3: Geofencing

### 3.1 Tujuan

Memastikan bahwa guru mapel (bukan Wali/Admin/Operator) benar-benar berada di lingkungan sekolah saat melakukan presensi.

### 3.2 Spesifikasi Teknis

- **Rumus:** **Haversine Formula** — dipilih karena akurat untuk jarak skala meter–kilometer di permukaan bumi dan ringan secara komputasi.
- **Implementasi:** Method di `GeofencingService.php` yang menerima 2 koordinat (titik sekolah, titik device) dan radius (meter), mengembalikan boolean.
- **Koordinat Sekolah:** Default `-7.533383, 112.217607`, dapat diubah Admin di Settings.
- **Radius Default (D2):** **500 meter**.
- **Toggle (D3):** Admin dapat mematikan geofencing global di Settings (`geofencing_aktif = 0`). Jika OFF, semua pengecualian hilang (mode daring bebas lokasi).

### 3.3 Kapan Geofencing Diterapkan?

| Aksi                                                            | Dikunci Geofencing? | Keterangan                                          |
|-----------------------------------------------------------------|---------------------|-----------------------------------------------------|
| Input Presensi Siswa (AW/AK) oleh **Guru Mapel**                | ✅ Ya                | Guru harus berada dalam radius sekolah.             |
| Input Presensi Siswa (AW/AK) oleh **Wali Kelas/Admin/Operator** | ❌ Tidak             | Bebas lokasi (untuk fleksibilitas input/revisi).    |
| Input Jurnal oleh **Guru Mapel** dengan status **Hadir**        | ✅ Ya                | Guru harus berada di sekolah untuk mengklaim hadir. |
| Input Jurnal oleh **Guru Mapel** dengan status **Izin/Sakit**   | ❌ Tidak             | Logis: jika izin/sakit, guru tidak di sekolah.      |
| Input Jurnal oleh **Admin/Operator** (atas nama guru)           | ❌ Tidak             | Bebas lokasi.                                       |
| **Toggle Settings OFF**                                         | ❌ Semua bebas       | Geofencing dinonaktifkan total.                     |

### 3.4 Mekanisme Server-Side

1. Frontend (browser/APK) meminta izin akses lokasi via Geolocation API.
2. Koordinat device dikirim ke server bersama request submit.
3. Server menghitung jarak (Haversine) antara koordinat device dan koordinat sekolah.
4. Jika jarak &gt; radius (500m) → **submit ditolak**, tampilkan pesan error.
5. Jika user **menolak izin lokasi** atau GPS timeout → **submit ditolak** (tidak ada alternatif manual override untuk guru mapel).

**Penting (Mobile):** Pastikan hosting produksi menggunakan **HTTPS/SSL** — Geolocation API tidak berfungsi di halaman non-HTTPS.

* * *

## Bagian 4: Time-Window Presensi

- **Aturan Dasar Guru Biasa:** Presensi Siswa dan Jurnal hanya dapat diinput dalam rentang `jam_mulai` sampai `jam_selesai + 15 menit` (toleransi).
- **Wali Kelas:** Untuk Presensi Siswa pada kelas walinya, input dan revisi **bebas waktu/time-window**.
- **Admin/Operator:** Untuk Presensi Siswa, input dan revisi tidak dibatasi time-window.
- **Catatan:** Jika Wali mengajar kelas yang bukan kelas walinya, aturan Guru Biasa tetap berlaku.
- **Pengecualian (D4):**
  
  - **Guru Biasa:** Terikat time-window untuk **input** Presensi Siswa dan Jurnal.
  - **Wali Kelas:** Bebas time-window untuk **input dan revisi Presensi Siswa pada kelas walinya**.
  - **Admin/Operator:** Bebas time-window untuk input/revisi administratif Presensi Siswa.

* * *

### 4.1 Rincian Presensi pada Dashboard Siswa

- Dashboard Siswa menampilkan total presensi dirinya.
- Saat kartu total presensi diklik, sistem membuka rincian presensi diri sendiri.
- Untuk menyederhanakan tampilan, rincian hanya menampilkan **Sakit, Izin, dan Alpha**. Status Hadir tidak perlu ditampilkan.
- Data tetap dibatasi `id_siswa = session('id_siswa')`.

## Bagian 5: Aturan Bisnis &amp; Catatan Developer

- **Sesi vs Jam:** "Sesi" (Awal/Akhir/Non Sesi) adalah **label administratif**. Jam mengajar aktual ada di kolom `jam_mulai`/`jam_selesai`. Jangan pernah menyamakan keduanya.
- **Presensi Siswa Resmi:** Hanya Sesi Awal yang dihitung di laporan, matrix, dan EWS.
- **Presensi Mengajar (Jurnal):** Wajib untuk **semua** baris jadwal, termasuk yang bertanda "Non Sesi".
- **Non Sesi:** Tidak punya kewajiban Presensi Siswa, tetapi **tetap** wajib Jurnal.
- **Guru Biasa tidak boleh membaca hasil tersimpan:** endpoint/view untuk hasil presensi yang sudah tersimpan harus menolak Guru Biasa; jangan mengandalkan hanya penyembunyian tombol UI.
- **Validasi `id_guru` (Keamanan):**
  
  - Di method `PresensiMengajar::save()`, sistem **WAJIB** memvalidasi bahwa `id_guru` yang dikirim dalam request **sama dengan** `session('id_guru')` untuk role Guru/Wali/Pimpinan. Ini mencegah guru lain mencatatkan jurnal atas nama orang lain.
  - Admin/Operator dapat menginput atas nama guru lain (tidak terikat validasi ini).
- **Tidak ada Team Teaching:** 1 kelas + 1 jam overlap hanya boleh diampu oleh 1 guru.
- **Dual-Output:** Controller harus mendukung `?format=json` untuk kebutuhan mobile.
- **Audit Revisi (C2/C3):** Perubahan pada Presensi Siswa dan Jurnal dicatat melalui `updated_at` dan `updated_by`.
- **EWS Radar:** Tidak memiliki menu sidebar, hanya diakses melalui widget dashboard.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Presensi Final
