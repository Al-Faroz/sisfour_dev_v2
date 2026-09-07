(() => {
    'use strict';

    const app = document.getElementById('mappingWaliApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const canManage = app.dataset.canManage === '1';

    const table = document.getElementById('tableMappingWali');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterWali');

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

    const destroyTable = () => {
        if (
            dataTable
            && typeof dataTable.destroy === 'function'
        ) {
            dataTable.destroy();
            dataTable = null;
        }
    };

    const initTable = () => {
        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(table, {
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[3, 'desc'], [2, 'asc']],
            });
        }
    };

    const renderRows = () => {
        destroyTable();

        tbody.innerHTML = rows.map((row, index) => {
            const tahun = `
                ${escapeHtml(row.nama_tahun)}
                -
                ${escapeHtml(row.semester)}
                ${
                    Number(row.tahun_aktif) === 1
                        ? '<span class="badge bg-label-success ms-1">Aktif</span>'
                        : ''
                }
            `;

            const action = canManage
                ? `
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger btn-nonaktifkan"
                            data-id="${row.id}"
                            title="Nonaktifkan wali"
                        >
                            <i class="bx bx-user-x me-1"></i>
                            Nonaktifkan
                        </button>
                    </td>
                `
                : '';

            return `
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
                    <td>
                        <span class="badge bg-label-primary">
                            ${escapeHtml(row.nama_kelas)}
                        </span>
                    </td>
                    <td>${tahun}</td>
                    <td>
                        <span class="badge bg-label-success">
                            Aktif
                        </span>
                    </td>
                    ${action}
                </tr>
            `;
        }).join('');

        initTable();
    };

    const loadData = async () => {
        try {
            const params = filterParams();
            const suffix = params.toString()
                ? `?${params.toString()}`
                : '';

            const response = await fetch(
                endpoint(
                    `master/wali-kelas/json${suffix}`
                ),
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
        ?.addEventListener('click', () => {
            filterForm.reset();
            loadData();
        });

    if (canManage) {
        const modalElement =
            document.getElementById('modalAssignWali');

        const modal = new bootstrap.Modal(
            modalElement
        );

        const form =
            document.getElementById('formAssignWali');

        const tahun =
            document.getElementById('assignTahun');

        const guru =
            document.getElementById('assignGuru');

        const kelas =
            document.getElementById('assignKelas');

        const resetOptions = () => {
            guru.innerHTML =
                '<option value="">Pilih tahun ajaran terlebih dahulu</option>';
            kelas.innerHTML =
                '<option value="">Pilih tahun ajaran terlebih dahulu</option>';

            guru.disabled = true;
            kelas.disabled = true;
        };

        const loadOptions = async () => {
            const idTahun = Number(tahun.value);

            if (!idTahun) {
                resetOptions();
                return;
            }

            guru.disabled = true;
            kelas.disabled = true;

            try {
                const response = await fetch(
                    endpoint(
                        `master/wali-kelas/options?id_tahun=${encodeURIComponent(idTahun)}`
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

                const data = result.data;

                guru.innerHTML = `
                    <option value="">Pilih guru</option>
                    ${(data.guru || []).map((item) => `
                        <option value="${item.id}">
                            ${escapeHtml(item.nama)}
                            (${escapeHtml(item.nip)})
                        </option>
                    `).join('')}
                `;

                kelas.innerHTML = `
                    <option value="">Pilih kelas</option>
                    ${(data.kelas || []).map((item) => `
                        <option value="${item.id}">
                            ${escapeHtml(item.nama_kelas)}
                        </option>
                    `).join('')}
                `;

                guru.disabled = false;
                kelas.disabled = false;
            } catch (error) {
                resetOptions();
                showError(error);
            }
        };

        document
            .getElementById('btnAssignWali')
            .addEventListener('click', () => {
                form.reset();
                resetOptions();
                modal.show();
            });

        tahun.addEventListener(
            'change',
            loadOptions
        );

        form.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                const button =
                    document.getElementById(
                        'btnSimpanMapping'
                    );

                const spinner =
                    button.querySelector(
                        '.spinner-border'
                    );

                button.disabled = true;
                spinner.classList.remove('d-none');

                try {
                    const response = await fetch(
                        endpoint(
                            'master/wali-kelas/assign'
                        ),
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
                } catch (error) {
                    showError(error);
                } finally {
                    button.disabled = false;
                    spinner.classList.add('d-none');
                }
            }
        );

        tbody.addEventListener(
            'click',
            async (event) => {
                const button =
                    event.target.closest(
                        '.btn-nonaktifkan'
                    );

                if (!button) {
                    return;
                }

                const id = Number(button.dataset.id);
                const row = rows.find(
                    (item) =>
                        Number(item.id) === id
                );

                const confirmation =
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Nonaktifkan wali kelas?',
                        html: `
                            <strong>
                                ${escapeHtml(row?.nama_guru || '')}
                            </strong>
                            <br>
                            Wali ${escapeHtml(row?.nama_kelas || '')}
                            <br><br>
                            Mapping tetap disimpan sebagai histori.
                        `,
                        showCancelButton: true,
                        confirmButtonText:
                            'Ya, nonaktifkan',
                        cancelButtonText: 'Batal',
                    });

                if (!confirmation.isConfirmed) {
                    return;
                }

                try {
                    const response = await fetch(
                        endpoint(
                            `master/wali-kelas/delete/${id}`
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
        );
    }

    loadData();
})();
