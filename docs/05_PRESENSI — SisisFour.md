# Presensi Siswa & Presensi Mengajar — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Prinsip

Sumber:

```text
siswa
anggota_kelas
riwayat_siswa
kelas
tahun_ajaran
mapping_wali_kelas
jadwal_guru
mata_pelajaran
users/RBAC
setting_sistem
```

Authorization waktu menggunakan server `Asia/Jakarta`.

# 2. Presensi Siswa

Tabel:

```text
presensi
```

Status:

```text
Hadir
Sakit
Izin
Alpha
```

Sesi:

```text
Sesi Awal
Sesi Akhir
```

# 3. Sesi Awal

Sumber resmi:

- laporan;
- Matrix;
- EWS;
- statistik H/S/I/A.

# 4. Sesi Akhir

Dokumentasi tambahan dan tidak masuk total resmi/EWS.

# 5. Unique

```text
UNIQUE(id_kelas,tanggal,sesi,id_siswa)
```

# 6. Actor

| Actor | Input | Revisi | View |
|---|---|---|---|
| Admin | Semua | Semua | Semua |
| Operator | Semua | Semua | Semua |
| Pimpinan | Tidak | Tidak | Semua |
| Guru Terjadwal | Ya | Tidak | workflow terbatas |
| Wali | kelas Wali | kelas Wali | kelas Wali |
| BK | Tidak | Tidak | EWS melalui permission |
| Siswa | Tidak | Tidak | diri |

# 7. Dual Guru + Wali

Urutan:

1. jadwal cocok + time-window valid → `GURU_TERJADWAL`;
2. tidak ada jadwal cocok atau jadwal selesai → fallback `WALI`;
3. sebelum jadwal mulai → Wali tidak boleh bypass;
4. kelas lain yang bukan kelas Wali → aturan Guru;
5. histori tahun aktif → Wali aktif dapat memakai konteks Wali.

# 8. Time Window

```text
jam_mulai <= now <= jam_selesai + 15 menit
```

Berlaku untuk Guru Terjadwal.

Admin/Operator dan fallback Wali kelas sendiri bebas time-window.

# 9. Geofencing

Key:

```text
geofencing_aktif
latitude_sekolah
longitude_sekolah
radius_geofencing
```

Baseline radius:

```text
500 meter
```

Geofence wajib pada jalur Guru yang ditentukan Service.

# 10. Bulk

Satu submit kelas atomic.

Server me-resolve:

- tahun aktif;
- kelas;
- membership;
- actor;
- scope;
- mapping Wali;
- Jadwal;
- existing record;
- snapshot.

Satu target invalid → rollback batch.

# 11. Snapshot

Presensi menyimpan snapshot nama Siswa dan Guru input untuk menjaga histori.

# 12. Revisi

Revisi Presensi Siswa hanya:

```text
Admin
Operator
Wali aktif kelas target
```

Guru biasa tidak merevisi record existing.

# 13. EWS

```text
status = Alpha
sesi = Sesi Awal
jumlah >= 3
periode = hari ini dan 13 hari sebelumnya
```

# 14. Presensi Mengajar / Jurnal

Tabel:

```text
presensi_mengajar
```

Unique:

```text
UNIQUE(id_jadwal,tanggal)
```

Status:

```text
Hadir
Izin
Sakit
```

Materi wajib.

Semua sesi Jadwal dapat mempunyai Jurnal, termasuk `Non Sesi`.

# 15. Scope Jurnal

- Admin/Operator: seluruh sesuai permission;
- Pimpinan: view semua, input diri bila identity/jadwal valid;
- Guru/Wali: diri sendiri;
- BK/Siswa: tidak.

Wali tidak mendapat Jurnal kelas hanya karena status Wali.

# 16. Histori Jurnal

Jadwal Nonaktif tetap reportable.

Status Jadwal Aktif hanya syarat input baru.

# 17. Route Web

```text
/presensi/siswa
/presensi/siswa/input/{id_kelas}
/presensi/siswa/revisi/{id_kelas}
/presensi/siswa/rekap
/presensi/siswa/ews

/presensi/mengajar
/presensi/mengajar/input/{id_jadwal}
/presensi/mengajar/laporan
```

# 18. Checkpoint

- time-window;
- geofence;
- dual Guru/Wali;
- bulk rollback;
- duplicate;
- revisi;
- Sesi Awal/Akhir;
- Jurnal Non Sesi;
- snapshot histori;
- scope direct URL.
