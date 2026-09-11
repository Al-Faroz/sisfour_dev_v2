(() => {
    'use strict';

    const app = document.getElementById('signageApp');

    if (!app) {
        return;
    }

    const dataUrl = String(app.dataset.dataUrl || '');
    const refreshMinutes = Math.max(
        1,
        Number(app.dataset.refreshMinutes || 5)
    );
    const rotationSeconds = Math.max(
        5,
        Number(app.dataset.rotationSeconds || 15)
    );
    const rankingDays = Math.max(
        1,
        Number(app.dataset.rankingDays || 14)
    );

    const refreshMs = refreshMinutes * 60 * 1000;
    const rotationMs = rotationSeconds * 1000;

    const statusElement = document.getElementById('signageStatus');
    const tableHead = document.getElementById('signageTableHead');
    const tableBody = document.getElementById('signageTableBody');
    const tableScroll = document.getElementById('signageTableScroll');
    const slideTitle = document.getElementById('signageSlideTitle');
    const slideSubtitle = document.getElementById('signageSlideSubtitle');
    const slideKicker = document.getElementById('signageSlideKicker');
    const slideCount = document.getElementById('signageSlideCount');
    const dots = Array.from(
        document.querySelectorAll('#signageSlideDots .signage-dot')
    );

    const slides = [
        {
            key: 'top_alpha',
            title: '20 Siswa Alpha Tertinggi',
            kicker: 'EARLY WARNING • ALPHA',
            totalLabel: 'Total Alpha',
            empty: 'Belum ada data Alpha pada periode ini.',
            type: 'ranking',
        },
        {
            key: 'top_izin',
            title: '20 Siswa Izin Tertinggi',
            kicker: 'EARLY WARNING • IZIN',
            totalLabel: 'Total Izin',
            empty: 'Belum ada data Izin pada periode ini.',
            type: 'ranking',
        },
        {
            key: 'top_sakit',
            title: '20 Siswa Sakit Tertinggi',
            kicker: 'EARLY WARNING • SAKIT',
            totalLabel: 'Total Sakit',
            empty: 'Belum ada data Sakit pada periode ini.',
            type: 'ranking',
        },
        {
            key: 'tidak_masuk_hari_ini',
            title: 'Tidak Masuk Hari Ini',
            kicker: 'MONITORING HARI INI • SESI AWAL',
            totalLabel: 'Status',
            empty: 'Seluruh siswa yang sudah dipresensi tercatat hadir.',
            type: 'today',
        },
    ];

    let currentData = null;
    let currentSlide = 0;
    let scrollFrame = 0;

    const esc = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('signageClock');
        const date = document.getElementById('signageDate');

        if (clock) {
            clock.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        }

        if (date) {
            date.textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            });
        }
    }

    function formatDateTime(value) {
        if (!value) {
            return '-';
        }

        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }

    function formatShortDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(`${value}T00:00:00`);

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
        });
    }

    function statusBadge(value) {
        const normalized = String(value || '');
        const className = normalized === 'Alpha'
            ? 'status-alpha'
            : normalized === 'Izin'
                ? 'status-izin'
                : 'status-sakit';

        return `<span class="signage-status-badge ${className}">${esc(normalized || '-')}</span>`;
    }

    function rankingSubtitle() {
        const period = currentData?.ranking_period;

        if (!period?.mulai || !period?.selesai) {
            return `Sesi Awal • ${rankingDays} hari terakhir`;
        }

        return `Sesi Awal • ${formatShortDate(period.mulai)}–${formatShortDate(period.selesai)}`;
    }

    function renderRanking(rows, slide) {
        tableHead.innerHTML = `
            <tr>
                <th class="col-no">No</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th class="col-center">${esc(slide.totalLabel)}</th>
            </tr>
        `;

        tableBody.innerHTML = rows.length
            ? rows.map((row, index) => `
                <tr>
                    <td class="col-no-value">${index + 1}</td>
                    <td class="student-name">${esc(row.nama_siswa || '-')}</td>
                    <td>${esc(row.nama_kelas || '-')}</td>
                    <td class="col-center total-value">${Number(row.total || 0)}</td>
                </tr>
            `).join('')
            : `
                <tr>
                    <td colspan="4" class="empty ok">
                        ${esc(slide.empty)}
                    </td>
                </tr>
            `;
    }

    function renderToday(rows, slide) {
        tableHead.innerHTML = `
            <tr>
                <th class="col-no">No</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th class="col-center">Status</th>
            </tr>
        `;

        tableBody.innerHTML = rows.length
            ? rows.map((row, index) => `
                <tr>
                    <td class="col-no-value">${index + 1}</td>
                    <td class="student-name">${esc(row.nama_siswa || '-')}</td>
                    <td>${esc(row.nama_kelas || '-')}</td>
                    <td class="col-center">${statusBadge(row.status)}</td>
                </tr>
            `).join('')
            : `
                <tr>
                    <td colspan="4" class="empty ok">
                        ${esc(slide.empty)}
                    </td>
                </tr>
            `;
    }

    function updateDots() {
        dots.forEach((dot, index) => {
            dot.classList.toggle('is-active', index === currentSlide);
        });
    }

    function restartAutoScroll() {
        if (scrollFrame) {
            cancelAnimationFrame(scrollFrame);
            scrollFrame = 0;
        }

        if (!tableScroll) {
            return;
        }

        tableScroll.scrollTop = 0;

        let last = performance.now();
        let pauseUntil = last + 1800;

        const frame = (now) => {
            const delta = Math.min(80, now - last);
            last = now;

            if (tableScroll.scrollHeight > tableScroll.clientHeight + 4) {
                if (now >= pauseUntil) {
                    tableScroll.scrollTop += delta * 0.022;

                    if (
                        tableScroll.scrollTop + tableScroll.clientHeight
                        >= tableScroll.scrollHeight - 2
                    ) {
                        tableScroll.scrollTop = 0;
                        pauseUntil = now + 2200;
                    }
                }
            } else {
                tableScroll.scrollTop = 0;
            }

            scrollFrame = requestAnimationFrame(frame);
        };

        scrollFrame = requestAnimationFrame(frame);
    }

    function renderSlide(index) {
        if (!currentData || !slides[index]) {
            return;
        }

        currentSlide = index;
        const slide = slides[index];
        const rows = Array.isArray(currentData[slide.key])
            ? currentData[slide.key]
            : [];

        slideKicker.textContent = slide.kicker;
        slideTitle.textContent = slide.title;
        slideSubtitle.textContent = slide.type === 'today'
            ? 'Sakit • Izin • Alpha pada Sesi Awal hari ini'
            : rankingSubtitle();
        slideCount.textContent = String(rows.length);

        if (slide.type === 'today') {
            renderToday(rows, slide);
        } else {
            renderRanking(rows, slide);
        }

        updateDots();
        restartAutoScroll();
    }

    function renderSummary(summary) {
        const data = summary || {};
        const sakit = Number(data.Sakit || 0);
        const izin = Number(data.Izin || 0);
        const alpha = Number(data.Alpha || 0);
        const total = Number(data.total || sakit + izin + alpha);

        document.getElementById('todaySakit').textContent = String(sakit);
        document.getElementById('todayIzin').textContent = String(izin);
        document.getElementById('todayAlpha').textContent = String(alpha);
        document.getElementById('todayTotal').textContent = `${total} siswa tidak masuk`;
    }

    async function readJsonResponse(response) {
        const payload = await response.json().catch(() => null);

        if (!payload || typeof payload !== 'object') {
            throw new Error('Response server tidak valid.');
        }

        if (!response.ok || payload.status !== 'success') {
            throw new Error(payload.message || 'Data gagal dimuat.');
        }

        return payload;
    }

    async function loadData() {
        if (!dataUrl) {
            statusElement.textContent = 'URL data signage tidak tersedia.';
            return;
        }

        statusElement.textContent = 'Memperbarui data...';
        statusElement.classList.remove('is-error');

        try {
            const response = await fetch(`${dataUrl}?_=${Date.now()}`, {
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await readJsonResponse(response);
            currentData = payload.data || {};

            document.getElementById('signageTahun').textContent =
                currentData.tahun_ajaran || 'Tahun Ajaran aktif belum tersedia';
            document.getElementById('signageLastUpdate').textContent =
                formatDateTime(currentData.generated_at);

            renderSummary(currentData.today_summary);
            renderSlide(currentSlide);

            statusElement.textContent = `Data aktif • refresh ${refreshMinutes} menit`;
        } catch (error) {
            statusElement.textContent = `Gagal memperbarui: ${error.message || 'error'}`;
            statusElement.classList.add('is-error');
        }
    }

    function nextSlide() {
        if (!currentData) {
            return;
        }

        renderSlide((currentSlide + 1) % slides.length);
    }

    updateClock();
    window.setInterval(updateClock, 1000);
    loadData();
    window.setInterval(loadData, refreshMs);
    window.setInterval(nextSlide, rotationMs);
})();
