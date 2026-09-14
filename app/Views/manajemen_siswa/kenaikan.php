<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div id="kenaikanSiswaApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Kenaikan Kelas</h4>
            <p class="text-muted mb-0">Proses siswa tingkat 7 dan 8 menuju kelas pada tahun ajaran berikutnya.</p>
        </div>
    </div>

    <?php if ($tahunAktif): ?>
        <div class="alert alert-info sisfour-compact-note">
            Tahun ajaran aktif:
            <strong><?= esc($tahunAktif['nama_tahun'].' - '.$tahunAktif['semester']) ?></strong>
        </div>
    <?php endif; ?>

    <div class="card sisfour-filter-card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="filterTingkatKenaikan">Tingkat</label>
                    <select class="form-select" id="filterTingkatKenaikan" data-searchable-off="1">
                        <option value="">Semua Tingkat</option>
                        <option value="7">Tingkat 7</option>
                        <option value="8">Tingkat 8</option>
                        <option value="9">Tingkat 9</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card sisfour-table-card">
        <div class="card-header"><h5 class="mb-0">Pilih Kelas Asal</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableKenaikanKelas">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th>Jumlah Siswa</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sourceClasses as $kelas): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($kelas['nama_kelas']) ?></td>
                            <td><?= esc($kelas['tingkat']) ?></td>
                            <td><?= (int)$kelas['jumlah_siswa'] ?> siswa</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary btn-proses-naik"
                                    data-id="<?= (int)$kelas['id'] ?>"
                                    data-nama="<?= esc($kelas['nama_kelas']) ?>"
                                >
                                    Proses
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($sourceClasses === []): ?>
                        <tr class="sisfour-empty-row"><td colspan="4" class="text-muted">Tidak ada kelas tingkat 7/8 pada tahun ajaran aktif.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalKenaikan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="formKenaikan">
                    <?= csrf_field() ?>
                    <input type="hidden" id="idKelasAsal">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Proses Kenaikan Kelas</h5>
                            <div class="small text-muted" id="labelKelasAsal"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label" for="kelasTujuanNaik">Kelas Tujuan</label>
                                <select class="form-select" id="kelasTujuanNaik" name="id_kelas_tujuan" required>
                                    <option value="">Pilih kelas tujuan</option>
                                </select>
                                <input type="hidden" id="idTahunBaru" name="id_tahun_baru">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Dipilih</label>
                                <div class="form-control bg-light" id="jumlahNaikDipilih">0 siswa</div>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                            <strong>Checklist Siswa</strong>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnPilihSemuaNaik">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnKosongkanNaik">Kosongkan</button>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-hover mb-0">
                                <thead><tr><th style="width:50px;"></th><th>Nama</th><th>NISN</th><th>JK</th></tr></thead>
                                <tbody id="tbodyNaik"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer sisfour-modal-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Proses Kenaikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>