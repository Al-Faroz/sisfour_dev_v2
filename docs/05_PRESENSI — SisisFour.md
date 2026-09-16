# Presensi Siswa & Presensi Mengajar — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Business baseline:** G2 CLOSED / `main` setelah PR #5  
**Mobile implementation:** G3.2 — Guru/Wali Presensi & Jurnal

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

G3.2 wajib mempertahankan:

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

Sumber hanya Sesi Awal.

## 12. Presensi Mengajar / Jurnal

Parent table:

```text
presensi_mengajar
```

Satu record per `id_jadwal + tanggal`.

Semua sesi Jadwal dapat mempunyai Jurnal termasuk `Non Sesi`.

Status Guru pada Jurnal:

```text
Hadir
Izin
Sakit
```

Field utama G3.2:

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

Child menyimpan hanya exception siswa yang tidak mengikuti pembelajaran:

```text
Sakit
Izin
Alpha
```

Tidak ada status `Hadir` pada child. Siswa yang tidak memiliki row child berarti **tidak ada exception S/I/A yang dicatat pada Jurnal tersebut**, bukan klaim Presensi resmi bahwa siswa Hadir.

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

Unique business key:

```text
UNIQUE(id_presensi_mengajar, id_siswa)
```

Snapshot nama/NISN dipertahankan untuk histori Jurnal.

## 14. Pemisahan Presensi Resmi vs Jurnal

Kontrak wajib:

```text
presensi
= kehadiran resmi sekolah

presensi_mengajar_siswa
= exception kehadiran pada satu pembelajaran/Jurnal
```

S/I/A pada Jurnal:

- tidak menulis atau mengubah tabel `presensi`;
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

Server wajib menolak:

```text
siswa di luar roster kelas
status selain Sakit/Izin/Alpha
siswa yang sama dua kali pada satu Jurnal
exception siswa ketika status Guru bukan Hadir
```

Jika status Guru `Izin` atau `Sakit`, daftar siswa S/I/A harus kosong.

UI wajib meminta konfirmasi sebelum mengosongkan daftar siswa ketika user mengubah status Guru dari `Hadir` menjadi `Izin/Sakit`.

## 16. Atomic Save / Revision Jurnal

Parent Jurnal dan child siswa adalah satu mutation atomic:

```text
BEGIN
save/update presensi_mengajar
replace exact child presensi_mengajar_siswa
write activity log
COMMIT
```

Jika salah satu langkah gagal:

```text
ROLLBACK semua
```

Tidak boleh terjadi parent tersimpan tetapi daftar siswa hanya tersimpan sebagian.

Revisi Admin/Operator mengganti daftar child menjadi exact state terbaru dalam transaksi yang sama.

## 17. Actor Jurnal

Rule server tetap:

- Guru/Pimpinan yang mempunyai scope diri hanya mengisi berdasarkan Jadwal miliknya sendiri sesuai permission aktual.
- Admin/Operator scope `SEMUA` dapat memilih Guru sesuai permission dan melakukan revisi sesuai rule.
- Guru biasa tidak mendapatkan revisi record existing.
- Semua actor tetap melalui Service; selector UI bukan authorization boundary.

Pada experience Guru mobile, jika hanya ada satu identitas Guru valid, UI boleh memilihnya otomatis agar user tidak melakukan tap administratif yang tidak perlu.

Jika hanya ada satu Jadwal valid, UI boleh langsung memuat form Jurnal. Auto-selection tidak boleh melewati validasi Service.

## 18. Geofence & Time Window Jurnal

Status `Hadir` untuk Guru mengikuti geofence bila setting aktif.

Status `Izin/Sakit` tidak memerlukan lokasi, tetapi actor non-`SEMUA` tetap terikat time-window sesuai Service.

G3.2 tidak memindahkan validasi geofence atau time-window ke JavaScript.

## 19. UX Input Jurnal G3.2

Urutan form setelah Jadwal dimuat:

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

UI tidak menampilkan seluruh roster sebagai form panjang. Guru mencari dan menambahkan hanya siswa exception.

Setelah siswa ditambahkan, status wajib dipilih eksplisit:

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

Pesan UI wajib menjelaskan bahwa status tersebut hanya tersimpan pada Jurnal dan tidak mengubah Presensi resmi.

## 20. Laporan Jurnal

Listing utama mempertahankan:

```text
1 row = 1 Jurnal
```

Kolom/summary utama:

```text
Tanggal
Guru / Kelas
Mapel / Jam / Sesi
Status Guru
Materi / Catatan
Jumlah siswa S/I/A
Detail
```

Daftar nama siswa tidak di-join sebagai row utama karena dapat menggandakan Jurnal.

### Query strategy

Per page:

```text
Query parent Jurnal paginated
+
1 aggregate query child WHERE id_presensi_mengajar IN (...)
```

Tidak boleh N+1 query per Jurnal.

Detail siswa dimuat on-demand saat user memilih `Detail`.

Detail menampilkan:

```text
informasi Jurnal
materi
catatan
summary S/I/A
nama siswa + NISN + status
```

Child bersifat exception-only sehingga ukuran data jauh lebih kecil daripada menyimpan seluruh roster setiap Jurnal.

## 21. Mobile UX G3.2

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
textarea Materi/Catatan nyaman pada keyboard mobile
student search name-first
S/I/A child controls reachable
save busy state
input dipertahankan saat network/server gagal
action mudah dijangkau
Nama Guru tetap name-first; NIP hanya secondary search/disambiguation
```

UI boleh mempertahankan desktop controls lengkap selama mobile tidak menjadi padat atau horizontal-scroll.

### Laporan

Desktop dapat memakai table ringkas. Mobile memakai card/list tanpa horizontal table scroll.

## 22. Network Failure

Tidak ada mutation yang dinyatakan sukses sebelum response server sukses.

Jika network gagal saat save:

```text
Presensi → pilihan status siswa dipertahankan di layar
Jurnal   → status + materi + catatan + daftar siswa S/I/A dipertahankan
```

User diberi pesan gagal dan dapat mencoba ulang. Tidak ada background replay otomatis.

## 23. Histori & Integrity

Jadwal nonaktif tetap dapat dibaca untuk laporan historis sesuai Service.

Permanent delete Siswa harus ditolak bila siswa pernah menjadi child pada `presensi_mengajar_siswa`.

FK child ke parent memakai cascade delete untuk menjaga orphan safety bila parent Jurnal memang dihapus oleh maintenance yang sah. FK child ke Siswa bersifat restrict untuk mempertahankan histori.

## 24. SQL Schema G3.2

Schema delta canonical tidak memakai CodeIgniter migration.

Localhost / development / UAT:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
```

Hosting / production:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

Kedua SQL:

```text
menambah presensi_mengajar.catatan TEXT NULL bila belum ada
membuat presensi_mengajar_siswa bila belum ada
membuat unique/index/FK pada saat tabel baru dibuat
menyediakan verification query
aman dijalankan ulang pada schema yang sudah memiliki delta G3.2
```

SQL localhost wajib diuji sebelum UAT fitur Jurnal baru. SQL hosting hanya dijalankan setelah backup production, PR G3.2 lulus UAT/merge/release disetujui, dan ada approval deploy eksplisit.

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

G3.2 tidak menambah route baru. Detail laporan memakai endpoint laporan existing dengan query `id_jurnal` dalam mode JSON.

## 26. Acceptance G3.2

Presensi/Jurnal Guru/Wali ACC bila:

- business authorization tetap sama;
- SQL schema localhost/hosting tervalidasi dan verification query PASS;
- no body/table horizontal overflow pada mobile role operasional;
- Presensi name-first;
- H/S/I/A Presensi nyaman pada 360–412px;
- Guru/Wali context benar;
- Jurnal self-flow tidak meminta pilihan identitas berulang bila tidak perlu;
- Materi + Catatan usable dengan keyboard mobile;
- search siswa hanya roster kelas/tanggal Jurnal;
- S/I/A child tersimpan tanpa mengubah tabel `presensi`;
- status Guru Izin/Sakit menolak child siswa;
- parent + child save/revisi atomic;
- laporan tetap 1 row per Jurnal;
- aggregate laporan tidak N+1;
- detail child lazy-load;
- busy guard bekerja;
- network failure tidak menghapus input;
- geofence/time-window masih server-authoritative;
- desktop Admin/Operator compatibility tetap normal;
- browser console bersih.
