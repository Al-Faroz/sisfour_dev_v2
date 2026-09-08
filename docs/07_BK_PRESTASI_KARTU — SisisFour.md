# BK, Prestasi & Kartu Pelajar — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Master Pelanggaran

Tabel:

```text
ref_pelanggaran
```

Kategori:

```text
Ringan
Sedang
Berat
```

Permission:

```text
bk_pelanggaran_master.manage
```

Baseline memiliki 12 referensi.

# 2. Catatan Kasus

Tabel:

```text
catatan_kasus
```

Field utama:

```text
id_siswa
id_pelanggaran
tanggal
keterangan
id_guru_input nullable
created_at
updated_at
updated_by
```

Permission:

```text
bk_kasus.manage
bk_kasus.view
```

# 3. Akses Kasus

| Actor | Manage | View |
|---|---|---|
| Admin | Semua | Semua |
| Operator | Semua | Semua |
| Pimpinan | Tidak | Semua |
| BK | Semua | Semua |
| Guru | Tidak | Tidak |
| Wali | Tidak | kelas Wali |
| Siswa | Tidak | diri |

Create baru hanya untuk Siswa aktif. Histori tetap tersedia setelah lifecycle nonaktif.

# 4. Poin

Agregat poin menggunakan:

```text
SUM(ref_pelanggaran.poin)
```

Poin tidak disnapshot pada Catatan Kasus.

# 5. Prestasi

Tabel:

```text
catatan_prestasi
```

Permission:

```text
prestasi.manage
prestasi.view
```

Admin/Operator/BK manage semua. Pimpinan view semua. Wali view kelas sendiri. Siswa view diri.

# 6. Tingkat Prestasi

```text
Madrasah
Kecamatan
Kabupaten
Provinsi
Nasional
Internasional
```

# 7. Kartu Pelajar

Tabel:

```text
kartu_pelajar
```

Field bisnis:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Generated field:

```text
id_siswa_aktif
```

menjamin maksimal satu kartu Aktif per siswa.

# 8. Permission Kartu

```text
kartu_pelajar.manage
kartu_pelajar.view
```

View:

- Admin/Operator/Pimpinan = SEMUA;
- Wali = KELAS_DIAMPU;
- Siswa = DIRI_SENDIRI;
- BK/Guru biasa = tidak.

# 9. Generate/Reissue

Generate hanya siswa Aktif.

Batch:

- seluruh ID divalidasi;
- seluruh scope divalidasi;
- transaction;
- idempotent terhadap kartu Aktif.

Reissue visual tidak mengganti nomor/kode verifikasi.

# 10. Canvas

```text
1011 × 638 px
```

Depan dinamis, belakang statis.

# 11. Fallback

```text
assets/kartu/default/background_kta_depan.jpg
assets/kartu/default/background_kta_belakang.jpg
```

Setting override:

```text
background_kta_depan
background_kta_belakang
```

# 12. Depan

Overlay:

```text
Nama
NISN
Kelas
JK
Tahun Ajaran
TTL
Alamat
Foto
QR
Nomor Kartu
```

Tidak memakai NIS.

# 13. QR

```text
SISFOUR|V1|NISN={nisn}|NAMA={nama_encoded}|VERIFY={kode_verifikasi}
```

Tidak boleh berisi data sensitif.

# 14. Public Verify

```text
GET /kartu/verify/{kode_verifikasi}
```

Readonly tanpa login, data minimum.

# 15. Lifecycle

Saat:

```text
Lulus
Pindah
Keluar
```

kartu Aktif menjadi Nonaktif dalam transaction lifecycle Siswa.

# 16. Akun BK

Akun BK disiapkan melalui Master Guru/Pegawai + Settings User Management.

# 17. Checkpoint

- scope;
- Top 20;
- Prestasi;
- satu kartu Aktif;
- batch;
- render;
- QR;
- public verify;
- lifecycle.
