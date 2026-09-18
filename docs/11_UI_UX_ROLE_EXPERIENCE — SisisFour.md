# UI/UX Role Experience — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Role experience:** Admin, Operator, Pimpinan, BK, Kesehatan, PTSP, Guru, Guru+Wali, Siswa

> Dokumen ini menetapkan hierarchy pengalaman pengguna per role/context. Ia tidak mengubah role, permission, route, scope, atau business rule. Mobile/WebView mengikuti `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`.

## 1. Role Resmi

```text
admin
operator
pimpinan
bk
kesehatan
guru
siswa
```

Wali Kelas bukan role. Experience Wali aktif ketika Guru memiliki mapping Wali aktif pada Tahun Ajaran aktif.

## 2. Prinsip Experience

UI mengutamakan:

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

## 4. Aturan Periodik Lintas Role

Untuk tabel/list yang mempunyai dimensi Tahun Ajaran:

```text
default = Tahun Ajaran aktif
Reset   = Tahun Ajaran aktif
history = selectable bila domain mendukung
export  = mengikuti Tahun Ajaran terpilih
```

Aturan ini tidak membuat filter Tahun Ajaran palsu pada tabel global/non-periodik.

## 5. Admin

Tujuan:

```text
konfigurasi
master data
operasional lintas modul
kontrol sistem
```

Admin dapat memakai data-grid lebih padat. Mobile tetap responsive, tetapi desktop/laptop adalah surface utama administrasi berat.

## 6. Operator

Tujuan:

```text
administrasi harian
master data
presensi/laporan operasional
kartu dan workflow siswa sesuai permission
Konseling operasional bila memiliki bk_konseling.*
```

Operator boleh Konseling view/manage/export tetapi tidak `bk_konseling.settings`.

## 7. Pimpinan

Pimpinan adalah experience monitoring/decision, umumnya readonly.

Priority:

```text
1. exception KPI
2. trend singkat
3. EWS / Catatan Pelanggaran penting
4. statistik umum
```

Contract:

```text
Catatan Pelanggaran = agregat/jumlah, tanpa poin
Konseling BK = tidak menjadi widget/detail/source data Pimpinan
widget tanpa permission = tidak tersedia, bukan angka 0 palsu
Dashboard = current-state Tahun Ajaran aktif
```

### Dashboard Pimpinan G3.5

KPI canonical 2×2:

```text
Kelas Belum Presensi
Jadwal Belum Jurnal
EWS Alpha 14 Hari
Catatan Pelanggaran Bulan Ini
```

Quick Action permission-aware:

```text
Rekap
Jurnal
EWS
Laporan
```

Quick Action tidak menambah permission. Pelanggaran Bulan Ini dan Prestasi Terbaru hanya membaca Tahun Ajaran aktif. Monitoring lanjutan memakai Tren Presensi 7 Hari, EWS maksimal 5 item, Prestasi maksimal 5 item, dan Ringkasan Master. EWS/Prestasi menyediakan `Lihat Semua` bila permission tersedia.

Pimpinan tetap readonly. Tidak ada Konseling BK pada widget, detail, quick action, maupun payload dashboard.

## 8. BK

Tujuan:

```text
search siswa cepat
catat Catatan Pelanggaran cepat
lihat riwayat Tindak Lanjut Pelanggaran
buat/lanjutkan Konseling rahasia
lihat riwayat Tindak Lanjut Konseling
monitor EWS
catat Prestasi
```

Priority G3.4:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut yang perlu perhatian
EWS
Prestasi
```

### Dashboard BK G3.4

Dashboard adalah current-state Tahun Ajaran aktif, bukan historical listing. Tidak ada selector Tahun Ajaran di dashboard.

KPI canonical 2×2:

```text
Konseling Proses
Pelanggaran Bulan Ini
EWS Alpha 14 Hari
Prestasi Bulan Ini
```

Quick Action permission-aware:

```text
Konseling BK
Catatan Pelanggaran
EWS
Prestasi
```

Recent/top section dibatasi maksimum 5 item dan menyediakan `Lihat Semua`. Mobile memakai card/list tanpa horizontal operational table scroll.

Jadwal Follow-up Terdekat menggunakan `tanggal_berikutnya` dari entry Tindak Lanjut Konseling terbaru bila histori sudah ada; jika belum ada histori, gunakan `tanggal_berikutnya` parent. Tanggal kosong tidak dibuat menjadi jadwal. G3.4 tidak menciptakan SLA, deadline, atau label overdue baru.

Widget tanpa permission tidak disamarkan sebagai angka 0.

### Catatan Pelanggaran

Nama siswa + pelanggaran adalah primary information; tanggal/kategori metadata; keterangan/history masuk Detail. Poin tidak ditampilkan/dihitung.

### Konseling

Konseling hanya surface Admin/Operator/BK sesuai permission. Pengaturan Form hanya Admin/BK.

Workflow detail canonical:

```text
Identitas Konseling
→ Hasil Pertemuan Awal
→ Riwayat Tindak Lanjut Konseling
→ Form Tambah/Edit Tindak Lanjut
```

Satu Konseling dapat memiliki banyak tindak lanjut. Riwayat harus terlihat sebelum form agar Guru BK mengetahui context sebelumnya.

Tidak ada Delete parent Konseling dan tidak ada Delete Tindak Lanjut Konseling.

### Filter BK

Catatan Pelanggaran, Konseling, dan Prestasi adalah surface periodik. Tahun Ajaran selalu tersedia sebagai Period Context, default aktif, dan Reset kembali aktif pada experience yang memiliki tombol Reset.

Jika filter desktop banyak seperti Konseling, layout boleh dua baris dan tidak dipaksa menjadi satu baris sempit.

## 9. Guru

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

Jalur Presensi/Jurnal ideal maksimal 1–2 tap. Action mengikuti authorization server.

## 10. Guru + Wali Kelas

Wali mewarisi seluruh experience Guru dan mendapat context kelas wali.

Quick link contextual dapat mencakup Presensi, Rekap, Data Siswa, Matrix/EWS, Catatan Pelanggaran, Prestasi, Kartu sesuai permission.

Guru/Wali **tidak** mendapat surface/detail/quick link Konseling BK.

## 11. Siswa

Tujuan adalah self-service readonly/limited action atas data diri.

Priority:

```text
status Sesi Awal hari ini
rekap bulan berjalan
quick action
Kartu Pelajar
Prestasi
Catatan Pelanggaran diri sesuai permission
profile
```

Tidak adanya Presensi Sesi Awal berarti data belum tersedia, bukan otomatis Hadir.

```text
Catatan Pelanggaran diri tidak mengandung poin/ranking
Konseling BK tidak pernah ditampilkan ke Siswa
section tanpa permission tidak disamarkan sebagai data kosong
```

### Dashboard Siswa G3.6

Dashboard adalah current-state Tahun Ajaran aktif dan memakai scope `DIRI_SENDIRI`.

```text
Header           = Tahun Ajaran aktif + Data Saya
Status           = Presensi Sesi Awal hari ini
KPI 2×2          = Hadir / Sakit / Izin / Alpha bulan berjalan
Quick Action     = Presensi Saya / Kartu / Prestasi / Profil
Recent           = ketidakhadiran maksimal 5 + Lihat Rekap
Kartu            = Kartu Pelajar diri sendiri
Prestasi         = maksimal 5, Tahun Ajaran aktif + Lihat Semua
Pelanggaran      = maksimal 5, Tahun Ajaran aktif + Lihat Semua
```

Quick Action permission-aware dan tidak menambah capability. Kartu/Profile tetap divalidasi server-side terhadap identity/scope actor. Bila Tahun Ajaran aktif tidak tersedia, data periodik dashboard menampilkan state unavailable dan tidak menggunakan angka 0 palsu. Dashboard tidak menambah selector historical.

Untuk surface periodik **Catatan Pelanggaran** dan **Prestasi** dengan effective scope `DIRI_SENDIRI`, experience canonical adalah:

```text
Tahun Ajaran = tetap tampil sebagai Period Context
initial       = Tahun Ajaran aktif
history       = dapat dipilih
pergantian TA = langsung memuat daftar periode terpilih
Pencarian     = tidak ditampilkan
Kategori      = tidak ditampilkan pada Catatan Pelanggaran
Tingkat       = tidak ditampilkan pada Prestasi
Dari/Sampai   = tidak ditampilkan
Reset         = tidak ditampilkan
Tampilkan     = tidak ditampilkan
daftar        = langsung data diri sendiri; bila kosong tampil empty state
```

Penyederhanaan ini adalah presentation/role experience saja. Scope `DIRI_SENDIRI` tetap ditentukan dan ditegakkan server-side; UI tidak boleh dipakai sebagai security boundary.

Focused local UAT 18 September 2026 untuk experience ini: **PASS / user evidence**.

## 12. Kesehatan — G3.6A

Kesehatan adalah experience operasional domain UKS berbasis identity Pegawai.

Priority dashboard current-state Tahun Ajaran aktif:

```text
KPI 2×2
- Kunjungan UKS Hari Ini
- Kunjungan UKS Bulan Ini
- Rujuk ke Klinik Bulan Ini
- Pemeriksaan CKG Bulan Ini

Primary Action
- Card besar Tambah Data Kunjungan

Quick Action 2×2
- Data UKS
- Data CKG
- Import CKG
- Master UKS

Recent
- Kunjungan UKS terbaru max 5
- Pemeriksaan CKG terbaru max 5
```

Dashboard tidak menambah selector historis. Bila Tahun Ajaran aktif tidak tersedia, KPI ditampilkan unavailable dan tidak menjadi angka 0 palsu.

Listing Data CKG dan Catatan Harian UKS tetap periodik dan dapat memilih Tahun Ajaran historis. Scope Guru+Wali dihitung terhadap period terpilih; Siswa selalu `DIRI_SENDIRI`.

Untuk Siswa, hanya selector Tahun Ajaran yang dipertahankan sebagai Period Context/history. Filter kelas, pencarian, rentang tanggal, Keluhan/Hasil, Reset, dan Tampilkan tidak dirender karena target data tidak dapat berubah dari identity login.

Tidak ada medical risk score, SLA, overdue, atau interpretasi klinis yang diciptakan dashboard.

Role experience priority:

```text
admin > operator > pimpinan > bk > kesehatan > ptsp > guru > siswa
```

PTSP masuk priority G3.6B setelah Kesehatan dan sebelum Guru.

## 13. Quick Action Mobile

```text
Pimpinan  Rekap / Jurnal / EWS / Laporan
Guru      Presensi / Jurnal / Jadwal / Profil
Wali      Presensi / Rekap Kelas / EWS / Data Siswa
BK        Konseling / Catatan Pelanggaran / EWS / Prestasi
Kesehatan Tambah Data Kunjungan / Data UKS / Data CKG / Import CKG / Master UKS
Siswa     Presensi Saya / Kartu / Prestasi / Profil
```

Quick Action tidak menambah permission.

## 14. Table / List Strategy

```text
Admin/Operator    boleh dense table/matrix bila perlu
Pimpinan          summary/adaptive table/list
Guru/Wali         task table compact / list
BK                case/service-oriented adaptive table/list
Kesehatan         health-record adaptive table/list
Siswa             list/card atau table sangat sederhana
```

Pimpinan/BK/Kesehatan/Guru/Wali/Siswa mobile wajib no-horizontal-table-scroll.

## 15. Search Strategy

Search entity menerima Nama + identifier. Result menonjolkan Nama; identifier menjadi context sekunder. Search tidak perlu dirender pada surface self-only bila target data tidak dapat berubah dari identity user login.

## 16. Dashboard Data Limit

Dashboard bukan laporan lengkap:

```text
3–5 recent/top item
+ Lihat Semua
```

`Top` tidak berarti ranking poin Pelanggaran.

## 17. Role Context & Security

- BK yang juga Guru dipilih berdasarkan effective role priority aplikasi.
- Wali tetap contextual, bukan secondary role baru.
- Direct URL diperiksa authorization.
- Hidden menu/button bukan security control.
- Scope tetap server-side.
- Data Konseling tidak boleh dikirim ke role terlarang lalu hanya disembunyikan di UI.

## 18. Loading / Empty / Error

Setiap role harus membedakan:

```text
Loading
Belum ada data
Tidak ada data pada filter
Data tidak tersedia karena tidak punya akses
Sesi berakhir
Network gagal
```

Jangan menampilkan `0` sebagai pengganti data yang tidak boleh/tidak tersedia.

## 19. Mutation UX

```text
Guru/Wali  Presensi/Jurnal
BK         Catatan Pelanggaran/Tindak Lanjut Pelanggaran/Konseling/Tindak Lanjut Konseling/Prestasi
Kesehatan  CKG / Catatan Harian UKS / Master UKS
Admin/Operator sesuai permission
```

Wajib busy guard, server-confirmed success, input penting dipertahankan pada failure, dan project confirmation untuk destructive action. Konseling tidak memiliki destructive delete action pada contract saat ini.

## 20. Current Phase Status

```text
G3.1 Mobile foundation            CLOSED / MERGED
G3.2 Guru/Wali Presensi/Jurnal    CLOSED / MERGED
G3.3 Dashboard Guru/Wali          CLOSED / MERGED
G3.3.1 Fondasi BK                 CLOSED / MERGED — PR #9
G3.4 BK role experience           CLOSED / MERGED — PR #10
G3.5 Pimpinan role experience     CLOSED / MERGED — PR #11
G3.6 Siswa role experience        CLOSED / MERGED — PR #12
G3.6A Kesehatan/UKS experience    CLOSED / MERGED — PR #13
G3.6B PTSP experience              ACTIVE / SOURCE IMPLEMENTED / LOCAL SQL PENDING
```

## 21. Acceptance

Role experience ACC bila:

- informasi penting berada paling atas;
- identifier bukan beban aktivitas harian;
- action penting mudah ditemukan;
- shortcut tidak melampaui permission;
- periodic tables konsisten memakai Tahun Ajaran;
- Dashboard BK current-state hanya membaca Tahun Ajaran aktif dan tidak menambah selector historis palsu;
- Dashboard BK membatasi recent/top 3–5 item + Lihat Semua;
- jadwal follow-up memakai source tanggal tersimpan tanpa membuat SLA/label overdue baru;
- Dashboard Pimpinan current-state hanya membaca Tahun Ajaran aktif untuk data periodik;
- Dashboard Pimpinan mempertahankan readonly, shortcut permission-aware, dan tidak menerima data Konseling;
- Dashboard Siswa current-state membatasi Presensi/Prestasi/Pelanggaran pada identity login + Tahun Ajaran aktif;
- Dashboard Siswa membedakan Period Context unavailable dari nilai 0 dan menyediakan shortcut permission-aware;
- Dashboard Siswa tidak menerima data Konseling dan tidak menampilkan poin Pelanggaran;
- Dashboard Kesehatan current-state memakai Tahun Ajaran aktif dan hanya KPI/quick action yang dikunci G3.6A;
- UKS listing/history mengikuti Tahun Ajaran terpilih dan former Wali hanya mendapat siswa dari mapping Wali period tersebut;
- role Kesehatan memakai identity Pegawai dan Full Access hanya pada domain UKS;
- Dashboard/UKS tidak menciptakan medical scoring, SLA, overdue, atau risk label;
- Siswa `DIRI_SENDIRI` pada Catatan Pelanggaran/Prestasi hanya memakai Tahun Ajaran sebagai Period Context dan daftar langsung data diri;
- filter padat tidak dipaksa satu baris sempit;
- mobile role table/list tidak horizontal-scroll;
- data lengkap tetap dapat dicapai melalui Detail/report;
- Catatan Pelanggaran tidak memakai poin;
- Konseling hanya muncul pada role sah;
- follow-up Konseling multiple entry tidak overwrite histori;
- tidak ada delete Konseling/follow-up;
- business/security rule tetap server-side.


## 13. PTSP — G3.6B

Authenticated PTSP memakai shell CI4/Sneat existing. Public PTSP memakai landing tersendiri tanpa sidebar authenticated.

Mobile/public acceptance:

- tiga form public stack vertikal pada viewport sempit;
- internal list memakai card presentation pada mobile dan table desktop;
- mutation controls tidak dirender untuk Pimpinan;
- receipt thermal hanya muncul untuk Layanan yang baru disubmit dan tidak memuat ticket/queue/tracking number;
- Pengaduan tidak meminta nama/kontak;
- attachment tidak mempunyai direct public URL.
