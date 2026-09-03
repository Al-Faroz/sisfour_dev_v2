# 👤 Profile Guru &amp; Profile Siswa — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini mengatur mekanisme pengelolaan data diri untuk **Guru** dan **Siswa**. Modul Profile adalah fitur khusus yang memungkinkan pengguna melihat dan (untuk Guru) mengedit data diri mereka sendiri, terpisah dari modul Master Data yang bersifat administratif.

* * *

## 1. Pendahuluan

Modul Profile memiliki dua jenis akses:

- **Profile Guru** — untuk role yang memiliki `id_guru` (Guru, Wali Kelas, BK, Pimpinan).
- **Profile Siswa** — untuk role Siswa.

**Catatan:** Admin dan Operator **tidak** memiliki menu Profile terpisah. Mereka dapat mengubah data diri melalui modul **Master Data Guru** (karena mereka memiliki akses CRUD penuh). Menu Data Guru (`master/guru`) juga **disembunyikan** untuk Guru/Wali — digantikan oleh Profile.

* * *

## 2. Profile Guru

### 2.1 Hak Akses

- **Role yang mendapat akses:** Guru, Wali Kelas, BK, Pimpinan (semua yang memiliki `id_guru` di session).
- **Admin &amp; Operator:** Tidak memiliki menu Profile Guru.
- **Permission:**
  
  - `profile_guru.view` (scope: DIRI\_SENDIRI)
  - `profile_guru.edit` (scope: DIRI\_SENDIRI)

### 2.2 Field yang Ditampilkan &amp; Status Edit

| Field                        | Tipe Input        | Status   | Keterangan                                                                                 |
|------------------------------|-------------------|----------|--------------------------------------------------------------------------------------------|
| **NIP**                      | Text              | Readonly | Satu-satunya field yang terkunci. Hanya Admin/Operator dapat mengubah melalui Master Data. |
| **Nama Lengkap &amp; Gelar** | Text              | Editable | Dapat diubah (gelar sering berubah).                                                       |
| **Jenis Kelamin**            | Select (L/P)      | Editable | Dapat diubah.                                                                              |
| **Tempat Lahir**             | Text              | Editable | Dapat diubah.                                                                              |
| **Tanggal Lahir**            | Date              | Editable | Dapat diubah.                                                                              |
| **Alamat**                   | Textarea          | Editable | Dapat diubah.                                                                              |
| **No Telepon**               | Text              | Editable | Dapat diubah.                                                                              |
| **Email**                    | Email             | Editable | Dapat diubah.                                                                              |
| **Status Kepegawaian**       | Select            | Editable | Pilihan: PNS, PPPK, NON ASN, Yayasan, Outsourcing.                                         |
| **Foto**                     | File Upload (PNG) | Editable | Max 2MB, crop 3:4, re-encode.                                                              |

### 2.3 Upload Foto

- **Format:** PNG saja.
- **Ukuran:** Maksimal 2MB.
- **Proses (B7):** Re-encode menggunakan `imagecreatefrompng()` + `imagepng()` untuk menghilangkan metadata berbahaya.
- **Crop:** Rasio 3:4 (sama dengan Kartu Pelajar).
- **Preview:** Sebelum upload, user dapat melihat preview hasil crop di modal.
- **Penyimpanan:** `uploads/foto_guru/{nama_file}.png` dengan nama random (contoh: `guru_1_20260827.png`).
- **Validasi:** Jika file bukan PNG atau ukuran &gt; 2MB, upload ditolak.

* * *

## 3. Profile Siswa

### 3.1 Hak Akses

- **Role yang mendapat akses:** Siswa (hanya yang login sebagai siswa).
- **Status:** Readonly — siswa **hanya** dapat melihat, tidak dapat mengubah data apapun.
- **Permission:** `profile_siswa.view` (scope: DIRI\_SENDIRI).

### 3.2 Field yang Ditampilkan (Readonly)

| Field             | Tipe       | Keterangan                                  |
|-------------------|------------|---------------------------------------------|
| **NIK**           | Text       | Nomor Induk Kependudukan (16 digit).        |
| **NISN**          | Text       | Nomor Induk Siswa Nasional (basis login).   |
| **Nama Lengkap**  | Text       |                                             |
| **Jenis Kelamin** | Text (L/P) |                                             |
| **Tempat Lahir**  | Text       |                                             |
| **Tanggal Lahir** | Date       |                                             |
| **Alamat**        | Textarea   |                                             |
| **No Telepon**    | Text       |                                             |
| **Kelas**         | Text       | Diambil dari `anggota_kelas` (tahun aktif). |
| **Foto**          | Image      | Hanya menampilkan foto (jika ada).          |

**Catatan:** Semua field Readonly. Jika ada perubahan data, siswa harus melapor ke Admin/Operator atau Wali Kelas (untuk biodata).

* * *

## 4. Endpoint API (Dual-Output)

Semua endpoint mendukung `?format=json` untuk mobile (Cordova) dan dilindungi oleh JWT.

### 4.1 Profile Guru API

| Method | Endpoint                 | Deskripsi                                 | Auth |
|--------|--------------------------|-------------------------------------------|------|
| GET    | `/api/profile/guru`      | Ambil data profile guru yang login        | JWT  |
| PUT    | `/api/profile/guru`      | Update data profile guru (field terbatas) | JWT  |
| POST   | `/api/profile/guru/foto` | Upload foto profile guru (PNG, max 2MB)   | JWT  |

#### GET /api/profile/guru — Response Sukses

```
{
    "status": "success",
    "data": {
        "id": 1,
        "nip": "196808212003122001",
        "nama": "MUKAROMAH, S.Ag",
        "jenis_kelamin": "P",
        "tempat_lahir": "Jombang",
        "tanggal_lahir": "1968-08-21",
        "alamat": "Jl. Merdeka No. 10, Jombang",
        "no_telepon": "081234567890",
        "email": "mukaromah@example.com",
        "status_kepegawaian": "PNS",
        "foto": "uploads/foto_guru/guru_1_20260824.png",
        "foto_url": "https://domain.com/uploads/foto_guru/guru_1_20260824.png"
    }
}
    
```

#### PUT /api/profile/guru — Request Body

```
{
    "nama": "MUKAROMAH, S.Ag., M.Pd",
    "jenis_kelamin": "P",
    "tempat_lahir": "Jombang",
    "tanggal_lahir": "1968-08-21",
    "alamat": "Jl. Merdeka No. 10, Jombang",
    "no_telepon": "081234567890",
    "email": "mukaromah@example.com",
    "status_kepegawaian": "PNS"
}
    
```

**Larangan:** Field `nip` TIDAK BOLEH dikirim. Sistem akan menolak request jika field NIP disertakan.

#### POST /api/profile/guru/foto — Upload Foto

- **Content-Type:** `multipart/form-data`
- **Field:** `foto` (file PNG, max 2MB)
- **Response Sukses:**
  
  ```
  {
      "status": "success",
      "message": "Foto berhasil diupload",
      "data": {
          "foto": "uploads/foto_guru/guru_1_20260827.png",
          "foto_url": "https://domain.com/uploads/foto_guru/guru_1_20260827.png"
      }
  }
              
  ```

- **Response Error (format salah):**
  
  ```
  {
      "status": "error",
      "message": "Format foto harus PNG",
      "code": 400
  }
              
  ```

### 4.2 Profile Siswa API

| Method | Endpoint             | Deskripsi                                      | Auth |
|--------|----------------------|------------------------------------------------|------|
| GET    | `/api/profile/siswa` | Ambil data profile siswa yang login (readonly) | JWT  |

#### GET /api/profile/siswa — Response Sukses

```
{
    "status": "success",
    "data": {
        "id": 1,
        "nik": "3510123412341234",
        "nisn": "9876543210",
        "nama": "Ahmad Fauzi",
        "jenis_kelamin": "L",
        "tempat_lahir": "Jombang",
        "tanggal_lahir": "2008-01-15",
        "alamat": "Jl. Merdeka No. 10, Jombang",
        "no_telepon": "081234567890",
        "kelas": "7-A",
        "foto": "uploads/foto_siswa/siswa_1_20260824.png",
        "foto_url": "https://domain.com/uploads/foto_siswa/siswa_1_20260824.png"
    }
}
    
```

* * *

## 5. Menu &amp; Routing

### 5.1 Struktur Menu

| ID | Nama Menu     | Parent | Urutan | Icon       | Link          | Akses Role                     |
|----|---------------|--------|--------|------------|---------------|--------------------------------|
| 9  | Profile Guru  | NULL   | 7      | bx bx-user | profile/guru  | Guru, Wali Kelas, BK, Pimpinan |
| 10 | Profile Siswa | NULL   | 7      | bx bx-user | profile/siswa | Siswa                          |

### 5.2 Controller &amp; Route

| Controller     | Route (Web)      | Route (API)                               | Fungsi                                                   |
|----------------|------------------|-------------------------------------------|----------------------------------------------------------|
| `ProfileGuru`  | `/profile/guru`  | `/api/profile/guru` (GET, PUT, POST foto) | View &amp; edit data diri guru (hanya NIP yang readonly) |
| `ProfileSiswa` | `/profile/siswa` | `/api/profile/siswa` (GET)                | View data diri siswa (readonly)                          |

* * *

## 6. Catatan Penting untuk Developer

- **Validasi Field Readonly:** Controller `ProfileGuru::update()` **WAJIB** menolak field `nip` jika dikirim dalam request (meskipun diabaikan, lebih aman ditolak dengan pesan error).
- **Nama &amp; JK:** Tidak seperti sebelumnya, field `nama` dan `jenis_kelamin` sekarang **editable** di Profile Guru.
- **Upload Foto (B7):** Gunakan mekanisme re-encode yang sama dengan Master Data Guru. Simpan dengan nama random (`guru_{id}_{timestamp}.png`).
- **Pengaruh ke Master Data:** Perubahan data di Profile Guru **langsung** mengupdate tabel `guru`. Tidak ada tabel terpisah.
- **Scope DIRI\_SENDIRI:** Pastikan model/query selalu menggunakan `where('id_guru', session('id_guru'))` atau `where('id_siswa', session('id_siswa'))` untuk mencegah user lain mengakses data profile orang lain.
- **Admin/Operator:** Mereka mengelola data diri melalui **Master Data Guru** (bukan Profile). Pastikan menu Profile tidak muncul untuk mereka (sudah diatur di `role_menus`).
- **API Completeness:** Pastikan route `PUT /api/profile/guru` dan `POST /api/profile/guru/foto` tercantum di **Routes Final** untuk mendukung mobile (Cordova).
- **Dual-Output:** Controller harus mendukung `?format=json` untuk web dan JWT untuk API.
- **NIK di Profile Siswa:** Tampilkan NIK penuh di profile siswa (karena ini area terautentikasi, berbeda dengan verifikasi publik yang dimasking).

* * *

© 2026 SisisFour · MTsN 4 Jombang · Profile Final
