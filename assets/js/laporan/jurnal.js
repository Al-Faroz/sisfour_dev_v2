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
    const mobileList = document.getElementById('jurnalMobileList');
    const alertBox = document.getElementById('jurnalAlert');

    const detailModalElement = document.getElementById('laporanJurnalDetailModal');
    const detailTitle = document.getElementById('laporanJurnalDetailTitle');
    const detailMeta = document.getElementById('laporanJurnalDetailMeta');
    const detailInfo = document.getElementById('laporanJurnalDetailInfo');
    const detailContent = document.getElementById('laporanJurnalDetailContent');
    const detailStatus = document.getElementById('laporanJurnalDetailStatus');
    const detailMateri = document.getElementById('laporanJurnalDetailMateri');
    const detailCatatan = document.getElementById('laporanJurnalDetailCatatan');
    const detailStudents = document.getElementById('laporanJurnalDetailStudents');
    const detailEmpty = document.getElementById('laporanJurnalDetailEmpty');
    const detailSakit = document.getElementById('laporanJurnalDetailSakit');
    const detailIzin = document.getElementById('laporanJurnalDetailIzin');
    const detailAlpha = document.getElementById('laporanJurnalDetailAlpha');

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
        || !mobileList
        || !alertBox
    ) {
        return;
    }

    const fixedGuru = Number(options.dataset.fixedGuru || 0);
    const state = { limit: 25, offset: 0, total: 0 };
    const detailModal = detailModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(detailModalElement)
        : null;

    const pager = window.SisfourPagination?.mount(body, {
        id: 'laporanJurnalPager',
        label: 'jurnal',
        onChange: (next) => {
            state.limit = next.limit;
            state.offset = next.offset;
            load();
        },
    });

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const truncate = (value, max = 120) => {
        const text = String(value ?? '').trim();
        return text.length <= max ? text : `${text.slice(0, max - 1)}…`;
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
        window.history.replaceState(null, '', `${window.location.pathname}${query ? `?${query}` : ''}`);
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
            if (value !== null && element && !element.disabled) element.value = value;
        });
    };

    const show = (message, type = 'info') => {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    };

    const hide = () => alertBox.classList.add('d-none');

    async function requestJson(requestUrl) {
        let res;
        try {
            res = await fetch(requestUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
        } catch (error) {
            throw new Error('Koneksi ke server gagal. Periksa jaringan lalu coba lagi.');
        }

        let json = null;
        try {
            json = await res.json();
        } catch (error) {
            json = null;
        }

        if (!res.ok || json?.status !== 'success') {
            throw new Error(json?.message || `Request gagal (${res.status}).`);
        }

        return json;
    }

    const guruBadge = (value) => {
        const css = value === 'Hadir' ? 'success' : value === 'Izin' ? 'warning' : 'danger';
        return `<span class="badge bg-label-${css}">${escapeHtml(value || '-')}</span>`;
    };

    const studentBadge = (value) => {
        const css = value === 'Sakit' ? 'warning' : value === 'Izin' ? 'info' : 'danger';
        return `<span class="badge bg-label-${css}">${escapeHtml(value || '-')}</span>`;
    };

    const summaryHtml = (row) => {
        const summary = row.siswa_exception_summary || {};
        const total = Number(row.siswa_exception_count || summary.total || 0);

        if (!total) return '<span class="text-muted small">Tidak ada</span>';

        return `
            <div class="fw-semibold">${total} siswa</div>
            <small class="text-muted">S ${Number(summary.Sakit || 0)} · I ${Number(summary.Izin || 0)} · A ${Number(summary.Alpha || 0)}</small>
        `;
    };

    const detailButton = (id) => `
        <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" data-jurnal-detail="${Number(id)}">
            <i class="bx bx-detail me-1"></i> Detail
        </button>
    `;

    const render = (rows) => {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada Jurnal sesuai filter.</td></tr>';
            mobileList.innerHTML = '<div class="sisfour-mobile-state text-muted">Tidak ada Jurnal sesuai filter.</div>';
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>
                    <div class="fw-semibold">${escapeHtml(row.nama_guru_snapshot || '-')}</div>
                    <small class="text-muted">${escapeHtml(row.nama_kelas || '-')} · ${escapeHtml(row.nip || '-')}</small>
                </td>
                <td>
                    <div class="fw-semibold">${escapeHtml(row.kode_mapel || '-')} — ${escapeHtml(row.nama_mapel || '-')}</div>
                    <small class="text-muted">${escapeHtml(row.hari || '-')} · ${escapeHtml(row.jam_mulai || '')}-${escapeHtml(row.jam_selesai || '')} · ${escapeHtml(row.sesi || '-')}</small>
                </td>
                <td>${guruBadge(row.status)}</td>
                <td style="min-width:260px">
                    <div>${escapeHtml(truncate(row.materi || '-', 100))}</div>
                    ${row.catatan ? `<small class="text-muted">Catatan: ${escapeHtml(truncate(row.catatan, 70))}</small>` : ''}
                </td>
                <td>${summaryHtml(row)}</td>
                <td class="text-end">${detailButton(row.id)}</td>
            </tr>
        `).join('');

        mobileList.innerHTML = rows.map((row) => `
            <article class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="min-w-0">
                        <div class="fw-semibold text-break">${escapeHtml(row.nama_mapel || '-')} · ${escapeHtml(row.nama_kelas || '-')}</div>
                        <small class="text-muted">${escapeHtml(row.tanggal)} · ${escapeHtml(row.jam_mulai || '')}-${escapeHtml(row.jam_selesai || '')}</small>
                    </div>
                    ${guruBadge(row.status)}
                </div>
                <div class="small mb-2">
                    <div class="fw-semibold">${escapeHtml(row.nama_guru_snapshot || '-')}</div>
                    <div class="text-muted mt-1">${escapeHtml(truncate(row.materi || '-', 110))}</div>
                </div>
                <div class="d-flex justify-content-between align-items-end gap-2">
                    <div>${summaryHtml(row)}</div>
                    ${detailButton(row.id)}
                </div>
            </article>
        `).join('');
    };

    async function loadDetail(idJurnal) {
        if (!idJurnal) return;

        detailTitle.textContent = 'Detail Jurnal';
        detailMeta.textContent = '';
        detailContent.classList.add('d-none');
        detailInfo.className = 'alert alert-info';
        detailInfo.textContent = 'Memuat detail Jurnal...';
        detailInfo.classList.remove('d-none');
        detailStudents.innerHTML = '';
        detailEmpty.classList.add('d-none');
        detailModal?.show();

        try {
            const p = new URLSearchParams({ format: 'json', id_jurnal: String(idJurnal) });
            const json = await requestJson(endpoint(`laporan/jurnal?${p.toString()}`));
            const data = json.data || {};
            const jurnal = data.jurnal || {};
            const students = Array.isArray(data.siswa) ? data.siswa : [];
            const summary = data.siswa_exception_summary || {};

            detailTitle.textContent = `${jurnal.nama_kelas || '-'} — ${jurnal.nama_mapel || '-'}`;
            detailMeta.textContent = [
                jurnal.nama_guru_snapshot || '-',
                jurnal.tanggal || '-',
                `${jurnal.jam_mulai || ''} - ${jurnal.jam_selesai || ''}`,
                jurnal.sesi || '-'
            ].join(' · ');
            detailStatus.textContent = jurnal.status || '-';
            detailMateri.textContent = jurnal.materi || '-';
            detailCatatan.textContent = jurnal.catatan || '-';
            detailSakit.textContent = String(Number(summary.Sakit || 0));
            detailIzin.textContent = String(Number(summary.Izin || 0));
            detailAlpha.textContent = String(Number(summary.Alpha || 0));

            if (!students.length) {
                detailEmpty.classList.remove('d-none');
            } else {
                detailStudents.innerHTML = students.map((student) => `
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-break">${escapeHtml(student.nama_siswa_snapshot || '-')}</div>
                            <small class="text-muted">${student.nisn_snapshot ? `NISN ${escapeHtml(student.nisn_snapshot)}` : 'NISN tidak tersedia'}</small>
                        </div>
                        ${studentBadge(student.status)}
                    </div>
                `).join('');
            }

            detailInfo.classList.add('d-none');
            detailContent.classList.remove('d-none');
        } catch (error) {
            detailInfo.className = 'alert alert-danger';
            detailInfo.textContent = error.message;
        }
    }

    async function load() {
        btnCari.disabled = true;
        pager?.setDisabled(true);
        show('Memuat Laporan Jurnal...', 'info');

        const p = buildParams(true);
        p.set('format', 'json');

        try {
            const json = await requestJson(endpoint(`laporan/jurnal?${p.toString()}`));
            const data = json.data || {};
            const rows = Array.isArray(data.rows) ? data.rows : [];

            hide();
            state.total = Number(data.total || 0);
            state.limit = Number(data.limit || state.limit);
            state.offset = Number(data.offset ?? state.offset);

            if (rows.length === 0 && state.total > 0 && state.offset >= state.total) {
                state.offset = Math.floor((state.total - 1) / state.limit) * state.limit;
                await load();
                return;
            }

            render(rows);
            pager?.render(state);
            syncUrl();
        } catch (error) {
            show(error.message || 'Laporan Jurnal gagal dimuat.', 'danger');
            render([]);
            state.total = 0;
            pager?.render(state);
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
        window.location.href = endpoint(`laporan/jurnal?${p.toString()}`);
    });

    btnCari.addEventListener('click', () => {
        state.offset = 0;
        load();
    });

    btnExport.addEventListener('click', () => {
        const p = buildParams(false);
        window.location.href = endpoint(`laporan/jurnal/export?${p.toString()}`);
    });

    app.addEventListener('click', (event) => {
        const button = event.target.closest('[data-jurnal-detail]');
        if (!button) return;
        loadDetail(Number(button.dataset.jurnalDetail || 0));
    });

    restoreState();
    load();
})();
