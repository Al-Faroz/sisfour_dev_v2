# 📊 Laporan &amp; Export — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini mengatur seluruh mekanisme pelaporan dan ekspor data, meliputi **Matrix Presensi**, **Export Per Bulan**, **Export Per Semester**, dan **Laporan Jurnal Mengajar**. Semua laporan menggunakan struktur **Total H|S|I|A** yang telah disepakatan dan hanya menghitung data dari **Sesi Awal** sebagai sumber resmi.

**✅ Struktur Total H|S|I|A:** Kolom "Total" pada Matrix dan Export dipecah menjadi 4 sub-kolom: **H | S | I | A** (Hadir, Sakit, Izin, Alpha). Total ini **hanya** dihitung dari data **Sesi Awal**.

* * *

## 1. Hak Akses Laporan

Berikut matriks akses untuk setiap fitur laporan berdasarkan role.

| Fitur                       | Admin | Operator | Pimpinan | BK | Guru (non-wali)  | Wali Kelas    | Siswa |
|-----------------------------|-------|----------|----------|----|------------------|---------------|-------|
| **Matrix Presensi**         | ✅     | ✅        | ✅        | ❌  | KELAS\_TERJADWAL | KELAS\_DIAMPU | ❌     |
| **Export Per Bulan**        | ✅     | ✅        | ✅        | ❌  | ❌                | KELAS\_DIAMPU | ❌     |
| **Export Per Semester**     | ✅     | ✅        | ✅        | ❌  | ❌                | KELAS\_DIAMPU | ❌     |
| **Laporan Jurnal Mengajar** | ✅     | ✅        | ✅        | ❌  | DIRI\_SENDIRI    | DIRI\_SENDIRI | ❌     |
| **Export Jurnal Mengajar**  | ✅     | ✅        | ✅        | ❌  | ❌                | ❌             | ❌     |

**Keterangan Scope:**

- KELAS\_TERJADWAL = hanya melihat data kelas yang terjadwal untuk guru tersebut (hari itu).
- KELAS\_DIAMPU = hanya melihat data kelas yang diampu (Wali Kelas).
- DIRI\_SENDIRI = hanya melihat jurnal milik sendiri.

* * *

## 2. Matrix Presensi Siswa

### 2.1 Spesifikasi

- **Format:** Tabel dengan baris = siswa, kolom = tanggal (1 bulan penuh), isi sel = status kehadiran (**H/S/I/A**).
- **Sumber Data:** Hanya dari **Sesi Awal**.
- **Total:** Dipecah menjadi 4 sub-kolom: **H | S | I | A** (jumlah masing-masing status).
- **Filter:** Kelas + Bulan. Tahun Ajaran otomatis mengambil yang aktif (`status_aktif = 1`).
- **Hak Akses:** Admin, Operator, Pimpinan (SEMUA); Guru (KELAS\_TERJADWAL); Wali Kelas (KELAS\_DIAMPU). BK dan Siswa **tidak** punya akses.
- **Permission:** `laporan_matrix.view`.
- **Pagination (E3):** Menggunakan **Client-side** DataTables (karena keputusan final).

### 2.2 Mockup

┌──────────────────────────────────────────────────────────────────────────────────────────────┐ │ 📊 Matrix Presensi Siswa – Kelas 7-A – Agustus 2026 │ ├──────────────────────────────────────────────────────────────────────────────────────────────┤ │ No │ NISN │ Nama Siswa │ Total │ 01 │ 02 │ 03 │ ... │ 31 │ │ │ │ │ H │ S │ I │ A │ Aug │ Aug │ Aug │ │ Aug │ ├────┼──────────┼─────────────┼───┼───┼───┼───┼──────┼──────┼──────┼──────┼──────┤ │ 1 │ 12345 │ Ahmad │ 20│ 2 │ 0 │ 2 │ H │ H │ S │ ... │ H │ │ 2 │ 12346 │ Budi │ 18│ 3 │ 1 │ 2 │ H │ A │ H │ ... │ H │ │ 3 │ 12347 │ Cinta │ 22│ 1 │ 1 │ 0 │ H │ H │ H │ ... │ I │ └────┴──────────┴─────────────┴───┴───┴───┴───┴──────┴──────┴──────┴──────┴──────┘

* * *

## 3. Export Presensi — Per Bulan

### 3.1 Spesifikasi

- **Deskripsi:** Rekap presensi per siswa dalam satu bulan.
- **Struktur Kolom:**
  
  1. **No** – urutan
  2. **NISN** – NISN siswa
  3. **Nama Siswa** – nama lengkap
  4. **Kelas** – nama kelas
  5. **Total (H|S|I|A)** – dihitung dari **Sesi Awal**
  6. **Tanggal 1..31** – masing-masing tanggal memiliki 2 sub-kolom: **AW** (Sesi Awal) dan **AK** (Sesi Akhir).
- **Catatan:** Total H/S/I/A **hanya** dari Sesi Awal. AK hanya sebagai informasi tambahan dan **tidak** dijumlahkan ke total.
- **Filter:** Kelas + Bulan. Tahun Ajaran aktif otomatis.
- **Judul:** `REKAP ABSENSI – BULAN {NAMA_BULAN} – {TAHUN}`.
- **Hak Akses:** Admin, Operator, Pimpinan (SEMUA); Wali Kelas (KELAS\_DIAMPU). Guru non-wali **tidak** punya akses.

### 3.2 Mockup

┌──────────────────────────────────────────────────────────────────────────────────────────────┐ │ 📤 REKAP ABSENSI – BULAN AGUSTUS 2026 │ │ Kelas: 7-A │ ├──────────────────────────────────────────────────────────────────────────────────────────────┤ │ No │ NISN │ Nama │ Kelas │ Total │ 01 Aug │ 02 Aug │ ... │ 31 Aug │ │ │ │ Siswa│ │ H │ S │ I │ A │ AW │ AK │ AW │ AK │ │ AW │ AK │ ├────┼──────┼──────┼──────┼───┼───┼───┼───┼────┼──────┼────┼──────┼─────┼────┼──────┤ │ 1 │12345 │Ahmad │ 7-A │20 │ 2 │ 0 │ 2 │ H │ H │ H │ H │ ... │ H │ H │ │ 2 │12346 │Budi │ 7-A │18 │ 3 │ 1 │ 2 │ H │ - │ A │ - │ ... │ H │ - │ │ 3 │12347 │Cinta │ 7-A │22 │ 1 │ 1 │ 0 │ H │ H │ H │ H │ ... │ I │ H │ └────┴──────┴──────┴──────┴───┴───┴───┴───┴────┴──────┴────┴──────┴─────┴────┴──────┘

* * *

## 4. Export Presensi — Per Semester

### 4.1 Spesifikasi

- **Deskripsi:** Rekap presensi per siswa dalam satu semester (6 bulan).
- **Struktur Kolom:**
  
  1. **No** – urutan
  2. **NISN** – NISN siswa
  3. **Nama Siswa** – nama lengkap
  4. **Kelas** – nama kelas
  5. **Total (H|S|I|A)** – total seluruh semester dari **Sesi Awal**
  6. **Bulan 1..6** – masing-masing bulan memiliki 4 sub-kolom: **H | S | I | A** (jumlah hari per status di bulan tersebut, dari Sesi Awal).
- **Catatan:** Semua angka dihitung dari **Sesi Awal** saja. AK tidak ditampilkan di laporan semester.
- **Filter:** Kelas + Semester (Ganjil/Genap).
- **Judul:** `REKAP ABSENSI – SEMESTER {GANJIL/GENAP} {TAHUN}`.
- **Hak Akses:** Admin, Operator, Pimpinan (SEMUA); Wali Kelas (KELAS\_DIAMPU).
- **Semester:** Ganjil = Juli–Desember, Genap = Januari–Juni (sesuai field `semester` di `tahun_ajaran`).

### 4.2 Mockup

┌──────────────────────────────────────────────────────────────────────────────────────────────┐ │ 📤 REKAP ABSENSI – SEMESTER GANJIL 2026/2027 │ │ Kelas: 7-A │ ├──────────────────────────────────────────────────────────────────────────────────────────────┤ │ No │ NISN │ Nama │ Kelas │ Total │ Bulan 1 (Jul)│ Bulan 2 (Ags)│ ... │ Bulan 6 (Des)│ │ │ │ Siswa│ │ H │ S │ I │ A │ H │ S │ I │ A│ H │ S │ I │ A│ │ H │ S │ I │ A│ ├────┼──────┼──────┼──────┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼─────┼───┼───┼───┼───┤ │ 1 │12345 │Ahmad │ 7-A │80 │ 8 │ 2 │ 4 │ 20│ 2 │ 0 │ 0│ 22│ 1 │ 0 │ 1│ ... │ 15│ 1 │ 1 │ 1│ │ 2 │12346 │Budi │ 7-A │75 │12 │ 3 │ 4 │ 18│ 3 │ 0 │ 1│ 20│ 3 │ 0 │ 1│ ... │ 12│ 2 │ 2 │ 1│ └────┴──────┴──────┴──────┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴─────┴───┴───┴───┴───┘

* * *

## 5. Laporan Jurnal Mengajar

### 5.1 Spesifikasi

- **Deskripsi:** Laporan jurnal mengajar dengan filter dinamis berdasarkan Guru dan Kelas.
- **Kolom Tampilan:** Tanggal, Hari, Jam, Guru, Kelas, Mapel, Status, Materi.
- **Filter (Dinamis):**
  
  - **Admin/Operator/Pimpinan (SEMUA):** Pilih **Nama Guru** terlebih dulu (dropdown semua guru aktif). Setelah guru dipilih, dropdown **Kelas** akan terisi otomatis dengan kelas-kelas yang diajar oleh guru tersebut (berdasarkan `jadwal_guru`). Filter diterapkan saat tombol "Tampilkan" ditekan.
  - **Guru &amp; Wali Kelas (DIRI\_SENDIRI):** Field Guru otomatis terisi (readonly, nama sendiri). Dropdown Kelas hanya menampilkan kelas yang diajar oleh guru tersebut.
- **Hak Akses:** Admin, Operator, Pimpinan (SEMUA); Guru/Wali Kelas (DIRI\_SENDIRI). BK &amp; Siswa **tidak** punya akses.

### 5.2 Mockup

┌─────────────────────────────────────────────────────────────────────────────┐ │ 📋 Laporan Jurnal Mengajar │ │ Filter: \[Guru: Ahmad Fauzi ▼] \[Kelas: 7-A ▼] \[📊 Tampilkan] │ ├─────────────────────────────────────────────────────────────────────────────┤ │ Tanggal │ Hari │ Jam │ Guru │ Kelas │ Mapel │ Status │ Materi │ ├────────────┼────────┼────────┼──────────────┼───────┼───────┼────────┼────────┤ │ 2026-08-01 │ Senin │ 07:30 │ Ahmad Fauzi │ 7-A │ MTK │ Hadir │ Bab 1 │ │ 2026-08-02 │ Selasa │ 09:00 │ Ahmad Fauzi │ 7-A │ IPA │ Hadir │ Praktik│ │ 2026-08-03 │ Rabu │ 10:30 │ Ahmad Fauzi │ 7-B │ MTK │ Sakit │ Tugas │ └────────────┴────────┴────────┴──────────────┴───────┴───────┴────────┴────────┘

* * *

## 6. Aturan Export &amp; Filter

### 6.1 Filter Sinkron

- **WAJIB:** Export mengikuti filter yang sedang aktif di layar. Tidak ada opsi export "semua data" tanpa filter.
- **Halaman Export Presensi (42):**
  
  - Dropdown **Jenis Export** (Per Bulan / Per Semester).
  - Dropdown **Kelas** (wajib).
  - Dropdown **Bulan** (jika Per Bulan) atau **Semester** (jika Per Semester).
- **Halaman Laporan Jurnal (43):**
  
  - Dropdown **Guru** (terkunci untuk guru sendiri).
  - Dropdown **Kelas** (dinamis berdasarkan guru yang dipilih, menggunakan AJAX).

### 6.2 Format Export Excel

- **Format:** Tabel data polos (tanpa kop surat/logo sekolah).
- **Judul:** Hanya judul laporan di baris paling atas (contoh: "Laporan Matrix Presensi — Kelas 7-A — Agustus 2026").
- **Library:** Menggunakan **PhpSpreadsheet** via Composer.
- **Berlaku untuk:** Matrix, Export Bulanan, Export Semester, Laporan Jurnal.

* * *

## 7. Catatan Penting untuk Developer

- **Sumber Data Total:** Semua perhitungan H/S/I/A pada laporan harus menggunakan filter `WHERE sesi = 'Sesi Awal'`.
- **Data AK:** Hanya ditampilkan di Export Per Bulan sebagai informasi tambahan, tetapi **tidak** dijumlahkan ke Total.
- **Tahun Ajaran:** Otomatis mengambil yang aktif (`status_aktif = 1`). Tidak ada dropdown tahun ajaran di halaman laporan.
- **Semester (A7):** Ganjil = Juli–Desember, Genap = Januari–Juni. Nilai ini diambil dari kolom `semester` di tabel `tahun_ajaran`.
- **Filter Dinamis Jurnal:** Dropdown Kelas harus di-render ulang (AJAX) saat Guru dipilih, untuk menghindari daftar kelas yang tidak relevan.
- **Permission:** Laporan Jurnal hanya bisa diakses jika user memiliki `laporan_jurnal.view`. Export membutuhkan `laporan_jurnal.export`.
- **Dual-Output:** Semua halaman laporan mendukung `?format=json` untuk kebutuhan mobile (Cordova).
- **NISN vs NIK:** Laporan menggunakan **NISN** sebagai identitas siswa, karena NIK adalah data internal sensitif yang tidak perlu diekspos di laporan.
- **Wali Kelas Export:** Wali Kelas memiliki permission `laporan_export.generate` dengan scope `KELAS_DIAMPU`, sehingga hanya bisa mengekspor data kelas yang diampu.
- **Client-Side Pagination:** Menggunakan DataTables dengan processing client-side (E3) untuk semua tabel laporan.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Laporan &amp; Export Final
