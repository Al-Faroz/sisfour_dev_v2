# Laporan & Export — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Released baseline:** G3.3 merged / `main` @ `06e4e559c045763096058fc889342da78d973314`  
**Development:** G3.3.1 rework — local gate pending

> Dokumen ini menyatakan kontrak laporan/export current. Detail business domain tetap mengacu ke dokumen Presensi dan BK.

## 1. Prinsip Periodik Global

Untuk laporan/list/export yang mempunyai dimensi Tahun Ajaran:

```text
initial filter = Tahun Ajaran aktif
Reset          = Tahun Ajaran aktif
history        = selectable bila domain mendukung
export         = mengikuti Tahun Ajaran yang dipilih
```

Export tidak boleh memakai current membership sebagai pengganti snapshot period historis jika domain sudah mempunyai `id_tahun`.

## 2. Sumber Resmi Presensi

```text
presensi.sesi = 'Sesi Awal'
```

Sesi Akhir tidak masuk total resmi. Exception siswa pada `presensi_mengajar_siswa` bukan Presensi resmi dan tidak masuk Matrix/EWS/Signage Presensi.

## 3. Permission Laporan Akademik

```text
laporan_matrix.view
laporan_export.generate
laporan_jurnal.view
laporan_jurnal.export
```

## 4. Matrix / Export Presensi

Matrix/Export Presensi mengikuti Tahun Ajaran terpilih dan membership periode yang tepat. Tidak adanya record tidak boleh otomatis dianggap Alpha tanpa melihat membership/histori.

Export utama XLSX. Ganjil = Juli–Desember; Genap = Januari–Juni.

## 5. Laporan Jurnal

```text
/laporan/jurnal
/laporan/jurnal/json
/laporan/jurnal/export
```

Listing utama tetap `1 row = 1 Jurnal`.

Query strategy:

```text
1 query parent Jurnal bounded/paginated
+
1 aggregate query child untuk seluruh id parent pada page
```

Dilarang N+1 child query per row.

Export Jurnal parent-level dan memuat Tahun Ajaran + Semester.

## 6. Export Catatan Pelanggaran — G3.3.1

Catatan Pelanggaran sekarang mempunyai snapshot `id_tahun`; export mengikuti filter Tahun Ajaran terpilih.

Satu XLSX terdiri dari dua sheet:

### Sheet `Pelanggaran`

```text
No
ID Catatan
Tahun Ajaran
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
Tahun Ajaran
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

Export **tidak membawa poin** dan tidak membuat ranking poin.

Legacy Catatan Pelanggaran yang `id_tahun`-nya belum dapat dipetakan secara aman tidak boleh dipaksa masuk Tahun Ajaran aktif hanya agar muncul di export.

## 7. Export Prestasi — G3.3.1

Prestasi sekarang mempunyai snapshot `id_tahun`; export mengikuti Tahun Ajaran terpilih.

Kolom utama:

```text
No
Tahun Ajaran
NISN
Nama Siswa
Kelas
Tanggal
Prestasi
Tingkat
Penyelenggara
Keterangan
```

Legacy Prestasi dengan `id_tahun NULL` harus diaudit terpisah bila periodenya ambigu.

## 8. Export Konseling BK — Data Rahasia

Permission:

```text
bk_konseling.export
```

Effective role:

```text
admin
operator
bk
```

Export mengikuti Tahun Ajaran terpilih dan terdiri dari dua sheet:

### Sheet `Konseling BK`

Parent/pertemuan awal:

```text
No
Tahun Ajaran
Tanggal
Pertemuan Ke
Kelas
NISN
Nama Siswa
Bentuk Layanan
Cara Hadir
Bidang
Topik
Uraian Masalah
Hasil Pembahasan & Kesepakatan
Rencana Berikutnya
Tanggal Berikutnya
Status
Dicatat Oleh
```

### Sheet `Tindak Lanjut`

Histori 1:N:

```text
No
ID Konseling
Tahun Ajaran
Tanggal Konseling
Tanggal Tindak Lanjut
Kelas
NISN
Nama Siswa
Perkembangan
Hasil/Kesepakatan
Rencana Berikutnya
Tanggal Berikutnya
Status
Dicatat Oleh
```

Konseling tidak boleh ikut export/dashboard Pimpinan/Guru/Wali/Siswa hanya karena actor mempunyai permission laporan lain.

## 9. Database-First

```text
WHERE / JOIN / GROUP BY / HAVING / ORDER BY / LIMIT / OFFSET
bounded membership/history query
batch aggregate
pagination
pivot ringan PHP bila diperlukan
```

Dilarang load seluruh dataset besar lalu melakukan agregasi utama di PHP/JS.

Batas export canonical maksimum 50.000 row per dataset sebelum user diminta mempersempit filter.

## 10. Mobile

Untuk role operasional:

- laporan/list mobile memakai card/adaptive presentation;
- tidak ada horizontal table scroll;
- Detail memakai modal scrollable/fullscreen-sm-down bila panjang;
- Nama menjadi informasi manusia utama;
- identifier sekunder.

## 11. Privasi

Laporan/export tidak mengekspor password/hash/token/credential atau dokumen personalia mentah.

Konseling BK memiliki boundary lebih ketat dari laporan BK umum dan tidak menjadi sumber data lintas-role.

## 12. Current Gate

```text
G3.2 Jurnal schema/report local+hosting         PASS
G3.3 dashboard                                 MERGED
G3.3.1 baseline BK export/privacy              PASS
17 Sep period-filter export source             IMPLEMENTED / UAT PENDING
17 Sep Konseling 2-sheet follow-up export      IMPLEMENTED / UAT PENDING
17 Sep localhost schema delta                  PREPARED / PENDING EXECUTION
17 Sep hosting delta                           NOT STARTED
PR #9                                          DRAFT / BELUM MERGE
```

Baseline hosting smoke tidak membuktikan export/schema rework 17 September.