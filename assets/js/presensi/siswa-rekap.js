(() => {
    'use strict';

    const app = document.getElementById('rekapPresensiSiswaApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl || '/';
    const selfView = app.dataset.selfView === '1';
    const form = document.getElementById('formFilterRekap');
    const kelas = document.getElementById('rekapKelas');
    const tanggalMulai = document.getElementById('rekapTanggalMulai');
    const tanggalSelesai = document.getElementById('rekapTanggalSelesai');
    const sesi = document.getElementById('rekapSesi');
    const status = document.getElementById('rekapStatus');
    const table = document.getElementById('tableRekapPresensi');
    const tbody = document.getElementById('rekapTableBody');
    const mobileList = document.getElementById('rekapMobileList');
    const totalEl = document.getElementById('rekapTotal');
    const btnReset = document.getElementById('btnResetRekap');

    if (!form || !tanggalMulai || !tanggalSelesai || !table || !tbody || !mobileList || !totalEl) {
        return;
    }

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    const pager = window.SisfourPagination?.mount(table, {
        id: 'rekapPresensiPager',
        label: 'data presensi',
        onChange: (next) => {
            state.limit = next.limit;
            state.offset = next.offset;
            load();
        },
    });

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const badgeClass = (value) => ({
        Hadir: 'bg-label-success',
        Sakit: 'bg-label-warning',
        Izin: 'bg-label-info',
        Alpha: 'bg-label-danger',
    }[value] || 'bg-label-secondary');

    const buildParams = (withPaging = true) => {
        const params = new URLSearchParams();

        if (tanggalMulai.value) params.set('tanggal_mulai', tanggalMulai.value);
        if (tanggalSelesai.value) params.set('tanggal_selesai', tanggalSelesai.value);

        if (!selfView) {
            if (kelas?.value) params.set('id_kelas', kelas.value);
            if (sesi?.value) params.set('sesi', sesi.value);
            if (status?.value) params.set('status', status.value);
        }

        if (withPaging) {
            params.set('limit', String(state.limit));
            params.set('offset', String(state.offset));
        }

        return params;
    };

    const syncUrl = () => {
        const params = buildParams(true);
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
        state.offset = Number.isFinite(offset) && offset >= 0 ? offset : 0;

        const mapping = [
            ['tanggal_mulai', tanggalMulai],
            ['tanggal_selesai', tanggalSelesai],
            ['id_kelas', kelas],
            ['sesi', sesi],
            ['status', status],
        ];

        mapping.forEach(([key, element]) => {
            const value = params.get(key);
            if (value !== null && element) element.value = value;
        });

        if (kelas) window.SisfourSearchableSelect?.sync(kelas);
    };

    const setListState = (message, type = 'muted') => {
        mobileList.innerHTML = `<div class="list-group-item sisfour-mobile-state text-${type}">${escapeHtml(message)}</div>`;
    };

    const renderRows = (rows) => {
        if (!rows.length) {
            tbody.innerHTML = '<tr class="sisfour-empty-row"><td colspan="4" class="text-muted">Tidak ada data pada filter ini.</td></tr>';
            setListState('Tidak ada data pada filter ini.');
            return;
        }

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>${escapeHtml(row.nama_siswa_snapshot || '-')}</td>
                <td>${escapeHtml(row.sesi)}</td>
                <td><span class="badge ${badgeClass(row.status)}">${escapeHtml(row.status)}</span></td>
            </tr>
        `).join('');

        mobileList.innerHTML = rows.map((row) => `
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                    <div class="min-w-0 flex-grow-1">
                        <strong class="sisfour-cell-title">${escapeHtml(row.nama_siswa_snapshot || '-')}</strong>
                        <span class="sisfour-cell-meta">${escapeHtml(row.tanggal)} · ${escapeHtml(row.sesi)}</span>
                    </div>
                    <span class="badge ${badgeClass(row.status)} flex-shrink-0">${escapeHtml(row.status)}</span>
                </div>
            </div>
        `).join('');
    };

    const load = async () => {
        if (!tanggalMulai.value || !tanggalSelesai.value) return;

        if (!selfView && !kelas?.value) {
            tbody.innerHTML = '<tr class="sisfour-empty-row"><td colspan="4" class="text-muted">Pilih kelas terlebih dahulu.</td></tr>';
            setListState('Pilih kelas terlebih dahulu.');
            state.total = 0;
            totalEl.textContent = '0 data';
            pager?.render(state);
            syncUrl();
            return;
        }

        const url = new URL('presensi/siswa/rekap/json', baseUrl);
        buildParams(true).forEach((value, key) => url.searchParams.set(key, value));

        pager?.setDisabled(true);
        tbody.innerHTML = '<tr class="sisfour-loading-row"><td colspan="4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>';
        mobileList.innerHTML = '<div class="list-group-item sisfour-mobile-state text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>';

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json();

            if (!response.ok || payload.status === 'error') {
                throw new Error(payload.message || 'Data gagal dimuat.');
            }

            const data = payload.data || {};
            const rows = Array.isArray(data.rows) ? data.rows : [];

            state.total = Number(data.total || 0);
            state.limit = Number(data.limit || state.limit);
            state.offset = Number(data.offset ?? state.offset);

            if (rows.length === 0 && state.total > 0 && state.offset >= state.total) {
                state.offset = Math.floor((state.total - 1) / state.limit) * state.limit;
                await load();
                return;
            }

            totalEl.textContent = `${state.total} data`;
            renderRows(rows);
            pager?.render(state);
            syncUrl();
        } catch (error) {
            tbody.innerHTML = `<tr class="sisfour-error-row"><td colspan="4" class="text-danger">${escapeHtml(error.message)}</td></tr>`;
            setListState(error.message || 'Data gagal dimuat.', 'danger');
        } finally {
            pager?.setDisabled(false);
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        state.offset = 0;
        load();
    });

    btnReset?.addEventListener('click', () => {
        if (kelas) {
            kelas.value = '';
            window.SisfourSearchableSelect?.sync(kelas);
        }
        if (sesi) sesi.value = '';
        if (status) status.value = '';
        state.offset = 0;

        if (selfView) {
            load();
            return;
        }

        tbody.innerHTML = '<tr class="sisfour-empty-row"><td colspan="4" class="text-muted">Pilih kelas dan gunakan filter untuk menampilkan data.</td></tr>';
        setListState('Pilih kelas dan gunakan filter untuk menampilkan data.');
        state.total = 0;
        totalEl.textContent = '0 data';
        pager?.render(state);
        syncUrl();
    });

    restoreState();

    if (selfView || kelas?.value) load();
    else {
        pager?.render(state);
        syncUrl();
    }
})();
