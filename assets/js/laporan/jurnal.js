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
    const prev = document.getElementById('btnJurnalPrev');
    const next = document.getElementById('btnJurnalNext');
    const body = document.getElementById('jurnalBody');
    const info = document.getElementById('jurnalPageInfo');
    const alertBox = document.getElementById('jurnalAlert');

    const fixedGuru = Number(options.dataset.fixedGuru || 0);
    const limit = 50;
    let offset = 0;
    let total = 0;

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function params(includePaging = true) {
        const p = new URLSearchParams({
            id_tahun: tahun.value,
            tanggal_mulai: mulai.value,
            tanggal_selesai: selesai.value,
            format: 'json'
        });

        const idGuru = fixedGuru || Number(guru?.value || 0);
        if (idGuru) p.set('id_guru', String(idGuru));
        if (kelas.value) p.set('id_kelas', kelas.value);
        if (hari.value) p.set('hari', hari.value);
        if (status.value) p.set('status', status.value);

        if (includePaging) {
            p.set('limit', String(limit));
            p.set('offset', String(offset));
        }

        return p;
    }

    function show(message, type = 'info') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
    }

    function hide() {
        alertBox.classList.add('d-none');
    }

    async function load() {
        btnCari.disabled = true;
        show('Memuat Laporan Jurnal...', 'info');

        try {
            const res = await fetch(endpoint(`laporan/jurnal?${params().toString()}`), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const data = json.data || {};

            if (!res.ok || json.status !== 'success') {
                show(json.message || 'Laporan Jurnal gagal dimuat.', 'danger');
                return;
            }

            hide();
            total = Number(data.total || 0);
            render(data.rows || []);
            updatePager();
        } catch (_) {
            show('Terjadi kesalahan saat memuat Laporan Jurnal.', 'danger');
        } finally {
            btnCari.disabled = false;
        }
    }

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada Jurnal sesuai filter.</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.tanggal)}</td>
                <td>${escapeHtml(row.hari)}<br><small class="text-muted">${escapeHtml(row.jam_mulai)} - ${escapeHtml(row.jam_selesai)}</small></td>
                <td>${escapeHtml(row.nama_guru_snapshot)}<br><small class="text-muted">${escapeHtml(row.nip || '-')}</small></td>
                <td>${escapeHtml(row.nama_kelas || '-')}</td>
                <td>${escapeHtml(row.kode_mapel || '-')} — ${escapeHtml(row.nama_mapel || '-')}</td>
                <td>${escapeHtml(row.sesi)}</td>
                <td>${escapeHtml(row.status)}</td>
                <td style="min-width:260px">${escapeHtml(row.materi)}</td>
            </tr>
        `).join('');
    }

    function updatePager() {
        const start = total === 0 ? 0 : offset + 1;
        const end = Math.min(offset + limit, total);
        info.textContent = `${start}-${end} dari ${total} data`;
        prev.disabled = offset <= 0;
        next.disabled = offset + limit >= total;
    }

    tahun?.addEventListener('change', () => {
        const p = new URLSearchParams({
            id_tahun: tahun.value,
            tanggal_mulai: mulai.value,
            tanggal_selesai: selesai.value
        });

        window.location.href = endpoint(`laporan/jurnal?${p.toString()}`);
    });

    btnCari?.addEventListener('click', () => {
        offset = 0;
        load();
    });

    prev?.addEventListener('click', () => {
        offset = Math.max(0, offset - limit);
        load();
    });

    next?.addEventListener('click', () => {
        if (offset + limit < total) {
            offset += limit;
            load();
        }
    });

    btnExport?.addEventListener('click', () => {
        const p = params(false);
        p.delete('format');
        window.location.href = endpoint(`laporan/jurnal/export?${p.toString()}`);
    });

    load();
})();
