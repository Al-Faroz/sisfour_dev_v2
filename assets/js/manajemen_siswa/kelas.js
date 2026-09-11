(() => {
    'use strict';

    const app = document.getElementById('manajemenKelasSiswaApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableKelasSiswa');
    const tbody = table?.querySelector('tbody');
    const filter = document.getElementById('formFilterKelasSiswa');
    const form = document.getElementById('formAturKelas');
    const modalElement = document.getElementById('modalAturKelas');
    const modal = modalElement
        ? bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    let rows = [];

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    if (!table || !tbody || !filter || !form || !modal) {
        return;
    }

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const node = document.createElement('div');
        node.textContent = value ?? '';

        return node.innerHTML;
    };

    const parseResponse = async (response) => {
        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.status === 'error') {
            throw new Error(
                payload.message || 'Permintaan gagal.'
            );
        }

        return payload;
    };

    const showError = (message) => {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: message,
            });
        }

        window.alert(message);
        return Promise.resolve();
    };

    const showSuccess = (message) => {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: message,
                timer: 1500,
                showConfirmButton: false,
            });
        }

        window.alert(message);
        return Promise.resolve();
    };

    const pager = window.SisfourPagination?.mount(
        table,
        {
            id: 'manajemenKelasSiswaPager',
            label: 'siswa',
            onChange: (next) => {
                state.limit = next.limit;
                state.offset = next.offset;
                load();
            },
        }
    );

    const render = () => {
        tbody.innerHTML = rows.map((row, index) => {
            const hasClass = Number(row.id_kelas || 0) > 0;

            return `
                <tr>
                    <td>${state.offset + index + 1}</td>
                    <td class="fw-semibold">${escapeHtml(row.nama)}</td>
                    <td class="font-monospace">${escapeHtml(row.nisn)}</td>
                    <td class="font-monospace">${escapeHtml(row.nik)}</td>
                    <td>${row.jenis_kelamin === 'L' ? 'L' : 'P'}</td>
                    <td>
                        ${hasClass
                            ? `<span class="badge bg-label-primary">${escapeHtml(row.nama_kelas)}</span>`
                            : '<span class="badge bg-label-warning">Belum Ada Kelas</span>'}
                    </td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-atur"
                            data-id="${row.id}"
                        >
                            ${hasClass ? 'Pindah Kelas' : 'Tempatkan'}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Tidak ada data siswa.
                    </td>
                </tr>
            `;
        }
    };

    const queryParams = () => {
        const params = new URLSearchParams(
            new FormData(filter)
        );

        [...params.entries()].forEach(([key, value]) => {
            if (!String(value).trim()) {
                params.delete(key);
            }
        });

        params.set('limit', String(state.limit));
        params.set('offset', String(state.offset));

        return params;
    };

    async function load() {
        pager?.setDisabled(true);

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat data...
                </td>
            </tr>
        `;

        try {
            const response = await fetch(
                endpoint(
                    `manajemen-siswa/kelas/json?${queryParams()}`
                ),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const payload = await parseResponse(response);
            const data = payload.data || {};

            rows = Array.isArray(data.rows) ? data.rows : [];
            state.total = Number(data.total || 0);
            state.limit = Number(data.limit || state.limit);
            state.offset = Number(data.offset || 0);

            render();
            pager?.render(state);
        } catch (error) {
            rows = [];
            render();
            pager?.render({
                total: 0,
                limit: state.limit,
                offset: 0,
            });

            await showError(
                error.message || 'Data gagal dimuat.'
            );
        } finally {
            pager?.setDisabled(false);
        }
    }

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('.btn-atur');

        if (!button) {
            return;
        }

        const id = Number(button.dataset.id);
        const row = rows.find(
            (item) => Number(item.id) === id
        );

        if (!row) {
            return;
        }

        document.getElementById('idSiswaKelas').value = id;
        document.getElementById('namaSiswaKelas').value =
            row.nama ?? '';
        document.getElementById('kelasSaatIni').value =
            row.nama_kelas || 'Belum Ada Kelas';
        document.getElementById('idKelasTujuan').value = '';

        modal.show();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const id = Number(
            document.getElementById('idSiswaKelas').value
        );

        try {
            const response = await fetch(
                endpoint(
                    `manajemen-siswa/kelas/set/${id}`
                ),
                {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const payload = await parseResponse(response);

            modal.hide();
            await showSuccess(payload.message);
            await load();
        } catch (error) {
            await showError(
                error.message || 'Kelas gagal diperbarui.'
            );
        }
    });

    filter.addEventListener('submit', (event) => {
        event.preventDefault();
        state.offset = 0;
        load();
    });

    document
        .getElementById('btnResetFilter')
        ?.addEventListener('click', () => {
            filter.reset();
            state.offset = 0;
            load();
        });

    load();
})();
