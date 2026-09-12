# UI/UX Guru, Walas & Siswa — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`


> Dokumen ini adalah **canonical UI/UX contract** untuk tiga experience yang sedang menjadi fokus. Ia membedakan dengan tegas antara **runtime baseline yang sudah ada** dan **mockup/desain yang belum diimplementasikan**.

## 1. Scope

Fokus:

```text
Guru biasa
Guru + Wali Kelas (Walas)
Siswa
```

Tidak mengubah role resmi, permission key, scope data, route, atau business rule tanpa perubahan dokumen domain terkait.

## 2. Status Desain

Pada tanggal acuan ini:

- runtime source tetap baseline `39da465`;
- belum ada source UI baru yang diputuskan final setelah baseline tersebut;
- render/mockup Dashboard Guru yang dibuat dalam diskusi adalah **referensi visual untuk komparasi**, bukan implementasi aplikasi;
- catatan perubahan visual dari pengguna masih dalam tahap pengumpulan.

Karena itu, dokumen ini menyatakan **inventaris aktual + guardrail redesign**, bukan memaksakan mockup sebagai target final.

## 3. Experience Guru Biasa — Baseline Aktual

Tujuan utama Guru adalah **mengajar**.

Surface utama yang tersedia berdasarkan route/permission/context baseline:

```text
Dashboard Guru
Presensi Siswa sesuai jadwal
Input Presensi Siswa
Presensi/Jurnal Mengajar
Input Jurnal Mengajar
Jadwal Guru
Laporan Jurnal
Profile Guru
Personalia Guru
Portofolio Guru
```

Surface lain dapat terlihat berdasarkan permission `KELAS_DIAMPU`/menu database, tetapi data-level authorization tetap ditentukan Service.

### Dashboard Guru baseline

Data utama:

- Tahun Ajaran aktif;
- jadwal mengajar hari ini;
- task summary presensi/jurnal;
- riwayat jurnal terakhir;
- akses Profile Guru bila permission tersedia.

Setiap jadwal dapat memiliki state:

```text
presensi: not_applicable / submitted / not_started / available / ended
jurnal:   submitted / not_started / available / ended
```

Waktu dan schedule menentukan action yang tersedia.

## 4. Experience Walas — Baseline Aktual

**Walas bukan role.** Experience ini aktif ketika Guru memiliki mapping Wali Kelas pada Tahun Ajaran aktif.

Walas mewarisi seluruh experience Guru dan mendapat contextual kelas wali:

- identitas/nama kelas wali;
- jumlah siswa kelas wali;
- ringkasan Presensi Sesi Awal hari ini: Hadir/Sakit/Izin/Alpha;
- EWS kelas bila permission tersedia;
- ketidakhadiran terbaru kelas;
- quick links contextual.

Quick links baseline dapat mencakup:

```text
Presensi Kelas
Rekap Presensi
Data Siswa
Matrix Presensi
EWS Kelas
Kasus Siswa
Prestasi Siswa
Kartu Pelajar
```

Link hanya muncul jika permission terkait dimiliki.

### Hak contextual penting

- Wali dapat mengisi Presensi kelas walinya sesuai aturan waktu/context;
- Wali dapat merevisi Presensi kelas walinya sesuai business rule;
- hak Wali tidak berlaku ke kelas lain;
- hak mengajar/jurnal tetap berasal dari identity/jadwal Guru.

## 5. Experience Siswa — Baseline Aktual

Tujuan utama Siswa adalah **melihat informasi diri sendiri**.

Dashboard Siswa readonly memuat:

- Tahun Ajaran aktif;
- status kehadiran hari ini dari **Sesi Awal**;
- rekap Presensi bulan berjalan;
- ketidakhadiran/presensi terbaru;
- riwayat Prestasi;
- riwayat Kasus/Pelanggaran;
- Kartu Pelajar diri sendiri;
- akses Profile Siswa.

Tidak adanya row Presensi Sesi Awal berarti **data belum tersedia**, bukan otomatis `Hadir`.

Surface utama:

```text
Dashboard Siswa
Rekap/Riwayat Presensi diri
Kasus/Pelanggaran diri
Prestasi diri
Kartu Pelajar diri
Preview/Download Kartu sendiri
Profile Siswa readonly
```

## 6. Guardrail Redesign

Semua perubahan UI/UX tiga experience harus memenuhi:

1. **Tidak mengubah authorization dengan CSS/JS.**
2. **Tidak membuat role `walas`.** Gunakan mapping contextual Wali.
3. **Tidak memperlihatkan data lintas scope.**
4. **Tidak menganggap hidden menu sebagai security control.**
5. **Tidak mengubah Sesi Awal sebagai sumber status resmi Siswa/Wali.**
6. **Tidak mengubah mutation permission hanya karena tombol dipindah/dibuat lebih mudah.**
7. Responsive untuk desktop dan mobile/WebView.
8. Vanilla JS + Fetch; tidak menambah jQuery business logic.
9. Pertahankan komponen/template Sneat sebagai basis visual kecuali keputusan arsitektural baru dibuat eksplisit.
10. Semua form mutation memakai loading/busy guard untuk mencegah double-submit bila relevan.

## 7. Proses Redesign yang Berlaku

Urutan yang digunakan:

```text
1. screenshot/tampilan runtime saat ini
2. catatan masalah UI/UX
3. mockup/render usulan
4. komparasi dengan runtime
5. keputusan final
6. implementasi View/JS
7. Controller/Service hanya bila kebutuhan data memang berubah
8. static + browser regression
9. update canonical docs sebagai state final
```

Mockup yang belum di-ACC tidak boleh dijadikan dasar perubahan Service/RBAC.

## 8. Kriteria Dashboard Baru

Bila Dashboard Guru/Walas/Siswa didesain ulang, prioritas informasi:

### Guru

```text
Apa jadwal saya hari ini?
Presensi mana yang harus dikerjakan?
Jurnal mana yang belum selesai?
Apa aktivitas mengajar terbaru saya?
```

### Walas

```text
Semua kebutuhan Guru
+ bagaimana kondisi kelas wali saya hari ini?
+ siapa yang tidak hadir/berisiko?
+ tindakan kelas apa yang perlu dilakukan?
```

### Siswa

```text
Bagaimana status kehadiran saya hari ini?
Bagaimana riwayat saya?
Apa Prestasi/Pelanggaran saya?
Di mana Kartu dan Profile saya?
```

## 9. Validasi UI/UX

Minimal uji:

- Guru non-Wali;
- Guru+Wali;
- Siswa;
- desktop lebar;
- laptop;
- mobile/WebView;
- menu active/open;
- empty state;
- loading/error state;
- session expired;
- data panjang/teks panjang;
- tombol mutation tidak double-submit;
- direct URL tetap mengikuti RBAC walaupun menu disembunyikan.

## 10. Status Keputusan yang Belum Dikunci

Belum menjadi kontrak final sampai pengguna menyetujui dan source diimplementasikan:

- komposisi card/widget Dashboard Guru baru;
- komposisi card/widget Dashboard Walas baru;
- komposisi card/widget Dashboard Siswa baru;
- penyederhanaan sidebar Guru non-Wali vs Walas;
- urutan visual quick actions;
- gaya/warna/spacing di luar basis Sneat saat ini.
