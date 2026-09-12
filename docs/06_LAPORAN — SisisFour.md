# Laporan & Export — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.


## 1. Sumber Resmi

```text
presensi.sesi = 'Sesi Awal'
```

Sesi Akhir tidak masuk total resmi.

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

## 6. Laporan Jurnal

```text
/laporan/jurnal
/laporan/jurnal/json
/laporan/jurnal/export
```

Kolom utama:

```text
Tanggal
Hari
Jam
Guru
Kelas
Mapel
Sesi
Status
Materi
Tahun
Semester
```

Jadwal nonaktif tidak menghapus histori jurnal.

## 7. Database-First

```text
query membership/history bounded
+
query presensi periode bounded
+
pivot ringan PHP
```

Dilarang N+1 per siswa × tanggal.

## 8. Role

- Admin/Operator sesuai permission SEMUA.
- Pimpinan supervisi/read-only.
- Wali kelas wali bila scope diberikan.
- Guru biasa tidak mempunyai laporan kelas hanya karena mengajar.
- Siswa tidak mendapat laporan kelas global.

## 9. Privasi

Laporan tidak mengekspor password/hash/token, credential, data BK yang tidak relevan, atau dokumen personalia mentah.

## 10. Error

Server menolak kelas di luar scope, periode/tahun invalid, Wali kelas lain, Guru data Guru lain tanpa hak, dan export tanpa permission.
