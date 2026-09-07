(() => {
    'use strict';

    const app = document.getElementById('masterGuruApp');
    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const canManage = app.dataset.canManage === '1';

    const tableElement = document.getElementById('tableGuru');
    const tbody = tableElement.querySelector('tbody');
    const filterForm = document.getElementById('formFilterGuru');

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modalGuruElement = document.getElementById('modalGuru');
    const modalImportElement = document.getElementById('modalImportGuru');

    const modalGuru = modalGuruElement ? new bootstrap.Modal(modalGuruElement) : null;
    const modalImport = modalImportElement ? new bootstrap.Modal(modalImportElement) : null;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const formParams = () => {
        const params = new URLSearchParams(new FormData(filterForm));
        for (const [key, value] of [...params.entries()]) {
            if (!String(value).trim()) {
                params.delete(key);
            }
        }
        return params;
    };

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || 'Permintaan tidak dapat diproses.');
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

    const showSuccess = async (message) => {
        await Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: message,
            timer: 1600,
            showConfirmButton: false,
        });
    };

    const destroyDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
    };

    const renderRows = () => {
        destroyDataTable();

        tbody.innerHTML = rows.map((guru, index) => {
            const nama = escapeHtml(guru.nama);
            const nip = escapeHtml(guru.nip);
            const jk = guru.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
            const status = guru.status_kepegawaian
                ? `<span class="badge bg-label-primary">${escapeHtml(guru.status_kepegawaian)}</span>`
                : '<span class="text-muted">-</span>';

            const kontak = [
                guru.no_telepon ? `<div>${escapeHtml(guru.no_telepon)}</div>` : '',
                guru.email ? `<small class="text-muted">${escapeHtml(guru.email)}</small>` : '',
            ].join('') || '<span class="text-muted">-</span>';

            const action = canManage
                ? `
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="${guru.id}" title="Edit">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${guru.id}" title="Hapus">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                `
                : '';

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="fw-semibold">${nama}</div>
                        ${guru.tempat_lahir || guru.tanggal_lahir
                            ? `<small class="text-muted">${escapeHtml(guru.tempat_lahir || '')}${guru.tempat_lahir && guru.tanggal_lahir ? ', ' : ''}${escapeHtml(guru.tanggal_lahir || '')}</small>`
                            : ''}
                    </td>
                    <td><span class="font-monospace">${nip}</span></td>
                    <td>${jk}</td>
                    <td>${status}</td>
                    <td>${kontak}</td>
                    ${canManage ? `<td>${action}</td>` : ''}
                </tr>
            `;
        }).join('');

        if (window.jQuery && jQuery.fn.DataTable) {
            dataTable = jQuery(tableElement).DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[1, 'asc']],
                language: {
                    search: 'Cari di tabel:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya',
                    },
                },
            });
        }
    };

    const loadData = async () => {
        try {
            const params = formParams();
            const url = endpoint(`master/guru/json${params.toString() ? `?${params}` : ''}`);
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const result = await parseResponse(response);
            rows = Array.isArray(result.data) ? result.data : [];
            renderRows();
        } catch (error) {
            showError(error);
        }
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadData();
    });

    document.getElementById('btnResetFilter')?.addEventListener('click', () => {
        filterForm.reset();
        loadData();
    });

    document.getElementById('btnExportGuru')?.addEventListener('click', (event) => {
        event.preventDefault();
        const params = formParams();
        window.location.href = endpoint(`master/guru/export${params.toString() ? `?${params}` : ''}`);
    });

    if (canManage) {
        const formGuru = document.getElementById('formGuru');
        const formImport = document.getElementById('formImportGuru');
        const modalTitle = document.getElementById('modalGuruTitle');
        const fotoInput = document.getElementById('foto');

        const resetGuruForm = () => {
            editingId = null;
            formGuru.reset();
            document.getElementById('guruId').value = '';
            modalTitle.textContent = 'Tambah Guru';
            fotoInput.disabled = false;
        };

        document.getElementById('btnTambahGuru')?.addEventListener('click', () => {
            resetGuruForm();
            modalGuru.show();
        });

        document.getElementById('btnImportGuru')?.addEventListener('click', () => {
            formImport.reset();
            modalImport.show();
        });

        tbody.addEventListener('click', async (event) => {
            const editButton = event.target.closest('.btn-edit');
            const deleteButton = event.target.closest('.btn-delete');

            if (editButton) {
                const id = Number(editButton.dataset.id);
                const guru = rows.find((item) => Number(item.id) === id);

                if (!guru) {
                    return;
                }

                editingId = id;
                modalTitle.textContent = 'Edit Guru';

                [
                    'nip',
                    'nama',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'no_telepon',
                    'email',
                    'status_kepegawaian',
                    'alamat',
                ].forEach((field) => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.value = guru[field] ?? '';
                    }
                });

                document.getElementById('guruId').value = String(id);
                fotoInput.value = '';
                modalGuru.show();
                return;
            }

            if (deleteButton) {
                const id = Number(deleteButton.dataset.id);
                const guru = rows.find((item) => Number(item.id) === id);

                const confirmation = await Swal.fire({
                    icon: 'warning',
                    title: 'Pindahkan ke Recycle Bin?',
                    text: guru ? guru.nama : 'Data Guru',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                });

                if (!confirmation.isConfirmed) {
                    return;
                }

                try {
                    const response = await fetch(endpoint(`master/guru/delete/${id}`), {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const result = await parseResponse(response);
                    await showSuccess(result.message);
                    await loadData();
                } catch (error) {
                    showError(error);
                }
            }
        });

        formGuru.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = document.getElementById('btnSimpanGuru');
            const spinner = submitButton.querySelector('.spinner-border');
            submitButton.disabled = true;
            spinner.classList.remove('d-none');

            try {
                if (editingId === null) {
                    const response = await fetch(endpoint('master/guru/create'), {
                        method: 'POST',
                        body: new FormData(formGuru),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const result = await parseResponse(response);
                    modalGuru.hide();
                    await showSuccess(result.message);
                    await loadData();
                } else {
                    const payload = new URLSearchParams();
                    const formData = new FormData(formGuru);

                    for (const [key, value] of formData.entries()) {
                        if (key !== 'foto' && key !== 'csrf_test_name') {
                            payload.append(key, value);
                        }
                    }

                    const response = await fetch(endpoint(`master/guru/update/${editingId}`), {
                        method: 'PUT',
                        body: payload,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const result = await parseResponse(response);

                    const foto = fotoInput.files?.[0];
                    if (foto) {
                        const fotoForm = new FormData();
                        fotoForm.append('foto', foto);

                        const fotoResponse = await fetch(endpoint(`master/guru/upload-foto/${editingId}`), {
                            method: 'POST',
                            body: fotoForm,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });

                        await parseResponse(fotoResponse);
                    }

                    modalGuru.hide();
                    await showSuccess(result.message);
                    await loadData();
                }
            } catch (error) {
                showError(error);
            } finally {
                submitButton.disabled = false;
                spinner.classList.add('d-none');
            }
        });

        formImport.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitButton = formImport.querySelector('button[type="submit"]');
            const spinner = submitButton.querySelector('.spinner-border');
            submitButton.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const response = await fetch(endpoint('master/guru/import'), {
                    method: 'POST',
                    body: new FormData(formImport),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const result = await parseResponse(response);
                modalImport.hide();
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            } finally {
                submitButton.disabled = false;
                spinner.classList.add('d-none');
            }
        });
    }

    loadData();
})();
