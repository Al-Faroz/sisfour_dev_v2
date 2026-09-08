# SisisFour Docs v0.5 — Performance Finalization

Dokumen ini memfinalkan arsitektur pengolahan dataset besar sebelum implementasi Presensi.

Aturan utama:

> Filtering, searching, sorting, grouping, counting, aggregation, dan rekap dataset besar dilakukan oleh MariaDB/MySQL melalui SQL/Query Builder CI4. PHP hanya menerima hasil yang sudah dibatasi dan melakukan business rule, formatting, atau pivot ringan.

File yang diperbarui:

- `00_POLA_PENGERJAAN___SisisFour.md`
- `01_MASTERPLAN — SisisFour.md`
- `02_DATABASE — SisisFour.md`
- `05_PRESENSI — SisisFour.md`
- `06_LAPORAN — SisisFour.md`
- `08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md`
- `15_TESTING_POLISH — SisisFour.md`
