# PTSP — SisisFour

**Status:** Canonical Target / Belum Diimplementasikan  
**Tanggal Acuan:** 17 September 2026  
**Global guardrail:** `00_POLA_PENGERJAAN___SisisFour.md` + `00A_GLOBAL_STANDARD_SISFOUR.md`  
**Source field:** workbook `field-form-UKS-CKG-PTSP.xlsx`, sheet `Form Layanan PTSP`, `Pengaduan`, dan `Form Polling Kepuasan`.

> Dokumen ini adalah SSOT domain PTSP. Keputusan user terbaru mengalahkan catatan lama pada workbook bila ada konflik.

## 1. Role & Identity

Role resmi domain:

```text
ptsp
```

Identity:

```text
Role PTSP -> Pegawai -> users.id_pegawai
multi-role -> YA
```

Full Access PTSP **hanya berlaku pada domain PTSP**, bukan seluruh aplikasi.

## 2. Menu / Surface

Authenticated menu:

```text
PTSP
├── Layanan PTSP
├── Polling Kepuasan
└── Pengaduan
```

Public surface:

```text
PTSP Landing Page
├── Form Layanan PTSP
├── Form Polling Kepuasan
└── Form Pengaduan
```

PTSP mempunyai landing page tersendiri untuk public form.

## 3. Access Boundary Internal

```mermaid
flowchart TD
    A["DOMAIN PTSP INTERNAL"] --> B{"Actor"}

    B -- "Admin" --> F["FULL ACCESS PTSP"]
    B -- "Operator" --> F
    B -- "PTSP" --> F

    B -- "Pimpinan" --> P["READONLY + EXPORT"]
    P --> P1["Scope SEMUA"]

    B -- "BK / Kesehatan / Guru / Wali / Siswa / role lain" --> X["DEFAULT DENY INTERNAL ADMIN SURFACE"]

    F --> V["Target Validation"]
    P1 --> V
    V --> R["Period Context"]
    R --> I["Business Invariant"]
    I --> D["Persistence"]
    D --> UI["Presentation UI"]
```

Canonical capability:

| Actor | View | Create/Internal Mutation | Update Status | Export XLSX | Hard Delete | Scope |
|---|---|---|---|---|---|---|
| Admin | YA | YA | YA | YA | YA | SEMUA |
| Operator | YA | YA | YA | YA | YA | SEMUA |
| PTSP | YA | YA | YA | YA | YA | SEMUA |
| Pimpinan | YA | TIDAK | TIDAK | YA | TIDAK | SEMUA |

Role lain tidak mendapat internal PTSP administration hanya karena public form dapat diakses tanpa login.

## 4. Public Submission Boundary

Ketiga form berikut adalah **public form tanpa login**:

```text
Layanan PTSP
Polling Kepuasan
Pengaduan
```

Public form tidak memberi akses ke listing/detail/admin PTSP.

Target awal proteksi dibuat minimal sesuai keputusan user:

```text
server-side validation
CSRF bila form berjalan same-origin
upload validation untuk Pengaduan
busy guard / anti-double-submit
```

CAPTCHA/honeypot/anti-spam lanjutan tidak diwajibkan pada target awal; dapat ditambah bila kebutuhan runtime muncul.

## 5. Period Context PTSP

Semua submission PTSP snapshot Tahun Ajaran aktif secara server-side.

```text
Public/Create            -> Tahun Ajaran aktif
Read / History           -> Tahun Ajaran filter
Update Existing          -> tetap Tahun Ajaran record
Export                    -> Tahun Ajaran terpilih
Statistics API default   -> Tahun Ajaran aktif / dapat difilter period
Reset internal filter    -> Tahun Ajaran aktif
```

Jika tidak ada Tahun Ajaran aktif, create baru tidak boleh menghasilkan record periodik tanpa period snapshot.

PTSP memakai Tahun Ajaran sebagai konteks administrasi madrasah; pemohon tidak harus siswa.

## 6. Layanan PTSP

Field canonical dari workbook:

```text
Nama Lengkap              wajib
Kategori Pemohon          wajib
  - Siswa/Siswi
  - Alumni
  - Wali Murid
  - Guru/Staff
  - Umum/Instansi Lain
Nomor WhatsApp            wajib
Jenis Layanan             wajib
  - Legalisir Ijazah/Raport
  - Rekomendasi Mutasi Siswa
  - Surat Keterangan Lulus (SKL)
  - Surat Keterangan Aktif Belajar
  - Surat Keterangan Lainnya
  - Layanan Kepegawaian
  - Pendaftaran Siswa Baru
  - Lainnya
Tujuan / Keterangan       wajib
```

Workflow:

```mermaid
stateDiagram-v2
    [*] --> Baru
    Baru --> Diproses
    Diproses --> Selesai
    Selesai --> [*]
```

Mutation status:

```text
Admin / Operator / PTSP
```

Tidak ada requirement follow-up 1:N dan tidak ada SLA pada kontrak saat ini.

Petugas operasional adalah role PTSP; actor mutation tetap `users.id`.

### Cetak Thermal

Catatan workbook lama `mencetak tiket antrian` **digantikan keputusan user terbaru**.

Setelah submit:

```text
-> tampilkan sukses
-> dapat cetak di printer thermal
-> isi cetak = bukti sudah mengisi
-> TANPA nomor tiket
-> TANPA nomor antrean
-> TANPA kode tracking
```

Bukti thermal dapat berisi ringkasan non-rahasia seperti waktu submit, nama pemohon, kategori, jenis layanan, dan keterangan sukses.

## 7. Polling Kepuasan

Semua pihak dapat mengisi public polling dan submission berulang diperbolehkan; tidak ada dedup berdasarkan WhatsApp pada kontrak saat ini.

Field canonical:

```text
Nama Lengkap
Kategori Responden
  - Siswa/Siswi
  - Alumni
  - Wali Murid
  - Guru/Staff
  - Umum/Instansi Lain
Nomor WhatsApp
Tingkat Kepuasan
  - Sangat Memuaskan
  - Memuaskan
  - Cukup Memadai
  - Kurang Memuaskan
  - Sangat Mengecewakan
Masukan & Saran
```

Storage kepuasan:

```text
label original + numeric score
Sangat Memuaskan      = 5
Memuaskan             = 4
Cukup Memadai         = 3
Kurang Memuaskan      = 2
Sangat Mengecewakan   = 1
```

Label tetap dipakai di UI; score dipakai untuk statistik/dashboard.

Pimpinan memiliki ReadOnly SEMUA dan dapat melihat data individual polling pada internal surface.

## 8. Pengaduan

Pengaduan adalah **satu public form anonim**. Tidak dibedakan internal vs eksternal pada data/form.

Field canonical:

```text
Klasifikasi Laporan, multi-pilih
  - Pengaduan
  - Aspirasi
  - Permintaan Informasi
Judul Laporan
Isi Laporan
Tanggal Kejadian
Lampiran (opsional)
  - image/*
  - PDF
```

Form tidak meminta nama/kontak pelapor.

Workflow:

```mermaid
stateDiagram-v2
    [*] --> Masuk
    Masuk --> Diverifikasi
    Diverifikasi --> Diproses
    Diverifikasi --> Selesai
    Diproses --> Selesai
    Selesai --> [*]
```

Tidak ada follow-up child 1:N, tidak ada disposisi unit lain, dan tidak ada tracking code publik pada kontrak saat ini.

Internal actor yang berhak melihat data Pengaduan:

```text
Admin
Operator
PTSP
Pimpinan (ReadOnly)
```

## 9. Delete PTSP

Layanan, Polling, dan Pengaduan dapat di-hard-delete oleh:

```text
Admin
Operator
PTSP
```

Pimpinan ReadOnly dan tidak mempunyai destructive capability.

Hard delete adalah keputusan domain PTSP eksplisit; tidak boleh digeneralisasi ke domain lain.

Delete harus melalui authorization Service dan project confirmation pada UI.

## 10. Export

Format export:

```text
XLSX
```

Akses export PTSP:

```text
Admin
Operator
Pimpinan
PTSP
```

Export mengikuti filter Tahun Ajaran dan filter lain yang sedang aktif.

Role Kesehatan tidak otomatis mempunyai export PTSP; Full Access Kesehatan hanya berlaku di domain UKS.

## 11. Public Statistics API

PTSP wajib menyediakan **API statistik public read-only untuk masing-masing form** agar dapat dipasang pada portal berita/web madrasah seperti WordPress.

Canonical target:

```text
GET /api/public/ptsp/statistik/layanan
GET /api/public/ptsp/statistik/polling
GET /api/public/ptsp/statistik/pengaduan
```

Nama route final boleh disesuaikan saat implementasi, tetapi kontrak output tetap: **aggregate only, tidak membocorkan data personal/raw record**.

### Statistik Layanan

Target aggregate minimum:

```text
total submission
jumlah per status
jumlah per kategori pemohon
jumlah per jenis layanan
periode/Tahun Ajaran
```

### Statistik Polling

Target aggregate minimum:

```text
total responden
jumlah per kategori responden
distribusi label kepuasan
distribusi score 1..5
rata-rata score
periode/Tahun Ajaran
```

### Statistik Pengaduan

Target aggregate minimum:

```text
total laporan
jumlah per klasifikasi
jumlah per status
periode/Tahun Ajaran
```

Public statistics API **tidak boleh mengeluarkan**:

```text
Nama
Nomor WhatsApp
Judul laporan
Isi laporan
Tanggal kejadian individual
Lampiran
raw record identifier yang dapat dipakai enumerasi data privat
```

API bersifat GET/read-only dan dirancang dapat dikonsumsi lintas-origin oleh portal/WordPress. Implementasi dapat menyediakan reusable copy/paste JavaScript/widget snippet yang membaca endpoint tersebut.

```mermaid
flowchart LR
    A["SisisFour PTSP"] --> B["Public Aggregate API"]
    B --> C["Layanan Stats"]
    B --> D["Polling Stats"]
    B --> E["Pengaduan Stats"]

    C --> W["WordPress / Portal Madrasah"]
    D --> W
    E --> W

    B --> X["NO PII / NO RAW RECORD"]
```

## 12. Dashboard

Role PTSP mempunyai landing/dashboard tersendiri.

Dashboard internal dapat merangkum Layanan, Polling, dan Pengaduan sesuai filter period.

Pimpinan **tidak memerlukan widget PTSP tambahan pada dashboard utama Pimpinan**; Pimpinan mengakses data melalui menu PTSP ReadOnly.

## 13. Audit

Mutation internal wajib masuk audit/log activity:

```text
status update
hard delete
internal administrative mutation
```

Public submission menyimpan created timestamp dan period snapshot. Actor authenticated menggunakan `users.id`; display actor Role PTSP melalui `users.id_pegawai -> pegawai.nama`.

Data PTSP diperlakukan sebagai data operasional biasa sesuai keputusan user; tidak ada redaction khusus di internal authorized surface selain larangan exposure melalui public statistics API.

## 14. Testing Minimum

```text
public Layanan dapat submit tanpa login
public Polling dapat submit tanpa login dan berulang
public Pengaduan anonim dapat submit tanpa login
semua create snapshot Tahun Ajaran aktif
internal Admin/Operator/PTSP full domain
Pimpinan readonly SEMUA + export
role lain DENY internal PTSP administration
workflow Layanan Baru -> Diproses -> Selesai
workflow Pengaduan Masuk -> Diverifikasi -> Diproses/Selesai
hard delete hanya Admin/Operator/PTSP
thermal receipt tidak memiliki nomor tiket/antrian/tracking
XLSX mengikuti period/filter
statistics API aggregate-only
statistics API tidak membocorkan PII/raw record
WordPress/cross-origin consumption bekerja sesuai kontrak
mobile/public landing no horizontal overflow
```

## 15. Roadmap

Target phase:

```text
G3.6B — PTSP
```

Dikerjakan setelah G3.6A UKS/Kesehatan. PTSP ditempatkan setelah role experience authenticated utama stabil karena mempunyai tambahan public landing + public submission + public statistics API yang memerlukan regression surface berbeda.

Belum ada source/schema/permission/menu/API implementation dari dokumen ini. Implementasi memerlukan phase dan approval terpisah.