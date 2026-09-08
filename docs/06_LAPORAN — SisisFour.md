# Laporan & Export — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Sumber Resmi

Perhitungan Presensi resmi:

```text
presensi.sesi = Sesi Awal
```

Sesi Akhir tidak masuk total resmi.

# 2. Permission

```text
laporan_matrix.view
laporan_export.generate
laporan_jurnal.view
laporan_jurnal.export
```

# 3. Scope

Matrix/Export Presensi:

```text
SEMUA
KELAS_DIAMPU
```

Laporan Jurnal:

```text
SEMUA
DIRI_SENDIRI
```

# 4. Matrix

Route:

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

Tanda `-` tidak otomatis berarti Alpha.

# 5. Membership Historis

Tanggal sebelum masuk kelas atau setelah keluar dari kelas tidak boleh dihitung Alpha untuk kelas tersebut.

Sumber histori:

```text
riwayat_siswa
```

# 6. Export Presensi

Route:

```text
/laporan/presensi/export
/laporan/presensi/export/bulanan
/laporan/presensi/export/semester
```

Format utama: XLSX.

Total H/S/I/A hanya Sesi Awal.

# 7. Semester

```text
Ganjil → Juli–Desember
Genap  → Januari–Juni
```

Siswa Pindah/Keluar/Lulus tetap dapat muncul jika memiliki data pada periode.

# 8. Laporan Jurnal

Route:

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
NIP
Guru
Kelas
Mapel
Sesi
Status
Materi
Tahun
Semester
```

# 9. Histori Jadwal

Laporan Jurnal tidak menghilangkan row karena Jadwal kini Nonaktif.

# 10. Database-First

Pola Matrix:

```text
1 query membership kelas
+
1 query Presensi kelas untuk satu bulan
→ pivot PHP ringan
```

Dilarang query per siswa per tanggal.

# 11. Privasi

Tidak mengekspor:

- password/hash;
- token;
- biodata keluarga yang tidak relevan;
- data BK dalam laporan Presensi.

# 12. Audit

Export penting dicatat ke `log_activity` bila logger modul digunakan.

# 13. Error Rule

Server menolak:

- kelas di luar scope;
- tahun invalid;
- periode invalid;
- Wali meminta kelas lain;
- Guru meminta Jurnal Guru lain;
- export tanpa permission.

# 14. Checkpoint

- Matrix;
- membership historis;
- export bulanan;
- export semester;
- Laporan/Export Jurnal;
- scope;
- EXPLAIN query besar.
