# Presensi Siswa & Presensi Mengajar — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Business baseline:** G2 CLOSED / `main` setelah PR #5  
**Mobile implementation:** G3.2 — Guru/Wali Presensi & Jurnal

> Dokumen ini menyatakan business contract Presensi/Jurnal yang tetap berlaku dan UX contract G3.2. UI mobile tidak mengubah authorization, geofence, time-window, transaksi, atau scope server.

## 1. Waktu dan Tahun Aktif

Seluruh keputusan waktu menggunakan `Asia/Jakarta`.

Workflow input Presensi/Jurnal current-state menggunakan Tahun Ajaran aktif sesuai Service.

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

Pada mobile, identitas visual utama siswa adalah **Nama**, bukan NISN.

Canonical mobile:

```text
Siswa              Status
Ahmad Fulan        [H] [S] [I] [A]
```

NISN tetap tersedia untuk desktop/audit/search yang membutuhkan, tetapi tidak menjadi kolom rutin pada portrait mobile.

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

UI tidak memperluas hak actor. Service tetap memutuskan target data dan capability.

## 5. Dual Guru + Wali

Akun Guru yang juga Wali mempertahankan kedua context.

Service menentukan context berdasarkan jadwal, mapping wali, target kelas, waktu, dan permission.

Wali dapat input/revisi Presensi kelas wali sesuai rule, tetapi status Wali tidak memberi hak tambahan pada Jurnal Mengajar.

## 6. Geofencing

```text
geofencing_aktif
latitude_sekolah
longitude_sekolah
radius_geofencing
```

Validasi radius dilakukan server, bukan browser.

Browser hanya memperoleh lokasi ketika workflow save memang membutuhkannya. Keputusan sah/tidak sah tetap milik server.

## 7. Bulk dan Transaction

Satu submit kelas diproses atomically.

Server memvalidasi actor, scope, tahun, membership, kelas, sesi, jadwal/mapping, existing record, status, dan snapshot.

G3.2 wajib mempertahankan:

```text
busy guard
no double-submit
server-confirmed success
input status tetap terlihat bila network/save gagal
```

Tidak ada silent offline queue Presensi.

## 8. Revisi

Revisi record tersimpan:

```text
Admin
Operator
Wali aktif kelas target
```

Guru biasa tidak merevisi record existing.

UI revisi tetap memakai Service yang sama dan tidak menghapus histori secara client-side.

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

Status Jurnal:

```text
Hadir
Izin
Sakit
```

Materi/keterangan wajib untuk seluruh status.

Wali tidak mendapat hak Jurnal hanya karena menjadi Wali.

## 13. Actor Jurnal

Rule server tetap:

- Guru/Pimpinan yang mempunyai scope diri hanya mengisi berdasarkan Jadwal miliknya sendiri sesuai permission aktual.
- Admin/Operator scope `SEMUA` dapat memilih Guru sesuai permission dan melakukan revisi sesuai rule.
- Guru biasa tidak mendapatkan revisi record existing.
- Semua actor tetap melalui Service; selector UI bukan authorization boundary.

Pada experience Guru mobile, jika hanya ada satu identitas Guru valid, UI boleh memilihnya otomatis agar user tidak melakukan tap administratif yang tidak perlu.

Jika hanya ada satu Jadwal valid, UI boleh langsung memuat form Jurnal. Auto-selection tidak boleh melewati validasi Service.

## 14. Geofence & Time Window Jurnal

Status `Hadir` untuk Guru mengikuti geofence bila setting aktif.

Status `Izin/Sakit` tidak memerlukan lokasi, tetapi actor non-`SEMUA` tetap terikat time-window sesuai Service.

G3.2 tidak memindahkan validasi geofence atau time-window ke JavaScript.

## 15. Mobile UX G3.2

### Presensi Siswa

Wajib:

```text
Nama = primary identity
NISN = hidden dari routine mobile table
2 kolom efektif: Siswa + Status
H/S/I/A reachable pada 360px
compact touch target minimum ±40px
no horizontal table scroll
save action mudah dijangkau
```

Setiap H/S/I/A harus memiliki label aksesibel penuh (`Hadir`, `Sakit`, `Izin`, `Alpha`) walaupun teks visual mobile disingkat.

### Jurnal

Wajib:

```text
status control 44px+
textarea nyaman pada keyboard mobile
save busy state
input dipertahankan saat network/server gagal
action mudah dijangkau
Nama Guru tetap name-first; NIP hanya secondary search/disambiguation
```

UI boleh mempertahankan desktop controls lengkap selama mobile tidak menjadi padat atau horizontal-scroll.

## 16. Network Failure

Tidak ada mutation yang dinyatakan sukses sebelum response server sukses.

Jika network gagal saat save:

```text
Presensi → pilihan status siswa dipertahankan di layar
Jurnal   → status + materi/keterangan dipertahankan
```

User diberi pesan gagal dan dapat mencoba ulang. Tidak ada background replay otomatis.

## 17. Histori

Jadwal nonaktif tetap dapat dibaca untuk laporan historis sesuai Service.

UI G3.2 tidak menghapus atau menyederhanakan history backend.

## 18. Route Utama

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

G3.2 tidak membutuhkan route baru selama endpoint existing mencukupi.

## 19. Acceptance G3.2

Presensi/Jurnal Guru/Wali ACC bila:

- business authorization tetap sama;
- no body/table horizontal overflow pada mobile role operasional;
- Presensi name-first;
- H/S/I/A nyaman pada 360–412px;
- Guru/Wali context benar;
- Jurnal self-flow tidak meminta pilihan identitas berulang bila tidak perlu;
- textarea/status usable dengan keyboard mobile;
- busy guard bekerja;
- network failure tidak menghapus input;
- geofence/time-window masih server-authoritative;
- desktop Admin/Operator compatibility tetap normal;
- browser console bersih.
