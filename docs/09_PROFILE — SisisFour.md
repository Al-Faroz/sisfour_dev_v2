# Profile, Personalia & Portofolio — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Profile bekerja pada identity actor. Dokumen ini tidak mendefinisikan role baru `pegawai`.

## 1. Prinsip Self Identity

Target Profile berasal dari relasi authenticated user:

```text
users.id_guru
users.id_pegawai
users.id_siswa
```

Client tidak boleh memilih target Profile bebas. Service/controller selalu me-resolve identity actor.

## 2. Profile Guru

Web:

```text
GET  /profile/guru
GET  /profile/guru/json
PUT  /profile/guru/update
POST /profile/guru/upload-foto
```

Permission:

```text
profile_guru.view
profile_guru.edit
```

Profile Guru hanya valid bila user mempunyai `id_guru`.

Credential tidak diubah dari Profile. Sinkronisasi username/managed password akibat perubahan NIP/NIK dilakukan oleh Master Guru/Service berwenang.

Foto public:

```text
uploads/foto_guru/
```

## 3. Profile Pegawai

Web:

```text
GET  /profile/pegawai
GET  /profile/pegawai/json
PUT  /profile/pegawai/update
POST /profile/pegawai/upload-foto
```

Profile Pegawai berbasis `users.id_pegawai`, bukan role `pegawai` dan bukan permission `profile_pegawai.*`.

AuthFilter memastikan login; ProfileService memastikan self identity.

Foto public:

```text
uploads/foto_pegawai/
```

Akun Pegawai yang belum mempunyai role operasional tetap dapat diarahkan ke Profile Pegawai sebagai self-service identity.

## 4. Profile Siswa

Web:

```text
GET /profile/siswa
GET /profile/siswa/json
```

Permission:

```text
profile_siswa.view
```

Target selalu `users.id_siswa`. Profile Siswa bersifat readonly untuk biodata inti pada baseline.

## 5. API Profile

Protected `auth:api`:

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto

GET  /api/profile/pegawai
PUT  /api/profile/pegawai
POST /api/profile/pegawai/foto

GET  /api/profile/siswa
```

Actor API berasal dari RequestContext canonical `api_user` yang disiapkan AuthFilter.

## 6. Personalia Self-Service

Kategori:

```text
pendidikan
penugasan
pangkat
dokumen
```

Guru:

```text
GET    /profile/guru/personalia
POST   /profile/guru/personalia/save/{category}
DELETE /profile/guru/personalia/delete/{category}/{record_id}
GET    /profile/guru/portofolio
```

Pegawai:

```text
GET    /profile/pegawai/personalia
POST   /profile/pegawai/personalia/save/{category}
DELETE /profile/pegawai/personalia/delete/{category}/{record_id}
GET    /profile/pegawai/portofolio
```

Self-service hanya boleh mengubah record milik identity sendiri.

## 7. Personalia dari Master

Actor dengan permission Master yang sesuai dapat melihat/mengelola target:

```text
/master/guru/personalia/{guru_id}
/master/guru/portofolio/{guru_id}
/master/pegawai/personalia/{pegawai_id}
/master/pegawai/portofolio/{pegawai_id}
```

Manage dan readonly dibedakan permission. PermissionFilter hanya route gate; owner/target tetap divalidasi Service.

## 8. Dokumen Personalia Non-Public

Storage:

```text
writable/uploads/personalia/guru/{id}/...
writable/uploads/personalia/pegawai/{id}/...
```

Format yang diterima:

```text
PDF
PNG
JPG/JPEG
```

Batas ukuran:

```text
5 MB
```

Raw file tidak dapat diakses sebagai static URL. Delivery:

```text
GET /personalia/file/{category}/{record_id}/{field}
```

Akses raw document:

```text
owner self
ATAU
actor dengan permission Master manage yang relevan
```

Readonly Master tidak otomatis mendapat raw document.

Path harus tetap berada pada prefix storage identity yang tepat; traversal/absolute path/owner silang ditolak.

## 9. Portofolio

Portofolio Guru/Pegawai adalah PDF A4 yang dibentuk dari data terbaru, bukan snapshot database terpisah.

Isi utama:

```text
Identitas + foto
Pendidikan
Penugasan/Jabatan
Kepangkatan
```

Raw document tidak disisipkan ke PDF.

## 10. Credential Boundary

Profile/Personalia tidak mengubah langsung:

```text
username
password
primary role
secondary role
status account
auth_version administratif
```

Perubahan tersebut menjadi tanggung jawab Master/User Management Service yang berwenang.

## 11. Mobile/Responsive

Tab Personalia pada viewport sempit boleh horizontal-scroll. UI readonly tidak menampilkan mutation yang tidak bisa digunakan. Pinch zoom tidak boleh dinonaktifkan.

## 12. Checkpoint

- Guru hanya identity sendiri.
- Pegawai hanya identity sendiri.
- Siswa readonly diri sendiri.
- Admin/Operator manage target sesuai permission.
- Pimpinan/readonly tidak mutation.
- Raw document boundary.
- Path traversal ditolak.
- Invalid upload ditolak.
- Portofolio PDF dapat dibuat.
- Credential boundary terjaga.
- Web/API actor konsisten.
