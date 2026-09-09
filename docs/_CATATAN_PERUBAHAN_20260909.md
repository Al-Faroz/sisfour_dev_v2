# CATATAN PERUBAHAN CHECKPOINT — KARTU PELAJAR

Tanggal: 09 September 2026

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
- Ditambahkan cetak massal A4:
  - ukuran kartu ID-1 sekitar 85,6 × 53,98 mm;
  - layout 2 kolom × 5 baris;
  - 10 kartu per lembar;
  - PDF depan dan belakang dibuat terpisah;
  - sisi belakang statis dan identik;
  - cetak dapat dilakukan untuk kartu terpilih atau per kelas;
  - maksimum 200 kartu per file PDF.
- Tidak ada perubahan schema tabel `kartu_pelajar`.
- Public verify dan QR payload tetap sama.

## Dokumen acuan yang nanti perlu diperbarui
- 01_MASTERPLAN — SisisFour.md
- 02_DATABASE — SisisFour.md
- 03_AUTH_RBAC_MENU — SisisFour.md
- 04_MASTER_DATA — SisisFour.md
- 07_BK_PRESTASI_KARTU — SisisFour.md
- Routes Final — SisisFour.md
- Tree Structure — SisisFour.md

Dokumen acuan utama belum diubah dalam checkpoint ini.
