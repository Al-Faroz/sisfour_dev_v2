# Phase 3.1 — Integrity & Hardening SisisFour

**Tanggal:** 09 September 2026  
**Repo baseline:** `main @ 5d4d44c3b1c16aa7b51b9ff7a3976fcfe018671f`  
**DB baseline:** `sisfour_dev_v2 (28).sql`

## Tujuan

Phase 3.1 tidak menambah fitur bisnis baru. Fokusnya menutup temuan audit setelah Phase 3:

1. memperbaiki schema `ci_sessions.timestamp`;
2. memastikan CHECK constraint Personalia aktif;
3. memperketat whitelist jenis dokumen;
4. memperketat path secure document;
5. mengunci kebijakan self-delete;
6. menambah test policy Personalia;
7. memperbarui kontrak Profile dan quality gate.

## Session

CodeIgniter 4.7.x `DatabaseHandler` menulis `timestamp` dengan `now()` dan garbage collection membandingkannya dengan waktu database. Schema legacy `INT UNSIGNED` tidak sesuai.

Target Phase 3.1:

```sql
timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```

SQL mengosongkan `ci_sessions` terlebih dahulu. Ini disengaja; tidak ada konversi terhadap nilai `4294967295` karena nilainya bukan timestamp legacy yang dapat dipercaya.

## CHECK Personalia

Constraint yang dikunci:

```text
chk_rp_owner
chk_rpen_owner
chk_rpen_periode
chk_rpk_owner
chk_dp_owner
```

Owner wajib tepat satu:

```text
id_guru XOR id_pegawai
```

Penugasan wajib:

```text
tanggal_selesai IS NULL
ATAU
tanggal_selesai >= tanggal_mulai
```

## Dokumen

Kategori backend:

```text
KTP / KK
SK Pengangkatan Awal
Kartu / Bukti NUPTK
Sertifikat Pendidik
Kartu Pegawai
Lainnya
```

Path storage valid hanya:

```text
uploads/personalia/guru/{id}/{file}.pdf|png|jpg|jpeg
uploads/personalia/pegawai/{id}/{file}.pdf|png|jpg|jpeg
```

Saat file dibuka, path wajib cocok dengan owner record. MIME response diturunkan dari extension storage yang sudah lolos policy, bukan dari kolom `mime_type` yang dapat berubah di database.

## Self-delete

Kebijakan final:

- self Guru/Pegawai yang mempunyai hak edit dapat delete record miliknya sendiri;
- actor Master dengan permission manage dapat delete target;
- readonly actor tidak dapat delete;
- owner tetap dicek ulang pada Service;
- tidak ada approval workflow.

## Setelah Instalasi

Wajib kirim/cek dump database terbaru. Setelah terbukti:

- `ci_sessions.timestamp` DATETIME;
- semua CHECK tampil di `SHOW CREATE TABLE`;
- login baru menghasilkan timestamp valid;

baru `docs/02_DATABASE — SisisFour.md` diperbarui menjadi baseline database final berikutnya.
