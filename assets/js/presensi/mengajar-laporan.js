(() => {
    'use strict';

    const app = document.getElementById('jurnalLaporanApp');

    if (!app) {
        return;
    }

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const inputMulai = document.getElementById('laporanJurnalMulai');
    const inputSelesai = document.getElementById('laporanJurnalSelesai');
    const inputStatus = document.getElementById('laporanJurnalStatus');
    const btnMuat = document.getElementById('btnMuatLaporanJurnal');
    const info = document.getElementById('laporanJurnalInfo');
    const body = document.getElementById('laporanJurnalBody');
    const meta = document.getElementById('laporanJurnalMeta');
    const btnPrev = document.getElementById('btnJurnalPrev');
    const btnNext = document.getElementById('btnJurnalNext');

    const limit = 50;
    let offset = 0;
    let total = 0;

    function url(path) {
        return `${baseUrl}/${String(path).replace(/^\/+/, '')}`;
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function showInfo(message, type = 'info') {
        info.className = `alert alert-${type}`;
        info.textContent = message;
        info.classList.remove('d-none');
    }

    function hideInfo() {
        info.classList.add('d-none');
    }

    function badge(status) {
        const css = status === 'Hadir'
            ? 'success'
            : status === 'Izin'
                ? 'warning'
                : 'danger';

        return `<span class="badge bg-label-${css}">${escapeHtml(status)}</span>`;
    }

    function render(rows) {
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
                    <div class="fw-semibold">${escapeHtml(row.nama_mapel || '-')}</div>
                    <small class="text-muted">${escapeHtml(row.kode_mapel || '')}</small>
                </td>
                <td>
                    <div>${escapeHtml(row.jam_mulai || '')} - ${escapeHtml(row.jam_selesai || '')}</div>
                    <small class="text-muted">${escapeHtml(row.sesi || '-')}</small>
                </td>
                <td>${badge(row.status || '-')}</td>
                <td style="min-width: 260px;">${escapeHtml(row.materi || '-')}</td>
            </tr>
        `).join('');
    }

    function updateMeta() {
        const start = total === 0 ? 0 : offset + 1;
        const end = Math.min(offset + limit, total);

        meta.textContent = `Menampilkan ${start}-${end} dari ${total} data.`;
        btnPrev.disabled = offset <= 0;
        btnNext.disabled = offset + limit >= total;
    }

    async function load(resetOffset = false) {
        if (resetOffset) {
            offset = 0;
        }

        const mulai = inputMulai.value;
        const selesai = inputSelesai.value;
        const status = inputStatus.value;

        if (!mulai || !selesai) {
            showInfo('Tanggal mulai dan selesai wajib diisi.', 'warning');
            return;
        }

        btnMuat.disabled = true;
        showInfo('Memuat histori Jurnal...', 'info');

        const params = new URLSearchParams({
            format: 'json',
            tanggal_mulai: mulai,
            tanggal_selesai: selesai,
            limit: String(limit),
            offset: String(offset)
        });

        if (status) {
            params.set('status', status);
        }

        try {
            const response = await fetch(
                url(`presensi/mengajar/laporan?${params.toString()}`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const json = await response.json();

            if (!response.ok || json.status !== 'success') {
                showInfo(json.message || 'Gagal memuat histori Jurnal.', 'danger');
                render([]);
                total = 0;
                updateMeta();
                return;
            }

            hideInfo();

            const data = json.data || {};
            total = Number(data.total || 0);
            render(data.rows || []);
            updateMeta();
        } catch (error) {
            showInfo('Terjadi kesalahan saat memuat histori Jurnal.', 'danger');
            render([]);
        } finally {
            btnMuat.disabled = false;
        }
    }

    btnMuat?.addEventListener('click', () => load(true));

    btnPrev?.addEventListener('click', () => {
        offset = Math.max(0, offset - limit);
        load(false);
    });

    btnNext?.addEventListener('click', () => {
        if (offset + limit < total) {
            offset += limit;
            load(false);
        }
    });

    load(true);
})();
