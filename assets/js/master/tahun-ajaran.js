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
        timer: 2200,
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
            const canPrepareGenap =
                aktif && String(tahun.semester) === 'Ganjil';

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
                                canPrepareGenap
                                    ? `
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-info btn-prepare-semester"
                                            data-id="${tahun.id}"
                                            title="Siapkan Semester Genap"
                                        >
                                            <i class="bx bx-copy-alt me-1"></i>
                                            Siapkan Genap
                                        </button>
                                    `
                                    : ''
                            }

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
        const prepareSemester =
            event.target.closest('.btn-prepare-semester');

        if (prepareSemester) {
            const id = Number(prepareSemester.dataset.id);
            const tahun = rows.find(
                (item) => Number(item.id) === id
            );

            if (!tahun) {
                return;
            }

            const confirmation = await Swal.fire({
                icon: 'info',
                title: 'Siapkan Semester Genap?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">
                            Sumber: <strong>${escapeHtml(tahun.nama_tahun)} - Ganjil</strong>
                        </p>
                        <p class="mb-2">
                            Sistem akan membuat konteks <strong>${escapeHtml(tahun.nama_tahun)} - Genap</strong>
                            dalam status Nonaktif, lalu menyalin kelas dan membership siswa aktif.
                        </p>
                        <p class="mb-0 text-muted">
                            Presensi/Jurnal tidak disalin. Jadwal Guru Genap harus diimport atau direview sebelum aktivasi.
                        </p>
                    </div>
                `,
                input: 'checkbox',
                inputValue: 1,
                inputPlaceholder:
                    'Salin Mapping Wali Kelas dari Semester Ganjil',
                showCancelButton: true,
                confirmButtonText: 'Siapkan Genap',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const payload = new FormData();
                payload.append('mode', 'prepare_next_semester');
                payload.append(
                    'copy_wali',
                    confirmation.value ? '1' : '0'
                );

                const response = await fetch(
                    endpoint('master/tahun/create'),
                    {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await parseResponse(response);

                await Swal.fire({
                    icon: 'success',
                    title: 'Semester Genap disiapkan',
                    text: result.message,
                });

                await loadData();
            } catch (error) {
                showError(error);
            }

            return;
        }

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

            const isGenap =
                String(tahun?.semester || '') === 'Genap';

            const confirmation = await Swal.fire({
                icon: 'question',
                title: isGenap
                    ? 'Aktifkan Semester Genap?'
                    : 'Aktifkan tahun ajaran?',
                html: isGenap
                    ? `
                        <div class="text-start">
                            <p>
                                <strong>${escapeHtml(tahun?.nama_tahun || '')} - Genap</strong>
                            </p>
                            <p class="mb-0 text-muted">
                                Sistem akan melakukan precheck kelas, membership, Mapping Wali,
                                histori siswa, dan topology Jadwal. Aktivasi ditolak jika belum siap.
                            </p>
                        </div>
                    `
                    : undefined,
                text: isGenap
                    ? undefined
                    : `${tahun?.nama_tahun || ''} - ${tahun?.semester || ''}. Tahun ajaran aktif sebelumnya akan otomatis dinonaktifkan.`,
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
