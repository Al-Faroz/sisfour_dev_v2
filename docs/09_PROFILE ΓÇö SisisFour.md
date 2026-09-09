# Profile Guru, Pegawai & Siswa — SisisFour

**Versi Acuan:** v0.8 PHASE 3.1 HARDENING  
**Tanggal Acuan:** 09 September 2026  
**Baseline Aplikasi:** `main` @ `5d4d44c3b1c16aa7b51b9ff7a3976fcfe018671f`  
**Baseline Database:** `sisfour_dev_v2 (28).sql`

Dokumen ini adalah kontrak Profile SisisFour setelah implementasi Personalia/Portofolio dan hardening Phase 3.1.

---

# 1. Prinsip

Profile selalu bekerja pada:

```text
DIRI_SENDIRI
```

Profile bukan Master Data dan bukan User Management. Target identity tidak boleh dipilih melalui parameter bebas.

Identity berasal dari relasi pada `users`:

```text
users.id_guru
users.id_pegawai
users.id_siswa
```

# 2. Profile Guru

Route Web utama:

```text
GET  /profile/guru
GET  /profile/guru/json
PUT  /profile/guru/update
POST /profile/guru/upload-foto
```

Profile Guru hanya tersedia bila account mempunyai `users.id_guru`.

Data identitas inti yang dapat diedit sendiri mengikuti `ProfileService`/`ProfileGuru`. Credential tidak dapat diubah dari Profile.

NIK/NIP tetap merupakan identitas administratif. Perubahan NIP yang mengubah login identifier hanya dilakukan melalui Master Guru oleh actor berwenang dan mengikuti sinkronisasi account Phase 2.

Foto Guru:

```text
uploads/foto_guru/
```

# 3. Profile Pegawai

Route Web utama:

```text
GET  /profile/pegawai
GET  /profile/pegawai/json
PUT  /profile/pegawai/update
POST /profile/pegawai/upload-foto
```

Profile Pegawai hanya tersedia bila account mempunyai `users.id_pegawai`.

Pegawai tidak membutuhkan role baru `pegawai`. Account Pegawai memperoleh hak operasional dari role yang dimiliki, sedangkan Profile self ditentukan oleh identity `id_pegawai`.

Foto Pegawai:

```text
uploads/foto_pegawai/
```

# 4. Route API Profile Existing

Route API berada di group `api` dengan filter `auth:api`:

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto

GET  /api/profile/pegawai
PUT  /api/profile/pegawai
POST /api/profile/pegawai/foto

GET  /api/profile/siswa
```

Endpoint API Personalia/Portofolio Phase 3 belum ditambahkan; kontrak Personalia Phase 3 saat ini adalah Web terlebih dahulu.

# 5. Profile Siswa

Route Web:

```text
GET /profile/siswa
GET /profile/siswa/json
```

Target selalu `users.id_siswa`.

Profile Siswa bersifat readonly untuk data identitas. Kelas berasal dari membership tahun ajaran dan histori tersedia melalui riwayat siswa.

# 6. Riwayat Personalia Guru/Pegawai

Phase 3 menambahkan self-service Personalia.

### Guru self

```text
GET    /profile/guru/personalia
POST   /profile/guru/personalia/save/{category}
DELETE /profile/guru/personalia/delete/{category}/{record_id}
GET    /profile/guru/portofolio
```

### Pegawai self

```text
GET    /profile/pegawai/personalia
POST   /profile/pegawai/personalia/save/{category}
DELETE /profile/pegawai/personalia/delete/{category}/{record_id}
GET    /profile/pegawai/portofolio
```

Kategori:

```text
pendidikan
penugasan
pangkat
dokumen
```

Self-service yang mempunyai hak edit **boleh menambah, memperbarui, dan menghapus record miliknya sendiri**. Kebijakan self-delete ini dikunci pada Phase 3.1. Tidak ada workflow approval/verifikasi.

Semua mutation tetap melalui `PersonaliaService`, owner kembali divalidasi pada Service, dan aktivitas dicatat ke `log_activity`.

# 7. Master Personalia

Admin/Operator dengan permission manage Master dapat membantu mengelola Personalia dari:

```text
/master/guru/personalia/{guru_id}
/master/pegawai/personalia/{pegawai_id}
```

Pimpinan/actor readonly yang hanya mempunyai permission view dapat melihat riwayat dan Portofolio tetapi tidak dapat melakukan mutation.

# 8. Dokumen Personalia

Dokumen mentah disimpan non-public di:

```text
WRITEPATH/uploads/personalia/{guru|pegawai}/{id}/...
```

Bukan pada public web root.

Format upload:

```text
PDF
PNG
JPG/JPEG
```

Maksimum 5 MB.

Jenis dokumen umum yang diterima Service:

```text
KTP / KK
SK Pengangkatan Awal
Kartu / Bukti NUPTK
Sertifikat Pendidik
Kartu Pegawai
Lainnya
```

Phase 3.1 mengunci whitelist tersebut pada backend; request manual tidak dapat menyimpan kategori di luar daftar.

Secure file delivery:

```text
GET /personalia/file/{category}/{record_id}/{field}
```

Raw document hanya dapat dibuka oleh:

```text
pemilik identity sendiri
ATAU
actor dengan master_guru.manage / master_pegawai.manage
```

Readonly Master tidak mendapat raw document.

Phase 3.1 juga mengunci storage path ke prefix identity yang tepat. Record Guru A tidak dapat diarahkan ke folder Guru B/Pegawai lain melalui perubahan path database.

# 9. Portofolio

Portofolio Guru/Pegawai adalah PDF A4 yang dibangkitkan dari data terbaru, bukan snapshot tabel tersendiri.

Isi utama:

```text
Header identitas + foto
Data Identitas Pribadi
Riwayat Pendidikan Formal
Riwayat Penugasan & Jabatan
Riwayat Kepangkatan
```

NIP hanya ditampilkan bila tersedia. Untuk Pegawai tanpa riwayat penugasan, `pegawai.jabatan` legacy dapat dipakai sebagai fallback display sampai riwayat yang benar diisi.

Raw document tidak dimasukkan ke Portofolio.

# 10. Credential Boundary

Profile/Personalia tidak mengubah secara mandiri:

```text
username
password
role
secondary role
status account
auth_version administratif
```

Perubahan login identifier akibat NIP/NIK tetap menjadi tanggung jawab Master Guru/Pegawai dan Service account sync.

# 11. IDOR & Authorization

Dilarang mempercayai target dari parameter bebas tanpa owner check.

Actor Web berasal dari session. Actor API berasal dari token untuk endpoint yang memang tersedia.

`PermissionFilter` adalah route gate. `PersonaliaService` tetap menjadi boundary authorization/data scope.

# 12. Checkpoint Wajib

- Guru hanya dapat mengelola riwayat identity sendiri;
- Pegawai hanya dapat mengelola riwayat identity sendiri;
- Admin/Operator manage dapat membantu identity target;
- Pimpinan readonly tidak dapat mutation;
- Pimpinan readonly tidak dapat raw document;
- path beda owner ditolak;
- path traversal ditolak;
- jenis dokumen di luar whitelist ditolak;
- file >5 MB ditolak;
- executable/format selain PDF/PNG/JPG ditolak;
- self-delete hanya menghapus record miliknya sendiri;
- Portofolio dapat dibuat dan dibuka;
- CSRF aktif untuk mutation Web;
- tidak ada 404/500.
