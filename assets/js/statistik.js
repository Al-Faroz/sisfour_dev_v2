(() => {
    'use strict';

    const app = document.getElementById('statistikApp');
    if (!app || typeof ApexCharts === 'undefined') return;

    const form = document.getElementById('statistikFilter');
    const dataUrl = String(app.dataset.dataUrl || '');
    const exportUrl = String(app.dataset.exportUrl || '');
    const statusEl = document.getElementById('statistikStatus');
    const exportEl = document.getElementById('statistikExportPdf');
    const charts = {};
    let current = {};

    const initialEl = document.getElementById('statistikInitialData');
    try {
        current = JSON.parse(initialEl?.textContent || '{}');
    } catch (_) {
        current = {};
    }

    const number = (value) => Number(value || 0);
    const labels = (rows) => (Array.isArray(rows) ? rows : []).map((row) => String(row.label ?? '-'));
    const totals = (rows) => (Array.isArray(rows) ? rows : []).map((row) => number(row.total));

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function renderChart(id, options) {
        const el = document.getElementById(id);
        if (!el) return;
        destroyChart(id);
        el.innerHTML = '';
        charts[id] = new ApexCharts(el, {
            chart: {
                toolbar: { show: false },
                animations: { enabled: false },
                fontFamily: 'inherit',
                height: options.height || 285,
                type: options.type || 'bar',
            },
            noData: { text: 'Belum ada data' },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' },
            ...options,
        });
        charts[id].render();
    }

    function barRows(id, rows, horizontal = false, suffix = '') {
        renderChart(id, {
            type: 'bar',
            series: [{ name: 'Total', data: totals(rows) }],
            xaxis: { categories: labels(rows) },
            plotOptions: { bar: { horizontal, borderRadius: 4 } },
            tooltip: { y: { formatter: (value) => `${value}${suffix}` } },
        });
    }

    function donutRows(id, rows) {
        renderChart(id, {
            type: 'donut',
            series: totals(rows),
            labels: labels(rows),
            stroke: { width: 1 },
        });
    }

    function lineRows(id, rows, seriesDefs) {
        const safe = Array.isArray(rows) ? rows : [];
        renderChart(id, {
            type: 'line',
            series: seriesDefs.map((def) => ({
                name: def.label,
                data: safe.map((row) => number(row[def.key])),
            })),
            xaxis: { categories: safe.map((row) => String(row.tanggal || '-')) },
            stroke: { curve: 'smooth', width: 2 },
            markers: { size: 2 },
        });
    }

    function rankingChart(id, rows) {
        const safe = Array.isArray(rows) ? rows : [];
        renderChart(id, {
            type: 'bar',
            height: 300,
            series: [{ name: 'Total', data: safe.map((row) => number(row.total)) }],
            xaxis: { categories: safe.map((row) => String(row.nama_siswa || '-')) },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
        });
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function render(payload) {
        current = payload || {};
        const data = current.data || {};
        const meta = data.meta || {};
        const executive = data.executive || {};
        const composition = data.composition || {};
        const attendance = data.attendance || {};
        const ews = data.ews || {};
        const teaching = data.teaching || {};
        const discipline = data.discipline || {};
        const achievement = data.achievement || {};
        const uks = data.uks || {};
        const ptsp = data.ptsp || {};
        const mobility = data.mobility || {};

        document.querySelectorAll('[data-kpi]').forEach((el) => {
            el.textContent = String(number(executive[el.dataset.kpi]));
        });

        setText('statistikTahunLabel', meta.tahun_label || '-');
        setText('statistikPeriodeLabel', meta.periode_label || '-');
        setText('statistikKelasLabel', meta.kelas_label || '-');

        barRows('chartLevel', composition.by_level || []);
        donutRows('chartGender', composition.by_gender || []);

        const attendanceSummary = attendance.summary || {};
        donutRows('chartAttendanceSummary', ['Hadir','Sakit','Izin','Alpha'].map((key) => ({
            label: key,
            total: number(attendanceSummary[key]),
        })));
        lineRows('chartAttendanceTrend', attendance.trend || [], [
            { key: 'Hadir', label: 'Hadir' },
            { key: 'Sakit', label: 'Sakit' },
            { key: 'Izin', label: 'Izin' },
            { key: 'Alpha', label: 'Alpha' },
        ]);
        const classRows = (attendance.by_class || []).map((row) => ({
            label: row.label,
            total: number(row.persen_hadir),
        }));
        barRows('chartAttendanceClass', classRows, true, '%');

        const windowInfo = ews.window || {};
        setText(
            'ewsWindowLabel',
            windowInfo.mulai && windowInfo.selesai
                ? `Jendela EWS: ${windowInfo.mulai} s.d. ${windowInfo.selesai}`
                : ''
        );
        setText('ewsAlphaCount', `${number(ews.ews_alpha_count)} EWS`);
        rankingChart('chartEwsSakit', ews.top_sakit || []);
        rankingChart('chartEwsIzin', ews.top_izin || []);
        rankingChart('chartEwsAlpha', ews.top_alpha || []);

        const teachingToday = teaching.today || {};
        const teachingApplicable = teachingToday.applicable !== false;
        document.querySelectorAll('[data-teaching]').forEach((el) => {
            el.textContent = teachingApplicable
                ? String(number(teachingToday[el.dataset.teaching]))
                : '—';
        });
        setText(
            'teachingTodayContext',
            teachingApplicable ? String(teachingToday.tanggal || '') : 'hanya Tahun aktif'
        );
        donutRows('chartTeachingStatus', teaching.status_distribution || []);
        lineRows('chartTeachingTrend', teaching.trend || [], [
            { key: 'total', label: 'Jurnal/Presensi Mengajar' },
        ]);

        setText('disciplineTotal', String(number(discipline.total)));
        donutRows('chartDiscipline', discipline.categories || []);
        lineRows('chartDisciplineTrend', discipline.trend || [], [
            { key: 'total', label: 'Catatan' },
        ]);

        setText('achievementTotal', String(number(achievement.total)));
        barRows('chartAchievement', achievement.levels || []);
        lineRows('chartAchievementTrend', achievement.trend || [], [
            { key: 'total', label: 'Prestasi' },
        ]);

        setText('uksKunjungan', String(number(uks.kunjungan)));
        setText('uksCkg', String(number(uks.ckg)));
        setText('uksRujukan', String(number(uks.rujukan_klinik)));
        donutRows('chartUks', uks.hasil || []);

        barRows('chartPtspLayanan', ptsp.layanan_status || []);
        barRows('chartPtspPengaduan', ptsp.pengaduan_status || []);
        donutRows('chartPtspKlasifikasi', ptsp.pengaduan_klasifikasi || []);
        const poll = ptsp.polling || {};
        setText('ptspAvgScore', poll.avg_score ?? '—');
        setText('ptspPollingTotal', String(number(poll.total)));
        barRows(
            'chartPtspPolling',
            (poll.scores || []).map((row) => ({
                label: `Skor ${row.label}`,
                total: row.total,
            }))
        );

        donutRows('chartMobility', mobility.status || []);
    }

    function queryFromForm() {
        const params = new URLSearchParams(new FormData(form));
        for (const [key, value] of Array.from(params.entries())) {
            if (String(value).trim() === '') params.delete(key);
        }
        return params;
    }

    function updateExport(params) {
        if (!exportEl) return;
        exportEl.href = `${exportUrl}?${params.toString()}`;
    }

    function collectChartSvgs() {
        const result = {};
        Object.keys(charts).forEach((id) => {
            const svg = document.getElementById(id)?.querySelector('svg');
            if (svg) result[id] = svg.outerHTML;
        });
        return result;
    }

    function prepareExportForm(params) {
        const exportForm = document.getElementById('statistikExportForm');
        const chartInput = document.getElementById('statistikChartSvgs');
        if (!exportForm || !chartInput) return null;

        exportForm.querySelectorAll('.statistik-export-filter').forEach((el) => el.remove());
        params.forEach((value, key) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            input.className = 'statistik-export-filter';
            exportForm.appendChild(input);
        });
        chartInput.value = JSON.stringify(collectChartSvgs());
        return exportForm;
    }

    function toggleCustomDate() {
        const custom = document.getElementById('filterPeriode')?.value === 'custom';
        document.querySelectorAll('.statistik-custom-date').forEach((el) => {
            el.classList.toggle('d-none', !custom);
        });
    }

    function filterClasses() {
        const level = String(document.getElementById('filterTingkat')?.value || '');
        const select = document.getElementById('filterKelas');
        if (!select) return;

        let selectedVisible = true;
        Array.from(select.options).forEach((option, index) => {
            if (index === 0) return;
            const visible = !level || String(option.dataset.tingkat || '') === level;
            option.hidden = !visible;
            if (option.selected && !visible) selectedVisible = false;
        });
        if (!selectedVisible) select.value = '';
    }

    async function load() {
        const params = queryFromForm();
        statusEl.textContent = 'Memuat...';
        const button = document.getElementById('statistikApply');
        if (button) button.disabled = true;

        try {
            const response = await fetch(`${dataUrl}?${params.toString()}`, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json().catch(() => null);
            if (!payload || !response.ok || payload.status !== 'success') {
                throw new Error(payload?.message || payload?.data?.message || 'Data Statistik gagal dimuat.');
            }

            render(payload.data);
            updateExport(params);
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
            statusEl.textContent = 'Data diperbarui.';
        } catch (error) {
            statusEl.textContent = error?.message || 'Gagal memuat data.';
        } finally {
            if (button) button.disabled = false;
        }
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        load();
    });
    document.getElementById('filterPeriode')?.addEventListener('change', toggleCustomDate);
    document.getElementById('filterTingkat')?.addEventListener('change', filterClasses);
    document.getElementById('filterTahun')?.addEventListener('change', () => {
        // Kelas adalah period-bound. Reload server agar option kelas berasal dari Tahun terpilih.
        const params = queryFromForm();
        params.delete('id_kelas');
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    });

    exportEl?.addEventListener('click', (event) => {
        event.preventDefault();
        const params = queryFromForm();
        const exportForm = prepareExportForm(params);
        if (exportForm) {
            exportForm.submit();
        } else {
            window.location.href = `${exportUrl}?${params.toString()}`;
        }
    });

    toggleCustomDate();
    filterClasses();
    if (current?.success) {
        render(current);
        updateExport(queryFromForm());
    }
})();
