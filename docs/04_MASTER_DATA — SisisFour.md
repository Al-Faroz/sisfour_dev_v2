# 📋 Master Data — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini mengatur seluruh data master madrasah: **Guru, Pegawai, Siswa, Kelas, Tahun Ajaran, Mata Pelajaran, Wali Kelas, dan Jadwal Guru**. Setiap modul memiliki aturan bisnis, hak akses, dan mekanisme operasional yang spesifik.

* * *

## 1. Hak Akses Master Data

| Fitur                                         | Admin | Operator | Pimpinan | BK | Guru (non-wali) | Wali Kelas        | Siswa                   |
|-----------------------------------------------|-------|----------|----------|----|-----------------|-------------------|-------------------------|
| **CRUD Guru**                                 | ✅     | ✅        | Readonly | ❌  | ❌               | ❌                 | ❌                       |
| **CRUD Pegawai**                              | ✅     | ✅        | Readonly | ❌  | ❌               | ❌                 | ❌                       |
| **Lihat Data Siswa**                          | ✅     | ✅        | Readonly | ❌  | ❌               | KELAS\_DIAMPU     | Readonly (diri sendiri) |
| **Edit Biodata &amp; Foto Siswa**             | ✅     | ✅        | ❌        | ❌  | ❌               | ✅ (KELAS\_DIAMPU) | ❌                       |
| **Mutasi / Kenaikan Kelas / Kelulusan Siswa** | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **Import/Export Data Siswa**                  | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **CRUD Kelas**                                | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **CRUD Tahun Ajaran**                         | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **CRUD Mata Pelajaran**                       | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **Import/Export Excel (Guru/Pegawai/Jadwal)** | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **Mapping Wali Kelas (Assign)**               | ✅     | ✅        | ❌        | ❌  | ❌               | ❌                 | ❌                       |
| **Lihat Jadwal Guru (Semua)**                 | ✅     | ✅        | ✅        | ❌  | ❌               | ❌                 | ❌                       |
| **Lihat Jadwal Guru (Diri Sendiri)**          | ✅     | ✅        | ✅        | ❌  | ✅               | ✅                 | ❌                       |
| **Edit Data Diri (Guru) — Profile**           | ✅     | ✅        | ✅        | ✅  | ✅               | ✅                 | ❌                       |

**Keterangan Simbol:**

- ✅ = Memiliki akses penuh (CRUD / edit).
- Readonly = Hanya dapat melihat data, tidak dapat mengubah.
- KELAS\_DIAMPU = Akses terbatas pada data kelas yang diampu (Wali Kelas).
- ❌ = Tidak memiliki akses sama sekali.

* * *

## 2. Soft Delete &amp; Recycle Bin

- Tabel yang menggunakan soft delete (`deleted_at`): **guru, pegawai, siswa, kelas, tahun\_ajaran, mapping\_wali\_kelas**.
- Data yang dihapus sementara dapat **dipulihkan (restore)** atau **dihapus permanen** oleh Admin/Operator.
- **Pengecualian:** `mata_pelajaran` menggunakan **hard delete** karena strukturnya sederhana.

* * *

## 3. Aturan Spesifik per Modul

### 3.1 Guru &amp; Pegawai

- **Auto-create User:**
  
  - Saat **Guru** dibuat → otomatis dibuatkan akun `users` dengan `username = NIP`, `password = hash(NIP)`, dan `role = 'guru'`.
  - Saat **Pegawai** dibuat → otomatis dibuatkan akun `users` dengan `username = NIP`, `password = hash(NIP)`, tetapi **`role = NULL`** . Admin harus menentukan role secara manual (Operator, Pimpinan, atau lainnya).
- **Validasi NIP Lintas Tabel:** NIP di tabel `guru` dan `pegawai` **tidak boleh sama**. Service wajib mengecek kedua tabel saat validasi.
- **Upload Foto:** Format **PNG**, max 2MB, di-crop rasio **3:4**, di-re-encode untuk menghilangkan metadata, disimpan di `uploads/foto_guru/`.

### 3.2 Siswa

- **Identifier:** NIK (16 digit, UNIQUE) sebagai pengganti NIS. NISN tetap sebagai basis akun login.
- **Auto-create User:** Saat siswa dibuat → akun `users` dengan `username = NISN`, `password = hash(NISN)`, dan `role = 'siswa'`.
- **Upload Foto:** Format **PNG**, max 2MB, crop 3:4, re-encode, disimpan di `uploads/foto_siswa/`.
- **Status Aktif:** `Aktif, Lulus, Pindah, Keluar`.
- **Mutasi (Pindah/Keluar) &amp; Kelulusan:**
  
  - Hanya Admin/Operator yang dapat melakukan mutasi atau kelulusan.
  - Saat siswa **Pindah**, **Keluar**, atau **Lulus**, sistem wajib mengisi `tanggal_mutasi` dan `keterangan_mutasi`.
  - **Efek:** Kartu pelajar otomatis menjadi **Nonaktif**.
  - **Histori (A4):** Wajib mencatat ke tabel `riwayat_siswa` dengan status yang sesuai dan mengisi `tanggal_selesai`.
- **Akses Wali Kelas:** Wali Kelas dapat **mengedit biodata dan foto** siswa di kelas yang diampu (scope KELAS\_DIAMPU), tetapi **tidak** dapat melakukan mutasi, kenaikan kelas, atau import/export.

### 3.3 Kelas &amp; Tahun Ajaran

- **Struktur Kelas:** `tingkat` (7/8/9) + `rombel` (A/B/C/...) → `nama_kelas` di-generate otomatis (contoh: "7-A").
- **Tahun Ajaran (A7):** Setiap tahun ajaran wajib memiliki `semester` (`Ganjil` atau `Genap`).
- **Status Aktif:** Hanya satu tahun ajaran yang boleh memiliki `status_aktif = 1` pada satu waktu. Mengaktifkan satu tahun ajaran otomatis menonaktifkan yang sebelumnya.
- **Kenaikan Kelas (A6):**
  
  - Admin memilih kelas asal.
  - Sistem menampilkan **daftar checklist** semua siswa di kelas tersebut, dengan **default semua terpilih**.
  - Admin dapat **uncheck** siswa tertentu jika ada pengecualian (misal mutasi).
  - Admin memilih **kelas tujuan** yang sudah dibuat sebelumnya.
  - Proses: pindahkan siswa yang terpilih ke kelas tujuan &amp; tahun ajaran baru. Catat ke `riwayat_siswa`.
  - **Catatan:** Wali Kelas &amp; Jadwal Guru **tidak** ikut pindah — harus diinput ulang untuk tahun ajaran baru.

### 3.4 Mata Pelajaran (E4)

- Menggunakan **hard delete** (tanpa `deleted_at`).
- Jika mapel sudah digunakan di `jadwal_guru`, foreign key `RESTRICT` akan mencegah penghapusan.
- Data default 17 mapel sudah tersedia di seeder SQL.

* * *

## 4. Mapping Wali Kelas (A2)

- **Aturan Dasar:**
  
  - 1 guru = maksimal 1 kelas per tahun ajaran → UNIQUE `(id_guru, id_tahun)`.
  - 1 kelas = maksimal 1 wali aktif per tahun ajaran → UNIQUE `(id_kelas, id_tahun)`.
  - Status Wali Kelas **bukan** role, dicek dinamis dari tabel `mapping_wali_kelas`.
- **Mekanisme Soft Delete (A2 — C2):**
  
  - Data **aktif** → `deleted_at = NULL` → dihitung UNIQUE.
  - Data **nonaktif/dihapus** → `deleted_at = TIMESTAMP` → **tidak** dihitung UNIQUE (karena nilai timestamp unik).
  - Data nonaktif tetap tersimpan sebagai histori.
- **Assign &amp; Ganti Wali:**
  
  - **Assign baru:** Insert baris dengan `deleted_at = NULL`.
  - **Ganti wali (restore/reassign):** Jika guru yang sama **pernah** menjadi wali di tahun yang sama (data di-soft-delete), sistem **tidak boleh** melakukan `INSERT` baru. Sistem wajib melakukan **RESTORE**: `UPDATE mapping_wali_kelas SET deleted_at = NULL, id_kelas = {kelas_baru} WHERE id_guru = {guru} AND id_tahun = {tahun}`.
  - **Hapus (nonaktifkan):** `UPDATE mapping_wali_kelas SET deleted_at = NOW() WHERE id = {id}`.
- **Dropdown Logic:**
  
  - **Dropdown Guru:** Hanya guru yang **belum** menjadi wali aktif (`deleted_at = NULL`) di tahun yang sama yang muncul.
  - **Dropdown Kelas:** Hanya kelas yang **belum** memiliki wali aktif (`deleted_at = NULL`) di tahun yang sama yang muncul.

* * *

## 5. Jadwal Guru

### 5.1 Konsep Sesi

- **Sesi Awal** → menentukan kewajiban Presensi Siswa (resmi, dihitung di laporan).
- **Sesi Akhir** → dokumentasi tambahan, tidak dihitung di laporan resmi.
- **Non Sesi** → **tidak** wajib Presensi Siswa, tetapi **tetap** wajib Presensi Mengajar (Jurnal).

### 5.2 Import Excel (WAJIB)

- Jadwal guru **hanya** dapat diinput melalui **import Excel**. Tidak ada form input manual satu-satu.
- **Template wajib memiliki kolom:** `nip_guru`, `nama_kelas`, `kode_mapel`, `hari`, `jam_mulai`, `jam_selesai`, `sesi`.
- **Validasi bentrok (di Service):**
  
  - Guru yang sama tidak boleh memiliki 2 jadwal dengan rentang jam overlap di hari yang sama.
  - Kelas yang sama tidak boleh memiliki 2 jadwal dengan rentang jam overlap di hari yang sama (**tidak ada team teaching**).
  - Jika ditemukan bentrok, import **berhenti total** dan laporkan baris yang bermasalah.
- **Atomic Transaction:** Seluruh proses import dibungkus dalam **database transaction**. Jika ada satu baris error, seluruh transaksi **di-rollback** (tidak ada baris yang tersimpan).
- **Query Builder:** Dilarang menggunakan raw SQL string concat. Wajib menggunakan **Query Builder** atau **parameter binding** untuk keamanan.
- **Semester/Tahun Ajaran Baru:** Import jadwal baru **tidak menghapus** jadwal lama. Jadwal lama otomatis di-set `status_jadwal = 'Nonaktif'`. Jadwal baru masuk dengan `status_jadwal = 'Aktif'`.

### 5.3 Time-Window Presensi

- Presensi (Siswa &amp; Mengajar) hanya bisa diinput dalam rentang `jam_mulai` sampai `jam_selesai + 15 menit`.
- **Pengecualian (D4):** Wali Kelas, Admin, dan Operator **tidak terikat** time-window untuk melakukan **revisi** Presensi Siswa.

* * *

## 6. Import &amp; Export Excel

### 6.1 Aturan Umum

- **Template:** Tersedia untuk diunduh di halaman masing-masing modul.
- **Stop-on-error + Atomic Transaction:** Jika ada satu baris error, seluruh proses **berhenti dan rollback**. Tidak ada baris yang tersimpan.
- **Hak Akses Import/Export:** Hanya Admin dan Operator.
- **Export:** Tabel data polos dengan judul laporan di baris atas (tanpa kop surat/logo). Export **wajib** mengikuti filter yang aktif di layar.

### 6.2 Template Import

**Template Import Guru:**

```
NIP                 | NAMA LENGKAP & GELAR           | JENIS KELAMIN (L/P)
198501012011011001  | Ahmad Fauzi, S.Pd.I            | L
```

**Template Import Pegawai:**

```
NIP                 | NAMA LENGKAP        | JENIS KELAMIN (L/P) | JABATAN
198501012011012003  | Siti Aminah, S.E.   | P                   | Tenaga Administrasi
```

**Template Import Siswa (NIK = 16 digit):**

```
NIK              | NISN        | NAMA LENGKAP    | JENIS KELAMIN | TEMPAT LAHIR | TANGGAL LAHIR | ALAMAT
3510123412341234 | 9876543210  | Ahmad Fauzi     | L             | Jombang      | 2008-01-15    | Jl. Merdeka No. 10
```

**Template Import Jadwal Guru:**

```
NIP_GURU            | NAMA_KELAS | KODE_MAPEL | HARI   | JAM_MULAI | JAM_SELESAI | SESI
196808212003122001  | 7-A        | MTK        | Senin  | 07:30     | 09:00       | Sesi Awal
```

### 6.3 Filter pada Halaman List

- **Guru:** Nama, NIP, Jenis Kelamin, Status Kepegawaian.
- **Pegawai:** Nama, NIP, Jenis Kelamin, Jabatan.
- **Siswa:** Nama, NIK, NISN, Kelas (dropdown), Status Aktif.
- **Kelas:** Tingkat (7/8/9), Tahun Ajaran.
- **Jadwal:** Guru (dropdown), Kelas (dropdown dinamis berdasarkan guru), Hari.
- **Semua filter menggunakan DataTables server-side/client-side hybrid sesuai kebutuhan.**

* * *

## 7. Service Layer (Business Logic)

### 7.1 `KelasService`

- **naikKelas($id\_kelas\_asal, $id\_kelas\_tujuan, $id\_tahun\_baru, $daftar\_siswa\_terpilih):** Memproses kenaikan kelas dengan checklist (A6).
- **luluskan($id\_kelas, $daftar\_siswa\_terpilih):** Memproses kelulusan.
- **mutasiSiswa($id\_siswa, $status\_baru, $keterangan):** Memproses mutasi (Pindah/Keluar) dan mencatat histori (A4).

### 7.2 `JadwalGuruService`

- **importJadwal($file\_excel):** Memproses import jadwal dengan validasi bentrok, atomic transaction, dan Query Builder.
- **validateBentrok($data):** Mengecek overlap jam untuk guru dan kelas.
- **setStatusJadwal($id\_tahun):** Menonaktifkan jadwal lama saat tahun ajaran baru.

### 7.3 `MappingWaliService`

- **assign($id\_guru, $id\_kelas, $id\_tahun):** Meng-assign wali kelas dengan mekanisme restore (A2).
- **isWaliAktif($id\_guru, $id\_tahun):** Mengecek apakah guru sedang menjadi wali aktif.
- **getKelasDiampu($id\_guru, $id\_tahun):** Mengambil daftar kelas yang diampu (untuk scope KELAS\_DIAMPU).

* * *

## 8. Catatan Penting untuk Developer

- **NIK:** 16 digit, wajib validasi format saat input dan import. NIK **tidak** ditampilkan penuh di halaman verifikasi publik kartu.
- **NIP:** Tidak boleh sama antara `guru` dan `pegawai`.
- **Import Jadwal:** Gunakan **Query Builder**, bukan raw SQL string concat.
- **Atomic Transaction:** Semua import (Guru, Pegawai, Siswa, Jadwal) wajib menggunakan transaction.
- **Restore Wali Kelas (A2):** Jangan pernah `INSERT` baru untuk assign ulang guru yang sudah pernah menjadi wali. Gunakan `UPDATE` dengan `deleted_at = NULL`.
- **Kenaikan Kelas (A6):** Gunakan checklist dengan default semua terpilih, bukan hardcode "semua siswa".
- **Histori Siswa (A4):** Setiap perubahan status/kelas siswa wajib tercatat di `riwayat_siswa`.
- **Upload Foto (B7):** Wajib re-encode file PNG untuk menghilangkan metadata berbahaya.
- **Akses Wali Kelas:** Hanya biodata dan foto yang dapat diedit. Mutasi, kenaikan kelas, dan import/export tetap eksklusif Admin/Operator.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Master Data Final
