# CATATAN PERUBAHAN CHECKPOINT — 09 SEPTEMBER 2026

## Master / Manajemen Siswa
- Master Siswa difokuskan untuk identitas siswa.
- Penempatan/Pindah Kelas, Kenaikan, Mutasi, Kelulusan dipisah ke Manajemen Siswa.
- Import 1.493 siswa berhasil sekaligus dan membentuk siswa/users/user_roles/anggota_kelas/riwayat_siswa.

## Kartu Pelajar
- Menu `Terbitkan Kartu` dihapus karena duplikat dengan `Daftar Kartu`.
- Generate dan cetak dipisahkan sebagai dua proses.
- Generate tunggal dipertahankan.
- Ditambahkan bulk generate seluruh siswa aktif yang belum memiliki kartu.
- Bulk generate diproses per transaksi maksimal 200 siswa.
- Siswa yang sudah memiliki kartu aktif tidak dibuatkan kartu baru.
- Siswa tanpa foto tidak menampilkan placeholder maupun frame foto.
- Preview kartu tunggal dan PDF tunggal tetap tersedia.
- Ditambahkan cetak massal A4 2 kolom × 5 baris, maksimal 200 kartu per PDF.
- Public verify dan QR payload tetap sama.

## Phase 1 — BK, Searchable, Signage
- Tindak lanjut kasus BK dibuat 1:N melalui `tindak_lanjut_kasus`.
- Searchable select Vanilla JS/Fetch dipakai ulang secara global.
- Signage publik menggunakan `/signage` dan `/signage/data` tanpa auth.
- Signage menampilkan EWS Alpha, kelas belum Presensi Sesi Awal, dan Guru belum Presensi Mengajar.

## Phase 2 / 2.1 — Master Guru & Pegawai
- Core Guru/Pegawai diseragamkan tanpa menggabungkan tabel.
- NIK menjadi identitas wajib untuk data baru/edit/import; legacy ditangani konservatif.
- NIP nullable.
- Login identifier memakai NIP bila ada, selain itu NIK.
- Sinkronisasi username/password/auth_version diterapkan pada perubahan identifier yang relevan.
- Unique identity `users.id_guru`, `users.id_pegawai`, `users.id_siswa` dikunci.

## Phase 3 — Personalia & Portofolio
- Ditambahkan `riwayat_pendidikan`.
- Ditambahkan `riwayat_penugasan`.
- Ditambahkan `riwayat_pangkat`.
- Ditambahkan `dokumen_personalia`.
- Shared history memakai owner `id_guru` XOR `id_pegawai`.
- Guru/Pegawai dapat self-service riwayat.
- Admin/Operator dapat membantu melalui Master.
- Pimpinan readonly dapat melihat riwayat dan Portofolio tanpa raw document.
- Dokumen disimpan di `WRITEPATH/uploads/personalia/...`.
- Portofolio PDF dibangkitkan dari data terbaru, bukan snapshot DB.

## Phase 3.1 — Integrity & Hardening
- Audit dump `(28)` mengonfirmasi 32 tabel dan 0 orphan FK pada data yang diperiksa.
- `ci_sessions.timestamp` legacy `INT UNSIGNED` dikoreksi ke `DATETIME` agar sesuai CodeIgniter 4.7.x DatabaseHandler.
- Semua row `ci_sessions` lama sengaja dibuang saat hardening sehingga pengguna Web harus login ulang.
- CHECK owner XOR Personalia dipasang ulang secara eksplisit.
- CHECK periode `riwayat_penugasan` dipasang ulang.
- Backend `jenis_dokumen` memakai whitelist tetap.
- Secure file delivery tidak lagi mempercayai `mime_type` database untuk response header.
- Storage path hanya menerima `uploads/personalia/{guru|pegawai}/{id}/file` dengan extension yang diizinkan.
- Path owner berbeda, traversal, absolute path, dan path di luar storage Personalia ditolak.
- Kebijakan self-delete dikunci: self yang mempunyai hak edit boleh menghapus record miliknya sendiri; Admin/Operator manage tetap boleh menghapus; tidak ada approval workflow.
- Ditambahkan `PersonaliaHardeningTest` untuk whitelist dan path boundary.

## Phase 3.2 — Final Polish & Documentation Sync
- `02_DATABASE — SisisFour.md` disinkronkan ke dump `sisfour_dev_v2 (29).sql` dengan 32 tabel dan `ci_sessions.timestamp = DATETIME`.
- Dokumen Profile dan Testing dikonsolidasikan ke satu nama canonical; duplicate filename encoding `ΓÇö` dihapus.
- `.gitignore` ditambah untuk `build/`, `writable/uploads/`, dan `uploads/foto_pegawai/` agar artifact test/dokumen raw/foto runtime tidak ikut `git add -A`.
- Pesan jabatan legacy Pegawai diperbaiki agar mengarahkan pengguna ke Riwayat & Portofolio yang sudah tersedia.
- Tampilan Personalia readonly tidak lagi menampilkan kolom Aksi.
- Tab Personalia dibuat horizontal-scroll pada layar sempit.
- Portofolio PDF diberi page-break guard untuk section title dan row tabel.
- Viewport utama tidak lagi menonaktifkan zoom pengguna.
- Ditambahkan script verifikasi CHECK Personalia tanpa mutation data.

## Sinkronisasi Dokumen Acuan
Phase 3.2 memperbarui:

- `02_DATABASE — SisisFour.md`
- `09_PROFILE — SisisFour.md`
- `15_TESTING_POLISH — SisisFour.md`
- `Tree Structure — SisisFour.md`
- `_CATATAN_PERUBAHAN_20260909.md`
- `PHASE3_2_FINAL_POLISH.md`

Status CHECK Personalia pada live MariaDB tetap diverifikasi dengan `SHOW CREATE TABLE` karena representasi CHECK tidak terlihat pada dump `(29)`.
