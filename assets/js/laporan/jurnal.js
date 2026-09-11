(() => {
    'use strict';

    const app = document.getElementById('laporanJurnalApp');
    if (!app) return;

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const options = document.getElementById('jurnalOptions');
    if (!options) return;

    const tahun = document.getElementById('jurnalTahun');
    const guru = document.getElementById('jurnalGuru');
    const kelas = document.getElementById('jurnalKelas');
    const hari = document.getElementById('jurnalHari');
    const status = document.getElementById('jurnalStatus');
    const mulai = document.getElementById('jurnalMulai');
    const selesai = document.getElementById('jurnalSelesai');
    const btnCari = document.getElementById('btnJurnalCari');
    const btnExport = document.getElementById('btnJurnalExport');
    const body = document.getElementById('jurnalBody');
    const alertBox = document.getElementById('jurnalAlert');
    const legacyInfo = document.getElementById('jurnalPageInfo');

    if (
        !tahun
        || !kelas
        || !hari
        || !status
        || !mulai
        || !selesai
        || !btnCari
        || !btnExport
        || !body
        || !alertBox
    ) {
        return;
    }

    if (legacyInfo) {
        legacyInfo.classList.add('d-none');
    }

    const fixedGuru = Number(options.dataset.fixedGuru || 0);

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    const pager = window.SisfourPagination?.mount(body, {
        id: 'laporanJurnalPager',
        label: 'jurnal',
        onChange: (next) => {
            state.limit = next.limit;
            state.offset = next.offset;
            load();
        },
    });

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const buildParams = (includePaging = true) => {
        const p = new URLSearchParams({
            id_tahun: tahun.value,
            tanggal_mulai: mulai.value,
            tanggal_selesai: selesai.value,
        });

        const idGuru = fixedGuru || Number(guru?.value || 0);

        if (idGuru) p.set('id_guru', String(idGuru));
        if (kelas.value) p.set('id_kelas', kelas.value);
        if (hari.value) p.set('hari', hari.value);
        if (status.value) p.set('status', status.value);

        if (includePaging) {
            p.set('limit', String(state.limit));
            p.set('offset', String(state.offset));
        }

        return p;
    };

    const syncUrl = () => {
        const p = buildParams(true);
        const query = p.toString();

        window.history.replaceState(
            null,
            '',
            `${window.location.pathname}${query ? `?${query}` : ''}`
        );
    };

    const restoreState = () => {
        const p = new URLSearchParams(window.location.search);
        const limit = Number(p.get('limit') || 25);
        const offset = Number(p.get('offset') || 0);

        state.limit = [25, 50, 100].includes(limit) ? limit : 25;
        state.offset = Number.isFinite(offset) && offset >= 0 ? offset : 0;

        [
            ['id_tahun', tahun],
            ['id_guru', guru],
            ['id_kelas', kelas],
            ['hari', hari],
            ['status', status],
            ['tanggal_mulai', mulai],
            ['tanggal_selesai', selesai],
        ].forEach(([key, element]) => {
            const value = p.get(key);

            if (value !== null && element && !element.disabled) {
                element.value = value;
            }
        });
    };

    const show = (message, type = 'info') => {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
    };

    const hide = () => {
        alertBox.classList.add('d-none');
    };

    const render = (rows) => {
        if (!rows.length) {
            body.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        Tidak ada Jurnal sesuai filter.
                    </td>
                </tr>
            `;
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>
                    ${escapeHtml(row.hari)}
                    <br>
                    <small class="text-muted">
                        ${escapeHtml(row.jam_mulai)}
                        -
                        ${escapeHtml(row.jam_selesai)}
                    </small>
                </td>
                <td>
                    ${escapeHtml(row.nama_guru_snapshot)}
                    <br>
                    <small class="text-muted">
                        ${escapeHtml(row.nip || '-')}
                    </small>
                </td>
                <td>${escapeHtml(row.nama_kelas || '-')}</td>
                <td>
                    ${escapeHtml(row.kode_mapel || '-')}
                    —
                    ${escapeHtml(row.nama_mapel || '-')}
                </td>
                <td>${escapeHtml(row.sesi)}</td>
                <td>${escapeHtml(row.status)}</td>
                <td style="min-width:260px">
                    ${escapeHtml(row.materi)}
                </td>
            </tr>
        `).join('');
    };

    async function load() {
        btnCari.disabled = true;
        pager?.setDisabled(true);
        show('Memuat Laporan Jurnal...', 'info');

        const p = buildParams(true);
        p.set('format', 'json');

        try {
            const res = await fetch(
                endpoint(`laporan/jurnal?${p.toString()}`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const json = await res.json();
            const data = json.data || {};

            if (!res.ok || json.status !== 'success') {
                show(
                    json.message || 'Laporan Jurnal gagal dimuat.',
                    'danger'
                );
                return;
            }

            hide();

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

            render(rows);
            pager?.render(state);
            syncUrl();
        } catch (error) {
            show(
                'Terjadi kesalahan saat memuat Laporan Jurnal.',
                'danger'
            );
        } finally {
            btnCari.disabled = false;
            pager?.setDisabled(false);
        }
    }

    tahun.addEventListener('change', () => {
        const p = new URLSearchParams({
            id_tahun: tahun.value,
            tanggal_mulai: mulai.value,
            tanggal_selesai: selesai.value,
        });

        window.location.href = endpoint(
            `laporan/jurnal?${p.toString()}`
        );
    });

    btnCari.addEventListener('click', () => {
        state.offset = 0;
        load();
    });

    btnExport.addEventListener('click', () => {
        const p = buildParams(false);

        window.location.href = endpoint(
            `laporan/jurnal/export?${p.toString()}`
        );
    });

    restoreState();
    load();
})();
