# Laporan & Export — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Released baseline:** G3.3 merged / `main` @ `06e4e559c045763096058fc889342da78d973314`  
**Development:** G3.3.1 PASS / pending merge approval

> Dokumen ini menyatakan kontrak laporan/export current. Detail business domain tetap mengacu ke dokumen Presensi dan BK.

## 1. Sumber Resmi Presensi

```text
presensi.sesi = 'Sesi Awal'
```

Sesi Akhir tidak masuk total resmi. Exception siswa pada `presensi_mengajar_siswa` bukan Presensi resmi dan tidak masuk Matrix/EWS/Signage Presensi.

## 2. Permission Laporan Akademik

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

Isi utama:

```text
NISN
Nama
Kelas
Total H/S/I/A
Tanggal 01..31
```

Tidak adanya record tidak boleh otomatis dianggap Alpha tanpa melihat membership/histori.

## 4. Export Presensi

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

## 5. Laporan Jurnal Canonical

```text
/laporan/jurnal
/laporan/jurnal/json
/laporan/jurnal/export
```

Listing utama:

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

Query strategy per page:

```text
1 query parent Jurnal bounded/paginated
+
1 aggregate query child untuk seluruh id parent pada page
```

Detail child baru dibaca on-demand. Dilarang N+1 child query per row.

## 6. Export Jurnal

Export tetap parent-level.

Kolom canonical:

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

Batas export canonical maksimum 50.000 parent row sebelum user diminta mempersempit filter.

## 7. Export Catatan Pelanggaran — G3.3.1

Permission mengikuti boundary Catatan Pelanggaran existing (`bk_kasus.view/manage` sesuai route/Service).

Satu XLSX terdiri dari dua sheet:

### Sheet `Pelanggaran`

```text
No
ID Catatan
NISN
Nama Siswa
Kelas
Tanggal
Pelanggaran
Kategori
Keterangan
Jumlah Tindak Lanjut
```

### Sheet `Tindak Lanjut`

```text
No
ID Catatan
NISN
Nama Siswa
Kelas
Tanggal Pelanggaran
Pelanggaran
Kategori
Tanggal Tindak Lanjut
Tindak Lanjut
Keterangan TL
Dicatat Oleh
```

`Kelas` adalah kelas aktif siswa saat export, bukan snapshot historis baru pada `catatan_kasus`.

Export **tidak membawa poin** dan tidak membuat ranking poin.

## 8. Export Prestasi — G3.3.1

Export Prestasi memuat:

```text
No
NISN
Nama Siswa
Kelas
Tanggal
Prestasi
Tingkat
Penyelenggara
Keterangan
```

`Kelas` berasal dari kelas aktif siswa sesuai kontrak export current.

## 9. Export Konseling BK — Data Rahasia

Permission khusus:

```text
bk_konseling.export
```

Effective role tetap harus salah satu:

```text
admin
operator
bk
```

Export memuat identitas siswa/kelas, data Tahap 1, perkembangan Tahap 2, rencana/tanggal berikutnya, status, dan actor pencatat.

Konseling tidak boleh ikut export/dashboard Pimpinan/Guru/Wali/Siswa hanya karena actor memiliki akses ke laporan lain.

## 10. Database-First

```text
WHERE / JOIN / GROUP BY / HAVING / ORDER BY / LIMIT / OFFSET
bounded membership/history query
batch aggregate
pagination
pivot ringan PHP bila diperlukan
```

Dilarang load seluruh dataset besar lalu melakukan agregasi utama di PHP/JS.

## 11. Mobile

Untuk role operasional:

- laporan/list mobile memakai card/adaptive presentation;
- tidak ada horizontal table scroll;
- Detail memakai modal scrollable/fullscreen-sm-down bila panjang;
- Nama menjadi informasi manusia utama;
- identifier sekunder.

Admin/Operator desktop tetap boleh memakai tabel administratif/matrix bila memang diperlukan.

## 12. Privasi

Laporan/export tidak mengekspor password/hash/token/credential atau dokumen personalia mentah.

Konseling BK memiliki boundary lebih ketat dari laporan BK umum dan tidak menjadi sumber data lintas-role.

## 13. Current Gate

```text
G3.2 Jurnal schema/report local+hosting PASS
G3.2 merged PR #7
G3.3 dashboard merged PR #8
G3.3.1 Pelanggaran export 2 sheet + Kelas PASS
G3.3.1 Prestasi export + Kelas PASS
G3.3.1 Konseling export/privacy smoke PASS
Hosting smoke UAT G3.3.1 PASS
```

PR #9 masih menunggu approval eksplisit untuk merge.