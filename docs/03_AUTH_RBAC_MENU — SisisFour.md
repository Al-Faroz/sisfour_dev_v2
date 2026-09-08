# Authentication, RBAC & Menu — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Web Authentication

Session Web disimpan di database.

Session minimum:

```text
user_id
role
username
id_guru
id_pegawai
id_siswa
auth_version
logged_in
```

`session.role` hanya primary role dan boleh NULL.

# 2. Login

Syarat:

- username tidak kosong;
- password tidak kosong;
- user aktif;
- password valid.

Setiap login berhasil:

- mencatat login attempt berhasil;
- menaikkan `auth_version`;
- meregenerasi session;
- menyimpan session actor;
- mencatat event LOGIN.

Lima kegagalan login berturut-turut menyebabkan lock 5 menit.

# 3. API Authentication

```text
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
POST /api/auth/refresh
GET  /api/version
```

Token disimpan di `api_tokens`. Logout merevoke access token.

# 4. Role

```text
admin
operator
pimpinan
bk
guru
siswa
```

`wali_kelas` bukan role.

# 5. Multi-Role

```text
effective_roles =
users.role
UNION
user_roles.role
```

Permission user adalah union seluruh effective role.

# 6. Scope

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
DIRI_SENDIRI
TIDAK_ADA
```

Scalar priority:

1. SEMUA;
2. KELAS_DIAMPU bila benar-benar Wali aktif;
3. KELAS_TERJADWAL;
4. DIRI_SENDIRI;
5. TIDAK_ADA.

Service Presensi dapat membaca seluruh scope mentah untuk dual-context.

# 7. Wali

Wali aktif di-resolve dari:

```text
users.id_guru
→ mapping_wali_kelas
→ tahun aktif
→ deleted_at IS NULL
```

Saat mapping nonaktif, scope `KELAS_DIAMPU` hilang.

# 8. Karakter Role

| Role | Karakter |
|---|---|
| Admin | seluruh permission baseline |
| Operator | administrasi operasional + laporan + BK + kartu + Log |
| Pimpinan | supervisi readonly, Jurnal diri bila valid |
| BK | EWS + BK + Prestasi |
| Guru | Jadwal/Jurnal/Presensi + contextual Wali |
| Siswa | data diri |

# 9. Permission

Database memiliki 43 permission. Matrix lengkap ada di `02_DATABASE`.

# 10. PermissionFilter

Route protected memakai:

```text
auth
permission:permission_key
```

Beberapa route memakai OR permission.

PermissionFilter hanya gate route. Service tetap memvalidasi target.

# 11. Menu

Menu berasal dari:

```text
menus
role_menus
permission
contextual Wali
```

Sidebar adalah renderer.

Menu bukan security boundary.

# 12. Contextual Wali

Menu yang hanya berguna untuk Wali dapat diberikan statis kepada role Guru tetapi hanya tampil/usable ketika scope Wali valid.

Contoh:

- Data Siswa;
- Matrix;
- Export;
- EWS;
- Kasus readonly;
- Prestasi;
- Kartu.

# 13. Operator dan Log Activity

Baseline:

```text
Operator mempunyai log_activity.view = SEMUA
```

tetapi tidak memiliki `role_menus` Log Activity. Direct URL tetap authorized. Keputusan sidebar diselesaikan pada Testing & Polish.

# 14. Login Branding

GET login membaca:

```text
nama_sekolah
logo_sekolah
icon_sekolah
```

Login menampilkan `SisFour Dev` dan logo upload Setting Sistem.

# 15. Maintenance

Saat Maintenance ON:

- login page tetap terbuka;
- effective Admin dapat login;
- non-Admin diblokir 503;
- API mendapatkan JSON 503;
- logout tetap dapat dipakai.

# 16. Security

- browser tidak menentukan effective role;
- browser tidak menentukan effective scope;
- actor dari session/token;
- target diverifikasi Service;
- direct URL tetap diperiksa;
- mutation Web CSRF protected;
- credential tidak dicatat ke Log Activity.

# 17. Uji Role Minimum

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
Guru + Operator
Guru + Pimpinan
```
