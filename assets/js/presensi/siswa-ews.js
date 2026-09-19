(() => {
    'use strict';

    const app = document.getElementById('ewsPresensiSiswaApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl || '/';
    const form = document.getElementById('formFilterEws');
    const tanggalMulai = document.getElementById('ewsTanggalMulai');
    const tanggalSelesai = document.getElementById('ewsTanggalSelesai');
    const tbody = document.getElementById('ewsTableBody');
    const table = tbody?.closest('table');
    const mobileList = document.getElementById('ewsMobileList');

    if (!form || !tanggalMulai || !tanggalSelesai || !tbody || !table || !mobileList) return;

    let rows = [];
    const state = { limit: 25, offset: 0, total: 0 };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const pager = table && window.SisfourPagination
        ? window.SisfourPagination.mount(table, {
            id: 'ewsPresensiPager',
            label: 'siswa',
            onChange: (next) => {
                state.limit = next.limit;
                state.offset = next.offset;
                render();
            },
        })
        : null;

    const normalizeOffset = () => {
        const maxOffset = state.total > 0
            ? Math.floor((state.total - 1) / state.limit) * state.limit
            : 0;
        state.offset = Math.min(state.offset, maxOffset);
    };

    const render = () => {
        state.total = rows.length;
        normalizeOffset();
        const pageRows = rows.slice(state.offset, state.offset + state.limit);

        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Tidak ada siswa yang memenuhi kriteria EWS.</td></tr>';
            mobileList.innerHTML = '<div class="list-group-item sisfour-mobile-state text-muted">Tidak ada siswa yang memenuhi kriteria EWS.</div>';
            pager?.render(state);
            return;
        }

        tbody.innerHTML = pageRows.map((row, index) => `
            <tr>
                <td>${state.offset + index + 1}</td>
                <td class="fw-semibold">${escapeHtml(row.nama_siswa_snapshot || '-')}</td>
                <td class="text-center"><span class="badge bg-label-danger">${Number(row.total_alpha || 0)}</span></td>
            </tr>
        `).join('');

        mobileList.innerHTML = pageRows.map((row, index) => `
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div class="min-w-0">
                        <small class="text-muted d-block mb-1">#${state.offset + index + 1}</small>
                        <strong class="sisfour-cell-title">${escapeHtml(row.nama_siswa_snapshot || '-')}</strong>
                        <span class="sisfour-cell-meta">Alpha Sesi Awal dalam periode EWS</span>
                    </div>
                    <span class="badge bg-label-danger flex-shrink-0">${Number(row.total_alpha || 0)} Alpha</span>
                </div>
            </div>
        `).join('');

        pager?.render(state);
    };

    const load = async () => {
        const url = new URL('presensi/siswa/ews/json', baseUrl);
        url.searchParams.set('tanggal_mulai', tanggalMulai.value);
        url.searchParams.set('tanggal_selesai', tanggalSelesai.value);

        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>';
        mobileList.innerHTML = '<div class="list-group-item sisfour-mobile-state text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>';
        pager?.setDisabled(true);

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok || payload.status === 'error') {
                throw new Error(payload.message || 'EWS gagal dimuat.');
            }

            rows = Array.isArray(payload.data) ? payload.data : [];
            state.offset = 0;
            render();
        } catch (error) {
            rows = [];
            state.total = 0;
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
            mobileList.innerHTML = `<div class="list-group-item sisfour-mobile-state text-danger">${escapeHtml(error.message)}</div>`;
            pager?.render(state);
        } finally {
            pager?.setDisabled(false);
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        load();
    });

    load();
})();
