# ✅ Presensi Siswa & Presensi Mengajar — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menjadi kontrak implementasi Presensi Siswa dan Presensi Mengajar/Jurnal.

Semua implementasi Presensi harus menggunakan Master Data final sebagai sumber:

```text
Siswa
Anggota Kelas
Kelas
Tahun Ajaran
Mapping Wali Kelas
Jadwal Guru
Mata Pelajaran
```

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

---

## 2. Kelas Siswa

Daftar siswa kelas berasal dari:

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

Jangan menggunakan data kelas dari request tanpa validasi membership.

---

## 3. Jadwal Aktif

Untuk workflow Guru biasa, jadwal harus:

```text
jadwal_guru.status_jadwal = Aktif
jadwal_guru.id_tahun = tahun aktif
```

Jadwal Nonaktif hanya untuk histori dan tidak dapat digunakan input Presensi baru.

---

# BAGIAN B — PRESENSI SISWA

## 4. Status

Hanya:

```text
Hadir
Sakit
Izin
Alpha
```

Tidak ada status Terlambat.

---

## 5. Sesi Presensi Siswa

Presensi Siswa hanya:

```text
Sesi Awal
Sesi Akhir
```

`Non Sesi` pada Jadwal Guru tidak menghasilkan kewajiban Presensi Siswa.

### 5.1 Sesi Awal

- Presensi resmi;
- dihitung Matrix;
- dihitung laporan;
- dihitung EWS;
- digunakan statistik ketidakhadiran.

### 5.2 Sesi Akhir

- dokumentasi;
- tersimpan;
- tidak dihitung laporan resmi;
- tidak dihitung Matrix;
- tidak dihitung EWS.

---

# 6. Hubungan Jadwal dan Sesi

Untuk Guru biasa:

- Guru hanya dapat memulai input bila memiliki jadwal aktif untuk kelas yang bersangkutan;
- jadwal harus berada dalam konteks hari/tanggal;
- jadwal `Non Sesi` tidak membuka Presensi Siswa;
- label Sesi Presensi mengikuti jadwal `Sesi Awal` atau `Sesi Akhir`.

Sistem harus mencegah konfigurasi operasional yang menyebabkan lebih dari satu kewajiban Presensi Siswa identik untuk kelas/tanggal/sesi.

Unique database menjadi guard terakhir:

```text
(id_kelas, tanggal, sesi, id_siswa)
```

---

# 7. Hak Akses Presensi Siswa

| Role | Input | Lihat Tersimpan | Revisi | Scope |
|---|---|---|---|---|
| Admin | Ya | Ya | Ya | SEMUA |
| Operator | Ya | Ya | Ya | SEMUA |
| Pimpinan | Tidak | Ya | Tidak | SEMUA |
| BK | Tidak | Tidak langsung | Tidak | — |
| Guru biasa | Ya | Tidak | Tidak | KELAS_TERJADWAL |
| Wali | Ya | Ya | Ya | KELAS_DIAMPU |
| Siswa | Tidak | Diri | Tidak | DIRI_SENDIRI |

---

# 8. Guru Biasa

Guru biasa:

1. harus memiliki `id_guru`;
2. harus memiliki jadwal Aktif;
3. hanya boleh kelas terjadwal;
4. hanya boleh sesi sesuai Jadwal;
5. terikat time-window;
6. terikat geofencing;
7. setelah submit tidak boleh membuka data Presensi tersimpan;
8. tidak boleh revise.

Server harus memvalidasi semua rule tersebut, bukan hanya tombol UI.

Jika Guru mempunyai role lain dengan scope lebih tinggi, permission efektif mengikuti union multi-role.

---

# 9. Wali Kelas

Wali:

- tidak bergantung pada Jadwal untuk kelas Wali;
- dapat input Sesi Awal;
- dapat input Sesi Akhir;
- bebas time-window untuk kelas Wali;
- bebas geofencing untuk kelas Wali;
- dapat melihat tersimpan;
- dapat revise;
- hanya kelas mapping aktif.

Jika Wali mengajar kelas lain, aturan Guru biasa berlaku.

---

# 10. Admin dan Operator

Admin/Operator:

- semua kelas;
- input administratif;
- bebas time-window;
- bebas geofencing;
- dapat revise;
- dapat melihat data tersimpan.

Identity `id_guru_input` harus diisi sesuai actor bila tersedia, sedangkan audit actor aplikasi tetap dapat menggunakan `user_id`.

---

# 11. Pimpinan

Pimpinan:

- view-only;
- tidak boleh input;
- tidak boleh revise;
- melihat semua sesuai permission.

---

# 12. Siswa

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

# 13. Tampilan Input

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

# 14. Bulk Save

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
```

yang dikirim client.

Semua di-resolve dari database/session.

---

# 15. Atomic Transaction Presensi

Satu submit kelas harus transaction.

Alur:

```text
BEGIN
↓
validasi permission/scope
↓
validasi tahun
↓
validasi kelas
↓
validasi daftar siswa server-side
↓
validasi time-window
↓
validasi geofence
↓
insert/upsert semua siswa
↓
COMMIT
```

Jika satu siswa invalid:

```text
ROLLBACK seluruh batch
```

Tidak boleh terjadi setengah kelas tersimpan.

---

# 16. Snapshot

Tabel Presensi menyimpan:

```text
id_siswa
nama_siswa_snapshot
id_guru_input
nama_guru_input_snapshot
```

Snapshot diambil server dari data master saat save.

Tujuan:

- histori tetap terbaca jika nama berubah;
- laporan lama tidak tergantung nama master saat ini;
- record tetap informatif bila FK tertentu menjadi NULL.

---

# 17. Input Baru vs Revisi

## 17.1 Input Baru

Bila record belum ada:

```text
INSERT
created_at
```

## 17.2 Revisi

Bila record sudah ada:

- hanya Admin/Operator/Wali yang berhak;
- update status;
- update `updated_at`;
- isi `updated_by`.

Guru biasa tidak boleh mengubah record existing.

Jika request Guru biasa menemukan record existing, server menolak, bukan melakukan overwrite diam-diam.

---

# 18. Proteksi Duplicate

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

# 19. Rumus

Guru biasa dapat input hanya:

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

# 20. Pengecualian

| Actor | Presensi Siswa |
|---|---|
| Admin | bebas |
| Operator | bebas |
| Wali pada kelas Wali | bebas |
| Guru biasa | terikat |
| Wali di kelas non-Wali | terikat |

---

# BAGIAN D — GEOFENCING

# 21. Tujuan

Geofence memastikan Guru mapel berada di lokasi sekolah ketika mengklaim aktivitas yang mensyaratkan keberadaan fisik.

---

# 22. Setting

Setting minimum:

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

Koordinat default dapat disimpan di `setting_sistem` dan harus dapat diubah Admin.

---

# 23. Haversine

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
- latitude -90..90;
- longitude -180..180;
- null/invalid ditolak bila geofence wajib.

---

# 24. Presensi Siswa Geofence

Guru biasa:

```text
wajib
```

Admin/Operator/Wali pada kelas Wali:

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

# BAGIAN E — PRESENSI MENGAJAR / JURNAL

# 25. Definisi

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

# 26. Status Jurnal

```text
Hadir
Izin
Sakit
```

Tidak ada Alpha.

---

# 27. Materi

`materi` wajib untuk seluruh status.

Contoh Izin/Sakit:

```text
Memberikan tugas melalui grup kelas.
```

Tidak boleh menyimpan materi kosong atau whitespace-only.

---

# 28. Unique

```text
UNIQUE(id_jadwal, tanggal)
```

Satu baris jadwal satu jurnal per hari.

---

# 29. Hak Akses Jurnal

| Role | Lihat Jadwal | Input | Revisi | Scope |
|---|---|---|---|---|
| Admin | Semua | Ya | Ya | SEMUA |
| Operator | Semua | Ya | Ya | SEMUA |
| Pimpinan | Semua | Diri bila punya jadwal | Tidak | sesuai permission |
| BK | Tidak | Tidak | Tidak | — |
| Guru | Diri | Ya | Tidak | DIRI_SENDIRI |
| Wali | Diri | Ya | Tidak | DIRI_SENDIRI |
| Siswa | Tidak | Tidak | Tidak | — |

Status Wali tidak menambah kemampuan revisi Jurnal.

---

# 30. Validasi `id_guru`

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

Admin/Operator boleh input atas nama Guru lain.

---

# 31. Time Window Jurnal

Guru:

```text
jam_mulai
≤ sekarang
≤ jam_selesai + 15 menit
```

Admin/Operator administratif tidak terikat.

Untuk status Izin/Sakit, time-window tetap mengikuti aturan input Jurnal kecuali dokumen operasional kemudian menetapkan pengecualian eksplisit.

---

# 32. Geofence Jurnal

## 32.1 Hadir

Guru status Hadir:

```text
geofence wajib
```

jika setting aktif.

## 32.2 Izin/Sakit

Tidak wajib geofence.

## 32.3 Admin/Operator

Tidak wajib.

---

# 33. Snapshot Jurnal

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

# BAGIAN F — JADWAL REIMPORT

# 34. Jadwal Lama Nonaktif

Reimport Jadwal membuat set lama Nonaktif, bukan delete.

Presensi/Jurnal historis yang sudah tersimpan:

- tetap menunjuk `id_jadwal` lama;
- tetap valid;
- tidak boleh ikut berubah ke Jadwal baru.

Jangan melakukan cascading update histori ke Jadwal baru.

---

# 35. Jadwal Baru

Jadwal baru hanya berlaku untuk input setelah import.

Jika kebutuhan administrasi mengharuskan koreksi histori, koreksi dilakukan pada record Presensi/Jurnal dengan authorization khusus, bukan dengan memindahkan foreign key secara massal.

---

# BAGIAN G — SISWA MASUK DI TENGAH SEMESTER

# 36. Siswa Baru

Siswa yang baru menjadi anggota kelas mulai tanggal tertentu hanya boleh muncul pada input setelah menjadi anggota.

Untuk histori sebelum masuk kelas:

- jangan generate Presensi retroaktif otomatis;
- laporan tidak boleh menganggap tidak adanya record sebelum membership sebagai Alpha.

Tanggal mulai `riwayat_siswa` dapat digunakan sebagai batas keanggotaan saat analitik diperlukan.

---

# 37. Siswa Pindah/Keluar/Lulus

Setelah status siswa bukan Aktif:

- jangan tampilkan pada input baru;
- histori Presensi lama tetap ada;
- laporan historis tetap menggunakan snapshot.

---

# BAGIAN H — EWS

# 38. EWS Alpha

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

# 39. Scope EWS

- Admin: semua;
- Operator: semua;
- Pimpinan: semua;
- BK: sesuai permission;
- Wali: kelas Wali.

EWS tidak perlu menu sidebar; dapat menjadi widget dashboard.

---

# BAGIAN I — STRATEGI QUERY DAN SKALA DATA

# 40. Database-First Processing

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

# 41. Query Input Presensi

Untuk form satu kelas:

```text
1 query membership siswa kelas
+
query Presensi existing kelas/tanggal/sesi bila diperlukan
```

Jangan melakukan query Presensi satu per siswa.

---

# 42. Query EWS

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

# 43. Histori Presensi

Daftar histori besar wajib:

```text
server-side filter
server-side sort
server-side pagination
```

Dilarang mengirim puluhan/ratusan ribu row ke DataTables client-side.

---

# 44. Query Boundary

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

# BAGIAN J — API / JSON

# 45. Dual Output

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

# BAGIAN K — LOG & AUDIT

# 46. Audit Revisi

Revisi Presensi:

```text
updated_at
updated_by
```

Jika diperlukan audit lebih detail, tulis `log_activity`.

Original `created_at` tidak boleh ditimpa saat revisi.

---

# BAGIAN L — ERROR RULE

# 47. Error Penting

Server harus menolak dengan jelas bila:

- tidak ada Tahun aktif;
- jadwal tidak aktif;
- kelas tidak valid;
- siswa bukan anggota;
- Guru tidak memiliki jadwal;
- Guru mencoba membuka hasil tersimpan;
- Guru mencoba revise;
- Guru di luar time-window;
- Guru di luar geofence;
- duplicate record;
- sesi Non Sesi dipakai untuk Presensi Siswa;
- Wali mengakses kelas bukan Wali;
- request mencampur siswa dari kelas lain;
- koordinat tidak valid;
- materi Jurnal kosong;
- Guru mencoba jurnal milik Guru lain.

---

# BAGIAN M — ROUTE DAN SERVICE

# 48. Service Presensi Siswa

`PresensiService` minimal menangani:

- resolve tahun aktif;
- resolve scope;
- resolve kelas terjadwal;
- resolve kelas Wali;
- load siswa kelas;
- validasi time-window;
- validasi geofence;
- bulk save;
- revisi;
- read sesuai scope;
- EWS source query.

---

# 49. GeofencingService

Minimal method:

```text
hitungJarak(...)
isDalamRadius(...)
validasiKoordinat(...)
```

Tidak menyimpan keputusan hanya di frontend.

---

# 50. Service Jurnal

Minimal menangani:

- daftar jadwal hari ini;
- validasi ownership Guru;
- validasi time-window;
- validasi geofence berdasarkan status;
- save;
- revisi administratif;
- read sesuai scope.

---

# BAGIAN N — CHECKPOINT

# 51. Presensi Siswa

- input Sesi Awal;
- input Sesi Akhir;
- default Hadir;
- bulk atomic;
- Guru sesuai Jadwal;
- Guru tidak bisa melihat saved;
- Wali bisa saved/revise;
- Admin/Operator full;
- Pimpinan readonly;
- Siswa diri;
- time-window;
- geofence;
- duplicate guard;
- snapshots;
- history membership respected.

# 52. Jurnal

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
- Jadwal lama Nonaktif tetap historis.
