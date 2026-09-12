# Presensi Siswa & Presensi Mengajar — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.


## 1. Waktu dan Tahun Aktif

Seluruh keputusan waktu menggunakan `Asia/Jakarta`.

## 2. Presensi Siswa

Tabel:

```text
presensi
```

Status:

```text
Hadir
Sakit
Izin
Alpha
```

Sesi:

```text
Sesi Awal
Sesi Akhir
```

### Sesi Awal

Sumber resmi laporan, Matrix, EWS, Signage, dan statistik.

### Sesi Akhir

Dokumentasi tambahan. Tidak masuk rekap resmi/EWS/Signage ranking.

## 3. Pola Input Guru

Default UI seluruh siswa = **Hadir**. Guru hanya mengubah siswa Sakit/Izin/Alpha.

Semua status tetap disimpan (Model A).

## 4. Actor

| Actor | Input | Revisi | View |
|---|---|---|---|
| Admin | Semua | Semua | Semua |
| Operator | Semua | Semua | Semua |
| Pimpinan | Tidak | Tidak | laporan sesuai permission |
| Guru terjadwal | sesuai Jadwal | Tidak | workflow sendiri |
| Wali | kelas wali | kelas wali | kelas wali |
| BK | Tidak | Tidak | EWS/BK sesuai permission |
| Siswa | Tidak | Tidak | diri |

## 5. Dual Guru + Wali

Akun Guru yang juga Wali mempertahankan kedua context.

Service menentukan context berdasarkan jadwal, mapping wali, target kelas, waktu, dan permission.

## 6. Geofencing

```text
geofencing_aktif
latitude_sekolah
longitude_sekolah
radius_geofencing
```

Validasi radius dilakukan server, bukan browser.

## 7. Bulk dan Transaction

Satu submit kelas diproses atomically.

Server memvalidasi actor, scope, tahun, membership, kelas, sesi, jadwal/mapping, existing record, status, dan snapshot.

## 8. Revisi

Revisi record tersimpan:

```text
Admin
Operator
Wali aktif kelas target
```

Guru biasa tidak merevisi record existing.

## 9. Rekap

Ketidakhadiran resmi:

```text
Sakit
Izin
Alpha
```

`Hadir` tidak dihitung sebagai ketidakhadiran.

Siswa tidak dianggap Alpha pada tanggal sebelum menjadi anggota kelas.

## 10. EWS Internal

EWS internal memakai Sesi Awal dan scope actor. Threshold internal mengikuti Service dan tidak boleh dicampur dengan Signage ranking.

## 11. Digital Signage

```text
Top 20 Alpha 14 hari
Top 20 Izin 14 hari
Top 20 Sakit 14 hari
Tidak Masuk Hari Ini (S/I/A)
```

Sumber hanya Sesi Awal.

## 12. Presensi Mengajar / Jurnal

Tabel:

```text
presensi_mengajar
```

Satu record per jadwal/tanggal.

Semua sesi Jadwal dapat mempunyai Jurnal termasuk `Non Sesi`.

Wali tidak mendapat hak Jurnal hanya karena menjadi Wali.

## 13. Histori

Jadwal nonaktif tetap dapat dibaca untuk laporan historis.

## 14. Route Utama

```text
/presensi/siswa
/presensi/siswa/input/{kelas}
/presensi/siswa/revisi/{kelas}
/presensi/siswa/rekap
/presensi/siswa/ews
/presensi/mengajar
/presensi/mengajar/input/{jadwal}
/presensi/mengajar/laporan
```
