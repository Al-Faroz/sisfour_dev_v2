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
    const tbody = document.getElementById('rekapTableBody');
    const totalEl = document.getElementById('rekapTotal');
    const btnReset = document.getElementById('btnResetRekap');

    if (!form || !tanggalMulai || !tanggalSelesai || !tbody || !totalEl) {
        return;
    }

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    const pager = window.SisfourPagination?.mount(tbody, {
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

        if (tanggalMulai.value) {
            params.set('tanggal_mulai', tanggalMulai.value);
        }

        if (tanggalSelesai.value) {
            params.set('tanggal_selesai', tanggalSelesai.value);
        }

        if (!selfView) {
            if (kelas?.value) {
                params.set('id_kelas', kelas.value);
            }

            if (sesi?.value) {
                params.set('sesi', sesi.value);
            }

            if (status?.value) {
                params.set('status', status.value);
            }
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

            if (value !== null && element) {
                element.value = value;
            }
        });
    };

    const renderRows = (rows) => {
        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Tidak ada data pada filter ini.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>${escapeHtml(row.nama_siswa_snapshot || '-')}</td>
                <td>${escapeHtml(row.sesi)}</td>
                <td>
                    <span class="badge ${badgeClass(row.status)}">
                        ${escapeHtml(row.status)}
                    </span>
                </td>
            </tr>
        `).join('');
    };

    const load = async () => {
        if (!tanggalMulai.value || !tanggalSelesai.value) {
            return;
        }

        if (!selfView && !kelas?.value) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        Pilih kelas terlebih dahulu.
                    </td>
                </tr>
            `;
            state.total = 0;
            totalEl.textContent = '0 data';
            pager?.render(state);
            syncUrl();
            return;
        }

        const url = new URL('presensi/siswa/rekap/json', baseUrl);
        const params = buildParams(true);

        params.forEach((value, key) => {
            url.searchParams.set(key, value);
        });

        pager?.setDisabled(true);

        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat...
                </td>
            </tr>
        `;

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

            if (
                rows.length === 0
                && state.total > 0
                && state.offset >= state.total
            ) {
                state.offset = Math.floor(
                    (state.total - 1) / state.limit
                ) * state.limit;

                await load();
                return;
            }

            totalEl.textContent = `${state.total} data`;
            renderRows(rows);
            pager?.render(state);
            syncUrl();
        } catch (error) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-4">
                        ${escapeHtml(error.message)}
                    </td>
                </tr>
            `;
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
        if (kelas) kelas.value = '';
        if (sesi) sesi.value = '';
        if (status) status.value = '';

        state.offset = 0;

        if (selfView) {
            load();
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    Pilih kelas dan gunakan filter untuk menampilkan data.
                </td>
            </tr>
        `;
        state.total = 0;
        totalEl.textContent = '0 data';
        pager?.render(state);
        syncUrl();
    });

    restoreState();

    if (selfView || kelas?.value) {
        load();
    } else {
        pager?.render(state);
        syncUrl();
    }
})();
