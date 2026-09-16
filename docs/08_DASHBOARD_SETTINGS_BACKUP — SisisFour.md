# Dashboard, Settings, Maintenance, Backup & Log — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 16 September 2026

## 1. Dashboard by Experience

Dashboard mengikuti `11_UI_UX_ROLE_EXPERIENCE — SisisFour.md`.

```text
Admin       system/master/operational overview
Operator    administrasi operasional sesuai permission
Pimpinan    monitoring exception/decision
BK          kasus/EWS/tindak lanjut/prestasi
Guru        tugas mengajar hari ini
Guru+Wali   tugas Guru + kondisi kelas wali
Siswa       self-service data diri
```

Wali Kelas tetap context Guru, bukan role.

## 2. Mobile Dashboard Rule

Pimpinan/BK/Guru/Wali/Siswa mengikuti `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`:

```text
4 KPI = grid 2×2
spacing compact
quick action 2×2 bila relevan
recent/top list maksimal 3–5 item
no horizontal table scroll
name-first identity
```

Implementasi besar dilakukan pada G3. G2 hanya stabilization/regression.

## 3. Pimpinan

Prioritas:

```text
kelas belum Presensi
jadwal belum Jurnal
EWS
kasus/pelanggaran penting
trend singkat
```

Statistik jumlah master berada di bawah data exception.

## 4. BK

Prioritas:

```text
Kasus
Pelanggaran Berat
EWS
Tindak Lanjut
Prestasi
```

## 5. Guru

Prioritas:

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
Quick Action
```

Action mengikuti time-window dan state server.

G3.3 menetapkan implementasi Guru:

```text
KPI 2×2 menggunakan task summary server
Belum Presensi = jadwal dengan Presensi applicable yang belum submitted
Belum Jurnal = jadwal aktif hari ini yang belum memiliki Jurnal
Selesai = Presensi selesai/tidak applicable DAN Jurnal submitted
Quick Action hanya route yang permission-nya dimiliki user
jadwal berikutnya/sedang berlangsung dipilih dari state waktu server
mobile jadwal memakai card/list; tabel hanya tablet/desktop
riwayat Jurnal dashboard dibatasi ringkas
```

Dashboard tidak membuka permission baru. Direct route tetap melewati route filter dan Service.

## 6. Guru + Wali

Tambahan context:

```text
kelas wali
jumlah siswa
rekap H/S/I/A Sesi Awal
EWS kelas
absence terbaru
quick link contextual
```

G3.3 menetapkan implementasi Wali:

```text
experience Guru tetap tampil lebih dulu
kelas wali berasal dari mapping tahun aktif
rekap H/S/I/A hanya Sesi Awal hari ini
jika jumlah row rekap = 0, UI menyatakan data belum tersedia
EWS kelas mengikuti definisi EWS canonical
absence terbaru name-first, NISN secondary
quick link ditampilkan hanya bila permission sumber valid
mobile EWS/absence memakai list, bukan horizontal table
```

Wali tetap context Guru; tidak ada role atau permission baru.

## 7. Siswa

Readonly/self-service:

```text
status Sesi Awal hari ini
rekap bulan berjalan
recent absence
Kartu Pelajar
Prestasi
Kasus/Pelanggaran diri
Profile
```

Tidak ada row Sesi Awal berarti data belum tersedia, bukan otomatis Hadir.

## 8. Settings User

Permission: `settings_user.manage`.

Fitur:

- create/update account;
- primary/secondary role;
- identity relation;
- aktif/nonaktif;
- reset managed credential;
- auth invalidation bila security state berubah.

Mutation memakai busy guard.

## 9. Settings Menu

Permission: `settings_menu.manage`.

`role_menus` mengatur visibility, bukan authorization boundary. Direct URL tetap diperiksa filter/Service.

Sidebar data-driven dan hanya menampilkan context/menu yang relevan.

## 10. Settings Sistem

Permission: `settings_sistem.manage`.

Key utama meliputi nama/alamat sekolah, logo/icon, geofence, maintenance dan background Kartu.

Branding runtime:

```text
uploads/settings/branding/
```

Kartu background:

```text
uploads/settings/kartu/
```

`icon_sekolah` menjadi favicon login + authenticated shell bila file valid. URL favicon diberi cache-busting. Setting UI menampilkan preview/path asset aktif.

## 11. Maintenance

Global filter.

Saat aktif:

```text
Admin efektif → recovery sesuai policy
Non-Admin Web → 503 HTML
AJAX/API      → 503 JSON
```

Confirmation memakai UI project, bukan browser native dialog.

## 12. Backup

Permission: `backup.manage`.

Storage:

```text
writable/backups/
```

Create/download/delete menggunakan validation path dan permission. Backup adalah pekerjaan Admin desktop, bukan target utama APK role operasional.

## 13. Log Activity

Permission: `log_activity.view`.

Log tidak boleh menyimpan password/hash/token/cookie/session id/secret. Filter/pagination/export mengikuti UI canonical.

## 14. G3.3 Acceptance — Dashboard Guru/Wali

G3.3 PASS bila:

- KPI Guru sesuai Jadwal/Belum Presensi/Belum Jurnal/Selesai;
- quick action tidak melampaui effective permission;
- action Presensi/Jurnal tetap mengikuti state/time-window server;
- Guru tanpa jadwal mendapat empty state yang jelas;
- Wali mendapat kelas, jumlah siswa, H/S/I/A, EWS dan absence yang tepat;
- tidak adanya Presensi Sesi Awal ditampilkan sebagai belum tersedia, bukan Hadir;
- 360×800, 390×844, 412×915 tidak memiliki body/table horizontal overflow;
- mobile jadwal/EWS/absence menggunakan card/list;
- tablet/desktop tetap ringkas dan readable;
- route/RBAC/business rule G3.2 tidak berubah;
- tidak ada schema/database baru.

## 15. Phase Boundary

```text
G2      → dashboard/settings stabilization only
G3.3    → Dashboard Guru/Wali mobile-first
G3.4+   → role dashboard berikutnya
G4      → Cordova integration
```
