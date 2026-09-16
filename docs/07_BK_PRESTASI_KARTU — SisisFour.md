# BK, Konseling, Prestasi & Kartu Pelajar — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Baseline Aplikasi:** `main` @ `06e4e559c045763096058fc889342da78d973314`  
**Development aktif:** `feat/g3-bk-foundation-konseling-20260916`

> Dokumen ini menyatakan kontrak BK yang berlaku mulai G3.3.1. Authorization target tetap diputuskan Route/Filter + Service; View/JavaScript/menu bukan security boundary.

## 1. Ruang Lingkup

Modul pada dokumen ini:

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

Tabel legacy tetap:

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

Mulai G3.3.1, **poin bukan lagi bagian dari business rule BK**.

Kontrak:

- UI Master Pelanggaran hanya mengelola nama dan kategori;
- Catatan Pelanggaran tidak menampilkan poin;
- export tidak membawa poin;
- active dashboard payload tidak membawa poin;
- dashboard tidak membuat ranking poin;
- endpoint Top Poin dipensiunkan;
- kolom database legacy `ref_pelanggaran.poin` belum di-drop pada tahap ini agar rollback aman;
- create/update melalui aplikasi menulis nilai legacy `0` dan tidak pernah memakai nilai tersebut untuk keputusan bisnis.

## 3. Catatan Pelanggaran Siswa

Tabel fisik tetap menggunakan nama legacy:

```text
catatan_kasus
```

Nama experience/UI canonical:

```text
Catatan Pelanggaran Siswa
```

Relasi utama:

```text
siswa
ref_pelanggaran
guru input (nullable)
user updater (nullable)
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

Modal detail mengikuti urutan:

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

Tindak lanjut dibuat/diubah melalui permission `bk_kasus.manage` dan target divalidasi ulang di Service.

Export Catatan Pelanggaran menggunakan satu XLSX dengan dua sheet:

```text
Pelanggaran   = satu baris per catatan + Kelas + Jumlah Tindak Lanjut
Tindak Lanjut = satu baris per tindak lanjut, terhubung lewat ID Catatan
```

## 5. Konseling BK — Data Rahasia

Tabel:

```text
konseling_bk
```

Akses operasional Konseling hanya untuk effective role:

```text
admin
operator
bk
```

dan tetap membutuhkan permission terkait.

Permission operasional:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
```

Ketiga permission menggunakan scope `SEMUA` untuk Admin, Operator, dan BK.

Pimpinan, Guru/Wali, dan Siswa tidak memperoleh permission/menu/detail/widget Konseling. Mengetahui URL tidak memberi akses karena route filter dan Service tetap memverifikasi authorization.

Konseling **tidak mensyaratkan** akun mempunyai `users.id_guru`. Actor pencatat selalu direkam dari user login melalui:

```text
konseling_bk.created_by -> users.id
```

Pada dump localhost aktual, akun role BK terhubung melalui:

```text
users.id_pegawai -> pegawai.id
```

Field `id_guru_bk` bersifat nullable/metadata legacy dan bukan identity audit utama.

## 6. Workflow Konseling Dua Tahap

### Tahap 1 — Buat Catatan Awal

Wajib diisi hanya:

```text
Siswa & Waktu
- Kelas
- Siswa
- Tanggal
- Pertemuan ke-

Jenis Layanan
- Bentuk layanan
- Cara siswa hadir
- Bidang
- Topik
```

Urutan selector siswa:

```text
Kelas aktif
→ Siswa aktif yang menjadi anggota kelas tersebut pada Tahun Ajaran aktif
```

Kelas dan Siswa sama-sama searchable. Saat Kelas berubah, pilihan Siswa di-reset dan diambil ulang.

Server selalu memvalidasi ulang:

```text
kelas ∈ Tahun Ajaran aktif
siswa ∈ anggota_kelas(kelas, Tahun Ajaran aktif)
siswa.status_aktif = Aktif
```

Setelah Tahap 1 disimpan:

```text
status = Proses
created_by = user login
id_guru_bk = identity Guru actor bila tersedia, selain itu NULL
```

### Tahap 2 — Lengkapi / Update

Record yang sama kemudian dilengkapi dengan:

```text
Uraian Masalah
Hasil Pembahasan dan Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status: Proses | Selesai
```

Ketentuan:

- update Tahap 2 tidak mengubah identitas awal siswa/kelas/jenis layanan;
- tanggal berikutnya, bila ada, tidak boleh lebih awal dari tanggal konseling;
- status `Selesai` mewajibkan `Uraian Masalah` dan `Hasil Pembahasan dan Kesepakatan`;
- tidak disediakan delete dari workflow G3.3.1 agar histori rahasia tidak mudah hilang.

Modal detail mengikuti urutan:

```text
Identitas Konseling
→ Perkembangan Tersimpan
→ Form Tahap 2 / Update Tindak Lanjut
```

`Perkembangan Tersimpan` adalah snapshot record Konseling yang sudah persisten, bukan multi-entry history. Histori Konseling 1:N, bila kelak diperlukan, harus menjadi perubahan schema tersendiri.

## 7. Pengaturan Isian Form Konseling

Tidak ada tabel baru untuk master pilihan Form Konseling.

Penyimpanan memakai tabel existing:

```text
setting_sistem
setting_key = bk_konseling_form_options
```

Pilihan yang dapat dikelola:

```text
Bentuk Layanan
Cara Siswa Hadir
Topik per Bidang
Rencana Berikutnya
```

Pilihan yang tetap fixed karena domain/schema:

```text
Bidang = Pribadi | Sosial | Belajar | Karier
Status = Proses | Selesai
```

Permission pengaturan:

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

UI dan backend validation membaca sumber setting yang sama. Jika setting belum ada/rusak/tidak valid, aplikasi menggunakan default aman dari kode.

Setiap group minimal satu dan maksimal 40 pilihan. Panjang pilihan mengikuti schema Konseling: Bentuk Layanan maksimal 50 karakter, Cara Siswa Hadir 80, setiap Topik 150, dan Rencana Berikutnya 100. Duplikat case-insensitive dibersihkan sebelum disimpan.

## 8. Referensi Default Jenis Layanan Konseling

Bentuk layanan default:

```text
Konseling individu
Konseling kelompok
Konsultasi
```

Cara hadir default:

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

Topik mengikuti bidang. Referensi awal diadaptasi dari form BK yang disepakati pada G3.3.1 dan divalidasi server-side.

Rencana berikutnya default:

```text
Selesai
Konseling lanjutan
Memanggil orang tua
Koordinasi dengan wali kelas
Kunjungan rumah
Rujuk ke UKS
Rujuk ke psikolog atau Puskesmas
```

## 9. Konseling — Export

Export XLSX hanya tersedia dengan permission:

```text
bk_konseling.export
```

Export memuat identitas siswa, kelas, data Tahap 1, isi Tahap 2, tindak lanjut, status, dan actor pencatat.

Data export tetap mengikuti boundary operasional Konseling: Admin, Operator, atau BK dengan permission terkait.

## 10. Prestasi Siswa

Tabel:

```text
catatan_prestasi
```

Permission:

```text
prestasi.view
prestasi.manage
```

Tingkat prestasi:

```text
Madrasah
Kecamatan
Kabupaten
Provinsi
Nasional
Internasional
```

Akses baseline:

- Admin/Operator/BK dapat manage sesuai permission;
- Pimpinan dapat readonly bila mempunyai `prestasi.view`;
- Wali dapat melihat kelas sendiri bila scope diberikan;
- Siswa hanya data diri pada surface yang memang disediakan.

Form Tambah Prestasi menggunakan remote searchable siswa dan POST FormData. Controller wajib membaca `getPost()` untuk POST dan tetap mendukung raw input pada PUT/Edit.

Export Prestasi memuat:

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

## 11. Kartu Pelajar

Tabel:

```text
kartu_pelajar
```

Field bisnis utama:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Database menjaga maksimum **satu kartu Aktif per siswa** melalui generated/unique contract.

Permission:

```text
kartu_pelajar.view
kartu_pelajar.manage
```

## 12. Generate / Reissue Kartu

Generate hanya untuk siswa yang sah pada scope actor.

Kontrak:

- satu request bulk maksimum 200 target;
- seluruh target divalidasi;
- operasi idempotent terhadap kartu yang sudah Aktif;
- transaksi dan logging ditangani Service;
- browser dapat mengulang batch sampai `remaining_count = 0`.

Reissue adalah lifecycle kartu berwenang dan tidak boleh digunakan untuk menembus scope siswa.

## 13. Canvas dan Background Kartu

Ukuran canonical:

```text
1011 × 638 px
```

Default source:

```text
public/assets/kartu/default/background_kta_depan.jpg
public/assets/kartu/default/background_kta_belakang.jpg
```

Override runtime:

```text
setting_sistem.background_kta_depan
setting_sistem.background_kta_belakang
```

Upload template dari Settings dinormalisasi ke 1011×638 dan disimpan di `uploads/settings/kartu/`.

## 14. QR dan Public Verify

Payload QR:

```text
SISFOUR|V1|NISN={nisn}|NAMA={nama_encoded}|VERIFY={kode_verifikasi}
```

Route public:

```text
GET /kartu/verify/{kode_verifikasi}
```

Public verify readonly dan hanya menampilkan data minimum yang diperlukan untuk verifikasi kartu.

## 15. Cetak Massal

Kontrak:

```text
maksimum 200 kartu
A4 portrait
2 kolom × 5 baris
10 kartu per halaman
side = front | back
```

## 16. Lifecycle Siswa

Saat siswa menjadi:

```text
Lulus
Pindah
Keluar
```

kartu Aktif dinonaktifkan melalui lifecycle Service yang berwenang. Histori Catatan Pelanggaran dan Konseling tetap disimpan sesuai relasinya.

## 17. SQL G3.3.1 dan Hosting Gate

Localhost canonical/finalization:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX2_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
```

`FIX3_LOCALHOST` menambah permission/menu Setting Form Konseling dan secara eksplisit membersihkan accidental permission/menu Konseling untuk Pimpinan, Guru/Wali, dan Siswa.

**Hosting dikerjakan terakhir.** Sebelum perubahan hosting:

```text
1. user mengirim dump SQL hosting aktual
2. dump diaudit terhadap schema, permission, menu, identity, dan data existing
3. syntax SQL hosting disusun/direvisi berdasarkan kondisi nyata dump
4. baru dilakukan static/review gate dan eksekusi dengan approval user
```

File SQL hosting yang sudah terdapat pada branch selama development bersifat **provisional / DO NOT RUN** sampai dump hosting aktual diaudit dan file tersebut diganti/difinalkan.

## 18. Checkpoint G3.3.1

G3.3.1 belum CLOSED sampai seluruh item berikut PASS:

```text
Master Pelanggaran tanpa poin
Catatan Pelanggaran tanpa poin
Top Poin retired
Export Pelanggaran dua sheet + Kelas
Prestasi create/edit aman + export memiliki Kelas
Konseling BK terpisah dari Catatan Pelanggaran
Konseling Tahap 1 create
Konseling Tahap 2 update
Kelas -> Siswa terikat Tahun Ajaran aktif
Pengaturan Form Konseling tersimpan di setting_sistem
Admin + Operator + BK = operasional Konseling sesuai permission
Admin + BK = Pengaturan Form Konseling
Pimpinan + Guru/Wali + Siswa = tanpa surface/detail Konseling
actor pencatat berbasis users.id, BK dapat berupa Pegawai
mobile Pimpinan/Wali/Siswa memenuhi role visibility dan no horizontal operational overflow
static gate + browser regression PASS
```

G3.4 Dashboard BK baru boleh dimulai setelah checkpoint G3.3.1 localhost/application lulus regression. Hosting tetap mengikuti dump-audit gate tersendiri sebelum deployment.