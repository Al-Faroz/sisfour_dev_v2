(() => {
    'use strict';

    const app = document.getElementById('masterTahunAjaranApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableTahunAjaran');
    const tbody = table.querySelector('tbody');
    const mobileList = document.getElementById('tahunMobileList');

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modal = new bootstrap.Modal(
        document.getElementById('modalTahunAjaran')
    );

    const form = document.getElementById('formTahunAjaran');
    const modalTitle =
        document.getElementById('modalTahunAjaranTitle');

    const semesterSteps = [
        { key: 'precheck', label: 'Validasi data Semester Ganjil' },
        { key: 'create_year', label: 'Membuat Semester Genap' },
        { key: 'copy_classes', label: 'Menyalin struktur Kelas' },
        { key: 'copy_members', label: 'Menyalin Anggota Kelas siswa Aktif' },
        { key: 'copy_wali', label: 'Menyalin Mapping Wali' },
        { key: 'copy_schedule', label: 'Menyalin Jadwal Guru' },
        { key: 'close_history', label: 'Menutup histori Aktif Ganjil' },
        { key: 'open_history', label: 'Membuat histori Aktif Genap' },
        { key: 'deactivate_source', label: 'Menonaktifkan Semester Ganjil' },
        { key: 'activate_target', label: 'Mengaktifkan Semester Genap' },
        { key: 'verify', label: 'Verifikasi akhir' },
    ];

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response
            .json()
            .catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            throw new Error(
                data.message || 'Permintaan tidak dapat diproses.'
            );
        }

        return data;
    };

    const showError = (error) => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: error?.message || 'Terjadi kesalahan.',
        });
    };

    const showSuccess = (message) => Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: message,
        timer: 2200,
        showConfirmButton: false,
    });

    const normalizeSemesterSteps = (steps = []) => {
        const byKey = new Map(
            (Array.isArray(steps) ? steps : []).map(
                (step) => [String(step.key || ''), step]
            )
        );

        return semesterSteps.map((base) => ({
            ...base,
            ...(byKey.get(base.key) || {}),
        }));
    };

    const stepBadge = (status) => {
        const map = {
            success: ['success', '✓', 'Berhasil'],
            rolled_back: ['warning', '↶', 'Rollback'],
            failed: ['danger', '✕', 'Gagal'],
            skipped: ['secondary', '○', 'Tidak dijalankan'],
            pending: ['info', '⟳', 'Menunggu'],
            unknown: ['secondary', '?', 'Tidak diketahui'],
        };

        const item = map[status] || map.pending;

        return `
            <span class="badge bg-label-${item[0]} ms-2">
                ${item[1]} ${item[2]}
            </span>
        `;
    };

    const renderSemesterSteps = (steps, forceStatus = null) => {
        const normalized = normalizeSemesterSteps(steps);

        return `
            <div class="text-start border rounded p-2 mt-2" style="max-height: 390px; overflow:auto;">
                ${normalized.map((step) => {
                    const status = forceStatus || step.status || 'pending';
                    const countText =
                        Number.isFinite(Number(step.count))
                        && Number.isFinite(Number(step.expected))
                            ? `<div class="small text-muted mt-1">${Number(step.count)} / ${Number(step.expected)}</div>`
                            : '';
                    const message = step.message
                        ? `<div class="small text-muted mt-1">${escapeHtml(step.message)}</div>`
                        : '';

                    return `
                        <div class="py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span>${escapeHtml(step.label)}</span>
                                ${stepBadge(status)}
                            </div>
                            ${countText}
                            ${message}
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    };

    const semesterSummaryHtml = (detail) => {
        const source = detail?.source || {};
        const target = detail?.target || {};
        const counts = detail?.counts || {};

        return `
            <div class="text-start mb-3">
                ${source.nama_tahun ? `
                    <div><strong>Tahun Pelajaran:</strong> ${escapeHtml(source.nama_tahun)}</div>
                    <div><strong>Dari:</strong> Ganjil</div>
                    <div><strong>Ke:</strong> Genap</div>
                ` : ''}
                ${target.id ? `<div><strong>ID Semester Genap:</strong> ${Number(target.id)}</div>` : ''}
            </div>
            ${Object.keys(counts).length > 0 ? `
                <div class="text-start small border rounded p-2 mb-3">
                    <div><strong>Hasil verifikasi:</strong></div>
                    <div>Kelas: ${Number(counts.kelas || 0)}</div>
                    <div>Anggota: ${Number(counts.anggota || 0)}</div>
                    <div>Mapping Wali: ${Number(counts.wali || 0)}</div>
                    <div>Jadwal Guru: ${Number(counts.jadwal || 0)}</div>
                    <div>Histori Aktif Ganjil tersisa: ${Number(counts.histori_aktif_ganjil || 0)}</div>
                    <div>Histori Aktif Genap: ${Number(counts.histori_aktif_genap || 0)}</div>
                    <div>Presensi Genap: ${Number(counts.presensi_genap || 0)}</div>
                    <div>Jurnal Genap: ${Number(counts.jurnal_genap || 0)}</div>
                </div>
            ` : ''}
        `;
    };

    const showSemesterResult = async (payload, httpOk = true) => {
        const detail = payload?.data || payload || {};
        const success = httpOk
            && payload?.status !== 'error'
            && detail?.success === true;
        const rolledBack = detail?.rolled_back === true;
        const title = success
            ? 'Semester Genap berhasil diaktifkan'
            : rolledBack
                ? 'Proses gagal — seluruh perubahan di-rollback'
                : 'Siapkan Genap gagal';

        await Swal.fire({
            icon: success ? 'success' : 'error',
            title,
            width: 760,
            html: `
                ${semesterSummaryHtml(detail)}
                <div class="text-start mb-2">
                    ${escapeHtml(detail?.message || payload?.message || 'Proses selesai.')}
                </div>
                ${renderSemesterSteps(detail?.steps || [])}
                <div class="alert ${success ? 'alert-success' : rolledBack ? 'alert-warning' : 'alert-danger'} text-start mt-3 mb-0">
                    ${success
                        ? 'Presensi dan Jurnal Mengajar tidak disalin. Semester Genap sekarang menjadi semester aktif.'
                        : rolledBack
                            ? 'Database dikembalikan ke kondisi sebelum proses. Semester Ganjil tetap menjadi sumber operasional.'
                            : 'Tidak ada pergantian semester yang dikonfirmasi berhasil.'}
                </div>
            `,
            confirmButtonText: 'Tutup',
        });
    };

    const destroyDataTable = () => {
        if (
            dataTable
            && typeof dataTable.destroy === 'function'
        ) {
            dataTable.destroy();
            dataTable = null;
        }
    };

    const initDataTable = () => {
        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(table, {
                pageLength: 25,
                order: [[1, 'desc'], [2, 'asc']],
            });
        }
    };

    const renderRows = () => {
        destroyDataTable();

        tbody.innerHTML = rows.map((tahun, index) => {
            const aktif = Number(tahun.status_aktif) === 1;
            const canPrepareGenap =
                aktif && String(tahun.semester) === 'Ganjil';

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td class="fw-semibold">
                        ${escapeHtml(tahun.nama_tahun)}
                    </td>
                    <td>${escapeHtml(tahun.semester)}</td>
                    <td>
                        ${
                            aktif
                                ? '<span class="badge bg-label-success">Aktif</span>'
                                : '<span class="badge bg-label-secondary">Nonaktif</span>'
                        }
                    </td>
                    <td>${Number(tahun.jumlah_kelas || 0)}</td>
                    <td>${Number(tahun.jumlah_anggota || 0)}</td>
                    <td>${Number(tahun.jumlah_jadwal || 0)}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            ${
                                canPrepareGenap
                                    ? `
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-info btn-prepare-semester"
                                            data-id="${tahun.id}"
                                            title="Siapkan dan aktifkan Semester Genap"
                                        >
                                            <i class="bx bx-copy-alt me-1"></i>
                                            Siapkan Genap
                                        </button>
                                    `
                                    : ''
                            }

                            ${
                                aktif
                                    ? ''
                                    : `
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success btn-aktifkan"
                                            data-id="${tahun.id}"
                                            title="Aktifkan"
                                        >
                                            <i class="bx bx-check-circle"></i>
                                        </button>
                                    `
                            }

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-edit"
                                data-id="${tahun.id}"
                                title="Edit"
                            >
                                <i class="bx bx-edit"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="${tahun.id}"
                                title="Hapus"
                                ${aktif ? 'disabled' : ''}
                            >
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        if (mobileList) {
            mobileList.innerHTML = rows.map((tahun) => {
                const aktif = Number(tahun.status_aktif) === 1;
                const canPrepareGenap = aktif && String(tahun.semester) === 'Ganjil';
                return `<div class="list-group-item py-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold sisfour-wrap-anywhere">${escapeHtml(tahun.nama_tahun)} - ${escapeHtml(tahun.semester)}</div>
                            <div class="small text-muted mt-1">${Number(tahun.jumlah_kelas || 0)} kelas · ${Number(tahun.jumlah_anggota || 0)} anggota · ${Number(tahun.jumlah_jadwal || 0)} jadwal</div>
                        </div>
                        ${aktif ? '<span class="badge bg-label-success flex-shrink-0">Aktif</span>' : '<span class="badge bg-label-secondary flex-shrink-0">Nonaktif</span>'}
                    </div>
                    <div class="sisfour-mobile-actions mt-3">
                        ${canPrepareGenap ? `<button type="button" class="btn btn-sm btn-outline-info sisfour-touch-target--compact btn-mobile-proxy" data-action="prepare" data-id="${tahun.id}">Siapkan Genap</button>` : ''}
                        ${!aktif ? `<button type="button" class="btn btn-sm btn-outline-success sisfour-touch-target--compact btn-mobile-proxy" data-action="activate" data-id="${tahun.id}">Aktifkan</button>` : ''}
                        <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-mobile-proxy" data-action="edit" data-id="${tahun.id}">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-mobile-proxy" data-action="delete" data-id="${tahun.id}" ${aktif ? 'disabled' : ''}>Hapus</button>
                    </div>
                </div>`;
            }).join('') || '<div class="list-group-item sisfour-mobile-state text-muted">Tidak ada tahun ajaran.</div>';
        }

        initDataTable();
    };

    const loadData = async () => {
        try {
            const response = await fetch(
                endpoint('master/tahun/json'),
                {
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const result = await parseResponse(response);

            rows = Array.isArray(result.data)
                ? result.data
                : [];

            renderRows();
        } catch (error) {
            showError(error);
        }
    };

    const resetForm = () => {
        editingId = null;
        form.reset();
        document.getElementById('tahunAjaranId').value = '';
        modalTitle.textContent = 'Tambah Tahun Ajaran';
    };

    document
        .getElementById('btnTambahTahun')
        .addEventListener('click', () => {
            resetForm();
            modal.show();
        });

    tbody.addEventListener('click', async (event) => {
        const edit = event.target.closest('.btn-edit');
        const aktifkan =
            event.target.closest('.btn-aktifkan');
        const hapus = event.target.closest('.btn-delete');
        const prepareSemester =
            event.target.closest('.btn-prepare-semester');

        if (prepareSemester) {
            const id = Number(prepareSemester.dataset.id);
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            if (!tahun) {
                return;
            }

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Siapkan Semester Genap?',
                width: 760,
                html: `
                    <div class="text-start">
                        <p class="mb-2">
                            <strong>${escapeHtml(tahun.nama_tahun)} - Ganjil</strong>
                            akan dipindahkan ke Semester Genap dalam satu transaksi atomic.
                        </p>
                        <p class="mb-2">
                            Jika satu langkah gagal, seluruh perubahan akan di-rollback.
                        </p>
                        ${renderSemesterSteps([], 'pending')}
                        <div class="alert alert-info text-start mt-3 mb-0">
                            Presensi dan Jurnal Mengajar tidak disalin. Setelah berhasil,
                            Semester Ganjil menjadi Nonaktif dan Semester Genap langsung Aktif.
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Siapkan Genap',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Menjalankan Siapkan Genap',
                width: 760,
                html: `
                    <div class="text-start mb-2">
                        Proses sedang dijalankan di server. Jangan refresh atau tutup halaman.
                    </div>
                    ${renderSemesterSteps([], 'pending')}
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            try {
                const payload = new FormData();
                payload.append('mode', 'prepare_next_semester');

                const response = await fetch(
                    endpoint('master/tahun/create'),
                    {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await response
                    .json()
                    .catch(() => ({}));

                await showSemesterResult(result, response.ok);
                await loadData();
            } catch (error) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Status proses tidak dapat dipastikan',
                    width: 760,
                    html: `
                        <div class="text-start mb-3">
                            ${escapeHtml(error?.message || 'Koneksi ke server gagal.')}
                        </div>
                        ${renderSemesterSteps(
                            semesterSteps.map((step) => ({
                                ...step,
                                status: 'unknown',
                                message: 'Status tidak diketahui karena respons server tidak diterima.',
                            }))
                        )}
                        <div class="alert alert-warning text-start mt-3 mb-0">
                            Jangan jalankan ulang proses sebelum status database diperiksa.
                        </div>
                    `,
                    confirmButtonText: 'Tutup',
                });
            }

            return;
        }

        if (edit) {
            const id = Number(edit.dataset.id);
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            if (!tahun) {
                return;
            }

            editingId = id;
            modalTitle.textContent = 'Edit Tahun Ajaran';

            document.getElementById(
                'tahunAjaranId'
            ).value = String(id);

            document.getElementById(
                'nama_tahun'
            ).value = tahun.nama_tahun;

            document.getElementById(
                'semester'
            ).value = tahun.semester;

            modal.show();
            return;
        }

        if (aktifkan) {
            const id = Number(aktifkan.dataset.id);
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'question',
                title: 'Aktifkan tahun ajaran?',
                text: `${tahun?.nama_tahun || ''} - ${tahun?.semester || ''}. Tahun ajaran aktif sebelumnya akan otomatis dinonaktifkan.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, aktifkan',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(
                    endpoint(
                        `master/tahun/aktifkan/${id}`
                    ),
                    {
                        method: 'POST',
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }

            return;
        }

        if (hapus && !hapus.disabled) {
            const id = Number(hapus.dataset.id);
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Pindahkan ke Recycle Bin?',
                text: `${tahun?.nama_tahun || ''} - ${tahun?.semester || ''}`,
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(
                    endpoint(
                        `master/tahun/delete/${id}`
                    ),
                    {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }
        }
    });

    mobileList?.addEventListener('click', (event) => {
        const button = event.target.closest('.btn-mobile-proxy');
        if (!button || button.disabled) return;
        const selectors = { prepare: '.btn-prepare-semester', activate: '.btn-aktifkan', edit: '.btn-edit', delete: '.btn-delete' };
        const selector = selectors[button.dataset.action];
        if (selector) tbody.querySelector(`${selector}[data-id="${button.dataset.id}"]`)?.click();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button =
            document.getElementById('btnSimpanTahun');

        const spinner =
            button.querySelector('.spinner-border');

        button.disabled = true;
        spinner.classList.remove('d-none');

        try {
            if (editingId === null) {
                const response = await fetch(
                    endpoint('master/tahun/create'),
                    {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                modal.hide();
                await showSuccess(result.message);
                await loadData();
            } else {
                const payload = new URLSearchParams();
                const formData = new FormData(form);

                for (const [key, value] of formData.entries()) {
                    if (key !== 'csrf_test_name') {
                        payload.append(key, value);
                    }
                }

                const response = await fetch(
                    endpoint(
                        `master/tahun/update/${editingId}`
                    ),
                    {
                        method: 'PUT',
                        body: payload,
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                modal.hide();
                await showSuccess(result.message);
                await loadData();
            }
        } catch (error) {
            showError(error);
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    loadData();
})();
