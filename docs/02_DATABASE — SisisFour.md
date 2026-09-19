# Database — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Application baseline:** `main` @ `90acc7f94fee391a5a7fbad2395e3f16571fe921` + G3.6B feature branch
**Database state:** G3.6A local+hosting CLOSED; G3.6B localhost SQL **prepared / execution pending**

> Database adalah sumber integritas persistence. Exact DDL runtime tetap harus diverifikasi dari schema live/dump aktual dan SQL final di `database/`; dokumen ini menyatakan kontrak schema/business yang berlaku.

## 1. Prinsip

- Business rule tambahan tetap dijaga Service.
- Perubahan schema memakai SQL eksplisit di `database/`, bukan CodeIgniter migration.
- SQL localhost diuji lebih dulu.
- SQL hosting baru disusun setelah dump hosting aktual diaudit.
- Jangan mengandalkan nilai `AUTO_INCREMENT` sebagai kontrak bisnis.
- Permission/menu ID tidak boleh di-hardcode bila environment dapat berbeda.
- Data periodik/historis harus memiliki snapshot period yang eksplisit bila filtering historis dibutuhkan; jangan menebak Tahun Ajaran dari current membership.

## 2. Tabel Canonical Target G3.3.1 Rework

Setelah rework 17 September, target schema memuat **35 tabel**. Tambahan terhadap baseline sebelumnya adalah `tindak_lanjut_konseling_bk`.

```text
anggota_kelas
api_tokens
catatan_kasus
catatan_prestasi
ci_sessions
dokumen_personalia
guru
jadwal_guru
kartu_pelajar
kelas
konseling_bk
login_attempts
log_activity
mapping_wali_kelas
mata_pelajaran
menus
pegawai
permissions
presensi
presensi_mengajar
presensi_mengajar_siswa
ref_pelanggaran
riwayat_pangkat
riwayat_pendidikan
riwayat_penugasan
riwayat_siswa
role_menus
role_permissions
setting_sistem
siswa
tahun_ajaran
tindak_lanjut_kasus
tindak_lanjut_konseling_bk
users
user_roles
```

## 3. Runtime / Authentication Tables

```text
ci_sessions
api_tokens
login_attempts
```

`ci_sessions` dipakai Web DatabaseHandler. `api_tokens` menyimpan access/refresh token, expiry, device, dan revoke state. `login_attempts` dipakai lockout/rate-limit.

## 4. Users, Role, dan Identity

Role resmi:

```text
admin
operator
pimpinan
bk
kesehatan
guru
siswa
```

Effective role:

```text
users.role UNION user_roles.role
```

Identity:

```text
Guru       -> users.id_guru
Siswa      -> users.id_siswa
BK         -> users.id_pegawai
Kesehatan  -> users.id_pegawai
Pegawai    -> users.id_pegawai
```

Tidak ada role `pegawai`.

Akun primary-role BK aktual memakai:

```text
users.id_pegawai -> pegawai.id
users.id_guru = NULL
```

Fitur BK tidak boleh mensyaratkan identity Guru kecuali field legacy/opsional secara eksplisit.

## 5. Tahun / Kelas / Membership

### `tahun_ajaran`

Hanya satu periode operasional aktif menurut Service.

### `kelas`

Terkait `tahun_ajaran`; soft delete mempertahankan histori.

### `anggota_kelas`

Kontrak current membership:

```text
UNIQUE(id_siswa, id_tahun)
```

Mutation current-state dan pembacaan histori tidak boleh mencampur membership periode yang salah.

## 6. Mapping Wali dan Jadwal

`mapping_wali_kelas` adalah sumber context Wali; Wali bukan role.

```text
1 Guru max 1 kelas aktif per tahun
1 Kelas max 1 Wali aktif per tahun
```

`jadwal_guru` mengikat Guru, Kelas, Mapel, Tahun, hari, jam, dan sesi.

## 7. Presensi Resmi dan Jurnal

`presensi.sesi = Sesi Awal` adalah sumber Matrix/EWS/Signage resmi sesuai kontrak Presensi.

`presensi_mengajar_siswa` adalah child exception Jurnal, bukan Presensi resmi.

Schema G3.2 tetap:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

G3.2 telah merged melalui PR #7 dan local/hosting telah divalidasi.

## 8. BK — Catatan Pelanggaran

Tabel:

```text
ref_pelanggaran
catatan_kasus
tindak_lanjut_kasus
```

Business label canonical: `Catatan Pelanggaran Siswa`.

`ref_pelanggaran.poin` tetap legacy rollback compatibility tetapi bukan business rule. UI/export/dashboard aktif tidak memakai poin dan create/update master menulis `0`.

### Snapshot Tahun Ajaran

Rework 17 September menambah:

```text
catatan_kasus.id_tahun INT UNSIGNED NULL
FK -> tahun_ajaran.id
index (id_tahun, tanggal)
```

Record baru wajib diisi server dengan Tahun Ajaran aktif. Nullable dipertahankan untuk legacy/backfill karena period histori lama tidak boleh ditebak.

Backfill hanya dilakukan bila histori membership siswa menunjuk **tepat satu** Tahun Ajaran. Record ambigu tetap `NULL` dan muncul pada verification count.

`tindak_lanjut_kasus` tetap 1:N terhadap `catatan_kasus`.

## 9. Konseling BK — Parent

Tabel:

```text
konseling_bk
```

Field kontrak parent:

```text
id                  INT UNSIGNED PK AUTO_INCREMENT
id_tahun            INT UNSIGNED NOT NULL
id_kelas            INT UNSIGNED NOT NULL
id_siswa            INT UNSIGNED NOT NULL
tanggal              DATE NOT NULL
pertemuan_ke         SMALLINT UNSIGNED NOT NULL DEFAULT 1
bentuk_layanan       VARCHAR(50) NOT NULL
cara_hadir           VARCHAR(80) NOT NULL
bidang               ENUM('Pribadi','Sosial','Belajar','Karier') NOT NULL
topik                VARCHAR(150) NOT NULL
uraian_masalah       TEXT NULL
hasil_kesepakatan    TEXT NULL
rencana_berikutnya   VARCHAR(100) NULL
tanggal_berikutnya   DATE NULL
status               ENUM('Proses','Selesai') NOT NULL DEFAULT 'Proses'
id_guru_bk           INT UNSIGNED NULL
created_by           INT UNSIGNED NULL
created_at           DATETIME NULL
updated_at           DATETIME NULL
updated_by           INT UNSIGNED NULL
```

FK utama:

```text
id_tahun   -> tahun_ajaran.id
id_kelas   -> kelas.id
id_siswa   -> siswa.id
id_guru_bk -> guru.id ON DELETE SET NULL
created_by -> users.id ON DELETE SET NULL
updated_by -> users.id ON DELETE SET NULL
```

Parent menyimpan Tahap 1 dan hasil pertemuan awal/Tahap 2.

## 10. Tindak Lanjut Konseling BK — 1:N

Keputusan 17 September 2026 menambah tabel:

```text
tindak_lanjut_konseling_bk
```

Field:

```text
id                  INT UNSIGNED PK AUTO_INCREMENT
id_konseling         INT UNSIGNED NOT NULL
tanggal              DATE NOT NULL
perkembangan         TEXT NOT NULL
hasil_kesepakatan    TEXT NULL
rencana_berikutnya   VARCHAR(100) NULL
tanggal_berikutnya   DATE NULL
status               ENUM('Proses','Selesai') NOT NULL DEFAULT 'Proses'
created_by           INT UNSIGNED NULL
created_at           DATETIME NULL
updated_by           INT UNSIGNED NULL
updated_at           DATETIME NULL
```

Relasi/FK:

```text
id_konseling -> konseling_bk.id ON DELETE RESTRICT ON UPDATE CASCADE
created_by    -> users.id ON DELETE SET NULL
updated_by    -> users.id ON DELETE SET NULL
```

Indexes canonical:

```text
(id_konseling, tanggal, id)
(status, tanggal_berikutnya)
created_by
updated_by
```

Tidak ada delete workflow aplikasi untuk parent Konseling maupun follow-up. Histori tidak boleh terhapus lewat cascade; FK parent menggunakan `ON DELETE RESTRICT`.

Status parent disinkronkan ke status follow-up terbaru setelah histori follow-up ada.

## 11. Prestasi

`catatan_prestasi` menyimpan prestasi siswa.

Rework 17 September menambah:

```text
catatan_prestasi.id_tahun INT UNSIGNED NULL
FK -> tahun_ajaran.id
index (id_tahun, tanggal)
```

Record baru snapshot Tahun Ajaran aktif di Service. Legacy ambiguous tetap `NULL`; tidak boleh dipetakan dengan tebakan.

## 12. Kartu Pelajar

Field utama:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Database menjaga maksimum satu kartu Aktif per siswa.

## 13. Personalia

```text
riwayat_pendidikan
riwayat_penugasan
riwayat_pangkat
dokumen_personalia
```

Owner:

```text
id_guru XOR id_pegawai
```

## 14. Permission / RBAC Schema

Tabel:

```text
permissions
role_permissions
menus
role_menus
```

Permission Konseling:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
bk_konseling.settings
```

Mapping:

```text
view/manage/export -> admin, operator, bk / SEMUA
settings           -> admin, bk / SEMUA
```

Pimpinan, Guru/Wali, dan Siswa tidak memperoleh `bk_konseling.*`.

## 15. `setting_sistem`

Konseling menggunakan:

```text
setting_key = bk_konseling_form_options
```

Value JSON text pada `setting_value`, `type='string'`. Row boleh belum ada; Service memakai default aman.

## 16. SQL G3.3.1

Baseline yang sudah diuji:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Rework localhost 17 September:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
```

Delta:

```text
+ catatan_kasus.id_tahun
+ catatan_prestasi.id_tahun
+ tindak_lanjut_konseling_bk
```

Tidak ada SQL hosting untuk rework 17 September sebelum localhost PASS dan dump hosting aktual diaudit ulang.

## 17. Data Integrity / Delete Dependency

- Permanent delete tidak boleh memutus histori penting.
- `tindak_lanjut_konseling_bk -> konseling_bk` menggunakan RESTRICT.
- actor User pada Konseling/follow-up memakai `ON DELETE SET NULL` agar business history tetap ada.
- Kelas/Tahun yang direferensikan record periodik tidak boleh dihapus bila FK/history harus dipertahankan.
- Record legacy dengan `id_tahun NULL` harus diaudit; jangan diam-diam dianggap aktif.

## 18. G3.6A — UKS / Kesehatan Schema

G3.6A menambah role `kesehatan` ke enum role runtime pada `users`, `user_roles`, `role_permissions`, dan `role_menus`. Role `ptsp` tetap milik G3.6B dan belum ditambahkan oleh SQL G3.6A.

Target business schema menambah enam tabel:

```text
uks_ckg
uks_kunjungan
uks_kunjungan_tindakan
uks_ref_keluhan
uks_ref_tindakan
uks_ref_hasil
```

Dengan enam tabel tersebut, canonical application tables menjadi **41 tabel**; local dump boleh mempunyai tabel framework `migrations` tambahan.

`uks_ckg` menyimpan snapshot `id_tahun`, `id_kelas`, `id_siswa`, tanggal, field CKG, actor audit, timestamp, dan `deleted_at`. NISN dipakai untuk resolve siswa pada import; duplicate business key record aktif adalah siswa + tanggal.

`uks_kunjungan` menyimpan satu parent kunjungan. Tindakan multi-pilih dinormalisasi pada `uks_kunjungan_tindakan`. Petugas memakai `id_petugas_user -> users.id`.

Master `uks_ref_keluhan`, `uks_ref_tindakan`, dan `uks_ref_hasil` memakai `status_aktif` untuk deactivate/reactivate normal agar ID referensi histori stabil.

SQL localhost:

```text
database/20260918_G3_6A_UKS_KESEHATAN_LOCALHOST.sql
```

SQL hosting belum dibuat dan hanya boleh disusun setelah localhost SQL + runtime UAT + local dump audit PASS serta dump hosting aktual diaudit ulang.

## 19. Schema Change Rule

- Jangan menambah kolom berdasarkan asumsi UI.
- Perubahan schema harus punya alasan business/integrity.
- SQL development dan hosting boleh berbeda sesuai state environment.
- Hosting SQL dibuat dari audit dump hosting aktual, bukan copy localhost.
- Gunakan schema-qualified SQL bila context phpMyAdmin ambigu.
- Hindari `information_schema` pada environment user yang tidak punya akses.
- Verification query wajib.

## 20. Current Database Gate

```text
G3.2 schema local/hosting                    PASS
G3.3.1 final schema local/hosting            PASS / CLOSED
G3.6A local/hosting schema                   PASS / CLOSED — PR #13
G3.6A merge/main                             90acc7f94fee391a5a7fbad2395e3f16571fe921

G3.6B localhost SQL                          PASS / user evidence
G3.6B local runtime/UAT                      PASS / user runtime evidence
G3.6B post-UAT local dump audit              PASS / read-only dump audit
G3.6B fresh hosting dump audit               PASS / read-only dump audit
G3.6B hosting delta SQL                      PREPARED / static audited
G3.6B hosting SQL execution                  PASS / user evidence
G3.6B post-SQL hosting dump audit            PENDING
```

Hosting G3.6B belum boleh diturunkan dari localhost atau dump lama. Hosting delta hanya disusun setelah local gate PASS dan fresh hosting dump aktual diaudit.

## 21. G3.6B — PTSP Schema

G3.6B menambah role `ptsp` pada enum `users.role`, `user_roles.role`, `role_permissions.role`, dan `role_menus.role`.

Tabel baru:

```text
ptsp_layanan
ptsp_polling
ptsp_pengaduan
ptsp_pengaduan_klasifikasi
```

Invariant:

- seluruh submission snapshot `id_tahun` aktif;
- PTSP memakai hard delete sesuai contract, sehingga tidak menambah `deleted_at`;
- Pengaduan multi-klasifikasi memakai junction PK `(id_pengaduan, klasifikasi)`;
- junction Pengaduan cascade saat parent di-hard-delete;
- lampiran hanya menyimpan path private relatif + nama asli + MIME; bytes berada di `WRITEPATH/uploads/ptsp/pengaduan/`;
- public stats membaca agregat saja dan tidak membutuhkan tabel/materialized view terpisah.

SQL artifacts:

```text
database/20260919_G3_6B_PTSP_LOCALHOST.sql
database/20260919_G3_6B_PTSP_HOSTING.sql
```

Fresh pre-SQL hosting baseline:

```text
tables             41
permissions        55
role_permissions   183
menus              46
role_menus         156
ptsp_* tables      0
ptsp permissions   0
ptsp menus         0
```

Expected post-hosting-SQL state:

```text
tables             45
permissions        66
role_permissions   223
menus              50
role_menus         173
```

Hosting execution tetap memerlukan approval eksplisit.
