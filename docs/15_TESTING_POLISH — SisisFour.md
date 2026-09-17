# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Phase aktif:** G3.3.1 closure patch — **focused local runtime PASS / static + hosting re-smoke pending**

> Quality gate dibagi per phase agar regression bisnis, mobile UI, schema delta, privacy, hosting, dan Cordova tidak bercampur. Merge/release tetap memerlukan approval eksplisit pengguna.

## 1. Static Gate Umum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

Tidak boleh ada syntax error, route target hilang, file tidak sengaja terhapus, atau whitespace conflict. Perubahan permission/schema harus dilengkapi audit SQL/runtime boundary.

## 2. G2 — CLOSED / MERGED

```text
PR #5          MERGED
merge commit   375766c07f3856515a71ffdb07f3681c3047ca31
```

F06–F14 tidak dibuka ulang pada G3 tanpa regression/blocker nyata.

## 3. G3 Global Mobile Gate

Role prioritas:

```text
Pimpinan
BK
Guru
Guru + Wali
Siswa
```

Viewport wajib:

```text
360×800
375×812
390×844
412×915
768×1024
1024×768
1366×768
```

Acceptance global:

- no body horizontal overflow;
- no horizontal table scroll role operasional;
- Nama sebagai primary identity;
- identifier sekunder;
- KPI 2×2 mobile bila ada KPI;
- touch target utama 44–48px;
- modal/keyboard nyaman;
- filter compact;
- mutation busy guard;
- network failure mempertahankan input penting;
- server-confirmed success;
- no uncaught browser error;
- direct URL tidak menembus RBAC/Service.

## 4. G3.1 — Mobile Foundation — CLOSED / MERGED

```text
PR #6          MERGED
merge commit   d10ced5d70ffc68642067aac44feeb6a91cacd29
```

Foundation accepted: safe-area, mobile density, touch target, adaptive operational table/list, mobile form/filter, sticky action, modal compatibility, compact pagination, loading/empty/error, dan WebView-friendly overflow baseline.

## 5. G3.2 — Guru/Wali Presensi & Jurnal — CLOSED / MERGED

```text
PR #7          MERGED
merge commit   176e5f764850d030968524af47117f259449064c
```

Gate PASS mencakup Presensi name-first, H/S/I/A mobile, Guru/Wali scope, Jurnal Materi+Catatan, child exception S/I/A, roster validation, atomic parent+child, official-Presensi invariant, no N+1, Detail lazy-load, serta SQL local/hosting.

Schema final:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

## 6. G3.3 — Dashboard Guru/Wali — CLOSED / MERGED

```text
PR #8          MERGED
merge commit   06e4e559c045763096058fc889342da78d973314
```

Final acceptance: KPI Guru, Aksi Cepat permission-aware, Guru/Wali context benar, no fake Hadir when no row, mobile card/list, dan no horizontal operational overflow.

## 7. G3.3.1 — Fondasi BK + Konseling

Seluruh gate besar sebelumnya telah PASS pada local + hosting. Full docs/source audit menemukan satu closure edge-case preservasi Rencana historis. Patch telah lolos focused local runtime UAT, tetapi PR #9 belum boleh masuk Ready sebelum static gate head terbaru dan focused hosting re-smoke PASS.

### 7.1 Business Contract — PASS

```text
Catatan Kasus -> Catatan Pelanggaran Siswa pada experience/UI
poin tidak tampil/dihitung/diekspor/diranking
kategori Ringan/Sedang/Berat tetap klasifikasi
Top Poin retired
Tindak Lanjut Pelanggaran 1:N
Prestasi terpisah
Konseling terpisah dan rahasia
```

### 7.2 Catatan Pelanggaran / Tindak Lanjut — PASS

- create/edit Catatan Pelanggaran;
- detail: Riwayat Tindak Lanjut sebelum form;
- create/edit Tindak Lanjut;
- actor `Dicatat oleh`;
- export dua sheet + Kelas;
- no points.

### 7.3 Prestasi — PASS

- create/edit aman;
- project confirmation untuk delete;
- export memiliki Kelas;
- permission/scope tidak regression.

### 7.4 Konseling Tahap 1 — PASS

```text
Kelas
→ Siswa aktif anggota kelas/Tahun aktif
→ Tanggal
→ Pertemuan ke-
→ Bentuk Layanan
→ Cara Hadir
→ Bidang
→ Topik
→ status Proses
```

Server memvalidasi kelas/tahun, membership aktif, option, permission, dan actor `created_by -> users.id`. BK tidak wajib identity Guru.

### 7.5 Konseling Tahap 2 — Broad Flow PASS

```text
Perkembangan Tersimpan
→ Uraian Masalah
→ Hasil Pembahasan & Kesepakatan
→ Rencana Berikutnya
→ Tanggal Berikutnya
→ Status Proses/Selesai
```

Invariant:

- metadata Tahap 1 tidak berubah;
- `Proses` boleh incomplete;
- `Selesai` wajib Uraian + Hasil;
- tanggal berikutnya tidak sebelum tanggal Konseling;
- tidak ada delete workflow.

### 7.6 Closure Patch — Preservasi Rencana Historis

Kasus yang ditemukan saat audit:

```text
record lama menyimpan Rencana X
→ Admin/BK menghapus X dari Pengaturan Form
→ record lama dibuka kembali
```

Kontrak final:

```text
Rencana X harus tetap terlihat sebagai nilai tersimpan
record lama boleh mempertahankan X
user boleh mengganti ke opsi aktif baru
nilai X tidak boleh menjadi opsi baru global setelah dihapus
forged X pada record lain tetap ditolak
```

Patch source:

- JS Detail menambahkan opsi bertanda `(tersimpan)` hanya untuk record yang sedang dibuka bila nilai tersebut sudah tidak ada di daftar aktif;
- Service Stage 2 hanya menambahkan `existing.rencana_berikutnya` ke allowed set record tersebut;
- tidak ada schema/SQL change.

Focused local runtime UAT 17 September 2026: **PASS**.

Evidence user:

```text
A. opsi lama sesudah dihapus Settings tetap tampil di record lama   PASS
B. record lama dapat disimpan tanpa mengganti opsi lama             PASS
C. opsi lama dapat diganti ke opsi aktif baru                        PASS
D. opsi lama tidak muncul pada record lain                           PASS
```

Yang masih wajib sebelum PR Ready:

```text
closure static gate head terbaru   PENDING
focused hosting re-smoke           PENDING
```

Tidak ada SQL hosting tambahan.

### 7.7 Pengaturan Form Konseling — PASS

```text
storage = setting_sistem / bk_konseling_form_options
Bentuk Layanan max 50
Cara Hadir max 80
Topik max 150
Rencana max 100
1..40 pilihan per group
Bidang fixed = Pribadi/Sosial/Belajar/Karier
Status fixed = Proses/Selesai
fallback default aman
```

Access Settings: Admin/BK saja.

### 7.8 Privacy / RBAC — PASS

```text
view/manage/export -> effective role Admin/Operator/BK + permission
settings           -> effective role Admin/BK + permission
```

Pimpinan/Guru/Wali/Siswa direct URL/menu/widget/detail ditolak/tidak dibentuk.

### 7.9 SQL / Database — PASS, tidak berubah oleh closure patch

Final local:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
```

Final hosting:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

```text
localhost SQL/UAT                 PASS
hosting dump compatibility audit  PASS
hosting SQL execution             PASS
broad hosting smoke UAT           PASS
closure patch SQL                 NOT REQUIRED
```

### 7.10 Cross-role / Responsive — PASS

Pimpinan no points/no Konseling, Wali permission-aware/no Konseling, Siswa self-only/no points/no Konseling, viewport tested tanpa body horizontal overflow.

### 7.11 PR Gate Saat Ini

```text
Broad application/static audit    PASS
FINAL UAT localhost sebelumnya    PASS
Security/privacy                  PASS
Hosting SQL + broad smoke         PASS
Full docs audit                   PASS
Closure patch source              PATCHED
Closure focused local runtime UAT PASS
Closure static gate latest head   PENDING
Closure hosting smoke             PENDING
PR #9                             DRAFT / belum merge
```

PR hanya kembali ke FINAL PASS setelah static gate head terbaru dan focused hosting re-smoke selesai. Ready/Merge tetap approval eksplisit terpisah.

## 8. G3.4 — BK Workflow + Dashboard BK — NEXT

Hanya dimulai setelah PR #9 merged. Gunakan foundation final:

```text
Dashboard BK mobile-first
Konseling Proses/follow-up terdekat
Catatan Pelanggaran terbaru/berat tanpa poin
Tindak Lanjut perlu perhatian
EWS
Prestasi
quick action permission-aware
no wide operational table
name-first
privacy Konseling tetap ketat
```

## 9. G3.5 — Pimpinan

Monitoring/decision; tidak mendapat detail Konseling.

## 10. G3.6 — Siswa

Self-service data diri; Catatan Pelanggaran tanpa poin; Konseling tidak tampil.

## 11. G3.7 — Global Mobile Sweep

Audit seluruh role: overflow, density, modal, safe-area, touch target, pagination, name-first, loading/error, permission visibility.

## 12. G3.8 — Viewport/WebView Readiness

Fokus safe-area, keyboard/focus, layering, session-expiry Fetch, network failure, link behavior, dan no Cordova-plugin dependency sebelum G4.

## 13. Auth / RBAC Regression Minimum

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
multi-role relevan
```

Cek menu, direct URL, read, mutation, target scope, contextual Wali.

## 14. Network / Mutation Safety

Tidak ada silent offline queue untuk Presensi, Jurnal, Catatan Pelanggaran, Tindak Lanjut, Konseling, atau Prestasi. Network failure bukan sukses palsu.

## 15. Performance

Bounded query, index, no N+1, pagination, dashboard ringkas, DOM bounded, export/PDF dalam memory limit.

## 16. G4 Gate — Cordova APK

G3 Web/mobile harus PASS sebelum architecture spike, real WebView, Back, keyboard, safe-area, geolocation, offline state, file/share, external link, security config, signed build, dan multi-device regression.

## 17. Phase Release Rule

```text
G2 CLOSED      → business/admin baseline
G3.1 CLOSED    → mobile foundation
G3.2 CLOSED    → Guru/Wali Presensi & Jurnal
G3.3 CLOSED    → Dashboard Guru/Wali
G3.3.1 PASS    → hanya setelah closure static + focused hosting re-smoke PASS
G3 PASS        → mobile/WebView UI siap
G4 PASS        → APK distribution gate
```

Setiap merge/release memerlukan approval eksplisit pengguna.