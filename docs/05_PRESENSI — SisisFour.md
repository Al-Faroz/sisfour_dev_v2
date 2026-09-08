# ✅ Presensi Siswa & Presensi Mengajar — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menjadi kontrak implementasi Presensi Siswa dan Presensi Mengajar/Jurnal.

Semua implementasi Presensi harus menggunakan Master Data final sebagai sumber:

```text
Siswa
Anggota Kelas
Riwayat Siswa
Kelas
Tahun Ajaran
Mapping Wali Kelas
Jadwal Guru
Mata Pelajaran
Users / RBAC
Setting Sistem
```

Timezone canonical aplikasi:

```text
Asia/Jakarta
```

Sumber waktu untuk authorization adalah waktu server aplikasi, bukan waktu client.

---

# BAGIAN A — DEFINISI UMUM

## 1. Tahun Operasional

Presensi selalu terikat:

```text
id_tahun
```

Tahun default adalah record:

```text
tahun_ajaran.status_aktif = 1
deleted_at IS NULL
```

Tidak boleh membuat Presensi tanpa Tahun Ajaran aktif.

Jika endpoint menerima `id_tahun`, server tetap harus memastikan user berhak mengakses tahun tersebut.

Untuk input dan revisi Presensi Siswa, tahun yang boleh dimutasi adalah Tahun Ajaran aktif kecuali dokumen operasional masa depan menetapkan mekanisme koreksi lintas tahun secara eksplisit.

---

## 2. Kelas dan Keanggotaan Siswa

Untuk input Presensi tanggal berjalan, daftar siswa kelas berasal dari:

```text
anggota_kelas
JOIN siswa
```

dengan:

```text
anggota_kelas.id_kelas = kelas target
anggota_kelas.id_tahun = tahun aktif
siswa.status_aktif = Aktif
siswa.deleted_at IS NULL
```

Server tidak boleh mempercayai membership kelas dari request.

Untuk histori dan laporan berdasarkan tanggal, `riwayat_siswa` harus digunakan bila diperlukan agar perpindahan kelas tidak merusak histori.

Batas histori keanggotaan:

```text
tanggal_mulai <= tanggal target
AND (
    tanggal_selesai IS NULL
    OR tanggal_selesai >= tanggal target
)
```

Siswa yang belum menjadi anggota kelas pada tanggal target tidak boleh dianggap Alpha hanya karena tidak mempunyai record Presensi.

---

## 3. Jadwal Aktif

Untuk workflow Guru biasa, jadwal harus:

```text
jadwal_guru.status_jadwal = Aktif
jadwal_guru.id_tahun = tahun aktif
```

Jadwal Nonaktif hanya untuk histori dan tidak dapat digunakan input Presensi baru.

Jadwal lama yang menjadi Nonaktif setelah reimport tidak boleh dihapus atau dipindahkan referensinya dari histori Presensi/Jurnal.

---

## 4. Hierarki Kewenangan Operasional

Jika seorang user mempunyai beberapa role/konteks sekaligus, urutan kewenangan bisnis adalah:

```text
Admin
>
Operator
>
Pimpinan
>
Wali Kelas
>
Guru biasa
>
BK
>
Siswa
```

Ketentuan:

- permission efektif tetap merupakan union multi-role;
- Admin/Operator dengan scope `SEMUA` mengalahkan pembatas Guru/Wali pada aksi yang memang mereka miliki;
- Pimpinan tetap readonly;
- Wali bukan role database, melainkan konteks dari `mapping_wali_kelas` aktif;
- keputusan akhir Presensi harus dilakukan di Service per target kelas/tanggal/sesi/aksi.

---

# BAGIAN B — PRESENSI SISWA

## 5. Status

Status Presensi Siswa hanya:

```text
Hadir
Sakit
Izin
Alpha
```

Tidak ada status Terlambat.

Status yang sama digunakan pada Sesi Awal dan Sesi Akhir.

---

## 6. Sesi Presensi Siswa

Presensi Siswa hanya:

```text
Sesi Awal
Sesi Akhir
```

`Non Sesi` pada Jadwal Guru tidak menghasilkan kewajiban Presensi Siswa.

### 6.1 Sesi Awal

- Presensi resmi;
- dihitung Matrix;
- dihitung laporan resmi;
- dihitung EWS;
- digunakan statistik ketidakhadiran.

### 6.2 Sesi Akhir

- dokumentasi;
- tersimpan;
- menggunakan status `Hadir/Sakit/Izin/Alpha`;
- tidak dihitung laporan resmi;
- tidak dihitung Matrix;
- tidak dihitung EWS.

---

## 7. Hubungan Jadwal dan Sesi

Untuk Guru biasa:

- Guru hanya dapat memulai input bila memiliki jadwal aktif untuk kelas yang bersangkutan;
- jadwal harus cocok dengan hari/tanggal;
- jadwal `Non Sesi` tidak membuka Presensi Siswa;
- sesi Presensi mengikuti jadwal `Sesi Awal` atau `Sesi Akhir`;
- time-window dan geofence diterapkan server-side.

Sistem harus mencegah konfigurasi operasional yang menyebabkan lebih dari satu kewajiban Presensi Siswa identik untuk kelas/tanggal/sesi.

Unique database menjadi guard terakhir:

```text
(id_kelas, tanggal, sesi, id_siswa)
```

---

## 8. Hak Akses Presensi Siswa

| Actor | Input | Lihat Tersimpan | Revisi | Scope |
|---|---|---|---|---|
| Admin | Ya | Ya | Ya | SEMUA |
| Operator | Ya | Ya | Ya | SEMUA |
| Pimpinan | Tidak | Ya | Tidak | SEMUA / readonly |
| BK | Tidak | Tidak langsung | Tidak | — |
| Guru biasa | Ya | Tidak | Tidak | KELAS_TERJADWAL |
| Wali | Ya | Ya | Ya | KELAS_DIAMPU |
| Siswa | Tidak | Diri | Tidak | DIRI_SENDIRI |

Revisi Presensi Siswa hanya dapat dilakukan oleh:

```text
Admin
Operator
Wali aktif kelas target
```

Guru biasa tidak dapat revisi.

---

## 9. Guru Biasa

Guru biasa:

1. harus memiliki `id_guru`;
2. harus memiliki jadwal Aktif;
3. hanya boleh kelas terjadwal;
4. hanya boleh sesi sesuai Jadwal;
5. terikat time-window;
6. terikat geofencing;
7. setelah submit tidak boleh membuka data Presensi tersimpan;
8. tidak boleh revisi.

Server harus memvalidasi semua rule tersebut, bukan hanya tombol UI.

Jika Guru mempunyai role lain dengan scope lebih tinggi, permission efektif mengikuti union multi-role dan hak role yang lebih tinggi berlaku.

---

## 10. Wali Kelas

Wali adalah Guru yang mempunyai mapping aktif:

```text
mapping_wali_kelas.id_guru = user.id_guru
mapping_wali_kelas.id_tahun = tahun aktif
mapping_wali_kelas.deleted_at IS NULL
```

Untuk kelas Wali:

- dapat input Sesi Awal;
- dapat input Sesi Akhir;
- dapat melihat data tersimpan;
- dapat revisi;
- dapat revisi seluruh tanggal dalam Tahun Ajaran aktif;
- hak berlaku selama mapping Wali masih aktif;
- bila mapping berakhir, hak Wali lama langsung hilang;
- Wali baru menjadi pihak yang berhak atas histori kelas pada Tahun Ajaran aktif.

Jika Wali mengajar kelas lain yang bukan kelas walinya, aturan Guru biasa berlaku.

---

## 11. Dual-Context Guru + Wali

Seorang user dapat menjadi Wali kelas sekaligus memiliki Jadwal Guru di kelas yang sama.

Aturan final:

### 11.1 Saat Jadwal Aktif dan Time-Window Valid

Jika pada kelas Wali sendiri terdapat jadwal aktif yang cocok dengan tanggal dan sesi, maka jalur input normal pertama menggunakan konteks:

```text
GURU_TERJADWAL
```

Akibatnya:

```text
time-window berlaku
geofence berlaku
sesi harus cocok dengan jadwal
```

Ini menjaga kewajiban Guru yang memang memiliki Jadwal Sesi Awal/Akhir.

### 11.2 Fallback Wali

Jika user adalah Wali aktif kelas target dan jalur Guru Terjadwal tidak dapat digunakan, misalnya:

- tidak ada jadwal dirinya pada sesi tersebut; atau
- time-window jadwal sudah lewat;

maka Service boleh menggunakan:

```text
KELAS_DIAMPU
```

sebagai fallback.

Pada fallback Wali:

```text
time-window tidak berlaku
geofence tidak berlaku
```

Wali tetap boleh melakukan input pada kelas walinya.

### 11.3 Contoh

```text
Pak A = Wali 7-A
Jadwal Sesi Awal 7-A = 07:00–08:00
```

Pada 07:30:

```text
→ Guru Terjadwal
→ time-window berlaku
→ geofence berlaku
```

Pada 10:00 dan Presensi belum diinput:

```text
→ jalur jadwal sudah lewat
→ fallback Wali
→ tetap boleh input 7-A
→ tanpa time-window
→ tanpa geofence
```

Jika Pak A mengajar 8-B tetapi bukan Wali 8-B:

```text
→ Guru biasa
→ wajib jadwal aktif
→ wajib sesi cocok
→ wajib time-window
→ wajib geofence
→ tidak ada fallback Wali
```

---

## 12. Admin dan Operator

Admin/Operator:

- semua kelas;
- input administratif;
- bebas time-window;
- bebas geofencing;
- dapat revisi;
- dapat melihat data tersimpan.

Jika user juga mempunyai role Guru/Wali, hak Admin/Operator yang memiliki scope `SEMUA` tetap menang untuk permission terkait.

`id_guru_input`:

- boleh diisi `id_guru` actor bila actor memang terkait Guru;
- boleh `NULL` bila Admin/Operator tidak mempunyai identitas Guru yang relevan;
- audit actor aplikasi tetap menggunakan `user_id`/`updated_by`.

Tidak boleh memalsukan actor dari request client.

---

## 13. Pimpinan

Pimpinan:

- view-only;
- tidak boleh input;
- tidak boleh revisi;
- melihat semua sesuai permission.

---

## 14. Siswa

Siswa hanya dapat membaca miliknya sendiri.

Rincian UI siswa fokus:

```text
Sakit
Izin
Alpha
```

Hadir tidak perlu ditampilkan pada daftar detail.

Query wajib dibatasi:

```text
id_siswa = session('id_siswa')
```

---

## 15. Tampilan Input

Daftar seluruh siswa kelas tanpa pagination.

Default:

```text
Hadir
```

Setiap siswa mempunyai button group:

```text
[Hadir] [Sakit] [Izin] [Alpha]
```

Button aktif harus terlihat jelas.

Nama dan NISN hanya ditampilkan sesuai kebutuhan operasional dan scope.

---

## 16. Bulk Save

Submit Presensi Siswa adalah operasi bulk satu kelas.

Request minimum:

```text
id_kelas
tanggal
sesi
daftar siswa + status
latitude/longitude bila wajib geofence
```

Server tidak mempercayai:

```text
nama siswa
kelas membership
id_guru
scope
status Wali
role client
```

yang dikirim client.

Semua di-resolve dari database/session.

---

## 17. Atomic Transaction Presensi

Satu submit kelas harus transaction.

Alur:

```text
BEGIN
↓
validasi permission
↓
resolve tahun aktif
↓
validasi kelas
↓
resolve actor dan konteks target
↓
resolve SEMUA / GURU_TERJADWAL / WALI / TIDAK_ADA
↓
validasi membership siswa server-side
↓
validasi time-window bila wajib
↓
validasi geofence bila wajib
↓
validasi existing record / hak revisi
↓
insert/update seluruh siswa
↓
log activity
↓
COMMIT
```

Jika satu siswa invalid:

```text
ROLLBACK seluruh batch
```

Tidak boleh terjadi setengah kelas tersimpan.

---

## 18. Snapshot

Tabel Presensi menyimpan:

```text
id_siswa
nama_siswa_snapshot
id_guru_input
nama_guru_input_snapshot
```

Snapshot diambil server dari data master saat record dibuat.

Tujuan:

- histori tetap terbaca jika nama berubah;
- laporan lama tidak tergantung nama master saat ini;
- record tetap informatif bila FK tertentu menjadi NULL.

Saat revisi status, snapshot historis tidak perlu ditimpa hanya karena nama master saat ini berubah.

---

## 19. Input Baru vs Revisi

### 19.1 Input Baru

Bila record belum ada:

```text
INSERT
created_at
```

### 19.2 Revisi

Bila record sudah ada:

- hanya Admin/Operator/Wali aktif yang berhak;
- update status;
- update `updated_at`;
- isi `updated_by`;
- `created_at` tidak boleh ditimpa.

Guru biasa tidak boleh mengubah record existing.

Jika request Guru biasa menemukan record existing, server menolak, bukan melakukan overwrite diam-diam.

### 19.3 Batas Revisi Wali

Wali aktif boleh revisi seluruh tanggal dalam Tahun Ajaran aktif untuk kelas yang saat ini dia walikan.

Jika mapping Wali berganti:

```text
Wali lama → hak revisi hilang
Wali baru → hak revisi histori tahun aktif diperoleh
```

---

## 20. Proteksi Duplicate

Unique:

```text
id_kelas
tanggal
sesi
id_siswa
```

Service harus menangani race condition dengan transaction dan DB constraint.

---

# BAGIAN C — TIME WINDOW

## 21. Rumus

Guru Terjadwal dapat input hanya:

```text
jam_mulai
≤ waktu server
≤ jam_selesai + 15 menit
```

Timezone:

```text
Asia/Jakarta
```

Server time menjadi sumber keputusan final.

Client time tidak boleh menjadi sumber authorization.

---

## 22. Pengecualian Presensi Siswa

| Actor/Konteks | Time-Window |
|---|---|
| Admin | bebas |
| Operator | bebas |
| Guru Terjadwal | terikat |
| Wali pada fallback kelas Wali | bebas |
| Wali di kelas non-Wali | terikat sebagai Guru biasa |
| Pimpinan | tidak input |
| BK | tidak input |
| Siswa | tidak input |

Wali yang sedang berada di time-window jadwal kelas walinya diproses sebagai Guru Terjadwal. Bila jalur jadwal tidak valid/berakhir, hak Wali boleh menjadi fallback.

---

# BAGIAN D — GEOFENCING

## 23. Tujuan

Geofence memastikan Guru mapel berada di lokasi sekolah ketika mengklaim aktivitas yang mensyaratkan keberadaan fisik.

---

## 24. Setting

Setting canonical:

```text
geofencing_aktif
latitude_sekolah
longitude_sekolah
radius_geofencing
```

Default radius:

```text
500 meter
```

Koordinat disimpan di `setting_sistem` dan dapat diubah Admin.

---

## 25. Haversine

Perhitungan dilakukan server-side.

Input device:

```text
latitude
longitude
```

Output:

```text
distance meter
inside / outside
```

Validasi:

- latitude `-90..90`;
- longitude `-180..180`;
- null/invalid ditolak bila geofence wajib.

---

## 26. Presensi Siswa Geofence

Guru Terjadwal:

```text
wajib bila geofencing_aktif = 1
```

Admin/Operator:

```text
tidak wajib
```

Wali fallback pada kelas Wali:

```text
tidak wajib
```

Jika global OFF:

```text
semua bebas lokasi
```

GPS gagal/permission ditolak saat geofence wajib:

```text
submit ditolak
```

Tidak ada override manual oleh Guru biasa.

---

# BAGIAN E — TANGGAL LIBUR / KALENDER

## 27. Tidak Ada Gate Kalender Libur pada v0.5

Presensi v0.5 tidak menggunakan tabel kalender libur sebagai gate input.

Sistem tidak perlu menolak input hanya karena tanggal tersebut secara nyata merupakan hari libur.

Jika user berwenang menginput Presensi pada tanggal tersebut dan seluruh permission/scope lain valid, data boleh tersimpan.

Jika input tersebut keliru, koreksi dilakukan melalui mekanisme revisi oleh actor yang berhak.

Tidak perlu menambahkan modul Hari Libur hanya untuk memblokir Presensi pada v0.5.

---

# BAGIAN F — PRESENSI MENGAJAR / JURNAL

## 28. Definisi

Jurnal Mengajar terpisah dari Presensi Siswa.

Sumber workflow:

```text
jadwal_guru
```

Semua jadwal aktif:

- Sesi Awal;
- Sesi Akhir;
- Non Sesi;

mewajibkan jurnal.

---

## 29. Status Jurnal

```text
Hadir
Izin
Sakit
```

Tidak ada Alpha.

---

## 30. Materi

`materi` wajib untuk seluruh status.

Contoh Izin/Sakit:

```text
Memberikan tugas melalui grup kelas.
```

Tidak boleh menyimpan materi kosong atau whitespace-only.

---

## 31. Unique Jurnal

```text
UNIQUE(id_jadwal, tanggal)
```

Satu baris jadwal satu jurnal per hari.

---

## 32. Hak Akses Jurnal

| Actor | Lihat Jadwal | Input | Revisi | Scope |
|---|---|---|---|---|
| Admin | Semua | Ya | Ya | SEMUA |
| Operator | Semua | Ya | Ya | SEMUA |
| Pimpinan | Semua | Diri bila punya jadwal | Tidak | sesuai permission |
| BK | Tidak | Tidak | Tidak | — |
| Guru | Diri | Ya | Tidak | DIRI_SENDIRI |
| Wali | Diri | Ya | Tidak | DIRI_SENDIRI |
| Siswa | Tidak | Tidak | Tidak | — |

Status Wali tidak menambah kemampuan revisi Jurnal.

Revisi Jurnal hanya Admin/Operator.

---

## 33. Validasi `id_guru`

Untuk Guru/Wali/Pimpinan:

```text
request id_guru
```

tidak dipercaya.

Server menggunakan:

```text
session id_guru
```

dan memastikan jadwal memang milik user.

Admin/Operator boleh input atas nama Guru lain, tetapi Service harus memastikan jadwal target benar-benar milik Guru tersebut.

---

## 34. Time Window Jurnal

Guru:

```text
jam_mulai
≤ sekarang
≤ jam_selesai + 15 menit
```

Admin/Operator administratif tidak terikat.

Untuk status Izin/Sakit, time-window tetap mengikuti aturan input Jurnal.

---

## 35. Geofence Jurnal

### 35.1 Hadir

Guru status Hadir:

```text
geofence wajib
```

jika setting aktif.

### 35.2 Izin/Sakit

Tidak wajib geofence.

### 35.3 Admin/Operator

Tidak wajib.

---

## 36. Snapshot Jurnal

Simpan:

```text
id_guru
nama_guru_snapshot
```

Juga simpan:

```text
id_jadwal
id_kelas
id_tahun
```

agar histori tetap eksplisit.

---

# BAGIAN G — JADWAL REIMPORT

## 37. Jadwal Lama Nonaktif

Reimport Jadwal membuat set lama Nonaktif, bukan delete.

Presensi/Jurnal historis yang sudah tersimpan:

- tetap menunjuk `id_jadwal` lama bila tabel terkait menyimpan referensi jadwal;
- tetap valid;
- tidak boleh ikut berubah ke Jadwal baru.

Jangan melakukan cascading update histori ke Jadwal baru.

---

## 38. Jadwal Baru

Jadwal baru hanya berlaku untuk input setelah import.

Jika kebutuhan administrasi mengharuskan koreksi histori, koreksi dilakukan pada record Presensi/Jurnal dengan authorization khusus, bukan dengan memindahkan foreign key secara massal.

---

# BAGIAN H — SISWA MASUK / PINDAH / KELUAR

## 39. Siswa Baru di Tengah Semester

Siswa yang baru menjadi anggota kelas mulai tanggal tertentu hanya boleh muncul pada input setelah menjadi anggota.

Untuk histori sebelum masuk kelas:

- jangan generate Presensi retroaktif otomatis;
- laporan tidak boleh menganggap tidak adanya record sebelum membership sebagai Alpha.

Gunakan `riwayat_siswa.tanggal_mulai` sebagai batas keanggotaan.

---

## 40. Siswa Pindah/Keluar/Lulus

Setelah status siswa bukan Aktif:

- jangan tampilkan pada input baru;
- histori Presensi lama tetap ada;
- laporan historis tetap menggunakan snapshot dan riwayat membership.

---

# BAGIAN I — EWS

## 41. EWS Alpha

Kriteria:

```text
>= 3 Alpha
dalam 14 hari terakhir
```

Sumber:

```text
Sesi Awal saja
```

Tidak menghitung Sesi Akhir.

---

## 42. Scope EWS

- Admin: semua;
- Operator: semua;
- Pimpinan: semua readonly;
- BK: sesuai permission;
- Wali: kelas Wali.

EWS tidak perlu menu sidebar; dapat menjadi widget dashboard.

---

# BAGIAN J — STRATEGI QUERY DAN SKALA DATA

## 43. Database-First Processing

Tabel `presensi` adalah dataset besar.

Seluruh operasi utama berikut harus dilakukan di database melalui Query Builder/SQL:

```text
WHERE
JOIN
BETWEEN
COUNT
SUM
GROUP BY
HAVING
ORDER BY
LIMIT / OFFSET
```

PHP Service tidak boleh melakukan:

```text
findAll seluruh Presensi
↓
foreach semua row
↓
filter / count / group
```

---

## 44. Query Input Presensi

Untuk form satu kelas:

```text
1 query membership siswa kelas
+
1 query Presensi existing kelas/tanggal/sesi bila diperlukan
```

Jangan melakukan query Presensi satu per siswa.

Untuk validasi Guru/Wali, query scope harus bounded berdasarkan user, tahun, kelas, tanggal, dan sesi yang diperlukan.

---

## 45. Query EWS

Contoh pola:

```sql
SELECT
    id_siswa,
    COUNT(*) AS total_alpha
FROM presensi
WHERE id_tahun = ?
  AND sesi = 'Sesi Awal'
  AND status = 'Alpha'
  AND tanggal BETWEEN ? AND ?
GROUP BY id_siswa
HAVING COUNT(*) >= 3;
```

Database menghitung jumlah Alpha.

PHP hanya:

- memformat hasil;
- menerapkan scope tambahan bila belum sepenuhnya ada di query;
- menggabungkan metadata siswa yang diperlukan.

---

## 46. Histori Presensi

Daftar histori besar wajib:

```text
server-side filter
server-side sort
server-side pagination
```

Dilarang mengirim puluhan/ratusan ribu row ke DataTables client-side.

---

## 47. Query Boundary

Setiap query Presensi harus memiliki minimal salah satu boundary yang relevan:

```text
id_tahun
id_kelas
id_siswa
tanggal/periode
sesi
scope user
```

Query tanpa boundary terhadap tabel Presensi tidak boleh digunakan pada request Web normal.

---

## 48. Index Canonical Presensi

Index tambahan canonical yang digunakan untuk query besar:

```text
idx_presensi_kelas_periode (id_tahun, id_kelas, sesi, tanggal)
idx_presensi_siswa_periode (id_siswa, id_tahun, sesi, tanggal)
```

Tambahan index lain hanya dibuat bila `EXPLAIN` menunjukkan kebutuhan nyata.

Jangan menambah index berlebihan yang meningkatkan write cost tanpa alasan.

---

# BAGIAN K — API / JSON

## 49. Dual Output

Endpoint yang dibutuhkan mobile harus dapat menghasilkan JSON.

JSON response tidak boleh membocorkan data di luar scope.

Contoh:

```json
{
  "status": "success",
  "message": "Data berhasil dimuat.",
  "data": []
}
```

---

# BAGIAN L — LOG & AUDIT

## 50. Audit Revisi

Revisi Presensi:

```text
updated_at
updated_by
```

`updated_by` adalah `users.id` actor aplikasi.

Jika diperlukan audit lebih detail, tulis `log_activity` dengan field canonical:

```text
id_user
aksi
modul
keterangan
waktu
```

Original `created_at` tidak boleh ditimpa saat revisi.

---

# BAGIAN M — ERROR RULE

## 51. Error Penting

Server harus menolak dengan jelas bila:

- tidak ada Tahun aktif;
- jadwal tidak aktif untuk jalur Guru Terjadwal;
- kelas tidak valid;
- siswa bukan anggota pada tanggal yang relevan;
- Guru biasa tidak memiliki jadwal;
- Guru mencoba membuka hasil tersimpan;
- Guru mencoba revisi;
- Guru di luar time-window dan bukan Wali target/actor lebih tinggi;
- Guru di luar geofence saat geofence wajib;
- duplicate record;
- sesi Non Sesi dipakai untuk Presensi Siswa;
- Wali mengakses kelas bukan Wali dan tidak punya jadwal yang sah;
- request mencampur siswa dari kelas lain;
- koordinat tidak valid saat wajib;
- materi Jurnal kosong;
- Guru mencoba jurnal milik Guru lain.

Hari libur bukan alasan otomatis untuk menolak Presensi pada v0.5.

---

# BAGIAN N — ROUTE DAN SERVICE

## 52. Service Presensi Siswa

`PresensiService` minimal menangani:

- resolve tahun aktif;
- resolve permission;
- resolve actor multi-role;
- resolve scope `SEMUA`;
- resolve kelas terjadwal;
- resolve kelas Wali;
- resolve dual-context per target kelas/tanggal/sesi/aksi;
- menerapkan jalur Guru Terjadwal lebih dulu bila valid;
- menerapkan fallback Wali untuk kelas Wali bila jalur jadwal tidak valid/berakhir;
- load siswa kelas secara bounded;
- validasi membership tanggal;
- validasi time-window;
- validasi geofence;
- bulk save atomic;
- revisi Admin/Operator/Wali;
- read sesuai scope;
- EWS source query;
- audit actor.

Presensi Service tidak boleh hanya bergantung pada scalar `resolveScope()` global untuk seluruh kelas.

---

## 53. GeofencingService

Minimal method:

```text
hitungJarak(...)
isDalamRadius(...)
validasiKoordinat(...)
```

Tidak menyimpan keputusan hanya di frontend.

---

## 54. Service Jurnal

Minimal menangani:

- daftar jadwal hari ini;
- validasi ownership Guru;
- validasi jadwal Aktif;
- validasi time-window;
- validasi geofence berdasarkan status;
- save;
- revisi administratif Admin/Operator;
- read sesuai scope.

---

# BAGIAN O — CHECKPOINT FINAL

## 55. Presensi Siswa

- input Sesi Awal;
- input Sesi Akhir;
- Sesi Akhir tetap `Hadir/Sakit/Izin/Alpha`;
- default Hadir;
- bulk atomic;
- Guru sesuai Jadwal;
- Guru hanya sesi sesuai Jadwal;
- Guru tidak bisa melihat saved;
- Guru tidak bisa revisi;
- Wali bisa input kelas Wali;
- Wali bisa saved/revisi;
- Wali bisa revisi seluruh tanggal pada Tahun Ajaran aktif;
- hak Wali lama hilang setelah mapping tidak aktif;
- Wali baru memperoleh hak histori kelas tahun aktif;
- dual-context Guru/Wali diuji;
- saat jadwal valid Wali diproses sebagai Guru Terjadwal;
- saat time-window lewat Wali dapat fallback KELAS_DIAMPU;
- Wali di kelas lain tetap Guru biasa;
- Admin/Operator full;
- Admin/Operator menang pada multi-role;
- `id_guru_input` boleh NULL bila actor Admin/Operator bukan Guru;
- Pimpinan readonly;
- Siswa diri;
- time-window Asia/Jakarta;
- geofence server-side;
- geofence global OFF berfungsi;
- duplicate guard;
- snapshots;
- history membership respected;
- siswa tengah semester tidak dianggap Alpha sebelum masuk;
- tidak ada gate kalender libur.

## 56. Jurnal

- semua sesi termasuk Non Sesi;
- status benar;
- materi wajib;
- Guru hanya diri;
- Admin/Operator atas nama;
- time-window;
- geofence Hadir;
- Izin/Sakit tanpa geofence;
- unique jadwal/tanggal;
- revisi Guru ditolak;
- revisi Admin/Operator berhasil;
- Wali tidak memperoleh hak revisi Jurnal;
- Jadwal lama Nonaktif tetap historis.

---

# BAGIAN P — KEPUTUSAN YANG SUDAH DIKUNCI

## 57. Keputusan Final yang Tidak Perlu Ditanyakan Ulang

Keputusan berikut sudah final untuk v0.5 dan menjadi acuan implementasi:

1. Timezone aplikasi adalah `Asia/Jakarta`.
2. `.env` boleh override config, tetapi seluruh implementasi Presensi harus menggunakan waktu server WIB sebagai sumber authorization.
3. Wali bukan role; Wali berasal dari mapping aktif Tahun Ajaran aktif.
4. Hierarki bisnis: Admin > Operator > Pimpinan > Wali > Guru > BK > Siswa.
5. Multi-role menggunakan union permission; scope lebih tinggi seperti `SEMUA` menang untuk aksi yang dimiliki.
6. Guru biasa hanya input Presensi Siswa melalui Jadwal Aktif Sesi Awal/Akhir yang sesuai.
7. Guru biasa terikat time-window dan geofence.
8. Guru biasa tidak dapat membuka kembali Presensi tersimpan dan tidak dapat revisi.
9. Wali boleh input Sesi Awal/Akhir di kelas Wali meskipun tidak mempunyai Jadwal pada sesi tersebut.
10. Jika Wali juga mempunyai Jadwal cocok di kelas walinya, jalur normal saat jadwal valid adalah Guru Terjadwal sehingga time-window dan geofence berlaku.
11. Jika time-window jalur Guru Terjadwal sudah lewat, Wali boleh fallback ke hak KELAS_DIAMPU dan tetap input kelas Wali tanpa time-window/geofence.
12. Untuk kelas lain yang dia ajar tetapi bukan kelas Wali, Wali diperlakukan sebagai Guru biasa dan tidak mempunyai fallback.
13. Revisi Presensi Siswa hanya Admin, Operator, dan Wali aktif kelas target.
14. Wali aktif boleh revisi seluruh tanggal dalam Tahun Ajaran aktif pada kelas yang saat ini dia walikan.
15. Saat mapping Wali berganti, Wali lama kehilangan hak dan Wali baru memperoleh hak atas histori kelas Tahun Ajaran aktif.
16. `id_guru_input` boleh NULL bagi Admin/Operator yang tidak mempunyai identitas Guru; audit actor tetap menggunakan user aplikasi.
17. Sesi Akhir menggunakan status yang sama dengan Sesi Awal, tetapi tidak masuk laporan resmi/Matrix/EWS.
18. Tidak ada gate Hari Libur pada v0.5. Input pada tanggal libur tidak otomatis ditolak.
19. Jika input pada tanggal libur atau tanggal lain ternyata salah, koreksi melalui hak revisi yang berlaku.
20. Semua query Presensi besar wajib database-first, bounded, server-side, dan tidak boleh `findAll()` tanpa pembatas.

Bagian ini adalah ringkasan keputusan final. Bila ada implementasi yang bertentangan, dokumen ini harus menjadi acuan sampai ada revisi dokumen resmi berikutnya.
