<?= $this->extend('main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Profile Siswa</h4>
    <p class="text-muted mb-0">
        Profile Siswa bersifat readonly. Perubahan data administratif dilakukan melalui petugas yang berwenang.
    </p>
</div>

<?php if (!empty($profileError)): ?>
    <div class="alert alert-warning">
        <i class="bx bx-error-circle me-1"></i>
        <?= esc($profileError) ?>
    </div>
<?php elseif (!empty($profile)): ?>
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <?php
                    $foto = trim((string) ($profile['foto'] ?? ''));
                    $fotoUrl = $foto !== ''
                        ? base_url('uploads/foto_siswa/' . rawurlencode(basename($foto)))
                        : '';
                    ?>
                    <?php if ($fotoUrl !== ''): ?>
                        <img
                            src="<?= esc($fotoUrl) ?>"
                            alt="Foto <?= esc($profile['nama']) ?>"
                            class="rounded object-fit-cover mb-3"
                            width="180"
                            height="240"
                        >
                    <?php else: ?>
                        <div
                            class="d-inline-flex align-items-center justify-content-center rounded bg-label-secondary mb-3"
                            style="width:180px;height:240px;font-size:64px;"
                        >
                            <i class="bx bx-user"></i>
                        </div>
                    <?php endif; ?>

                    <h5 class="mb-1"><?= esc($profile['nama']) ?></h5>
                    <div class="text-muted">NISN <?= esc($profile['nisn']) ?></div>

                    <?php $kelasAktif = $profile['kelas_aktif'] ?? null; ?>
                    <div class="mt-3">
                        <span class="badge bg-label-primary">
                            <?= esc($kelasAktif['nama_kelas'] ?? 'Belum memiliki kelas aktif') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Biodata</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $items = [
                            'NIK' => $profile['nik'] ?? '-',
                            'NISN' => $profile['nisn'] ?? '-',
                            'Nama' => $profile['nama'] ?? '-',
                            'Jenis Kelamin' => ($profile['jenis_kelamin'] ?? '') === 'L'
                                ? 'Laki-laki'
                                : 'Perempuan',
                            'Tempat Lahir' => $profile['tempat_lahir'] ?? '-',
                            'Tanggal Lahir' => $profile['tanggal_lahir'] ?? '-',
                            'No. Telepon' => $profile['no_telepon'] ?? '-',
                            'Status' => $profile['status_aktif'] ?? '-',
                            'Kebutuhan Khusus' => $profile['kebutuhan_khusus'] ?? '-',
                            'Disabilitas' => $profile['disabilitas'] ?? '-',
                            'Nomor KIP/PIP' => $profile['nomor_kip_pip'] ?? '-',
                            'Nama Ayah Kandung' => $profile['nama_ayah_kandung'] ?? '-',
                            'Nama Ibu Kandung' => $profile['nama_ibu_kandung'] ?? '-',
                            'Nama Wali' => $profile['nama_wali'] ?? '-',
                        ];
                        ?>
                        <?php foreach ($items as $label => $value): ?>
                            <div class="col-md-6">
                                <div class="small text-muted"><?= esc($label) ?></div>
                                <div class="fw-semibold"><?= esc($value ?: '-') ?></div>
                            </div>
                        <?php endforeach; ?>

                        <div class="col-12">
                            <div class="small text-muted">Alamat</div>
                            <div class="fw-semibold">
                                <?= nl2br(esc($profile['alamat'] ?? '-')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Riwayat Kelas</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Tahun</th>
                                <th>Periode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($profile['riwayat_kelas'] ?? []) as $row): ?>
                                <tr>
                                    <td><?= esc($row['nama_kelas']) ?></td>
                                    <td>
                                        <?= esc($row['nama_tahun']) ?>
                                        · <?= esc($row['semester']) ?>
                                    </td>
                                    <td>
                                        <?= esc($row['tanggal_mulai']) ?>
                                        —
                                        <?= esc($row['tanggal_selesai'] ?: 'sekarang') ?>
                                    </td>
                                    <td><?= esc($row['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($profile['riwayat_kelas'])): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada riwayat kelas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
