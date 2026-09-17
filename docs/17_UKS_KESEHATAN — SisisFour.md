# UKS / Kesehatan — SisisFour

**Status:** Canonical Target / Belum Diimplementasikan  
**Tanggal Acuan:** 17 September 2026  
**Global guardrail:** `00_POLA_PENGERJAAN___SisisFour.md` + `00A_GLOBAL_STANDARD_SISFOUR.md`  
**Source field:** workbook `field-form-UKS-CKG-PTSP.xlsx`, sheet `Form UKS` dan `Form CKG`.

> Dokumen ini adalah SSOT domain UKS/Kesehatan. Keputusan user terbaru mengalahkan catatan lama pada workbook bila ada konflik.

## 1. Role & Identity

Role resmi domain:

```text
kesehatan
```

Identity:

```text
Role Kesehatan -> Pegawai -> users.id_pegawai
multi-role      -> YA
```

Full Access Kesehatan **hanya berlaku pada domain UKS**, bukan seluruh aplikasi.

## 2. Menu

```text
UKS
├── Data CKG  -> Data Kesehatan Siswa
└── Data UKS  -> Catatan Harian UKS
```

## 3. Access Boundary / Capability / Scope

```mermaid
flowchart TD
    A["MENU UKS"] --> B{"Actor / Context"}

    B -- "Admin" --> F["FULL ACCESS UKS"]
    B -- "Operator" --> F
    B -- "Kesehatan" --> F

    B -- "Pimpinan" --> P["READONLY + EXPORT"]
    P --> P1["Scope SEMUA"]

    B -- "Guru + Wali Kelas" --> W["READONLY"]
    W --> W1["Scope KELAS WALI"]

    B -- "Siswa" --> S["READONLY"]
    S --> S1["Scope DIRI_SENDIRI"]

    B -- "Guru non-Wali / BK / PTSP / role lain" --> X["DEFAULT DENY"]

    F --> V["Target Validation"]
    P1 --> V
    W1 --> V
    S1 --> V
    V --> R["Period Context"]
    R --> I["Business Invariant"]
    I --> U["Persistence"]
    U --> UI["Presentation UI"]
```

Canonical matrix:

| Actor | View | Create | Update | Import CKG | Export XLSX | Soft Delete | Kelola Master | Scope |
|---|---|---|---|---|---|---|---|---|
| Admin | YA | YA | YA | YA | YA | YA | YA | SEMUA |
| Operator | YA | YA | YA | YA | YA | YA | YA | SEMUA |
| Kesehatan | YA | YA | YA | YA | YA | YA | YA | SEMUA |
| Pimpinan | YA | TIDAK | TIDAK | TIDAK | YA | TIDAK | TIDAK | SEMUA |
| Guru + Wali | YA | TIDAK | TIDAK | TIDAK | TIDAK | TIDAK | TIDAK | KELAS WALI |
| Siswa | YA | TIDAK | TIDAK | TIDAK | TIDAK | TIDAK | TIDAK | DIRI_SENDIRI |

Guru tanpa context Wali **tidak memiliki akses UKS**.

## 4. Period Context

Data CKG dan Catatan Harian UKS adalah domain periodik Tahun Ajaran.

```text
Read / History     -> Tahun Ajaran filter
Create             -> Tahun Ajaran aktif server-side
Update Existing    -> tetap Tahun Ajaran record
Export             -> Tahun Ajaran terpilih + scope actor
Reset filter       -> Tahun Ajaran aktif
```

Wali historis:

```text
pilih Tahun Ajaran lama
-> scope Wali dihitung terhadap kelas wali pada periode tersebut
-> bukan kelas wali Tahun Ajaran aktif
```

Kelas pada histori harus merepresentasikan kelas siswa pada periode/kejadian record, bukan kelas aktif siswa saat ini.

## 5. Data CKG

Satu siswa boleh mempunyai lebih dari satu pemeriksaan CKG dalam satu Tahun Ajaran.

Field canonical dari workbook:

```text
Tanggal
Kelas
Nama Siswa
Berat badan (kg)
Tinggi badan (cm)
Status gizi (BB/U)
  - Normal
  - Kurus
  - Sangat kurus
  - Gemuk
  - Obesitas
Status tinggi (TB/U)
  - Normal
  - Pendek
  - Sangat pendek
Lingkar perut (cm)
Tekanan darah sistol
Tekanan darah diastol
Gula darah (mg/dL)
Kondisi gigi dan mulut
  - Normal
  - Karies
  - Gusi bermasalah
  - Kebersihan kurang
Visus mata kanan
Visus mata kiri
Buta warna
  - Ya
  - Tidak
Hasil tes pendengaran
  - Normal
  - Terganggu
  - Serumen
Skrining talasemia (kelas VII)
  - Tidak dilakukan
  - Negatif
  - Perlu pemeriksaan lanjut
Skrining tuberkulosis
  - Negatif
  - Terduga, perlu rujukan
```

### Import Excel

CKG mendukung import Excel.

Canonical rule:

```text
Import -> resolve siswa -> validasi membership Tahun Ajaran -> validate field -> insert/update
```

Duplicate rule:

```text
siswa + tanggal pemeriksaan sudah ada
-> UPDATE record existing
-> bukan insert duplikat baru
```

**OPEN sebelum implementasi:** stable key file import. Nama siswa tidak boleh menjadi satu-satunya key. Rekomendasi target: `NISN` sebagai key utama import.

## 6. Catatan Harian UKS

Satu kunjungan siswa = satu parent record. Tidak ada kebutuhan child/follow-up 1:N pada kontrak saat ini.

Field canonical dari workbook:

```text
Tanggal
Jam masuk
Kelas
Nama siswa
Keluhan
  - Demam
  - Sakit kepala
  - Sakit perut
  - Mual atau muntah
  - Diare
  - Batuk pilek
  - Sakit gigi
  - Nyeri haid
  - Lemas atau pingsan
  - Mimisan
  - Luka atau lecet
  - Keseleo
  - Sesak napas
  - Gatal atau alergi
  - Sakit mata
  - Cedera olahraga
  - Lainnya
Catatan keluhan (opsional)
Suhu tubuh °C (opsional)
Tekanan darah (opsional)
Tindakan, multi-pilih
  - Istirahat
  - Kompres
  - Minum air hangat atau oralit
  - Perawatan luka
  - Pemberian obat
Obat yang diberikan
Jam keluar
Hasil
  - Kembali ke kelas
  - Istirahat di UKS
  - Dijemput orang tua
  - Dirujuk ke klinik
Orang tua dihubungi
  - Ya
  - Tidak
Petugas UKS -> actor login
```

`Hasil` adalah disposition kunjungan. Tidak ada lifecycle status tambahan pada kontrak saat ini.

Catatan Harian UKS hanya untuk siswa.

## 7. Correction / Delete

Record UKS/CKG boleh diedit sesuai capability Admin/Operator/Kesehatan.

Delete menggunakan **soft delete**, bukan hard delete.

```text
soft delete -> record hilang dari listing/export normal
            -> histori/audit tetap dapat ditelusuri
```

Tidak ada kewajiban mengisi alasan penghapusan.

Technical tombstone minimal mengikuti pola aplikasi (`deleted_at` bila implementasi menggunakan SoftDeletes); mutation tetap masuk audit/log actor sesuai standar SisisFour.

## 8. Master / Reference

Admin/Operator/Kesehatan boleh mengelola master/reference UKS.

Master candidate dari workbook:

```text
Keluhan
Tindakan
Hasil kunjungan
```

Pilihan pemeriksaan CKG dan enum sederhana (`Ya/Tidak`, status gizi, kondisi gigi, pendengaran, dsb.) diperlakukan sebagai fixed domain option pada target awal, kecuali keputusan domain berikutnya mengubahnya menjadi configurable.

## 9. Privacy

Data UKS/CKG mengikuti model akses operasional seperti Presensi sesuai keputusan user; tidak dibuat lapisan privasi medis khusus tambahan.

Namun authorization tetap wajib melalui Route/Permission + Service + Scope. Data tidak boleh dikirim ke actor di luar Access Boundary lalu hanya disembunyikan di UI.

Pimpinan dapat melihat seluruh detail UKS/CKG dan export XLSX.

Siswa dapat melihat seluruh data kesehatan dirinya sendiri, tetapi **view only** dan tidak mempunyai export.

## 10. Dashboard / Notification

Role Kesehatan mendapat experience/dashboard domain seperti role operasional lain.

Target dashboard UKS dapat merangkum CKG dan kunjungan UKS sesuai Tahun Ajaran/filter.

Pimpinan **tidak memerlukan widget/data UKS tambahan pada dashboard utama Pimpinan**; akses dilakukan melalui menu UKS.

Notification mengikuti pola notification global SisisFour ketika domain diimplementasikan; trigger konkret ditetapkan pada phase implementasi tanpa mengubah Access Boundary.

## 11. Audit

Semua mutation penting wajib masuk audit/log activity:

```text
create
update
import
soft delete
master/reference mutation
```

Actor menggunakan `users.id`; display actor Role Kesehatan melalui `users.id_pegawai -> pegawai.nama`.

## 12. Testing Minimum

```text
Admin/Operator/Kesehatan full domain UKS
Pimpinan readonly SEMUA + export
Wali readonly hanya kelas wali
Wali histori mengikuti kelas wali pada period historis
Guru non-Wali DENY
Siswa readonly data diri sendiri
BK/PTSP DENY
create snapshot Tahun aktif
history/export mengikuti Tahun terpilih
CKG duplicate siswa+tanggal -> update existing
soft delete tidak muncul listing/export normal
mobile no horizontal overflow
```

## 13. Roadmap

Target phase:

```text
G3.6A — UKS / Kesehatan
```

Dikerjakan setelah role experience Pimpinan dan Siswa stabil agar scope Pimpinan/Wali/Siswa dapat diuji lintas-role dengan foundation yang sama.

Belum ada source/schema/permission/menu implementation dari dokumen ini. Implementasi memerlukan phase dan approval terpisah.