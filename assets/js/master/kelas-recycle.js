(() => {
    'use strict';

    const app = document.getElementById('kelasRecycleApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableKelasRecycle');
    const tbody = table.querySelector('tbody');
    const mobileList = document.getElementById('kelasRecycleMobileList');

    let rows = [];
    let dataTable = null;

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            throw new Error(
                data.message || 'Permintaan gagal.'
            );
        }

        return data;
    };

    const showError = (error) => Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error?.message || 'Terjadi kesalahan.',
    });

    const render = () => {
        if (dataTable && typeof dataTable.destroy === 'function') {
            dataTable.destroy();
            dataTable = null;
        }

        tbody.innerHTML = rows.map((kelas, index) => `
            <tr>
                <td>${index + 1}</td>
                <td class="fw-semibold">${escapeHtml(kelas.nama_kelas)}</td>
                <td>${escapeHtml(kelas.tingkat)}</td>
                <td>${escapeHtml(kelas.nama_tahun)} - ${escapeHtml(kelas.semester)}</td>
                <td>${escapeHtml(kelas.deleted_at || '-')}</td>
                <td>
                    <div class="d-flex gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success sisfour-touch-target--compact btn-restore"
                            data-id="${kelas.id}"
                        >
                            <i class="bx bx-revision me-1"></i>
                            Restore
                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-force-delete"
                            data-id="${kelas.id}"
                        >
                            <i class="bx bx-x me-1"></i>
                            Permanen
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        if (mobileList) {
            mobileList.innerHTML = rows.map((kelas) => `<div class="list-group-item py-3">
                <div class="fw-semibold sisfour-wrap-anywhere">${escapeHtml(kelas.nama_kelas)}</div>
                <div class="small text-muted mt-1">Tingkat ${escapeHtml(kelas.tingkat)}</div>
                <div class="small mt-1 sisfour-wrap-anywhere">${escapeHtml(kelas.nama_tahun)} - ${escapeHtml(kelas.semester)} · dihapus ${escapeHtml(kelas.deleted_at || '-')}</div>
                <div class="sisfour-mobile-actions mt-3">
                    <button type="button" class="btn btn-sm btn-outline-success sisfour-touch-target--compact btn-mobile-proxy" data-action="restore" data-id="${kelas.id}">Restore</button>
                    <button type="button" class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-mobile-proxy" data-action="force" data-id="${kelas.id}">Permanen</button>
                </div>
            </div>`).join('') || '<div class="list-group-item sisfour-mobile-state text-muted">Recycle Bin Kelas kosong.</div>';
        }

        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(table, {
                pageLength: 25,
                order: [[3, 'desc'], [1, 'asc']],
            });
        }
    };

    const loadData = async () => {
        try {
            const response = await fetch(
                endpoint('master/kelas/recycle/json'),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const result = await parseResponse(response);
            rows = Array.isArray(result.data) ? result.data : [];
            render();
        } catch (error) {
            showError(error);
        }
    };

    mobileList?.addEventListener('click', (event) => {
        const button = event.target.closest('.btn-mobile-proxy');
        if (!button) return;
        const selector = button.dataset.action === 'restore' ? '.btn-restore' : '.btn-force-delete';
        tbody.querySelector(`${selector}[data-id="${button.dataset.id}"]`)?.click();
    });

    tbody.addEventListener('click', async (event) => {
        const restore = event.target.closest('.btn-restore');
        const forceDelete =
            event.target.closest('.btn-force-delete');

        if (restore) {
            const id = Number(restore.dataset.id);
            const kelas = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'question',
                title: 'Pulihkan Kelas?',
                text: kelas?.nama_kelas || 'Data Kelas',
                showCancelButton: true,
                confirmButtonText: 'Pulihkan',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(
                    endpoint(`master/kelas/restore/${id}`),
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

                await Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: result.message,
                });

                await loadData();
            } catch (error) {
                showError(error);
            }

            return;
        }

        if (forceDelete) {
            const id = Number(forceDelete.dataset.id);
            const kelas = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus permanen?',
                html: `
                    <strong>
                        ${escapeHtml(kelas?.nama_kelas || 'Data Kelas')}
                    </strong>
                    <br>
                    Data tidak dapat dipulihkan kembali.
                `,
                showCancelButton: true,
                confirmButtonText: 'Hapus permanen',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });

            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(
                    endpoint(
                        `master/kelas/force-delete/${id}`
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

                await Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: result.message,
                });

                await loadData();
            } catch (error) {
                showError(error);
            }
        }
    });

    loadData();
})();
