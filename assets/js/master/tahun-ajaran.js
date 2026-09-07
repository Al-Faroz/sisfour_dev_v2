(() => {
    'use strict';

    const app = document.getElementById('masterTahunAjaranApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableTahunAjaran');
    const tbody = table.querySelector('tbody');

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modal = new bootstrap.Modal(
        document.getElementById('modalTahunAjaran')
    );

    const form = document.getElementById('formTahunAjaran');
    const modalTitle =
        document.getElementById('modalTahunAjaranTitle');

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
