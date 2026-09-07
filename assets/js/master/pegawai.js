(() => {
    'use strict';

    const app = document.getElementById('masterPegawaiApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const canManage = app.dataset.canManage === '1';

    const table = document.getElementById('tablePegawai');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterPegawai');

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modalPegawaiElement =
        document.getElementById('modalPegawai');

    const modalImportElement =
        document.getElementById('modalImportPegawai');

    const modalPegawai = modalPegawaiElement
        ? new bootstrap.Modal(modalPegawaiElement)
        : null;

    const modalImport = modalImportElement
        ? new bootstrap.Modal(modalImportElement)
        : null;

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const getFilterParams = () => {
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
        timer: 1700,
        showConfirmButton: false,
    });

    const destroyDataTable = () => {
        if (dataTable && typeof dataTable.destroy === 'function') {
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

        tbody.innerHTML = rows
            .map((pegawai, index) => {
                const jk = pegawai.jenis_kelamin === 'L'
                    ? 'Laki-laki'
                    : 'Perempuan';

                const role = pegawai.role_user
                    ? `<span class="badge bg-label-primary">${escapeHtml(pegawai.role_user)}</span>`
                    : '<span class="badge bg-label-warning">Belum ditetapkan</span>';

                const kontak = [
                    pegawai.no_telepon
                        ? `<div>${escapeHtml(pegawai.no_telepon)}</div>`
                        : '',
                    pegawai.email
                        ? `<small class="text-muted">${escapeHtml(pegawai.email)}</small>`
                        : '',
                ].join('') || '<span class="text-muted">-</span>';

                const aksi = canManage
                    ? `
                        <div class="d-flex gap-1">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-edit"
                                data-id="${pegawai.id}"
                                title="Edit"
                            >
                                <i class="bx bx-edit"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="${pegawai.id}"
                                title="Hapus"
                            >
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    `
                    : '';

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <div class="fw-semibold">
                                ${escapeHtml(pegawai.nama)}
                            </div>
                            ${
                                pegawai.tempat_lahir
                                || pegawai.tanggal_lahir
                                    ? `
                                        <small class="text-muted">
                                            ${escapeHtml(pegawai.tempat_lahir || '')}
                                            ${
                                                pegawai.tempat_lahir
                                                && pegawai.tanggal_lahir
                                                    ? ', '
                                                    : ''
                                            }
                                            ${escapeHtml(pegawai.tanggal_lahir || '')}
                                        </small>
                                    `
                                    : ''
                            }
                        </td>
                        <td>
                            <span class="font-monospace">
                                ${escapeHtml(pegawai.nip)}
                            </span>
                        </td>
                        <td>${jk}</td>
                        <td>${escapeHtml(pegawai.jabatan || '-')}</td>
                        <td>${role}</td>
                        <td>${kontak}</td>
                        ${canManage ? `<td>${aksi}</td>` : ''}
                    </tr>
                `;
            })
            .join('');

        initDataTable();
    };

    const loadData = async () => {
        try {
            const params = getFilterParams();
            const suffix = params.toString()
                ? `?${params.toString()}`
                : '';

            const response = await fetch(
                endpoint(`master/pegawai/json${suffix}`),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
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
        ?.addEventListener('click', () => {
            filterForm.reset();
            loadData();
        });

    document
        .getElementById('btnExportPegawai')
        ?.addEventListener('click', (event) => {
            event.preventDefault();

            const params = getFilterParams();
            const suffix = params.toString()
                ? `?${params.toString()}`
                : '';

            window.location.href = endpoint(
                `master/pegawai/export${suffix}`
            );
        });

    if (canManage) {
        const form = document.getElementById('formPegawai');
        const importForm =
            document.getElementById('formImportPegawai');

        const modalTitle =
            document.getElementById('modalPegawaiTitle');

        const resetForm = () => {
            editingId = null;
            form.reset();
            document.getElementById('pegawaiId').value = '';
            modalTitle.textContent = 'Tambah Pegawai';
        };

        document
            .getElementById('btnTambahPegawai')
            ?.addEventListener('click', () => {
                resetForm();
                modalPegawai.show();
            });

        document
            .getElementById('btnImportPegawai')
            ?.addEventListener('click', () => {
                importForm.reset();
                modalImport.show();
            });

        tbody.addEventListener('click', async (event) => {
            const editButton =
                event.target.closest('.btn-edit');

            const deleteButton =
                event.target.closest('.btn-delete');

            if (editButton) {
                const id = Number(editButton.dataset.id);
                const pegawai = rows.find(
                    (item) => Number(item.id) === id
                );

                if (!pegawai) {
                    return;
                }

                editingId = id;
                modalTitle.textContent = 'Edit Pegawai';

                [
                    'nip',
                    'nama',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'jabatan',
                    'no_telepon',
                    'email',
                    'alamat',
                ].forEach((field) => {
                    const input = document.getElementById(field);

                    if (input) {
                        input.value = pegawai[field] ?? '';
                    }
                });

                document.getElementById('pegawaiId').value =
                    String(id);

                modalPegawai.show();
                return;
            }

            if (deleteButton) {
                const id = Number(deleteButton.dataset.id);
                const pegawai = rows.find(
                    (item) => Number(item.id) === id
                );

                const confirmation = await Swal.fire({
                    icon: 'warning',
                    title: 'Pindahkan ke Recycle Bin?',
                    text: pegawai?.nama || 'Data Pegawai',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                });

                if (!confirmation.isConfirmed) {
                    return;
                }

                try {
                    const response = await fetch(
                        endpoint(`master/pegawai/delete/${id}`),
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
                document.getElementById('btnSimpanPegawai');

            const spinner =
                button.querySelector('.spinner-border');

            button.disabled = true;
            spinner.classList.remove('d-none');

            try {
                if (editingId === null) {
                    const response = await fetch(
                        endpoint('master/pegawai/create'),
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

                    modalPegawai.hide();
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
                            `master/pegawai/update/${editingId}`
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

                    modalPegawai.hide();
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

        importForm.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                const button =
                    importForm.querySelector(
                        'button[type="submit"]'
                    );

                const spinner =
                    button.querySelector('.spinner-border');

                button.disabled = true;
                spinner.classList.remove('d-none');

                try {
                    const response = await fetch(
                        endpoint('master/pegawai/import'),
                        {
                            method: 'POST',
                            body: new FormData(importForm),
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        }
                    );

                    const result =
                        await parseResponse(response);

                    modalImport.hide();
                    await showSuccess(result.message);
                    await loadData();
                } catch (error) {
                    showError(error);
                } finally {
                    button.disabled = false;
                    spinner.classList.add('d-none');
                }
            }
        );
    }

    loadData();
})();
