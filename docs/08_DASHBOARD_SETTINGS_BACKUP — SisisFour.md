# Dashboard, Settings, Maintenance, Backup & Log — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026

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

## 14. Phase Boundary

```text
G2 → dashboard/settings stabilization only
G3 → mobile role dashboard redesign
G4 → Cordova integration
```
