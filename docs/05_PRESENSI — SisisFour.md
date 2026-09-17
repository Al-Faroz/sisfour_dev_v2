# Presensi Siswa & Presensi Mengajar — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 16 September 2026
**Business baseline:** G2 CLOSED
**Mobile implementation:** G3.2 — Guru/Wali Presensi & Jurnal **CLOSED / MERGED PR #7**

> Dokumen ini menyatakan business contract Presensi/Jurnal yang tetap berlaku dan UX contract G3.2. UI mobile tidak mengubah authorization, geofence, time-window, transaksi, atau scope server.

## 1. Waktu dan Tahun Aktif

Seluruh keputusan waktu menggunakan `Asia/Jakarta`.

Workflow input Presensi/Jurnal current-state menggunakan Tahun Ajaran aktif sesuai Service.

## 2. Presensi Siswa Resmi

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

## 3. Pola Input Presensi Guru

Default UI seluruh siswa = **Hadir**. Guru hanya mengubah siswa Sakit/Izin/Alpha.

Semua status tetap disimpan pada tabel `presensi`.

Pada mobile, identitas visual utama siswa adalah **Nama**, bukan NISN.

Canonical mobile:

```text
Siswa              Status
Ahmad Fulan        [H] [S] [I] [A]
```

NISN tetap tersedia untuk desktop/audit/search yang membutuhkan, tetapi tidak menjadi kolom rutin pada portrait mobile.

## 4. Actor Presensi Siswa

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

## 7. Bulk dan Transaction Presensi

Satu submit kelas diproses atomically.

Server memvalidasi actor, scope, tahun, membership, kelas, sesi, jadwal/mapping, existing record, status, dan snapshot.

Wajib mempertahankan:

```text
busy guard
no double-submit
server-confirmed success
input status tetap terlihat bila network/save gagal
```

Tidak ada silent offline queue Presensi.

## 8. Revisi Presensi

Revisi record tersimpan:

```text
Admin
Operator
Wali aktif kelas target
```

Guru biasa tidak merevisi record existing.

UI revisi tetap memakai Service yang sama dan tidak menghapus histori secara client-side.

## 9. Rekap Presensi

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

Sumber hanya Sesi Awal. Istilah `Top` di sini adalah ranking status Presensi dan tidak terkait sistem poin Pelanggaran BK yang telah dihentikan pada G3.3.1.

## 12. Presensi Mengajar / Jurnal

Parent table:

```text
presensi_mengajar
```

Satu record per `id_jadwal + tanggal`.

Semua sesi Jadwal dapat mempunyai Jurnal termasuk `Non Sesi`.

Status Guru:

```text
Hadir
Izin
Sakit
```

Field G3.2:

```text
status
materi      wajib
catatan     optional
```

`catatan` adalah catatan tambahan Jurnal dan tidak menggantikan `materi`.

Wali tidak mendapat hak Jurnal hanya karena menjadi Wali.

## 13. Exception Siswa pada Jurnal

G3.2 menambah child table:

```text
presensi_mengajar_siswa
```

Relasi:

```text
presensi_mengajar 1 --- N presensi_mengajar_siswa
```

Child hanya menyimpan exception siswa yang tidak mengikuti pembelajaran:

```text
Sakit
Izin
Alpha
```

Tidak ada status `Hadir` pada child. Tidak adanya row child berarti tidak ada exception S/I/A yang dicatat pada Jurnal tersebut, bukan klaim Presensi resmi bahwa siswa Hadir.

Field child:

```text
id
id_presensi_mengajar
id_siswa
nama_siswa_snapshot
nisn_snapshot
status
created_at
updated_at
```

Unique:

```text
UNIQUE(id_presensi_mengajar, id_siswa)
```

Snapshot nama/NISN dipertahankan untuk histori Jurnal.

## 14. Pemisahan Presensi Resmi vs Jurnal

```text
presensi
= kehadiran resmi sekolah

presensi_mengajar_siswa
= exception kehadiran pada satu pembelajaran/Jurnal
```

S/I/A pada Jurnal:

- tidak menulis/mengubah `presensi`;
- tidak masuk Rekap Presensi resmi;
- tidak masuk EWS Presensi;
- tidak masuk Signage Presensi;
- tidak mengubah Sesi Awal/Sesi Akhir;
- boleh berbeda dari Presensi resmi pada hari yang sama.

Contoh valid:

```text
Presensi Sesi Awal: Andi = Hadir
Jurnal Matematika:  Andi = Alpha
```

Makna: Andi hadir ke sekolah tetapi tidak mengikuti pembelajaran Matematika yang dicatat Guru.

## 15. Rule Siswa Jurnal

Siswa yang dapat dipilih harus berasal dari roster kelas Jurnal pada **tanggal Jurnal**.

Hari berjalan memakai current membership. Revisi historis memakai `riwayat_siswa` agar perpindahan kelas tidak merusak roster masa lalu.

Server menolak:

```text
siswa di luar roster kelas
status selain Sakit/Izin/Alpha
siswa yang sama dua kali pada satu Jurnal
exception siswa ketika status Guru bukan Hadir
```

Jika status Guru `Izin` atau `Sakit`, daftar siswa S/I/A harus kosong.

UI meminta konfirmasi sebelum mengosongkan daftar siswa ketika status Guru berubah dari `Hadir` menjadi `Izin/Sakit`.

## 16. Atomic Save / Revision Jurnal

Parent Jurnal + child siswa adalah satu mutation atomic:

```text
BEGIN
save/update presensi_mengajar
replace exact child presensi_mengajar_siswa
write activity log
COMMIT
```

Jika salah satu gagal, rollback semua.

Revisi Admin/Operator mengganti daftar child menjadi exact state terbaru dalam transaksi yang sama.

## 17. Actor Jurnal

- Guru/Pimpinan dengan scope diri hanya mengisi berdasarkan Jadwal sendiri sesuai permission aktual.
- Admin/Operator scope `SEMUA` dapat memilih Guru sesuai permission dan melakukan revisi sesuai rule.
- Guru biasa tidak mendapat revisi record existing.
- Semua actor tetap melalui Service; selector UI bukan authorization boundary.

Experience Guru boleh auto-select identity/Jadwal bila hanya satu opsi valid; auto-selection tidak boleh melewati Service.

## 18. Geofence & Time Window Jurnal

Status `Hadir` Guru mengikuti geofence bila setting aktif.

Status `Izin/Sakit` tidak memerlukan lokasi, tetapi actor non-`SEMUA` tetap terikat time-window sesuai Service.

Validasi tidak dipindahkan ke JavaScript.

## 19. UX Input Jurnal

Urutan form:

```text
Informasi Kelas / Mapel / Jam
Status Guru
Materi / Keterangan *
Catatan
Siswa Tidak Mengikuti Pembelajaran
Simpan Jurnal
```

Bagian siswa memakai search roster kelas:

```text
Nama = primary
NISN = secondary
```

UI hanya menambah siswa exception. Setelah ditambahkan, status wajib dipilih eksplisit:

```text
[S] [I] [A]
```

Tidak ada default S/I/A otomatis.

Badge ringkas:

```text
S n
I n
A n
```

UI menjelaskan bahwa status child hanya tersimpan pada Jurnal dan tidak mengubah Presensi resmi.

## 20. Laporan Jurnal

Listing utama:

```text
1 row = 1 Jurnal
```

Kolom/summary:

```text
Tanggal
Guru / Kelas
Mapel / Jam / Sesi
Status Guru
Materi / Catatan
Jumlah siswa S/I/A
Detail
```

Daftar nama siswa tidak di-join sebagai row utama.

Query strategy per page:

```text
Query parent Jurnal paginated
+
1 aggregate query child WHERE id_presensi_mengajar IN (...)
```

Tidak boleh N+1. Detail siswa dimuat on-demand.

## 21. Mobile UX

### Presensi Siswa

```text
Nama = primary identity
NISN hidden dari routine mobile table
2 kolom efektif: Siswa + Status
H/S/I/A reachable 360px
compact target ±40px
no horizontal table scroll
save action mudah dijangkau
```

### Jurnal

```text
status control 44px+
textarea Materi/Catatan nyaman keyboard mobile
student search name-first
S/I/A child controls reachable
save busy state
input dipertahankan saat failure
action mudah dijangkau
```

### Laporan

Desktop dapat memakai table ringkas. Mobile memakai card/list tanpa horizontal table scroll.

## 22. Network Failure

Tidak ada mutation dinyatakan sukses sebelum server sukses.

Jika network gagal:

```text
Presensi → pilihan status siswa dipertahankan
Jurnal   → status + materi + catatan + daftar siswa S/I/A dipertahankan
```

Tidak ada background replay otomatis.

## 23. Histori & Integrity

Jadwal nonaktif tetap dapat dibaca untuk laporan historis sesuai Service.

Permanent delete Siswa harus ditolak bila siswa pernah menjadi child pada `presensi_mengajar_siswa`.

FK child ke parent cascade untuk orphan safety bila parent dihapus oleh maintenance sah; FK child ke Siswa menjaga histori sesuai schema.

## 24. SQL Schema G3.2

Final scripts:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

Delta:

```text
menambah presensi_mengajar.catatan TEXT NULL
membuat presensi_mengajar_siswa
membuat unique/index/FK
menyediakan verification query
```

Status per 16 September 2026:

```text
localhost schema/UAT PASS
hosting schema PASS
PR #7 MERGED
```

## 25. Route Utama

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

G3.2 tidak menambah route baru.

## 26. Acceptance G3.2 — PASS / CLOSED

Semua contract berikut telah lulus:

- business authorization tetap;
- SQL localhost/hosting tervalidasi;
- no body/table horizontal overflow role operasional;
- Presensi name-first + H/S/I/A usable;
- Guru/Wali context benar;
- Jurnal self-flow ringkas;
- Materi + Catatan usable;
- roster siswa valid;
- child S/I/A tidak mengubah `presensi`;
- Guru Izin/Sakit menolak child;
- parent+child atomic;
- laporan 1 row per Jurnal;
- aggregate no N+1;
- Detail lazy-load;
- busy/network guard;
- geofence/time-window server-authoritative;
- desktop Admin/Operator compatibility;
- browser console clean.

G3.2 ditutup dan merged melalui PR #7 dengan merge commit `176e5f764850d030968524af47117f259449064c`.