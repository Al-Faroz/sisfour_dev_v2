# 📋 BK, Prestasi & Kartu Pelajar — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026  
**Status:** FINAL setelah koreksi akses Catatan Kasus Siswa

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
| --- | --- |
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
| --- | --- | --- |
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
| --- | --- |
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
| --- | --- | --- |
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

Tabel:

```text
kartu_pelajar
```

Field:

```text
id
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Status:

```text
Aktif
Nonaktif
```

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

Manage v0.5 mengikuti role_permissions final database. Wali dan Siswa tidak manage.

---

# 11. Generate / Reissue

Generate hanya bila siswa belum mempunyai kartu sesuai kebijakan idempotensi.

Server membuat:

```text
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif = Aktif
```

`kode_verifikasi` harus random dan sulit ditebak.

Reissue visual tidak mengubah identitas kartu secara diam-diam.

---

# 12. Cetak

Cetak individual harus scoped server-side.

Wali hanya kelas Wali. Siswa hanya kartu diri.

Cetak massal wajib memvalidasi seluruh ID siswa di server dan tidak mempercayai checkbox client sebagai authorization.

---

# 13. Format Kartu

Kanvas canonical:

```text
1011 × 638 px
```

Field dapat mencakup:

```text
Nama
NIK
Kelas
Jenis Kelamin
Tahun Ajaran
Tempat/Tanggal Lahir
Alamat
Foto
QR
Nomor/Kode Kartu
```

Foto mengikuti aturan Master Siswa. Bila foto tidak ada, gunakan placeholder; jangan menggagalkan seluruh generate.

---

# 14. QR Verification Publik

Endpoint:

```text
/kartu/verify/{kode_verifikasi}
```

Tanpa login, read minimum saja.

Boleh:

```text
Nama
Kelas
Status kartu
Nama madrasah
Nomor/kode seperlunya
Foto bila kebijakan mengizinkan
```

Tidak boleh:

```text
NIK penuh
alamat lengkap
orang tua
telepon
data BK
riwayat Presensi
credential/token
```

NIK pada halaman publik wajib masking.

---

# 15. Nonaktif Otomatis

Saat siswa menjadi:

```text
Lulus
Pindah
Keluar
```

kartu aktif menjadi `Nonaktif`, idealnya dalam transaction perubahan status siswa.

---

# 16. Service dan Security

Service yang disarankan:

```text
BkService
PrestasiService
KartuPelajarService
KartuRenderService
```

Semua Service wajib:

- resolve permission + scope server-side;
- tidak percaya target/actor dari browser;
- database-first untuk filter/agregasi;
- escape output;
- audit mutation;
- Siswa `DIRI_SENDIRI` selalu dari session/token identity.

---

# 17. Checkpoint

Uji minimal:

- Admin/Operator/BK manage Catatan Kasus;
- Pimpinan readonly;
- Guru biasa tidak dapat akses kasus;
- Wali hanya siswa kelas Wali;
- Siswa dapat melihat kasus diri dan tidak dapat mengganti target siswa;
- Siswa tidak dapat membuka Top 20;
- Prestasi dan Kartu Siswa hanya diri;
- menu dan direct URL menghasilkan scope yang sama.
