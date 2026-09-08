# FINALISASI INTEGRASI MASTER DATA — TEST MATRIX

Dokumen ini adalah checklist runtime setelah pemasangan paket
`master_data_integration_final`.

## 1. Prasyarat

1. Database menggunakan dump/patch terbaru.
2. `app/Config/Filters.php` tetap mengaktifkan CSRF untuk Web dan hanya
   mengecualikan `api/*`.
3. `app/Config/Security.php` dari paket ini sudah terpasang.
4. `assets/js/csrf-fetch.js` termuat sebelum JavaScript modul.
5. Browser melakukan hard reload setelah file diganti.

## 2. CSRF Smoke Test

Login sebagai Admin lalu uji request berikut dari UI:

- Update Guru.
- Delete/Restore Guru.
- Update Pegawai.
- Delete/Restore Pegawai.
- Update Siswa.
- Upload Foto Siswa.
- Delete/Restore Siswa.
- Tambah Anggota Kelas.
- Keluarkan Anggota Kelas.
- Update Kelas.
- Delete/Restore Kelas.
- Aktifkan Tahun Ajaran.
- Update Tahun Ajaran.
- Delete/Restore Tahun Ajaran.
- Update Mata Pelajaran.
- Delete Mata Pelajaran yang belum dipakai.
- Assign Wali Kelas.
- Nonaktifkan Wali Kelas.
- Restore Wali Kelas.
- Import Jadwal Guru.
- Delete Jadwal Guru.

Expected:
- tidak ada response 403 akibat CSRF;
- request yang valid berhasil;
- request invalid tetap ditolak oleh Service/validation.

## 3. Guru

- Create Guru membuat `users` dan `user_roles` sesuai aturan.
- NIP unik.
- Edit data tidak membuat user ganda.
- Foto PNG maksimal 2 MB.
- Soft delete menonaktifkan user terkait.
- Restore mengaktifkan/membuat kembali user sesuai kondisi.
- Force delete hanya berhasil bila tidak terikat FK.

## 4. Pegawai

- Create Pegawai membuat user dengan username = NIP.
- Role user boleh NULL sampai ditentukan Admin.
- NIP unik.
- Soft delete/restore sinkron dengan user.
- Import berhenti seluruhnya jika satu baris error.

## 5. Siswa

- NIK wajib 16 digit dan unik.
- NISN unik dan menjadi username.
- Create membuat user role siswa.
- Wali tidak dapat mengubah NISN.
- Wali hanya dapat melihat/mengedit siswa kelas yang diwalikan.
- Mutasi mengisi tanggal/keterangan, menutup riwayat, dan menonaktifkan kartu.
- Import atomic.
- Export mengikuti filter.
- Soft delete/restore sinkron dengan user.

## 6. Kelas

- `nama_kelas` = `tingkat-rombel`.
- Satu siswa hanya boleh satu kelas per tahun.
- Tambah anggota membuat `anggota_kelas` dan `riwayat_siswa`.
- Keluarkan anggota menutup/menghapus relasi sesuai aturan Service.
- Kenaikan kelas:
  - checklist default terpilih;
  - target pada tahun berikut;
  - histori lama ditutup;
  - histori baru dibuat;
  - wali dan jadwal tidak ikut dipindah.
- Kelulusan hanya kelas tingkat 9.
- Kelas yang masih dipakai tidak dapat dihapus.

## 7. Tahun Ajaran

- Data baru selalu Nonaktif.
- Hanya satu tahun/semester aktif.
- Mengaktifkan data baru otomatis menonaktifkan yang lama.
- Tahun aktif tidak dapat dihapus.
- Tahun yang masih direferensikan data lain tidak dapat dihapus.
- Restore selalu kembali dalam status Nonaktif.

## 8. Mata Pelajaran

- Kode mapel uppercase, unik, maksimal 10 karakter.
- Mapel yang belum dipakai dapat di-hard-delete.
- Mapel yang sudah dipakai `jadwal_guru` tidak dapat dihapus.

## 9. Mapping Wali Kelas

- Wali Kelas bukan role.
- Satu guru maksimal satu wali aktif per tahun.
- Satu kelas maksimal satu wali aktif per tahun.
- Nonaktifkan menggunakan soft delete.
- Guru yang pernah menjadi wali pada tahun yang sama di-reassign dengan
  restore row lama, bukan INSERT duplikat.
- Guru biasa hanya melihat mapping dirinya.
- Pimpinan readonly seluruh mapping.
- Admin/Operator full.

## 10. Jadwal Guru

- Tidak ada input manual satu-satu.
- Template import:
  `NIP_GURU | NAMA_KELAS | KODE_MAPEL | HARI | JAM_MULAI | JAM_SELESAI | SESI`.
- Import hanya ke tahun ajaran/semester aktif.
- Bentrok guru ditolak.
- Bentrok kelas ditolak.
- Tidak ada team teaching.
- Satu baris error membatalkan seluruh import.
- Jadwal aktif lama menjadi Nonaktif.
- Jadwal hasil import menjadi Aktif.
- Guru hanya melihat jadwal dirinya.
- Pimpinan readonly semua.
- Export mengikuti filter aktif.

## 11. RBAC / Sidebar

Uji minimal akun berikut:

### Admin
Semua Master Data tampil dan dapat dikelola.

### Operator
Semua Master Data operasional tampil dan dapat dikelola sesuai permission.

### Pimpinan
Master Data readonly yang memang diberikan `view_all` tampil tanpa tombol
mutasi/management.

### Guru Biasa
Tidak melihat Data Siswa Wali-only.
Mapping Wali dan Jadwal hanya diri sendiri.

### Guru yang sedang menjadi Wali
Data Siswa muncul secara contextual dan hanya kelas yang diwalikan.

### Siswa
Tidak memperoleh menu administrasi Master Data.

## 12. Kriteria Lulus Integrasi

Master Data dinyatakan lulus bila:

- tidak ada error CSRF pada request Web mutasi;
- tidak ada route 404 untuk endpoint Master Data;
- tidak ada duplicate user/anggota/mapping aktif;
- transaksi import rollback utuh pada error;
- scope RBAC sesuai role/context Wali;
- histori siswa dan mapping tidak terputus;
- seluruh FK penting tetap konsisten.
