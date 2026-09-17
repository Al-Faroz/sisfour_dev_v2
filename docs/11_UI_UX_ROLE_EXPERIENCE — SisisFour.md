# UI/UX Role Experience — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
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
```

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

Priority G3.4 nanti:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut yang perlu perhatian
EWS
Prestasi
```

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

## 12. Quick Action Mobile

```text
Pimpinan  Rekap / Jurnal / EWS / Laporan
Guru      Presensi / Jurnal / Jadwal / Profil
Wali      Presensi / Rekap Kelas / EWS / Data Siswa
BK        Konseling / Catatan Pelanggaran / EWS / Prestasi
Siswa     Presensi Saya / Kartu / Prestasi / Profil
```

Quick Action tidak menambah permission.

## 13. Table / List Strategy

```text
Admin/Operator    boleh dense table/matrix bila perlu
Pimpinan          summary/adaptive table/list
Guru/Wali         task table compact / list
BK                case/service-oriented adaptive table/list
Siswa             list/card atau table sangat sederhana
```

Pimpinan/BK/Guru/Wali/Siswa mobile wajib no-horizontal-table-scroll.

## 14. Search Strategy

Search entity menerima Nama + identifier. Result menonjolkan Nama; identifier menjadi context sekunder. Search tidak perlu dirender pada surface self-only bila target data tidak dapat berubah dari identity user login.

## 15. Dashboard Data Limit

Dashboard bukan laporan lengkap:

```text
3–5 recent/top item
+ Lihat Semua
```

`Top` tidak berarti ranking poin Pelanggaran.

## 16. Role Context & Security

- BK yang juga Guru dipilih berdasarkan effective role priority aplikasi.
- Wali tetap contextual, bukan secondary role baru.
- Direct URL diperiksa authorization.
- Hidden menu/button bukan security control.
- Scope tetap server-side.
- Data Konseling tidak boleh dikirim ke role terlarang lalu hanya disembunyikan di UI.

## 17. Loading / Empty / Error

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

## 18. Mutation UX

```text
Guru/Wali  Presensi/Jurnal
BK         Catatan Pelanggaran/Tindak Lanjut Pelanggaran/Konseling/Tindak Lanjut Konseling/Prestasi
Admin/Operator sesuai permission
```

Wajib busy guard, server-confirmed success, input penting dipertahankan pada failure, dan project confirmation untuk destructive action. Konseling tidak memiliki destructive delete action pada G3.3.1.

## 19. Current Phase Status

```text
G3.1 Mobile foundation            CLOSED / MERGED
G3.2 Guru/Wali Presensi/Jurnal    CLOSED / MERGED
G3.3 Dashboard Guru/Wali          CLOSED / MERGED
G3.3.1 Fondasi BK                 LOCAL FINAL GATE PASS / HOSTING SOURCE RE-SMOKE PENDING
G3.4 BK role experience           NEXT setelah PR #9 merge
```

## 20. Acceptance

Role experience ACC bila:

- informasi penting berada paling atas;
- identifier bukan beban aktivitas harian;
- action penting mudah ditemukan;
- shortcut tidak melampaui permission;
- periodic tables konsisten memakai Tahun Ajaran;
- Siswa `DIRI_SENDIRI` pada Catatan Pelanggaran/Prestasi hanya memakai Tahun Ajaran sebagai Period Context dan daftar langsung data diri;
- filter padat tidak dipaksa satu baris sempit;
- mobile role table/list tidak horizontal-scroll;
- data lengkap tetap dapat dicapai melalui Detail/report;
- Catatan Pelanggaran tidak memakai poin;
- Konseling hanya muncul pada role sah;
- follow-up Konseling multiple entry tidak overwrite histori;
- tidak ada delete Konseling/follow-up;
- business/security rule tetap server-side.
