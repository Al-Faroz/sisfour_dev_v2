(() => {
    'use strict';

    const app = document.getElementById('masterMapelApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableMapel');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterMapel');

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modal = new bootstrap.Modal(
        document.getElementById('modalMapel')
    );

    const form = document.getElementById('formMapel');
    const modalTitle =
        document.getElementById('modalMapelTitle');

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
        timer: 1600,
        showConfirmButton: false,
    });

    const filterParams = () => {
        const params = new URLSearchParams(
            new FormData(filterForm)
        );

        for (const [key, value] of [...params.entries()]) {
            if (!String(value).trim()) {
                params.delete(key);
            }
        }

        return params;
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
                lengthMenu: [10, 25, 50, 100],
                order: [[1, 'asc']],
            });
        }
    };

    const renderRows = () => {
        destroyDataTable();

        tbody.innerHTML = rows.map((mapel, index) => {
            const jumlahJadwal =
                Number(mapel.jumlah_jadwal || 0);

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td class="fw-semibold">
                        ${escapeHtml(mapel.nama_mapel)}
                    </td>
                    <td>
                        <span class="badge bg-label-primary font-monospace">
                            ${escapeHtml(mapel.kode_mapel)}
                        </span>
                    </td>
                    <td>
                        ${
                            jumlahJadwal > 0
                                ? `<span class="badge bg-label-warning">${jumlahJadwal} jadwal</span>`
                                : '<span class="badge bg-label-secondary">Belum digunakan</span>'
                        }
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-edit"
                                data-id="${mapel.id}"
                                title="Edit"
                            >
                                <i class="bx bx-edit"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="${mapel.id}"
                                title="Hapus"
                                ${jumlahJadwal > 0 ? 'disabled' : ''}
                            >
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        initDataTable();
    };

    const loadData = async () => {
        try {
            const params = filterParams();
            const suffix = params.toString()
                ? `?${params.toString()}`
                : '';

            const response = await fetch(
                endpoint(`master/mapel/json${suffix}`),
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

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadData();
    });

    document
        .getElementById('btnResetFilter')
        .addEventListener('click', () => {
            filterForm.reset();
            loadData();
        });

    const resetForm = () => {
        editingId = null;
        form.reset();
        document.getElementById('mapelId').value = '';
        modalTitle.textContent =
            'Tambah Mata Pelajaran';
    };

    document
        .getElementById('btnTambahMapel')
        .addEventListener('click', () => {
            resetForm();
            modal.show();
        });

    tbody.addEventListener('click', async (event) => {
        const edit = event.target.closest('.btn-edit');
        const hapus = event.target.closest('.btn-delete');

        if (edit) {
            const id = Number(edit.dataset.id);
            const mapel = rows.find(
                (item) => Number(item.id) === id
            );

            if (!mapel) {
                return;
            }

            editingId = id;
            modalTitle.textContent =
                'Edit Mata Pelajaran';

            document.getElementById(
                'mapelId'
            ).value = String(id);

            document.getElementById(
                'nama_mapel'
            ).value = mapel.nama_mapel;

            document.getElementById(
                'kode_mapel'
            ).value = mapel.kode_mapel;

            modal.show();
            return;
        }

        if (hapus && !hapus.disabled) {
            const id = Number(hapus.dataset.id);
            const mapel = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus mata pelajaran?',
                html: `
                    <strong>
                        ${escapeHtml(mapel?.nama_mapel || '')}
                    </strong>
                    <br>
                    Kode: ${escapeHtml(mapel?.kode_mapel || '')}
                    <br><br>
                    Penghapusan bersifat permanen.
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(
                    endpoint(
                        `master/mapel/delete/${id}`
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

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button =
            document.getElementById('btnSimpanMapel');

        const spinner =
            button.querySelector('.spinner-border');

        button.disabled = true;
        spinner.classList.remove('d-none');

        try {
            if (editingId === null) {
                const response = await fetch(
                    endpoint('master/mapel/create'),
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
                        `master/mapel/update/${editingId}`
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
