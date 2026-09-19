(() => {
    'use strict';

    const app = document.getElementById('signageApp');
    if (!app) return;

    const dataUrl = String(app.dataset.dataUrl || '');
    const refreshMinutes = Math.max(1, Number(app.dataset.refreshMinutes || 5));
    const rotationSeconds = Math.max(5, Number(app.dataset.rotationSeconds || 15));
    const rankingDays = Math.max(1, Number(app.dataset.rankingDays || 14));
    const refreshMs = refreshMinutes * 60 * 1000;
    const rotationMs = rotationSeconds * 1000;

    const statusElement = document.getElementById('signageStatus');
    const ewsBody = document.getElementById('ewsBody');
    const kelasBody = document.getElementById('kelasBody');
    const jurnalBody = document.getElementById('jurnalBody');

    const ewsCategories = [
        { key: 'top_sakit', label: 'Sakit', css: 'sakit' },
        { key: 'top_izin', label: 'Izin', css: 'izin' },
        { key: 'top_alpha', label: 'Alpha', css: 'alpha' },
    ];

    let currentData = null;
    let ewsFrames = [];
    let kelasPages = [];
    let jurnalPages = [];
    let ewsIndex = 0;
    let kelasIndex = 0;
    let jurnalIndex = 0;

    const esc = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    function pageSize() {
        if (window.innerHeight >= 950) return 12;
        if (window.innerHeight >= 780) return 9;
        if (window.innerHeight >= 650) return 7;
        return 5;
    }

    function chunk(rows, size) {
        const safeRows = Array.isArray(rows) ? rows : [];
        if (!safeRows.length) return [[]];

        const pages = [];
        for (let i = 0; i < safeRows.length; i += size) {
            pages.push(safeRows.slice(i, i + size));
        }
        return pages;
    }

    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('signageClock');
        const date = document.getElementById('signageDate');

        if (clock) {
            clock.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
            });
        }
        if (date) {
            date.textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
            });
        }
    }

    function formatTime(value) {
        if (!value) return '-';
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return String(value);
        return parsed.toLocaleTimeString('id-ID', {
            hour: '2-digit', minute: '2-digit', hour12: false,
        });
    }

    function formatShortDate(value) {
        if (!value) return '-';
        const parsed = new Date(`${value}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) return String(value);
        return parsed.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
    }

    function renderSummary(summary) {
        const counts = summary?.counts || {};
        const percent = summary?.percent || {};
        ['Hadir', 'Sakit', 'Izin', 'Alpha'].forEach((status) => {
            const countEl = document.getElementById(`summary${status}Count`);
            const percentEl = document.getElementById(`summary${status}Percent`);
            if (countEl) countEl.textContent = String(Number(counts[status] || 0));
            if (percentEl) percentEl.textContent = `${Number(percent[status] || 0).toFixed(1)}%`;
        });

        const coverage = summary?.coverage || {};
        const coverageEl = document.getElementById('coverageText');
        if (coverageEl) {
            coverageEl.textContent =
                `Coverage kelas: ${Number(coverage.sudah_kelas || 0)} / ${Number(coverage.wajib_kelas || 0)}`
                + (Number(coverage.belum_kelas || 0) > 0
                    ? ` • belum ${Number(coverage.belum_kelas || 0)}`
                    : '');
        }
    }

    function buildFrames() {
        const size = pageSize();
        ewsFrames = [];

        ewsCategories.forEach((category) => {
            const pages = chunk(currentData?.[category.key], size);
            pages.forEach((rows, page) => {
                ewsFrames.push({
                    category,
                    rows,
                    page,
                    pageCount: pages.length,
                });
            });
        });

        kelasPages = chunk(currentData?.kelas_belum_presensi, size);
        jurnalPages = chunk(currentData?.jadwal_belum_jurnal, size);
        ewsIndex %= Math.max(1, ewsFrames.length);
        kelasIndex %= Math.max(1, kelasPages.length);
        jurnalIndex %= Math.max(1, jurnalPages.length);
    }

    function renderEws() {
        const frame = ewsFrames[ewsIndex] || {
            category: ewsCategories[0],
            rows: [],
            page: 0,
            pageCount: 1,
        };
        const { category, rows } = frame;
        const period = currentData?.ranking_period;
        const subtitle = document.getElementById('ewsSubtitle');
        const totalHeader = document.getElementById('ewsTotalHeader');
        const label = document.getElementById('ewsPageLabel');

        if (subtitle) {
            subtitle.textContent = period?.mulai && period?.selesai
                ? `${category.label} tertinggi • ${formatShortDate(period.mulai)}–${formatShortDate(period.selesai)}`
                : `${category.label} tertinggi • ${rankingDays} hari`;
        }
        if (totalHeader) totalHeader.textContent = `Total ${category.label}`;
        if (label) {
            label.className = `panel-page panel-page--${category.css}`;
            label.textContent = `${category.label} • ${frame.page + 1}/${frame.pageCount}`;
        }

        if (!rows.length) {
            ewsBody.innerHTML = `<tr><td colspan="4" class="empty">Belum ada data ${esc(category.label)} pada periode ini.</td></tr>`;
            return;
        }

        const start = frame.page * pageSize();
        ewsBody.innerHTML = rows.map((row, idx) => `
            <tr>
                <td class="no">${start + idx + 1}</td>
                <td class="strong">${esc(row.nama_siswa || '-')}</td>
                <td>${esc(row.nama_kelas || '-')}</td>
                <td class="value">${Number(row.total || 0)}</td>
            </tr>
        `).join('');
    }

    function renderKelas() {
        const pages = kelasPages.length ? kelasPages : [[]];
        const rows = pages[kelasIndex] || [];
        const label = document.getElementById('kelasPageLabel');
        if (label) label.textContent = `${kelasIndex + 1}/${pages.length}`;

        if (!rows.length) {
            kelasBody.innerHTML = '<tr><td colspan="3" class="empty ok">Seluruh kelas wajib sudah melakukan Presensi Sesi Awal.</td></tr>';
            return;
        }

        const start = kelasIndex * pageSize();
        kelasBody.innerHTML = rows.map((row, idx) => `
            <tr>
                <td class="no">${start + idx + 1}</td>
                <td class="strong">${esc(row.nama_kelas || '-')}</td>
                <td>${esc(row.wali_kelas || '-')}</td>
            </tr>
        `).join('');
    }

    function renderJurnal() {
        const pages = jurnalPages.length ? jurnalPages : [[]];
        const rows = pages[jurnalIndex] || [];
        const label = document.getElementById('jurnalPageLabel');
        if (label) label.textContent = `${jurnalIndex + 1}/${pages.length}`;

        if (!rows.length) {
            jurnalBody.innerHTML = '<tr><td colspan="5" class="empty ok">Tidak ada jadwal yang melewati batas input dan belum memiliki jurnal.</td></tr>';
            return;
        }

        const start = jurnalIndex * pageSize();
        jurnalBody.innerHTML = rows.map((row, idx) => `
            <tr>
                <td class="no">${start + idx + 1}</td>
                <td class="strong">${esc(row.nama_guru || '-')}</td>
                <td>${esc(row.nama_kelas || '-')}</td>
                <td>${esc(row.nama_mapel || '-')}</td>
                <td class="jam">${esc(row.jam_mulai || '-')}–${esc(row.jam_selesai || '-')}</td>
            </tr>
        `).join('');
    }

    function renderAll() {
        if (!currentData) return;
        renderSummary(currentData.attendance_summary || {});
        renderEws();
        renderKelas();
        renderJurnal();
    }

    function rotate() {
        if (!currentData) return;
        if (ewsFrames.length > 1) ewsIndex = (ewsIndex + 1) % ewsFrames.length;
        if (kelasPages.length > 1) kelasIndex = (kelasIndex + 1) % kelasPages.length;
        if (jurnalPages.length > 1) jurnalIndex = (jurnalIndex + 1) % jurnalPages.length;
        renderEws();
        renderKelas();
        renderJurnal();
    }

    async function loadData() {
        if (!dataUrl) {
            statusElement.textContent = 'URL data signage tidak tersedia.';
            statusElement.classList.add('is-error');
            return;
        }

        statusElement.textContent = 'Memperbarui data...';
        statusElement.classList.remove('is-error');

        try {
            const response = await fetch(`${dataUrl}?_=${Date.now()}`, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json().catch(() => null);
            if (!payload || !response.ok || payload.status !== 'success') {
                throw new Error(payload?.message || 'Response server tidak valid.');
            }

            currentData = payload.data || {};
            const tahun = document.getElementById('signageTahun');
            const updated = document.getElementById('signageLastUpdate');
            if (tahun) tahun.textContent = currentData.tahun_ajaran || 'Tahun Ajaran aktif belum tersedia';
            if (updated) updated.textContent = formatTime(currentData.generated_at);

            buildFrames();
            renderAll();
            statusElement.textContent = `Data aktif • ${Number(currentData.attendance_summary?.total_recorded || 0)} siswa tercatat`;
        } catch (error) {
            statusElement.textContent = `Gagal memperbarui: ${error?.message || 'error'}`;
            statusElement.classList.add('is-error');
        }
    }

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            if (!currentData) return;
            buildFrames();
            renderAll();
        }, 200);
    });

    updateClock();
    window.setInterval(updateClock, 1000);
    loadData();
    window.setInterval(loadData, refreshMs);
    window.setInterval(rotate, rotationMs);
})();
