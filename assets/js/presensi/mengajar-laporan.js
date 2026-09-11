(() => {
    'use strict';

    const app = document.getElementById('jurnalLaporanApp');
    if (!app) return;

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const inputMulai = document.getElementById('laporanJurnalMulai');
    const inputSelesai = document.getElementById('laporanJurnalSelesai');
    const inputStatus = document.getElementById('laporanJurnalStatus');
    const btnMuat = document.getElementById('btnMuatLaporanJurnal');
    const info = document.getElementById('laporanJurnalInfo');
    const body = document.getElementById('laporanJurnalBody');
    const meta = document.getElementById('laporanJurnalMeta');

    if (
        !inputMulai
        || !inputSelesai
        || !inputStatus
        || !btnMuat
        || !info
        || !body
    ) {
        return;
    }

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    const pager = window.SisfourPagination?.mount(body, {
        id: 'mengajarLaporanPager',
        label: 'jurnal mengajar',
        onChange: (next) => {
            state.limit = next.limit;
            state.offset = next.offset;
            load(false);
        },
    });

    if (meta) {
        meta.classList.add('d-none');
    }

    const url = (path) =>
        `${baseUrl}/${String(path).replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const showInfo = (message, type = 'info') => {
        info.className = `alert alert-${type}`;
        info.textContent = message;
        info.classList.remove('d-none');
    };

    const hideInfo = () => {
        info.classList.add('d-none');
    };

    const badge = (status) => {
        const css = status === 'Hadir'
            ? 'success'
            : status === 'Izin'
                ? 'warning'
                : 'danger';

        return `
            <span class="badge bg-label-${css}">
                ${escapeHtml(status)}
            </span>
        `;
    };

    const buildParams = (withPaging = true) => {
        const params = new URLSearchParams({
            tanggal_mulai: inputMulai.value,
            tanggal_selesai: inputSelesai.value,
        });

        if (inputStatus.value) {
            params.set('status', inputStatus.value);
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

        [
            ['tanggal_mulai', inputMulai],
            ['tanggal_selesai', inputSelesai],
            ['status', inputStatus],
        ].forEach(([key, element]) => {
            const value = params.get(key);

            if (value !== null) {
                element.value = value;
            }
        });
    };

    const render = (rows) => {
        if (!Array.isArray(rows) || rows.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Tidak ada data Jurnal pada filter tersebut.
                    </td>
                </tr>
            `;
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>${escapeHtml(row.nama_guru_snapshot || '-')}</td>
                <td>${escapeHtml(row.nama_kelas || '-')}</td>
                <td>
                    <div class="fw-semibold">
                        ${escapeHtml(row.nama_mapel || '-')}
                    </div>
                    <small class="text-muted">
                        ${escapeHtml(row.kode_mapel || '')}
                    </small>
                </td>
                <td>
                    <div>
                        ${escapeHtml(row.jam_mulai || '')}
                        -
                        ${escapeHtml(row.jam_selesai || '')}
                    </div>
                    <small class="text-muted">
                        ${escapeHtml(row.sesi || '-')}
                    </small>
                </td>
                <td>${badge(row.status || '-')}</td>
                <td style="min-width:260px;">
                    ${escapeHtml(row.materi || '-')}
                </td>
            </tr>
        `).join('');
    };

    async function load(resetOffset = false) {
        if (resetOffset) {
            state.offset = 0;
        }

        if (!inputMulai.value || !inputSelesai.value) {
            showInfo('Tanggal mulai dan selesai wajib diisi.', 'warning');
            return;
        }

        btnMuat.disabled = true;
        pager?.setDisabled(true);
        showInfo('Memuat histori Jurnal...', 'info');

        const params = buildParams(true);
        params.set('format', 'json');

        try {
            const response = await fetch(
                url(`presensi/mengajar/laporan?${params.toString()}`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const json = await response.json();

            if (!response.ok || json.status !== 'success') {
                showInfo(
                    json.message || 'Gagal memuat histori Jurnal.',
                    'danger'
                );
                render([]);
                state.total = 0;
                pager?.render(state);
                return;
            }

            hideInfo();

            const data = json.data || {};
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

                await load(false);
                return;
            }

            render(rows);
            pager?.render(state);
            syncUrl();
        } catch (error) {
            showInfo(
                'Terjadi kesalahan saat memuat histori Jurnal.',
                'danger'
            );
            render([]);
        } finally {
            btnMuat.disabled = false;
            pager?.setDisabled(false);
        }
    }

    btnMuat.addEventListener('click', () => load(true));

    restoreState();
    load(false);
})();
