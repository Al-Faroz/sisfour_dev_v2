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
                            class="btn btn-sm btn-outline-success btn-restore"
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
