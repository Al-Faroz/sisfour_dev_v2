(() => {
    'use strict';

    const app = document.getElementById('masterJadwalApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const canManage = app.dataset.canManage === '1';

    const table = document.getElementById('tableJadwal');
    const tbody = table?.querySelector('tbody');
    const filterForm = document.getElementById('formFilterJadwal');
    const filterGuru = document.getElementById('filterGuru');
    const filterKelas = document.getElementById('filterKelas');

    if (!table || !tbody || !filterForm || !filterGuru || !filterKelas) {
        return;
    }

    let rows = [];

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const ensurePager = () => {
        let container = document.getElementById('jadwalPager');

        if (container) {
            return container;
        }

        container = document.createElement('div');
        container.id = 'jadwalPager';
        container.className = 'card-footer';
        table.closest('.card')?.appendChild(container);

        return container;
    };

    const pager = window.SisfourPagination?.create(
        ensurePager(),
        {
            label: 'jadwal',
            onChange: (next) => {
                state.limit = next.limit;
                state.offset = next.offset;
                loadData();
            },
        }
    );

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            throw new Error(
                data.message || 'Permintaan tidak dapat diproses.'
            );
        }

        return data;
    };

    const showError = (error) => Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error?.message || 'Terjadi kesalahan.',
    });

    const showSuccess = (message) => Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: message,
        timer: 1700,
        showConfirmButton: false,
    });

    const filterParams = (withPaging = true) => {
        const params = new URLSearchParams(
            new FormData(filterForm)
        );

        for (const [key, value] of [...params.entries()]) {
            if (!String(value).trim()) {
                params.delete(key);
            }
        }

        if (withPaging) {
            params.set('limit', String(state.limit));
            params.set('offset', String(state.offset));
        }

        return params;
    };

    const syncUrl = () => {
        const params = filterParams(true);
        const query = params.toString();

        window.history.replaceState(
            null,
            '',
            `${window.location.pathname}${query ? `?${query}` : ''}`
        );
    };

    const restoreState = () => {
        const params = new URLSearchParams(window.location.search);
        const limit = Number(params.get('limit') || 25);
        const offset = Number(params.get('offset') || 0);

        state.limit = [25, 50, 100].includes(limit) ? limit : 25;
        state.offset = Number.isFinite(offset) && offset >= 0
            ? offset
            : 0;

        ['id_guru', 'id_kelas', 'id_tahun', 'hari', 'status_jadwal']
            .forEach((name) => {
                const value = params.get(name);

                if (
                    value !== null
                    && filterForm.elements[name]
                ) {
                    filterForm.elements[name].value = value;
                }
            });
    };

    const renderRows = () => {
        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${canManage ? 10 : 9}" class="text-center text-muted py-4">
                        Tidak ada jadwal pada filter ini.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map((row, index) => {
            const statusClass =
                row.status_jadwal === 'Aktif'
                    ? 'success'
                    : 'secondary';

            const identifier = String(row.nip || row.nik || '').trim();

            const action = canManage
                ? `
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger btn-delete-jadwal"
                            data-id="${row.id}"
                            title="Hapus jadwal"
                        >
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                `
                : '';

            return `
                <tr>
                    <td>${state.offset + index + 1}</td>
                    <td>
                        <div class="fw-semibold">
                            ${escapeHtml(row.nama_guru)}
                        </div>
                        <small class="text-muted font-monospace">
                            ${escapeHtml(identifier || '-')}
                        </small>
                    </td>
                    <td>${escapeHtml(row.nama_kelas)}</td>
                    <td>
                        <div>${escapeHtml(row.nama_mapel)}</div>
                        <small class="text-muted">
                            ${escapeHtml(row.kode_mapel)}
                        </small>
                    </td>
                    <td>${escapeHtml(row.hari)}</td>
                    <td class="font-monospace">
                        ${escapeHtml(String(row.jam_mulai).slice(0, 5))}
                        -
                        ${escapeHtml(String(row.jam_selesai).slice(0, 5))}
                    </td>
                    <td>${escapeHtml(row.sesi)}</td>
                    <td>
                        ${escapeHtml(row.nama_tahun)}
                        -
                        ${escapeHtml(row.semester)}
                        ${
                            Number(row.tahun_aktif) === 1
                                ? '<span class="badge bg-label-success ms-1">Tahun Aktif</span>'
                                : ''
                        }
                    </td>
                    <td>
                        <span class="badge bg-label-${statusClass}">
                            ${escapeHtml(row.status_jadwal)}
                        </span>
                    </td>
                    ${action}
                </tr>
            `;
        }).join('');
    };

    const loadData = async () => {
        pager?.setDisabled(true);

        tbody.innerHTML = `
            <tr>
                <td colspan="${canManage ? 10 : 9}" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat jadwal...
                </td>
            </tr>
        `;

        try {
            const params = filterParams(true);
            params.set('format', 'json');

            const response = await fetch(
                endpoint(`master/jadwal/json?${params.toString()}`),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const result = await parseResponse(response);
            const data = result.data || {};

            rows = Array.isArray(data.rows) ? data.rows : [];
            state.total = Number(data.total || 0);
            state.limit = Number(data.limit || state.limit);
            state.offset = Number(data.offset || 0);

            renderRows();
            pager?.render(state);
            syncUrl();
        } catch (error) {
            showError(error);
        } finally {
            pager?.setDisabled(false);
        }
    };

    const loadKelasByGuru = async () => {
        const idGuru = Number(filterGuru.value || 0);

        try {
            const suffix = idGuru
                ? `?id_guru=${encodeURIComponent(idGuru)}`
                : '';

            const response = await fetch(
                endpoint(`master/jadwal/options${suffix}`),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const result = await parseResponse(response);
            const options = result.data?.kelas || [];
            const selected = filterKelas.value;

            filterKelas.innerHTML = `
                <option value="">Semua</option>
                ${options.map((kelas) => `
                    <option value="${kelas.id}">
                        ${escapeHtml(kelas.nama_tahun)}
                        -
                        ${escapeHtml(kelas.semester)}
                        ·
                        ${escapeHtml(kelas.nama_kelas)}
                    </option>
                `).join('')}
            `;

            if (
                selected
                && options.some(
                    (kelas) => String(kelas.id) === String(selected)
                )
            ) {
                filterKelas.value = selected;
            }
        } catch (error) {
            showError(error);
        }
    };

    filterGuru.addEventListener('change', async () => {
        state.offset = 0;
        await loadKelasByGuru();
    });

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        state.offset = 0;
        loadData();
    });

    document
        .getElementById('btnResetFilter')
        ?.addEventListener('click', async () => {
            filterForm.reset();
            state.offset = 0;
            await loadKelasByGuru();
            await loadData();
        });

    if (canManage) {
        const modal = new bootstrap.Modal(
            document.getElementById('modalImportJadwal')
        );

        const importForm =
            document.getElementById('formImportJadwal');

        document
            .getElementById('btnImportJadwal')
            ?.addEventListener('click', () => {
                importForm.reset();
                modal.show();
            });

        importForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button =
                document.getElementById('btnProsesImportJadwal');

            const spinner =
                button.querySelector('.spinner-border');

            button.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const response = await fetch(
                    endpoint('master/jadwal/import'),
                    {
                        method: 'POST',
                        body: new FormData(importForm),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await parseResponse(response);
                modal.hide();

                await Swal.fire({
                    icon: 'success',
                    title: 'Import berhasil',
                    text: result.message,
                });

                state.offset = 0;
                await loadKelasByGuru();
                await loadData();
            } catch (error) {
                showError(error);
            } finally {
                button.disabled = false;
                spinner.classList.add('d-none');
            }
        });

        document
            .getElementById('btnExportJadwal')
            ?.addEventListener('click', (event) => {
                event.preventDefault();

                const params = filterParams(false);
                const suffix = params.toString()
                    ? `?${params.toString()}`
                    : '';

                window.location.href =
                    endpoint(`master/jadwal/export${suffix}`);
            });

        tbody.addEventListener('click', async (event) => {
            const button =
                event.target.closest('.btn-delete-jadwal');

            if (!button) return;

            const id = Number(button.dataset.id);
            const row = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus jadwal?',
                html: `
                    <strong>${escapeHtml(row?.nama_guru || '')}</strong>
                    <br>
                    ${escapeHtml(row?.nama_kelas || '')}
                    ·
                    ${escapeHtml(row?.nama_mapel || '')}
                    <br>
                    ${escapeHtml(row?.hari || '')}
                    ${escapeHtml(String(row?.jam_mulai || '').slice(0, 5))}
                    -
                    ${escapeHtml(String(row?.jam_selesai || '').slice(0, 5))}
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });

            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(
                    endpoint(`master/jadwal/delete/${id}`),
                    {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await parseResponse(response);
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }
        });
    }

    restoreState();
    loadData();
})();
