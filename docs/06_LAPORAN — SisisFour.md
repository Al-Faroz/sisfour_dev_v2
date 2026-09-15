# Laporan & Export — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Released baseline:** `main` setelah G3.1  
**Schema delta aktif:** G3.2 Jurnal student exceptions (belum production)

> Dokumen ini menyatakan kontrak laporan yang berlaku untuk development G3.2. Schema delta branch tidak berarti database production sudah dimigrasikan.

## 1. Sumber Resmi Presensi

```text
presensi.sesi = 'Sesi Awal'
```

Sesi Akhir tidak masuk total resmi.

Exception siswa pada `presensi_mengajar_siswa` **bukan** Presensi resmi dan tidak masuk Matrix/EWS/Signage Presensi.

## 2. Permission

```text
laporan_matrix.view
laporan_export.generate
laporan_jurnal.view
laporan_jurnal.export
```

## 3. Matrix Presensi

```text
GET /laporan/presensi/matrix
GET /laporan/presensi/matrix/json
```

Isi:

```text
NISN
Nama
Kelas
Total H/S/I/A
Tanggal 01..31
```

Tidak adanya record tidak boleh otomatis dianggap Alpha tanpa melihat membership/histori.

## 4. Membership Historis

`riwayat_siswa` menentukan apakah siswa memang menjadi anggota pada tanggal laporan.

## 5. Export Presensi

```text
/laporan/presensi/export
/laporan/presensi/export/bulan
/laporan/presensi/export/semester
```

Format utama XLSX.

```text
Ganjil -> Juli–Desember
Genap  -> Januari–Juni
```

## 6. Laporan Jurnal Canonical

```text
/laporan/jurnal
/laporan/jurnal/json
/laporan/jurnal/export
```

Listing utama wajib mempertahankan:

```text
1 row = 1 Jurnal
```

Informasi utama:

```text
Tanggal
Guru / Kelas
Mapel / Hari / Jam / Sesi
Status Guru
Materi
Catatan
Jumlah Sakit/Izin/Alpha siswa
Detail
```

Nama siswa exception tidak di-flatten menjadi row utama karena dapat menggandakan parent Jurnal.

Jadwal nonaktif tidak menghapus histori Jurnal.

### Detail Jurnal

Detail dimuat on-demand dan menampilkan:

```text
informasi parent Jurnal
Materi
Catatan
summary S/I/A
Nama siswa
NISN snapshot
status Sakit/Izin/Alpha
```

Authorization Detail tetap mengikuti `laporan_jurnal.view` dan scope actor.

### Query strategy

Untuk listing page:

```text
1 query parent Jurnal bounded/paginated
+
1 aggregate query child untuk seluruh id parent pada page
```

Dilarang satu query child per row parent.

Detail child baru dibaca ketika user memilih `Detail`.

## 7. Export Jurnal

Export tetap parent-level agar ukuran file terkendali.

Kolom G3.2:

```text
No
Tanggal
Hari
Jam
NIP
Guru
Kelas
Kode Mapel
Mata Pelajaran
Sesi
Status Guru
Materi
Catatan
Sakit Siswa
Izin Siswa
Alpha Siswa
Total S/I/A
Tahun Ajaran
Semester
```

Export tidak membuat satu row untuk setiap siswa exception. Nama siswa lengkap tersedia melalui Detail aplikasi. Jika kebutuhan export per-siswa muncul kemudian, ia harus menjadi export khusus dengan scope/filter tersendiri.

Batas export canonical tetap maksimal 50.000 parent row sebelum user diminta mempersempit filter.

## 8. Database-First

```text
query membership/history bounded
query parent laporan bounded
aggregate child batch
pagination
pivot ringan PHP bila diperlukan
```

Dilarang N+1 per siswa, per tanggal, atau per Jurnal.

## 9. Mobile

Untuk role operasional:

- laporan Jurnal mobile memakai card/list;
- tidak ada horizontal table scroll;
- Detail memakai modal scrollable/fullscreen-sm-down;
- Nama menjadi informasi manusia utama;
- identifier tetap sekunder.

Admin/Operator desktop tetap boleh memakai tabel ringkas.

## 10. Role

- Admin/Operator sesuai permission SEMUA.
- Pimpinan supervisi/read-only sesuai permission.
- Wali/Guru dengan scope diri hanya melihat Jurnal dirinya bila permission mengizinkan.
- Status Wali tidak otomatis memberi hak melihat Jurnal Guru lain.
- Siswa tidak mendapat laporan kelas/global.

## 11. Privasi

Laporan tidak mengekspor password/hash/token, credential, data BK yang tidak relevan, atau dokumen personalia mentah.

Snapshot Nama/NISN child Jurnal hanya tampil pada Detail yang telah melewati authorization.

## 12. Error

Server menolak scope actor yang tidak sah, periode/tahun invalid, Guru data Guru lain tanpa hak, export tanpa permission, dan request Detail Jurnal di luar scope.

Jika schema delta G3.2 belum dimigrasikan pada local/staging, endpoint data Jurnal baru harus mengembalikan error terkontrol `SCHEMA_NOT_READY`, bukan SQL error mentah.
