(() => {
    'use strict';

    const app = document.getElementById('guruRecycleApp');
    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableGuruRecycle');
    const tbody = table.querySelector('tbody');
    let dataTable = null;
    let rows = [];

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || 'Permintaan gagal.');
        }
        return data;
    };

    const errorAlert = (error) => Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error?.message || 'Terjadi kesalahan.',
    });

    const loadData = async () => {
        try {
            const response = await fetch(endpoint('master/guru/recycle/json'), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            const result = await parseResponse(response);
            rows = Array.isArray(result.data) ? result.data : [];

            if (dataTable) {
                dataTable.destroy();
                dataTable = null;
            }

            tbody.innerHTML = rows.map((guru, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(guru.nama)}</td>
                    <td><span class="font-monospace">${escapeHtml(guru.nip)}</span></td>
                    <td>${guru.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                    <td>${escapeHtml(guru.deleted_at || '-')}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success btn-restore" data-id="${guru.id}">
                                <i class="bx bx-revision me-1"></i> Restore
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-force-delete" data-id="${guru.id}">
                                <i class="bx bx-x me-1"></i> Permanen
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            if (window.jQuery && jQuery.fn.DataTable) {
                dataTable = jQuery(table).DataTable({
                    pageLength: 25,
                    order: [[1, 'asc']],
                    language: {
                        search: 'Cari:',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                        infoEmpty: 'Tidak ada data',
                        zeroRecords: 'Data tidak ditemukan',
                    },
                });
            }
        } catch (error) {
            errorAlert(error);
        }
    };

    tbody.addEventListener('click', async (event) => {
        const restore = event.target.closest('.btn-restore');
        const forceDelete = event.target.closest('.btn-force-delete');

        if (restore) {
            const id = Number(restore.dataset.id);
            const guru = rows.find((item) => Number(item.id) === id);

            const confirmation = await Swal.fire({
                icon: 'question',
                title: 'Pulihkan Guru?',
                text: guru?.nama || 'Data Guru',
                showCancelButton: true,
                confirmButtonText: 'Pulihkan',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(endpoint(`master/guru/restore/${id}`), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const result = await parseResponse(response);
                await Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message });
                await loadData();
            } catch (error) {
                errorAlert(error);
            }
        }

        if (forceDelete) {
            const id = Number(forceDelete.dataset.id);
            const guru = rows.find((item) => Number(item.id) === id);

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Hapus permanen?',
                html: `<strong>${escapeHtml(guru?.nama || 'Data Guru')}</strong><br>Data tidak dapat dipulihkan kembali.`,
                showCancelButton: true,
                confirmButtonText: 'Hapus permanen',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(endpoint(`master/guru/force-delete/${id}`), {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const result = await parseResponse(response);
                await Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message });
                await loadData();
            } catch (error) {
                errorAlert(error);
            }
        }
    });

    loadData();
})();
