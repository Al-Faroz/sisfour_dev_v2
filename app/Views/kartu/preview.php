<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<style>
.student-card-canvas{width:min(100%,1011px);aspect-ratio:1011/638;border-radius:24px;padding:38px;background:linear-gradient(135deg,#f7f8ff,#ffffff);border:1px solid #ddd;position:relative;overflow:hidden}
.student-card-grid{display:grid;grid-template-columns:180px 1fr 220px;gap:28px;align-items:center;height:100%}
.student-photo{width:170px;height:220px;object-fit:cover;border-radius:16px;border:1px solid #ccc;background:#eee}
.qr-box img{width:200px;height:200px}
@media(max-width:800px){.student-card-grid{grid-template-columns:1fr;text-align:center}.student-card-canvas{aspect-ratio:auto}.student-photo{margin:auto}.qr-box img{width:160px;height:160px}}
</style>
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="fw-bold mb-0">Preview Kartu Pelajar</h4><a class="btn btn-primary" href="<?= esc(base_url('kartu/download/' . $card['id'])) ?>">Download PDF</a></div>
<div class="student-card-canvas">
    <div class="student-card-grid">
        <div>
            <?php if ($photo_data_uri): ?><img class="student-photo" src="<?= esc($photo_data_uri) ?>" alt="Foto"><?php else: ?><div class="student-photo d-flex align-items-center justify-content-center text-muted">Tidak ada foto</div><?php endif; ?>
        </div>
        <div>
            <div class="text-uppercase text-muted small">Kartu Pelajar</div>
            <h2 class="mt-1 mb-3"><?= esc($card['nama']) ?></h2>
            <div><strong>NISN:</strong> <?= esc($card['nisn']) ?></div>
            <div><strong>Kelas:</strong> <?= esc($card['kelas']['nama_kelas'] ?? '-') ?></div>
            <div><strong>Jenis Kelamin:</strong> <?= esc($card['jenis_kelamin']) ?></div>
            <div><strong>TTL:</strong> <?= esc(($card['tempat_lahir'] ?? '-') . ', ' . ($card['tanggal_lahir'] ?? '-')) ?></div>
            <div class="mt-3"><strong>No. Kartu:</strong> <?= esc($card['nomor_kartu']) ?></div>
            <div><strong>Status:</strong> <?= esc($card['status_aktif']) ?></div>
        </div>
        <div class="qr-box text-center">
            <img src="<?= esc($qr_data_uri) ?>" alt="QR">
            <div class="small text-muted mt-2">Scan untuk verifikasi</div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
