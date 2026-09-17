<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php $options = $initial['options'] ?? []; $kelasOptions = $initial['kelas'] ?? []; ?>

<div id="bkKonselingApp" data-base-url="<?= esc(base_url()) ?>" data-can-manage="<?= ! empty($initial['can_manage']) ? '1' : '0' ?>" data-can-export="<?= ! empty($initial['can_export']) ? '1' : '0' ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy"><h4 class="fw-bold mb-1">Konseling BK</h4><p class="text-muted mb-0">Catatan layanan Konseling bersifat rahasia dan hanya dapat diakses pengguna berwenang.</p></div>
        <div class="sisfour-page-actions">
            <?php if (! empty($initial['can_export'])): ?><a href="#" id="btnKonselingExport" class="btn btn-outline-success"><i class="bx bx-export me-1"></i> Export</a><?php endif; ?>
            <?php if (! empty($initial['can_manage'])): ?><button type="button" id="btnKonselingBaru" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Konseling Baru</button><?php endif; ?>
        </div>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Konseling BK tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div class="alert alert-warning d-flex align-items-start gap-2" role="status"><i class="bx bx-lock-alt fs-4 flex-shrink-0"></i><div><strong>Data rahasia.</strong> Pertemuan awal dicatat dua tahap; tindak lanjut berikutnya disimpan sebagai riwayat terpisah dan dapat ditambahkan berkali-kali.</div></div>

        <div class="card sisfour-filter-card mb-4"><div class="card-body">
            <div class="row g-3 align-items-end mb-0">
                <div class="col-12 col-lg-3"><label for="konselingTahun" class="form-label">Tahun Ajaran</label><select id="konselingTahun" name="id_tahun" class="form-select" data-searchable-off="1"><?php foreach (($initial['tahun_options'] ?? []) as $ta): ?><?php $aktif=(int)($ta['status_aktif']??0)===1; ?><option value="<?= (int)$ta['id'] ?>" <?= (int)($initial['tahun_dipilih']['id']??0)===(int)$ta['id']?'selected':'' ?>><?= esc($ta['nama_tahun'].' - '.$ta['semester'].($aktif?' (Aktif)':'')) ?></option><?php endforeach; ?></select></div>
                <div class="col-12 col-lg-5"><label for="konselingSearch" class="form-label">Pencarian</label><input id="konselingSearch" class="form-control" placeholder="Nama / NISN / topik"></div>
                <div class="col-12 col-lg-4"><label for="konselingFilterKelas" class="form-label">Kelas</label><select id="konselingFilterKelas" class="form-select" data-searchable-select><option value="">Semua kelas</option><?php foreach ($kelasOptions as $kelas): ?><option value="<?= (int)$kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row g-3 align-items-end mt-0">
                <div class="col-6 col-lg-2"><label for="konselingFilterStatus" class="form-label">Status</label><select id="konselingFilterStatus" class="form-select" data-searchable-off="1"><option value="">Semua</option><option value="Proses">Proses</option><option value="Selesai">Selesai</option></select></div>
                <div class="col-6 col-lg-3"><label for="konselingFilterBidang" class="form-label">Bidang</label><select id="konselingFilterBidang" class="form-select" data-searchable-off="1"><option value="">Semua</option><?php foreach (($options['bidang']??[]) as $bidang): ?><option value="<?= esc($bidang,'attr') ?>"><?= esc($bidang) ?></option><?php endforeach; ?></select></div>
                <div class="col-6 col-lg-2"><label for="konselingMulai" class="form-label">Dari</label><input id="konselingMulai" type="date" class="form-control"></div>
                <div class="col-6 col-lg-2"><label for="konselingSelesai" class="form-label">Sampai</label><input id="konselingSelesai" type="date" class="form-control"></div>
                <div class="col-12 col-lg-3"><div class="sisfour-filter-actions justify-content-lg-end"><button type="button" id="btnKonselingReset" class="btn btn-outline-secondary"><i class="bx bx-reset me-1"></i> Reset</button><button type="button" id="btnKonselingCari" class="btn btn-primary"><i class="bx bx-filter-alt me-1"></i> Tampilkan</button></div></div>
            </div>
        </div></div>

        <div id="konselingAlert" class="alert d-none" role="alert"></div>
        <div class="card sisfour-table-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2"><h5 class="mb-0">Riwayat Konseling</h5><span class="text-muted small" id="konselingTotal">0 catatan</span></div>
            <div id="konselingMobileList" class="d-md-none list-group list-group-flush"></div>
            <div class="d-none d-md-block table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Layanan</th><th>Bidang / Topik</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody id="konselingBody"></tbody></table></div>
            <div class="card-footer"><div id="bkKonselingPager"></div></div>
        </div>

        <?php if (! empty($initial['can_manage'])): ?>
        <div class="modal fade" id="modalKonselingBaru" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" id="formKonselingBaru">
            <div class="modal-header"><div><h5 class="modal-title mb-1">Konseling BK — Tahap 1</h5><small class="text-muted">Catat identitas, waktu, dan jenis layanan pertemuan awal.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="card border mb-4"><div class="card-header"><h6 class="mb-0">Siswa &amp; Waktu</h6></div><div class="card-body"><div class="row g-3">
                    <div class="col-md-6"><label for="konselingKelas" class="form-label">Kelas</label><select id="konselingKelas" name="id_kelas" class="form-select" required data-searchable-select data-search-placeholder="Cari kelas..."><option value="">Pilih kelas</option><?php foreach ($kelasOptions as $kelas): ?><option value="<?= (int)$kelas['id'] ?>"><?= esc($kelas['nama_kelas']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label for="konselingSiswa" class="form-label">Siswa</label><select id="konselingSiswa" name="id_siswa" class="form-select" required disabled data-searchable-select data-search-placeholder="Cari nama / NISN..."><option value="">Pilih kelas terlebih dahulu</option></select></div>
                    <div class="col-md-6"><label for="konselingTanggal" class="form-label">Tanggal</label><input id="konselingTanggal" name="tanggal" type="date" class="form-control" required></div>
                    <div class="col-md-6"><label for="konselingPertemuan" class="form-label">Pertemuan ke-</label><input id="konselingPertemuan" name="pertemuan_ke" type="number" min="1" max="99" value="1" class="form-control" required></div>
                </div></div></div>
                <div class="card border mb-0"><div class="card-header"><h6 class="mb-0">Jenis Layanan</h6></div><div class="card-body"><div class="row g-3">
                    <div class="col-md-6"><label for="konselingBentuk" class="form-label">Bentuk Layanan</label><select id="konselingBentuk" name="bentuk_layanan" class="form-select" required data-searchable-off="1"><?php foreach (($options['bentuk_layanan']??[]) as $value): ?><option value="<?= esc($value,'attr') ?>"><?= esc($value) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label for="konselingCaraHadir" class="form-label">Cara Siswa Hadir</label><select id="konselingCaraHadir" name="cara_hadir" class="form-select" required data-searchable-off="1"><?php foreach (($options['cara_hadir']??[]) as $value): ?><option value="<?= esc($value,'attr') ?>"><?= esc($value) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label for="konselingBidang" class="form-label">Bidang</label><select id="konselingBidang" name="bidang" class="form-select" required data-searchable-off="1"><?php foreach (($options['bidang']??[]) as $value): ?><option value="<?= esc($value,'attr') ?>"><?= esc($value) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label for="konselingTopik" class="form-label">Topik</label><select id="konselingTopik" name="topik" class="form-select" required data-searchable-off="1"></select></div>
                </div></div></div>
            </div>
            <div class="modal-footer sisfour-modal-actions"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Simpan Tahap 1</button></div>
        </form></div></div>
        <?php endif; ?>

        <div class="modal fade" id="modalKonselingDetail" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title mb-1">Detail Konseling BK</h5><small class="text-muted">Pertemuan awal dan seluruh tindak lanjut disimpan terpisah agar histori tidak tertimpa.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div id="konselingDetailLoading" class="text-center text-muted py-4">Memuat data...</div>
                <div id="konselingDetailContent" class="d-none">
                    <div class="card bg-label-secondary mb-4"><div class="card-body"><div class="row g-3">
                        <div class="col-md-6"><small class="text-muted d-block">Siswa</small><strong id="detailKonselingSiswa"></strong></div><div class="col-md-3"><small class="text-muted d-block">Kelas</small><strong id="detailKonselingKelas"></strong></div><div class="col-md-3"><small class="text-muted d-block">Tanggal</small><strong id="detailKonselingTanggal"></strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Jenis Layanan</small><strong id="detailKonselingLayanan"></strong></div><div class="col-md-6"><small class="text-muted d-block">Bidang / Topik</small><strong id="detailKonselingTopik"></strong></div><div class="col-md-6"><small class="text-muted d-block">Cara Hadir</small><span id="detailKonselingCara"></span></div><div class="col-md-6"><small class="text-muted d-block">Dicatat oleh</small><span id="detailKonselingGuru"></span></div>
                    </div></div></div>

                    <?php if (! empty($initial['can_manage'])): ?>
                    <form id="formKonselingUpdate" class="card border mb-4"><div class="card-header d-flex justify-content-between align-items-center gap-2"><h6 class="mb-0">Tahap 2 — Hasil Pertemuan Awal</h6><span id="detailKonselingStatus" class="badge bg-label-warning">Proses</span></div><div class="card-body"><input type="hidden" name="id"><div class="row g-3">
                        <div class="col-12"><label for="konselingUraian" class="form-label">Uraian Masalah</label><textarea id="konselingUraian" name="uraian_masalah" class="form-control" rows="4"></textarea></div>
                        <div class="col-12"><label for="konselingHasil" class="form-label">Hasil Pembahasan dan Kesepakatan</label><textarea id="konselingHasil" name="hasil_kesepakatan" class="form-control" rows="4"></textarea></div>
                        <div class="col-md-6"><label for="konselingRencana" class="form-label">Rencana Berikutnya</label><select id="konselingRencana" name="rencana_berikutnya" class="form-select" data-searchable-off="1"><option value="">Belum ditentukan</option><?php foreach (($options['rencana']??[]) as $value): ?><option value="<?= esc($value,'attr') ?>"><?= esc($value) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label for="konselingTanggalBerikutnya" class="form-label">Tanggal Pertemuan Berikutnya</label><input id="konselingTanggalBerikutnya" name="tanggal_berikutnya" type="date" class="form-control"></div>
                        <div class="col-md-6"><label for="konselingStatus" class="form-label">Status</label><select id="konselingStatus" name="status" class="form-select" data-searchable-off="1"><option value="Proses">Proses</option><option value="Selesai">Selesai</option></select></div>
                    </div></div><div class="card-footer sisfour-modal-actions"><button type="submit" class="btn btn-primary">Simpan Pertemuan Awal</button></div></form>
                    <?php else: ?>
                    <div class="card border mb-4"><div class="card-header"><h6 class="mb-0">Hasil Pertemuan Awal</h6></div><div class="card-body"><p class="mb-2"><strong>Uraian:</strong> <span id="detailKonselingUraianReadonly"></span></p><p class="mb-0"><strong>Hasil:</strong> <span id="detailKonselingHasilReadonly"></span></p></div></div>
                    <?php endif; ?>

                    <div class="mb-4"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Riwayat Tindak Lanjut Konseling</h6><span class="badge bg-label-primary" id="jumlahKonselingTindak">0</span></div><div id="timelineKonselingTindak" class="vstack gap-3"></div></div>

                    <?php if (! empty($initial['can_manage'])): ?>
                    <form id="formKonselingTindak" class="card border mb-0"><div class="card-header"><h6 class="mb-0" id="judulKonselingTindak">Tambah Tindak Lanjut</h6></div><div class="card-body"><input type="hidden" name="id"><input type="hidden" name="id_konseling"><div class="row g-3">
                        <div class="col-md-4"><label for="konselingTindakTanggal" class="form-label">Tanggal</label><input id="konselingTindakTanggal" name="tanggal" type="date" class="form-control" required></div>
                        <div class="col-12"><label for="konselingTindakPerkembangan" class="form-label">Perkembangan</label><textarea id="konselingTindakPerkembangan" name="perkembangan" rows="3" class="form-control" required></textarea></div>
                        <div class="col-12"><label for="konselingTindakHasil" class="form-label">Hasil / Kesepakatan</label><textarea id="konselingTindakHasil" name="hasil_kesepakatan" rows="3" class="form-control"></textarea></div>
                        <div class="col-md-6"><label for="konselingTindakRencana" class="form-label">Rencana Berikutnya</label><select id="konselingTindakRencana" name="rencana_berikutnya" class="form-select" data-searchable-off="1"><option value="">Belum ditentukan</option><?php foreach (($options['rencana']??[]) as $value): ?><option value="<?= esc($value,'attr') ?>"><?= esc($value) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label for="konselingTindakTanggalBerikutnya" class="form-label">Tanggal Berikutnya</label><input id="konselingTindakTanggalBerikutnya" name="tanggal_berikutnya" type="date" class="form-control"></div>
                        <div class="col-md-6"><label for="konselingTindakStatus" class="form-label">Status</label><select id="konselingTindakStatus" name="status" class="form-select" data-searchable-off="1"><option value="Proses">Proses</option><option value="Selesai">Selesai</option></select></div>
                    </div></div><div class="card-footer sisfour-modal-actions"><button type="button" id="btnBatalEditKonselingTindak" class="btn btn-outline-secondary d-none">Batal Edit</button><button type="submit" class="btn btn-primary">Simpan Tindak Lanjut</button></div></form>
                    <?php endif; ?>
                </div>
            </div>
        </div></div></div>

        <script type="application/json" id="konselingTopikData"><?= json_encode($options['topik'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>