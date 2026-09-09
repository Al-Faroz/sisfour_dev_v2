(() => {
    'use strict';

    const app = document.getElementById('masterSiswaApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const canEdit = app.dataset.canEdit === '1';
    const canEditNisn = app.dataset.canEditNisn === '1';
    const canManage = app.dataset.canManage === '1';
    const canImportExport = app.dataset.canImportExport === '1';

    const table = document.getElementById('tableSiswa');
    const tbody = table?.querySelector('tbody') ?? null;
    const filterForm = document.getElementById('formFilterSiswa');

    if (!table || !tbody || !filterForm) {
        console.error(
            'Master Siswa gagal diinisialisasi: tableSiswa atau formFilterSiswa tidak ditemukan.'
        );
        return;
    }

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modalSiswaElement = document.getElementById('modalSiswa');
    const modalSiswa = modalSiswaElement
        ? new bootstrap.Modal(modalSiswaElement)
        : null;

    const modalImportElement = document.getElementById('modalImportSiswa');
    const modalImport = modalImportElement
        ? new bootstrap.Modal(modalImportElement)
        : null;

    const endpoint = (path) =>
        `${baseUrl}/${String(path).replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            const errors = data?.data?.errors;
            const errorText = Array.isArray(errors) && errors.length
                ? `\n\n${errors.slice(0, 20).join('\n')}`
                : '';

            throw new Error(
                (data.message || 'Permintaan tidak dapat diproses.')
                + errorText
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

    const statusBadge = (status) => {
        const map = {
            Aktif: 'success',
            Lulus: 'primary',
            Pindah: 'warning',
            Keluar: 'danger',
        };

        return `
            <span class="badge bg-label-${map[status] || 'secondary'}">
                ${escapeHtml(status)}
            </span>
        `;
    };

    const renderRows = () => {
        destroyDataTable();

        tbody.innerHTML = rows.map((siswa, index) => {
            const kelas = siswa.nama_kelas_aktif
                ? `
                    <span class="badge bg-label-primary">
                        ${escapeHtml(siswa.nama_kelas_aktif)}
                    </span>
                `
                : `
                    <span class="badge bg-label-warning">
                        Belum Ada Kelas
                    </span>
                `;

            const editButton = canEdit
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary btn-edit"
                        data-id="${siswa.id}"
                        title="Edit biodata"
                    >
                        <i class="bx bx-edit"></i>
                    </button>
                `
                : '';

            const deleteButton = canManage
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${siswa.id}"
                        title="Pindahkan ke Recycle Bin"
                    >
                        <i class="bx bx-trash"></i>
                    </button>
                `
                : '';

            const actionCell = canEdit || canManage
                ? `
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            ${editButton}
                            ${deleteButton}
                        </div>
                    </td>
                `
                : '';

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="fw-semibold">
                            ${escapeHtml(siswa.nama)}
                        </div>
                        <div class="small text-muted">
                            ${escapeHtml(siswa.tempat_lahir || '')}
                        </div>
                    </td>
                    <td>
                        <div class="font-monospace">
                            ${escapeHtml(siswa.nik)}
                        </div>
                        <div class="font-monospace text-muted">
                            ${escapeHtml(siswa.nisn)}
                        </div>
                    </td>
                    <td>${kelas}</td>
                    <td>
                        ${siswa.jenis_kelamin === 'L'
                            ? 'Laki-laki'
                            : 'Perempuan'}
                    </td>
                    <td>
                        ${statusBadge(siswa.status_aktif)}
                    </td>
                    <td>
                        ${siswa.no_telepon
                            ? escapeHtml(siswa.no_telepon)
                            : '<span class="text-muted">-</span>'}
                    </td>
                    ${actionCell}
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
                endpoint(`master/siswa/json${suffix}`),
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
        .getElementById('btnExportSiswa')
        ?.addEventListener('click', (event) => {
            event.preventDefault();

            const params = filterParams();
            const suffix = params.toString()
                ? `?${params.toString()}`
                : '';

            window.location.href = endpoint(
                `master/siswa/export${suffix}`
            );
        });

    if (canEdit || canManage) {
        const formSiswa = document.getElementById('formSiswa');
        const modalTitle = document.getElementById(
            'modalSiswaTitle'
        );
        const siswaIdInput = document.getElementById('siswaId');
        const nisnInput = document.getElementById('nisn');
        const fotoInput = document.getElementById('foto');
        const btnSimpan = document.getElementById(
            'btnSimpanSiswa'
        );

        if (
            formSiswa
            && modalTitle
            && siswaIdInput
            && nisnInput
            && btnSimpan
            && modalSiswa
        ) {
            const resetForm = () => {
                editingId = null;
                formSiswa.reset();
                siswaIdInput.value = '';
                modalTitle.textContent = 'Tambah Siswa';
                nisnInput.readOnly = false;
            };

            document
                .getElementById('btnTambahSiswa')
                ?.addEventListener('click', () => {
                    if (!canManage) {
                        showError(
                            new Error(
                                'Anda tidak memiliki hak untuk menambah siswa.'
                            )
                        );
                        return;
                    }

                    resetForm();
                    modalSiswa.show();
                });

            tbody.addEventListener('click', async (event) => {
                const editButton = event.target.closest(
                    '.btn-edit'
                );
                const deleteButton = event.target.closest(
                    '.btn-delete'
                );

                if (editButton) {
                    const id = Number(editButton.dataset.id);
                    const siswa = rows.find(
                        (item) => Number(item.id) === id
                    );

                    if (!siswa) return;

                    editingId = id;
                    formSiswa.reset();
                    modalTitle.textContent =
                        'Edit Biodata Siswa';
                    siswaIdInput.value = String(id);

                    [
                        'nik',
                        'nisn',
                        'nama',
                        'jenis_kelamin',
                        'tempat_lahir',
                        'tanggal_lahir',
                        'alamat',
                        'no_telepon',
                        'kebutuhan_khusus',
                        'disabilitas',
                        'nomor_kip_pip',
                        'nama_ayah_kandung',
                        'nama_ibu_kandung',
                        'nama_wali',
                    ].forEach((field) => {
                        const input =
                            document.getElementById(field);

                        if (input) {
                            input.value =
                                siswa[field] ?? '';
                        }
                    });

                    nisnInput.readOnly = !canEditNisn;

                    if (fotoInput) {
                        fotoInput.value = '';
                    }

                    modalSiswa.show();
                    return;
                }

                if (deleteButton) {
                    const id = Number(
                        deleteButton.dataset.id
                    );
                    const siswa = rows.find(
                        (item) => Number(item.id) === id
                    );

                    const confirmation = await Swal.fire({
                        icon: 'warning',
                        title: 'Pindahkan ke Recycle Bin?',
                        text:
                            `${siswa?.nama || 'Data Siswa'}. `
                            + 'Gunakan menu Mutasi Siswa untuk siswa yang pindah sekolah atau keluar.',
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
                                `master/siswa/delete/${id}`
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

            formSiswa.addEventListener(
                'submit',
                async (event) => {
                    event.preventDefault();

                    const spinner =
                        btnSimpan.querySelector(
                            '.spinner-border'
                        );

                    btnSimpan.disabled = true;
                    spinner?.classList.remove('d-none');

                    try {
                        let response;

                        if (editingId === null) {
                            if (!canManage) {
                                throw new Error(
                                    'Anda tidak memiliki hak untuk menambah siswa.'
                                );
                            }

                            response = await fetch(
                                endpoint(
                                    'master/siswa/create'
                                ),
                                {
                                    method: 'POST',
                                    body: new FormData(
                                        formSiswa
                                    ),
                                    headers: {
                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },
                                    credentials:
                                        'same-origin',
                                }
                            );
                        } else {
                            const payload =
                                new URLSearchParams();

                            for (
                                const [key, value]
                                of new FormData(
                                    formSiswa
                                ).entries()
                            ) {
                                if (
                                    key !== 'foto'
                                    && key
                                        !== 'csrf_test_name'
                                ) {
                                    payload.append(
                                        key,
                                        value
                                    );
                                }
                            }

                            response = await fetch(
                                endpoint(
                                    `master/siswa/update/${editingId}`
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
                                    credentials:
                                        'same-origin',
                                }
                            );
                        }

                        const result =
                            await parseResponse(response);

                        if (
                            editingId !== null
                            && fotoInput?.files?.length
                        ) {
                            const fotoForm =
                                new FormData();

                            fotoForm.append(
                                'foto',
                                fotoInput.files[0]
                            );

                            const fotoResponse =
                                await fetch(
                                    endpoint(
                                        `master/siswa/upload-foto/${editingId}`
                                    ),
                                    {
                                        method: 'POST',
                                        body: fotoForm,
                                        headers: {
                                            'X-Requested-With':
                                                'XMLHttpRequest',
                                        },
                                        credentials:
                                            'same-origin',
                                    }
                                );

                            await parseResponse(
                                fotoResponse
                            );
                        }

                        modalSiswa.hide();
                        await showSuccess(result.message);
                        await loadData();
                    } catch (error) {
                        showError(error);
                    } finally {
                        btnSimpan.disabled = false;
                        spinner?.classList.add('d-none');
                    }
                }
            );
        }
    }

    if (canImportExport) {
        const btnImport = document.getElementById(
            'btnImportSiswa'
        );
        const formImport = document.getElementById(
            'formImportSiswa'
        );

        if (
            btnImport
            && formImport
            && modalImport
        ) {
            btnImport.addEventListener('click', () => {
                formImport.reset();
                modalImport.show();
            });

            formImport.addEventListener(
                'submit',
                async (event) => {
                    event.preventDefault();

                    const submitButton =
                        formImport.querySelector(
                            'button[type="submit"]'
                        );

                    if (submitButton) {
                        submitButton.disabled = true;
                    }

                    try {
                        const response = await fetch(
                            endpoint(
                                'master/siswa/import'
                            ),
                            {
                                method: 'POST',
                                body: new FormData(
                                    formImport
                                ),
                                headers: {
                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },
                                credentials:
                                    'same-origin',
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
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    }
                }
            );
        } else {
            console.error(
                'Import Siswa gagal diinisialisasi: '
                + 'btnImportSiswa/formImportSiswa/modalImportSiswa tidak lengkap.'
            );
        }
    }

    loadData();
})();
