# Dashboard, Settings, Maintenance, Backup & Log — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 17 September 2026

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

Contract:

```text
Catatan Pelanggaran = jumlah/exception, bukan ranking poin
Konseling BK bukan widget/source data Pimpinan
widget tanpa permission = tidak tersedia, bukan 0 palsu
```

## 4. BK — Foundation untuk G3.4

Foundation target G3.3.1 setelah rework 17 September:

```text
Catatan Pelanggaran tanpa poin
Tindak Lanjut Pelanggaran 1:N
Konseling parent Tahap 1/Tahap 2
Tindak Lanjut Konseling 1:N
Konseling rahasia
Catatan Pelanggaran/Konseling/Prestasi period-aware
EWS
Prestasi
Pengaturan Form Konseling
```

Priority G3.4 nanti:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut perlu perhatian
EWS
Prestasi ringkas
quick action permission-aware
```

G3.4 tidak boleh menghidupkan poin, membuka Konseling ke role lain, atau mengabaikan filter Tahun Ajaran pada surface historis.

## 5. Guru

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
Quick Action
```

G3.3 contract tetap: KPI 2×2, task summary server, action permission-aware, mobile card/list.

## 6. Guru + Wali

Wali mewarisi Guru + context kelas:

```text
kelas wali + jumlah siswa
H/S/I/A Sesi Awal
EWS kelas
ketidakhadiran terbaru
quick link contextual
```

Catatan Pelanggaran dapat muncul bila permission sah. Konseling tidak tampil pada dashboard/quick link dan direct URL tetap ditolak.

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

No row Sesi Awal = data belum tersedia, bukan Hadir. Konseling tidak dibentuk/ditampilkan. Pelanggaran tanpa poin.

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

## 9. Settings User / Menu / Sistem

- `settings_user.manage`: account/role/identity/security state.
- `settings_menu.manage`: visibility/navigation; bukan authorization.
- `settings_sistem.manage`: settings umum.

Pengaturan Form Konseling:

```text
permission = bk_konseling.settings
storage    = setting_sistem / bk_konseling_form_options
access     = Admin + BK
```

Operator tidak mendapat Settings Konseling.

## 10. Historical Rencana Konseling

Perubahan daftar Rencana tidak boleh merusak nilai historis parent maupun Tindak Lanjut Konseling.

```text
Rencana X tersimpan
→ X dihapus dari Settings
→ X tetap tampil sebagai X (tersimpan) pada record terkait
→ boleh dipertahankan / diganti opsi aktif
→ tidak menjadi pilihan global lagi
```

Parent invariant sudah focused-local PASS sebelum rework; follow-up 1:N wajib diuji ulang.

## 11. Maintenance / Backup / Log

Maintenance:

```text
Admin efektif → recovery policy
Non-Admin Web → 503 HTML
AJAX/API      → 503 JSON
```

Backup: `backup.manage`, storage `writable/backups/`.

Log Activity: `log_activity.view`; tidak menyimpan password/hash/token/cookie/session id/secret.

## 12. Period Context pada Dashboard vs Listing

Dashboard current-state boleh tetap memakai Tahun Ajaran aktif sesuai business context. Listing/history periodik wajib menyediakan selector Tahun Ajaran sesuai global UI contract.

Jangan menambah selector Tahun Ajaran pada dashboard hanya untuk kosmetik bila seluruh KPI memang current-state aktif.

## 13. Gate Status

```text
G3.3 Dashboard Guru/Wali                     CLOSED / MERGED
G3.3.1 baseline local/hosting                PASS
Historical-Rencana parent local UAT          PASS
17 Sep periodic/follow-up source             IMPLEMENTED
17 Sep localhost delta SQL                   PREPARED
17 Sep localhost SQL/UAT                     PENDING
17 Sep final static                          PENDING
17 Sep hosting audit/delta/re-smoke          NOT STARTED
PR #9                                        DRAFT / NOT MERGED
G3.4                                         NEXT setelah PR #9 merge
```

## 14. Phase Boundary

```text
G2      dashboard/settings stabilization
G3.3    Dashboard Guru/Wali
G3.3.1  fondasi BK/Konseling + rework period/follow-up
G3.4    Dashboard/Workflow BK memakai foundation final
G3.5+   role berikutnya
G4      Cordova integration
```