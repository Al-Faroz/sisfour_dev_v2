# 👤 Profile Guru & Profile Siswa — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan Profile pribadi Guru dan Siswa, termasuk hak akses, field, update, foto, API, sinkronisasi dengan Master Data, dan perlindungan IDOR.

Profile bukan pengganti Master Data.

---

# BAGIAN A — PRINSIP

## 1. Scope

Semua Profile:

```text
DIRI_SENDIRI
```

Target data tidak boleh dipilih bebas dari URL.

Sumber identitas:

```text
Web → session
API → JWT verified
```

---

## 2. Profile vs Master

### Profile Guru

Guru melihat/mengubah data diri yang diizinkan.

### Master Guru

Admin/Operator mengelola data administrasi seluruh Guru.

### Profile Siswa

Siswa melihat data diri readonly.

### Master Siswa

Admin/Operator mengelola seluruh siswa dan Wali mengelola biodata kelas Wali secara terbatas.

---

# BAGIAN B — PROFILE GURU

## 3. Permission

```text
profile_guru.view
profile_guru.edit
```

Scope:

```text
DIRI_SENDIRI
```

Actor yang dapat memakai Profile Guru bila mempunyai `id_guru` + permission:

- Guru;
- Wali;
- BK;
- Pimpinan;
- Operator/Admin bila account mereka memang mempunyai `id_guru` dan permission Profile.

Admin bootstrap tanpa `id_guru` tidak mempunyai target Profile Guru pribadi.

---

## 4. Field

| Field | Profile Guru |
|---|---|
| NIP | Readonly |
| Nama | Editable |
| Jenis Kelamin | Editable |
| Tempat Lahir | Editable |
| Tanggal Lahir | Editable |
| Alamat | Editable |
| No Telepon | Editable |
| Email | Editable |
| Status Kepegawaian | Editable bila kebijakan mengizinkan |
| Foto | Editable |

---

## 5. NIP

NIP tidak boleh diubah dari Profile.

Alasan:
- identifier login;
- uniqueness lintas Guru/Pegawai;
- relasi user;
- dampak audit.

Perubahan administratif NIP hanya lewat Master Guru oleh Admin/Operator.

---

## 6. Nama dan Data Identitas

Perubahan Profile Guru langsung memperbarui tabel:

```text
guru
```

Tidak membuat tabel profile terpisah.

Dampak historis:

- snapshot nama Guru di Jurnal/Presensi lama tidak berubah;
- laporan historis dapat tetap menggunakan snapshot.

---

## 7. Status Kepegawaian

Walaupun field dapat ditampilkan editable, implementasi final harus memutuskan apakah Guru boleh mengubah status administratifnya sendiri.

Kebijakan yang direkomendasikan untuk v0.5:

```text
Status Kepegawaian = readonly di Profile
```

karena bersifat administrasi.

Jika ingin editable, harus dinyatakan eksplisit sebelum coding.

---

# BAGIAN C — FOTO GURU

## 8. Ketentuan

```text
PNG
max 2 MB
crop 3:4
re-encode
nama random
metadata dibuang
```

Lokasi:

```text
uploads/foto_guru/
```

---

## 9. Upload

Server:
1. validasi permission;
2. resolve `id_guru` dari session/JWT;
3. validasi MIME/image;
4. decode;
5. crop/re-encode;
6. simpan;
7. update Guru;
8. hapus file lama bila aman;
9. log perubahan.

Jangan menerima `id_guru` bebas sebagai target.

---

# BAGIAN D — PROFILE SISWA

## 10. Permission

```text
profile_siswa.view
```

Scope:

```text
DIRI_SENDIRI
```

---

## 11. Field Siswa

Readonly:

| Field | Status |
|---|---|
| NIK | Readonly |
| NISN | Readonly |
| Nama | Readonly |
| Jenis Kelamin | Readonly |
| Tempat Lahir | Readonly |
| Tanggal Lahir | Readonly |
| Alamat | Readonly |
| No Telepon | Readonly |
| Kelas | Readonly |
| Foto | Readonly |
| Status | Readonly |

Siswa tidak mempunyai endpoint update Profile.

---

## 12. Kelas Siswa

Kelas Profile tidak diambil dari field siswa.

Gunakan:

```text
anggota_kelas
+
tahun_ajaran aktif
+
kelas
```

Jika tidak ada membership aktif:

```text
kelas = belum ditempatkan / tidak tersedia
```

Untuk siswa Lulus/Pindah/Keluar, UI dapat menampilkan kelas terakhir dari `riwayat_siswa` bila diperlukan.

---

## 13. Data yang Tidak Perlu Ditampilkan

Profile Siswa tidak harus menampilkan semua data keluarga.

Default tidak menampilkan:

- password;
- account role;
- auth_version;
- data BK;
- token;
- log activity.

Data orang tua/wali dapat ditampilkan hanya jika kebijakan UI membutuhkannya.

---

# BAGIAN E — KOREKSI BIODATA SISWA

## 14. Jalur Perubahan

Siswa menemukan kesalahan:

```text
lapor ke Wali/Admin/Operator
```

Admin/Operator:
- dapat memperbaiki Master Siswa.

Wali:
- dapat memperbaiki biodata siswa kelas Wali;
- tidak dapat mengubah NISN;
- tidak dapat melakukan mutasi/kelulusan/kenaikan.

Siswa:
- tidak update langsung.

---

# BAGIAN F — WEB ROUTE

## 15. Profile Guru

Contoh kontrak:

```text
GET  /profile/guru
PUT  /profile/guru
POST /profile/guru/foto
```

Target Guru tidak memakai `{id}` untuk workflow diri sendiri.

---

## 16. Profile Siswa

```text
GET /profile/siswa
```

Tidak ada:

```text
PUT /profile/siswa
POST /profile/siswa/foto
```

untuk role Siswa.

---

# BAGIAN G — API MOBILE

## 17. Guru

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto
```

JWT menentukan actor.

---

## 18. Siswa

```text
GET /api/profile/siswa
```

Readonly.

---

## 19. API Rule

Tidak menerima:

```text
?id_guru=
?id_siswa=
```

untuk mengganti target.

Jika API administratif kelak membutuhkan target ID, gunakan endpoint berbeda dengan permission admin yang eksplisit.

---

# BAGIAN H — SECURITY

## 20. IDOR

Dilarang:

```text
/profile/siswa?id_siswa=123
```

lalu server menampilkan siswa 123.

Web:

```text
target = session('id_siswa')
```

Guru:

```text
target = session('id_guru')
```

API:

```text
target = JWT identity
```

---

## 21. CSRF

Mutation Web Profile Guru:

```text
PUT
POST foto
```

wajib CSRF melalui wrapper Fetch global.

API JWT tidak memakai CSRF Web.

---

## 22. XSS

Output:

```php
esc(...)
```

untuk field teks.

Alamat, nama, email, dll tidak boleh dirender raw.

---

## 23. Upload Security

Foto harus:
- benar-benar image;
- tidak hanya berdasarkan extension;
- re-encode;
- random filename;
- tidak executable;
- size limit.

---

# BAGIAN I — USER ACCOUNT

## 24. Profile Bukan Account Settings

Profile tidak digunakan untuk:
- mengganti role;
- menambah multi-role;
- reset password;
- mengganti username;
- mengaktifkan/nonaktifkan account.

Itu domain Settings/User Management.

---

## 25. Sinkron Username

Karena NIP readonly di Profile Guru, update Profile tidak menyentuh username.

Karena Profile Siswa readonly, Profile tidak menyentuh username NISN.

Perubahan identifier dilakukan Master Data dengan sinkronisasi account.

---

# BAGIAN J — LOG ACTIVITY

## 26. Event

Profile Guru:

```text
UPDATE PROFILE
UPDATE FOTO PROFILE
```

dapat dicatat ke:

```text
log_activity
```

Keterangan tidak boleh menyimpan password/token.

Profile Siswa readonly tidak menghasilkan mutation log.

---

# BAGIAN K — SERVICE

## 27. Service yang Disarankan

```text
ProfileGuruService
ProfileSiswaService
UploadService
```

### `ProfileGuruService`
- resolve actor;
- get diri;
- update allowed fields;
- reject NIP;
- log.

### `ProfileSiswaService`
- resolve actor;
- get diri;
- resolve kelas aktif/terakhir.

### `UploadService`
- validasi;
- crop;
- re-encode;
- filename;
- cleanup.

---

# BAGIAN L — RESPONSE

## 28. HTML

Halaman Web menggunakan layout utama.

## 29. JSON

Endpoint API:

```json
{
  "status": "success",
  "message": "Profile berhasil dimuat.",
  "data": {}
}
```

Tidak boleh mengirim field account sensitif.

---

# BAGIAN M — ERROR RULE

## 30. Server Menolak Bila

- user tidak login;
- permission Profile tidak ada;
- `id_guru`/`id_siswa` identity tidak ada;
- request mencoba mengubah NIP;
- request Siswa mencoba update;
- file foto invalid;
- target ID dari request berbeda dengan identity actor;
- user account nonaktif.

---

# BAGIAN N — CHECKPOINT

## 31. Profile Guru

- view diri;
- update field allowed;
- NIP readonly;
- status administratif sesuai kebijakan;
- foto;
- CSRF;
- IDOR;
- snapshot histori tidak berubah;
- log.

## 32. Profile Siswa

- view diri;
- kelas dari anggota kelas;
- siswa lain tidak dapat diakses;
- seluruh field readonly;
- tidak ada endpoint update;
- tidak bocor data BK/account.

## 33. API

- JWT;
- diri sendiri;
- token invalid → 401;
- IDOR ditolak;
- response minimum.

---

# 34. Kriteria Selesai

Profile dinyatakan selesai bila:

1. scope selalu DIRI_SENDIRI;
2. Profile Guru tidak dapat mengubah NIP;
3. Profile Siswa readonly;
4. kelas siswa berasal dari Master Data final;
5. tidak ada IDOR;
6. upload Guru aman;
7. account settings terpisah;
8. API dan Web konsisten;
9. mutation terlindungi CSRF/JWT sesuai channel.
