# Profile Guru & Profile Siswa — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Scope

Profile selalu:

```text
DIRI_SENDIRI
```

Profile bukan Master Data dan bukan User Management.

# 2. Permission Database

```text
profile_guru.view
profile_guru.edit
profile_siswa.view
```

Role dengan Profile Guru self permission pada baseline mencakup Admin, Operator, Pimpinan, BK, dan Guru sesuai role_permissions. Target hanya valid jika account mempunyai `id_guru`.

Siswa mempunyai `profile_siswa.view = DIRI_SENDIRI`.

# 3. Route Web Terdaftar

```text
GET  /profile/guru
GET  /profile/guru/json
PUT  /profile/guru/update
POST /profile/guru/upload-foto

GET  /profile/siswa
GET  /profile/siswa/json
```

# 4. Route API Terdaftar

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto
GET  /api/profile/siswa
```

# 5. Status Runtime Baseline

Pada repo baseline `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`, route tersebut menunjuk:

```text
ProfileGuru
ProfileSiswa
```

Namun file berikut tidak ditemukan di `app/Controllers/`:

```text
ProfileGuru.php
ProfileSiswa.php
```

Pencarian class yang sama juga tidak menemukan implementasi.

Dengan demikian **Profile belum runtime-ready** pada baseline ini.

# 6. Kontrak Profile Guru

Target:

```text
users.id_guru
```

NIP readonly.

Field pribadi yang boleh diubah:

```text
nama
jenis_kelamin
tempat_lahir
tanggal_lahir
alamat
no_telepon
email
foto
```

Status kepegawaian adalah data administratif dan tidak diubah sendiri melalui Profile.

Foto:

```text
uploads/foto_guru/
```

# 7. Kontrak Profile Siswa

Target:

```text
users.id_siswa
```

Readonly.

Kelas berasal dari membership aktif dan dapat memakai riwayat untuk histori.

Siswa tidak mempunyai endpoint mutation Profile.

# 8. IDOR

Dilarang memilih target melalui parameter bebas:

```text
?id_guru=...
?id_siswa=...
```

Actor Web dari session, actor API dari token.

# 9. Account Setting

Profile tidak mengubah:

- username;
- role;
- secondary role;
- password;
- status account;
- auth_version secara administratif.

# 10. Release Blocker

Sebelum rilis wajib memilih dan menyelesaikan salah satu:

1. implementasikan Controller/Service/View/API Profile sesuai route dan permission yang sudah ada; atau
2. keluarkan Profile dari scope dan hapus route/menu/permission secara konsisten.

Route tidak boleh menunjuk Controller yang tidak ada.

# 11. Checkpoint Setelah Sinkron

- Guru view/edit diri;
- NIP readonly;
- foto;
- Siswa readonly diri;
- kelas resolve;
- IDOR;
- CSRF Web;
- JWT API;
- role tanpa identity ditangani;
- tidak ada 404/500.
