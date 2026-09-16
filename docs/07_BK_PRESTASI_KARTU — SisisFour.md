# BK, Konseling, Prestasi & Kartu Pelajar — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Baseline Aplikasi:** `main` @ `06e4e559c045763096058fc889342da78d973314`  
**Development aktif:** `feat/g3-bk-foundation-konseling-20260916` — closure focused re-smoke pending

> Dokumen ini menyatakan kontrak BK yang berlaku mulai G3.3.1. Authorization final tetap Route/Filter + Service; View/JavaScript/menu bukan security boundary.

## 1. Ruang Lingkup

```text
Master Pelanggaran
Catatan Pelanggaran Siswa
Tindak Lanjut Pelanggaran
Konseling BK
Pengaturan Form Konseling
Prestasi Siswa
Kartu Pelajar
Public Verify Kartu
```

## 2. Master Pelanggaran — Tanpa Poin

Tabel legacy:

```text
ref_pelanggaran
```

Kategori canonical:

```text
Ringan
Sedang
Berat
```

Permission:

```text
bk_pelanggaran_master.manage
```

Mulai G3.3.1, **poin bukan business rule BK**.

Kontrak:

- UI Master Pelanggaran hanya nama + kategori;
- Catatan Pelanggaran tidak menampilkan poin;
- export tidak membawa poin;
- active dashboard payload tidak membawa poin;
- dashboard tidak membuat ranking poin;
- Top Poin retired;
- `ref_pelanggaran.poin` tetap legacy untuk rollback compatibility;
- create/update aplikasi menulis legacy `0` dan tidak memakainya untuk keputusan bisnis.

## 3. Catatan Pelanggaran Siswa

Tabel fisik legacy:

```text
catatan_kasus
```

Nama UI canonical:

```text
Catatan Pelanggaran Siswa
```

Relasi utama:

```text
siswa
ref_pelanggaran
guru input nullable
user updater nullable
```

Permission:

```text
bk_kasus.view
bk_kasus.manage
```

Akses bisnis:

| Actor | View | Manage |
|---|---|---|
| Admin | semua | semua |
| Operator | semua | semua |
| Pimpinan | agregat/readonly sesuai permission | tidak |
| BK | semua | semua |
| Wali | kelas Wali sesuai scope | tidak kecuali permission lain diberikan |
| Guru biasa | tidak | tidak |
| Siswa | diri sendiri bila surface mengizinkan | tidak |

Catatan Pelanggaran berisi kejadian, kategori, tanggal, keterangan, dan histori tindak lanjut. Tidak ada skor/poin.

Modal detail:

```text
Detail Pelanggaran
→ Riwayat Tindak Lanjut
→ Form Tambah/Edit Tindak Lanjut
```

## 4. Tindak Lanjut Pelanggaran

Tabel:

```text
tindak_lanjut_kasus
```

Relasi:

```text
catatan_kasus 1:N tindak_lanjut_kasus
```

Tindak lanjut dibuat/diubah melalui `bk_kasus.manage` dan target divalidasi ulang Service.

Export Catatan Pelanggaran menggunakan satu XLSX dua sheet:

```text
Pelanggaran   = satu baris per catatan + Kelas + Jumlah Tindak Lanjut
Tindak Lanjut = satu baris per tindak lanjut + ID Catatan + Kelas + Dicatat Oleh
```

Tidak ada poin pada export.

## 5. Konseling BK — Data Rahasia

Tabel:

```text
konseling_bk
```

Effective role operasional:

```text
admin
operator
bk
```

Permission:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
```

Pimpinan, Guru/Wali, dan Siswa tidak memperoleh permission/menu/detail/widget Konseling.

Konseling tidak mensyaratkan `users.id_guru`. Actor audit:

```text
konseling_bk.created_by -> users.id
```

Akun role BK aktual:

```text
users.id_pegawai -> pegawai.id
users.id_guru = NULL
```

`id_guru_bk` nullable/metadata legacy dan bukan identity audit utama.

## 6. Workflow Konseling Dua Tahap

### Tahap 1 — Buat Catatan Awal

Wajib:

```text
Kelas
Siswa
Tanggal
Pertemuan ke-
Bentuk layanan
Cara siswa hadir
Bidang
Topik
```

Urutan:

```text
Kelas aktif
→ Siswa aktif anggota kelas pada Tahun Ajaran aktif
```

Server memvalidasi ulang:

```text
kelas ∈ Tahun Ajaran aktif
siswa ∈ anggota_kelas target
siswa.status_aktif = Aktif
```

Setelah simpan:

```text
status = Proses
created_by = user login
id_guru_bk = identity Guru actor bila tersedia, selain itu NULL
```

### Tahap 2 — Lengkapi / Update Record yang Sama

```text
Uraian Masalah
Hasil Pembahasan dan Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses | Selesai
```

Ketentuan:

- metadata Tahap 1 tidak diubah;
- tanggal berikutnya tidak boleh sebelum tanggal Konseling;
- `Selesai` mewajibkan Uraian + Hasil;
- `Proses` boleh belum lengkap;
- tidak ada delete workflow G3.3.1.

Modal detail:

```text
Identitas Konseling
→ Perkembangan Tersimpan
→ Form Tahap 2
```

`Perkembangan Tersimpan` adalah snapshot record persisten, bukan multi-entry history. Histori Konseling 1:N, bila dibutuhkan kelak, harus menjadi perubahan schema tersendiri.

## 7. Preservasi Rencana Berikutnya Historis

Pengaturan Form adalah daftar pilihan aktif, tetapi perubahan daftar **tidak boleh merusak data Konseling yang sudah tersimpan**.

Kontrak:

```text
record lama menyimpan Rencana X
→ X kemudian dihapus dari Settings
→ record lama dibuka
→ X tetap tampil sebagai "X (tersimpan)"
→ save tanpa mengganti X harus tetap valid
→ user dapat mengganti X ke opsi aktif Y
→ record lain yang tidak pernah menyimpan X tetap tidak boleh memakai X
```

Implementasi closure patch:

- `assets/js/bk/konseling.js` menambahkan option lokal `(tersimpan)` hanya bila nilai record tidak lagi ada pada options aktif;
- `KonselingBkService::validateStageTwo()` mengizinkan current stored rencana record tersebut selain daftar aktif;
- opsi lama tidak dimasukkan kembali sebagai pilihan global Settings;
- tidak ada perubahan schema/SQL.

Patch ditemukan dari full docs/source audit setelah broad local + hosting smoke. Karena source berubah, patch ini wajib focused local + hosting re-smoke sebelum PR #9 Ready/Merge.

## 8. Pengaturan Isian Form Konseling

Tidak ada tabel master baru.

Storage:

```text
setting_sistem
setting_key = bk_konseling_form_options
```

Configurable:

```text
Bentuk Layanan
Cara Siswa Hadir
Topik per Bidang
Rencana Berikutnya
```

Fixed:

```text
Bidang = Pribadi | Sosial | Belajar | Karier
Status = Proses | Selesai
```

Permission:

```text
bk_konseling.settings
```

Akses:

```text
Admin = ya
BK = ya
Operator = tidak
Pimpinan = tidak
Guru/Wali = tidak
Siswa = tidak
```

UI + backend membaca sumber setting sama. Missing/rusak/invalid memakai default aman.

Constraint per group:

```text
minimal 1 pilihan
maksimal 40 pilihan
Bentuk Layanan max 50 karakter
Cara Hadir max 80
Topik max 150
Rencana max 100
deduplikasi case-insensitive
```

## 9. Referensi Default Konseling

Bentuk layanan:

```text
Konseling individu
Konseling kelompok
Konsultasi
```

Cara hadir:

```text
Datang sendiri
Dipanggil guru BK
Rujukan wali kelas
Rujukan guru mata pelajaran
Permintaan orang tua
Rujukan UKS
```

Bidang fixed:

```text
Pribadi
Sosial
Belajar
Karier
```

Topik mengikuti bidang.

Rencana default:

```text
Selesai
Konseling lanjutan
Memanggil orang tua
Koordinasi dengan wali kelas
Kunjungan rumah
Rujuk ke UKS
Rujuk ke psikolog atau Puskesmas
```

## 10. Konseling — Export

Permission:

```text
bk_konseling.export
```

Export memuat identitas siswa, kelas, Tahap 1, isi Tahap 2, rencana, tanggal berikutnya, status, dan actor pencatat.

Boundary tetap Admin/Operator/BK + permission.

## 11. Prestasi Siswa

Tabel:

```text
catatan_prestasi
```

Permission:

```text
prestasi.view
prestasi.manage
```

Tingkat:

```text
Madrasah
Kecamatan
Kabupaten
Provinsi
Nasional
Internasional
```

Akses baseline:

- Admin/Operator/BK manage sesuai permission;
- Pimpinan readonly bila mempunyai `prestasi.view`;
- Wali kelas sendiri bila scope diberikan;
- Siswa hanya diri sendiri pada surface yang disediakan.

Form Tambah Prestasi memakai searchable siswa + POST FormData. Controller membaca `getPost()` untuk POST dan tetap mendukung raw input pada PUT/Edit.

Export:

```text
NISN
Nama Siswa
Kelas aktif
Tanggal
Prestasi
Tingkat
Penyelenggara
Keterangan
```

## 12. Kartu Pelajar

Tabel:

```text
kartu_pelajar
```

Field utama:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Database menjaga maksimum satu kartu Aktif per siswa.

Permission:

```text
kartu_pelajar.view
kartu_pelajar.manage
```

## 13. Generate / Reissue Kartu

Generate hanya untuk siswa sah pada scope actor.

- satu request bulk maksimum 200 target;
- seluruh target divalidasi;
- idempotent terhadap kartu Aktif;
- transaksi/logging Service;
- browser dapat mengulang batch sampai remaining 0.

Reissue tidak boleh menembus scope siswa.

## 14. Canvas / Background Kartu

Canonical:

```text
1011 × 638 px
```

Default:

```text
public/assets/kartu/default/background_kta_depan.jpg
public/assets/kartu/default/background_kta_belakang.jpg
```

Override:

```text
setting_sistem.background_kta_depan
setting_sistem.background_kta_belakang
```

Upload Settings dinormalisasi dan disimpan di `uploads/settings/kartu/`.

## 15. QR / Public Verify

Payload:

```text
SISFOUR|V1|NISN={nisn}|NAMA={nama_encoded}|VERIFY={kode_verifikasi}
```

Public route:

```text
GET /kartu/verify/{kode_verifikasi}
```

Readonly, data minimum untuk verifikasi.

## 16. Cetak Massal

```text
maksimum 200 kartu
A4 portrait
2 kolom × 5 baris
10 kartu per halaman
side = front | back
```

## 17. Lifecycle Siswa

Saat siswa Lulus/Pindah/Keluar, kartu Aktif dinonaktifkan oleh lifecycle Service. Histori Catatan Pelanggaran dan Konseling tetap dipertahankan.

## 18. SQL G3.3.1

Localhost:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
```

Hosting final dari audit dump aktual:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

FIX1/FIX2 localhost adalah patch transisi dan tidak berada pada branch final.

Broad schema execution local/hosting: **PASS**. Closure preservasi Rencana tidak membutuhkan SQL baru.

## 19. Gate G3.3.1

Broad gates yang telah PASS:

```text
Master Pelanggaran tanpa poin
Catatan Pelanggaran tanpa poin
Top Poin retired
Export Pelanggaran 2 sheet + Kelas
Prestasi create/edit + export Kelas
Konseling terpisah
Konseling Tahap 1/Tahap 2
Kelas -> Siswa Tahun aktif
Settings persistence/backend validation
Admin/Operator/BK operational matrix
Admin/BK Settings
Pimpinan/Guru/Wali/Siswa tanpa Konseling
actor users.id / BK Pegawai
mobile cross-role no overflow
SQL local + hosting
broad hosting smoke
```

Closure gate tambahan:

```text
historical Rencana preservation patch static/local focused UAT PENDING
focused hosting re-smoke PENDING
```

G3.4 baru dimulai setelah closure gate PASS, PR #9 merged, dan user memberi approval eksplisit.