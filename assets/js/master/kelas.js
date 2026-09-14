(() => {
    'use strict';

    const app = document.getElementById('masterKelasApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableKelas');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterKelas');
    const form = document.getElementById('formKelas');
    const modal = new bootstrap.Modal(document.getElementById('modalKelas'));

    let rows = [];
    let editingId = null;
    const state = { limit: 25, offset: 0, total: 0 };

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;
    const esc = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };
    const parse = async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || 'Permintaan gagal.');
        }
        return data;
    };
    const error = (err) => Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: err?.message || 'Terjadi kesalahan.',
    });
    const success = (message) => Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: message,
        timer: 1500,
        showConfirmButton: false,
    });

    const filterParams = () => {
        const params = new URLSearchParams(new FormData(filterForm));
        [...params.entries()].forEach(([key, value]) => {
            if (!String(value).trim()) params.delete(key);
        });
        return params;
    };

    const normalizeOffset = () => {
        const maxOffset = state.total > 0
            ? Math.floor((state.total - 1) / state.limit) * state.limit
            : 0;
        state.offset = Math.min(state.offset, maxOffset);
    };

    const pager = window.SisfourPagination?.mount(table, {
        id: 'kelasPager',
        label: 'kelas',
        onChange: (next) => {
            state.limit = next.limit;
            state.offset = next.offset;
            render();
        },
    });

    const render = () => {
        state.total = rows.length;
        normalizeOffset();
        const pageRows = rows.slice(state.offset, state.offset + state.limit);

        tbody.innerHTML = pageRows.map((row, index) => `
            <tr>
                <td>${state.offset + index + 1}</td>
                <td class="fw-semibold">${esc(row.nama_kelas)}</td>
                <td>${esc(row.tingkat)}</td>
                <td>${esc(row.rombel)}</td>
                <td>
                    ${esc(row.nama_tahun)} - ${esc(row.semester)}
                    ${Number(row.tahun_aktif) === 1 ? '<span class="badge bg-label-success ms-1">Aktif</span>' : ''}
                </td>
                <td>${Number(row.jumlah_siswa || 0)} siswa</td>
                <td>
                    <div class="sisfour-row-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="${row.id}" title="Edit" aria-label="Edit kelas"><i class="bx bx-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}" title="Hapus" aria-label="Hapus kelas"><i class="bx bx-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('') || '<tr class="sisfour-empty-row"><td colspan="7" class="text-muted">Tidak ada data Kelas.</td></tr>';

        pager?.render(state);
    };

    const load = async () => {
        try {
            const query = filterParams();
            const response = await fetch(
                endpoint(`master/kelas/json?${query.toString()}`),
                {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                }
            );
            const data = await parse(response);
            rows = Array.isArray(data.data) ? data.data : [];
            state.offset = 0;
            render();
        } catch (err) {
            error(err);
        }
    };

    document.getElementById('btnExportKelas')?.addEventListener('click', () => {
        const params = filterParams();
        params.set('export', '1');
        window.location.href = endpoint(`master/kelas?${params.toString()}`);
    });

    document.getElementById('btnTambahKelas').addEventListener('click', () => {
        editingId = null;
        form.reset();
        document.getElementById('kelasId').value = '';
        document.getElementById('modalKelasTitle').textContent = 'Tambah Kelas';
        modal.show();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = document.getElementById('btnSimpanKelas');
        const spinner = button.querySelector('.spinner-border');
        button.disabled = true;
        spinner.classList.remove('d-none');

        try {
            let response;
            if (editingId === null) {
                response = await fetch(endpoint('master/kelas/create'), {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
            } else {
                const payload = new URLSearchParams();
                for (const [key, value] of new FormData(form).entries()) {
                    if (key !== 'csrf_test_name') payload.append(key, value);
                }
                response = await fetch(endpoint(`master/kelas/update/${editingId}`), {
                    method: 'PUT',
                    body: payload,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
            }

            const data = await parse(response);
            modal.hide();
            await success(data.message);
            await load();
        } catch (err) {
            error(err);
        } finally {
            button.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    tbody.addEventListener('click', async (event) => {
        const edit = event.target.closest('.btn-edit');
        const remove = event.target.closest('.btn-delete');

        if (edit) {
            const id = Number(edit.dataset.id);
            const row = rows.find((item) => Number(item.id) === id);
            if (!row) return;

            editingId = id;
            form.reset();
            document.getElementById('kelasId').value = String(id);
            document.getElementById('tingkat').value = row.tingkat ?? '';
            document.getElementById('rombel').value = row.rombel ?? '';
            document.getElementById('id_tahun').value = row.id_tahun ?? '';
            window.SisfourSearchableSelect?.sync(document.getElementById('id_tahun'));
            document.getElementById('modalKelasTitle').textContent = 'Edit Kelas';
            modal.show();
            return;
        }

        if (remove) {
            const id = Number(remove.dataset.id);
            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus kelas?',
                text: 'Kelas hanya dapat dihapus jika tidak lagi memiliki dependency aktif.',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
            });
            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(endpoint(`master/kelas/delete/${id}`), {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await parse(response);
                await success(data.message);
                await load();
            } catch (err) {
                error(err);
            }
        }
    });

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        load();
    });

    document.getElementById('btnResetFilter').addEventListener('click', () => {
        filterForm.reset();
        window.SisfourSearchableSelect?.sync(document.getElementById('filterTahun'));
        load();
    });

    load();
})();
