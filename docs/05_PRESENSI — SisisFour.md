# ✅ Presensi Siswa &amp; Presensi Mengajar (Jurnal) — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

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

| Role                | Lihat Daftar Kelas         | Input Sesi Awal/Akhir   | Revisi         | EWS Radar | Rekap (Diri Sendiri) | Geofencing &amp; Time-Window |
|---------------------|----------------------------|-------------------------|----------------|-----------|----------------------|------------------------------|
| **Admin**           | ✅ Semua                    | ✅ Semua                 | ✅ Semua        | ✅         | ✅                    | ❌ Bebas                      |
| **Operator**        | ✅ Semua                    | ✅ Semua                 | ✅ Semua        | ✅         | ✅                    | ❌ Bebas                      |
| **Pimpinan**        | ✅ Semua (readonly)         | ❌ Tidak                 | ❌ Tidak        | ✅         | ✅                    | ❌                            |
| **BK**              | ❌ Tidak                    | ❌ Tidak                 | ❌ Tidak        | ✅         | ❌                    | ❌                            |
| **Guru (non-wali)** | ✅ Kelas terjadwal hari ini | ✅ Sesuai jadwal (AW/AK) | ❌ Tidak        | ❌         | ❌                    | ✅ Terikat GPS + Time-Window  |
| **Wali Kelas**      | ✅ Kelas diampu             | ✅ Kelas diampu          | ✅ Kelas diampu | ✅         | ✅                    | ❌ Bebas (Input &amp; Revisi) |
| **Siswa**           | ❌                          | ❌                       | ❌              | ❌         | ✅                    | ❌                            |

**Catatan (D4):** Wali Kelas, Admin, dan Operator **tidak terikat** time-window untuk melakukan **revisi**. Guru mapel terikat time-window untuk input.

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

| Role                      | Lihat Jadwal                   | Input Jurnal                             | Revisi | Laporan              | Geofencing                  |
|---------------------------|--------------------------------|------------------------------------------|--------|----------------------|-----------------------------|
| **Admin**                 | ✅ Semua                        | ✅ Semua (atas nama)                      | ✅      | ✅                    | ❌ Bebas                     |
| **Operator**              | ✅ Semua                        | ✅ Semua (atas nama)                      | ✅      | ✅                    | ❌ Bebas                     |
| **Pimpinan**              | ✅ Semua                        | ✅ Hanya diri sendiri (jika punya jadwal) | ❌      | ✅                    | ❌ Bebas                     |
| **BK**                    | ❌                              | ❌                                        | ❌      | ❌                    | ❌                           |
| **Guru &amp; Wali Kelas** | ✅ Jadwal hari ini (semua sesi) | ✅ Hanya diri sendiri                     | ❌      | ✅ Hanya diri sendiri | ✅ HANYA jika status = Hadir |
| **Siswa**                 | ❌                              | ❌                                        | ❌      | ❌                    | ❌                           |

**Catatan (C3):** Jurnal **tidak dapat direvisi** oleh Guru/Wali sendiri. Hanya Admin dan Operator yang dapat merevisi, dan revisi akan tercatat di `updated_at` dan `updated_by`.

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

- **Aturan Dasar:** Presensi hanya dapat diinput dalam rentang `jam_mulai` sampai `jam_selesai + 15 menit` (toleransi).
- **Pengecualian (D4):**
  
  - **Guru Mapel:** Terikat time-window untuk **input** Presensi Siswa dan Jurnal.
  - **Wali Kelas, Admin, Operator:** **Bebas** time-window untuk melakukan **revisi** Presensi Siswa.

* * *

## Bagian 5: Aturan Bisnis &amp; Catatan Developer

- **Sesi vs Jam:** "Sesi" (Awal/Akhir/Non Sesi) adalah **label administratif**. Jam mengajar aktual ada di kolom `jam_mulai`/`jam_selesai`. Jangan pernah menyamakan keduanya.
- **Presensi Siswa Resmi:** Hanya Sesi Awal yang dihitung di laporan, matrix, dan EWS.
- **Presensi Mengajar (Jurnal):** Wajib untuk **semua** baris jadwal, termasuk yang bertanda "Non Sesi".
- **Non Sesi:** Tidak punya kewajiban Presensi Siswa, tetapi **tetap** wajib Jurnal.
- **Validasi `id_guru` (Keamanan):**
  
  - Di method `PresensiMengajar::save()`, sistem **WAJIB** memvalidasi bahwa `id_guru` yang dikirim dalam request **sama dengan** `session('id_guru')` untuk role Guru/Wali/Pimpinan. Ini mencegah guru lain mencatatkan jurnal atas nama orang lain.
  - Admin/Operator dapat menginput atas nama guru lain (tidak terikat validasi ini).
- **Tidak ada Team Teaching:** 1 kelas + 1 jam overlap hanya boleh diampu oleh 1 guru.
- **Dual-Output:** Controller harus mendukung `?format=json` untuk kebutuhan mobile.
- **Audit Revisi (C2/C3):** Perubahan pada Presensi Siswa dan Jurnal dicatat melalui `updated_at` dan `updated_by`.
- **EWS Radar:** Tidak memiliki menu sidebar, hanya diakses melalui widget dashboard.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Presensi Final
