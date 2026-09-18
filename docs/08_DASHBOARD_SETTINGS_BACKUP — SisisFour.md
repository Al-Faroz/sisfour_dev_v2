# Dashboard, Settings, Maintenance, Backup & Log — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026

## 1. Dashboard by Experience

```text
Admin       system/master/operational overview
Operator    administrasi operasional sesuai permission
Pimpinan    monitoring exception/decision
BK          Catatan Pelanggaran/Konseling/EWS/Tindak Lanjut/Prestasi
Guru        tugas mengajar hari ini
Guru+Wali   tugas Guru + kondisi kelas wali
Siswa       self-service data diri
```

Wali Kelas tetap context Guru, bukan role.

## 2. Mobile Dashboard Rule

Pimpinan/BK/Guru/Wali/Siswa:

```text
4 KPI = grid 2×2
spacing compact
quick action 2×2 bila relevan
recent/top list 3–5 item
no horizontal operational table scroll
name-first identity
```

## 3. Pimpinan

Prioritas:

```text
kelas belum Presensi
jadwal belum Jurnal
EWS
Catatan Pelanggaran
trend singkat
```

Contract:

```text
Catatan Pelanggaran = jumlah/exception, bukan ranking poin
Konseling BK bukan widget/source data Pimpinan
widget tanpa permission = tidak tersedia, bukan 0 palsu
Dashboard = current-state Tahun Ajaran aktif
```

G3.5 Dashboard Pimpinan:

```text
KPI 2×2
- Kelas Belum Presensi
- Jadwal Belum Jurnal
- EWS Alpha 14 Hari
- Catatan Pelanggaran Bulan Ini

Quick Action permission-aware
- Rekap        -> presensi_siswa.view
- Jurnal       -> laporan_jurnal.view
- EWS          -> ews_radar.view
- Laporan      -> laporan_matrix.view

Monitoring
- Tren Presensi 7 Hari / Sesi Awal
- EWS maksimal 5 + Lihat Semua
- Prestasi Terbaru maksimal 5 + Lihat Semua
- Ringkasan Master
```

Catatan Pelanggaran Bulan Ini dan Prestasi Terbaru wajib dibatasi `id_tahun = Tahun Ajaran aktif`. Quick Action hanya shortcut ke endpoint existing dan tidak menambah capability. Konseling tetap tidak dibentuk untuk Pimpinan.

## 4. BK — G3.4 Dashboard/Workflow

Foundation G3.3.1 sudah final dan merged melalui PR #9. G3.4 memakai foundation tersebut tanpa menambah schema/permission/route baru.

Dashboard BK adalah **current-state Tahun Ajaran aktif**. Dashboard tidak memakai selector Tahun Ajaran; selector historis tetap berada pada listing periodik Catatan Pelanggaran, Konseling, dan Prestasi.

KPI canonical:

```text
Konseling Proses
Pelanggaran Bulan Ini
EWS Alpha 14 Hari
Prestasi Bulan Ini
```

Semua KPI periodik BK dibatasi `id_tahun = Tahun Ajaran aktif`. Widget tanpa permission tidak dibentuk sebagai angka 0 palsu.

Quick action permission-aware:

```text
Konseling BK
Catatan Pelanggaran
EWS
Prestasi
```

Quick action hanya shortcut ke capability yang sudah sah; ia tidak menambah permission.

Priority content:

```text
1. Konseling Proses / jadwal follow-up terdekat
2. Catatan Pelanggaran terbaru/berat
3. Tindak Lanjut yang perlu perhatian melalui tanggal berikutnya tersimpan
4. EWS
5. Prestasi ringkas
```

Jadwal follow-up terdekat memakai source:

```text
jika sudah ada Tindak Lanjut Konseling
→ tanggal_berikutnya entry tindak lanjut terbaru

jika belum ada Tindak Lanjut Konseling
→ tanggal_berikutnya pada hasil pertemuan awal parent
```

Tanggal kosong tidak dibuat menjadi jadwal. G3.4 tidak menciptakan SLA, deadline baru, label overdue, atau kesimpulan "terlambat".

Dashboard bukan laporan lengkap:

```text
maksimal 5 item per recent/top section
+ Lihat Semua
```

UI BK memakai adaptive card/list dan tidak menggunakan horizontal operational table pada mobile.

G3.4 tidak boleh menghidupkan poin, membuka Konseling ke role lain, atau mengabaikan filter Tahun Ajaran pada surface historis.

## 5. Guru

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
Quick Action
```

G3.3 contract tetap: KPI 2×2, task summary server, action permission-aware, mobile card/list.

## 6. Guru + Wali

Wali mewarisi Guru + context kelas:

```text
kelas wali + jumlah siswa
H/S/I/A Sesi Awal
EWS kelas
ketidakhadiran terbaru
quick link contextual
```

Catatan Pelanggaran dapat muncul bila permission sah. Konseling tidak tampil pada dashboard/quick link dan direct URL tetap ditolak.

## 7. Siswa — G3.6 Dashboard Self-Service

Dashboard Siswa adalah current-state Tahun Ajaran aktif dengan scope `DIRI_SENDIRI`.

```text
Header
- Tahun Ajaran aktif sebagai context, bukan selector
- Data Saya

Status
- Presensi Sesi Awal hari ini

KPI 2×2
- Hadir
- Sakit
- Izin
- Alpha

Quick Action permission-aware
- Presensi Saya
- Kartu
- Prestasi
- Profil

Recent/self-service
- Sakit/Izin/Alpha maksimal 5 + Lihat Rekap
- Kartu Pelajar diri sendiri
- Prestasi maksimal 5 + Lihat Semua
- Catatan Pelanggaran maksimal 5 + Lihat Semua
```

Presensi, Prestasi, dan Catatan Pelanggaran periodik dibatasi `id_tahun = Tahun Ajaran aktif`. Tidak adanya Tahun Ajaran aktif harus menjadi state unavailable, bukan KPI 0 palsu. No row Sesi Awal pada periode valid = data belum tercatat, bukan Hadir.

Quick Action hanya shortcut ke capability existing. Kartu/Profile tetap memakai self-scope/identity server-side. Konseling tidak dibentuk/ditampilkan/dikirim. Pelanggaran tanpa poin.

## 8. Boundary Konseling

```text
Admin       operasional + settings sesuai permission
Operator    operasional view/manage/export; tanpa settings
BK          operasional + settings sesuai permission
Pimpinan    tidak menerima detail/widget
Guru/Wali   tidak menerima detail/widget/quick link
Siswa       tidak menerima detail/widget
```

Authorization Route/Filter + Service; menu bukan security boundary.

## 9. Settings User / Menu / Sistem

- `settings_user.manage`: account/role/identity/security state.
- `settings_menu.manage`: visibility/navigation; bukan authorization.
- `settings_sistem.manage`: settings umum.

Pengaturan Form Konseling:

```text
permission = bk_konseling.settings
storage    = setting_sistem / bk_konseling_form_options
access     = Admin + BK
```

Operator tidak mendapat Settings Konseling.

## 10. Historical Rencana Konseling

Perubahan daftar Rencana tidak boleh merusak nilai historis parent maupun Tindak Lanjut Konseling.

```text
Rencana X tersimpan
→ X dihapus dari Settings
→ X tetap tampil sebagai X (tersimpan) pada record terkait
→ boleh dipertahankan / diganti opsi aktif
→ tidak menjadi pilihan global lagi
```

Parent invariant dan follow-up tetap wajib diregresikan bila source terkait berubah.

## 11. Maintenance / Backup / Log

Maintenance:

```text
Admin efektif → recovery policy
Non-Admin Web → 503 HTML
AJAX/API      → 503 JSON
```

Backup: `backup.manage`, storage `writable/backups/`.

Log Activity: `log_activity.view`; tidak menyimpan password/hash/token/cookie/session id/secret.

## 12. Period Context pada Dashboard vs Listing

Dashboard current-state memakai Tahun Ajaran aktif sesuai business context. Listing/history periodik wajib menyediakan selector Tahun Ajaran sesuai global UI contract.

Jangan menambah selector Tahun Ajaran pada dashboard hanya untuk kosmetik bila seluruh KPI memang current-state aktif.

## 13. Gate Status

```text
G3.3 Dashboard Guru/Wali                     CLOSED / MERGED — PR #8
G3.3.1 Fondasi BK/Konseling                  CLOSED / MERGED — PR #9
G3.4 Dashboard/Workflow BK                   CLOSED / MERGED — PR #10
G3.5 Dashboard Pimpinan                      CLOSED / MERGED — PR #11
G3.5 closure gate                            PASS
main baseline G3.6                           6bdfc276ae07b6e70065ee7fae9e6ef51c3299ce
G3.6 source Dashboard Siswa                  IMPLEMENTED ON FEATURE BRANCH
G3.6 static gate                             PENDING
G3.6 local runtime/UAT                       PENDING
G3.6 self-scope/privacy regression           PENDING
G3.6 cross-role regression                   PENDING
G3.6 hosting deployment/re-smoke             NOT STARTED
G3.6 PR                                      #12 DRAFT / NOT MERGED
```

## 14. Phase Boundary

```text
G2      dashboard/settings stabilization
G3.3    Dashboard Guru/Wali
G3.3.1  fondasi BK/Konseling + period/follow-up
G3.4    Dashboard/Workflow BK memakai foundation final
G3.5    Dashboard Pimpinan readonly/monitoring
G3.6    Dashboard Siswa self-service
G3.6A+  domain berikutnya
G4      Cordova integration
```
