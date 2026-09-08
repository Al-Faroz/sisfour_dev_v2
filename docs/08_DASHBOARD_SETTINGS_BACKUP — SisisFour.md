# 📊 Dashboard, Settings, Backup & Log Activity — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026  
**Status:** FINAL Dashboard per Effective Role

---

# BAGIAN A — DASHBOARD

# 1. Prinsip

Dashboard adalah agregasi data, bukan security boundary.

Setiap widget wajib:

- mempunyai permission sumber;
- menerapkan scope server-side;
- query database-first;
- tidak mengirim dataset global untuk difilter frontend;
- tidak menyediakan shortcut ke route yang tidak usable;
- mempunyai fallback data kosong.

Dashboard menggunakan effective roles, bukan hanya primary role session.

Priority dashboard:

```text
Admin > Operator > Pimpinan > Guru/Wali > BK > Siswa
```

Jika effective role Guru dan mapping Wali aktif, tampilkan Dashboard Guru + widget Wali contextual.

---

# 2. Arsitektur

Controller:

```text
Dashboard.php
```

harus tipis.

Agregasi:

```text
DashboardService.php
```

Alur:

```text
user_id
→ AuthService::getUserRoles()
→ resolve effective dashboard role
→ resolve contextual Wali
→ permission-aware widgets
→ View
```

---

# 3. Permission Dashboard

Permission dasar:

```text
dashboard.view
```

Widget tambahan mengikuti permission modul sumber:

```text
EWS       → ews_radar.view
BK        → bk_kasus.view
Prestasi  → prestasi.view
Kartu     → kartu_pelajar.view
Log       → log_activity.view
```

`dashboard.view` tidak memberi seluruh widget otomatis.

---

# 4. Dashboard Admin

Scope operasional `SEMUA`.

Widget final:

```text
Siswa Aktif
Guru
Pegawai
Kelas Aktif
Tahun Ajaran Aktif
Kelas Wajib Presensi Hari Ini
Kelas Sudah Presensi
Kelas Belum Presensi
Ringkasan H/S/I/A Sesi Awal
Jadwal Wajib Jurnal
Jadwal Sudah Jurnal
Jadwal Belum Jurnal
EWS 14 hari
Kasus BK bulan ini
Prestasi bulan ini
Kartu aktif
Status Geofence
Status Maintenance
Tren Presensi 7 hari
Aktivitas terakhir
```

Admin bootstrap tetap valid tanpa relasi Guru/Pegawai.

---

# 5. Dashboard Operator

Fokus operasional, bukan Settings/Backup.

Prioritas:

```text
Kelas Belum Presensi
Jadwal Belum Jurnal
EWS
Presensi Hari Ini
Siswa/Guru/Pegawai/Kelas
BK
Prestasi
Kartu
Tren Presensi
Aktivitas terbaru bila log_activity.view diberikan
```

Operator tidak otomatis mendapat Settings/Backup.

---

# 6. Dashboard Pimpinan

Readonly.

Widget:

```text
Kelas Belum Presensi
Jadwal Belum Jurnal
EWS Radar
Kasus BK bulan ini
Tren Presensi
EWS Alpha teratas
Top 20 poin pelanggaran
Prestasi terbaru
Ringkasan Kartu
Ringkasan Master Data
```

Dashboard Pimpinan tidak menyediakan mutation Presensi/BK/Laporan.

Jurnal diri, bila Pimpinan mempunyai `id_guru` + jadwal, tetap diatur permission `presensi_mengajar.input = DIRI_SENDIRI` pada menu/route Jurnal, bukan mutation dari widget supervisi.

---

# 7. Dashboard BK

Widget:

```text
Kasus bulan ini
Pelanggaran berat bulan ini
EWS 14 hari
Prestasi bulan ini
EWS Alpha teratas
Top 20 poin pelanggaran
Kasus terbaru
Prestasi terbaru
```

BK tidak memperoleh Master Siswa global hanya karena dashboard menampilkan nama siswa dari kasus/EWS.

---

# 8. Dashboard Guru Biasa

Dashboard bersifat task-oriented.

Summary:

```text
Jumlah Jadwal Hari Ini
Presensi Siswa Perlu Diisi
Jurnal Perlu Diisi
Jurnal Selesai
```

Setiap Jadwal menampilkan:

```text
Jam
Kelas
Mapel
Sesi
Status Presensi
Status Jurnal
```

Status Presensi:

```text
Non Sesi       → tidak berlaku
Sudah Diinput  → existing business key
Belum Waktunya → sebelum jam_mulai
Isi Presensi   → window valid
Waktu Habis    → setelah jam_selesai + 15
```

Link Presensi wajib membawa:

```text
id_kelas
tanggal
sesi
```

Status Jurnal:

```text
Sudah
Belum Waktunya
Isi Jurnal
Terlewat
```

Guru juga melihat riwayat Jurnal diri dan Profile.

Tidak ada Matrix/Export/BK global/Master Siswa global.

---

# 9. Dashboard Wali

Dashboard Wali = Dashboard Guru + contextual widget.

Widget Wali:

```text
Kelas Wali
Jumlah siswa aktif
Ringkasan H/S/I/A hari ini Sesi Awal
EWS kelas
Top EWS kelas
Sakit/Izin/Alpha terbaru
Quick link contextual
```

Quick link hanya tampil jika permission valid:

```text
Presensi Kelas
Rekap Presensi
Matrix Kelas
Export Kelas
Data Siswa
Catatan Kasus readonly
Prestasi
Kartu Pelajar
```

Jika mapping Wali dinonaktifkan, seluruh widget/quick link Wali hilang tanpa mengubah role.

Pada Jadwal yang juga merupakan kelas Wali, fallback Wali setelah time-window berakhir dapat ditandai sebagai `Isi sebagai Wali` sesuai aturan Presensi.

---

# 10. Dashboard Siswa

Sederhana dan seluruh data `DIRI_SENDIRI`.

Ringkasan Presensi bulan ini:

```text
Hadir
Sakit
Izin
Alpha
```

Detail terbaru fokus:

```text
Sakit
Izin
Alpha
```

Tambahan:

```text
Prestasi milik sendiri
Kartu Pelajar milik sendiri
Profile sendiri
Catatan Kasus milik sendiri
```

**Keputusan final:** Catatan Kasus Siswa tetap ditampilkan dengan `bk_kasus.view = DIRI_SENDIRI`.

Query tidak boleh menerima target siswa bebas.

---

# 11. Kelas Belum Presensi

Jangan menghitung:

```text
seluruh kelas master - kelas yang sudah presensi
```

Canonical:

```text
jadwal_guru Aktif
WHERE hari = hari ini
AND sesi = Sesi Awal
GROUP BY id_kelas
```

Hanya kelas tersebut yang mempunyai kewajiban Presensi Sesi Awal hari ini.

Bandingkan terhadap business key Presensi hari ini.

---

# 12. Jadwal Belum Jurnal

Hitung **per id_jadwal**, bukan Guru unik.

Sumber:

```text
jadwal_guru Aktif hari ini
LEFT JOIN presensi_mengajar
  ON id_jadwal
 AND tanggal = hari ini
```

Termasuk:

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

Jika Guru mempunyai 4 jadwal dan baru mengisi 1 Jurnal, masih ada 3 Jadwal Belum Jurnal.

---

# 13. EWS

EWS Presensi:

```text
Alpha
Sesi Awal
>= 3 Alpha
14 hari inklusif
```

Periode tepat:

```text
hari ini + 13 hari sebelumnya
```

Bukan `-14 days` yang menghasilkan 15 tanggal bila kedua ujung inklusif.

---

# 14. Tren Presensi

Hanya:

```text
Sesi Awal
```

Gunakan satu query agregasi periode:

```sql
SELECT tanggal,
       COUNT(*) total,
       SUM(status='Hadir') hadir
FROM presensi
WHERE id_tahun = ?
  AND sesi = 'Sesi Awal'
  AND tanggal BETWEEN ? AND ?
GROUP BY tanggal;
```

PHP hanya mengisi tanggal kosong. Jangan menjalankan dua query per hari.

---

# 15. Performance Dashboard

Gunakan:

```text
COUNT
SUM
GROUP BY
HAVING
ORDER BY
LIMIT
WHERE periode
```

Top list maksimal 20 row.

Dilarang mengambil seluruh tabel lalu sort/count di PHP.

Schedule Guru hari ini boleh dihitung statusnya di PHP setelah query sudah dibatasi satu Guru + satu hari.

---

# BAGIAN B — SETTINGS

# 16. Hak Akses Settings

Default:

```text
Admin
```

Permission:

```text
settings_user.manage
settings_menu.manage
settings_sistem.manage
```

Role lain tidak memperoleh Settings kecuali permission eksplisit diberikan.

---

# 17. User Management

Mencakup:

```text
create/update user
aktif/nonaktif
reset password
primary role
secondary roles
relasi Guru/Pegawai/Siswa
auth_version
```

Wali tidak boleh dibuat role.

Role NULL valid untuk Pegawai.

Reset password wajib hash dan tidak menampilkan hash/token.

Provisioning BK canonical: akun berasal dari Master Pegawai/Guru lalu Admin menetapkan role `bk` melalui User Management. Untuk Guru yang benar-benar merangkap, secondary role `guru` dapat dipertahankan. Multi-role Guru+Pimpinan juga didukung oleh mekanisme yang sama.

---

# 18. Menu & Role

Admin mengelola `role_menus`, tetapi konfigurasi dianggap tidak konsisten bila menu diberikan ke role tanpa permission yang membuat route tujuan usable.

Menu bukan authorization boundary.

---

# 19. Setting Sistem

Key minimum:

```text
latitude_sekolah
longitude_sekolah
radius_geofencing
geofencing_aktif
nama_sekolah
alamat_sekolah
logo_sekolah
icon_sekolah
background_kta_depan
background_kta_belakang
maintenance_mode
maintenance_message
```

Validasi:

```text
latitude  -90..90
longitude -180..180
radius    >0
```

Asset branding harus image valid, path aman dan tidak executable.

Khusus Kartu Pelajar, Settings menyediakan upload Admin untuk `background_kta_depan` dan `background_kta_belakang`. Kedua asset harus di-re-encode dan dinormalisasi ke 1011×638 px. Depan menjadi artwork dasar overlay data siswa; belakang dicetak statis tanpa overlay data siswa.

Perubahan setting harus clear cache relevan dan log activity.

---

# BAGIAN C — MAINTENANCE

# 20. Behavior

Saat Maintenance ON:

```text
Admin → tetap dapat login/masuk
Role lain → maintenance page / API 503
```

MaintenanceFilter tidak boleh mempunyai wildcard pengecualian terlalu luas.

---

# BAGIAN D — BACKUP

# 21. Hak Akses

Default:

```text
Admin
backup.manage
```

Operator tidak otomatis mendapat Backup.

Backup menggunakan PHP murni, tidak bergantung `exec/shell_exec/system/passthru`.

Lokasi:

```text
writable/backups/
```

Nama:

```text
backup_YYYYMMDD_HHMMSS.sql
```

Download/delete wajib auth + permission + filename whitelist dan tidak boleh menerima arbitrary path.

Restore UI bila dibuat harus Admin-only, validasi kuat, konfirmasi eksplisit dan disarankan maintenance mode.

---

# BAGIAN E — LOG ACTIVITY

# 22. Schema

Canonical:

```text
id
id_user
aksi
modul
keterangan
waktu
```

Jangan memakai schema alternatif tanpa perubahan database resmi.

---

# 23. Hak Akses Log

```text
Admin    → Ya
Operator → Ya
Pimpinan → Tidak
BK       → Tidak
Guru     → Tidak
Wali     → Tidak
Siswa    → Tidak
```

Permission:

```text
log_activity.view
```

Dashboard Operator boleh menampilkan aktivitas terakhir hanya bila permission ini benar-benar tersedia.

---

# 24. Event Audit Minimum

```text
Login/Logout
User/Role/Menu
Master Data
Mutasi/Kenaikan/Kelulusan
Presensi/Revisi
Jurnal/Revisi
Export
BK/Prestasi
Kartu
Settings
Backup
Maintenance
```

Log tidak boleh berisi password/hash/token/cookie atau koordinat pribadi tanpa kebutuhan audit.

---

# 25. Checkpoint Dashboard

Wajib diuji:

```text
Admin
Operator
Pimpinan
BK
Guru
Wali
Siswa
multi-role Guru+Operator
```

Pastikan:

- effective dashboard benar;
- Wali contextual berubah otomatis;
- widget tunduk permission;
- shortcut tidak menuju route terlarang;
- kelas belum Presensi hanya kelas wajib;
- Jadwal belum Jurnal dihitung per jadwal;
- EWS tepat 14 hari;
- Siswa hanya data diri termasuk Catatan Kasus diri;
- query agregasi database-first.
