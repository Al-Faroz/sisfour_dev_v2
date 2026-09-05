# 👤 Profile Guru & Profile Siswa — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini mengatur profile pribadi Guru dan Siswa serta batas data yang dapat dilihat/diedit oleh user.

---

# 1. Prinsip

Modul Profile berbeda dari Master Data.

Profile digunakan untuk:

```text
melihat data diri
```

dan bagi Guru:

```text
mengubah data diri yang diizinkan
```

Semua akses menggunakan:

```text
DIRI_SENDIRI
```

---

# 2. Profile Guru

## 2.1 Hak Akses

Yang memiliki `id_guru` dan permission profile dapat mengakses profile Guru:

- Guru Biasa
- Wali Kelas
- BK
- Pimpinan
- user lain yang memang memiliki permission profile_guru

### Permission

```text
profile_guru.view
profile_guru.edit
```

Scope:

```text
DIRI_SENDIRI
```

---

# 3. Field Profile Guru

| Field | Status |
| --- | --- |
| NIP | Readonly |
| Nama Lengkap & Gelar | Editable |
| Jenis Kelamin | Editable |
| Tempat Lahir | Editable |
| Tanggal Lahir | Editable |
| Alamat | Editable |
| No Telepon | Editable |
| Email | Editable |
| Status Kepegawaian | Editable sesuai aturan |
| Foto | Editable |

### NIP

NIP adalah identifier utama dan:

```text
tidak boleh diubah melalui Profile
```

Perubahan NIP, apabila diperlukan secara administratif, hanya dilakukan melalui proses Master Data oleh Admin/Operator.

---

# 4. Foto Guru

Ketentuan:

- PNG,
- maksimal 2MB,
- crop 3:4,
- re-encode,
- nama file random,
- metadata berbahaya dihapus.

Lokasi:

```text
uploads/foto_guru/
```

---

# 5. Profile Siswa

## 5.1 Hak Akses

Hanya siswa yang sedang login sebagai dirinya sendiri.

Permission:

```text
profile_siswa.view
```

Scope:

```text
DIRI_SENDIRI
```

---

# 6. Field Profile Siswa

Readonly seluruhnya.

| Field | Status |
| --- | --- |
| NIK | Readonly |
| NISN | Readonly |
| Nama Lengkap | Readonly |
| Jenis Kelamin | Readonly |
| Tempat Lahir | Readonly |
| Tanggal Lahir | Readonly |
| Alamat | Readonly |
| No Telepon | Readonly |
| Kelas | Readonly |
| Foto | Readonly |

Siswa tidak memiliki endpoint update profile.

---

# 7. Perubahan Biodata Siswa

Jika siswa menemukan kesalahan data:

- Admin/Operator dapat memperbaiki.
- Wali Kelas dapat memperbaiki biodata siswa di kelas walinya sesuai hak akses Master Siswa.
- Siswa sendiri tidak dapat mengubah data.

Khusus:

```text
NISN
```

**tidak boleh diedit oleh Wali Kelas.**

---

# 8. Profile vs Master Data

## Profile Guru

Digunakan Guru untuk mengubah data diri yang diizinkan.

## Master Guru

Digunakan Admin/Operator untuk pengelolaan administrasi seluruh guru.

## Profile Siswa

Readonly untuk siswa.

## Master Siswa

Digunakan Admin/Operator dan secara terbatas Wali Kelas.

---

# 9. API

### Profile Guru

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto
```

### Profile Siswa

```text
GET /api/profile/siswa
```

Semua endpoint API harus:

- menggunakan JWT,
- mengambil identitas dari token/session,
- tidak menerima ID user/guru/siswa bebas untuk mengganti target data,
- menerapkan scope `DIRI_SENDIRI`.

---

# 10. Keamanan IDOR

Contoh yang dilarang:

```text
/profile/siswa?id_siswa=123
```

lalu sistem menampilkan siswa 123 tanpa memastikan siswa tersebut adalah user yang login.

Sumber identitas harus berasal dari:

```text
session('id_siswa')
```

atau identitas yang telah diverifikasi dari JWT.

Hal yang sama berlaku untuk Profile Guru.

---

# 11. Catatan Developer

1. Jangan menyediakan endpoint edit untuk Profile Siswa.
2. NIP Guru readonly di Profile.
3. NISN Siswa immutable untuk Wali.
4. Scope profile selalu `DIRI_SENDIRI`.
5. Wali Kelas bukan role profile tersendiri.
6. Hak akses profile tetap menggunakan PermissionFilter.
7. Data profile tidak boleh dipakai sebagai bypass Master Data.

---

© 2026 SisisFour · MTsN 4 Jombang · Profile Final
