(() => {
    'use strict';

    const app = document.getElementById('laporanMatrixApp');
    if (!app) return;

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const tahun = document.getElementById('matrixTahun');
    const kelas = document.getElementById('matrixKelas');
    const bulan = document.getElementById('matrixBulan');
    const btn = document.getElementById('btnMatrixMuat');
    const alertBox = document.getElementById('matrixAlert');
    const head = document.getElementById('matrixHead');
    const body = document.getElementById('matrixBody');
    const title = document.getElementById('matrixTitle');

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    function show(message, type = 'info') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
    }

    function hide() {
        alertBox.classList.add('d-none');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    async function refreshKelas() {
        const params = new URLSearchParams({
            format: 'json',
            id_tahun: tahun.value
        });

        try {
            const res = await fetch(endpoint(`laporan/presensi/matrix?${params.toString()}`), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const data = json.data || {};

            if (!res.ok || json.status !== 'success') {
                show(json.message || 'Gagal memuat kelas.', 'danger');
                return;
            }

            kelas.innerHTML = '<option value="">Pilih Kelas</option>';
            (data.kelas || []).forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.nama_kelas;
                kelas.appendChild(option);
            });
        } catch (_) {
            show('Gagal memuat pilihan kelas.', 'danger');
        }
    }

    async function loadMatrix() {
        if (!tahun.value || !kelas.value || !bulan.value) {
            show('Tahun Ajaran, Kelas, dan Bulan wajib dipilih.', 'warning');
            return;
        }

        btn.disabled = true;
        show('Memuat Matrix...', 'info');

        const params = new URLSearchParams({
            format: 'json',
            id_tahun: tahun.value,
            id_kelas: kelas.value,
            bulan: bulan.value
        });

        try {
            const res = await fetch(endpoint(`laporan/presensi/matrix?${params.toString()}`), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const data = json.data || {};

            if (!res.ok || json.status !== 'success') {
                show(json.message || 'Matrix gagal dimuat.', 'danger');
                return;
            }

            hide();
            render(data);
        } catch (_) {
            show('Terjadi kesalahan saat memuat Matrix.', 'danger');
        } finally {
            btn.disabled = false;
        }
    }

    function render(data) {
        const days = Number(data.period?.days || 0);
        title.textContent = `Matrix Presensi ${data.kelas?.nama_kelas || ''} — ${data.bulan || ''}`;

        let header = '<tr><th>No</th><th>NISN</th><th>Nama Siswa</th><th>H</th><th>S</th><th>I</th><th>A</th>';
        for (let d = 1; d <= days; d++) {
            header += `<th>${String(d).padStart(2, '0')}</th>`;
        }
        header += '</tr>';
        head.innerHTML = header;

        const rows = data.rows || [];
        if (!rows.length) {
            body.innerHTML = `<tr><td colspan="${7 + days}" class="text-center text-muted py-4">Tidak ada membership siswa pada periode ini.</td></tr>`;
            return;
        }

        body.innerHTML = rows.map((row, index) => {
            let html = `<tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(row.nisn)}</td>
                <td>${escapeHtml(row.nama)}</td>
                <td>${row.H}</td><td>${row.S}</td><td>${row.I}</td><td>${row.A}</td>`;
            for (let d = 1; d <= days; d++) {
                html += `<td class="text-center">${escapeHtml(row.days?.[d] ?? '-')}</td>`;
            }
            return html + '</tr>';
        }).join('');
    }

    tahun?.addEventListener('change', refreshKelas);
    btn?.addEventListener('click', loadMatrix);

    if (kelas?.value) {
        loadMatrix();
    }
})();
