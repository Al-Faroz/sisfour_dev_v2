# Dashboard, Settings, Maintenance, Backup & Log — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026

## 1. Dashboard by Experience

```text
Admin       system/master/operational overview
Operator    administrasi operasional sesuai permission
Pimpinan    monitoring exception/decision
BK          Catatan Pelanggaran/Konseling/EWS/Tindak Lanjut/Prestasi
Guru        tugas mengajar hari ini
Guru+Wali   tugas Guru + kondisi kelas wali
Siswa       self-service data diri
```

Wali Kelas tetap context Guru, bukan role.

## 2. Mobile Dashboard Rule

Pimpinan/BK/Guru/Wali/Siswa:

```text
4 KPI = grid 2×2
spacing compact
quick action 2×2 bila relevan
recent/top list 3–5 item
no horizontal operational table scroll
name-first identity
```

## 3. Pimpinan

Prioritas:

```text
kelas belum Presensi
jadwal belum Jurnal
EWS
Catatan Pelanggaran
trend singkat
```

Final G3.3.1:

```text
Catatan Pelanggaran = jumlah/exception, bukan ranking poin
Konseling BK bukan widget/source data Pimpinan
widget tanpa permission = "tidak tersedia", bukan 0 palsu
KPI mobile 2×2
trend Presensi list mobile / tabel desktop
EWS ringkas
```

## 4. BK — Baseline G3.4

Foundation G3.3.1:

```text
Catatan Pelanggaran tanpa poin
Konseling dua tahap dan rahasia
Tindak Lanjut Pelanggaran 1:N
EWS
Prestasi
Pengaturan Form Konseling
```

Priority G3.4:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut perlu perhatian
EWS
Prestasi ringkas
quick action permission-aware
```

G3.4 tidak boleh menghidupkan poin atau membuka Konseling ke role lain.

## 5. Guru

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
Quick Action
```

G3.3 contract: KPI 2×2, task summary server, action permission-aware, jadwal berikutnya dari server time state, mobile card/list, riwayat Jurnal ringkas.

## 6. Guru + Wali

Wali mewarisi Guru + context kelas:

```text
kelas wali + jumlah siswa
H/S/I/A Sesi Awal
EWS kelas
ketidakhadiran terbaru
quick link contextual
```

G3.3.1:

```text
quick link = Catatan Pelanggaran bila permission sah
Konseling tidak tampil pada dashboard/quick link
Direct URL Konseling tetap ditolak
```

## 7. Siswa

Self-service:

```text
status Sesi Awal hari ini
rekap bulan
recent absence
Kartu Pelajar
Prestasi
Catatan Pelanggaran diri sesuai permission
Profile
```

No row Sesi Awal = data belum tersedia, bukan Hadir.

G3.3.1:

```text
recent absence list mobile
Prestasi/Pelanggaran hanya diri sendiri
section tanpa permission tidak tampil sebagai data kosong
Konseling tidak dibentuk/ditampilkan
Pelanggaran tanpa poin
```

## 8. Boundary Konseling

```text
Admin       operasional + settings sesuai permission
Operator    operasional view/manage/export; tanpa settings
BK          operasional + settings sesuai permission
Pimpinan    tidak menerima detail/widget
Guru/Wali   tidak menerima detail/widget/quick link
Siswa       tidak menerima detail/widget
```

Authorization Route/Filter + Service; menu bukan security boundary.

## 9. Settings User

Permission `settings_user.manage`:

- create/update account;
- primary/secondary role;
- identity relation;
- aktif/nonaktif;
- reset credential;
- auth invalidation bila security state berubah.

## 10. Settings Menu

Permission `settings_menu.manage`. `role_menus` hanya visibility/navigation.

## 11. Settings Sistem & Pengaturan Konseling

Settings Sistem umum memakai `settings_sistem.manage`.

Pengaturan Form Konseling adalah surface khusus:

```text
permission = bk_konseling.settings
storage    = setting_sistem / bk_konseling_form_options
access     = Admin + BK
```

Operator tidak mendapat Settings Konseling.

Perubahan daftar Rencana tidak boleh menghapus/mengosongkan nilai historis record Konseling yang sudah tersimpan. Closure patch membuat nilai lama tetap tersedia hanya sebagai `(tersimpan)` pada record terkait; opsi tersebut tidak hidup kembali sebagai pilihan global.

## 12. Maintenance

Saat aktif:

```text
Admin efektif → recovery policy
Non-Admin Web → 503 HTML
AJAX/API      → 503 JSON
```

Confirmation memakai komponen project, bukan native `confirm()`.

## 13. Backup

Permission `backup.manage`, storage `writable/backups/`. Fokus Admin desktop.

## 14. Log Activity

Permission `log_activity.view`. Log tidak menyimpan password/hash/token/cookie/session id/secret.

## 15. Gate Status

```text
G3.3 Dashboard Guru/Wali              CLOSED / MERGED
G3.3.1 broad local UAT                PASS
G3.3.1 privacy/RBAC                   PASS
G3.3.1 SQL local + hosting            PASS
G3.3.1 broad hosting smoke            PASS
G3.3.1 historical-Rencana closure     PATCHED / focused re-smoke PENDING
PR #9                                 DRAFT / NOT MERGED
G3.4                                  NEXT setelah closure + merge
```

## 16. Phase Boundary

```text
G2      dashboard/settings stabilization
G3.3    Dashboard Guru/Wali
G3.3.1  fondasi BK/Konseling + cross-role finalization
G3.4    Dashboard/Workflow BK memakai foundation final
G3.5+   role berikutnya
G4      Cordova integration
```