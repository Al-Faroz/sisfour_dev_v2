# 🚦 Routes Final — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini adalah referensi route final SisisFour berdasarkan arsitektur repository saat ini.

> **Catatan penting:** Wali Kelas bukan role. Route tidak boleh menggunakan `role:wali`. Status Wali ditentukan dinamis melalui `AuthService::isWaliKelas()` dan scope permission.

---

# 1. Prinsip Routing

- AutoRoute dinonaktifkan.
- Semua route ditulis eksplisit.
- Route protected menggunakan filter `auth` dan/atau `permission`.
- Permission didefinisikan di `Routes.php`.
- Scope ditentukan oleh `AuthService::resolveScope()`.
- Semua endpoint JSON wajib menggunakan authorization yang sama dengan endpoint HTML.
- Endpoint yang tidak memiliki permission harus mengembalikan HTTP **403 — Akses Ditolak**.
- Tidak boleh ada menu yang normalnya menghasilkan 403 ketika dibuka oleh user yang diberi menu.

---

# 2. Auth Web

| Method | Route | Controller | Filter |
| --- | --- | --- | --- |
| GET | `/` | `Auth::login` | public |
| GET | `/auth/login` | `Auth::login` | public |
| POST | `/auth/login` | `Auth::login` | public |
| POST | `/auth/logout` | `Auth::logout` | auth |

**Logout web wajib menggunakan POST.**

Tidak ada lagi route logout GET.

---

# 3. Dashboard

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/dashboard` | `dashboard.view` |
| GET | `/dashboard/data` | `dashboard.view` |

Dashboard memilih widget berdasarkan role + konteks Wali + permission.

---

# 4. Master Guru

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/guru` | `master_guru.view` / `master_guru.manage` |
| GET | `/master/guru/json` | `master_guru.view` / `master_guru.manage` |
| GET | `/master/guru/template` | `master_guru.manage` |
| POST | `/master/guru/create` | `master_guru.manage` |
| POST | `/master/guru/import` | `master_guru.manage` |
| GET | `/master/guru/export` | `master_guru.manage` |
| PUT | `/master/guru/update/(:segment)` | `master_guru.manage` |
| DELETE | `/master/guru/delete/(:segment)` | `master_guru.manage` |

Pimpinan hanya menggunakan permission view.

---

# 5. Master Pegawai

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/pegawai` | `master_pegawai.view` / `master_pegawai.manage` |
| GET | `/master/pegawai/json` | `master_pegawai.view` / `master_pegawai.manage` |
| GET | `/master/pegawai/template` | `master_pegawai.manage` |
| POST | `/master/pegawai/create` | `master_pegawai.manage` |
| POST | `/master/pegawai/import` | `master_pegawai.manage` |
| GET | `/master/pegawai/export` | `master_pegawai.manage` |
| PUT | `/master/pegawai/update/(:segment)` | `master_pegawai.manage` |
| DELETE | `/master/pegawai/delete/(:segment)` | `master_pegawai.manage` |

---

# 6. Master Siswa

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/siswa` | `master_siswa.view` |
| GET | `/master/siswa/json` | `master_siswa.view` |
| GET | `/master/siswa/template` | `master_siswa.import_export` |
| POST | `/master/siswa/create` | `master_siswa.manage` |
| POST | `/master/siswa/import` | `master_siswa.import_export` |
| GET | `/master/siswa/export` | `master_siswa.import_export` |
| PUT | `/master/siswa/update/(:segment)` | `master_siswa.edit_biodata` |
| DELETE | `/master/siswa/delete/(:segment)` | `master_siswa.manage` |
| POST | `/master/siswa/mutasi/(:segment)` | `master_siswa.manage` |

Untuk Wali Kelas:

```text
master_siswa.view
master_siswa.edit_biodata
```

hanya berlaku untuk kelas walinya.

NISN tidak boleh diubah oleh Wali.

---

# 7. Master Kelas

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/kelas` | `master_kelas.manage` |
| GET | `/master/kelas/json` | `master_kelas.manage` |
| POST | `/master/kelas/create` | `master_kelas.manage` |
| PUT | `/master/kelas/update/(:segment)` | `master_kelas.manage` |
| DELETE | `/master/kelas/delete/(:segment)` | `master_kelas.manage` |

---

# 8. Tahun Ajaran

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/tahun` | `master_tahun_ajaran.manage` |
| GET | `/master/tahun/json` | `master_tahun_ajaran.manage` |
| POST | `/master/tahun/create` | `master_tahun_ajaran.manage` |
| PUT | `/master/tahun/update/(:segment)` | `master_tahun_ajaran.manage` |
| DELETE | `/master/tahun/delete/(:segment)` | `master_tahun_ajaran.manage` |

---

# 9. Mata Pelajaran

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/mapel` | `master_mapel.manage` |
| GET | `/master/mapel/json` | `master_mapel.manage` |
| POST | `/master/mapel/create` | `master_mapel.manage` |
| PUT | `/master/mapel/update/(:segment)` | `master_mapel.manage` |
| DELETE | `/master/mapel/delete/(:segment)` | `master_mapel.manage` |

---

# 10. Mapping Wali Kelas

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/wali-kelas` | `mapping_wali.view` / `mapping_wali.manage` / `mapping_wali.view_all` |
| GET | `/master/wali-kelas/json` | sama |
| POST | `/master/wali-kelas/assign` | `mapping_wali.manage` |
| DELETE | `/master/wali-kelas/delete/(:segment)` | `mapping_wali.manage` |

`isWaliKelas()` harus dihitung dari mapping aktif.

---

# 11. Jadwal Guru

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/master/jadwal` | `jadwal_guru.view` / `jadwal_guru.view_all` / `jadwal_guru.manage` |
| GET | `/master/jadwal/json` | sama |
| GET | `/master/jadwal/template` | `jadwal_guru.manage` |
| POST | `/master/jadwal/import` | `jadwal_guru.manage` |
| DELETE | `/master/jadwal/delete/(:segment)` | `jadwal_guru.manage` |

---

# 12. Presensi Siswa

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/presensi/siswa` | `presensi_siswa.input` |
| GET | `/presensi/siswa/input/(:segment)` | `presensi_siswa.input` |
| POST | `/presensi/siswa/save` | `presensi_siswa.input` |
| GET | `/presensi/siswa/revisi/(:segment)` | `presensi_siswa.revisi` |
| POST | `/presensi/siswa/revisi/save` | `presensi_siswa.revisi` |
| GET | `/presensi/siswa/rekap` | `presensi_siswa.view` |
| GET | `/presensi/siswa/ews` | `ews_radar.view` |

### Aturan penting

Guru Biasa:

```text
input saja
```

Tidak boleh membuka hasil tersimpan.

Wali:

```text
input + view + revisi
```

tetapi hanya kelas walinya.

---

# 13. Presensi Mengajar / Jurnal

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/presensi/mengajar` | `presensi_mengajar.input` |
| GET | `/presensi/mengajar/input/(:segment)` | `presensi_mengajar.input` |
| POST | `/presensi/mengajar/save` | `presensi_mengajar.input` |
| GET | `/presensi/mengajar/laporan` | `presensi_mengajar.view` |

Revisi jurnal:

```text
Admin/Operator
```

harus dilakukan melalui permission/route administratif yang secara eksplisit disediakan aplikasi.

Guru/Wali tidak dapat merevisi jurnalnya sendiri.

---

# 14. Laporan Matrix

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/laporan/presensi/matrix` | `laporan_matrix.view` |
| GET | `/laporan/presensi/matrix/json` | `laporan_matrix.view` |

Scope:

```text
SEMUA
KELAS_DIAMPU
```

Guru Biasa tidak mempunyai permission ini.

---

# 15. Export Presensi

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/laporan/presensi/export` | `laporan_export.generate` |
| GET | `/laporan/presensi/export/bulan` | `laporan_export.generate` |
| GET | `/laporan/presensi/export/semester` | `laporan_export.generate` |

Wali dibatasi ke kelas walinya.

Export wajib memvalidasi view permission terkait.

---

# 16. Laporan Jurnal

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/laporan/jurnal` | `laporan_jurnal.view` |
| GET | `/laporan/jurnal/json` | `laporan_jurnal.view` |
| GET | `/laporan/jurnal/export` | `laporan_jurnal.export` |

Scope:

```text
SEMUA
DIRI_SENDIRI
```

---

# 17. BK Kasus

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/bk/kasus` | `bk_kasus.view` |
| GET | `/bk/kasus/json` | `bk_kasus.view` |
| GET | `/bk/kasus/top` | `bk_kasus.view` |
| POST | `/bk/kasus/create` | `bk_kasus.manage` |
| GET | `/bk/kasus/export` | `bk_kasus.manage` |

Guru Biasa tidak mempunyai permission BK.

Wali menggunakan:

```text
bk_kasus.view + KELAS_DIAMPU
```

dan readonly.

---

# 18. Master Pelanggaran

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/bk/pelanggaran` | `bk_pelanggaran_master.manage` |
| GET | `/bk/pelanggaran/json` | `bk_pelanggaran_master.manage` |
| POST | `/bk/pelanggaran/create` | `bk_pelanggaran_master.manage` |
| PUT | `/bk/pelanggaran/update/(:segment)` | `bk_pelanggaran_master.manage` |
| DELETE | `/bk/pelanggaran/delete/(:segment)` | `bk_pelanggaran_master.manage` |

---

# 19. Prestasi

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/bk/prestasi` | `prestasi.view` |
| GET | `/bk/prestasi/json` | `prestasi.view` |
| POST | `/bk/prestasi/create` | `prestasi.manage` |
| PUT | `/bk/prestasi/update/(:segment)` | `prestasi.manage` |
| DELETE | `/bk/prestasi/delete/(:segment)` | `prestasi.manage` |
| GET | `/bk/prestasi/export` | `prestasi.view` / `prestasi.manage` |

Scope:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
```

sesuai role.

---

# 20. Kartu Pelajar

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/kartu/daftar` | `kartu_pelajar.view` |
| GET | `/kartu/daftar/json` | `kartu_pelajar.view` |
| POST | `/kartu/generate` | `kartu_pelajar.manage` |
| GET | `/kartu/cetak/(:segment)` | `kartu_pelajar.manage` atau mekanisme cetak Wali yang sah |
| GET | `/kartu/preview/(:segment)` | `kartu_pelajar.view` |
| GET | `/kartu/preview/(:segment)/json` | `kartu_pelajar.view` |
| GET | `/kartu/download/(:segment)` | `kartu_pelajar.view` |
| POST | `/kartu/reissue/(:segment)` | `kartu_pelajar.manage` |

Wali:

```text
view + cetak
```

tetapi tidak:

```text
generate
reissue
manage
```

Pimpinan:

```text
view + manage
```

sesuai business rule final.

---

# 21. Verifikasi Kartu Publik

| Method | Route | Filter |
|---|---|---|
| GET | `/kartu/verify/(:segment)` | Public |

Data publik harus menggunakan NIK masking.

---

# 22. Profile Guru

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/profile/guru` | `profile_guru.view` |
| GET | `/profile/guru/json` | `profile_guru.view` |
| PUT | `/profile/guru/update` | `profile_guru.edit` |
| POST | `/profile/guru/upload-foto` | `profile_guru.edit` |

Scope:

```text
DIRI_SENDIRI
```

---

# 23. Profile Siswa

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/profile/siswa` | `profile_siswa.view` |
| GET | `/profile/siswa/json` | `profile_siswa.view` |

Tidak ada route update profile siswa.

---

# 24. Settings

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/settings/user` | `settings_user.manage` |
| GET | `/settings/user/json` | `settings_user.manage` |
| POST | `/settings/user/create` | `settings_user.manage` |
| PUT | `/settings/user/update/(:segment)` | `settings_user.manage` |
| DELETE | `/settings/user/delete/(:segment)` | `settings_user.manage` |
| GET | `/settings/menu` | `settings_menu.manage` |
| GET | `/settings/menu/json` | `settings_menu.manage` |
| PUT | `/settings/menu/update/(:segment)` | `settings_menu.manage` |
| GET | `/settings/sistem` | `settings_sistem.manage` |
| GET | `/settings/sistem/json` | `settings_sistem.manage` |
| PUT | `/settings/sistem/update` | `settings_sistem.manage` |

---

# 25. Backup

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/backup` | `backup.manage` |
| POST | `/backup/create` | `backup.manage` |
| GET | `/backup/download/(:segment)` | `backup.manage` |

---

# 26. Log Activity

| Method | Route | Permission |
| --- | --- | --- |
| GET | `/log/activity` | `log_activity.view` |
| GET | `/log/activity/json` | `log_activity.view` |

Permission hanya untuk:

```text
Admin
Operator
```

---

# 27. API Mobile

Semua endpoint API menggunakan:

```text
auth:api
```

dan permission yang sama dengan modul web.

Contoh utama:

```text
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
POST /api/auth/refresh

GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto

GET  /api/profile/siswa

GET  /api/dashboard
GET  /api/presensi/siswa
POST /api/presensi/siswa/save
GET  /api/presensi/mengajar
POST /api/presensi/mengajar/save
```

API tidak boleh memiliki aturan akses yang lebih longgar daripada web.

---

# 28. HTTP 403

Jika permission tidak terpenuhi:

```text
HTTP 403
```

Halaman:

```text
Akses Ditolak
```

Untuk API:

```json
{
  "status": "error",
  "message": "Akses Ditolak",
  "code": 403
}
```

---

# 29. Duplikasi Route

Tidak boleh ada dua deklarasi route yang sama untuk:

```text
/dashboard
/dashboard/data
```

Setiap route hanya didefinisikan satu kali.

---

# 30. Logout

Route final:

```text
POST /auth/logout
```

Tidak menggunakan:

```text
GET /auth/logout
```

Frontend harus melakukan submit POST, termasuk CSRF token apabila CSRF global sudah diaktifkan.

---

# 31. Permission Registry

Permission yang digunakan seluruh route:

```text
1  dashboard.view
2  presensi_siswa.input
3  presensi_siswa.revisi
4  presensi_siswa.view
5  presensi_mengajar.input
6  presensi_mengajar.view
7  master_guru.manage
8  master_guru.view
9  master_pegawai.manage
10 master_pegawai.view
11 master_siswa.view
12 master_siswa.edit_biodata
13 master_siswa.manage
14 master_siswa.import_export
15 master_kelas.manage
16 master_tahun_ajaran.manage
17 master_mapel.manage
18 mapping_wali.manage
19 mapping_wali.view
20 mapping_wali.view_all
21 jadwal_guru.manage
22 jadwal_guru.view
23 jadwal_guru.view_all
24 laporan_matrix.view
25 laporan_export.generate
26 laporan_jurnal.view
27 laporan_jurnal.export
28 ews_radar.view
29 bk_kasus.manage
30 bk_kasus.view
31 bk_pelanggaran_master.manage
32 prestasi.manage
33 prestasi.view
34 kartu_pelajar.manage
35 kartu_pelajar.view
36 settings_user.manage
37 settings_menu.manage
38 settings_sistem.manage
39 backup.manage
40 log_activity.view
41 profile_guru.view
42 profile_guru.edit
43 profile_siswa.view
```

---

# 32. Catatan Developer Final

1. Jangan membuat route khusus `wali` hanya untuk membedakan Wali Kelas.
2. Wali adalah konteks dinamis.
3. PermissionFilter memeriksa permission.
4. Service/Model wajib menerapkan scope.
5. MenuService wajib menghasilkan menu yang usable.
6. Guru Biasa tidak boleh mendapatkan endpoint laporan presensi.
7. Guru Biasa tidak boleh membaca hasil presensi siswa yang sudah tersimpan.
8. Wali dapat mengakses fungsi Wali hanya saat mapping wali aktif.
9. Pimpinan readonly kecuali kewenangan Kartu Pelajar yang memang diberikan.
10. Admin/Operator mempunyai akses administratif sesuai permission.
11. Semua API mengikuti aturan yang sama dengan web.
12. Semua perubahan route harus memperbarui dokumen ini.

---

© 2026 SisisFour · MTsN 4 Jombang · Routes Final
