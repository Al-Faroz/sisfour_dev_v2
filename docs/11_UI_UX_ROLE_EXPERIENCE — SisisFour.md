# UI/UX Role Experience — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026  
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

Wali Kelas **bukan role**. Experience Wali aktif ketika Guru memiliki mapping Wali Kelas pada Tahun Ajaran aktif.

## 2. Prinsip Experience

UI harus mengutamakan:

```text
apa yang paling sering dikerjakan user
apa yang paling mendesak
apa yang perlu diketahui sekarang
```

bukan urutan tabel database atau struktur menu teknis.

## 3. Identitas Manusia

Pada seluruh role operasional:

```text
Nama lengkap = informasi utama
NISN/NIP/NIK = identifier sekunder
```

Identifier digunakan untuk search, verifikasi, pencocokan, nama sama, audit, export dan integrasi.

## 4. Admin

Tujuan:

```text
konfigurasi
master data
operasional lintas modul
kontrol sistem
```

Admin dapat memakai data-grid lebih padat dan beberapa matrix yang tidak cocok untuk role operasional.

Prioritas:

```text
system health
master integrity
operational exception
settings / backup / log
```

Mobile tetap responsive, tetapi desktop/laptop adalah surface utama pekerjaan administrasi berat.

## 5. Operator

Tujuan:

```text
administrasi harian
master data
presensi/laporan operasional
kartu dan workflow siswa sesuai permission
```

Operator tidak otomatis memiliki Settings/Backup; effective permission tetap server-side.

UI lebih task-oriented daripada Admin, tetapi tetap dapat memakai tabel administratif bila diperlukan.

## 6. Pimpinan

Pimpinan adalah experience **monitoring/decision**, umumnya readonly.

Pertanyaan utama:

```text
kelas mana belum presensi?
guru/jadwal mana belum jurnal?
siapa yang masuk EWS?
apa kasus penting terbaru?
bagaimana trend operasional?
```

Dashboard priority:

```text
1. exception KPI
2. trend singkat
3. EWS/kasus/pelanggaran penting
4. statistik umum
```

Tidak perlu memenuhi dashboard dengan master count di area paling atas.

Mobile:

```text
4 KPI = 2×2
summary table 2–3 kolom
Top 5 + Lihat Semua
```

## 7. BK

Tujuan:

```text
search siswa cepat
catat kasus cepat
lihat history cepat
buat tindak lanjut cepat
monitor EWS
```

Priority:

```text
kasus urgent
EWS
follow-up
pelanggaran berat
prestasi
```

Catatan Kasus pada mobile tidak mempertahankan seluruh kolom desktop. Nama siswa dan kasus menjadi primary information; tanggal/kategori/poin menjadi metadata; keterangan/history masuk Detail.

## 8. Guru

Tujuan utama Guru adalah mengajar.

Pertanyaan utama:

```text
apa jadwal saya hari ini?
presensi mana belum diisi?
jurnal mana belum diisi?
apa action yang bisa dilakukan sekarang?
```

Dashboard priority:

```text
1. Belum Presensi
2. Belum Jurnal
3. Jadwal Hari Ini
4. Selesai
5. Quick Action
6. Jadwal berikutnya / ringkasan aktivitas
```

Jalur ke Presensi/Jurnal ideal maksimal 1–2 tap.

Action mengikuti time-window dan authorization server.

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

Wali dapat memperoleh quick link sesuai permission/context:

```text
Presensi Kelas
Rekap Presensi
Data Siswa
Matrix/EWS
Kasus
Prestasi
Kartu
```

Link yang terlihat tidak menggantikan authorization Service.

## 10. Siswa

Tujuan adalah self-service readonly/limited action atas data diri.

Pertanyaan utama:

```text
bagaimana kehadiran saya hari ini?
bagaimana rekap saya?
di mana kartu saya?
apa prestasi/riwayat saya?
```

Priority:

```text
1. status Sesi Awal hari ini
2. rekap bulan berjalan
3. quick action
4. kartu pelajar
5. prestasi
6. riwayat kasus/pelanggaran
7. profile
```

Tidak adanya Presensi Sesi Awal berarti data belum tersedia, bukan otomatis Hadir.

Siswa tidak menggunakan pola data-grid Admin bila list/card lebih natural.

## 11. Quick Action Mobile

Recommended:

```text
Pimpinan  Rekap / Jurnal / EWS / Laporan
Guru      Presensi / Jurnal / Jadwal / Profil
Wali      Presensi / Rekap Kelas / EWS / Data Siswa
BK        Tambah Kasus / EWS / Pelanggaran / Prestasi
Siswa     Presensi Saya / Kartu / Prestasi / Profil
```

Quick Action tidak menambah permission; ia hanya shortcut ke route yang memang diizinkan.

## 12. Table Strategy by Role

```text
Admin/Operator    boleh dense table/matrix bila perlu
Pimpinan          summary/adaptive table
Guru/Wali         task table compact
BK                case-oriented adaptive table
Siswa             list/card atau table sangat sederhana
```

Pimpinan/BK/Guru/Wali/Siswa mobile wajib no-horizontal-table-scroll sesuai dokumen `14`.

## 13. Search Strategy

User sehari-hari mencari orang dari nama.

Search entity harus menerima:

```text
Nama
NISN/NIP/NIK bila relevan
```

Result menampilkan:

```text
Nama
context manusia · identifier
```

Nama tetap visual dominant.

## 14. Dashboard Data Limit

Dashboard bukan laporan lengkap.

```text
3–5 recent/top item
+ Lihat Semua
```

`Top 20` tetap dapat ada pada halaman detail/report, bukan harus 20 row di dashboard mobile.

## 15. Role Context & Security

- BK yang juga Guru tetap dipilih berdasarkan effective role priority yang ditetapkan aplikasi.
- Wali tetap contextual, bukan secondary role baru.
- Direct URL harus tetap diperiksa authorization.
- Hidden menu/button bukan security control.
- Scope `DIRI_SENDIRI`, kelas wali, kelas terjadwal, atau semua data tetap ditentukan server.

## 16. Loading / Empty / Error

Setiap role harus memahami kondisi tanpa membaca console:

```text
Loading
Belum ada data
Tidak ada data pada filter
Sesi berakhir
Tidak punya akses
Network gagal
```

Pesan disesuaikan context role dan tidak menggunakan istilah teknis internal bila tidak perlu.

## 17. Mutation UX

Role yang melakukan mutation:

```text
Guru/Wali  Presensi/Jurnal
BK         Kasus/Tindak Lanjut/Prestasi sesuai hak
Admin/Operator sesuai permission
```

Wajib:

```text
busy guard
server-confirmed success
error tidak menghapus input penting
confirmation untuk destructive action
```

## 18. Mobile/WebView

Semua role prioritas diuji pada 360–412px portrait dan Android WebView.

Contract detail:

`14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`.

## 19. Phase Implementation

```text
G2  tidak melakukan redesign role besar; hanya stabilization
G3  implement role experience mobile-first
G4  Cordova packaging/integration
```

## 20. Acceptance

Role experience ACC bila:

- informasi paling penting berada paling atas;
- user tidak perlu identifier untuk aktivitas harian normal;
- action penting mudah ditemukan;
- menu/shortcut tidak melampaui permission;
- mobile role table tidak horizontal-scroll;
- dashboard tidak terlalu panjang;
- data lengkap tetap dapat dicapai melalui detail/report;
- business rule tetap server-side.
