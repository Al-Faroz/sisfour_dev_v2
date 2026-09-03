# 📊 Dashboard, Settings &amp; Backup — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini mengatur tiga komponen penting: **Dashboard** yang menampilkan widget sesuai peran pengguna, **Settings** yang mengelola konfigurasi sistem, serta **Backup &amp; Log** untuk pemeliharaan dan keamanan data.

* * *

## Bagian 1: Dashboard

### 1.1 Dashboard Admin &amp; Operator

- **Widget:**
  
  - **Total Siswa Aktif:** Menghitung siswa dengan `status_aktif = 'Aktif'` dan `deleted_at IS NULL`.
  - **Total Guru:** Menghitung guru dengan `deleted_at IS NULL`.
  - **Total Kelas:** Menghitung kelas dengan `deleted_at IS NULL`.
  - **Aktivitas Terakhir:** 5–10 entri terakhir dari `log_activity`.
- **Hak Akses:** Admin dan Operator.

### 1.2 Dashboard Pimpinan (6 Widget)

- **Kelas Belum Presensi Hari Ini:** Menghitung kelas yang belum mengisi Presensi Siswa (Sesi Awal) pada hari berjalan.
- **Ringkasan EWS Radar:** Jumlah siswa dengan ≥ 3 Alpha dalam 14 hari terakhir.
- **Kasus BK Bulan Ini:** Jumlah catatan kasus pada bulan berjalan.
- **Tren Presensi:** Grafik/gambaran visual tingkat kehadiran mingguan.
- **Guru Belum Input Jurnal:** Menghitung guru yang memiliki jadwal aktif hari itu tetapi belum mengisi Presensi Mengajar.
- **Top 20 Poin Pelanggaran:** 20 siswa dengan akumulasi poin pelanggaran tertinggi.

**Catatan:** Seluruh widget Dashboard Pimpinan hanya menampilkan data, tidak ada aksi/input.

### 1.3 Dashboard BK (4 Widget)

- **Kasus Bulan Ini:** Jumlah catatan kasus pada bulan berjalan.
- **Top 20 Poin Pelanggaran:** 20 siswa dengan poin tertinggi.
- **EWS Radar:** Siswa dengan ≥ 3 Alpha dalam 14 hari.
- **Prestasi Terbaru:** 5 prestasi siswa terakhir yang dicatat.

### 1.4 Dashboard Guru &amp; Wali Kelas

- **Jadwal Hari Ini:** Menampilkan jadwal hari itu beserta status presensi (Siswa &amp; Mengajar) yang perlu diisi.
- **Riwayat Jurnal Terakhir:** 5 entri jurnal terakhir yang diinput oleh guru tersebut.
- **Ringkasan Kelas Diampu (Wali Kelas):** Menampilkan daftar siswa di kelas yang diampu (jika guru adalah wali kelas).

### 1.5 Dashboard Siswa

- **Rekap Presensi Diri Sendiri:** Total Hadir/Sakit/Izin/Alpha pada bulan berjalan.
- **Riwayat Prestasi &amp; Pelanggaran:** Daftar prestasi dan catatan kasus milik siswa tersebut.

* * *

## Bagian 2: Settings

### 2.1 Hak Akses Settings

- **Seluruh modul Settings hanya dapat diakses oleh role Admin.**
- Operator, Pimpinan, BK, Guru, Wali Kelas, dan Siswa **tidak** memiliki akses ke menu Settings.

### 2.2 Manajemen User

- **Fungsi:** CRUD user, reset password, menentukan sumber identitas (Guru/Pegawai) untuk Operator &amp; Pimpinan.
- **Reset Password:**
  
  - **Siswa:** Direset ke NISN.
  - **Guru:** Direset ke NIP.
  - **Pegawai (termasuk Operator/Pimpinan berbasis Pegawai):** Direset ke NIP.
  - **Admin:** Direset ke username.
- **Auto-create (B1):**
  
  - Saat **Guru** dibuat → otomatis role = 'guru'.
  - Saat **Pegawai** dibuat → role = **NULL** (tidak ditentukan). Admin harus menentukan role secara manual.

### 2.3 Menu &amp; Role

- **Fungsi:** Assign menu tampil/tidak tampil untuk setiap role (mengupdate tabel `role_menus`).
- **Catatan:** Permission **tidak** dapat diedit dari UI. Permission dan scope dikunci di kode/seed untuk menjaga keamanan.

### 2.4 Setting Sistem (F1)

Tabel `setting_sistem` memiliki kolom `type` untuk validasi tipe data (string, int, float, boolean). Admin dapat mengubah nilai-nilai berikut:

| Setting Key               | Deskripsi                          | Default                             | Type    |
|---------------------------|------------------------------------|-------------------------------------|---------|
| `koordinat_lat`           | Koordinat latitude sekolah         | -7.533383                           | string  |
| `koordinat_lng`           | Koordinat longitude sekolah        | 112.217607                          | string  |
| `radius_geofencing`       | Radius geofencing dalam meter (D2) | 500                                 | int     |
| `geofencing_aktif`        | Toggle geofencing ON/OFF (D3)      | 1                                   | boolean |
| `nama_sekolah`            | Nama sekolah (untuk branding)      | MTsN 4 Jombang                      | string  |
| `alamat_sekolah`          | Alamat sekolah                     | Jl. Pendidikan No. 1, Jombang       | string  |
| `logo_sekolah`            | Path logo sekolah                  | NULL                                | string  |
| `icon_sekolah`            | Path icon sekolah                  | NULL                                | string  |
| `background_kta_depan`    | Path background kartu (depan)      | NULL                                | string  |
| `background_kta_belakang` | Path background kartu (belakang)   | NULL                                | string  |
| `maintenance_mode`        | Toggle maintenance ON/OFF          | 0                                   | boolean |
| `maintenance_message`     | Pesan maintenance yang ditampilkan | Sistem sedang dalam pemeliharaan... | string  |

**Catatan D3:** Toggle `geofencing_aktif` langsung berlaku (ON/OFF) tanpa konfirmasi tambahan. Perubahan dicatat di `log_activity`.

### 2.5 Maintenance Mode

- **Toggle:** Admin dapat mengaktifkan/menonaktifkan dari Setting Sistem.
- **Efek:** Semua role **kecuali Admin** diarahkan ke halaman maintenance. Admin tetap dapat login dan mengakses aplikasi untuk mematikan mode maintenance.
- **Pesan:** Admin dapat mengedit pesan maintenance (teks bebas).

* * *

## Bagian 3: Backup &amp; Log Activity

### 3.1 Hak Akses

- **Hanya Admin** yang memiliki akses ke modul Backup dan Log Activity.
- Operator **tidak** memiliki akses ke modul ini.

### 3.2 Backup Database (B8)

- **Metode:** SQL dump murni menggunakan **PHP tanpa exec()/shell\_exec()** (karena hosting mematikan fungsi tersebut).
- **Implementasi:** Sistem melakukan `SHOW CREATE TABLE` dan `SELECT *` per tabel, kemudian menyusun ulang menjadi file .sql.
- **Penyimpanan:** File .sql disimpan di `writable/backups/` dengan format nama `backup_YYYYMMDD_HHMMSS.sql`.
- **Keamanan (B8):** Folder `writable/backups/` diproteksi dengan `.htaccess` (blokir akses HTTP). Tidak ada enkripsi file (cukup proteksi folder).
- **Download:** Admin dapat mengunduh file backup melalui halaman Backup.
- **Hapus:** Admin dapat menghapus file backup lama jika diperlukan.

### 3.3 Log Activity (C4)

- **Deskripsi:** Mencatat aksi-aksi penting dalam sistem (create/update/delete pada data master, login/logout, perubahan setting).
- **Bukan:** Tidak mencatat setiap klik/request (hanya aksi penting untuk menjaga ukuran tabel).
- **Field:** `id_user`, `aksi`, `modul`, `keterangan`, `waktu`.
- **Retensi (C4):** Data log disimpan selama **3 tahun**. Data yang lebih dari 3 tahun otomatis dihapus oleh sistem (cron/command).
- **Filter:** Halaman Log Activity memiliki filter berdasarkan tanggal, user, dan modul.
- **Export:** Admin dapat mengekspor log ke Excel.

* * *

## 4. Catatan Penting untuk Developer

- **Dashboard:** Setiap widget harus mengikuti scope RBAC masing-masing role (misal Guru hanya melihat data dirinya sendiri).
- **Settings Tipe Data (F1):** Validasi nilai setting harus menggunakan kolom `type` di tabel `setting_sistem`. Misal, `radius_geofencing` harus integer, `geofencing_aktif` harus boolean.
- **Geofencing Toggle (D3):** Saat Admin mengubah `geofencing_aktif`, sistem wajib mencatat ke `log_activity` dengan aksi "Geofencing OFF/ON".
- **Backup (B8):** Dilarang menggunakan `exec('mysqldump ...')`. Wajib menggunakan implementasi PHP murni untuk kompatibilitas hosting.
- **Log Retensi (C4):** Buat command/script yang berjalan terjadwal (misal daily cron) untuk menghapus log yang berusia &gt; 3 tahun.
- **Maintenance Mode:** Filter `MaintenanceFilter` harus mengecualikan role Admin.
- **Upload Background KTA:** Format PNG, max 2MB, re-encode, disimpan di `uploads/kartu_pelajar/background_depan/` dan `.../background_belakang/`.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Dashboard, Settings &amp; Backup Final
