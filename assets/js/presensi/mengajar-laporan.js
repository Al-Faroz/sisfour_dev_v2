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
    const mobileList = document.getElementById('laporanJurnalMobileList');

    const detailModalElement = document.getElementById('jurnalDetailModal');
    const detailTitle = document.getElementById('jurnalDetailTitle');
    const detailMeta = document.getElementById('jurnalDetailMeta');
    const detailInfo = document.getElementById('jurnalDetailInfo');
    const detailContent = document.getElementById('jurnalDetailContent');
    const detailStatus = document.getElementById('jurnalDetailStatus');
    const detailMateri = document.getElementById('jurnalDetailMateri');
    const detailCatatan = document.getElementById('jurnalDetailCatatan');
    const detailStudents = document.getElementById('jurnalDetailStudents');
    const detailEmpty = document.getElementById('jurnalDetailEmpty');
    const detailSakit = document.getElementById('jurnalDetailSakit');
    const detailIzin = document.getElementById('jurnalDetailIzin');
    const detailAlpha = document.getElementById('jurnalDetailAlpha');

    if (
        !inputMulai
        || !inputSelesai
        || !inputStatus
        || !btnMuat
        || !info
        || !body
        || !mobileList
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

    const detailModal = detailModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(detailModalElement)
        : null;

    const url = (path) =>
        `${baseUrl}/${String(path).replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const truncate = (value, max = 120) => {
        const text = String(value ?? '').trim();
        if (text.length <= max) return text;
        return `${text.slice(0, max - 1)}…`;
    };

    const showInfo = (message, type = 'info') => {
        info.className = `alert alert-${type}`;
        info.textContent = message;
        info.classList.remove('d-none');
    };

    const hideInfo = () => {
        info.classList.add('d-none');
    };

    async function requestJson(requestUrl) {
        let response;

        try {
            response = await fetch(requestUrl, {
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
            json = await response.json();
        } catch (error) {
            json = null;
        }

        if (!response.ok || json?.status !== 'success') {
            throw new Error(json?.message || `Request gagal (${response.status}).`);
        }

        return json;
    }

    const badge = (status) => {
        const css = status === 'Hadir'
            ? 'success'
            : status === 'Izin'
                ? 'warning'
                : 'danger';

        return `<span class="badge bg-label-${css}">${escapeHtml(status)}</span>`;
    };

    const studentBadge = (status) => {
        const css = status === 'Sakit'
            ? 'warning'
            : status === 'Izin'
                ? 'info'
                : 'danger';
        return `<span class="badge bg-label-${css}">${escapeHtml(status)}</span>`;
    };

    const exceptionSummaryHtml = (row) => {
        const summary = row.siswa_exception_summary || {};
        const total = Number(row.siswa_exception_count || summary.total || 0);

        if (total <= 0) {
            return '<span class="text-muted small">Tidak ada</span>';
        }

        return `
            <div class="fw-semibold">${total} siswa</div>
            <small class="text-muted">
                S ${Number(summary.Sakit || 0)} · I ${Number(summary.Izin || 0)} · A ${Number(summary.Alpha || 0)}
            </small>
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

    const detailButton = (id, compact = false) => `
        <button
            type="button"
            class="btn ${compact ? 'btn-sm' : ''} btn-outline-primary sisfour-touch-target--compact"
            data-jurnal-detail="${Number(id)}"
        >
            <i class="bx bx-detail me-1"></i> Detail
        </button>
    `;

    const render = (rows) => {
        if (!Array.isArray(rows) || rows.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Tidak ada data Jurnal pada filter tersebut.
                    </td>
                </tr>
            `;
            mobileList.innerHTML = '<div class="sisfour-mobile-state text-muted">Tidak ada data Jurnal pada filter tersebut.</div>';
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>
                    <div class="fw-semibold">${escapeHtml(row.nama_guru_snapshot || '-')}</div>
                    <small class="text-muted">${escapeHtml(row.nama_kelas || '-')}</small>
                </td>
                <td>
                    <div class="fw-semibold">${escapeHtml(row.nama_mapel || '-')}</div>
                    <small class="text-muted">
                        ${escapeHtml(row.jam_mulai || '')} - ${escapeHtml(row.jam_selesai || '')} · ${escapeHtml(row.sesi || '-')}
                    </small>
                </td>
                <td>${badge(row.status || '-')}</td>
                <td style="min-width:260px;">
                    <div>${escapeHtml(truncate(row.materi || '-', 110))}</div>
                    ${row.catatan ? `<small class="text-muted">Catatan: ${escapeHtml(truncate(row.catatan, 80))}</small>` : ''}
                </td>
                <td>${exceptionSummaryHtml(row)}</td>
                <td class="text-end">${detailButton(row.id, true)}</td>
            </tr>
        `).join('');

        mobileList.innerHTML = rows.map((row) => `
            <article class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="min-w-0">
                        <div class="fw-semibold text-break">${escapeHtml(row.nama_mapel || '-')} · ${escapeHtml(row.nama_kelas || '-')}</div>
                        <small class="text-muted">${escapeHtml(row.tanggal)} · ${escapeHtml(row.jam_mulai || '')}-${escapeHtml(row.jam_selesai || '')}</small>
                    </div>
                    ${badge(row.status || '-')}
                </div>
                <div class="small mb-2">
                    <div class="fw-semibold">${escapeHtml(row.nama_guru_snapshot || '-')}</div>
                    <div class="text-muted mt-1">${escapeHtml(truncate(row.materi || '-', 120))}</div>
                </div>
                <div class="d-flex justify-content-between align-items-end gap-2">
                    <div>${exceptionSummaryHtml(row)}</div>
                    ${detailButton(row.id, true)}
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
            const params = new URLSearchParams({
                format: 'json',
                id_jurnal: String(idJurnal),
            });
            const json = await requestJson(
                url(`presensi/mengajar/laporan?${params.toString()}`)
            );

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

            if (students.length === 0) {
                detailEmpty.classList.remove('d-none');
            } else {
                detailStudents.innerHTML = students.map((student) => `
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-break">${escapeHtml(student.nama_siswa_snapshot || '-')}</div>
                            <small class="text-muted">${student.nisn_snapshot ? `NISN ${escapeHtml(student.nisn_snapshot)}` : 'NISN tidak tersedia'}</small>
                        </div>
                        ${studentBadge(student.status || '-')}
                    </div>
                `).join('');
            }

            detailInfo.classList.add('d-none');
            detailContent.classList.remove('d-none');
        } catch (error) {
            detailInfo.className = 'alert alert-danger';
            detailInfo.textContent = error.message;
            detailInfo.classList.remove('d-none');
        }
    }

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
            const json = await requestJson(
                url(`presensi/mengajar/laporan?${params.toString()}`)
            );

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
            showInfo(error.message || 'Terjadi kesalahan saat memuat histori Jurnal.', 'danger');
            render([]);
            state.total = 0;
            pager?.render(state);
        } finally {
            btnMuat.disabled = false;
            pager?.setDisabled(false);
        }
    }

    btnMuat.addEventListener('click', () => load(true));

    app.addEventListener('click', (event) => {
        const button = event.target.closest('[data-jurnal-detail]');
        if (!button) return;
        loadDetail(Number(button.dataset.jurnalDetail || 0));
    });

    restoreState();
    load(false);
})();
