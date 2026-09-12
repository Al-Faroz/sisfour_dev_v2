# BK, Prestasi & Kartu Pelajar — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog**.

## 1. Ruang Lingkup

Modul pada dokumen ini:

```text
Master Pelanggaran
Catatan Kasus
Tindak Lanjut Kasus
Prestasi Siswa
Kartu Pelajar
Public Verify Kartu
```

Authorization target tetap diputuskan Service; View/JavaScript bukan security boundary.

## 2. Master Pelanggaran

Tabel:

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

Mutation create/update/delete memakai Fetch dan memiliki busy guard agar double-submit dari UI tidak menghasilkan request ganda.

## 3. Catatan Kasus

Tabel:

```text
catatan_kasus
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
| Siswa | diri sendiri bila endpoint/dashboard mengizinkan | tidak |

Create baru hanya untuk siswa yang valid pada scope actor. Histori tidak hilang ketika lifecycle siswa kemudian berubah.

## 4. Tindak Lanjut Kasus

Tabel:

```text
tindak_lanjut_kasus
```

Relasi:

```text
catatan_kasus 1:N tindak_lanjut_kasus
```

Tindak lanjut dibuat/diubah melalui permission `bk_kasus.manage` dan divalidasi kembali terhadap kasus target.

UI Kasus dan Tindak Lanjut memiliki busy guard pada mutation.

## 5. Poin Pelanggaran

Agregat poin berasal dari referensi pelanggaran:

```text
SUM(ref_pelanggaran.poin)
```

Poin bukan credential dan bukan authorization signal. Perubahan nilai referensi harus mempertimbangkan dampak histori laporan.

## 6. Prestasi Siswa

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

Mutation Prestasi memakai busy guard.

## 7. Kartu Pelajar

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

Scope view/manage tetap dihitung Service.

## 8. Generate Kartu

Generate hanya untuk siswa yang sah pada scope actor.

Kontrak:

- satu request bulk maksimum 200 target;
- seluruh target divalidasi;
- operasi idempotent terhadap kartu yang sudah Aktif;
- transaksi dan logging ditangani Service;
- browser dapat mengulang batch sampai `remaining_count = 0`.

## 9. Reissue

Reissue adalah lifecycle kartu yang berwenang dan tidak boleh digunakan untuk menembus scope siswa.

Nomor kartu/kode verifikasi mengikuti kontrak Service yang berlaku; client tidak boleh membuat nilainya sendiri.

## 10. Canvas dan Background

Ukuran kartu canonical:

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

Upload template dari Settings dinormalisasi ke 1011×638 dan disimpan di:

```text
uploads/settings/kartu/
```

Renderer final menggunakan background **shared/cached per request** sehingga cetak massal tidak menduplikasi base64 background pada setiap item kartu.

## 11. Isi Depan

Overlay utama:

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

NIS tidak dipakai sebagai identitas kartu baseline.

## 12. QR dan Public Verify

Payload QR:

```text
SISFOUR|V1|NISN={nisn}|NAMA={nama_encoded}|VERIFY={kode_verifikasi}
```

Route public:

```text
GET /kartu/verify/{kode_verifikasi}
```

Public verify readonly dan hanya menampilkan data minimum yang diperlukan untuk verifikasi kartu.

## 13. Cetak Massal

Route mutation:

```text
POST /kartu/cetak-massal
```

Kontrak:

```text
maksimum 200 kartu
A4 portrait
2 kolom × 5 baris
10 kartu per halaman
side = front | back
```

Optimasi release:

- background depan/belakang hanya dimuat sekali per request;
- sisi belakang tidak membuat QR/foto/data individual yang tidak digunakan;
- Dompdf tetap `isRemoteEnabled = false`;
- grid fisik kartu tidak berubah.

## 14. Lifecycle Siswa

Saat siswa:

```text
Lulus
Pindah
Keluar
```

kartu Aktif dinonaktifkan melalui lifecycle Service yang berwenang.

## 15. Checkpoint

- Master Pelanggaran CRUD.
- Kasus scope + tindak lanjut 1:N.
- Prestasi scope.
- Busy guard mutation.
- Satu kartu Aktif per siswa.
- Generate tunggal/bulk.
- Max batch/print 200.
- Preview/download.
- Cetak massal depan/belakang.
- QR + public verify.
- Lifecycle kartu.
- Background fallback/override.
- Tidak ada IDOR pada target siswa/kartu.
