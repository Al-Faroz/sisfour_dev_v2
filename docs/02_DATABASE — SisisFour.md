# Database — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Application baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + G3.3.1 branch pending merge  
**Database state:** localhost + hosting telah menerima delta G3.2 dan G3.3.1; hosting smoke UAT G3.3.1 PASS

> Database adalah sumber integritas persistence. Exact DDL runtime tetap harus diverifikasi dari schema live/dump aktual dan SQL final di `database/`; dokumen ini menyatakan kontrak schema/business yang berlaku.

## 1. Prinsip

- Business rule tambahan tetap dijaga Service.
- Perubahan schema memakai SQL eksplisit di `database/`, bukan CodeIgniter migration.
- SQL localhost diuji lebih dulu.
- SQL hosting baru disusun setelah dump hosting aktual diaudit.
- Jangan mengandalkan nilai `AUTO_INCREMENT` sebagai kontrak bisnis.
- Permission/menu ID tidak boleh di-hardcode bila environment dapat berbeda.

## 2. Tabel Canonical Saat Ini

Setelah G3.2 + G3.3.1, kontrak schema memuat **34 tabel**:

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

Pada import environment baru, runtime auth state boleh dibersihkan tanpa menyentuh business data:

```sql
TRUNCATE TABLE ci_sessions;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE login_attempts;
```

## 4. Users, Role, dan Identity

### `users`

- `username` unique;
- primary role nullable;
- role resmi: `admin`, `operator`, `pimpinan`, `bk`, `guru`, `siswa`;
- `id_guru`, `id_pegawai`, `id_siswa` masing-masing unique;
- `auth_version` dipakai invalidation credential/security state.

### `user_roles`

Secondary role. Effective role:

```text
users.role UNION user_roles.role
```

### Identity Guru/Pegawai/Siswa

```text
Guru     -> users.id_guru
Pegawai  -> users.id_pegawai
Siswa    -> users.id_siswa
```

Tidak ada role `pegawai`.

Dump localhost dan hosting G3.3.1 mengonfirmasi akun primary-role BK aktual memakai:

```text
users.id_pegawai -> pegawai.id
users.id_guru = NULL
```

Karena itu fitur BK tidak boleh mensyaratkan identity Guru kecuali field legacy/opsional secara eksplisit.

## 5. Tahun / Kelas / Membership

### `tahun_ajaran`

Hanya satu periode operasional aktif menurut Service.

### `kelas`

Terkait `tahun_ajaran`, soft delete mempertahankan histori.

### `anggota_kelas`

Kontrak current membership:

```text
UNIQUE(id_siswa, id_tahun)
```

### `riwayat_siswa`

Histori membership/lifecycle. Mutation current-state dan pembacaan histori tidak boleh mencampur membership periode yang salah.

## 6. Mapping Wali dan Jadwal

`mapping_wali_kelas` adalah sumber context Wali; Wali bukan role.

```text
1 Guru max 1 kelas aktif per tahun
1 Kelas max 1 Wali aktif per tahun
```

`jadwal_guru` mengikat Guru, Kelas, Mapel, Tahun, hari, jam, dan sesi:

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

## 7. Presensi Resmi

### `presensi`

```text
status: Hadir | Sakit | Izin | Alpha
sesi:   Sesi Awal | Sesi Akhir
```

Sesi Awal adalah sumber Matrix/EWS/Signage resmi sesuai kontrak Presensi.

## 8. Jurnal Mengajar — G3.2

### `presensi_mengajar`

Satu record per `id_jadwal + tanggal`.

Field G3.2:

```text
status
materi     wajib
catatan    TEXT NULL / optional
```

### `presensi_mengajar_siswa`

Child exception per pembelajaran:

```text
presensi_mengajar 1:N presensi_mengajar_siswa
status child = Sakit | Izin | Alpha
UNIQUE(id_presensi_mengajar, id_siswa)
```

Child adalah exception Jurnal, **bukan Presensi resmi**. Ia tidak menulis/mengubah `presensi`, tidak masuk Rekap/EWS/Signage resmi, dan hanya boleh ada ketika status Guru `Hadir`.

Exact DDL delta:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

G3.2 telah merged melalui PR #7 dan schema local/hosting telah divalidasi.

## 9. BK — Catatan Pelanggaran

Tabel fisik legacy tetap:

```text
ref_pelanggaran
catatan_kasus
tindak_lanjut_kasus
```

Business label canonical:

```text
Catatan Pelanggaran Siswa
```

`ref_pelanggaran.kategori` tetap:

```text
Ringan | Sedang | Berat
```

`ref_pelanggaran.poin` masih ada sebagai kolom legacy untuk rollback compatibility, tetapi sejak G3.3.1:

```text
poin bukan business rule
UI tidak menampilkan poin
export tidak membawa poin
dashboard/payload aktif tidak mengagregasi poin
create/update master menulis nilai legacy 0
Top Poin retired
```

`tindak_lanjut_kasus` tetap 1:N terhadap `catatan_kasus` dan actor dicatat melalui `id_user_input -> users.id`.

## 10. Konseling BK — G3.3.1

Tabel baru:

```text
konseling_bk
```

Field kontrak:

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

Identity audit utama adalah `created_by -> users.id`; `id_guru_bk` nullable/legacy metadata dan bukan syarat akun BK.

Workflow schema mendukung create Tahap 1 lalu update Tahap 2 pada record yang sama. G3.3.1 tidak membuat tabel histori Konseling 1:N dan tidak menyediakan delete workflow.

Exact SQL final:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

FIX1/FIX2 localhost adalah patch transisi dan tidak menjadi bagian branch final.

## 11. Prestasi

`catatan_prestasi` tetap menyimpan prestasi siswa. G3.3.1 tidak mengubah schema tabel Prestasi; perubahan ada pada controller/export. Export canonical menambahkan Kelas aktif siswa.

## 12. Kartu Pelajar

Field utama:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Generated/unique contract menjaga maksimum satu kartu Aktif per siswa.

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

Periode penugasan valid bila `tanggal_selesai IS NULL` atau `tanggal_selesai >= tanggal_mulai`.

## 14. Permission / RBAC Schema

Tabel:

```text
permissions
role_permissions
menus
role_menus
```

Sebelum G3.3.1 terdapat 43 permission key canonical. G3.3.1 menambah 4:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
bk_konseling.settings
```

Sehingga current canonical set = **47 permission key**.

Mapping final Konseling:

```text
view/manage/export -> admin, operator, bk / scope SEMUA
settings           -> admin, bk / scope SEMUA
```

Pimpinan, Guru/Wali, dan Siswa tidak memperoleh `bk_konseling.*`.

`menus.id` tidak diasumsikan AUTO_INCREMENT pada hosting; SQL G3.3.1 mengalokasikan menu baru berdasarkan state aktual dan tidak hardcode ID lintas environment.

## 15. `setting_sistem`

Key existing tetap mencakup branding, geofence, maintenance, dan background Kartu.

G3.3.1 menggunakan key tambahan:

```text
bk_konseling_form_options
```

Value disimpan sebagai JSON text pada `setting_value` dengan `type='string'` demi kompatibilitas model existing. Row boleh belum ada; Service memakai default aman sampai pengaturan pertama disimpan.

## 16. Data Integrity / Delete Dependency

Permanent delete harus ditolak ketika histori/dependency penting akan putus. Khusus delta terbaru:

- Siswa yang direferensikan `presensi_mengajar_siswa` atau `konseling_bk` tidak boleh dihapus secara permanen tanpa maintenance policy eksplisit.
- Kelas/Tahun yang direferensikan Konseling tidak boleh dihapus bila FK/history harus dipertahankan.
- Guru yang hanya direferensikan `konseling_bk.id_guru_bk` tetap dilindungi FK sampai metadata tersebut dilepas/set NULL secara sah.
- User actor pada Konseling/Tindak Lanjut menggunakan FK `ON DELETE SET NULL` untuk menjaga business history.

## 17. Schema Change Rule

- Jangan menambah kolom berdasarkan asumsi UI.
- Perubahan schema harus punya alasan business/integrity.
- SQL development dan hosting boleh berbeda bila state environment berbeda.
- Hosting SQL harus dibuat dari audit dump hosting aktual, bukan copy buta localhost patch.
- Gunakan schema-qualified SQL bila context phpMyAdmin dapat ambigu.
- Hindari ketergantungan `information_schema` bila user DB tidak memiliki akses.
- Verification query wajib menyertai delta penting.
- `docs/02_DATABASE` harus disinkronkan setelah delta lulus local/hosting gate.

## 18. Current Database Gate

Per 16 September 2026:

```text
G3.2 schema local       PASS
G3.2 schema hosting     PASS
G3.3.1 schema local     PASS
G3.3.1 schema hosting   PASS
G3.3.1 hosting smoke    PASS
```

PR #9 belum merge; source merge tetap menunggu approval eksplisit meskipun database hosting telah menerima delta yang telah diuji.