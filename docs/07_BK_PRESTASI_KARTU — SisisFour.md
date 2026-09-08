# 📋 BK, Prestasi & Kartu Pelajar — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026  
**Status:** FINAL v0.5 — BK + Prestasi + Kartu Dua Sisi + QR Universal

Dokumen ini menetapkan aturan BK, Pelanggaran, Catatan Kasus, Prestasi, Top Poin, Kartu Pelajar dan QR Verification.

---

# 1. Prinsip Identitas dan Scope

Identitas siswa canonical:

```text
siswa.id
```

Kelas aktif/historis di-resolve dari:

```text
anggota_kelas
riwayat_siswa
```

Wali bukan role. Scope Wali:

```text
KELAS_DIAMPU
```

Siswa menggunakan:

```text
DIRI_SENDIRI
```

dan target siswa wajib berasal dari `session('id_siswa')`, bukan query bebas.

---

# 2. Master Pelanggaran

Tabel:

```text
ref_pelanggaran
```

Field:

```text
id
nama_pelanggaran
kategori
poin
```

Kategori:

```text
Ringan
Sedang
Berat
```

Hak manage:

| Role | Akses |
|---|---|
| Admin | Full |
| Operator | Full |
| Pimpinan | Readonly melalui modul BK bila diberi view |
| BK | Full |
| Guru | Tidak |
| Wali | Tidak manage |
| Siswa | Tidak |

Permission:

```text
bk_pelanggaran_master.manage
```

Pelanggaran yang sudah dipakai Catatan Kasus tidak boleh dihapus bila FK RESTRICT.

Schema v0.5 menghitung poin histori dari `ref_pelanggaran.poin`; perubahan nilai referensi dapat mengubah agregat historis. Bila sekolah membutuhkan snapshot poin, schema harus diubah sebelum implementasi.

---

# 3. Catatan Kasus

Tabel:

```text
catatan_kasus
```

Field canonical:

```text
id
id_siswa
id_pelanggaran
tanggal
keterangan
id_guru_input
created_at
updated_at
updated_by
```

Permission:

```text
bk_kasus.manage
bk_kasus.view
```

Scope view canonical:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
```

## 3.1 Hak Akses Catatan Kasus

| Role | Manage | View |
|---|---|---|
| Admin | Semua | Semua |
| Operator | Semua | Semua |
| Pimpinan | Tidak | Semua readonly |
| BK | Semua | Semua |
| Guru biasa | Tidak | Tidak |
| Wali | Tidak | Siswa kelas Wali |
| Siswa | Tidak | **Diri sendiri** |

**Keputusan final v0.5:** Siswa tetap dapat melihat Catatan Kasus miliknya sendiri.

Role mapping Siswa:

```text
bk_kasus.view = DIRI_SENDIRI
```

Siswa tidak boleh create/edit/delete kasus, tidak boleh memilih `id_siswa` lain, dan tidak boleh membuka Top 20 global.

---

# 4. Input Kasus

Input minimum:

```text
Siswa
Pelanggaran
Tanggal
Keterangan
```

Actor tidak dipercaya dari browser.

Admin/Operator/BK memakai user login sebagai actor audit.

`id_guru_input` hanya boleh diisi identitas Guru yang benar. Jangan mengisi ID palsu. Bila actor administratif tanpa `id_guru` harus dapat manage, schema `id_guru_input` perlu dibuat nullable sebelum implementasi final BK.

---

# 5. View Wali

Wali dapat melihat detail kasus siswa kelas Wali, readonly.

Wali tidak boleh:

```text
create
edit
delete
manage master pelanggaran
melihat kelas lain
```

Jika mapping Wali nonaktif, akses hilang segera.

---

# 6. View Siswa

Query wajib:

```text
WHERE catatan_kasus.id_siswa = session('id_siswa')
```

Tidak menerima parameter target siswa bebas.

Dashboard Siswa boleh menampilkan Catatan Kasus terbaru miliknya sendiri. Halaman detail `bk/kasus` untuk Siswa harus otomatis scoped `DIRI_SENDIRI` dan readonly.

Data yang boleh ditampilkan kepada siswa:

```text
tanggal
nama pelanggaran
kategori
poin
keterangan
```

Data actor internal tidak wajib ditampilkan.

---

# 7. Histori Kasus

Kasus tidak hilang saat siswa:

```text
Pindah
Keluar
Lulus
```

Role scope `SEMUA` tetap dapat melihat histori.

Wali aktif hanya melihat kelas Wali saat ini kecuali kebijakan histori lintas kelas ditetapkan kemudian.

Siswa tetap dapat melihat histori kasus miliknya sendiri selama account/identity masih diizinkan mengakses sistem.

---

# 8. Poin dan Top 20

Total poin:

```text
SUM(ref_pelanggaran.poin)
```

Top 20 adalah widget analitik, bukan menu utama.

| Role | Top 20 |
|---|---|
| Admin | Semua |
| Operator | Semua |
| Pimpinan | Semua readonly |
| BK | Semua |
| Guru | Tidak |
| Wali | Kelas Wali |
| Siswa | Tidak |

Wali tidak boleh menerima Top 20 global lalu difilter frontend.

EWS Presensi tidak menggunakan poin BK.

---

# 9. Prestasi

Tabel:

```text
catatan_prestasi
```

Permission:

```text
prestasi.manage
prestasi.view
```

| Role | Manage | View |
|---|---|---|
| Admin | Semua | Semua |
| Operator | Semua | Semua |
| Pimpinan | Tidak | Semua readonly |
| BK | Semua | Semua |
| Guru | Tidak | Tidak |
| Wali | Tidak | Kelas Wali |
| Siswa | Tidak | Diri sendiri |

Scope:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
```

Tingkat Prestasi dapat berupa Madrasah/Kecamatan/Kabupaten/Provinsi/Nasional/Internasional dan tetap divalidasi Service/UI.

Upload bukti tidak diwajibkan v0.5.

---

# 10. Kartu Pelajar

Tabel bisnis:

```text
kartu_pelajar
```

Field bisnis:

```text
id
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Field teknis final:

```text
id_siswa_aktif
```

`id_siswa_aktif` adalah generated column: berisi `id_siswa` hanya ketika kartu `Aktif`, selain itu `NULL`. Unique index pada field ini menjamin maksimal satu kartu Aktif per siswa, termasuk pada request paralel.

Permission:

```text
kartu_pelajar.view
kartu_pelajar.manage
```

View:

| Role | Scope |
| --- | --- |
| Admin | SEMUA |
| Operator | SEMUA |
| Pimpinan | SEMUA |
| BK | Tidak |
| Guru biasa | Tidak |
| Wali | KELAS_DIAMPU |
| Siswa | DIRI_SENDIRI |

---

# 11. Generate / Reissue

Generate hanya untuk siswa `Aktif` dan belum memiliki kartu Aktif.

Generate batch wajib:

```text
validasi seluruh ID
→ validasi seluruh scope
→ BEGIN TRANSACTION
→ resolve existing/generate
→ COMMIT
```

Jika satu target gagal, seluruh batch rollback. Maksimum batch v0.5 = 200 siswa.

Server membuat:

```text
nomor_kartu
kode_verifikasi random 64 hex
tanggal_terbit
status_aktif = Aktif
```

Reissue bersifat visual dan tidak mengubah `nomor_kartu` maupun `kode_verifikasi`.

---

# 12. Cetak Dua Sisi

Kanvas canonical kedua sisi:

```text
1011 × 638 px
```

PDF individual:

```text
Page 1 = Sisi depan + data dinamis siswa
Page 2 = Sisi belakang statis
```

Sisi belakang tidak mempunyai overlay data siswa. Semua siswa menggunakan `background_kta_belakang` yang sama.

---

# 13. Layout Canonical Sisi Depan

Template depan yang disetujui menjadi sumber layout v0.5.

Background:

```text
setting_sistem.background_kta_depan
fallback = assets/kartu/default/background_kta_depan.jpg
```

Font preferred:

```text
Poppins
fallback Arial / DejaVu Sans
```

Overlay dinamis hanya:

```text
Nama
NISN
Kelas
Jenis Kelamin
Tahun Ajaran
Tempat/Tanggal Lahir
Alamat
Foto
QR
Nomor Kartu
```

**Kartu tidak memakai NIS; hanya NISN.**

Koordinat canonical pada canvas 1011×638:

```text
Foto
x=760 y=73 w=210 h=280
rasio 3:4

QR
x=810 y=375 w=120 h=120

Nomor Kartu
x=790 y=505 w=160
font 9px, center

Nama
x=40 y=175 w=570
font 42px, weight 800, uppercase, line-height 1.15
adaptive: 42 → 38 → 34px
maksimal area 2 baris

Meta (NISN/Kelas/Jenis Kelamin/Tahun Ajaran)
x=40 y=340 w=460
font isi 19px, weight 700
label 11px, weight 500

TTL
x=40 y=460 w=600
font 17px

Alamat
x=40 y=490 w=600
font 17px
maksimal area 2 baris
```

Foto siswa canonical disimpan oleh Master Siswa di `ROOTPATH/uploads/foto_siswa`; renderer Kartu wajib membaca lokasi tersebut dan fallback placeholder bila file tidak ada.

---

# 14. Sisi Belakang

Background:

```text
setting_sistem.background_kta_belakang
fallback = assets/kartu/default/background_kta_belakang.png
```

Tidak ada overlay:

```text
Nama
NISN
QR
Kelas
Foto
Nomor Kartu
```

Semua siswa mempunyai sisi belakang identik sesuai background aktif.

---

# 15. QR Universal V1

QR tidak berisi URL saja dan tidak berisi data sensitif.

Payload canonical:

```text
SISFOUR|V1|NISN={NISN}|NAMA={NAMA_URL_ENCODED}|VERIFY={KODE_VERIFIKASI}
```

Contoh:

```text
SISFOUR|V1|NISN=1234567890|NAMA=AHMAD%20FAUZI|VERIFY=8f9b...
```

Makna:

```text
SISFOUR = identifier format
V1      = versi payload
NISN    = universal student identifier untuk integrasi aplikasi
NAMA    = informasi tambahan; aplikasi harus URL-decode bila diperlukan
VERIFY  = authenticity identifier dari kartu_pelajar.kode_verifikasi
```

Aplikasi perpustakaan/kantin/gate boleh cukup parse `NISN`. Aplikasi yang membutuhkan validasi dapat memakai `NISN + VERIFY` dan endpoint/API SisisFour.

Dilarang memasukkan ke QR:

```text
NIK
alamat
TTL
telepon
password/JWT/token
data BK
data Presensi
```

---

# 16. QR Verification Publik

Endpoint tetap:

```text
/kartu/verify/{kode_verifikasi}
```

Read minimum tanpa login:

```text
Nama
Kelas
Nomor Kartu
Status Kartu
Status Siswa
Nama Madrasah
NIK masked
```

Tidak mengirim foto di payload publik v0.5. Tidak boleh menampilkan NIK penuh, alamat lengkap, orang tua, telepon, data BK, Presensi, atau credential.

---

# 17. Template Settings

Key canonical:

```text
background_kta_depan
background_kta_belakang
```

Modul Settings harus menyediakan upload Admin untuk kedua background. Asset wajib image valid, re-encode, path aman/non-executable, dan hasil akhir canonical 1011×638 px. Renderer selalu mempunyai fallback default agar cetak tidak fatal ketika setting belum diisi.

---

# 18. Nonaktif Otomatis

Saat siswa menjadi:

```text
Lulus
Pindah
Keluar
```

seluruh kartu Aktif siswa menjadi `Nonaktif` di transaction perubahan status siswa. Implementasi canonical berada pada `KelasService::luluskan()` dan `KelasService::mutasiSiswa()`.

---

# 19. Akun BK

Akun BK tidak dibuat dari modul BK.

Jika petugas BK berasal dari Pegawai:

```text
Master Pegawai → users role NULL → Settings/User Management → role bk
```

Jika petugas BK berasal dari Guru dan hanya bertugas sebagai BK, `users.id_guru` tetap boleh ada tetapi capability operasional dapat hanya role `bk`. Bila benar-benar merangkap Guru Mapel, gunakan multi-role `bk + guru`.

---

# 20. Service dan Security

Service canonical:

```text
BkService
PrestasiService
KartuPelajarService
KartuRenderService
```

Semua Service wajib resolve permission/scope server-side, tidak percaya actor/target dari browser, database-first, escape output, audit mutation, dan identitas Siswa `DIRI_SENDIRI` selalu dari session/token.

Catatan Kasus `manage` berarti create/update/delete. Create hanya untuk siswa aktif; edit histori tetap boleh mempertahankan siswa lama yang sudah Lulus/Pindah/Keluar.

---

# 21. Checkpoint Final

Uji minimal:

- Admin/Operator/BK create/update/delete Catatan Kasus;
- create Kasus menolak siswa nonaktif/deleted;
- Pimpinan readonly;
- Guru biasa tidak dapat akses kasus;
- Wali hanya siswa kelas Wali;
- Siswa hanya kasus diri dan tidak dapat Top 20;
- Prestasi scope SEMUA/KELAS_DIAMPU/DIRI_SENDIRI;
- satu siswa tidak dapat mempunyai dua kartu Aktif;
- generate batch atomic;
- preview/PDF sisi depan mengikuti koordinat template;
- PDF page 2 hanya background belakang;
- QR payload tepat `SISFOUR|V1|...` dan dapat diparse NISN;
- public verify tidak mengekspos foto/NIK penuh/BK/Presensi;
- Lulus/Pindah/Keluar menonaktifkan kartu dalam transaction;
- multi-role Guru+Operator dan Guru+Pimpinan tetap union permission.
