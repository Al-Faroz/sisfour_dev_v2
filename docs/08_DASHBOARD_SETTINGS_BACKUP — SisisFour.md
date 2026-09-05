# 📊 Dashboard, Settings & Backup — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini mengatur Dashboard per konteks user, Settings, Backup, dan Log Activity.

---

# Bagian 1 — Dashboard

## 1.1 Prinsip

Dashboard bukan sekadar tampilan ringkasan. Setiap widget harus:

- mengikuti permission,
- mengikuti scope,
- tidak membocorkan data,
- tidak memberi akses bypass ke endpoint.

Role yang tidak memiliki permission tidak boleh mendapatkan widget terkait.

---

# 2. Dashboard Admin

Widget utama:

- Total Siswa Aktif
- Total Guru
- Total Kelas
- Aktivitas Terakhir
- Ringkasan Presensi
- Ringkasan BK
- Ringkasan Prestasi
- Status sistem

Admin memiliki akses data penuh.

---

# 3. Dashboard Operator

Operator memiliki dashboard operasional.

Widget dapat mencakup:

- Total Siswa
- Total Guru
- Total Kelas
- Presensi hari ini
- Kelas belum presensi
- Aktivitas terakhir
- Ringkasan BK
- Ringkasan Prestasi
- Status kartu

Semua data mengikuti kewenangan administratif Operator.

---

# 4. Dashboard Pimpinan

Dashboard Pimpinan bersifat **read-only**.

Widget:

1. Kelas belum presensi hari ini.
2. EWS Radar.
3. Kasus BK bulan ini.
4. Tren presensi.
5. Guru belum input jurnal.
6. Top 20 poin pelanggaran.
7. Ringkasan prestasi.

Tidak boleh ada tombol create/update/delete pada dashboard Pimpinan.

---

# 5. Dashboard BK

Widget:

- Kasus bulan ini.
- Top 20 poin pelanggaran.
- EWS Radar.
- Prestasi terbaru.

BK dapat membuka detail BK dan Prestasi sesuai permission.

---

# 6. Dashboard Guru Biasa

Guru Biasa mendapatkan dashboard untuk pekerjaan mengajar.

Widget utama:

- Jadwal hari ini.
- Presensi Siswa yang dapat diinput hari ini.
- Presensi Mengajar/Jurnal yang harus diisi.
- Status input jurnal milik sendiri.

### Penting

Guru Biasa **tidak mendapatkan**:

- Matrix Presensi,
- Export Presensi,
- laporan presensi,
- BK,
- Prestasi,
- daftar master siswa.

Dashboard tidak boleh menyediakan link tersembunyi menuju modul-modul tersebut.

---

# 7. Dashboard Wali Kelas

Wali menggunakan Dashboard Guru + widget tambahan khusus konteks Wali.

Widget tambahan:

- Kelas yang diwalikan.
- Jumlah siswa kelas wali.
- Ringkasan H/S/I/A kelas wali.
- Siswa bermasalah/berisiko sesuai EWS.
- Akses cepat ke data siswa kelas wali.
- Akses laporan kelas wali.
- Akses kartu kelas wali.

Status Wali ditentukan dinamis dari:

```text
mapping_wali_kelas
```

Wali dapat tetap mengajar kelas lain sebagai Guru Biasa.

Pada kelas yang bukan kelas walinya, semua aturan Guru Biasa berlaku.

---

# 8. Dashboard Siswa

Dashboard Siswa dibuat sederhana.

## 8.1 Rekap Presensi Diri

Menampilkan jumlah:

```text
Hadir
Sakit
Izin
Alpha
```

## 8.2 Rincian Presensi

Saat kartu total presensi diklik:

```text
→ rincian presensi diri sendiri
```

Rincian hanya menampilkan:

```text
Sakit
Izin
Alpha
```

Status `Hadir` tidak perlu ditampilkan di tabel rincian agar tampilan sederhana.

Query wajib:

```text
id_siswa = session('id_siswa')
```

Tidak boleh menerima `id_siswa` bebas dari URL/request untuk menentukan data siswa.

## 8.3 Prestasi

Siswa hanya melihat prestasi miliknya sendiri.

## 8.4 Kartu

Siswa hanya melihat/preview/download kartu miliknya sendiri.

---

# Bagian 2 — Settings

## 9. Hak Akses Settings

Seluruh menu Settings hanya untuk:

```text
Admin
```

Operator, Pimpinan, BK, Guru, Wali, dan Siswa tidak memiliki akses.

---

# 10. Manajemen User

Fungsi:

- create,
- update,
- aktif/nonaktif,
- reset password,
- role,
- multi-role,
- relasi identitas.

### Aturan Admin/Operator

Akun Admin/Operator normal wajib terkait dengan:

```text
data_pegawai
```

atau identitas pegawai/guru yang valid.

### Exception

Admin awal yang dibuat saat instalasi awal boleh berdiri sendiri tanpa relasi pegawai.

---

# 11. Menu & Role

Admin dapat mengelola `role_menus`.

Namun:

- menu tidak boleh menyalahi permission,
- menu tidak boleh membuka endpoint yang menghasilkan 403 bagi role yang diberi menu,
- menu Wali bersifat contextual.

Karena Wali bukan role, menu Wali-only harus dihitung berdasarkan:

```text
role guru
+
AuthService::isWaliKelas()
+
permission
```

---

# 12. Setting Sistem

Setting utama:

| Key | Fungsi |
| --- | --- |
| `koordinat_lat` | Latitude sekolah |
| `koordinat_lng` | Longitude sekolah |
| `radius_geofencing` | Radius geofencing |
| `geofencing_aktif` | ON/OFF geofencing |
| `nama_sekolah` | Nama sekolah |
| `alamat_sekolah` | Alamat |
| `logo_sekolah` | Logo |
| `icon_sekolah` | Icon |
| `background_kta_depan` | Background kartu depan |
| `background_kta_belakang` | Background kartu belakang |
| `maintenance_mode` | Mode maintenance |
| `maintenance_message` | Pesan maintenance |

Semua perubahan dicatat ke Log Activity.

---

# Bagian 3 — Maintenance

Saat maintenance aktif:

```text
Admin → tetap dapat masuk
Role lain → diarahkan ke halaman maintenance
```

Admin harus tetap dapat mematikan maintenance.

---

# Bagian 4 — Backup

## 13. Hak Akses

```text
Admin → Full
Operator → Tidak
Pimpinan → Tidak
BK → Tidak
Guru → Tidak
Siswa → Tidak
```

## 14. Metode

Backup menggunakan PHP murni.

Dilarang bergantung pada:

```text
exec()
shell_exec()
system()
```

Backup menghasilkan file:

```text
backup_YYYYMMDD_HHMMSS.sql
```

Lokasi:

```text
writable/backups/
```

File backup tidak boleh dapat diakses langsung melalui HTTP.

---

# Bagian 5 — Log Activity

## 15. Hak Akses

**Hanya Admin dan Operator.**

Role berikut tidak boleh melihat Log Activity:

```text
Pimpinan
BK
Guru
Wali Kelas
Siswa
```

## 16. Isi Log

Minimal:

```text
id_user
role
ip_address
activity
details
created_at
```

Aksi penting yang harus dicatat:

- login/logout,
- perubahan user,
- reset password,
- perubahan master,
- mutasi siswa,
- perubahan presensi,
- export,
- generate/reissue/cetak kartu,
- perubahan settings,
- backup,
- perubahan role/menu.

---

# 17. Catatan Developer

1. Dashboard wajib menerapkan permission.
2. Widget bukan security boundary.
3. Semua endpoint dashboard harus tetap protected.
4. Siswa wajib di-isolasi dengan `id_siswa` miliknya.
5. Wali wajib di-isolasi ke kelas walinya.
6. Pimpinan selalu read-only, kecuali kewenangan khusus Kartu Pelajar yang memang diberikan.
7. Log Activity hanya Admin/Operator.
8. Menu yang tampil harus usable dan tidak boleh menghasilkan 403 normal saat diklik.

---

© 2026 SisisFour · MTsN 4 Jombang · Dashboard, Settings & Backup Final
