(() => {
    'use strict';

    const app = document.getElementById('rekapPresensiSiswaApp');

    if (!app) {
        return;
    }

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
    const pageInfo = document.getElementById('rekapPageInfo');
    const btnPrev = document.getElementById('btnRekapPrev');
    const btnNext = document.getElementById('btnRekapNext');
    const btnReset = document.getElementById('btnResetRekap');

    const state = {
        limit: 50,
        offset: 0,
        total: 0,
    };

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

    const load = async () => {
        if (!tanggalMulai.value || !tanggalSelesai.value) {
            return;
        }

        if (!selfView && !kelas?.value) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Pilih kelas terlebih dahulu.</td></tr>';
            return;
        }

        const url = new URL('presensi/siswa/rekap/json', baseUrl);
        url.searchParams.set('tanggal_mulai', tanggalMulai.value);
        url.searchParams.set('tanggal_selesai', tanggalSelesai.value);
        url.searchParams.set('limit', String(state.limit));
        url.searchParams.set('offset', String(state.offset));

        if (!selfView) {
            url.searchParams.set('id_kelas', kelas.value);

            if (sesi?.value) {
                url.searchParams.set('sesi', sesi.value);
            }

            if (status?.value) {
                url.searchParams.set('status', status.value);
            }
        }

        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>';

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok || payload.status === 'error') {
                throw new Error(payload.message || 'Data gagal dimuat.');
            }

            const data = payload.data || {};
            const rows = data.rows || [];
            state.total = Number(data.total || 0);

            totalEl.textContent = `${state.total} data`;

            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data pada filter ini.</td></tr>';
            } else {
                tbody.innerHTML = rows.map((row) => `
                    <tr>
                        <td>${escapeHtml(row.tanggal)}</td>
                        <td>${escapeHtml(row.nama_siswa_snapshot || '-')}</td>
                        <td>${escapeHtml(row.sesi)}</td>
                        <td><span class="badge ${badgeClass(row.status)}">${escapeHtml(row.status)}</span></td>
                    </tr>
                `).join('');
            }

            const page = Math.floor(state.offset / state.limit) + 1;
            const totalPages = Math.max(1, Math.ceil(state.total / state.limit));
            pageInfo.textContent = `Halaman ${page} dari ${totalPages}`;
            btnPrev.disabled = state.offset <= 0;
            btnNext.disabled = state.offset + state.limit >= state.total;
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        state.offset = 0;
        load();
    });

    btnPrev.addEventListener('click', () => {
        state.offset = Math.max(0, state.offset - state.limit);
        load();
    });

    btnNext.addEventListener('click', () => {
        if (state.offset + state.limit < state.total) {
            state.offset += state.limit;
            load();
        }
    });

    btnReset.addEventListener('click', () => {
        if (kelas) {
            kelas.value = '';
        }
        if (sesi) {
            sesi.value = '';
        }
        if (status) {
            status.value = '';
        }
        state.offset = 0;
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Gunakan filter untuk menampilkan data.</td></tr>';
        totalEl.textContent = '0 data';
        pageInfo.textContent = 'Halaman 1';
        btnPrev.disabled = true;
        btnNext.disabled = true;
    });

    if (selfView) {
        load();
    }
})();
