(() => {
    'use strict';

    const app =
        document.getElementById(
            'mappingWaliRecycleApp'
        );

    if (!app) {
        return;
    }

    const baseUrl =
        app.dataset.baseUrl.replace(/\/+$/, '');

    const table =
        document.getElementById(
            'tableMappingWaliRecycle'
        );

    const tbody = table.querySelector('tbody');
    const mobileList = document.getElementById('mappingWaliRecycleMobileList');

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

    const showError = (error) => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: error?.message || 'Terjadi kesalahan.',
        });
    };

    const render = () => {
        if (
            dataTable
            && typeof dataTable.destroy === 'function'
        ) {
            dataTable.destroy();
            dataTable = null;
        }

        tbody.innerHTML = rows.map((row, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <div class="fw-semibold">
                        ${escapeHtml(row.nama_guru)}
                    </div>
                    <small class="text-muted font-monospace">
                        ${escapeHtml(row.nip)}
                    </small>
                </td>
                <td>${escapeHtml(row.nama_kelas)}</td>
                <td>
                    ${escapeHtml(row.nama_tahun)}
                    -
                    ${escapeHtml(row.semester)}
                </td>
                <td>${escapeHtml(row.deleted_at || '-')}</td>
                <td>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success sisfour-touch-target--compact btn-restore"
                            data-id="${row.id}"
                        >
                            <i class="bx bx-revision me-1"></i>
                            Restore
                        </button>

                        <span
                            class="badge text-bg-light border"
                            title="Histori Mapping Wali Kelas dipertahankan untuk integritas data akademik."
                        >
                            <i class="bx bx-lock-alt me-1"></i>
                            Histori dilindungi
                        </span>
                    </div>
                </td>
            </tr>
        `).join('');

        if (mobileList) {
            mobileList.innerHTML = rows.map((row) => `<div class="list-group-item py-3">
                <div class="fw-semibold sisfour-wrap-anywhere">${escapeHtml(row.nama_guru)}</div>
                <div class="small text-muted font-monospace sisfour-wrap-anywhere">${escapeHtml(row.nip)}</div>
                <div class="small mt-2 sisfour-wrap-anywhere">${escapeHtml(row.nama_kelas)} · ${escapeHtml(row.nama_tahun)} - ${escapeHtml(row.semester)}</div>
                <div class="small text-muted mt-1">Dinonaktifkan ${escapeHtml(row.deleted_at || '-')}</div>
                <div class="sisfour-mobile-actions mt-3">
                    <button type="button" class="btn btn-sm btn-outline-success sisfour-touch-target--compact btn-mobile-proxy" data-id="${row.id}">Restore</button>
                    <span class="badge text-bg-light border">Histori dilindungi</span>
                </div>
            </div>`).join('') || '<div class="list-group-item sisfour-mobile-state text-muted">Histori wali kosong.</div>';
        }

        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(
                table,
                {
                    pageLength: 25,
                    order: [[4, 'desc']],
                }
            );
        }
    };

    const loadData = async () => {
        try {
            const response = await fetch(
                endpoint(
                    'master/wali-kelas/recycle/json'
                ),
                {
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const result =
                await parseResponse(response);

            rows = Array.isArray(result.data)
                ? result.data
                : [];

            render();
        } catch (error) {
            showError(error);
        }
    };

    mobileList?.addEventListener('click', (event) => {
        const button = event.target.closest('.btn-mobile-proxy');
        if (!button) return;
        tbody.querySelector(`.btn-restore[data-id="${button.dataset.id}"]`)?.click();
    });

    tbody.addEventListener(
        'click',
        async (event) => {
            const restore =
                event.target.closest('.btn-restore');

            if (!restore) {
                return;
            }

            const id = Number(restore.dataset.id);

            const row = rows.find(
                (item) =>
                    Number(item.id) === id
            );

            const confirmation =
                await Swal.fire({
                    icon: 'question',
                    title: 'Restore mapping?',
                    html: `
                        <strong>
                            ${escapeHtml(row?.nama_guru || '')}
                        </strong>
                        <br>
                        kembali menjadi wali
                        ${escapeHtml(row?.nama_kelas || '')}.
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Restore',
                    cancelButtonText: 'Batal',
                });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(
                    endpoint(
                        `master/wali-kelas/restore/${id}`
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
        }
    );

    loadData();
})();
