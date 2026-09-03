# 📋 BK, Prestasi &amp; Kartu Pelajar — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini mengatur tiga modul penting: **Bimbingan Konseling (BK)** yang mencakup catatan kasus dan master pelanggaran, **Pencatatan Prestasi Siswa**, serta **Kartu Pelajar Digital** dengan verifikasi QR. Seluruh modul telah terintegrasi dengan identitas siswa menggunakan **NIK** dan aturan masking untuk keamanan privasi.

* * *

## Bagian 1: Bimbingan Konseling (BK)

### 1.1 Master Pelanggaran

- **Tabel:** `ref_pelanggaran`.
- **Field:** `id`, `nama_pelanggaran`, `kategori` (Ringan/Sedang/Berat), `poin`.
- **Data Default:** 12 data awal tersedia (Terlambat, Bolos, Berkelahi, dll).
- **CRUD:** Admin, Operator, dan BK dapat mengelola. Role lain **tidak** punya akses.

### 1.2 Catatan Kasus

- **Tabel:** `catatan_kasus`.
- **Field:** `id`, `id_siswa`, `id_pelanggaran`, `tanggal`, `keterangan`, `id_guru_input`, `created_at`, `updated_at`, `updated_by`.
- **Aturan:**
  
  - Poin pelanggaran terakumulasi otomatis per siswa.
  - Poin ini tidak memicu notifikasi/status khusus otomatis.
  - **Tidak terhubung** dengan EWS Radar (EWS murni berbasis Alpha).
  - Setiap perubahan (update) wajib mengisi `updated_at` dan `updated_by` untuk audit.

### 1.3 Hak Akses BK

| Role                | Master Pelanggaran | Catatan Kasus (Input) | Catatan Kasus (View)        | Top 20 Poin Pelanggaran |
|---------------------|--------------------|-----------------------|-----------------------------|-------------------------|
| **Admin**           | ✅ CRUD             | ✅                     | ✅ SEMUA                     | ✅                       |
| **Operator**        | ✅ CRUD             | ✅                     | ✅ SEMUA                     | ✅                       |
| **Pimpinan**        | ❌                  | ❌                     | Readonly (SEMUA)            | ✅                       |
| **BK**              | ✅ CRUD             | ✅                     | ✅ SEMUA                     | ✅                       |
| **Guru (non-wali)** | ❌                  | ❌                     | Readonly (KELAS\_TERJADWAL) | Readonly                |
| **Wali Kelas**      | ❌                  | ❌                     | Readonly (KELAS\_DIAMPU)    | Readonly                |
| **Siswa**           | ❌                  | ❌                     | Readonly (DIRI\_SENDIRI)    | ❌                       |

**Catatan Audit:** Setiap perubahan pada Catatan Kasus (update) wajib mengisi `updated_at` dan `updated_by`.

* * *

## Bagian 2: Prestasi Siswa

### 2.1 Spesifikasi

- **Tabel:** `catatan_prestasi`.
- **Field:** `id`, `id_siswa`, `nama_prestasi`, `tingkat` (kota/provinsi/nasional/dll), `tanggal`, `penyelenggara`, `keterangan`, `id_guru_input`, `created_at`.
- **Upload Bukti:** TIDAK ada fitur upload file — cukup input teks.
- **Filter:** Halaman list wajib memiliki filter **Kelas + Rentang Tanggal**.

### 2.2 Hak Akses Prestasi

| Role                | Prestasi (Input) | Prestasi (View)             |
|---------------------|------------------|-----------------------------|
| **Admin**           | ✅                | ✅ SEMUA                     |
| **Operator**        | ✅                | ✅ SEMUA                     |
| **Pimpinan**        | ❌                | Readonly (SEMUA)            |
| **BK**              | ✅                | ✅ SEMUA                     |
| **Guru (non-wali)** | ❌                | Readonly (KELAS\_TERJADWAL) |
| **Wali Kelas**      | ❌                | Readonly (KELAS\_DIAMPU)    |
| **Siswa**           | ❌                | Readonly (DIRI\_SENDIRI)    |

* * *

## Bagian 3: Top 20 Poin Pelanggaran

- **Deskripsi:** Widget yang menampilkan 20 siswa dengan akumulasi poin pelanggaran tertinggi.
- **Lokasi:** Dashboard Pimpinan dan Dashboard BK.
- **Akses Tambahan:** Guru &amp; Wali Kelas dapat melihat Top 20 dalam mode **readonly**.
- **Catatan:** Widget ini **terpisah** dari EWS Radar dan tidak saling mempengaruhi.

* * *

## Bagian 4: Kartu Pelajar Digital

### 4.1 Ringkasan Fitur

- **Bulk Generate:** Terbitkan kartu untuk semua siswa aktif sekaligus (Admin/Operator).
- **Cetak Massal:** PDF 2 kolom × 5 baris = 10 kartu per lembar A4, per kelas (depan &amp; belakang).
- **Verifikasi Publik:** QR code → halaman publik data siswa terbatas (tanpa login).
- **Preview &amp; Download:** Siswa dapat melihat dan mengunduh kartunya sendiri.
- **Nonaktif Otomatis:** Saat siswa lulus/pindah/keluar, kartu otomatis menjadi Nonaktif.
- **Reissue:** Generate ulang PDF tanpa mengubah nomor kartu/kode verifikasi (Admin/Operator).
- **Background Dinamis:** Admin dapat mengunggah background depan &amp; belakang melalui Settings.

### 4.2 Spesifikasi Teknis Kartu

- **Kanvas:** 1011 × 638 px (rasio mendekati 3:2).
- **Field Dinamis (data-field):** `nama`, `nik`, `kelas`, `jenis_kelamin`, `tahun_ajaran`, `ttl`, `alamat`, `foto`, `qr`, `kode_kartu`.
- **Foto:** Rasio **3:4** (210×280px di kanvas). Jika siswa tidak punya foto, area foto **dikosongkan** (bukan placeholder).
- **QR Code (B6):** Rasio 1:1 (120×120px), berisi `kode_verifikasi` random string yang mengarah ke endpoint publik `/kartu/verify/{kode}`.
- **NIK di Kartu Fisik:** Dicetak **penuh** (untuk keperluan administrasi).
- **Background:**
  
  - Disimpan di `uploads/kartu_pelajar/background_depan/` dan `.../background_belakang/`.
  - Admin dapat mengunggah melalui Settings (format PNG, max 2MB, re-encode).
  - Background belakang statis (tanpa data siswa).
- **Dompdf Catatan:**
  
  - Font **Poppins** harus di-embed lokal (file .ttf).
  - `box-shadow` terbatas — perlu disederhanakan/dihilangkan.
  - Background image via CSS `url()` membutuhkan path absolut atau base64.

### 4.3 Hak Akses Kartu Pelajar

| Role                | Bulk Generate | Cetak Massal  | Preview/Download | Reissue | Verifikasi Publik |
|---------------------|---------------|---------------|------------------|---------|-------------------|
| **Admin**           | ✅             | ✅             | ✅                | ✅       | ✅                 |
| **Operator**        | ✅             | ✅             | ✅                | ✅       | ✅                 |
| **Pimpinan**        | ❌             | Readonly      | Readonly         | ❌       | ✅                 |
| **BK**              | ❌             | ❌             | ❌                | ❌       | ✅                 |
| **Guru (non-wali)** | ❌             | ❌             | ❌                | ❌       | ✅                 |
| **Wali Kelas**      | ❌             | KELAS\_DIAMPU | KELAS\_DIAMPU    | ❌       | ✅                 |
| **Siswa**           | ❌             | ❌             | DIRI\_SENDIRI    | ❌       | ✅                 |

### 4.4 Cetak Massal (A4)

- **Batasan:** Hanya dapat mencetak per kelas (bukan per sekolah/tingkat) untuk menghindari timeout Dompdf.
- **Layout:** 2 kolom × 5 baris = 10 kartu per lembar A4 potret (210×297mm).
- **Halaman 1:** 10 kartu bagian depan (background + data siswa).
- **Halaman 2:** 10 kartu bagian belakang (background statis).
- **Urutan siswa harus identik** di kedua halaman (agar depan-belakang sesuai).
- **Filter:** Per Kelas + Tahun Ajaran aktif.

### 4.5 Verifikasi Publik (QR) — Masking NIK

- **Endpoint:** `/kartu/verify/{kode_verifikasi}` (tanpa autentikasi).
- **Output:** Halaman publik yang menampilkan data siswa terbatas:
  
  - **Nama** — lengkap.
  - **NIK** — DIMASKING (tidak ditampilkan penuh). Format: `351012xxxxxx1234` (6 digit awal + 6 mask + 4 digit akhir).
  - **Kelas** — nama kelas.
  - **Foto** — jika ada.
- **Catatan:** NIK penuh **hanya** tercetak di kartu fisik yang dipegang siswa, **tidak** ditampilkan di halaman verifikasi publik.
- **Library QR:** Menggunakan **endroid/qr-code** via Composer.
- **Keamanan (B6):** Kode verifikasi adalah **random string** yang sulit ditebak (contoh: `substr(sha1(uniqid().microtime()), 0, 16)`).

* * *

## 5. Catatan Penting untuk Developer

- **Poin BK vs EWS:** Poin pelanggaran di BK **tidak** mempengaruhi EWS Radar. EWS murni menghitung Alpha dari presensi Sesi Awal.
- **Input BK &amp; Prestasi:** Hanya BK, Operator, dan Admin yang dapat menambah/mengubah data. Guru/Wali **hanya** dapat melihat (readonly) dengan scope masing-masing.
- **Audit BK (C1):** Field `updated_at` dan `updated_by` di `catatan_kasus` wajib diisi saat update. Tidak perlu tabel audit terpisah.
- **Kartu Nonaktif Otomatis:** Sistem wajib mendengarkan event perubahan `status_aktif` siswa. Saat siswa menjadi `Lulus`, `Pindah`, atau `Keluar`, kartu otomatis di-set `status_aktif = 'Nonaktif'`.
- **Reissue (B6):** Nomor kartu dan kode verifikasi **tetap sama** saat reissue. Sistem hanya generate ulang PDF untuk dicetak ulang (bukan menerbitkan identitas baru).
- **Upload Foto (B7):** Foto siswa untuk kartu wajib re-encode (PNG) dan di-crop rasio 3:4. Jika tidak ada foto, area dikosongkan.
- **NIK Masking:** Di halaman verifikasi publik, NIK wajib dimasking dengan format 6 digit awal + 6 mask + 4 digit akhir. Di kartu fisik, NIK dicetak penuh.
- **Wali Kelas Access:** Wali Kelas mendapat scope `KELAS_DIAMPU` untuk `kartu_pelajar.manage` (cetak massal) dan `kartu_pelajar.view` (preview/download).

* * *

© 2026 SisisFour · MTsN 4 Jombang · BK, Prestasi &amp; Kartu Final
