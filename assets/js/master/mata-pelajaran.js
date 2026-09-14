(() => {
    'use strict';

    const app = document.getElementById('masterMapelApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableMapel');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterMapel');
    const form = document.getElementById('formMapel');
    const modal = new bootstrap.Modal(document.getElementById('modalMapel'));
    const modalTitle = document.getElementById('modalMapelTitle');

    let rows = [];
    let editingId = null;
    const state = { limit: 25, offset: 0, total: 0 };

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;
    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };
    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || 'Permintaan tidak dapat diproses.');
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
        timer: 1600,
        showConfirmButton: false,
    });

    const filterParams = () => {
        const params = new URLSearchParams(new FormData(filterForm));
        for (const [key, value] of [...params.entries()]) {
            if (!String(value).trim()) params.delete(key);
        }
        return params;
    };

    const pager = window.SisfourPagination?.mount(table, {
        id: 'mapelPager',
        label: 'mata pelajaran',
        onChange: (next) => {
            state.limit = next.limit;
            state.offset = next.offset;
            renderRows();
        },
    });

    const normalizeOffset = () => {
        const maxOffset = state.total > 0
            ? Math.floor((state.total - 1) / state.limit) * state.limit
            : 0;
        state.offset = Math.min(state.offset, maxOffset);
    };

    const renderRows = () => {
        state.total = rows.length;
        normalizeOffset();
        const pageRows = rows.slice(state.offset, state.offset + state.limit);

        tbody.innerHTML = pageRows.map((mapel, index) => {
            const jumlahJadwal = Number(mapel.jumlah_jadwal || 0);
            return `
                <tr>
                    <td>${state.offset + index + 1}</td>
                    <td class="fw-semibold">${escapeHtml(mapel.nama_mapel)}</td>
                    <td><span class="badge bg-label-primary font-monospace">${escapeHtml(mapel.kode_mapel)}</span></td>
                    <td>${jumlahJadwal > 0
                        ? `<span class="badge bg-label-warning">${jumlahJadwal} jadwal</span>`
                        : '<span class="badge bg-label-secondary">Belum digunakan</span>'}</td>
                    <td>
                        <div class="sisfour-row-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="${mapel.id}" title="Edit" aria-label="Edit mata pelajaran"><i class="bx bx-edit"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${mapel.id}" title="Hapus" aria-label="Hapus mata pelajaran" ${jumlahJadwal > 0 ? 'disabled' : ''}><i class="bx bx-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('') || '<tr class="sisfour-empty-row"><td colspan="5" class="text-muted">Tidak ada data Mata Pelajaran.</td></tr>';

        pager?.render(state);
    };

    const loadData = async () => {
        try {
            const params = filterParams();
            const suffix = params.toString() ? `?${params.toString()}` : '';
            const response = await fetch(endpoint(`master/mapel/json${suffix}`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const result = await parseResponse(response);
            rows = Array.isArray(result.data) ? result.data : [];
            state.offset = 0;
            renderRows();
        } catch (error) {
            showError(error);
        }
    };

    document.getElementById('btnExportMapel')?.addEventListener('click', () => {
        const params = filterParams();
        params.set('export', '1');
        window.location.href = endpoint(`master/mapel?${params.toString()}`);
    });

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadData();
    });

    document.getElementById('btnResetFilter').addEventListener('click', () => {
        filterForm.reset();
        loadData();
    });

    const resetForm = () => {
        editingId = null;
        form.reset();
        document.getElementById('mapelId').value = '';
        modalTitle.textContent = 'Tambah Mata Pelajaran';
    };

    document.getElementById('btnTambahMapel').addEventListener('click', () => {
        resetForm();
        modal.show();
    });

    tbody.addEventListener('click', async (event) => {
        const edit = event.target.closest('.btn-edit');
        const hapus = event.target.closest('.btn-delete');

        if (edit) {
            const id = Number(edit.dataset.id);
            const mapel = rows.find((item) => Number(item.id) === id);
            if (!mapel) return;

            editingId = id;
            modalTitle.textContent = 'Edit Mata Pelajaran';
            document.getElementById('mapelId').value = String(id);
            document.getElementById('nama_mapel').value = mapel.nama_mapel;
            document.getElementById('kode_mapel').value = mapel.kode_mapel;
            modal.show();
            return;
        }

        if (hapus && !hapus.disabled) {
            const id = Number(hapus.dataset.id);
            const mapel = rows.find((item) => Number(item.id) === id);
            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus mata pelajaran?',
                html: `<strong>${escapeHtml(mapel?.nama_mapel || '')}</strong><br>Kode: ${escapeHtml(mapel?.kode_mapel || '')}<br><br>Penghapusan bersifat permanen.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });
            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(endpoint(`master/mapel/delete/${id}`), {
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

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = document.getElementById('btnSimpanMapel');
        const spinner = button.querySelector('.spinner-border');
        button.disabled = true;
        spinner.classList.remove('d-none');

        try {
            let response;
            if (editingId === null) {
                response = await fetch(endpoint('master/mapel/create'), {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
            } else {
                const payload = new URLSearchParams();
                const formData = new FormData(form);
                for (const [key, value] of formData.entries()) {
                    if (key !== 'csrf_test_name') payload.append(key, value);
                }
                response = await fetch(endpoint(`master/mapel/update/${editingId}`), {
                    method: 'PUT',
                    body: payload,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
            }

            const result = await parseResponse(response);
            modal.hide();
            await showSuccess(result.message);
            await loadData();
        } catch (error) {
            showError(error);
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    loadData();
})();
