# UI/UX Role Experience — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Role experience:** Admin, Operator, Pimpinan, BK, Guru, Guru+Wali, Siswa

> Dokumen ini menetapkan hierarchy pengalaman pengguna per role/context. Ia tidak mengubah role, permission, route, scope, atau business rule. Mobile/WebView mengikuti `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`.

## 1. Role Resmi

```text
admin
operator
pimpinan
bk
guru
siswa
```

Wali Kelas bukan role. Experience Wali aktif ketika Guru memiliki mapping Wali aktif pada Tahun Ajaran aktif.

## 2. Prinsip Experience

UI harus mengutamakan:

```text
apa yang paling sering dikerjakan user
apa yang paling mendesak
apa yang perlu diketahui sekarang
```

bukan urutan tabel database atau struktur menu teknis.

## 3. Identitas Manusia

```text
Nama lengkap = informasi utama
NISN/NIP/NIK = identifier sekunder
```

Search tetap menerima nama + identifier untuk verifikasi/disambiguasi.

## 4. Admin

Tujuan:

```text
konfigurasi
master data
operasional lintas modul
kontrol sistem
```

Admin dapat memakai data-grid lebih padat. Mobile tetap responsive, tetapi desktop/laptop adalah surface utama administrasi berat.

## 5. Operator

Tujuan:

```text
administrasi harian
master data
presensi/laporan operasional
kartu dan workflow siswa sesuai permission
Konseling operasional bila memiliki bk_konseling.*
```

Operator tidak otomatis memiliki Settings/Backup. Untuk G3.3.1, Operator boleh Konseling view/manage/export tetapi tidak `bk_konseling.settings`.

## 6. Pimpinan

Pimpinan adalah experience monitoring/decision, umumnya readonly.

Pertanyaan utama:

```text
kelas mana belum presensi?
guru/jadwal mana belum jurnal?
siapa yang masuk EWS?
apa Catatan Pelanggaran penting terbaru?
bagaimana trend operasional?
```

Dashboard priority:

```text
1. exception KPI
2. trend singkat
3. EWS / Catatan Pelanggaran penting
4. statistik umum
```

G3.3.1 menetapkan:

```text
Catatan Pelanggaran = agregat/jumlah, tanpa poin
Konseling BK = tidak menjadi widget/detail/source data Pimpinan
widget tanpa permission = "tidak tersedia", bukan angka 0 palsu
```

## 7. BK

Tujuan:

```text
search siswa cepat
catat Catatan Pelanggaran cepat
lihat riwayat Tindak Lanjut cepat
buat/lanjutkan Konseling secara rahasia
monitor EWS
catat Prestasi
```

Priority untuk G3.4:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut yang perlu perhatian
EWS
Prestasi
```

Catatan Pelanggaran mobile tidak mempertahankan seluruh kolom desktop. Nama siswa + pelanggaran menjadi primary information; tanggal/kategori metadata; keterangan/history masuk Detail. **Poin tidak ditampilkan atau dihitung.**

Konseling adalah data rahasia dan hanya surface untuk Admin/Operator/BK sesuai permission. Pengaturan Form Konseling hanya Admin/BK.

## 8. Guru

Tujuan utama Guru adalah mengajar.

Dashboard priority:

```text
1. Belum Presensi
2. Belum Jurnal
3. Jadwal Hari Ini
4. Selesai
5. Quick Action
6. Jadwal berikutnya / ringkasan aktivitas
```

Jalur Presensi/Jurnal ideal maksimal 1–2 tap. Action mengikuti time-window dan authorization server.

## 9. Guru + Wali Kelas

Wali mewarisi seluruh experience Guru dan mendapat context kelas wali.

Priority:

```text
1. tugas sebagai Guru
2. kondisi kelas wali
3. Presensi kelas
4. EWS kelas
5. quick action kelas
6. history/insight
```

Quick link contextual dapat mencakup:

```text
Presensi Kelas
Rekap Presensi
Data Siswa
Matrix/EWS
Catatan Pelanggaran
Prestasi
Kartu
```

G3.3.1: Guru/Wali **tidak** mendapat surface/detail/quick link Konseling BK. Link Catatan Pelanggaran tetap permission/scope-aware.

## 10. Siswa

Tujuan adalah self-service readonly/limited action atas data diri.

Priority:

```text
1. status Sesi Awal hari ini
2. rekap bulan berjalan
3. quick action
4. kartu pelajar
5. prestasi
6. Catatan Pelanggaran diri sesuai permission/surface
7. profile
```

Tidak adanya Presensi Sesi Awal berarti data belum tersedia, bukan otomatis Hadir.

G3.3.1:

```text
Catatan Pelanggaran diri tidak mengandung poin/ranking
Konseling BK tidak pernah ditampilkan ke Siswa
section tanpa permission tidak boleh disamarkan sebagai "data kosong"
```

## 11. Quick Action Mobile

Recommended baseline:

```text
Pimpinan  Rekap / Jurnal / EWS / Laporan
Guru      Presensi / Jurnal / Jadwal / Profil
Wali      Presensi / Rekap Kelas / EWS / Data Siswa
BK        Konseling / Catatan Pelanggaran / EWS / Prestasi
Siswa     Presensi Saya / Kartu / Prestasi / Profil
```

Quick Action tidak menambah permission; hanya shortcut ke route yang memang diizinkan.

## 12. Table / List Strategy

```text
Admin/Operator    boleh dense table/matrix bila perlu
Pimpinan          summary/adaptive table/list
Guru/Wali         task table compact / list
BK                case/service-oriented adaptive table/list
Siswa             list/card atau table sangat sederhana
```

Pimpinan/BK/Guru/Wali/Siswa mobile wajib no-horizontal-table-scroll.

## 13. Search Strategy

Search entity menerima Nama + identifier. Result menonjolkan:

```text
Nama
context manusia · identifier
```

Nama tetap visual dominant.

## 14. Dashboard Data Limit

Dashboard bukan laporan lengkap:

```text
3–5 recent/top item
+ Lihat Semua
```

Istilah `Top` hanya berarti item prioritas/terbaru sesuai konteks, **bukan ranking poin Pelanggaran**.

## 15. Role Context & Security

- BK yang juga Guru dipilih berdasarkan effective role priority aplikasi.
- Wali tetap contextual, bukan secondary role baru.
- Direct URL diperiksa authorization.
- Hidden menu/button bukan security control.
- Scope tetap server-side.
- Data Konseling tidak boleh dikirim ke role terlarang lalu hanya disembunyikan di UI.

## 16. Loading / Empty / Error

Setiap role harus memahami kondisi:

```text
Loading
Belum ada data
Tidak ada data pada filter
Data tidak tersedia karena tidak punya akses
Sesi berakhir
Network gagal
```

Jangan menampilkan `0` sebagai pengganti data yang memang tidak boleh/ tidak tersedia.

## 17. Mutation UX

Role mutation utama:

```text
Guru/Wali  Presensi/Jurnal
BK         Catatan Pelanggaran/Tindak Lanjut/Konseling/Prestasi sesuai hak
Admin/Operator sesuai permission
```

Wajib busy guard, server-confirmed success, input penting dipertahankan pada failure, dan project confirmation untuk destructive action.

## 18. Current Phase Status

```text
G3.1 Mobile foundation            CLOSED / MERGED
G3.2 Guru/Wali Presensi/Jurnal    CLOSED / MERGED
G3.3 Dashboard Guru/Wali          CLOSED / MERGED
G3.3.1 Fondasi BK                 PASS / PENDING MERGE
G3.4 BK role experience           NEXT setelah PR #9 merge
```

## 19. Acceptance

Role experience ACC bila:

- informasi penting berada paling atas;
- identifier bukan beban aktivitas harian;
- action penting mudah ditemukan;
- shortcut tidak melampaui permission;
- mobile role table/list tidak horizontal-scroll;
- data lengkap tetap dapat dicapai melalui Detail/report;
- Catatan Pelanggaran tidak memakai poin;
- Konseling hanya muncul pada role yang sah;
- business/security rule tetap server-side.