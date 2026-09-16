# BK, Konseling, Prestasi & Kartu Pelajar — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Baseline Aplikasi:** `main` @ `06e4e559c045763096058fc889342da78d973314`  
**Development aktif:** `feat/g3-bk-foundation-konseling-20260916`

> Dokumen ini menyatakan kontrak BK yang berlaku mulai G3.3.1. Authorization target tetap diputuskan Service; View/JavaScript bukan security boundary.

## 1. Ruang Lingkup

Modul pada dokumen ini:

```text
Master Pelanggaran
Catatan Pelanggaran Siswa
Tindak Lanjut Pelanggaran
Konseling BK
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
| Pimpinan | semua sesuai permission | tidak |
| BK | semua | semua |
| Wali | kelas Wali sesuai scope | tidak kecuali permission lain diberikan |
| Guru biasa | tidak | tidak |
| Siswa | diri sendiri bila surface mengizinkan | tidak |

Catatan Pelanggaran berisi kejadian, kategori, tanggal, keterangan, dan histori tindak lanjut. Tidak ada skor/poin.

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

## 5. Konseling BK — Data Rahasia

Tabel baru:

```text
konseling_bk
```

SQL resmi:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Permission khusus:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
```

Ketiga permission diberikan dengan scope `SEMUA` hanya kepada role `bk` pada SQL G3.3.1.

Selain permission route, Service juga memverifikasi effective role `bk`. Dengan demikian Catatan Konseling tidak otomatis dapat dibuka Admin, Operator, Pimpinan, Guru/Wali, atau Siswa.

Guru BK yang mencatat diambil otomatis dari identity user login (`users.id_guru`), bukan field bebas dari browser.

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
id_guru_bk = Guru identity user login
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

## 7. Referensi Jenis Layanan Konseling

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

Bidang:

```text
Pribadi
Sosial
Belajar
Karier
```

Topik mengikuti bidang. Referensi awal diadaptasi dari form BK yang disepakati pada G3.3.1 dan divalidasi server-side.

Rencana berikutnya:

```text
Selesai
Konseling lanjutan
Memanggil orang tua
Koordinasi dengan wali kelas
Kunjungan rumah
Rujuk ke UKS
Rujuk ke psikolog atau Puskesmas
```

## 8. Konseling — Export

Export XLSX hanya tersedia dengan permission:

```text
bk_konseling.export
```

Export memuat identitas siswa, kelas, data Tahap 1, isi Tahap 2, tindak lanjut, status, dan Guru BK.

Data export tetap mengikuti prinsip kerahasiaan role BK.

## 9. Prestasi Siswa

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

## 10. Kartu Pelajar

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

## 11. Generate / Reissue Kartu

Generate hanya untuk siswa yang sah pada scope actor.

Kontrak:

- satu request bulk maksimum 200 target;
- seluruh target divalidasi;
- operasi idempotent terhadap kartu yang sudah Aktif;
- transaksi dan logging ditangani Service;
- browser dapat mengulang batch sampai `remaining_count = 0`.

Reissue adalah lifecycle kartu berwenang dan tidak boleh digunakan untuk menembus scope siswa.

## 12. Canvas dan Background Kartu

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

## 13. QR dan Public Verify

Payload QR:

```text
SISFOUR|V1|NISN={nisn}|NAMA={nama_encoded}|VERIFY={kode_verifikasi}
```

Route public:

```text
GET /kartu/verify/{kode_verifikasi}
```

Public verify readonly dan hanya menampilkan data minimum yang diperlukan untuk verifikasi kartu.

## 14. Cetak Massal

Kontrak:

```text
maksimum 200 kartu
A4 portrait
2 kolom × 5 baris
10 kartu per halaman
side = front | back
```

## 15. Lifecycle Siswa

Saat siswa menjadi:

```text
Lulus
Pindah
Keluar
```

kartu Aktif dinonaktifkan melalui lifecycle Service yang berwenang. Histori Catatan Pelanggaran dan Konseling tetap disimpan sesuai relasinya.

## 16. Checkpoint G3.3.1

```text
Master Pelanggaran tanpa poin
Catatan Pelanggaran tanpa poin
Top Poin retired
Konseling BK terpisah dari Catatan Pelanggaran
Konseling Tahap 1 create
Konseling Tahap 2 update
Kelas -> Siswa terikat Tahun Ajaran aktif
confidential BK-only permission + Service role guard
export Konseling BK
SQL localhost + hosting
```

G3.4 Dashboard BK baru boleh memakai data Konseling setelah checkpoint G3.3.1 lulus regression dan schema SQL diterapkan.
