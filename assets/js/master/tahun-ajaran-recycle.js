(() => {
    'use strict';

    const app =
        document.getElementById('tahunAjaranRecycleApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table =
        document.getElementById('tableTahunAjaranRecycle');
    const tbody = table.querySelector('tbody');

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
        const data = await response
            .json()
            .catch(() => ({}));

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
        if (
            dataTable
            && typeof dataTable.destroy === 'function'
        ) {
            dataTable.destroy();
            dataTable = null;
        }

        tbody.innerHTML = rows.map((tahun, index) => `
            <tr>
                <td>${index + 1}</td>
                <td class="fw-semibold">
                    ${escapeHtml(tahun.nama_tahun)}
                </td>
                <td>${escapeHtml(tahun.semester)}</td>
                <td>${escapeHtml(tahun.deleted_at || '-')}</td>
                <td>
                    <div class="d-flex gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success btn-restore"
                            data-id="${tahun.id}"
                        >
                            <i class="bx bx-revision me-1"></i>
                            Restore
                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger btn-force-delete"
                            data-id="${tahun.id}"
                        >
                            <i class="bx bx-x me-1"></i>
                            Permanen
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(table, {
                pageLength: 25,
                order: [[1, 'desc'], [2, 'asc']],
            });
        }
    };

    const loadData = async () => {
        try {
            const response = await fetch(
                endpoint('master/tahun/recycle/json'),
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

            render();
        } catch (error) {
            showError(error);
        }
    };

    tbody.addEventListener('click', async (event) => {
        const restore =
            event.target.closest('.btn-restore');

        const forceDelete =
            event.target.closest('.btn-force-delete');

        if (restore) {
            const id = Number(restore.dataset.id);
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'question',
                title: 'Pulihkan tahun ajaran?',
                text: `${tahun?.nama_tahun || ''} - ${tahun?.semester || ''}`,
                showCancelButton: true,
                confirmButtonText: 'Pulihkan',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(
                    endpoint(
                        `master/tahun/restore/${id}`
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
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus permanen?',
                html: `
                    <strong>
                        ${escapeHtml(tahun?.nama_tahun || '')}
                        -
                        ${escapeHtml(tahun?.semester || '')}
                    </strong>
                    <br>
                    Data tidak dapat dipulihkan kembali.
                `,
                showCancelButton: true,
                confirmButtonText: 'Hapus permanen',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(
                    endpoint(
                        `master/tahun/force-delete/${id}`
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
