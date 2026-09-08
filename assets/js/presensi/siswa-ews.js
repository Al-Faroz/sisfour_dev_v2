(() => {
    'use strict';

    const app = document.getElementById('ewsPresensiSiswaApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl || '/';
    const form = document.getElementById('formFilterEws');
    const tanggalMulai = document.getElementById('ewsTanggalMulai');
    const tanggalSelesai = document.getElementById('ewsTanggalSelesai');
    const tbody = document.getElementById('ewsTableBody');

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const load = async () => {
        const url = new URL('presensi/siswa/ews/json', baseUrl);
        url.searchParams.set('tanggal_mulai', tanggalMulai.value);
        url.searchParams.set('tanggal_selesai', tanggalSelesai.value);

        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>';

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok || payload.status === 'error') {
                throw new Error(payload.message || 'EWS gagal dimuat.');
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];

            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Tidak ada siswa yang memenuhi kriteria EWS.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map((row, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td class="fw-semibold">${escapeHtml(row.nama_siswa_snapshot || '-')}</td>
                    <td class="text-center"><span class="badge bg-label-danger">${Number(row.total_alpha || 0)}</span></td>
                </tr>
            `).join('');
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        load();
    });

    load();
})();
